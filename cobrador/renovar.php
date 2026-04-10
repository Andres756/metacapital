<?php
$pageTitle = 'Renovar préstamo';
$pageNav   = 'cobrar';
require_once __DIR__ . '/header.php';

$db          = getDB();
$prestamo_id = (int)($_GET['prestamo'] ?? 0);

if (!$prestamo_id) {
    header('Location: /cobrador/dashboard.php'); exit;
}

// Cargar préstamo — verificar que pertenece al cobro activo y no es clavo
$stmt = $db->prepare("
    SELECT p.*, d.nombre AS deudor_nombre, d.telefono AS deudor_tel,
           d.documento AS deudor_doc, d.id AS deudor_id, d.comportamiento
    FROM prestamos p
    JOIN deudores d ON d.id = p.deudor_id
    WHERE p.id = ? AND p.cobro_id = ?
      AND p.estado IN ('activo','en_mora','en_acuerdo')
");
$stmt->execute([$prestamo_id, $cobro]);
$p = $stmt->fetch();

if (!$p) {
    header('Location: /cobrador/dashboard.php'); exit;
}

// Bloquear clavos
if ($p['comportamiento'] === 'clavo') {
    header('Location: /cobrador/cobrar.php?deudor=' . $p['deudor_id']); exit;
}

// % papelería del cobro
$stmtPap = $db->prepare("SELECT papeleria_pct FROM cobros WHERE id=?");
$stmtPap->execute([$cobro]);
$papeleria_pct = (float)($stmtPap->fetchColumn() ?? 10);

// Verificar si tiene pagos
$stmtPagos = $db->prepare("SELECT COUNT(*) FROM pagos WHERE prestamo_id=? AND (anulado=0 OR anulado IS NULL)");
$stmtPagos->execute([$prestamo_id]);
$tienePagos = (int)$stmtPagos->fetchColumn() > 0;

// Si no tiene pagos verificar que plazo vencido
$puedeRenovar = true;
$avisoSinPagos = false;
if (!$tienePagos) {
    $stmtUlt = $db->prepare("SELECT MAX(fecha_vencimiento) FROM cuotas WHERE prestamo_id=? AND estado != 'anulado'");
    $stmtUlt->execute([$prestamo_id]);
    $ultima = $stmtUlt->fetchColumn();
    if ($ultima && $ultima >= date('Y-m-d')) {
        $puedeRenovar  = false; // plazo aún vigente sin pagos
    }
    $avisoSinPagos = true;
}
?>

<!-- Header -->
<div class="cob-header">
    <div>
        <div class="cob-title">RENOVAR PRÉSTAMO</div>
        <div style="font-size:0.7rem;color:var(--muted);font-family:var(--font-mono)">
            Préstamo #<?= $prestamo_id ?>
        </div>
    </div>
    <a href="/cobrador/cobrar.php?deudor=<?= $p['deudor_id'] ?>"
       style="color:var(--muted);font-size:1.3rem;text-decoration:none">✕</a>
</div>

<!-- Deudor -->
<div class="cob-card" style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1rem">
    <div class="cob-avatar" style="width:46px;height:46px;font-size:1.2rem;flex-shrink:0">
        <?= strtoupper(substr($p['deudor_nombre'], 0, 1)) ?>
    </div>
    <div style="flex:1;min-width:0">
        <div style="font-weight:700;font-size:1rem"><?= htmlspecialchars($p['deudor_nombre']) ?></div>
        <div style="font-size:0.7rem;color:var(--muted);font-family:var(--font-mono)">
            CC: <?= htmlspecialchars($p['deudor_doc'] ?? '—') ?>
            <?php if ($p['deudor_tel']): ?> · <?= htmlspecialchars($p['deudor_tel']) ?><?php endif; ?>
        </div>
    </div>
</div>

<!-- Resumen préstamo actual -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.5rem;margin-bottom:1rem">
    <div style="padding:0.75rem;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);text-align:center">
        <div style="font-family:var(--font-mono);font-size:0.58rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.2rem">Saldo actual</div>
        <div style="font-weight:700;color:#f97316;font-size:1rem">$<?= number_format($p['saldo_pendiente'], 0, ',', '.') ?></div>
    </div>
    <div style="padding:0.75rem;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);text-align:center">
        <div style="font-family:var(--font-mono);font-size:0.58rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.2rem">Monto original</div>
        <div style="font-weight:700;font-size:1rem">$<?= number_format($p['monto_prestado'], 0, ',', '.') ?></div>
    </div>
    <div style="padding:0.75rem;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);text-align:center">
        <div style="font-family:var(--font-mono);font-size:0.58rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.2rem">Frecuencia</div>
        <div style="font-weight:700;font-size:1rem"><?= ucfirst($p['frecuencia_pago']) ?></div>
    </div>
</div>

<?php if ($avisoSinPagos): ?>
<div style="padding:0.75rem 1rem;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.35);border-radius:var(--radius);font-size:0.8rem;color:#f59e0b;margin-bottom:1rem;font-family:var(--font-mono)">
    ⚠ Este préstamo no tiene pagos registrados.
    <?php if (!$puedeRenovar): ?>
    Aún tiene cuotas vigentes — no se puede renovar todavía.
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!$puedeRenovar): ?>
<div class="cob-card" style="text-align:center;padding:2rem;color:var(--muted)">
    <div style="font-size:2rem;margin-bottom:0.5rem">⏳</div>
    <div style="font-family:var(--font-mono);font-size:0.82rem">
        No se puede renovar — el préstamo no tiene pagos y aún hay cuotas vigentes.
    </div>
    <a href="/cobrador/cobrar.php?deudor=<?= $p['deudor_id'] ?>"
       class="cob-btn cob-btn-ghost" style="display:inline-block;margin-top:1rem">
        ← Volver
    </a>
