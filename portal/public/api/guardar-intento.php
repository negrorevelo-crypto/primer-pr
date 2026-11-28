<?php
require dirname(__DIR__, 2) . '/app/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function salir_json(int $code, array $d): void
{
    http_response_code($code);
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}

$u = usuario();
if (!$u) salir_json(401, ['ok' => false, 'error' => 'Su sesión terminó. Vuelva a iniciar sesión.']);
if (!es_post()) salir_json(405, ['ok' => false, 'error' => 'Método no permitido']);
if (!hash_equals(csrf_token(), (string)($_SERVER['HTTP_X_CSRF'] ?? ''))) salir_json(419, ['ok' => false, 'error' => 'Recargue la página e intente de nuevo.']);
if ($u['rol'] !== 'admin' && !licencia_vigente((int)$u['id'])) salir_json(403, ['ok' => false, 'error' => 'Su licencia no está vigente.']);

$raw = file_get_contents('php://input');
if (strlen($raw) > 20000) salir_json(413, ['ok' => false, 'error' => 'Datos demasiado grandes.']);
$d = json_decode($raw, true);
if (!is_array($d)) salir_json(400, ['ok' => false, 'error' => 'Datos inválidos.']);

// Token de partida de un solo uso
$p = $_SESSION['partida'] ?? null;
$nuevo = bin2hex(random_bytes(16));
$_SESSION['partida'] = ['token' => $nuevo, 't' => microtime(true)];
if (!$p || !hash_equals($p['token'], (string)($d['token'] ?? ''))) {
    salir_json(409, ['ok' => false, 'error' => 'Partida no reconocida. Recargue la página.', 'token' => $nuevo]);
}
$transcurridoMs = (microtime(true) - $p['t']) * 1000;

$resultado = (string)($d['resultado'] ?? '');
if (!in_array($resultado, ['lanzada', 'cancelada', 'agotada'], true)) salir_json(400, ['ok' => false, 'error' => 'Resultado inválido.', 'token' => $nuevo]);

$entero = function ($v, int $min, int $max): ?int {
    if ($v === null || $v === '') return null;
    if (!is_numeric($v)) return null;
    $n = (int)round((float)$v);
    return ($n < $min || $n > $max) ? null : $n;
};
$reac = $entero($d['tiempo_reaccion_ms'] ?? null, 50, 60000);
$total = $entero($d['tiempo_total_ms'] ?? null, 100, 60000);
$errores = $entero($d['errores'] ?? 0, 0, 500) ?? 0;
$vIni = $entero($d['valor_inicial'] ?? null, 0, PHP_INT_MAX);
$vLan = $entero($d['valor_lanzado'] ?? null, 0, PHP_INT_MAX);

if ($resultado === 'lanzada') {
    if ($total === null || $reac === null || $reac > $total) salir_json(400, ['ok' => false, 'error' => 'Tiempos inválidos.', 'token' => $nuevo]);
    // El tiempo reportado no puede superar lo transcurrido en el servidor desde que se entregó el token
    if ($total > $transcurridoMs + 2000) salir_json(400, ['ok' => false, 'error' => 'Tiempos inválidos.', 'token' => $nuevo]);
} else {
    $total = null;
}

$escenario = escenario_valido($d['escenario'] ?? 'lanzamiento-v1');

// Tiempo propio: el total menos lo que tardó la plataforma simulada en mostrar ventanas y verificar el captcha
$propio = $total;
if ($total !== null && $escenario === 'subasta-v1') {
    $dem = $d['detalle']['demoras'] ?? [];
    $espera = 0;
    foreach (['colocacion', 'captcha', 'atencion'] as $k) {
        $v = $dem[$k] ?? null;
        if (!is_numeric($v) || $v < 0 || $v > 20000) salir_json(400, ['ok' => false, 'error' => 'Tiempos inválidos.', 'token' => $nuevo]);
        $espera += (int)$v;
    }
    $propio = $total - $espera;
    if ($propio < 100) salir_json(400, ['ok' => false, 'error' => 'Tiempos inválidos.', 'token' => $nuevo]);
}
$detalle = json_encode($d['detalle'] ?? null, JSON_UNESCAPED_UNICODE);
if (strlen((string)$detalle) > 8000) $detalle = null;

$uid = (int)$u['id'];
$antes = estadisticas($uid, $escenario)['mejor'];
$cal = ultima_calibracion($uid);

q('INSERT INTO intentos (usuario_id, firma_id, escenario, resultado, tiempo_reaccion_ms, tiempo_total_ms, tiempo_propio_ms, valor_inicial, valor_lanzado, errores, calibracion_id, detalle) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
    [$uid, $u['firma_id'], $escenario, $resultado, $reac, $total, $propio, $vIni, $vLan, $errores, $cal['id'] ?? null, $detalle]);

$esRecord = $resultado === 'lanzada' && ($antes === null || $propio < $antes);
$posicion = null;
$rank = ranking_firma($u['firma_id'] ? (int)$u['firma_id'] : null, date('Y-m-d', strtotime('-30 days')), $escenario);
foreach ($rank as $i => $r) if ((int)$r['id'] === $uid) $posicion = $i + 1;

salir_json(200, [
    'ok' => true,
    'token' => $nuevo,
    'es_record' => $esRecord,
    'mejor_anterior' => $antes,
    'mejor' => $esRecord ? $propio : $antes,
    'propio' => $propio,
    'posicion' => $posicion,
    'total_ranking' => count($rank),
]);
