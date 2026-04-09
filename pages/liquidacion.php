<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
if (!in_array($_SESSION['rol'], ['admin','superadmin'])) { include __DIR__ . '/403.php'; exit; }

$db    = getDB();
$cobro = cobroActivo();

$pageTitle   = 'Liquidaciones';
$pageSection = 'Liquidación';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header-row">
    <div>
        <h1>LIQUIDACIONES</h1>
        <p>// Historial de liquidaciones del cobro activo</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modal-nueva-liquidacion')">
        + Nueva Liquidación
    </button>
</div>

<!-- Filtros -->
<div class="card mb-2">
    <div class="card-body" style="padding:1rem">
        <div style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap">
            <div class="field" style="margin:0;min-width:150px">
                <label>Estado</label>
                <select id="filtro-estado">
                    <option value="">Todos</option>
                    <option value="borrador">Borrador</option>
                    <option value="cerrada">Cerrada</option>
                </select>
            </div>
            <div class="field" style="margin:0">
                <label>Fecha inicio</label>
                <input type="date" id="filtro-fecha-ini">
            </div>
            <div class="field" style="margin:0">
                <label>Fecha fin</label>
                <input type="date" id="filtro-fecha-fin" value="<?= date('Y-m-d') ?>">
            </div>
            <button class="btn btn-ghost" onclick="cargarLiquidaciones()">🔍 Filtrar</button>
            <button class="btn btn-ghost" onclick="limpiarFiltros()">✕ Limpiar</button>
        </div>
    </div>
</div>

<!-- Tabla de liquidaciones -->
<div class="card">
    <div class="card-header">
        <span class="card-title">LIQUIDACIONES</span>
        <span class="text-mono text-xs text-muted" id="total-registros">Cargando...</span>
    </div>
    <div id="tabla-liquidaciones">
        <div class="empty-state"><span class="empty-icon">⏳</span><p>Cargando liquidaciones...</p></div>
    </div>
</div>

<!-- Modal nueva liquidación -->
<?php require_once __DIR__ . '/liquidacion_modal.php'; ?>

<?php
$extraScript = <<<'JS'
<script>
// Cargar al inicio
document.addEventListener('DOMContentLoaded', () => cargarLiquidaciones());

async function cargarLiquidaciones() {
    const estado    = document.getElementById('filtro-estado').value;
    const fechaIni  = document.getElementById('filtro-fecha-ini').value;
    const fechaFin  = document.getElementById('filtro-fecha-fin').value;

    const res = await apiPost('/api/liquidacion.php', {
        action    : 'lista',
        estado,
        fecha_ini : fechaIni,
        fecha_fin : fechaFin
    });

    if (!res.ok) { toast(res.msg || 'Error al cargar', 'error'); return; }

    const lista = res.liquidaciones;
    document.getElementById('total-registros').textContent = lista.length + ' registros';

    if (lista.length === 0) {
        document.getElementById('tabla-liquidaciones').innerHTML =
            '<div class="empty-state"><span class="empty-icon">◈</span><p>Sin liquidaciones en este período</p></div>';
        return;
    }

    const fmt = n => '$' + Math.abs(Math.round(parseFloat(n)||0)).toLocaleString('es-CO');

    let html = `
        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Cobrador</th>
                    <th>Base</th>
                    <th>Base Trabajado</th>
                    <th>Pagos</th>
                    <th>Préstamos</th>
                    <th>Gastos</th>
                    <th>Papelería</th>
                    <th>Efectivo</th>
                    <th>Diferencia</th>
                    <th>Nueva Base</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
    `;

    lista.forEach(l => {
        const estadoBadge = l.estado === 'cerrada'
            ? '<span class="badge badge-green">CERRADA</span>'
            : '<span class="badge badge-orange">BORRADOR</span>';

        const diferencia  = parseFloat(l.diferencia || 0);
        const difColor    = diferencia === 0 ? '#22c55e' : (diferencia > 0 ? '#f59e0b' : '#ef4444');
        const difSign     = diferencia > 0 ? '+' : '';

        html += `
            <tr>
                <td class="text-mono">${l.fecha}</td>
                <td>${l.cobrador_nombre || '—'}</td>
                <td class="text-mono">${fmt(l.base)}</td>
                <td class="text-mono" style="color:var(--accent)">${fmt(l.base_trabajado)}</td>
                <td class="text-mono" style="color:#22c55e">${fmt(l.total_pagos)}</td>
                <td class="text-mono" style="color:var(--accent)">${fmt(l.total_prestamos)}</td>
                <td class="text-mono" style="color:#f97316">${fmt(l.total_gastos_aprobados)}</td>
                <td class="text-mono" style="color:#f97316">${fmt(l.total_papeleria)}</td>
                <td class="text-mono" style="color:#22c55e">${fmt(l.efectivo_esperado)}</td>
                <td class="text-mono fw-600" style="color:${difColor}">${difSign}${fmt(l.diferencia)}</td>
                <td class="text-mono fw-600" style="color:var(--accent)">${fmt(l.nueva_base)}</td>
                <td>${estadoBadge}</td>
                <td>
                    <a href="/pages/liquidacion_detalle.php?id=${l.id}" class="btn btn-ghost btn-sm">Ver</a>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table></div>';
    document.getElementById('tabla-liquidaciones').innerHTML = html;
}

function limpiarFiltros() {
    document.getElementById('filtro-estado').value    = '';
    document.getElementById('filtro-fecha-ini').value = '';
    document.getElementById('filtro-fecha-fin').value = '';
    cargarLiquidaciones();
}
</script>
JS;

require_once __DIR__ . '/../includes/footer.php';
?>