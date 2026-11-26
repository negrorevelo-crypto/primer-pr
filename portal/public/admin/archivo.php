<?php
// Entrega archivos privados (perfil de calibración y capturas) solo al administrador
require dirname(__DIR__, 2) . '/app/bootstrap.php';
requiere_admin();

if (isset($_GET['calibracion']) && ctype_digit((string)$_GET['calibracion'])) {
    $perfil = q('SELECT perfil FROM calibraciones WHERE id = ?', [(int)$_GET['calibracion']])->fetchColumn();
    if ($perfil === false) { http_response_code(404); exit('No encontrado'); }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(json_decode($perfil, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$uid = (string)($_GET['usuario'] ?? '');
$f = (string)($_GET['captura'] ?? '');
if (ctype_digit($uid) && preg_match('/^[0-9A-Za-z\-]+\.(png|jpg|webp)$/', $f)) {
    $ruta = rtrim((string)config('almacenamiento'), '/') . '/capturas/' . $uid . '/' . $f;
    if (is_file($ruta)) {
        header('Content-Type: ' . ['png' => 'image/png', 'jpg' => 'image/jpeg', 'webp' => 'image/webp'][pathinfo($f, PATHINFO_EXTENSION)]);
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit;
    }
}
http_response_code(404);
echo 'No encontrado';
