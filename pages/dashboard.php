<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();

$db    = getDB();
$cobro = cobroActivo();

// ── Vista cobrador (sin puede_ver_dashboard) ─────────────────
if (!canDo('puede_ver_dashboard')) {

    // Cuotas pendientes/vencidas de hoy
    $stmtCuotas = $db->prepare("
        SELECT cu.*, d.nombre AS deudor, d.telefono,
               p.frecuencia_pago, p.valor_cuota AS monto_cuota, p.dias_mora, p.id AS prestamo_id
        FROM cuotas cu
        JOIN prestamos p ON p.id = cu.prestamo_id
        JOIN deudores  d ON d.id = p.deudor_id
        WHERE cu.cobro_id=? AND cu.estado IN ('pendiente','parcial')
          AND cu.fecha_vencimiento <= CURDATE() AND p.estado != 'anulado'
        ORDER BY cu.fecha_vencimiento ASC
    ");
    $stmtCuotas->execute([$cobro]);
    $cuotasPendientes = $stmtCuotas->fetchAll();

    // Total recaudado hoy
    $stmtHoy = $db->prepare("SELECT COALESCE(SUM(monto_pagado),0) FROM pagos
        WHERE cobro_id=? AND fecha_pago=CURDATE() AND (anulado=0 OR anulado IS NULL)");
    $stmtHoy->execute([$cobro]);
    $cobradoHoy = (float)$stmtHoy->fetchColumn();

    // Pagos realizados hoy
    $stmtPagosHoy = $db->prepare("
        SELECT pg.*, d.nombre AS deudor, c.nombre AS cuenta, cu.numero_cuota
        FROM pagos pg
        JOIN deudores d ON d.id = pg.deudor_id
        JOIN cuotas cu  ON cu.id = pg.cuota_id
        LEFT JOIN cuentas c ON c.id = pg.cuenta_id
        WHERE pg.cobro_id=? AND pg.fecha_pago=CURDATE()
          AND (pg.anulado=0 OR pg.anulado IS NULL)
        ORDER BY pg.created_at DESC
    ");
    $stmtPagosHoy->execute([$cobro]);
    $pagosHoy = $stmtPagosHoy->fetchAll();

    // Préstamos activos del cobro (todos — cobradores ven su ruta completa)
    $stmtPrestamos = $db->prepare("
        SELECT p.*, d.nombre AS deudor, d.telefono,
               (SELECT COUNT(*) FROM cuotas WHERE prestamo_id=p.id AND estado IN ('pendiente','parcial') AND fecha_vencimiento <= CURDATE()) AS cuotas_vencidas
        FROM prestamos p
        JOIN deudores d ON d.id = p.deudor_id
        WHERE p.cobro_id=? AND p.estado IN ('activo','en_mora','en_acuerdo')
        ORDER BY p.estado DESC, d.nombre ASC
    ");
    $stmtPrestamos->execute([$cobro]);
    $prestamosActivos = $stmtPrestamos->fetchAll();

    // Cuántos cobró hoy
    $cobrosRealizados = count($pagosHoy);

    $pageTitle   = 'Mi Cobro';
    $pageSection = 'Dashboard';
    require_once __DIR__ . '/../includes/header.php';
    ?>

    <div class="page-header page-header-row">
      <div>
        <h1>MI COBRO</h1>
        <p>// <?= date('l, d \d\e F \d\e Y') ?> — <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></p>
      </div>
      <?php if (canDo('puede_registrar_pago')): ?>
      <a href="/pages/pagos.php" class="btn btn-primary">💰 Registrar Pago</a>
      <?php endif; ?>
    </div>

    <!-- Stats del día -->
    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:1.5rem">
      <div class="stat-card green">
        <div class="stat-label">RECAUDADO HOY</div>
        <div class="stat-value"><?= fmt($cobradoHoy) ?></div>
        <div class="stat-sub"><?= $cobrosRealizados ?> cobro<?= $cobrosRealizados !== 1 ? 's' : '' ?> registrado<?= $cobrosRealizados !== 1 ? 's' : '' ?></div>
      </div>
      <div class="stat-card orange">
        <div class="stat-label">CUOTAS PENDIENTES</div>
        <div class="stat-value"><?= count($cuotasPendientes) ?></div>
        <div class="stat-sub">vencidas o de hoy</div>
      </div>
      <div class="stat-card blue">
        <div class="stat-label">PRÉSTAMOS ACTIVOS</div>
        <div class="stat-value"><?= count($prestamosActivos) ?></div>
        <div class="stat-sub">en este cobro</div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">

      <!-- Cuotas pendientes -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">CUOTAS POR COBRAR</span>
          <span class="badge badge-orange"><?= count($cuotasPendientes) ?></span>
        </div>
        <?php if (empty($cuotasPendientes)): ?>
        <div class="empty-state">
          <span class="empty-icon" style="color:var(--accent)">✓</span>
          <p>¡Al día! Sin cuotas pendientes.</p>
        </div>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Deudor</th><th>Teléfono</th><th>Vence</th><th>Cuota</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($cuotasPendientes as $cu):
                $diasAtraso = (int)floor((time() - strtotime($cu['fecha_vencimiento'])) / 86400);
                $esVencida  = $diasAtraso > 0;
              ?>
              <tr>
                <td style="font-weight:600"><?= htmlspecialchars($cu['deudor']) ?></td>
                <td class="text-mono text-muted" style="font-size:0.78rem">
                  <?php if ($cu['telefono']): ?>
                  <a href="https://wa.me/57<?= preg_replace('/\D/','',$cu['telefono']) ?>" target="_blank"
                     style="color:var(--accent);text-decoration:none">
                    <?= htmlspecialchars($cu['telefono']) ?>
                  </a>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td>
                  <?php if ($esVencida): ?>
                  <span class="badge badge-red" style="font-size:0.65rem">
                    <?= $diasAtraso ?>d atraso
                  </span>
                  <?php else: ?>
                  <span class="text-mono text-xs"><?= date('d M', strtotime($cu['fecha_vencimiento'])) ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-mono fw-600 orange"><?= fmt($cu['saldo_cuota']) ?></td>
                <td>
                  <?php if (canDo('puede_registrar_pago')): ?>
                  <a href="/pages/pagos.php?prestamo_id=<?= $cu['prestamo_id'] ?>"
                     class="btn btn-primary btn-sm">Cobrar</a>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <!-- Pagos de hoy -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">COBROS DE HOY</span>
          <span class="badge badge-green"><?= $cobrosRealizados ?></span>
        </div>
        <?php if (empty($pagosHoy)): ?>
        <div class="empty-state">
          <span class="empty-icon">◈</span>
          <p>Sin cobros registrados hoy.</p>
        </div>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Deudor</th><th>Cuota #</th><th>Monto</th><th>Cuenta</th></tr>
            </thead>
            <tbody>
              <?php foreach ($pagosHoy as $pg): ?>
              <tr>
                <td style="font-weight:600"><?= htmlspecialchars($pg['deudor']) ?></td>
                <td class="text-mono text-muted text-xs">#<?= $pg['numero_cuota'] ?></td>
                <td class="text-mono fw-600 green"><?= fmt($pg['monto_pagado']) ?></td>
                <td class="text-muted text-xs"><?= htmlspecialchars($pg['cuenta'] ?? '—') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr style="border-top:2px solid var(--border)">
                <td colspan="2" class="text-muted text-xs" style="text-align:right;padding-right:1rem">TOTAL</td>
                <td class="text-mono fw-600 green"><?= fmt($cobradoHoy) ?></td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
        <?php endif; ?>
      </div>

    </div>

    <!-- Préstamos asignados -->
    <div class="card" style="margin-top:1.5rem">
      <div class="card-header">
        <span class="card-title">MIS PRÉSTAMOS</span>
        <span class="text-mono text-xs text-muted"><?= count($prestamosActivos) ?> activos</span>
      </div>
      <?php if (empty($prestamosActivos)): ?>
      <div class="empty-state"><span class="empty-icon">◎</span><p>Sin préstamos activos.</p></div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Deudor</th><th>Teléfono</th><th>Estado</th>
              <th>Cuota</th><th>Saldo</th><th>Vencidas</th><th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($prestamosActivos as $p):
              $estadoClass = match($p['estado']) {
                'en_mora'    => 'badge-red',
                'en_acuerdo' => 'badge-orange',
                default      => 'badge-green'
              };
            ?>
            <tr>
              <td style="font-weight:600"><?= htmlspecialchars($p['deudor']) ?></td>
              <td class="text-mono text-muted" style="font-size:0.78rem">
                <?php if ($p['telefono']): ?>
                <a href="https://wa.me/57<?= preg_replace('/\D/','',$p['telefono']) ?>" target="_blank"
                   style="color:var(--accent);text-decoration:none">
                  <?= htmlspecialchars($p['telefono']) ?>
                </a>
                <?php else: ?>—<?php endif; ?>
              </td>
              <td><span class="badge <?= $estadoClass ?>"><?= strtoupper($p['estado']) ?></span></td>
              <td class="text-mono"><?= fmt($p['monto_cuota']) ?></td>
              <td class="text-mono fw-600 orange"><?= fmt($p['saldo_pendiente']) ?></td>
              <td>
                <?php if ($p['cuotas_vencidas'] > 0): ?>
                <span class="badge badge-red"><?= $p['cuotas_vencidas'] ?></span>
                <?php else: ?>
                <span style="color:var(--accent)">✓</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (canDo('puede_registrar_pago')): ?>
                <a href="/pages/pagos.php?prestamo_id=<?= $p['id'] ?>"
                   class="btn btn-ghost btn-sm">Cobrar</a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit; // Importante: no continuar al dashboard completo
}

// ── Dashboard completo (con puede_ver_dashboard) ─────────────

$db     = getDB();
$cobro  = cobroActivo();

// ---- Stats cartera ----
$cartera = $db->prepare("SELECT * FROM v_cartera_cobro WHERE cobro_id = ?");
$cartera->execute([$cobro]);
$stats = $cartera->fetch() ?: [];

// ---- Cobrado hoy ----
$stmtHoy = $db->prepare("SELECT COALESCE(SUM(monto_pagado),0) FROM pagos WHERE cobro_id=? AND fecha_pago=CURDATE() AND (anulado=0 OR anulado IS NULL)");
$stmtHoy->execute([$cobro]);
$cobradoHoy = $stmtHoy->fetchColumn();

// ---- Cuotas vencidas (para cobrador) ----
$stmtVenc = $db->prepare("SELECT cu.*, d.nombre AS deudor, d.telefono, p.frecuencia_pago, p.dias_mora
    FROM cuotas cu
    JOIN prestamos p ON p.id = cu.prestamo_id
    JOIN deudores  d ON d.id = p.deudor_id
    WHERE cu.cobro_id=? AND cu.estado IN ('pendiente','parcial') AND cu.fecha_vencimiento <= CURDATE() AND p.estado != 'anulado'
    ORDER BY cu.fecha_vencimiento ASC LIMIT 20");
$stmtVenc->execute([$cobro]);
$cuotasHoy = $stmtVenc->fetchAll();

// ---- En acuerdo ----
$stmtAcuerdo = $db->prepare("SELECT p.*, d.nombre AS deudor, d.telefono
    FROM prestamos p JOIN deudores d ON d.id = p.deudor_id
    WHERE p.cobro_id=? AND p.estado='en_acuerdo' AND p.estado != 'anulado' ORDER BY p.fecha_compromiso ASC");
$stmtAcuerdo->execute([$cobro]);
$enAcuerdo = $stmtAcuerdo->fetchAll();

// ---- Últimos pagos ----
$stmtPagos = $db->prepare("SELECT pg.*, d.nombre AS deudor, c.nombre AS cuenta
    FROM pagos pg
    JOIN deudores d ON d.id = pg.deudor_id
    LEFT JOIN cuentas c ON c.id = pg.cuenta_id
    WHERE pg.cobro_id=? ORDER BY pg.created_at DESC LIMIT 8");
$stmtPagos->execute([$cobro]);
$ultPagos = $stmtPagos->fetchAll();

// Total del día solo pagos no anulados
$stmtHoyReal = $db->prepare("SELECT COALESCE(SUM(monto_pagado),0) FROM pagos WHERE cobro_id=? AND fecha_pago=CURDATE() AND (anulado=0 OR anulado IS NULL)");
$stmtHoyReal->execute([$cobro]);
$totalHoyReal = (float)$stmtHoyReal->fetchColumn();

// ---- Saldo disponible en caja ----
$stmtSaldo = $db->prepare("SELECT COALESCE(SUM(saldo_actual),0) FROM v_saldo_cuentas WHERE cobro_id=?");
$stmtSaldo->execute([$cobro]);
$saldoCaja = (float)$stmtSaldo->fetchColumn();

// ---- Cobrado esta semana ----
$stmtSemana = $db->prepare("SELECT COALESCE(SUM(monto_pagado),0) FROM pagos WHERE cobro_id=? AND fecha_pago >= DATE(NOW() - INTERVAL WEEKDAY(NOW()) DAY) AND (anulado=0 OR anulado IS NULL)");
$stmtSemana->execute([$cobro]);
$cobradoSemana = (float)$stmtSemana->fetchColumn();

// ---- Cobrado este mes ----
$stmtMes = $db->prepare("SELECT COALESCE(SUM(monto_pagado),0) FROM pagos WHERE cobro_id=? AND MONTH(fecha_pago)=MONTH(CURDATE()) AND YEAR(fecha_pago)=YEAR(CURDATE()) AND (anulado=0 OR anulado IS NULL)");
$stmtMes->execute([$cobro]);
$cobradoMes = (float)$stmtMes->fetchColumn();

// ---- Próximos vencimientos (hoy + 3 días) ----
$stmtProximos = $db->prepare("SELECT COUNT(*) FROM cuotas cu JOIN prestamos p ON p.id=cu.prestamo_id WHERE cu.cobro_id=? AND cu.estado IN ('pendiente','parcial') AND cu.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY) AND p.estado != 'anulado'");
$stmtProximos->execute([$cobro]);
$proximosVenc = (int)$stmtProximos->fetchColumn();

// ---- Réditos pendientes (capitalistas con redito pendiente de pagar) ----
$stmtReditos = $db->prepare("
    SELECT cap.id AS capitalista_id, cap.nombre AS capitalista,
           vs.total_reditos_pagados,
           vs.saldo_actual
    FROM capitalistas cap
    JOIN v_saldo_capitalistas vs ON vs.capitalista_id = cap.id
    WHERE cap.cobro_id=? AND cap.tipo='prestado' AND cap.estado='activo'
      AND cap.tasa_redito > 0
");
$stmtReditos->execute([$cobro]);
$reditosPend = $stmtReditos->fetchAll();

// ── SALUD DEL NEGOCIO ────────────────────────────────────────
// Capital total invertido por capitalistas
$stmtAportes = $db->prepare("
    SELECT COALESCE(SUM(m.monto),0) 
    FROM capital_movimientos m
    JOIN capitalistas cap ON cap.id = m.capitalista_id
    WHERE m.cobro_id=? AND m.tipo='ingreso_capital' AND m.anulado=0
      AND cap.tipo IN ('propio','prestado')
");
$stmtAportes->execute([$cobro]);
$totalAportes = (float)$stmtAportes->fetchColumn();

// Retiros de capital
$stmtRetiros = $db->prepare("SELECT COALESCE(SUM(monto),0) FROM capital_movimientos WHERE cobro_id=? AND tipo='retiro_capital' AND anulado=0");
$stmtRetiros->execute([$cobro]);
$totalRetiros = (float)$stmtRetiros->fetchColumn();

// Cartera total (saldo pendiente activo)
$stmtCartera = $db->prepare("SELECT COALESCE(SUM(saldo_pendiente),0) FROM prestamos WHERE cobro_id=? AND estado NOT IN ('pagado','renovado','refinanciado','anulado')");
$stmtCartera->execute([$cobro]);
$carteraTotal = (float)$stmtCartera->fetchColumn();

// Total cobrado históricamente en pagos
$stmtCobrado = $db->prepare("SELECT COALESCE(SUM(monto_pagado),0) FROM pagos WHERE cobro_id=? AND (anulado=0 OR anulado IS NULL)");
$stmtCobrado->execute([$cobro]);
$totalCobradoHistorico = (float)$stmtCobrado->fetchColumn();

// Gastos y réditos pagados
$stmtGastos = $db->prepare("SELECT COALESCE(SUM(monto),0) FROM capital_movimientos WHERE cobro_id=? AND tipo='salida' AND anulado=0");
$stmtGastos->execute([$cobro]);
$totalGastos = (float)$stmtGastos->fetchColumn();

$stmtReditos = $db->prepare("SELECT COALESCE(SUM(monto),0) FROM capital_movimientos WHERE cobro_id=? AND tipo='redito' AND anulado=0");
$stmtReditos->execute([$cobro]);
$totalReditos = (float)$stmtReditos->fetchColumn();

// Cálculos de crecimiento
$capitalNeto     = $totalAportes - $totalRetiros;           // Lo que realmente pusieron
$patrimonioHoy   = $saldoCaja + $carteraTotal;              // Lo que vale el negocio hoy
$crecimiento     = $patrimonioHoy - $capitalNeto;           // Ganancia neta
$roiPct          = $capitalNeto > 0 ? round(($crecimiento / $capitalNeto) * 100, 1) : 0;
$gananciaReal    = $totalCobradoHistorico - $totalGastos - $totalReditos; // Intereses - egresos

// Cobros último mes para velocidad
$stmtVelMes = $db->prepare("SELECT COALESCE(SUM(monto_pagado),0) FROM pagos WHERE cobro_id=? AND fecha_pago >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND (anulado=0 OR anulado IS NULL)");
$stmtVelMes->execute([$cobro]);
$cobrosMes = (float)$stmtVelMes->fetchColumn();

$pageTitle   = 'Dashboard';
$pageSection = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header-row">
  <div>
    <h1>DASHBOARD</h1>
    <p>// <?= date('l, d \d\e F \d\e Y') ?> — <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></p>
  </div>
  <?php if (canDo('puede_crear_prestamo')): ?>
  <div class="btn-group">
    <a href="/pages/prestamos.php?action=nuevo" class="btn btn-primary">+ Nuevo Préstamo</a>
    <a href="/pages/pagos.php" class="btn btn-success">💰 Registrar Pago</a>
  </div>
  <?php endif; ?>
</div>

<!-- SALUD DEL NEGOCIO -->
<div class="card mt-2" style="border-color:#22c55e44;background:linear-gradient(135deg,rgba(34,197,94,0.04) 0%,transparent 60%)">
  <div class="card-header">
    <span class="card-title" style="color:#22c55e">◈ ESTADO DEL NEGOCIO</span>
    <span style="font-family:var(--font-mono);font-size:0.65rem;color:var(--muted)">Actualizado en tiempo real</span>
  </div>
  <div style="padding:1rem 1.25rem">

    <!-- Barra de progreso patrimonio vs capital -->
    <?php
      $barPct = $capitalNeto > 0 ? min(round(($patrimonioHoy / $capitalNeto) * 100), 200) : 100;
      $barColor = $roiPct >= 0 ? '#22c55e' : '#ef4444';
    ?>
    <div style="margin-bottom:1.25rem">
      <div style="display:flex;justify-content:space-between;margin-bottom:0.4rem">
        <span style="font-family:var(--font-mono);font-size:0.65rem;color:var(--muted)">CAPITAL INVERTIDO <?= fmt($capitalNeto) ?></span>
        <span style="font-family:var(--font-mono);font-size:0.65rem;color:#22c55e">PATRIMONIO HOY <?= fmt($patrimonioHoy) ?></span>
      </div>
      <div style="height:6px;background:var(--border);border-radius:3px;overflow:hidden">
        <div style="height:100%;width:<?= min($barPct, 100) ?>%;background:<?= $barColor ?>;border-radius:3px;transition:width 1s ease"></div>
      </div>
    </div>

    <!-- 4 métricas principales -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem">

      <div style="padding:1rem;background:var(--bg);border-radius:var(--radius);border:1px solid #22c55e33">
        <div style="font-family:var(--font-mono);font-size:0.6rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.4rem">Capital Invertido</div>
        <div style="font-family:var(--font-display);font-size:1.4rem;color:#e2e8f0"><?= fmt($capitalNeto) ?></div>
        <div style="font-size:0.65rem;color:var(--muted);margin-top:0.2rem">Lo que pusieron los socios</div>
      </div>

      <div style="padding:1rem;background:var(--bg);border-radius:var(--radius);border:1px solid #22c55e55">
        <div style="font-family:var(--font-mono);font-size:0.6rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.4rem">Patrimonio Actual</div>
        <div style="font-family:var(--font-display);font-size:1.4rem;color:#22c55e"><?= fmt($patrimonioHoy) ?></div>
        <div style="font-size:0.65rem;color:var(--muted);margin-top:0.2rem">Caja <?= fmt($saldoCaja) ?> + Cartera <?= fmt($carteraTotal) ?></div>
      </div>

      <div style="padding:1rem;background:var(--bg);border-radius:var(--radius);border:1px solid <?= $roiPct >= 0 ? '#22c55e33' : '#ef444433' ?>">
        <div style="font-family:var(--font-mono);font-size:0.6rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.4rem">Crecimiento Neto</div>
        <div style="font-family:var(--font-display);font-size:1.4rem;color:<?= $roiPct >= 0 ? '#22c55e' : '#ef4444' ?>">
          <?= ($crecimiento >= 0 ? '+' : '') . fmt($crecimiento) ?>
        </div>
        <div style="font-size:0.65rem;margin-top:0.2rem">
          <span style="color:<?= $roiPct >= 0 ? '#22c55e' : '#ef4444' ?>;font-weight:700">
            <?= ($roiPct >= 0 ? '▲' : '▼') . ' ' . abs($roiPct) ?>% ROI
          </span>
          <span style="color:var(--muted)"> desde el inicio</span>
        </div>
      </div>

      <div style="padding:1rem;background:var(--bg);border-radius:var(--radius);border:1px solid #818cf833">
        <div style="font-family:var(--font-mono);font-size:0.6rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.4rem">Cobrado en 30 días</div>
        <div style="font-family:var(--font-display);font-size:1.4rem;color:#818cf8"><?= fmt($cobrosMes) ?></div>
        <div style="font-size:0.65rem;color:var(--muted);margin-top:0.2rem">Total histórico <?= fmt($totalCobradoHistorico) ?></div>
      </div>

    </div>

    <!-- Fila secundaria: desglose de gastos -->
    <?php if ($totalGastos > 0 || $totalReditos > 0): ?>
    <div style="display:flex;gap:1rem;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border)">
      <div style="font-size:0.7rem;color:var(--muted)">
        Gastos operativos: <span style="color:#ef4444"><?= fmt($totalGastos) ?></span>
      </div>
      <div style="font-size:0.7rem;color:var(--muted)">
        Réditos pagados: <span style="color:#f59e0b"><?= fmt($totalReditos) ?></span>
      </div>
      <div style="font-size:0.7rem;color:var(--muted)">
        Ganancia real (cobros - egresos): <span style="color:#22c55e;font-weight:700"><?= fmt($gananciaReal) ?></span>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>


<!-- STATS FILA 1: Caja y cartera -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1rem">

  <div class="stat-card" style="border-color:#22c55e33">
    <div class="stat-label">SALDO EN CAJA</div>
    <div class="stat-value" style="color:#22c55e"><?= fmt($saldoCaja) ?></div>
    <div class="stat-sub">Disponible ahora</div>
  </div>

  <div class="stat-card">
    <div class="stat-label">CAPITAL PRESTADO</div>
    <div class="stat-value"><?= fmt($stats['total_prestado'] ?? 0) ?></div>
    <div class="stat-sub"><?= ($stats['prestamos_activos'] ?? 0) + ($stats['prestamos_en_mora'] ?? 0) + ($stats['prestamos_en_acuerdo'] ?? 0) ?> préstamos activos</div>
  </div>

  <div class="stat-card orange">
    <div class="stat-label">SALDO POR COBRAR</div>
    <div class="stat-value"><?= fmt($stats['total_por_cobrar'] ?? 0) ?></div>
    <div class="stat-sub"><?= ($stats['prestamos_en_mora'] ?? 0) ?> en mora · <?= fmt($stats['cartera_riesgo'] ?? 0) ?> en riesgo</div>
  </div>

  <div class="stat-card purple">
    <div class="stat-label">COBRADO HOY</div>
    <div class="stat-value"><?= fmt($totalHoyReal) ?></div>
    <div class="stat-sub"><?= count($cuotasHoy) ?> cuotas vencidas</div>
  </div>

  <div class="stat-card" style="border-color:#818cf833">
    <div class="stat-label">COBRADO ESTA SEMANA</div>
    <div class="stat-value" style="color:#818cf8"><?= fmt($cobradoSemana) ?></div>
    <div class="stat-sub"><?= $proximosVenc ?> cuotas próx. 3 días</div>
  </div>

  <div class="stat-card" style="border-color:#f59e0b33">
    <div class="stat-label">COBRADO ESTE MES</div>
    <div class="stat-value" style="color:#f59e0b"><?= fmt($cobradoMes) ?></div>
    <div class="stat-sub">
      <?php
        $pct = ($stats['total_por_cobrar'] ?? 0) > 0
          ? round(($cobradoMes / ($stats['total_por_cobrar'] ?? 1)) * 100, 1) : 0;
        echo $pct . '% de la cartera';
      ?>
    </div>
  </div>

</div>

<!-- ALERTAS -->
<?php if (!empty($reditosPend)): ?>
<div class="alert alert-warning mb-2">
  ⚠ Tienes <?= count($reditosPend) ?> rédito(s) pendientes de pagar a capitalistas.
  <a href="/pages/salidas.php?tipo=redito" style="color:inherit;text-decoration:underline;margin-left:0.5rem">Ver salidas →</a>
</div>
<?php endif; ?>

<?php if (!empty($enAcuerdo)): ?>
<div class="alert alert-info mb-2">
  📋 <?= count($enAcuerdo) ?> deudor(es) en acuerdo de pago.
  <?php foreach($enAcuerdo as $a): if($a['fecha_compromiso'] <= date('Y-m-d')): ?>
    <strong><?= htmlspecialchars($a['deudor']) ?></strong> debía pagar el <?= $a['fecha_compromiso'] ?>.
  <?php endif; endforeach; ?>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">

  <!-- CUOTAS DEL DÍA -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">COBROS PENDIENTES</span>
      <a href="/pages/prestamos.php?filtro=vencido" class="btn btn-ghost btn-sm">Ver todos</a>
    </div>
    <?php if (empty($cuotasHoy)): ?>
      <div class="empty-state">
        <span class="empty-icon">✓</span>
        <p>Sin cuotas vencidas hoy</p>
      </div>
    <?php else: ?>
    <div style="padding:0.5rem 0">
      <?php foreach ($cuotasHoy as $c): ?>
      <?php $dias = (int)$c['dias_mora']; ?>
      <div class="schedule-row <?= $dias > 0 ? 'vencida' : '' ?>">
        <div class="schedule-dot <?= $dias > 0 ? 'vencida' : 'pending' ?>"></div>
        <div style="flex:1;min-width:0">
          <div style="font-weight:600;font-size:0.8rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            <?= htmlspecialchars($c['deudor']) ?>
          </div>
          <div style="font-size:0.65rem;color:var(--muted)">
            <?= $dias > 0 ? "$dias días mora" : 'Vence hoy' ?> · <?= htmlspecialchars($c['telefono'] ?? '—') ?>
          </div>
        </div>
        <div style="text-align:right">
          <div class="text-green text-mono" style="font-weight:600"><?= fmt($c['saldo_cuota']) ?></div>
          <div style="font-size:0.62rem;color:var(--muted)">Cuota #<?= $c['numero_cuota'] ?></div>
        </div>
        <?php if (canDo('puede_registrar_pago')): ?>
        <a href="/pages/pagos.php?cuota=<?= $c['id'] ?>" class="btn btn-success btn-sm">Pagar</a>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- ÚLTIMOS PAGOS -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">ÚLTIMOS PAGOS</span>
      <a href="/pages/pagos.php" class="btn btn-ghost btn-sm">Historial</a>
    </div>
    <?php if (empty($ultPagos)): ?>
      <div class="empty-state">
        <span class="empty-icon">◎</span>
        <p>Sin pagos registrados</p>
      </div>
    <?php else: ?>
    <div style="padding:0.5rem 0">
      <?php foreach ($ultPagos as $p): ?>
      <div class="schedule-row" style="<?= $p['anulado'] ? 'opacity:0.45;text-decoration:line-through' : '' ?>">
        <div class="schedule-dot <?= $p['anulado'] ? '' : 'paid' ?>"></div>
        <div style="flex:1;min-width:0">
          <div style="font-weight:600;font-size:0.8rem"><?= htmlspecialchars($p['deudor']) ?></div>
          <div style="font-size:0.65rem;color:var(--muted)">
            <?= formatFechaPHP($p['fecha_pago']) ?> · <?= htmlspecialchars($p['cuenta'] ?? 'Efectivo') ?>
            <?= $p['anulado'] ? ' · <span style="color:#ef4444">ANULADO</span>' : '' ?>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:0.5rem">
          <div class="<?= $p['anulado'] ? 'text-muted' : 'text-green' ?> text-mono fw-600"><?= fmt($p['monto_pagado']) ?></div>
          <?php if (!$p['anulado'] && canDo('puede_anular_pago')): ?>
            <button class="btn btn-ghost btn-sm" style="color:#ef4444;padding:2px 6px;font-size:0.7rem"
              onclick="anularPagoDash(<?= $p['id'] ?>, '<?= htmlspecialchars($p['deudor']) ?>')"
              title="Anular pago">✕</button>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- RESUMEN CUENTAS + CARTERA -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-top:1.5rem">

  <!-- SALDO POR CUENTA -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">SALDO POR CUENTA</span>
      <a href="/pages/cuentas.php" class="btn btn-ghost btn-sm">Ver cuentas</a>
    </div>
    <div style="padding:0.5rem 0">
      <?php
        $stmtCtas = $db->prepare("SELECT nombre, tipo, saldo_actual FROM v_saldo_cuentas WHERE cobro_id=? ORDER BY saldo_actual DESC");
        $stmtCtas->execute([$cobro]);
        $cuentasDash = $stmtCtas->fetchAll();
        foreach ($cuentasDash as $ct):
      ?>
      <div class="schedule-row">
        <div style="flex:1">
          <div style="font-weight:600;font-size:0.82rem"><?= htmlspecialchars($ct['nombre']) ?></div>
          <div style="font-size:0.65rem;color:var(--muted)"><?= ucfirst($ct['tipo']) ?></div>
        </div>
        <div class="text-mono fw-600" style="color:<?= $ct['saldo_actual'] > 0 ? '#22c55e' : 'var(--muted)' ?>">
          <?= fmt($ct['saldo_actual']) ?>
        </div>
      </div>
      <?php endforeach; ?>
      <div class="schedule-row" style="border-top:1px solid var(--border);margin-top:0.5rem;padding-top:0.5rem">
        <div style="flex:1;font-size:0.75rem;color:var(--muted)">TOTAL CAJA</div>
        <div class="text-mono fw-600" style="color:#22c55e"><?= fmt($saldoCaja) ?></div>
      </div>
    </div>
  </div>

  <!-- TOP DEUDORES EN MORA -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">DEUDORES EN MORA</span>
      <a href="/pages/prestamos.php?filtro=en_mora" class="btn btn-ghost btn-sm">Ver todos</a>
    </div>
    <?php
      $stmtMora = $db->prepare("
        SELECT d.nombre, d.telefono, p.saldo_pendiente, p.dias_mora, p.id AS prestamo_id
        FROM prestamos p JOIN deudores d ON d.id=p.deudor_id
        WHERE p.cobro_id=? AND p.estado='en_mora'
        ORDER BY p.dias_mora DESC LIMIT 6
      ");
      $stmtMora->execute([$cobro]);
      $enMora = $stmtMora->fetchAll();
    ?>
    <?php if (empty($enMora)): ?>
    <div class="empty-state"><span class="empty-icon" style="color:#22c55e">✓</span><p>Sin deudores en mora</p></div>
    <?php else: ?>
    <div style="padding:0.5rem 0">
      <?php foreach ($enMora as $m): ?>
      <div class="schedule-row vencida">
        <div class="schedule-dot vencida"></div>
        <div style="flex:1;min-width:0">
          <div style="font-weight:600;font-size:0.82rem"><?= htmlspecialchars($m['nombre']) ?></div>
          <div style="font-size:0.65rem;color:var(--muted)"><?= htmlspecialchars($m['telefono']??'—') ?> · <?= $m['dias_mora'] ?> días mora</div>
        </div>
        <div style="text-align:right">
          <div class="text-mono fw-600" style="color:#ef4444"><?= fmt($m['saldo_pendiente']) ?></div>
          <a href="/pages/prestamo_detalle.php?id=<?= $m['prestamo_id'] ?>" style="font-size:0.62rem;color:var(--muted)">Ver →</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- RESUMEN CARTERA -->
<div class="card mt-2">
  <div class="card-header">
    <span class="card-title">ESTADO DE CARTERA</span>
  </div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem">
      <?php
        $estados = [
          ['label'=>'Activos',      'val'=>$stats['prestamos_activos']??0,     'class'=>'green'],
          ['label'=>'En Mora',      'val'=>$stats['prestamos_en_mora']??0,      'class'=>'orange'],
          ['label'=>'En Acuerdo',   'val'=>$stats['prestamos_en_acuerdo']??0,   'class'=>'blue'],
          ['label'=>'Pagados',      'val'=>$stats['prestamos_pagados']??0,      'class'=>'muted'],
          ['label'=>'Incobrables',  'val'=>$stats['prestamos_incobrables']??0,  'class'=>'red'],
        ];
        foreach ($estados as $e):
      ?>
      <div style="text-align:center;padding:0.75rem;background:var(--bg);border-radius:var(--radius);border:1px solid var(--border)">
        <div style="font-family:var(--font-display);font-size:2rem;color:var(--<?= $e['class'] === 'muted' ? 'muted' : ($e['class'] === 'green' ? 'accent' : ($e['class'] === 'orange' ? 'warn' : ($e['class'] === 'red' ? 'danger' : 'info'))) ?>)">
          <?= $e['val'] ?>
        </div>
        <div style="font-family:var(--font-mono);font-size:0.62rem;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-top:0.25rem">
          <?= $e['label'] ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
async function anularPagoDash(pagoId, deudor) {
    if (!confirm(`¿Anular el pago de ${deudor}? La cuota volverá a pendiente y se revertirá el movimiento de caja.`)) return;
    const res = await apiPost('/api/pagos.php', { action: 'anular', pago_id: pagoId });
    if (res.ok) { toast(res.msg, 'success'); setTimeout(() => location.reload(), 1200); }
    else toast(res.msg || 'Error al anular pago', 'error');
}
</script>

<?php
function formatFechaPHP($d) {
    if (!$d) return '—';
    return date('d M Y', strtotime($d));
}
require_once __DIR__ . '/../includes/footer.php';