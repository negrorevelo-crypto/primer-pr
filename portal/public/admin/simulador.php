<?php
require dirname(__DIR__, 2) . '/app/bootstrap.php';
$admin = requiere_admin();

$fmt = fn(int $ms) => rtrim(rtrim(number_format($ms / 1000, 2, ',', ''), '0'), ',');
$aMs = function ($v): ?int {
    $v = str_replace(',', '.', trim((string)$v));
    return is_numeric($v) ? (int)round((float)$v * 1000) : null;
};

$errores = [];
$actual = tiempos_subasta();
$minimos = tiempos_minimos();
$guardado = ajustes_simulador();

function guardar_ajustes(array $datos, array $admin): void
{
    $datos['actualizado'] = date('c');
    $datos['por'] = $admin['usuario'];
    $dir = dirname(archivo_ajustes());
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    if (@file_put_contents(archivo_ajustes(), json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
        aviso('error', 'No se pudo guardar: el servidor no puede escribir en la carpeta storage. Dele permiso de escritura en Plesk.');
    } else {
        aviso('ok', 'Guardado. Se aplica desde la próxima partida de cada corredor.');
    }
}

if (es_post()) {
    csrf_verificar();
    $accion = (string)($_POST['accion'] ?? '');
    if ($accion === 'perfil' && isset(TIEMPOS_PERFILES[$_POST['perfil'] ?? ''])) {
        $guardado['perfil'] = $_POST['perfil'];
        $guardado['tiempos'] = TIEMPOS_PERFILES[$_POST['perfil']][1];
        guardar_ajustes($guardado, $admin);
        auditar('simulador_tiempos', $guardado['perfil']);
        redirigir(url('admin/simulador.php'));
    }
    if ($accion === 'tiempos') {
        $nuevo = [];
        $nuevosMin = [];
        foreach (TIEMPOS_MOMENTOS as $k => [$nombre]) {
            $v = [$aMs($_POST[$k . '_min'] ?? ''), $aMs($_POST[$k . '_tip'] ?? ''), $aMs($_POST[$k . '_max'] ?? '')];
            $piso = $aMs($_POST[$k . '_piso'] ?? '');
            if (in_array(null, $v, true) || $piso === null) { $errores[$k] = 'Escriba todos los valores en segundos.'; continue; }
            if (!($v[0] <= $v[1] && $v[1] <= $v[2] && $v[0] < $v[2])) { $errores[$k] = 'Debe cumplirse mínimo ≤ típico ≤ máximo, y el mínimo menor que el máximo.'; continue; }
            if ($v[2] > 20000 || $piso > 20000) { $errores[$k] = 'Ningún valor puede pasar de 20 segundos.'; continue; }
            $nuevo[$k] = $v;
            $nuevosMin[$k] = $piso;
        }
        if (!$errores) {
            $guardado['perfil'] = 'personalizado';
            foreach (TIEMPOS_PERFILES as $p => [, $t]) if ($t == $nuevo) $guardado['perfil'] = $p;
            $guardado['tiempos'] = $nuevo;
            $guardado['minimos'] = $nuevosMin;
            guardar_ajustes($guardado, $admin);
            auditar('simulador_tiempos', $guardado['perfil'] . ' · mínimos ' . json_encode($nuevosMin));
            redirigir(url('admin/simulador.php'));
        }
        $actual = $nuevo + $actual;
    }
}

$perfilActual = $guardado['perfil'] ?? 'grabacion';
$personales = (int)q("SELECT COUNT(*) FROM usuarios WHERE ajustes_juego IS NOT NULL AND ajustes_juego NOT LIKE '%\"oficial\"%' AND activo = 1")->fetchColumn();

pagina_inicio('Simulador', 'admin-simulador');
cabeza('Tiempos del simulador', 'Cuánto tarda la plataforma simulada en cada momento de la subasta. En cada partida se sortea un valor entre el mínimo y el máximo; casi siempre cae cerca del típico.');
?>
<section class="panel">
  <div class="panel-cab"><h2>Tiempos oficiales</h2><?= !empty($guardado['actualizado']) ? '<span class="tenue" style="font-size:13px">Guardado el ' . h(fmt_fecha($guardado['actualizado'], true)) . '</span>' : '<span class="tenue" style="font-size:13px">Usando los valores medidos en la grabación</span>' ?></div>
  <form method="post" class="panel-cuerpo fila" style="gap:10px">
    <?= csrf_campo() ?><input type="hidden" name="accion" value="perfil">
    <?php foreach (TIEMPOS_PERFILES as $k => [$nombre]): ?>
      <button class="btn <?= $perfilActual === $k ? 'btn-tinta' : '' ?>" name="perfil" value="<?= h($k) ?>" type="submit"><?= h($nombre) ?></button>
    <?php endforeach; ?>
    <span class="tenue" style="font-size:13px">Son los tiempos de quien no elija los suyos. Elegir un perfil lo guarda de inmediato.</span>
  </form>
  <div class="panel-pie"><?= $personales ? '<b>' . $personales . '</b> ' . ($personales === 1 ? 'corredor juega' : 'corredores juegan') . ' con sus propios tiempos. ' : '' ?>El ranking compara el <b>tiempo propio</b> (total menos lo que tardó la plataforma), así que los tiempos de cada uno no cambian su posición.</div>
</section>

<form method="post" class="panel" style="margin-top:18px" novalidate>
  <?= csrf_campo() ?><input type="hidden" name="accion" value="tiempos">
  <div class="panel-cab"><h2>Ajuste fino</h2><span class="tenue" style="font-size:13px"><?= $perfilActual === 'personalizado' ? 'Perfil personalizado' : 'Perfil: ' . h(TIEMPOS_PERFILES[$perfilActual][0] ?? '') ?> · valores en segundos</span></div>
  <?php if ($errores): ?><div class="panel-cuerpo" style="padding-bottom:0"><div class="aviso aviso-error" style="margin:0"><?= icono('alerta') ?><span>Revise las filas marcadas.</span></div></div><?php endif; ?>
  <div class="tabla-envoltura"><table class="tabla">
    <thead><tr><th>Momento</th><th class="num">En la grabación</th><th>Mínimo</th><th>Típico</th><th>Máximo</th><th>Mínimo para corredores</th></tr></thead>
    <tbody>
    <?php foreach (TIEMPOS_MOMENTOS as $k => [$nombre, $desc, $medido]): $v = $actual[$k]; $e = isset($errores[$k]); ?>
      <tr>
        <td><b><?= h($nombre) ?></b><br><small><?= h($desc) ?></small><?= $e ? '<br><span style="color:var(--rojo-hondo);font-size:12.5px">' . h($errores[$k]) . '</span>' : '' ?></td>
        <td class="num"><?= h($fmt($medido)) ?> s</td>
        <?php foreach (['min' => 0, 'tip' => 1, 'max' => 2] as $suf => $i): $campo = $k . '_' . $suf; ?>
          <td><input name="<?= h($campo) ?>" type="text" inputmode="decimal" style="width:84px" aria-label="<?= h($nombre . ' · ' . ['mínimo', 'típico', 'máximo'][$i]) ?>" value="<?= h($e ? ($_POST[$campo] ?? '') : $fmt($v[$i])) ?>"<?= $e ? ' aria-invalid="true"' : '' ?>></td>
        <?php endforeach; ?>
        <td><input name="<?= h($k . '_piso') ?>" type="text" inputmode="decimal" style="width:84px" aria-label="<?= h($nombre . ' · mínimo para corredores') ?>" value="<?= h($e ? ($_POST[$k . '_piso'] ?? '') : $fmt($minimos[$k])) ?>"<?= $e ? ' aria-invalid="true"' : '' ?>></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <div class="panel-pie fila" style="justify-content:space-between">
    <span>Ningún corredor puede poner un valor por debajo del <b>mínimo para corredores</b>. Ejemplo: captcha 0,7 · 1,3 · 4 tarda casi siempre algo más de un segundo y a veces hasta 4.</span>
    <button class="btn btn-rojo" type="submit">Guardar tiempos</button>
  </div>
</form>
<?php pagina_fin(); ?>
