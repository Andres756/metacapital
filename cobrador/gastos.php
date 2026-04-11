<?php
$pageTitle = 'Gastos';
$pageNav   = 'gastos';
require_once __DIR__ . '/header.php';

$db = getDB();

// Categorías de gasto del cobro
$stmtCat = $db->query("SELECT id, nombre FROM categorias_gasto WHERE activa=1 ORDER BY nombre");
$stmtCat->execute([$cobro]);
$categorias = $stmtCat->fetchAll();

// Gastos del cobrador hoy
$stmtG = $db->prepare("
    SELECT g.*, cat.nombre AS categoria_nombre
    FROM gastos_cobrador g
    LEFT JOIN categorias_gasto cat ON cat.id = g.categoria_id
    WHERE g.cobro_id=? AND g.usuario_id=? AND g.fecha=CURDATE()
    ORDER BY g.created_at DESC
");
$stmtG->execute([$cobro, $_SESSION['usuario_id']]);
$gastosHoy = $stmtG->fetchAll();

$totalHoy = array_sum(array_column($gastosHoy, 'monto'));
?>

<div class="cob-header">
    <div>
        <div class="cob-title">GASTOS</div>
        <div style="font-size:0.72rem;color:var(--muted);font-family:var(--font-mono)">
            <?= date('d M Y') ?>
        </div>
    </div>
    <div style="text-align:right">
        <div style="font-size:1.3rem;font-weight:700;color:#f97316">
            <?= fmt($totalHoy) ?>
        </div>
        <div style="font-size:0.65rem;color:var(--muted);font-family:var(--font-mono)">
            <?= count($gastosHoy) ?> gasto<?= count($gastosHoy) !== 1 ? 's' : '' ?> hoy
        </div>
    </div>
</div>

<!-- Formulario nuevo gasto -->
<div class="cob-card" style="margin-bottom:1.25rem">
    <div style="font-family:var(--font-mono);font-size:0.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.75rem">
        Registrar gasto
    </div>

    <?php if (empty($categorias)): ?>
    <div style="padding:1rem;background:rgba(249,115,22,.08);border:1px solid rgba(249,115,22,.3);border-radius:var(--radius);font-size:0.82rem;color:#f97316;margin-bottom:0.75rem">
        ⚠ El administrador aún no ha creado categorías de gasto. Contacta al admin.
    </div>
    <?php else: ?>

    <div class="field-lg">
        <label>Categoría *</label>
        <select id="g_categoria">
            <option value="">— Seleccionar —</option>
            <?php foreach ($categorias as $cat): ?>
            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field-lg">
        <label>Descripción *</label>
        <input type="text" id="g_descripcion"
               placeholder="¿En qué fue el gasto?"
               style="font-size:1rem">
    </div>

    <div class="field-lg">
        <label>Monto *</label>
        <input type="number" id="g_monto"
               placeholder="0" step="1000" min="1"
               style="font-size:1.6rem;font-weight:700;text-align:center;color:#f97316">
    </div>

    <button class="cob-btn cob-btn-primary" onclick="registrarGasto()"
            style="background:#f97316">
        REGISTRAR GASTO
    </button>

    <?php endif; ?>
</div>

<!-- Gastos de hoy -->
<?php if (!empty($gastosHoy)): ?>
<div style="font-family:var(--font-mono);font-size:0.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.5rem">
    Gastos registrados hoy
</div>

<?php foreach ($gastosHoy as $g):
    $estadoColor = match($g['estado']) {
        'aprobado'  => '#22c55e',
        'rechazado' => '#ef4444',
        default     => '#f59e0b'
    };
    $estadoLabel = match($g['estado']) {
        'aprobado'  => '✓ Aprobado',
        'rechazado' => '✕ Rechazado',
        default     => '⏳ Pendiente'
    };
?>
<div class="cob-card" style="margin-bottom:0.5rem;border-left:3px solid <?= $estadoColor ?>;border-radius:0 var(--radius) var(--radius) 0">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <div style="flex:1;min-width:0">
            <div style="font-weight:600;font-size:0.95rem">
                <?= htmlspecialchars($g['descripcion']) ?>
            </div>
            <div style="font-size:0.72rem;color:var(--muted);font-family:var(--font-mono);margin-top:2px">
                <?= htmlspecialchars($g['categoria_nombre'] ?? 'Sin categoría') ?>
            </div>
        </div>
        <div style="text-align:right;flex-shrink:0;margin-left:0.75rem">
            <div style="font-size:1rem;font-weight:700;color:#f97316">
                <?= fmt($g['monto']) ?>
            </div>
            <div style="font-size:0.65rem;font-family:var(--font-mono);color:<?= $estadoColor ?>;margin-top:2px">
                <?= $estadoLabel ?>
            </div>
        </div>
    </div>
    <?php if ($g['estado'] === 'rechazado'): ?>
    <div style="font-size:0.72rem;color:#ef4444;margin-top:0.4rem;padding-top:0.4rem;border-top:1px solid rgba(239,68,68,.2)">
        El administrador rechazó este gasto — no se incluirá en la liquidación.
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php else: ?>
<div style="text-align:center;padding:2rem;color:var(--muted)">
    <div style="font-size:1.5rem;margin-bottom:0.5rem">◎</div>
    <div style="font-family:var(--font-mono);font-size:0.8rem">Sin gastos registrados hoy</div>
</div>
<?php endif; ?>

<?php
$extraScript = <<<'JS'
<script>
async function registrarGasto() {
    const categoria   = document.getElementById('g_categoria').value;
    const descripcion = document.getElementById('g_descripcion').value.trim();
    const monto       = parseFloat(document.getElementById('g_monto').value) || 0;

    if (!categoria)         { alert('Selecciona una categoría'); return; }
    if (!descripcion)       { alert('Ingresa la descripción del gasto'); return; }
    if (!monto || monto<=0) { alert('Ingresa el monto'); return; }

    const btn = document.querySelector('[onclick="registrarGasto()"]');
    btn.textContent = '⏳ Guardando...';
    btn.disabled    = true;

    try {
        const res = await fetch('/api/gastos.php', {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({
                action      : 'crear',
                categoria_id: parseInt(categoria),
                descripcion,
                monto,
                fecha       : new Date().toISOString().split('T')[0]
            })
        });

        const texto = await res.text();
        let data;
        try { data = JSON.parse(texto); }
        catch(e) { alert('Error del servidor: ' + texto.substring(0,200)); btn.textContent='REGISTRAR GASTO'; btn.disabled=false; return; }

        if (data.ok) {
            btn.textContent      = '✓ Gasto registrado';
            btn.style.background = '#22c55e';
            setTimeout(() => location.reload(), 1000);
        } else {
            alert(data.msg || 'Error al registrar');
            btn.textContent      = 'REGISTRAR GASTO';
            btn.style.background = '#f97316';
            btn.disabled         = false;
        }
    } catch(e) {
        alert('Error de conexión: ' + e.message);
        btn.textContent      = 'REGISTRAR GASTO';
        btn.style.background = '#f97316';
        btn.disabled         = false;
    }
}
</script>
JS;

require_once __DIR__ . '/footer.php';
?>