</div>

<?php else: ?>

<!-- Formulario renovación -->
<form id="form-renovar">

    <div class="field-lg">
        <label>Nuevo monto *</label>
        <div style="font-size:0.68rem;color:var(--muted);font-family:var(--font-mono);margin-bottom:0.35rem">
            Mínimo: $<?= number_format($p['saldo_pendiente'], 0, ',', '.') ?>
        </div>
        <input type="number" id="r_monto"
               value="<?= max($p['monto_prestado'], $p['saldo_pendiente']) ?>"
               min="<?= $p['saldo_pendiente'] ?>" step="10000"
               style="font-size:1.6rem;font-weight:700;text-align:center;color:var(--accent);width:100%;padding:0.85rem;border-radius:var(--radius);border:1px solid var(--border);background:var(--bg);color:var(--accent)"
               oninput="calcularPreview()">
    </div>

    <!-- Diferencia que sale de caja -->
    <div id="wrap-diferencia" style="display:none;padding:0.6rem 0.85rem;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);border-radius:var(--radius);margin-bottom:0.75rem;font-family:var(--font-mono);font-size:0.78rem;color:#ef4444">
        ⚠ Sale de caja: <strong id="lbl-diferencia">—</strong>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-bottom:0.75rem">
        <div class="field-lg" style="margin:0">
            <label>Tipo de interés</label>
            <select id="r_tipo_int" onchange="calcularPreview()">
                <option value="porcentaje" <?= $p['tipo_interes']==='porcentaje'?'selected':'' ?>>% Porcentaje</option>
                <option value="valor_fijo" <?= $p['tipo_interes']==='valor_fijo'?'selected':'' ?>>$ Valor fijo</option>
            </select>
        </div>
        <div class="field-lg" style="margin:0">
            <label id="r_label_int">Interés (%)</label>
            <input type="number" id="r_interes"
                   value="<?= $p['interes_valor'] ?>"
                   step="1" min="0" oninput="calcularPreview()">
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-bottom:0.75rem">
        <div class="field-lg" style="margin:0">
            <label>Frecuencia</label>
            <select id="r_frecuencia" onchange="onFreChange()">
                <option value="diario"    <?= $p['frecuencia_pago']==='diario'   ?'selected':'' ?>>Diario</option>
                <option value="semanal"   <?= $p['frecuencia_pago']==='semanal'  ?'selected':'' ?>>Semanal</option>
                <option value="quincenal" <?= $p['frecuencia_pago']==='quincenal'?'selected':'' ?>>Quincenal</option>
                <option value="mensual"   <?= $p['frecuencia_pago']==='mensual'  ?'selected':'' ?>>Mensual</option>
            </select>
        </div>
        <div class="field-lg" style="margin:0">
            <label>Número de cuotas</label>
            <input type="number" id="r_cuotas"
                   value="<?= $p['num_cuotas'] ?>"
                   min="1" oninput="calcularPreview()">
        </div>
    </div>

    <!-- Omitir domingos -->
    <div id="r_wrap_domingos" style="display:<?= $p['frecuencia_pago']==='diario'?'block':'none' ?>;margin-bottom:0.75rem">
        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:0.95rem">
            <input type="checkbox" id="r_domingos" style="width:20px;height:20px"
                   <?= $p['omitir_domingos'] ? 'checked' : '' ?>>
            Omitir domingos
        </label>
    </div>

    <div class="field-lg">
        <label>Método de pago</label>
        <select id="r_metodo">
            <option value="efectivo">Efectivo</option>
            <option value="banco">Banco</option>
        </select>
    </div>

    <div class="field-lg">
        <label>Nota <span style="color:var(--muted);font-weight:400">(opcional)</span></label>
        <textarea id="r_nota" rows="2"
                  style="width:100%;padding:0.75rem;border-radius:var(--radius);border:1px solid var(--border);background:var(--bg);color:var(--text);font-size:0.95rem;resize:none"
                  placeholder="Observaciones..."></textarea>
    </div>

    <!-- Preview -->
    <div id="r_preview" style="display:none;margin-bottom:1rem">
        <div style="background:rgba(124,106,255,.1);border:1px solid rgba(124,106,255,.3);border-radius:var(--radius);padding:1rem">
            <div style="font-family:var(--font-mono);font-size:0.62rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.65rem">
                Resumen renovación
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem">
                <div>
                    <div style="font-size:0.68rem;color:var(--muted);font-family:var(--font-mono)">Interés</div>
                    <div style="font-weight:700" id="r_prev_int">—</div>
                </div>
                <div>
                    <div style="font-size:0.68rem;color:var(--muted);font-family:var(--font-mono)">Total a pagar</div>
                    <div style="font-weight:700;color:var(--accent)" id="r_prev_total">—</div>
                </div>
                <div>
                    <div style="font-size:0.68rem;color:var(--muted);font-family:var(--font-mono)">Valor cuota</div>
                    <div style="font-weight:700" id="r_prev_cuota">—</div>
                </div>
                <div>
                    <div style="font-size:0.68rem;color:var(--muted);font-family:var(--font-mono)">Fecha fin est.</div>
                    <div style="font-weight:700" id="r_prev_fecha">—</div>
                </div>
            </div>
        </div>
    </div>

    <button type="button" class="cob-btn cob-btn-primary" onclick="renovar()">
        🔄 RENOVAR PRÉSTAMO
    </button>
    <a href="/cobrador/cobrar.php?deudor=<?= $p['deudor_id'] ?>"
       class="cob-btn cob-btn-ghost" style="display:block;text-align:center;margin-top:0.5rem;text-decoration:none">
        Cancelar
    </a>
