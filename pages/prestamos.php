<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
if (!canDo('puede_ver_prestamos')) { include __DIR__ . '/403.php'; exit; }

$db    = getDB();
$cobro = cobroActivo();

$buscar  = trim($_GET['q'] ?? '');
$filtro  = $_GET['filtro'] ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$limit   = 15;
$offset  = ($page - 1) * $limit;

$where  = ['p.cobro_id = ?'];
$params = [$cobro];

if ($buscar) {
    $where[]  = '(d.nombre LIKE ? OR p.id = ?)';
    $params[] = "%$buscar%";
    $params[] = (int)$buscar;
}

$filtroEstados = [
    'activo'   => ['activo'],
    'mora'     => ['en_mora'],
    'acuerdo'  => ['en_acuerdo'],
    'vencido'  => ['en_mora','en_acuerdo'],
    'pagado'   => ['pagado'],
    'inactivo' => ['renovado','refinanciado','incobrable'],
];
if (isset($filtroEstados[$filtro])) {
    $placeholders = implode(',', array_fill(0, count($filtroEstados[$filtro]), '?'));
    $where[]  = "p.estado IN ($placeholders)";
    $params   = array_merge($params, $filtroEstados[$filtro]);
} else {
    $where[] = "p.estado NOT IN ('renovado','refinanciado')";
}

$whereSQL = implode(' AND ', $where);

$stmtTotal = $db->prepare("SELECT COUNT(*) FROM prestamos p JOIN deudores d ON d.id=p.deudor_id WHERE $whereSQL");
$stmtTotal->execute($params);
$total     = $stmtTotal->fetchColumn();
$totalPags = ceil($total / $limit);

$sql = "
    SELECT p.*, d.nombre AS deudor_nombre, d.telefono AS deudor_tel,
        (SELECT COUNT(*) FROM cuotas WHERE prestamo_id=p.id AND estado='pagado') AS cuotas_pagadas,
        (SELECT COUNT(*) FROM cuotas WHERE prestamo_id=p.id) AS cuotas_total,
        (SELECT MIN(fecha_vencimiento) FROM cuotas WHERE prestamo_id=p.id AND estado IN ('pendiente','parcial')) AS proxima_cuota
    FROM prestamos p
    JOIN deudores d ON d.id=p.deudor_id
    WHERE $whereSQL
    ORDER BY CASE p.estado WHEN 'en_mora' THEN 1 WHEN 'en_acuerdo' THEN 2 WHEN 'activo' THEN 3 ELSE 4 END, p.updated_at DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$prestamos = $stmt->fetchAll();

