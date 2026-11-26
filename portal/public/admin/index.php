<?php
require dirname(__DIR__, 2) . '/app/bootstrap.php';
requiere_admin();

$buscar = trim((string)($_GET['q'] ?? ''));
$firmaF = (string)($_GET['firma'] ?? '');
$estadoF = (string)($_GET['estado'] ?? '');
$estados = ['activa' => 'Activa', 'por-vencer' => 'Vence en 7 días', 'vencida' => 'Vencida', 'sin' => 'Sin licencia', 'inactivo' => 'Cuenta desactivada'];

$sql = "SELECT u.*, f.nombre AS firma,
          (SELECT MAX(l.fin) FROM licencias l WHERE l.usuario_id = u.id AND l.estado = 'activa') AS fin_lic,
          (SELECT MAX(l.fin) FROM licencias l WHERE l.usuario_id = u.id AND l.estado = 'activa' AND CURDATE() BETWEEN l.inicio AND l.fin) AS fin_vig,
          (SELECT MIN(l.inicio) FROM licencias l WHERE l.usuario_id = u.id AND l.estado = 'activa' AND l.fin = COALESCE((SELECT MAX(l2.fin) FROM licencias l2 WHERE l2.usuario_id = u.id AND l2.estado = 'activa' AND CURDATE() BETWEEN l2.inicio AND l2.fin), (SELECT MAX(l3.fin) FROM licencias l3 WHERE l3.usuario_id = u.id AND l3.estado = 'activa'))) AS ini_lic,
          (SELECT COUNT(*) FROM intentos i WHERE i.usuario_id = u.id) AS partidas
        FROM usuarios u LEFT JOIN firmas f ON f.id = u.firma_id WHERE 1 = 1";
$p = [];
if ($buscar !== '') { $sql .= ' AND (u.nombre LIKE ? OR u.usuario LIKE ? OR u.correo LIKE ?)'; $p[] = "%$buscar%"; $p[] = "%$buscar%"; $p[] = "%$buscar%"; }
if ($firmaF === 'ninguna') $sql .= ' AND u.firma_id IS NULL';
elseif (ctype_digit($firmaF)) { $sql .= ' AND u.firma_id = ?'; $p[] = (int)$firmaF; }
$sql .= ' ORDER BY u.activo DESC, u.nombre';
$filas = q($sql, $p)->fetchAll();

$hoy = date('Y-m-d');
foreach ($filas as &$f) {
    if (!$f['activo']) $f['estado'] = 'inactivo';
    elseif ($f['fin_vig']) $f['estado'] = dias_hasta($f['fin_vig']) <= 7 ? 'por-vencer' : 'activa';
    elseif ($f['fin_lic'] && $f['fin_lic'] < $hoy) $f['estado'] = 'vencida';
    else $f['estado'] = 'sin';
}
unset($f);
$todas = $filas;
if (isset($estados[$estadoF])) $filas = array_values(array_filter($filas, fn($f) => $f['estado'] === $estadoF || ($estadoF === 'activa' && $f['estado'] === 'por-vencer')));

