<?php
// Crea el primer administrador. Solo funciona si no existe ningún administrador
// y si 'instalacion_habilitada' es true en app/config.php. Borre este archivo después.
require dirname(__DIR__) . '/app/bootstrap.php';

$hayAdmin = (int)q("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'")->fetchColumn() > 0;
$habilitada = (bool)config('instalacion_habilitada');
$error = '';

if (es_post() && !$hayAdmin && $habilitada) {
    csrf_verificar();
    $nombre = trim((string)($_POST['nombre'] ?? ''));
    $usuario = strtolower(trim((string)($_POST['usuario'] ?? '')));
    $correo = trim((string)($_POST['correo'] ?? ''));
    $clave = (string)($_POST['clave'] ?? '');
    if ($nombre === '' || !preg_match('/^[a-z0-9._-]{3,60}$/', $usuario)) {
        $error = 'Escriba su nombre y un usuario de 3 a 60 caracteres (letras, números, punto, guion).';
    } elseif ($e = validar_clave_nueva($clave, $usuario)) {
        $error = $e;
    } else {
        q('INSERT INTO usuarios (rol, usuario, nombre, correo, clave_hash, debe_cambiar_clave) VALUES (?, ?, ?, ?, ?, 0)',
            ['admin', $usuario, $nombre, $correo ?: null, password_hash($clave, PASSWORD_DEFAULT)]);
        auditar('instalacion', 'Primer administrador: ' . $usuario, (int)db()->lastInsertId());
        aviso('ok', 'Administrador creado. Ponga instalacion_habilitada en false en app/config.php y borre instalar.php.');
        redirigir(url('login.php'));
    }
}

pagina_inicio('Instalación', '', ['sin_riel' => true]);
?>
<div style="max-width:480px;margin:6vh auto 0">
  <?= marca(false) ?>
  <?php cabeza('Crear el primer administrador'); ?>
  <?php if ($hayAdmin || !$habilitada): ?>
    <div class="aviso aviso-info"><?= icono('alerta') ?><span>La instalación ya se hizo o está deshabilitada. Borre este archivo del servidor.</span></div>
    <a class="btn" href="<?= h(url('login.php')) ?>">Ir al inicio de sesión</a>
  <?php else: ?>
  <form method="post" class="panel" novalidate>
    <div class="panel-cuerpo" style="display:grid;gap:14px">
      <?= csrf_campo() ?>
      <?php if ($error): ?><div class="aviso aviso-error" style="margin:0"><?= icono('alerta') ?><span><?= h($error) ?></span></div><?php endif; ?>
      <div class="campo"><label for="nombre">Nombre</label><input id="nombre" name="nombre" type="text" required value="<?= h($_POST['nombre'] ?? '') ?>"></div>
      <div class="campo"><label for="usuario">Usuario</label><input id="usuario" name="usuario" type="text" required autocapitalize="none" value="<?= h($_POST['usuario'] ?? '') ?>"></div>
      <div class="campo"><label for="correo">Correo</label><input id="correo" name="correo" type="email" value="<?= h($_POST['correo'] ?? '') ?>"></div>
      <div class="campo"><label for="clave">Contraseña</label><input id="clave" name="clave" type="password" required minlength="8" autocomplete="new-password"><span class="ayuda">Mínimo 8 caracteres, con letras y números.</span></div>
      <div class="form-pie" style="margin-top:4px"><button class="btn btn-rojo" type="submit">Crear administrador</button></div>
    </div>
  </form>
  <?php endif; ?>
</div>
<?php pagina_fin(); ?>
