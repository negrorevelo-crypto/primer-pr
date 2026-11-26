<?php
require dirname(__DIR__, 2) . '/app/bootstrap.php';
$admin = requiere_admin();

$id = isset($_GET['id']) && ctype_digit((string)$_GET['id']) ? (int)$_GET['id'] : 0;
$nueva = isset($_GET['nueva']);
$x = $id ? q('SELECT * FROM firmas WHERE id = ?', [$id])->fetch() : null;
if ($id && !$x) { aviso('error', 'La firma no existe.'); redirigir(url('admin/firmas.php')); }
$errores = [];
$medios = ['transferencia' => 'Transferencia', 'nequi' => 'Nequi', 'daviplata' => 'Daviplata', 'efectivo' => 'Efectivo', 'pasarela' => 'Pasarela en línea', 'otro' => 'Otro'];

if (es_post()) {
    csrf_verificar();
    $accion = (string)($_POST['accion'] ?? '');

    if ($accion === 'guardar') {
        $d = [
            'nombre' => trim((string)($_POST['nombre'] ?? '')),
            'nit' => trim((string)($_POST['nit'] ?? '')),
            'contacto_nombre' => trim((string)($_POST['contacto_nombre'] ?? '')),
            'contacto_correo' => trim((string)($_POST['contacto_correo'] ?? '')),
            'contacto_telefono' => trim((string)($_POST['contacto_telefono'] ?? '')),
            'activa' => isset($_POST['activa']) ? 1 : 0,
        ];
        if ($d['nombre'] === '') $errores['nombre'] = 'Escriba el nombre de la firma.';
        elseif (q('SELECT id FROM firmas WHERE nombre = ? AND id <> ?', [$d['nombre'], $id])->fetch()) $errores['nombre'] = 'Ya existe una firma con ese nombre.';
        if ($d['contacto_correo'] !== '' && !filter_var($d['contacto_correo'], FILTER_VALIDATE_EMAIL)) $errores['contacto_correo'] = 'El correo no es válido.';
        if (!$errores) {
            $vals = [$d['nombre'], $d['nit'] ?: null, $d['contacto_nombre'] ?: null, $d['contacto_correo'] ?: null, $d['contacto_telefono'] ?: null, $d['activa']];
            if ($x) {
                q('UPDATE firmas SET nombre=?, nit=?, contacto_nombre=?, contacto_correo=?, contacto_telefono=?, activa=? WHERE id=?', [...$vals, $id]);
            } else {
                q('INSERT INTO firmas (nombre, nit, contacto_nombre, contacto_correo, contacto_telefono, activa) VALUES (?,?,?,?,?,?)', $vals);
                $id = (int)db()->lastInsertId();
            }
            auditar('firma_guardar', $d['nombre']);
            aviso('ok', 'Firma guardada.');
            redirigir(url('admin/firmas.php?id=' . $id));
        }
        $x = array_merge($x ?? [], $d);
        $nueva = !$id;
    }

    if ($x && $accion === 'paquete') {
        $cupos = (int)($_POST['cupos'] ?? 0);
        $ini = (string)($_POST['inicio'] ?? '');
        $fin = (string)($_POST['fin'] ?? '');
        $valor = trim((string)($_POST['valor'] ?? ''));
        $valorNum = $valor === '' ? null : (float)preg_replace('/[^\d]/', '', $valor);
        if ($cupos < 1 || $cupos > 5000) $errores['cupos'] = 'Entre 1 y 5.000 cupos.';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ini)) $errores['inicio'] = 'Fecha de inicio inválida.';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin) || $fin < $ini) $errores['fin'] = 'La fecha de fin debe ser posterior al inicio.';
        if (!$errores) {
            db()->beginTransaction();
            q('INSERT INTO paquetes (firma_id, cupos, inicio, fin, valor, notas) VALUES (?,?,?,?,?,?)', [$id, $cupos, $ini, $fin, $valorNum, mb_substr(trim((string)($_POST['notas'] ?? '')), 0, 500) ?: null]);
            $pid = (int)db()->lastInsertId();
            if ($valorNum && !empty($_POST['registrar_pago'])) {
                $medio = array_key_exists($_POST['medio'] ?? '', $medios) ? $_POST['medio'] : 'transferencia';
                q('INSERT INTO pagos (firma_id, paquete_id, valor, medio, referencia, fecha_pago, registrado_por) VALUES (?,?,?,?,?,?,?)',
                    [$id, $pid, $valorNum, $medio, mb_substr(trim((string)($_POST['referencia'] ?? '')), 0, 120) ?: null, date('Y-m-d'), $admin['id']]);
            }
            db()->commit();
            auditar('paquete_crear', $x['nombre'] . " $cupos cupos $ini a $fin");
            aviso('ok', "Paquete de $cupos cupos registrado.");
            redirigir(url('admin/firmas.php?id=' . $id));
        }
    }

    if ($x && $accion === 'asignar') {
        // Asigna un cupo del paquete a cada corredor activo de la firma sin licencia vigente
        $pid = (int)($_POST['paquete_id'] ?? 0);
        $paq = q('SELECT * FROM paquetes WHERE id = ? AND firma_id = ?', [$pid, $id])->fetch();
        if ($paq) {
            $libres = (int)$paq['cupos'] - (int)q("SELECT COUNT(*) FROM licencias WHERE paquete_id = ? AND estado <> 'anulada'", [$pid])->fetchColumn();
            $sin = q("SELECT u.id FROM usuarios u WHERE u.firma_id = ? AND u.activo = 1 AND u.rol <> 'admin'
                      AND NOT EXISTS (SELECT 1 FROM licencias l WHERE l.usuario_id = u.id AND l.estado = 'activa' AND l.fin >= ?)
                      ORDER BY u.nombre", [$id, $paq['inicio']])->fetchAll(PDO::FETCH_COLUMN);
            $n = 0;
            foreach ($sin as $uid) {
                if ($n >= $libres) break;
                q("INSERT INTO licencias (usuario_id, origen, paquete_id, plan, inicio, fin, creado_por) VALUES (?, 'paquete', ?, 'otro', ?, ?, ?)", [$uid, $pid, max($paq['inicio'], date('Y-m-d')), $paq['fin'], $admin['id']]);
                $n++;
            }
            auditar('paquete_asignar', $x['nombre'] . " $n licencias");
            $resto = count($sin) - $n;
            aviso($n ? 'ok' : 'info', $n ? "Se activaron $n licencias." . ($resto > 0 ? " Quedaron $resto corredores sin cupo." : '') : 'No hay corredores sin licencia o no quedan cupos.');
        }
        redirigir(url('admin/firmas.php?id=' . $id));
    }
}

