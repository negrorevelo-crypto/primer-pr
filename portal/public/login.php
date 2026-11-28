<?php
require dirname(__DIR__) . '/app/bootstrap.php';

if (usuario()) redirigir(url('inicio.php'));

$error = '';
$usuarioIn = '';
if (es_post()) {
    csrf_verificar();
    $usuarioIn = trim((string)($_POST['usuario'] ?? ''));
    $clave = (string)($_POST['clave'] ?? '');
    if ($usuarioIn === '' || $clave === '') {
        $error = 'Escriba su usuario y su contraseña.';
    } else {
        [$ok, $error] = intentar_login($usuarioIn, $clave);
        if ($ok) {
            $volver = $_SESSION['volver'] ?? '';
            unset($_SESSION['volver']);
            // Solo rutas internas
            if (!is_string($volver) || !str_starts_with($volver, base() . '/') || str_contains($volver, '//')) $volver = url('inicio.php');
            redirigir($volver);
        }
    }
}

$c = config('app');
pagina_inicio('Iniciar sesión', '', ['sin_riel' => true, 'clase_body' => 'pagina-acceso']);
?>
<div class="acceso">
  <section class="acceso-tinta">
    <?= marca(true) ?>
    <div class="acceso-lema">
      <h1>Entrene el lanzamiento de ofertas en su propia pantalla.</h1>
      <p>Simulación de la rueda de negocios ajustada a su computador. Cada partida mide su reacción desde la apertura hasta la confirmación.</p>
    </div>
    <?= banda('pre', 0, ['La rueda abre cuando usted entra', '']) ?>
  </section>
  <section class="acceso-form">
    <form method="post" autocomplete="on" novalidate>
      <?= csrf_campo() ?>
      <div>
        <h2>Iniciar sesión</h2>
        <p class="tenue" style="margin-top:6px">Use el usuario y la contraseña que le entregaron.</p>
      </div>
      <?php if ($error): ?>
        <div class="aviso aviso-error" role="alert"><?= icono('alerta') ?><span><?= h($error) ?></span></div>
      <?php endif; ?>
      <div class="campo">
        <label for="usuario">Usuario</label>
        <input id="usuario" name="usuario" type="text" autocomplete="username" autocapitalize="none" spellcheck="false" required value="<?= h($usuarioIn) ?>" <?= $error ? 'aria-invalid="true"' : 'autofocus' ?>>
      </div>
      <div class="campo">
        <label for="clave">Contraseña</label>
        <input id="clave" name="clave" type="password" autocomplete="current-password" required <?= $error ? 'autofocus' : '' ?>>
      </div>
      <button class="btn btn-rojo btn-grande" type="submit">Entrar <?= icono('flecha') ?></button>
      <p class="acceso-ayuda">¿Aún no tiene acceso u olvidó su contraseña? Escriba a
        <a href="mailto:<?= h($c['contacto_correo']) ?>"><?= h($c['contacto_correo']) ?></a><?php if (!empty($c['contacto_whatsapp'])): ?> o por <a href="https://wa.me/<?= h($c['contacto_whatsapp']) ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?>.
      </p>
    </form>
  </section>
</div>
<?php pagina_fin(); ?>
