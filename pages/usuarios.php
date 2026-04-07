<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
if ($_SESSION['rol'] !== 'superadmin') { include __DIR__ . '/403.php'; exit; }

$db    = getDB();
$cobro = cobroActivo();

// Todos los usuarios
$stmtU = $db->prepare("
    SELECT u.*,
           GROUP_CONCAT(uc.cobro_id) AS cobros_ids,
           (SELECT nombre FROM cobros WHERE id = uc2.cobro_id LIMIT 1) AS cobro_nombre
    FROM usuarios u
    LEFT JOIN usuario_cobro uc  ON uc.usuario_id = u.id
    LEFT JOIN usuario_cobro uc2 ON uc2.usuario_id = u.id AND uc2.cobro_id = ?
    GROUP BY u.id
    ORDER BY u.rol, u.nombre
");
$stmtU->execute([$cobro]);
$usuarios = $stmtU->fetchAll();

// Permisos del cobro activo por usuario
$stmtP = $db->prepare("
    SELECT uc.*, u.nombre AS usuario_nombre
    FROM usuario_cobro uc
    JOIN usuarios u ON u.id = uc.usuario_id
    WHERE uc.cobro_id = ?
    ORDER BY u.nombre
");
$stmtP->execute([$cobro]);
$permisosCobro = $stmtP->fetchAll();
$permisosPorUsuario = array_column($permisosCobro, null, 'usuario_id');

// Cobros disponibles
$stmtCobros = $db->query("SELECT id, nombre FROM cobros WHERE activo=1 ORDER BY nombre");
$cobros     = $stmtCobros->fetchAll();

$pageTitle   = 'Usuarios';
$pageSection = 'Usuarios';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header-row">
  <div>
    <h1>USUARIOS</h1>
    <p>// Gestión de accesos y permisos</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('modal-usuario')">+ Nuevo Usuario</button>
</div>

<!-- Lista de usuarios -->
<div class="card mb-2">
  <div class="card-header">
    <span class="card-title">TODOS LOS USUARIOS</span>
    <span class="text-mono text-xs text-muted"><?= count($usuarios) ?> registrados</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Último acceso</th><th>Estado</th><th>Acciones</th></tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u):
          $rolClass = match($u['rol']) {
            'superadmin' => 'badge-purple',
            'admin'      => 'badge-blue',
            'cobrador'   => 'badge-green',
            default      => 'badge-muted'
          };
          $esMismo = $u['id'] == $_SESSION['usuario_id'];
        ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:0.6rem">
              <div style="width:32px;height:32px;border-radius:50%;background:var(--surface);display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-size:0.9rem;border:1px solid var(--border)">
                <?= strtoupper(substr($u['nombre'],0,1)) ?>
              </div>
              <div>
                <div style="font-weight:600"><?= htmlspecialchars($u['nombre']) ?></div>
                <?php if ($esMismo): ?>
                <div style="font-size:0.65rem;color:var(--accent);font-family:var(--font-mono)">← TÚ</div>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td class="text-mono text-muted" style="font-size:0.78rem"><?= htmlspecialchars($u['email']) ?></td>
          <td><span class="badge <?= $rolClass ?>"><?= strtoupper($u['rol']) ?></span></td>
          <td class="text-mono text-muted" style="font-size:0.75rem">
            <?= $u['ultimo_login'] ? date('d M Y H:i', strtotime($u['ultimo_login'])) : '—' ?>
          </td>
          <td>
            <span class="badge <?= $u['activo'] ? 'badge-green' : 'badge-muted' ?>">
              <?= $u['activo'] ? 'ACTIVO' : 'INACTIVO' ?>
            </span>
          </td>
          <td>
            <div class="btn-group">
              <button class="btn btn-ghost btn-sm"
                onclick="editarUsuario(<?= htmlspecialchars(json_encode($u)) ?>)">
                Editar
              </button>
              <?php if ($u['rol'] !== 'superadmin'): ?>
              <button class="btn btn-ghost btn-sm"
                onclick="verPermisos(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['nombre'])) ?>')">
                Permisos
              </button>
              <?php if (!$esMismo): ?>
              <button class="btn btn-ghost btn-sm red"
                onclick="toggleActivo(<?= $u['id'] ?>, <?= $u['activo'] ?>)">
                <?= $u['activo'] ? 'Desactivar' : 'Activar' ?>
              </button>
              <?php endif; ?>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Permisos del cobro activo -->
<div class="card">
  <div class="card-header">
    <span class="card-title">PERMISOS EN ESTE COBRO</span>
  </div>
  <?php if (empty($permisosCobro)): ?>
  <div class="empty-state"><span class="empty-icon">◈</span><p>Sin usuarios asignados a este cobro</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Usuario</th>
          <th style="text-align:center">Dashboard</th>
          <th style="text-align:center">Deudores</th>
          <th style="text-align:center">Préstamos</th>
          <th style="text-align:center">Pagos</th>
          <th style="text-align:center">Capital</th>
          <th style="text-align:center">Crear deudor</th>
          <th style="text-align:center">Crear préstamo</th>
          <th style="text-align:center">Reg. pago</th>
          <th style="text-align:center">Anular pago</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($permisosCobro as $p): ?>
        <tr>
          <td><strong><?= htmlspecialchars($p['usuario_nombre']) ?></strong></td>
          <?php foreach ([
            'puede_ver_dashboard','puede_ver_deudores','puede_ver_prestamos',
            'puede_ver_pagos','puede_ver_capital',
            'puede_crear_deudor','puede_crear_prestamo',
            'puede_registrar_pago','puede_anular_pago'
          ] as $perm): ?>
          <td style="text-align:center">
            <span style="color:<?= ($p[$perm]??0) ? 'var(--accent)' : 'var(--border)' ?>;font-size:1rem">
              <?= ($p[$perm]??0) ? '✓' : '✗' ?>
            </span>
          </td>
          <?php endforeach; ?>
          <td>
            <button class="btn btn-ghost btn-sm"
              onclick="verPermisos(<?= $p['usuario_id'] ?>, '<?= htmlspecialchars(addslashes($p['usuario_nombre'])) ?>')">
              Editar
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- ====== MODAL USUARIO ====== -->
<div class="modal-overlay" id="modal-usuario">
  <div class="modal">
    <div class="modal-header">
      <h2 id="titulo-usuario">NUEVO USUARIO</h2>
      <button class="modal-close" onclick="closeModal('modal-usuario')">✕</button>
    </div>
    <div class="modal-body">
      <form id="form-usuario">
        <input type="hidden" name="id" id="u-id">
        <div class="form-grid">
          <div class="field">
            <label>Nombre completo <span class="required">*</span></label>
            <input type="text" name="nombre" id="u-nombre" required placeholder="Ej: Juan Pérez">
          </div>
          <div class="field">
            <label>Email <span class="required">*</span></label>
            <input type="email" name="email" id="u-email" required placeholder="correo@ejemplo.com">
          </div>
          <div class="field">
            <label id="label-password">Contraseña <span class="required">*</span></label>
            <input type="password" name="password" id="u-password" placeholder="Mínimo 6 caracteres">
            <small id="hint-password" style="color:var(--muted);font-size:0.68rem;display:none">Dejar en blanco para no cambiar</small>
          </div>
          <div class="field">
            <label>Rol</label>
            <select name="rol" id="u-rol">
              <option value="cobrador">Cobrador</option>
              <option value="admin">Admin</option>
              <option value="consulta">Solo consulta</option>
              <option value="superadmin">Superadmin</option>
            </select>
          </div>
          <div class="field field-span2" id="campo-cobros">
            <label>Asignar al cobro activo</label>
            <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-family:var(--font-mono);font-size:0.78rem">
              <input type="checkbox" name="asignar_cobro" id="u-asignar" value="1" checked>
              Asignar a cobro actual
            </label>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-usuario')">Cancelar</button>
      <button class="btn btn-primary" id="btn-usuario" onclick="guardarUsuario()">GUARDAR</button>
    </div>
  </div>
</div>

<!-- ====== MODAL PERMISOS ====== -->
<div class="modal-overlay" id="modal-permisos">
  <div class="modal">
    <div class="modal-header">
      <h2 id="titulo-permisos">PERMISOS</h2>
      <button class="modal-close" onclick="closeModal('modal-permisos')">✕</button>
    </div>
    <div class="modal-body">
      <form id="form-permisos">
        <input type="hidden" name="usuario_id" id="p-uid">
        <input type="hidden" name="cobro_id"   value="<?= $cobro ?>">
        <?php
        $gruposPermisos = [
          'VISTAS' => [
            'puede_ver_dashboard'     => 'Dashboard',
            'puede_ver_deudores'      => 'Ver deudores',
            'puede_ver_prestamos'     => 'Ver préstamos',
            'puede_ver_pagos'         => 'Ver pagos',
            'puede_ver_capital'       => 'Ver capital',
            'puede_ver_cuentas'       => 'Ver cuentas',
            'puede_ver_salidas'       => 'Ver salidas',
            'puede_ver_movimientos'   => 'Ver movimientos',
            'puede_ver_proyeccion'    => 'Ver proyección',
            'puede_ver_reportes'      => 'Ver reportes',
            'puede_ver_configuracion' => 'Configuración',
            'puede_ver_usuarios'      => 'Gestión usuarios',
            'puede_ver_cobros'        => 'Gestión cobros',
          ],
          'DEUDORES' => [
            'puede_crear_deudor'    => 'Crear deudor',
            'puede_editar_deudor'   => 'Editar deudor',
            'puede_eliminar_deudor' => 'Eliminar deudor',
          ],
          'PRÉSTAMOS' => [
            'puede_crear_prestamo'  => 'Crear préstamo',
            'puede_editar_prestamo' => 'Editar / Renovar / Refinanciar',
            'puede_anular_prestamo' => 'Anular préstamo',
          ],
          'PAGOS' => [
            'puede_registrar_pago'  => 'Registrar pago',
            'puede_anular_pago'     => 'Anular pago',
          ],
          'CAPITAL' => [
            'puede_crear_capitalista'            => 'Crear capitalista',
            'puede_editar_capitalista'           => 'Editar capitalista',
            'puede_registrar_movimiento_capital' => 'Registrar movimiento',
            'puede_ver_historial_capitalista'    => 'Ver historial capitalista',
            'puede_crear_cuenta'                 => 'Crear cuenta',
            'puede_editar_cuenta'                => 'Editar cuenta',
          ],
          'SALIDAS' => [
            'puede_crear_salida'    => 'Registrar salida',
            'puede_eliminar_salida' => 'Anular salida',
          ],
          'SISTEMA' => [
            'puede_exportar'        => 'Exportar datos (CSV)',
          ],
        ];
        ?>
        <div style="display:grid;gap:1.25rem;max-height:60vh;overflow-y:auto;padding-right:0.25rem">
          <?php foreach ($gruposPermisos as $grupo => $permisos): ?>
          <div>
            <div style="font-family:var(--font-mono);font-size:0.6rem;letter-spacing:2px;color:var(--accent);margin-bottom:0.5rem;padding-bottom:0.25rem;border-bottom:1px solid var(--border)"><?= $grupo ?></div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:0.4rem">
              <?php foreach ($permisos as $key => $label): ?>
              <label style="display:flex;align-items:center;gap:0.6rem;padding:0.5rem 0.75rem;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);cursor:pointer;font-size:0.8rem">
                <input type="checkbox" name="<?= $key ?>" id="perm-<?= $key ?>" value="1" style="width:16px;height:16px;cursor:pointer;flex-shrink:0">
                <span><?= $label ?></span>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-permisos')">Cancelar</button>
      <button class="btn btn-primary" id="btn-permisos" onclick="guardarPermisos()">GUARDAR PERMISOS</button>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
const permisosCobro = <?= json_encode($permisosPorUsuario) ?>;

function editarUsuario(u) {
    document.getElementById('titulo-usuario').textContent = 'EDITAR USUARIO';
    document.getElementById('u-id').value     = u.id;
    document.getElementById('u-nombre').value = u.nombre;
    document.getElementById('u-email').value  = u.email;
    document.getElementById('u-rol').value    = u.rol;
    document.getElementById('u-password').value = '';
    document.getElementById('label-password').innerHTML = 'Nueva contraseña';
    document.getElementById('hint-password').style.display = 'block';
    openModal('modal-usuario');
}

async function guardarUsuario() {
    var btn  = document.getElementById('btn-usuario');
    var data = Object.fromEntries(new FormData(document.getElementById('form-usuario')));
    if (!data.nombre || !data.email) { toast('Nombre y email son obligatorios', 'error'); return; }
    var esNuevo = !data.id;
    if (esNuevo && !data.password) { toast('La contraseña es obligatoria', 'error'); return; }
    if (data.password && data.password.length < 6) { toast('La contraseña debe tener al menos 6 caracteres', 'error'); return; }

    data.action = data.id ? 'editar' : 'crear';
    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>';
    var res = await apiPost('/api/usuarios.php', data);
    btn.disabled = false; btn.innerHTML = 'GUARDAR';
    if (res.ok) {
        toast(res.msg);
        closeModal('modal-usuario');
        setTimeout(() => location.reload(), 800);
    } else toast(res.msg || 'Error', 'error');
}

function verPermisos(uid, nombre) {
    document.getElementById('titulo-permisos').textContent = 'PERMISOS — ' + nombre.toUpperCase();
    document.getElementById('p-uid').value = uid;

    // Cargar permisos actuales si existen
    var p = permisosCobro[uid] || {};
    var todosPermisos = [
        'puede_ver_dashboard','puede_ver_deudores','puede_ver_prestamos',
        'puede_ver_pagos','puede_ver_capital','puede_ver_cuentas',
        'puede_ver_salidas','puede_ver_movimientos','puede_ver_proyeccion',
        'puede_ver_reportes','puede_ver_configuracion','puede_ver_usuarios','puede_ver_cobros',
        'puede_crear_deudor','puede_editar_deudor','puede_eliminar_deudor',
        'puede_crear_prestamo','puede_editar_prestamo','puede_anular_prestamo',
        'puede_registrar_pago','puede_anular_pago',
        'puede_crear_capitalista','puede_editar_capitalista',
        'puede_registrar_movimiento_capital','puede_ver_historial_capitalista',
        'puede_crear_cuenta','puede_editar_cuenta',
        'puede_crear_salida','puede_eliminar_salida',
        'puede_exportar'
    ];
    todosPermisos.forEach(function(key) {
        var el = document.getElementById('perm-' + key);
        if (el) el.checked = p[key] == 1;
    });
    openModal('modal-permisos');
}

async function guardarPermisos() {
    var btn  = document.getElementById('btn-permisos');
    var data = Object.fromEntries(new FormData(document.getElementById('form-permisos')));
    // checkboxes no marcados no aparecen en FormData, forzar a 0
    var todosPermisos = [
        'puede_ver_dashboard','puede_ver_deudores','puede_ver_prestamos',
        'puede_ver_pagos','puede_ver_capital','puede_ver_cuentas',
        'puede_ver_salidas','puede_ver_movimientos','puede_ver_proyeccion',
        'puede_ver_reportes','puede_ver_configuracion','puede_ver_usuarios','puede_ver_cobros',
        'puede_crear_deudor','puede_editar_deudor','puede_eliminar_deudor',
        'puede_crear_prestamo','puede_editar_prestamo','puede_anular_prestamo',
        'puede_registrar_pago','puede_anular_pago',
        'puede_crear_capitalista','puede_editar_capitalista',
        'puede_registrar_movimiento_capital','puede_ver_historial_capitalista',
        'puede_crear_cuenta','puede_editar_cuenta',
        'puede_crear_salida','puede_eliminar_salida',
        'puede_exportar'
    ];
    todosPermisos.forEach(function(key) {
        if (!data[key]) data[key] = '0';
    });
    data.action = 'permisos';
    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>';
    var res = await apiPost('/api/usuarios.php', data);
    btn.disabled = false; btn.innerHTML = 'GUARDAR PERMISOS';
    if (res.ok) {
        toast(res.msg);
        closeModal('modal-permisos');
        setTimeout(() => location.reload(), 800);
    } else toast(res.msg || 'Error', 'error');
}

async function toggleActivo(uid, activo) {
    var accion = activo ? 'desactivar' : 'activar';
    if (!confirm('¿' + (activo ? 'Desactivar' : 'Activar') + ' este usuario?')) return;
    var res = await apiPost('/api/usuarios.php', { action: accion, id: uid });
    if (res.ok) { toast(res.msg); setTimeout(() => location.reload(), 600); }
    else toast(res.msg || 'Error', 'error');
}
</script>