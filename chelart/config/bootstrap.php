<?php
declare(strict_types=1);

/** Read hosting settings from real environment variables or the private Plesk file. */
function chelart_setting(string $key, string $default = ''): string
{
    $environmentValue = getenv($key);
    if ($environmentValue === false && isset($_SERVER[$key])) {
        $environmentValue = (string)$_SERVER[$key];
    }
    if ($environmentValue === false && isset($_ENV[$key])) {
        $environmentValue = (string)$_ENV[$key];
    }
    if ($environmentValue !== false && $environmentValue !== '') {
        return (string)$environmentValue;
    }

    static $privateSettings = null;
    if ($privateSettings === null) {
        $settingsFile = __DIR__ . '/plesk-secrets.php';
        $loaded = is_file($settingsFile) && is_readable($settingsFile) ? require $settingsFile : [];
        $privateSettings = is_array($loaded) ? $loaded : [];
    }

    $value = $privateSettings[$key] ?? $default;
    return is_scalar($value) ? (string)$value : $default;
}

/** Dominio oficial: los demás nombres del sitio redirigen (301) a https://chelart.com */
const CHELART_CANONICAL_HOST = 'chelart.com';
(static function (): void {
    $host = strtolower(preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
    $redirectHosts = ['chelartconstruction.revelco.com.co', 'www.chelart.com'];
    if (PHP_SAPI === 'cli' || !in_array($host, $redirectHosts, true)) {
        return;
    }
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
    if (str_starts_with($uri, '/plesk-site-preview/')) {
        return;
    }
    header('Location: https://' . CHELART_CANONICAL_HOST . $uri, true, 301);
    exit;
})();

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Keep site links and assets inside Plesk's temporary preview path when used. */
function app_base_path(): string
{
    $requestPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
    if (preg_match('~^(/plesk-site-preview/[^/]+/)~', $requestPath, $matches)) {
        return $matches[1];
    }

    return '/';
}

