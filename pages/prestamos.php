<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
if (!canDo('puede_ver_prestamos')) { include __DIR__ . '/403.php'; exit; }

$db    = getDB();
$cobro = cobroActivo();

// ── Cobros disponibles para filtro ─────────────────────────────
if ($_SESSION['rol'] === 'superadmin') {
    $stmtCobros = $db->query("SELECT id, nombre FROM cobros WHERE activo=1 ORDER BY nombre");
} else {
    $stmtCobros = $db->prepare("
        SELECT c.id, c.nombre FROM cobros c
        JOIN usuario_cobro uc ON uc.cobro_id=c.id
        WHERE uc.usuario_id=? AND c.activo=1 ORDER BY c.nombre
    ");
    $stmtCobros->execute([$_SESSION['usuario_id']]);
}
$todosCobros = $stmtCobros->fetchAll();

// ── Filtros ────────────────────────────────────────────────────
$buscar      = trim($_GET['q'] ?? '');
$filtroEstado= $_GET['estado'] ?? '';
$filtroCobro = (int)($_GET['cobro'] ?? 0);
$fechaDesde  = $_GET['desde'] ?? '';
$fechaHasta  = $_GET['hasta'] ?? '';
$page        = max(1, (int)($_GET['page'] ?? 1));
$limit       = 20;
$offset      = ($page - 1) * $limit;

// ── WHERE ───────────────────────────────────────────────────────
if ($filtroCobro > 0) {
    $where  = ['p.cobro_id=?'];
    $params = [$filtroCobro];
} elseif ($_SESSION['rol'] === 'superadmin') {
    $where  = ['1=1'];
    $params = [];
} else {
    $cobrosIds = implode(',', array_map('intval', array_column($todosCobros, 'id')));
    $where  = $cobrosIds ? ["p.cobro_id IN ($cobrosIds)"] : ['1=0'];
    $params = [];
}

if ($buscar) {
    $where[]  = '(d.nombre LIKE ? OR p.id = ?)';
    $params[] = "%$buscar%";
    $params[] = (int)$buscar;
}

$estadosMap = [
    'activo'     => ['activo'],
    'mora'       => ['en_mora'],
    'acuerdo'    => ['en_acuerdo'],
    'pagado'     => ['pagado'],
    'renovado'   => ['renovado','refinanciado'],
    'anulado'    => ['anulado'],
    'incobrable' => ['incobrable'],
];

if (isset($estadosMap[$filtroEstado])) {
    $phs     = implode(',', array_fill(0, count($estadosMap[$filtroEstado]), '?'));
    $where[] = "p.estado IN ($phs)";
    $params  = array_merge($params, $estadosMap[$filtroEstado]);
} else {
    $where[] = "p.estado NOT IN ('renovado','refinanciado','anulado')";
}

if ($fechaDesde) { $where[] = 'p.fecha_inicio >= ?'; $params[] = $fechaDesde; }
if ($fechaHasta) { $where[] = 'p.fecha_inicio <= ?'; $params[] = $fechaHasta; }

$whereSQL = implode(' AND ', $where);

// ── Total ───────────────────────────────────────────────────────
$stmtTotal = $db->prepare("SELECT COUNT(*) FROM prestamos p JOIN deudores d ON d.id=p.deudor_id WHERE $whereSQL");
$stmtTotal->execute($params);
$total     = (int)$stmtTotal->fetchColumn();
$totalPags = ceil($total / $limit);

// ── Lista ───────────────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT p.*,
        d.nombre  AS deudor_nombre,
        d.telefono AS deudor_tel,
        co.nombre AS cobro_nombre,
        (SELECT COUNT(*) FROM cuotas WHERE prestamo_id=p.id AND estado='pagado')   AS cuotas_pagadas,
        (SELECT COUNT(*) FROM cuotas WHERE prestamo_id=p.id)                       AS cuotas_total,
        (SELECT MIN(fecha_vencimiento) FROM cuotas
         WHERE prestamo_id=p.id AND estado IN ('pendiente','parcial'))              AS proxima_cuota
    FROM prestamos p
    JOIN deudores d ON d.id=p.deudor_id
    JOIN cobros co  ON co.id=p.cobro_id
    WHERE $whereSQL
    ORDER BY
        CASE p.estado WHEN 'en_mora' THEN 1 WHEN 'en_acuerdo' THEN 2 WHEN 'activo' THEN 3 ELSE 4 END,
        p.dias_mora DESC, p.updated_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$prestamos = $stmt->fetchAll();

// ── Stats (sin filtro de estado, con cobro+fecha+búsqueda) ──────
$whereBase  = [];
$paramsBase = [];

if ($filtroCobro > 0) {
    $whereBase[]  = 'p.cobro_id=?';
    $paramsBase[] = $filtroCobro;
} elseif ($_SESSION['rol'] === 'superadmin') {
    $whereBase[] = '1=1';
} else {
    $cobrosIds2 = implode(',', array_map('intval', array_column($todosCobros,'id')));
    $whereBase[] = $cobrosIds2 ? "p.cobro_id IN ($cobrosIds2)" : '1=0';
}
if ($buscar)     { $whereBase[] = '(d.nombre LIKE ? OR p.id = ?)'; $paramsBase[] = "%$buscar%"; $paramsBase[] = (int)$buscar; }
if ($fechaDesde) { $whereBase[] = 'p.fecha_inicio >= ?'; $paramsBase[] = $fechaDesde; }
if ($fechaHasta) { $whereBase[] = 'p.fecha_inicio <= ?'; $paramsBase[] = $fechaHasta; }
$whereBaseSQL = implode(' AND ', $whereBase);

$stmtStats = $db->prepare("
    SELECT
        SUM(CASE WHEN p.estado='activo'  THEN 1 ELSE 0 END) AS activos,
        SUM(CASE WHEN p.estado='en_mora' THEN 1 ELSE 0 END) AS en_mora,
        SUM(CASE WHEN p.estado='pagado'  THEN 1 ELSE 0 END) AS pagados,
        SUM(CASE WHEN p.estado='en_mora' THEN p.saldo_pendiente ELSE 0 END) AS saldo_mora,
        SUM(CASE WHEN p.estado NOT IN ('renovado','refinanciado','anulado')
            THEN p.saldo_pendiente ELSE 0 END) AS total_saldo
    FROM prestamos p
    JOIN deudores d ON d.id=p.deudor_id
    WHERE $whereBaseSQL
");
$stmtStats->execute($paramsBase);
$stats = $stmtStats->fetch();

// ── Deudores para el modal ──────────────────────────────────────
$cobroModal = $filtroCobro > 0 ? $filtroCobro : $cobro;
$stmtDeud = $db->prepare("
    SELECT d.id, d.nombre FROM deudores d
    JOIN deudor_cobro dc ON dc.deudor_id=d.id
    WHERE dc.cobro_id=? AND d.activo=1 AND d.comportamiento != 'clavo'
    ORDER BY d.nombre
");
$stmtDeud->execute([$cobroModal]);
$deudores  = $stmtDeud->fetchAll();
$deudorPre = (int)($_GET['deudor'] ?? 0);

$pageTitle   = 'Préstamos';
$pageSection = 'Préstamos';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header-row">
  <div>
    <h1>PRÉSTAMOS</h1>
    <p>// <?= number_format($total) ?> registros · Saldo: <?= fmt($stats['total_saldo']) ?></p>
  </div>
  <?php if (canDo('puede_crear_prestamo')): ?>
  <button class="btn btn-primary" onclick="openModal('modal-prestamo')">+ Nuevo Préstamo</button>
  <?php endif; ?>
</div>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:1.5rem">
  <div class="stat-card purple">
    <div class="stat-label">Activos</div>
    <div class="stat-value"><?= $stats['activos'] ?></div>
  </div>
  <div class="stat-card orange">
    <div class="stat-label">En Mora</div>
    <div class="stat-value"><?= $stats['en_mora'] ?></div>
  </div>
  <div class="stat-card orange">
    <div class="stat-label">Saldo en Mora</div>
    <div class="stat-value" style="font-size:1.1rem"><?= fmt($stats['saldo_mora']) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Saldo Total</div>
    <div class="stat-value" style="font-size:1.1rem"><?= fmt($stats['total_saldo']) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Pagados</div>
    <div class="stat-value"><?= $stats['pagados'] ?></div>
  </div>
</div>

<!-- Filtros -->
<div class="filter-bar mb-2">
  <form method="GET" style="display:flex;gap:0.5rem;flex-wrap:wrap;width:100%">
    <div class="search-bar" style="flex:1;min-width:180px">
      <span class="search-icon">⌕</span>
      <input type="text" name="q" value="<?= htmlspecialchars($buscar) ?>"
             placeholder="Buscar deudor o # préstamo...">
    </div>
    <?php if (count($todosCobros) > 1): ?>
    <select name="cobro" onchange="this.form.submit()" style="width:auto">
      <option value="0">Todos los cobros</option>
      <?php foreach ($todosCobros as $cb): ?>
      <option value="<?= $cb['id'] ?>" <?= $filtroCobro===$cb['id']?'selected':'' ?>>
        <?= htmlspecialchars($cb['nombre']) ?>
      </option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>
    <select name="estado" onchange="this.form.submit()" style="width:auto">
      <option value="">Activos + mora + acuerdo</option>
      <option value="activo"     <?= $filtroEstado==='activo'    ?'selected':'' ?>>Solo activos</option>
      <option value="mora"       <?= $filtroEstado==='mora'      ?'selected':'' ?>>En mora</option>
      <option value="acuerdo"    <?= $filtroEstado==='acuerdo'   ?'selected':'' ?>>En acuerdo</option>
      <option value="pagado"     <?= $filtroEstado==='pagado'    ?'selected':'' ?>>Pagados</option>
      <option value="renovado"   <?= $filtroEstado==='renovado'  ?'selected':'' ?>>Renovados/Refinanciados</option>
      <option value="anulado"    <?= $filtroEstado==='anulado'   ?'selected':'' ?>>Anulados</option>
      <option value="incobrable" <?= $filtroEstado==='incobrable'?'selected':'' ?>>Incobrables</option>
    </select>
    <input type="date" name="desde" value="<?= htmlspecialchars($fechaDesde) ?>" style="width:auto">
    <input type="date" name="hasta" value="<?= htmlspecialchars($fechaHasta) ?>" style="width:auto">
    <button type="submit" class="btn btn-secondary">Buscar</button>
    <?php if ($buscar || $filtroEstado || $filtroCobro || $fechaDesde || $fechaHasta): ?>
    <a href="/pages/prestamos.php" class="btn btn-ghost">✕ Limpiar</a>
    <?php endif; ?>
  </form>
</div>

<!-- Tabla -->
<div class="card">
  <?php if (empty($prestamos)): ?>
    <div class="empty-state"><span class="empty-icon">◎</span><p>No se encontraron préstamos</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th><th>Deudor</th>
          <?php if (count($todosCobros) > 1): ?><th>Cobro</th><?php endif; ?>
          <th>Monto</th><th>Total / Cuota</th><th>Frecuencia</th>
          <th>Próx. cuota</th><th>Saldo</th><th>Avance</th><th>Estado</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($prestamos as $p):
          $pct = $p['cuotas_total'] > 0 ? round($p['cuotas_pagadas']/$p['cuotas_total']*100) : 0;
          $estadoClass = match($p['estado']) {
            'activo'     => 'badge-purple',
            'en_mora'    => 'badge-orange',
            'en_acuerdo' => 'badge-blue',
            'pagado'     => 'badge-green',
            'anulado'    => 'badge-red',
            default      => 'badge-muted'
          };
          $diasMora = (int)$p['dias_mora'];
        ?>
        <tr>
          <td class="text-mono text-muted"><?= $p['id'] ?></td>
          <td>
            <div style="font-weight:600">
              <a href="/pages/deudor_detalle.php?id=<?= $p['deudor_id'] ?>"
                 style="color:var(--text);text-decoration:none">
                <?= htmlspecialchars($p['deudor_nombre']) ?>
              </a>
            </div>
            <?php if ($p['deudor_tel']): ?>
            <div class="text-xs text-muted"><?= htmlspecialchars($p['deudor_tel']) ?></div>
            <?php endif; ?>
          </td>
          <?php if (count($todosCobros) > 1): ?>
          <td class="text-xs text-muted"><?= htmlspecialchars($p['cobro_nombre']) ?></td>
          <?php endif; ?>
          <td class="text-mono"><?= fmt($p['monto_prestado']) ?>
            <div class="text-xs text-muted"><?= $p['interes_valor'] ?><?= $p['tipo_interes']==='porcentaje'?'%':' fijo' ?></div>
          </td>
          <td class="text-mono">
            <?= fmt($p['total_a_pagar']) ?>
            <div class="text-xs text-muted"><?= $p['num_cuotas'] ?> × <?= fmt($p['valor_cuota']) ?></div>
          </td>
          <td>
            <span class="badge badge-muted"><?= ucfirst($p['frecuencia_pago']) ?></span>
            <?php if ($p['omitir_domingos']): ?><span style="font-size:0.62rem;color:var(--muted)"> ✗☀</span><?php endif; ?>
          </td>
          <td class="text-mono <?= ($p['proxima_cuota'] && $p['proxima_cuota'] <= date('Y-m-d')) ? 'orange fw-600' : '' ?>">
            <?= $p['proxima_cuota'] ? date('d M Y', strtotime($p['proxima_cuota'])) : '—' ?>
            <?php if ($diasMora > 0): ?>
            <div class="text-xs" style="color:#f59e0b"><?= $diasMora ?>d mora</div>
            <?php endif; ?>
          </td>
          <td class="text-mono <?= $p['saldo_pendiente'] > 0 ? 'orange' : 'green' ?>">
            <?= fmt($p['saldo_pendiente']) ?>
          </td>
          <td style="min-width:80px">
            <div class="progress" style="margin-bottom:0.2rem">
              <div class="progress-bar <?= $p['estado']==='en_mora'?'orange':'' ?>" style="width:<?= $pct ?>%"></div>
            </div>
            <div class="text-xs text-muted text-mono"><?= $p['cuotas_pagadas'] ?>/<?= $p['cuotas_total'] ?></div>
          </td>
          <td><span class="badge <?= $estadoClass ?>"><?= strtoupper($p['estado']) ?></span></td>
          <td><a href="/pages/prestamo_detalle.php?id=<?= $p['id'] ?>" class="btn btn-info btn-sm">Ver</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalPags > 1):
    $qs = http_build_query(['q'=>$buscar,'cobro'=>$filtroCobro,'estado'=>$filtroEstado,'desde'=>$fechaDesde,'hasta'=>$fechaHasta]);
  ?>
  <div class="pagination">
    <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?>&<?= $qs ?>" class="page-btn">‹</a><?php endif; ?>
    <?php for ($i = max(1,$page-2); $i <= min($totalPags,$page+2); $i++): ?>
    <a href="?page=<?= $i ?>&<?= $qs ?>" class="page-btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($page < $totalPags): ?><a href="?page=<?= $page+1 ?>&<?= $qs ?>" class="page-btn">›</a><?php endif; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- Modal nuevo préstamo -->
<?php if (canDo('puede_crear_prestamo')): ?>
<div class="modal-overlay" id="modal-prestamo">
  <div class="modal" style="max-width:640px">
    <div class="modal-header">
      <h2>NUEVO PRÉSTAMO</h2>
      <button class="modal-close" onclick="closeModal('modal-prestamo')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-grid mb-2">

        <!-- Cobro — nuevo campo -->
        <div class="field field-span2">
          <label>Cobro <span class="required">*</span></label>
          <select id="pr_cobro">
            <option value="">— Selecciona un cobro —</option>
            <?php foreach ($todosCobros as $cb): ?>
            <option value="<?= $cb['id'] ?>" <?= $filtroCobro===$cb['id'] ? 'selected' : ($cobro===$cb['id'] && !$filtroCobro ? 'selected' : '') ?>>
              <?= htmlspecialchars($cb['nombre']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field field-span2">
          <label>Deudor <span class="required">*</span></label>
          <input type="text" id="pr_buscar_deudor" placeholder="Buscar por nombre o cédula..."
                 oninput="buscarDeudor(this.value)" autocomplete="off">
          <div id="pr_resultados" style="display:none;max-height:180px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--radius);background:var(--card);margin-top:0.25rem"></div>
          <div id="pr_deudor_tag" style="display:none;margin-top:0.35rem;padding:0.4rem 0.75rem;background:rgba(124,106,255,.12);border-radius:var(--radius);font-size:0.82rem;display:none;align-items:center;justify-content:space-between">
            <span id="pr_deudor_txt" style="font-weight:600"></span>
            <button type="button" onclick="limpiarDeudor()" style="background:none;border:none;color:var(--muted);cursor:pointer;font-size:1rem;padding:0">✕</button>
          </div>
          <input type="hidden" id="pr_deudor_id">
        </div>

        <div class="field">
          <label>Monto a prestar <span class="required">*</span></label>
          <input type="number" id="pr_monto" step="10000" min="1" placeholder="0" oninput="calcPreview()">
        </div>
        <div class="field">
          <label>Tipo de interés</label>
          <select id="pr_tipo_int" onchange="calcPreview()">
            <option value="porcentaje">% Porcentaje</option>
            <option value="valor_fijo">$ Valor fijo</option>
          </select>
        </div>
        <div class="field">
          <label id="pr_label_int">Interés (%)</label>
          <input type="number" id="pr_interes" value="20" step="1" min="0" oninput="calcPreview()">
        </div>
        <div class="field">
          <label>Frecuencia</label>
          <select id="pr_frecuencia" onchange="calcPreview()">
            <option value="diario">Diario</option>
            <option value="semanal">Semanal</option>
            <option value="quincenal">Quincenal</option>
            <option value="mensual" selected>Mensual</option>
          </select>
        </div>
        <div class="field">
          <label>Número de cuotas</label>
          <input type="number" id="pr_cuotas" value="1" min="1" oninput="calcPreview()">
        </div>
        <div class="field">
          <label>Fecha inicio</label>
          <input type="date" id="pr_fecha" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="field">
          <label>Método de pago</label>
          <select id="pr_metodo">
            <option value="efectivo">Efectivo</option>
            <option value="banco">Banco</option>
          </select>
        </div>
        <div id="pr_preview" style="display:none" class="field field-span2">
          <div style="background:rgba(124,106,255,.08);border:1px solid rgba(124,106,255,.25);border-radius:var(--radius);padding:0.85rem;font-family:var(--font-mono);font-size:0.8rem">
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.5rem">
              <div><div style="color:var(--muted);font-size:0.65rem">Interés</div><div id="pv_int" style="font-weight:700">—</div></div>
              <div><div style="color:var(--muted);font-size:0.65rem">Total</div><div id="pv_total" style="font-weight:700;color:var(--accent)">—</div></div>
              <div><div style="color:var(--muted);font-size:0.65rem">Cuota</div><div id="pv_cuota" style="font-weight:700">—</div></div>
              <div><div style="color:var(--muted);font-size:0.65rem">Fin est.</div><div id="pv_fin" style="font-weight:700">—</div></div>
            </div>
          </div>
        </div>
        <div class="field field-span2">
          <label>Observaciones</label>
          <textarea id="pr_obs" rows="2" placeholder="Notas del préstamo..."></textarea>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-prestamo')">Cancelar</button>
      <button class="btn btn-primary" id="btn-crear-prestamo" onclick="crearPrestamo()">REGISTRAR PRÉSTAMO</button>
    </div>
  </div>
</div>

<?php
// Precargar deudores de todos los cobros para el buscador
$deudoresPorCobro = [];
foreach ($todosCobros as $cb) {
    $s = $db->prepare("
        SELECT d.id, d.nombre, d.documento FROM deudores d
        JOIN deudor_cobro dc ON dc.deudor_id=d.id
        WHERE dc.cobro_id=? AND d.activo=1 AND d.comportamiento != 'clavo'
        ORDER BY d.nombre
    ");
    $s->execute([$cb['id']]);
    $deudoresPorCobro[$cb['id']] = $s->fetchAll();
}
$dJson = json_encode($deudoresPorCobro);
?>
<script>
var DPC = <?= $dJson ?>;

function onCobroModalChange(cobroId) {
    limpiarDeudor();
    document.getElementById('pr_buscar_deudor').value = '';
    document.getElementById('pr_resultados').style.display = 'none';
}

function buscarDeudor(q) {
    var cobroId = document.getElementById('pr_cobro').value;
    var res = document.getElementById('pr_resultados');
    if (!cobroId) { toast('Selecciona un cobro primero', 'error'); return; }
    if (!q.trim()) { res.style.display = 'none'; return; }
    var lista = (DPC[cobroId] || []).filter(function(d) {
        return d.nombre.toLowerCase().indexOf(q.toLowerCase()) >= 0
            || (d.documento || '').indexOf(q) >= 0;
    }).slice(0, 8);
    if (!lista.length) {
        res.innerHTML = '<div style="padding:0.75rem;font-size:0.82rem;color:var(--muted)">Sin resultados</div>';
    } else {
        res.innerHTML = lista.map(function(d) {
            return '<div style="padding:0.55rem 0.85rem;cursor:pointer;border-bottom:1px solid var(--border)"'
                + ' onmousedown="elegirDeudor(' + d.id + ',\'' + d.nombre.replace(/'/g,'\\\'') + '\',\'' + (d.documento||'').replace(/'/g,'\\\'') + '\')"'
                + ' onmouseover="this.style.background=\'rgba(124,106,255,.1)\'"'
                + ' onmouseout="this.style.background=\'\'">'
                + '<div style="font-weight:600">' + d.nombre + '</div>'
                + (d.documento ? '<div style="font-size:0.7rem;color:var(--muted)">CC: ' + d.documento + '</div>' : '')
                + '</div>';
        }).join('');
    }
    res.style.display = 'block';
}

function elegirDeudor(id, nombre, doc) {
    document.getElementById('pr_deudor_id').value = id;
    document.getElementById('pr_deudor_txt').textContent = nombre + (doc ? ' — CC: ' + doc : '');
    document.getElementById('pr_buscar_deudor').value = '';
    document.getElementById('pr_resultados').style.display = 'none';
    document.getElementById('pr_deudor_tag').style.display = 'flex';
}

function limpiarDeudor() {
    document.getElementById('pr_deudor_id').value = '';
    document.getElementById('pr_deudor_tag').style.display = 'none';
    document.getElementById('pr_deudor_txt').textContent = '';
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('#pr_buscar_deudor') && !e.target.closest('#pr_resultados')) {
        document.getElementById('pr_resultados').style.display = 'none';
    }
});

// Registrar el cambio de cobro sin onchange inline para evitar conflictos
var selCobro = document.getElementById('pr_cobro');
if (selCobro) {
    selCobro.addEventListener('change', function(e) {
        e.stopPropagation();
        onCobroModalChange(this.value);
    });
}
</script>
<?php endif; ?>

<?php
$extraScript = <<<'JS'
<script>
function calcPreview() {
    const monto   = parseFloat(document.getElementById('pr_monto').value)   || 0;
    const tipoInt = document.getElementById('pr_tipo_int').value;
    const intVal  = parseFloat(document.getElementById('pr_interes').value)  || 0;
    const cuotas  = parseInt(document.getElementById('pr_cuotas').value)     || 1;
    const freq    = document.getElementById('pr_frecuencia').value;
    const fecha   = document.getElementById('pr_fecha').value;

    document.getElementById('pr_label_int').textContent =
        tipoInt === 'porcentaje' ? 'Interés (%)' : 'Interés ($ fijo total)';

    if (!monto) { document.getElementById('pr_preview').style.display='none'; return; }

    const intCalc  = tipoInt === 'porcentaje' ? monto * (intVal/100) : intVal;
    const total    = monto + intCalc;
    const valCuota = Math.round(total / cuotas);
    const diasMap  = {diario:1,semanal:7,quincenal:15,mensual:30};
    const fechaFin = new Date(fecha || new Date());
    fechaFin.setDate(fechaFin.getDate() + (diasMap[freq]||30)*cuotas);
    const fmt = n => '$'+Math.round(n).toLocaleString('es-CO');

    document.getElementById('pv_int').textContent   = fmt(intCalc);
    document.getElementById('pv_total').textContent = fmt(total);
    document.getElementById('pv_cuota').textContent = fmt(valCuota)+' x '+cuotas;
    document.getElementById('pv_fin').textContent   =
        fechaFin.toLocaleDateString('es-CO',{day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('pr_preview').style.display = 'block';
}

async function crearPrestamo() {
    const deudorId = parseInt(document.getElementById('pr_deudor_id').value) || 0;
    const monto    = parseFloat(document.getElementById('pr_monto').value)   || 0;
    const cuotas   = parseInt(document.getElementById('pr_cuotas').value)    || 1;
    const fecha    = document.getElementById('pr_fecha').value;

    if (!document.getElementById('pr_cobro').value) { toast('Selecciona un cobro', 'error'); return; }
    if (!deudorId) { toast('Selecciona un deudor', 'error'); return; }
    if (!monto)    { toast('Ingresa el monto', 'error'); return; }
    if (!fecha)    { toast('Ingresa la fecha', 'error'); return; }

    const tipoInt = document.getElementById('pr_tipo_int').value;
    const intVal  = parseFloat(document.getElementById('pr_interes').value) || 0;
    const freq    = document.getElementById('pr_frecuencia').value;
    const intCalc = tipoInt === 'porcentaje' ? monto*(intVal/100) : intVal;
    const total   = monto + intCalc;
    const fmt     = n => '$'+Math.round(n).toLocaleString('es-CO');
    const deuNom  = document.getElementById('pr_deudor_txt').textContent;
    const cobNom  = document.getElementById('pr_cobro').selectedOptions[0]?.text || '';

    if (!confirm(
        'Confirmar préstamo:\n\n'+
        'Cobro: '   + cobNom + '\n'+
        'Deudor: '  + deuNom + '\n'+
        'Monto: '   + fmt(monto)   + '\n'+
        'Interés: ' + fmt(intCalc) + '\n'+
        'Total: '   + fmt(total)   + '\n'+
        'Cuotas: '  + cuotas + ' x ' + fmt(Math.round(total/cuotas)) + '\n'+
        'Frecuencia: ' + freq
    )) return;

    const btn = document.getElementById('btn-crear-prestamo');
    btn.disabled=true; btn.innerHTML='<span class="spinner"></span> Registrando...';

    const res = await apiPost('/api/prestamos.php', {
        action         : 'crear',
        cobro_id       : parseInt(document.getElementById('pr_cobro').value) || 0,
        deudor_id      : deudorId,
        monto_prestado : monto,
        tipo_interes   : tipoInt,
        interes_valor  : intVal,
        frecuencia_pago: freq,
        num_cuotas     : cuotas,
        fecha_inicio   : fecha,
        metodo_pago    : document.getElementById('pr_metodo').value,
        observaciones  : document.getElementById('pr_obs').value.trim(),
        tipo_origen    : 'nuevo'
    });

    btn.disabled=false; btn.innerHTML='REGISTRAR PRÉSTAMO';

    if (res.ok) {
        toast('Préstamo registrado');
        closeModal('modal-prestamo');
        setTimeout(()=>location.reload(), 800);
    } else {
        toast(res.msg || 'Error', 'error');
    }
}
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>