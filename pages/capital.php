<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
if (!canDo('puede_ver_capital')) { include __DIR__ . '/403.php'; exit; }

$db    = getDB();
$cobro = cobroActivo();

// ── 1. Capitalistas base ─────────────────────────────────────
$stmtCap = $db->prepare("
    SELECT cap.id AS capitalista_id, cap.nombre, cap.estado, cap.color,
           cap.tipo, cap.tasa_redito, cap.tipo_redito,
           COALESCE(SUM(CASE WHEN m.tipo='ingreso_capital' AND m.es_entrada=1 AND m.anulado=0 THEN m.monto ELSE 0 END),0) AS total_aportado,
           COALESCE(SUM(CASE WHEN m.tipo='retiro_capital'  AND m.es_entrada=0 AND m.anulado=0 THEN m.monto ELSE 0 END),0) AS total_retirado,
           COALESCE(SUM(CASE WHEN m.tipo='redito'          AND m.es_entrada=0 AND m.anulado=0 THEN m.monto ELSE 0 END),0) AS total_reditos_pagados
    FROM capitalistas cap
    LEFT JOIN capital_movimientos m ON m.capitalista_id=cap.id AND m.cobro_id=?
    WHERE cap.cobro_id=?
    GROUP BY cap.id
    ORDER BY cap.nombre ASC
");
$stmtCap->execute([$cobro, $cobro]);
$capitalistas = $stmtCap->fetchAll();

// ── 2. Totales globales reales ───────────────────────────────
// Caja real
$stmtCaja = $db->prepare("SELECT COALESCE(SUM(saldo_actual),0) FROM v_saldo_cuentas WHERE cobro_id=?");
$stmtCaja->execute([$cobro]);
$capitalGlobal = (float)$stmtCaja->fetchColumn();

// Cartera activa real (saldo_pendiente de préstamos activos)
$stmtCartera = $db->prepare("
    SELECT COALESCE(SUM(saldo_pendiente),0)
    FROM prestamos
    WHERE cobro_id=? AND estado NOT IN ('pagado','renovado','refinanciado','anulado')
");
$stmtCartera->execute([$cobro]);
$carteraActiva = (float)$stmtCartera->fetchColumn();

// Patrimonio total del negocio = caja + cartera
$patrimonioNegocio = $capitalGlobal + $carteraActiva;

// Total aportado por todos los capitalistas
$totalAportado = array_sum(array_column($capitalistas, 'total_aportado'));

// Crecimiento global = patrimonio actual vs lo que pusieron
$crecGlobal    = $patrimonioNegocio - $totalAportado;
$crecGlobalPct = $totalAportado > 0 ? round(($crecGlobal / $totalAportado) * 100, 1) : 0;

// ── 3. Calcular participación y métricas por capitalista ─────
foreach ($capitalistas as &$cap) {
    $aportado = (float)$cap['total_aportado'];
    $retirado = (float)$cap['total_retirado'];

    // % participación = su aporte / total aportes
    $pct = $totalAportado > 0 ? $aportado / $totalAportado : 0;
    $cap['pct_participacion'] = round($pct * 100, 1);

    // Lo que le corresponde del patrimonio total
    $patrimonioSuyo = $patrimonioNegocio * $pct;
    $cap['patrimonio_suyo'] = round($patrimonioSuyo);

    // Cartera que le corresponde
    $cap['cartera_suya'] = round($carteraActiva * $pct);

    // Caja que le corresponde
    $cap['caja_suya'] = round($capitalGlobal * $pct);

    // Capital neto invertido (aportes - retiros)
    $cap['capital_neto'] = $aportado - $retirado;

    // Crecimiento real = (patrimonio suyo - capital neto) 
    $diff    = $patrimonioSuyo - $cap['capital_neto'];
    $diffPct = $cap['capital_neto'] > 0 ? round(($diff / $cap['capital_neto']) * 100, 1) : 0;
    $cap['ganancia']     = round($diff);
    $cap['ganancia_pct'] = $diffPct;
}
unset($cap);

// Para el form
$cuentas = $db->prepare("SELECT id, nombre FROM cuentas WHERE cobro_id=? AND activa=1 ORDER BY nombre");
$cuentas->execute([$cobro]); $cuentas = $cuentas->fetchAll();

$pageTitle   = 'Capital';
$pageSection = 'Capital';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header-row">
  <div>
    <h1>CAPITAL</h1>
    <p>// Gestión de capitalistas e inversiones</p>
  </div>
  <?php if (canDo('puede_crear_capitalista')): ?>
  <button class="btn btn-primary" onclick="openModal('modal-capitalista')">+ Nuevo Capitalista</button>
  <?php endif; ?>
</div>

<!-- Stats globales -->
<div class="stats-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:1.75rem">
  <div class="stat-card" style="border-color:#22c55e33">
    <div class="stat-label">SALDO EN CAJA</div>
    <div class="stat-value" style="color:#22c55e"><?= fmt($capitalGlobal) ?></div>
    <div class="stat-sub">Disponible ahora</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">TOTAL INVERTIDO</div>
    <div class="stat-value" style="color:var(--muted)"><?= fmt($totalAportado) ?></div>
    <div class="stat-sub"><?= count($capitalistas) ?> capitalistas</div>
  </div>
  <div class="stat-card orange">
    <div class="stat-label">EN CARTERA</div>
    <div class="stat-value"><?= fmt($carteraActiva) ?></div>
    <div class="stat-sub">Saldo activo por cobrar</div>
  </div>
  <div class="stat-card" style="border-color:#818cf833">
    <div class="stat-label">PATRIMONIO TOTAL</div>
    <div class="stat-value" style="color:#818cf8"><?= fmt($patrimonioNegocio) ?></div>
    <div class="stat-sub">Caja + Cartera activa</div>
  </div>
  <div class="stat-card" style="border-color:<?= $crecGlobal >= 0 ? '#22c55e33' : '#ef444433' ?>">
    <div class="stat-label">CRECIMIENTO</div>
    <div class="stat-value" style="color:<?= $crecGlobal >= 0 ? '#22c55e' : '#ef4444' ?>">
      <?= ($crecGlobal >= 0 ? '▲' : '▼') . abs($crecGlobalPct) ?>%
    </div>
    <div class="stat-sub"><?= ($crecGlobal >= 0 ? '+' : '') . fmt($crecGlobal) ?> vs invertido</div>
  </div>
</div>

<!-- Lista capitalistas -->
<?php if (empty($capitalistas)): ?>
<div class="card">
  <div class="empty-state">
    <span class="empty-icon">◆</span>
    <p>No hay capitalistas registrados. Crea uno para empezar.</p>
  </div>
</div>
<?php else: ?>

<?php foreach ($capitalistas as $cap):
  $color     = $cap['color'] ?? '#7c6aff';
  $diff      = $cap['ganancia'];
  $diffPct   = $cap['ganancia_pct'];
  $diffColor = $diff >= 0 ? '#22c55e' : '#ef4444';
  $diffIcon  = $diff >= 0 ? '▲' : '▼';
  $reditoPend = 0;
?>
<div class="item-card mb-2" style="border-color:<?= $color ?>22">
  <div class="item-card-header" onclick="toggleCard('cap-<?= $cap['capitalista_id'] ?>')">
    <div class="avatar" style="background:<?= $color ?>;color:#000">
      <?= strtoupper(substr($cap['nombre'],0,1)) ?>
    </div>
    <div class="item-card-info">
      <div class="item-card-name"><?= htmlspecialchars($cap['nombre']) ?></div>
      <div class="item-card-meta">
        <?= $cap['tipo'] === 'prestado' ? '💳 Capital prestado' : '🏦 Capital propio' ?>
        · <span style="color:<?= $color ?>;font-weight:600"><?= $cap['pct_participacion'] ?>% participación</span>
        <?php if ($cap['tipo'] === 'prestado' && $cap['tasa_redito'] > 0): ?>
          · <?= $cap['tasa_redito'] ?><?= $cap['tipo_redito']=='porcentaje'?'% mensual':' fijo/mes' ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="item-card-stats">
      <div>
        <div class="item-stat-label">Invertido</div>
        <div class="item-stat-val text-mono" style="color:var(--muted)"><?= fmt($cap['capital_neto']) ?></div>
      </div>
      <div>
        <div class="item-stat-label">En cartera</div>
        <div class="item-stat-val text-mono text-orange"><?= fmt($cap['cartera_suya']) ?></div>
      </div>
      <div>
        <div class="item-stat-label">En caja</div>
        <div class="item-stat-val text-mono text-green"><?= fmt($cap['caja_suya']) ?></div>
      </div>
      <div>
        <div class="item-stat-label">Patrimonio</div>
        <div class="item-stat-val text-mono" style="color:#818cf8"><?= fmt($cap['patrimonio_suyo']) ?></div>
      </div>
      <div>
        <div class="item-stat-label">Crecimiento</div>
        <div class="item-stat-val text-mono" style="color:<?= $diffColor ?>">
          <?= $diffIcon . abs($diffPct) ?>% <span style="font-size:.7rem"><?= ($diff>=0?'+':'').fmt($diff) ?></span>
        </div>
      </div>
    </div>
    <?php if ($cap['estado'] === 'liquidado'): ?>
    <span class="badge badge-muted">LIQUIDADO</span>
    <?php else: ?>
    <span class="badge badge-green">ACTIVO</span>
    <?php endif; ?>
  </div>

  <!-- Barra de participación -->
  <?php if ($cap['capital_neto'] > 0): ?>
  <div style="padding:0 1.25rem 0.5rem">
    <div style="display:flex;justify-content:space-between;font-family:var(--font-mono);font-size:0.6rem;color:var(--muted);margin-bottom:0.3rem">
      <span>Invertido <?= fmt($cap['capital_neto']) ?> · Patrimonio hoy <?= fmt($cap['patrimonio_suyo']) ?></span>
      <span style="color:<?= $diffColor ?>"><?= ($diff>=0?'+':'').fmt($diff) ?></span>
    </div>
    <div style="height:4px;background:var(--border);border-radius:2px;overflow:hidden">
      <?php $barPct = $cap['capital_neto'] > 0 ? min(round(($cap['patrimonio_suyo'] / $cap['capital_neto']) * 100), 200) : 0; ?>
      <div style="height:100%;width:<?= min($barPct,100) ?>%;background:<?= $diffColor ?>;border-radius:2px;transition:width 1s ease"></div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Detalle expandible -->
  <div class="item-card-detail" id="cap-<?= $cap['capitalista_id'] ?>">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-top:1rem">

      <!-- Resumen financiero -->
      <div>
        <div style="font-family:var(--font-mono);font-size:0.65rem;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:0.75rem">Resumen financiero</div>
        <?php
          $rows = [
            ['💰 Total aportado',         fmt($cap['total_aportado']),                   'green'],
            ['💸 Total retirado',          fmt($cap['total_retirado']),                   'orange'],
            ['📊 Capital neto invertido',  fmt($cap['capital_neto']),                     'blue'],
            ['% Participación',            $cap['pct_participacion'].'%',               'muted'],
            ['🏃 En cartera (su parte)',   fmt($cap['cartera_suya']),                     'orange'],
            ['💵 En caja (su parte)',      fmt($cap['caja_suya']),                        'green'],
            ['🏦 Patrimonio total',        fmt($cap['patrimonio_suyo']),                  'blue'],
            ['📈 Ganancia/Pérdida',        ($diff>=0?'+':'').fmt($diff).' ('.$diffPct.'%)',  $diff>=0?'green':'orange'],
          ];
          foreach ($rows as [$label, $val, $c2]):
        ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:0.45rem 0;border-bottom:1px solid var(--border);font-family:var(--font-mono);font-size:0.75rem">
          <span style="color:var(--muted)"><?= $label ?></span>
          <span class="text-<?= $c2 ?>" style="font-weight:600"><?= $val ?></span>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Acciones -->
      <div>
        <div style="font-family:var(--font-mono);font-size:0.65rem;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:0.75rem">Acciones</div>
        <div style="display:flex;flex-direction:column;gap:0.5rem">
          <?php if (canDo('puede_crear_capitalista')): ?>
          <button class="btn btn-success btn-sm" onclick="openMovimiento(<?= $cap['capitalista_id'] ?>, '<?= htmlspecialchars($cap['nombre']) ?>', 'abono')">
            ↑ Registrar Abono
          </button>
          <button class="btn btn-warning btn-sm" onclick="openMovimiento(<?= $cap['capitalista_id'] ?>, '<?= htmlspecialchars($cap['nombre']) ?>', 'retiro')">
            ↓ Registrar Retiro
          </button>
          <?php if ($cap['tipo']=='prestado' && $cap['tasa_redito'] > 0): ?>
          <button class="btn btn-info btn-sm" onclick="causarRedito(<?= $cap['capitalista_id'] ?>, '<?= htmlspecialchars($cap['nombre']) ?>', <?= $cap['caja_suya'] ?>, <?= $cap['tasa_redito'] ?>, '<?= $cap['tipo_redito'] ?>')">
            ◈ Causar Rédito Mensual
          </button>
          <?php endif; ?>
          <?php endif; ?>
          <button class="btn btn-ghost btn-sm" onclick="verHistorial(<?= $cap['capitalista_id'] ?>)">
            📋 Ver Historial
          </button>
        </div>
      </div>

    </div>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- ====== MODAL NUEVO CAPITALISTA ====== -->
<div class="modal-overlay" id="modal-capitalista">
  <div class="modal">
    <div class="modal-header">
      <h2>NUEVO CAPITALISTA</h2>
      <button class="modal-close" onclick="closeModal('modal-capitalista')">✕</button>
    </div>
    <div class="modal-body">
      <form id="form-capitalista">
        <div class="form-grid mb-2">
          <div class="field field-span2">
            <label>Nombre <span class="required">*</span></label>
            <input type="text" name="nombre" placeholder="Nombre del capitalista" required>
          </div>
          <div class="field">
            <label>Tipo</label>
            <select name="tipo" id="cap-tipo" onchange="toggleCamposPrestado()">
              <option value="propio">Propio (tuyo)</option>
              <option value="prestado">Prestado (inversionista)</option>
            </select>
          </div>
          <div class="field">
            <label>Color (para UI)</label>
            <input type="color" name="color" value="#7c6aff" style="height:38px;padding:2px">
          </div>
          <div class="field field-span2">
            <label>Descripción</label>
            <input type="text" name="descripcion" placeholder="Opcional">
          </div>
        </div>
        <div id="campos-prestado" style="display:none">
          <div class="divider"></div>
          <p style="font-family:var(--font-mono);font-size:0.68rem;color:var(--muted);margin-bottom:0.75rem;text-transform:uppercase;letter-spacing:1px">
            Condiciones del capital prestado
          </p>
          <div class="form-grid mb-2">
            <div class="field">
              <label>Monto inicial ($)</label>
              <input type="number" name="monto_inicial" placeholder="0" step="100000">
            </div>
            <div class="field">
              <label>Cuenta donde ingresa</label>
              <select name="cuenta_id">
                <option value="">— Seleccionar —</option>
                <?php foreach ($cuentas as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label>Tipo de rédito</label>
              <select name="tipo_redito">
                <option value="porcentaje">Porcentaje (%)</option>
                <option value="valor_fijo">Valor fijo ($)</option>
              </select>
            </div>
            <div class="field">
              <label>Tasa rédito mensual</label>
              <input type="number" name="tasa_redito" value="0" step="0.5" min="0" placeholder="0 = sin rédito">
            </div>
            <div class="field">
              <label>Frecuencia pago rédito</label>
              <select name="frecuencia_redito">
                <option value="mensual">Mensual</option>
                <option value="quincenal">Quincenal</option>
                <option value="libre">Libre</option>
              </select>
            </div>
            <div class="field">
              <label>Fecha inicio</label>
              <input type="date" name="fecha_inicio" value="<?= date('Y-m-d') ?>">
            </div>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-capitalista')">Cancelar</button>
      <button class="btn btn-primary" id="btn-cap" onclick="guardarCapitalista()">GUARDAR</button>
    </div>
  </div>
</div>

<!-- ====== MODAL MOVIMIENTO ====== -->
<div class="modal-overlay" id="modal-movimiento">
  <div class="modal">
    <div class="modal-header">
      <h2 id="mov-titulo">REGISTRAR MOVIMIENTO</h2>
      <button class="modal-close" onclick="closeModal('modal-movimiento')">✕</button>
    </div>
    <div class="modal-body">
      <form id="form-movimiento">
        <input type="hidden" name="capitalista_id" id="mov-cap-id">
        <input type="hidden" name="tipo" id="mov-tipo">
        <div class="form-grid">
          <div class="field field-span2">
            <label>Capitalista</label>
            <input type="text" id="mov-cap-nombre" disabled style="opacity:0.6">
          </div>
          <div class="field">
            <label>Monto ($) <span class="required">*</span></label>
            <input type="number" name="monto" id="mov-monto" step="10000" min="1" required>
          </div>
          <div class="field">
            <label>Fecha</label>
            <input type="date" name="fecha" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="field">
            <label>Cuenta <span class="required">*</span></label>
            <select name="cuenta_id" required>
              <option value="">— Seleccionar cuenta —</option>
              <?php foreach ($cuentas as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field field-span2">
            <label>Descripción</label>
            <input type="text" name="descripcion" placeholder="Opcional">
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-movimiento')">Cancelar</button>
      <button class="btn btn-primary" id="btn-mov" onclick="guardarMovimiento()">REGISTRAR</button>
    </div>
  </div>
</div>

<!-- ====== MODAL HISTORIAL ====== -->
<div class="modal-overlay" id="modal-historial">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h2 id="hist-titulo">HISTORIAL</h2>
      <button class="modal-close" onclick="closeModal('modal-historial')">✕</button>
    </div>
    <div class="modal-body" id="hist-body">
      <div style="text-align:center;padding:2rem"><span class="spinner"></span></div>
    </div>
  </div>
</div>

<?php
$extraScript = <<<JS
<script>
function toggleCamposPrestado() {
    const tipo = document.getElementById('cap-tipo').value;
    document.getElementById('campos-prestado').style.display = tipo === 'prestado' ? 'block' : 'none';
}

async function guardarCapitalista() {
    const btn  = document.getElementById('btn-cap');
    const data = Object.fromEntries(new FormData(document.getElementById('form-capitalista')));
    if (!data.nombre?.trim()) { toast('El nombre es obligatorio', 'error'); return; }
    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>';
    const res = await apiPost('/api/capital.php', { action: 'crear_capitalista', ...data });
    btn.disabled = false; btn.innerHTML = 'GUARDAR';
    if (res.ok) { toast(res.msg); closeModal('modal-capitalista'); setTimeout(()=>location.reload(),800); }
    else toast(res.msg || 'Error', 'error');
}

function openMovimiento(id, nombre, tipo, montoDefault) {
    const titulos = {
        abono:        '↑ REGISTRAR ABONO',
        retiro:       '↓ REGISTRAR RETIRO',
        redito_pagado:'✓ PAGAR RÉDITO',
    };
    document.getElementById('mov-titulo').textContent    = titulos[tipo] || 'MOVIMIENTO';
    document.getElementById('mov-cap-id').value          = id;
    document.getElementById('mov-tipo').value            = tipo;
    document.getElementById('mov-cap-nombre').value      = nombre;
    document.getElementById('mov-monto').value           = montoDefault || '';
    openModal('modal-movimiento');
}

async function causarRedito(id, nombre, saldo, tasa, tipoRedito) {
    const redito = tipoRedito === 'porcentaje' ? saldo * tasa / 100 : tasa;
    if (!confirm('¿Causar rédito de ' + fmt(redito) + ' para ' + nombre + '?')) return;
    const res = await apiPost('/api/capital.php', {
        action: 'movimiento', capitalista_id: id,
        tipo: 'redito_causado', monto: redito,
        descripcion: 'Rédito mensual causado', fecha: new Date().toISOString().split('T')[0]
    });
    if (res.ok) { toast(res.msg); setTimeout(()=>location.reload(),800); }
    else toast(res.msg || 'Error', 'error');
}

async function guardarMovimiento() {
    const btn  = document.getElementById('btn-mov');
    const data = Object.fromEntries(new FormData(document.getElementById('form-movimiento')));
    if (!data.monto || parseFloat(data.monto) <= 0) { toast('Ingresa el monto', 'error'); return; }
    if (!data.cuenta_id) { toast('Selecciona la cuenta', 'error'); return; }
    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>';
    const res = await apiPost('/api/capital.php', { action: 'movimiento', ...data });
    btn.disabled = false; btn.innerHTML = 'REGISTRAR';
    if (res.ok) { toast(res.msg); closeModal('modal-movimiento'); setTimeout(()=>location.reload(),800); }
    else toast(res.msg || 'Error', 'error');
}

async function verHistorial(id) {
    document.getElementById('hist-body').innerHTML = '<div style="text-align:center;padding:2rem"><span class="spinner"></span></div>';
    openModal('modal-historial');
    const res = await apiGet('/api/capital.php?action=historial&capitalista_id=' + id);
    if (!res.ok) { document.getElementById('hist-body').innerHTML = '<p class="text-muted text-mono" style="padding:1rem">Error al cargar</p>'; return; }
    const cap = res.capitalista;
    document.getElementById('hist-titulo').textContent = 'HISTORIAL — ' + cap.nombre.toUpperCase();
    let html = '<div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Tipo</th><th>Descripción</th><th>Monto</th></tr></thead><tbody>';
    if (!res.movimientos.length) {
        html += '<tr><td colspan="4" style="text-align:center;color:var(--muted);padding:2rem;font-family:var(--font-mono)">Sin movimientos</td></tr>';
    }
    res.movimientos.forEach(m => {
        const esEntrada = m.es_entrada == 1;
        const color     = esEntrada ? 'green' : 'orange';
        const signo     = esEntrada ? '+' : '-';
        html += '<tr>';
        html += '<td class="text-mono">' + m.fecha + '</td>';
        html += '<td><span class="badge badge-muted">' + m.tipo.replace(/_/g,' ').toUpperCase() + '</span></td>';
        html += '<td style="color:var(--text-soft)">' + (m.descripcion || '—') + '</td>';
        html += '<td class="' + color + ' text-mono fw-600">' + signo + fmt(m.monto) + '</td>';
        html += '</tr>';
    });
    html += '</tbody></table></div>';
    html += '<div style="display:flex;justify-content:space-between;padding:1rem;font-family:var(--font-mono);font-size:0.8rem;border-top:1px solid var(--border);margin-top:0.5rem">';
    html += '<span style="color:var(--muted)">Saldo actual</span>';
    html += '<span style="color:var(--accent);font-size:1.1rem;font-family:var(--font-display)">' + fmt(res.saldo) + '</span>';
    html += '</div>';
    document.getElementById('hist-body').innerHTML = html;
}
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>