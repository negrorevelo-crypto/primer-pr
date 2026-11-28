<?php
require dirname(__DIR__) . '/app/bootstrap.php';
$u = requiere_login();
$uid = (int)$u['id'];

$periodos = ['7' => '7 días', '30' => '30 días', '90' => '90 días', 'todo' => 'Todo'];
$per = array_key_exists($_GET['p'] ?? '', $periodos) ? $_GET['p'] : '30';
$esc = escenario_valido($_GET['e'] ?? ESCENARIO_DEFECTO);
$desde = $per === 'todo' ? '2000-01-01' : date('Y-m-d', strtotime('-' . (int)$per . ' days'));

// Firma a mostrar: la propia; el administrador puede elegir
$firmaId = $u['firma_id'] ? (int)$u['firma_id'] : null;
$firmas = [];
if ($u['rol'] === 'admin') {
    $firmas = q('SELECT id, nombre FROM firmas ORDER BY nombre')->fetchAll();
    if (isset($_GET['firma']) && ctype_digit((string)$_GET['firma'])) $firmaId = (int)$_GET['firma'];
    elseif (!$firmaId && $firmas) $firmaId = (int)$firmas[0]['id'];
}
$firmaNombre = $firmaId ? q('SELECT nombre FROM firmas WHERE id = ?', [$firmaId])->fetchColumn() : null;
$verEquipo = in_array($u['rol'], ['admin', 'admin_firma'], true) && $firmaId;

$est = estadisticas($uid, $esc);
$serie = serie_tiempos($uid, 40, $esc);
$hist = q("SELECT * FROM intentos WHERE usuario_id = ? AND escenario = ? AND creado_en >= ? ORDER BY id DESC LIMIT 100", [$uid, $esc, $desde])->fetchAll();
$rank = ranking_firma($firmaId, $desde, $esc);

