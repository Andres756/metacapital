<?php
$pageTitle = 'Nuevo préstamo';
$pageNav   = 'prestamo';
require_once __DIR__ . '/header.php';

$db        = getDB();
$deudor_id = (int)($_GET['deudor'] ?? 0);
$deudorPre = null;

// Cargar deudor preseleccionado
if ($deudor_id) {
    $stmt = $db->prepare("
        SELECT d.* FROM deudores d
        JOIN deudor_cobro dc ON dc.deudor_id = d.id
        WHERE d.id = ? AND dc.cobro_id = ? AND d.activo = 1
          AND d.comportamiento != 'clavo'
    ");
    $stmt->execute([$deudor_id, $cobro]);
    $deudorPre = $stmt->fetch();
}

// Cuentas disponibles
$stmtCuentas = $db->prepare("SELECT id, nombre, tipo FROM cuentas WHERE cobro_id=? AND activa=1 ORDER BY nombre");
$stmtCuentas->execute([$cobro]);
$cuentas = $stmtCuentas->fetchAll();

// Lista de deudores para el buscador (sin clavos)
$stmtD = $db->prepare("
    SELECT d.id, d.nombre, d.telefono, d.documento
    FROM deudores d
    JOIN deudor_cobro dc ON dc.deudor_id = d.id
    WHERE dc.cobro_id = ? AND d.activo = 1
      AND d.comportamiento != 'clavo'
    ORDER BY d.nombre ASC
");
$stmtD->execute([$cobro]);
$deudores = $stmtD->fetchAll();
?>

<div class="cob-header">
    <div class="cob-title">NUEVO PRÉSTAMO</div>
</div>

<!-- Buscador de deudor -->
<?php if (!$deudorPre): ?>
<div style="margin-bottom:1rem">
    <input type="text" id="buscador-deudor"
           placeholder="🔍 Buscar cliente..."
           style="width:100%;padding:0.85rem;font-size:1rem;border-radius:var(--radius);border:1px solid var(--border);background:var(--card);color:var(--text)"
           oninput="filtrarDeudores(this.value)">
</div>

<div id="lista-busqueda">
<?php foreach ($deudores as $d): ?>
<div class="cob-deudor-row deudor-item"
     data-nombre="<?= strtolower(htmlspecialchars($d['nombre'])) ?>"
     onclick="window.location='/cobrador/prestamo.php?deudor=<?= $d['id'] ?>'"
     style="cursor:pointer">
    <div class="cob-avatar"><?= strtoupper(substr($d['nombre'], 0, 1)) ?></div>
    <div style="flex:1;min-width:0">
        <div style="font-weight:600"><?= htmlspecialchars($d['nombre']) ?></div>
        <div style="font-size:0.72rem;color:var(--muted);font-family:var(--font-mono)">
            CC: <?= htmlspecialchars($d['documento'] ?? '—') ?>
            <?php if ($d['telefono']): ?> · <?= htmlspecialchars($d['telefono']) ?><?php endif; ?>
        </div>
    </div>
    <div style="color:var(--muted);font-size:0.8rem">→</div>
</div>
<?php endforeach; ?>
</div>

<?php else: ?>

<!-- Deudor seleccionado -->
<div class="cob-card" style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem">
    <div class="cob-avatar" style="width:50px;height:50px;font-size:1.3rem">
        <?= strtoupper(substr($deudorPre['nombre'], 0, 1)) ?>
    </div>
    <div style="flex:1">
        <div style="font-weight:700;font-size:1.05rem"><?= htmlspecialchars($deudorPre['nombre']) ?></div>
        <div style="font-size:0.75rem;color:var(--muted);font-family:var(--font-mono)">
            CC: <?= htmlspecialchars($deudorPre['documento'] ?? '—') ?>
            <?php if ($deudorPre['telefono']): ?> · <?= htmlspecialchars($deudorPre['telefono']) ?><?php endif; ?>
        </div>
    </div>
    <a href="/cobrador/prestamo.php"
       style="background:none;border:none;color:var(--muted);font-size:1.2rem;text-decoration:none">✕</a>
</div>

<!-- Formulario préstamo -->
<form id="form-prestamo">

    <div class="field-lg">
        <label>Monto a prestar *</label>
        <input type="number" id="p_monto" placeholder="0"
               step="10000" min="1"
               style="font-size:1.6rem;font-weight:700;text-align:center;color:var(--accent)"
               oninput="calcularPreview()">
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-bottom:0.75rem">
        <div class="field-lg" style="margin:0">
            <label>Tipo de interés</label>
            <select id="p_tipo_int" onchange="calcularPreview()">
                <option value="porcentaje">% Porcentaje</option>
                <option value="valor_fijo">$ Valor fijo</option>
            </select>
        </div>
        <div class="field-lg" style="margin:0">
            <label id="label-interes">Interés (%)</label>
            <input type="number" id="p_interes" value="20"
                   step="1" min="0" oninput="calcularPreview()">
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-bottom:0.75rem">
        <div class="field-lg" style="margin:0">
            <label>Frecuencia</label>
            <select id="p_frecuencia" onchange="onFrecuenciaChange()">
                <option value="diario">Diario</option>
                <option value="semanal">Semanal</option>
                <option value="quincenal">Quincenal</option>
                <option value="mensual" selected>Mensual</option>
            </select>
        </div>
        <div class="field-lg" style="margin:0">
            <label>Número de cuotas</label>
            <input type="number" id="p_cuotas" value="1"
                   min="1" oninput="calcularPreview()">
        </div>
    </div>

    <!-- Omitir domingos (solo si diario) -->
    <div id="wrap-domingos" style="display:none;margin-bottom:0.75rem">
        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:0.95rem">
            <input type="checkbox" id="p_domingos" style="width:20px;height:20px">
            Omitir domingos
        </label>
    </div>

    <div class="field-lg">
        <label>Fecha inicio</label>
        <input type="date" id="p_fecha" value="<?= date('Y-m-d') ?>">
    </div>

    <div class="field-lg">
        <label>Cuenta de desembolso *</label>
        <select id="p_cuenta">
            <option value="">— Seleccionar —</option>
            <?php foreach ($cuentas as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Preview calculado -->
    <div id="preview-prestamo" style="display:none;margin-bottom:1rem">
        <div style="background:rgba(124,106,255,.1);border:1px solid rgba(124,106,255,.3);border-radius:var(--radius);padding:1rem">
            <div style="font-family:var(--font-mono);font-size:0.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.75rem">
                Resumen del préstamo
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem">
                <div>
                    <div style="font-size:0.7rem;color:var(--muted);font-family:var(--font-mono)">Interés</div>
                    <div style="font-weight:700" id="prev-interes">—</div>
                </div>
                <div>
                    <div style="font-size:0.7rem;color:var(--muted);font-family:var(--font-mono)">Total a pagar</div>
                    <div style="font-weight:700;color:var(--accent)" id="prev-total">—</div>
                </div>
                <div>
                    <div style="font-size:0.7rem;color:var(--muted);font-family:var(--font-mono)">Valor cuota</div>
                    <div style="font-weight:700" id="prev-cuota">—</div>
                </div>
                <div>
                    <div style="font-size:0.7rem;color:var(--muted);font-family:var(--font-mono)">Fecha fin est.</div>
                    <div style="font-weight:700" id="prev-fecha">—</div>
                </div>
            </div>
        </div>
    </div>

    <div class="field-lg">
        <label>Observaciones (opcional)</label>
        <textarea id="p_obs" rows="2"
                  style="width:100%;padding:0.75rem;border-radius:var(--radius);border:1px solid var(--border);background:var(--bg);color:var(--text);font-size:1rem;resize:none"
                  placeholder="Notas del préstamo..."></textarea>
    </div>

    <button type="button" class="cob-btn cob-btn-primary" onclick="guardarPrestamo()">
        REGISTRAR PRÉSTAMO
    </button>

</form>

<?php endif; ?>

<?php
$deudorIdJs = $deudorPre ? (int)$deudorPre['id'] : 0;
?>
<script>
const DEUDOR_ID = <?= $deudorIdJs ?>;

function filtrarDeudores(q) {
    const items = document.querySelectorAll('.deudor-item');
    const busq  = q.toLowerCase().trim();
    items.forEach(item => {
        item.style.display = !busq || item.dataset.nombre.includes(busq) ? 'flex' : 'none';
    });
}

function onFrecuenciaChange() {
    const freq = document.getElementById('p_frecuencia').value;
    document.getElementById('wrap-domingos').style.display = freq === 'diario' ? 'block' : 'none';
    document.getElementById('label-interes').textContent =
        document.getElementById('p_tipo_int').value === 'porcentaje' ? 'Interés (%)' : 'Interés ($ fijo)';
    calcularPreview();
}

function calcularPreview() {
    const monto   = parseFloat(document.getElementById('p_monto').value)   || 0;
    const tipoInt = document.getElementById('p_tipo_int').value;
    const intVal  = parseFloat(document.getElementById('p_interes').value)  || 0;
    const cuotas  = parseInt(document.getElementById('p_cuotas').value)     || 1;
    const freq    = document.getElementById('p_frecuencia').value;
    const fecha   = document.getElementById('p_fecha').value;

    document.getElementById('label-interes').textContent =
        tipoInt === 'porcentaje' ? 'Interés (%)' : 'Interés ($ fijo total)';

    if (!monto) { document.getElementById('preview-prestamo').style.display = 'none'; return; }

    const intCalc = tipoInt === 'porcentaje' ? monto * (intVal / 100) : intVal;
    const total   = monto + intCalc;
    const valCuota= cuotas > 0 ? Math.round(total / cuotas) : total;

    const diasMap = { diario:1, semanal:7, quincenal:15, mensual:30 };
    const fechaFin = new Date(fecha || new Date());
    fechaFin.setDate(fechaFin.getDate() + (diasMap[freq] || 30) * cuotas);

    const fmt = n => '$' + Math.round(n).toLocaleString('es-CO');
    document.getElementById('prev-interes').textContent = fmt(intCalc);
    document.getElementById('prev-total').textContent   = fmt(total);
    document.getElementById('prev-cuota').textContent   = fmt(valCuota) + ' × ' + cuotas;
    document.getElementById('prev-fecha').textContent   =
        fechaFin.toLocaleDateString('es-CO', {day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('preview-prestamo').style.display = 'block';
}

async function guardarPrestamo() {
    const monto   = parseFloat(document.getElementById('p_monto').value) || 0;
    const cuenta  = document.getElementById('p_cuenta').value;
    const cuotas  = parseInt(document.getElementById('p_cuotas').value) || 1;
    const fecha   = document.getElementById('p_fecha').value;

    if (!monto || monto <= 0) { alert('Ingresa el monto a prestar'); return; }
    if (!cuenta)              { alert('Selecciona la cuenta de desembolso'); return; }
    if (!fecha)               { alert('Ingresa la fecha de inicio'); return; }

    const freq     = document.getElementById('p_frecuencia').value;
    const domingos = freq === 'diario' && document.getElementById('p_domingos').checked;
    const tipoInt  = document.getElementById('p_tipo_int').value;
    const intVal   = parseFloat(document.getElementById('p_interes').value) || 0;
    const intCalc  = tipoInt === 'porcentaje' ? monto * (intVal/100) : intVal;
    const total    = monto + intCalc;
    const valCuota = Math.round(total / cuotas);
    const fmt      = n => '$' + Math.round(n).toLocaleString('es-CO');

    const ok = confirm(
        'Confirmar préstamo:\n\n' +
        'Monto: '      + fmt(monto)    + '\n' +
        'Interés: '    + fmt(intCalc)  + '\n' +
        'Total: '      + fmt(total)    + '\n' +
        'Cuotas: '     + cuotas + ' × ' + fmt(valCuota) + '\n' +
        'Frecuencia: ' + freq
    );
    if (!ok) return;

    const btn = document.querySelector('[onclick="guardarPrestamo()"]');
    btn.textContent = '⏳ Registrando...';
    btn.disabled    = true;

    try {
        const res = await fetch('/api/prestamos.php', {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({
                action              : 'crear',
                deudor_id           : DEUDOR_ID,
                monto_prestado      : monto,
                tipo_interes        : tipoInt,
                interes_valor       : intVal,
                frecuencia_pago     : freq,
                num_cuotas          : cuotas,
                fecha_inicio        : fecha,
                cuenta_desembolso_id: parseInt(cuenta),
                omitir_domingos     : domingos ? 1 : 0,
                observaciones       : document.getElementById('p_obs').value.trim(),
                tipo_origen         : 'nuevo'
            })
        });

        const texto = await res.text();
        let data;
        try {
            data = JSON.parse(texto);
        } catch(e) {
            console.error('Respuesta no es JSON:', texto);
            alert('Error del servidor: ' + texto.substring(0, 300));
            btn.textContent      = 'REGISTRAR PRÉSTAMO';
            btn.style.background = '';
            btn.disabled         = false;
            return;
        }

        if (data.ok) {
            btn.textContent      = '✓ Préstamo registrado';
            btn.style.background = '#22c55e';
            setTimeout(() => {
                window.location = '/cobrador/cobrar.php?deudor=' + DEUDOR_ID;
            }, 1200);
        } else {
            alert(data.msg || 'Error al registrar');
            btn.textContent      = 'REGISTRAR PRÉSTAMO';
            btn.style.background = '';
            btn.disabled         = false;
        }
    } catch(e) {
        alert('Error de conexión: ' + e.message);
        btn.textContent      = 'REGISTRAR PRÉSTAMO';
        btn.style.background = '';
        btn.disabled         = false;
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>