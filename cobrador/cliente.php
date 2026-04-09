<?php
$pageTitle = 'Nuevo cliente';
$pageNav   = 'cliente';
$usaMapa   = true;
require_once __DIR__ . '/header.php';

$db = getDB();
?>

<div class="cob-header">
    <div class="cob-title">NUEVO CLIENTE</div>
</div>

<!-- Paso 1: Buscar por documento -->
<div id="paso-buscar">
    <div style="font-family:var(--font-mono);font-size:0.72rem;color:var(--muted);margin-bottom:0.5rem;text-transform:uppercase;letter-spacing:1px">
        Paso 1 — Ingresa el número de documento
    </div>
    <div style="display:flex;gap:0.5rem">
        <input type="text" id="input-documento"
               placeholder="Número de cédula"
               style="flex:1;padding:0.85rem;font-size:1.1rem;border-radius:var(--radius);border:1px solid var(--border);background:var(--card);color:var(--text)"
               onkeydown="if(event.key==='Enter') buscarDocumento()">
        <button onclick="buscarDocumento()"
                style="padding:0.85rem 1rem;background:var(--accent);color:#fff;border:none;border-radius:var(--radius);font-size:1.1rem;cursor:pointer;white-space:nowrap">
            🔍
        </button>
    </div>
    <div id="resultado-busqueda" style="margin-top:0.75rem"></div>
</div>

<!-- Paso 2: Formulario (oculto hasta buscar) -->
<div id="paso-formulario" style="display:none;margin-top:1.25rem">

    <div style="font-family:var(--font-mono);font-size:0.72rem;color:var(--muted);margin-bottom:0.75rem;text-transform:uppercase;letter-spacing:1px">
        Paso 2 — Datos del cliente
    </div>

    <form id="form-cliente">
        <input type="hidden" id="c_id" value="">

        <div class="field-lg">
            <label>Documento *</label>
            <input type="text" id="c_documento" placeholder="Número de cédula" required>
        </div>
        <div class="field-lg">
            <label>Nombre completo *</label>
            <input type="text" id="c_nombre" placeholder="Nombre del cliente" required>
        </div>
        <div class="field-lg">
            <label>Teléfono *</label>
            <input type="tel" id="c_telefono" placeholder="300 000 0000" required>
        </div>
        <div class="field-lg">
            <label>Barrio / Sector *</label>
            <input type="text" id="c_barrio" placeholder="Barrio o sector" required>
        </div>
        <div class="field-lg">
            <label>Dirección *</label>
            <input type="text" id="c_direccion"
                   placeholder="Escribe la dirección para buscar..."
                   autocomplete="off" required>
            <input type="hidden" id="c_lat">
            <input type="hidden" id="c_lng">
            <input type="hidden" id="c_place_id">
        </div>

        <div id="mapa-cliente-wrap" style="display:none;margin-bottom:1rem">
            <div id="mapa-cliente"
                 style="width:100%;height:220px;border-radius:var(--radius);border:1px solid var(--border);overflow:hidden;min-height:220px">
            </div>
            <div style="font-size:0.72rem;color:var(--muted);margin-top:0.4rem;font-family:var(--font-mono)">
                📍 Arrastra el pin si la ubicación no es exacta
            </div>
        </div>

        <div class="field-lg">
            <label>Notas</label>
            <textarea id="c_notas" placeholder="Observaciones..." rows="2"
                      style="width:100%;padding:0.75rem;border-radius:var(--radius);border:1px solid var(--border);background:var(--bg);color:var(--text);font-size:1rem;resize:none"></textarea>
        </div>

        <button type="button" class="cob-btn cob-btn-primary" onclick="guardarCliente()">
            REGISTRAR CLIENTE
        </button>
        <button type="button" class="cob-btn cob-btn-ghost" onclick="resetBusqueda()">
            Cancelar
        </button>
    </form>
</div>

<?php
$extraScript = <<<'JS'
<script>
let mapaCli   = null;
let markerCli = null;
let autocompleteCli = null;

