<?php
// Guarda los tiempos de la plataforma que elige cada corredor para su práctica
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

$d = json_decode((string)file_get_contents('php://input'), true);
$perfil = is_array($d) ? (string)($d['perfil'] ?? '') : '';
$min = tiempos_minimos();

if ($perfil === 'oficial') {
    $guardar = ['perfil' => 'oficial'];
} elseif (isset(TIEMPOS_PERFILES[$perfil])) {
    $guardar = ['perfil' => $perfil];
} elseif ($perfil === 'personalizado') {
    $t = tiempos_validos($d['tiempos'] ?? null);
    if (count($t) !== count(TIEMPOS_MOMENTOS)) salir_json(400, ['ok' => false, 'error' => 'Revise los valores: mínimo ≤ típico ≤ máximo, y máximo 20 segundos.']);
    foreach ($t as $k => $v) {
        if ($v[0] < $min[$k]) salir_json(400, ['ok' => false, 'error' => TIEMPOS_MOMENTOS[$k][0] . ': el mínimo permitido es ' . number_format($min[$k] / 1000, 2, ',', '') . ' s.']);
    }
    $guardar = ['perfil' => 'personalizado', 'tiempos' => $t];
} else {
    salir_json(400, ['ok' => false, 'error' => 'Perfil no válido.']);
}

q('UPDATE usuarios SET ajustes_juego = ? WHERE id = ?', [json_encode($guardar), (int)$u['id']]);
$u['ajustes_juego'] = json_encode($guardar);
salir_json(200, ['ok' => true, 'perfil' => $guardar['perfil'], 'tiempos' => tiempos_para_usuario($u)]);
