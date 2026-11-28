<?php
require dirname(__DIR__, 2) . '/app/bootstrap.php';
requiere_admin();

$mes = preg_match('/^\d{4}-\d{2}$/', (string)($_GET['mes'] ?? '')) ? $_GET['mes'] : date('Y-m');
$desde = $mes . '-01';
$hasta = date('Y-m-t', strtotime($desde));
$medios = ['transferencia' => 'Transferencia', 'nequi' => 'Nequi', 'daviplata' => 'Daviplata', 'efectivo' => 'Efectivo', 'pasarela' => 'Pasarela en línea', 'otro' => 'Otro'];

$pagos = q("SELECT p.*, u.nombre AS usuario_nombre, u.id AS uid, f.nombre AS firma_nombre, f.id AS fid, l.inicio AS l_inicio, l.fin AS l_fin, pq.cupos
            FROM pagos p
            LEFT JOIN usuarios u ON u.id = p.usuario_id
            LEFT JOIN firmas f ON f.id = p.firma_id
            LEFT JOIN licencias l ON l.id = p.licencia_id
            LEFT JOIN paquetes pq ON pq.id = p.paquete_id
            WHERE p.fecha_pago BETWEEN ? AND ? ORDER BY p.fecha_pago DESC, p.id DESC", [$desde, $hasta])->fetchAll();
$total = array_sum(array_map(fn($p) => (float)$p['valor'], $pagos));

if (isset($_GET['csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="pagos-' . $mes . '.csv"');
    $o = fopen('php://output', 'w');
    fwrite($o, "\xEF\xBB\xBF");
    fputcsv($o, ['Fecha', 'Pagó', 'Firma', 'Concepto', 'Medio', 'Referencia', 'Valor'], ';');
    foreach ($pagos as $p) {
        $concepto = $p['paquete_id'] ? 'Paquete de ' . $p['cupos'] . ' cupos' : ($p['licencia_id'] ? 'Licencia ' . $p['l_inicio'] . ' a ' . $p['l_fin'] : 'Pago');
        fputcsv($o, [$p['fecha_pago'], $p['usuario_nombre'] ?? $p['firma_nombre'], $p['firma_nombre'], $concepto, $medios[$p['medio']], $p['referencia'], number_format((float)$p['valor'], 0, ',', '')], ';');
    }
    exit;
}

$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$nombreMes = $meses[(int)substr($mes, 5, 2) - 1] . ' ' . substr($mes, 0, 4);
$ant = date('Y-m', strtotime($desde . ' -1 month'));
$sig = date('Y-m', strtotime($desde . ' +1 month'));

pagina_inicio('Pagos', 'admin-pagos');
cabeza('Pagos de ' . $nombreMes, count($pagos) . ' pagos · total <b>' . h(fmt_cop($total)) . '</b>',
    '<div class="seg"><a href="?mes=' . $ant . '">← Anterior</a>' . ($sig <= date('Y-m') ? '<a href="?mes=' . $sig . '">Siguiente →</a>' : '') . '</div><a class="btn" href="?mes=' . $mes . '&csv=1">Descargar Excel (CSV)</a>');
?>
<section class="panel">
  <?php if (!$pagos): ?>
    <div class="vacio"><p>No hay pagos registrados en <?= h($nombreMes) ?>.</p><p>Los pagos se registran al activar la licencia de un usuario o al crear un paquete de una firma.</p></div>
  <?php else: ?>
  <div class="tabla-envoltura"><table class="tabla">
    <thead><tr><th>Fecha</th><th>Pagó</th><th>Concepto</th><th>Medio</th><th>Referencia</th><th class="num">Valor</th></tr></thead>
    <tbody>
    <?php foreach ($pagos as $p): ?>
      <tr>
        <td><?= h(fmt_fecha($p['fecha_pago'])) ?></td>
        <td><?php if ($p['uid']): ?><a class="fila-enlace" href="<?= h(url('admin/usuario.php?id=' . (int)$p['uid'])) ?>"><?= h($p['usuario_nombre']) ?></a><?= $p['firma_nombre'] ? '<br><small>' . h($p['firma_nombre']) . '</small>' : '' ?>
            <?php elseif ($p['fid']): ?><a class="fila-enlace" href="<?= h(url('admin/firmas.php?id=' . (int)$p['fid'])) ?>"><?= h($p['firma_nombre']) ?></a><?php else: ?>—<?php endif; ?></td>
        <td><?= $p['paquete_id'] ? 'Paquete de ' . (int)$p['cupos'] . ' cupos' : ($p['licencia_id'] ? 'Licencia ' . h(fmt_fecha($p['l_inicio'])) . ' – ' . h(fmt_fecha($p['l_fin'])) : 'Pago') ?></td>
        <td><?= h($medios[$p['medio']]) ?></td>
        <td><?= h($p['referencia'] ?? '—') ?></td>
        <td class="num"><b><?= h(fmt_cop($p['valor'])) ?></b></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>
<?php pagina_fin(); ?>
