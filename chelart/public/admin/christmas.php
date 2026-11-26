<?php
declare(strict_types=1);

// CHRISTMAS SERVICE LEADS: gestión de solicitudes de la campaña de Navidad.
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
require_once dirname(__DIR__, 2) . '/config/christmas.php';
require_once dirname(__DIR__, 2) . '/config/christmas_admin.php';
christmas_admin_require_login();
require_once dirname(__DIR__, 2) . '/config/database.php';

function x_h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function x_datetime(?string $value): string
{
    if (!$value) {
        return '—';
    }
    try {
        return (new DateTimeImmutable($value, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone(CHRISTMAS_TIMEZONE))->format('M j, Y · g:i A');
    } catch (Throwable) {
        return $value;
    }
}

function x_date(?string $value): string
{
    if (!$value) {
        return '—';
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date ? $date->format('M j, Y') : $value;
}

function x_money(mixed $value): string
{
    return $value === null || $value === '' ? '—' : '$' . number_format((float)$value, 2);
}

function x_areas(string $value): string
{
    $keys = array_filter(explode(',', $value));
    return implode(', ', array_map(static fn(string $key): string => christmas_label(CHRISTMAS_DECORATION_AREAS, $key), $keys));
}

/** Convierte "$1,250.00" en 1250.00; null si está vacío; false si no es válido. */
function x_parse_money(string $raw): float|null|false
{
    $clean = str_replace(['$', ',', ' '], '', trim($raw));
    if ($clean === '') {
        return null;
    }
    if (!preg_match('/^\d{1,7}(\.\d{1,2})?$/', $clean)) {
        return false;
    }
    return (float)$clean;
}

/** Evita que Excel interprete celdas como fórmulas (inyección CSV). */
function x_csv_cell(mixed $value): string
{
    $text = (string)($value ?? '');
    return preg_match('/^[=+\-@\t\r]/', $text) ? "'" . $text : $text;
}

$filters = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'package' => isset(CHRISTMAS_PACKAGES[$_GET['package'] ?? '']) ? (string)$_GET['package'] : '',
    'status' => isset(CHRISTMAS_STATUSES[$_GET['status'] ?? '']) ? (string)$_GET['status'] : '',
    'from' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['from'] ?? '')) ? (string)$_GET['from'] : '',
    'to' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['to'] ?? '')) ? (string)$_GET['to'] : '',
];
$filters['q'] = function_exists('mb_substr') ? mb_substr($filters['q'], 0, 120) : substr($filters['q'], 0, 120);

function x_where(array $filters): array
{
    $clauses = [];
    $params = [];
    if ($filters['q'] !== '') {
        $like = '%' . addcslashes($filters['q'], '%_\\') . '%';
        $clauses[] = '(full_name LIKE :q1 OR email LIKE :q2 OR phone LIKE :q3 OR city LIKE :q4 OR request_number LIKE :q5)';
        foreach (['q1', 'q2', 'q3', 'q4', 'q5'] as $key) {
            $params[':' . $key] = $like;
        }
        $digits = preg_replace('/\D/', '', $filters['q']);
        if (strlen($digits) >= 4) {
            $clauses[count($clauses) - 1] = '(full_name LIKE :q1 OR email LIKE :q2 OR phone LIKE :q3 OR city LIKE :q4 OR request_number LIKE :q5'
                . " OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '.', '') LIKE :q6)";
            $params[':q6'] = '%' . $digits . '%';
        }
    }
    if ($filters['package'] !== '') {
        $clauses[] = 'package_type = :package';
        $params[':package'] = $filters['package'];
    }
    if ($filters['status'] !== '') {
        $clauses[] = 'request_status = :status';
        $params[':status'] = $filters['status'];
    }
    if ($filters['from'] !== '') {
        $clauses[] = 'installation_date >= :from';
        $params[':from'] = $filters['from'];
    }
    if ($filters['to'] !== '') {
        $clauses[] = 'installation_date <= :to';
        $params[':to'] = $filters['to'];
    }
    return [$clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses), $params];
}

$message = '';
$error = '';
$databaseError = false;

