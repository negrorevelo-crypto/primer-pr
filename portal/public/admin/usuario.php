<?php
require dirname(__DIR__, 2) . '/app/bootstrap.php';
$admin = requiere_admin();

$id = isset($_GET['id']) && ctype_digit((string)$_GET['id']) ? (int)$_GET['id'] : 0;
$x = $id ? q('SELECT * FROM usuarios WHERE id = ?', [$id])->fetch() : null;
if ($id && !$x) { aviso('error', 'El usuario no existe.'); redirigir(url('admin/index.php')); }

$firmas = q('SELECT id, nombre FROM firmas WHERE activa = 1 OR id = ? ORDER BY nombre', [$x['firma_id'] ?? 0])->fetchAll();
$errores = [];
$medios = ['transferencia' => 'Transferencia', 'nequi' => 'Nequi', 'daviplata' => 'Daviplata', 'efectivo' => 'Efectivo', 'pasarela' => 'Pasarela en línea', 'otro' => 'Otro'];

function url_absoluta(string $ruta): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url($ruta);
}

function cupos_libres(int $paqueteId): int
{
    $p = q('SELECT cupos FROM paquetes WHERE id = ?', [$paqueteId])->fetchColumn();
    $usados = (int)q("SELECT COUNT(*) FROM licencias WHERE paquete_id = ? AND estado <> 'anulada'", [$paqueteId])->fetchColumn();
    return (int)$p - $usados;
}