$err = fn($k) => isset($errores[$k]) ? '<span class="ayuda" style="color:var(--rojo-hondo)">' . h($errores[$k]) . '</span>' : '';
$inv = fn($k) => isset($errores[$k]) ? ' aria-invalid="true"' : '';

// ---------- Ficha de una firma ----------
if ($x || $nueva) {
    $paquetes = $id ? q("SELECT p.*, (SELECT COUNT(*) FROM licencias l WHERE l.paquete_id = p.id AND l.estado <> 'anulada') usados FROM paquetes p WHERE p.firma_id = ? ORDER BY p.fin DESC", [$id])->fetchAll() : [];
    $corredores = $id ? q("SELECT u.id, u.nombre, u.usuario, u.rol,
            (SELECT MAX(l.fin) FROM licencias l WHERE l.usuario_id = u.id AND l.estado = 'activa' AND CURDATE() BETWEEN l.inicio AND l.fin) fin_vig,
            (SELECT MAX(l.fin) FROM licencias l WHERE l.usuario_id = u.id AND l.estado = 'activa') fin_lic
            FROM usuarios u WHERE u.firma_id = ? AND u.activo = 1 ORDER BY u.nombre", [$id])->fetchAll() : [];
    $sinLic = count(array_filter($corredores, fn($c) => !$c['fin_vig'] && $c['rol'] !== 'admin'));

    pagina_inicio($x['nombre'] ?? 'Nueva firma', 'admin-firmas');
    echo '<p style="margin-top:6px"><a href="' . h(url('admin/firmas.php')) . '">← Firmas y cupos</a></p>';
    cabeza($id ? $x['nombre'] : 'Nueva firma', $id ? (count($corredores) . ' cuentas activas · ' . $sinLic . ' sin licencia vigente') : 'Una firma agrupa a sus corredores: comparten ranking y pueden recibir cupos de un paquete.');
    ?>
    <div class="tablero" style="margin-top:0;grid-template-columns:minmax(0,1fr) minmax(0,1.2fr)">
    <section class="panel">
      <div class="panel-cab"><h2>Datos de la firma</h2></div>
      <form method="post" class="panel-cuerpo" novalidate>
        <?= csrf_campo() ?><input type="hidden" name="accion" value="guardar">
        <div class="rejilla">
          <div class="campo" style="grid-column:1/-1"><label for="nombre">Nombre</label><input id="nombre" name="nombre" type="text" required value="<?= h($x['nombre'] ?? '') ?>"<?= $inv('nombre') ?>><?= $err('nombre') ?></div>
          <div class="campo"><label for="nit">NIT</label><input id="nit" name="nit" type="text" value="<?= h($x['nit'] ?? '') ?>" placeholder="830.094.283-1"></div>
          <div class="campo"><label for="contacto_nombre">Contacto</label><input id="contacto_nombre" name="contacto_nombre" type="text" value="<?= h($x['contacto_nombre'] ?? '') ?>"></div>
          <div class="campo"><label for="contacto_correo">Correo del contacto</label><input id="contacto_correo" name="contacto_correo" type="email" value="<?= h($x['contacto_correo'] ?? '') ?>"<?= $inv('contacto_correo') ?>><?= $err('contacto_correo') ?></div>
          <div class="campo"><label for="contacto_telefono">Teléfono</label><input id="contacto_telefono" name="contacto_telefono" type="text" value="<?= h($x['contacto_telefono'] ?? '') ?>"></div>
        </div>
        <label class="radio" style="margin-top:14px"><input type="checkbox" name="activa" value="1"<?= ($x['activa'] ?? 1) ? ' checked' : '' ?>><span>Firma activa</span></label>
        <div class="form-pie"><button class="btn <?= $id ? 'btn-tinta' : 'btn-rojo' ?>" type="submit"><?= $id ? 'Guardar cambios' : 'Crear firma' ?></button></div>
      </form>
    </section>
    <?php if ($id): ?>
    <section class="panel">
      <div class="panel-cab"><h2>Corredores</h2><a class="btn btn-chico" href="<?= h(url('admin/usuario.php')) ?>"><?= icono('mas') ?>Nuevo usuario</a></div>
      <?php if (!$corredores): ?><div class="vacio"><p>Aún no hay usuarios en esta firma.</p></div><?php else: ?>
      <div class="tabla-envoltura"><table class="tabla"><thead><tr><th>Nombre</th><th>Rol</th><th>Licencia</th></tr></thead><tbody>
        <?php foreach ($corredores as $c): ?><tr><td><a class="fila-enlace" href="<?= h(url('admin/usuario.php?id=' . (int)$c['id'])) ?>"><?= h($c['nombre']) ?></a><br><small><?= h($c['usuario']) ?></small></td><td><?= h(ROLES[$c['rol']]) ?></td><td><?= $c['fin_vig'] ? '<span class="chip chip-verde">Hasta ' . h(fmt_fecha($c['fin_vig'])) . '</span>' : ($c['rol'] === 'admin' ? '<span class="tenue">—</span>' : ($c['fin_lic'] && $c['fin_lic'] < date('Y-m-d') ? '<span class="chip chip-rojo">Vencida</span>' : '<span class="chip chip-gris">Sin licencia</span>')) ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
      <?php endif; ?>
    </section>
    <?php endif; ?>
    </div>

    <?php if ($id): ?>
    <section class="panel" style="margin-top:18px">
      <div class="panel-cab"><h2>Paquetes de cupos</h2><span class="tenue" style="font-size:13px">La firma paga N cupos; cada corredor ocupa uno</span></div>
      <?php if ($paquetes): ?>
      <div class="tabla-envoltura"><table class="tabla"><thead><tr><th>Periodo</th><th class="num">Cupos usados</th><th class="num">Valor</th><th></th></tr></thead><tbody>
        <?php foreach ($paquetes as $pq): $vigente = $pq['fin'] >= date('Y-m-d'); ?>
          <tr>
            <td><?= h(fmt_fecha($pq['inicio'])) ?> – <?= h(fmt_fecha($pq['fin'])) ?> <?= $vigente ? '' : '<span class="chip chip-gris">Vencido</span>' ?></td>
            <td class="num"><b><?= (int)$pq['usados'] ?></b> de <?= (int)$pq['cupos'] ?></td>
            <td class="num"><?= h(fmt_cop($pq['valor'])) ?></td>
            <td class="num"><?php if ($vigente && $pq['usados'] < $pq['cupos'] && $sinLic): ?>
              <form method="post" onsubmit="return confirm('¿Activar una licencia de este paquete para cada corredor sin licencia vigente?')"><?= csrf_campo() ?><input type="hidden" name="accion" value="asignar"><input type="hidden" name="paquete_id" value="<?= (int)$pq['id'] ?>"><button class="btn btn-chico btn-tinta" type="submit">Asignar a <?= min($sinLic, $pq['cupos'] - $pq['usados']) ?> sin licencia</button></form>
            <?php endif; ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody></table></div>
      <?php else: ?><div class="vacio"><p>Sin paquetes. Cuando la firma pague por sus corredores, regístrelo aquí.</p></div><?php endif; ?>
      <form method="post" class="panel-cuerpo" style="border-top:1px solid var(--linea)" novalidate>
        <h3 style="margin-bottom:12px">Nuevo paquete</h3>
        <?= csrf_campo() ?><input type="hidden" name="accion" value="paquete">
        <?php if ($errores): ?><div class="aviso aviso-error"><?= icono('alerta') ?><span>Revise los campos marcados.</span></div><?php endif; ?>
        <div class="rejilla">
          <div class="campo"><label for="cupos">Cupos</label><input id="cupos" name="cupos" type="number" min="1" max="5000" value="<?= h($_POST['cupos'] ?? '10') ?>"<?= $inv('cupos') ?>><?= $err('cupos') ?></div>
          <div class="campo"><label for="p_inicio">Inicio</label><input id="p_inicio" name="inicio" type="date" value="<?= h($_POST['inicio'] ?? date('Y-m-d')) ?>"<?= $inv('inicio') ?>><?= $err('inicio') ?></div>
          <div class="campo"><label for="p_fin">Fin</label><input id="p_fin" name="fin" type="date" value="<?= h($_POST['fin'] ?? sumar_meses(date('Y-m-d'), 12)) ?>"<?= $inv('fin') ?>><?= $err('fin') ?></div>
          <div class="campo"><label for="p_valor">Valor del paquete (COP)</label><input id="p_valor" name="valor" type="text" inputmode="numeric" value="<?= h($_POST['valor'] ?? '') ?>"></div>
          <div class="campo"><label for="p_medio">Medio de pago</label><select id="p_medio" name="medio"><?php foreach ($medios as $k => $t): ?><option value="<?= $k ?>"><?= $t ?></option><?php endforeach; ?></select></div>
          <div class="campo"><label for="p_ref">Referencia o factura</label><input id="p_ref" name="referencia" type="text" maxlength="120"></div>
        </div>
        <div class="campo" style="margin-top:14px"><label for="p_notas">Notas</label><input id="p_notas" name="notas" type="text" maxlength="500"></div>
        <label class="radio" style="margin-top:14px"><input type="checkbox" name="registrar_pago" value="1" checked><span>Registrar el pago hoy<small>Desmárquelo si la firma pagará después.</small></span></label>
        <div class="form-pie"><button class="btn btn-rojo" type="submit">Registrar paquete</button></div>
      </form>
    </section>
    <?php endif; ?>
    <?php
    pagina_fin();
    exit;
}

// ---------- Lista de firmas ----------
$firmas = q("SELECT f.*,
    (SELECT COUNT(*) FROM usuarios u WHERE u.firma_id = f.id AND u.activo = 1) cuentas,
    (SELECT COALESCE(SUM(p.cupos),0) FROM paquetes p WHERE p.firma_id = f.id AND p.fin >= CURDATE()) cupos,
    (SELECT COUNT(*) FROM licencias l JOIN paquetes p ON p.id = l.paquete_id WHERE p.firma_id = f.id AND p.fin >= CURDATE() AND l.estado <> 'anulada') usados,
    (SELECT COUNT(*) FROM intentos i WHERE i.firma_id = f.id AND i.creado_en >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) partidas30
    FROM firmas f ORDER BY f.activa DESC, f.nombre")->fetchAll();

pagina_inicio('Firmas y cupos', 'admin-firmas');
cabeza('Firmas y cupos', 'Firmas comisionistas, sus corredores y los paquetes que pagan.', '<a class="btn btn-rojo" href="?nueva=1">' . icono('mas') . 'Nueva firma</a>');
?>
<section class="panel">
  <?php if (!$firmas): ?>
    <div class="vacio"><p>Aún no hay firmas.</p><p>Cree la primera, por ejemplo Mercado y Bolsa S.A., para agrupar a sus corredores y su ranking.</p></div>
  <?php else: ?>
  <div class="tabla-envoltura"><table class="tabla">
    <thead><tr><th>Firma</th><th>NIT</th><th class="num">Cuentas activas</th><th class="num">Cupos vigentes</th><th class="num">Partidas 30 días</th><th>Estado</th></tr></thead>
    <tbody>
    <?php foreach ($firmas as $f): ?>
      <tr>
        <td><a class="fila-enlace" href="?id=<?= (int)$f['id'] ?>"><?= h($f['nombre']) ?></a><?= $f['contacto_nombre'] ? '<br><small>' . h($f['contacto_nombre']) . '</small>' : '' ?></td>
        <td><?= h($f['nit'] ?? '—') ?></td>
        <td class="num"><?= (int)$f['cuentas'] ?></td>
        <td class="num"><?= $f['cupos'] ? '<b>' . (int)$f['usados'] . '</b> de ' . (int)$f['cupos'] : '—' ?></td>
        <td class="num"><?= (int)$f['partidas30'] ?></td>
        <td><?= $f['activa'] ? '<span class="chip chip-verde">Activa</span>' : '<span class="chip chip-gris">Inactiva</span>' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>
<?php pagina_fin(); ?>
