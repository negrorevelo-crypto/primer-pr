/* SumaRitmo · Comparte tu logro.
   Genera en el dispositivo una publicación vertical 1080×1920 (video animado y PNG)
   con los datos que la persona elige. Nada de esto se envía al servidor: el GPX se lee
   aquí y el video se graba desde el canvas con MediaRecorder. */
(() => {
  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
  const dialog = $('#share-dialog');
  if (!dialog) return;

  const W = 1080;
  const H = 1920;
  const M = 96;
  const DURATION = 6.5;
  const MAX_FIELDS = 6;
  const SITE = 'aturitmo.revelco.com.co';
  const canvas = $('#share-canvas');
  const ctx = canvas.getContext('2d');
  const sourceSelect = $('#share-source');
  const fieldsBox = $('#share-fields');
  const fieldsHint = $('#share-fields-hint');
  const gpxInput = $('#share-gpx');
  const gpxStatus = $('#share-gpx-status');
  const trimInput = $('#share-trim');
  const placeInput = $('#share-place');
  const captionInput = $('#share-caption');
  const status = $('#share-status');
  const videoButton = $('#share-make-video');
  const imageButton = $('#share-image');
  const result = $('#share-result');
  const resultText = $('#share-result-text');
  const sendVideo = $('#share-send-video');
  const downloadVideo = $('#share-download-video');
  const previewVideo = $('#share-video');
  const replayButton = $('#share-replay');

  const state = {
    source: 'day',
    selected: new Set(),
    style: 'brick',
    route: null,
    history: null,
    dashboard: null,
    photos: new Map(),
    pngBlob: null,
    videoBlob: null,
    videoUrl: '',
    playing: 0,
    recording: false,
  };

  /* ---------- Formatos ---------- */
  const num = (value, digits = 1) => new Intl.NumberFormat('es-CO', { maximumFractionDigits: digits, minimumFractionDigits: 0 }).format(value);
  const distanceText = km => num(km, km >= 100 ? 0 : km >= 10 ? 1 : 2);
  const clock = minutes => {
    const total = Math.round(minutes * 60);
    const h = Math.floor(total / 3600);
    const m = Math.floor((total % 3600) / 60);
    const s = total % 60;
    return h ? `${h}:${String(m).padStart(2, '0')}` : `${m}:${String(s).padStart(2, '0')}`;
  };
  const durationStat = minutes => minutes >= 60
    ? { value: clock(minutes), unit: 'h', fmt: n => clock(n) }
    : { value: String(Math.round(minutes)), unit: 'min', fmt: n => String(Math.round(n)) };
  const parseDate = value => new Date(String(value).replace(' ', 'T') + '-05:00');
  const dateLabel = date => {
    if (Number.isNaN(date.getTime())) return '';
    const parts = new Intl.DateTimeFormat('es-CO', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric', timeZone: 'America/Bogota' }).formatToParts(date);
    const get = type => (parts.find(part => part.type === type)?.value || '').replace('.', '');
    const weekday = get('weekday');
    return `${weekday.charAt(0).toUpperCase()}${weekday.slice(1)} ${get('day')} ${get('month')} ${get('year')}`;
  };
  const shortDate = date => new Intl.DateTimeFormat('es-CO', { weekday: 'short', day: 'numeric', month: 'short', timeZone: 'America/Bogota' }).format(date).replace(/\./g, '');

  const PACE_SPORTS = ['Correr', 'Caminata', 'Senderismo'];
  const PHOTO_BY_SPORT = {
    'Fútbol': 'futbol', 'Pádel': 'padel', 'Tenis': 'tenis', 'Natación': 'natacion', 'Correr': 'correr',
    'Ciclismo': 'ciclismo-atardecer', 'Gimnasio / fuerza': 'fuerza', 'Baloncesto': 'baloncesto', 'Caminata': 'senderismo',
    'Movilidad o equilibrio': 'descanso', 'Yoga': 'descanso', 'Baile': 'correr', 'Otro': 'ciclismo-montana',
  };

  /* ---------- Qué se cuenta en la publicación ---------- */
  function paceStat(sport, km, minutes) {
    if (!(km > 0 && minutes > 0)) return null;
    if (sport === 'Ciclismo') {
      const speed = km / (minutes / 60);
      return { label: 'Velocidad', value: num(speed, 1), unit: 'km/h', num: speed, fmt: n => num(n, 1) };
    }
    if (sport === 'Natación') {
      const per100 = minutes / (km * 10);
      return { label: 'Ritmo', value: clock(per100), unit: '/100 m' };
    }
    if (PACE_SPORTS.includes(sport)) {
      const perKm = minutes / km;
      if (perKm > 30) return null;
      return { label: 'Ritmo', value: clock(perKm), unit: '/km' };
    }
    return null;
  }

  function activityStory(entry) {
    const sport = entry.sport_name || 'Actividad';
    const routeKm = state.route?.distanceKm || 0;
    const km = Number(entry.distance_km) || routeKm;
    const minutes = Number(entry.duration_minutes) || state.route?.durationMin || 0;
    const stats = {};
    if (km > 0) stats.distance = { label: 'Distancia', value: distanceText(km), unit: 'km', num: km, fmt: distanceText };
    if (minutes > 0) stats.duration = { label: 'Tiempo', ...durationStat(minutes), num: minutes };
    const pace = paceStat(sport, km, minutes);
    if (pace) stats.pace = pace;
    if (entry.average_heart_rate) stats.hr = { label: 'Pulso medio', value: String(entry.average_heart_rate), unit: 'lpm', num: Number(entry.average_heart_rate), fmt: n => String(Math.round(n)) };
    if (entry.maximum_heart_rate) stats.hrmax = { label: 'Pulso máximo', value: String(entry.maximum_heart_rate), unit: 'lpm', num: Number(entry.maximum_heart_rate), fmt: n => String(Math.round(n)) };
    if (entry.calories_burned) stats.calories = { label: 'Calorías', value: num(Number(entry.calories_burned), 0), unit: 'kcal', num: Number(entry.calories_burned), fmt: n => num(Math.round(n), 0) };
    if (entry.effort_rating) stats.effort = { label: 'Esfuerzo', value: String(entry.effort_rating), unit: '/10', num: Number(entry.effort_rating), fmt: n => String(Math.round(n)) };
    if (entry.body_feeling) stats.feeling = { label: 'Así quedé', value: entry.body_feeling, unit: '', text: true };
    const date = parseDate(entry.occurred_at);
    return {
      kind: 'activity', title: sport, sport, date: dateLabel(date), detail: entry.session_detail || '', stats,
      defaults: ['distance', 'duration', 'pace', 'hr', 'calories'], fileTag: sport,
    };
  }

  function dayStory(payload) {
    const entries = (payload?.today?.entries || []).filter(entry => entry.entry_type === 'activity');
    const minutes = entries.reduce((sum, entry) => sum + (Number(entry.duration_minutes) || 0), 0);
    const km = entries.reduce((sum, entry) => sum + (Number(entry.distance_km) || 0), 0) || state.route?.distanceKm || 0;
    const calories = entries.reduce((sum, entry) => sum + (Number(entry.calories_burned) || 0), 0);
    const hrEntries = entries.filter(entry => entry.average_heart_rate && entry.duration_minutes);
    const hrMinutes = hrEntries.reduce((sum, entry) => sum + Number(entry.duration_minutes), 0);
    const hr = hrMinutes ? hrEntries.reduce((sum, entry) => sum + Number(entry.average_heart_rate) * Number(entry.duration_minutes), 0) / hrMinutes : 0;
    const sports = [...new Set(entries.map(entry => entry.sport_name).filter(Boolean))];
    const stats = {};
    if (minutes > 0) stats.duration = { label: 'Tiempo activo', ...durationStat(minutes), num: minutes };
    if (km > 0) stats.distance = { label: 'Distancia', value: distanceText(km), unit: 'km', num: km, fmt: distanceText };
    if (entries.length) stats.sessions = { label: entries.length === 1 ? 'Sesión' : 'Sesiones', value: String(entries.length), unit: '', num: entries.length, fmt: n => String(Math.round(n)) };
    if (hr) stats.hr = { label: 'Pulso medio', value: String(Math.round(hr)), unit: 'lpm', num: hr, fmt: n => String(Math.round(n)) };
    if (calories) stats.calories = { label: 'Calorías', value: num(calories, 0), unit: 'kcal', num: calories, fmt: n => num(Math.round(n), 0) };
    const sleep = payload?.last_sleep;
    if (sleep && String(sleep.occurred_at).slice(0, 10) === new Date().toLocaleDateString('en-CA', { timeZone: 'America/Bogota' })) {
      stats.sleep = { label: 'Dormí', value: num(Number(sleep.sleep_hours), 2), unit: 'h', num: Number(sleep.sleep_hours), fmt: n => num(n, 1) };
    }
    return {
      kind: 'day', title: 'Mi día', sport: sports[0] || '', date: dateLabel(new Date()), detail: sports.join(' · '), stats,
      defaults: ['distance', 'duration', 'sessions', 'hr', 'sleep'], fileTag: 'mi-dia',
    };
  }

  function weekStory(payload) {
    const weeks = payload?.trends?.weeks || [];
    const week = weeks[weeks.length - 1] || {};
    const minutes = Number(payload?.week?.minutes) || 0;
    const goal = Number(payload?.week?.goal) || 0;
    const km = Number(week.distance_km) || 0;
    const stats = {};
    if (minutes) stats.duration = { label: 'Tiempo activo', ...durationStat(minutes), num: minutes };
    if (goal && minutes) {
      const pct = Math.round(minutes / goal * 100);
      stats.goal = { label: 'De mi meta', value: String(pct), unit: '%', num: pct, fmt: n => String(Math.round(n)) };
    }
    if (km) stats.distance = { label: 'Distancia', value: distanceText(km), unit: 'km', num: km, fmt: distanceText };
    if (week.active_days) stats.days = { label: 'Días activos', value: String(week.active_days), unit: '/7', num: Number(week.active_days), fmt: n => String(Math.round(n)) };
    const sports = (payload?.trends?.sports || []).map(item => item.sport).slice(0, 3);
    return {
      kind: 'week', title: 'Mi semana', sport: sports[0] || '', date: `Semana del ${shortDate(mondayOf(new Date()))}`, detail: sports.join(' · '), stats,
      defaults: ['duration', 'goal', 'distance', 'days'], fileTag: 'mi-semana',
    };
  }
  function mondayOf(date) {
    const copy = new Date(date);
    const day = (copy.getDay() + 6) % 7;
    copy.setDate(copy.getDate() - day);
    return copy;
  }

  function currentStory() {
    if (state.source === 'day') return dayStory(state.dashboard);
    if (state.source === 'week') return weekStory(state.dashboard);
    const entry = (state.history || []).find(item => String(item.id) === state.source)
      || (state.dashboard?.week?.activities || []).find(item => String(item.id) === state.source);
    return entry ? activityStory(entry) : dayStory(state.dashboard);
  }

  /* ---------- Recorrido GPX ---------- */
  function haversine(a, b) {
    const rad = Math.PI / 180;
    const dLat = (b.lat - a.lat) * rad;
    const dLon = (b.lon - a.lon) * rad;
    const h = Math.sin(dLat / 2) ** 2 + Math.cos(a.lat * rad) * Math.cos(b.lat * rad) * Math.sin(dLon / 2) ** 2;
    return 12742 * Math.asin(Math.sqrt(h));
  }
  function parseGpx(text) {
    const doc = new DOMParser().parseFromString(text, 'application/xml');
    if (doc.getElementsByTagName('parsererror').length) throw new Error('Este archivo no parece un GPX válido.');
    let nodes = [...doc.getElementsByTagNameNS('*', 'trkpt')];
    if (nodes.length < 2) nodes = [...doc.getElementsByTagNameNS('*', 'rtept')];
    const points = nodes.map(node => ({
      lat: Number(node.getAttribute('lat')),
      lon: Number(node.getAttribute('lon')),
      time: node.getElementsByTagNameNS('*', 'time')[0]?.textContent || '',
    })).filter(point => Number.isFinite(point.lat) && Number.isFinite(point.lon) && Math.abs(point.lat) <= 90 && Math.abs(point.lon) <= 180);
    if (points.length < 2) throw new Error('El GPX no tiene puntos de recorrido.');
    let total = 0;
    points[0].km = 0;
    for (let i = 1; i < points.length; i++) {
      total += haversine(points[i - 1], points[i]);
      points[i].km = total;
    }
    const first = Date.parse(points[0].time);
    const last = Date.parse(points[points.length - 1].time);
    const durationMin = Number.isFinite(first) && Number.isFinite(last) && last > first ? (last - first) / 60000 : 0;
    return { points, distanceKm: Math.round(total * 100) / 100, durationMin };
  }
  function routePoints() {
    if (!state.route) return null;
    let pts = state.route.points;
    const total = state.route.distanceKm;
    if (trimInput.checked && total > 1.2) pts = pts.filter(point => point.km >= 0.3 && point.km <= total - 0.3);
    if (pts.length < 2) return null;
    const step = Math.max(1, Math.ceil(pts.length / 900));
    const reduced = pts.filter((_, index) => index % step === 0);
    if (reduced[reduced.length - 1] !== pts[pts.length - 1]) reduced.push(pts[pts.length - 1]);
    return reduced;
  }
  function projectRoute(pts, box) {
    const meanLat = pts.reduce((sum, point) => sum + point.lat, 0) / pts.length;
    const k = Math.cos(meanLat * Math.PI / 180);
    const xy = pts.map(point => [point.lon * k, -point.lat]);
    const xs = xy.map(p => p[0]);
    const ys = xy.map(p => p[1]);
    const minX = Math.min(...xs), maxX = Math.max(...xs), minY = Math.min(...ys), maxY = Math.max(...ys);
    const spanX = maxX - minX || 1e-6;
    const spanY = maxY - minY || 1e-6;
    const scale = Math.min(box.w / spanX, box.h / spanY);
    const offX = box.x + (box.w - spanX * scale) / 2;
    const offY = box.y + (box.h - spanY * scale) / 2;
    return xy.map(([x, y]) => [offX + (x - minX) * scale, offY + (y - minY) * scale]);
  }

  /* ---------- Dibujo ---------- */
  const PALETTES = {
    brick: { bg: '#EE7828', fg: '#2C2C2C', sub: 'rgba(44,44,44,.74)', line: 'rgba(44,44,44,.2)', route: '#2C2C2C', halo: 'rgba(248,246,242,.35)', accent: '#F8F6F2', sun: '#F8F6F2', markBg: '#2C2C2C', markFg: '#EE7828', arcs: 'rgba(44,44,44,.16)' },
    graphite: { bg: '#2C2C2C', fg: '#F0ECE6', sub: '#C5BAAA', line: 'rgba(240,236,230,.18)', route: '#EE7828', halo: 'rgba(238,120,40,.18)', accent: '#EE7828', sun: null, markBg: '#EE7828', markFg: '#2C2C2C', arcs: 'rgba(197,186,170,.16)' },
    photo: { bg: '#2C2C2C', fg: '#FFFFFF', sub: 'rgba(255,255,255,.82)', line: 'rgba(255,255,255,.26)', route: '#EE7828', halo: 'rgba(0,0,0,.4)', accent: '#EE7828', sun: null, markBg: '#EE7828', markFg: '#2C2C2C', arcs: null },
  };
  const wave = new Path2D('M3 13.5c2.2-5.4 4.6-5.4 6.4-1.5 1.8 3.9 4.2 3.9 6.2-1.5 1.2-3.2 3-4 5.4-2.6');
  const pin = new Path2D('M12 21s-6.5-6-6.5-11a6.5 6.5 0 0 1 13 0c0 5-6.5 11-6.5 11z');

  const clamp01 = value => Math.max(0, Math.min(1, value));
  const expoOut = x => x >= 1 ? 1 : 1 - Math.pow(2, -10 * x);
  const easeInOut = x => x < .5 ? 4 * x * x * x : 1 - Math.pow(-2 * x + 2, 3) / 2;
  const phase = (t, start, length) => clamp01((t - start) / length);

  function font(weight, size) { return `${weight} ${size}px Archivo, "Segoe UI", system-ui, sans-serif`; }
  function setTracking(value) { if ('letterSpacing' in ctx) ctx.letterSpacing = `${value}px`; }
  function fitFont(text, weight, size, maxWidth, min = 40) {
    let current = size;
    ctx.font = font(weight, current);
    while (current > min && ctx.measureText(text).width > maxWidth) {
      current -= 4;
      ctx.font = font(weight, current);
    }
    return current;
  }
  function ellipsize(text, maxWidth) {
    if (ctx.measureText(text).width <= maxWidth) return text;
    let cut = text;
    while (cut.length > 1 && ctx.measureText(`${cut}…`).width > maxWidth) cut = cut.slice(0, -1);
    return `${cut.trimEnd()}…`;
  }
  function wrapLines(text, maxWidth, maxLines) {
    const words = text.split(/\s+/);
    const lines = [];
    let line = '';
    words.forEach(word => {
      const test = line ? `${line} ${word}` : word;
      if (ctx.measureText(test).width > maxWidth && line) { lines.push(line); line = word; } else line = test;
    });
    if (line) lines.push(line);
    if (lines.length > maxLines) {
      const kept = lines.slice(0, maxLines);
      kept[maxLines - 1] = ellipsize(`${kept[maxLines - 1]} ${lines.slice(maxLines).join(' ')}`, maxWidth);
      return kept;
    }
    return lines;
  }
  function roundRect(x, y, w, h, r) {
    ctx.beginPath();
    if (ctx.roundRect) ctx.roundRect(x, y, w, h, r);
    else ctx.rect(x, y, w, h);
  }
  function withAlpha(alpha, dy, draw) {
    if (alpha <= 0) return;
    ctx.save();
    ctx.globalAlpha *= alpha;
    ctx.translate(0, dy);
    draw();
    ctx.restore();
  }

  function drawBackground(palette, story) {
    ctx.fillStyle = palette.bg;
    ctx.fillRect(0, 0, W, H);
    if (state.style === 'photo') {
      const image = state.photos.get(photoName(story));
      if (image?.complete && image.naturalWidth) {
        const scale = Math.max(W / image.naturalWidth, H / image.naturalHeight);
        const iw = image.naturalWidth * scale;
        const ih = image.naturalHeight * scale;
        ctx.drawImage(image, (W - iw) / 2, (H - ih) / 2, iw, ih);
      }
      const shade = ctx.createLinearGradient(0, 0, 0, H);
      shade.addColorStop(0, 'rgba(30,28,26,.55)');
      shade.addColorStop(.35, 'rgba(30,28,26,.38)');
      shade.addColorStop(.62, 'rgba(30,28,26,.72)');
      shade.addColorStop(1, 'rgba(30,28,26,.92)');
      ctx.fillStyle = shade;
      ctx.fillRect(0, 0, W, H);
      return;
    }
    if (palette.sun) {
      ctx.save();
      ctx.globalAlpha = state.style === 'brick' ? .9 : 1;
      ctx.fillStyle = palette.sun;
      ctx.beginPath();
      ctx.arc(W - 40, 70, 250, 0, Math.PI * 2);
      ctx.fill();
      ctx.restore();
    }
    if (palette.arcs) {
      ctx.save();
      ctx.strokeStyle = palette.arcs;
      ctx.lineWidth = 3;
      ctx.translate(W * .18, H + 40);
      ctx.rotate(-24 * Math.PI / 180);
      [[620, 300], [760, 390], [900, 480]].forEach(([rx, ry]) => {
        ctx.beginPath();
        ctx.ellipse(0, 0, rx, ry, 0, 0, Math.PI * 2);
        ctx.stroke();
      });
      ctx.restore();
    }
  }

  function drawBrand(palette, story) {
    const size = 84;
    ctx.fillStyle = palette.markBg;
    roundRect(M, 132, size, size, 22);
    ctx.fill();
    ctx.save();
    ctx.translate(M + 12, 132 + 12);
    ctx.scale(60 / 24, 60 / 24);
    ctx.strokeStyle = palette.markFg;
    ctx.lineWidth = 2.3;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.stroke(wave);
    ctx.restore();
    ctx.fillStyle = palette.fg;
    ctx.font = font(800, 50);
    setTracking(-2);
    ctx.textBaseline = 'alphabetic';
    ctx.textAlign = 'left';
    const name = 'SumaRitmo';
    ctx.fillText(name, M + size + 22, 192);
    const nameWidth = ctx.measureText(name).width;
    ctx.fillStyle = palette.accent;
    ctx.fillText('.', M + size + 22 + nameWidth, 192);
    setTracking(0);
    ctx.fillStyle = state.style === 'brick' ? palette.fg : palette.sub;
    ctx.font = font(600, 32);
    ctx.textAlign = 'right';
    ctx.fillText(story.date, W - M, 190);
    ctx.textAlign = 'left';
  }

  function drawTitle(palette, story, top) {
    ctx.fillStyle = palette.fg;
    const size = fitFont(story.title, 800, 132, W - M * 2, 72);
    setTracking(-size * .04);
    ctx.fillText(story.title, M - 4, top);
    setTracking(0);
    const place = placeInput.value.trim();
    const line = [place, story.detail].filter(Boolean).join(' · ');
    if (!line) return top;
    ctx.font = font(500, 40);
    ctx.fillStyle = palette.sub;
    let x = M;
    if (place) {
      ctx.save();
      ctx.translate(M - 4, top + 34);
      ctx.scale(44 / 24, 44 / 24);
      ctx.strokeStyle = state.style === 'brick' ? palette.fg : palette.accent;
      ctx.lineWidth = 2;
      ctx.stroke(pin);
      ctx.restore();
      x = M + 50;
    }
    ctx.fillText(ellipsize(line, W - M - x), x, top + 72);
    return top + 72;
  }

  function drawRoute(palette, pts, box, progress) {
    const projected = projectRoute(pts, box);
    const count = Math.max(2, Math.round(projected.length * progress));
    const path = projected.slice(0, count);
    ctx.save();
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.beginPath();
    path.forEach(([x, y], index) => index ? ctx.lineTo(x, y) : ctx.moveTo(x, y));
    ctx.strokeStyle = palette.halo;
    ctx.lineWidth = 30;
    ctx.stroke();
    ctx.strokeStyle = palette.route;
    ctx.lineWidth = 12;
    ctx.stroke();
    const [sx, sy] = projected[0];
    ctx.fillStyle = state.style === 'brick' ? palette.accent : palette.bg;
    ctx.strokeStyle = palette.route;
    ctx.lineWidth = 7;
    ctx.beginPath();
    ctx.arc(sx, sy, 16, 0, Math.PI * 2);
    ctx.fill();
    ctx.stroke();
    const [hx, hy] = path[path.length - 1];
    ctx.fillStyle = palette.route;
    ctx.beginPath();
    ctx.arc(hx, hy, progress < 1 ? 20 : 22, 0, Math.PI * 2);
    ctx.fill();
    ctx.fillStyle = state.style === 'brick' ? palette.accent : palette.fg;
    ctx.beginPath();
    ctx.arc(hx, hy, 8, 0, Math.PI * 2);
    ctx.fill();
    ctx.restore();
  }

  function statValue(stat, progress) {
    if (stat.text || progress >= 1 || typeof stat.num !== 'number' || !stat.fmt) return stat.value;
    return stat.fmt(stat.num * expoOut(progress));
  }

  function drawStat(palette, stat, x, y, valueSize, labelSize, maxWidth, progress) {
    ctx.fillStyle = palette.sub;
    ctx.font = font(600, labelSize);
    ctx.fillText(stat.label, x, y);
    const value = statValue(stat, progress);
    ctx.fillStyle = palette.fg;
    const unitSize = Math.round(valueSize * (stat.text ? 1 : .34));
    ctx.font = font(500, unitSize);
    const unitWidth = stat.unit ? ctx.measureText(stat.unit).width + valueSize * .08 : 0;
    const size = fitFont(value, stat.text ? 700 : 800, stat.text ? Math.round(valueSize * .62) : valueSize, maxWidth - unitWidth, 36);
    setTracking(stat.text ? 0 : -size * .035);
    const valueY = y + size * .98 + labelSize * .35;
    ctx.fillText(value, x - size * .03, valueY);
    const width = ctx.measureText(value).width;
    setTracking(0);
    if (stat.unit) {
      ctx.fillStyle = palette.sub;
      ctx.font = font(500, Math.round(size * .34));
      ctx.fillText(stat.unit, x + width + size * .06, valueY);
    }
  }

  function photoName(story) {
    return PHOTO_BY_SPORT[story.sport] || 'correr';
  }

  function draw(t = 99) {
    const story = currentStory();
    const palette = PALETTES[state.style];
    const keys = [...state.selected].filter(key => story.stats[key]);
    const pts = routePoints();
    const hasRoute = Boolean(pts);
    ctx.save();
    ctx.clearRect(0, 0, W, H);
    ctx.textBaseline = 'alphabetic';
    drawBackground(palette, story);

    const intro = expoOut(phase(t, 0, .7));
    withAlpha(intro, (1 - intro) * -24, () => drawBrand(palette, story));

    const titleIn = expoOut(phase(t, .15, .8));
    let cursor = 0;
    withAlpha(titleIn, (1 - titleIn) * 48, () => { cursor = drawTitle(palette, story, 400); });
    if (!cursor) cursor = 472;

    if (!keys.length) {
      ctx.fillStyle = palette.sub;
      ctx.font = font(600, 46);
      wrapLines('Elige al menos un dato para mostrar.', W - M * 2, 2).forEach((line, index) => ctx.fillText(line, M, 760 + index * 60));
    }

    const [heroKey, ...rest] = keys;
    if (hasRoute) {
      const box = { x: M + 10, y: 530, w: W - M * 2 - 20, h: 410 };
      drawRoute(palette, pts, box, easeInOut(phase(t, .5, 2.4)));
    }
    if (heroKey) {
      const heroIn = phase(t, .45, 1.9);
      const heroAlpha = expoOut(phase(t, .4, .6));
      const y = hasRoute ? 1020 : 620;
      withAlpha(heroAlpha, (1 - heroAlpha) * 40, () => {
        drawStat(palette, story.stats[heroKey], M, y, hasRoute ? 200 : 300, hasRoute ? 38 : 44, W - M * 2, heroIn);
      });
    }
    const gridTop = hasRoute ? 1286 : 1000;
    const rowHeight = hasRoute ? 164 : 196;
    const maxRows = hasRoute ? 2 : 3;
    const colWidth = (W - M * 2) / 2;
    rest.slice(0, maxRows * 2).forEach((key, index) => {
      const col = index % 2;
      const row = Math.floor(index / 2);
      const x = M + col * colWidth;
      const y = gridTop + row * rowHeight;
      const appear = expoOut(phase(t, 1.15 + index * .13, .7));
      withAlpha(appear, (1 - appear) * 36, () => {
        ctx.fillStyle = palette.line;
        ctx.fillRect(x, y, colWidth - (col ? 0 : 28), 3);
        drawStat(palette, story.stats[key], x, y + 54, hasRoute ? 76 : 92, 30, colWidth - 40, phase(t, 1.2 + index * .13, 1.4));
      });
    });

    const outroIn = expoOut(phase(t, 2.2, .8));
    withAlpha(outroIn, (1 - outroIn) * 30, () => {
      const caption = captionInput.value.trim() || 'Mi única competencia soy yo.';
      ctx.font = font(750, 50);
      setTracking(-1.2);
      ctx.fillStyle = palette.fg;
      const lines = wrapLines(caption, W - M * 2, 2);
      const base = 1690 - (lines.length - 1) * 60;
      lines.forEach((line, index) => ctx.fillText(line, M, base + index * 60));
      setTracking(0);
      ctx.font = font(600, 30);
      ctx.fillStyle = palette.sub;
      ctx.fillText(`Hecho a mi ritmo en ${SITE}`, M, 1788);
    });
    ctx.restore();
  }

  /* ---------- Interfaz ---------- */
  function renderFields() {
    const story = currentStory();
    const available = Object.keys(story.stats);
    const kept = [...state.selected].filter(key => available.includes(key));
    state.selected = new Set(kept.length ? kept : story.defaults.filter(key => available.includes(key)).slice(0, 4));
    fieldsBox.replaceChildren();
    if (!available.length) {
      fieldsHint.textContent = story.kind === 'activity'
        ? 'Esta sesión no tiene datos para mostrar.'
        : 'Aún no hay actividad registrada para este periodo. Elige una sesión de tu historial o registra una.';
      return;
    }
    available.forEach(key => {
      const stat = story.stats[key];
      const label = document.createElement('label');
      const input = document.createElement('input');
      input.type = 'checkbox';
      input.value = key;
      input.checked = state.selected.has(key);
      input.addEventListener('change', () => {
        if (input.checked) state.selected.add(key); else state.selected.delete(key);
        syncLimit();
        refresh();
      });
      const value = document.createElement('b');
      value.textContent = `${stat.value}${stat.unit && !stat.text ? ` ${stat.unit}` : ''}`;
      label.append(input, document.createTextNode(stat.label), value);
      fieldsBox.append(label);
    });
    syncLimit();
  }
  function syncLimit() {
    const full = state.selected.size >= MAX_FIELDS;
    $$('input', fieldsBox).forEach(input => { input.disabled = full && !input.checked; });
    const story = currentStory();
    const extra = state.route ? 5 : 6;
    fieldsHint.textContent = state.selected.size > extra
      ? `Con recorrido caben ${extra} datos; el último no se verá.`
      : `El primero que marques sale en grande. Elige hasta ${MAX_FIELDS}.`;
    if (!Object.keys(story.stats).length) fieldsHint.textContent = 'Aún no hay datos para mostrar en este periodo.';
  }

  function sourceLabel(entry) {
    const parts = [entry.sport_name || 'Actividad'];
    if (Number(entry.distance_km) > 0) parts.push(`${distanceText(Number(entry.distance_km))} km`);
    else if (entry.duration_minutes) parts.push(`${entry.duration_minutes} min`);
    parts.push(shortDate(parseDate(entry.occurred_at)));
    return parts.join(' · ');
  }
  function fillSources() {
    const previous = state.source;
    sourceSelect.replaceChildren();
    const day = new Option('Mi día de hoy', 'day');
    const week = new Option('Mi semana', 'week');
    sourceSelect.append(day, week);
    const list = state.history || state.dashboard?.week?.activities || [];
    if (list.length) {
      const group = document.createElement('optgroup');
      group.label = 'Una sesión de mi historial';
      list.forEach(entry => group.append(new Option(sourceLabel(entry), String(entry.id))));
      sourceSelect.append(group);
    }
    sourceSelect.value = [...sourceSelect.options].some(option => option.value === previous) ? previous : 'day';
    state.source = sourceSelect.value;
  }

  let refreshTimer = 0;
  function refresh() {
    stopPlayback();
    clearVideo();
    draw(99);
    state.pngBlob = null;
    clearTimeout(refreshTimer);
    refreshTimer = setTimeout(() => {
      canvas.toBlob(blob => { state.pngBlob = blob; }, 'image/png');
    }, 200);
  }

  function loadPhoto(story) {
    const name = photoName(story);
    if (state.photos.has(name)) return;
    const image = new Image();
    image.decoding = 'async';
    image.onload = () => { if (state.style === 'photo') refresh(); };
    image.src = `assets/images/${name}.jpg`;
    state.photos.set(name, image);
  }

  function stopPlayback() {
    if (state.playing) cancelAnimationFrame(state.playing);
    state.playing = 0;
  }
  function play() {
    if (state.recording) return;
    stopPlayback();
    previewVideo.hidden = true;
    const start = performance.now();
    const frame = now => {
      const t = (now - start) / 1000;
      draw(t);
      state.playing = t < DURATION ? requestAnimationFrame(frame) : 0;
    };
    state.playing = requestAnimationFrame(frame);
  }

  function clearVideo() {
    if (state.recording) return;
    if (state.videoUrl) URL.revokeObjectURL(state.videoUrl);
    state.videoUrl = '';
    state.videoBlob = null;
    result.hidden = true;
    previewVideo.hidden = true;
    previewVideo.removeAttribute('src');
  }

  function pickMime() {
    if (!window.MediaRecorder) return '';
    return ['video/mp4;codecs=avc1.42E01E', 'video/mp4;codecs=avc1', 'video/mp4', 'video/webm;codecs=vp9', 'video/webm;codecs=vp8', 'video/webm']
      .find(type => MediaRecorder.isTypeSupported(type)) || '';
  }
  function fileName(ext) {
    const tag = currentStory().fileTag.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    return `sumaritmo-${tag || 'logro'}-${new Date().toLocaleDateString('en-CA')}.${ext}`;
  }
  function setStatus(message, isError = false) {
    status.textContent = message;
    status.classList.toggle('is-error', isError);
  }

  async function recordVideo() {
    const mime = pickMime();
    if (!mime || !canvas.captureStream) {
      setStatus('Este navegador no puede crear el video. Usa «Compartir imagen» o abre SumaRitmo en Chrome o Safari actualizados.', true);
      return;
    }
    stopPlayback();
    clearVideo();
    state.recording = true;
    videoButton.disabled = true;
    imageButton.disabled = true;
    setStatus('Creando tu video… mantén esta pantalla abierta unos segundos.');
    const stream = canvas.captureStream(30);
    const recorder = new MediaRecorder(stream, { mimeType: mime, videoBitsPerSecond: 8_000_000 });
    const chunks = [];
    recorder.ondataavailable = event => { if (event.data.size) chunks.push(event.data); };
    const stopped = new Promise(resolve => { recorder.onstop = resolve; });
    try {
      draw(0);
      recorder.start(250);
      const start = performance.now();
      await new Promise(resolve => {
        const frame = now => {
          const t = (now - start) / 1000;
          draw(t);
          const left = Math.max(0, Math.ceil(DURATION - t));
          status.textContent = `Creando tu video… ${left} s`;
          if (t < DURATION + .2) requestAnimationFrame(frame); else resolve();
        };
        requestAnimationFrame(frame);
      });
      recorder.stop();
      await stopped;
      stream.getTracks().forEach(track => track.stop());
      const type = mime.split(';')[0];
      state.videoBlob = new Blob(chunks, { type });
      state.videoUrl = URL.createObjectURL(state.videoBlob);
      const ext = type === 'video/mp4' ? 'mp4' : 'webm';
      downloadVideo.href = state.videoUrl;
      downloadVideo.download = fileName(ext);
      downloadVideo.lastChild.textContent = ' Descargar video';
      sendVideo.hidden = false;
      previewVideo.src = state.videoUrl;
      previewVideo.hidden = false;
      previewVideo.play().catch(() => {});
      result.hidden = false;
      resultText.textContent = ext === 'mp4'
        ? 'Tu video está listo (MP4, 1080×1920).'
        : 'Tu video está listo (WebM, 1080×1920). Si tu red social no lo acepta, usa la imagen o abre SumaRitmo en Safari o Chrome actualizados.';
      setStatus('');
      sendVideo.focus();
    } catch (error) {
      setStatus('No pudimos crear el video en este dispositivo. Prueba con «Compartir imagen».', true);
    } finally {
      state.recording = false;
      videoButton.disabled = false;
      imageButton.disabled = false;
      draw(99);
    }
  }

  // Sin clics automáticos: el archivo se ofrece en un enlace visible que la persona pulsa.
  let offeredUrl = '';
  function offerDownload(blob, name, isVideo) {
    if (offeredUrl && offeredUrl !== state.videoUrl) URL.revokeObjectURL(offeredUrl);
    offeredUrl = isVideo ? state.videoUrl : URL.createObjectURL(blob);
    downloadVideo.href = offeredUrl;
    downloadVideo.download = name;
    downloadVideo.lastChild.textContent = isVideo ? ' Descargar video' : ' Descargar imagen';
    sendVideo.hidden = !isVideo;
    resultText.textContent = isVideo ? resultText.textContent : 'Tu imagen está lista (PNG, 1080×1920).';
    result.hidden = false;
    downloadVideo.focus();
  }
  async function shareBlob(blob, name) {
    const file = new File([blob], name, { type: blob.type });
    const text = `Mi logro en SumaRitmo · ${SITE}`;
    if (navigator.canShare && navigator.canShare({ files: [file] })) {
      try {
        await navigator.share({ files: [file], title: 'Mi logro en SumaRitmo', text });
        setStatus('¡Listo! Gracias por compartir tu ritmo.');
        return;
      } catch (error) {
        if (error.name === 'AbortError') { setStatus(''); return; }
      }
    }
    offerDownload(blob, name, blob.type.startsWith('video/'));
    setStatus('Pulsa «Descargar» y luego súbelo desde tu galería a TikTok, Instagram o WhatsApp.');
  }

  async function shareImage() {
    stopPlayback();
    draw(99);
    let blob = state.pngBlob;
    if (!blob) blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
    if (!blob) { setStatus('No pudimos crear la imagen.', true); return; }
    state.pngBlob = blob;
    await shareBlob(blob, fileName('png'));
  }

  async function loadHistory() {
    if (state.history) return;
    try {
      const response = await fetch('api.php?share_history=1', { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } });
      if (!response.ok) throw new Error();
      const payload = await response.json();
      state.history = Array.isArray(payload.activities) ? payload.activities : [];
    } catch (_) {
      state.history = null;
    }
  }
  async function fontsReady() {
    if (!document.fonts?.load) return;
    try {
      await Promise.all([document.fonts.load(font(800, 100)), document.fonts.load(font(600, 40)), document.fonts.load(font(500, 40))]);
    } catch (_) {}
  }

  async function open(source) {
    state.dashboard = window.SumaRitmo?.dashboard || state.dashboard;
    if (source) state.source = String(source);
    document.documentElement.style.overflow = 'hidden';
    dialog.showModal();
    setStatus('');
    fillSources();
    renderFields();
    draw(99);
    await Promise.all([fontsReady(), loadHistory()]);
    fillSources();
    if (source) sourceSelect.value = String(source);
    state.source = sourceSelect.value;
    if (state.source === 'day' && !Object.keys(dayStory(state.dashboard).stats).length && state.history?.length) {
      state.source = String(state.history[0].id);
      sourceSelect.value = state.source;
      setStatus('Hoy aún no hay actividad registrada; elegimos tu sesión más reciente.');
    }
    loadPhoto(currentStory());
    renderFields();
    refresh();
    play();
  }

  document.addEventListener('click', event => {
    const opener = event.target.closest('[data-open-share]');
    const entry = event.target.closest('[data-share-entry]');
    if (opener) open(null);
    else if (entry) open(entry.dataset.shareEntry);
  });
  document.addEventListener('sumaritmo:dashboard', event => {
    state.dashboard = event.detail;
    if (dialog.open && !state.recording) { renderFields(); refresh(); }
  });
  $('[data-close-share]').addEventListener('click', () => dialog.close());
  dialog.addEventListener('close', () => {
    stopPlayback();
    document.documentElement.style.overflow = '';
    previewVideo.pause();
  });
  dialog.addEventListener('cancel', event => { if (state.recording) event.preventDefault(); });

  sourceSelect.addEventListener('change', () => {
    state.source = sourceSelect.value;
    state.selected.clear();
    loadPhoto(currentStory());
    renderFields();
    refresh();
    play();
  });
  $$('input[name="share_style"]').forEach(input => input.addEventListener('change', () => {
    state.style = input.value;
    loadPhoto(currentStory());
    refresh();
  }));
  [placeInput, captionInput].forEach(input => input.addEventListener('input', refresh));
  trimInput.addEventListener('change', refresh);
  gpxInput.addEventListener('change', async () => {
    const file = gpxInput.files?.[0];
    state.route = null;
    if (!file) { gpxStatus.textContent = 'El archivo se lee en este dispositivo y no se guarda.'; renderFields(); refresh(); return; }
    if (file.size > 15 * 1024 * 1024) { gpxStatus.textContent = 'El GPX supera 15 MB. Exporta solo esta actividad.'; gpxInput.value = ''; return; }
    try {
      state.route = parseGpx(await file.text());
      gpxStatus.textContent = `Recorrido de ${distanceText(state.route.distanceKm)} km cargado. Se dibuja aquí y no se guarda.`;
    } catch (error) {
      gpxStatus.textContent = error.message || 'No pudimos leer este GPX.';
      gpxInput.value = '';
    }
    renderFields();
    refresh();
    play();
  });
  replayButton.addEventListener('click', play);
  videoButton.addEventListener('click', recordVideo);
  imageButton.addEventListener('click', shareImage);
  sendVideo.addEventListener('click', () => {
    if (state.videoBlob) shareBlob(state.videoBlob, downloadVideo.download || fileName('mp4'));
  });
})();