function initMapaCliente() {
    if (mapaCli) return;
    const centro = { lat: 5.5353, lng: -73.3678 };
    mapaCli = new google.maps.Map(document.getElementById('mapa-cliente'), {
        center: centro, zoom: 15,
        mapTypeControl: false, streetViewControl: false, fullscreenControl: false,
    });
    markerCli = new google.maps.Marker({ position: centro, map: mapaCli, draggable: true });
    markerCli.addListener('dragend', function () {
        const pos = markerCli.getPosition();
        document.getElementById('c_lat').value      = pos.lat().toFixed(7);
        document.getElementById('c_lng').value      = pos.lng().toFixed(7);
        document.getElementById('c_place_id').value = '';
    });
    autocompleteCli = new google.maps.places.Autocomplete(
        document.getElementById('c_direccion'),
        { componentRestrictions: { country: 'co' }, fields: ['geometry','formatted_address','place_id'] }
    );
    autocompleteCli.addListener('place_changed', function () {
        const place = autocompleteCli.getPlace();
        if (!place.geometry || !place.geometry.location) return;
        const lat = place.geometry.location.lat();
        const lng = place.geometry.location.lng();
        document.getElementById('c_lat').value      = lat.toFixed(7);
        document.getElementById('c_lng').value      = lng.toFixed(7);
        document.getElementById('c_place_id').value = place.place_id || '';
        const pos = new google.maps.LatLng(lat, lng);
        mapaCli.setCenter(pos); mapaCli.setZoom(17); markerCli.setPosition(pos);
        document.getElementById('mapa-cliente-wrap').style.display = 'block';
        setTimeout(() => google.maps.event.trigger(mapaCli, 'resize'), 100);
    });
}

window.addEventListener('load', () => {
    if (typeof google !== 'undefined') initMapaCliente();
});

async function buscarDocumento() {
    const doc = document.getElementById('input-documento').value.trim();
    if (!doc) { alert('Ingresa el número de documento'); return; }

    const resultado = document.getElementById('resultado-busqueda');
    resultado.innerHTML = '<div style="color:var(--muted);font-family:var(--font-mono);font-size:0.8rem">Consultando...</div>';

    try {
        const res  = await fetch('/api/consultar_deudor.php?documento=' + encodeURIComponent(doc));
        const data = await res.json();

        if (!data.ok) {
            resultado.innerHTML = '<div style="color:#ef4444;font-size:0.85rem">' + (data.msg || 'Error') + '</div>';
            return;
        }

        if (!data.existe) {
            // No existe → habilitar formulario para crearlo
            resultado.innerHTML = `
                <div style="padding:0.75rem;background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);border-radius:var(--radius);font-size:0.85rem;color:#22c55e;font-family:var(--font-mono)">
                    ✓ Documento no registrado — completa los datos para registrarlo
                </div>`;
            document.getElementById('c_documento').value = doc;
            document.getElementById('c_id').value = '';
            document.getElementById('paso-formulario').style.display = 'block';
            setTimeout(() => {
                if (typeof google !== 'undefined') initMapaCliente();
                document.getElementById('c_nombre').focus();
            }, 300);
            return;
        }

        const d = data.deudor;

        // Es CLAVO
        if (d.comportamiento === 'clavo') {
            resultado.innerHTML = `
                <div style="padding:1rem;background:rgba(239,68,68,.1);border:2px solid #ef4444;border-radius:var(--radius)">
                    <div style="color:#ef4444;font-weight:700;font-size:1rem;margin-bottom:0.4rem">
                        🚨 CLIENTE CLAVO
                    </div>
                    <div style="font-weight:600;font-size:0.95rem">${d.nombre}</div>
                    <div style="font-size:0.75rem;color:var(--muted);font-family:var(--font-mono);margin-top:2px">
                        CC: ${d.documento}
                    </div>
                    <div style="font-size:0.82rem;color:#ef4444;margin-top:0.5rem">
                        Este cliente tiene mal comportamiento de pago. No se puede crear préstamo.
                    </div>
                    <div style="font-size:0.75rem;color:var(--muted);margin-top:0.4rem;font-family:var(--font-mono)">
                        ⚠ El administrador ha sido notificado de esta consulta.
                    </div>
                </div>`;
            document.getElementById('paso-formulario').style.display = 'none';
            return;
        }

        // Existe y no es clavo → cargar datos
        const badgeColor = d.comportamiento === 'bueno' ? '#22c55e' : '#f59e0b';
        const badgeText  = d.comportamiento === 'bueno' ? 'Buen pagador' : 'Comportamiento regular';

        resultado.innerHTML = `
            <div style="padding:0.75rem;background:rgba(124,106,255,.1);border:1px solid rgba(124,106,255,.3);border-radius:var(--radius)">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <div>
                        <div style="font-weight:700">${d.nombre}</div>
                        <div style="font-size:0.72rem;color:var(--muted);font-family:var(--font-mono)">${d.telefono || '—'}</div>
                    </div>
                    <span style="background:${badgeColor}22;color:${badgeColor};font-size:0.7rem;padding:3px 8px;border-radius:20px;font-family:var(--font-mono);font-weight:600">
                        ${badgeText}
                    </span>
                </div>
            </div>`;

        // Cargar datos en el formulario
        document.getElementById('c_id').value        = d.id;
        document.getElementById('c_documento').value = d.documento || '';
        document.getElementById('c_nombre').value    = d.nombre    || '';
        document.getElementById('c_telefono').value  = d.telefono  || '';
        document.getElementById('c_barrio').value    = d.barrio    || '';
        document.getElementById('c_direccion').value = d.direccion || '';
        document.getElementById('c_lat').value       = d.lat       || '';
        document.getElementById('c_lng').value       = d.lng       || '';
        document.getElementById('c_place_id').value  = d.place_id  || '';
        document.getElementById('c_notas').value     = d.notas     || '';

        document.getElementById('paso-formulario').style.display = 'block';

        // Si tiene coordenadas, mostrar mapa
        if (d.lat && d.lng) {
            document.getElementById('mapa-cliente-wrap').style.display = 'block';
            setTimeout(() => {
                initMapaCliente();
                const pos = new google.maps.LatLng(parseFloat(d.lat), parseFloat(d.lng));
                mapaCli.setCenter(pos); mapaCli.setZoom(17); markerCli.setPosition(pos);
                google.maps.event.trigger(mapaCli, 'resize');
            }, 300);
        }

    } catch(e) {
        resultado.innerHTML = '<div style="color:#ef4444;font-size:0.85rem">Error de conexión</div>';
    }
}

