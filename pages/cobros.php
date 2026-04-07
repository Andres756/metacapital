<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
if ($_SESSION['rol'] !== 'superadmin') { include __DIR__ . '/403.php'; exit; }

$db = getDB();

// Cobros base — fetch explícito fila por fila
$stmtCobros = $db->query("SELECT id, nombre, descripcion, telefono, direccion, activo, created_at, updated_at FROM cobros ORDER BY activo DESC, nombre ASC");
$listaCobros = [];
while ($c = $stmtCobros->fetch(PDO::FETCH_ASSOC)) {
    $cid = (int)$c['id'];

    $s = $db->prepare("SELECT COUNT(*) FROM usuarios u JOIN usuario_cobro uc ON uc.usuario_id=u.id WHERE uc.cobro_id=? AND u.activo=1");
    $s->execute([$cid]); $c['num_usuarios'] = (int)$s->fetchColumn();

    $s = $db->prepare("SELECT COUNT(*) FROM deudores d JOIN deudor_cobro dc ON dc.deudor_id=d.id WHERE dc.cobro_id=? AND d.activo=1");
    $s->execute([$cid]); $c['num_deudores'] = (int)$s->fetchColumn();

    $s = $db->prepare("SELECT COUNT(*) FROM prestamos WHERE cobro_id=? AND estado='activo'");
    $s->execute([$cid]); $c['prestamos_activos'] = (int)$s->fetchColumn();

    $s = $db->prepare("SELECT COUNT(*) FROM prestamos WHERE cobro_id=? AND estado='en_mora'");
    $s->execute([$cid]); $c['prestamos_mora'] = (int)$s->fetchColumn();

    $s = $db->prepare("SELECT COALESCE(SUM(saldo_pendiente),0) FROM prestamos WHERE cobro_id=? AND estado NOT IN ('pagado','renovado','refinanciado','anulado')");
    $s->execute([$cid]); $c['cartera_total'] = (float)$s->fetchColumn();

    $s = $db->prepare("SELECT COALESCE(SUM(CASE WHEN es_entrada=1 THEN monto ELSE -monto END),0) FROM capital_movimientos WHERE cobro_id=? AND anulado=0 AND tipo != 'prestamo_proporcional'");
    $s->execute([$cid]); $c['capital_disponible'] = (float)$s->fetchColumn();

    $listaCobros[] = $c;
}

$cobroActual = cobroActivo();


$pageTitle   = 'Cobros';
$pageSection = 'Cobros';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header-row">
  <div>
    <h1>COBROS</h1>
    <p>// Gestión de todos los cobros del sistema</p>
  </div>
  <button class="btn btn-primary" onclick="nuevoCobro()">+ Nuevo Cobro</button>
</div>

<!-- Stats globales -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:1.5rem">
  <div class="stat-card purple">
    <div class="stat-label">Total Cobros</div>
    <div class="stat-value"><?= count($listaCobros) ?></div>
  </div>
  <div class="stat-card green">
    <div class="stat-label">Activos</div>
    <div class="stat-value"><?= count(array_filter($listaCobros, fn($cobro) => ($cobro['activo'] ?? 0))) ?></div>
  </div>
  <div class="stat-card orange">
    <div class="stat-label">Cartera Total</div>
    <div class="stat-value" style="font-size:1.2rem"><?= fmt(array_sum(array_column($listaCobros,'cartera_total'))) ?></div>
  </div>
  <div class="stat-card blue">
    <div class="stat-label">Capital Global</div>
    <div class="stat-value" style="font-size:1.2rem"><?= fmt(array_sum(array_column($listaCobros,'capital_disponible'))) ?></div>
  </div>
</div>

