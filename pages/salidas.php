<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
if (!canDo('puede_ver_salidas')) { include __DIR__ . '/403.php'; exit; }

$db    = getDB();
$cobro = cobroActivo();

$filtroTipo = $_GET['tipo']  ?? '';
$filtroMes  = $_GET['mes']   ?? date('Y-m');
$page       = max(1, (int)($_GET['page'] ?? 1));
$limit      = 20;
$offset     = ($page - 1) * $limit;

list($anio, $mes) = explode('-', $filtroMes);

$tiposSalida = ['prestamo','redito','retiro_capital','retiro','liquidacion','salida'];

$where  = ["m.cobro_id=?", "m.es_entrada=0", "m.tipo IN ('prestamo','redito','retiro_capital','retiro','liquidacion','salida')", 'YEAR(m.fecha)=?', 'MONTH(m.fecha)=?'];
$params = [$cobro, $anio, $mes];

if ($filtroTipo && in_array($filtroTipo, $tiposSalida)) {
    $where[]  = 'm.tipo=?';
    $params[] = $filtroTipo;
}
$whereSQL = implode(' AND ', $where);

$stmtTotal = $db->prepare("SELECT COUNT(*) FROM capital_movimientos m WHERE $whereSQL");
$stmtTotal->execute($params);
$totalPags = ceil($stmtTotal->fetchColumn() / $limit);