if (es_post()) {
    csrf_verificar();
    $accion = (string)($_POST['accion'] ?? '');

    if ($accion === 'guardar') {
        $d = [
            'nombre' => trim((string)($_POST['nombre'] ?? '')),
            'usuario' => strtolower(trim((string)($_POST['usuario'] ?? ''))),
            'correo' => trim((string)($_POST['correo'] ?? '')),
            'telefono' => preg_replace('/[^\d+]/', '', (string)($_POST['telefono'] ?? '')),
            'firma_id' => ctype_digit((string)($_POST['firma_id'] ?? '')) ? (int)$_POST['firma_id'] : null,
            'rol' => array_key_exists($_POST['rol'] ?? '', ROLES) ? $_POST['rol'] : 'corredor',
            'activo' => isset($_POST['activo']) ? 1 : 0,
            'notas' => mb_substr(trim((string)($_POST['notas'] ?? '')), 0, 500),
        ];
        if ($d['nombre'] === '') $errores['nombre'] = 'Escriba el nombre.';
        if (!preg_match('/^[a-z0-9._-]{3,60}$/', $d['usuario'])) $errores['usuario'] = 'De 3 a 60 caracteres: letras minúsculas, números, punto o guion.';
        elseif (q('SELECT id FROM usuarios WHERE usuario = ? AND id <> ?', [$d['usuario'], $id])->fetch()) $errores['usuario'] = 'Ese usuario ya existe.';
        if ($d['correo'] !== '' && !filter_var($d['correo'], FILTER_VALIDATE_EMAIL)) $errores['correo'] = 'El correo no es válido.';
        if ($x && (int)$x['id'] === (int)$admin['id'] && ($d['rol'] !== 'admin' || !$d['activo'])) $errores['rol'] = 'No puede quitarse a sí mismo el rol de administrador ni desactivar su cuenta.';
        if ($d['rol'] === 'admin_firma' && !$d['firma_id']) $errores['firma_id'] = 'Un responsable de firma debe estar asociado a una firma.';

        if (!$errores) {
            if ($x) {
                q('UPDATE usuarios SET nombre=?, usuario=?, correo=?, telefono=?, firma_id=?, rol=?, activo=?, notas=? WHERE id=?',
                    [$d['nombre'], $d['usuario'], $d['correo'] ?: null, $d['telefono'] ?: null, $d['firma_id'], $d['rol'], $d['activo'], $d['notas'] ?: null, $id]);
                auditar('usuario_editar', $d['usuario']);
                aviso('ok', 'Datos guardados.');
                redirigir(url('admin/usuario.php?id=' . $id));
            }
            $clave = generar_clave();
            q('INSERT INTO usuarios (nombre, usuario, correo, telefono, firma_id, rol, activo, notas, clave_hash, debe_cambiar_clave) VALUES (?,?,?,?,?,?,?,?,?,1)',
                [$d['nombre'], $d['usuario'], $d['correo'] ?: null, $d['telefono'] ?: null, $d['firma_id'], $d['rol'], $d['activo'], $d['notas'] ?: null, password_hash($clave, PASSWORD_DEFAULT)]);
            $nuevo = (int)db()->lastInsertId();
            auditar('usuario_crear', $d['usuario']);
            $_SESSION['clave_temporal'] = ['id' => $nuevo, 'clave' => $clave];
            aviso('ok', 'Usuario creado. Copie la contraseña temporal: solo se muestra una vez.');
            redirigir(url('admin/usuario.php?id=' . $nuevo));
        }
        $x = array_merge($x ?? [], $d);
    }

    if ($x && $accion === 'clave') {
        $clave = generar_clave();
        q('UPDATE usuarios SET clave_hash = ?, debe_cambiar_clave = 1, intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?', [password_hash($clave, PASSWORD_DEFAULT), $id]);
        auditar('usuario_clave', $x['usuario']);
        $_SESSION['clave_temporal'] = ['id' => $id, 'clave' => $clave];
        aviso('ok', 'Nueva contraseña temporal generada. La anterior ya no funciona.');
        redirigir(url('admin/usuario.php?id=' . $id));
    }

    if ($x && $accion === 'licencia') {
        $origen = in_array($_POST['origen'] ?? '', ['individual', 'paquete', 'cortesia'], true) ? $_POST['origen'] : 'individual';
        $plan = array_key_exists($_POST['plan'] ?? '', PLANES) ? $_POST['plan'] : 'mensual';
        $inicio = (string)($_POST['inicio'] ?? '');
        $fin = (string)($_POST['fin'] ?? '');
        $paqueteId = null;
        if ($origen === 'paquete') {
            $paqueteId = ctype_digit((string)($_POST['paquete_id'] ?? '')) ? (int)$_POST['paquete_id'] : 0;
            $paq = $paqueteId ? q('SELECT * FROM paquetes WHERE id = ? AND firma_id = ?', [$paqueteId, (int)$x['firma_id']])->fetch() : null;
            if (!$paq) $errores['paquete_id'] = 'Elija un paquete de la firma del corredor.';
            elseif (cupos_libres($paqueteId) <= 0) $errores['paquete_id'] = 'Ese paquete no tiene cupos libres.';
            else { $inicio = $inicio ?: $paq['inicio']; $fin = $fin ?: $paq['fin']; $plan = 'otro'; }
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $inicio)) $errores['inicio'] = 'Fecha de inicio inválida.';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin) && !isset($errores['inicio']) && PLANES[$plan] > 0) $fin = sumar_meses($inicio, PLANES[$plan]);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin)) $errores['fin'] = 'Fecha de fin inválida.';
        elseif (!isset($errores['inicio']) && $fin < $inicio) $errores['fin'] = 'La fecha de fin es anterior al inicio.';

        $valor = trim((string)($_POST['valor'] ?? ''));
        $valorNum = $valor === '' ? null : (float)preg_replace('/[^\d]/', '', $valor);
        if ($origen === 'individual' && $valorNum === null && empty($_POST['sin_pago'])) $errores['valor'] = 'Registre el valor pagado o marque "Sin pago por ahora".';

        if (!$errores) {
            db()->beginTransaction();
            q('INSERT INTO licencias (usuario_id, origen, paquete_id, plan, inicio, fin, creado_por) VALUES (?,?,?,?,?,?,?)', [$id, $origen, $paqueteId, $plan, $inicio, $fin, $admin['id']]);
            $licId = (int)db()->lastInsertId();
            if ($valorNum) {
                $medio = array_key_exists($_POST['medio'] ?? '', $medios) ? $_POST['medio'] : 'transferencia';
                $fp = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_POST['fecha_pago'] ?? '')) ? $_POST['fecha_pago'] : date('Y-m-d');
                q('INSERT INTO pagos (usuario_id, firma_id, licencia_id, valor, medio, referencia, fecha_pago, registrado_por) VALUES (?,?,?,?,?,?,?,?)',
                    [$id, $x['firma_id'], $licId, $valorNum, $medio, mb_substr(trim((string)($_POST['referencia'] ?? '')), 0, 120) ?: null, $fp, $admin['id']]);
            }
            db()->commit();
            auditar('licencia_crear', $x['usuario'] . " $inicio a $fin ($origen)");
            aviso('ok', 'Licencia registrada del ' . fmt_fecha($inicio) . ' al ' . fmt_fecha($fin) . '.');
            redirigir(url('admin/usuario.php?id=' . $id . '#licencias'));
        }
    }

    if ($x && $accion === 'estado_licencia') {
        $lid = (int)($_POST['licencia_id'] ?? 0);
        $nuevo = in_array($_POST['estado'] ?? '', ['activa', 'suspendida', 'anulada'], true) ? $_POST['estado'] : null;
        if ($nuevo && q('UPDATE licencias SET estado = ? WHERE id = ? AND usuario_id = ?', [$nuevo, $lid, $id])->rowCount()) {
            auditar('licencia_estado', $x['usuario'] . " #$lid → $nuevo");
            aviso('ok', 'Licencia ' . ['activa' => 'reactivada', 'suspendida' => 'suspendida', 'anulada' => 'anulada'][$nuevo] . '.');
        }
        redirigir(url('admin/usuario.php?id=' . $id . '#licencias'));
    }
}

