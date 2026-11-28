<?php
require dirname(__DIR__) . '/app/bootstrap.php';
$u = requiere_login();
$uid = (int)$u['id'];
$c = config('app');

$lic = licencia_vigente($uid);
$ultimaLic = $lic ?: (q('SELECT * FROM licencias WHERE usuario_id = ? ORDER BY fin DESC LIMIT 1', [$uid])->fetch() ?: null);
$esAdmin = $u['rol'] === 'admin';
$puedeJugar = $esAdmin || $lic;

// Banda de rueda = periodo de la licencia
if ($lic) {
    $ini = strtotime($lic['inicio']);
    $fin = strtotime($lic['fin'] . ' 23:59:59');
    $avance = ($fin > $ini) ? (time() - $ini) / ($fin - $ini) : 1;
    $quedan = dias_hasta($lic['fin']);
    $bandaHtml = banda('abierta', $avance, [
        'Desde <b>' . h(fmt_fecha($lic['inicio'])) . '</b>',
        'Vence <b>' . h(fmt_fecha($lic['fin'])) . '</b> · ' . ($quedan === 0 ? 'último día' : 'quedan ' . $quedan . ' ' . ($quedan === 1 ? 'día' : 'días')),
    ]);
} elseif ($esAdmin) {
    $bandaHtml = banda('abierta', 1, ['Acceso de administrador', 'Sin vencimiento']);
} elseif ($ultimaLic && $ultimaLic['fin'] < date('Y-m-d')) {
    $bandaHtml = banda('cierre', 1, ['Su licencia venció el <b>' . h(fmt_fecha($ultimaLic['fin'])) . '</b>', '']);
} else {
    $bandaHtml = banda('pre', 0, ['Su acceso se abre cuando se registre el pago', '']);
}

$est = estadisticas($uid);
$serie = serie_tiempos($uid, 20);
$cal = ultima_calibracion($uid);
$desde = date('Y-m-d', strtotime('-30 days'));
$rank = ranking_firma($u['firma_id'] ? (int)$u['firma_id'] : null, $desde);
$miPos = null;
foreach ($rank as $i => $r) if ((int)$r['id'] === $uid) $miPos = $i + 1;

$hora = (int)date('G');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
$nombreCorto = explode(' ', trim($u['nombre']))[0];

pagina_inicio('Inicio', 'inicio');
echo $bandaHtml;
cabeza($saludo . ', ' . $nombreCorto, h($u['firma_nombre'] ?? 'Cuenta individual'));
?>

<?php if (!$puedeJugar): ?>
  <?php
    $msg = rawurlencode('Hola, soy ' . $u['nombre'] . ' (usuario ' . $u['usuario'] . '). Quiero activar mi acceso al entrenamiento de ofertas.');
  ?>
  <section class="panel sin-licencia">
    <h2><?= $ultimaLic ? 'Su licencia está vencida' : 'Su cuenta aún no tiene una licencia activa' ?></h2>
    <p class="tenue">Para entrar a la rueda necesita una licencia vigente. Haga el pago por transferencia o Nequi y envíe el comprobante; el acceso se activa el mismo día.</p>
    <div class="fila">
      <?php if (!empty($c['contacto_whatsapp'])): ?>
        <a class="btn btn-rojo" href="https://wa.me/<?= h($c['contacto_whatsapp']) ?>?text=<?= $msg ?>" target="_blank" rel="noopener">Escribir por WhatsApp</a>
      <?php endif; ?>
      <a class="btn" href="mailto:<?= h($c['contacto_correo']) ?>?subject=<?= rawurlencode('Activar acceso · ' . $u['usuario']) ?>">Escribir un correo</a>
    </div>
    <?php if ($est['total']): ?><p class="tenue">Sus resultados anteriores siguen guardados en <a href="<?= h(url('resultados.php')) ?>">Resultados</a>.</p><?php endif; ?>
  </section>