<!-- Lista de cobros -->
<div style="display:grid;gap:1rem">
  <?php foreach ($listaCobros as $cobro):
    $esActual = $cobro['id'] == $cobroActual;
  ?>
  <div class="card" style="border-color:<?= $esActual ? 'var(--accent)' : 'var(--border)' ?>">
    <div style="padding:1.25rem;display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap">

      <!-- Indicador + nombre -->
      <div style="display:flex;align-items:center;gap:0.75rem;flex:1;min-width:200px">
        <div style="width:12px;height:12px;border-radius:50%;background:<?= ($cobro['activo'] ?? 0) ? 'var(--accent)' : 'var(--border)' ?>;flex-shrink:0"></div>
        <div>
          <div style="font-family:var(--font-display);font-size:1.1rem;font-weight:700">
            <?= htmlspecialchars($cobro['nombre']) ?>
            <?php if ($esActual): ?>
            <span class="badge badge-green" style="margin-left:0.5rem;font-size:0.6rem">ACTIVO AHORA</span>
            <?php endif; ?>
          </div>
          <?php if ($cobro['descripcion']): ?>
          <div style="font-size:0.75rem;color:var(--muted);font-family:var(--font-mono);margin-top:0.1rem">
            <?= htmlspecialchars($cobro['descripcion']) ?>
          </div>
          <?php endif; ?>
          <div style="font-size:0.65rem;color:var(--muted);font-family:var(--font-mono);margin-top:0.2rem">
            Creado <?= isset($cobro['created_at']) ? date('d M Y', strtotime($cobro['created_at'])) : '—' ?>
            <?php if (!empty($cobro['telefono'])): ?> · <?= htmlspecialchars($cobro['telefono']) ?><?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Stats del cobro -->
      <div style="display:flex;gap:1.5rem;flex-wrap:wrap">
        <div style="text-align:center">
          <div style="font-family:var(--font-mono);font-size:0.6rem;color:var(--muted);text-transform:uppercase;margin-bottom:2px">Usuarios</div>
          <div style="font-family:var(--font-display);font-size:1.1rem"><?= ($cobro['num_usuarios'] ?? 0) ?></div>
        </div>
        <div style="text-align:center">
          <div style="font-family:var(--font-mono);font-size:0.6rem;color:var(--muted);text-transform:uppercase;margin-bottom:2px">Deudores</div>
          <div style="font-family:var(--font-display);font-size:1.1rem"><?= ($cobro['num_deudores'] ?? 0) ?></div>
        </div>
        <div style="text-align:center">
          <div style="font-family:var(--font-mono);font-size:0.6rem;color:var(--muted);text-transform:uppercase;margin-bottom:2px">Activos</div>
          <div style="font-family:var(--font-display);font-size:1.1rem;color:var(--accent)"><?= ($cobro['prestamos_activos'] ?? 0) ?></div>
        </div>
        <div style="text-align:center">
          <div style="font-family:var(--font-mono);font-size:0.6rem;color:var(--muted);text-transform:uppercase;margin-bottom:2px">En mora</div>
          <div style="font-family:var(--font-display);font-size:1.1rem;color:<?= (($cobro['prestamos_mora'] ?? 0) ?? 0)>0?'var(--orange)':'var(--muted)' ?>"><?= ($cobro['prestamos_mora'] ?? 0) ?></div>
        </div>
        <div style="text-align:center">
          <div style="font-family:var(--font-mono);font-size:0.6rem;color:var(--muted);text-transform:uppercase;margin-bottom:2px">Cartera</div>
          <div style="font-family:var(--font-display);font-size:1rem;color:var(--orange)"><?= fmt($cobro['cartera_total'] ?? 0) ?></div>
        </div>
        <div style="text-align:center">
          <div style="font-family:var(--font-mono);font-size:0.6rem;color:var(--muted);text-transform:uppercase;margin-bottom:2px">Capital</div>
          <div style="font-family:var(--font-display);font-size:1rem;color:var(--accent)"><?= fmt($cobro['capital_disponible'] ?? 0) ?></div>
        </div>
      </div>

      <!-- Acciones -->
      <div class="btn-group">
        <?php if (!$esActual): ?>
        <button class="btn btn-primary btn-sm" onclick="cambiarCobro(<?= $cobro['id'] ?>)">
          Entrar
        </button>
        <?php endif; ?>
        <button class="btn btn-ghost btn-sm" onclick="editarCobro(<?= htmlspecialchars(json_encode($cobro)) ?>)">
          Editar
        </button>
        <?php if (($cobro['activo'] ?? 0) && !$esActual): ?>
        <button class="btn btn-ghost btn-sm red" onclick="toggleCobro(<?= $cobro['id'] ?>, 0)">
          Desactivar
        </button>
        <?php elseif (!($cobro['activo'] ?? 0)): ?>
        <button class="btn btn-ghost btn-sm" onclick="toggleCobro(<?= $cobro['id'] ?>, 1)">
          Activar
        </button>
        <?php endif; ?>
      </div>

    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- MODAL COBRO -->
