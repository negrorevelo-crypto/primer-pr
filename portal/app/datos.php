<?php
declare(strict_types=1);

const ESCENARIOS = ['subasta-v1' => 'Subasta (réplica de la rueda)', 'lanzamiento-v1' => 'Lanzamiento básico'];
const ESCENARIO_DEFECTO = 'subasta-v1';

/** Rangos de demora de la plataforma en la réplica: [mínimo, típico, máximo] en ms */
const TIEMPOS_MOMENTOS = [
    'boton_tras_apertura' => ['Aparece Lanzar Oferta', 'Desde la apertura hasta que aparece el botón', 680],
    'ventana_colocacion' => ['Ventana Colocación', 'Desde el clic en Lanzar Oferta hasta que se abre la ventana', 300],
    'captcha' => ['Captcha', 'Desde el clic en la casilla hasta que queda verificado', 1300],
    'ventana_atencion' => ['Ventana Atención', 'Desde el clic en Lanzar hasta que aparece la confirmación', 200],
];
const TIEMPOS_PERFILES = [
    'grabacion' => ['Como la grabación', ['boton_tras_apertura' => [400, 700, 1500], 'ventana_colocacion' => [150, 300, 1200], 'captcha' => [700, 1300, 4000], 'ventana_atencion' => [80, 250, 1500]]],
    'congestionada' => ['Rueda congestionada', ['boton_tras_apertura' => [600, 1200, 3000], 'ventana_colocacion' => [300, 800, 2500], 'captcha' => [1200, 2500, 6000], 'ventana_atencion' => [200, 700, 3000]]],
    'agil' => ['Rueda ágil', ['boton_tras_apertura' => [300, 500, 900], 'ventana_colocacion' => [100, 200, 500], 'captcha' => [500, 900, 1800], 'ventana_atencion' => [50, 150, 400]]],
];
// Mínimos que un corredor no puede bajar, si el administrador no fija otros
const TIEMPOS_MINIMOS_DEFECTO = ['boton_tras_apertura' => 300, 'ventana_colocacion' => 100, 'captcha' => 500, 'ventana_atencion' => 50];

// Tiempo con el que se compite: el propio (sin las demoras de la plataforma); partidas antiguas usan el total
const SQL_TIEMPO = 'COALESCE(tiempo_propio_ms, tiempo_total_ms)';

function archivo_ajustes(): string
{
    return rtrim((string)config('almacenamiento'), '/\\') . '/ajustes-simulador.json';
}

/** Ajustes guardados desde Administración › Simulador */
function ajustes_simulador(): array
{
    $f = archivo_ajustes();
    if (!is_file($f)) return [];
    $d = json_decode((string)@file_get_contents($f), true);
    return is_array($d) ? $d : [];
}

function tiempos_validos($t): array
{
    $ok = [];
    foreach (array_keys(TIEMPOS_MOMENTOS) as $k) {
        $v = is_array($t) ? ($t[$k] ?? null) : null;
        if (is_array($v) && count($v) === 3) {
            $v = array_map('intval', array_values($v));
            if ($v[0] >= 0 && $v[0] <= $v[1] && $v[1] <= $v[2] && $v[2] <= 20000 && $v[0] < $v[2]) $ok[$k] = $v;
        }
    }
    return $ok;
}

/** Tiempos oficiales: panel del administrador › config.php › medidos en la grabación */
function tiempos_subasta(): array
{
    return tiempos_validos(ajustes_simulador()['tiempos'] ?? null)
        + tiempos_validos(config('app.tiempos_subasta'))
        + TIEMPOS_PERFILES['grabacion'][1];
}

/** Mínimos que fija el administrador para los tiempos de cada corredor (ms) */
function tiempos_minimos(): array
{
    $m = ajustes_simulador()['minimos'] ?? [];
    $ok = [];
    foreach (TIEMPOS_MINIMOS_DEFECTO as $k => $def) {
        $v = $m[$k] ?? $def;
        $ok[$k] = is_numeric($v) ? max(0, min(20000, (int)$v)) : $def;
    }
    return $ok;
}

/** Sube cada valor al mínimo permitido conservando mínimo ≤ típico ≤ máximo */
function aplicar_minimos(array $t, array $min): array
{
    foreach ($t as $k => $v) {
        $piso = $min[$k] ?? 0;
        $a = max($v[0], $piso); $b = max($v[1], $a); $c = max($v[2], $b, $a + 1);
        $t[$k] = [$a, $b, $c];
    }
    return $t;
}

/** Ajustes propios de un corredor (perfil y tiempos), o null si usa los oficiales */
function mis_tiempos(array $u): ?array
{
    $d = json_decode((string)($u['ajustes_juego'] ?? ''), true);
    if (!is_array($d) || empty($d['perfil']) || $d['perfil'] === 'oficial') return null;
    if (isset(TIEMPOS_PERFILES[$d['perfil']])) return ['perfil' => $d['perfil'], 'tiempos' => TIEMPOS_PERFILES[$d['perfil']][1]];
    $t = tiempos_validos($d['tiempos'] ?? null);
    return count($t) === count(TIEMPOS_MOMENTOS) ? ['perfil' => 'personalizado', 'tiempos' => $t] : null;
}

/** Tiempos con los que juega un corredor */
function tiempos_para_usuario(array $u): array
{
    $mios = mis_tiempos($u);
    return aplicar_minimos($mios ? $mios['tiempos'] : tiempos_subasta(), tiempos_minimos());
}

function escenario_valido($e): string
{
    return is_string($e) && isset(ESCENARIOS[$e]) ? $e : ESCENARIO_DEFECTO;
}

