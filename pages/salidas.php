<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
if (!canDo('puede_ver_salidas')) { include __DIR__ . '/403.php'; exit; }

$db    = getDB();
$cobro = cobroActivo();

// ── Cobros del usuario ─────────────────────────────────────
if ($_SESSION['rol'] === 'superadmin') {
    $stmtCobros = $db->query("SELECT id, nombre FROM cobros WHERE activo=1 ORDER BY nombre");
} else {
    $stmtCobros = $db->prepare("SELECT c.id, c.nombre FROM cobros c JOIN usuario_cobro uc ON uc.cobro_id=c.id WHERE uc.usuario_id=? AND c.activo=1 ORDER BY c.nombre");
    $stmtCobros->execute([$_SESSION['usuario_id']]);
}
$todosCobros = $stmtCobros->fetchAll();
$cobrosIds   = array_column($todosCobros, 'id');

// ── Tipos de salida personalizados (globales) ──────────────
$tiposSalidaCustom = $db->query("SELECT id, nombre FROM tipos_salida WHERE activo=1 ORDER BY nombre")->fetchAll();

// ── Filtros ────────────────────────────────────────────────
$filtroTipo  = $_GET['tipo']  ?? '';
$filtroMes   = $_GET['mes']   ?? date('Y-m');
$filtroCobro = (int)($_GET['cobro'] ?? 0);
$page        = max(1, (int)($_GET['page'] ?? 1));
$limit       = 20;
$offset      = ($page - 1) * $limit;

list($anio, $mes) = explode('-', $filtroMes);

// ── WHERE cobro ────────────────────────────────────────────
if ($filtroCobro > 0 && in_array($filtroCobro, $cobrosIds)) {
    $cobrosWhere  = 'm.cobro_id=?';
    $cobrosParams = [$filtroCobro];
    $statsWhere   = 'cobro_id=?';
    $statsParams  = [$filtroCobro];
} elseif ($_SESSION['rol'] === 'superadmin') {
    $cobrosWhere = $statsWhere = '1=1';
    $cobrosParams = $statsParams = [];
} else {
    $phs = implode(',', array_fill(0, count($cobrosIds), '?'));
    $cobrosWhere  = $phs ? "m.cobro_id IN ($phs)" : '1=0';
    $statsWhere   = $phs ? "cobro_id IN ($phs)"   : '1=0';
    $cobrosParams = $statsParams = $cobrosIds;
}

// Tipos fijos en capital_movimientos
$tiposFijos = ['prestamo','redito','retiro_capital','retiro','liquidacion','salida'];

$where  = [$cobrosWhere, "m.es_entrada=0",
           "m.tipo IN ('prestamo','redito','retiro_capital','retiro','liquidacion','salida')",
           'YEAR(m.fecha)=?', 'MONTH(m.fecha)=?'];
$params = array_merge($cobrosParams, [$anio, $mes]);

// Filtro tipo — fijos o custom (por tipo_salida_id)
if ($filtroTipo !== '') {
    if (in_array($filtroTipo, $tiposFijos)) {
        $where[]  = 'm.tipo=?';
        $params[] = $filtroTipo;
    } elseif (is_numeric($filtroTipo)) {
        $where[]  = 'm.tipo_salida_id=?';
        $params[] = (int)$filtroTipo;
    }
}
$whereSQL = implode(' AND ', $where);

$stmtTotal = $db->prepare("SELECT COUNT(*) FROM capital_movimientos m WHERE $whereSQL");
$stmtTotal->execute($params);
$totalPags = ceil($stmtTotal->fetchColumn() / $limit);