try {
    $pdo = database();

    // Actualizar estado, cotización y notas internas.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals((string)$_SESSION['quote_admin_csrf'], (string)($_POST['csrf_token'] ?? ''))) {
            http_response_code(400);
            $error = 'La sesión del formulario venció. Recarga la página e inténtalo de nuevo.';
        } elseif (($_POST['action'] ?? '') === 'update' && ctype_digit((string)($_POST['id'] ?? ''))) {
            $id = (int)$_POST['id'];
            $status = (string)($_POST['request_status'] ?? '');
            $estimated = x_parse_money((string)($_POST['estimated_price'] ?? ''));
            $final = x_parse_money((string)($_POST['final_quote'] ?? ''));
            $note = trim(str_replace("\r\n", "\n", (string)($_POST['new_note'] ?? '')));
            $note = (string)preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $note);
            if (!isset(CHRISTMAS_STATUSES[$status]) || $estimated === false || $final === false || strlen($note) > 4000) {
                $error = 'Revisa los datos: el estado debe ser válido y los valores deben ser números (ej. 1250.00).';
            } else {
                $current = $pdo->prepare('SELECT request_status, internal_notes FROM christmas_quote_requests WHERE id = :id');
                $current->execute([':id' => $id]);
                $row = $current->fetch();
                if ($row) {
                    $stamp = christmas_now()->format('Y-m-d g:i A');
                    $log = [];
                    if ($row['request_status'] !== $status) {
                        $log[] = "[{$stamp}] Estado: " . CHRISTMAS_STATUSES[$row['request_status']] . ' → ' . CHRISTMAS_STATUSES[$status];
                    }
                    if ($note !== '') {
                        $log[] = "[{$stamp}] {$note}";
                    }
                    $notes = trim(implode("\n\n", array_filter([(string)$row['internal_notes'], implode("\n", $log)])));
                    $update = $pdo->prepare('UPDATE christmas_quote_requests SET request_status = :status, estimated_price = :estimated, final_quote = :final, internal_notes = :notes WHERE id = :id');
                    $update->execute([':status' => $status, ':estimated' => $estimated, ':final' => $final, ':notes' => $notes !== '' ? $notes : null, ':id' => $id]);
                    header('Location: christmas.php?id=' . $id . '&saved=1', true, 303);
                    exit;
                }
                $error = 'No se encontró la solicitud.';
            }
        }
    }

    [$where, $params] = x_where($filters);

    // Exportar a CSV (abre en Excel con tildes correctas gracias al BOM UTF-8).
    if (($_GET['export'] ?? '') === 'csv') {
        $export = $pdo->prepare('SELECT r.*, (SELECT COUNT(*) FROM christmas_quote_photos p WHERE p.request_id = r.id) AS photo_count FROM christmas_quote_requests r' . $where . ' ORDER BY r.created_at DESC, r.id DESC LIMIT 20000');
        $export->execute($params);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="christmas-leads-' . christmas_now()->format('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Request number', 'Received (ET)', 'Status', 'Full name', 'Email', 'Phone', 'Contact method', 'Address', 'City', 'ZIP', 'Property type', 'Package', 'Decoration areas', 'Decorations supplied by', 'Property size', 'Installation date', 'Removal date', 'Additional details', 'Photos', 'Language', 'Campaign', 'Discount %', 'Estimated price', 'Final quote', 'Internal notes']);
        while ($row = $export->fetch()) {
            fputcsv($out, array_map('x_csv_cell', [
                $row['request_number'], x_datetime($row['created_at']), CHRISTMAS_STATUSES[$row['request_status']] ?? $row['request_status'],
                $row['full_name'], $row['email'], $row['phone'], christmas_label(CHRISTMAS_CONTACT_METHODS, $row['contact_method']),
                $row['property_address'], $row['city'], $row['zip_code'], christmas_label(CHRISTMAS_PROPERTY_TYPES, $row['property_type']),
                christmas_label(CHRISTMAS_PACKAGES, $row['package_type']), x_areas((string)$row['decoration_areas']),
                christmas_label(CHRISTMAS_SUPPLIERS, $row['decoration_supplier']), christmas_label(CHRISTMAS_PROPERTY_SIZES, $row['property_size']),
                $row['installation_date'], $row['removal_date'], $row['additional_details'], $row['photo_count'], $row['language'],
                $row['campaign_source'], $row['discount_percentage'], $row['estimated_price'], $row['final_quote'], $row['internal_notes'],
            ]));
        }
        fclose($out);
        exit;
    }

    $detail = null;
    $detailPhotos = [];
    if (isset($_GET['id']) && ctype_digit((string)$_GET['id'])) {
        $statement = $pdo->prepare('SELECT * FROM christmas_quote_requests WHERE id = :id LIMIT 1');
        $statement->execute([':id' => (int)$_GET['id']]);
        $detail = $statement->fetch() ?: null;
        if ($detail) {
            $photoStatement = $pdo->prepare('SELECT id, original_name, size_bytes, width, height FROM christmas_quote_photos WHERE request_id = :id ORDER BY id');
            $photoStatement->execute([':id' => (int)$detail['id']]);
            $detailPhotos = $photoStatement->fetchAll();
        }
        if (isset($_GET['saved']) && $error === '') {
            $message = 'Cambios guardados.';
        }
    }

    $page = max(1, min(100000, (int)($_GET['page'] ?? 1)));
    $pageSize = 25;
    $count = $pdo->prepare('SELECT COUNT(*) FROM christmas_quote_requests' . $where);
    $count->execute($params);
    $total = (int)$count->fetchColumn();
    $lastPage = max(1, (int)ceil($total / $pageSize));
    $page = min($page, $lastPage);
    $list = $pdo->prepare('SELECT r.id, r.request_number, r.full_name, r.email, r.phone, r.city, r.zip_code, r.package_type, r.installation_date, r.request_status, r.discount_percentage, r.created_at,
            (SELECT COUNT(*) FROM christmas_quote_photos p WHERE p.request_id = r.id) AS photo_count
        FROM christmas_quote_requests r' . $where . ' ORDER BY r.created_at DESC, r.id DESC LIMIT :limit OFFSET :offset');
    foreach ($params as $key => $value) {
        $list->bindValue($key, $value, PDO::PARAM_STR);
    }
    $list->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $list->bindValue(':offset', ($page - 1) * $pageSize, PDO::PARAM_INT);
    $list->execute();
    $requests = $list->fetchAll();

    $summary = $pdo->query('SELECT request_status, COUNT(*) AS total FROM christmas_quote_requests GROUP BY request_status')->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Throwable $exception) {
    error_log('Chelart Christmas leads could not be loaded: ' . $exception->getMessage());
    $databaseError = true;
    $requests = [];
    $summary = [];
    $total = 0;
    $page = 1;
    $lastPage = 1;
    $detail = null;
}

