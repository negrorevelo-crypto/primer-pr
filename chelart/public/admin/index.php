<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

$adminUsername = trim(chelart_setting('QUOTE_ADMIN_USERNAME'));
$adminPasswordHash = trim(chelart_setting('QUOTE_ADMIN_PASSWORD_HASH'));
$adminIsConfigured = $adminUsername !== '' && $adminPasswordHash !== '';
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
if (!$isHttps) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('El historial privado solo está disponible por HTTPS.');
}
$sessionKey = 'quote_admin_authenticated';
$idleTimeout = 1800;
$now = time();
$message = '';

if (isset($_SESSION[$sessionKey]) && ($now - (int)($_SESSION['quote_admin_last_activity'] ?? 0)) > $idleTimeout) {
    unset($_SESSION[$sessionKey], $_SESSION['quote_admin_last_activity']);
}

$isAuthenticated = !empty($_SESSION[$sessionKey]);
if ($isAuthenticated) {
    $_SESSION['quote_admin_last_activity'] = $now;
}

if (empty($_SESSION['quote_admin_csrf'])) {
    $_SESSION['quote_admin_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals((string)$_SESSION['quote_admin_csrf'], $postedToken)) {
        http_response_code(400);
        $message = 'La sesión del formulario venció. Recarga e inténtalo de nuevo.';
    } elseif (($_POST['action'] ?? '') === 'logout' && $isAuthenticated) {
        unset($_SESSION[$sessionKey], $_SESSION['quote_admin_last_activity']);
        session_regenerate_id(true);
        $_SESSION['quote_admin_csrf'] = bin2hex(random_bytes(32));
        header('Location: ./', true, 303);
        exit;
    } elseif (($_POST['action'] ?? '') === 'login' && !$isAuthenticated) {
        if (!$adminIsConfigured) {
            http_response_code(503);
            $message = 'El acceso privado aún no está configurado en Plesk.';
        } else {
            $attempts = array_values(array_filter(
                (array)($_SESSION['quote_admin_attempts'] ?? []),
                static fn($time): bool => is_int($time) && $time > $now - 900
            ));
            if (count($attempts) >= 5) {
                http_response_code(429);
                $message = 'Demasiados intentos. Espera 15 minutos antes de volver a intentar.';
            } else {
                $username = trim((string)($_POST['username'] ?? ''));
                $password = (string)($_POST['password'] ?? '');
                $validHash = password_get_info($adminPasswordHash)['algo'] !== null;
                $valid = hash_equals($adminUsername, $username)
                    && $validHash
                    && password_verify($password, $adminPasswordHash);
                if ($valid) {
                    session_regenerate_id(true);
                    $_SESSION[$sessionKey] = true;
                    $_SESSION['quote_admin_last_activity'] = $now;
                    $_SESSION['quote_admin_csrf'] = bin2hex(random_bytes(32));
                    unset($_SESSION['quote_admin_attempts']);
                    header('Location: ' . ((($_GET['next'] ?? '') === 'christmas') ? 'christmas.php' : './'), true, 303);
                    exit;
                }
                $attempts[] = $now;
                $_SESSION['quote_admin_attempts'] = $attempts;
                $message = 'Usuario o contraseña incorrectos.';
            }
        }
    }
}