function resetBusqueda() {
    document.getElementById('input-documento').value = '';
    document.getElementById('resultado-busqueda').innerHTML = '';
    document.getElementById('paso-formulario').style.display = 'none';
    document.getElementById('form-cliente').reset();
    document.getElementById('c_id').value = '';
    document.getElementById('mapa-cliente-wrap').style.display = 'none';
}

async function guardarCliente() {
    const nombre    = document.getElementById('c_nombre').value.trim();
    const telefono  = document.getElementById('c_telefono').value.trim();
    const documento = document.getElementById('c_documento').value.trim();
    const barrio    = document.getElementById('c_barrio').value.trim();
    const direccion = document.getElementById('c_direccion').value.trim();
    const lat       = document.getElementById('c_lat').value;
    const lng       = document.getElementById('c_lng').value;
    const id        = document.getElementById('c_id').value;

    const errores = [];
    if (!nombre)    errores.push('Nombre completo');
    if (!telefono)  errores.push('Teléfono');
    if (!documento) errores.push('Documento');
    if (!barrio)    errores.push('Barrio / Sector');
    if (!direccion) errores.push('Dirección');
    if (!lat || !lng) errores.push('Ubicación en el mapa');

    if (errores.length > 0) {
        alert('Faltan campos obligatorios:\n\n• ' + errores.join('\n• '));
        return;
    }

    const btn = document.querySelector('[onclick="guardarCliente()"]');
    btn.textContent = '⏳ Guardando...';
    btn.disabled    = true;

    try {
        const res = await fetch('/api/deudores.php', {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({
                action        : 'guardar',
                id            : id || undefined,
                nombre, telefono, documento, barrio, direccion,
                lat, lng,
                place_id      : document.getElementById('c_place_id').value,
                notas         : document.getElementById('c_notas').value.trim(),
                comportamiento: 'bueno',
            })
        });

        const data = await res.json();

        if (data.ok) {
            btn.textContent      = '✓ Guardado';
            btn.style.background = '#22c55e';
            const deudorId = data.id || id;
            setTimeout(() => {
                if (confirm('¿Registrar un préstamo ahora?')) {
                    window.location = '/cobrador/prestamo.php?deudor=' + deudorId;
                } else {
                    resetBusqueda();
                    btn.textContent      = 'REGISTRAR CLIENTE';
                    btn.style.background = '';
                    btn.disabled         = false;
                }
            }, 800);
        } else {
            alert(data.msg || 'Error al guardar');
            btn.textContent      = 'REGISTRAR CLIENTE';
            btn.style.background = '';
            btn.disabled         = false;
        }
    } catch(e) {
        alert('Error de conexión');
        btn.textContent      = 'REGISTRAR CLIENTE';
        btn.style.background = '';
        btn.disabled         = false;
    }
}
</script>
JS;

require_once __DIR__ . '/footer.php';
?>