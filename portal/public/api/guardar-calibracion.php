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

$raw = $_POST['perfil'] ?? '';
if (!is_string($raw) || $raw === '' || strlen($raw) > 200000) salir_json(400, ['ok' => false, 'error' => 'El perfil está vacío o es demasiado grande.']);
$perfil = json_decode($raw, true);
if (!is_array($perfil)) salir_json(400, ['ok' => false, 'error' => 'El perfil no tiene un formato válido.']);
if (empty($perfil['autorizacionDatos'])) salir_json(400, ['ok' => false, 'error' => 'Falta la autorización de tratamiento de datos.']);

$uid = (int)$u['id'];

// Capturas
$guardadas = [];
if (!empty($_FILES['capturas']) && is_array($_FILES['capturas']['name'])) {
    $f = $_FILES['capturas'];
    if (count($f['name']) > 3) salir_json(400, ['ok' => false, 'error' => 'Máximo 3 capturas.']);
    $dir = rtrim((string)config('almacenamiento'), '/') . '/capturas/' . $uid;
    if (!is_dir($dir) && !@mkdir($dir, 0750, true)) salir_json(500, ['ok' => false, 'error' => 'No se pudo crear la carpeta de capturas.']);
    $tipos = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $pref = date('Ymd-His') . '-' . bin2hex(random_bytes(3));
    foreach ($f['name'] as $i => $nombre) {
        if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
        if ($f['error'][$i] !== UPLOAD_ERR_OK || $f['size'][$i] > 12 * 1024 * 1024) salir_json(400, ['ok' => false, 'error' => 'La captura ' . ($i + 1) . ' no se recibió o supera 12 MB.']);
        $mime = $finfo->file($f['tmp_name'][$i]);
        if (!isset($tipos[$mime])) salir_json(400, ['ok' => false, 'error' => 'La captura ' . ($i + 1) . ' debe ser PNG, JPG o WEBP.']);
        $archivo = $pref . '-' . ($i + 1) . '.' . $tipos[$mime];
        if (!move_uploaded_file($f['tmp_name'][$i], $dir . '/' . $archivo)) salir_json(500, ['ok' => false, 'error' => 'No se pudo guardar la captura.']);
        $guardadas[] = $archivo;
    }
}

// Resumen para la tabla
$mons = is_array($perfil['monitores'] ?? null) ? $perfil['monitores'] : [];
$prin = null;
foreach ($mons as $m) if (!empty($m['principal'])) { $prin = $m; break; }
$prin = $prin ?? ($mons[0] ?? null);
$resol = $prin ? round((float)$prin['w'] * (float)$prin['dpr']) . 'x' . round((float)$prin['h'] * (float)$prin['dpr']) : null;
$escala = ctype_digit((string)($perfil['escalaConfirmada'] ?? '')) ? (int)$perfil['escalaConfirmada'] : null;
$modo = ($perfil['modo'] ?? '') === 'mi-puesto' ? 'mi-puesto' : 'grabacion';
$resp = $perfil['pruebaAjuste']['respuesta'] ?? null;
$encaja = $resp === 'ok' ? 1 : ($resp === 'mal' ? 0 : null);
$perfil['capturas'] = $guardadas;
$perfil['servidor'] = ['recibido' => date('c'), 'userAgent' => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 400)];

q('INSERT INTO calibraciones (usuario_id, resolucion, escala, monitores, modo, encaja, perfil, capturas) VALUES (?,?,?,?,?,?,?,?)', [
    $uid, $resol, $escala, count($mons) ?: null, $modo, $encaja,
    json_encode($perfil, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    $guardadas ? json_encode($guardadas) : null,
]);
$calId = (string)db()->lastInsertId();
auditar('calibracion', $resol . ' ' . $escala . '% ' . $modo);

salir_json(200, ['ok' => true, 'codigo' => $calId]);