// ---------- Datos para la vista ----------
$claveTemp = null;
if (!empty($_SESSION['clave_temporal']) && (int)$_SESSION['clave_temporal']['id'] === $id) {
    $claveTemp = $_SESSION['clave_temporal']['clave'];
    unset($_SESSION['clave_temporal']);
}
$licencias = $x && $id ? q('SELECT l.*, p.inicio AS p_inicio FROM licencias l LEFT JOIN paquetes p ON p.id = l.paquete_id WHERE l.usuario_id = ? ORDER BY l.fin DESC', [$id])->fetchAll() : [];
$pagos = $x && $id ? q('SELECT * FROM pagos WHERE usuario_id = ? ORDER BY fecha_pago DESC, id DESC', [$id])->fetchAll() : [];
$cal = $x && $id ? ultima_calibracion($id) : null;
$intentos = $x && $id ? q('SELECT * FROM intentos WHERE usuario_id = ? ORDER BY id DESC LIMIT 10', [$id])->fetchAll() : [];
$vig = $x && $id ? licencia_vigente($id) : null;
$paquetes = ($x && $x['firma_id']) ? q('SELECT * FROM paquetes WHERE firma_id = ? AND fin >= CURDATE() ORDER BY fin DESC', [(int)$x['firma_id']])->fetchAll() : [];
$ultFin = $licencias ? max(array_column(array_filter($licencias, fn($l) => $l['estado'] === 'activa'), 'fin') ?: ['']) : '';
$inicioSug = $ultFin && $ultFin >= date('Y-m-d') ? date('Y-m-d', strtotime($ultFin . ' +1 day')) : date('Y-m-d');
$firmaNombre = null;
foreach ($firmas as $fi) if ((int)$fi['id'] === (int)($x['firma_id'] ?? 0)) $firmaNombre = $fi['nombre'];

$err = fn($k) => isset($errores[$k]) ? '<span class="ayuda" style="color:var(--rojo-hondo)">' . h($errores[$k]) . '</span>' : '';
$inv = fn($k) => isset($errores[$k]) ? ' aria-invalid="true"' : '';

$titulo = $id ? $x['nombre'] : 'Nuevo usuario';
pagina_inicio($titulo, 'admin-usuarios');
echo '<p style="margin:6px 0 14px"><a href="' . h(url('admin/index.php')) . '">← Usuarios</a></p>';
if ($id && $x['rol'] !== 'admin') {
    $lb = $vig ?: ($licencias[0] ?? null);
    if ($lb && $lb['estado'] === 'activa') {
        $fase = $vig ? 'abierta' : ($lb['inicio'] > date('Y-m-d') ? 'pre' : 'cierre');
        $ini = strtotime($lb['inicio']); $fn = strtotime($lb['fin'] . ' 23:59:59');
        echo banda($fase, $fase === 'abierta' ? (time() - $ini) / max(1, $fn - $ini) : ($fase === 'cierre' ? 1 : 0), ['Desde <b>' . h(fmt_fecha($lb['inicio'])) . '</b>', ($fase === 'cierre' ? 'Venció el ' : 'Vence ') . '<b>' . h(fmt_fecha($lb['fin'])) . '</b>']);
    } else {
        echo banda('pre', 0, ['Sin licencia activa: la rueda abre cuando registre el pago', '']);
    }
}
cabeza($titulo, $id ? h($x['usuario']) . ' · ' . h(ROLES[$x['rol']]) . ($firmaNombre ? ' · ' . h($firmaNombre) : ' · Individual') . ' · ' . estado_licencia_html($vig ?: ($licencias[0] ?? null)) : 'El sistema genera una contraseña temporal; el usuario la cambia al entrar.');

