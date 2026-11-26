(() => {
"use strict";
const $ = s => document.querySelector(s);
const CFG = JSON.parse(document.getElementById("cfg").textContent);
const lienzo = CFG.lienzo;
const ESCALAS = [100,125,150,175,200,225,250,300,350,400];

const estado = {
  deteccion: null,
  monitores: [],
  monitoresOrigen: "este",
  ventanas: [],
  modo: "grabacion",
  pruebaAjuste: { lienzo, respuesta: null },
};

// ---------- Detección ----------
function navegador() {
  const ua = navigator.userAgent;
  let m;
  if ((m = /Edg\/(\d+)/.exec(ua))) return "Edge " + m[1];
  if ((m = /OPR\/(\d+)/.exec(ua))) return "Opera " + m[1];
  if ((m = /Firefox\/(\d+)/.exec(ua))) return "Firefox " + m[1];
  if ((m = /Chrome\/(\d+)/.exec(ua))) return "Chrome " + m[1];
  if ((m = /Version\/(\d+).*Safari/.exec(ua))) return "Safari " + m[1];
  return "Desconocido";
}
async function sistema() {
  try {
    if (navigator.userAgentData && navigator.userAgentData.getHighEntropyValues) {
      const v = await navigator.userAgentData.getHighEntropyValues(["platformVersion"]);
      if (navigator.userAgentData.platform === "Windows") {
        return parseInt(v.platformVersion, 10) >= 13 ? "Windows 11" : "Windows 10";
      }
      if (navigator.userAgentData.platform) return navigator.userAgentData.platform;
    }
  } catch (e) {}
  const ua = navigator.userAgent;
  if (/Windows/.test(ua)) return "Windows";
  if (/Mac OS X/.test(ua)) return "macOS";
  if (/Linux/.test(ua)) return "Linux";
  return "Desconocido";
}
function escalaCercana(v) {
  return ESCALAS.reduce((a, b) => Math.abs(b - v) < Math.abs(a - v) ? b : a);
}
async function detectar() {
  const dpr = window.devicePixelRatio || 1;
  const zoom = window.outerWidth && window.innerWidth ? Math.round(window.outerWidth / window.innerWidth * 100) : null;
  const zoomNormal = zoom === null || (zoom >= 95 && zoom <= 106);
  const escalaWin = zoomNormal ? escalaCercana(dpr * 100) : escalaCercana(dpr * 100 / (zoom / 100));
  estado.deteccion = {
    pantallaCss: { w: screen.width, h: screen.height },
    areaDisponibleCss: { w: screen.availWidth, h: screen.availHeight },
    devicePixelRatio: dpr,
    resolucionFisica: { w: Math.round(screen.width * dpr), h: Math.round(screen.height * dpr) },
    escalaWindowsEstimada: escalaWin,
    ventanaNavegador: { innerW: window.innerWidth, innerH: window.innerHeight, outerW: window.outerWidth, outerH: window.outerHeight },
    zoomEstimado: zoom,
    pantallaExtendida: typeof screen.isExtended === "boolean" ? screen.isExtended : null,
    profundidadColor: screen.colorDepth,
    tactil: navigator.maxTouchPoints > 0,
    navegador: navegador(),
    sistema: await sistema(),
    idioma: navigator.language,
    zonaHoraria: Intl.DateTimeFormat().resolvedOptions().timeZone,
    fecha: new Date().toISOString(),
  };
  $("#escala").value = String(escalaWin);
  if (estado.monitoresOrigen === "este") {
    estado.monitores = [{ id: 1, etiqueta: "Este monitor", x: 0, y: 0, w: screen.width, h: screen.height, dpr, principal: true }];
    if (!estado.ventanas.length) ventanasPorDefecto();
  }
  pintarDatos(zoomNormal);
  pintarEscritorio();
  actualizarJson();
}
function pintarDatos(zoomNormal) {
  const d = estado.deteccion;
  const items = [
    [d.resolucionFisica.w + " × " + d.resolucionFisica.h, "Resolución de la pantalla"],
    [d.escalaWindowsEstimada + " %", "Escala de Windows (estimada)"],
    [d.pantallaCss.w + " × " + d.pantallaCss.h, "Píxeles que ve el navegador"],
    [d.areaDisponibleCss.w + " × " + d.areaDisponibleCss.h, "Sin barra de tareas"],
    [d.ventanaNavegador.innerW + " × " + d.ventanaNavegador.innerH, "Área útil de esta ventana"],
    [(d.zoomEstimado ?? "—") + " %", "Zoom del navegador (estimado)"],
    [estado.monitores.length + (d.pantallaExtendida ? " (hay más)" : ""), "Monitores"],
    [d.navegador, d.sistema],
  ];
  $("#datos").innerHTML = items.map(([v, t]) => `<div class="dato"><b>${esc(v)}</b><span>${esc(t)}</span></div>`).join("");
  $("#avisoZoom").innerHTML = zoomNormal ? "" :
    `<div class="aviso aviso-info">El zoom del navegador parece estar en ${d.zoomEstimado} %. Presione <b>Ctrl + 0</b> y luego “Volver a detectar”.</div>`;
  if (d.pantallaExtendida && estado.monitoresOrigen === "este") {
    $("#avisoMonitores").innerHTML = `<div class="aviso aviso-info">Tiene más de un monitor. Presione “Detectar todos mis monitores” y acepta el permiso del navegador, o agréguelos a mano.</div>`;
  }
}
async function detectarMonitores() {
  if (!("getScreenDetails" in window)) {
    $("#avisoMonitores").innerHTML = `<div class="aviso aviso-info">Este navegador no permite leer todos los monitores. Use Chrome o Edge, o agréguelos a mano.</div>`;
    return;
  }
  try {
    const sd = await window.getScreenDetails();
    estado.monitores = sd.screens.map((s, i) => ({
      id: i + 1,
      etiqueta: s.label || "Monitor " + (i + 1),
      x: s.left, y: s.top, w: s.width, h: s.height,
      dpr: s.devicePixelRatio, principal: !!s.isPrimary,
    }));
    estado.monitoresOrigen = "api";
    $("#avisoMonitores").innerHTML = `<div class="aviso aviso-ok">Se detectaron ${estado.monitores.length} monitor(es).</div>`;
    ventanasPorDefecto();
    pintarDatos(true);
    pintarEscritorio();
    actualizarJson();
  } catch (e) {
    $("#avisoMonitores").innerHTML = `<div class="aviso aviso-info">No se dio el permiso. Puede agregar los monitores a mano.</div>`;
  }
}
function agregarMonitorManual() {
  const m = /(\d{3,5})\s*[x×*]\s*(\d{3,5})/i.exec($("#mRes").value);
  if (!m) { alert("Escriba la resolución como 1920 x 1080"); return; }
  const esc = Math.max(100, Math.min(400, +$("#mEsc").value || 100)) / 100;
  const w = Math.round(+m[1] / esc), h = Math.round(+m[2] / esc);
  const base = estado.monitores.find(x => x.principal) || estado.monitores[0];
  const minX = Math.min(...estado.monitores.map(x => x.x)), maxX = Math.max(...estado.monitores.map(x => x.x + x.w));
  const minY = Math.min(...estado.monitores.map(x => x.y)), maxY = Math.max(...estado.monitores.map(x => x.y + x.h));
  const pos = $("#mPos").value;
  let x = base.x, y = base.y;
  if (pos === "derecha") x = maxX;
  if (pos === "izquierda") x = minX - w;
  if (pos === "arriba") y = minY - h;
  if (pos === "abajo") y = maxY;
  estado.monitores.push({ id: estado.monitores.length + 1, etiqueta: "Monitor " + (estado.monitores.length + 1), x, y, w, h, dpr: esc, principal: false, manual: true });
  estado.monitoresOrigen = "manual";
  $("#formMonitor").hidden = true;
  pintarDatos(true);
  pintarEscritorio();
  actualizarJson();
}

// ---------- Prueba de ajuste ----------
function dibujarPrueba() {
  const { w: W, h: H } = lienzo;
  const svg = $("#svgPrueba");
  svg.setAttribute("viewBox", `0 0 ${W} ${H}`);
  let g = "";
  for (let x = 100; x < W; x += 100) g += `<line x1="${x}" y1="0" x2="${x}" y2="${H}" stroke="#333" stroke-width="1"/>`;
  for (let y = 100; y < H; y += 100) g += `<line x1="0" y1="${y}" x2="${W}" y2="${y}" stroke="#333" stroke-width="1"/>`;
  const L = 120, t = 10;
  const esquina = (x, y, sx, sy) => `<path d="M${x} ${y + sy * L} L${x} ${y} L${x + sx * L} ${y}" fill="none" stroke="#ff3b3b" stroke-width="${t * 2}"/>`;
  const factor = Math.min(window.innerWidth / W, window.innerHeight / H);
  estado.pruebaAjuste.factor = +factor.toFixed(4);
  estado.pruebaAjuste.viewport = { w: window.innerWidth, h: window.innerHeight };
  estado.pruebaAjuste.pantallaCompleta = !!document.fullscreenElement;
  svg.innerHTML = `
    <rect x="0" y="0" width="${W}" height="${H}" fill="#111"/>
    ${g}
    <rect x="1" y="1" width="${W - 2}" height="${H - 2}" fill="none" stroke="#fff" stroke-width="2"/>
    ${esquina(0, 0, 1, 1)}${esquina(W, 0, -1, 1)}${esquina(0, H, 1, -1)}${esquina(W, H, -1, -1)}
    <circle cx="${W / 2}" cy="${H / 2}" r="${H * 0.28}" fill="none" stroke="#4493f8" stroke-width="6"/>
    <text x="${W / 2}" y="${H / 2 - 30}" fill="#fff" font-size="48" font-family="Segoe UI, sans-serif" text-anchor="middle">Lienzo ${W} × ${H}</text>
    <text x="${W / 2}" y="${H / 2 + 30}" fill="#9aa4af" font-size="30" font-family="Segoe UI, sans-serif" text-anchor="middle">Su ventana: ${window.innerWidth} × ${window.innerHeight} · factor ${factor.toFixed(3).replace(".", ",")}</text>`;
}
async function iniciarPrueba() {
  $("#prueba").style.display = "block";
  try { await document.documentElement.requestFullscreen(); } catch (e) {}
  setTimeout(dibujarPrueba, 250);
}
function terminarPrueba(resp) {
  dibujarPrueba();
  estado.pruebaAjuste.respuesta = resp;
  $("#prueba").style.display = "none";
  if (document.fullscreenElement) document.exitFullscreen().catch(() => {});
  const p = estado.pruebaAjuste;
  $("#resPrueba").innerHTML = resp === "ok"
    ? `<p class="aviso aviso-ok" style="margin:12px 0 0">Listo: la aplicación encaja en su pantalla (factor ${String(p.factor).replace(".", ",")}).</p>`
    : `<p class="aviso aviso-info" style="margin:12px 0 0">Gracias. Cuéntenos en Comentarios qué se veía mal y adjunta una captura.</p>`;
  actualizarJson();
}
window.addEventListener("resize", () => { if ($("#prueba").style.display === "block") dibujarPrueba(); });
document.addEventListener("fullscreenchange", () => { if ($("#prueba").style.display === "block") setTimeout(dibujarPrueba, 150); });

// ---------- Editor de ventanas ----------
const NOMBRES = ["Rueda de negocios", "Libro de ofertas", "Ingreso de oferta", "Confirmación", "Mensajes"];
let sigId = 1, seleccion = null, k = 1;
function ventanasPorDefecto() {
  const m = estado.monitores.find(x => x.principal) || estado.monitores[0];
  if (!m) return;
  const W = m.w, H = estado.deteccion ? Math.min(m.h, estado.deteccion.areaDisponibleCss.h) : m.h;
  const r = (v) => Math.round(v);
  const marcos = [
    [0, 0, .6, .65], [.6, 0, .4, .65], [0, .65, .35, .35], [.35, .65, .3, .35], [.65, .65, .35, .35],
  ];
  sigId = 1;
  estado.ventanas = marcos.map(([x, y, w, h], i) => ({
    id: sigId++, nombre: NOMBRES[i], x: r(m.x + x * W), y: r(m.y + y * H), w: r(w * W), h: r(h * H),
  }));
}
function limites() {
  const ms = estado.monitores;
  return {
    x0: Math.min(...ms.map(m => m.x)), y0: Math.min(...ms.map(m => m.y)),
    x1: Math.max(...ms.map(m => m.x + m.w)), y1: Math.max(...ms.map(m => m.y + m.h)),
  };
}
function pintarEscritorio() {
  if ($("#editor").hidden || !estado.monitores.length) return;
  const cont = $("#escritorio");
  const L = limites();
  k = cont.clientWidth / (L.x1 - L.x0);
  cont.style.height = Math.round((L.y1 - L.y0) * k) + "px";
  let html = estado.monitores.map(m => `<div class="monitor" style="left:${(m.x - L.x0) * k}px;top:${(m.y - L.y0) * k}px;width:${m.w * k}px;height:${m.h * k}px"><span class="et">${esc(m.etiqueta)} · ${Math.round(m.w * m.dpr)}×${Math.round(m.h * m.dpr)}${m.principal ? " · principal" : ""}</span></div>`).join("");
  html += estado.ventanas.map(v => `<div class="ventana${v.id === seleccion ? " sel" : ""}" data-id="${v.id}" style="left:${(v.x - L.x0) * k}px;top:${(v.y - L.y0) * k}px;width:${v.w * k}px;height:${v.h * k}px"><div class="tit">${esc(v.nombre)}</div><div class="med">${v.w}×${v.h}</div><div class="asa" data-asa="1"></div></div>`).join("");
  cont.innerHTML = html;
  pintarTabla();
}
function pintarTabla() {
  $("#tablaVentanas").innerHTML = estado.ventanas.map(v => `<tr data-id="${v.id}">
    <td><input type="text" data-c="nombre" value="${esc(v.nombre)}" maxlength="60"></td>
    <td class="n"><input type="number" data-c="x" value="${v.x}"></td>
    <td class="n"><input type="number" data-c="y" value="${v.y}"></td>
    <td class="n"><input type="number" data-c="w" value="${v.w}" min="40"></td>
    <td class="n"><input type="number" data-c="h" value="${v.h}" min="30"></td>
    <td><button class="btn btn-sutil btn-chico btn-peligro" type="button" data-borrar="${v.id}" aria-label="Quitar ventana">Quitar</button></td></tr>`).join("");
}
function moverVentanaDom(v) {
  const L = limites();
  const el = $(`.ventana[data-id="${v.id}"]`);
  if (!el) return;
  el.style.left = (v.x - L.x0) * k + "px"; el.style.top = (v.y - L.y0) * k + "px";
  el.style.width = v.w * k + "px"; el.style.height = v.h * k + "px";
  el.querySelector(".med").textContent = v.w + "×" + v.h;
}
let arrastre = null;
$("#escritorio").addEventListener("pointerdown", e => {
  const el = e.target.closest(".ventana");
  if (!el) return;
  const v = estado.ventanas.find(x => x.id === +el.dataset.id);
  seleccion = v.id;
  document.querySelectorAll(".ventana").forEach(n => n.classList.toggle("sel", n === el));
  arrastre = { v, modo: e.target.dataset.asa ? "tam" : "mover", sx: e.clientX, sy: e.clientY, x: v.x, y: v.y, w: v.w, h: v.h };
  el.setPointerCapture(e.pointerId);
  e.preventDefault();
});
$("#escritorio").addEventListener("pointermove", e => {
  if (!arrastre) return;
  const a = arrastre, dx = (e.clientX - a.sx) / k, dy = (e.clientY - a.sy) / k;
  const snap = v => Math.round(v / 5) * 5;
  if (a.modo === "mover") { a.v.x = snap(a.x + dx); a.v.y = snap(a.y + dy); }
  else { a.v.w = Math.max(40, snap(a.w + dx)); a.v.h = Math.max(30, snap(a.h + dy)); }
  moverVentanaDom(a.v);
});
const soltar = () => { if (arrastre) { arrastre = null; pintarTabla(); actualizarJson(); } };
$("#escritorio").addEventListener("pointerup", soltar);
$("#escritorio").addEventListener("pointercancel", soltar);
$("#tablaVentanas").addEventListener("input", e => {
  const tr = e.target.closest("tr"); if (!tr) return;
  const v = estado.ventanas.find(x => x.id === +tr.dataset.id);
  const c = e.target.dataset.c;
  v[c] = c === "nombre" ? e.target.value : Math.round(+e.target.value || 0);
  if (c === "nombre") { const t = $(`.ventana[data-id="${v.id}"] .tit`); if (t) t.textContent = v.nombre; }
  else moverVentanaDom(v);
  actualizarJson();
});
$("#tablaVentanas").addEventListener("click", e => {
  const id = e.target.dataset.borrar; if (!id) return;
  estado.ventanas = estado.ventanas.filter(v => v.id !== +id);
  pintarEscritorio(); actualizarJson();
});
$("#btnAgregarVentana").onclick = () => {
  const m = estado.monitores.find(x => x.principal) || estado.monitores[0];
  estado.ventanas.push({ id: sigId++, nombre: "Ventana " + (estado.ventanas.length + 1), x: m.x + 40, y: m.y + 40, w: Math.round(m.w * .3), h: Math.round(m.h * .3) });
  pintarEscritorio(); actualizarJson();
};
$("#btnReiniciarVentanas").onclick = () => { ventanasPorDefecto(); pintarEscritorio(); actualizarJson(); };
document.querySelectorAll('input[name="modo"]').forEach(r => r.addEventListener("change", () => {
  estado.modo = document.querySelector('input[name="modo"]:checked').value;
  $("#editor").hidden = estado.modo !== "mi-puesto";
  pintarEscritorio(); actualizarJson();
}));
window.addEventListener("resize", () => { if (!arrastre) pintarEscritorio(); });

// ---------- Perfil y envío ----------
function monitorDe(v) {
  const cx = v.x + v.w / 2, cy = v.y + v.h / 2;
  return estado.monitores.find(m => cx >= m.x && cx < m.x + m.w && cy >= m.y && cy < m.y + m.h) || null;
}
function perfil() {
  return {
    version: 1,
    deteccion: estado.deteccion,
    escalaConfirmada: $("#escala").value,
    monitores: estado.monitores,
    monitoresOrigen: estado.monitoresOrigen,
    pruebaAjuste: { ...estado.pruebaAjuste, comentario: $("#comentario").value.trim() },
    modo: estado.modo,
    ventanas: estado.modo === "mi-puesto" ? estado.ventanas.map(v => {
      const m = monitorDe(v);
      return { ...v, monitor: m ? m.id : null,
        relativo: m ? { x: +((v.x - m.x) / m.w).toFixed(4), y: +((v.y - m.y) / m.h).toFixed(4), w: +(v.w / m.w).toFixed(4), h: +(v.h / m.h).toFixed(4) } : null };
    }) : [],
    autorizacionDatos: $("#autoriza").checked,
  };
}
function actualizarJson() { $("#vistaJson").textContent = JSON.stringify(perfil(), null, 2); }
function validar() {
  if (!$("#autoriza").checked) return "Marque la autorización de tratamiento de datos.";
  const f = $("#capturas").files;
  if (f.length > 3) return "Puede adjuntar máximo 3 capturas.";
  for (const x of f) if (x.size > 12 * 1024 * 1024) return `La captura “${x.name}” supera 12 MB.`;
  return null;
}
async function enviar() {
  const err = validar();
  const out = $("#resultado");
  if (err) { out.innerHTML = `<div class="aviso aviso-error">${esc(err)}</div>`; return; }
  if (estado.pruebaAjuste.respuesta === null && !confirm("No hizo la prueba de ajuste (paso 3). ¿Guardar de todos modos?")) return;
  const fd = new FormData();
  fd.append("perfil", JSON.stringify(perfil()));
  for (const x of $("#capturas").files) fd.append("capturas[]", x, x.name);
  const b = $("#btnEnviar"); b.disabled = true; b.textContent = "Enviando…";
  try {
    const r = await fetch(CFG.guardar, { method: "POST", headers: { "X-CSRF": CFG.csrf }, body: fd });
    const j = await r.json().catch(() => ({ ok: false, error: "Respuesta inválida del servidor." }));
    if (!j.ok) throw new Error(j.error || "Error " + r.status);
    out.innerHTML = `<div class="aviso aviso-ok"><span>Calibración guardada. El juego ya usa esta configuración. <a href="${CFG.jugar}">Entrar a la rueda</a></span></div>`;
  } catch (e) {
    out.innerHTML = `<div class="aviso aviso-info">No se pudo guardar: ${esc(e.message)}. Use “Descargar mi perfil” y envíe el archivo por correo.</div>`;
  } finally { b.disabled = false; b.textContent = "Guardar calibración"; }
}
function descargar() {
  const nombre = CFG.usuario.replace(/[^\w-]+/g, "_");
  const blob = new Blob([JSON.stringify(perfil(), null, 2)], { type: "application/json" });
  const a = document.createElement("a");
  a.href = URL.createObjectURL(blob); a.download = `perfil-pantalla-${nombre}.json`; a.click();
  setTimeout(() => URL.revokeObjectURL(a.href), 1000);
}
function esc(s) { return String(s).replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c])); }

// ---------- Eventos ----------
$("#lienzoTxt").textContent = lienzo.w + " × " + lienzo.h;
$("#btnDetectar").onclick = () => { estado.monitoresOrigen = "este"; $("#avisoMonitores").innerHTML = ""; detectar(); };
$("#btnMonitores").onclick = detectarMonitores;
$("#btnAgregarMonitor").onclick = () => { $("#formMonitor").hidden = false; $("#mRes").focus(); };
$("#btnCancelarMonitor").onclick = () => { $("#formMonitor").hidden = true; };
$("#btnGuardarMonitor").onclick = agregarMonitorManual;
$("#btnPrueba").onclick = iniciarPrueba;
$("#btnPruebaOk").onclick = () => terminarPrueba("ok");
$("#btnPruebaMal").onclick = () => terminarPrueba("mal");
$("#btnEnviar").onclick = enviar;
$("#btnDescargar").onclick = descargar;
["comentario","escala","autoriza"].forEach(id => $("#" + id).addEventListener("input", actualizarJson));
$("#autoriza").addEventListener("change", actualizarJson);
detectar();
})();