$query = static fn(array $extra = []): string => http_build_query(array_filter($extra + $filters, static fn($value) => $value !== '' && $value !== null));
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow, noarchive">
  <link rel="icon" type="image/png" sizes="32x32" href="../favicon-32.png">
  <title>Christmas Service Leads | Chelart</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/site.css?v=20261126">
  <style>
    .x-shell{width:min(1320px,calc(100% - 40px));margin:36px auto 80px}.x-top{display:flex;justify-content:space-between;align-items:flex-end;gap:24px;flex-wrap:wrap;margin-bottom:24px}.x-top h1{font-family:var(--serif);font-size:clamp(34px,4.6vw,54px);font-weight:500;letter-spacing:-.04em;margin:0}.x-top p{margin:8px 0 0;color:var(--muted);font-size:14px}
    .x-card{background:var(--white);padding:clamp(18px,3vw,34px);box-shadow:0 18px 55px rgba(42,35,28,.07);margin-bottom:20px}.x-nav{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
    .x-filters{display:grid;grid-template-columns:2fr 1fr 1fr 1fr 1fr auto;gap:10px;align-items:end}.x-filters label{display:flex;flex-direction:column;gap:6px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}.x-filters input,.x-filters select,.x-form input,.x-form select,.x-form textarea{min-height:44px;padding:9px 11px;border:1px solid #ded8cf;background:#fff;font-size:14px;color:var(--ink);text-transform:none;letter-spacing:0;font-weight:400}
    .x-filter-actions{display:flex;gap:8px}.x-summary{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 18px;padding:0;list-style:none}.x-summary a{display:inline-flex;gap:8px;align-items:center;min-height:36px;padding:6px 12px;border:1px solid var(--line);font-size:12px;background:#fff}.x-summary a.is-active{border-color:var(--orange);background:#fbf1eb}.x-summary b{color:var(--orange-dark)}
    .x-table-wrap{overflow-x:auto}.x-table{width:100%;border-collapse:collapse;min-width:900px}.x-table th,.x-table td{padding:13px 10px;border-bottom:1px solid var(--line);text-align:left;vertical-align:top;font-size:13px;line-height:1.5}.x-table th{color:var(--muted);font-size:11px;letter-spacing:.1em;text-transform:uppercase}.x-table td a{color:var(--orange-dark);font-weight:700}
    .x-status{display:inline-block;padding:4px 8px;background:#f4ece2;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;white-space:nowrap}.x-status-NEW_REQUEST{background:#fbe1d3;color:#873613}.x-status-APPROVED,.x-status-SCHEDULED{background:#e3efe2;color:#25572a}.x-status-COMPLETED{background:#e4e2de;color:#3d3933}.x-status-CANCELLED{background:#eee;color:#777;text-decoration:line-through}
    .x-alert{padding:13px 15px;margin-bottom:16px;border-left:3px solid var(--orange);background:#fff4ed;font-size:14px}.x-ok{border-color:#2f7a3a;background:#ecf6ec}.x-muted{color:var(--muted)}.x-pagination{display:flex;justify-content:space-between;margin-top:18px;font-size:13px;color:var(--muted)}
    .x-detail{display:grid;grid-template-columns:1.4fr 1fr;gap:20px;align-items:start}.x-dl{display:grid;grid-template-columns:1fr 1fr;gap:0 22px;margin:0}.x-dl div{padding:12px 0;border-bottom:1px solid var(--line)}.x-dl dt{margin-bottom:4px;color:var(--muted);font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}.x-dl dd{margin:0;white-space:pre-wrap;overflow-wrap:anywhere;font-size:15px;line-height:1.55}.x-dl .wide{grid-column:1/-1}
    .x-card h2{margin:0 0 14px;font-family:var(--serif);font-size:24px;font-weight:500}.x-photos{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px}.x-photos a{display:block;aspect-ratio:4/3;overflow:hidden;background:var(--sand)}.x-photos img{width:100%;height:100%;object-fit:cover}.x-photos figcaption{font-size:11px;color:var(--muted);margin-top:4px;overflow-wrap:anywhere}
    .x-form{display:grid;gap:14px}.x-form label{display:flex;flex-direction:column;gap:6px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}.x-form textarea{min-height:100px;resize:vertical}.x-notes{white-space:pre-wrap;font-size:13px;line-height:1.6;background:#faf7f2;padding:12px;max-height:340px;overflow:auto;margin:0}.x-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:10px}.x-help{font-size:12px;color:var(--muted);margin:0}
    @media(max-width:980px){.x-filters{grid-template-columns:1fr 1fr}.x-filters label:first-child{grid-column:1/-1}.x-detail{grid-template-columns:1fr}}
    @media(max-width:700px){.x-shell{width:calc(100% - 24px);margin:22px auto 48px}.x-filters,.x-dl,.x-grid-2{grid-template-columns:1fr}.x-dl .wide{grid-column:auto}}
  </style>
</head>
<body class="quote-page">
  <header class="site-header quote-header"><div class="nav-wrap">
    <a class="brand" href="../" aria-label="Inicio de Chelart"><img src="../assets/img/chelart-logo-192.png" alt="Chelart Construction" width="192" height="192" sizes="96px"></a>
    <div class="x-nav"><a class="text-link" href="./">Historial general</a>
      <form method="post" action="./"><input type="hidden" name="csrf_token" value="<?= x_h($_SESSION['quote_admin_csrf']) ?>"><input type="hidden" name="action" value="logout"><button class="button button-quiet" type="submit">Cerrar sesión</button></form></div>
  </div></header>

  <main class="x-shell">
    <?php if ($databaseError): ?>
      <div class="x-top"><div><p class="eyebrow">Área privada de Chelart</p><h1>Christmas Service Leads</h1></div></div>
      <div class="x-alert" role="alert">No se pudo cargar la información. Confirma la conexión a MariaDB y que se ejecutó <code>sql/christmas_campaign.sql</code>.</div>
    <?php elseif ($detail): ?>
      <div class="x-top"><div><p class="eyebrow">Solicitud <?= x_h($detail['request_number']) ?> · <?= x_h($detail['campaign_source']) ?></p><h1><?= x_h($detail['full_name']) ?></h1><p>Recibida <?= x_h(x_datetime($detail['created_at'])) ?> (hora de Florida) · Idioma: <?= $detail['language'] === 'es' ? 'español' : 'inglés' ?></p></div>
        <a class="text-link" href="christmas.php?<?= x_h($query()) ?>">← Volver a la lista</a></div>
      <?php if ($message !== ''): ?><div class="x-alert x-ok" role="status"><?= x_h($message) ?></div><?php endif; ?>
      <?php if ($error !== ''): ?><div class="x-alert" role="alert"><?= x_h($error) ?></div><?php endif; ?>
      <div class="x-detail">
        <div>
          <section class="x-card"><h2>Cliente y propiedad</h2><dl class="x-dl">
            <div><dt>Correo</dt><dd><a href="mailto:<?= x_h($detail['email']) ?>"><?= x_h($detail['email']) ?></a></dd></div>
            <div><dt>Teléfono</dt><dd><a href="tel:<?= x_h(preg_replace('/[^0-9+]/', '', $detail['phone'])) ?>"><?= x_h($detail['phone']) ?></a></dd></div>
            <div><dt>Contacto preferido</dt><dd><?= x_h(christmas_label(CHRISTMAS_CONTACT_METHODS, $detail['contact_method'])) ?><?php if ($detail['contact_method'] === 'whatsapp'): ?> · <a href="https://wa.me/<?= x_h(preg_replace('/\D/', '', strlen(preg_replace('/\D/', '', $detail['phone'])) === 10 ? '1' . $detail['phone'] : $detail['phone'])) ?>" target="_blank" rel="noopener noreferrer">Abrir WhatsApp</a><?php endif; ?></dd></div>
            <div><dt>Tipo de propiedad</dt><dd><?= x_h(christmas_label(CHRISTMAS_PROPERTY_TYPES, $detail['property_type'])) ?></dd></div>
            <div class="wide"><dt>Dirección</dt><dd><?= x_h($detail['property_address']) ?>, <?= x_h($detail['city']) ?> <?= x_h($detail['zip_code']) ?> · <a href="https://www.google.com/maps/search/?api=1&amp;query=<?= x_h(rawurlencode($detail['property_address'] . ', ' . $detail['city'] . ', FL ' . $detail['zip_code'])) ?>" target="_blank" rel="noopener noreferrer">Ver mapa</a></dd></div>
            <div><dt>Tamaño aproximado</dt><dd><?= x_h(christmas_label(CHRISTMAS_PROPERTY_SIZES, $detail['property_size'])) ?></dd></div>
            <div><dt>Paquete</dt><dd><strong><?= x_h(christmas_label(CHRISTMAS_PACKAGES, $detail['package_type'])) ?></strong></dd></div>
            <div class="wide"><dt>Áreas a decorar</dt><dd><?= x_h(x_areas((string)$detail['decoration_areas'])) ?></dd></div>
            <div><dt>Decoración aportada por</dt><dd><?= x_h(christmas_label(CHRISTMAS_SUPPLIERS, $detail['decoration_supplier'])) ?></dd></div>
            <div><dt>Promoción</dt><dd><?= (float)$detail['discount_percentage'] > 0 ? x_h(rtrim(rtrim((string)$detail['discount_percentage'], '0'), '.')) . '% (Black Friday)' : 'No aplica' ?></dd></div>
            <div><dt>Instalación preferida</dt><dd><?= x_h(x_date($detail['installation_date'])) ?></dd></div>
            <div><dt>Retiro preferido</dt><dd><?= x_h(x_date($detail['removal_date'])) ?></dd></div>
            <div class="wide"><dt>Detalles adicionales</dt><dd><?= x_h($detail['additional_details'] ?: '—') ?></dd></div>
          </dl></section>
          <section class="x-card"><h2>Fotos (<?= count($detailPhotos) ?>)</h2>
            <?php if ($detailPhotos === []): ?><p class="x-muted">El cliente no adjuntó fotos.</p><?php else: ?>
              <div class="x-photos"><?php foreach ($detailPhotos as $photo): ?><figure style="margin:0"><a href="christmas-photo.php?id=<?= (int)$photo['id'] ?>" target="_blank" rel="noopener"><img src="christmas-photo.php?id=<?= (int)$photo['id'] ?>" alt="<?= x_h($photo['original_name']) ?>" loading="lazy"></a><figcaption><?= x_h($photo['original_name']) ?> · <?= (int)round($photo['size_bytes'] / 1024) ?> KB · <a href="christmas-photo.php?id=<?= (int)$photo['id'] ?>&amp;download=1">Descargar</a></figcaption></figure><?php endforeach; ?></div>
            <?php endif; ?></section>
        </div>
        <section class="x-card"><h2>Gestión</h2>
          <form class="x-form" method="post" action="christmas.php?id=<?= (int)$detail['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= x_h($_SESSION['quote_admin_csrf']) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int)$detail['id'] ?>">
            <label>Estado<select name="request_status"><?php foreach (CHRISTMAS_STATUSES as $key => $label): ?><option value="<?= x_h($key) ?>"<?= $detail['request_status'] === $key ? ' selected' : '' ?>><?= x_h(strtoupper($label)) ?></option><?php endforeach; ?></select></label>
            <div class="x-grid-2">
              <label>Precio estimado (USD)<input name="estimated_price" inputmode="decimal" value="<?= x_h($detail['estimated_price']) ?>" placeholder="0.00"></label>
              <label>Cotización final (USD)<input name="final_quote" inputmode="decimal" value="<?= x_h($detail['final_quote']) ?>" placeholder="0.00"></label>
            </div>
            <p class="x-help">Valores internos. No se envían al cliente ni se cobran automáticamente. Registra la cotización final solo después de la revisión del equipo<?= (float)$detail['discount_percentage'] > 0 ? ' y recuerda aplicar el ' . x_h(rtrim(rtrim((string)$detail['discount_percentage'], '0'), '.')) . '% de la promoción al paquete de instalación y retiro' : '' ?>.</p>
            <label>Agregar nota interna<textarea name="new_note" maxlength="4000" placeholder="Llamada, visita, acuerdos…"></textarea></label>
            <button class="button button-primary" type="submit">Guardar cambios</button>
          </form>
          <h2 style="margin-top:26px">Historial de notas</h2>
          <?php if (trim((string)$detail['internal_notes']) === ''): ?><p class="x-muted">Sin notas todavía.</p><?php else: ?><pre class="x-notes"><?= x_h($detail['internal_notes']) ?></pre><?php endif; ?>
        </section>
      </div>
    <?php else: ?>
      <div class="x-top"><div><p class="eyebrow">Área privada de Chelart</p><h1>Christmas Service Leads</h1><p><?= number_format($total) ?> solicitudes<?= $filters !== array_fill_keys(array_keys($filters), '') ? ' con estos filtros' : '' ?> · Campaña <?= christmas_campaign_enabled() ? 'activa' : 'desactivada' ?><?= christmas_promo_active() ? ' · Promoción ' . christmas_discount_percent() . '% vigente' : '' ?></p></div>
        <a class="button button-quiet" href="christmas.php?<?= x_h($query(['export' => 'csv'])) ?>">Exportar CSV / Excel</a></div>
      <?php if ($error !== ''): ?><div class="x-alert" role="alert"><?= x_h($error) ?></div><?php endif; ?>
      <ul class="x-summary"><?php foreach (CHRISTMAS_STATUSES as $key => $label): ?><li><a class="<?= $filters['status'] === $key ? 'is-active' : '' ?>" href="christmas.php?<?= x_h($query(['status' => $filters['status'] === $key ? '' : $key, 'page' => ''])) ?>"><?= x_h(strtoupper($label)) ?> <b><?= (int)($summary[$key] ?? 0) ?></b></a></li><?php endforeach; ?></ul>
      <section class="x-card">
        <form class="x-filters" method="get">
          <label>Buscar<input name="q" value="<?= x_h($filters['q']) ?>" placeholder="Nombre, correo, teléfono, ciudad o referencia"></label>
          <label>Paquete<select name="package"><option value="">Todos</option><?php foreach (CHRISTMAS_PACKAGES as $key => $labels): ?><option value="<?= x_h($key) ?>"<?= $filters['package'] === $key ? ' selected' : '' ?>><?= x_h($labels['en']) ?></option><?php endforeach; ?></select></label>
          <label>Estado<select name="status"><option value="">Todos</option><?php foreach (CHRISTMAS_STATUSES as $key => $label): ?><option value="<?= x_h($key) ?>"<?= $filters['status'] === $key ? ' selected' : '' ?>><?= x_h(strtoupper($label)) ?></option><?php endforeach; ?></select></label>
          <label>Instalación desde<input type="date" name="from" value="<?= x_h($filters['from']) ?>"></label>
          <label>Instalación hasta<input type="date" name="to" value="<?= x_h($filters['to']) ?>"></label>
          <div class="x-filter-actions"><button class="button button-primary" type="submit">Filtrar</button><a class="button button-quiet" href="christmas.php">Limpiar</a></div>
        </form>
      </section>
      <section class="x-card">
        <?php if ($requests === []): ?><p class="x-muted"><?= $total === 0 && $where === '' ? 'Aún no hay solicitudes de Navidad.' : 'No hay solicitudes con esos filtros.' ?></p><?php else: ?>
          <div class="x-table-wrap"><table class="x-table"><thead><tr><th>Recibida</th><th>Referencia / cliente</th><th>Contacto</th><th>Ciudad</th><th>Paquete</th><th>Instalación</th><th>Fotos</th><th>Estado</th></tr></thead><tbody>
          <?php foreach ($requests as $row): ?>
            <tr>
              <td><?= x_h(x_datetime($row['created_at'])) ?></td>
              <td><a href="christmas.php?<?= x_h($query(['id' => (string)$row['id']])) ?>"><?= x_h($row['request_number']) ?></a><br><?= x_h($row['full_name']) ?></td>
              <td><a href="mailto:<?= x_h($row['email']) ?>"><?= x_h($row['email']) ?></a><br><?= x_h($row['phone']) ?></td>
              <td><?= x_h($row['city']) ?> · <?= x_h($row['zip_code']) ?></td>
              <td><?= x_h(christmas_label(CHRISTMAS_PACKAGES, $row['package_type'])) ?><?= (float)$row['discount_percentage'] > 0 ? '<br><span class="x-muted">Black Friday</span>' : '' ?></td>
              <td><?= x_h(x_date($row['installation_date'])) ?></td>
              <td><?= (int)$row['photo_count'] ?></td>
              <td><span class="x-status x-status-<?= x_h($row['request_status']) ?>"><?= x_h(strtoupper(CHRISTMAS_STATUSES[$row['request_status']] ?? $row['request_status'])) ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody></table></div>
          <div class="x-pagination"><span>Página <?= $page ?> de <?= $lastPage ?></span><span><?php if ($page > 1): ?><a class="text-link" href="christmas.php?<?= x_h($query(['page' => (string)($page - 1)])) ?>">← Anterior</a><?php endif; ?> <?php if ($page < $lastPage): ?><a class="text-link" href="christmas.php?<?= x_h($query(['page' => (string)($page + 1)])) ?>">Siguiente →</a><?php endif; ?></span></div>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  </main>
</body>
</html>