/** Resumen de partidas de un usuario (marcas por tiempo propio) */
function estadisticas(int $uid, string $esc = ESCENARIO_DEFECTO): array
{
    $r = q("SELECT COUNT(*) total,
                   SUM(resultado = 'lanzada') lanzadas,
                   MIN(CASE WHEN resultado = 'lanzada' THEN " . SQL_TIEMPO . " END) mejor,
                   SUM(errores) errores
            FROM intentos WHERE usuario_id = ? AND escenario = ?", [$uid, $esc])->fetch();
    $ult = q("SELECT " . SQL_TIEMPO . " FROM intentos WHERE usuario_id = ? AND escenario = ? AND resultado = 'lanzada' ORDER BY id DESC LIMIT 10", [$uid, $esc])->fetchAll(PDO::FETCH_COLUMN);
    $r['promedio10'] = $ult ? (int)round(array_sum($ult) / count($ult)) : null;
    $r['total'] = (int)$r['total'];
    $r['lanzadas'] = (int)$r['lanzadas'];
    $r['errores'] = (int)$r['errores'];
    $r['mejor'] = $r['mejor'] !== null ? (int)$r['mejor'] : null;
    return $r;
}

/** Últimas partidas lanzadas, de la más antigua a la más reciente */
function serie_tiempos(int $uid, int $n = 20, string $esc = ESCENARIO_DEFECTO): array
{
    $f = q("SELECT " . SQL_TIEMPO . " ms, creado_en FROM intentos WHERE usuario_id = ? AND escenario = ? AND resultado = 'lanzada' ORDER BY id DESC LIMIT " . (int)$n, [$uid, $esc])->fetchAll();
    return array_reverse($f);
}

/** Ranking de la firma: mejor tiempo propio por corredor en el periodo */
function ranking_firma(?int $firmaId, string $desde, string $esc = ESCENARIO_DEFECTO): array
{
    if (!$firmaId) return [];
    return q("SELECT u.id, u.nombre, MIN(COALESCE(i.tiempo_propio_ms, i.tiempo_total_ms)) mejor, COUNT(*) partidas
              FROM intentos i JOIN usuarios u ON u.id = i.usuario_id
              WHERE i.firma_id = ? AND i.escenario = ? AND i.resultado = 'lanzada' AND i.creado_en >= ? AND u.activo = 1
              GROUP BY u.id, u.nombre ORDER BY MIN(COALESCE(i.tiempo_propio_ms, i.tiempo_total_ms)) ASC, COUNT(*) DESC", [$firmaId, $esc, $desde])->fetchAll();
}

function ultima_calibracion(int $uid): ?array
{
    return q('SELECT * FROM calibraciones WHERE usuario_id = ? ORDER BY id DESC LIMIT 1', [$uid])->fetch() ?: null;
}

/** Gráfica SVG del tiempo total por partida; el récord se marca en latón */
function grafica_tiempos(array $serie): string
{
    if (count($serie) < 2) return '';
    $W = 600; $H = 140; $pl = 0; $pr = 6; $pt = 8; $pb = 8;
    $vals = array_map(fn($p) => (int)$p['ms'], $serie);
    $min = min($vals); $max = max($vals);
    if ($max === $min) { $max += 100; $min = max(0, $min - 100); }
    $n = count($vals);
    $x = fn($i) => $pl + ($W - $pl - $pr) * ($n === 1 ? 0 : $i / ($n - 1));
    $y = fn($v) => $pt + ($H - $pt - $pb) * (1 - ($v - $min) / ($max - $min));
    $d = '';
    foreach ($vals as $i => $v) $d .= ($i ? 'L' : 'M') . round($x($i), 1) . ' ' . round($y($v), 1);
    $mejor = min($vals);
    $pts = '';
    foreach ($vals as $i => $v) {
        $rec = $v === $mejor;
        $pts .= '<circle class="punto' . ($rec ? ' punto-record' : '') . '" cx="' . round($x($i), 1) . '" cy="' . round($y($v), 1) . '" r="' . ($rec ? 5 : 3.2) . '"><title>' . h(fmt_s($v)) . ' · ' . h(fmt_fecha($serie[$i]['creado_en'], true)) . '</title></circle>';
    }
    $ejes = '<line class="eje" x1="' . $pl . '" x2="' . ($W - $pr) . '" y1="' . $y($min) . '" y2="' . $y($min) . '"/>'
        . '<line class="eje" x1="' . $pl . '" x2="' . ($W - $pr) . '" y1="' . $y($max) . '" y2="' . $y($max) . '"/>';
    $pct = fn($v) => round($y($v) / $H * 100, 2);
    $et = '<span class="g-et g-y" style="top:calc((100% - 22px) * ' . ($pct($max) / 100) . ')">' . h(number_format($max / 1000, 2, ',', '')) . ' s</span>'
        . '<span class="g-et g-y" style="top:calc((100% - 22px) * ' . ($pct($min) / 100) . ')">' . h(number_format($min / 1000, 2, ',', '')) . ' s</span>'
        . '<span class="g-et g-x" style="left:52px">' . h(fmt_fecha($serie[0]['creado_en'])) . '</span>'
        . '<span class="g-et g-x" style="right:0">' . h(fmt_fecha($serie[$n - 1]['creado_en'])) . '</span>';
    return '<div class="grafica-caja"><svg class="grafica" viewBox="0 0 ' . $W . ' ' . $H . '" role="img" aria-label="Tiempo total de las últimas ' . $n . ' partidas; abajo es más rápido">'
        . $ejes . '<path class="linea" d="' . $d . '"/>' . $pts . '</svg>' . $et . '</div>';
}