if ($errores && ($_POST['accion'] ?? '') !== 'guardar') {
    echo '<div class="aviso aviso-error">' . icono('alerta') . '<span>Revise el formulario de licencia: ' . h(implode(' ', $errores)) . '</span></div>';
}

if ($claveTemp):
    $msg = "Hola " . explode(' ', $x['nombre'])[0] . ", este es su acceso al entrenamiento de ofertas:\n\nEnlace: " . url_absoluta('login.php') . "\nUsuario: " . $x['usuario'] . "\nContraseña temporal: " . $claveTemp . "\n\nAl entrar le pedirá crear su propia contraseña.";
?>
<section class="panel" style="margin-bottom:18px;border-color:var(--laton-vivo)">
  <div class="panel-cab" style="background:var(--laton-tinte)"><h2>Contraseña temporal</h2><span class="tenue" style="font-size:13px">Solo se muestra esta vez</span></div>
  <div class="panel-cuerpo" style="display:grid;gap:14px">
    <div class="clave-mostrada"><code id="claveTxt"><?= h($claveTemp) ?></code><button class="btn btn-chico" type="button" data-copiar="#claveTxt"><?= icono('copiar') ?>Copiar</button></div>
    <div class="campo"><label for="msgTxt">Mensaje para enviar al usuario</label><textarea id="msgTxt" rows="7" readonly><?= h($msg) ?></textarea></div>
    <div class="fila">
      <button class="btn btn-tinta" type="button" data-copiar="#msgTxt"><?= icono('copiar') ?>Copiar mensaje</button>
      <?php if (!empty($x['telefono'])): $tel = ltrim($x['telefono'], '+'); if (strlen($tel) === 10) $tel = '57' . $tel; ?>
        <a class="btn" href="https://wa.me/<?= h($tel) ?>?text=<?= rawurlencode($msg) ?>" target="_blank" rel="noopener">Enviar por WhatsApp</a>
      <?php endif; ?>
      <?php if (!empty($x['correo'])): ?>
        <a class="btn" href="mailto:<?= h($x['correo']) ?>?subject=<?= rawurlencode('Su acceso al entrenamiento de ofertas') ?>&body=<?= rawurlencode($msg) ?>">Enviar por correo</a>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<div class="tablero" style="margin-top:0;grid-template-columns:minmax(0,1fr) minmax(0,1fr)">