<?php else: ?>
<div class="tablero">
  <section class="panel puesto" aria-labelledby="t-marca">
    <div>
      <h2 id="t-marca">Su mejor marca <span class="tenue" style="font-weight:400;font-size:14px">· <?= h(ESCENARIOS[ESCENARIO_DEFECTO]) ?></span></h2>
      <div class="puesto-marca" style="margin-top:10px">
        <span class="cifra"><?= $est['mejor'] !== null ? h(number_format($est['mejor'] / 1000, 3, ',', '.')) : '—' ?></span>
        <?php if ($est['mejor'] !== null): ?><span class="tenue">segundos</span><span class="record"><?= icono('rombo') ?>Récord personal</span><?php endif; ?>
      </div>
      <?php if ($est['mejor'] === null): ?><p class="tenue" style="margin-top:8px">Aún no tiene partidas. La primera marca queda aquí.</p><?php endif; ?>
    </div>
    <div class="fila">
      <a class="btn btn-rojo btn-grande" href="<?= h(url('jugar.php')) ?>"><?= icono('jugar') ?>Entrar a la rueda</a>
      <?php if (!$cal): ?><a class="btn" href="<?= h(url('calibrar.php')) ?>"><?= icono('calibrar') ?>Calibrar mi pantalla</a><?php endif; ?>
    </div>
    <div class="puesto-dato">
      <div><b><?= $est['promedio10'] !== null ? h(fmt_s($est['promedio10'])) : '—' ?></b><span>Promedio últimas 10</span></div>
      <div><b><?= h($est['lanzadas']) ?></b><span>Ofertas lanzadas</span></div>
      <div><b><?= $miPos ? h($miPos) . ' de ' . count($rank) : '—' ?></b><span>Puesto en la firma (30 días)</span></div>
      <?php if ($cal): ?>
      <div>
        <b><?= h(str_replace('x', ' × ', (string)$cal['resolucion']) . ' · ' . $cal['escala'] . ' %') ?></b>
        <span>Pantalla calibrada el <?= h(fmt_fecha($cal['creado_en'])) ?></span>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <div class="lateral">
    <section class="panel">
      <div class="panel-cab"><h2>Tiempo por partida</h2><a class="btn btn-sutil btn-chico" href="<?= h(url('resultados.php')) ?>">Ver todo</a></div>
      <div class="panel-cuerpo">
        <?php if (count($serie) >= 2): ?>
          <?= grafica_tiempos($serie) ?>
          <p class="tenue" style="font-size:13px;margin-top:6px">Tiempo propio de las últimas <?= count($serie) ?> ofertas lanzadas. Más abajo es más rápido; el punto dorado es su récord.</p>
        <?php else: ?>
          <div class="vacio"><p>Juegue dos partidas para ver su tendencia.</p></div>
        <?php endif; ?>
      </div>
    </section>
    <section class="panel">
      <div class="panel-cab"><h2>Ranking de <?= h($u['firma_nombre'] ?? 'la firma') ?></h2><span class="tenue" style="font-size:13px">Últimos 30 días</span></div>
      <?php if (!$u['firma_id']): ?>
        <div class="vacio"><p>El ranking es por firma. Su cuenta no está asociada a una firma.</p></div>
      <?php elseif (!$rank): ?>
        <div class="vacio"><p>Nadie de la firma ha jugado en los últimos 30 días.</p><p>Su primera partida abre el ranking.</p></div>
      <?php else: ?>
        <ol class="lista-rank">
          <?php foreach (array_slice($rank, 0, 5) as $i => $r): ?>
            <li class="<?= (int)$r['id'] === $uid ? 'yo' : '' ?>"><span class="pos"><?= $i + 1 ?></span><span class="quien"><?= h($r['nombre']) ?></span><b><?= h(fmt_s((int)$r['mejor'])) ?></b></li>
          <?php endforeach; ?>
          <?php if ($miPos && $miPos > 5): $r = $rank[$miPos - 1]; ?>
            <li class="yo"><span class="pos"><?= $miPos ?></span><span class="quien"><?= h($r['nombre']) ?></span><b><?= h(fmt_s((int)$r['mejor'])) ?></b></li>
          <?php endif; ?>
        </ol>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php endif; ?>
<?php pagina_fin(); ?>
