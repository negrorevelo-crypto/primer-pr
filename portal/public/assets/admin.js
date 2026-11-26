(() => {
  "use strict";
  // Copiar al portapapeles
  document.querySelectorAll("[data-copiar]").forEach((b) => {
    b.addEventListener("click", async () => {
      const el = document.querySelector(b.dataset.copiar);
      const txt = el.value !== undefined ? el.value : el.textContent;
      const original = b.innerHTML;
      try { await navigator.clipboard.writeText(txt); } catch (e) { if (el.select) { el.select(); document.execCommand("copy"); } }
      b.textContent = "Copiado";
      setTimeout(() => { b.innerHTML = original; }, 1600);
    });
  });

  // Formulario de licencia
  const f = document.getElementById("formLicencia");
  if (!f) return;
  const origen = f.querySelector("#origen"), plan = f.querySelector("#plan"), ini = f.querySelector("#inicio"), fin = f.querySelector("#fin"), paq = f.querySelector("#paquete_id");
  const finDe = (inicio, meses) => {
    if (!inicio || !meses) return "";
    const [y, m, d] = inicio.split("-").map(Number);
    const t = new Date(y, m - 1 + meses, d);
    t.setDate(t.getDate() - 1);
    return `${t.getFullYear()}-${String(t.getMonth() + 1).padStart(2, "0")}-${String(t.getDate()).padStart(2, "0")}`;
  };
  const recalcular = () => {
    const meses = +plan.selectedOptions[0].dataset.meses;
    if (origen.value !== "paquete" && meses) fin.value = finDe(ini.value, meses);
  };
  const desdePaquete = () => {
    const o = paq && paq.selectedOptions[0];
    if (origen.value === "paquete" && o) { ini.value = o.dataset.inicio; fin.value = o.dataset.fin; }
  };
  const mostrar = () => {
    f.querySelectorAll("[data-solo]").forEach((n) => { n.hidden = n.dataset.solo !== origen.value; });
    f.querySelectorAll("[data-no]").forEach((n) => { n.hidden = n.dataset.no === origen.value; });
    desdePaquete();
    if (origen.value !== "paquete") recalcular();
  };
  origen.addEventListener("change", mostrar);
  plan.addEventListener("change", recalcular);
  ini.addEventListener("change", recalcular);
  if (paq) paq.addEventListener("change", desdePaquete);
  const valor = f.querySelector("#valor");
  if (valor) valor.addEventListener("input", () => {
    const n = parseInt(valor.value.replace(/\D/g, ""), 10);
    valor.value = n ? n.toLocaleString("es-CO") : "";
  });
  mostrar();
})();