<section class="panel">
  <div class="panel-cab"><h2>Datos de la cuenta</h2></div>
  <form method="post" class="panel-cuerpo" novalidate>
    <?= csrf_campo() ?><input type="hidden" name="accion" value="guardar">
    <?php if (($_POST['accion'] ?? '') === 'guardar' && $errores): ?><div class="aviso aviso-error"><?= icono('alerta') ?><span>Revise los campos marcados.</span></div><?php endif; ?>
    <div class="rejilla">
      <div class="campo"><label for="nombre">Nombre completo</label><input id="nombre" name="nombre" type="text" required value="<?= h($x['nombre'] ?? '') ?>"<?= $inv('nombre') ?>><?= $err('nombre') ?></div>
      <div class="campo"><label for="usuario">Usuario para entrar</label><input id="usuario" name="usuario" type="text" required autocapitalize="none" spellcheck="false" value="<?= h($x['usuario'] ?? '') ?>" placeholder="nombre.apellido"<?= $inv('usuario') ?>><?= $err('usuario') ?></div>
      <div class="campo"><label for="correo">Correo</label><input id="correo" name="correo" type="email" value="<?= h($x['correo'] ?? '') ?>"<?= $inv('correo') ?>><?= $err('correo') ?></div>
      <div class="campo"><label for="telefono">Celular (WhatsApp)</label><input id="telefono" name="telefono" type="text" inputmode="tel" value="<?= h($x['telefono'] ?? '') ?>" placeholder="3001234567"></div>
      <div class="campo"><label for="firma_id">Firma</label>
        <select id="firma_id" name="firma_id"<?= $inv('firma_id') ?>><option value="">Sin firma (individual)</option>
        <?php foreach ($firmas as $fi): ?><option value="<?= (int)$fi['id'] ?>"<?= (int)($x['firma_id'] ?? 0) === (int)$fi['id'] ? ' selected' : '' ?>><?= h($fi['nombre']) ?></option><?php endforeach; ?></select>
        <?= $err('firma_id') ?: '<span class="ayuda">Define con quién compite en el ranking.</span>' ?></div>
      <div class="campo"><label for="rol">Rol</label>
        <select id="rol" name="rol"<?= $inv('rol') ?>><?php foreach (ROLES as $k => $t): ?><option value="<?= $k ?>"<?= ($x['rol'] ?? 'corredor') === $k ? ' selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select>
        <?= $err('rol') ?: '<span class="ayuda">El responsable de firma ve los resultados de todos sus corredores.</span>' ?></div>
    </div>
    <div class="campo" style="margin-top:14px"><label for="notas">Notas internas</label><textarea id="notas" name="notas" maxlength="500"><?= h($x['notas'] ?? '') ?></textarea></div>
    <label class="radio" style="margin-top:14px"><input type="checkbox" name="activo" value="1"<?= ($x['activo'] ?? 1) ? ' checked' : '' ?>><span>Cuenta activa<small>Una cuenta desactivada no puede entrar, aunque tenga licencia.</small></span></label>
    <div class="form-pie"><button class="btn <?= $id ? 'btn-tinta' : 'btn-rojo' ?>" type="submit"><?= $id ? 'Guardar cambios' : 'Crear usuario' ?></button></div>
  </form>
</section>

<?php if ($id): ?>
<div class="lateral">
  <section class="panel">
    <div class="panel-cab"><h2>Acceso</h2></div>
    <div class="panel-cuerpo" style="display:grid;gap:12px">
      <p class="tenue" style="font-size:14px">Último acceso: <b><?= $x['ultimo_acceso'] ? h(fmt_fecha($x['ultimo_acceso'], true)) : 'nunca' ?></b><?= $x['debe_cambiar_clave'] ? ' · tiene pendiente crear su contraseña' : '' ?><?= $x['bloqueado_hasta'] && strtotime($x['bloqueado_hasta']) > time() ? ' · <b style="color:var(--rojo-hondo)">bloqueado por intentos fallidos</b>' : '' ?></p>
      <form method="post" onsubmit="return confirm('¿Generar una nueva contraseña temporal? La actual dejará de funcionar.')">
        <?= csrf_campo() ?><input type="hidden" name="accion" value="clave">
        <button class="btn" type="submit"><?= icono('llave') ?>Generar contraseña temporal</button>
      </form>
    </div>
  </section>
  <section class="panel">
    <div class="panel-cab"><h2>Calibración</h2></div>
    <div class="panel-cuerpo">
      <?php if (!$cal): ?>
        <p class="tenue">Aún no ha calibrado su pantalla.</p>
      <?php else: $caps = json_decode((string)$cal['capturas'], true) ?: []; ?>
        <p><b><?= h(str_replace('x', ' × ', (string)$cal['resolucion'])) ?></b> al <b><?= (int)$cal['escala'] ?> %</b> · <?= (int)$cal['monitores'] ?> monitor(es) · <?= $cal['modo'] === 'mi-puesto' ? 'ventanas de su puesto' : 'distribución de la grabación' ?></p>
        <p class="tenue" style="font-size:14px;margin-top:4px"><?= h(fmt_fecha($cal['creado_en'], true)) ?> · Prueba de ajuste: <?= $cal['encaja'] === null ? 'no la hizo' : ((int)$cal['encaja'] ? 'encaja' : '<b style="color:var(--rojo-hondo)">reportó problemas</b>') ?></p>
        <div class="fila" style="margin-top:10px">
          <a class="btn btn-chico" href="<?= h(url('admin/archivo.php?calibracion=' . (int)$cal['id'])) ?>" target="_blank">Ver perfil completo</a>
          <?php foreach ($caps as $i => $c): ?><a class="btn btn-chico" href="<?= h(url('admin/archivo.php?usuario=' . $id . '&captura=' . rawurlencode($c))) ?>" target="_blank">Captura <?= $i + 1 ?></a><?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</div>
