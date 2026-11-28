<?php
require dirname(__DIR__) . '/app/bootstrap.php';
$u = requiere_licencia();
preg_match('/^(\d{3,5})x(\d{3,5})$/', (string)config('app.lienzo'), $m);
$cal = ultima_calibracion((int)$u['id']);
$cfg = [
    'lienzo' => ['w' => (int)($m[1] ?? 1920), 'h' => (int)($m[2] ?? 1080)],
    'guardar' => url('api/guardar-calibracion.php'),
    'jugar' => url('jugar.php'),
    'csrf' => csrf_token(),
    'usuario' => $u['usuario'],
];

pagina_inicio('Calibrar pantalla', 'calibrar');
cabeza('Calibrar pantalla', $cal
    ? 'Última calibración: ' . h(fmt_fecha($cal['creado_en'])) . ' · ' . h($cal['resolucion']) . ' al ' . (int)$cal['escala'] . ' %. Repítala si cambió de computador o de monitores.'
    : 'Hágalo en el computador y el navegador que usa en la rueda. Tarda unos 3 minutos.');
?>
<div class="pasos">
  <section class="panel paso">
    <div class="panel-cab"><h2><span class="paso-n">1</span>Su pantalla</h2></div>
    <div class="panel-cuerpo">
      <p class="tenue">Estos datos se leen solos. Antes, deje el zoom del navegador en 100 %: presione <b>Ctrl + 0</b> (cero).</p>
      <div class="datos" id="datos"></div>
      <div id="avisoZoom"></div>
      <div class="rejilla" style="margin-top:14px">
        <div class="campo">
          <label for="escala">Escala de Windows (Configuración › Pantalla › Escala)</label>
          <select id="escala">
            <option value="100">100 %</option><option value="125">125 %</option><option value="150">150 %</option>
            <option value="175">175 %</option><option value="200">200 %</option><option value="225">225 %</option>
            <option value="250">250 %</option><option value="300">300 %</option><option value="otra">Otra</option>
          </select>
        </div>
      </div>
      <div class="fila" style="margin-top:14px">
        <button class="btn" id="btnDetectar" type="button">Volver a detectar</button>
        <button class="btn" id="btnMonitores" type="button"><?= icono('calibrar') ?>Detectar todos mis monitores</button>
        <button class="btn btn-sutil" id="btnAgregarMonitor" type="button"><?= icono('mas') ?>Agregar monitor a mano</button>
      </div>
      <div id="avisoMonitores" style="margin-top:12px"></div>
      <div id="formMonitor" hidden>
        <div class="rejilla" style="margin-top:4px">
          <div class="campo"><label for="mRes">Resolución del monitor</label><input id="mRes" type="text" placeholder="1920 x 1080"></div>
          <div class="campo"><label for="mEsc">Escala de ese monitor (%)</label><input id="mEsc" type="number" value="100" min="100" max="400" step="25"></div>
          <div class="campo"><label for="mPos">Ubicación respecto a este monitor</label>
            <select id="mPos"><option value="derecha">A la derecha</option><option value="izquierda">A la izquierda</option><option value="arriba">Arriba</option><option value="abajo">Abajo</option></select></div>
        </div>
        <div class="form-pie"><button class="btn btn-tinta" id="btnGuardarMonitor" type="button">Agregar</button><button class="btn btn-sutil" id="btnCancelarMonitor" type="button">Cancelar</button></div>
      </div>
    </div>
  </section>

  <section class="panel paso">
    <div class="panel-cab"><h2><span class="paso-n">2</span>Prueba de ajuste</h2></div>
    <div class="panel-cuerpo">
      <p class="tenue">Se abre en pantalla completa el área de trabajo del simulador (<span id="lienzoTxt"></span>). Revise que vea las 4 esquinas rojas completas y que el círculo se vea redondo.</p>
      <div class="fila" style="margin-top:14px"><button class="btn" id="btnPrueba" type="button">Iniciar prueba en pantalla completa</button></div>
      <div id="resPrueba"></div>
    </div>
  </section>

  <section class="panel paso">
    <div class="panel-cab"><h2><span class="paso-n">3</span>Distribución de sus ventanas</h2></div>
    <div class="panel-cuerpo">
      <p class="tenue" style="margin-bottom:12px">¿Cómo quiere ver las ventanas en el simulador?</p>
      <label class="radio"><input type="radio" name="modo" value="grabacion" checked><span>Igual a la sesión grabada<small>Todos los corredores ven la misma distribución.</small></span></label>
      <label class="radio"><input type="radio" name="modo" value="mi-puesto"><span>Como las tengo en mi computador<small>Arrastre cada ventana a su lugar y cambie su tamaño desde la esquina inferior derecha.</small></span></label>
      <div id="editor" hidden>
        <div id="escritorio"></div>
        <div class="tabla-envoltura">
          <table class="tabla vent">
            <thead><tr><th>Ventana</th><th>X</th><th>Y</th><th>Ancho</th><th>Alto</th><th></th></tr></thead>
            <tbody id="tablaVentanas"></tbody>
          </table>
        </div>
        <div class="fila" style="margin-top:12px"><button class="btn" id="btnAgregarVentana" type="button"><?= icono('mas') ?>Agregar ventana</button><button class="btn btn-sutil" id="btnReiniciarVentanas" type="button">Restablecer</button></div>
        <p class="tenue" style="margin-top:8px;font-size:13px">Medidas en píxeles de Windows. La ventana llamada “Ingreso de oferta” es donde aparece el botón LANZAR OFERTA en el juego.</p>
      </div>
    </div>
  </section>

  <section class="panel paso">
    <div class="panel-cab"><h2><span class="paso-n">4</span>Captura y guardado</h2></div>
    <div class="panel-cuerpo">
      <p class="tenue">Opcional: adjunte una captura de su escritorio con la plataforma abierta como la usa. Tómela con <b>Windows + Impr Pant</b>; queda en <b>Imágenes › Capturas de pantalla</b>. Puede tapar datos de clientes.</p>
      <div class="rejilla" style="margin-top:14px">
        <div class="campo"><label for="capturas">Capturas (PNG o JPG, máximo 3)</label><input id="capturas" type="file" accept="image/png,image/jpeg,image/webp" multiple></div>
        <div class="campo"><label for="comentario">Comentarios</label><textarea id="comentario" maxlength="1000" placeholder="Algo que debamos saber de su puesto de trabajo"></textarea></div>
      </div>
      <label class="radio" style="margin-top:14px"><input type="checkbox" id="autoriza"><span>Autorizo el tratamiento de estos datos para configurar el simulador de entrenamiento, conforme a la Ley 1581 de 2012.</span></label>
      <div class="fila" style="margin-top:14px"><button class="btn btn-rojo" id="btnEnviar" type="button">Guardar calibración</button><button class="btn btn-sutil" id="btnDescargar" type="button">Descargar mi perfil</button></div>
      <div id="resultado" style="margin-top:12px"></div>
      <details style="margin-top:12px"><summary>Ver los datos que se guardan</summary><pre id="vistaJson"></pre></details>
    </div>
  </section>
</div>

<div id="prueba" role="dialog" aria-label="Prueba de ajuste">
  <svg id="svgPrueba" preserveAspectRatio="xMidYMid meet"></svg>
  <div class="barra">
    <button class="btn btn-rojo" id="btnPruebaOk" type="button">Veo las 4 esquinas y el círculo redondo</button>
    <button class="btn" id="btnPruebaMal" type="button">Algo se ve cortado o deformado</button>
  </div>
</div>
<script type="application/json" id="cfg"><?= json_encode($cfg, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php pagina_fin(['assets/calibrador.js']); ?>
