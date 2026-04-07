<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
if (!canDo('puede_ver_cuentas')) { include __DIR__ . '/403.php'; exit; }

$db    = getDB();
$cobro = cobroActivo();

$stmt = $db->prepare("SELECT * FROM v_saldo_cuentas WHERE cobro_id = ? ORDER BY nombre");
$stmt->execute([$cobro]);
$cuentas = $stmt->fetchAll();

$totalCaja = array_sum(array_column($cuentas, 'saldo_actual'));

$pageTitle   = 'Cuentas';
$pageSection = 'Cuentas';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header-row">
  <div>
    <h1>CUENTAS</h1>
    <p>// Cuadre de caja · Total: <?= fmt($totalCaja) ?></p>
  </div>
  <?php if (canDo('puede_crear_cuenta')): ?>
  <button class="btn btn-primary" onclick="openModal('modal-cuenta')">+ Nueva Cuenta</button>
  <?php endif; ?>
</div>

<div class="stats-grid" style="margin-bottom:1.75rem">
  <div class="stat-card">
    <div class="stat-label">Total en Caja</div>
    <div class="stat-value"><?= fmt($totalCaja) ?></div>
    <div class="stat-sub"><?= count($cuentas) ?> cuentas activas</div>
  </div>
  <?php foreach ($cuentas as $c): ?>
  <div class="stat-card <?= $c['saldo_actual'] < 0 ? 'red' : '' ?>">
    <div class="stat-label"><?= htmlspecialchars($c['nombre']) ?></div>
    <div class="stat-value" style="font-size:1.4rem"><?= fmt($c['saldo_actual']) ?></div>
    <div class="stat-sub"><?= ucfirst($c['tipo']) ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Detalle por cuenta -->
<?php if (empty($cuentas)): ?>
<div class="card">
  <div class="empty-state">
    <span class="empty-icon">◇</span>
    <p>No hay cuentas registradas. Crea una para empezar.</p>
  </div>
</div>
<?php else: ?>
<div class="card">
  <div class="card-header"><span class="card-title">DETALLE DE CUENTAS</span></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Cuenta</th><th>Tipo</th><th>Número</th><th>Titular</th><th>Entradas</th><th>Salidas</th><th>Saldo</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($cuentas as $c): ?>
        <tr>
          <td style="font-weight:600"><?= htmlspecialchars($c['nombre']) ?></td>
          <td><span class="badge badge-muted"><?= ucfirst($c['tipo']) ?></span></td>
          <td class="text-mono text-muted"><?= htmlspecialchars($c['numero'] ?? '—') ?></td>
          <td><?= htmlspecialchars($c['titular'] ?? '—') ?></td>
          <td class="green text-mono"><?= fmt($c['total_entradas']) ?></td>
          <td class="orange text-mono"><?= fmt($c['total_salidas']) ?></td>
          <td class="text-mono fw-600 <?= $c['saldo_actual'] < 0 ? 'red' : 'green' ?>"><?= fmt($c['saldo_actual']) ?></td>
          <td>
            <?php if (canDo('puede_editar_cuenta')): ?>
            <button class="btn btn-ghost btn-sm" onclick='editarCuenta(<?= json_encode($c) ?>)'>Editar</button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- MODAL NUEVA / EDITAR CUENTA -->
<div class="modal-overlay" id="modal-cuenta">
  <div class="modal">
    <div class="modal-header">
      <h2 id="titulo-cuenta">NUEVA CUENTA</h2>
      <button class="modal-close" onclick="closeModal('modal-cuenta')">✕</button>
    </div>
    <div class="modal-body">
      <form id="form-cuenta">
        <input type="hidden" name="id" id="cuenta-id">
        <div class="form-grid">
          <div class="field field-span2">
            <label>Nombre <span class="required">*</span></label>
            <input type="text" name="nombre" id="cuenta-nombre" placeholder="Ej: Nequi Mariana" required>
          </div>
          <div class="field">
            <label>Tipo</label>
            <select name="tipo" id="cuenta-tipo">
              <option value="efectivo">Efectivo</option>
              <option value="nequi">Nequi</option>
              <option value="bancolombia">Bancolombia</option>
              <option value="daviplata">Daviplata</option>
              <option value="transfiya">Transfiya</option>
              <option value="otro">Otro</option>
            </select>
          </div>
          <div class="field">
            <label>Número / Teléfono</label>
            <input type="text" name="numero" id="cuenta-numero" placeholder="Opcional">
          </div>
          <div class="field field-span2">
            <label>Titular</label>
            <input type="text" name="titular" id="cuenta-titular" placeholder="A nombre de">
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-cuenta')">Cancelar</button>
      <button class="btn btn-primary" id="btn-cuenta" onclick="guardarCuenta()">GUARDAR</button>
    </div>
  </div>
</div>

<?php
$extraScript = <<<JS
<script>
function editarCuenta(c) {
    document.getElementById('titulo-cuenta').textContent = 'EDITAR CUENTA';
    document.getElementById('cuenta-id').value      = c.cuenta_id;
    document.getElementById('cuenta-nombre').value  = c.nombre;
    document.getElementById('cuenta-tipo').value    = c.tipo;
    document.getElementById('cuenta-numero').value  = c.numero || '';
    document.getElementById('cuenta-titular').value = c.titular || '';
    openModal('modal-cuenta');
}
async function guardarCuenta() {
    const btn  = document.getElementById('btn-cuenta');
    const data = Object.fromEntries(new FormData(document.getElementById('form-cuenta')));
    if (!data.nombre?.trim()) { toast('El nombre es obligatorio', 'error'); return; }
    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>';
    const res = await apiPost('/api/capital.php', { action: 'cuenta', ...data });
    btn.disabled = false; btn.innerHTML = 'GUARDAR';
    if (res.ok) { toast(res.msg); closeModal('modal-cuenta'); setTimeout(()=>location.reload(),800); }
    else toast(res.msg || 'Error', 'error');
}
document.querySelector('[onclick="openModal(\'modal-cuenta\')"]')?.addEventListener('click', () => {
    document.getElementById('titulo-cuenta').textContent = 'NUEVA CUENTA';
    document.getElementById('form-cuenta').reset();
    document.getElementById('cuenta-id').value = '';
});
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>