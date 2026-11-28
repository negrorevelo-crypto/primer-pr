<?php
// Router para el servidor integrado de PHP (alternativa sin Docker).
// Sirve chelart/public como raíz web, igual que el Document root de Plesk.
$_SERVER['HTTPS'] = 'on'; // /admin/ exige HTTPS; en localhost se simula

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$file = $_SERVER['DOCUMENT_ROOT'] . $path;

if (is_dir($file)) {
    $file = rtrim($file, '/') . '/index.php';
    if (is_file($file)) {
        chdir(dirname($file));
        require $file;
        return true;
    }
}
return false; // archivos estáticos y .php existentes los sirve PHP directamente
