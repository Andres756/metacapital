<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();

$db    = getDB();
$cobro = cobroActivo();

// ── Vista cobrador ─────────────────────────────────────────
if (!canDo('puede_ver_dashboard')) {
    $stmtCuotas = $db->prepare("
        SELECT cu.*, d.nombre AS deudor, d.telefono,
               p.frecuencia_pago, p.valor_cuota AS monto_cuota, p.dias_mora, p.id AS prestamo_id
        FROM cuotas cu JOIN prestamos p ON p.id=cu.prestamo_id JOIN deudores d ON d.id=p.deudor_id
        WHERE cu.cobro_id=? AND cu.estado IN ('pendiente','parcial')
          AND cu.fecha_vencimiento <= CURDATE() AND p.estado != 'anulado'
        ORDER BY cu.fecha_vencimiento ASC
    ");
    $stmtCuotas->execute([$cobro]);
    $cuotasPendientes = $stmtCuotas->fetchAll();

    $stmtHoy = $db->prepare("SELECT COALESCE(SUM(monto_pagado),0) FROM pagos WHERE cobro_id=? AND fecha_pago=CURDATE() AND (anulado=0 OR anulado IS NULL)");
    $stmtHoy->execute([$cobro]);
    $cobradoHoy = (float)$stmtHoy->fetchColumn();

    $stmtPagosHoy = $db->prepare("SELECT pg.*, d.nombre AS deudor, cu.numero_cuota FROM pagos pg JOIN deudores d ON d.id=pg.deudor_id JOIN cuotas cu ON cu.id=pg.cuota_id WHERE pg.cobro_id=? AND pg.fecha_pago=CURDATE() AND (pg.anulado=0 OR pg.anulado IS NULL) ORDER BY pg.created_at DESC");
    $stmtPagosHoy->execute([$cobro]);
    $pagosHoy = $stmtPagosHoy->fetchAll();

    $stmtPrestamos = $db->prepare("SELECT p.*, d.nombre AS deudor, d.telefono, (SELECT COUNT(*) FROM cuotas WHERE prestamo_id=p.id AND estado IN ('pendiente','parcial') AND fecha_vencimiento <= CURDATE()) AS cuotas_vencidas FROM prestamos p JOIN deudores d ON d.id=p.deudor_id WHERE p.cobro_id=? AND p.estado IN ('activo','en_mora','en_acuerdo') ORDER BY p.estado DESC, d.nombre ASC");
    $stmtPrestamos->execute([$cobro]);
    $prestamosActivos = $stmtPrestamos->fetchAll();

    $pageTitle = 'Mi Cobro'; $pageSection = 'Dashboard';
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <div class="page-header page-header-row">
      <div><h1>MI COBRO</h1><p>// <?= date('l, d \d\e F \d\e Y') ?> — <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></p></div>
      <?php if (canDo('puede_registrar_pago')): ?><a href="/pages/pagos.php" class="btn btn-primary">$ Registrar Pago</a><?php endif; ?>
    </div>
    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:1.5rem">
      <div class="stat-card green"><div class="stat-label">RECAUDADO HOY</div><div class="stat-value"><?= fmt($cobradoHoy) ?></div><div class="stat-sub"><?= count($pagosHoy) ?> cobros</div></div>
      <div class="stat-card orange"><div class="stat-label">CUOTAS PENDIENTES</div><div class="stat-value"><?= count($cuotasPendientes) ?></div><div class="stat-sub">vencidas o de hoy</div></div>
      <div class="stat-card blue"><div class="stat-label">PRÉSTAMOS ACTIVOS</div><div class="stat-value"><?= count($prestamosActivos) ?></div></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
      <div class="card">
        <div class="card-header"><span class="card-title">CUOTAS POR COBRAR</span><span class="badge badge-orange"><?= count($cuotasPendientes) ?></span></div>
        <?php if (empty($cuotasPendientes)): ?>
        <div class="empty-state"><span class="empty-icon" style="color:var(--accent)">✓</span><p>¡Al día!</p></div>
        <?php else: ?>
        <div class="table-wrap"><table><thead><tr><th>Deudor</th><th>Atraso</th><th>Cuota</th><th></th></tr></thead><tbody>
        <?php foreach ($cuotasPendientes as $cu): $d = (int)floor((time()-strtotime($cu['fecha_vencimiento']))/86400); ?>
        <tr>
          <td style="font-weight:600"><?= htmlspecialchars($cu['deudor']) ?></td>
          <td><?php if ($d>0): ?><span class="badge badge-red"><?= $d ?>d</span><?php else: ?><span class="badge badge-muted">Hoy</span><?php endif; ?></td>
          <td class="text-mono fw-600 orange"><?= fmt($cu['saldo_cuota']) ?></td>
          <td><?php if (canDo('puede_registrar_pago')): ?><a href="/pages/pagos.php?prestamo_id=<?= $cu['prestamo_id'] ?>" class="btn btn-primary btn-sm">Cobrar</a><?php endif; ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody></table></div>
        <?php endif; ?>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title">COBROS DE HOY</span><span class="badge badge-green"><?= count($pagosHoy) ?></span></div>
        <?php if (empty($pagosHoy)): ?>
        <div class="empty-state"><span class="empty-icon">◈</span><p>Sin cobros hoy</p></div>
        <?php else: ?>
        <div class="table-wrap"><table><thead><tr><th>Deudor</th><th>Cuota</th><th>Monto</th></tr></thead><tbody>
        <?php foreach ($pagosHoy as $pg): ?>
        <tr><td style="font-weight:600"><?= htmlspecialchars($pg['deudor']) ?></td><td class="text-mono text-muted">#<?= $pg['numero_cuota'] ?></td><td class="text-mono fw-600 green"><?= fmt($pg['monto_pagado']) ?></td></tr>
        <?php endforeach; ?>
        </tbody><tfoot><tr><td colspan="2" style="text-align:right;color:var(--muted);font-size:0.72rem">TOTAL</td><td class="text-mono fw-600 green"><?= fmt($cobradoHoy) ?></td></tr></tfoot></table></div>
        <?php endif; ?>
      </div>
    </div>
    <?php require_once __DIR__ . '/../includes/footer.php'; exit; ?>
<?php }

// ── Dashboard admin ────────────────────────────────────────

// Cobros del usuario
if ($_SESSION['rol'] === 'superadmin') {
    $stmtCobros = $db->query("SELECT id, nombre FROM cobros WHERE activo=1 ORDER BY nombre");
} else {
    $stmtCobros = $db->prepare("SELECT c.id, c.nombre FROM cobros c JOIN usuario_cobro uc ON uc.cobro_id=c.id WHERE uc.usuario_id=? AND c.activo=1 ORDER BY c.nombre");
    $stmtCobros->execute([$_SESSION['usuario_id']]);
}
$todosCobros = $stmtCobros->fetchAll();
$cobrosIds   = array_column($todosCobros, 'id');

// Filtro de cobro
$filtroCobro = (int)($_GET['cobro'] ?? 0);
if ($filtroCobro > 0 && in_array($filtroCobro, $cobrosIds)) {
    $scopeIds    = [$filtroCobro];
    $scopeWhere  = 'cobro_id=?';
    $scopeParams = [$filtroCobro];
} else {
    $filtroCobro = 0;
    $scopeIds    = $cobrosIds;
    $phs         = implode(',', array_fill(0, count($cobrosIds), '?'));
    $scopeWhere  = $phs ? "cobro_id IN ($phs)" : '1=0';
    $scopeParams = $cobrosIds;
}

// Helper query
$q = fn($sql, $p=[]) => (function() use ($db,$sql,$p) { $s=$db->prepare($sql); $s->execute($p); return (float)$s->fetchColumn(); })();
$qi = fn($sql, $p=[]) => (int)(function() use ($db,$sql,$p) { $s=$db->prepare($sql); $s->execute($p); return $s->fetchColumn(); })();

// ── KPIs ───────────────────────────────────────────────────
// Deudores únicos con préstamos activos
$stmtD = $db->prepare("SELECT COUNT(DISTINCT deudor_id) FROM prestamos WHERE $scopeWhere AND estado NOT IN ('anulado','pagado','renovado','refinanciado')");
$stmtD->execute($scopeParams);
$totalDeudores = (int)$stmtD->fetchColumn();

// Total deudores registrados en cobros
$stmtDReg = $db->prepare("SELECT COUNT(DISTINCT dc.deudor_id) FROM deudor_cobro dc WHERE ".str_replace('cobro_id','dc.cobro_id',$scopeWhere));
$stmtDReg->execute($scopeParams);
$totalDeudoresReg = (int)$stmtDReg->fetchColumn();

// Capital inicial (aportes - retiros)
$totalAportes = $q("SELECT COALESCE(SUM(monto),0) FROM capital_movimientos WHERE $scopeWhere AND tipo='ingreso_capital' AND (anulado=0 OR anulado IS NULL)", $scopeParams);
$totalRetiros = $q("SELECT COALESCE(SUM(monto),0) FROM capital_movimientos WHERE $scopeWhere AND tipo IN ('retiro_capital','liquidacion') AND (anulado=0 OR anulado IS NULL)", $scopeParams);
$capitalInicial = $totalAportes - $totalRetiros;

// Cartera activa (saldo pendiente)
$carteraActiva = $q("SELECT COALESCE(SUM(saldo_pendiente),0) FROM prestamos WHERE $scopeWhere AND estado NOT IN ('anulado','pagado','renovado','refinanciado')", $scopeParams);

// Saldo en caja
$saldoCaja = 0;
foreach ($scopeIds as $cid) { $saldoCaja += getSaldoCaja($db, $cid); }

// Patrimonio hoy
$patrimonioHoy = $saldoCaja + $carteraActiva;
$crecimiento   = $patrimonioHoy - $capitalInicial;
$roiPct        = $capitalInicial > 0 ? round(($crecimiento / $capitalInicial) * 100, 1) : 0;

// En mora
$stmtMoraC = $db->prepare("SELECT COUNT(*), COALESCE(SUM(saldo_pendiente),0) FROM prestamos WHERE $scopeWhere AND estado='en_mora'");
$stmtMoraC->execute($scopeParams);
[$countMora, $saldoMora] = $stmtMoraC->fetch(\PDO::FETCH_NUM);

// En acuerdo
$stmtAcuerdoC = $db->prepare("SELECT COUNT(*), COALESCE(SUM(saldo_pendiente),0) FROM prestamos WHERE $scopeWhere AND estado='en_acuerdo'");
$stmtAcuerdoC->execute($scopeParams);
[$countAcuerdo, $saldoAcuerdo] = $stmtAcuerdoC->fetch(\PDO::FETCH_NUM);

// Activos
$stmtActivoC = $db->prepare("SELECT COUNT(*), COALESCE(SUM(saldo_pendiente),0) FROM prestamos WHERE $scopeWhere AND estado='activo'");
$stmtActivoC->execute($scopeParams);
[$countActivo, $saldoActivo] = $stmtActivoC->fetch(\PDO::FETCH_NUM);

// Incobrable — préstamos en mora > 60 días
$stmtInc = $db->prepare("SELECT COUNT(*), COALESCE(SUM(saldo_pendiente),0) FROM prestamos WHERE $scopeWhere AND estado='en_mora' AND dias_mora > 60");
$stmtInc->execute($scopeParams);
[$countInc, $saldoInc] = $stmtInc->fetch(\PDO::FETCH_NUM);
$pctInc = $carteraActiva > 0 ? round(($saldoInc / $carteraActiva) * 100, 1) : 0;

// Cobrado hoy
$cobradoHoy = $q("SELECT COALESCE(SUM(monto_pagado),0) FROM pagos WHERE $scopeWhere AND fecha_pago=CURDATE() AND (anulado=0 OR anulado IS NULL)", $scopeParams);

// Cobrado semana
$cobradoSemana = $q("SELECT COALESCE(SUM(monto_pagado),0) FROM pagos WHERE $scopeWhere AND fecha_pago >= DATE(NOW() - INTERVAL WEEKDAY(NOW()) DAY) AND (anulado=0 OR anulado IS NULL)", $scopeParams);

// Cobrado mes
$cobradoMes = $q("SELECT COALESCE(SUM(monto_pagado),0) FROM pagos WHERE $scopeWhere AND MONTH(fecha_pago)=MONTH(CURDATE()) AND YEAR(fecha_pago)=YEAR(CURDATE()) AND (anulado=0 OR anulado IS NULL)", $scopeParams);

// Gastos y réditos
$totalGastos  = $q("SELECT COALESCE(SUM(monto),0) FROM capital_movimientos WHERE $scopeWhere AND tipo='salida' AND (anulado=0 OR anulado IS NULL)", $scopeParams);
$totalReditos = $q("SELECT COALESCE(SUM(monto),0) FROM capital_movimientos WHERE $scopeWhere AND tipo='redito' AND (anulado=0 OR anulado IS NULL)", $scopeParams);
$gananciaReal = $cobradoMes - $totalGastos - $totalReditos;

// Cuotas vencidas
$stmtVenc = $db->prepare("SELECT COUNT(*) FROM cuotas cu JOIN prestamos p ON p.id=cu.prestamo_id WHERE cu.$scopeWhere AND cu.estado IN ('pendiente','parcial') AND cu.fecha_vencimiento <= CURDATE() AND p.estado != 'anulado'");
$stmtVenc->execute($scopeParams);
$vencidasHoy = (int)$stmtVenc->fetchColumn();

// Préstamos nuevos este mes
$stmtNuevos = $db->prepare("SELECT COUNT(*) FROM prestamos WHERE $scopeWhere AND MONTH(fecha_inicio)=MONTH(CURDATE()) AND YEAR(fecha_inicio)=YEAR(CURDATE()) AND estado != 'anulado'");
$stmtNuevos->execute($scopeParams);
$prestamosNuevosMes = (int)$stmtNuevos->fetchColumn();

// Top mora
$stmtTopMora = $db->prepare("SELECT d.nombre, d.telefono, p.saldo_pendiente, p.dias_mora, p.id AS pid, c.nombre AS cobro_nombre FROM prestamos p JOIN deudores d ON d.id=p.deudor_id JOIN cobros c ON c.id=p.cobro_id WHERE p.$scopeWhere AND p.estado='en_mora' ORDER BY p.dias_mora DESC LIMIT 7");
$stmtTopMora->execute($scopeParams);
$topMora = $stmtTopMora->fetchAll();

// Últimos pagos
$stmtUltPagos = $db->prepare("SELECT pg.monto_pagado, pg.created_at, d.nombre AS deudor, cu.numero_cuota, co.nombre AS cobro_nombre FROM pagos pg JOIN deudores d ON d.id=pg.deudor_id JOIN cuotas cu ON cu.id=pg.cuota_id JOIN cobros co ON co.id=pg.cobro_id WHERE pg.$scopeWhere AND pg.fecha_pago=CURDATE() AND (pg.anulado=0 OR pg.anulado IS NULL) ORDER BY pg.created_at DESC LIMIT 8");
$stmtUltPagos->execute($scopeParams);
$ultPagosHoy = $stmtUltPagos->fetchAll();

// Acuerdos vencidos
$stmtAcuerdos = $db->prepare("SELECT p.id, p.saldo_pendiente, p.fecha_compromiso, d.nombre AS deudor FROM prestamos p JOIN deudores d ON d.id=p.deudor_id WHERE p.$scopeWhere AND p.estado='en_acuerdo' ORDER BY p.fecha_compromiso ASC LIMIT 5");
$stmtAcuerdos->execute($scopeParams);
$acuerdos = $stmtAcuerdos->fetchAll();

// Réditos pendientes
$stmtRed = $db->prepare("SELECT COUNT(*) FROM capitalistas WHERE $scopeWhere AND tipo='prestado' AND tasa_redito>0 AND estado='activo'");
$stmtRed->execute($scopeParams);
$reditosPend = (int)$stmtRed->fetchColumn();

// Nombre cobro activo
$stmtNC = $db->prepare("SELECT nombre FROM cobros WHERE id=?");
$stmtNC->execute([$cobro]);
$nombreCobro = $stmtNC->fetchColumn() ?: '—';

$pageTitle   = 'Dashboard';
$pageSection = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
.dk { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:1.1rem 1.25rem;transition:border-color .2s }
.dk:hover { border-color:var(--accent) }
.dk-label { font-family:var(--font-mono);font-size:0.6rem;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);margin-bottom:0.3rem }
.dk-value { font-family:var(--font-display);font-size:2rem;line-height:1;color:var(--text) }
.dk-sub   { font-family:var(--font-mono);font-size:0.62rem;color:var(--muted);margin-top:0.25rem }
.dk-accent { color:var(--accent) !important }
.dk-green  { color:#22c55e !important }
.dk-warn   { color:var(--warn) !important }
.dk-danger { color:var(--danger) !important }
.dk-blue   { color:#60a5fa !important }
.prog { background:var(--border);border-radius:3px;height:4px;overflow:hidden;margin-top:0.5rem }
.prog-fill { height:100%;border-radius:3px;transition:width .8s ease }
.accion-btn {
    display:flex;align-items:center;gap:0.75rem;padding:0.9rem 1rem;
    background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);
    text-decoration:none;color:var(--text);font-weight:600;font-size:0.82rem;
    transition:border-color .2s,background .2s
}
.accion-btn:hover { border-color:var(--accent);background:var(--surface) }
.accion-icon { font-family:var(--font-mono);color:var(--accent);font-size:1.1rem;width:24px;text-align:center;flex-shrink:0 }
</style>

<!-- CABECERA -->
<div class="page-header page-header-row" style="margin-bottom:1rem">
  <div>
    <h1>DASHBOARD</h1>
    <p>// <?= date('d \d\e F \d\e Y') ?> · <?= htmlspecialchars($filtroCobro ? $nombreCobro : 'Todos los cobros') ?></p>
  </div>
  <div style="display:flex;gap:0.5rem;align-items:center">
    <?php if (canDo('puede_crear_prestamo')): ?>
    <a href="/pages/prestamos.php" class="btn btn-ghost">≡ Préstamos</a>
    <a href="/pages/pagos.php" class="btn btn-primary">$ Registrar Pago</a>
    <?php endif; ?>
  </div>
</div>

<!-- FILTRO COBRO -->
<?php if (count($todosCobros) > 1): ?>
<div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;margin-bottom:1.25rem">
  <span style="font-family:var(--font-mono);font-size:0.65rem;color:var(--muted);letter-spacing:1px">COBRO:</span>
  <a href="/pages/dashboard.php"
     class="btn btn-sm <?= !$filtroCobro ? 'btn-primary' : 'btn-ghost' ?>">Todos</a>
  <?php foreach ($todosCobros as $cb): ?>
  <a href="/pages/dashboard.php?cobro=<?= $cb['id'] ?>"
     class="btn btn-sm <?= $filtroCobro===$cb['id'] ? 'btn-primary' : 'btn-ghost' ?>">
    <?= htmlspecialchars($cb['nombre']) ?>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ALERTAS -->
<?php if ($reditosPend > 0): ?>
<div class="alert alert-warning mb-2" style="font-size:0.82rem">
  ⚠ <?= $reditosPend ?> capitalista<?= $reditosPend>1?'s':'' ?> con rédito pendiente de pago.
  <a href="/pages/salidas.php" style="color:inherit;text-decoration:underline;margin-left:0.5rem">Registrar en Salidas →</a>
</div>
<?php endif; ?>

<!-- ── FILA 1: NEGOCIO ─────────────────────────────────── -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1rem">

  <div class="dk" style="border-color:rgba(124,106,255,.25)">
    <div class="dk-label">Deudores activos</div>
    <div class="dk-value dk-accent"><?= $totalDeudores ?></div>
    <div class="dk-sub"><?= $totalDeudoresReg ?> registrados en total</div>
  </div>

  <div class="dk">
    <div class="dk-label">Capital inicial</div>
    <div class="dk-value"><?= fmt($capitalInicial) ?></div>
    <div class="dk-sub">Aportes <?= fmt($totalAportes) ?> · Retiros <?= fmt($totalRetiros) ?></div>
  </div>

  <div class="dk" style="border-color:rgba(34,197,94,.25)">
    <div class="dk-label">Patrimonio hoy</div>
    <div class="dk-value dk-green"><?= fmt($patrimonioHoy) ?></div>
    <div class="dk-sub">Caja <?= fmt($saldoCaja) ?> + Cartera <?= fmt($carteraActiva) ?></div>
    <div style="margin-top:0.4rem;display:flex;align-items:center;gap:0.4rem">
      <span style="font-family:var(--font-mono);font-size:0.62rem;font-weight:700;color:<?= $roiPct>=0?'#22c55e':'var(--danger)' ?>">
        <?= $roiPct>=0?'▲':'▼' ?> <?= abs($roiPct) ?>% ROI
      </span>
      <span style="font-family:var(--font-mono);font-size:0.6rem;color:var(--muted)">
        <?= ($crecimiento>=0?'+':'') ?><?= fmt($crecimiento) ?>
      </span>
    </div>
  </div>

  <div class="dk" style="border-color:<?= $saldoInc>0?'rgba(239,68,68,.3)':'var(--border)' ?>">
    <div class="dk-label">Cartera incobrable</div>
    <div class="dk-value <?= $saldoInc>0?'dk-danger':'' ?>"><?= fmt($saldoInc) ?></div>
    <div class="dk-sub"><?= $countInc ?> préstamo<?= $countInc!=1?'s':'' ?> · +60 días mora</div>
    <?php if ($carteraActiva > 0): ?>
    <div class="prog"><div class="prog-fill" style="width:<?= $pctInc ?>%;background:var(--danger)"></div></div>
    <div class="dk-sub" style="margin-top:0.2rem"><?= $pctInc ?>% de la cartera</div>
    <?php endif; ?>
  </div>

</div>

<!-- ── FILA 2: CARTERA DESGLOSADA ──────────────────────── -->
<div style="display:grid;grid-template-columns:3fr 2fr;gap:1rem;margin-bottom:1rem">

  <!-- Estado cartera -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">ESTADO DE CARTERA</span>
      <a href="/pages/prestamos.php" class="btn btn-ghost btn-sm">Ver todos</a>
    </div>
    <div style="padding:1rem 1.25rem">
      <?php
        $filas = [
          ['Activos',    $countActivo,  $saldoActivo,  'var(--accent)', $carteraActiva],
          ['En Mora',    $countMora,    $saldoMora,    'var(--warn)',   $carteraActiva],
          ['En Acuerdo', $countAcuerdo, $saldoAcuerdo, '#60a5fa',      $carteraActiva],
          ['Incobrable', $countInc,     $saldoInc,     'var(--danger)',$carteraActiva],
        ];
        foreach ($filas as [$lbl, $cnt, $sal, $col, $total]):
          $pct = $total > 0 ? round(($sal/$total)*100,1) : 0;
      ?>
      <div style="margin-bottom:0.85rem">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.25rem">
          <div style="display:flex;align-items:center;gap:0.5rem">
            <span style="width:8px;height:8px;border-radius:50%;background:<?= $col ?>;flex-shrink:0;display:inline-block"></span>
            <span style="font-size:0.8rem;font-weight:600"><?= $lbl ?></span>
            <span style="font-family:var(--font-mono);font-size:0.65rem;color:var(--muted)">(<?= $cnt ?>)</span>
          </div>
          <div style="text-align:right">
            <span style="font-family:var(--font-mono);font-weight:700;color:<?= $col ?>"><?= fmt($sal) ?></span>
            <span style="font-family:var(--font-mono);font-size:0.6rem;color:var(--muted);margin-left:0.4rem"><?= $pct ?>%</span>
          </div>
        </div>
        <div class="prog">
          <div class="prog-fill" style="width:<?= $pct ?>%;background:<?= $col ?>"></div>
        </div>
      </div>
      <?php endforeach; ?>
      <div style="border-top:1px solid var(--border);padding-top:0.75rem;display:flex;justify-content:space-between">
        <span style="font-family:var(--font-mono);font-size:0.65rem;color:var(--muted)">TOTAL CARTERA</span>
        <span style="font-family:var(--font-mono);font-weight:700"><?= fmt($carteraActiva) ?></span>
      </div>
    </div>
  </div>

  <!-- Cobros + acciones -->
  <div style="display:flex;flex-direction:column;gap:1rem">

    <!-- KPIs cobro -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem">
      <div class="dk" style="padding:0.85rem 1rem">
        <div class="dk-label">Cobrado hoy</div>
        <div style="font-family:var(--font-display);font-size:1.4rem;color:var(--accent)"><?= fmt($cobradoHoy) ?></div>
        <div class="dk-sub"><?= count($ultPagosHoy) ?> pagos</div>
      </div>
      <div class="dk" style="padding:0.85rem 1rem">
        <div class="dk-label">Esta semana</div>
        <div style="font-family:var(--font-display);font-size:1.4rem"><?= fmt($cobradoSemana) ?></div>
        <div class="dk-sub">Mes: <?= fmt($cobradoMes) ?></div>
      </div>
      <div class="dk" style="padding:0.85rem 1rem;border-color:<?= $vencidasHoy>0?'rgba(239,68,68,.3)':'var(--border)' ?>">
        <div class="dk-label">Vencidas hoy</div>
        <div style="font-family:var(--font-display);font-size:1.4rem;color:<?= $vencidasHoy>0?'var(--danger)':'var(--text)' ?>"><?= $vencidasHoy ?></div>
        <div class="dk-sub">cuotas pendientes</div>
      </div>
      <div class="dk" style="padding:0.85rem 1rem">
        <div class="dk-label">Nuevos en mes</div>
        <div style="font-family:var(--font-display);font-size:1.4rem;color:#60a5fa"><?= $prestamosNuevosMes ?></div>
        <div class="dk-sub">préstamos</div>
      </div>
    </div>

    <!-- Acciones rápidas admin -->
    <div class="card" style="flex:1">
      <div class="card-header"><span class="card-title">ACCIONES RÁPIDAS</span></div>
      <div style="padding:0.75rem;display:flex;flex-direction:column;gap:0.5rem">
        <?php
          $acciones = [
            ['href'=>'/pages/liquidacion.php', 'icon'=>'⊟', 'label'=>'Liquidación',    'perm'=>'puede_ver_configuracion'],
            ['href'=>'/pages/salidas.php',      'icon'=>'↑', 'label'=>'Nueva Salida',   'perm'=>'puede_crear_salida'],
            ['href'=>'/pages/capital.php',      'icon'=>'◆', 'label'=>'Capital',        'perm'=>'puede_ver_capital'],
            ['href'=>'/pages/pagos.php',        'icon'=>'$', 'label'=>'Registrar Pago', 'perm'=>'puede_registrar_pago'],
          ];
          foreach ($acciones as $a):
            if (!canDo($a['perm'])) continue;
        ?>
        <a href="<?= $a['href'] ?>" class="accion-btn">
          <span class="accion-icon"><?= $a['icon'] ?></span>
          <span><?= $a['label'] ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</div>

<!-- ── FILA 3: MORA + PAGOS HOY ─────────────────────────── -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">

  <!-- Top mora -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">TOP MORA</span>
      <a href="/pages/prestamos.php?filtro=mora" class="btn btn-ghost btn-sm">Ver todos</a>
    </div>
    <?php if (empty($topMora)): ?>
    <div class="empty-state" style="padding:1.5rem">
      <span style="font-size:2rem;color:#22c55e">✓</span>
      <p>Sin deudores en mora</p>
    </div>
    <?php else: ?>
    <div>
      <?php foreach ($topMora as $m): ?>
      <div class="schedule-row vencida" style="padding:0.65rem 1rem">
        <div class="schedule-dot vencida"></div>
        <div style="flex:1;min-width:0">
          <div style="font-weight:600;font-size:0.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($m['nombre']) ?></div>
          <div style="font-family:var(--font-mono);font-size:0.62rem;color:var(--muted)">
            <?= $m['dias_mora'] ?>d mora
            <?php if (count($todosCobros) > 1): ?> · <?= htmlspecialchars($m['cobro_nombre']) ?><?php endif; ?>
            <?php if ($m['telefono']): ?> · <a href="https://wa.me/57<?= preg_replace('/\D/','',$m['telefono']) ?>" target="_blank" style="color:var(--accent)">WA</a><?php endif; ?>
          </div>
        </div>
        <div style="text-align:right">
          <div style="font-family:var(--font-mono);font-weight:700;color:var(--warn);font-size:0.82rem"><?= fmt($m['saldo_pendiente']) ?></div>
          <a href="/pages/prestamo_detalle.php?id=<?= $m['pid'] ?>" style="font-size:0.6rem;color:var(--muted)">Ver →</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Pagos hoy -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">PAGOS HOY</span>
      <span style="font-family:var(--font-mono);font-size:0.72rem;color:var(--accent);font-weight:700"><?= fmt($cobradoHoy) ?></span>
    </div>
    <?php if (empty($ultPagosHoy)): ?>
    <div class="empty-state" style="padding:1.5rem">
      <span class="empty-icon">◈</span><p>Sin pagos hoy</p>
    </div>
    <?php else: ?>
    <div style="max-height:280px;overflow-y:auto">
      <?php foreach ($ultPagosHoy as $pg): ?>
      <div class="schedule-row" style="padding:0.6rem 1rem">
        <div class="schedule-dot paid"></div>
        <div style="flex:1;min-width:0">
          <div style="font-weight:600;font-size:0.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($pg['deudor']) ?></div>
          <div style="font-family:var(--font-mono);font-size:0.62rem;color:var(--muted)">
            Cuota #<?= $pg['numero_cuota'] ?> · <?= date('H:i', strtotime($pg['created_at'])) ?>
            <?php if (count($todosCobros) > 1): ?> · <?= htmlspecialchars($pg['cobro_nombre']) ?><?php endif; ?>
          </div>
        </div>
        <div style="font-family:var(--font-mono);font-weight:700;color:var(--accent)"><?= fmt($pg['monto_pagado']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- ── FILA 4: ACUERDOS + FINANZAS MES ──────────────────── -->
<?php if (!empty($acuerdos) || $totalGastos > 0 || $totalReditos > 0): ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">

  <?php if (!empty($acuerdos)): ?>
  <div class="card">
    <div class="card-header"><span class="card-title">EN ACUERDO DE PAGO</span><span class="badge badge-blue"><?= count($acuerdos) ?></span></div>
    <div>
      <?php foreach ($acuerdos as $a): $vencido = $a['fecha_compromiso'] && $a['fecha_compromiso'] < date('Y-m-d'); ?>
      <div class="schedule-row" style="padding:0.65rem 1rem">
        <div class="schedule-dot <?= $vencido?'vencida':'paid' ?>"></div>
        <div style="flex:1">
          <div style="font-weight:600;font-size:0.82rem"><?= htmlspecialchars($a['deudor']) ?></div>
          <div style="font-family:var(--font-mono);font-size:0.62rem;color:<?= $vencido?'var(--warn)':'var(--muted)' ?>">
            <?= $a['fecha_compromiso'] ? date('d M Y', strtotime($a['fecha_compromiso'])) : 'Sin fecha' ?>
            <?= $vencido ? ' · VENCIDO' : '' ?>
          </div>
        </div>
        <div style="font-family:var(--font-mono);font-weight:700;font-size:0.82rem"><?= fmt($a['saldo_pendiente']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($totalGastos > 0 || $totalReditos > 0 || $cobradoMes > 0): ?>
  <div class="card">
    <div class="card-header"><span class="card-title">FINANZAS DEL MES</span></div>
    <div style="padding:1rem 1.25rem;display:flex;flex-direction:column;gap:0.75rem">
      <div style="display:flex;justify-content:space-between">
        <span style="font-size:0.82rem;color:var(--muted)">Cobrado este mes</span>
        <span style="font-family:var(--font-mono);font-weight:700;color:var(--accent)"><?= fmt($cobradoMes) ?></span>
      </div>
      <div style="display:flex;justify-content:space-between">
        <span style="font-size:0.82rem;color:var(--muted)">Gastos operativos</span>
        <span style="font-family:var(--font-mono);font-weight:700;color:var(--danger)">−<?= fmt($totalGastos) ?></span>
      </div>
      <div style="display:flex;justify-content:space-between">
        <span style="font-size:0.82rem;color:var(--muted)">Réditos pagados</span>
        <span style="font-family:var(--font-mono);font-weight:700;color:var(--warn)">−<?= fmt($totalReditos) ?></span>
      </div>
      <div style="border-top:1px solid var(--border);padding-top:0.75rem;display:flex;justify-content:space-between">
        <span style="font-size:0.82rem;font-weight:600">Ganancia real</span>
        <span style="font-family:var(--font-mono);font-weight:700;color:<?= $gananciaReal>=0?'#22c55e':'var(--danger)' ?>;font-size:1rem">
          <?= ($gananciaReal>=0?'+':'') ?><?= fmt($gananciaReal) ?>
        </span>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>