$equipo = [];
if ($verEquipo) {
    $equipo = q("SELECT u.id, u.nombre, u.usuario,
                  COUNT(i.id) partidas,
                  SUM(i.resultado = 'lanzada') lanzadas,
                  MIN(CASE WHEN i.resultado = 'lanzada' THEN COALESCE(i.tiempo_propio_ms, i.tiempo_total_ms) END) mejor,
                  AVG(CASE WHEN i.resultado = 'lanzada' THEN COALESCE(i.tiempo_propio_ms, i.tiempo_total_ms) END) promedio,
                  SUM(i.errores) errores,
                  MAX(i.creado_en) ultima
                 FROM usuarios u LEFT JOIN intentos i ON i.usuario_id = u.id AND i.escenario = ? AND i.creado_en >= ?
                 WHERE u.firma_id = ? AND u.activo = 1 AND u.rol <> 'admin'
                 GROUP BY u.id, u.nombre, u.usuario
                 ORDER BY MIN(CASE WHEN i.resultado = 'lanzada' THEN COALESCE(i.tiempo_propio_ms, i.tiempo_total_ms) END) IS NULL, MIN(CASE WHEN i.resultado = 'lanzada' THEN COALESCE(i.tiempo_propio_ms, i.tiempo_total_ms) END) ASC, u.nombre", [$esc, $desde, $firmaId])->fetchAll();
}

$filtro = '<div class="seg" role="group" aria-label="Periodo">';
foreach ($periodos as $k => $t) {
    $qs = http_build_query(array_filter(['p' => $k, 'e' => $esc, 'firma' => $u['rol'] === 'admin' ? $firmaId : null]));
    $filtro .= '<a href="?' . h($qs) . '"' . ($k === $per ? ' aria-current="true"' : '') . '>' . $t . '</a>';
}
$filtro .= '</div>';
$filtro .= '<div class="seg" role="group" aria-label="Escenario">';
foreach (ESCENARIOS as $k => $t) {
    $qs = http_build_query(array_filter(['p' => $per, 'e' => $k, 'firma' => $u['rol'] === 'admin' ? $firmaId : null]));
    $filtro .= '<a href="?' . h($qs) . '"' . ($k === $esc ? ' aria-current="true"' : '') . '>' . h($t) . '</a>';
}
$filtro .= '</div>';
if ($firmas) {
    $filtro .= '<form method="get" class="fila"><input type="hidden" name="p" value="' . h($per) . '"><input type="hidden" name="e" value="' . h($esc) . '"><select name="firma" aria-label="Firma" onchange="this.form.submit()">';
    foreach ($firmas as $f) $filtro .= '<option value="' . (int)$f['id'] . '"' . ((int)$f['id'] === $firmaId ? ' selected' : '') . '>' . h($f['nombre']) . '</option>';
    $filtro .= '</select></form>';
}

pagina_inicio('Resultados', 'resultados');
cabeza('Resultados', 'Sus partidas y el ranking de ' . h(rtrim($firmaNombre ?? 'su firma', '.')) . '.', $filtro);
$res = ['lanzada' => ['Lanzada', 'chip-verde'], 'cancelada' => ['Cancelada', 'chip-gris'], 'agotada' => ['Tiempo agotado', 'chip-ambar']];
?>
<div class="tablero" style="margin-top:0">
  <section class="panel">
    <div class="panel-cab"><h2>Su evolución</h2><?php if ($est['mejor'] !== null): ?><span class="record"><?= icono('rombo') ?>Récord <?= h(fmt_s($est['mejor'])) ?></span><?php endif; ?></div>
    <div class="panel-cuerpo">
      <?php if (count($serie) >= 2): ?>
        <?= grafica_tiempos($serie) ?>
        <p class="tenue" style="font-size:13px;margin-top:6px">Tiempo propio de las últimas <?= count($serie) ?> ofertas lanzadas (sin lo que tarda la plataforma). Más abajo es más rápido.</p>
      <?php else: ?>
        <div class="vacio"><p>Juegue dos partidas para ver su evolución.</p><p><a class="btn btn-rojo" href="<?= h(url('jugar.php')) ?>" style="margin-top:8px">Entrar a la rueda</a></p></div>
      <?php endif; ?>
    </div>
    <div class="panel-pie fila" style="gap:24px">
      <span><b><?= h($est['lanzadas']) ?></b> lanzadas de <?= h($est['total']) ?> partidas</span>
      <span>Promedio últimas 10: <b><?= h(fmt_s($est['promedio10'])) ?></b></span>
      <span><b><?= h($est['errores']) ?></b> errores en total</span>
    </div>
  </section>

  <section class="panel">
    <div class="panel-cab"><h2>Ranking de <?= h($firmaNombre ?? 'la firma') ?></h2><span class="tenue" style="font-size:13px"><?= h($periodos[$per]) ?> · mejor tiempo propio</span></div>
    <?php if (!$firmaId): ?>
      <div class="vacio"><p>El ranking es por firma. Su cuenta no está asociada a una firma.</p></div>
    <?php elseif (!$rank): ?>
      <div class="vacio"><p>Sin partidas en este periodo.</p></div>
    <?php else: ?>
      <div class="tabla-envoltura"><table class="tabla">
        <thead><tr><th class="num">#</th><th>Corredor</th><th class="num">Mejor tiempo propio</th><th class="num">Partidas</th></tr></thead>
        <tbody>
        <?php foreach ($rank as $i => $r): ?>
          <tr class="<?= (int)$r['id'] === $uid ? 'yo' : '' ?>"><td class="num"><?= $i === 0 ? '<span class="record">' . icono('rombo') . '1</span>' : $i + 1 ?></td><td><?= h($r['nombre']) ?></td><td class="num"><b><?= h(fmt_s((int)$r['mejor'])) ?></b></td><td class="num"><?= h($r['partidas']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php endif; ?>
  </section>
</div>

<?php if ($verEquipo): ?>
<section class="panel" style="margin-top:18px">
  <div class="panel-cab"><h2>Corredores de <?= h($firmaNombre) ?></h2><span class="tenue" style="font-size:13px"><?= count($equipo) ?> cuentas activas</span></div>
  <div class="tabla-envoltura"><table class="tabla">
    <thead><tr><th>Corredor</th><th class="num">Partidas</th><th class="num">Lanzadas</th><th class="num">Mejor</th><th class="num">Promedio</th><th class="num">Errores</th><th>Última partida</th></tr></thead>
    <tbody>
    <?php foreach ($equipo as $e): ?>
      <tr><td><?= h($e['nombre']) ?><br><small><?= h($e['usuario']) ?></small></td><td class="num"><?= (int)$e['partidas'] ?></td><td class="num"><?= (int)$e['lanzadas'] ?></td><td class="num"><b><?= h(fmt_s($e['mejor'] !== null ? (int)$e['mejor'] : null)) ?></b></td><td class="num"><?= h(fmt_s($e['promedio'] !== null ? (int)round((float)$e['promedio']) : null)) ?></td><td class="num"><?= (int)$e['errores'] ?></td><td><?= $e['ultima'] ? h(fmt_fecha($e['ultima'], true)) : '<span class="tenue">Sin partidas</span>' ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<?php endif; ?>

<section class="panel" style="margin-top:18px">
  <div class="panel-cab"><h2>Sus partidas</h2><span class="tenue" style="font-size:13px"><?= h($periodos[$per]) ?><?= count($hist) === 100 ? ' · últimas 100' : '' ?></span></div>
  <?php if (!$hist): ?>
    <div class="vacio"><p>No hay partidas en este periodo.</p></div>
  <?php else: ?>
  <div class="tabla-envoltura"><table class="tabla">
    <thead><tr><th>Fecha</th><th>Resultado</th><th class="num">Reacción</th><th class="num">Tiempo propio</th><th class="num">Total</th><th class="num">Valor lanzado</th><th class="num">Errores</th></tr></thead>
    <tbody>
    <?php foreach ($hist as $i): $tp = $i['tiempo_propio_ms'] ?? $i['tiempo_total_ms']; $esRec = $i['resultado'] === 'lanzada' && $tp !== null && (int)$tp === $est['mejor']; ?>
      <tr>
        <td><?= h(fmt_fecha($i['creado_en'], true)) ?></td>
        <td><span class="chip <?= $res[$i['resultado']][1] ?>"><?= $res[$i['resultado']][0] ?></span></td>
        <td class="num"><?= h(fmt_s($i['tiempo_reaccion_ms'] !== null ? (int)$i['tiempo_reaccion_ms'] : null)) ?></td>
        <td class="num"><?= $esRec ? '<span class="record">' . icono('rombo') . h(fmt_s((int)$tp)) . '</span>' : '<b>' . h(fmt_s($tp !== null ? (int)$tp : null)) . '</b>' ?></td>
        <td class="num"><?= h(fmt_s($i['tiempo_total_ms'] !== null ? (int)$i['tiempo_total_ms'] : null)) ?></td>
        <td class="num"><?= h(fmt_cop($i['valor_lanzado'])) ?></td>
        <td class="num"><?= (int)$i['errores'] ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>
<?php pagina_fin(); ?>