</div>

<section class="panel" id="licencias" style="margin-top:18px">
  <div class="panel-cab"><h2>Licencias</h2><?= $vig ? '<span class="tenue" style="font-size:13px">Vigente hasta ' . h(fmt_fecha($vig['fin'])) . '</span>' : '' ?></div>
  <?php if ($licencias): ?>
  <div class="tabla-envoltura"><table class="tabla">
    <thead><tr><th>Periodo</th><th>Plan</th><th>Origen</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($licencias as $l):
      $hoy = date('Y-m-d');
      $est = $l['estado'] !== 'activa' ? [$l['estado'] === 'suspendida' ? 'Suspendida' : 'Anulada', 'chip-gris'] : ($l['fin'] < $hoy ? ['Vencida', 'chip-rojo'] : ($l['inicio'] > $hoy ? ['Programada', 'chip-laton'] : ['Vigente', 'chip-verde']));
    ?>
      <tr>
        <td><?= h(fmt_fecha($l['inicio'])) ?> – <?= h(fmt_fecha($l['fin'])) ?></td>
        <td><?= h(ucfirst($l['plan'])) ?></td>
        <td><?= ['individual' => 'Pago individual', 'paquete' => 'Paquete de la firma', 'cortesia' => 'Cortesía'][$l['origen']] ?></td>
        <td><span class="chip <?= $est[1] ?>"><?= $est[0] ?></span></td>
        <td class="num">
          <form method="post" class="fila" style="justify-content:flex-end">
            <?= csrf_campo() ?><input type="hidden" name="accion" value="estado_licencia"><input type="hidden" name="licencia_id" value="<?= (int)$l['id'] ?>">
            <?php if ($l['estado'] === 'activa'): ?>
              <button class="btn btn-sutil btn-chico" name="estado" value="suspendida">Suspender</button>
              <button class="btn btn-sutil btn-chico btn-peligro" name="estado" value="anulada" onclick="return confirm('¿Anular esta licencia? Si viene de un paquete, libera el cupo.')">Anular</button>
            <?php else: ?>
              <button class="btn btn-sutil btn-chico" name="estado" value="activa">Reactivar</button>
            <?php endif; ?>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php else: ?>
    <div class="vacio"><p>Sin licencias. Registre el pago y active el acceso aquí abajo.</p></div>
  <?php endif; ?>

  <form method="post" class="panel-cuerpo" style="border-top:1px solid var(--linea)" id="formLicencia" novalidate>
    <h3 style="margin-bottom:12px">Agregar licencia</h3>
    <?= csrf_campo() ?><input type="hidden" name="accion" value="licencia">
    <div class="rejilla">
      <div class="campo"><label for="origen">Origen</label>
        <select id="origen" name="origen">
          <option value="individual">Pago individual del corredor</option>
          <option value="paquete"<?= $paquetes ? '' : ' disabled' ?>>Cupo de un paquete de la firma<?= $paquetes ? '' : ' (no hay paquetes vigentes)' ?></option>
          <option value="cortesia">Cortesía (sin pago)</option>
        </select></div>
      <div class="campo" data-solo="paquete" hidden><label for="paquete_id">Paquete</label>
        <select id="paquete_id" name="paquete_id"<?= $inv('paquete_id') ?>>
        <?php foreach ($paquetes as $pq): $lib = cupos_libres((int)$pq['id']); ?>
          <option value="<?= (int)$pq['id'] ?>" data-inicio="<?= h($pq['inicio']) ?>" data-fin="<?= h($pq['fin']) ?>"<?= $lib <= 0 ? ' disabled' : '' ?>><?= h(fmt_fecha($pq['inicio'])) ?> – <?= h(fmt_fecha($pq['fin'])) ?> · <?= $lib ?> de <?= (int)$pq['cupos'] ?> cupos libres</option>
        <?php endforeach; ?></select><?= $err('paquete_id') ?></div>
      <div class="campo" data-no="paquete"><label for="plan">Plan</label>
        <select id="plan" name="plan"><?php foreach (PLANES as $k => $m): ?><option value="<?= $k ?>" data-meses="<?= $m ?>"><?= $k === 'otro' ? 'Otro periodo' : ucfirst($k) ?></option><?php endforeach; ?></select></div>
      <div class="campo"><label for="inicio">Inicio</label><input id="inicio" name="inicio" type="date" value="<?= h($inicioSug) ?>"<?= $inv('inicio') ?>><?= $err('inicio') ?></div>
      <div class="campo"><label for="fin">Fin</label><input id="fin" name="fin" type="date" value="<?= h(sumar_meses($inicioSug, 1)) ?>"<?= $inv('fin') ?>><?= $err('fin') ?: '<span class="ayuda">Se calcula con el plan; puede cambiarla.</span>' ?></div>
    </div>
    <div data-solo="individual">
      <h3 style="margin:18px 0 12px">Pago recibido</h3>
      <div class="rejilla">
        <div class="campo"><label for="valor">Valor (COP)</label><input id="valor" name="valor" type="text" inputmode="numeric" placeholder="150.000"<?= $inv('valor') ?>><?= $err('valor') ?></div>
        <div class="campo"><label for="medio">Medio</label><select id="medio" name="medio"><?php foreach ($medios as $k => $t): ?><option value="<?= $k ?>"><?= $t ?></option><?php endforeach; ?></select></div>
        <div class="campo"><label for="referencia">Referencia o comprobante</label><input id="referencia" name="referencia" type="text" maxlength="120"></div>
        <div class="campo"><label for="fecha_pago">Fecha del pago</label><input id="fecha_pago" name="fecha_pago" type="date" value="<?= date('Y-m-d') ?>"></div>
      </div>
      <label class="radio" style="margin-top:12px"><input type="checkbox" name="sin_pago" value="1"><span>Sin pago por ahora<small>Active la licencia y registre el pago después en Pagos.</small></span></label>
    </div>
    <div class="form-pie"><button class="btn btn-rojo" type="submit">Activar licencia</button></div>
  </form>