$cuenta = array_count_values(array_column($todas, 'estado'));
$pagosMes = (float)q("SELECT COALESCE(SUM(valor),0) FROM pagos WHERE fecha_pago >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();
$firmas = q('SELECT id, nombre FROM firmas ORDER BY nombre')->fetchAll();

pagina_inicio('Usuarios', 'admin-usuarios');
cabeza('Usuarios', 'Cuentas de corredores, licencias y accesos.', '<a class="btn btn-rojo" href="' . h(url('admin/usuario.php')) . '">' . icono('mas') . 'Nuevo usuario</a>');
?>
<div class="datos" style="margin:0 0 18px">
  <div class="dato"><b><?= ($cuenta['activa'] ?? 0) + ($cuenta['por-vencer'] ?? 0) ?></b><span>Con licencia activa</span></div>
  <div class="dato"><b><?= (int)($cuenta['por-vencer'] ?? 0) ?></b><span><a href="?estado=por-vencer">Vencen en los próximos 7 días</a></span></div>
  <div class="dato"><b><?= (int)($cuenta['vencida'] ?? 0) + (int)($cuenta['sin'] ?? 0) ?></b><span><a href="?estado=sin">Sin licencia</a> o <a href="?estado=vencida">vencidas</a></span></div>
  <div class="dato"><b><?= h(fmt_cop($pagosMes)) ?></b><span><a href="<?= h(url('admin/pagos.php')) ?>">Pagos de este mes</a></span></div>
</div>

<section class="panel">
  <form class="panel-cab filtros" method="get" role="search">
    <div class="campo" style="flex:1 1 240px"><label for="q">Buscar</label><input id="q" name="q" type="search" value="<?= h($buscar) ?>" placeholder="Nombre, usuario o correo"></div>
    <div class="campo"><label for="firma">Firma</label>
      <select id="firma" name="firma"><option value="">Todas</option><option value="ninguna"<?= $firmaF === 'ninguna' ? ' selected' : '' ?>>Sin firma</option>
      <?php foreach ($firmas as $fi): ?><option value="<?= (int)$fi['id'] ?>"<?= $firmaF === (string)$fi['id'] ? ' selected' : '' ?>><?= h($fi['nombre']) ?></option><?php endforeach; ?></select></div>
    <div class="campo"><label for="estado">Licencia</label>
      <select id="estado" name="estado"><option value="">Todas</option>
      <?php foreach ($estados as $k => $t): ?><option value="<?= $k ?>"<?= $estadoF === $k ? ' selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select></div>
    <button class="btn btn-tinta" type="submit">Filtrar</button>
    <?php if ($buscar !== '' || $firmaF !== '' || $estadoF !== ''): ?><a class="btn btn-sutil" href="?">Quitar filtros</a><?php endif; ?>
  </form>
  <?php if (!$todas): ?>
    <div class="vacio"><p>Aún no hay usuarios.</p><p>Cree el primero con <b>Nuevo usuario</b>; el sistema genera la contraseña temporal y el mensaje para enviarle.</p></div>
  <?php elseif (!$filas): ?>
    <div class="vacio"><p>Ningún usuario coincide con los filtros.</p></div>
  <?php else: ?>
  <div class="tabla-envoltura"><table class="tabla">
    <thead><tr><th>Usuario</th><th>Firma</th><th>Rol</th><th>Licencia</th><th>Periodo · vence</th><th>Último acceso</th><th class="num">Partidas</th></tr></thead>
    <tbody>
    <?php foreach ($filas as $f):
      $chip = ['activa' => ['Activa', 'chip-verde'], 'por-vencer' => ['Por vencer', 'chip-ambar'], 'vencida' => ['Vencida', 'chip-rojo'], 'sin' => ['Sin licencia', 'chip-gris'], 'inactivo' => ['Desactivada', 'chip-gris']][$f['estado']];
      if ($f['rol'] === 'admin') $chip = ['Administrador', 'chip-gris'];
    ?>
      <tr>
        <td><a class="fila-enlace" href="<?= h(url('admin/usuario.php?id=' . (int)$f['id'])) ?>"><?= h($f['nombre']) ?></a><br><small><?= h($f['usuario']) ?></small></td>
        <td><?= $f['firma'] ? h($f['firma']) : '<span class="tenue">Individual</span>' ?></td>
        <td><?= h(ROLES[$f['rol']]) ?></td>
        <td><span class="chip <?= $chip[1] ?>"><?= $chip[0] ?></span></td>
        <td><?= $f['rol'] === 'admin' ? '<span class="tenue">—</span>' : banda_mini($f['ini_lic'], $f['fin_vig'] ?: $f['fin_lic']) ?></td>
        <td><?= $f['ultimo_acceso'] ? h(fmt_fecha($f['ultimo_acceso'], true)) : '<span class="tenue">Nunca</span>' ?></td>
        <td class="num"><?= (int)$f['partidas'] ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>
<?php pagina_fin(); ?>
