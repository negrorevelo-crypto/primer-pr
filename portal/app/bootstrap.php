<?php
declare(strict_types=1);

date_default_timezone_set('America/Bogota');

// ---------- Configuración ----------
function config(?string $clave = null)
{
    static $cfg = null;
    if ($cfg === null) {
        $archivo = __DIR__ . '/config.php';
        if (!is_file($archivo)) {
            http_response_code(500);
            exit('Falta app/config.php. Copie app/config.example.php como app/config.php y complete los datos.');
        }
        $cfg = require $archivo;
    }
    if ($clave === null) return $cfg;
    $v = $cfg;
    foreach (explode('.', $clave) as $p) {
        if (!is_array($v) || !array_key_exists($p, $v)) return null;
        $v = $v[$p];
    }
    return $v;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = config('db');
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], (int)($c['puerto'] ?? 3306), $c['nombre']);
        try {
            $pdo = new PDO($dsn, $c['usuario'], $c['clave'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $pdo->exec("SET time_zone = '-05:00'");
        } catch (PDOException $e) {
            error_log('DB: ' . $e->getMessage());
            http_response_code(500);
            preg_match('/\[(\d{4})\]/', $e->getMessage(), $m);
            $causas = [
                '1045' => 'El usuario o la contraseña de la base de datos no son correctos (claves usuario y clave).',
                '1044' => 'El usuario no tiene acceso a esa base de datos: revise que el nombre (clave nombre) sea exacto y que el usuario pertenezca a esa base en Plesk.',
                '1049' => 'No existe una base de datos con ese nombre (clave nombre). Cópielo tal cual aparece en Plesk.',
                '2002' => 'No se encontró el servidor de base de datos (clave host). Use el que muestra Plesk en Bases de datos.',
                '2005' => 'No se encontró el servidor de base de datos (clave host). Use el que muestra Plesk en Bases de datos.',
                '2054' => 'El servidor MySQL pide otro método de autenticación. Cambie la contraseña del usuario en Plesk.',
            ];
            $codigo = $m[1] ?? '';
            header('Content-Type: text/html; charset=utf-8');
            exit('<p style="font:16px system-ui;margin:24px">No hay conexión con la base de datos. Revise app/config.php.<br>'
                . htmlspecialchars($causas[$codigo] ?? 'Código del error: ' . ($codigo ?: (string)$e->getCode()), ENT_QUOTES, 'UTF-8')
                . '<br><small>Servidor configurado: ' . htmlspecialchars($c['host'] . ':' . (int)($c['puerto'] ?? 3306) . ' · base: ' . $c['nombre'], ENT_QUOTES, 'UTF-8') . '</small></p>');
        }
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

// ---------- Sesión ----------
function iniciar_sesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('entrenamiento');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => $https]);
    session_start();
    // Cierre por inactividad: 8 horas
    if (isset($_SESSION['ultimo']) && time() - $_SESSION['ultimo'] > 28800) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['ultimo'] = time();
}
iniciar_sesion();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

// ---------- Utilidades ----------
function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function redirigir(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function base(): string
{
    // Ruta base del portal (permite instalarlo en la raíz del subdominio o en una carpeta)
    static $b = null;
    if ($b === null) {
        $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $b = preg_replace('#/(admin|api)$#', '', $dir);
    }
    return $b;
}

function url(string $ruta): string
{
    return base() . '/' . ltrim($ruta, '/');
}

function ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

function auditar(string $accion, string $detalle = '', ?int $usuarioId = null): void
{
    $u = $usuarioId ?? ($_SESSION['uid'] ?? null);
    q('INSERT INTO auditoria (usuario_id, accion, detalle, ip) VALUES (?, ?, ?, ?)', [$u, $accion, mb_substr($detalle, 0, 1000), ip()]);
}

function aviso(string $tipo, string $texto): void
{
    $_SESSION['avisos'][] = ['tipo' => $tipo, 'texto' => $texto];
}

function tomar_avisos(): array
{
    $a = $_SESSION['avisos'] ?? [];
    unset($_SESSION['avisos']);
    return $a;
}

// ---------- CSRF ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function csrf_verificar(): void
{
    $t = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF'] ?? '');
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) {
        http_response_code(419);
        exit('La sesión del formulario venció. Vuelva atrás y recargue la página.');
    }
}

function es_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

// ---------- Usuarios y acceso ----------
const ROLES = ['corredor' => 'Corredor', 'admin_firma' => 'Responsable de firma', 'admin' => 'Administrador'];
const PLANES = ['mensual' => 1, 'trimestral' => 3, 'semestral' => 6, 'anual' => 12, 'otro' => 0];

function usuario(): ?array
{
    static $u = false;
    if ($u !== false) return $u;
    $u = null;
    if (!empty($_SESSION['uid'])) {
        $u = q('SELECT u.*, f.nombre AS firma_nombre FROM usuarios u LEFT JOIN firmas f ON f.id = u.firma_id WHERE u.id = ? AND u.activo = 1', [$_SESSION['uid']])->fetch() ?: null;
        if (!$u) {
            $_SESSION = [];
        }
    }
    return $u;
}

