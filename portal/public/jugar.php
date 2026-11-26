<?php
require dirname(__DIR__) . '/app/bootstrap.php';
$u = requiere_licencia();
$uid = (int)$u['id'];

// Lienzo lógico del simulador
preg_match('/^(\d{3,5})x(\d{3,5})$/', (string)config('app.lienzo'), $m);
$W = (int)($m[1] ?? 1920);
$H = (int)($m[2] ?? 1080);

// Ventanas: las del puesto del corredor si calibró en modo "mi puesto"
$cal = ultima_calibracion($uid);
$ventanas = [];
if ($cal && $cal['modo'] === 'mi-puesto') {
    $p = json_decode($cal['perfil'], true) ?: [];
    $principal = null;
    foreach ($p['monitores'] ?? [] as $mon) if (!empty($mon['principal'])) $principal = $mon['id'];
    foreach ($p['ventanas'] ?? [] as $v) {
        if (empty($v['relativo'])) continue;
        if ($principal !== null && isset($v['monitor']) && $v['monitor'] !== $principal) continue;
        $r = $v['relativo'];
        $ventanas[] = ['nombre' => mb_substr((string)$v['nombre'], 0, 60), 'x' => (float)$r['x'], 'y' => (float)$r['y'], 'w' => (float)$r['w'], 'h' => (float)$r['h']];
    }
}

// Token de partida de un solo uso: el servidor valida que el tiempo reportado sea posible
$_SESSION['partida'] = ['token' => bin2hex(random_bytes(16)), 't' => microtime(true)];

$est = estadisticas($uid);
$cfg = [
    'lienzo' => ['w' => $W, 'h' => $H],
    'ventanas' => $ventanas,
    'calibrado' => (bool)$cal,
    'modo' => $cal['modo'] ?? null,
    'mejor' => $est['mejor'],
    'token' => $_SESSION['partida']['token'],
    'csrf' => csrf_token(),
    'guardar' => url('api/guardar-intento.php'),
    'resultados' => url('resultados.php'),
    'inicio' => url('inicio.php'),
    'calibrar' => url('calibrar.php'),
];

pagina_inicio('Rueda de entrenamiento', 'jugar', ['sin_riel' => true, 'clase_body' => 'juego-body']);
?>
<div class="juego" id="juego">
  <div class="juego-barra">
    <a class="btn btn-sutil btn-chico juego-salir" href="<?= h(url('inicio.php')) ?>" id="btnSalir"><?= icono('salir') ?>Salir</a>
    <?= banda('pre', 0, [], 'banda') ?>
    <div class="juego-reloj" id="reloj" aria-live="off">0,000 s</div>
  </div>
  <div class="juego-escena" id="escena">
    <div class="lienzo" id="lienzo" style="width:<?= $W ?>px;height:<?= $H ?>px"></div>
    <div class="falsa" id="falsa" role="status">Anticipado: espere el campanazo</div>
    <div class="juego-centro" id="centro">
      <div class="tarjeta-juego" id="tarjeta">
        <h2>Entrar a la rueda</h2>
        <p>Al iniciar, la pantalla pasa a modo completo y la rueda queda en pre-apertura. En un momento al azar suena el campanazo y aparece <b>LANZAR OFERTA</b>. Lance, ajuste el valor si quiere y confirme lo más rápido posible.</p>
        <p class="tenue" style="font-size:14px">Si hace clic antes del campanazo, cuenta como error. Tiene 30 segundos desde la apertura.</p>
        <?php if (!$cal): ?>
          <div class="aviso aviso-info" style="margin:0"><?= icono('calibrar') ?><span>Aún no ha calibrado su pantalla: verá la distribución estándar. <a href="<?= h(url('calibrar.php')) ?>">Calibrar ahora</a></span></div>
        <?php endif; ?>
        <div class="fila">
          <button class="btn btn-rojo btn-grande" id="btnIniciar" type="button" autofocus><?= icono('jugar') ?>Iniciar</button>
          <?php if ($est['mejor'] !== null): ?><span class="record"><?= icono('rombo') ?>Su récord: <?= h(fmt_s($est['mejor'])) ?></span><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<script type="application/json" id="cfg"><?= json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php pagina_fin(['assets/juego.js']); ?>
