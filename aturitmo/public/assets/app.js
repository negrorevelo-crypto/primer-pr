(() => {
  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
  const csrf = $('meta[name="csrf-token"]')?.content;
  const toast = $('#toast');
  let toastTimeout;
  let recommendationReason = 'Aún no hay datos suficientes para explicar una sugerencia.';
  const ageSelect = $('#register-age-range');
  const adultConfirm = $('#adult-confirm');
  if (ageSelect && adultConfirm) {
    const syncAdultConfirmation = () => {
      adultConfirm.checked = ageSelect.value !== '';
    };
    ageSelect.addEventListener('change', syncAdultConfirmation);
    syncAdultConfirmation();
  }
  if (!$('#tab-today')) return;

  function notify(message) {
    if (!toast) return;
    toast.textContent = message;
    toast.classList.add('show');
    clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => toast.classList.remove('show'), 2800);
  }

  async function request(url, options = {}) {
    const headers = { Accept: 'application/json', ...(options.headers || {}) };
    if (options.body) {
      headers['Content-Type'] = 'application/json';
      headers['X-CSRF-Token'] = csrf || '';
    }
    const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', ...options, headers });
    if (response.status === 401) {
      window.location.href = 'index.php?entrar=1';
      throw new Error('Inicia sesión de nuevo.');
    }
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(payload.error || 'No pudimos completar esta acción.');
    return payload;
  }

  function setTab(name) {
    $$('.tab-panel').forEach(panel => {
      const active = panel.id === `tab-${name}`;
      panel.classList.toggle('active', active);
      panel.hidden = !active;
    });
    $$('[data-tab]').forEach(button => {
      const active = button.dataset.tab === name;
      button.classList.toggle('active', active);
      if (active) button.setAttribute('aria-current', 'page'); else button.removeAttribute('aria-current');
    });
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
  }
  $$('[data-tab]').forEach(button => button.addEventListener('click', () => setTab(button.dataset.tab)));

  const entryTabs = $$('[data-entry-tab]');
  function setEntryTab(name, focus = false) {
    entryTabs.forEach(tab => {
      const active = tab.dataset.entryTab === name;
      tab.setAttribute('aria-selected', String(active));
      tab.tabIndex = active ? 0 : -1;
      if (active && focus) tab.focus();
      const panel = document.getElementById(tab.getAttribute('aria-controls'));
      if (panel) panel.hidden = !active;
    });
  }
  entryTabs.forEach((tab, index) => {
    tab.addEventListener('click', () => setEntryTab(tab.dataset.entryTab));
    tab.addEventListener('keydown', event => {
      const step = event.key === 'ArrowRight' ? 1 : event.key === 'ArrowLeft' ? -1 : 0;
      if (event.key === 'Home' || event.key === 'End') {
        event.preventDefault();
        setEntryTab(entryTabs[event.key === 'Home' ? 0 : entryTabs.length - 1].dataset.entryTab, true);
      } else if (step) {
        event.preventDefault();
        setEntryTab(entryTabs[(index + step + entryTabs.length) % entryTabs.length].dataset.entryTab, true);
      }
    });
  });

  function svgIcon(name) {
    const ns = 'http://www.w3.org/2000/svg';
    const svg = document.createElementNS(ns, 'svg');
    svg.setAttribute('class', 'icon');
    svg.setAttribute('aria-hidden', 'true');
    const use = document.createElementNS(ns, 'use');
    use.setAttribute('href', `#i-${name}`);
    svg.append(use);
    return svg;
  }
  function shareButton(entry, label) {
    const share = document.createElement('button');
    share.type = 'button';
    share.className = 'share-entry';
    share.dataset.shareEntry = String(entry.id);
    share.setAttribute('aria-label', `Compartir ${label}`);
    share.append(svgIcon('share'), document.createTextNode('Compartir'));
    return share;
  }

  function formatHours(raw) {
    return new Intl.NumberFormat('es-CO', { maximumFractionDigits: 2 }).format(Number(raw));
  }
  function formatDistance(raw) {
    return new Intl.NumberFormat('es-CO', { maximumFractionDigits: 1 }).format(Number(raw));
  }
  function friendlyTime(value) {
    const date = new Date(value.replace(' ', 'T') + '-05:00');
    if (Number.isNaN(date.getTime())) return value;
    return new Intl.DateTimeFormat('es-CO', { hour: 'numeric', minute: '2-digit' }).format(date);
  }
  function friendlyDate(value) {
    const date = new Date(value.replace(' ', 'T') + '-05:00');
    if (Number.isNaN(date.getTime())) return value;
    return new Intl.DateTimeFormat('es-CO', { weekday: 'short', day: 'numeric', month: 'short' }).format(date);
  }
  function formatWeight(raw) {
    return new Intl.NumberFormat('es-CO', { maximumFractionDigits: 2 }).format(Number(raw));
  }
  function formatMeasuredDate(value) {
    const date = new Date(`${value}T12:00:00`);
    if (Number.isNaN(date.getTime())) return value;
    return new Intl.DateTimeFormat('es-CO', { day: 'numeric', month: 'short', year: 'numeric' }).format(date);
  }
  function localDateValue(date = new Date()) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
  }
  function drawWeightHistory(weight) {
    $('#current-weight').textContent = weight.latest ? formatWeight(weight.latest.weight_kg) : '—';
    const history = $('#weight-history');
    history.replaceChildren();
    if (!weight.history.length) {
      const empty = document.createElement('p');
      empty.className = 'empty-state';
      empty.textContent = 'Cuando registres un pesaje, aparecerá aquí.';
      history.append(empty);
      return;
    }
    weight.history.forEach(entry => {
      const row = document.createElement('div');
      row.className = 'weight-row';
      const date = document.createElement('time');
      date.dateTime = entry.measured_on;
      date.textContent = formatMeasuredDate(entry.measured_on);
      const value = document.createElement('strong');
      value.textContent = `${formatWeight(entry.weight_kg)} kg`;
      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'delete-entry';
      remove.dataset.deleteWeight = String(entry.id);
      remove.setAttribute('aria-label', `Eliminar pesaje del ${date.textContent}`);
      remove.textContent = 'Borrar';
      row.append(date, value, remove);
      history.append(row);
    });
  }
  function createTimelineEntry(entry) {
    const article = document.createElement('article');
    article.className = 'timeline-entry';
    const icon = document.createElement('span');
    icon.className = `timeline-icon ${entry.entry_type === 'activity' ? 'move-icon' : entry.entry_type === 'sleep' ? 'sleep-icon' : entry.entry_type === 'meal' ? 'food-icon' : 'note-icon'}`;
    icon.append(svgIcon(entry.entry_type === 'activity' ? 'activity' : entry.entry_type === 'sleep' ? 'sleep' : entry.entry_type === 'meal' ? 'food' : 'mood'));
    const copy = document.createElement('div');
    const title = document.createElement('strong');
    const detail = document.createElement('p');
    const time = document.createElement('time');
    if (entry.entry_type === 'activity') {
      title.textContent = entry.sport_name || 'Actividad';
      const sourceLabel = entry.source_type === 'screenshot' ? 'desde captura' : entry.source_type === 'file' ? 'desde Garmin CSV' : '';
      const signals = [entry.distance_km ? `${formatHours(entry.distance_km)} km` : '', entry.average_heart_rate ? `${entry.average_heart_rate} lpm promedio` : '', entry.effort_rating ? `esfuerzo ${entry.effort_rating}/10` : '', entry.body_feeling || '', entry.session_detail || '', sourceLabel].filter(Boolean);
      detail.textContent = `${entry.duration_minutes} min${signals.length ? ` · ${signals.join(' · ')}` : ''}`;
    } else if (entry.entry_type === 'sleep') {
      title.textContent = 'Descanso';
      detail.textContent = `${formatHours(entry.sleep_hours)} h${entry.sleep_score !== null ? ` · puntaje ${entry.sleep_score}` : ''}${entry.hrv_ms ? ` · HRV ${formatHours(entry.hrv_ms)} ms` : ''} · ${entry.content || 'Registrado'}${entry.source_type === 'screenshot' ? ' · desde captura' : ''}`;
    } else if (entry.entry_type === 'meal') {
      title.textContent = entry.meal_category || 'Comida';
      detail.textContent = entry.content || '';
    } else if (entry.entry_type === 'consumption') {
      const labels = { none: 'Sin consumo anotado', alcohol: 'Alcohol', other: 'Otra opción', both: 'Alcohol y otra opción', prefer_not: 'Prefirió no responder' };
      const timingLabels = { before_sleep: 'en las horas antes de dormir', earlier: 'más temprano' };
      title.textContent = 'Registro y descanso';
      const parts = [labels[entry.consumption_status] || 'Registro guardado'];
      if (entry.consumption_timing) parts.push(timingLabels[entry.consumption_timing] || '');
      if (entry.alcohol_drinks) parts.push(`aprox. ${entry.alcohol_drinks} bebidas`);
      detail.textContent = parts.filter(Boolean).join(' · ');
    } else {
      title.textContent = entry.mood ? `Hoy me siento: ${entry.mood.toLowerCase()}` : 'Nota personal';
      detail.textContent = entry.content || 'Registro de ánimo';
    }
    time.textContent = friendlyTime(entry.occurred_at);
    copy.append(title, detail);
    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'delete-entry';
    remove.dataset.deleteEntry = String(entry.id);
    remove.setAttribute('aria-label', `Eliminar registro de ${title.textContent}`);
    remove.textContent = 'Borrar';
    const actions = document.createElement('div');
    actions.className = 'entry-actions';
    if (entry.entry_type === 'activity') actions.append(shareButton(entry, `la sesión de ${title.textContent}`));
    actions.append(remove);
    article.append(icon, copy, time, actions);
    return article;
  }
  function createActivityRow(entry) {
    const row = document.createElement('div');
    row.className = 'activity-row';
    const sourceLabel = entry.source_type === 'screenshot' ? 'desde captura' : entry.source_type === 'file' ? 'desde Garmin CSV' : '';
    const sessionNotes = [entry.distance_km ? `${formatHours(entry.distance_km)} km` : '', entry.average_heart_rate ? `${entry.average_heart_rate} lpm` : '', entry.effort_rating ? `Esfuerzo ${entry.effort_rating}/10` : '', entry.body_feeling || '', entry.session_detail || '', sourceLabel].filter(Boolean).join(' · ') || '—';
    [entry.sport_name || 'Actividad', `${entry.duration_minutes} min`, sessionNotes, friendlyDate(entry.occurred_at)].forEach((value, index) => {
      const cell = document.createElement('span');
      cell.className = ['cell-sport', 'cell-time', 'cell-detail', 'cell-date'][index];
      cell.textContent = value;
      row.append(cell);
    });
    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'delete-entry';
    remove.dataset.deleteEntry = String(entry.id);
    remove.setAttribute('aria-label', `Eliminar sesión de ${entry.sport_name || 'actividad'}`);
    remove.textContent = 'Borrar';
    const actions = document.createElement('div');
    actions.className = 'entry-actions';
    actions.append(shareButton(entry, `la sesión de ${entry.sport_name || 'actividad'}`), remove);
    row.append(actions);
    return row;
  }

  function drawWeeklyTrend(weeks) {
    const chart = $('#weekly-trend');
    chart.replaceChildren();
    if (!weeks.some(week => Number(week.minutes) > 0)) {
      const empty = document.createElement('p');
      empty.className = 'empty-state';
      empty.textContent = 'Tus semanas aparecerán aquí cuando registres actividades.';
      chart.append(empty);
      return;
    }
    const scaleMax = Math.max(1, ...weeks.map(week => Math.max(Number(week.minutes) || 0, Number(week.goal) || 0)));
    weeks.forEach(week => {
      const item = document.createElement('div');
      item.className = 'week-bar';
      const meta = document.createElement('div');
      meta.className = 'week-bar-meta';
      const label = document.createElement('strong');
      const value = document.createElement('span');
      label.textContent = week.label;
      value.textContent = `${week.minutes} min${Number(week.distance_km) > 0 ? ` · ${formatDistance(week.distance_km)} km` : ''}`;
      meta.append(label, value);
      const track = document.createElement('div');
      track.className = 'week-bar-track';
      const fill = document.createElement('i');
      fill.style.width = `${Math.min(100, Math.max(0, Number(week.minutes) / scaleMax * 100))}%`;
      fill.setAttribute('aria-hidden', 'true');
      const marker = document.createElement('span');
      marker.className = 'week-goal-marker';
      marker.style.left = `${Math.min(100, Number(week.goal) / scaleMax * 100)}%`;
      marker.title = `Meta semanal: ${week.goal} minutos`;
      marker.setAttribute('aria-hidden', 'true');
      track.append(fill, marker);
      const note = document.createElement('small');
      const goalPercent = Number(week.goal) > 0 ? Math.round(Number(week.minutes) / Number(week.goal) * 100) : 0;
      note.textContent = `${week.active_days} ${week.active_days === 1 ? 'día' : 'días'} · ${goalPercent}% de tu meta`;
      item.append(meta, track, note);
      chart.append(item);
    });
  }

  function drawSleepTrend(days) {
    const chart = $('#sleep-trend');
    chart.replaceChildren();
    if (!days.some(day => day.hours !== null)) {
      const empty = document.createElement('p');
      empty.className = 'empty-state';
      empty.textContent = 'Tus registros de sueño aparecerán aquí.';
      chart.append(empty);
      return;
    }
    days.forEach(day => {
      const item = document.createElement('div');
      item.className = `sleep-bar${day.hours === null ? ' no-data' : ''}`;
      const bar = document.createElement('i');
      if (day.hours !== null) bar.style.height = `${Math.max(5, Math.min(100, Number(day.hours) / 12 * 100))}%`;
      const label = document.createElement('small');
      label.textContent = day.label;
      item.title = day.hours === null ? `${day.label}: sin registro` : `${day.label}: ${formatHours(day.hours)} h`;
      item.setAttribute('aria-label', item.title);
      item.append(bar, label);
      chart.append(item);
    });
  }

  function drawSportBreakdown(sports) {
    const list = $('#sport-breakdown');
    list.replaceChildren();
    if (!sports.length) {
      const empty = document.createElement('p');
      empty.className = 'empty-state';
      empty.textContent = 'Cuando registres tus sesiones, verás aquí cómo se reparten.';
      list.append(empty);
      return;
    }
    const max = Math.max(1, ...sports.map(sport => Number(sport.minutes)));
    sports.forEach(sport => {
      const row = document.createElement('div');
      row.className = 'sport-breakdown-row';
      const label = document.createElement('strong');
      const detail = document.createElement('span');
      label.textContent = sport.sport;
      detail.textContent = `${sport.minutes} min${Number(sport.distance_km) > 0 ? ` · ${formatDistance(sport.distance_km)} km` : ''} · ${sport.sessions} ${sport.sessions === 1 ? 'sesión' : 'sesiones'}`;
      const track = document.createElement('div');
      track.className = 'sport-track';
      const fill = document.createElement('i');
      fill.style.width = `${Math.min(100, Number(sport.minutes) / max * 100)}%`;
      track.append(fill);
      row.append(label, detail, track);
      list.append(row);
    });
  }

  function drawMealBreakdown(categories) {
    const list = $('#meal-breakdown');
    list.replaceChildren();
    const meals = Object.entries(categories || {}).sort((a, b) => b[1] - a[1]);
    if (!meals.length) {
      const empty = document.createElement('p');
      empty.className = 'empty-state';
      empty.textContent = 'Cuando registres comida o bebida, verás aquí el resumen de la semana.';
      list.append(empty);
      return;
    }
    const max = Math.max(1, ...meals.map(([, count]) => Number(count)));
    meals.forEach(([category, count]) => {
      const row = document.createElement('div');
      row.className = 'sport-breakdown-row';
      const label = document.createElement('strong');
      const detail = document.createElement('span');
      label.textContent = category;
      detail.textContent = `${count} ${Number(count) === 1 ? 'registro' : 'registros'}`;
      const track = document.createElement('div');
      track.className = 'sport-track meal-track';
      const fill = document.createElement('i');
      fill.style.width = `${Math.min(100, Number(count) / max * 100)}%`;
      track.append(fill);
      row.append(label, detail, track);
      list.append(row);
    });
  }

  async function loadDashboard() {
    const payload = await request('api.php');
    window.SumaRitmo = Object.assign(window.SumaRitmo || {}, { dashboard: payload, request, notify });
    document.dispatchEvent(new CustomEvent('sumaritmo:dashboard', { detail: payload }));
    $('#today-minutes').textContent = payload.today.minutes;
    $('#today-activity-note').textContent = payload.today.minutes
      ? `Buen trabajo: cada sesión cuenta.${Number(payload.today.distance_km) > 0 ? ` Hoy sumaste ${formatDistance(payload.today.distance_km)} km.` : ''}`
      : 'Aún sin actividad registrada.';
    $('#today-meals').textContent = payload.today.meals;
    const lastSleep = payload.last_sleep;
    $('#last-sleep').textContent = lastSleep ? formatHours(lastSleep.sleep_hours) : '—';
    $('#sleep-unit').textContent = lastSleep ? 'h' : '';
    $('#sleep-note').textContent = lastSleep ? (lastSleep.content || 'Registrado') : 'Anota cómo dormiste.';

    $('#recommendation-title').textContent = payload.recommendation.title;
    $('#recommendation-text').textContent = payload.recommendation.text;
    recommendationReason = payload.recommendation.reason;
    $('#recommendation-reason').textContent = recommendationReason;

    $('#week-minutes').textContent = payload.week.minutes;
    $('#weekly-summary').textContent = `Llevas ${payload.week.minutes} de ${payload.week.goal} minutos en tu meta semanal. Registra los deportes que practiques y suma a tu manera.`;
    $('#weekly-goal').value = payload.week.goal;
    if ($('#age-range')) $('#age-range').value = payload.profile.age_range || '';
    if ($('#goal-focus')) $('#goal-focus').value = payload.profile.goal_focus || 'bienestar';
    let preferredSports = [];
    try {
      preferredSports = Array.isArray(payload.profile.preferred_sports)
        ? payload.profile.preferred_sports
        : JSON.parse(payload.profile.preferred_sports || '[]');
    } catch (_) {}
    $$('[name="preferred_sports[]"]').forEach(input => { input.checked = preferredSports.includes(input.value); });
    if ($('#available-days')) $('#available-days').value = payload.profile.available_days_per_week || '';
    if ($('#session-minutes')) $('#session-minutes').value = payload.profile.preferred_session_minutes || '';
    const planName = $('#plan-name');
    const planCopy = $('#plan-copy');
    const planStatus = $('#plan-status');
    if (planName) planName.textContent = payload.plan?.code === 'nivel_1' ? 'Nivel 1 · A tu ritmo' : 'Tu plan SumaRitmo';
    if (planStatus) planStatus.textContent = payload.plan?.status === 'pilot' ? 'Piloto · sin cobro' : (payload.plan?.status || 'Activo');
    if (planCopy) planCopy.textContent = payload.plan?.status === 'pilot'
      ? 'Acceso de prueba habilitado. Durante el piloto no se realizan cobros. Incluye captura asistida, seguimiento y recomendaciones.'
      : 'Tu nivel incluye captura asistida, seguimiento y recomendaciones.';
    const baselineNote = $('#baseline-note');
    if (baselineNote) {
      const days = Number(payload.baseline?.days_with_data || 0);
      baselineNote.textContent = days >= 7
        ? 'Ya tenemos una semana de registros para comparar tus tendencias.'
        : `Estamos conociendo tu ritmo: ${days} de 7 días con algún registro. Tus primeras ideas son orientativas y mejorarán con más datos.`;
    }

    const trends = payload.trends;
    drawWeightHistory(payload.weight);
    $('#metric-activity-7').textContent = trends.recent_activity.minutes;
    $('#metric-active-days').textContent = trends.recent_activity.active_days;
    $('#active-days-note').textContent = payload.profile.available_days_per_week
      ? `Con movimiento en ${trends.recent_activity.active_days} días; dijiste que sueles tener ${payload.profile.available_days_per_week} días disponibles por semana.`
      : 'Con alguna actividad en los últimos 7 días.';
    $('#metric-sleep-average').textContent = trends.sleep_average === null ? '—' : formatHours(trends.sleep_average);
    $('#metric-sleep-unit').textContent = 'h promedio';
    $('#metric-sleep-count').textContent = trends.sleep_records
      ? `Basado en ${trends.sleep_records} ${trends.sleep_records === 1 ? 'registro' : 'registros'}${trends.previous_sleep_average === null ? ' en 7 días.' : `; antes: ${formatHours(trends.previous_sleep_average)} h.`}`
      : 'Aún no hay registros recientes.';
    $('#metric-meals').textContent = trends.meal_records;
    const mostCommonMood = Object.entries(trends.mood_counts || {}).sort((a, b) => b[1] - a[1])[0]?.[0];
    $('#metric-mood-note').textContent = trends.mood_records
      ? `${trends.mood_records} registros de ánimo${mostCommonMood ? ` · más anotado: ${mostCommonMood.toLowerCase()}` : ''}.`
      : 'Sin registros de ánimo en los últimos 7 días.';
    const change = trends.recent_activity.minutes - trends.previous_activity.minutes;
    const percent = trends.previous_activity.minutes > 0 ? Math.round(Math.abs(change) / trends.previous_activity.minutes * 100) : null;
    let comparison = change === 0
      ? `Igual que los 7 días anteriores (${trends.previous_activity.minutes} min).`
      : `${change > 0 ? '+' : '−'}${Math.abs(change)} min${percent === null ? '' : ` (${percent}%)`} frente a los 7 días anteriores (${trends.previous_activity.minutes} min).`;
    if (Number(trends.recent_activity.distance_km) > 0) comparison += ` · ${formatDistance(trends.recent_activity.distance_km)} km en estos 7 días.`;
    $('#activity-comparison').textContent = comparison;
    drawWeeklyTrend(trends.weeks);
    drawSleepTrend(trends.sleep_days);
    drawSportBreakdown(trends.sports);
    drawMealBreakdown(trends.meal_categories);

    const timeline = $('#today-timeline');
    timeline.replaceChildren();
    const visibleEntries = payload.today.entries.filter(entry => entry.entry_type !== 'consumption');
    if (!visibleEntries.length) {
      const empty = document.createElement('p'); empty.className = 'empty-state'; empty.textContent = 'Todavía no has añadido registros. Cuando lo hagas, aparecerán aquí.'; timeline.append(empty);
    } else {
      visibleEntries.forEach(entry => timeline.append(createTimelineEntry(entry)));
    }
    const history = $('#activity-history');
    history.replaceChildren();
    if (!payload.week.activities.length) {
      const empty = document.createElement('p'); empty.className = 'empty-state'; empty.textContent = 'Cuando registres tus sesiones, podrás verlas aquí.'; history.append(empty);
    } else {
      payload.week.activities.forEach(entry => history.append(createActivityRow(entry)));
    }
  }

  const captureImageUrls = new WeakMap();
  const tesseractScript = 'https://cdn.jsdelivr.net/npm/tesseract.js@5.1.1/dist/tesseract.min.js';
  let tesseractLoad = null;
  let tesseractWorker = null;
  function loadTesseract() {
    if (window.Tesseract) return Promise.resolve(window.Tesseract);
    if (!tesseractLoad) tesseractLoad = new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = tesseractScript;
      script.onload = () => window.Tesseract ? resolve(window.Tesseract) : reject(new Error('No se pudo iniciar la lectura de texto.'));
      script.onerror = () => reject(new Error('No se pudo cargar la lectura de captura. Puedes completar los campos a mano.'));
      document.head.append(script);
    });
    return tesseractLoad;
  }
  function normalizedText(value) {
    return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/\u00a0/g, ' ');
  }
  function afterLabel(text, labelPattern, maxChars = 70) {
    const regex = new RegExp(`(?:${labelPattern})[^\\d]{0,${maxChars}}(\\d{1,3}(?:[.,]\\d{1,2})?)`, 'i');
    const match = text.match(regex);
    return match ? { value: Number(match[1].replace(',', '.')), index: match.index + match[0].length } : null;
  }
  function activityClockMinutes(value) {
    const match = value.match(/(\d{1,2}):(\d{2})(?::(\d{2}))?/);
    if (!match) return null;
    if (match[3]) return Number(match[1]) * 60 + Number(match[2]) + Number(match[3]) / 60;
    return Number(match[1]) <= 6 ? Number(match[1]) * 60 + Number(match[2]) : Number(match[1]) + Number(match[2]) / 60;
  }
  function sleepClockHours(value) {
    const match = value.match(/(\d{1,2}):(\d{2})/);
    return match ? Number(match[1]) + Number(match[2]) / 60 : null;
  }
  function readScreenshotFields(form, rawText) {
    const text = normalizedText(rawText);
    const setIfEmpty = (name, value, decimals = 0) => {
      const field = form.elements.namedItem(name);
      if (!field || field.value || !Number.isFinite(value)) return false;
      field.value = decimals ? value.toFixed(decimals) : String(Math.round(value));
      return true;
    };
    let found = 0;
    if (form.dataset.entryForm === 'activity') {
      const durationLabel = text.match(/(?:tiempo total|tiempo transcurrido|duracion|duration|elapsed time)/i);
      if (durationLabel) {
        const fragment = text.slice(durationLabel.index, durationLabel.index + 90);
        const clock = activityClockMinutes(fragment);
        const minutesMatch = fragment.match(/(\d{1,4}(?:[.,]\d{1,2})?)\s*(?:min|minute|minutes|mins)\b/i);
        const numeric = minutesMatch ? Number(minutesMatch[1].replace(',', '.')) : null;
        const minutes = clock ?? numeric;
        if (minutes && setIfEmpty('duration_minutes', Math.round(minutes))) found++;
      }
      const distanceMatch = text.match(/(?:distancia|distance)[^\d]{0,50}(\d{1,4}(?:[.,]\d{1,2})?)\s*(km|kilometros?|mi|millas?|m)\b/i);
      if (distanceMatch) {
        let distance = Number(distanceMatch[1].replace(',', '.'));
        const unit = distanceMatch[2].toLowerCase();
        if (unit === 'mi' || unit.startsWith('milla')) distance *= 1.60934;
        else if (unit === 'm') distance /= 1000;
        if (setIfEmpty('distance_km', distance, 2)) found++;
      }
      const hr = afterLabel(text, 'frecuencia cardiaca media|fc media|average heart rate|avg\\.? hr|frecuencia cardiaca promedio');
      if (hr && setIfEmpty('average_heart_rate', hr.value)) found++;
      const calories = afterLabel(text, 'calorias|calories|cal\\.?');
      if (calories && setIfEmpty('calories_burned', calories.value)) found++;
    } else {
      const sleepLabel = text.match(/(?:duracion del sueno|tiempo de sueno|total sleep|sleep duration)/i);
      if (sleepLabel) {
        const fragment = text.slice(sleepLabel.index, sleepLabel.index + 90);
        let hours = null;
        const hm = fragment.match(/(\d{1,2})\s*(?:h|hr|hora)[^\d]{0,10}(\d{1,2})\s*(?:m|min|minuto)?/i);
        if (hm) hours = Number(hm[1]) + Number(hm[2]) / 60;
        else {
          const clock = sleepClockHours(fragment);
          if (clock !== null) hours = clock;
          else {
            const numeric = afterLabel(fragment, '.*?', 1);
            if (numeric) hours = numeric.value;
          }
        }
        if (hours && setIfEmpty('sleep_hours', hours, 2)) found++;
      }
      const score = afterLabel(text, 'sleep score|puntuacion del sueno|puntaje del sueno');
      if (score && setIfEmpty('sleep_score', score.value)) found++;
      const hrv = afterLabel(text, 'hrv|variabilidad de frecuencia cardiaca');
      if (hrv && setIfEmpty('hrv_ms', hrv.value, 1)) found++;
    }
    return found;
  }
  const garminCsvRows = new WeakMap();
  function parseCsvRows(text, delimiter) {
    const rows = [];
    let row = [];
    let field = '';
    let quoted = false;
    for (let index = 0; index < text.length; index++) {
      const char = text[index];
      if (quoted) {
        if (char === '"' && text[index + 1] === '"') { field += '"'; index++; }
        else if (char === '"') quoted = false;
        else field += char;
      } else if (char === '"' && field === '') quoted = true;
      else if (char === delimiter) { row.push(field); field = ''; }
      else if (char === '\n' || char === '\r') {
        if (char === '\r' && text[index + 1] === '\n') index++;
        row.push(field); field = '';
        if (row.some(value => value.trim() !== '')) rows.push(row);
        row = [];
      } else field += char;
    }
    row.push(field);
    if (row.some(value => value.trim() !== '')) rows.push(row);
    return rows;
  }
  function detectCsvDelimiter(text) {
    const firstLine = text.split(/\r?\n/, 1)[0] || '';
    let quoted = false;
    const counts = { ',': 0, ';': 0, '\t': 0 };
    for (let index = 0; index < firstLine.length; index++) {
      const char = firstLine[index];
      if (char === '"' && firstLine[index + 1] === '"') index++;
      else if (char === '"') quoted = !quoted;
      else if (!quoted && Object.hasOwn(counts, char)) counts[char]++;
    }
    return Object.entries(counts).sort((a, b) => b[1] - a[1])[0][0];
  }
  function csvNumber(value) {
    let text = String(value ?? '').trim().replace(/[^\d,.-]/g, '');
    if (!text || !/\d/.test(text)) return null;
    const comma = text.lastIndexOf(',');
    const dot = text.lastIndexOf('.');
    if (comma >= 0 && dot >= 0) {
      const decimal = comma > dot ? ',' : '.';
      text = text.replace(decimal === ',' ? /\./g : /,/g, '').replace(decimal, '.');
    } else if (comma >= 0) {
      const digitsAfter = text.length - comma - 1;
      text = digitsAfter > 0 && digitsAfter <= 2 ? text.replace(',', '.') : text.replace(/,/g, '');
    } else if ((text.match(/\./g) || []).length > 1) {
      const last = text.lastIndexOf('.');
      text = text.slice(0, last).replace(/\./g, '') + text.slice(last);
    }
    const number = Number(text);
    return Number.isFinite(number) ? number : null;
  }
  function garminDateKey(value, dayFirst) {
    const raw = String(value ?? '').trim();
    let match = raw.match(/^(\d{4})[-/](\d{1,2})[-/](\d{1,2})/);
    if (match) return `${match[1]}-${match[2].padStart(2, '0')}-${match[3].padStart(2, '0')}`;
    match = raw.match(/^(\d{1,2})[/-](\d{1,2})[/-](\d{2,4})/);
    if (match) {
      let first = Number(match[1]);
      let second = Number(match[2]);
      let year = Number(match[3]);
      if (year < 100) year += year < 70 ? 2000 : 1900;
      const month = dayFirst || first > 12 ? second : first;
      const day = dayFirst || first > 12 ? first : second;
      if (month < 1 || month > 12 || day < 1 || day > 31) return null;
      const date = new Date(year, month - 1, day);
      if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) return null;
      return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    }
    const parsed = new Date(raw);
    return Number.isNaN(parsed.getTime()) ? null : `${parsed.getFullYear()}-${String(parsed.getMonth() + 1).padStart(2, '0')}-${String(parsed.getDate()).padStart(2, '0')}`;
  }
  function garminSport(value) {
    const text = normalizedText(value);
    if (/soccer|football|futbol/.test(text)) return 'Fútbol';
    if (/padel/.test(text)) return 'Pádel';
    if (/tennis|tenis/.test(text)) return 'Tenis';
    if (/swim|natacion|pool/.test(text)) return 'Natación';
    if (/run|running|correr|trail/.test(text)) return 'Correr';
    if (/bike|biking|cycl|ciclismo|ride/.test(text)) return 'Ciclismo';
    if (/walk|walking|hike|caminata|hiking/.test(text)) return 'Caminata';
    if (/strength|cardio|fitness|weight|fuerza|gym/.test(text)) return 'Gimnasio / fuerza';
    if (/basketball|baloncesto/.test(text)) return 'Baloncesto';
    if (/dance|baile/.test(text)) return 'Baile';
    if (/yoga/.test(text)) return 'Yoga';
    if (/mobility|flexibility|movilidad/.test(text)) return 'Movilidad o equilibrio';
    return 'Otro';
  }
  function garminDuration(value) {
    const raw = String(value ?? '').trim();
    const clock = raw.match(/^(\d{1,3}):(\d{1,2})(?::(\d{1,2}))?$/);
    if (clock) return clock[3] !== undefined
      ? Number(clock[1]) * 60 + Number(clock[2]) + Number(clock[3]) / 60
      : Number(clock[1]) + Number(clock[2]) / 60;
    const withUnit = raw.match(/([\d.,]+)\s*(?:min(?:ute)?s?|minutes?)\b/i);
    return withUnit ? csvNumber(withUnit[1]) : null;
  }
  function parseGarminActivities(text) {
    const cleanText = text.replace(/^\uFEFF/, '');
    const rows = parseCsvRows(cleanText, detectCsvDelimiter(cleanText));
    if (rows.length < 2) throw new Error('El CSV no contiene actividades. Descárgalo desde Actividades → Todas las actividades.');
    const headers = rows[0].map(header => normalizedText(header).replace(/[^a-z0-9]+/g, ' ').trim());
    const findColumn = names => headers.findIndex(header => names.includes(header));
    const columns = {
      type: findColumn(['activity type', 'tipo de actividad', 'tipo actividad', 'activity']),
      date: findColumn(['date', 'fecha', 'start date', 'fecha de inicio']),
      title: findColumn(['title', 'activity name', 'nombre', 'nombre de actividad', 'activity title']),
      distance: findColumn(['distance', 'distancia']),
      calories: findColumn(['calories', 'calorias', 'calories burned']),
      duration: findColumn(['time', 'moving time', 'elapsed time', 'duration', 'tiempo', 'duracion']),
      averageHr: findColumn(['avg hr', 'average heart rate', 'fc media', 'frecuencia cardiaca media', 'fc promedio']),
      maximumHr: findColumn(['max hr', 'maximum heart rate', 'fc maxima', 'frecuencia cardiaca maxima']),
    };
    if (columns.date < 0 || columns.duration < 0 || columns.type < 0) {
      throw new Error('No reconocí las columnas del CSV. Usa la opción “Exportar CSV” de Garmin Connect en Todas las actividades.');
    }
    const dayFirst = headers[columns.date] === 'fecha';
    const today = localDateValue();
    const cutoff = new Date(); cutoff.setHours(0, 0, 0, 0); cutoff.setDate(cutoff.getDate() - 59);
    const earliest = `${cutoff.getFullYear()}-${String(cutoff.getMonth() + 1).padStart(2, '0')}-${String(cutoff.getDate()).padStart(2, '0')}`;
    const valid = [];
    let older = 0;
    let unreadable = 0;
    rows.slice(1).forEach(row => {
      const valueAt = index => index < 0 ? '' : (row[index] ?? '').trim();
      const date = garminDateKey(valueAt(columns.date), dayFirst);
      const duration = garminDuration(valueAt(columns.duration));
      if (!date || !duration || duration < 1 || duration > 1440) { unreadable++; return; }
      if (date < earliest) { older++; return; }
      if (date > today) { unreadable++; return; }
      const distanceValue = valueAt(columns.distance);
      let distance = csvNumber(distanceValue);
      const lowerDistance = normalizedText(distanceValue);
      if (distance !== null && (/mile|\bmi\b/.test(lowerDistance))) distance *= 1.60934;
      else if (distance !== null && (/\bm\b|meter/.test(lowerDistance) || distance > 2000)) distance /= 1000;
      const activity = {
        entry_date: date,
        sport_name: garminSport(valueAt(columns.type)),
        duration_minutes: String(Math.max(1, Math.round(duration))),
        distance_km: distance && distance > 0 ? distance.toFixed(2) : '',
        average_heart_rate: csvNumber(valueAt(columns.averageHr)) ? String(Math.round(csvNumber(valueAt(columns.averageHr)))) : '',
        maximum_heart_rate: csvNumber(valueAt(columns.maximumHr)) ? String(Math.round(csvNumber(valueAt(columns.maximumHr)))) : '',
        calories_burned: csvNumber(valueAt(columns.calories)) ? String(Math.round(csvNumber(valueAt(columns.calories)))) : '',
        session_detail: valueAt(columns.title).slice(0, 160),
      };
      valid.push(activity);
    });
    valid.sort((a, b) => b.entry_date.localeCompare(a.entry_date));
    return { entries: valid, older, unreadable, earliest };
  }
  $$('[data-capture-image]').forEach(input => input.addEventListener('change', async () => {
    const form = input.closest('[data-entry-form]');
    const preview = $('[data-capture-preview]', form);
    const button = $('[data-read-capture]', form);
    const status = $('[data-capture-status]', form);
    const csvPanel = $('[data-csv-import-panel]', form);
    const source = form.elements.namedItem('source_type');
    const oldUrl = captureImageUrls.get(input);
    if (oldUrl) URL.revokeObjectURL(oldUrl);
    captureImageUrls.delete(input);
    preview.replaceChildren();
    preview.hidden = true;
    button.hidden = true;
    csvPanel.hidden = true;
    garminCsvRows.delete(input);
    const file = input.files?.[0];
    if (!file) {
      source.value = 'manual';
      status.textContent = 'Las capturas y el CSV se leen en este dispositivo. Revisa los datos antes de guardarlos.';
      return;
    }
    if (form.dataset.entryForm === 'activity' && (/\.csv$/i.test(file.name) || file.type === 'text/csv' || file.type === 'application/vnd.ms-excel')) {
      source.value = 'manual';
      csvPanel.hidden = false;
      const importButton = $('[data-import-csv]', form);
      const csvStatus = $('[data-csv-import-status]', form);
      const csvSummary = $('[data-csv-import-summary]', form);
      const csvPreview = $('[data-csv-import-preview]', form);
      importButton.disabled = true;
      csvPreview.replaceChildren();
      if (file.size > 10 * 1024 * 1024) {
        csvStatus.textContent = 'El CSV supera 10 MB. Exporta las actividades de un periodo más corto desde Garmin Connect.';
        return;
      }
      csvStatus.textContent = 'Leyendo el CSV localmente…';
      try {
        const parsed = parseGarminActivities(await file.text());
        if (parsed.entries.length > 500) throw new Error('Hay más de 500 actividades en los últimos 60 días. Filtra Garmin Connect por fecha y vuelve a exportar ese periodo.');
        if (!parsed.entries.length) {
          csvSummary.textContent = 'No encontramos actividades importables dentro de los últimos 60 días.';
          csvStatus.textContent = `${parsed.older} registros anteriores se dejaron fuera; ${parsed.unreadable} filas no se pudieron leer.`;
          return;
        }
        garminCsvRows.set(input, parsed.entries);
        csvSummary.textContent = `Encontramos ${parsed.entries.length} actividades entre ${parsed.earliest} y hoy. Las anteriores a dos meses quedan fuera.`;
        parsed.entries.slice(0, 8).forEach(activity => {
          const item = document.createElement('li');
          const detail = activity.session_detail ? ` · ${activity.session_detail}` : '';
          item.textContent = `${activity.entry_date} · ${activity.sport_name} · ${activity.duration_minutes} min${activity.distance_km ? ` · ${activity.distance_km} km` : ''}${detail}`;
          csvPreview.append(item);
        });
        if (parsed.entries.length > 8) {
          const more = document.createElement('li');
          more.textContent = `…y ${parsed.entries.length - 8} actividades más`;
          csvPreview.append(more);
        }
        csvStatus.textContent = `${parsed.older} anteriores a dos meses y ${parsed.unreadable} filas incompletas se excluirán. El archivo original no se envía.`;
        importButton.disabled = false;
      } catch (error) {
        csvStatus.textContent = error.message || 'No pudimos leer este CSV de Garmin.';
      }
      return;
    }
    if (!file.type.startsWith('image/') || file.size > 12 * 1024 * 1024) {
      input.value = '';
      notify('Elige una imagen de hasta 12 MB o el CSV de actividades de Garmin.');
      return;
    }
    const url = URL.createObjectURL(file);
    captureImageUrls.set(input, url);
    const image = document.createElement('img');
    image.src = url;
    image.alt = 'Captura seleccionada para revisar';
    preview.append(image);
    preview.hidden = false;
    button.hidden = false;
    source.value = 'screenshot';
    status.textContent = 'Captura lista. Puedes pedir una lectura automática o escribir los datos mirando la imagen.';
  }));
  $$('[data-import-csv]').forEach(button => button.addEventListener('click', async () => {
    const form = button.closest('[data-entry-form]');
    const input = $('[data-capture-image]', form);
    const entries = garminCsvRows.get(input);
    if (!entries?.length) return;
    button.disabled = true;
    button.textContent = 'Sincronizando…';
    const status = $('[data-csv-import-status]', form);
    status.textContent = 'Guardando las actividades nuevas y comprobando cuáles ya estaban sincronizadas…';
    try {
      const result = await request('api.php', { method: 'POST', body: JSON.stringify({ action: 'garmin_csv_import', entries }) });
      status.textContent = `Listo: ${result.imported} nuevas; ${result.duplicates} ya estaban sincronizadas y no se duplicaron.`;
      notify(`${result.imported} actividades nuevas · ${result.duplicates} repetidas omitidas`);
      await loadDashboard();
      input.value = '';
      garminCsvRows.delete(input);
      setTimeout(() => { $('[data-csv-import-panel]', form).hidden = true; }, 2600);
    } catch (error) {
      status.textContent = error.message || 'No pudimos importar el archivo. Puedes intentarlo otra vez.';
      button.disabled = false;
      button.textContent = 'Volver a intentar';
    }
  }));
  $$('[data-read-capture]').forEach(button => button.addEventListener('click', async () => {
    const form = button.closest('[data-entry-form]');
    const input = $('[data-capture-image]', form);
    const file = input.files?.[0];
    const status = $('[data-capture-status]', form);
    if (!file) return;
    button.disabled = true;
    button.textContent = 'Leyendo…';
    status.textContent = 'Procesando la imagen en este dispositivo. Puede tomar unos segundos.';
    try {
      const library = await loadTesseract();
      if (!tesseractWorker) tesseractWorker = await library.createWorker('spa+eng');
      const result = await tesseractWorker.recognize(file);
      const count = readScreenshotFields(form, result.data.text || '');
      status.textContent = count
        ? `Encontré ${count} ${count === 1 ? 'dato' : 'datos'}. Revísalos y corrige lo que haga falta antes de guardar.`
        : 'No pude reconocer datos con suficiente claridad. Completa los campos mirando la captura.';
    } catch (error) {
      status.textContent = error.message || 'No se pudo leer la captura. Puedes completar los campos a mano.';
    } finally {
      button.disabled = false;
      button.textContent = 'Leer datos de la captura';
    }
  }));
  let pendingEntry = null;
  const reviewDialog = $('#entry-review-dialog');
  const reviewForm = $('#entry-review-form');
  function summarizeEntry(form, data) {
    const parts = [];
    if (form.dataset.entryForm === 'activity') {
      parts.push(data.sport_name, `${data.duration_minutes} min`);
      if (data.distance_km) parts.push(`${data.distance_km} km`);
      if (data.average_heart_rate) parts.push(`${data.average_heart_rate} lpm promedio`);
      if (data.calories_burned) parts.push(`${data.calories_burned} kcal`);
      parts.push(`fecha ${data.entry_date}`);
    } else if (form.dataset.entryForm === 'sleep') {
      parts.push(`${data.sleep_hours} h de sueño`, data.sleep_score ? `puntaje ${data.sleep_score}` : '', data.content, `fecha ${data.entry_date}`);
    } else if (form.dataset.entryForm === 'meal') {
      parts.push(data.meal_category, data.content);
    } else {
      parts.push(data.mood || data.content);
    }
    parts.push(data.source_type === 'screenshot' ? 'datos revisados desde captura' : 'anotado por ti');
    return parts.filter(Boolean).join(' · ');
  }
  $$('[data-entry-form]').forEach(form => {
    const date = form.elements.namedItem('entry_date');
    if (date && !date.value) date.value = localDateValue();
    form.addEventListener('submit', event => {
      event.preventDefault();
      const data = Object.fromEntries(new FormData(form).entries());
      if (form.dataset.entryForm === 'activity') {
        data.sport_name = data.sport;
        delete data.sport;
      }
      pendingEntry = { form, data };
      $('#entry-review-summary').textContent = summarizeEntry(form, data);
      const consentWrap = $('#capture-consent-wrap');
      const consent = $('#capture-consent');
      const needsCaptureConsent = data.source_type === 'screenshot';
      consentWrap.hidden = !needsCaptureConsent;
      consent.checked = false;
      reviewDialog.showModal();
    });
  });
  reviewForm?.addEventListener('submit', async event => {
    event.preventDefault();
    if (!pendingEntry) return;
    const { form, data } = pendingEntry;
    if (data.source_type === 'screenshot' && !$('#capture-consent').checked) {
      notify('Autoriza el procesamiento de la captura para guardar los datos revisados.');
      return;
    }
    data.capture_consent = data.source_type === 'screenshot' && $('#capture-consent').checked;
    const button = $('#entry-review-save');
    button.disabled = true;
    button.textContent = 'Guardando…';
    try {
      const payload = await request('api.php', { method: 'POST', body: JSON.stringify({ action: 'entry', entry_type: form.dataset.entryForm, ...data }) });
      reviewDialog.close();
      form.reset();
      const date = form.elements.namedItem('entry_date');
      if (date) date.value = localDateValue();
      const capture = $('[data-capture-image]', form);
      const preview = $('[data-capture-preview]', form);
      const status = $('[data-capture-status]', form);
      if (capture) {
        const url = captureImageUrls.get(capture);
        if (url) URL.revokeObjectURL(url);
        captureImageUrls.delete(capture);
        preview.replaceChildren();
        preview.hidden = true;
        $('[data-read-capture]', form).hidden = true;
        status.textContent = 'La imagen se analiza en este dispositivo y no se guarda. Revisa los campos antes de confirmar.';
      }
      pendingEntry = null;
      await loadDashboard();
      notify(payload.message);
    } catch (error) {
      notify(error.message);
    } finally {
      button.disabled = false;
      button.textContent = 'Guardar registro';
    }
  });

  const weightForm = $('#weight-form');
  const weightDate = $('input[name="measured_on"]', weightForm || document);
  if (weightDate) {
    weightDate.max = localDateValue();
    if (!weightDate.value) weightDate.value = localDateValue();
  }
  weightForm?.addEventListener('submit', async event => {
    event.preventDefault();
    const form = event.currentTarget;
    const data = Object.fromEntries(new FormData(form).entries());
    const button = $('button[type="submit"]', form);
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Guardando…';
    try {
      const payload = await request('api.php', { method: 'POST', body: JSON.stringify({ action: 'weight', ...data }) });
      form.reset();
      weightDate.value = localDateValue();
      await loadDashboard();
      notify(payload.message);
    } catch (error) {
      notify(error.message);
    } finally {
      button.disabled = false;
      button.textContent = originalText;
    }
  });

  document.addEventListener('click', async event => {
    const weightButton = event.target.closest('[data-delete-weight]');
    if (weightButton) {
      if (!window.confirm('¿Quieres borrar este pesaje?')) return;
      weightButton.disabled = true;
      try {
        const payload = await request('api.php', { method: 'POST', body: JSON.stringify({ action: 'delete_weight', weight_id: Number(weightButton.dataset.deleteWeight) }) });
        await loadDashboard();
        notify(payload.message);
      } catch (error) {
        notify(error.message);
        weightButton.disabled = false;
      }
      return;
    }
    const button = event.target.closest('[data-delete-entry]');
    if (!button) return;
    if (!window.confirm('¿Quieres borrar este registro?')) return;
    button.disabled = true;
    try {
      const payload = await request('api.php', { method: 'POST', body: JSON.stringify({ action: 'delete_entry', entry_id: Number(button.dataset.deleteEntry) }) });
      await loadDashboard();
      notify(payload.message);
    } catch (error) {
      notify(error.message);
      button.disabled = false;
    }
  });

  $('#goal-form')?.addEventListener('submit', async event => {
    event.preventDefault();
    const input = $('#weekly-goal');
    try {
      const payload = await request('api.php', { method: 'POST', body: JSON.stringify({ action: 'goal', weekly_goal: Number(input.value) }) });
      await loadDashboard();
      notify(payload.message);
    } catch (error) { notify(error.message); }
  });

  $('#profile-form')?.addEventListener('submit', async event => {
    event.preventDefault();
    const form = event.currentTarget;
    const fields = new FormData(form);
    const data = Object.fromEntries(fields.entries());
    data.preferred_sports = fields.getAll('preferred_sports[]');
    delete data['preferred_sports[]'];
    try {
      const payload = await request('api.php', { method: 'POST', body: JSON.stringify({ action: 'profile', ...data }) });
      await loadDashboard();
      notify(payload.message);
    } catch (error) { notify(error.message); }
  });

  $('#why-button')?.addEventListener('click', () => $('#reason-dialog').showModal());
  const garminHelpDialog = $('#garmin-export-dialog');
  $$('[data-open-garmin-help]').forEach(button => button.addEventListener('click', () => garminHelpDialog?.showModal()));
  const quotes = [
    'Un paso pequeño sigue siendo un paso.',
    'Descansar también es parte de avanzar.',
    'Tu proceso no tiene que parecerse al de nadie más.',
    'La constancia también se construye en los días tranquilos.',
    'Hoy puedes empezar desde donde estás.',
    'Disfrutar el deporte también es progreso.'
  ];
  const quoteDate = new Date().toLocaleDateString('en-CA');
  const quoteKey = `sumaritmo-quote-${quoteDate}`;
  let savedQuote = null;
  try { savedQuote = window.localStorage.getItem(quoteKey); } catch (_) {}
  if (savedQuote && quotes.includes(savedQuote)) $('#daily-quote').textContent = savedQuote;
  $('#new-quote')?.addEventListener('click', () => {
    const current = $('#daily-quote').textContent;
    const options = quotes.filter(quote => quote !== current);
    const nextQuote = options[Math.floor(Math.random() * options.length)];
    $('#daily-quote').textContent = nextQuote;
    try { window.localStorage.setItem(quoteKey, nextQuote); } catch (_) {}
  });

  const deleteDialog = $('#delete-dialog');
  const deleteCheck = $('#delete-confirm-check');
  const deleteButton = $('#confirm-delete');
  $('#delete-account-button')?.addEventListener('click', () => deleteDialog.showModal());
  deleteCheck?.addEventListener('change', () => { deleteButton.disabled = !deleteCheck.checked; });
  deleteButton?.addEventListener('click', async event => {
    event.preventDefault();
    if (!deleteCheck.checked) return;
    deleteButton.disabled = true;
    deleteButton.textContent = 'Borrando…';
    try {
      const payload = await request('api.php', { method: 'POST', body: JSON.stringify({ action: 'delete_account', confirm: true }) });
      window.location.href = payload.redirect;
    } catch (error) {
      notify(error.message);
      deleteButton.disabled = false;
      deleteButton.textContent = 'Borrar cuenta';
    }
  });

  loadDashboard().catch(error => {
    $('#recommendation-title').textContent = 'No pudimos cargar tus datos.';
    $('#recommendation-text').textContent = error.message;
    notify(error.message);
  });
})();
