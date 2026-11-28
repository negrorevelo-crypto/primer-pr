(() => {
  "use strict";
  // Réplica de la subasta descendente de la rueda.
  // Pantalla construida en HTML real (tablas, textos y controles), con la distribución de la página de muestra.
  // Secuencia: pre-apertura → apertura → aparece "Lanzar Oferta" → Colocación (precio + captcha + Lanzar) →
  // Atención (Aceptar) → calce o superado por otro operador.
  const cfg = JSON.parse(document.getElementById("cfg").textContent);
  const $ = (s, r = document) => r.querySelector(s);
  const escena = $("#escena"), centro = $("#centro"), tarjeta = $("#tarjeta"), juego = $("#juego");
  const banda = $("#banda"), rellenoB = banda.querySelector(".banda-relleno"), reloj = $("#reloj");
  const LIMITE_MS = 60000;
  // Tiempo propio (sin la plataforma) del operador simulado
  const DIFICULTAD = { normal: { t: "Normal", media: 2700, desv: 600 }, exigente: { t: "Exigente", media: 2000, desv: 450 }, experto: { t: "Experto", media: 1500, desv: 300 } };
  let dificultad = "normal";
  try { const d = localStorage.getItem("rp-dificultad"); if (DIFICULTAD[d]) dificultad = d; } catch (e) {}

  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const rnd = (a, b) => a + Math.random() * (b - a);
  const normal = (m, d) => { let u = 0, v = 0; while (!u) u = Math.random(); while (!v) v = Math.random(); return m + d * Math.sqrt(-2 * Math.log(u)) * Math.cos(2 * Math.PI * v); };
  // Triangular: casi siempre cerca del valor típico, a veces más rápido o más lento
  const tri = ([a, c, b]) => { const u = Math.random(), f = (c - a) / (b - a); return Math.round(u < f ? a + Math.sqrt(u * (b - a) * (c - a)) : b - Math.sqrt((1 - u) * (b - a) * (b - c))); };
  const fmtS = (ms) => (ms / 1000).toFixed(3).replace(".", ",") + " s";
  const fmtCorto = (ms) => String(Math.round(ms / 10) / 100).replace(".", ",");
  const fmtCOP = (n) => "$ " + Math.round(n).toLocaleString("es-CO");
  const hora = (d) => d.toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit", second: "2-digit" });
  const fecha = (d) => `${d.getMonth() + 1}/${d.getDate()}/${String(d.getFullYear()).slice(2)}, ${hora(d)}`;
  const dia = (d) => `${String(d.getDate()).padStart(2, "0")}/${String(d.getMonth() + 1).padStart(2, "0")}/${d.getFullYear()}`;
  const hms = (ms) => { const s = Math.max(0, Math.ceil(ms / 1000)); return [Math.floor(s / 3600), Math.floor(s / 60) % 60, s % 60].map((x) => String(x).padStart(2, "0")).join(":"); };

  // ---------- Sonidos de la rueda ----------
  // Sintetizados con el navegador, con los tonos y tiempos medidos en el audio de la grabación
  let sonidoOn = true;
  try { sonidoOn = localStorage.getItem("rp-sonido") !== "no"; } catch (e) {}
  let actx = null;
  const audio = () => { if (!actx) { try { actx = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) { actx = null; } } if (actx && actx.state === "suspended") actx.resume(); return actx; };
  function tono(f, t0, dur, { tipo = "sine", vol = 0.22, armonico = 0 } = {}) {
    const c = audio(); if (!c || !sonidoOn) return;
    const t = c.currentTime + t0;
    const g = c.createGain(); g.connect(c.destination);
    g.gain.setValueAtTime(0.0001, t); g.gain.exponentialRampToValueAtTime(vol, t + 0.006); g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
    [[f, 1], ...(armonico ? [[f * 2, armonico]] : [])].forEach(([fr, a]) => {
      const o = c.createOscillator(), ga = c.createGain();
      o.type = tipo; o.frequency.setValueAtTime(fr, t); ga.gain.value = a;
      o.connect(ga).connect(g); o.start(t); o.stop(t + dur + 0.02);
    });
  }
  const sonar = {
    colocacion: () => { tono(2093, 0, 0.07); tono(2637, 0.25, 0.22); },
    atencion: () => { tono(1690, 0, 0.07, { vol: 0.2 }); tono(1110, 0.1, 0.1, { vol: 0.2 }); },
    otro: () => [[370, 0, 0.5], [277, 0.5, 0.3], [466, 0.7, 0.12], [277, 0.82, 0.18], [370, 1.0, 0.12], [554, 1.1, 0.4]]
      .forEach(([f, t0, d]) => tono(f, t0, d, { tipo: "triangle", vol: 0.3, armonico: 0.35 })),
  };

  // ---------- Tiempos de la plataforma ----------
  let T = Object.assign({}, cfg.tiempos);
  let mis = cfg.misTiempos || { perfil: "oficial", tiempos: cfg.oficiales };

  // ---------- Datos de la subasta ----------
  const num = Math.floor(rnd(20000, 29999)), ident = Math.floor(rnd(400000000, 499999999)), idProd = Math.floor(rnd(100000000, 199999999));
  const PRECIO_BASE = 450000000;
  const DESC = "BI-190-SERVICIO-VIGILANCIA";
  const SUBYACENTE = "13328-SERVICIO DE VIGILANCIA Y SEGURIDAD";

  function pantalla() {
    const hoy = new Date();
    const otras = [
      [ident, DESC, hoy],
      [ident + 1000000, "SUBASTA-LECHE-LIQUIDA-REGIONAL-1", hoy],
      [ident + 2000000, "SUBASTA-LECHE-LIQUIDA-REGIONAL-2", hoy],
      [ident + 3000000, "BI-191-DISPOSITIVOS-TECNOLOGICOS", new Date(hoy.getTime() + 6 * 86400000)],
    ];
    const filasLista = otras.map(([id, n, d]) => `<tr><td><a>${id}</a></td><td><a>Entrar</a></td><td><b>${n}</b></td><td class="d">${dia(d)}</td><td class="d">${dia(d)}</td></tr>`).join("");
    const campo = (l, v, id = "") => `<div class="rp-campo"><span class="l">${l}</span><span class="v"${id ? ` id="${id}"` : ""}>${v}</span></div>`;
    return `<div class="rp" id="rp">
      <header class="rp-cab">
        <div class="rp-logo">SIM</div>
        <div class="rp-reloj" id="rpReloj">--:--:-- --</div>
        <span class="rp-tag">SIMULADOR</span>
        <div class="rp-usuario"><span class="av" aria-hidden="true"></span><span class="ini">${esc(cfg.iniciales || "")}</span><span class="con">Conectado</span></div>
      </header>
      <nav class="rp-lado" aria-hidden="true"><span class="raya"></span><span class="act"><i></i></span><span class="mapa"></span></nav>
      <main class="rp-main">
        <section class="rp-sup">
          <div class="rp-izq">
            <div class="rp-tabs"><span class="sel">SUBASTAS ABIERTAS <i>×</i></span><span>HISTÓRICO SUBASTAS <i>×</i></span></div>
            <div class="rp-filtros">
              <label class="rp-f" style="width:110px"><span>Id</span><i></i></label>
              <label class="rp-f" style="width:115px"><span>Fecha Inicio</span><i></i></label>
              <label class="rp-f" style="width:120px"><span>Fecha Fin</span><i></i></label>
              <label class="rp-f rp-sel" style="width:160px"><span>Seleccione una Rueda *</span><b>Todas <em>▾</em></b><i></i></label>
              <label class="rp-check"><span class="caja"></span>Ver todas</label>
            </div>
            <div class="rp-barra" style="width:72%"></div>
            <div class="rp-lista"><table><thead><tr><th>Id</th><th>Entrar</th><th>Nombre</th><th class="d">Fecha Inicio</th><th class="d">Fecha Fin</th></tr></thead><tbody>${filasLista}</tbody></table><span class="rp-vbar"></span></div>
            <div class="rp-barra" style="width:60%;margin-top:2px"></div>
          </div>
          <div class="rp-der">
            <div class="rp-tabs"><span class="sel">ADJUDICACIÓN <i>×</i></span></div>
            <div class="rp-adj"><table><thead><tr><th>Código de Bolsa</th><th>Subasta</th><th>Subyacente</th><th>Operador</th><th>Código de Operador</th><th>Precio</th><th>Cantidad</th><th>Fecha</th></tr></thead><tbody id="rpAdj"></tbody></table></div>
            <div class="rp-barra" style="width:98%"></div>
          </div>
        </section>
        <div class="rp-tabs rp-tab-sub"><span class="sel">SUBASTA <i>×</i></span></div>
        <section class="rp-banda">
          ${campo("Número de Subasta", num)}${campo("Identificador de Subasta", ident)}${campo("Descripción", DESC)}${campo("Metodología", "Descendente")}${campo("Fecha Inicio", "", "rpIni")}${campo("Fecha Fin", "", "rpFin")}
          <label class="rp-check grande"><span class="caja"></span>Habilitado</label>
        </section>
        <div class="rp-prod-t">Productos</div>
        <div class="rp-prod"><table>
          <thead><tr><th>Id</th><th>Subyacente</th><th>Controles Oferta</th><th>Precio Base</th><th>Oferta</th><th>Postura</th><th>Temporizador</th><th>Puja</th><th>Posición</th><th>Cantidad Base</th><th>Unidad</th></tr></thead>
          <tbody><tr id="rpFila" class="pre">
            <td class="c-id">${idProd}</td>
            <td class="c-sub">${SUBYACENTE.slice(0, 26)}...</td>
            <td class="c-ctl">Desactivados</td>
            <td class="c-pb" id="rpPB">${fmtCOP(PRECIO_BASE)}</td>
            <td class="c-of" id="rpOferta"></td>
            <td class="c-po" id="rpPostura"></td>
            <td class="c-tm" id="rpTemp"></td>
            <td id="rpPuja"></td>
            <td id="rpPos"></td>
            <td>1</td>
            <td>Servicio</td>
          </tr></tbody>
        </table></div>
        <div class="rp-barra rp-barra-pie" style="width:80%"></div>
      </main>
      <div class="rp-toast" id="rpToast" role="status"><span class="ti">i</span><span id="rpToastTxt"></span><span class="tx">X</span></div>
      <div id="rpCapa"></div>
      <div class="rp-anticipo" id="rpAnticipo">Anticipado: espere a que aparezca Lanzar Oferta</div>
    </div>`;
  }

  // ---------- Avisos ----------
  let toastT = 0;
  function aviso(tipo, txt, ms = 5000) {
    const t = $("#rpToast");
    t.className = "rp-toast ver " + tipo;
    $(".ti", t).textContent = tipo === "error" ? "!" : "i";
    $("#rpToastTxt").textContent = txt;
    clearTimeout(toastT);
    toastT = setTimeout(() => t.classList.remove("ver"), ms);
  }

  // ---------- Estado ----------
  let fase = "listo", tIni = 0, demora = 0, apertura = null, tApert = 0, tBoton = 0, rivalHumano = 0, rivalTotal = 0, rivalHecho = false;
  let marcas = {}, demoras = {}, errores = 0, timers = [], rafId = 0, valor = PRECIO_BASE, token = cfg.token, esperando = false;
  const eventos = [];
  const programar = (fn, ms) => { const id = setTimeout(fn, ms); timers.push(id); return id; };
  const limpiar = () => { timers.forEach(clearTimeout); timers = []; cancelAnimationFrame(rafId); };
  const ahoraSim = () => new Date(apertura.getTime() - demora + (performance.now() - tIni));
  const fila = () => $("#rpFila");

  function ponerBanda(f) {
    banda.dataset.fase = f;
    banda.querySelectorAll(".banda-fases li").forEach((li) => li.toggleAttribute("aria-current", li.dataset.f === f));
    rellenoB.style.setProperty("--avance", f === "pre" ? 0 : 1);
  }

  function tic() {
    $("#rpReloj").textContent = hora(ahoraSim());
    if (fase === "pre") $("#rpTemp").textContent = `Siguiente Ronda # 1 de 1 (${fecha(apertura)})`;
    else if (fase === "abierta") $("#rpTemp").textContent = `Ronda # 1 de 1 ( ${hms(180000 - (performance.now() - tApert))})`;
    rafId = requestAnimationFrame(tic);
  }

  function montar() {
    const viejo = $("#rp"); if (viejo) viejo.remove();
    escena.insertAdjacentHTML("afterbegin", pantalla());
  }

  function iniciar() {
    audio();
    limpiar();
    juego.classList.remove("en-pausa");
    centro.hidden = true;
    montar();
    esperando = false; errores = 0; marcas = {}; rivalHecho = false; valor = PRECIO_BASE; eventos.length = 0;
    // Demoras de la plataforma para esta partida, sorteadas desde ya para que el otro operador enfrente las mismas
    demoras = { boton: tri(T.boton_tras_apertura), colocacion: tri(T.ventana_colocacion), captcha: tri(T.captcha), atencion: tri(T.ventana_atencion) };
    // La apertura cae en un minuto exacto del reloj simulado, como en la rueda
    demora = Math.round(rnd(6000, 15000));
    const base = new Date(Date.now() + demora + 60000);
    base.setSeconds(0, 0);
    apertura = base;
    $("#rpIni").textContent = fecha(apertura);
    $("#rpFin").textContent = fecha(new Date(apertura.getTime() + 180000));
    tIni = performance.now();
    fase = "pre";
    ponerBanda("pre");
    reloj.textContent = "0,000 s";
    rafId = requestAnimationFrame(tic);
    programar(abrir, demora);
  }

  function abrir() {
    fase = "abierta";
    tApert = performance.now();
    ponerBanda("abierta");
    fila().className = "naranja";
    $("#rpPos").textContent = "0";
    aviso("info", `SUBASTA CON ID ${ident} INICIADA`);
    programar(() => {
      tBoton = performance.now();
      fila().className = "abierta";
      $("#rpOferta").innerHTML = `<button class="rp-lanzar" id="rpLanzar" type="button">Lanzar Oferta</button>`;
      $("#rpLanzar").addEventListener("click", (e) => abrirColocacion(e.timeStamp || performance.now()));
      const d = DIFICULTAD[dificultad];
      rivalHumano = Math.round(Math.min(7000, Math.max(900, normal(d.media, d.desv))));
      rivalTotal = rivalHumano + demoras.colocacion + demoras.captcha + demoras.atencion;
      programar(rival, rivalTotal);
    }, demoras.boton);
    programar(() => terminar("agotada"), LIMITE_MS);
  }

  function rival() {
    if (fase !== "abierta" || marcas.aceptar) return;
    rivalHecho = true;
    eventos.push(["rival", rivalTotal]);
    fila().className = "naranja";
    $("#rpPos").textContent = "1";
    aviso("otro", "Otro Operador - Ha realizado una oferta.");
    sonar.otro();
    programar(() => { if (fase === "abierta") fila().className = "abierta"; }, 600);
  }

  const desdeBoton = (t) => Math.round(t - tBoton);

  function abrirColocacion(t) {
    if (fase !== "abierta" || $("#rpColoc") || esperando) return;
    if (marcas.lanzar === undefined) marcas.lanzar = desdeBoton(t);
    eventos.push(["lanzar_oferta", desdeBoton(t)]);
    esperando = true;
    programar(() => { esperando = false; if (fase === "abierta") mostrarColocacion(); }, demoras.colocacion);
  }

  function mostrarColocacion() {
    sonar.colocacion();
    $("#rpCapa").innerHTML = `<div class="rp-velo"></div>
      <div class="rp-modal rp-coloc entra" id="rpColoc" role="dialog" aria-label="Colocación">
        <h3>Colocación</h3><h4>Mercados Fisicos</h4>
        <label class="rp-pblbl" for="rpValor">Precio Base</label>
        <input class="rp-pbin" id="rpValor" type="text" inputmode="numeric" autocomplete="off" value="${fmtCOP(valor)}">
        <div class="rp-cap" id="rpCap" role="checkbox" aria-checked="false" tabindex="0">
          <span class="rp-cap-caja"></span><span class="rp-cap-txt">Soy humano</span>
          <span class="rp-cap-marca"><i></i>Captcha<br>simulado</span>
        </div>
        <button class="rp-btn rp-blanzar" id="rpBLanzar" type="button" disabled>Lanzar</button>
      </div>`;
    const inp = $("#rpValor");
    inp.addEventListener("input", () => { const n = parseInt(inp.value.replace(/\D/g, ""), 10) || 0; valor = n; inp.value = n ? fmtCOP(n) : ""; });
    const cap = $("#rpCap");
    const verificar = (e) => {
      if (cap.classList.contains("girando") || cap.classList.contains("ok")) return;
      marcas.captcha_clic = desdeBoton(e.timeStamp || performance.now());
      cap.classList.add("girando");
      programar(() => {
        cap.classList.remove("girando"); cap.classList.add("ok"); cap.setAttribute("aria-checked", "true");
        marcas.captcha_ok = desdeBoton(performance.now());
        const b = $("#rpBLanzar"); b.disabled = false; b.focus({ preventScroll: true });
      }, demoras.captcha);
    };
    cap.addEventListener("click", verificar);
    cap.addEventListener("keydown", (e) => { if (e.key === " " || e.key === "Enter") { e.preventDefault(); verificar(e); } });
    $("#rpBLanzar").addEventListener("click", (e) => {
      if (esperando) return;
      if (valor < 1) { errores++; eventos.push(["valor_invalido", desdeBoton(performance.now())]); inp.focus(); return; }
      marcas.colocar = desdeBoton(e.timeStamp || performance.now());
      $("#rpCapa").innerHTML = "";
      esperando = true;
      programar(() => { esperando = false; if (fase === "abierta") atencion(); }, demoras.atencion);
    });
  }

  function atencion() {
    sonar.atencion();
    $("#rpCapa").innerHTML = `<div class="rp-velo"></div>
      <div class="rp-modal rp-aten entra" id="rpAten" role="alertdialog" aria-label="Atención">
        <button class="rp-aten-x" id="rpX" type="button" aria-label="Cerrar">x</button>
        <h3>Atención</h3><p>¿Está de acuerdo con el lanzamiento de la oferta?</p>
        <div class="rp-aten-b"><button class="rp-btn" id="rpAceptar" type="button">Aceptar</button><button class="rp-btn" id="rpCancelar" type="button">Cancelar</button></div>
      </div>`;
    if (!rivalHecho) aviso("info", "Se realizó el calce del producto");
    $("#rpAceptar").focus({ preventScroll: true });
    $("#rpAceptar").addEventListener("click", (e) => { marcas.aceptar = desdeBoton(e.timeStamp || performance.now()); terminar("lanzada"); });
    $("#rpCancelar").addEventListener("click", () => terminar("cancelada"));
    $("#rpX").addEventListener("click", () => { errores++; eventos.push(["cerro_confirmacion", desdeBoton(performance.now())]); $("#rpCapa").innerHTML = ""; });
  }

  async function terminar(resultado) {
    if (fase !== "abierta") return;
    fase = "cierre";
    limpiar();
    $("#rpCapa").innerHTML = "";
    const total = resultado === "lanzada" ? marcas.aceptar : null;
    const espera = demoras.colocacion + demoras.captcha + demoras.atencion;
    const propio = total !== null ? total - espera : null;
    const gano = resultado === "lanzada" && !rivalHecho && total < rivalTotal;
    $("#rpTemp").textContent = "Rondas Terminadas";
    if (resultado === "lanzada") {
      $("#rpPostura").textContent = fmtCOP(valor);
      $("#rpPuja").textContent = "P";
      $("#rpPos").textContent = gano ? "1" : "2";
      $("#rpAdj").innerHTML = `<tr><td>${idProd}</td><td>${DESC.slice(0, 9)}...</td><td>${SUBYACENTE.slice(0, 13)}...</td><td>${gano ? esc(cfg.operador) : "Otro operador"}</td><td>${gano ? esc(cfg.codigoOperador) : "—"}</td><td>${fmtCOP(valor)}</td><td>1</td><td>${dia(new Date())}</td></tr>`;
      if (gano) $("#rpPB").classList.add("verde");
      else aviso("error", "Algo salió mal al lanzar la postura.");
    }
    fila().className = "abierta";
    ponerBanda("cierre");
    reloj.textContent = propio !== null ? fmtS(propio) : "—";
    const datos = {
      token, resultado, errores, escenario: cfg.escenario,
      tiempo_reaccion_ms: marcas.lanzar ?? null,
      tiempo_total_ms: total,
      valor_inicial: PRECIO_BASE,
      valor_lanzado: resultado === "lanzada" ? valor : null,
      detalle: { gano, rival_ms: rivalTotal, rival_propio_ms: rivalHumano, dificultad, marcas, demoras, perfil_tiempos: mis.perfil, eventos, pantalla: { w: innerWidth, h: innerHeight, dpr: devicePixelRatio } },
    };
    let resp = null, fallo = "";
    try {
      const r = await fetch(cfg.guardar, { method: "POST", headers: { "Content-Type": "application/json", "X-CSRF": cfg.csrf }, body: JSON.stringify(datos) });
      resp = await r.json();
      if (!resp.ok) fallo = resp.error || "No se pudo guardar.";
      if (resp.token) token = resp.token;
    } catch (e) { fallo = "Sin conexión: esta partida no se guardó."; }
    setTimeout(() => resultadoCard(resultado, total, propio, gano, resp, fallo), 1400);
  }

  const rombo = '<svg class="ico rombo" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.5 21.5 12 12 21.5 2.5 12Z" fill="currentColor"/></svg>';
  function resultadoCard(resultado, total, propio, gano, resp, fallo) {
    juego.classList.add("en-pausa");
    const rec = resultado === "lanzada" && resp && resp.es_record;
    let titulo, linea = "";
    if (resultado === "lanzada") {
      titulo = gano ? (rec ? "Calce logrado · nuevo récord" : "Calce logrado") : "Otro operador calzó primero";
      linea = gano ? `Le ganó al otro operador por ${fmtS(rivalHumano - propio)}.` : `El otro operador tardó ${fmtS(rivalHumano)} propios; usted, ${fmtS(propio)}.`;
    } else {
      titulo = resultado === "cancelada" ? "Oferta cancelada" : "Se agotó el tiempo";
    }
    let comparar = "";
    if (resultado === "lanzada" && resp && resp.ok) {
      if (rec && resp.mejor_anterior) comparar = `<span class="record">${rombo}Mejoró ${fmtS(resp.mejor_anterior - propio)} su marca anterior</span>`;
      else if (rec) comparar = `<span class="record">${rombo}Primera marca registrada</span>`;
      else if (resp.mejor) comparar = `<span class="tenue">Su récord: ${fmtS(resp.mejor)}</span>`;
    }
    const m = marcas, d = demoras;
    const tramo = (v) => (Number.isFinite(v) ? fmtS(Math.max(0, v)) : "—");
    tarjeta.innerHTML = `
      <h2>${titulo}</h2>
      <div><span class="cifra">${propio !== null ? fmtS(propio) : "—"}</span>
        <p class="tenue" style="margin-top:8px">${propio !== null ? `Tiempo propio. Total con la plataforma: ${fmtS(total)}.` : "Sin tiempo registrado."} ${linea}</p>
        <div style="margin-top:8px">${comparar}</div></div>
      <div class="res-datos">
        <div><b>${tramo(m.lanzar)}</b><span>Reacción a Lanzar Oferta</span></div>
        <div><b>${tramo(m.captcha_clic - m.lanzar - d.colocacion)}</b><span>Hasta marcar el captcha</span></div>
        <div><b>${tramo(m.colocar - m.captcha_ok)}</b><span>De verificado a Lanzar</span></div>
        <div><b>${tramo(m.aceptar - m.colocar - d.atencion)}</b><span>Confirmación (Aceptar)</span></div>
        <div><b>${errores}</b><span>Errores</span></div>
        <div><b>${resp && resp.posicion ? resp.posicion + " de " + resp.total_ranking : "—"}</b><span>Puesto en la firma</span></div>
      </div>
      <p class="tenue" style="font-size:13px">Esta vez la plataforma tardó: ventana Colocación ${fmtS(d.colocacion)} · captcha ${fmtS(d.captcha)} · ventana Atención ${fmtS(d.atencion)}</p>
      ${fallo ? `<div class="aviso aviso-error" style="margin:0"><span>${esc(fallo)}</span></div>` : ""}
      ${selectorDificultad()}
      <div id="misTiempos"></div>
      <div class="fila">
        <button class="btn btn-rojo btn-grande" id="btnOtra" type="button">Jugar otra vez</button>
        <a class="btn" href="${cfg.resultados}">Ver resultados</a>
      </div>
      <p class="tenue" style="font-size:13px">Presione Enter para jugar otra vez.</p>`;
    centro.hidden = false;
    conectarDificultad();
    pintarMisTiempos($("#misTiempos"));
    $("#btnOtra").addEventListener("click", iniciar);
    $("#btnOtra").focus({ preventScroll: true });
  }

  // ---------- Velocidad del otro operador ----------
  function selectorDificultad() {
    return `<div class="opciones-juego"><span class="etiqueta">Velocidad del otro operador (tiempo propio)</span><div class="seg" role="group" aria-label="Velocidad del otro operador">${Object.entries(DIFICULTAD)
      .map(([k, d]) => `<a data-dif="${k}"${k === dificultad ? ' aria-current="true"' : ""}>${d.t} · ${fmtCorto(d.media)} s</a>`).join("")}</div>
      <label class="radio" style="margin:4px 0 0;padding:8px 12px"><input type="checkbox" data-sonido${sonidoOn ? " checked" : ""}><span>Sonidos de la rueda<small>Avisos sonoros como en la plataforma: ventana Colocación, ventana Atención y oferta de otro operador.</small></span></label>
      <div><button class="btn btn-sutil btn-chico" type="button" data-probar>Probar sonidos</button></div></div>`;
  }
  function conectarDificultad() {
    const chk = tarjeta.querySelector("[data-sonido]");
    if (chk) chk.addEventListener("change", () => { sonidoOn = chk.checked; try { localStorage.setItem("rp-sonido", sonidoOn ? "si" : "no"); } catch (e) {} });
    const pr = tarjeta.querySelector("[data-probar]");
    if (pr) pr.addEventListener("click", () => { const antes = sonidoOn; sonidoOn = true; sonar.colocacion(); setTimeout(sonar.atencion, 900); setTimeout(() => { sonar.otro(); setTimeout(() => { sonidoOn = antes; }, 1700); }, 1700); });
    tarjeta.querySelectorAll("[data-dif]").forEach((a) => a.addEventListener("click", () => {
      dificultad = a.dataset.dif;
      try { localStorage.setItem("rp-dificultad", dificultad); } catch (e) {}
      tarjeta.querySelectorAll("[data-dif]").forEach((b) => b.toggleAttribute("aria-current", b === a));
    }));
  }

  // ---------- Mis tiempos de la plataforma ----------
  const PERFILES = Object.assign({ oficial: { nombre: "Oficiales", tiempos: cfg.oficiales } }, cfg.perfiles, { personalizado: { nombre: "Personalizados", tiempos: null } });
  function pintarMisTiempos(cont) {
    if (!cont) return;
    const abierto = mis.perfil === "personalizado" || !!(cont.querySelector("details") && cont.querySelector("details").open);
    const valores = mis.perfil === "personalizado" ? mis.tiempos : (PERFILES[mis.perfil] || PERFILES.oficial).tiempos;
    const filas = Object.entries(cfg.momentos).map(([k, m]) => `<tr>
        <td><b>${esc(m.nombre)}</b><br><small>${esc(m.desc)} · mínimo ${fmtCorto(cfg.minimos[k])} s</small></td>
        ${[0, 1, 2].map((i) => `<td><input type="text" inputmode="decimal" data-k="${k}" data-i="${i}" value="${fmtCorto(valores[k][i])}" aria-label="${esc(m.nombre)} · ${["mínimo", "típico", "máximo"][i]}" ${mis.perfil === "personalizado" ? "" : "disabled"}></td>`).join("")}
      </tr>`).join("");
    cont.innerHTML = `<details class="mis-tiempos"${abierto ? " open" : ""}>
        <summary>Mis tiempos de la plataforma: <b>${esc((PERFILES[mis.perfil] || PERFILES.oficial).nombre)}</b></summary>
        <div class="seg" role="group" aria-label="Perfil de tiempos" style="margin-top:10px">${Object.entries(PERFILES).map(([k, p]) => `<a data-perfil="${k}"${k === mis.perfil ? ' aria-current="true"' : ""}>${esc(p.nombre)}</a>`).join("")}</div>
        <div class="tabla-envoltura"><table class="tabla mt-tabla"><thead><tr><th>Momento</th><th>Mínimo (s)</th><th>Típico (s)</th><th>Máximo (s)</th></tr></thead><tbody>${filas}</tbody></table></div>
        <div class="fila" style="margin-top:10px"><button class="btn btn-tinta btn-chico" type="button" data-guardar ${mis.perfil === "personalizado" ? "" : "hidden"}>Guardar mis tiempos</button><span class="tenue mt-msg" style="font-size:13px">En cada partida se sortea un valor entre el mínimo y el máximo. El ranking usa su tiempo propio, así que estos tiempos no cambian su posición.</span></div>
      </details>`;
    cont.querySelectorAll("[data-perfil]").forEach((a) => a.addEventListener("click", () => {
      const p = a.dataset.perfil;
      if (p === "personalizado") {
        mis = { perfil: "personalizado", tiempos: JSON.parse(JSON.stringify(valores)) };
        pintarMisTiempos(cont);
        cont.querySelector("details").open = true;
        return;
      }
      guardarMisTiempos(cont, { perfil: p });
    }));
    const g = cont.querySelector("[data-guardar]");
    if (g) g.addEventListener("click", () => {
      const t = {};
      cont.querySelectorAll("input[data-k]").forEach((inp) => {
        const k = inp.dataset.k, i = +inp.dataset.i;
        (t[k] = t[k] || [0, 0, 0])[i] = Math.round(parseFloat(inp.value.replace(",", ".")) * 1000);
      });
      guardarMisTiempos(cont, { perfil: "personalizado", tiempos: t });
    });
  }
  async function guardarMisTiempos(cont, datos) {
    const msg = () => cont.querySelector(".mt-msg");
    try {
      const r = await fetch(cfg.guardarAjustes, { method: "POST", headers: { "Content-Type": "application/json", "X-CSRF": cfg.csrf }, body: JSON.stringify(datos) });
      const j = await r.json();
      if (!j.ok) { msg().textContent = j.error || "No se pudo guardar."; msg().style.color = "var(--rojo-hondo)"; return; }
      T = Object.assign({}, j.tiempos);
      mis = { perfil: j.perfil, tiempos: j.tiempos };
      pintarMisTiempos(cont);
      msg().textContent = "Guardado. Se aplica desde la próxima partida.";
      msg().style.color = "var(--verde)";
    } catch (e) { msg().textContent = "Sin conexión: no se guardó."; }
  }

  // Clics antes de que aparezca "Lanzar Oferta" = anticipado
  escena.addEventListener("pointerdown", () => {
    const antes = fase === "pre" || (fase === "abierta" && !$("#rpLanzar"));
    if (!antes || !centro.hidden) return;
    errores++;
    eventos.push(["anticipado", Math.round(performance.now() - tIni)]);
    const p = $("#rpAnticipo");
    if (p) { p.classList.add("ver"); clearTimeout(p._t); p._t = setTimeout(() => p.classList.remove("ver"), 1200); }
  });

  // La pantalla queda visible detrás de la tarjeta inicial
  montar();
  $("#rpReloj").textContent = hora(new Date());
  $("#rpTemp").textContent = "Siguiente Ronda # 1 de 1";
  const inicial = $("#selectorInicial");
  if (inicial) { inicial.innerHTML = selectorDificultad(); conectarDificultad(); }
  pintarMisTiempos($("#misTiempos"));
  juego.classList.add("replica", "en-pausa");
  $("#btnIniciar").addEventListener("click", iniciar);
})();
