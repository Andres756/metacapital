<?php
$pageTitle = 'Mis cobros';
$pageNav   = 'miscobros';
require_once __DIR__ . '/header.php';

$db   = getDB();
$hoy  = date('Y-m-d');
$uid  = $_SESSION['usuario_id'];

// ── Pagos del día ────────────────────────────────────────────
$stmtPagos = $db->prepare("
    SELECT pg.*, d.nombre AS deudor, d.telefono,
           cu.numero_cuota, c.nombre AS cuenta_nombre,
           p.monto_prestado, p.frecuencia_pago
    FROM pagos pg
    JOIN deudores d  ON d.id  = pg.deudor_id
    JOIN cuotas cu   ON cu.id = pg.cuota_id
    JOIN prestamos p ON p.id  = pg.prestamo_id
    LEFT JOIN cuentas c ON c.id = pg.cuenta_id
    WHERE pg.cobro_id=? AND pg.fecha_pago=?
      AND pg.usuario_id=?
      AND (pg.anulado=0 OR pg.anulado IS NULL)
    ORDER BY pg.created_at DESC
");
$stmtPagos->execute([$cobro, $hoy, $uid]);
$pagos = $stmtPagos->fetchAll();

// ── Préstamos creados hoy — todos del cobro, sin filtrar por usuario ─
$stmtPrest = $db->prepare("
    SELECT p.*, d.nombre AS deudor
    FROM prestamos p
    JOIN deudores d ON d.id = p.deudor_id
    WHERE p.cobro_id=? AND DATE(p.created_at)=?
      AND p.estado != 'anulado'
    ORDER BY p.created_at DESC
");
$stmtPrest->execute([$cobro, $hoy]);
$prestamosHoy = $stmtPrest->fetchAll();

// ── Gastos del día ───────────────────────────────────────────
$stmtGastos = $db->prepare("
    SELECT g.*, cat.nombre AS categoria_nombre
    FROM gastos_cobrador g
    LEFT JOIN categorias_gasto cat ON cat.id = g.categoria_id
    WHERE g.cobro_id=? AND g.fecha=? AND g.usuario_id=?
    ORDER BY g.created_at DESC
");
$stmtGastos->execute([$cobro, $hoy, $uid]);
$gastos = $stmtGastos->fetchAll();

// ── Totales ──────────────────────────────────────────────────
$totalCobrado   = array_sum(array_column($pagos, 'monto_pagado'));
$totalPrestado  = array_sum(array_column($prestamosHoy, 'monto_prestado'));
$totalGastos    = array_sum(array_column(
    array_filter($gastos, fn($g) => $g['estado'] !== 'rechazado'),
    'monto'
));

$stmtLiq = $db->prepare("SELECT base_trabajado FROM liquidaciones WHERE cobro_id=? AND fecha=?");
$stmtLiq->execute([$cobro, $hoy]);
$liqHoy = $stmtLiq->fetch();
$base_trabajado = $liqHoy ? (float)$liqHoy['base_trabajado'] : null;
$efectivoEsperado = $base_trabajado !== null
    ? ($totalCobrado + $base_trabajado) - $totalPrestado - $totalGastos
    : null;

?>

<div class="cob-header">
    <div>
        <div class="cob-title">MIS COBROS</div>
        <div style="font-size:0.72rem;color:var(--muted);font-family:var(--font-mono)">
            <?= date('l d \d\e F') ?>
        </div>
    </div>
</div>

<!-- Stats del día -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-bottom:1.25rem">

    <?php if ($base_trabajado !== null): ?>
    <div class="cob-card" style="border-color:rgba(124,106,255,.3);grid-column:1/-1">
        <div style="display:flex;justify-content:space-between;align-items:center">
            <div>
                <div style="font-size:0.65rem;color:var(--muted);font-family:var(--font-mono);text-transform:uppercase;letter-spacing:1px">Base entregada por el admin</div>
                <div style="font-size:1.6rem;font-weight:700;color:var(--accent)"><?= fmt($base_trabajado) ?></div>
                <div style="font-size:0.7rem;color:var(--muted)">Dinero con el que saliste hoy</div>
            </div>
            <div style="font-size:2rem">💼</div>
        </div>
    </div>
    <?php else: ?>
    <div class="cob-card" style="border-color:rgba(249,115,22,.3);grid-column:1/-1">
        <div style="font-size:0.65rem;color:var(--muted);font-family:var(--font-mono);text-transform:uppercase;letter-spacing:1px">Base trabajado</div>
        <div style="font-size:1rem;color:#f59e0b;font-family:var(--font-mono);margin-top:0.25rem">⏳ El admin aún no ha iniciado el día</div>
    </div>
    <?php endif; ?>

    <div class="cob-card" style="border-color:rgba(34,197,94,.3)">
        <div style="font-size:0.65rem;color:var(--muted);font-family:var(--font-mono);text-transform:uppercase;letter-spacing:1px">Cobrado</div>
        <div style="font-size:1.4rem;font-weight:700;color:#22c55e"><?= fmt($totalCobrado) ?></div>
        <div style="font-size:0.7rem;color:var(--muted)"><?= count($pagos) ?> pago<?= count($pagos) !== 1 ? 's' : '' ?></div>
    </div>

    <div class="cob-card" style="border-color:rgba(124,106,255,.3)">
        <div style="font-size:0.65rem;color:var(--muted);font-family:var(--font-mono);text-transform:uppercase;letter-spacing:1px">Prestado</div>
        <div style="font-size:1.4rem;font-weight:700;color:var(--accent)"><?= fmt($totalPrestado) ?></div>
        <div style="font-size:0.7rem;color:var(--muted)"><?= count($prestamosHoy) ?> préstamo<?= count($prestamosHoy) !== 1 ? 's' : '' ?></div>
    </div>

    <div class="cob-card" style="border-color:rgba(249,115,22,.3)">
        <div style="font-size:0.65rem;color:var(--muted);font-family:var(--font-mono);text-transform:uppercase;letter-spacing:1px">Gastos</div>
        <div style="font-size:1.4rem;font-weight:700;color:#f97316"><?= fmt($totalGastos) ?></div>
        <div style="font-size:0.7rem;color:var(--muted)"><?= count($gastos) ?> gasto<?= count($gastos) !== 1 ? 's' : '' ?></div>
    </div>

    <div class="cob-card" style="border-color:rgba(34,197,94,.5);background:rgba(34,197,94,.05)">
        <div style="font-size:0.65rem;color:var(--muted);font-family:var(--font-mono);text-transform:uppercase;letter-spacing:1px">Efectivo a entregar</div>
        <?php if ($base_trabajado !== null): ?>
        <div style="font-size:1.4rem;font-weight:700;color:<?= $efectivoEsperado >= 0 ? '#22c55e' : '#ef4444' ?>">
            <?= fmt($efectivoEsperado) ?>
        </div>
        <div style="font-size:0.7rem;color:var(--muted)">(Cobrado + Base) − Prestado − Gastos</div>
        <?php else: ?>
        <div style="font-size:1rem;color:var(--muted);font-family:var(--font-mono)">—</div>
        <div style="font-size:0.7rem;color:var(--muted)">Pendiente de inicio del día</div>
        <?php endif; ?>
    </div>

</div>

<!-- Tabs -->
<div style="display:flex;gap:0.4rem;margin-bottom:1rem;overflow-x:auto;padding-bottom:2px">
    <button onclick="showTab('pagos')"     id="tab-pagos"     class="tab-btn tab-active">💰 Pagos (<?= count($pagos) ?>)</button>
    <button onclick="showTab('prestamos')" id="tab-prestamos" class="tab-btn">📋 Préstamos (<?= count($prestamosHoy) ?>)</button>
    <button onclick="showTab('gastos')"    id="tab-gastos"    class="tab-btn">🧾 Gastos (<?= count($gastos) ?>)</button>
</div>

<style>
.tab-btn { padding:0.5rem 0.85rem;border-radius:var(--radius);font-family:var(--font-mono);font-size:0.72rem;font-weight:600;border:1px solid var(--border);background:var(--card);color:var(--muted);cursor:pointer;white-space:nowrap; }
.tab-btn.tab-active { background:var(--accent);color:#fff;border-color:var(--accent); }
</style>

<!-- Tab: Pagos -->
<div id="panel-pagos">
<?php if (empty($pagos)): ?>
<div style="text-align:center;padding:2rem;color:var(--muted)">
    <div style="font-size:1.5rem;margin-bottom:0.5rem">◎</div>
    <div style="font-family:var(--font-mono);font-size:0.8rem">Sin pagos registrados hoy</div>
</div>
<?php else: ?>
<?php foreach ($pagos as $pg): ?>
<div class="cob-card" style="margin-bottom:0.5rem">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <div style="flex:1;min-width:0">
            <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($pg['deudor']) ?></div>
            <div style="font-size:0.72rem;color:var(--muted);font-family:var(--font-mono);margin-top:2px">
                Cuota #<?= $pg['numero_cuota'] ?> · <?= htmlspecialchars($pg['cuenta_nombre'] ?? 'Efectivo') ?> · <?= ucfirst($pg['metodo_pago'] ?? 'efectivo') ?>
            </div>
        </div>
        <div style="text-align:right;flex-shrink:0;margin-left:0.75rem">
            <div style="font-size:1rem;font-weight:700;color:#22c55e"><?= fmt($pg['monto_pagado']) ?></div>
            <div style="font-size:0.65rem;color:var(--muted);font-family:var(--font-mono)"><?= date('h:i a', strtotime($pg['created_at'])) ?></div>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<!-- Tab: Préstamos -->
<div id="panel-prestamos" style="display:none">
<?php if (empty($prestamosHoy)): ?>
<div style="text-align:center;padding:2rem;color:var(--muted)">
    <div style="font-size:1.5rem;margin-bottom:0.5rem">◎</div>
    <div style="font-family:var(--font-mono);font-size:0.8rem">Sin préstamos registrados hoy</div>
</div>
<?php else: ?>
<?php foreach ($prestamosHoy as $p): ?>
<div class="cob-card" style="margin-bottom:0.5rem;border-left:3px solid var(--accent);border-radius:0 var(--radius) var(--radius) 0">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <div style="flex:1;min-width:0">
            <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($p['deudor']) ?></div>
            <div style="font-size:0.72rem;color:var(--muted);font-family:var(--font-mono);margin-top:2px">
                <?= $p['num_cuotas'] ?> cuotas · <?= ucfirst($p['frecuencia_pago']) ?> · Cuota: <?= fmt($p['valor_cuota']) ?>
            </div>
        </div>
        <div style="text-align:right;flex-shrink:0;margin-left:0.75rem">
            <div style="font-size:1rem;font-weight:700;color:var(--accent)"><?= fmt($p['monto_prestado']) ?></div>
            <div style="font-size:0.65rem;color:var(--muted);font-family:var(--font-mono)"><?= date('h:i a', strtotime($p['created_at'])) ?></div>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<!-- Tab: Gastos -->
<div id="panel-gastos" style="display:none">
<?php if (empty($gastos)): ?>
<div style="text-align:center;padding:2rem;color:var(--muted)">
    <div style="font-size:1.5rem;margin-bottom:0.5rem">◎</div>
    <div style="font-family:var(--font-mono);font-size:0.8rem">Sin gastos registrados hoy</div>
</div>
<?php else: ?>
<?php foreach ($gastos as $g):
    $estadoColor = match($g['estado']) { 'aprobado' => '#22c55e', 'rechazado' => '#ef4444', default => '#f59e0b' };
?>
<div class="cob-card" style="margin-bottom:0.5rem;border-left:3px solid <?= $estadoColor ?>;border-radius:0 var(--radius) var(--radius) 0">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <div style="flex:1;min-width:0">
            <div style="font-weight:600"><?= htmlspecialchars($g['descripcion']) ?></div>
            <div style="font-size:0.72rem;color:var(--muted);font-family:var(--font-mono);margin-top:2px"><?= htmlspecialchars($g['categoria_nombre'] ?? '—') ?></div>
        </div>
        <div style="text-align:right;flex-shrink:0;margin-left:0.75rem">
            <div style="font-size:1rem;font-weight:700;color:#f97316"><?= fmt($g['monto']) ?></div>
            <div style="font-size:0.65rem;font-family:var(--font-mono);color:<?= $estadoColor ?>">
                <?= match($g['estado']) { 'aprobado' => '✓ Aprobado', 'rechazado' => '✕ Rechazado', default => '⏳ Pendiente' } ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<?php
$extraScript = <<<'JS'
<script>
function showTab(tab) {
    ['pagos','prestamos','gastos'].forEach(t => {
        document.getElementById('panel-' + t).style.display = 'none';
        document.getElementById('tab-'   + t).classList.remove('tab-active');
    });
    document.getElementById('panel-' + tab).style.display = 'block';
    document.getElementById('tab-'   + tab).classList.add('tab-active');
}
</script>
JS;

require_once __DIR__ . '/footer.php';
?>