<?php
declare(strict_types=1);

const VERSION_ESTATICOS = '20261128';

function icono(string $n, string $clase = 'ico'): string
{
    $p = [
        'inicio' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5.5 9v12h13V9"/><path d="M10 21v-6h4v6"/>',
        'jugar' => '<path d="M6 9a6 6 0 0 1 12 0c0 6.5 2.5 8.5 2.5 8.5h-17S6 15.5 6 9"/><path d="M10.2 21a2 2 0 0 0 3.6 0"/>',
        'resultados' => '<path d="M3 21h18"/><path d="M6 21v-7"/><path d="M11 21V5"/><path d="M16 21v-10"/>',
        'calibrar' => '<rect x="2.5" y="3.5" width="19" height="13" rx="1.5"/><path d="M8 20.5h8"/><path d="M12 16.5v4"/>',
        'usuarios' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20.5v-.5a6.5 6.5 0 0 1 13 0v.5"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8"/><path d="M21.5 20.5V20a5.5 5.5 0 0 0-3.5-5.1"/>',
        'firmas' => '<path d="M4 21V5a1.5 1.5 0 0 1 1.5-1.5h8A1.5 1.5 0 0 1 15 5v16"/><path d="M15 9h3.5A1.5 1.5 0 0 1 20 10.5V21"/><path d="M2 21h20"/><path d="M8 7.5h3M8 11.5h3M8 15.5h3"/>',
        'pagos' => '<rect x="2.5" y="5.5" width="19" height="13" rx="1.5"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9v.01M18 15v.01"/>',
        'salir' => '<path d="M9.5 20.5H5A1.5 1.5 0 0 1 3.5 19V5A1.5 1.5 0 0 1 5 3.5h4.5"/><path d="m15.5 16.5 4.5-4.5-4.5-4.5"/><path d="M20 12H9"/>',
        'llave' => '<circle cx="7.5" cy="15.5" r="4"/><path d="m10.5 12.5 9-9"/><path d="m16 7 3 3"/>',
        'mas' => '<path d="M12 5v14M5 12h14"/>',
        'flecha' => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        'alerta' => '<path d="M12 3.5 2.5 20h19L12 3.5Z"/><path d="M12 10v4.5M12 17.5v.01"/>',
        'ok' => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.8 2.8L16.5 9.5"/>',
        'reloj' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'copiar' => '<rect x="8.5" y="8.5" width="12" height="12" rx="1.5"/><path d="M15.5 8.5V5A1.5 1.5 0 0 0 14 3.5H5A1.5 1.5 0 0 0 3.5 5v9A1.5 1.5 0 0 0 5 15.5h3.5"/>',
    ];
    if ($n === 'rombo') {
        return '<svg class="' . h($clase) . ' rombo" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.5 21.5 12 12 21.5 2.5 12Z" fill="currentColor"/></svg>';
    }
    return '<svg class="' . h($clase) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$n] ?? '') . '</svg>';
}

function marca_svg(): string
{
    // Logo de Revelco redibujado en vector a partir del PNG oficial
    return '<svg viewBox="0 0 100 100" aria-hidden="true">'
        . '<circle cx="69" cy="32" r="25" fill="#8a8a8a"/><circle cx="32" cy="68" r="25" fill="#8a8a8a"/>'
        . '<path d="M65 34 36 66" stroke="#e31e24" stroke-width="35" stroke-linecap="round"/>'
        . '<circle cx="32" cy="31" r="17.5" fill="#231f20" stroke="#8a8a8a" stroke-width="3"/>'
        . '<circle cx="69" cy="68" r="17.5" fill="#231f20" stroke="#8a8a8a" stroke-width="3"/></svg>';
}

function marca(bool $sobreOscuro = true): string
{
    return '<span class="marca">' . marca_svg() . '<span class="marca-txt">Revelco</span></span>';
}

/**
 * Banda de rueda: PRE-APERTURA · RUEDA ABIERTA · CIERRE
 * $fase: pre | abierta | cierre ; $avance 0..1 ; $pie: [izquierda, derecha]
 */
function banda(string $fase, float $avance = 0, array $pie = [], string $id = ''): string
{
    $fases = ['pre' => 'Pre-apertura', 'abierta' => 'Rueda abierta', 'cierre' => 'Cierre'];
    $li = '';
    foreach ($fases as $k => $t) {
        $li .= '<li data-f="' . $k . '"' . ($k === $fase ? ' aria-current="step"' : '') . '>' . $t . '</li>';
    }
    $html = '<div class="banda" data-fase="' . h($fase) . '"' . ($id ? ' id="' . h($id) . '"' : '') . '>'
        . '<ol class="banda-fases">' . $li . '</ol>'
        . '<div class="banda-pista"><span class="banda-relleno" style="--avance:' . max(0, min(1, $avance)) . '"></span></div>';
    if ($pie) {
        $html .= '<div class="banda-pie"><span>' . ($pie[0] ?? '') . '</span><span>' . ($pie[1] ?? '') . '</span></div>';
    }
    return $html . '</div>';
}

