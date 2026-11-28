<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$claveDefecto = ADMIN_PASSWORD === 'cambiar-esta-clave' || ADMIN_PASSWORD === '';
$error = '';

if (isset($_GET['salir'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['clave'])) {
    if ($claveDefecto) {
        $error = 'Cambie ADMIN_PASSWORD en config.php antes de entrar.';
    } elseif (hash_equals(ADMIN_PASSWORD, (string)$_POST['clave'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        header('Location: admin.php');
        exit;
    } else {
        sleep(1);
        $error = 'Contraseña incorrecta.';
    }
}

if (empty($_SESSION['admin'])) {
    ?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Calibrador · Administración</title>
    <style>body{font-family:system-ui,Segoe UI,sans-serif;background:#f5f6f8;color:#1b1f24;display:grid;place-items:center;min-height:100vh;margin:0}
    form{background:#fff;padding:28px;border-radius:10px;border:1px solid #dde1e6;width:min(340px,90vw)}
    h1{font-size:18px;margin:0 0 16px}input{width:100%;box-sizing:border-box;padding:10px;font-size:15px;border:1px solid #c5cbd3;border-radius:6px}
    button{margin-top:12px;width:100%;padding:10px;background:#c8102e;color:#fff;border:0;border-radius:6px;font-size:15px;cursor:pointer}
    .err{color:#b42318;font-size:14px;margin-top:10px}</style></head><body>
    <form method="post"><h1>Calibrador · Administración</h1>
    <label for="clave">Contraseña</label><input id="clave" name="clave" type="password" autofocus required>
    <button type="submit">Entrar</button>
    <?php if ($error) echo '<div class="err">' . h($error) . '</div>'; ?>
    <?php if ($claveDefecto) echo '<div class="err">La contraseña por defecto está deshabilitada: edite config.php.</div>'; ?>
    </form></body></html><?php
    exit;
}

$dirPerfiles = DATA_DIR . '/perfiles';
$dirCapturas = DATA_DIR . '/capturas';

// Ver una captura
if (isset($_GET['img'])) {
    $f = (string)$_GET['img'];
    if (!preg_match('/^[0-9A-Za-z\-]+\.(png|jpg|webp)$/', $f) || !is_file($dirCapturas . '/' . $f)) {
        http_response_code(404); exit('No encontrado');
    }
    $mime = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'webp' => 'image/webp'][pathinfo($f, PATHINFO_EXTENSION)];
    header('Content-Type: ' . $mime);
    readfile($dirCapturas . '/' . $f);
    exit;
}
// Ver / descargar un perfil
if (isset($_GET['perfil'])) {
    $c = (string)$_GET['perfil'];
    if (!preg_match('/^[0-9]{8}-[0-9]{6}-[0-9a-f]{6}$/', $c) || !is_file($dirPerfiles . '/' . $c . '.json')) {
        http_response_code(404); exit('No encontrado');
    }
    header('Content-Type: application/json; charset=utf-8');
    if (isset($_GET['descargar'])) header('Content-Disposition: attachment; filename="perfil-' . $c . '.json"');
    readfile($dirPerfiles . '/' . $c . '.json');
    exit;
}

$perfiles = [];
foreach (glob($dirPerfiles . '/*.json') ?: [] as $archivo) {
    $p = json_decode((string)file_get_contents($archivo), true);
    if (is_array($p)) $perfiles[] = $p;
}
usort($perfiles, fn($a, $b) => strcmp($b['servidor']['codigo'] ?? '', $a['servidor']['codigo'] ?? ''));

function fila(array $p): array {
    $d = $p['deteccion'] ?? [];
    $mons = $p['monitores'] ?? [];
    $prin = null;
    foreach ($mons as $m) { if (!empty($m['principal'])) { $prin = $m; break; } }
    $prin = $prin ?? ($mons[0] ?? null);
    $res = $prin ? round($prin['w'] * $prin['dpr']) . ' × ' . round($prin['h'] * $prin['dpr']) : '';
    $prueba = $p['pruebaAjuste'] ?? [];
    $resp = $prueba['respuesta'] ?? null;
    return [
        'codigo' => $p['servidor']['codigo'] ?? '',
        'fecha' => isset($p['servidor']['recibido']) ? date('Y-m-d H:i', strtotime($p['servidor']['recibido'])) : '',
        'nombre' => $p['corredor']['nombre'] ?? '',
        'firma' => $p['corredor']['firma'] ?? '',
        'correo' => $p['corredor']['correo'] ?? '',
        'monitores' => count($mons),
        'resolucion' => $res,
        'escala' => ($p['escalaConfirmada'] ?? '') !== '' ? $p['escalaConfirmada'] . ' %' : '',
        'areaUtil' => isset($prueba['viewport']) ? $prueba['viewport']['w'] . ' × ' . $prueba['viewport']['h'] : '',
        'factor' => isset($prueba['factor']) ? number_format((float)$prueba['factor'], 3, ',', '') : '',
        'ajuste' => $resp === 'ok' ? 'Sí' : ($resp === 'mal' ? 'No' : '—'),
        'modo' => ($p['modo'] ?? '') === 'mi-puesto' ? 'Mi puesto' : 'Grabación',
        'ventanas' => count($p['ventanas'] ?? []),
        'navegador' => $d['navegador'] ?? '',
        'sistema' => $d['sistema'] ?? '',
        'zoom' => isset($d['zoomEstimado']) ? $d['zoomEstimado'] . ' %' : '',
        'comentario' => $prueba['comentario'] ?? '',
        'capturas' => $p['capturas'] ?? [],
    ];
}
$filas = array_map('fila', $perfiles);

// Exportar CSV para Excel (separador ; y BOM UTF-8)
if (isset($_GET['csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="perfiles-pantalla-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    $cols = ['codigo', 'fecha', 'nombre', 'firma', 'correo', 'monitores', 'resolucion', 'escala', 'areaUtil', 'factor', 'ajuste', 'modo', 'ventanas', 'navegador', 'sistema', 'zoom', 'comentario'];
    fputcsv($out, $cols, ';');
    foreach ($filas as $f) {
        fputcsv($out, array_map(fn($c) => $f[$c], $cols), ';');
    }
    exit;
}
?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Calibrador · Perfiles de pantalla</title>
<style>
:root{--bg:#f5f6f8;--card:#fff;--ink:#1b1f24;--muted:#5b6470;--line:#dde1e6;--accent:#c8102e;--ok:#1a7f37;--bad:#b42318}
@media (prefers-color-scheme:dark){:root{--bg:#0f1216;--card:#171b21;--ink:#e6e9ed;--muted:#9aa4af;--line:#2a313a;--accent:#ff4d5e;--ok:#3fb950;--bad:#ff7b72}}
body{font-family:system-ui,"Segoe UI",sans-serif;background:var(--bg);color:var(--ink);margin:0;padding:24px 16px}
header{display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;max-width:1400px;margin:0 auto 16px}
h1{font-size:20px;margin:0}a{color:var(--accent)}
.acciones a{margin-left:14px;font-size:14px}
.wrap{max-width:1400px;margin:0 auto;background:var(--card);border:1px solid var(--line);border-radius:10px;overflow:auto}
table{border-collapse:collapse;width:100%;font-size:13px}
th,td{padding:8px 10px;border-bottom:1px solid var(--line);text-align:left;white-space:nowrap;vertical-align:top}
th{position:sticky;top:0;background:var(--card);color:var(--muted);font-weight:600}
td.num{font-variant-numeric:tabular-nums}
.si{color:var(--ok);font-weight:600}.no{color:var(--bad);font-weight:600}
.vacio{padding:40px;text-align:center;color:var(--muted)}
.resumen{max-width:1400px;margin:0 auto 12px;color:var(--muted);font-size:14px}
</style></head><body>
<header>
  <h1>Perfiles de pantalla de los corredores</h1>
  <div class="acciones"><a href="admin.php?csv=1">Descargar Excel (CSV)</a><a href="index.html" target="_blank">Abrir calibrador</a><a href="admin.php?salir=1">Salir</a></div>
</header>
<?php
$res = array_count_values(array_filter(array_column($filas, 'resolucion')));
arsort($res);
$esc = array_count_values(array_filter(array_column($filas, 'escala')));
arsort($esc);
$fmt = function ($arr) { $o = []; foreach ($arr as $k => $v) $o[] = h($k) . ' (' . $v . ')'; return implode(', ', $o); };
?>
<p class="resumen"><?= count($filas) ?> perfiles recibidos.
<?php if ($res) echo 'Resoluciones: ' . $fmt($res) . '. '; ?>
<?php if ($esc) echo 'Escalas: ' . $fmt($esc) . '.'; ?></p>
<div class="wrap">
<?php if (!$filas): ?>
  <div class="vacio">Todavía no hay perfiles. Comparta el enlace del calibrador con los corredores.</div>
<?php else: ?>
<table>
<thead><tr><th>Fecha</th><th>Corredor</th><th>Firma</th><th>Monitores</th><th>Resolución principal</th><th>Escala Windows</th><th>Área útil navegador</th><th>Factor</th><th>¿Encaja?</th><th>Modo</th><th>Navegador</th><th>Zoom</th><th>Capturas</th><th>Perfil</th><th>Comentario</th></tr></thead>
<tbody>
<?php foreach ($filas as $f): ?>
<tr>
  <td class="num"><?= h($f['fecha']) ?></td>
  <td><?= h($f['nombre']) ?><?php if ($f['correo']) echo '<br><small>' . h($f['correo']) . '</small>'; ?></td>
  <td><?= h($f['firma']) ?></td>
  <td class="num"><?= h($f['monitores']) ?></td>
  <td class="num"><?= h($f['resolucion']) ?></td>
  <td class="num"><?= h($f['escala']) ?></td>
  <td class="num"><?= h($f['areaUtil']) ?></td>
  <td class="num"><?= h($f['factor']) ?></td>
  <td class="<?= $f['ajuste'] === 'Sí' ? 'si' : ($f['ajuste'] === 'No' ? 'no' : '') ?>"><?= h($f['ajuste']) ?></td>
  <td><?= h($f['modo']) ?> (<?= h($f['ventanas']) ?> vent.)</td>
  <td><?= h($f['navegador']) ?><br><small><?= h($f['sistema']) ?></small></td>
  <td class="num"><?= h($f['zoom']) ?></td>
  <td><?php foreach ($f['capturas'] as $i => $c): ?><a href="admin.php?img=<?= h($c['archivo']) ?>" target="_blank">Ver <?= $i + 1 ?></a><?php if (!empty($c['ancho'])) echo ' <small>' . h($c['ancho'] . '×' . $c['alto']) . '</small>'; ?><br><?php endforeach; ?></td>
  <td><a href="admin.php?perfil=<?= h($f['codigo']) ?>" target="_blank">Ver</a> · <a href="admin.php?perfil=<?= h($f['codigo']) ?>&amp;descargar=1">JSON</a></td>
  <td style="white-space:normal;min-width:180px"><?= h($f['comentario']) ?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>
</div>
</body></html>
