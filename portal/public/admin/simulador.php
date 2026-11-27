<?php
require dirname(__DIR__, 2) . '/app/bootstrap.php';
$admin = requiere_admin();

$fmt = fn(int $ms) => rtrim(rtrim(number_format($ms / 1000, 2, ',', ''), '0'), ',');
$aMs = function ($v): ?int {
    $v = str_replace(',', '.', trim((string)$v));
    return is_numeric($v) ? (int)round((float)$v * 1000) : null;
};

$errores = [];
$actual = tiempos_subasta() + TIEMPOS_PERFILES['grabacion'][1];
$guardado = ajustes_simulador();

if (es_post()) {
    csrf_verificar();
    $perfil = (string)($_POST['perfil'] ?? '');
    if (isset(TIEMPOS_PERFILES[$perfil])) {
        $nuevo = TIEMPOS_PERFILES[$perfil][1];
    } else {
        $perfil = 'personalizado';
        $nuevo = [];
        foreach (TIEMPOS_MOMENTOS as $k => [$nombre]) {
            $v = [$aMs($_POST[$k . '_min'] ?? ''), $aMs($_POST[$k . '_tip'] ?? ''), $aMs($_POST[$k . '_max'] ?? '')];
            if (in_array(null, $v, true)) { $errores[$k] = 'Escriba los tres valores en segundos.'; continue; }
            if (!($v[0] <= $v[1] && $v[1] <= $v[2] && $v[0] < $v[2])) { $errores[$k] = 'Debe cumplirse mínimo ≤ típico ≤ máximo, y el mínimo menor que el máximo.'; continue; }
            if ($v[2] > 20000) { $errores[$k] = 'El máximo no puede pasar de 20 segundos.'; continue; }
            $nuevo[$k] = $v;
        }
    }
    if (!$errores) {
        $datos = ['perfil' => $perfil, 'tiempos' => $nuevo, 'actualizado' => date('c'), 'por' => $admin['usuario']];
        $dir = dirname(archivo_ajustes());
        if (!is_dir($dir)) @mkdir($dir, 0750, true);
        if (@file_put_contents(archivo_ajustes(), json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
            aviso('error', 'No se pudo guardar: el servidor no puede escribir en la carpeta storage. Dele permiso de escritura en Plesk.');
        } else {
            auditar('simulador_tiempos', $perfil);
            aviso('ok', 'Tiempos guardados. Se aplican desde la próxima partida de cada corredor.');
        }
        redirigir(url('admin/simulador.php'));
    }
    $actual = $nuevo + $actual;
}

$perfilActual = $guardado['perfil'] ?? null;
if (!$perfilActual) {
    foreach (TIEMPOS_PERFILES as $k => [, $t]) if ($t == array_intersect_key($actual, $t)) { $perfilActual = $k; break; }
}
$perfilActual = $perfilActual ?? 'personalizado';

pagina_inicio('Simulador', 'admin-simulador');
cabeza('Tiempos del simulador', 'Cuánto tarda la plataforma simulada en cada momento de la subasta. En cada partida se sortea un valor entre el mínimo y el máximo; casi siempre cae cerca del típico.');
?>
<section class="panel">
  <div class="panel-cab"><h2>Perfil</h2><?= $guardado ? '<span class="tenue" style="font-size:13px">Guardado el ' . h(fmt_fecha($guardado['actualizado'] ?? null, true)) . '</span>' : '<span class="tenue" style="font-size:13px">Usando los valores medidos en la grabación</span>' ?></div>
  <form method="post" class="panel-cuerpo fila" style="gap:10px">
    <?= csrf_campo() ?>
    <?php foreach (TIEMPOS_PERFILES as $k => [$nombre]): ?>
      <button class="btn <?= $perfilActual === $k ? 'btn-tinta' : '' ?>" name="perfil" value="<?= h($k) ?>" type="submit"><?= h($nombre) ?></button>
    <?php endforeach; ?>
    <span class="tenue" style="font-size:13px">Elegir un perfil lo guarda de inmediato.</span>
  </form>
</section>

<form method="post" class="panel" style="margin-top:18px" novalidate>
  <?= csrf_campo() ?><input type="hidden" name="perfil" value="personalizado">
  <div class="panel-cab"><h2>Ajuste fino</h2><span class="tenue" style="font-size:13px"><?= $perfilActual === 'personalizado' ? 'Perfil personalizado' : 'Perfil: ' . h(TIEMPOS_PERFILES[$perfilActual][0]) ?> · valores en segundos</span></div>
  <?php if ($errores): ?><div class="panel-cuerpo" style="padding-bottom:0"><div class="aviso aviso-error" style="margin:0"><?= icono('alerta') ?><span>Revise las filas marcadas.</span></div></div><?php endif; ?>
  <div class="tabla-envoltura"><table class="tabla">
    <thead><tr><th>Momento</th><th class="num">En la grabación</th><th>Mínimo</th><th>Típico</th><th>Máximo</th></tr></thead>
    <tbody>
    <?php foreach (TIEMPOS_MOMENTOS as $k => [$nombre, $medido]): $v = $actual[$k]; $e = isset($errores[$k]); ?>
      <tr>
        <td><b><?= h($nombre) ?></b><?= $e ? '<br><span class="ayuda" style="color:var(--rojo-hondo);font-size:12.5px">' . h($errores[$k]) . '</span>' : '' ?></td>
        <td class="num"><?= h($fmt($medido)) ?> s</td>
        <?php foreach (['min' => 0, 'tip' => 1, 'max' => 2] as $suf => $i): $campo = $k . '_' . $suf; ?>
          <td><input name="<?= h($campo) ?>" type="text" inputmode="decimal" style="width:90px" aria-label="<?= h($nombre . ' · ' . ['mínimo', 'típico', 'máximo'][$i]) ?>" value="<?= h($e ? ($_POST[$campo] ?? '') : $fmt($v[$i])) ?>"<?= $e ? ' aria-invalid="true"' : '' ?>></td>
        <?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <div class="panel-pie fila" style="justify-content:space-between">
    <span>Ejemplo: mínimo 0,7 · típico 1,3 · máximo 4 hace que el captcha tarde casi siempre algo más de un segundo y de vez en cuando hasta 4.</span>
    <button class="btn btn-rojo" type="submit">Guardar tiempos</button>
  </div>
</form>
<?php pagina_fin(); ?>