function admin_h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function admin_date(?string $value): string
{
    if (!$value) {
        return '—';
    }
    try {
        return (new DateTimeImmutable($value, new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('America/New_York'))
            ->format('M j, Y · g:i A T');
    } catch (Throwable) {
        return $value;
    }
}

$requests = [];
$totalRequests = 0;
$page = max(1, min(100000, (int)($_GET['page'] ?? 1)));
$pageSize = 20;
$search = trim((string)($_GET['q'] ?? ''));
$detail = null;
$detailServices = [];
$databaseError = false;

if ($isAuthenticated) {
    require_once dirname(__DIR__, 2) . '/config/database.php';
    try {
        $pdo = database();
        if (isset($_GET['id']) && ctype_digit((string)$_GET['id'])) {
            $detailStatement = $pdo->prepare('SELECT * FROM quote_requests WHERE id = :id LIMIT 1');
            $detailStatement->execute([':id' => (int)$_GET['id']]);
            $detail = $detailStatement->fetch() ?: null;
            if ($detail) {
                $serviceStatement = $pdo->prepare('SELECT service_label FROM quote_request_services WHERE quote_request_id = :id ORDER BY id');
                $serviceStatement->execute([':id' => (int)$detail['id']]);
                $detailServices = $serviceStatement->fetchAll(PDO::FETCH_COLUMN);
            }
        } else {
            $where = '';
            $params = [];
            if ($search !== '') {
                $where = ' WHERE request_code LIKE :search_code OR full_name LIKE :search_name OR email LIKE :search_email OR city LIKE :search_city';
                $searchPattern = '%' . $search . '%';
                $params = [
                    ':search_code' => $searchPattern,
                    ':search_name' => $searchPattern,
                    ':search_email' => $searchPattern,
                    ':search_city' => $searchPattern,
                ];
            }
            $countStatement = $pdo->prepare('SELECT COUNT(*) FROM quote_requests' . $where);
            $countStatement->execute($params);
            $totalRequests = (int)$countStatement->fetchColumn();
            $lastPage = max(1, (int)ceil($totalRequests / $pageSize));
            $page = min($page, $lastPage);
            $offset = ($page - 1) * $pageSize;
            $listStatement = $pdo->prepare(
                'SELECT q.id, q.request_code, q.full_name, q.email, q.phone, q.city, q.postal_code,
                        q.property_type, q.project_timeline, q.preferred_contact, q.status, q.created_at,
                        (SELECT GROUP_CONCAT(s.service_label ORDER BY s.id SEPARATOR ", ")
                         FROM quote_request_services s WHERE s.quote_request_id = q.id) AS services
                 FROM quote_requests q'
                 . ($search !== '' ? ' WHERE q.request_code LIKE :search_code OR q.full_name LIKE :search_name OR q.email LIKE :search_email OR q.city LIKE :search_city' : '') . '
                 ORDER BY q.created_at DESC, q.id DESC
                 LIMIT :limit OFFSET :offset'
            );
            if ($search !== '') {
                $searchPattern = '%' . $search . '%';
                $listStatement->bindValue(':search_code', $searchPattern, PDO::PARAM_STR);
                $listStatement->bindValue(':search_name', $searchPattern, PDO::PARAM_STR);
                $listStatement->bindValue(':search_email', $searchPattern, PDO::PARAM_STR);
                $listStatement->bindValue(':search_city', $searchPattern, PDO::PARAM_STR);
            }
            $listStatement->bindValue(':limit', $pageSize, PDO::PARAM_INT);
            $listStatement->bindValue(':offset', $offset, PDO::PARAM_INT);
            $listStatement->execute();
            $requests = $listStatement->fetchAll();
        }
    } catch (Throwable $error) {
        error_log('Chelart quote history could not be loaded: ' . $error->getMessage());
        $databaseError = true;
    }
}

$lastPage = max(1, (int)ceil($totalRequests / $pageSize));
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow, noarchive">
  <link rel="icon" type="image/png" sizes="32x32" href="../favicon-32.png"><link rel="apple-touch-icon" sizes="180x180" href="../favicon-180.png"><link rel="icon" type="image/png" sizes="512x512" href="../favicon-512.png">
  <title>Solicitudes de cotización | Chelart</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/site.css?v=20261126">
  <style>
    .admin-shell{width:min(1240px,calc(100% - 40px));margin:42px auto 80px}.admin-top{display:flex;justify-content:space-between;align-items:center;gap:24px;margin-bottom:32px}.admin-top h1,.admin-detail h1{font-family:var(--serif);font-size:clamp(36px,5vw,56px);font-weight:500;letter-spacing:-.04em;margin:0}.admin-top p{margin:8px 0 0;color:var(--muted);font-size:13px}.admin-card{background:var(--white);padding:clamp(22px,4vw,40px);box-shadow:0 18px 55px rgba(42,35,28,.07)}.admin-actions{display:flex;align-items:center;gap:12px}.admin-search{display:flex;gap:8px;flex-wrap:wrap}.admin-search input{min-width:min(300px,65vw);min-height:44px;padding:10px 12px;border:1px solid var(--line);background:white}.admin-table-wrap{overflow-x:auto;margin-top:24px}.admin-table{width:100%;border-collapse:collapse;min-width:780px}.admin-table th,.admin-table td{padding:14px 12px;border-bottom:1px solid var(--line);text-align:left;vertical-align:top;font-size:12px;line-height:1.5}.admin-table th{color:var(--muted);font-size:10px;letter-spacing:.12em;text-transform:uppercase}.admin-table td a{color:var(--orange-dark);font-weight:700}.admin-muted{color:var(--muted)}.admin-alert{padding:14px 16px;margin-bottom:18px;border-left:3px solid var(--orange);background:#fff4ed;color:var(--ink);font-size:13px}.admin-empty{padding:35px 0;color:var(--muted)}.admin-pagination{display:flex;justify-content:space-between;align-items:center;margin-top:24px;color:var(--muted);font-size:12px}.admin-detail{display:grid;grid-template-columns:1fr 1fr;gap:12px 22px;margin:28px 0}.admin-detail div{padding:13px 0;border-bottom:1px solid var(--line)}.admin-detail dt{margin-bottom:5px;color:var(--muted);font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}.admin-detail dd{margin:0;white-space:pre-wrap;overflow-wrap:anywhere;font-size:14px;line-height:1.6}.admin-detail .wide{grid-column:1/-1}.admin-login{width:min(520px,calc(100% - 32px));margin:7vh auto;background:var(--white);padding:clamp(26px,6vw,52px);box-shadow:0 18px 55px rgba(42,35,28,.08)}.admin-login h1{font-family:var(--serif);font-size:42px;font-weight:500;margin:0 0 10px}.admin-login>p{color:var(--muted);font-size:13px;line-height:1.6}.admin-login label{display:block;margin:18px 0 7px;font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}.admin-login input{width:100%;min-height:48px;padding:11px 12px;border:1px solid var(--line);background:#fff}.admin-login .button{margin-top:22px;width:100%}.admin-foot{margin-top:28px;color:var(--muted);font-size:11px}.admin-status{display:inline-block;padding:4px 8px;background:#f4ece2;font-size:10px;text-transform:uppercase;letter-spacing:.08em}.admin-count{color:var(--muted);font-size:12px}.admin-link{font-size:11px}
    @media(max-width:700px){.admin-shell{width:calc(100% - 24px);margin:26px auto 48px}.admin-top{align-items:flex-start;flex-direction:column}.admin-actions{width:100%;justify-content:space-between}.admin-detail{grid-template-columns:1fr}.admin-detail .wide{grid-column:auto}.admin-login{margin:4vh auto}}
  </style>
</head>
<body class="quote-page">
  <header class="site-header quote-header">
    <div class="nav-wrap">
      <a class="brand" href="../" aria-label="Inicio de Chelart"><img src="../assets/img/chelart-logo-192.png" alt="Chelart Construction" width="192" height="192" srcset="../assets/img/chelart-logo-192.png 192w" sizes="96px"></a>
      <?php if ($isAuthenticated): ?>
        <div class="admin-actions"><a class="text-link" href="christmas.php">Christmas Service Leads <span aria-hidden="true">→</span></a>
        <form method="post" class="admin-actions"><input type="hidden" name="csrf_token" value="<?= admin_h($_SESSION['quote_admin_csrf']) ?>"><input type="hidden" name="action" value="logout"><button class="button button-quiet" type="submit">Cerrar sesión</button></form></div>
      <?php endif; ?>
    </div>
  </header>

  <?php if (!$isAuthenticated): ?>
    <main class="admin-login">
      <p class="eyebrow">Área privada de Chelart</p>
      <h1>Historial de solicitudes</h1>
      <p>Inicia sesión para consultar la información de los clientes y sus proyectos.</p>
      <?php if ($message !== ''): ?><div class="admin-alert" role="alert"><?= admin_h($message) ?></div><?php endif; ?>
      <?php if (!$adminIsConfigured): ?>
        <div class="admin-alert">El acceso privado aún no está configurado en Plesk.</div>
      <?php else: ?>
        <form method="post" autocomplete="on">
          <input type="hidden" name="csrf_token" value="<?= admin_h($_SESSION['quote_admin_csrf']) ?>">
          <input type="hidden" name="action" value="login">
          <label for="username">Usuario</label><input id="username" name="username" autocomplete="username" required maxlength="80">
          <label for="password">Contraseña</label><input id="password" name="password" type="password" autocomplete="current-password" required>
          <button class="button button-primary" type="submit">Entrar al historial <span aria-hidden="true">↗</span></button>
        </form>
      <?php endif; ?>
      <p class="admin-foot"><a class="text-link" href="../">Volver a Chelart <span aria-hidden="true">↗</span></a></p>
    </main>
  <?php else: ?>
    <main class="admin-shell">
      <?php if ($detail): ?>
        <div class="admin-top"><div><p class="eyebrow">Solicitud <?= admin_h((string)$detail['request_code']) ?></p><h1>Detalle del proyecto</h1><p>Recibida <?= admin_h(admin_date((string)$detail['created_at'])) ?></p></div><a class="text-link" href="./">Volver al historial <span aria-hidden="true">↗</span></a></div>
        <section class="admin-card">
          <dl class="admin-detail">
            <div><dt>Cliente</dt><dd><?= admin_h((string)$detail['full_name']) ?></dd></div>
            <div><dt>Correo</dt><dd><a href="mailto:<?= admin_h((string)$detail['email']) ?>"><?= admin_h((string)$detail['email']) ?></a></dd></div>
            <div><dt>Teléfono</dt><dd><a href="tel:<?= admin_h((string)$detail['phone']) ?>"><?= admin_h((string)$detail['phone']) ?></a></dd></div>
            <div><dt>Ciudad / ZIP</dt><dd><?= admin_h((string)$detail['city']) ?> · <?= admin_h((string)$detail['postal_code']) ?></dd></div>
            <div><dt>Tipo de propiedad</dt><dd><?= admin_h((string)$detail['property_type']) ?></dd></div>
            <div><dt>Plazo estimado</dt><dd><?= admin_h((string)$detail['project_timeline']) ?></dd></div>
            <div><dt>Contacto preferido</dt><dd><?= admin_h((string)$detail['preferred_contact']) ?></dd></div>
            <div><dt>Estado</dt><dd><span class="admin-status"><?= admin_h((string)$detail['status']) ?></span></dd></div>
            <div class="wide"><dt>Servicios</dt><dd><?= admin_h(implode(', ', array_map('strval', $detailServices))) ?></dd></div>
            <div class="wide"><dt>Descripción del proyecto</dt><dd><?= admin_h((string)$detail['scope_description']) ?></dd></div>
          </dl>
        </section>
      <?php else: ?>
        <div class="admin-top"><div><p class="eyebrow">Área privada de Chelart</p><h1>Solicitudes recibidas</h1><p>Consulta el historial y los datos de cada proyecto.</p></div><span class="admin-count"><?= number_format($totalRequests) ?> solicitudes</span></div>
        <?php if ($databaseError): ?><div class="admin-alert" role="alert">No se pudo conectar con la base de datos. Confirma la configuración de MariaDB en Plesk.</div><?php else: ?>
          <section class="admin-card">
            <form class="admin-search" method="get"><input name="q" value="<?= admin_h($search) ?>" placeholder="Buscar por referencia, cliente, correo o ciudad" aria-label="Buscar solicitudes"><button class="button button-primary" type="submit">Buscar</button><?php if ($search !== ''): ?><a class="button button-quiet" href="./">Limpiar</a><?php endif; ?></form>
            <?php if ($requests === []): ?><p class="admin-empty"><?= $search !== '' ? 'No encontramos solicitudes con esos datos.' : 'Aún no hay solicitudes guardadas.' ?></p><?php else: ?>
              <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Fecha</th><th>Referencia / cliente</th><th>Contacto</th><th>Ciudad</th><th>Servicios</th><th>Estado</th></tr></thead><tbody>
              <?php foreach ($requests as $request): ?>
                <tr>
                  <td><?= admin_h(admin_date((string)$request['created_at'])) ?></td>
                  <td><a href="?id=<?= (int)$request['id'] ?>"><?= admin_h((string)$request['request_code']) ?></a><br><?= admin_h((string)$request['full_name']) ?></td>
                  <td><a href="mailto:<?= admin_h((string)$request['email']) ?>"><?= admin_h((string)$request['email']) ?></a><br><a href="tel:<?= admin_h((string)$request['phone']) ?>"><?= admin_h((string)$request['phone']) ?></a></td>
                  <td><?= admin_h((string)$request['city']) ?> · <?= admin_h((string)$request['postal_code']) ?></td>
                  <td><?= admin_h((string)($request['services'] ?? '—')) ?></td>
                  <td><span class="admin-status"><?= admin_h((string)$request['status']) ?></span></td>
                </tr>
              <?php endforeach; ?>
              </tbody></table></div>
              <div class="admin-pagination"><span>Página <?= $page ?> de <?= $lastPage ?></span><span><?php if ($page > 1): ?><a class="text-link" href="?page=<?= $page - 1 ?>&amp;q=<?= rawurlencode($search) ?>">← Anterior</a><?php endif; ?> <?php if ($page < $lastPage): ?><a class="text-link" href="?page=<?= $page + 1 ?>&amp;q=<?= rawurlencode($search) ?>">Siguiente →</a><?php endif; ?></span></div>
            <?php endif; ?>
          </section>
        <?php endif; ?>
      <?php endif; ?>
    </main>
  <?php endif; ?>
</body>
</html>
