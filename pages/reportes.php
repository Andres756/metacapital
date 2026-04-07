<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
if (!canDo('puede_ver_reportes')) { include __DIR__ . '/403.php'; exit; }

$db    = getDB();
$cobro = cobroActivo();

// ---- CARTERA ----
$stmtCartera = $db->prepare("
    SELECT p.id, p.estado, p.monto_prestado, p.interes_calculado, p.total_a_pagar,
           p.saldo_pendiente, p.dias_mora, p.fecha_inicio, p.fecha_fin_esperada,
           p.frecuencia_pago, p.num_cuotas, p.valor_cuota,
           d.nombre AS deudor, d.telefono,
           cap.nombre AS capitalista,
           (SELECT COUNT(*) FROM cuotas WHERE prestamo_id=p.id AND estado='pagado') AS cuotas_pagadas
    FROM prestamos p
    JOIN deudores d ON d.id = p.deudor_id
    LEFT JOIN capitalistas cap ON cap.id = p.capitalista_id
    WHERE p.cobro_id=? AND p.estado NOT IN ('renovado','refinanciado')
    ORDER BY FIELD(p.estado,'en_mora','en_acuerdo','activo','pagado','incobrable'), p.dias_mora DESC
");
$stmtCartera->execute([$cobro]);
$cartera = $stmtCartera->fetchAll();

$resCartera = [
    'total_prestado'   => 0, 'total_por_cobrar' => 0,
    'activos'          => 0, 'en_mora'          => 0,
    'en_acuerdo'       => 0, 'pagados'          => 0,
];
foreach ($cartera as $p) {
    $resCartera['total_prestado']   += $p['monto_prestado'];
    $resCartera['total_por_cobrar'] += ($p['estado'] !== 'pagado') ? $p['saldo_pendiente'] : 0;
    if (isset($resCartera[$p['estado']])) $resCartera[$p['estado']]++;
    else $resCartera['activos']++;
}

// ---- COBROS SEMANA ----
$inicioSemana = date('Y-m-d', strtotime('monday this week'));
$finSemana    = date('Y-m-d', strtotime('sunday this week'));

$stmtCobros = $db->prepare("
    SELECT
        DATE(m.fecha) AS dia,
        COUNT(*) AS num_pagos,
        SUM(m.monto) AS total_cobrado
    FROM capital_movimientos m
    WHERE m.cobro_id=? AND m.tipo='cobro_cuota' AND m.es_entrada=1 AND m.anulado=0
      AND m.fecha BETWEEN ? AND ?
    GROUP BY DATE(m.fecha)
    ORDER BY dia ASC
");
$stmtCobros->execute([$cobro, $inicioSemana, $finSemana]);
$cobrosSemana = $stmtCobros->fetchAll();

$totalSemana = array_sum(array_column($cobrosSemana, 'total_cobrado'));

// Cobros hoy
$stmtHoy = $db->prepare("
    SELECT m.monto, d.nombre AS deudor, c.nombre AS cuenta, m.descripcion, m.fecha
    FROM capital_movimientos m
    JOIN prestamos pr ON pr.id = m.prestamo_id
    JOIN deudores d   ON d.id  = pr.deudor_id
    LEFT JOIN cuentas c ON c.id = m.cuenta_id
    WHERE m.cobro_id=? AND m.tipo='cobro_cuota' AND m.anulado=0 AND DATE(m.fecha)=CURDATE()
    ORDER BY m.id DESC
");
$stmtHoy->execute([$cobro]);
$cobrosHoy = $stmtHoy->fetchAll();

// ---- CAPITAL E INVERSIONISTAS ----
$stmtCap = $db->prepare("
    SELECT vs.*, cap.tipo, cap.tasa_redito, cap.tipo_redito, cap.frecuencia_redito, cap.color
    FROM v_saldo_capitalistas vs
    JOIN capitalistas cap ON cap.id = vs.capitalista_id
    WHERE vs.cobro_id=?
    ORDER BY vs.saldo_actual DESC
");
$stmtCap->execute([$cobro]);
$capitalistas = $stmtCap->fetchAll();

$totalCapital  = array_sum(array_column($capitalistas, 'saldo_actual'));
$totalEntradas = array_sum(array_column($capitalistas, 'total_aportado'));
$totalSalidas  = array_sum(array_column($capitalistas, 'total_prestado'));

// ---- PROYECCIÓN: cuotas pendientes por mes ----
$stmtProyeccion = $db->prepare("
    SELECT
        DATE_FORMAT(cu.fecha_vencimiento, '%Y-%m') AS mes,
        COUNT(*) AS num_cuotas,
        SUM(cu.saldo_cuota) AS monto_esperado
    FROM cuotas cu
    JOIN prestamos p ON p.id = cu.prestamo_id
    WHERE p.cobro_id=? AND cu.estado IN ('pendiente','parcial')
      AND cu.fecha_vencimiento >= CURDATE()
    GROUP BY DATE_FORMAT(cu.fecha_vencimiento, '%Y-%m')
    ORDER BY mes ASC
    LIMIT 12
");
$stmtProyeccion->execute([$cobro]);
$proyeccion = $stmtProyeccion->fetchAll();

$pageTitle   = 'Reportes';
$pageSection = 'Reportes';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header-row">
  <div>
    <h1>REPORTES</h1>
    <p>// Análisis y proyecciones · <?= date('d \d\e F \d\e Y') ?></p>
  </div>
  <div class="btn-group">
    <button class="btn btn-ghost" onclick="exportarExcel()">📊 Exportar Excel</button>
    <button class="btn btn-ghost" onclick="window.print()">🖨 Imprimir</button>
  </div>
</div>

<!-- ============================================================ -->
<!-- REPORTE 1: CARTERA -->
<!-- ============================================================ -->
<div class="card mb-2">
  <div class="card-header">
    <span class="card-title">CARTERA GENERAL</span>
    <span class="text-mono text-xs text-muted"><?= count($cartera) ?> préstamos</span>
  </div>
  <div class="card-body">
    <div class="stats-grid" style="grid-template-columns:repeat(6,1fr);margin-bottom:1.25rem">
      <div class="stat-card purple">
        <div class="stat-label">Total Prestado</div>
        <div class="stat-value" style="font-size:1.1rem"><?= fmt($resCartera['total_prestado']) ?></div>
      </div>
      <div class="stat-card orange">
        <div class="stat-label">Por Cobrar</div>
        <div class="stat-value" style="font-size:1.1rem"><?= fmt($resCartera['total_por_cobrar']) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Activos</div>
        <div class="stat-value" style="font-size:1.5rem"><?= $resCartera['activos'] ?></div>
      </div>
      <div class="stat-card red">
        <div class="stat-label">En Mora</div>
        <div class="stat-value" style="font-size:1.5rem"><?= $resCartera['en_mora'] ?></div>
      </div>
      <div class="stat-card blue">
        <div class="stat-label">En Acuerdo</div>
        <div class="stat-value" style="font-size:1.5rem"><?= $resCartera['en_acuerdo'] ?></div>
      </div>
      <div class="stat-card green">
        <div class="stat-label">Pagados</div>
        <div class="stat-value" style="font-size:1.5rem"><?= $resCartera['pagados'] ?></div>
      </div>
    </div>
    <div class="table-wrap">
      <table id="tabla-cartera">
        <thead>
          <tr>
            <th>#</th><th>Deudor</th><th>Teléfono</th><th>Capital</th>
            <th>Total</th><th>Saldo</th><th>Cuotas</th>
            <th>Mora</th><th>Estado</th><th>Capitalista</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cartera as $p):
            $estadoClass = match($p['estado']) {
              'activo'      => 'badge-purple',
              'en_mora'     => 'badge-orange',
              'en_acuerdo'  => 'badge-blue',
              'pagado'      => 'badge-green',
              default       => 'badge-muted'
            };
          ?>
          <tr>
            <td class="text-mono">#<?= $p['id'] ?></td>
            <td><strong><?= htmlspecialchars($p['deudor']) ?></strong></td>
            <td class="text-mono text-muted"><?= htmlspecialchars($p['telefono'] ?? '—') ?></td>
            <td class="text-mono"><?= fmt($p['monto_prestado']) ?></td>
            <td class="text-mono"><?= fmt($p['total_a_pagar']) ?></td>
            <td class="text-mono fw-600 <?= $p['saldo_pendiente']>0?'orange':'' ?>"><?= fmt($p['saldo_pendiente']) ?></td>
            <td class="text-mono text-muted"><?= $p['cuotas_pagadas'] ?>/<?= $p['num_cuotas'] ?></td>
            <td class="text-mono <?= $p['dias_mora']>0?'red':'' ?>"><?= $p['dias_mora']>0 ? $p['dias_mora'].'d' : '—' ?></td>
            <td><span class="badge <?= $estadoClass ?>"><?= strtoupper($p['estado']) ?></span></td>
            <td class="text-muted text-xs"><?= htmlspecialchars($p['capitalista'] ?? '—') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="background:var(--surface)">
            <td colspan="3" style="padding:0.65rem 1rem;font-family:var(--font-mono);font-size:0.7rem;color:var(--muted)">TOTALES</td>
            <td class="text-mono fw-600"><?= fmt($resCartera['total_prestado']) ?></td>
            <td></td>
            <td class="text-mono fw-600 orange"><?= fmt($resCartera['total_por_cobrar']) ?></td>
            <td colspan="4"></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>

<!-- ============================================================ -->
<!-- REPORTE 2: COBROS -->
<!-- ============================================================ -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">

  <div class="card">
    <div class="card-header">
      <span class="card-title">COBROS ESTA SEMANA</span>
      <span class="text-mono text-xs" style="color:var(--accent)"><?= fmt($totalSemana) ?></span>
    </div>
    <div class="card-body">
      <?php
      $dias = ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
      $maxVal = max(1, max(array_column($cobrosSemana, 'total_cobrado') ?: [1]));
      $cobrosPorDia = array_column($cobrosSemana, null, 'dia');
      for ($i = 0; $i < 7; $i++):
        $fecha = date('Y-m-d', strtotime("monday this week +$i days"));
        $datos = $cobrosPorDia[$fecha] ?? null;
        $pct   = $datos ? round($datos['total_cobrado'] / $maxVal * 100) : 0;
        $esHoy = $fecha === date('Y-m-d');
      ?>
      <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.6rem">
        <span style="font-family:var(--font-mono);font-size:0.68rem;width:28px;color:<?= $esHoy?'var(--accent)':'var(--muted)' ?>"><?= $dias[$i] ?></span>
        <div style="flex:1;background:var(--bg);border-radius:3px;height:8px;overflow:hidden">
          <div style="width:<?= $pct ?>%;height:100%;background:<?= $esHoy?'var(--accent)':'var(--accent2)' ?>;border-radius:3px"></div>
        </div>
        <span style="font-family:var(--font-mono);font-size:0.68rem;width:80px;text-align:right;color:<?= $esHoy?'var(--accent)':'var(--text)' ?>">
          <?= $datos ? fmt($datos['total_cobrado']) : '—' ?>
        </span>
        <?php if ($datos): ?>
        <span style="font-size:0.6rem;color:var(--muted)"><?= $datos['num_pagos'] ?>p</span>
        <?php endif; ?>
      </div>
      <?php endfor; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title">COBROS DE HOY</span>
      <span class="text-mono text-xs" style="color:var(--accent)"><?= fmt(array_sum(array_column($cobrosHoy,'monto'))) ?></span>
    </div>
    <?php if (empty($cobrosHoy)): ?>
    <div class="empty-state" style="padding:2rem"><span class="empty-icon">◈</span><p>Sin cobros hoy</p></div>
    <?php else: ?>
    <div style="max-height:280px;overflow-y:auto">
      <?php foreach ($cobrosHoy as $c): ?>
      <div class="schedule-row" style="padding:0.65rem 1rem">
        <div class="schedule-dot paid"></div>
        <div style="flex:1">
          <div style="font-size:0.82rem;font-weight:600"><?= htmlspecialchars($c['deudor']) ?></div>
          <div style="font-size:0.65rem;color:var(--muted);font-family:var(--font-mono)"><?= htmlspecialchars($c['cuenta']??'—') ?></div>
        </div>
        <div style="font-family:var(--font-mono);font-weight:600;color:var(--accent)"><?= fmt($c['monto']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ============================================================ -->
<!-- REPORTE 3: CAPITAL -->
<!-- ============================================================ -->
<div class="card mb-2">
  <div class="card-header">
    <span class="card-title">CAPITAL E INVERSIONISTAS</span>
  </div>
  <div class="card-body">
    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:1.25rem">
      <div class="stat-card purple">
        <div class="stat-label">Capital Disponible</div>
        <div class="stat-value"><?= fmt($totalCapital) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Ingresado</div>
        <div class="stat-value"><?= fmt($totalEntradas) ?></div>
      </div>
      <div class="stat-card orange">
        <div class="stat-label">Total Movido</div>
        <div class="stat-value"><?= fmt($totalSalidas) ?></div>
      </div>
    </div>
    <div class="table-wrap">
      <table id="tabla-capital">
        <thead>
          <tr><th>Capitalista</th><th>Tipo</th><th>Tasa</th><th>Total Ingresado</th><th>Total Movido</th><th>Réditos Pagados</th><th>Saldo Actual</th></tr>
        </thead>
        <tbody>
          <?php foreach ($capitalistas as $c): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:0.5rem">
                <div style="width:10px;height:10px;border-radius:50%;background:<?= $c['color']??'#7c6aff' ?>"></div>
                <strong><?= htmlspecialchars($c['nombre']) ?></strong>
              </div>
            </td>
            <td><span class="badge badge-muted"><?= ucfirst($c['tipo']) ?></span></td>
            <td class="text-mono"><?= $c['tasa_redito']>0 ? $c['tasa_redito'].'%' : '—' ?></td>
            <td class="text-mono"><?= fmt($c['total_aportado']) ?></td>
            <td class="text-mono orange"><?= fmt($c['total_prestado']) ?></td>
            <td class="text-mono"><?= fmt($c['total_reditos_pagados']) ?></td>
            <td class="text-mono fw-600 <?= $c['saldo_actual']>0?'green':'orange' ?>"><?= fmt($c['saldo_actual']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="background:var(--surface)">
            <td colspan="3" style="padding:0.65rem 1rem;font-family:var(--font-mono);font-size:0.7rem;color:var(--muted)">TOTAL</td>
            <td class="text-mono fw-600"><?= fmt($totalEntradas) ?></td>
            <td class="text-mono fw-600 orange"><?= fmt($totalSalidas) ?></td>
            <td></td>
            <td class="text-mono fw-600 green"><?= fmt($totalCapital) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>

<!-- ============================================================ -->
<!-- REPORTE 4: PROYECCIÓN -->
<!-- ============================================================ -->
<div class="card mb-2">
  <div class="card-header">
    <span class="card-title">PROYECCIÓN DE INGRESOS</span>
    <span class="text-mono text-xs text-muted">Próximos 12 meses</span>
  </div>
  <div class="card-body">
    <?php if (empty($proyeccion)): ?>
    <div class="empty-state"><span class="empty-icon">◈</span><p>Sin cuotas proyectadas</p></div>
    <?php else:
      $maxProy = max(array_column($proyeccion, 'monto_esperado'));
    ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:0.75rem;margin-bottom:1.25rem">
      <?php foreach ($proyeccion as $mes):
        $pct = round($mes['monto_esperado'] / $maxProy * 100);
        $esEsteMes = substr($mes['mes'],0,7) === date('Y-m');
      ?>
      <div style="padding:0.85rem;background:var(--bg);border:1px solid <?= $esEsteMes?'var(--accent)':'var(--border)' ?>;border-radius:var(--radius)">
        <div style="font-family:var(--font-mono);font-size:0.65rem;color:var(--muted);margin-bottom:0.35rem">
          <?= date('M Y', strtotime($mes['mes'].'-01')) ?>
          <?= $esEsteMes ? '<span style="color:var(--accent)"> ← HOY</span>' : '' ?>
        </div>
        <div style="font-family:var(--font-display);font-size:1.3rem;color:<?= $esEsteMes?'var(--accent)':'var(--text)' ?>"><?= fmt($mes['monto_esperado']) ?></div>
        <div style="margin-top:0.5rem;background:var(--surface);border-radius:3px;height:4px">
          <div style="width:<?= $pct ?>%;height:100%;background:<?= $esEsteMes?'var(--accent)':'var(--accent2)' ?>;border-radius:3px"></div>
        </div>
        <div style="font-family:var(--font-mono);font-size:0.6rem;color:var(--muted);margin-top:0.3rem"><?= $mes['num_cuotas'] ?> cuotas</div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="table-wrap">
      <table id="tabla-proyeccion">
        <thead>
          <tr><th>Mes</th><th>Cuotas esperadas</th><th>Monto proyectado</th></tr>
        </thead>
        <tbody>
          <?php foreach ($proyeccion as $mes): ?>
          <tr>
            <td class="text-mono"><?= date('F Y', strtotime($mes['mes'].'-01')) ?></td>
            <td class="text-mono"><?= $mes['num_cuotas'] ?></td>
            <td class="text-mono fw-600 green"><?= fmt($mes['monto_esperado']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="background:var(--surface)">
            <td style="padding:0.65rem 1rem;font-family:var(--font-mono);font-size:0.7rem;color:var(--muted)">TOTAL PROYECTADO</td>
            <td class="text-mono fw-600"><?= array_sum(array_column($proyeccion,'num_cuotas')) ?></td>
            <td class="text-mono fw-600 green"><?= fmt(array_sum(array_column($proyeccion,'monto_esperado'))) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
function exportarExcel() {
    var wb = XLSX.utils.book_new();

    // Hoja 1: Cartera
    var ws1 = XLSX.utils.table_to_sheet(document.getElementById('tabla-cartera'));
    XLSX.utils.book_append_sheet(wb, ws1, 'Cartera');

    // Hoja 2: Capital
    var ws2 = XLSX.utils.table_to_sheet(document.getElementById('tabla-capital'));
    XLSX.utils.book_append_sheet(wb, ws2, 'Capital');

    // Hoja 3: Proyección
    var ws3 = XLSX.utils.table_to_sheet(document.getElementById('tabla-proyeccion'));
    XLSX.utils.book_append_sheet(wb, ws3, 'Proyeccion');

    var fecha = new Date().toISOString().split('T')[0];
    XLSX.writeFile(wb, 'META_Capital_Reporte_' + fecha + '.xlsx');
    toast('Exportando Excel... ✓');
}
</script>