function es_admin(): bool
{
    return (usuario()['rol'] ?? '') === 'admin';
}

function requiere_login(): array
{
    $u = usuario();
    if (!$u) {
        $_SESSION['volver'] = $_SERVER['REQUEST_URI'] ?? '';
        redirigir(url('login.php'));
    }
    $actual = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($u['debe_cambiar_clave'] && !in_array($actual, ['cambiar-clave.php', 'salir.php'], true)) {
        redirigir(url('cambiar-clave.php'));
    }
    return $u;
}

function requiere_admin(): array
{
    $u = requiere_login();
    if ($u['rol'] !== 'admin') {
        http_response_code(403);
        redirigir(url('inicio.php'));
    }
    return $u;
}

/** Licencia vigente hoy, o null */
function licencia_vigente(int $usuarioId): ?array
{
    return q("SELECT * FROM licencias WHERE usuario_id = ? AND estado = 'activa' AND CURDATE() BETWEEN inicio AND fin ORDER BY fin DESC LIMIT 1", [$usuarioId])->fetch() ?: null;
}

function requiere_licencia(): array
{
    $u = requiere_login();
    if ($u['rol'] === 'admin') return $u;
    if (!licencia_vigente((int)$u['id'])) {
        redirigir(url('inicio.php'));
    }
    return $u;
}

function intentar_login(string $usuario, string $clave): array
{
    $u = q('SELECT * FROM usuarios WHERE usuario = ?', [$usuario])->fetch();
    // Hash de relleno para que el tiempo de respuesta no revele si el usuario existe
    $hash = $u['clave_hash'] ?? '$2y$10$0jHHOYzX0dgd/X5L/fmTKeua2zbdvChv99zhSn82i4tV8o81/9DbS';
    if ($u && $u['bloqueado_hasta'] && strtotime($u['bloqueado_hasta']) > time()) {
        return [false, 'Cuenta bloqueada por intentos fallidos. Intente de nuevo en unos minutos.'];
    }
    $ok = password_verify($clave, $hash);
    if (!$u || !$ok) {
        if ($u) {
            $fallos = (int)$u['intentos_fallidos'] + 1;
            $bloqueo = $fallos >= 5 ? date('Y-m-d H:i:s', time() + 900) : null;
            q('UPDATE usuarios SET intentos_fallidos = ?, bloqueado_hasta = ? WHERE id = ?', [$bloqueo ? 0 : $fallos, $bloqueo, $u['id']]);
            auditar('login_fallido', $usuario, (int)$u['id']);
        }
        return [false, 'Usuario o contraseña incorrectos.'];
    }
    if (!$u['activo']) {
        return [false, 'Esta cuenta está desactivada. Comuníquese con el administrador.'];
    }
    if (password_needs_rehash($u['clave_hash'], PASSWORD_DEFAULT)) {
        q('UPDATE usuarios SET clave_hash = ? WHERE id = ?', [password_hash($clave, PASSWORD_DEFAULT), $u['id']]);
    }
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$u['id'];
    q('UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = NOW() WHERE id = ?', [$u['id']]);
    auditar('login', '', (int)$u['id']);
    return [true, ''];
}

function generar_clave(int $largo = 10): string
{
    // Sin caracteres que se confunden (0/O, 1/l/I)
    $c = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $s = '';
    for ($i = 0; $i < $largo; $i++) $s .= $c[random_int(0, strlen($c) - 1)];
    return $s;
}

function validar_clave_nueva(string $clave, string $usuario): ?string
{
    if (mb_strlen($clave) < 8) return 'La contraseña debe tener al menos 8 caracteres.';
    if (!preg_match('/[A-Za-z]/', $clave) || !preg_match('/\d/', $clave)) return 'Use letras y números.';
    if (mb_strtolower($clave) === mb_strtolower($usuario)) return 'La contraseña no puede ser igual al usuario.';
    return null;
}

// ---------- Formatos ----------
function fmt_s(?int $ms): string
{
    if ($ms === null) return '—';
    return number_format($ms / 1000, 3, ',', '.') . ' s';
}

function fmt_cop($v): string
{
    if ($v === null || $v === '') return '—';
    return '$' . number_format((float)$v, 0, ',', '.');
}

function fmt_fecha(?string $f, bool $hora = false): string
{
    if (!$f) return '—';
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $t = strtotime($f);
    $s = date('j', $t) . ' ' . $meses[(int)date('n', $t) - 1] . ' ' . date('Y', $t);
    if ($hora) $s .= ', ' . date('g:i a', $t);
    return $s;
}

function dias_hasta(string $fecha): int
{
    return (int)floor((strtotime($fecha . ' 23:59:59') - time()) / 86400);
}

function sumar_meses(string $fecha, int $meses): string
{
    $d = new DateTime($fecha);
    $d->modify('+' . $meses . ' months')->modify('-1 day');
    return $d->format('Y-m-d');
}

require __DIR__ . '/vista.php';
require __DIR__ . '/datos.php';
