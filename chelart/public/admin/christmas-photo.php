<?php
declare(strict_types=1);

// Entrega una foto adjunta solo a usuarios con sesión en el área privada.
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
require_once dirname(__DIR__, 2) . '/config/christmas.php';
require_once dirname(__DIR__, 2) . '/config/christmas_admin.php';
christmas_admin_require_login();
require_once dirname(__DIR__, 2) . '/config/database.php';

$id = (string)($_GET['id'] ?? '');
if (!ctype_digit($id)) {
    http_response_code(404);
    exit;
}
try {
    $statement = database()->prepare('SELECT stored_name, mime_type, original_name FROM christmas_quote_photos WHERE id = :id LIMIT 1');
    $statement->execute([':id' => (int)$id]);
    $photo = $statement->fetch();
} catch (Throwable $error) {
    error_log('Chelart Christmas photo lookup failed: ' . $error->getMessage());
    http_response_code(503);
    exit;
}
if (!$photo || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', (string)$photo['stored_name']) || !isset(CHRISTMAS_PHOTO_TYPES[$photo['mime_type']])) {
    http_response_code(404);
    exit;
}
$path = christmas_upload_dir() . DIRECTORY_SEPARATOR . $photo['stored_name'];
if (!is_file($path)) {
    http_response_code(404);
    exit;
}
$download = isset($_GET['download']);
$filename = preg_replace('/[^A-Za-z0-9._-]/', '_', (string)$photo['original_name']) ?: 'photo';
header('Content-Type: ' . $photo['mime_type']);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . $filename . '"');
header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'");
readfile($path);
