(() => {
  "use strict";
  // Réplica de la subasta descendente de la rueda, a partir de la grabación de una sesión real.
  // Secuencia: pre-apertura → apertura (fila naranja + aviso) → aparece "Lanzar Oferta" →
  // Colocación (precio base + "Soy humano" + Lanzar) → Atención (Aceptar) → calce o superado por otro operador.
  const cfg = JSON.parse(document.getElementById("cfg").textContent);
  const $ = (s, r = document) => r.querySelector(s);
  const W = 1920, H = 1020;
  const escena = $("#escena"), centro = $("#centro"), tarjeta = $("#tarjeta"), juego = $("#juego");
  const banda = $("#banda"), rellenoB = banda.querySelector(".banda-relleno"), reloj = $("#reloj");
  const LIMITE_MS = 60000;
  const DIFICULTAD = { normal: { t: "Normal", media: 4500, desv: 900 }, exigente: { t: "Exigente", media: 3500, desv: 700 }, experto: { t: "Experto", media: 2800, desv: 500 } };
  let dificultad = "normal";
  try { const d = localStorage.getItem("rp-dificultad"); if (DIFICULTAD[d]) dificultad = d; } catch (e) {}

  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const rnd = (a, b) => a + Math.random() * (b - a);
  const normal = (m, d) => { let u = 0, v = 0; while (!u) u = Math.random(); while (!v) v = Math.random(); return m + d * Math.sqrt(-2 * Math.log(u)) * Math.cos(2 * Math.PI * v); };
  const fmtS = (ms) => (ms / 1000).toFixed(3).replace(".", ",") + " s";
  const fmtUS = (n) => "$ " + Math.round(n).toLocaleString("en-US");
  const hora = (d) => d.toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit", second: "2-digit" });
  const fecha = (d) => `${d.getMonth() + 1}/${d.getDate()}/${String(d.getFullYear()).slice(2)}, ${hora(d)}`;
  const hms = (ms) => { const s = Math.max(0, Math.ceil(ms / 1000)); return [Math.floor(s / 3600), Math.floor(s / 60) % 60, s % 60].map((x) => String(x).padStart(2, "0")).join(":"); };

  // ---------- Construcción de la pantalla ----------
  const num = Math.floor(rnd(20000, 29999)), ident = Math.floor(rnd(400000000, 499999999)), idProd = Math.floor(rnd(77000000, 77999999));
  const PRECIO_BASE = 500000000;
  function pantalla() {
    const cols = (arr, y) => arr.map(([t, x]) => `<span class="abs" style="left:${x}px;top:${y}px">${t}</span>`).join("");
    const campo = (l, v, x, id = "") => `<div class="abs rp-campo" style="left:${x}px;top:458px"><b>${l}</b><span${id ? ` id="${id}"` : ""}>${v}</span></div>`;
    return `<div class="rp" id="rp">
      <div class="abs rp-nav"></div>
      <div class="abs rp-pestanas"></div>
      <div class="abs rp-pestana"><i></i>Rueda de entrenamiento</div>
      <div class="abs rp-url">entrenamiento.revelco.com.co/simulador/subasta</div>
      <div class="abs rp-marcadores">Simulación de entrenamiento · no es la plataforma real</div>
      <div class="abs rp-cab"></div>
      <div class="abs rp-logo">SIM</div>
      <div class="abs rp-reloj" id="rpReloj">--:--:-- --</div>
      <div class="abs rp-usuario"><span class="av"></span>${esc(cfg.nombreCorto)} <span>⌄</span></div>
      <div class="abs rp-con">Conectado</div>
      <div class="abs rp-lado"></div><div class="abs rp-lado-raya"></div><div class="abs rp-lado-act"></div>
      <div class="abs rp-tabs">
        <span class="abs rp-tab sel" style="left:0">SUBASTAS ABIERTAS ×</span>
        <span class="abs rp-tab gris" style="left:212px">HISTÓRICO SUBASTAS ×</span>
        <span class="abs rp-tab" style="left:936px">ADJUDICACIÓN ×</span>
      </div>
      <div class="abs rp-filtros">
        ${cols([["Id", 8], ["Fecha Inicio", 201], ["Fecha Fin", 395], ["Seleccione una Rueda *", 589]], 9)}
        <span class="abs" style="left:589px;top:33px;font-size:19px">Todas ▾</span>
        <span class="abs" style="left:783px;top:38px;width:24px;height:24px;border:2px solid #c9ced6;border-radius:2px"></span>
        <span class="abs" style="left:817px;top:39px;font-size:19px">Habilitado</span>
        <div class="abs rp-linea" style="left:8px;top:64px;width:160px"></div><div class="abs rp-linea" style="left:201px;top:64px;width:160px"></div><div class="abs rp-linea" style="left:395px;top:64px;width:160px"></div><div class="abs rp-linea" style="left:589px;top:64px;width:160px"></div>
        <div class="abs rp-scroll" style="left:2px;top:92px;width:696px"></div>
        ${cols([["Id", 12], ["Entrar", 137], ["Nombre", 237], ["Fecha Inicio", 390], ["Hora Inicio ↑", 518], ["Rueda", 652]], 133)}
      </div>
      <div class="abs rp-adj">${cols([["Código de Bolsa", 14], ["Subasta", 176], ["Subyacente", 282], ["Operador", 412], ["Código de Operador", 529], ["Precio", 720], ["Cantidad", 814]], 24)}
        <div class="abs rp-scroll" style="left:3px;top:135px;width:825px;height:12px;background:#264c6b"></div></div>
      <div class="abs rp-sub-tab">SUBASTA ×</div>
      <div class="abs rp-banda"></div>
      ${campo("Número de Subasta", num, 226)}${campo("Identificador de Subasta", ident, 486)}${campo("Descripción", "VIGILANCIA-4", 747)}${campo("Metodología", "Descendente", 1008)}${campo("Fecha Inicio", "", 1268, "rpIni")}${campo("Fecha Fin", "", 1529, "rpFin")}
      <div class="abs rp-prod-cab">Productos</div>
      <div class="abs rp-col-cab">${cols([["Id", 16], ["Precio Base", 126], ["Oferta", 282], ["Postura", 683], ["Subyacente", 932], ["Posici...", 1127], ["Temporizador", 1211]], 10)}</div>
      <div class="abs rp-fila" id="rpFila">
        <span class="abs rp-c" style="left:16px">${idProd}</span>
        <span class="abs rp-c rp-pb" id="rpPB">${PRECIO_BASE.toLocaleString("en-US")}</span>
        <span class="abs rp-c rp-of">Precio</span>
        <span class="abs rp-c rp-roj"></span>
        <span class="abs rp-ofertar">OFERTAR <em>$</em></span>
        <span class="abs rp-ico-mas">+</span><span class="abs rp-ico-bote"></span>
        <span id="rpPostura"></span>
        <span class="abs rp-c rp-subya">VIGILANCIA</span>
        <span class="abs rp-c rp-pos" id="rpPos"></span>
        <span class="abs rp-c rp-temp" id="rpTemp"></span>
      </div>
      <div class="abs rp-scroll" style="left:84px;top:950px;width:1222px;height:12px;background:#264c6b"></div>
      <div class="abs rp-pie"></div>
      <div class="abs rp-chat"></div>
      <div class="abs rp-toast" id="rpToast"><span class="ti">i</span><span id="rpToastTxt"></span><span class="tx">X</span></div>
      <div id="rpCapa"></div>
      <div class="abs rp-anticipo" id="rpAnticipo">Anticipado: espere a que aparezca Lanzar Oferta</div>
    </div>`;
  }

  function ajustar() {
    const rp = $("#rp");
    if (!rp) return;
    const r = escena.getBoundingClientRect();
    const k = Math.min(r.width / W, r.height / H);
    // Pegado arriba, como un navegador maximizado; centrado horizontalmente
    rp.style.transform = `translate(${(r.width - W * k) / 2}px, 0) scale(${k})`;
  }
  window.addEventListener("resize", ajustar);
  document.addEventListener("fullscreenchange", ajustar);

  // ---------- Avisos ----------
  let toastT = 0;
  function aviso(tipo, txt, ms = 5000) {
    const t = $("#rpToast");
    t.className = "abs rp-toast ver " + tipo;
    $(".ti", t).textContent = tipo === "error" ? "!" : "i";
    $("#rpToastTxt").textContent = txt;
    clearTimeout(toastT);
    toastT = setTimeout(() => t.classList.remove("ver"), ms);
  }

  // ---------- Estado ----------
  let fase = "listo", tIni = 0, demora = 0, apertura = null, tApert = 0, tBoton = 0, rivalMs = 0, rivalHecho = false;
  let marcas = {}, errores = 0, timers = [], rafId = 0, valor = PRECIO_BASE, token = cfg.token;
  const eventos = [];
  const programar = (fn, ms) => { const id = setTimeout(fn, ms); timers.push(id); return id; };
  const limpiar = () => { timers.forEach(clearTimeout); timers = []; cancelAnimationFrame(rafId); };
  const ahoraSim = () => new Date(apertura.getTime() - demora + (performance.now() - tIni));

  function ponerBanda(f) {
    banda.dataset.fase = f;
    banda.querySelectorAll(".banda-fases li").forEach((li) => li.toggleAttribute("aria-current", li.dataset.f === f));
    rellenoB.style.setProperty("--avance", f === "pre" ? 0 : 1);
  }

  function tic() {
    const now = ahoraSim();
    $("#rpReloj").textContent = hora(now);
    if (fase === "pre") {
      $("#rpTemp").textContent = `Siguiente Ronda # 1 de 1 (${fecha(apertura)})`;
    } else if (fase === "abierta") {
      $("#rpTemp").textContent = `Ronda # 1 de 1 ( ${hms(180000 - (performance.now() - tApert))})`;
    }
    rafId = requestAnimationFrame(tic);
  }

  async function iniciar() {
    try { if (!document.fullscreenElement) await document.documentElement.requestFullscreen(); } catch (e) {}
    limpiar();
    juego.classList.remove("en-pausa");
    centro.hidden = true;
    escena.querySelector(".lienzo")?.remove();
    const viejo = $("#rp"); if (viejo) viejo.remove();
    escena.insertAdjacentHTML("afterbegin", pantalla());
    ajustar();
    errores = 0; marcas = {}; rivalHecho = false; valor = PRECIO_BASE; eventos.length = 0;
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
    const fila = $("#rpFila");
    fila.className = "abs rp-fila naranja";
    $("#rpPos").textContent = "0";
    aviso("info", `SUBASTA CON ID ${ident} INICIADA`);
    // Latencia de la plataforma antes de habilitar la postura (≈1,2 s en la grabación)
    const latencia = Math.round(rnd(600, 1400));
    programar(() => {
      tBoton = performance.now();
      marcas.latencia = latencia;
      $("#rpPostura").innerHTML = `<button class="abs rp-lanzar" id="rpLanzar" type="button">Lanzar Oferta</button>`;
      $("#rpLanzar").addEventListener("click", (e) => abrirColocacion(e.timeStamp || performance.now()));
      const d = DIFICULTAD[dificultad];
      rivalMs = Math.round(Math.min(9000, Math.max(1800, normal(d.media, d.desv))));
      programar(rival, rivalMs);
    }, latencia);
    programar(() => { if (fase === "abierta") fila.className = "abs rp-fila verdosa"; }, latencia + 700);
    programar(() => { if (fase === "abierta") fila.className = "abs rp-fila azul"; }, latencia + 1300);
    programar(() => terminar("agotada"), LIMITE_MS);
  }

  function rival() {
    if (fase !== "abierta" || marcas.aceptar) return;
    rivalHecho = true;
    eventos.push(["rival", rivalMs]);
    const fila = $("#rpFila");
    fila.className = "abs rp-fila naranja";
    aviso("otro", "Otro Operador - Ha realizado una oferta.");
    programar(() => { if (fase === "abierta") fila.className = "abs rp-fila azul"; }, 600);
  }

  const desdeBoton = (t) => Math.round(t - tBoton);

  function abrirColocacion(t) {
    if (fase !== "abierta" || $("#rpColoc")) return;
    if (marcas.lanzar === undefined) marcas.lanzar = desdeBoton(t);
    eventos.push(["lanzar_oferta", desdeBoton(t)]);
    $("#rpCapa").innerHTML = `<div class="abs rp-velo"></div>
      <div class="abs rp-modal rp-coloc" id="rpColoc" role="dialog" aria-label="Colocación">
        <h3>Colocación</h3><h4>Mercados Fisicos</h4>
        <span class="abs rp-pblbl">Precio Base</span>
        <input class="abs rp-pbin" id="rpValor" type="text" inputmode="numeric" autocomplete="off" value="${fmtUS(valor)}">
        <div class="abs rp-cap" id="rpCap" role="checkbox" aria-checked="false" tabindex="0">
          <span class="abs rp-cap-caja"></span><span class="abs rp-cap-txt">Soy humano</span>
          <span class="abs rp-cap-marca"><i></i>Verificación<br>simulada</span>
        </div>
        <button class="abs rp-btn rp-blanzar" id="rpBLanzar" type="button" disabled>Lanzar</button>
      </div>`;
    const inp = $("#rpValor");
    inp.addEventListener("input", () => { const n = parseInt(inp.value.replace(/\D/g, ""), 10) || 0; valor = n; inp.value = n ? fmtUS(n) : ""; });
    const cap = $("#rpCap");
    const verificar = (e) => {
      if (cap.classList.contains("girando") || cap.classList.contains("ok")) return;
      marcas.captcha_clic = desdeBoton(e.timeStamp || performance.now());
      cap.classList.add("girando");
      programar(() => {
        cap.classList.remove("girando"); cap.classList.add("ok"); cap.setAttribute("aria-checked", "true");
        marcas.captcha_ok = desdeBoton(performance.now());
        const b = $("#rpBLanzar"); b.disabled = false; b.focus({ preventScroll: true });
      }, Math.round(rnd(900, 2200)));
    };
    cap.addEventListener("click", verificar);
    cap.addEventListener("keydown", (e) => { if (e.key === " " || e.key === "Enter") { e.preventDefault(); verificar(e); } });
    $("#rpBLanzar").addEventListener("click", (e) => {
      if (valor < 1) { errores++; eventos.push(["valor_invalido", desdeBoton(performance.now())]); inp.focus(); return; }
      marcas.colocar = desdeBoton(e.timeStamp || performance.now());
      atencion();
    });
  }

  function atencion() {
    $("#rpCapa").innerHTML = `<div class="abs rp-velo"></div>
      <div class="abs rp-modal rp-aten" id="rpAten" role="alertdialog" aria-label="Atención">
        <button class="abs rp-aten-x" id="rpX" type="button" aria-label="Cerrar">x</button>
        <h3>Atención</h3><p>¿Está de acuerdo con el lanzamiento de la oferta?</p>
        <button class="abs rp-btn rp-bacep" id="rpAceptar" type="button">Aceptar</button>
        <button class="abs rp-btn rp-bcanc" id="rpCancelar" type="button">Cancelar</button>
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
    const gano = resultado === "lanzada" && !rivalHecho && marcas.aceptar < rivalMs;
    if (resultado === "lanzada") {
      if (gano) { $("#rpPB").classList.add("verde"); $("#rpFila").className = "abs rp-fila azul"; }
      else aviso("error", "Algo salió mal al lanzar la postura.");
    }
    ponerBanda("cierre");
    const total = resultado === "lanzada" ? marcas.aceptar : null;
    reloj.textContent = total !== null ? fmtS(total) : "—";
    const datos = {
      token, resultado, errores, escenario: cfg.escenario,
      tiempo_reaccion_ms: marcas.lanzar ?? null,
      tiempo_total_ms: total,
      valor_inicial: PRECIO_BASE,
      valor_lanzado: resultado === "lanzada" ? valor : null,
      detalle: { gano, rival_ms: rivalMs, dificultad, marcas, desde_apertura_ms: total !== null ? total + (marcas.latencia || 0) : null, eventos, pantalla: { w: innerWidth, h: innerHeight, dpr: devicePixelRatio, completa: !!document.fullscreenElement } },
    };
    let resp = null, fallo = "";
    try {
      const r = await fetch(cfg.guardar, { method: "POST", headers: { "Content-Type": "application/json", "X-CSRF": cfg.csrf }, body: JSON.stringify(datos) });
      resp = await r.json();
      if (!resp.ok) fallo = resp.error || "No se pudo guardar.";
      if (resp.token) token = resp.token;
    } catch (e) { fallo = "Sin conexión: esta partida no se guardó."; }
    setTimeout(() => resultadoCard(resultado, total, gano, resp, fallo), 1400);
  }

  const rombo = '<svg class="ico rombo" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.5 21.5 12 12 21.5 2.5 12Z" fill="currentColor"/></svg>';
  function resultadoCard(resultado, total, gano, resp, fallo) {
    juego.classList.add("en-pausa");
    ajustar();
    const rec = resultado === "lanzada" && resp && resp.es_record;
    let titulo, linea = "";
    if (resultado === "lanzada") {
      titulo = gano ? (rec ? "Calce logrado · nuevo récord" : "Calce logrado") : "Otro operador calzó primero";
      linea = gano
        ? `Le ganó al otro operador por ${fmtS(rivalMs - total)}.`
        : `El otro operador lanzó a los ${fmtS(rivalMs)}; usted, a los ${fmtS(total)}.`;
    } else {
      titulo = resultado === "cancelada" ? "Oferta cancelada" : "Se agotó el tiempo";
    }
    let comparar = "";
    if (resultado === "lanzada" && resp && resp.ok) {
      if (rec && resp.mejor_anterior) comparar = `<span class="record">${rombo}Mejoró ${fmtS(resp.mejor_anterior - total)} su marca anterior</span>`;
      else if (rec) comparar = `<span class="record">${rombo}Primera marca registrada</span>`;
      else if (resp.mejor) comparar = `<span class="tenue">Su récord: ${fmtS(resp.mejor)}</span>`;
    }
    const m = marcas;
    const tramo = (a, b) => (a !== undefined && b !== undefined ? fmtS(b - a) : "—");
    tarjeta.innerHTML = `
      <h2>${titulo}</h2>
      <div><span class="cifra">${total !== null ? fmtS(total) : "—"}</span><p class="tenue" style="margin-top:8px">${linea || "Desde que apareció Lanzar Oferta."}</p><div style="margin-top:8px">${comparar}</div></div>
      <div class="res-datos">
        <div><b>${m.lanzar !== undefined ? fmtS(m.lanzar) : "—"}</b><span>Reacción a Lanzar Oferta</span></div>
        <div><b>${tramo(m.lanzar, m.captcha_clic)}</b><span>Hasta marcar Soy humano</span></div>
        <div><b>${tramo(m.captcha_ok, m.colocar)}</b><span>De verificado a Lanzar</span></div>
        <div><b>${tramo(m.colocar, m.aceptar)}</b><span>Confirmación (Aceptar)</span></div>
        <div><b>${errores}</b><span>Errores</span></div>
        <div><b>${resp && resp.posicion ? resp.posicion + " de " + resp.total_ranking : "—"}</b><span>Puesto en la firma</span></div>
      </div>
      ${fallo ? `<div class="aviso aviso-error" style="margin:0"><span>${esc(fallo)}</span></div>` : ""}
      ${selectorDificultad()}
      <div class="fila">
        <button class="btn btn-rojo btn-grande" id="btnOtra" type="button">Jugar otra vez</button>
        <a class="btn" href="${cfg.resultados}">Ver resultados</a>
      </div>
      <p class="tenue" style="font-size:13px">Presione Enter para jugar otra vez.</p>`;
    centro.hidden = false;
    conectarDificultad();
    $("#btnOtra").addEventListener("click", iniciar);
    $("#btnOtra").focus({ preventScroll: true });
  }

  function selectorDificultad() {
    return `<div class="opciones-juego"><span class="etiqueta">Velocidad del otro operador</span><div class="seg" role="group" aria-label="Velocidad del otro operador">${Object.entries(DIFICULTAD)
      .map(([k, d]) => `<a data-dif="${k}"${k === dificultad ? ' aria-current="true"' : ""}>${d.t} · ${String(d.media / 1000).replace(".", ",")} s</a>`).join("")}</div></div>`;
  }
  function conectarDificultad() {
    tarjeta.querySelectorAll("[data-dif]").forEach((a) => a.addEventListener("click", () => {
      dificultad = a.dataset.dif;
      try { localStorage.setItem("rp-dificultad", dificultad); } catch (e) {}
      tarjeta.querySelectorAll("[data-dif]").forEach((b) => b.toggleAttribute("aria-current", b === a));
    }));
  }

  // Clics antes de que aparezca "Lanzar Oferta" = anticipado
  escena.addEventListener("pointerdown", (e) => {
    const antes = fase === "pre" || (fase === "abierta" && !$("#rpLanzar"));
    if (!antes || !centro.hidden) return;
    errores++;
    eventos.push(["anticipado", Math.round(performance.now() - tIni)]);
    const p = $("#rpAnticipo");
    if (p) { p.classList.add("ver"); clearTimeout(p._t); p._t = setTimeout(() => p.classList.remove("ver"), 1200); }
  });

  // La pantalla queda visible detrás de la tarjeta inicial
  escena.insertAdjacentHTML("afterbegin", pantalla());
  $("#rpReloj").textContent = hora(new Date());
  $("#rpTemp").textContent = "Siguiente Ronda # 1 de 1";
  ajustar();

  // Tarjeta inicial con la velocidad del rival
  const inicial = $("#selectorInicial");
  if (inicial) { inicial.innerHTML = selectorDificultad(); conectarDificultad(); }
  juego.classList.add("replica", "en-pausa");
  $("#btnIniciar").addEventListener("click", iniciar);
})();