$stmtS = $db->prepare("SELECT
    SUM(CASE WHEN estado='activo'     THEN 1 ELSE 0 END) AS activos,
    SUM(CASE WHEN estado='en_mora'    THEN 1 ELSE 0 END) AS en_mora,
    SUM(CASE WHEN estado='en_acuerdo' THEN 1 ELSE 0 END) AS en_acuerdo,
    SUM(CASE WHEN estado='pagado'     THEN 1 ELSE 0 END) AS pagados,
    SUM(CASE WHEN estado NOT IN ('renovado','refinanciado') THEN saldo_pendiente ELSE 0 END) AS total_saldo
    FROM prestamos WHERE cobro_id=?");
$stmtS->execute([$cobro]);
$stats = $stmtS->fetch();

$deudoresQ = $db->prepare("SELECT d.id, d.nombre FROM deudores d JOIN deudor_cobro dc ON dc.deudor_id=d.id WHERE dc.cobro_id=? AND d.activo=1 ORDER BY d.nombre");
$deudoresQ->execute([$cobro]);
$deudores = $deudoresQ->fetchAll();

$deudorPre = (int)($_GET['deudor'] ?? 0);
$action    = $_GET['action'] ?? '';

$pageTitle   = 'Prestamos';
$pageSection = 'Prestamos';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header-row">
  <div>
    <h1>PRÉSTAMOS</h1>
    <p>// <?= $total ?> registros · Saldo total: <?= fmt($stats['total_saldo']) ?></p>
  </div>
  <?php if (canDo('puede_crear_prestamo')): ?>
  <button class="btn btn-primary" onclick="openModal('modal-prestamo')">+ Nuevo Préstamo</button>
  <?php endif; ?>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(5,1fr)">
  <div class="stat-card"><div class="stat-label">Activos</div><div class="stat-value"><?= $stats['activos'] ?></div></div>
  <div class="stat-card orange"><div class="stat-label">En Mora</div><div class="stat-value"><?= $stats['en_mora'] ?></div></div>
  <div class="stat-card blue"><div class="stat-label">En Acuerdo</div><div class="stat-value"><?= $stats['en_acuerdo'] ?></div></div>
  <div class="stat-card purple"><div class="stat-label">Saldo Total</div><div class="stat-value" style="font-size:1.3rem"><?= fmt($stats['total_saldo']) ?></div></div>
  <div class="stat-card"><div class="stat-label">Pagados</div><div class="stat-value"><?= $stats['pagados'] ?></div></div>
</div>

<div class="filter-bar mb-2">
  <form method="GET" style="display:flex;gap:0.5rem;flex-wrap:wrap;width:100%">
    <div class="search-bar" style="flex:1;min-width:180px">
      <span class="search-icon">⌕</span>
      <input type="text" name="q" value="<?= htmlspecialchars($buscar) ?>" placeholder="Buscar deudor o # prestamo...">
    </div>
    <select name="filtro" onchange="this.form.submit()" style="width:auto">
      <option value="">Todos activos</option>
      <option value="activo"   <?= $filtro==='activo'   ?'selected':'' ?>>Solo activos</option>
      <option value="mora"     <?= $filtro==='mora'     ?'selected':'' ?>>En mora</option>
      <option value="acuerdo"  <?= $filtro==='acuerdo'  ?'selected':'' ?>>En acuerdo</option>
      <option value="pagado"   <?= $filtro==='pagado'   ?'selected':'' ?>>Pagados</option>
      <option value="inactivo" <?= $filtro==='inactivo' ?'selected':'' ?>>Renovados/Refinanciados</option>
    </select>
    <button type="submit" class="btn btn-secondary">Buscar</button>
    <?php if ($buscar || $filtro): ?>
    <a href="/pages/prestamos.php" class="btn btn-ghost">X Limpiar</a>
    <?php endif; ?>
  </form>
</div>

<div class="card">
  <?php if (empty($prestamos)): ?>
    <div class="empty-state"><span class="empty-icon">◎</span><p>No se encontraron préstamos</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>#</th><th>Deudor</th><th>Monto</th><th>Interés</th><th>Total</th><th>Frecuencia</th><th>Próx. cuota</th><th>Saldo</th><th>Avance</th><th>Estado</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($prestamos as $p):
          $pct = $p['cuotas_total'] > 0 ? round($p['cuotas_pagadas']/$p['cuotas_total']*100) : 0;
          $estadoClass = match($p['estado']) {
            'activo'     => 'badge-purple',
            'en_mora'    => 'badge-orange',
            'en_acuerdo' => 'badge-blue',
            'pagado'     => 'badge-green',
            default      => 'badge-muted'
          };
          $diasMora = (int)$p['dias_mora'];
        ?>
        <tr>
          <td class="text-mono text-muted"><?= $p['id'] ?></td>
          <td>
            <div style="font-weight:600"><?= htmlspecialchars($p['deudor_nombre']) ?></div>
          </td>
          <td class="text-mono"><?= fmt($p['monto_prestado']) ?></td>
          <td class="text-mono"><?= $p['interes_valor'] ?><?= $p['tipo_interes']==='porcentaje'?'%':' fijo' ?><div class="text-xs text-muted">+<?= fmt($p['interes_calculado']) ?></div></td>
          <td class="text-mono fw-600"><?= fmt($p['total_a_pagar']) ?></td>
          <td>
            <span class="badge badge-muted"><?= ucfirst($p['frecuencia_pago']) ?></span>
            <?php if ($p['omitir_domingos']): ?><span title="Sin domingos" style="font-size:0.65rem;color:var(--muted)"> ✗☀</span><?php endif; ?>
            <div class="text-xs text-muted"><?= $p['num_cuotas'] ?> x <?= fmt($p['valor_cuota']) ?></div>
          </td>
          <td class="text-mono <?= ($p['proxima_cuota'] && $p['proxima_cuota'] <= date('Y-m-d')) ? 'orange' : '' ?>">
            <?= $p['proxima_cuota'] ? date('d M Y', strtotime($p['proxima_cuota'])) : '—' ?>
            <?php if ($diasMora > 0): ?><div class="text-xs" style="color:var(--warn)"><?= $diasMora ?>d mora</div><?php endif; ?>
          </td>
          <td class="text-mono <?= $p['saldo_pendiente'] > 0 ? 'orange' : 'green' ?>"><?= fmt($p['saldo_pendiente']) ?></td>
          <td style="min-width:80px">
            <div class="progress" style="margin-bottom:3px"><div class="progress-bar <?= $p['estado']==='en_mora'?'orange':'' ?>" style="width:<?= $pct ?>%"></div></div>
            <div class="text-xs text-muted"><?= $p['cuotas_pagadas'] ?>/<?= $p['cuotas_total'] ?></div>
          </td>
          <td><span class="badge <?= $estadoClass ?>"><?= strtoupper($p['estado']) ?></span></td>
          <td>
            <div class="btn-group">
              <a href="/pages/prestamo_detalle.php?id=<?= $p['id'] ?>" class="btn btn-info btn-sm">Ver</a>
              <?php if (canDo('puede_registrar_pago') && in_array($p['estado'],['activo','en_mora','en_acuerdo'])): ?>
              <a href="/pages/pagos.php?prestamo=<?= $p['id'] ?>" class="btn btn-success btn-sm">Pagar</a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalPags > 1): ?>
  <div class="pagination">
    <?php if ($page > 1): ?><a href="?page=<?=$page-1?>&q=<?=urlencode($buscar)?>&filtro=<?=$filtro?>" class="page-btn">&#8249;</a><?php endif; ?>
    <?php for ($i=max(1,$page-2);$i<=min($totalPags,$page+2);$i++): ?>
    <a href="?page=<?=$i?>&q=<?=urlencode($buscar)?>&filtro=<?=$filtro?>" class="page-btn <?=$i==$page?'active':''?>"><?=$i?></a>
    <?php endfor; ?>
    <?php if ($page < $totalPags): ?><a href="?page=<?=$page+1?>&q=<?=urlencode($buscar)?>&filtro=<?=$filtro?>" class="page-btn">&#8250;</a><?php endif; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- MODAL NUEVO PRESTAMO -->
