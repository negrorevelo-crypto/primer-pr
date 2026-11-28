<?php
declare(strict_types=1);

/**
 * Acceso al módulo CHRISTMAS SERVICE LEADS. Reutiliza la sesión del historial
 * privado existente (/admin/): mismo usuario, contraseña, HTTPS obligatorio,
 * vencimiento por inactividad (30 min) y token CSRF. No hay un login nuevo.
 */
function christmas_admin_require_login(): void
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    if (!$isHttps) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        exit('El módulo privado solo está disponible por HTTPS.');
    }
    $now = time();
    $active = !empty($_SESSION['quote_admin_authenticated'])
        && ($now - (int)($_SESSION['quote_admin_last_activity'] ?? 0)) <= 1800;
    if (!$active) {
        unset($_SESSION['quote_admin_authenticated'], $_SESSION['quote_admin_last_activity']);
        header('Location: ./?next=christmas', true, 303);
        exit;
    }
    $_SESSION['quote_admin_last_activity'] = $now;
    if (empty($_SESSION['quote_admin_csrf'])) {
        $_SESSION['quote_admin_csrf'] = bin2hex(random_bytes(32));
    }
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store, private');
}
