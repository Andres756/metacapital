<?php
$pageTitle = 'Ruta del día';
$pageNav   = 'dashboard';
$usaMapa   = true;
require_once __DIR__ . '/header.php';

$db = getDB();

// ── Lista: solo deudores con cuota vencida HOY o en mora ──────
$stmt = $db->prepare("
    SELECT d.*,
        MAX(p.id)              AS prestamo_id,
        MAX(p.estado)          AS prestamo_estado,
        MAX(p.saldo_pendiente) AS saldo_pendiente,
        MAX(p.valor_cuota)     AS valor_cuota,
        MAX(p.frecuencia_pago) AS frecuencia_pago,
        MAX(p.dias_mora)       AS dias_mora,
        COUNT(DISTINCT c.id)   AS cuotas_vencidas
    FROM deudores d
    JOIN deudor_cobro dc ON dc.deudor_id = d.id
    JOIN prestamos p     ON p.deudor_id = d.id
        AND p.cobro_id = ?
        AND p.estado IN ('activo','en_mora','en_acuerdo')
    JOIN cuotas c        ON c.prestamo_id = p.id
        AND c.estado IN ('pendiente','parcial')
        AND c.fecha_vencimiento <= CURDATE()
    WHERE dc.cobro_id = ? AND d.activo = 1
    GROUP BY d.id
    ORDER BY MAX(p.dias_mora) DESC, d.nombre ASC
");
$stmt->execute([$cobro, $cobro]);
$deudores = $stmt->fetchAll();

// ── Mapa: TODOS los deudores del cobro (para contexto geográfico)
// los que toca cobrar hoy se marcan con toca_hoy=true
$stmtTodos = $db->prepare("
    SELECT d.id, d.nombre, d.lat, d.lng, d.barrio,
        MAX(p.estado)    AS prestamo_estado,
        MAX(p.dias_mora) AS dias_mora,
        MAX(p.valor_cuota) AS valor_cuota,
        MAX(CASE WHEN c.id IS NOT NULL THEN 1 ELSE 0 END) AS toca_hoy
    FROM deudores d
    JOIN deudor_cobro dc ON dc.deudor_id = d.id
    LEFT JOIN prestamos p ON p.deudor_id = d.id
        AND p.cobro_id = ?
        AND p.estado IN ('activo','en_mora','en_acuerdo')
    LEFT JOIN cuotas c   ON c.prestamo_id = p.id
        AND c.estado IN ('pendiente','parcial')
        AND c.fecha_vencimiento <= CURDATE()
    WHERE dc.cobro_id = ? AND d.activo = 1 AND d.lat IS NOT NULL
    GROUP BY d.id
");
$stmtTodos->execute([$cobro, $cobro]);
$deudoresMapa = $stmtTodos->fetchAll();

// Stats del día
$stmtHoy = $db->prepare("
    SELECT COALESCE(SUM(monto_pagado), 0) FROM pagos
    WHERE cobro_id=? AND fecha_pago=CURDATE()
      AND usuario_id=? AND (anulado=0 OR anulado IS NULL)
");
$stmtHoy->execute([$cobro, $_SESSION['usuario_id']]);
$cobradoHoy = (float)$stmtHoy->fetchColumn();

$stmtN = $db->prepare("
    SELECT COUNT(*) FROM pagos
    WHERE cobro_id=? AND fecha_pago=CURDATE()
      AND usuario_id=? AND (anulado=0 OR anulado IS NULL)
");
$stmtN->execute([$cobro, $_SESSION['usuario_id']]);
$numCobros = (int)$stmtN->fetchColumn();

$cobro_id_js    = (int)$cobro;
$uid_js         = (int)$_SESSION['usuario_id'];
$deudoresJSON   = json_encode(array_map(fn($d) => [
    'id'       => (int)$d['id'],
    'nombre'   => $d['nombre'],
    'lat'      => $d['lat']  ? (float)$d['lat']  : null,
    'lng'      => $d['lng']  ? (float)$d['lng']  : null,
    'estado'   => $d['prestamo_estado'],
    'mora'     => (int)($d['dias_mora'] ?? 0),
    'cuota'    => (int)($d['valor_cuota'] ?? 0),
    'barrio'   => $d['barrio'] ?? '',
    'toca_hoy' => (bool)($d['toca_hoy'] ?? false),
], $deudoresMapa), JSON_UNESCAPED_UNICODE);
?>

<!-- Header -->
<div class="cob-header">
    <div>
        <div class="cob-title">MI RUTA</div>
        <div style="font-size:0.72rem;color:var(--muted);font-family:var(--font-mono)">
            <?= date('d M Y') ?> · <?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
        </div>
    </div>
    <div style="text-align:right">
        <div style="font-size:1.4rem;font-weight:700;color:var(--accent)">
            $<?= number_format($cobradoHoy, 0, ',', '.') ?>
        </div>
        <div style="font-size:0.65rem;color:var(--muted);font-family:var(--font-mono)">
            <?= $numCobros ?> cobro<?= $numCobros !== 1 ? 's' : '' ?> hoy
        </div>
    </div>
</div>

<!-- Controles -->
<div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem">

    <!-- Toggle Lista / Mapa -->
    <div style="display:flex;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;flex-shrink:0">
        <button id="btn-tl" onclick="setVista('lista')"
                style="padding:0.5rem 0.8rem;border:none;cursor:pointer;font-size:0.75rem;font-family:var(--font-mono);font-weight:700;background:var(--accent);color:#fff">
            ☰ Lista
        </button>
        <button id="btn-tm" onclick="setVista('mapa')"
                style="padding:0.5rem 0.8rem;border:none;cursor:pointer;font-size:0.75rem;font-family:var(--font-mono);font-weight:700;background:transparent;color:var(--muted)">
            🗺 Mapa
        </button>
    </div>

    <!-- GPS -->
    <button id="btn-gps" onclick="ordenarPorGPS()"
            style="flex:1;padding:0.5rem 0.6rem;background:rgba(124,106,255,.15);border:1px solid rgba(124,106,255,.3);border-radius:var(--radius);color:var(--accent);font-size:0.75rem;font-family:var(--font-mono);font-weight:600;cursor:pointer">
        📍 Ordenar
    </button>

    <!-- Trazar (solo en mapa) -->
    <button id="btn-trazar" onclick="trazarRuta()"
            style="display:none;padding:0.5rem 0.6rem;background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.3);border-radius:var(--radius);color:#22c55e;font-size:0.75rem;font-family:var(--font-mono);font-weight:600;cursor:pointer">
        🧭 Ir
    </button>

    <!-- Reset -->
    <button onclick="resetOrden()"
            style="padding:0.5rem 0.65rem;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);color:var(--muted);font-size:0.9rem;cursor:pointer;flex-shrink:0"
            title="Restablecer orden original">↺</button>
</div>

<!-- Hint drag -->
<div id="hint-drag" style="font-family:var(--font-mono);font-size:0.6rem;color:var(--muted);text-align:center;margin-bottom:0.35rem;display:none">
    ⠿ Arrastra para reordenar · se guarda automáticamente
</div>

<!-- ══ VISTA LISTA ══ -->
<div id="vista-lista">
<div id="lista-deudores">
<?php foreach ($deudores as $d):
    $ini     = strtoupper(substr($d['nombre'], 0, 1));
    $estado  = $d['prestamo_estado'] ?? null;
    $vencidas= (int)($d['cuotas_vencidas'] ?? 0);
    $mora    = (int)($d['dias_mora'] ?? 0);
    $avatarBg= $mora > 0 ? '#f97316' : ($estado ? 'var(--accent)' : 'var(--muted)');
?>
<div class="cob-deudor-row"
     data-id="<?= $d['id'] ?>"
     data-lat="<?= htmlspecialchars($d['lat'] ?? '') ?>"
     data-lng="<?= htmlspecialchars($d['lng'] ?? '') ?>"
     style="display:flex;align-items:center;user-select:none">

    <div class="drag-handle"
         style="flex-shrink:0;padding:0.7rem 0.35rem 0.7rem 0;color:var(--muted);font-size:1rem;cursor:grab;touch-action:none;opacity:0.4"
         title="Arrastra para reordenar">⠿</div>

    <a href="/cobrador/cobrar.php?deudor=<?= $d['id'] ?>"
       style="display:flex;align-items:center;gap:0.6rem;flex:1;text-decoration:none;color:inherit;min-width:0">

        <div class="cob-avatar" style="background:<?= $avatarBg ?>;flex-shrink:0"><?= $ini ?></div>

        <div style="flex:1;min-width:0">
            <div style="font-weight:600;font-size:0.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                <?= htmlspecialchars($d['nombre']) ?>
            </div>
            <div style="font-size:0.67rem;color:var(--muted);font-family:var(--font-mono)">
                <?= htmlspecialchars($d['barrio'] ?? '') ?><span class="dist-label"></span>
            </div>
        </div>

        <div style="text-align:right;flex-shrink:0;margin-right:0.35rem">
            <?php if ($estado === 'en_mora'): ?>
                <span class="cob-badge cob-badge-mora"><?= $mora ?>d mora</span>
            <?php elseif ($estado === 'en_acuerdo'): ?>
                <span class="cob-badge cob-badge-acuerdo">Acuerdo</span>
            <?php elseif ($estado === 'activo' && $vencidas > 0): ?>
                <span class="cob-badge cob-badge-mora"><?= $vencidas ?> venc.</span>
            <?php elseif ($estado === 'activo'): ?>
                <span class="cob-badge cob-badge-ok">Al día</span>
            <?php else: ?>
                <span class="cob-badge" style="background:rgba(255,255,255,.06);color:var(--muted)">Sin préstamo</span>
            <?php endif; ?>
            <?php if ($d['valor_cuota']): ?>
            <div style="font-size:0.8rem;font-weight:700;color:var(--accent);margin-top:2px">
                $<?= number_format($d['valor_cuota'], 0, ',', '.') ?>
            </div>
            <?php endif; ?>
        </div>
    </a>

    <a href="/cobrador/prestamo.php?deudor=<?= $d['id'] ?>"
       style="flex-shrink:0;padding:0.3rem 0.5rem;background:rgba(124,106,255,.12);border:1px solid rgba(124,106,255,.25);border-radius:var(--radius);color:var(--accent);font-size:0.7rem;font-family:var(--font-mono);text-decoration:none"
       title="Nuevo préstamo">+</a>
</div>
<?php endforeach; ?>
<?php if (empty($deudores)): ?>
<div style="text-align:center;padding:3rem 1rem;color:var(--muted)">
    <div style="font-size:2rem;margin-bottom:0.5rem">◎</div>
    <div style="font-family:var(--font-mono);font-size:0.8rem">Sin deudores en este cobro</div>
</div>
<?php endif; ?>
</div>
</div>

<!-- ══ VISTA MAPA ══ -->
<div id="vista-mapa" style="display:none">
    <div id="mapa-ruta"
         style="width:100%;height:calc(100vh - 210px);min-height:360px;border-radius:var(--radius);border:1px solid var(--border);overflow:hidden">
    </div>
    <!-- Bottom sheet al tocar un pin -->
    <div id="pin-sheet" style="display:none;position:fixed;bottom:65px;left:0;right:0;
         background:var(--card);border-top:2px solid var(--accent);padding:1rem;
         z-index:150;box-shadow:0 -4px 20px rgba(0,0,0,.4)">
        <div style="display:flex;align-items:center;gap:0.75rem">
            <div id="pin-av" class="cob-avatar" style="flex-shrink:0"></div>
            <div style="flex:1;min-width:0">
                <div id="pin-nombre" style="font-weight:700;font-size:1rem"></div>
                <div id="pin-info"   style="font-size:0.72rem;color:var(--muted);font-family:var(--font-mono)"></div>
            </div>
            <a id="pin-link" href="#"
               style="padding:0.55rem 0.9rem;background:var(--accent);color:#fff;border-radius:var(--radius);font-family:var(--font-mono);font-size:0.75rem;font-weight:700;text-decoration:none;flex-shrink:0">
                Cobrar →
            </a>
            <button onclick="document.getElementById('pin-sheet').style.display='none'"
                    style="background:none;border:none;color:var(--muted);font-size:1.2rem;cursor:pointer">✕</button>
        </div>
    </div>
</div>

<?php
$extraScript = <<<JS
<script>
const DEUDORES    = {$deudoresJSON};
const STORAGE_KEY = 'ruta_{$cobro_id_js}_u{$uid_js}';
let vistaActual   = 'lista';
let mapaListo     = false;
let mapaObj       = null;
let markers       = [];
let pinCobrador   = null;
let miLat         = null;
let miLng         = null;

// ─── Toggle vista ─────────────────────────────────────────────
function setVista(v) {
    vistaActual = v;
    const esLista = v === 'lista';
    document.getElementById('vista-lista').style.display = esLista ? 'block' : 'none';
    document.getElementById('vista-mapa').style.display  = esLista ? 'none'  : 'block';
    document.getElementById('btn-trazar').style.display  = esLista ? 'none'  : '';
    document.getElementById('btn-tl').style.background   = esLista ? 'var(--accent)' : 'transparent';
    document.getElementById('btn-tl').style.color        = esLista ? '#fff' : 'var(--muted)';
    document.getElementById('btn-tm').style.background   = esLista ? 'transparent' : 'var(--accent)';
    document.getElementById('btn-tm').style.color        = esLista ? 'var(--muted)' : '#fff';
    if (!esLista && !mapaListo) iniciarMapa();
    // Si ya estaba listo y vuelvo al mapa, sincronizar con orden actual de la lista
    if (!esLista && mapaListo) renumerarPorLista();
}

// ─── Iniciar mapa ─────────────────────────────────────────────
function iniciarMapa() {
    if (typeof google === 'undefined') return;

    mapaObj = new google.maps.Map(document.getElementById('mapa-ruta'), {
        zoom: 13, center: { lat: 5.5353, lng: -73.3678 },
        mapTypeControl: false, streetViewControl: false, fullscreenControl: false,
        styles: [
            {featureType:'all',elementType:'geometry',stylers:[{color:'#1a1a2e'}]},
            {featureType:'water',elementType:'geometry',stylers:[{color:'#0f0f1a'}]},
            {featureType:'road',elementType:'geometry',stylers:[{color:'#2a2a4a'}]},
            {featureType:'road',elementType:'labels.text.fill',stylers:[{color:'#7777aa'}]},
            {featureType:'poi',stylers:[{visibility:'off'}]},
            {featureType:'administrative',elementType:'labels.text.fill',stylers:[{color:'#555588'}]},
        ]
    });

    // Pintar pines inmediatamente sin GPS
    pintarPines();

    // Pin verde arrastrable — aparece en el centro del mapa
    // El cobrador lo mueve a donde está parado
    const centroColombia = { lat: 5.5353, lng: -73.3678 };
    pinCobrador = new google.maps.Marker({
        position: centroColombia,
        map: mapaObj,
        draggable: true,
        icon: {
            path: google.maps.SymbolPath.CIRCLE,
            scale: 13,
            fillColor: '#22c55e',
            fillOpacity: 1,
            strokeColor: '#fff',
            strokeWeight: 3,
        },
        title: '📍 Arrastra este pin a tu ubicación actual',
        zIndex: 999,
        animation: google.maps.Animation.BOUNCE,
    });

    // Hint al usuario
    mostrarHintPin();

    // Al mover el pin verde → recalcular orden
    pinCobrador.addListener('dragstart', () => {
        pinCobrador.setAnimation(null);
        ocultarHintPin();
    });
    pinCobrador.addListener('dragend', () => {
        const pos = pinCobrador.getPosition();
        miLat = pos.lat();
        miLng = pos.lng();
        renumerarPorDistancia();
        // Guardar posición del cobrador en localStorage
        localStorage.setItem(STORAGE_KEY + '_pos', JSON.stringify({ lat: miLat, lng: miLng }));
    });

    // Intentar GPS del dispositivo para posicionar el pin verde automáticamente
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            pos => {
                if (pos.coords.accuracy > 5000) return; // rechazar si es por IP
                miLat = pos.coords.latitude;
                miLng = pos.coords.longitude;
                pinCobrador.setPosition({ lat: miLat, lng: miLng });
                pinCobrador.setAnimation(null);
                mapaObj.setCenter({ lat: miLat, lng: miLng });
                mapaObj.setZoom(14);
                renumerarPorDistancia();
                ocultarHintPin();
            },
            () => {
                // GPS falló — restaurar posición guardada si existe
                const saved = localStorage.getItem(STORAGE_KEY + '_pos');
                if (saved) {
                    try {
                        const p = JSON.parse(saved);
                        miLat = p.lat; miLng = p.lng;
                        pinCobrador.setPosition({ lat: miLat, lng: miLng });
                        pinCobrador.setAnimation(null);
                        mapaObj.setCenter({ lat: miLat, lng: miLng });
                        mapaObj.setZoom(14);
                        renumerarPorDistancia();
                        ocultarHintPin();
                    } catch(e) {}
                }
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    }

    mapaListo = true;
}

function mostrarHintPin() {
    let hint = document.getElementById('hint-pin-mapa');
    if (!hint) {
        hint = document.createElement('div');
        hint.id = 'hint-pin-mapa';
        hint.style.cssText = 'position:absolute;top:10px;left:50%;transform:translateX(-50%);background:rgba(34,197,94,.9);color:#fff;padding:0.4rem 0.75rem;border-radius:20px;font-family:var(--font-mono);font-size:0.7rem;font-weight:600;z-index:200;pointer-events:none;white-space:nowrap';
        hint.textContent = '📍 Arrastra el pin verde a tu ubicación';
        document.getElementById('vista-mapa').style.position = 'relative';
        document.getElementById('vista-mapa').appendChild(hint);
    }
    hint.style.display = 'block';
}

function ocultarHintPin() {
    const hint = document.getElementById('hint-pin-mapa');
    if (hint) hint.style.display = 'none';
}

// ─── Pintar todos los pines de clientes ───────────────────────
function pintarPines() {
    markers.forEach(m => m.setMap(null));
    markers = [];

    const bounds = new google.maps.LatLngBounds();
    let hayPins  = false;

    // Orden actual de la lista (respeta drag&drop del cobrador)
    const ordenLista = Array.from(
        document.querySelectorAll('#lista-deudores .cob-deudor-row')
    ).map(f => parseInt(f.dataset.id));

    // Deudores de hoy en el orden de la lista
    const tocaHoy = ordenLista
        .map(id => DEUDORES.find(d => d.id === id && d.toca_hoy && d.lat && d.lng))
        .filter(Boolean);

    // Resto con GPS
    const resto = DEUDORES.filter(d => !d.toca_hoy && d.lat && d.lng);

    // Pines de hoy — numerados según orden actual
    tocaHoy.forEach((d, idx) => {
        hayPins = true;
        const pos   = { lat: d.lat, lng: d.lng };
        const color = d.mora > 0 ? '#f97316' : '#7c6aff';
        bounds.extend(pos);
        const m = new google.maps.Marker({
            position: pos, map: mapaObj,
            icon: { path: google.maps.SymbolPath.CIRCLE, scale: 15,
                    fillColor: color, fillOpacity: 1, strokeColor: '#fff', strokeWeight: 2 },
            label: { text: String(idx + 1), color: '#fff', fontSize: '10px', fontWeight: 'bold' },
            title: d.nombre, zIndex: 10,
        });
        m.addListener('click', () => abrirSheet(d, color));
        markers.push(m);
    });

    // Pines grises del resto
    resto.forEach(d => {
        hayPins = true;
        bounds.extend({ lat: d.lat, lng: d.lng });
        const m = new google.maps.Marker({
            position: { lat: d.lat, lng: d.lng }, map: mapaObj,
            icon: { path: google.maps.SymbolPath.CIRCLE, scale: 8,
                    fillColor: '#4b4b6b', fillOpacity: 0.45, strokeColor: '#fff', strokeWeight: 1 },
            title: d.nombre, zIndex: 1,
        });
        m.addListener('click', () => abrirSheet(d, '#4b4b6b'));
        markers.push(m);
    });

    if (hayPins) mapaObj.fitBounds(bounds);
}

// ─── Renumerar según orden actual de la LISTA (drag&drop) ─────
function renumerarPorLista() {
    pintarPines();
}

// ─── Renumerar por distancia desde el pin verde ───────────────
function renumerarPorDistancia() {
    if (!miLat || !miLng) return;

    // Reordenar también la lista
    const lista  = document.getElementById('lista-deudores');
    const filas  = Array.from(lista.querySelectorAll('.cob-deudor-row'));

    const conUbic = filas.filter(f => {
        const d = DEUDORES.find(x => x.id === parseInt(f.dataset.id) && x.toca_hoy);
        return d && d.lat && d.lng;
    });
    const sinUbic = filas.filter(f => !conUbic.includes(f));

    conUbic.sort((a, b) => {
        const da = DEUDORES.find(x => x.id === parseInt(a.dataset.id));
        const db = DEUDORES.find(x => x.id === parseInt(b.dataset.id));
        return haversine(miLat, miLng, da.lat, da.lng) - haversine(miLat, miLng, db.lat, db.lng);
    });

    // Actualizar distancias en la lista
    conUbic.forEach(f => {
        const d    = DEUDORES.find(x => x.id === parseInt(f.dataset.id));
        const dist = haversine(miLat, miLng, d.lat, d.lng);
        const lbl  = f.querySelector('.dist-label');
        if (lbl) lbl.textContent = ' · ' + (dist < 1 ? Math.round(dist*1000)+'m' : dist.toFixed(1)+'km');
        lista.appendChild(f);
    });
    sinUbic.forEach(f => lista.appendChild(f));

    guardarOrden();
    pintarPines(); // redibuja con nuevo orden
}

function abrirSheet(d, color) {
    const fmt = n => n ? '\$'+n.toLocaleString('es-CO') : '';
    document.getElementById('pin-av').textContent      = d.nombre[0].toUpperCase();
    document.getElementById('pin-av').style.background = color;
    document.getElementById('pin-nombre').textContent  = d.nombre;
    document.getElementById('pin-info').textContent    =
        (d.barrio||'') + (d.cuota ? ' · Cuota '+fmt(d.cuota) : '') +
        (d.mora > 0 ? ' · '+d.mora+'d mora' : '');
    document.getElementById('pin-link').href = '/cobrador/cobrar.php?deudor='+d.id;
    document.getElementById('pin-sheet').style.display = 'block';
}

// ─── Trazar ruta en Google Maps nativo ────────────────────────
function trazarRuta() {
    const ordenIds = Array.from(
        document.querySelectorAll('#lista-deudores .cob-deudor-row')
    ).map(f => parseInt(f.dataset.id));

    const pts = ordenIds
        .map(id => DEUDORES.find(d => d.id === id))
        .filter(d => d && d.lat && d.lng && d.toca_hoy);

    if (!pts.length) { alert('Ningún cliente tiene ubicación registrada.'); return; }

    const destino   = pts[pts.length - 1];
    const waypoints = pts.slice(0, -1).slice(0, 9);
    let url = 'https://www.google.com/maps/dir/?api=1';
    if (miLat && miLng) url += '&origin=' + miLat + ',' + miLng;
    url += '&destination=' + destino.lat + ',' + destino.lng;
    if (waypoints.length) url += '&waypoints=' + waypoints.map(d => d.lat+','+d.lng).join('|');
    url += '&travelmode=driving';
    window.open(url, '_blank');
}

// ─── Haversine ────────────────────────────────────────────────
function haversine(la1,lo1,la2,lo2) {
    const R=6371, dLa=(la2-la1)*Math.PI/180, dLo=(lo2-lo1)*Math.PI/180;
    const a=Math.sin(dLa/2)**2+Math.cos(la1*Math.PI/180)*Math.cos(la2*Math.PI/180)*Math.sin(dLo/2)**2;
    return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
}

// ─── Persistencia orden lista ─────────────────────────────────
function guardarOrden() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(
        Array.from(document.querySelectorAll('#lista-deudores .cob-deudor-row')).map(f=>f.dataset.id)
    ));
}

function aplicarOrdenGuardado() {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (!saved) return false;
    let ids; try { ids=JSON.parse(saved); } catch(e) { return false; }
    const lista=document.getElementById('lista-deudores');
    const filas=Array.from(lista.querySelectorAll('.cob-deudor-row'));
    const mapa={}; filas.forEach(f=>mapa[f.dataset.id]=f);
    const validos=ids.filter(id=>mapa[id]);
    if (!validos.length) return false;
    validos.forEach(id=>lista.appendChild(mapa[id]));
    filas.filter(f=>!validos.includes(f.dataset.id)).forEach(f=>lista.appendChild(f));
    return true;
}

// ─── GPS botón Ordenar (lista) ────────────────────────────────
function ordenarPorGPS() {
    const btn=document.getElementById('btn-gps');
    if (!navigator.geolocation) { alert('Sin soporte GPS'); return; }
    btn.innerHTML='⏳'; btn.disabled=true;

    navigator.geolocation.getCurrentPosition(pos => {
        if (pos.coords.accuracy > 5000) {
            btn.innerHTML='📍 Ordenar'; btn.disabled=false;
            alert('GPS impreciso. En el mapa arrastra el pin verde a tu ubicación.');
            return;
        }
        miLat = pos.coords.latitude;
        miLng = pos.coords.longitude;

        // Reordenar lista
        const lista=document.getElementById('lista-deudores');
        const filas=Array.from(lista.querySelectorAll('.cob-deudor-row'));
        filas.forEach(f => {
            const lat=parseFloat(f.dataset.lat), lng=parseFloat(f.dataset.lng);
            const lbl=f.querySelector('.dist-label');
            if (lat&&lng) {
                const d=haversine(miLat,miLng,lat,lng);
                f.dataset.dist=d;
                if(lbl) lbl.textContent=' · '+(d<1?Math.round(d*1000)+'m':d.toFixed(1)+'km');
            } else { f.dataset.dist=99999; if(lbl) lbl.textContent=''; }
        });
        filas.sort((a,b)=>parseFloat(a.dataset.dist)-parseFloat(b.dataset.dist));
        filas.forEach(f=>lista.appendChild(f));
        guardarOrden();

        btn.innerHTML='✓ Listo'; btn.style.color='#22c55e'; btn.disabled=false;
        document.getElementById('hint-drag').style.display='block';
        setTimeout(()=>{ btn.innerHTML='📍 Ordenar'; btn.style.color=''; }, 2500);

        // Sincronizar mapa si está abierto
        if (vistaActual==='mapa' && mapaListo) {
            pinCobrador.setPosition({ lat: miLat, lng: miLng });
            pintarPines();
        }
    }, () => {
        btn.innerHTML='📍 Ordenar'; btn.disabled=false;
        alert('No se pudo obtener ubicación. Usa el pin verde en el mapa.');
    }, { enableHighAccuracy:true, timeout:15000, maximumAge:0 });
}

function resetOrden() { localStorage.removeItem(STORAGE_KEY); location.reload(); }

// ─── Drag & Drop lista ────────────────────────────────────────
let dragging=null, ph=null;

const crearPH = h => {
    const el=document.createElement('div');
    el.style.cssText=`height:\${h}px;background:rgba(124,106,255,.1);border:2px dashed rgba(124,106,255,.35);border-radius:var(--radius);margin:2px 0;box-sizing:border-box`;
    return el;
};

function getAntes(y) {
    const filas=Array.from(document.querySelectorAll('#lista-deudores .cob-deudor-row')).filter(f=>f!==dragging);
    for (const f of filas) if (y < f.getBoundingClientRect().top+f.offsetHeight/2) return f;
    return null;
}

function insertar(y) {
    const lista=document.getElementById('lista-deudores');
    const antes=getAntes(y);
    antes ? lista.insertBefore(ph,antes) : lista.appendChild(ph);
}

function iniciarDrag(fila) {
    dragging=fila;
    dragging.style.opacity='0.35';
    dragging.style.boxShadow='0 6px 24px rgba(0,0,0,.5)';
    ph=crearPH(dragging.offsetHeight);
    dragging.after(ph);
}

function finDrag() {
    if (!dragging) return;
    dragging.style.opacity=''; dragging.style.boxShadow='';
    ph.replaceWith(dragging);
    dragging=null; ph=null;
    guardarOrden();
    document.getElementById('hint-drag').style.display='block';
    // Sincronizar mapa si está abierto
    if (vistaActual==='mapa' && mapaListo) pintarPines();
}

function initDrag() {
    const lista=document.getElementById('lista-deudores');
    lista.addEventListener('mousedown', e=>{
        if (!e.target.closest('.drag-handle')) return;
        e.preventDefault(); iniciarDrag(e.target.closest('.cob-deudor-row'));
    });
    document.addEventListener('mousemove', e=>{ if(dragging) insertar(e.clientY); });
    document.addEventListener('mouseup', finDrag);
    lista.addEventListener('touchstart', e=>{
        if (!e.target.closest('.drag-handle')) return;
        iniciarDrag(e.target.closest('.cob-deudor-row'));
    },{passive:true});
    lista.addEventListener('touchmove', e=>{
        if(!dragging) return; e.preventDefault(); insertar(e.touches[0].clientY);
    },{passive:false});
    lista.addEventListener('touchend', finDrag);
}

document.addEventListener('DOMContentLoaded', ()=>{
    if (aplicarOrdenGuardado()) document.getElementById('hint-drag').style.display='block';
    initDrag();
});
</script>
JS;

require_once __DIR__ . '/footer.php';
?>