<div class="modal-overlay" id="modal-prestamo">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h2>NUEVO PRÉSTAMO</h2>
      <button class="modal-close" onclick="closeModal('modal-prestamo')">&#10005;</button>
    </div>
    <div class="modal-body">
      <form id="form-prestamo">
        <div class="form-grid mb-2">
          <div class="field field-span2">
            <label>Deudor <span class="required">*</span></label>
            <select id="p_deudor" name="deudor_id" required>
              <option value="">— Seleccionar deudor —</option>
              <?php foreach ($deudores as $d): ?>
              <option value="<?= $d['id'] ?>" <?= $deudorPre==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Método de pago</label>
            <select name="metodo_pago">
              <option value="efectivo">Efectivo</option>
              <option value="banco">Banco</option>
            </select>
          </div>
          <div class="field">
            <label>Observaciones</label>
            <input type="text" name="observaciones" placeholder="Opcional">
          </div>
        </div>

        <div class="divider"></div>
        <p style="font-family:var(--font-mono);font-size:0.68rem;color:var(--muted);margin-bottom:0.75rem;text-transform:uppercase;letter-spacing:1px">Condiciones del préstamo</p>

        <div class="form-grid mb-2">
          <div class="field">
            <label>Monto prestado <span class="required">*</span></label>
            <input type="number" id="p_monto" name="monto_prestado" placeholder="0" step="10000" min="1" oninput="calcPreview()" required>
          </div>
          <div class="field">
            <label>Tipo de interés</label>
            <select id="p_tipo_interes" name="tipo_interes" onchange="calcPreview()">
              <option value="porcentaje">Porcentaje (%)</option>
              <option value="valor_fijo">Valor fijo ($)</option>
            </select>
          </div>
          <div class="field">
            <label id="label-interes">Interés (%)</label>
            <input type="number" id="p_interes" name="interes_valor" value="20" step="1" min="0" oninput="calcPreview()">
          </div>
          <div class="field">
            <label>Fecha de inicio <span class="required">*</span></label>
            <input type="date" id="p_fecha" name="fecha_inicio" value="<?= date('Y-m-d') ?>" onchange="calcPreview()" required>
          </div>
        </div>

        <div class="divider"></div>
        <p style="font-family:var(--font-mono);font-size:0.68rem;color:var(--muted);margin-bottom:0.75rem;text-transform:uppercase;letter-spacing:1px">Estructura de pago</p>

        <div class="form-grid mb-2">
          <div class="field">
            <label>Frecuencia de pago</label>
            <select id="p_frecuencia" name="frecuencia_pago" onchange="calcPreview()">
              <option value="diario">Diario</option>
              <option value="semanal">Semanal</option>
              <option value="quincenal">Quincenal</option>
              <option value="mensual" selected>Mensual</option>
            </select>
          </div>
          <div class="field">
            <label>Número de cuotas</label>
            <input type="number" id="p_cuotas" name="num_cuotas" value="1" min="1" max="120" oninput="calcPreview()">
          </div>
          <div class="field">
            <label>Valor cuota (override)</label>
            <input type="number" id="p_valor_cuota" name="valor_cuota_override" placeholder="Auto" step="1000" oninput="calcPreviewManual()">
            <span class="field-hint">Deja vacío para calcular automático</span>
          </div>
          <div class="field" id="campo-omitir-domingos" style="display:none">
            <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-weight:normal">
              <input type="checkbox" id="p_omitir_domingos" name="omitir_domingos" value="1"
                style="width:16px;height:16px;accent-color:var(--accent)">
              <span>Los domingos no cuentan <span class="text-muted" style="font-size:0.7rem">(las cuotas que caigan domingo se mueven al lunes)</span></span>
            </label>
          </div>
        </div>

        <div class="calc-preview hidden" id="calc-preview">
          <div class="calc-item"><div class="clabel">Interés total</div><div class="cval" id="cv-interes">$0</div></div>
          <div class="calc-item"><div class="clabel">Total a pagar</div><div class="cval" id="cv-total">$0</div></div>
          <div class="calc-item"><div class="clabel">Valor cuota</div><div class="cval" id="cv-cuota">$0</div></div>
          <div class="calc-item"><div class="clabel">Fecha límite</div><div class="cval" id="cv-fecha" style="font-size:0.9rem">—</div></div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-prestamo')">Cancelar</button>
      <button class="btn btn-primary" id="btn-guardar-prestamo" onclick="guardarPrestamo()">REGISTRAR PRÉSTAMO</button>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
