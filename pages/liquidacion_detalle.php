<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
if (!in_array($_SESSION['rol'], ['admin','superadmin'])) { include __DIR__ . '/403.php'; exit; }

$db    = getDB();
$cobro = cobroActivo();
$id    = (int)($_GET['id'] ?? 0);

if (!$id) { header('Location: /pages/liquidacion.php'); exit; }

// Cargar liquidación
$stmtLiq = $db->prepare("SELECT * FROM liquidaciones WHERE id=? AND cobro_id=?");
$stmtLiq->execute([$id, $cobro]);
$liquidacion = $stmtLiq->fetch();
if (!$liquidacion) { header('Location: /pages/liquidacion.php'); exit; }

$fecha = $liquidacion['fecha'];

// Buscar cobrador
$stmtCob = $db->prepare("
    SELECT u.id, u.nombre, u.activo FROM usuarios u
    JOIN usuario_cobro uc ON uc.usuario_id = u.id
    WHERE uc.cobro_id = ? AND u.rol = 'cobrador' LIMIT 1
");
$stmtCob->execute([$cobro]);
$cobrador = $stmtCob->fetch();

// Cargar % papelería
$stmtCobro = $db->prepare("SELECT papeleria_pct FROM cobros WHERE id=?");
$stmtCobro->execute([$cobro]);
$cobroData = $stmtCobro->fetch();

// Pagos del día
$stmtP = $db->prepare("
    SELECT pg.*, d.nombre AS deudor, cu.numero_cuota, c.nombre AS cuenta
    FROM pagos pg
    JOIN deudores d ON d.id = pg.deudor_id
    JOIN cuotas cu  ON cu.id = pg.cuota_id
    LEFT JOIN cuentas c ON c.id = pg.cuenta_id
    WHERE pg.cobro_id=? AND pg.fecha_pago=? AND (pg.anulado=0 OR pg.anulado IS NULL)
    ORDER BY pg.created_at ASC
");
$stmtP->execute([$cobro, $fecha]);
$pagosHoy   = $stmtP->fetchAll();
$totalPagos = array_sum(array_column($pagosHoy, 'monto_pagado'));

// Préstamos del día
$stmtPr = $db->prepare("
    SELECT p.*, d.nombre AS deudor FROM prestamos p
    JOIN deudores d ON d.id = p.deudor_id
    WHERE p.cobro_id=? AND DATE(p.created_at)=? AND p.estado!='anulado'
    ORDER BY p.created_at ASC
");
$stmtPr->execute([$cobro, $fecha]);
$prestamosHoy   = $stmtPr->fetchAll();
$totalPrestamos = array_sum(array_column($prestamosHoy, 'monto_prestado'));

// Papelería del día
$stmtPap = $db->prepare("SELECT COALESCE(SUM(monto_papeleria),0) FROM papeleria WHERE cobro_id=? AND fecha=?");
$stmtPap->execute([$cobro, $fecha]);
$totalPapeleria = (float)$stmtPap->fetchColumn();

// Gastos del día
$stmtG = $db->prepare("
    SELECT g.*, cat.nombre AS categoria_nombre, u.nombre AS usuario_nombre
    FROM gastos_cobrador g
    LEFT JOIN categorias_gasto cat ON cat.id = g.categoria_id
    LEFT JOIN usuarios u ON u.id = g.usuario_id
    WHERE g.cobro_id=? AND g.fecha=?
    ORDER BY g.created_at ASC
");
$stmtG->execute([$cobro, $fecha]);
$gastosHoy   = $stmtG->fetchAll();
$totalGastos = array_sum(array_column(
    array_filter($gastosHoy, fn($g) => $g['estado'] === 'aprobado'),
    'monto'
));

$base_trabajado    = (float)$liquidacion['base_trabajado'];
$base              = (float)$liquidacion['base'];
$efectivo_esperado = ($totalPagos + $base_trabajado) - $totalPrestamos - $totalGastos;
$nueva_base        = ($base - $base_trabajado) + (float)($liquidacion['dinero_entregado'] ?? 0);

$pageTitle   = 'Liquidación ' . date('d M Y', strtotime($fecha));
$pageSection = 'Liquidación';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="breadcrumb">
    <a href="/pages/liquidacion.php">Liquidaciones</a>
    <span class="sep">›</span>
    <span class="current"><?= date('d M Y', strtotime($fecha)) ?></span>
</div>

<div class="page-header page-header-row">
    <div>
        <h1>LIQUIDACIÓN — <?= date('d M Y', strtotime($fecha)) ?></h1>
        <p>// <?= htmlspecialchars($cobrador['nombre'] ?? '—') ?></p>
    </div>
    <div style="display:flex;gap:0.75rem;align-items:center">
        <?php if ($liquidacion['estado'] === 'cerrada'): ?>
        <span class="badge badge-green" style="font-size:0.9rem;padding:0.4rem 1rem">✓ CERRADA</span>
        <?php else: ?>
        <span class="badge badge-orange" style="font-size:0.9rem;padding:0.4rem 1rem">EN CURSO</span>
        <?php endif; ?>
        <a href="/pages/liquidacion.php" class="btn btn-ghost btn-sm">← Volver</a>
    </div>
</div>

<?php require_once __DIR__ . '/liquidacion_contenido.php'; ?>

<?php
$efectivoEsperadoJs = $efectivo_esperado;
$baseJs             = $base;
$baseTrabajadoJs    = $base_trabajado;
$liquidacionId      = $liquidacion['id'];
$cobradorId         = $cobrador['id'] ?? 0;

$extraScript = <<<JS
<script>
const EFECTIVO_ESPERADO = {$efectivoEsperadoJs};
const BASE              = {$baseJs};
const BASE_TRABAJADO    = {$baseTrabajadoJs};
const LIQUIDACION_ID    = {$liquidacionId};
const COBRADOR_ID       = {$cobradorId};

function calcularDiferencia() {
    const entregado  = parseFloat(document.getElementById('input-dinero-entregado').value) || 0;
    const diferencia = EFECTIVO_ESPERADO - entregado;
    const nuevaBase  = (BASE - BASE_TRABAJADO) + entregado;
    const fmt        = n => (n >= 0 ? '' : '-') + '\$' + Math.abs(Math.round(n)).toLocaleString('es-CO');
    const elDif  = document.getElementById('display-diferencia');
    const elBase = document.getElementById('display-nueva-base');
    elDif.textContent  = fmt(diferencia);
    elDif.style.color  = diferencia === 0 ? '#22c55e' : (diferencia > 0 ? '#f59e0b' : '#ef4444');
    elBase.textContent = 'Nueva base: ' + fmt(nuevaBase);
}

async function toggleCobrador(id, activo) {
    if (!confirm((activo ? 'Activar' : 'Inactivar') + ' al cobrador?')) return;
    const res = await apiPost('/api/usuarios.php', { action: activo ? 'activar' : 'desactivar', id });
    if (res.ok) { toast(res.msg); setTimeout(() => location.reload(), 600); }
    else toast(res.msg || 'Error', 'error');
}

async function aprobarGasto(id) {
    const res = await apiPost('/api/gastos.php', { action: 'aprobar', id });
    if (res.ok) { toast('Gasto aprobado'); setTimeout(() => location.reload(), 600); }
    else toast(res.msg || 'Error', 'error');
}

async function rechazarGasto(id) {
    const res = await apiPost('/api/gastos.php', { action: 'rechazar', id });
    if (res.ok) { toast('Gasto rechazado'); setTimeout(() => location.reload(), 600); }
    else toast(res.msg || 'Error', 'error');
}

function actualizarPapeleria(prestamoId, montoPrestado) {
    const pct   = parseFloat(document.getElementById('pap-pct-' + prestamoId).value) || 0;
    const monto = Math.round(montoPrestado * (pct / 100));
    document.getElementById('pap-monto-' + prestamoId).textContent = '\$' + monto.toLocaleString('es-CO');
    recalcularTotalPapeleria();
}

function aplicarPctGlobal() {
    const pct = parseFloat(document.getElementById('pap-pct-global').value) || 0;
    document.querySelectorAll('[id^="pap-pct-"]').forEach(input => {
        if (input.id === 'pap-pct-global') return;
        const id    = input.id.replace('pap-pct-', '');
        const monto = parseFloat(input.dataset.monto || 0);
        input.value = pct;
        const montoEl = document.getElementById('pap-monto-' + id);
        if (montoEl) montoEl.textContent = '\$' + Math.round(monto * (pct/100)).toLocaleString('es-CO');
    });
    recalcularTotalPapeleria();
}

function recalcularTotalPapeleria() {
    let total = 0;
    document.querySelectorAll('[id^="pap-monto-"]').forEach(el => {
        total += parseInt(el.textContent.replace(/\D/g,'')) || 0;
    });
    const el = document.getElementById('total-papeleria-display');
    if (el) el.textContent = '\$' + total.toLocaleString('es-CO');
}

async function cerrarLiquidacion() {
    const entregado          = parseFloat(document.getElementById('input-dinero-entregado').value) || 0;
    const papeleriaEntregada = parseFloat(document.getElementById('input-papeleria-entregada').value) || 0;
    const notas              = document.getElementById('input-notas').value.trim();

    if (entregado <= 0) { alert('Ingresa el dinero entregado por el cobrador'); return; }

    const papeleriaPorPrestamo = {};
    document.querySelectorAll('[id^="pap-pct-"]').forEach(input => {
        if (input.id === 'pap-pct-global') return;
        const id = input.id.replace('pap-pct-', '');
        papeleriaPorPrestamo[id] = parseFloat(input.value) || 0;
    });

    const diferencia = EFECTIVO_ESPERADO - entregado;
    const nuevaBase  = (BASE - BASE_TRABAJADO) + entregado;
    const fmt        = n => '\$' + Math.abs(Math.round(n)).toLocaleString('es-CO');

    if (!confirm(
        'CONFIRMAR CIERRE\\n\\n' +
        'Efectivo esperado: ' + fmt(EFECTIVO_ESPERADO) + '\\n' +
        'Dinero entregado:  ' + fmt(entregado) + '\\n' +
        'Diferencia:        ' + (diferencia >= 0 ? '+' : '-') + fmt(diferencia) + '\\n' +
        'Nueva base:        ' + fmt(nuevaBase) + '\\n' +
        'Papelería:         ' + fmt(papeleriaEntregada) + '\\n\\n' +
        '¿Cerrar? No se puede deshacer.'
    )) return;

    const res = await apiPost('/api/liquidacion.php', {
        action              : 'cerrar',
        liquidacion_id      : LIQUIDACION_ID,
        dinero_entregado    : entregado,
        papeleria_entregada : papeleriaEntregada,
        papeleria_prestamos : papeleriaPorPrestamo,
        notas
    });

    if (res.ok) { toast(res.msg); setTimeout(() => location.reload(), 800); }
    else toast(res.msg || 'Error', 'error');
}
</script>
JS;

require_once __DIR__ . '/../includes/footer.php';
?>