<div class="modal-overlay" id="modal-form-cobro">
  <div class="modal">
    <div class="modal-header">
      <h2 id="titulo-cobro">NUEVO COBRO</h2>
      <button class="modal-close" onclick="closeModal('modal-form-cobro')">✕</button>
    </div>
    <div class="modal-body">
      <form id="form-cobro">
        <input type="hidden" name="id" id="c-id">
        <div class="form-grid">
          <div class="field field-span2">
            <label>Nombre <span class="required">*</span></label>
            <input type="text" name="nombre" id="c-nombre" required placeholder="Ej: Cobro Zona Norte">
          </div>
          <div class="field field-span2">
            <label>Descripción</label>
            <input type="text" name="descripcion" id="c-descripcion" placeholder="Opcional">
          </div>
          <div class="field">
            <label>Teléfono</label>
            <input type="text" name="telefono" id="c-telefono" placeholder="3001234567">
          </div>
          <div class="field">
            <label>Dirección</label>
            <input type="text" name="direccion" id="c-direccion" placeholder="Calle 1 # 2-3">
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-form-cobro')">Cancelar</button>
      <button class="btn btn-primary" id="btn-cobro" onclick="guardarCobro()">GUARDAR</button>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
function nuevoCobro() {
    document.getElementById('titulo-cobro').textContent = 'NUEVO COBRO';
    document.getElementById('c-id').value          = '';
    document.getElementById('c-nombre').value      = '';
    document.getElementById('c-descripcion').value = '';
    document.getElementById('c-telefono').value    = '';
    document.getElementById('c-direccion').value   = '';
    openModal('modal-form-cobro');
}

function editarCobro(c) {
    document.getElementById('titulo-cobro').textContent = 'EDITAR COBRO';
    document.getElementById('c-id').value          = c.id;
    document.getElementById('c-nombre').value      = c.nombre;
    document.getElementById('c-descripcion').value = c.descripcion || '';
    document.getElementById('c-telefono').value    = c.telefono   || '';
    document.getElementById('c-direccion').value   = c.direccion  || '';
    openModal('modal-form-cobro');
}

async function guardarCobro() {
    var btn  = document.getElementById('btn-cobro');
    var data = Object.fromEntries(new FormData(document.getElementById('form-cobro')));
    if (!data.nombre) { toast('El nombre es obligatorio', 'error'); return; }
    // Eliminar id del objeto si está vacío para que no interfiera
    if (!data.id) delete data.id;
    data.action = data.id ? 'editar_cobro' : 'crear_cobro';
    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>';
    var res = await apiPost('/api/configuracion.php', data);
    btn.disabled = false; btn.innerHTML = 'GUARDAR';
    if (res.ok) {
        toast(res.msg);
        closeModal('modal-form-cobro');
        setTimeout(() => location.reload(), 800);
    } else toast(res.msg || 'Error', 'error');
}

async function cambiarCobro(id) {
    var res = await apiPost('/api/configuracion.php', { action: 'cambiar_cobro', cobro_id: id });
    if (res.ok) { toast('Entrando al cobro...'); setTimeout(() => window.location.href='/pages/dashboard.php', 600); }
    else toast(res.msg || 'Error', 'error');
}

async function toggleCobro(id, activo) {
    if (!confirm((activo ? '¿Activar' : '¿Desactivar') + ' este cobro?')) return;
    var res = await apiPost('/api/configuracion.php', { action: 'toggle_cobro', id: id, activo: activo });
    if (res.ok) { toast(res.msg); setTimeout(() => location.reload(), 600); }
    else toast(res.msg || 'Error', 'error');
}
</script>