function calcPreview() {
    var monto  = parseFloat(document.getElementById('p_monto').value) || 0;
    var tipo   = document.getElementById('p_tipo_interes').value;
    var intVal = parseFloat(document.getElementById('p_interes').value) || 0;
    var cuotas = parseInt(document.getElementById('p_cuotas').value) || 1;
    var freq   = document.getElementById('p_frecuencia').value;
    var fecha  = document.getElementById('p_fecha').value;

    document.getElementById('campo-omitir-domingos').style.display = freq === 'diario' ? '' : 'none';
    if (freq !== 'diario') document.getElementById('p_omitir_domingos').checked = false;

    document.getElementById('label-interes').textContent = tipo === 'porcentaje' ? 'Interés (%)' : 'Interés ($ fijo total)';
    if (!monto) { document.getElementById('calc-preview').classList.add('hidden'); return; }

    var r = calcularPrestamo({ monto: monto, tipoInteres: tipo, interesValor: intVal, numCuotas: cuotas });
    document.getElementById('cv-interes').textContent = fmt(r.interesCalc);
    document.getElementById('cv-total').textContent   = fmt(r.total);
    document.getElementById('cv-cuota').textContent   = fmt(r.valorCuota);

    var fechaFin = calcFechaFin(fecha, freq, cuotas);
    document.getElementById('cv-fecha').textContent = fechaFin ? formatFecha(fechaFin) : '—';
    document.getElementById('calc-preview').classList.remove('hidden');
}

function calcPreviewManual() {
    var monto    = parseFloat(document.getElementById('p_monto').value) || 0;
    var tipo     = document.getElementById('p_tipo_interes').value;
    var intVal   = parseFloat(document.getElementById('p_interes').value) || 0;
    var cuotaMan = parseFloat(document.getElementById('p_valor_cuota').value) || 0;
    if (!monto || !cuotaMan) { calcPreview(); return; }
    var r = calcularPrestamo({ monto: monto, tipoInteres: tipo, interesValor: intVal, numCuotas: 1 });
    var cuotasCalc = Math.ceil(r.total / cuotaMan);
    document.getElementById('p_cuotas').value         = cuotasCalc;
    document.getElementById('cv-interes').textContent  = fmt(r.interesCalc);
    document.getElementById('cv-total').textContent    = fmt(r.total);
    document.getElementById('cv-cuota').textContent    = fmt(cuotaMan);
    document.getElementById('calc-preview').classList.remove('hidden');
}

async function guardarPrestamo() {
    var deudor = document.getElementById('p_deudor').value;
    var monto  = document.getElementById('p_monto').value;
    var fecha  = document.getElementById('p_fecha').value;

    if (!deudor) { toast('Selecciona un deudor', 'error'); return; }
    if (!monto || parseFloat(monto) <= 0) { toast('Ingresa el monto', 'error'); return; }
    if (!fecha)  { toast('Ingresa la fecha de inicio', 'error'); return; }

    var btn = document.getElementById('btn-guardar-prestamo');
    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner"></span> Guardando...';

    var form = document.getElementById('form-prestamo');
    var data = Object.fromEntries(new FormData(form));
    if (!data.valor_cuota_override) delete data.valor_cuota_override;
    data.action = 'crear';

    var res = await apiPost('/api/prestamos.php', data);
    btn.disabled  = false;
    btn.innerHTML = 'REGISTRAR PRÉSTAMO';

    if (res.ok) {
        toast('Préstamo registrado correctamente');
        closeModal('modal-prestamo');
        setTimeout(function() {
            if (res.id) window.location = '/pages/prestamo_detalle.php?id=' + res.id;
            else location.reload();
        }, 800);
    } else {
        toast(res.msg || 'Error al guardar', 'error');
    }
}

<?php if ($action === 'nuevo'): ?>
document.addEventListener('DOMContentLoaded', function() { openModal('modal-prestamo'); });
<?php endif; ?>

calcPreview();
</script>