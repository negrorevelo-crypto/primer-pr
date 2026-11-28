<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';
$user = current_user();
$backgroundFiles = ['futbol.jpg', 'padel.jpg', 'natacion.jpg', 'tenis.jpg', 'correr.jpg', 'ciclismo-atardecer.jpg', 'baloncesto.jpg', 'senderismo.jpg', 'fuerza.jpg'];
$appBackground = '';
if ($user) {
    if (empty($_SESSION['login_token'])) {
        $_SESSION['login_token'] = bin2hex(random_bytes(12));
    }
    if (!isset($_SESSION['background_index'])) {
        $savedIndex = filter_var($_COOKIE['sumaritmo_background_index'] ?? null, FILTER_VALIDATE_INT);
        $index = $savedIndex !== false && $savedIndex !== null && $savedIndex >= 0 && $savedIndex < count($backgroundFiles)
            ? $savedIndex
            : random_int(0, count($backgroundFiles) - 1);
        $_SESSION['background_index'] = $index;
        if ($savedIndex === false || $savedIndex === null) {
            setcookie('sumaritmo_background_index', (string) $index, [
                'expires' => time() + 31536000,
                'path' => '/',
                'secure' => app_is_https(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }
    $appBackground = 'assets/images/' . $backgroundFiles[(int) $_SESSION['background_index'] % count($backgroundFiles)];
}
$creating = isset($_GET['crear']);
$errors = [
    'datos' => 'Revisa tu correo y contraseña.',
    'credenciales' => 'El correo o la contraseña no coinciden.',
    'espera' => 'Hubo varios intentos seguidos. Espera unos minutos y vuelve a intentar.',
    'registro' => 'Completa los datos, confirma que eres mayor de edad, acepta el acuerdo de uso y usa una contraseña de al menos 10 caracteres.',
    'correo' => 'Ese correo ya tiene una cuenta. Inicia sesión.',
    'base' => 'No pudimos conectar con el servicio. Revisa la configuración e inténtalo de nuevo.',
];
$error = $errors[(string) ($_GET['error'] ?? '')] ?? '';
$showAuth = $creating || $error !== '' || isset($_GET['entrar']) || isset($_GET['eliminada']);
$showAuth = $showAuth || isset($_GET['bienvenida']);
if (!$user && !$showAuth) {
    require __DIR__ . '/landing.php';
    exit;
}
$days = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$todayLabel = $days[(int) date('w')] . ', ' . date('j') . ' de ' . $months[(int) date('n') - 1];
$sportOptions = ['Fútbol', 'Pádel', 'Tenis', 'Natación', 'Correr', 'Ciclismo', 'Gimnasio / fuerza', 'Movilidad o equilibrio', 'Caminata', 'Baloncesto', 'Baile', 'Yoga', 'Otro'];
$preferenceLabels = ['Gimnasio / fuerza' => 'Fuerza', 'Movilidad o equilibrio' => 'Movilidad'];
$ageOptions = ['18-29' => '18 a 29 años', '30-44' => '30 a 44 años', '45-59' => '45 a 59 años', '60-64' => '60 a 64 años', '65-74' => '65 a 74 años', '75+' => '75 años o más'];
function icon(string $name, string $class = 'icon'): string
{
    return '<svg class="' . $class . '" aria-hidden="true" focusable="false"><use href="#i-' . $name . '"/></svg>';
}
?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#F0ECE6">
  <meta name="description" content="SumaRitmo te ayuda a entender tu ritmo y mejorar frente a ti mismo.">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= $user ? 'Mi día' : ($creating ? 'Crear cuenta' : 'Iniciar sesión') ?> — SumaRitmo</title>
  <link rel="preload" href="assets/fonts/archivo-latin.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="assets/app.css?v=20261128-1">
  <script src="assets/app.js?v=20261128-1" defer></script>
<?php if ($user): ?>
  <script src="assets/share.js?v=20261128-1" defer></script>
<?php endif; ?>
</head>
<body class="<?= $user ? 'is-app' : 'is-auth' ?>"<?= $user ? ' data-login-token="' . e((string) ($_SESSION['login_token'] ?? '')) . '"' : '' ?>>
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <defs>
    <symbol id="i-wave" viewBox="0 0 24 24"><path d="M3 13.5c2.2-5.4 4.6-5.4 6.4-1.5 1.8 3.9 4.2 3.9 6.2-1.5 1.2-3.2 3-4 5.4-2.6"/></symbol>
    <symbol id="i-day" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2.8v2.4M12 18.8v2.4M2.8 12h2.4M18.8 12h2.4M5.5 5.5l1.7 1.7M16.8 16.8l1.7 1.7M5.5 18.5l1.7-1.7M16.8 7.2l1.7-1.7"/></symbol>
    <symbol id="i-progress" viewBox="0 0 24 24"><path d="M3.5 17.5l5.5-5.5 4 4 7.5-8"/><path d="M15 8h5.5v5.5"/></symbol>
    <symbol id="i-space" viewBox="0 0 24 24"><circle cx="12" cy="8.5" r="3.8"/><path d="M4.5 20.5c1-4 4-6 7.5-6s6.5 2 7.5 6"/></symbol>
    <symbol id="i-activity" viewBox="0 0 24 24"><path d="M2.5 12.5h4l2.5-6 4.5 12 2.6-6h5.4"/></symbol>
    <symbol id="i-sleep" viewBox="0 0 24 24"><path d="M19.5 14.6A7.8 7.8 0 0 1 9.4 4.5a7.8 7.8 0 1 0 10.1 10.1z"/></symbol>
    <symbol id="i-food" viewBox="0 0 24 24"><path d="M3.5 11h17a8.5 8.5 0 0 1-17 0z"/><path d="M9 7.5c0-1.5 1.5-1.5 1.5-3M13.5 7.5c0-1.5 1.5-1.5 1.5-3"/></symbol>
    <symbol id="i-mood" viewBox="0 0 24 24"><path d="M12 20s-7.5-4.6-7.5-10A4.3 4.3 0 0 1 12 7.4 4.3 4.3 0 0 1 19.5 10c0 5.4-7.5 10-7.5 10z"/></symbol>
    <symbol id="i-spark" viewBox="0 0 24 24"><path d="M12 3v18M3 12h18M5.6 5.6l12.8 12.8M18.4 5.6L5.6 18.4"/></symbol>
    <symbol id="i-share" viewBox="0 0 24 24"><path d="M12 15V3.5M7.5 8 12 3.5 16.5 8"/><path d="M5 12v6.5A2 2 0 0 0 7 20.5h10a2 2 0 0 0 2-2V12"/></symbol>
    <symbol id="i-arrow" viewBox="0 0 24 24"><path d="M4.5 12h15M13.5 6l6 6-6 6"/></symbol>
    <symbol id="i-refresh" viewBox="0 0 24 24"><path d="M19.5 12a7.5 7.5 0 1 1-2.2-5.3"/><path d="M19.5 4.5v4.5H15"/></symbol>
    <symbol id="i-info" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5"/><path d="M12 11v5.5M12 7.6v.1"/></symbol>
    <symbol id="i-close" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></symbol>
    <symbol id="i-route" viewBox="0 0 24 24"><circle cx="6" cy="18" r="2.2"/><circle cx="18" cy="6" r="2.2"/><path d="M8 17.5h6.5a3 3 0 0 0 0-6h-5a3 3 0 0 1 0-6H16"/></symbol>
    <symbol id="i-pin" viewBox="0 0 24 24"><path d="M12 21s-6.5-6-6.5-11a6.5 6.5 0 0 1 13 0c0 5-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/></symbol>
    <symbol id="i-video" viewBox="0 0 24 24"><rect x="3" y="6" width="13" height="12" rx="2"/><path d="M16 10.5l5-3v9l-5-3z"/></symbol>
    <symbol id="i-image" viewBox="0 0 24 24"><rect x="3.5" y="4" width="17" height="16" rx="2"/><circle cx="9" cy="9.5" r="1.8"/><path d="M4 17.5l5-5 4 4 2.5-2.5 4.5 4.5"/></symbol>
    <symbol id="i-download" viewBox="0 0 24 24"><path d="M12 4v11.5M7.5 11 12 15.5 16.5 11"/><path d="M4.5 19.5h15"/></symbol>
  </defs>
</svg>
<?php if (!$user): ?>
  <main class="auth-shell">
    <a class="wordmark auth-wordmark" href="index.php"><span class="mark"><?= icon('wave') ?></span>SumaRitmo<span class="wordmark-dot">.</span></a>
    <section class="auth-card">
      <div class="auth-art" aria-hidden="true"><span class="art-sun"></span><span class="art-line line-one"></span><span class="art-line line-two"></span><div class="art-message">Muévete,<br>descansa,<br><em>disfruta.</em></div><span class="art-footnote">Tu bienestar, a tu manera.</span></div>
      <div class="auth-form-wrap">
        <h1><?= $creating ? 'Empieza a tu ritmo.' : 'Qué bueno verte.' ?></h1>
        <p class="auth-subtitle"><?= $creating ? 'Crea tu espacio y cuéntanos qué te gustaría mejorar en tus deportes, hábitos y bienestar.' : 'Entra a tu espacio y sigue sumando.' ?></p>
        <?php if ($error): ?><div class="form-alert" role="alert"><?= e($error) ?></div><?php elseif (isset($_GET['bienvenida'])): ?><div class="form-notice">Tu cuenta está lista. Empieza registrando tus deportes y hábitos.</div><?php elseif (isset($_GET['eliminada'])): ?><div class="form-notice">Se eliminó tu cuenta y sus registros.</div><?php endif; ?>
        <form method="post" action="auth.php" class="auth-form" autocomplete="on">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="<?= $creating ? 'register' : 'login' ?>">
          <?php if ($creating): ?><label>¿Cómo te llamas?<input name="name" type="text" maxlength="80" autocomplete="name" placeholder="Tu nombre" required></label>
          <label>¿En qué rango de edad estás?<select name="age_range" id="register-age-range" required><option value="">Elige un rango</option><?php foreach ($ageOptions as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?></select></label>
          <label class="check-row"><input type="checkbox" name="adult_confirm" id="adult-confirm" value="1" required><span>Confirmo que tengo 18 años o más.</span></label>
          <section class="terms-box" aria-labelledby="terms-title">
            <details>
              <summary id="terms-title">Lee el acuerdo de uso y los límites de SumaRitmo</summary>
              <div class="terms-copy">
                <h2>Acuerdo de uso responsable</h2>
                <p><strong>Qué ofrece.</strong> SumaRitmo ayuda a registrar actividad, descanso, alimentación y sensaciones, y ofrece orientación general a partir de esos datos. No presta atención médica, no diagnostica ni trata enfermedades, no prescribe ejercicio, alimentación o medicamentos, y no sustituye a profesionales de salud o entrenamiento.</p>
                <p><strong>Tus decisiones y tu seguridad.</strong> Las sugerencias son orientativas, pueden ser incompletas y no detectan todas las lesiones o problemas de salud. Tú decides si seguirlas y eres quien mejor conoce cómo se siente tu cuerpo. Adapta o detén la actividad si algo no se siente bien; consulta a un profesional antes de cambiar tu actividad si tienes una condición médica, una lesión o dudas sobre tu salud.</p>
                <p><strong>Señales de alarma.</strong> Detén el ejercicio y busca atención médica si presentas dolor o presión en el pecho, desmayo, mareo intenso, falta de aire inusual, dolor o hinchazón muscular intensos, debilidad marcada u orina oscura después del ejercicio. Ante síntomas graves o urgentes, contacta los servicios de emergencia de tu zona.</p>
                <p><strong>Sin promesas de resultados.</strong> Los registros y recomendaciones no garantizan mejoras ni pueden asegurar que se eviten lesiones o daños. Este acuerdo explica el alcance de la herramienta y no elimina derechos ni responsabilidades que no puedan excluirse conforme a la ley.</p>
                <p>SumaRitmo está dirigido únicamente a personas de 18 años o más.</p>
              </div>
            </details>
            <label class="check-row"><input type="checkbox" name="terms_accept" value="1" required><span>Leí y acepto este acuerdo y autorizo el tratamiento de datos según la <a href="privacy.php" target="_blank" rel="noopener">Política de Tratamiento de Datos</a>. Entiendo que SumaRitmo ofrece orientación general, no atención médica.</span></label>
          </section><?php endif; ?>
          <label>Correo electrónico<input name="email" type="email" maxlength="254" autocomplete="email" inputmode="email" placeholder="tu@correo.com" required></label>
          <label>Contraseña<input name="password" type="password" minlength="<?= $creating ? '10' : '1' ?>" autocomplete="<?= $creating ? 'new-password' : 'current-password' ?>" placeholder="<?= $creating ? 'Al menos 10 caracteres' : 'Tu contraseña' ?>" required></label>
          <?php if ($creating): ?><p class="password-hint">Usa al menos 10 caracteres. No reutilices una contraseña importante.</p><?php endif; ?>
          <button class="button-primary auth-submit" type="submit"><?= $creating ? 'Crear mi cuenta' : 'Iniciar sesión' ?> <?= icon('arrow') ?></button>
        </form>
        <p class="auth-switch"><?= $creating ? '¿Ya tienes cuenta?' : '¿Es tu primera vez?' ?> <a href="index.php<?= $creating ? '?entrar=1' : '?crear=1' ?>"><?= $creating ? 'Inicia sesión' : 'Crea tu cuenta' ?></a></p>
        <p class="auth-switch"><a href="index.php">Volver a SumaRitmo</a></p>
        <p class="auth-privacy">Tu rango nos ayuda a adaptar la orientación. No pedimos tu fecha de nacimiento. <a href="privacy.php" target="_blank" rel="noopener">Consulta la Política de Tratamiento de Datos.</a></p>
      </div>
    </section>
    <footer class="auth-footer">SumaRitmo · Cada paso y cada pausa también cuentan.</footer>
  </main>
<?php else: ?>
  <div class="app-shell">
    <aside class="sidebar">
      <a class="wordmark" href="index.php"><span class="mark"><?= icon('wave') ?></span>SumaRitmo<span class="wordmark-dot">.</span></a>
      <nav class="side-nav" aria-label="Navegación principal">
        <button class="nav-item active" data-tab="today" aria-current="page"><?= icon('day') ?> Mi día</button>
        <button class="nav-item" data-tab="progress"><?= icon('progress') ?> Mi progreso</button>
        <button class="nav-item" data-tab="settings"><?= icon('space') ?> Mi espacio</button>
      </nav>
      <button class="sidebar-share" type="button" data-open-share><?= icon('share') ?> Comparte tu logro</button>
      <div class="sidebar-bottom"><p class="sidebar-tip">Lo que haces <strong>a tu ritmo, suma.</strong></p><div class="sidebar-user"><span class="avatar"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></span><span><strong><?= e($user['name']) ?></strong><small>Cuenta personal</small></span></div></div>
    </aside>
    <main class="main-content">
      <header class="topbar"><a class="mobile-wordmark" href="index.php"><span class="mark"><?= icon('wave') ?></span>SumaRitmo<span class="wordmark-dot">.</span></a><span class="topbar-date"><?= e($todayLabel) ?></span><form method="post" action="auth.php" class="logout-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="logout"><button type="submit" class="logout-button">Salir</button></form></header>

      <section class="tab-panel active" id="tab-today" aria-labelledby="today-title">
        <section class="day-hero">
          <div class="day-hero-photo" aria-hidden="true"><img src="<?= e($appBackground) ?>" alt=""></div>
          <div class="day-hero-copy">
            <h1 id="today-title">Hola, <?= e($user['name']) ?></h1>
            <p class="day-hero-lead">Tu única competencia eres tú. Cada registro ayuda a entender tu propio ritmo.</p>
            <section class="recommendation-card" aria-labelledby="recommendation-title">
              <p class="rec-label"><?= icon('spark') ?> Una idea para hoy</p>
              <h2 id="recommendation-title">Vamos a conocer tu ritmo.</h2>
              <p id="recommendation-text">Registra tu actividad, descanso y cómo te sientes. Con el tiempo veremos juntos qué te ayuda a avanzar y cuándo te conviene recuperar.</p>
              <button class="why-link" id="why-button" type="button">¿Por qué me dices esto? <?= icon('arrow') ?></button>
            </section>
          </div>
        </section>
        <p class="baseline-note" id="baseline-note" aria-live="polite">Tus primeras ideas son orientativas y mejorarán con más registros.</p>

        <div class="section-title"><h2>Lo que llevas hoy</h2></div>
        <section class="metric-strip" aria-label="Resumen de hoy">
          <article class="metric-cell"><span class="metric-label"><?= icon('activity') ?> Deporte y actividad</span><strong class="metric-value"><span id="today-minutes">0</span><small>min</small></strong><p id="today-activity-note">Aún sin actividad registrada.</p></article>
          <article class="metric-cell"><span class="metric-label"><?= icon('sleep') ?> Descanso</span><strong class="metric-value"><span id="last-sleep">—</span><small id="sleep-unit">h</small></strong><p id="sleep-note">Anota cómo dormiste.</p></article>
          <article class="metric-cell"><span class="metric-label"><?= icon('food') ?> Comida y bebida</span><strong class="metric-value"><span id="today-meals">0</span><small>registros</small></strong><p>Sin contar ni juzgar.</p></article>
        </section>

        <section class="share-cta" aria-labelledby="share-cta-title">
          <div class="share-cta-art" aria-hidden="true"><span></span></div>
          <div class="share-cta-copy"><h2 id="share-cta-title">Comparte tu logro</h2><p>Convierte tu día o una sesión de tu historial en un video vertical para TikTok, Instagram o WhatsApp. Tú eliges qué datos mostrar.</p></div>
          <button class="button-primary" type="button" data-open-share><?= icon('share') ?> Crear publicación</button>
        </section>

        <div class="section-title"><h2>Registra tu día</h2><p class="section-note">Movimiento, descanso, ánimo y comida. Todo es opcional salvo lo básico.</p></div>
        <div class="entry-tabs" role="tablist" aria-label="Tipo de registro">
          <button role="tab" type="button" id="entry-tab-activity" aria-controls="entry-activity" aria-selected="true" data-entry-tab="activity"><?= icon('activity') ?><span>Actividad</span></button>
          <button role="tab" type="button" id="entry-tab-sleep" aria-controls="entry-sleep" aria-selected="false" tabindex="-1" data-entry-tab="sleep"><?= icon('sleep') ?><span>Sueño</span></button>
          <button role="tab" type="button" id="entry-tab-mood" aria-controls="entry-mood" aria-selected="false" tabindex="-1" data-entry-tab="mood"><?= icon('mood') ?><span>Ánimo</span></button>
          <button role="tab" type="button" id="entry-tab-meal" aria-controls="entry-meal" aria-selected="false" tabindex="-1" data-entry-tab="meal"><?= icon('food') ?><span>Comida</span></button>
        </div>
        <section class="entry-panels">
          <form class="entry-card" id="entry-activity" role="tabpanel" aria-labelledby="entry-tab-activity" data-entry-form="activity"><input type="hidden" name="source_type" value="manual">
            <div class="entry-heading"><strong>Deporte o actividad</strong><small>Escribe tus datos o léelos de una captura.</small></div>
            <div class="capture-tool"><div class="capture-help-row"><label class="file-label">Subir captura o CSV de Garmin<input type="file" accept="image/*,.csv,text/csv" data-capture-image></label><button class="capture-help-button" type="button" data-open-garmin-help>¿Cómo exporto desde Garmin?</button></div><div class="capture-preview" data-capture-preview hidden></div><button class="button-outline capture-read" type="button" data-read-capture hidden>Leer datos de la captura</button><div class="csv-import-panel" data-csv-import-panel hidden><strong>Vista previa de Garmin Connect</strong><p data-csv-import-summary></p><ul data-csv-import-preview></ul><p class="csv-import-status" data-csv-import-status aria-live="polite"></p><button class="button-primary" type="button" data-import-csv>Importar actividades de los últimos 60 días</button></div><p class="capture-status" data-capture-status>Las capturas y el CSV se leen en este dispositivo. Revisa los datos antes de guardarlos.</p></div>
            <div class="field-grid">
              <label>¿Qué hiciste?<select name="sport" required><option value="">Elige una actividad</option><?php foreach ($sportOptions as $sport): ?><option><?= e($sport) ?></option><?php endforeach; ?></select></label>
              <label>Fecha de la actividad<input name="entry_date" type="date" required></label>
              <label>Duración, en minutos<input name="duration_minutes" type="number" inputmode="numeric" min="1" max="1440" placeholder="Ej. 45" required></label>
              <label>Distancia, en km (opcional)<input name="distance_km" type="number" inputmode="decimal" min="0.01" max="2000" step="0.01" placeholder="Ej. 5,2"></label>
              <label>Frecuencia cardiaca media (opcional)<input name="average_heart_rate" type="number" inputmode="numeric" min="30" max="250" placeholder="lpm"></label>
              <label>Calorías (opcional)<input name="calories_burned" type="number" inputmode="numeric" min="1" max="20000" placeholder="kcal"></label>
              <label>¿Qué tan exigente se sintió? (opcional)<select name="effort_rating"><option value="">Prefiero no indicarlo</option><?php for ($effort = 1; $effort <= 10; $effort++): ?><option value="<?= $effort ?>"><?= $effort ?><?= $effort === 1 ? ' · Muy suave' : ($effort === 10 ? ' · Máximo' : '') ?></option><?php endfor; ?></select></label>
              <label>¿Cómo quedó tu cuerpo? (opcional)<select name="body_feeling"><option value="">Prefiero no indicarlo</option><option value="Bien">Bien</option><option value="Cansancio habitual">Cansancio habitual</option><option value="Más cansancio de lo normal">Más cansancio de lo normal</option><option value="Dolor inusual">Dolor inusual</option></select></label>
              <label class="field-wide">Detalle opcional<input name="session_detail" maxlength="160" placeholder="30 largos, partido amistoso…"></label>
            </div>
            <button type="submit" class="button-primary">Revisar sesión <?= icon('arrow') ?></button>
          </form>
          <form class="entry-card" id="entry-sleep" role="tabpanel" aria-labelledby="entry-tab-sleep" data-entry-form="sleep" hidden><input type="hidden" name="source_type" value="manual">
            <div class="entry-heading"><strong>Sueño</strong><small>Tu descanso también importa.</small></div>
            <div class="capture-tool"><div class="capture-help-row"><label class="file-label">Usar captura del sueño (opcional)<input type="file" accept="image/*" data-capture-image></label><button class="capture-help-button" type="button" data-open-garmin-help>¿Cómo exporto desde Garmin?</button></div><div class="capture-preview" data-capture-preview hidden></div><button class="button-outline capture-read" type="button" data-read-capture hidden>Leer datos de la captura</button><p class="capture-status" data-capture-status>La imagen se analiza en este dispositivo y no se guarda. Revisa los campos antes de confirmar.</p></div>
            <div class="field-grid">
              <label>Fecha de la noche<input name="entry_date" type="date" required></label>
              <label>¿Cuántas horas dormiste?<input name="sleep_hours" type="number" inputmode="decimal" min="0.5" max="24" step="0.25" placeholder="Ej. 7.5" required></label>
              <label>Puntaje del reloj, si aparece (opcional)<input name="sleep_score" type="number" inputmode="numeric" min="0" max="100" placeholder="0 a 100"></label>
              <label>HRV nocturna en ms (opcional)<input name="hrv_ms" type="number" inputmode="decimal" min="1" max="500" step="0.1" placeholder="Ej. 48"></label>
              <label class="field-wide">¿Cómo te sientes?<select name="content"><option>Descansado/a</option><option>Más o menos</option><option>Cansado/a</option></select></label>
            </div>
            <button type="submit" class="button-primary">Revisar descanso <?= icon('arrow') ?></button>
          </form>
          <form class="entry-card" id="entry-mood" role="tabpanel" aria-labelledby="entry-tab-mood" data-entry-form="mood" hidden>
            <div class="entry-heading"><strong>¿Cómo te sientes?</strong><small>Tu percepción también cuenta.</small></div>
            <div class="field-grid">
              <label>Elige lo que más se parece a hoy<select name="mood"><option>Con energía</option><option>Bien</option><option>Más o menos</option><option>Cansado/a</option></select></label>
              <label>Una nota para ti (opcional)<input name="content" maxlength="500" placeholder="Un logro, una sensación…"></label>
            </div>
            <button type="submit" class="button-primary">Guardar mi registro <?= icon('arrow') ?></button>
          </form>
          <form class="entry-card" id="entry-meal" role="tabpanel" aria-labelledby="entry-tab-meal" data-entry-form="meal" hidden>
            <div class="entry-heading"><strong>Comida o bebida</strong><small>Registro opcional. Anota lo que comiste o bebiste, sin contar ni juzgar.</small></div>
            <div class="field-grid">
              <label>¿Qué comiste o bebiste?<input name="content" maxlength="500" placeholder="Ej. Avena, agua, café…" required></label>
              <label>¿Qué tipo de registro es?<select name="meal_category"><option>Desayuno</option><option>Almuerzo</option><option>Cena</option><option>Algo entre comidas</option><option>Bebida sin alcohol</option></select></label>
            </div>
            <button type="submit" class="button-primary">Guardar registro <?= icon('arrow') ?></button>
          </form>
        </section>

        <div class="section-title"><h2>Lo que has registrado hoy</h2></div>
        <section id="today-timeline" class="timeline"><p class="empty-state">Todavía no has añadido registros. Cuando lo hagas, aparecerán aquí.</p></section>
        <section class="quote-card" aria-label="Un recordatorio para ti"><?= icon('spark') ?><p id="daily-quote">Un paso pequeño sigue siendo un paso.</p><button type="button" id="new-quote" aria-label="Otra frase"><?= icon('refresh') ?></button></section>
        <footer class="page-footer"><span>SumaRitmo · A tu ritmo, cada día.</span><span><a href="privacy.php">Privacidad</a> · Orientación general, no atención médica.</span></footer>
      </section>

      <section class="tab-panel" id="tab-progress" aria-labelledby="progress-title" hidden>
        <div class="welcome-row"><h1 id="progress-title">Tu progreso</h1><p class="subtle">Mira cómo vas, sin compararte con nadie.</p></div>
        <section class="progress-banner"><div><h2>Una semana a la vez.</h2><p id="weekly-summary">Registra tus deportes para ver cómo avanzas frente a tu propio punto de partida.</p></div><div class="weekly-total"><strong id="week-minutes">0</strong><span>minutos activos esta semana</span></div></section>
        <div class="section-title"><h2>Tus últimos 7 días</h2></div>
        <section class="insight-grid">
          <article class="insight-card"><span>Actividad</span><strong><b id="metric-activity-7">0</b><small>min</small></strong><p id="activity-comparison">Minutos registrados en los últimos 7 días.</p></article>
          <article class="insight-card"><span>Constancia</span><strong><b id="metric-active-days">0</b><small>días</small></strong><p id="active-days-note">Con alguna actividad en los últimos 7 días.</p></article>
          <article class="insight-card"><span>Sueño anotado</span><strong><b id="metric-sleep-average">—</b><small id="metric-sleep-unit">h promedio</small></strong><p id="metric-sleep-count">Aún no hay suficientes registros recientes.</p></article>
          <article class="insight-card"><span>Hábitos registrados</span><strong><b id="metric-meals">0</b><small>registros</small></strong><p id="metric-mood-note">0 registros de ánimo en los últimos 7 días.</p></article>
        </section>
        <section class="progress-panels">
          <article class="progress-panel"><div class="panel-heading"><h2>Actividad por semana <small>4 semanas</small></h2><span class="panel-legend"><i></i> minutos</span></div><div id="weekly-trend" class="weekly-trend" aria-label="Gráfico comparativo de minutos activos por semana"><p class="empty-state">Tus semanas aparecerán aquí cuando registres actividades.</p></div><p class="panel-footnote">Las barras comparan el tiempo entre semanas; la marca vertical muestra tu meta.</p></article>
          <article class="progress-panel"><div class="panel-heading"><h2>Descanso que anotaste <small>14 días</small></h2><span class="panel-legend sleep-legend"><i></i> horas</span></div><div id="sleep-trend" class="sleep-trend" aria-label="Registros diarios de horas de sueño"><p class="empty-state">Tus registros de sueño aparecerán aquí.</p></div></article>
        </section>
        <section class="progress-panels">
          <article class="progress-panel"><div class="panel-heading"><h2>Deportes que practicaste <small>4 semanas</small></h2></div><div id="sport-breakdown" class="sport-breakdown"><p class="empty-state">Cuando registres tus sesiones, verás aquí cómo se reparten.</p></div></article>
          <article class="progress-panel"><div class="panel-heading"><h2>Comida y bebida que anotaste <small>7 días</small></h2></div><div id="meal-breakdown" class="sport-breakdown"><p class="empty-state">Cuando registres comida o bebida, verás aquí el resumen de la semana.</p></div><p class="panel-footnote">Solo mostramos tus registros; no evaluamos si comes suficiente ni hacemos recomendaciones nutricionales.</p></article>
        </section>
        <section class="progress-panel weight-panel"><div class="panel-heading"><h2>Tu peso a lo largo del tiempo</h2><div class="weight-current"><strong id="current-weight">—</strong><span>kg · último registro</span></div></div><p class="weight-note">Registra un pesaje cuando quieras. Puedes elegir la fecha en que te pesaste.</p><form id="weight-form" class="weight-form"><label>Peso, en kg<input name="weight_kg" type="number" inputmode="decimal" min="1" max="500" step="0.1" placeholder="Ej. 68,4" required></label><label>Fecha del pesaje<input name="measured_on" type="date" required></label><button type="submit" class="button-primary">Guardar pesaje</button></form><div id="weight-history" class="weight-history" aria-live="polite"><p class="empty-state">Cuando registres un pesaje, aparecerá aquí.</p></div><p class="panel-footnote">Puedes revisar estas fechas junto con tu historial de sesiones. El peso y el rendimiento son señales personales; su coincidencia no indica que uno cause el otro.</p></section>
        <div class="section-title"><h2>Actividad reciente</h2><button class="button-outline button-small" type="button" data-open-share><?= icon('share') ?> Compartir una sesión</button></div>
        <section class="table-card"><div class="activity-table-head" aria-hidden="true"><span>Deporte</span><span>Tiempo</span><span>Detalle</span><span>Fecha</span><span></span></div><div id="activity-history"><p class="empty-state">Cuando registres tus sesiones, podrás verlas aquí.</p></div></section>
        <div class="privacy-note"><?= icon('info') ?><p>Comparamos tus registros contigo. Las sensaciones y los datos de un dispositivo son señales orientativas, no diagnósticos. Comida y bebida se registran sin contar calorías ni juzgar.</p></div>
      </section>

      <section class="tab-panel" id="tab-settings" aria-labelledby="settings-title" hidden>
        <div class="welcome-row"><h1 id="settings-title">Mi espacio</h1><p class="subtle">Ajusta tus objetivos y administra tus datos.</p></div>
        <section class="settings-card"><div class="settings-copy"><h2>Un objetivo a tu medida</h2><p>Elige qué quieres mejorar. Tu rango de edad ayuda a adaptar el contexto; no guardamos tu fecha de nacimiento.</p></div><form id="profile-form" class="settings-form"><label for="age-range">Rango de edad</label><select id="age-range" name="age_range" required><option value="">Aún no indicado</option><?php foreach ($ageOptions as $value => $label): ?><option value="<?= e($value) ?>"<?= $user['age_range'] === $value ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select><label for="goal-focus">¿Qué te gustaría mejorar?</label><select id="goal-focus" name="goal_focus"><option value="bienestar"<?= $user['goal_focus'] === 'bienestar' ? ' selected' : '' ?>>Sentirme mejor en mi día a día</option><option value="constancia"<?= $user['goal_focus'] === 'constancia' ? ' selected' : '' ?>>Crear constancia</option><option value="fuerza"<?= $user['goal_focus'] === 'fuerza' ? ' selected' : '' ?>>Mejorar mi fuerza</option><option value="rendimiento"<?= $user['goal_focus'] === 'rendimiento' ? ' selected' : '' ?>>Mejorar en un deporte</option></select><fieldset class="sport-preferences"><legend>Deportes que practicas</legend><?php foreach ($sportOptions as $sport): ?><label><input type="checkbox" name="preferred_sports[]" value="<?= e($sport) ?>"><?= e($preferenceLabels[$sport] ?? $sport) ?></label><?php endforeach; ?></fieldset><label for="available-days">Días disponibles por semana (opcional)</label><select id="available-days" name="available_days_per_week"><option value="">Prefiero no indicarlo</option><?php for ($dayCount = 1; $dayCount <= 7; $dayCount++): ?><option value="<?= $dayCount ?>"><?= $dayCount ?> <?= $dayCount === 1 ? 'día' : 'días' ?></option><?php endfor; ?></select><label for="session-minutes">Tiempo habitual por sesión (opcional)</label><select id="session-minutes" name="preferred_session_minutes"><option value="">Prefiero no indicarlo</option><option value="20">20 minutos</option><option value="30">30 minutos</option><option value="45">45 minutos</option><option value="60">1 hora</option><option value="90">1 hora y media</option><option value="120">2 horas</option></select><button class="button-primary" type="submit">Guardar perfil</button></form></section>
        <section class="settings-card"><div class="settings-copy"><h2>Actividad semanal</h2><p>Elige cuántos minutos quieres sumar. Es una referencia personal, no una calificación.</p></div><form id="goal-form" class="settings-form"><label for="weekly-goal">Meta, en minutos</label><div class="goal-control"><input id="weekly-goal" name="weekly_goal" type="number" inputmode="numeric" min="30" max="1200" step="15" value="<?= (int) $user['weekly_goal'] ?>"><button class="button-primary" type="submit">Guardar meta</button></div></form></section>
        <section class="settings-card"><div class="settings-copy"><h2>Conexiones</h2><p>Garmin y Strava aún no están conectados. Puedes seguir registrando aquí tus sesiones y sensaciones.</p></div><div class="provider-pills"><span>Garmin · Próximamente</span><span>Strava · Próximamente</span></div></section>
        <section class="settings-card"><div class="settings-copy"><h2 id="plan-name">Nivel 1 · A tu ritmo</h2><p id="plan-copy">Acceso de prueba habilitado. Durante el piloto no se realizan cobros. Incluye captura asistida, seguimiento y recomendaciones.</p></div><span class="plan-status pilot-status" id="plan-status">Piloto · sin cobro</span></section>
        <section class="settings-card danger-card"><div class="settings-copy"><h2>Descarga o elimina tu información</h2><p>Exporta tus registros personales o borra tu cuenta y sus registros.</p></div><div class="data-actions"><a class="button-outline" href="export.php"><?= icon('download') ?> Descargar mis datos</a><button class="button-danger" id="delete-account-button" type="button">Borrar mi cuenta</button></div></section>
        <form method="post" action="auth.php" class="settings-logout"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="logout"><button type="submit">Cerrar sesión</button></form>
      </section>
    </main>
    <nav class="mobile-nav" aria-label="Navegación móvil"><button class="active" data-tab="today" aria-current="page"><?= icon('day') ?><small>Mi día</small></button><button data-tab="progress"><?= icon('progress') ?><small>Progreso</small></button><button class="mobile-share" type="button" data-open-share><?= icon('share') ?><small>Compartir</small></button><button data-tab="settings"><?= icon('space') ?><small>Mi espacio</small></button></nav>
  </div>
  <div class="toast" id="toast" role="status" aria-live="polite"></div>
  <dialog class="confirm-dialog" id="entry-review-dialog"><form method="dialog" id="entry-review-form"><h2>Revisa antes de guardar</h2><p id="entry-review-summary"></p><label class="capture-consent" id="capture-consent-wrap" hidden><input type="checkbox" id="capture-consent"><span>Autorizo leer esta captura en mi dispositivo y guardar solo los datos que acabo de revisar. La imagen no se conserva.</span></label><div class="dialog-actions"><button class="button-outline" value="cancel">Volver a editar</button><button class="button-primary" id="entry-review-save" value="save">Guardar registro</button></div></form></dialog>
  <dialog class="confirm-dialog" id="reason-dialog"><form method="dialog"><h2>Por qué te lo sugerimos</h2><p id="recommendation-reason"></p><div class="dialog-actions"><button class="button-primary">Entendido</button></div></form></dialog>
  <dialog class="confirm-dialog" id="delete-dialog"><form method="dialog"><h2>¿Borramos tu cuenta?</h2><p>Se eliminarán tu perfil y todos los registros asociados. Esta acción no se puede deshacer.</p><label class="confirm-check"><input type="checkbox" id="delete-confirm-check"> Sí, quiero borrar permanentemente mi cuenta y mis datos.</label><div class="dialog-actions"><button class="button-outline">Conservar mi cuenta</button><button class="button-danger" id="confirm-delete" value="confirm" disabled>Borrar cuenta</button></div></form></dialog>
  <dialog class="confirm-dialog garmin-help-dialog" id="garmin-export-dialog" aria-labelledby="garmin-export-title"><form method="dialog"><button class="dialog-close" aria-label="Cerrar ayuda"><?= icon('close') ?></button><h2 id="garmin-export-title">Saca tus datos de Garmin</h2><p class="garmin-help-lead">La exportación se hace desde Garmin Connect en un navegador, mejor desde un computador.</p><section class="garmin-help-section"><h3>Para actividades deportivas (CSV)</h3><ol><li>Entra a <strong>Garmin Connect</strong> e inicia sesión.</li><li>Abre <strong>Actividades → Todas las actividades</strong> y pulsa <strong>Exportar CSV</strong>.</li><li>En SumaRitmo, selecciona ese CSV. Importaremos solo los últimos 60 días; las actividades ya sincronizadas se omiten.</li></ol></section><section class="garmin-help-section"><h3>Para el recorrido (GPX)</h3><ol><li>Abre la actividad en Garmin Connect o en Strava.</li><li>Pulsa el engranaje o los tres puntos y elige <strong>Exportar a GPX</strong>.</li><li>En <strong>Comparte tu logro</strong>, carga ese GPX para dibujar el recorrido. No se guarda en SumaRitmo.</li></ol></section><section class="garmin-help-section"><h3>Para sueño y bienestar</h3><ol><li>En Garmin Connect, abre tu perfil y entra a <strong>Configuración de la cuenta → Información de la cuenta</strong>.</li><li>En la parte inferior, selecciona un día y pulsa <strong>Exportar</strong>. Garmin descarga un archivo ZIP con datos de bienestar en formato FIT.</li><li>Para registrar el sueño en esta versión, usa una captura de pantalla; la importación de ZIP/FIT aún no está activa.</li></ol></section><p class="garmin-help-note"><strong>Privacidad:</strong> El archivo CSV se analiza en este dispositivo; no se guarda ni se envía completo. Al pulsar importar, SumaRitmo recibe únicamente los datos de actividad de los últimos 60 días para guardarlos en tu cuenta.</p><a class="garmin-support-link" href="https://support.garmin.com/en-US/?faq=W1TvTPW8JZ6LfJSfK512Q8" target="_blank" rel="noopener noreferrer">Ver instrucciones oficiales de Garmin</a><div class="dialog-actions"><button class="button-primary" value="close">Entendido</button></div></form></dialog>

  <dialog class="share-dialog" id="share-dialog" aria-labelledby="share-title">
    <div class="share-layout">
      <header class="share-head"><div><h2 id="share-title">Comparte tu logro</h2><p>Video vertical 9:16 listo para TikTok, Reels o un estado de WhatsApp.</p></div><button type="button" class="dialog-close" data-close-share aria-label="Cerrar"><?= icon('close') ?></button></header>
      <div class="share-stage">
        <div class="share-frame"><canvas id="share-canvas" width="1080" height="1920" aria-label="Vista previa de la publicación"></canvas><video id="share-video" playsinline muted loop hidden></video></div>
        <button type="button" class="share-replay" id="share-replay"><?= icon('refresh') ?> Ver animación</button>
      </div>
      <form class="share-controls" id="share-form" novalidate>
        <label class="share-field">¿Qué quieres compartir?<select id="share-source"><option value="day">Mi día de hoy</option></select></label>
        <fieldset class="share-field"><legend>Datos que se ven</legend><div class="share-chips" id="share-fields"></div><p class="share-hint" id="share-fields-hint">Elige hasta seis. Los que no tengas registrados no aparecen.</p></fieldset>
        <fieldset class="share-field share-route"><legend><?= icon('route') ?> Recorrido (opcional)</legend>
          <label class="file-label">Archivo GPX de Garmin o Strava<input type="file" id="share-gpx" accept=".gpx,application/gpx+xml,application/xml,text/xml"></label>
          <label class="check-row"><input type="checkbox" id="share-trim" checked><span>Ocultar los primeros y últimos 300 m para no mostrar dónde empiezas.</span></label>
          <p class="share-hint" id="share-gpx-status">El archivo se lee en este dispositivo y no se guarda.</p>
        </fieldset>
        <div class="share-row">
          <label class="share-field">Lugar (opcional)<input id="share-place" maxlength="36" placeholder="Ej. Villavicencio" autocomplete="off"></label>
          <label class="share-field">Frase (opcional)<input id="share-caption" maxlength="48" placeholder="Mi única competencia soy yo." autocomplete="off"></label>
        </div>
        <fieldset class="share-field"><legend>Estilo</legend><div class="share-styles">
          <label><input type="radio" name="share_style" value="brick" checked><span class="swatch swatch-brick"></span>Ladrillo</label>
          <label><input type="radio" name="share_style" value="photo"><span class="swatch swatch-photo"></span>Foto</label>
          <label><input type="radio" name="share_style" value="graphite"><span class="swatch swatch-graphite"></span>Grafito</label>
        </div></fieldset>
        <div class="share-actions share-main-actions">
          <button type="button" class="button-primary" id="share-make-video"><?= icon('video') ?> Crear video</button>
          <button type="button" class="button-outline" id="share-image"><?= icon('image') ?> Compartir imagen</button>
        </div>
        <div class="share-result" id="share-result" hidden>
          <p id="share-result-text">Tu video está listo.</p>
          <div class="share-actions"><button type="button" class="button-primary" id="share-send-video"><?= icon('share') ?> Compartir video</button><a class="button-outline" id="share-download-video" download><?= icon('download') ?> Descargar</a></div>
        </div>
        <p class="share-status" id="share-status" aria-live="polite"></p>
      </form>
    </div>
  </dialog>
<?php endif; ?>
</body>
</html>
