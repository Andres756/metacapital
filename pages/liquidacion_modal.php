<!-- Modal nueva liquidación -->
<div class="modal-overlay" id="modal-nueva-liquidacion">
    <div class="modal" style="max-width:480px">
        <div class="modal-header">
            <h2>NUEVA LIQUIDACIÓN</h2>
            <button class="modal-close" onclick="closeModal('modal-nueva-liquidacion')">✕</button>
        </div>
        <div class="modal-body">

            <div class="field mb-2">
                <label>Fecha <span class="required">*</span></label>
                <input type="date" id="nueva-liq-fecha"
                       max="<?= date('Y-m-d') ?>"
                       value="<?= date('Y-m-d') ?>">
                <div style="font-size:0.72rem;color:var(--muted);margin-top:0.25rem;font-family:var(--font-mono)">
                    No se permiten fechas futuras ni fechas ya liquidadas
                </div>
            </div>

            <div class="field mb-2">
                <label>Base entregada al cobrador</label>
                <input type="number" id="nueva-liq-base"
                       placeholder="0" step="10000" min="0"
                       style="font-size:1.1rem">
                <div style="font-size:0.72rem;color:var(--muted);margin-top:0.25rem;font-family:var(--font-mono)">
                    Dinero que salió de caja ese día
                </div>
            </div>

            <!-- Preview datos del día seleccionado -->
            <div id="preview-dia" style="display:none">
                <div style="background:var(--bg);border-radius:var(--radius);padding:1rem;border:1px solid var(--border)">
                    <div style="font-family:var(--font-mono);font-size:0.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.75rem">
                        Resumen del día seleccionado
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem">
                        <div>
                            <div style="font-size:0.65rem;color:var(--muted);font-family:var(--font-mono)">Pagos cobrados</div>
                            <div style="font-weight:700;color:#22c55e" id="prev-pagos">—</div>
                        </div>
                        <div>
                            <div style="font-size:0.65rem;color:var(--muted);font-family:var(--font-mono)">Préstamos</div>
                            <div style="font-weight:700;color:var(--accent)" id="prev-prestamos">—</div>
                        </div>
                        <div>
                            <div style="font-size:0.65rem;color:var(--muted);font-family:var(--font-mono)">Gastos aprobados</div>
                            <div style="font-weight:700;color:#f97316" id="prev-gastos">—</div>
                        </div>
                        <div>
                            <div style="font-size:0.65rem;color:var(--muted);font-family:var(--font-mono)">Papelería</div>
                            <div style="font-weight:700;color:#f97316" id="prev-papeleria">—</div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="error-fecha" style="display:none;margin-top:0.75rem">
                <div class="alert alert-danger" style="padding:0.5rem 0.75rem;font-size:0.8rem"></div>
            </div>

        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modal-nueva-liquidacion')">Cancelar</button>
            <button class="btn btn-primary" id="btn-crear-liquidacion" onclick="crearLiquidacion()">
                CREAR LIQUIDACIÓN
            </button>
        </div>
    </div>
</div>

<script>
let fechasUsadas = [];

// Cargar fechas ya usadas al abrir el modal
document.querySelector('[onclick="openModal(\'modal-nueva-liquidacion\')"]')
    ?.addEventListener('click', async () => {
        const res = await apiPost('/api/liquidacion.php', { action: 'fechas_usadas' });
        if (res.ok) fechasUsadas = res.fechas;
        validarFecha();
    });

document.getElementById('nueva-liq-fecha')?.addEventListener('change', () => {
    validarFecha();
    previewDia();
});

async function validarFecha() {
    const fecha    = document.getElementById('nueva-liq-fecha').value;
    const hoy      = new Date().toISOString().split('T')[0];
    const errorDiv = document.getElementById('error-fecha');
    const btn      = document.getElementById('btn-crear-liquidacion');
    const errMsg   = errorDiv.querySelector('.alert');

    if (!fecha) {
        errorDiv.style.display = 'none';
        btn.disabled = true;
        return;
    }

    if (fecha > hoy) {
        errMsg.textContent     = '⚠ No se permiten fechas futuras';
        errorDiv.style.display = 'block';
        btn.disabled           = true;
        return;
    }

    if (fechasUsadas.includes(fecha)) {
        errMsg.textContent     = '⚠ Ya existe una liquidación para esta fecha';
        errorDiv.style.display = 'block';
        btn.disabled           = true;
        return;
    }

    // Verificar orden con el servidor
    const res = await apiPost('/api/liquidacion.php', { action: 'preview_dia', fecha });
    if (!res.ok) {
        errMsg.textContent     = res.msg || 'Error al validar fecha';
        errorDiv.style.display = 'block';
        btn.disabled           = true;
        return;
    }

    errorDiv.style.display = 'none';
    btn.disabled           = false;

    // Mostrar preview
    const fmt = n => '$' + Math.round(parseFloat(n)||0).toLocaleString('es-CO');
    document.getElementById('prev-pagos').textContent     = fmt(res.total_pagos);
    document.getElementById('prev-prestamos').textContent = fmt(res.total_prestamos);
    document.getElementById('prev-gastos').textContent    = fmt(res.total_gastos);
    document.getElementById('prev-papeleria').textContent = fmt(res.total_papeleria);
    document.getElementById('preview-dia').style.display  = 'block';
}

async function previewDia() {
    const fecha = document.getElementById('nueva-liq-fecha').value;
    if (!fecha || fechasUsadas.includes(fecha)) return;

    const res = await apiPost('/api/liquidacion.php', {
        action: 'preview_dia',
        fecha
    });

    if (!res.ok) return;

    const fmt = n => '$' + Math.round(parseFloat(n)||0).toLocaleString('es-CO');

    document.getElementById('prev-pagos').textContent     = fmt(res.total_pagos);
    document.getElementById('prev-prestamos').textContent = fmt(res.total_prestamos);
    document.getElementById('prev-gastos').textContent    = fmt(res.total_gastos);
    document.getElementById('prev-papeleria').textContent = fmt(res.total_papeleria);
    document.getElementById('preview-dia').style.display  = 'block';
}

// Y en crearLiquidacion() mejorar el mensaje de error:
async function crearLiquidacion() {
    const fecha         = document.getElementById('nueva-liq-fecha').value;
    const baseTrabajado = parseFloat(document.getElementById('nueva-liq-base').value) || 0;

    if (!fecha) { alert('Selecciona una fecha'); return; }

    const btn      = document.getElementById('btn-crear-liquidacion');
    btn.textContent = '⏳ Creando...';
    btn.disabled    = true;

    const res = await apiPost('/api/liquidacion.php', {
        action        : 'iniciar',
        fecha,
        base_trabajado: baseTrabajado
    });

    if (res.ok) {
        toast(res.msg);
        closeModal('modal-nueva-liquidacion');
        setTimeout(() => window.location.href = '/pages/liquidacion_detalle.php?id=' + res.id, 600);
    } else {
        // Mostrar mensaje de días pendientes
        if (res.dias_pendientes && res.dias_pendientes.length > 0) {
            alert(
                '⚠ Días sin liquidar:\n\n• ' +
                res.dias_pendientes.join('\n• ') +
                '\n\nDebes liquidarlos en orden. Próximo: ' + res.siguiente
            );
            // Setear la fecha correcta automáticamente
            document.getElementById('nueva-liq-fecha').value = res.siguiente;
            validarFecha();
        } else {
            toast(res.msg || 'Error', 'error');
        }
        btn.textContent = 'CREAR LIQUIDACIÓN';
        btn.disabled    = false;
    }
}
</script>