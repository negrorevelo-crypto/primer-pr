<?php
declare(strict_types=1);

$configFile = __DIR__ . '/plesk-secrets.php';
$privateConfig = is_file($configFile) ? require $configFile : [];
if (!is_array($privateConfig)) {
    $privateConfig = [];
}
date_default_timezone_set('America/Bogota');

function app_config(string $key, ?string $default = null): ?string
{
    global $privateConfig;
    $value = $privateConfig[$key] ?? getenv($key);
    return is_string($value) && $value !== '' ? $value : $default;
}

function app_is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => app_is_https(),
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net 'wasm-unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; media-src 'self' blob:; font-src 'self'; connect-src 'self' https://cdn.jsdelivr.net https://tessdata.projectnaptha.com; worker-src 'self' blob:; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $host = app_config('DB_HOST', 'localhost');
    $port = app_config('DB_PORT', '3306');
    $name = app_config('DB_NAME');
    $user = app_config('DB_USER');
    $password = app_config('DB_PASSWORD');
    if (!$name || !$user || !$password) {
        throw new RuntimeException('La base de datos todavía no está configurada.');
    }
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf_token'];
}

function require_csrf(): void
{
    $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!is_string($provided) || !hash_equals(csrf_token(), $provided)) {
        http_response_code(403);
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'La sesión venció. Recarga la página e inténtalo de nuevo.']);
        } else {
            echo 'La solicitud venció. Recarga la página e inténtalo de nuevo.';
        }
        exit;
    }
}

function current_user(): ?array
{
    return isset($_SESSION['user_id']) ? [
        'id' => (int) $_SESSION['user_id'],
        'name' => (string) ($_SESSION['user_name'] ?? 'Amigo'),
        'email' => (string) ($_SESSION['user_email'] ?? ''),
        'weekly_goal' => (int) ($_SESSION['weekly_goal'] ?? 150),
        'age_range' => (string) ($_SESSION['age_range'] ?? ''),
        'goal_focus' => (string) ($_SESSION['goal_focus'] ?? 'bienestar'),
    ] : null;
}

function require_user(): array
{
    $user = current_user();
    if (!$user) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Inicia sesión para continuar.']);
        exit;
    }
    return $user;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