$stmt = $db->prepare("
    SELECT m.*, cap.nombre AS capitalista_nombre
    FROM capital_movimientos m
    LEFT JOIN capitalistas cap ON cap.id = m.capitalista_id
    WHERE $whereSQL
    ORDER BY m.fecha DESC, m.id DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$salidas = $stmt->fetchAll();

// Stats del mes
$stmtStats = $db->prepare("
    SELECT
        COALESCE(SUM(monto), 0) AS total_mes,
        COALESCE(SUM(CASE WHEN tipo='redito'         THEN monto ELSE 0 END),0) AS reditos,
        COALESCE(SUM(CASE WHEN tipo='salida'         THEN monto ELSE 0 END),0) AS gastos,
        COALESCE(SUM(CASE WHEN tipo='retiro_capital' THEN monto ELSE 0 END),0) AS devoluciones,
        COALESCE(SUM(CASE WHEN tipo='retiro'         THEN monto ELSE 0 END),0) AS retiros,
        COALESCE(SUM(CASE WHEN tipo='prestamo'       THEN monto ELSE 0 END),0) AS prestamos_salida
    FROM capital_movimientos
    WHERE cobro_id=? AND es_entrada=0
      AND tipo IN ('prestamo','redito','retiro_capital','retiro','liquidacion','salida')
      AND (anulado=0 OR anulado IS NULL)
      AND YEAR(fecha)=? AND MONTH(fecha)=?
");
$stmtStats->execute([$cobro, $anio, $mes]);
$stats = $stmtStats->fetch();

// Capitalistas con rédito pendiente
$stmtRedPend = $db->prepare("
    SELECT cap.id, cap.nombre, vs.saldo_actual
    FROM capitalistas cap
    JOIN v_saldo_capitalistas vs ON vs.capitalista_id = cap.id
    WHERE cap.cobro_id=? AND cap.tipo='prestado' AND cap.tasa_redito>0 AND cap.estado='activo'
");
$stmtRedPend->execute([$cobro]);
$reditosPend = $stmtRedPend->fetchAll();

$capitalistas = $db->prepare("SELECT id, nombre FROM capitalistas WHERE cobro_id=? AND estado='activo' ORDER BY nombre");
$capitalistas->execute([$cobro]); $capitalistas = $capitalistas->fetchAll();

$pageTitle   = 'Salidas';
$pageSection = 'Salidas';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header-row">
  <div>
    <h1>SALIDAS</h1>
    <p>// Gastos, réditos y devoluciones · <?= date('F Y', mktime(0,0,0,(int)$mes,1,(int)$anio)) ?></p>
  </div>
  <?php if (canDo('puede_crear_salida')): ?>
  <button class="btn btn-primary" onclick="openModal('modal-salida')">+ Nueva Salida</button>
  <?php endif; ?>
</div>

<?php if (!empty($reditosPend)): ?>
<div class="alert alert-warning mb-2" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
  <strong>Réditos por pagar:</strong>
  <?php foreach ($reditosPend as $r): ?>
  <span>
    <?= htmlspecialchars($r['nombre']) ?>
    <button class="btn btn-warning btn-sm" style="margin-left:0.4rem"
            onclick="pagarRedito(<?=$r['id']?>, '<?= htmlspecialchars(addslashes($r['nombre'])) ?>')">
      Registrar pago
    </button>
  </span>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:1.5rem">
  <div class="stat-card red">
    <div class="stat-label">Total Mes</div>
    <div class="stat-value" style="font-size:1.3rem"><?= fmt($stats['total_mes']) ?></div>
  </div>
  <div class="stat-card orange">
    <div class="stat-label">Réditos</div>
    <div class="stat-value" style="font-size:1.3rem"><?= fmt($stats['reditos']) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Gastos Op.</div>
    <div class="stat-value" style="font-size:1.3rem"><?= fmt($stats['gastos']) ?></div>
  </div>
  <div class="stat-card purple">
    <div class="stat-label">Devoluciones</div>
    <div class="stat-value" style="font-size:1.3rem"><?= fmt($stats['devoluciones']) ?></div>
  </div>
  <div class="stat-card blue">
    <div class="stat-label">Retiros Socio</div>
    <div class="stat-value" style="font-size:1.3rem"><?= fmt($stats['retiros']) ?></div>
  </div>
</div>

<!-- Filtros -->
<div class="filter-bar mb-2">
  <form method="GET" style="display:flex;gap:0.5rem;flex-wrap:wrap">
    <input type="month" name="mes" value="<?= htmlspecialchars($filtroMes) ?>" onchange="this.form.submit()">
    <select name="tipo" onchange="this.form.submit()">
      <option value="">Todos los tipos</option>
      <option value="prestamo"      <?= $filtroTipo==='prestamo'      ?'selected':'' ?>>Préstamos desembolsados</option>
      <option value="redito"        <?= $filtroTipo==='redito'        ?'selected':'' ?>>Réditos pagados</option>
      <option value="retiro_capital"<?= $filtroTipo==='retiro_capital'?'selected':'' ?>>Devoluciones capital</option>
      <option value="salida"        <?= $filtroTipo==='salida'        ?'selected':'' ?>>Gastos operativos</option>
      <option value="retiro"        <?= $filtroTipo==='retiro'        ?'selected':'' ?>>Retiros socio</option>
      <option value="liquidacion"   <?= $filtroTipo==='liquidacion'   ?'selected':'' ?>>Liquidaciones</option>
    </select>
    <?php if ($filtroTipo): ?>
    <a href="?mes=<?= $filtroMes ?>" class="btn btn-ghost">✕ Limpiar</a>
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
          <th>Fecha</th><th>Tipo</th><th>Descripción</th>
          <th>Capitalista</th><th>Método</th><th>Monto</th>
          <?php if (canDo('puede_eliminar_salida')): ?><th></th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php
        $tipoLabel = [
          'prestamo'     => ['badge-purple', 'Préstamo'],
          'redito'       => ['badge-orange', 'Rédito'],
          'retiro_capital'=> ['badge-blue',  'Devolución'],
          'salida'       => ['badge-muted',  'Gasto Op.'],
          'retiro'       => ['badge-green',  'Retiro Socio'],
          'liquidacion'  => ['badge-red',    'Liquidación'],
        ];
        foreach ($salidas as $s):
          [$cls, $lbl] = $tipoLabel[$s['tipo']] ?? ['badge-muted', $s['tipo']];
          $anulado = !empty($s['anulado']);
        ?>
        <tr style="<?= $anulado ? 'opacity:0.45;text-decoration:line-through' : '' ?>">
          <td class="text-mono"><?= date('d M Y', strtotime($s['fecha'])) ?></td>
          <td>
            <span class="badge <?= $cls ?>"><?= $lbl ?></span>
            <?= $anulado ? ' <span class="badge badge-muted" style="font-size:0.6rem">ANULADO</span>' : '' ?>
          </td>
          <td><?= htmlspecialchars($s['descripcion'] ?? '—') ?></td>
          <td><?= htmlspecialchars($s['capitalista_nombre'] ?? '—') ?></td>
          <td><span class="badge badge-muted"><?= ucfirst($s['metodo_pago'] ?? 'efectivo') ?></span></td>
          <td class="red text-mono fw-600"><?= fmt($s['monto']) ?></td>
          <?php if (canDo('puede_eliminar_salida')): ?>
          <td>
            <?php if (!$anulado): ?>
            <button class="btn btn-ghost btn-sm" onclick="eliminarSalida(<?= $s['id'] ?>)">✕</button>
            <?php endif; ?>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr style="background:var(--surface)">
          <td colspan="5" style="padding:0.65rem 1rem;font-family:var(--font-mono);font-size:0.72rem;color:var(--muted)">TOTAL</td>
          <td class="red text-mono fw-600" style="padding:0.65rem 1rem"><?= fmt($stats['total_mes']) ?></td>
          <?php if (canDo('puede_eliminar_salida')): ?><td></td><?php endif; ?>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php if ($totalPags > 1): ?>
  <div class="pagination">
    <?php for ($i=1;$i<=$totalPags;$i++): ?>
    <a href="?mes=<?=$filtroMes?>&tipo=<?=$filtroTipo?>&page=<?=$i?>"
       class="page-btn <?=$i==$page?'active':''?>"><?=$i?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- MODAL NUEVA SALIDA -->
<div class="modal-overlay" id="modal-salida">
  <div class="modal">
    <div class="modal-header">
      <h2>NUEVA SALIDA</h2>
      <button class="modal-close" onclick="closeModal('modal-salida')">&#10005;</button>
    </div>
    <div class="modal-body">
      <form id="form-salida">
        <div class="form-grid">
          <div class="field field-span2">
            <label>Tipo <span class="required">*</span></label>
            <select name="tipo" id="salida-tipo" onchange="toggleCamposSalida()" required>
              <option value="">— Seleccionar —</option>
              <option value="redito_capitalista">Rédito a capitalista</option>
              <option value="devolucion_capital">Devolución de capital</option>
              <option value="liquidacion">Liquidación total</option>
              <option value="gasto_operativo">Gasto operativo</option>
              <option value="retiro_socio">Retiro socio</option>
            </select>
          </div>
          <div class="field field-span2" id="campo-capitalista" style="display:none">
            <label>Capitalista</label>
            <select name="capitalista_id" id="salida-capitalista">
              <option value="">— Sin asignar —</option>
              <?php foreach ($capitalistas as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
              <?php endforeach; ?>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
function toggleCamposSalida() {
    var tipo   = document.getElementById('salida-tipo').value;
    var conCap = ['redito_capitalista','devolucion_capital','liquidacion'];
    document.getElementById('campo-capitalista').style.display = conCap.includes(tipo) ? 'block' : 'none';
}

function pagarRedito(capId, nombre) {
    document.getElementById('salida-tipo').value      = 'redito_capitalista';
    toggleCamposSalida();
    document.getElementById('salida-capitalista').value = capId;
    openModal('modal-salida');
}

async function guardarSalida() {
    var tipo  = document.getElementById('salida-tipo').value;
    var monto = document.getElementById('salida-monto').value;
    if (!tipo)  { toast('Selecciona el tipo', 'error'); return; }
    if (!monto || parseFloat(monto) <= 0) { toast('Ingresa el monto', 'error'); return; }

    var btn = document.getElementById('btn-salida');
    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Guardando...';

    var data    = Object.fromEntries(new FormData(document.getElementById('form-salida')));
    data.action = 'crear';
    var res     = await apiPost('/api/salidas.php', data);
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
</script>