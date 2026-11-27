<?php
declare(strict_types=1);

const ESCENARIOS = ['subasta-v1' => 'Subasta (réplica de la rueda)', 'lanzamiento-v1' => 'Lanzamiento básico'];
const ESCENARIO_DEFECTO = 'subasta-v1';

/** Rangos de demora de la plataforma en la réplica: [mínimo, típico, máximo] en ms */
function tiempos_subasta(): array
{
    $t = config('app.tiempos_subasta');
    $ok = [];
    foreach (['boton_tras_apertura', 'ventana_colocacion', 'captcha', 'ventana_atencion'] as $k) {
        $v = $t[$k] ?? null;
        if (is_array($v) && count($v) === 3 && $v[0] >= 0 && $v[0] <= $v[1] && $v[1] <= $v[2] && $v[2] <= 20000 && $v[0] < $v[2]) {
            $ok[$k] = array_map('intval', array_values($v));
        }
    }
    return $ok; // lo que falte usa los valores medidos en la grabación
}

function escenario_valido($e): string
{
    return is_string($e) && isset(ESCENARIOS[$e]) ? $e : ESCENARIO_DEFECTO;
}

/** Resumen de partidas de un usuario */
function estadisticas(int $uid, string $esc = ESCENARIO_DEFECTO): array
{
    $r = q("SELECT COUNT(*) total,
                   SUM(resultado = 'lanzada') lanzadas,
                   MIN(CASE WHEN resultado = 'lanzada' THEN tiempo_total_ms END) mejor,
                   SUM(errores) errores
            FROM intentos WHERE usuario_id = ? AND escenario = ?", [$uid, $esc])->fetch();
    $ult = q("SELECT tiempo_total_ms FROM intentos WHERE usuario_id = ? AND escenario = ? AND resultado = 'lanzada' ORDER BY id DESC LIMIT 10", [$uid, $esc])->fetchAll(PDO::FETCH_COLUMN);
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
    $f = q("SELECT tiempo_total_ms ms, creado_en FROM intentos WHERE usuario_id = ? AND escenario = ? AND resultado = 'lanzada' ORDER BY id DESC LIMIT " . (int)$n, [$uid, $esc])->fetchAll();
    return array_reverse($f);
}

/** Ranking de la firma: mejor tiempo por corredor en el periodo */
function ranking_firma(?int $firmaId, string $desde, string $esc = ESCENARIO_DEFECTO): array
{
    if (!$firmaId) return [];
    return q("SELECT u.id, u.nombre, MIN(i.tiempo_total_ms) mejor, COUNT(*) partidas
              FROM intentos i JOIN usuarios u ON u.id = i.usuario_id
              WHERE i.firma_id = ? AND i.escenario = ? AND i.resultado = 'lanzada' AND i.creado_en >= ? AND u.activo = 1
              GROUP BY u.id, u.nombre ORDER BY mejor ASC, partidas DESC", [$firmaId, $esc, $desde])->fetchAll();
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