/** Banda compacta para tablas: periodo de una licencia */
function banda_mini(?string $inicio, ?string $fin): string
{
    if (!$fin) return '<span class="tenue">—</span>';
    $hoy = time();
    $i = strtotime((string)$inicio);
    $f = strtotime($fin . ' 23:59:59');
    $fase = $hoy < $i ? 'pre' : ($hoy > $f ? 'cierre' : 'abierta');
    $av = $fase === 'abierta' && $f > $i ? ($hoy - $i) / ($f - $i) : ($fase === 'cierre' ? 1 : 0);
    return '<span class="minibanda" data-fase="' . $fase . '"><span class="mb-pista"><span style="--avance:' . round($av, 3) . '"></span></span><span>' . h(fmt_fecha($fin)) . '</span></span>';
}

function pagina_inicio(string $titulo, string $seccion = '', array $o = []): void
{
    $u = usuario();
    $sinRiel = !empty($o['sin_riel']) || !$u;
    ?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title><?= h($titulo) ?> · <?= h(config('app.nombre')) ?></title>
<link rel="icon" href="data:image/svg+xml,<?= rawurlencode(str_replace('<svg ', '<svg xmlns="http://www.w3.org/2000/svg" ', marca_svg())) ?>">
<link rel="stylesheet" href="<?= h(url('assets/app.css')) ?>?v=<?= VERSION_ESTATICOS ?>">
</head>
<body class="<?= $sinRiel ? 'sin-riel' : 'con-riel' ?> <?= h($o['clase_body'] ?? '') ?>">
<?php if (!$sinRiel): ?>
<aside class="riel">
  <a class="riel-marca" href="<?= h(url('inicio.php')) ?>" aria-label="Inicio"><?= marca(true) ?></a>
  <nav class="riel-nav" aria-label="Principal">
    <?php
    $items = [
        'inicio' => ['inicio.php', 'Inicio', 'inicio'],
        'jugar' => ['jugar.php', 'Jugar', 'jugar'],
        'resultados' => ['resultados.php', 'Resultados', 'resultados'],
        'calibrar' => ['calibrar.php', 'Calibrar', 'calibrar'],
    ];
    foreach ($items as $k => [$href, $txt, $ic]) {
        echo '<a href="' . h(url($href)) . '"' . ($seccion === $k ? ' aria-current="page"' : '') . '>' . icono($ic) . '<span>' . $txt . '</span></a>';
    }
    if ($u['rol'] === 'admin') {
        echo '<p class="riel-grupo">Administración</p>';
        $adm = [
            'admin-usuarios' => ['admin/index.php', 'Usuarios', 'usuarios'],
            'admin-firmas' => ['admin/firmas.php', 'Firmas y cupos', 'firmas'],
            'admin-pagos' => ['admin/pagos.php', 'Pagos', 'pagos'],
            'admin-simulador' => ['admin/simulador.php', 'Simulador', 'reloj'],
        ];
        foreach ($adm as $k => [$href, $txt, $ic]) {
            echo '<a href="' . h(url($href)) . '"' . ($seccion === $k ? ' aria-current="page"' : '') . '>' . icono($ic) . '<span>' . $txt . '</span></a>';
        }
    }
    ?>
  </nav>
  <div class="riel-usuario">
    <p class="riel-nombre"><?= h($u['nombre']) ?></p>
    <p class="riel-firma"><?= h($u['firma_nombre'] ?? ROLES[$u['rol']]) ?></p>
    <div class="riel-acciones">
      <a href="<?= h(url('cambiar-clave.php')) ?>"><?= icono('llave') ?><span>Contraseña</span></a>
      <a href="<?= h(url('salir.php')) ?>"><?= icono('salir') ?><span>Salir</span></a>
    </div>
  </div>
</aside>
<?php endif; ?>
<main class="obra" id="contenido">
<?php
    foreach (tomar_avisos() as $a) {
        echo '<div class="aviso aviso-' . h($a['tipo']) . '" role="status">' . icono($a['tipo'] === 'error' ? 'alerta' : 'ok') . '<span>' . $a['texto'] . '</span></div>';
    }
}

function pagina_fin(array $scripts = []): void
{
    echo "</main>\n";
    foreach ($scripts as $s) {
        echo '<script src="' . h(url($s)) . '?v=' . VERSION_ESTATICOS . '"></script>' . "\n";
    }
    echo "</body>\n</html>";
}

function cabeza(string $titulo, string $sub = '', string $acciones = ''): void
{
    echo '<header class="cabeza"><div><h1>' . h($titulo) . '</h1>' . ($sub ? '<p class="cabeza-sub">' . $sub . '</p>' : '') . '</div>'
        . ($acciones ? '<div class="cabeza-acciones">' . $acciones . '</div>' : '') . '</header>';
}

function estado_licencia_html(?array $lic): string
{
    if (!$lic) return '<span class="chip chip-gris">Sin licencia</span>';
    $d = dias_hasta($lic['fin']);
    if ($d < 0) return '<span class="chip chip-gris">Vencida</span>';
    if ($d <= 7) return '<span class="chip chip-ambar">Vence en ' . $d . ' d</span>';
    return '<span class="chip chip-verde">Activa</span>';
}
