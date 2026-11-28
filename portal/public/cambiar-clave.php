<?php
require dirname(__DIR__) . '/app/bootstrap.php';
$u = requiere_login();

$error = '';
if (es_post()) {
    csrf_verificar();
    $actual = (string)($_POST['actual'] ?? '');
    $nueva = (string)($_POST['nueva'] ?? '');
    $repite = (string)($_POST['repite'] ?? '');
    $fila = q('SELECT clave_hash FROM usuarios WHERE id = ?', [$u['id']])->fetch();
    if (!password_verify($actual, $fila['clave_hash'])) {
        $error = 'La contraseña actual no es correcta.';
    } elseif ($nueva !== $repite) {
        $error = 'La nueva contraseña y su confirmación no coinciden.';
    } elseif ($nueva === $actual) {
        $error = 'La nueva contraseña debe ser distinta de la actual.';
    } elseif ($e = validar_clave_nueva($nueva, $u['usuario'])) {
        $error = $e;
    } else {
        q('UPDATE usuarios SET clave_hash = ?, debe_cambiar_clave = 0 WHERE id = ?', [password_hash($nueva, PASSWORD_DEFAULT), $u['id']]);
        session_regenerate_id(true);
        auditar('cambio_clave');
        aviso('ok', 'Contraseña actualizada.');
        redirigir(url('inicio.php'));
    }
}

$primera = (bool)$u['debe_cambiar_clave'];
pagina_inicio('Cambiar contraseña', '', ['sin_riel' => $primera]);
?>
<?php if ($primera): ?><div style="max-width:480px;margin:6vh auto 0"><?= marca(false) ?><?php endif; ?>
<?php cabeza($primera ? 'Cree su contraseña' : 'Cambiar contraseña', $primera ? 'Antes de entrar, cambie la contraseña temporal que le entregaron.' : ''); ?>
<form method="post" class="panel" style="max-width:480px" novalidate>
  <div class="panel-cuerpo" style="display:grid;gap:14px">
    <?= csrf_campo() ?>
    <?php if ($error): ?><div class="aviso aviso-error" role="alert" style="margin:0"><?= icono('alerta') ?><span><?= h($error) ?></span></div><?php endif; ?>
    <div class="campo">
      <label for="actual"><?= $primera ? 'Contraseña temporal' : 'Contraseña actual' ?></label>
      <input id="actual" name="actual" type="password" autocomplete="current-password" required autofocus>
    </div>
    <div class="campo">
      <label for="nueva">Nueva contraseña</label>
      <input id="nueva" name="nueva" type="password" autocomplete="new-password" required minlength="8">
      <span class="ayuda">Mínimo 8 caracteres, con letras y números.</span>
    </div>
    <div class="campo">
      <label for="repite">Repita la nueva contraseña</label>
      <input id="repite" name="repite" type="password" autocomplete="new-password" required minlength="8">
    </div>
    <div class="form-pie" style="margin-top:4px">
      <button class="btn btn-rojo" type="submit">Guardar contraseña</button>
      <?php if ($primera): ?><a class="btn btn-sutil" href="<?= h(url('salir.php')) ?>">Salir</a><?php else: ?><a class="btn btn-sutil" href="<?= h(url('inicio.php')) ?>">Cancelar</a><?php endif; ?>
    </div>
  </div>
</form>
<?php if ($primera): ?></div><?php endif; ?>
<?php pagina_fin(); ?>