</form>

<?php endif; ?>

<?php
$saldo_js      = (float)$p['saldo_pendiente'];
$prestamo_id_js= (int)$prestamo_id;
$deudor_id_js  = (int)$p['deudor_id'];
$tiene_pagos_js= $tienePagos ? 'true' : 'false';

$extraScript = <<<JS
<script>
const SALDO       = {$saldo_js};
const TIENE_PAGOS = {$tiene_pagos_js};

function fmt(n) { return '\$' + Math.round(n).toLocaleString('es-CO'); }

function onFreChange() {
    const freq = document.getElementById('r_frecuencia').value;
    document.getElementById('r_wrap_domingos').style.display = freq === 'diario' ? 'block' : 'none';
    if (freq !== 'diario') document.getElementById('r_domingos').checked = false;
    calcularPreview();
}

function calcularPreview() {
    const monto   = parseFloat(document.getElementById('r_monto').value)   || 0;
    const tipoInt = document.getElementById('r_tipo_int').value;
    const intVal  = parseFloat(document.getElementById('r_interes').value)  || 0;
    const cuotas  = parseInt(document.getElementById('r_cuotas').value)     || 1;
    const freq    = document.getElementById('r_frecuencia').value;

    document.getElementById('r_label_int').textContent =
        tipoInt === 'porcentaje' ? 'Interés (%)' : 'Interés (\$ fijo)';

    // Diferencia que sale de caja
    const dif = monto - SALDO;
    const wrapDif = document.getElementById('wrap-diferencia');
    if (dif > 0) {
        document.getElementById('lbl-diferencia').textContent = fmt(dif);
        wrapDif.style.display = 'block';
    } else {
        wrapDif.style.display = 'none';
    }

    if (!monto || monto < SALDO) {
        document.getElementById('r_preview').style.display = 'none';
        return;
    }

    const intCalc  = tipoInt === 'porcentaje' ? monto * (intVal / 100) : intVal;
    const total    = monto + intCalc;
    const valCuota = cuotas > 0 ? Math.round(total / cuotas) : total;
    const diasMap  = { diario:1, semanal:7, quincenal:15, mensual:30 };
    const fechaFin = new Date();
    fechaFin.setDate(fechaFin.getDate() + (diasMap[freq] || 30) * cuotas);

    document.getElementById('r_prev_int').textContent   = fmt(intCalc);
    document.getElementById('r_prev_total').textContent = fmt(total);
    document.getElementById('r_prev_cuota').textContent = fmt(valCuota) + ' × ' + cuotas;
    document.getElementById('r_prev_fecha').textContent =
        fechaFin.toLocaleDateString('es-CO', {day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('r_preview').style.display = 'block';
}

async function renovar() {
    const monto  = parseFloat(document.getElementById('r_monto').value) || 0;
    const cuotas = parseInt(document.getElementById('r_cuotas').value)   || 1;
    const freq   = document.getElementById('r_frecuencia').value;

    if (!monto || monto <= 0) { alert('Ingresa el monto de renovación'); return; }
    if (monto < SALDO) {
        alert('El monto no puede ser menor al saldo pendiente (' + fmt(SALDO) + ')');
        return;
    }

    if (!TIENE_PAGOS) {
        if (!confirm('⚠ Este préstamo no tiene pagos registrados y el plazo ya venció.\\n¿Confirmas la renovación?')) return;
    }

    const dif = monto - SALDO;
    if (dif > 0) {
        if (!confirm('Se registrará una salida de caja de ' + fmt(dif) + '.\\n¿El dinero ya fue entregado al cliente?')) return;
    }

    const tipoInt  = document.getElementById('r_tipo_int').value;
    const intVal   = parseFloat(document.getElementById('r_interes').value) || 0;
    const intCalc  = tipoInt === 'porcentaje' ? monto * (intVal/100) : intVal;
    const total    = monto + intCalc;
    const valCuota = Math.round(total / cuotas);

    if (!confirm(
        'Confirmar renovación:\\n\\n' +
        'Nuevo monto: ' + fmt(monto) + '\\n' +
        'Interés: '     + fmt(intCalc) + '\\n' +
        'Total: '       + fmt(total) + '\\n' +
        'Cuotas: '      + cuotas + ' × ' + fmt(valCuota) + '\\n' +
        'Frecuencia: '  + freq
    )) return;

    const btn = document.querySelector('[onclick="renovar()"]');
    btn.textContent = '⏳ Procesando...';
    btn.disabled    = true;

    try {
        const res = await fetch('/api/prestamos.php', {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({
                action           : 'renovar',
                prestamo_id      : {$prestamo_id_js},
                monto_renovacion : monto,
                tipo_interes     : tipoInt,
                interes_valor    : intVal,
                frecuencia_pago  : freq,
                num_cuotas       : cuotas,
                metodo_pago      : document.getElementById('r_metodo').value,
                omitir_domingos  : document.getElementById('r_domingos').checked ? 1 : 0,
                nota_gestion     : document.getElementById('r_nota').value.trim(),
            })
        });

        const texto = await res.text();
        let data;
        try { data = JSON.parse(texto); }
        catch(e) {
            alert('Error del servidor: ' + texto.substring(0, 200));
            btn.textContent = '🔄 RENOVAR PRÉSTAMO';
            btn.disabled    = false;
            return;
        }

        if (data.ok) {
            btn.textContent      = '✓ Renovado';
            btn.style.background = '#22c55e';
            setTimeout(() => {
                window.location = '/cobrador/cobrar.php?deudor={$deudor_id_js}';
            }, 1200);
        } else {
            alert(data.msg || 'Error al renovar');
            btn.textContent = '🔄 RENOVAR PRÉSTAMO';
            btn.disabled    = false;
        }
    } catch(e) {
        alert('Error de conexión: ' + e.message);
        btn.textContent = '🔄 RENOVAR PRÉSTAMO';
        btn.disabled    = false;
    }
}

// Calcular al cargar
calcularPreview();
</script>
JS;

require_once __DIR__ . '/footer.php';
?>