</section>

<div class="tablero" style="grid-template-columns:minmax(0,1fr) minmax(0,1fr)">
  <section class="panel">
    <div class="panel-cab"><h2>Pagos</h2></div>
    <?php if (!$pagos): ?><div class="vacio"><p>Sin pagos registrados.</p></div><?php else: ?>
    <div class="tabla-envoltura"><table class="tabla"><thead><tr><th>Fecha</th><th>Medio</th><th>Referencia</th><th class="num">Valor</th></tr></thead><tbody>
      <?php foreach ($pagos as $pg): ?><tr><td><?= h(fmt_fecha($pg['fecha_pago'])) ?></td><td><?= h($medios[$pg['medio']]) ?></td><td><?= h($pg['referencia'] ?? '—') ?></td><td class="num"><b><?= h(fmt_cop($pg['valor'])) ?></b></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
  </section>
  <section class="panel">
    <div class="panel-cab"><h2>Últimas partidas</h2></div>
    <?php if (!$intentos): ?><div class="vacio"><p>Aún no ha jugado.</p></div><?php else: ?>
    <div class="tabla-envoltura"><table class="tabla"><thead><tr><th>Fecha</th><th>Resultado</th><th class="num">Total</th><th class="num">Errores</th></tr></thead><tbody>
      <?php foreach ($intentos as $it): ?><tr><td><?= h(fmt_fecha($it['creado_en'], true)) ?></td><td><?= h(['lanzada' => 'Lanzada', 'cancelada' => 'Cancelada', 'agotada' => 'Tiempo agotado'][$it['resultado']]) ?></td><td class="num"><b><?= h(fmt_s($it['tiempo_total_ms'] !== null ? (int)$it['tiempo_total_ms'] : null)) ?></b></td><td class="num"><?= (int)$it['errores'] ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
  </section>
</div>
<?php else: ?>
</div>
<?php endif; ?>
<?php pagina_fin(['assets/admin.js']); ?>
