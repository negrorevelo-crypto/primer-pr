<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function responder(int $code, array $data): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function fallar(int $code, string $msg): void {
    responder($code, ['ok' => false, 'error' => $msg]);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    fallar(405, 'Método no permitido');
}

$raw = $_POST['perfil'] ?? '';
if (!is_string($raw) || $raw === '' || strlen($raw) > 200000) {
    fallar(400, 'El perfil está vacío o es demasiado grande.');
}
$perfil = json_decode($raw, true);
if (!is_array($perfil)) {
    fallar(400, 'El perfil no tiene un formato válido.');
}

$nombre = trim((string)($perfil['corredor']['nombre'] ?? ''));
if ($nombre === '' || mb_strlen($nombre) > 120) {
    fallar(400, 'Falta el nombre del corredor.');
}
if (empty($perfil['autorizacionDatos'])) {
    fallar(400, 'Falta la autorización de tratamiento de datos.');
}

$dirPerfiles = DATA_DIR . '/perfiles';
$dirCapturas = DATA_DIR . '/capturas';
foreach ([$dirPerfiles, $dirCapturas] as $d) {
    if (!is_dir($d) && !@mkdir($d, 0750, true)) {
        fallar(500, 'El servidor no pudo crear la carpeta de datos. Revise DATA_DIR en config.php.');
    }
}

$codigo = date('Ymd-His') . '-' . bin2hex(random_bytes(3));

// Capturas de pantalla (opcionales)
$capturas = [];
if (!empty($_FILES['capturas']) && is_array($_FILES['capturas']['name'])) {
    $f = $_FILES['capturas'];
    $total = count($f['name']);
    if ($total > MAX_CAPTURAS) {
        fallar(400, 'Máximo ' . MAX_CAPTURAS . ' capturas por envío.');
    }
    $tipos = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    for ($i = 0; $i < $total; $i++) {
        if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
        if ($f['error'][$i] !== UPLOAD_ERR_OK) {
            fallar(400, 'No se pudo recibir la captura ' . ($i + 1) . '. Puede ser demasiado grande.');
        }
        if ($f['size'][$i] > MAX_CAPTURA_MB * 1024 * 1024) {
            fallar(400, 'La captura ' . ($i + 1) . ' supera ' . MAX_CAPTURA_MB . ' MB.');
        }
        $mime = $finfo->file($f['tmp_name'][$i]);
        if (!isset($tipos[$mime])) {
            fallar(400, 'La captura ' . ($i + 1) . ' debe ser PNG, JPG o WEBP.');
        }
        $dim = @getimagesize($f['tmp_name'][$i]);
        $archivo = $codigo . '-' . ($i + 1) . '.' . $tipos[$mime];
        if (!move_uploaded_file($f['tmp_name'][$i], $dirCapturas . '/' . $archivo)) {
            fallar(500, 'No se pudo guardar la captura ' . ($i + 1) . '.');
        }
        $capturas[] = [
            'archivo' => $archivo,
            'nombreOriginal' => mb_substr((string)$f['name'][$i], 0, 200),
            'ancho' => $dim[0] ?? null,
            'alto' => $dim[1] ?? null,
            'bytes' => (int)$f['size'][$i],
        ];
    }
}

$perfil['capturas'] = $capturas;
$perfil['servidor'] = [
    'codigo' => $codigo,
    'recibido' => date('c'),
    'userAgent' => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 400),
];

$ok = file_put_contents(
    $dirPerfiles . '/' . $codigo . '.json',
    json_encode($perfil, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    LOCK_EX
);
if ($ok === false) {
    fallar(500, 'No se pudo guardar el perfil.');
}

responder(200, ['ok' => true, 'codigo' => $codigo, 'capturas' => count($capturas)]);
