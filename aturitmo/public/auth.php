<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
require_csrf();
$action = (string) ($_POST['action'] ?? 'login');

function set_login_background(): void
{
    $previous = filter_var($_COOKIE['sumaritmo_background_index'] ?? null, FILTER_VALIDATE_INT);
    $imageCount = 9;
    $index = $previous === false || $previous === null
        ? random_int(0, $imageCount - 1)
        : (((int) $previous + 1) % $imageCount);
    setcookie('sumaritmo_background_index', (string) $index, [
        'expires' => time() + 31536000,
        'path' => '/',
        'secure' => app_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_SESSION['background_index'] = $index;
    $_SESSION['login_token'] = bin2hex(random_bytes(12));
}

if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: index.php');
    exit;
}

$attempts = (int) ($_SESSION['auth_attempts'] ?? 0);
$lastAttempt = (int) ($_SESSION['auth_last_attempt'] ?? 0);
if ($attempts >= 8 && time() - $lastAttempt < 900) {
    header('Location: index.php?error=espera');
    exit;
}
if (time() - $lastAttempt >= 900) {
    $attempts = 0;
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || $password === '') {
    header('Location: index.php?error=datos');
    exit;
}

try {
    $pdo = db();
    if ($action === 'register') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $ageRange = trim((string) ($_POST['age_range'] ?? ''));
        $allowedAgeRanges = ['18-29', '30-44', '45-59', '60-64', '65-74', '75+'];
        if ($name === '' || mb_strlen($name) > 80 || mb_strlen($password) < 10 || !in_array($ageRange, $allowedAgeRanges, true) || ($_POST['adult_confirm'] ?? '') !== '1' || ($_POST['terms_accept'] ?? '') !== '1') {
            header('Location: index.php?crear=1&error=registro');
            exit;
        }
        $termsVersion = '2026-11-21-v3';
        $stmt = $pdo->prepare('INSERT INTO users (display_name, email, password_hash, age_range, adult_confirmed_at, terms_accepted_at, terms_version) VALUES (:name, :email, :password_hash, :age_range, NOW(), NOW(), :terms_version)');
        $stmt->execute(['name' => $name, 'email' => $email, 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'age_range' => $ageRange, 'terms_version' => $termsVersion]);
        $id = (int) $pdo->lastInsertId();
        $_SESSION['auth_attempts'] = 0;
        session_regenerate_id(true);
        $_SESSION['user_id'] = $id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['weekly_goal'] = 150;
        $_SESSION['age_range'] = $ageRange;
        $_SESSION['goal_focus'] = 'bienestar';
        set_login_background();
        header('Location: index.php?bienvenida=1');
        exit;
    }

    $stmt = $pdo->prepare('SELECT id, display_name, email, password_hash, weekly_activity_goal_minutes, age_range, goal_focus FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    if (!$user) {
        $_SESSION['auth_attempts'] = $attempts + 1;
        $_SESSION['auth_last_attempt'] = time();
        header('Location: index.php?aviso=usuario_no_existe');
        exit;
    }
    if (!password_verify($password, (string) $user['password_hash'])) {
        $_SESSION['auth_attempts'] = $attempts + 1;
        $_SESSION['auth_last_attempt'] = time();
        header('Location: index.php?error=credenciales');
        exit;
    }
    $_SESSION['auth_attempts'] = 0;
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = (string) $user['display_name'];
    $_SESSION['user_email'] = (string) $user['email'];
    $_SESSION['weekly_goal'] = (int) $user['weekly_activity_goal_minutes'];
    $_SESSION['age_range'] = (string) ($user['age_range'] ?? '');
    $_SESSION['goal_focus'] = (string) ($user['goal_focus'] ?? 'bienestar');
    set_login_background();
    header('Location: index.php');
    exit;
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000' && $action === 'register') {
        header('Location: index.php?crear=1&error=correo');
        exit;
    }
    error_log('SumaRitmo auth database failure: ' . $exception->getMessage());
    header('Location: index.php?error=base');
    exit;
} catch (Throwable $exception) {
    error_log('SumaRitmo auth failure: ' . $exception->getMessage());
    header('Location: index.php?error=base');
    exit;
}
