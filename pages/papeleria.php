<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
if (!in_array($_SESSION['rol'], ['admin','superadmin'])) { include __DIR__ . '/403.php'; exit; }

$db    = getDB();
$cobro = cobroActivo();

// Saldo total papelería = total entrado - total salido
$stmtEntradas = $db->prepare("
    SELECT COALESCE(SUM(monto_papeleria), 0)
    FROM papeleria
    WHERE cobro_id=?
");
$stmtEntradas->execute([$cobro]);
$totalEntradas = (float)$stmtEntradas->fetchColumn();

$stmtSalidas = $db->prepare("
    SELECT COALESCE(SUM(monto), 0)
    FROM papeleria_salidas
    WHERE cobro_id=?
");
$stmtSalidas->execute([$cobro]);
$totalSalidas = (float)$stmtSalidas->fetchColumn();

$saldoActual = $totalEntradas - $totalSalidas;

// Historial mensual — agrupar entradas por mes
$stmtSemanal = $db->prepare("
    SELECT
        YEAR(fecha)                          AS anio,
        MONTH(fecha)                         AS mes,
        MIN(fecha)                           AS fecha_inicio,
        MAX(fecha)                           AS fecha_fin,
        COALESCE(SUM(monto_papeleria), 0)    AS total_entradas,
        COUNT(*)                             AS num_prestamos
    FROM papeleria
    WHERE cobro_id=?
    GROUP BY YEAR(fecha), MONTH(fecha)
    ORDER BY anio DESC, mes DESC
");
$stmtSemanal->execute([$cobro]);
$semanas = $stmtSemanal->fetchAll();

// Salidas por mes para cruzar
$stmtSalidasSem = $db->prepare("
    SELECT
        YEAR(fecha)                    AS anio,
        MONTH(fecha)                   AS mes,
        COALESCE(SUM(monto), 0)        AS total_salidas
    FROM papeleria_salidas
    WHERE cobro_id=?
    GROUP BY YEAR(fecha), MONTH(fecha)
");
$stmtSalidasSem->execute([$cobro]);
$salidasPorSemana = [];
foreach ($stmtSalidasSem->fetchAll() as $s) {
    $salidasPorSemana[$s['anio'].'-'.$s['mes']] = (float)$s['total_salidas'];
}

// Últimas salidas
$stmtUltSalidas = $db->prepare("
    SELECT ps.*, cat.nombre AS categoria_nombre, u.nombre AS usuario_nombre
    FROM papeleria_salidas ps
    LEFT JOIN papeleria_categorias cat ON cat.id = ps.categoria_id
    LEFT JOIN usuarios u ON u.id = ps.usuario_id
    WHERE ps.cobro_id=?
    ORDER BY ps.fecha DESC, ps.created_at DESC
    LIMIT 30
");
$stmtUltSalidas->execute([$cobro]);
$ultimasSalidas = $stmtUltSalidas->fetchAll();

// Categorías activas
$stmtCats = $db->prepare("SELECT * FROM papeleria_categorias WHERE cobro_id=? AND activa=1 ORDER BY nombre");
$stmtCats->execute([$cobro]);
$categorias = $stmtCats->fetchAll();

// Todas las categorías (para gestión)
$stmtTodas = $db->prepare("SELECT * FROM papeleria_categorias WHERE cobro_id=? ORDER BY nombre");
$stmtTodas->execute([$cobro]);
$todasCategorias = $stmtTodas->fetchAll();

$pageTitle   = 'Papelería';
$pageSection = 'Papelería';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header-row">
    <div>
        <h1>PAPELERÍA</h1>
        <p>// Saldo independiente de papelería — no afecta la caja del cobro</p>
    </div>
    <div class="btn-group">
        <button class="btn btn-ghost btn-sm" onclick="openModal('modal-categorias')">⚙ Categorías</button>
        <button class="btn btn-primary" onclick="openModal('modal-nueva-salida')">+ Registrar salida</button>
    </div>
</div>

<!-- Stats principales -->
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:1.5rem">
    <div class="stat-card" style="border-color:#f9731633">
        <div class="stat-label">TOTAL RECAUDADO</div>
        <div class="stat-value" style="color:#f97316"><?= fmt($totalEntradas) ?></div>
        <div class="stat-sub">Papelería cobrada histórica</div>
    </div>
    <div class="stat-card" style="border-color:#ef444433">
        <div class="stat-label">TOTAL PAGADO</div>
        <div class="stat-value" style="color:#ef4444"><?= fmt($totalSalidas) ?></div>
        <div class="stat-sub">Salidas registradas</div>
    </div>
    <div class="stat-card" style="border-color:<?= $saldoActual >= 0 ? '#22c55e33' : '#ef444433' ?>;background:<?= $saldoActual >= 0 ? 'rgba(34,197,94,.04)' : 'rgba(239,68,68,.04)' ?>">
        <div class="stat-label">SALDO DISPONIBLE</div>
        <div class="stat-value" style="color:<?= $saldoActual >= 0 ? '#22c55e' : '#ef4444' ?>"><?= fmt($saldoActual) ?></div>
        <div class="stat-sub">Listo para distribuir</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:1.5rem">

<!-- Historial semanal -->
<div>
    <div class="card">
        <div class="card-header">
            <span class="card-title">HISTORIAL SEMANAL</span>
            <div style="display:flex;gap:0.5rem;align-items:center">
                <input type="month" id="filtro-mes"
                       value="<?= date('Y-m') ?>"
                       style="padding:0.35rem 0.5rem;border-radius:var(--radius);border:1px solid var(--border);background:var(--card);color:var(--text);font-family:var(--font-mono);font-size:0.8rem"
                       onchange="cargarSemanas()">
            </div>
        </div>
        <div id="tabla-semanas">
            <div class="empty-state"><span class="empty-icon">⏳</span><p>Cargando...</p></div>
        </div>
    </div>
</div>

<!-- Salidas recientes -->
<div>
    <div class="card">
        <div class="card-header">
            <span class="card-title">SALIDAS RECIENTES</span>
        </div>
        <?php if (empty($ultimasSalidas)): ?>
        <div class="empty-state"><span class="empty-icon">◈</span><p>Sin salidas registradas</p></div>
        <?php else: ?>
        <div style="padding:0.5rem 0">
        <?php foreach ($ultimasSalidas as $s): ?>
        <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:0.75rem 1.25rem;border-bottom:1px solid var(--border)">
            <div style="flex:1;min-width:0">
                <div style="font-weight:600;font-size:0.9rem"><?= htmlspecialchars($s['descripcion']) ?></div>
                <div style="font-size:0.72rem;color:var(--muted);font-family:var(--font-mono);margin-top:2px">
                    <?= htmlspecialchars($s['categoria_nombre'] ?? '—') ?> ·
                    <?= date('d M Y', strtotime($s['fecha'])) ?> ·
                    <?= htmlspecialchars($s['usuario_nombre'] ?? '—') ?>
                </div>
            </div>
            <div style="font-size:1rem;font-weight:700;color:#ef4444;flex-shrink:0;margin-left:0.75rem">
                −<?= fmt($s['monto']) ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

</div>

<!-- Modal nueva salida -->
<div class="modal-overlay" id="modal-nueva-salida">
    <div class="modal" style="max-width:480px">
        <div class="modal-header">
            <h2>NUEVA SALIDA DE PAPELERÍA</h2>
            <button class="modal-close" onclick="closeModal('modal-nueva-salida')">✕</button>
        </div>
        <div class="modal-body">
            <div class="form-grid">
                <div class="field field-span2">
                    <label>Categoría *</label>
                    <select id="sal-categoria">
                        <option value="">— Seleccionar —</option>
                        <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field field-span2">
                    <label>Descripción *</label>
                    <input type="text" id="sal-descripcion" placeholder="Ej: Pago cobrador semana 15">
                </div>
                <div class="field">
                    <label>Monto *</label>
                    <input type="number" id="sal-monto" placeholder="0" step="1000" min="1">
                </div>
                <div class="field">
                    <label>Fecha *</label>
                    <input type="date" id="sal-fecha" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div style="background:rgba(249,115,22,.08);border:1px solid rgba(249,115,22,.3);border-radius:var(--radius);padding:0.75rem;font-size:0.8rem;color:#f97316;margin-top:0.5rem">
                Saldo disponible: <strong><?= fmt($saldoActual) ?></strong>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modal-nueva-salida')">Cancelar</button>
            <button class="btn btn-primary" onclick="registrarSalida()">REGISTRAR</button>
        </div>
    </div>
</div>

<!-- Modal gestión categorías -->
<div class="modal-overlay" id="modal-categorias">
    <div class="modal" style="max-width:480px">
        <div class="modal-header">
            <h2>CATEGORÍAS DE PAPELERÍA</h2>
            <button class="modal-close" onclick="closeModal('modal-categorias')">✕</button>
        </div>
        <div class="modal-body">
            <!-- Crear nueva -->
            <div style="display:flex;gap:0.5rem;margin-bottom:1rem">
                <input type="text" id="nueva-cat-nombre"
                       placeholder="Nombre de la categoría"
                       style="flex:1;padding:0.6rem;border-radius:var(--radius);border:1px solid var(--border);background:var(--bg);color:var(--text)">
                <button class="btn btn-primary btn-sm" onclick="crearCategoriaPapeleria()">+ Agregar</button>
            </div>

            <!-- Lista -->
            <?php if (empty($todasCategorias)): ?>
            <div class="empty-state" style="padding:1.5rem"><span class="empty-icon">◈</span><p>Sin categorías</p></div>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Nombre</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($todasCategorias as $cat): ?>
                        <tr>
                            <td><?= htmlspecialchars($cat['nombre']) ?></td>
                            <td>
                                <span class="badge <?= $cat['activa'] ? 'badge-green' : 'badge-muted' ?>">
                                    <?= $cat['activa'] ? 'ACTIVA' : 'INACTIVA' ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-ghost btn-sm"
                                        onclick="toggleCategoriaPapeleria(<?= $cat['id'] ?>, <?= $cat['activa'] ? 0 : 1 ?>)">
                                    <?= $cat['activa'] ? 'Inactivar' : 'Activar' ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modal-categorias')">Cerrar</button>
        </div>
    </div>
</div>

<?php
$saldoActualJs = $saldoActual;
$extraScript = '<script>const SALDO_PAPELERIA = ' . $saldoActualJs . ';</script>';
$extraScript .= <<<'JS'
<script>

document.addEventListener('DOMContentLoaded', () => cargarSemanas());

async function cargarSemanas() {
    const mes = document.getElementById('filtro-mes').value;
    if (!mes) return;

    const [anio, mesNum] = mes.split('-').map(Number);
    const primerDia = new Date(anio, mesNum - 1, 1);
    const ultimoDia = new Date(anio, mesNum, 0);

    const semanas = [];
    let cursor = new Date(primerDia);

    const diaSemana = cursor.getDay();
    if (diaSemana !== 1) {
        const diff = diaSemana === 0 ? -6 : 1 - diaSemana;
        cursor.setDate(cursor.getDate() + diff);
    }

    while (cursor <= ultimoDia) {
        const lunes  = new Date(cursor);
        const sabado = new Date(cursor);
        sabado.setDate(sabado.getDate() + 5);

        const inicioReal = lunes < primerDia ? new Date(primerDia) : lunes;
        const finReal    = sabado > ultimoDia ? new Date(ultimoDia) : sabado;

        semanas.push({
            inicio : inicioReal.toISOString().split('T')[0],
            fin    : finReal.toISOString().split('T')[0],
            label  : inicioReal.getDate() + ' → ' + finReal.getDate() + ' ' +
                     finReal.toLocaleDateString('es-CO', { month: 'short' })
        });

        cursor.setDate(cursor.getDate() + 7);
    }

    const fechaIni = primerDia.toISOString().split('T')[0];
    const fechaFin = ultimoDia.toISOString().split('T')[0];

    const res = await apiPost('/api/papeleria.php', {
        action    : 'historial',
        fecha_ini : fechaIni,
        fecha_fin : fechaFin
    });

    if (!res.ok) {
        document.getElementById('tabla-semanas').innerHTML =
            '<div class="empty-state"><span class="empty-icon">✕</span><p>' + (res.msg || 'Error') + '</p></div>';
        return;
    }

    const fmt = n => '$' + Math.round(parseFloat(n) || 0).toLocaleString('es-CO');

    let html = `
        <div class="table-wrap"><table>
            <thead><tr>
                <th>Semana</th><th>Préstamos</th>
                <th>Entradas</th><th>Salidas</th><th>Balance</th>
            </tr></thead>
            <tbody>
    `;

    let hayDatos = false;

    semanas.forEach(sem => {
        const entradas      = res.entradas.filter(e => e.fecha >= sem.inicio && e.fecha <= sem.fin);
        const salidas       = res.salidas.filter(s  => s.fecha >= sem.inicio && s.fecha <= sem.fin);
        const totalEntradas = entradas.reduce((a, e) => a + parseFloat(e.monto_papeleria || 0), 0);
        const totalSalidas  = salidas.reduce((a, s)  => a + parseFloat(s.monto || 0), 0);
        const balance       = totalEntradas - totalSalidas;

        if (totalEntradas === 0 && totalSalidas === 0) return;
        hayDatos = true;

        const balColor = balance >= 0 ? '#22c55e' : '#ef4444';

        html += `<tr>
            <td class="text-mono text-muted" style="font-size:0.78rem">${sem.label}</td>
            <td class="text-muted">${entradas.length}</td>
            <td class="text-mono" style="color:#f97316;font-weight:600">${fmt(totalEntradas)}</td>
            <td class="text-mono" style="color:#ef4444">${fmt(totalSalidas)}</td>
            <td class="text-mono fw-600" style="color:${balColor}">
                ${balance >= 0 ? '+' : ''}${fmt(balance)}
            </td>
        </tr>`;
    });

    if (!hayDatos) {
        html = '<div class="empty-state"><span class="empty-icon">◈</span><p>Sin movimientos en este mes</p></div>';
    } else {
        html += '</tbody></table></div>';
    }

    document.getElementById('tabla-semanas').innerHTML = html;
}

async function registrarSalida() {
    const categoria   = document.getElementById('sal-categoria').value;
    const descripcion = document.getElementById('sal-descripcion').value.trim();
    const monto       = parseFloat(document.getElementById('sal-monto').value) || 0;
    const fecha       = document.getElementById('sal-fecha').value;

    if (!categoria)         { toast('Selecciona una categoría', 'error'); return; }
    if (!descripcion)       { toast('Ingresa la descripción', 'error'); return; }
    if (!monto || monto<=0) { toast('Ingresa el monto', 'error'); return; }
    if (!fecha)             { toast('Selecciona la fecha', 'error'); return; }

    if (monto > SALDO_PAPELERIA) {
        toast('Saldo insuficiente. Disponible: $' + Math.round(SALDO_PAPELERIA).toLocaleString('es-CO'), 'error');
        return;
    }

    const btn = document.querySelector('[onclick="registrarSalida()"]');
    btn.textContent = '⏳ Guardando...';
    btn.disabled    = true;

    const res = await apiPost('/api/papeleria.php', {
        action      : 'salida',
        categoria_id: parseInt(categoria),
        descripcion,
        monto,
        fecha
    });

    if (res.ok) {
        toast(res.msg);
        closeModal('modal-nueva-salida');
        setTimeout(() => location.reload(), 800);
    } else {
        toast(res.msg || 'Error', 'error');
        btn.textContent = 'REGISTRAR';
        btn.disabled    = false;
    }
}

async function crearCategoriaPapeleria() {
    const nombre = document.getElementById('nueva-cat-nombre').value.trim();
    if (!nombre) { toast('Ingresa el nombre', 'error'); return; }
    const res = await apiPost('/api/papeleria.php', { action: 'crear_categoria', nombre });
    if (res.ok) { toast(res.msg); setTimeout(() => location.reload(), 600); }
    else toast(res.msg || 'Error', 'error');
}

async function toggleCategoriaPapeleria(id, activa) {
    const res = await apiPost('/api/papeleria.php', { action: 'toggle_categoria', id, activa });
    if (res.ok) { toast(res.msg); setTimeout(() => location.reload(), 600); }
    else toast(res.msg || 'Error', 'error');
}
</script>
JS;

require_once __DIR__ . '/../includes/footer.php';
?>