$stmt = $db->prepare("
    SELECT m.*, cap.nombre AS capitalista_nombre, co.nombre AS cobro_nombre,
           ts.nombre AS tipo_salida_nombre
    FROM capital_movimientos m
    LEFT JOIN capitalistas cap ON cap.id = m.capitalista_id
    JOIN cobros co ON co.id = m.cobro_id
    LEFT JOIN tipos_salida ts ON ts.id = m.tipo_salida_id
    WHERE $whereSQL
    ORDER BY m.fecha DESC, m.id DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$salidas = $stmt->fetchAll();

// ── Stats ──────────────────────────────────────────────────
$stmtStats = $db->prepare("
    SELECT
        COALESCE(SUM(monto), 0) AS total_mes,
        COALESCE(SUM(CASE WHEN tipo='redito'         THEN monto ELSE 0 END),0) AS reditos,
        COALESCE(SUM(CASE WHEN tipo='salida'         THEN monto ELSE 0 END),0) AS gastos,
        COALESCE(SUM(CASE WHEN tipo='retiro_capital' THEN monto ELSE 0 END),0) AS devoluciones,
        COALESCE(SUM(CASE WHEN tipo='retiro'         THEN monto ELSE 0 END),0) AS retiros
    FROM capital_movimientos
    WHERE $statsWhere AND es_entrada=0
      AND tipo IN ('prestamo','redito','retiro_capital','retiro','liquidacion','salida')
      AND (anulado=0 OR anulado IS NULL)
      AND YEAR(fecha)=? AND MONTH(fecha)=?
");
$stmtStats->execute(array_merge($statsParams, [$anio, $mes]));
$stats = $stmtStats->fetch();

// ── Capitalistas por cobro ─────────────────────────────────
$capPorCobro = [];
foreach ($todosCobros as $cb) {
    $s = $db->prepare("SELECT id, nombre FROM capitalistas WHERE cobro_id=? AND estado='activo' ORDER BY nombre");
    $s->execute([$cb['id']]);
    $capPorCobro[$cb['id']] = $s->fetchAll();
}
$capJson = json_encode($capPorCobro);

// Réditos pendientes
// WHERE para réditos — con alias cap
if ($filtroCobro > 0 && in_array($filtroCobro, $cobrosIds)) {
    $reditosWhere  = 'cap.cobro_id=?';
    $reditosParams = [$filtroCobro];
} elseif ($_SESSION['rol'] === 'superadmin') {
    $reditosWhere  = '1=1';
    $reditosParams = [];
} else {
    $phsR = implode(',', array_fill(0, count($cobrosIds), '?'));
    $reditosWhere  = $phsR ? "cap.cobro_id IN ($phsR)" : '1=0';
    $reditosParams = $cobrosIds;
}

$stmtRedPend = $db->prepare("
    SELECT cap.id, cap.nombre, cap.cobro_id, co.nombre AS cobro_nombre
    FROM capitalistas cap
    JOIN v_saldo_capitalistas vs ON vs.capitalista_id = cap.id
    JOIN cobros co ON co.id = cap.cobro_id
    WHERE cap.tipo='prestado' AND cap.tasa_redito>0 AND cap.estado='activo'
      AND $reditosWhere
");
$stmtRedPend->execute($reditosParams);
$reditosPend = $stmtRedPend->fetchAll();

$cobroModal = $filtroCobro > 0 ? $filtroCobro : ($cobrosIds[0] ?? 0);

$pageTitle   = 'Salidas';
$pageSection = 'Salidas';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header-row">
  <div>
    <h1>SALIDAS</h1>
    <p>// <?= date('F Y', mktime(0,0,0,(int)$mes,1,(int)$anio)) ?></p>
  </div>
  <div style="display:flex;gap:0.5rem">
    <?php if (canDo('puede_crear_salida')): ?>
    <button class="btn btn-primary" onclick="openModal('modal-salida')">+ Nueva Salida</button>
    <?php endif; ?>
    <?php if ($_SESSION['rol'] === 'superadmin' || $_SESSION['rol'] === 'admin'): ?>
    <button class="btn btn-ghost" onclick="openModal('modal-tipos-salida')">⚙ Tipos</button>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($reditosPend)): ?>
<div class="alert alert-warning mb-2" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
  <strong>Réditos por pagar:</strong>
  <?php foreach ($reditosPend as $r): ?>
  <span>
    <?= htmlspecialchars($r['nombre']) ?>
    <?php if (count($todosCobros) > 1): ?>
    <span class="text-xs text-muted">(<?= htmlspecialchars($r['cobro_nombre']) ?>)</span>
    <?php endif; ?>
    <button class="btn btn-warning btn-sm" style="margin-left:0.4rem"
            onclick="pagarRedito(<?=$r['id']?>, '<?= htmlspecialchars(addslashes($r['nombre'])) ?>', <?= $r['cobro_id'] ?>)">
      Registrar pago
    </button>
  </span>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Filtro por cobro -->
<?php if (count($todosCobros) > 1): ?>
<div class="filter-bar mb-2">
  <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap">
    <span style="font-family:var(--font-mono);font-size:0.72rem;color:var(--muted)">Cobro:</span>
    <a href="?mes=<?= $filtroMes ?>" class="btn btn-sm <?= !$filtroCobro ? 'btn-primary' : 'btn-ghost' ?>">Todos</a>
    <?php foreach ($todosCobros as $cb): ?>
    <a href="?cobro=<?= $cb['id'] ?>&mes=<?= $filtroMes ?>"
       class="btn btn-sm <?= $filtroCobro===$cb['id'] ? 'btn-primary' : 'btn-ghost' ?>">
      <?= htmlspecialchars($cb['nombre']) ?>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:1.5rem">
  <div class="stat-card red"><div class="stat-label">Total Mes</div><div class="stat-value" style="font-size:1.2rem"><?= fmt($stats['total_mes']) ?></div></div>
  <div class="stat-card orange"><div class="stat-label">Réditos</div><div class="stat-value" style="font-size:1.2rem"><?= fmt($stats['reditos']) ?></div></div>
  <div class="stat-card"><div class="stat-label">Gastos Op.</div><div class="stat-value" style="font-size:1.2rem"><?= fmt($stats['gastos']) ?></div></div>
  <div class="stat-card purple"><div class="stat-label">Devoluciones</div><div class="stat-value" style="font-size:1.2rem"><?= fmt($stats['devoluciones']) ?></div></div>
  <div class="stat-card blue"><div class="stat-label">Retiros Socio</div><div class="stat-value" style="font-size:1.2rem"><?= fmt($stats['retiros']) ?></div></div>
</div>

<!-- Filtros -->
<div class="filter-bar mb-2">
  <form method="GET" style="display:flex;gap:0.5rem;flex-wrap:wrap">
    <?php if ($filtroCobro): ?><input type="hidden" name="cobro" value="<?= $filtroCobro ?>"><?php endif; ?>
    <input type="month" name="mes" value="<?= htmlspecialchars($filtroMes) ?>" onchange="this.form.submit()">
    <select name="tipo" onchange="this.form.submit()">
      <option value="">Todos los tipos</option>
      <optgroup label="── Fijos ──">
        <option value="prestamo"       <?= $filtroTipo==='prestamo'      ?'selected':'' ?>>Préstamos desembolsados</option>
        <option value="redito"         <?= $filtroTipo==='redito'        ?'selected':'' ?>>Réditos pagados</option>
        <option value="retiro_capital" <?= $filtroTipo==='retiro_capital'?'selected':'' ?>>Devoluciones capital</option>
        <option value="liquidacion"    <?= $filtroTipo==='liquidacion'   ?'selected':'' ?>>Liquidaciones</option>
        <option value="retiro"         <?= $filtroTipo==='retiro'        ?'selected':'' ?>>Retiros socio</option>
      </optgroup>
      <?php if (!empty($tiposSalidaCustom)): ?>
      <optgroup label="── Personalizados ──">
        <?php foreach ($tiposSalidaCustom as $ts): ?>
        <option value="<?= $ts['id'] ?>" <?= $filtroTipo==(string)$ts['id']?'selected':'' ?>>
          <?= htmlspecialchars($ts['nombre']) ?>
        </option>
        <?php endforeach; ?>
      </optgroup>
      <?php endif; ?>
    </select>
    <?php if ($filtroTipo): ?>
    <a href="?mes=<?= $filtroMes ?><?= $filtroCobro ? '&cobro='.$filtroCobro : '' ?>" class="btn btn-ghost">✕ Limpiar</a>
    <?php endif; ?>
  </form>
</div>

<!-- Tabla -->
<div class="card">
  <?php if (empty($salidas)): ?>
    <div class="empty-state"><span class="empty-icon">▽</span><p>Sin salidas en este período</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Fecha</th><th>Tipo</th>
          <?php if (count($todosCobros) > 1): ?><th>Cobro</th><?php endif; ?>
          <th>Descripción</th><th>Capitalista</th><th>Método</th><th>Monto</th>
          <?php if (canDo('puede_eliminar_salida')): ?><th></th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php
        $tipoLabel = [
          'prestamo'      => ['badge-purple', 'Préstamo'],
          'redito'        => ['badge-orange', 'Rédito'],
          'retiro_capital'=> ['badge-blue',   'Devolución'],
          'salida'        => ['badge-muted',  'Gasto Op.'],
          'retiro'        => ['badge-green',  'Retiro Socio'],
          'liquidacion'   => ['badge-red',    'Liquidación'],
        ];
        foreach ($salidas as $s):
          $anulado = !empty($s['anulado']);
          // Si tiene tipo_salida_id mostrar el nombre personalizado
          if ($s['tipo_salida_id'] && $s['tipo_salida_nombre']) {
              $cls = 'badge-muted';
              $lbl = htmlspecialchars($s['tipo_salida_nombre']);
          } else {
              [$cls, $lbl] = $tipoLabel[$s['tipo']] ?? ['badge-muted', $s['tipo']];
          }
        ?>
        <tr style="<?= $anulado ? 'opacity:0.45;text-decoration:line-through' : '' ?>">
          <td class="text-mono"><?= date('d M Y', strtotime($s['fecha'])) ?></td>
          <td>
            <span class="badge <?= $cls ?>"><?= $lbl ?></span>
            <?= $anulado ? '<span class="badge badge-muted" style="font-size:0.6rem">ANULADO</span>' : '' ?>
          </td>
          <?php if (count($todosCobros) > 1): ?>
          <td class="text-xs text-muted"><?= htmlspecialchars($s['cobro_nombre']) ?></td>
          <?php endif; ?>
          <td><?= htmlspecialchars($s['descripcion'] ?? '—') ?></td>
          <td><?= htmlspecialchars($s['capitalista_nombre'] ?? '—') ?></td>
          <td><span class="badge badge-muted"><?= ucfirst($s['metodo_pago'] ?? 'efectivo') ?></span></td>
          <td class="red text-mono fw-600"><?= fmt($s['monto']) ?></td>
          <?php if (canDo('puede_eliminar_salida')): ?>
          <td><?php if (!$anulado): ?>
            <button class="btn btn-ghost btn-sm" onclick="eliminarSalida(<?= $s['id'] ?>)">✕</button>
          <?php endif; ?></td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="<?= 5 + (count($todosCobros)>1?1:0) ?>" style="padding:0.65rem 1rem;font-family:var(--font-mono);font-size:0.72rem;color:var(--muted)">TOTAL</td>
          <td class="red text-mono fw-600" style="padding:0.65rem 1rem"><?= fmt($stats['total_mes']) ?></td>
          <?php if (canDo('puede_eliminar_salida')): ?><td></td><?php endif; ?>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php if ($totalPags > 1): ?>
  <div class="pagination">
    <?php for ($i=1;$i<=$totalPags;$i++): ?>
    <a href="?mes=<?=$filtroMes?>&tipo=<?=$filtroTipo?>&cobro=<?=$filtroCobro?>&page=<?=$i?>"
       class="page-btn <?=$i==$page?'active':''?>"><?=$i?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- MODAL NUEVA SALIDA -->
<?php if (canDo('puede_crear_salida')): ?>
<div class="modal-overlay" id="modal-salida">
  <div class="modal">
    <div class="modal-header">
      <h2>NUEVA SALIDA</h2>
      <button class="modal-close" onclick="closeModal('modal-salida')">&#10005;</button>
    </div>
    <div class="modal-body">
      <form id="form-salida">
        <div class="form-grid">

          <!-- Cobro -->
          <div class="field field-span2">
            <label>Cobro <span class="required">*</span></label>
            <select name="cobro_id" id="salida-cobro" onchange="onSalidaCobroChange(this.value)">
              <?php foreach ($todosCobros as $cb): ?>
              <option value="<?= $cb['id'] ?>" <?= $cobroModal===$cb['id']?'selected':'' ?>>
                <?= htmlspecialchars($cb['nombre']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Tipo -->
          <div class="field field-span2">
            <label>Tipo <span class="required">*</span></label>
            <select name="tipo" id="salida-tipo" onchange="toggleCamposSalida()" required>
              <option value="">— Seleccionar —</option>
              <optgroup label="── Con capitalista ──">
                <option value="redito_capitalista">Rédito a capitalista</option>
                <option value="devolucion_capital">Devolución de capital</option>
                <option value="liquidacion">Liquidación total</option>
              </optgroup>
              <optgroup label="── Sin capitalista ──">
                <?php foreach ($tiposSalidaCustom as $ts): ?>
                <option value="custom_<?= $ts['id'] ?>"><?= htmlspecialchars($ts['nombre']) ?></option>
                <?php endforeach; ?>
              </optgroup>
            </select>
          </div>

          <!-- Capitalista (solo tipos con capitalista) -->
          <div class="field field-span2" id="campo-capitalista" style="display:none">
            <label>Capitalista</label>
            <select name="capitalista_id" id="salida-capitalista">
              <option value="">— Sin asignar —</option>
            </select>
          </div>

          <div class="field">
            <label>Monto <span class="required">*</span></label>
            <input type="number" name="monto" id="salida-monto" step="1000" min="1" required>
          </div>
          <div class="field">
            <label>Fecha</label>
            <input type="date" name="fecha" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="field">
            <label>Método de pago</label>
            <select name="metodo_pago">
              <option value="efectivo">Efectivo</option>
              <option value="banco">Banco</option>
            </select>
          </div>
          <div class="field field-span2">
            <label>Descripción</label>
            <input type="text" name="descripcion" placeholder="Detalle de la salida...">
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-salida')">Cancelar</button>
      <button class="btn btn-danger" id="btn-salida" onclick="guardarSalida()">REGISTRAR</button>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- MODAL TIPOS DE SALIDA -->
<?php if ($_SESSION['rol'] === 'superadmin' || $_SESSION['rol'] === 'admin'): ?>
<div class="modal-overlay" id="modal-tipos-salida">
  <div class="modal" style="max-width:520px">
    <div class="modal-header">
      <h2>TIPOS DE SALIDA</h2>
      <button class="modal-close" onclick="closeModal('modal-tipos-salida')">✕</button>
    </div>
    <div class="modal-body">
      <!-- Tipos fijos — solo info -->
      <div style="margin-bottom:1.25rem">
        <div style="font-family:var(--font-mono);font-size:0.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.5rem">
          Tipos fijos del sistema
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:0.4rem">
          <?php foreach (['Rédito a capitalista','Devolución de capital','Liquidación total'] as $tf): ?>
          <span class="badge badge-muted"><?= $tf ?></span>
          <?php endforeach; ?>
        </div>
        <div style="font-size:0.7rem;color:var(--muted);margin-top:0.4rem;font-family:var(--font-mono)">
          Estos tipos afectan saldos de capitalistas y no se pueden modificar.
        </div>
      </div>

      <div class="divider"></div>

      <!-- Tipos personalizados -->
      <div style="font-family:var(--font-mono);font-size:0.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin:0.75rem 0 0.5rem">
        Tipos personalizados
      </div>

      <?php if (empty($tiposSalidaCustom)): ?>
      <div class="empty-state" style="padding:1rem"><p>Sin tipos creados</p></div>
      <?php else: ?>
      <div style="margin-bottom:1rem">
        <?php foreach ($tiposSalidaCustom as $ts): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:0.5rem 0;border-bottom:1px solid var(--border)">
          <span style="font-weight:600"><?= htmlspecialchars($ts['nombre']) ?></span>
          <button class="btn btn-ghost btn-sm" style="color:var(--danger)"
                  onclick="eliminarTipoSalida(<?= $ts['id'] ?>, '<?= htmlspecialchars(addslashes($ts['nombre'])) ?>')">✕</button>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Crear nuevo tipo -->
      <div style="display:flex;gap:0.5rem;margin-top:0.75rem">
        <input type="text" id="nuevo-tipo-nombre" placeholder="Nombre del tipo..." style="flex:1">
        <button class="btn btn-primary" onclick="crearTipoSalida()">+ Agregar</button>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-tipos-salida')">Cerrar</button>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
var CAP_POR_COBRO   = <?= $capJson ?>;
var COBRO_DEFAULT   = <?= $cobroModal ?>;

document.addEventListener('DOMContentLoaded', function() {
    onSalidaCobroChange(COBRO_DEFAULT);
});

function onSalidaCobroChange(cobroId) {
    var sel  = document.getElementById('salida-capitalista');
    var caps = CAP_POR_COBRO[cobroId] || [];
    sel.innerHTML = '<option value="">— Sin asignar —</option>';
    caps.forEach(function(c) {
        var opt = document.createElement('option');
        opt.value = c.id; opt.textContent = c.nombre;
        sel.appendChild(opt);
    });
}

function toggleCamposSalida() {
    var tipo   = document.getElementById('salida-tipo').value;
    var conCap = ['redito_capitalista','devolucion_capital','liquidacion'];
    document.getElementById('campo-capitalista').style.display = conCap.includes(tipo) ? 'block' : 'none';
}

function pagarRedito(capId, nombre, cobroId) {
    document.getElementById('salida-cobro').value    = cobroId;
    onSalidaCobroChange(cobroId);
    document.getElementById('salida-tipo').value     = 'redito_capitalista';
    toggleCamposSalida();
    setTimeout(function() {
        document.getElementById('salida-capitalista').value = capId;
    }, 50);
    openModal('modal-salida');
}

async function guardarSalida() {
    var tipo  = document.getElementById('salida-tipo').value;
    var monto = document.getElementById('salida-monto').value;
    if (!tipo)  { toast('Selecciona el tipo', 'error'); return; }
    if (!monto || parseFloat(monto) <= 0) { toast('Ingresa el monto', 'error'); return; }

    var btn = document.getElementById('btn-salida');
    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Guardando...';

    var data = Object.fromEntries(new FormData(document.getElementById('form-salida')));
    data.action = 'crear';

    // Si es tipo custom extraer el id
    if (tipo.startsWith('custom_')) {
        data.tipo = 'gasto_operativo';
        data.tipo_salida_id = parseInt(tipo.replace('custom_', ''));
    }

    var res = await apiPost('/api/salidas.php', data);
    btn.disabled = false; btn.innerHTML = 'REGISTRAR';

    if (res.ok) {
        toast(res.msg || 'Salida registrada');
        closeModal('modal-salida');
        setTimeout(function() { location.reload(); }, 800);
    } else {
        toast(res.msg || 'Error', 'error');
    }
}

async function eliminarSalida(id) {
    if (!confirm('¿Eliminar esta salida?')) return;
    var res = await apiPost('/api/salidas.php', { action: 'eliminar', id: id });
    if (res.ok) { toast('Eliminada'); setTimeout(function(){ location.reload(); }, 600); }
    else toast(res.msg || 'Error', 'error');
}

async function crearTipoSalida() {
    var nombre = document.getElementById('nuevo-tipo-nombre').value.trim();
    if (!nombre) { toast('Ingresa el nombre', 'error'); return; }
    var res = await apiPost('/api/salidas.php', { action: 'crear_tipo', nombre });
    if (res.ok) { toast(res.msg); setTimeout(function(){ location.reload(); }, 600); }
    else toast(res.msg || 'Error', 'error');
}

async function eliminarTipoSalida(id, nombre) {
    if (!confirm('¿Eliminar el tipo "' + nombre + '"?')) return;
    var res = await apiPost('/api/salidas.php', { action: 'eliminar_tipo', id });
    if (res.ok) { toast(res.msg); setTimeout(function(){ location.reload(); }, 600); }
    else toast(res.msg || 'Error', 'error');
}
</script>