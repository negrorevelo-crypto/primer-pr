(() => {
  "use strict";
  const cfg = JSON.parse(document.getElementById("cfg").textContent);
  const $ = (s) => document.querySelector(s);
  const W = cfg.lienzo.w, H = cfg.lienzo.h;
  const lienzo = $("#lienzo"), escena = $("#escena"), centro = $("#centro"), tarjeta = $("#tarjeta");
  const banda = $("#banda"), relleno = banda.querySelector(".banda-relleno"), reloj = $("#reloj"), falsa = $("#falsa");
  const LIMITE_MS = 30000;

  // Distribución estándar (la de la sesión grabada) cuando el corredor no tiene "mi puesto"
  const ESTANDAR = [
    { nombre: "Rueda de negocios", x: 0, y: 0, w: .6, h: .64 },
    { nombre: "Libro de ofertas", x: .6, y: 0, w: .4, h: .64 },
    { nombre: "Ingreso de oferta", x: 0, y: .64, w: .36, h: .36 },
    { nombre: "Confirmación", x: .36, y: .64, w: .3, h: .36 },
    { nombre: "Mensajes", x: .66, y: .64, w: .34, h: .36 },
  ];
  const ventanas = (cfg.ventanas && cfg.ventanas.length ? cfg.ventanas : ESTANDAR).map((v) => ({ ...v }));
  const iOferta = Math.max(0, ventanas.findIndex((v) => /ingreso|oferta|orden/i.test(v.nombre) && !/libro/i.test(v.nombre)));
  let iConfirma = ventanas.findIndex((v) => /confirm/i.test(v.nombre));
  if (iConfirma === iOferta) iConfirma = -1;

  // ---------- Escalado del lienzo ----------
  function ajustar() {
    const r = escena.getBoundingClientRect();
    const k = Math.min(r.width / W, r.height / H);
    lienzo.style.transform = `translate(${-W / 2 * k}px, ${-H / 2 * k}px) scale(${k})`;
  }
  window.addEventListener("resize", ajustar);
  document.addEventListener("fullscreenchange", ajustar);

  // ---------- Ventanas ----------
  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  function pintarVentanas() {
    lienzo.innerHTML = ventanas.map((v, i) => {
      const minW = Math.max(v.w * W, 260), minH = Math.max(v.h * H, 140);
      return `<section class="ventana-sim" data-i="${i}" style="left:${v.x * W}px;top:${v.y * H}px;width:${minW}px;height:${minH}px">
        <div class="vs-tit">${esc(v.nombre)}</div><div class="vs-cuerpo" data-cuerpo="${i}">${relleno_ventana(v, i)}</div></section>`;
    }).join("");
  }
  function relleno_ventana(v, i) {
    if (i === iOferta) return `<p>Esperando apertura de la rueda…</p>`;
    if (/libro/i.test(v.nombre)) {
      return `<div class="vs-filas">${[["Compra", "120.000", "$ 4.180"], ["Compra", "80.000", "$ 4.175"], ["Venta", "95.000", "$ 4.210"], ["Venta", "60.000", "$ 4.220"]]
        .map((f) => `<div><span>${f[0]}</span><b>${f[1]}</b><span>${f[2]}</span></div>`).join("")}</div>`;
    }
    if (/rueda/i.test(v.nombre)) return `<p id="estadoRueda">Pre-apertura. La sesión de oferta pública abrirá en cualquier momento.</p>`;
    return `<p>Sin novedades.</p>`;
  }
  const cuerpo = (i) => lienzo.querySelector(`[data-cuerpo="${i}"]`);
  const marco = (i) => lienzo.querySelector(`.ventana-sim[data-i="${i}"]`);

  // ---------- Estado de la partida ----------
  let fase = "listo", t0 = 0, tReac = null, errores = 0, valorInicial = 0, raf = 0, espera = 0, limite = 0, token = cfg.token, mejor = cfg.mejor;
  const eventos = [];

  function ponerFase(f) {
    banda.dataset.fase = f;
    banda.querySelectorAll(".banda-fases li").forEach((li) => li.toggleAttribute("aria-current", li.dataset.f === f));
    relleno.style.setProperty("--avance", f === "pre" ? 0 : 1);
  }
  function fmt(ms) { return (ms / 1000).toFixed(3).replace(".", ",") + " s"; }
  function tic() { reloj.textContent = fmt(performance.now() - t0); raf = requestAnimationFrame(tic); }
  function fmtCOP(n) { return "$ " + Math.round(n).toLocaleString("es-CO"); }
  function digitos(s) { return parseInt(String(s).replace(/\D/g, ""), 10) || 0; }

  async function iniciar() {
    try { if (!document.fullscreenElement) await document.documentElement.requestFullscreen(); } catch (e) { /* sigue sin pantalla completa */ }
    centro.hidden = true;
    errores = 0; tReac = null; eventos.length = 0;
    pintarVentanas(); ajustar();
    fase = "pre"; ponerFase("pre");
    reloj.textContent = "0,000 s";
    const demora = 1500 + Math.random() * 8500;
    espera = setTimeout(abrir, demora);
  }

  function abrir() {
    fase = "abierta";
    t0 = performance.now();
    ponerFase("abierta");
    const er = document.getElementById("estadoRueda");
    if (er) er.textContent = "Rueda abierta. Oferta pública en curso.";
    marco(iOferta).classList.add("activa");
    cuerpo(iOferta).innerHTML = `<div class="oferta"><button class="btn btn-rojo oferta-lanzar" id="btnLanzar" type="button">LANZAR OFERTA</button></div>`;
    const b = $("#btnLanzar");
    b.focus({ preventScroll: true });
    b.addEventListener("click", (e) => lanzar(e.timeStamp || performance.now()), { once: true });
    raf = requestAnimationFrame(tic);
    limite = setTimeout(() => terminar("agotada"), LIMITE_MS);
  }

  function lanzar(t) {
    tReac = Math.round(t - t0);
    eventos.push(["lanzar", tReac]);
    valorInicial = (100 + Math.floor(Math.random() * 901)) * 1e6;
    cuerpo(iOferta).innerHTML = `<form class="oferta" id="formValor" novalidate>
        <label for="valor">Valor de la oferta (COP)</label>
        <input id="valor" type="text" inputmode="numeric" autocomplete="off" value="${fmtCOP(valorInicial)}">
        <p class="falsa-txt" id="valorError" style="color:#ff8a8e;font-size:17px;min-height:22px"></p>
        <button class="btn btn-rojo" type="submit">Lanzar oferta</button></form>`;
    const inp = $("#valor");
    inp.focus({ preventScroll: true });
    inp.select();
    inp.addEventListener("input", () => {
      const n = digitos(inp.value);
      inp.value = n ? fmtCOP(n) : "";
    });
    $("#formValor").addEventListener("submit", (e) => {
      e.preventDefault();
      const n = digitos(inp.value);
      if (n < 1000000) {
        errores++; eventos.push(["valor_invalido", Math.round(performance.now() - t0)]);
        $("#valorError").textContent = "Escriba un valor de al menos $ 1.000.000.";
        inp.focus();
        return;
      }
      confirmar(n);
    });
  }

  function confirmar(valor) {
    eventos.push(["confirmar", Math.round(performance.now() - t0)]);
    const i = iConfirma >= 0 ? iConfirma : iOferta;
    if (i !== iOferta) {
      cuerpo(iOferta).innerHTML = `<p class="oferta-resumen" style="padding:26px">Oferta por <b>${fmtCOP(valor)}</b> pendiente de confirmación.</p>`;
      marco(iOferta).classList.remove("activa");
      marco(i).classList.add("activa");
    }
    cuerpo(i).innerHTML = `<div class="oferta">
        <p class="oferta-pregunta">¿Está de acuerdo con el lanzamiento de la oferta?</p>
        <p class="oferta-resumen">Valor: <b>${fmtCOP(valor)}</b></p>
        <div class="oferta-botones"><button class="btn btn-rojo" id="btnAceptar" type="button">Aceptar</button><button class="btn" id="btnCancelar" type="button">Cancelar</button></div></div>`;
    $("#btnAceptar").focus({ preventScroll: true });
    $("#btnAceptar").addEventListener("click", (e) => terminar("lanzada", valor, e.timeStamp || performance.now()), { once: true });
    $("#btnCancelar").addEventListener("click", () => terminar("cancelada", valor), { once: true });
  }

  async function terminar(resultado, valor = null, t = performance.now()) {
    if (fase !== "abierta") return;
    fase = "cierre";
    clearTimeout(limite); cancelAnimationFrame(raf);
    const total = Math.round(t - t0);
    reloj.textContent = fmt(total);
    ponerFase("cierre");
    lienzo.querySelectorAll(".ventana-sim.activa").forEach((n) => n.classList.remove("activa"));
    const cerrado = { lanzada: "Oferta lanzada.", cancelada: "Oferta cancelada.", agotada: "Se agotó el tiempo." }[resultado];
    [iOferta, iConfirma].forEach((i) => { if (i >= 0 && cuerpo(i)) cuerpo(i).innerHTML = `<p>${cerrado}</p>`; });
    const datos = {
      token, resultado, errores,
      tiempo_reaccion_ms: tReac,
      tiempo_total_ms: resultado === "lanzada" ? total : null,
      valor_inicial: valorInicial || null,
      valor_lanzado: resultado === "lanzada" ? valor : null,
      detalle: { eventos, pantalla: { w: innerWidth, h: innerHeight, dpr: devicePixelRatio, completa: !!document.fullscreenElement }, ventanas: cfg.modo || "estandar" },
    };
    let resp = null, fallo = "";
    try {
      const r = await fetch(cfg.guardar, { method: "POST", headers: { "Content-Type": "application/json", "X-CSRF": cfg.csrf }, body: JSON.stringify(datos) });
      resp = await r.json();
      if (!resp.ok) fallo = resp.error || "No se pudo guardar.";
      if (resp.token) token = resp.token;
    } catch (e) { fallo = "Sin conexión: esta partida no se guardó."; }
    mostrarResultado(resultado, total, resp, fallo);
  }

  function mostrarResultado(resultado, total, resp, fallo) {
    const rec = resultado === "lanzada" && resp && resp.es_record;
    const titulo = resultado === "lanzada" ? (rec ? "Nuevo récord personal" : "Oferta lanzada") : resultado === "cancelada" ? "Oferta cancelada" : "Se agotó el tiempo";
    const cifra = resultado === "lanzada" ? fmt(total) : "—";
    const rombo = '<svg class="ico rombo" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.5 21.5 12 12 21.5 2.5 12Z" fill="currentColor"/></svg>';
    let comparar = "";
    if (resultado === "lanzada" && resp && resp.ok) {
      if (rec && resp.mejor_anterior) comparar = `<span class="record">${rombo}Mejoró ${fmt(resp.mejor_anterior - total)} su marca anterior</span>`;
      else if (rec) comparar = `<span class="record">${rombo}Primera marca registrada</span>`;
      else if (resp.mejor) comparar = `<span class="tenue">Su récord: ${fmt(resp.mejor)} · a ${fmt(total - resp.mejor)}</span>`;
    }
    if (rec) mejor = total;
    tarjeta.innerHTML = `
      <h2>${titulo}</h2>
      <div><span class="cifra">${cifra}</span><div style="margin-top:10px">${comparar}</div></div>
      <div class="res-datos">
        <div><b>${tReac !== null ? fmt(tReac) : "—"}</b><span>Reacción al campanazo</span></div>
        <div><b>${errores}</b><span>Errores</span></div>
        <div><b>${resp && resp.posicion ? resp.posicion + " de " + resp.total_ranking : "—"}</b><span>Puesto en la firma</span></div>
      </div>
      ${fallo ? `<div class="aviso aviso-error" style="margin:0"><span>${esc(fallo)}</span></div>` : ""}
      <div class="fila">
        <button class="btn btn-rojo btn-grande" id="btnOtra" type="button">Jugar otra vez</button>
        <a class="btn" href="${cfg.resultados}">Ver resultados</a>
      </div>
      <p class="tenue" style="font-size:13px">Presione Enter para jugar otra vez.</p>`;
    centro.hidden = false;
    const b = $("#btnOtra");
    b.addEventListener("click", iniciar);
    b.focus({ preventScroll: true });
  }

  // Clic antes del campanazo = salida en falso
  escena.addEventListener("pointerdown", () => {
    if (fase !== "pre") return;
    errores++;
    eventos.push(["anticipado", 0]);
    falsa.classList.add("ver");
    clearTimeout(falsa._t);
    falsa._t = setTimeout(() => falsa.classList.remove("ver"), 1200);
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && fase === "abierta") terminar("cancelada");
  });

  $("#btnIniciar").addEventListener("click", iniciar);
  pintarVentanas();
  ajustar();
})();
