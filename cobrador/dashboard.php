<?php
$pageTitle = 'Ruta del día';
$pageNav   = 'dashboard';
$usaMapa   = true;
require_once __DIR__ . '/header.php';

$db = getDB();

// Deudores del cobro con sus préstamos activos
$stmt = $db->prepare("
    SELECT d.*,
        p.id            AS prestamo_id,
        p.estado        AS prestamo_estado,
        p.saldo_pendiente,
        p.valor_cuota,
        p.frecuencia_pago,
        p.dias_mora,
        (SELECT COUNT(*) FROM cuotas c
         WHERE c.prestamo_id = p.id
           AND c.estado IN ('pendiente','parcial')
           AND c.fecha_vencimiento <= CURDATE()
        ) AS cuotas_vencidas
    FROM deudores d
    JOIN deudor_cobro dc ON dc.deudor_id = d.id
    LEFT JOIN prestamos p ON p.deudor_id = d.id
        AND p.cobro_id = ?
        AND p.estado IN ('activo','en_mora','en_acuerdo')
    WHERE dc.cobro_id = ? AND d.activo = 1
    ORDER BY d.nombre ASC
");
$stmt->execute([$cobro, $cobro]);
$deudores = $stmt->fetchAll();

// Stats del día
$stmtHoy = $db->prepare("
    SELECT COALESCE(SUM(monto_pagado), 0)
    FROM pagos
    WHERE cobro_id=? AND fecha_pago=CURDATE()
      AND usuario_id=? AND (anulado=0 OR anulado IS NULL)
");
$stmtHoy->execute([$cobro, $_SESSION['usuario_id']]);
$cobradoHoy = (float)$stmtHoy->fetchColumn();

$stmtCobros = $db->prepare("
    SELECT COUNT(*) FROM pagos
    WHERE cobro_id=? AND fecha_pago=CURDATE()
      AND usuario_id=? AND (anulado=0 OR anulado IS NULL)
");
$stmtCobros->execute([$cobro, $_SESSION['usuario_id']]);
$numCobros = (int)$stmtCobros->fetchColumn();
?>

<!-- Header -->
<div class="cob-header">
    <div>
        <div class="cob-title">MI RUTA</div>
        <div style="font-size:0.72rem;color:var(--muted);font-family:var(--font-mono)">
            <?= date('l d \d\e F') ?> · <?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
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

<!-- Botón ordenar por GPS -->
<button class="cob-btn cob-btn-primary" id="btn-ordenar-gps" onclick="ordenarPorGPS()">
    📍 Ordenar ruta por mi ubicación
</button>

<!-- Lista de deudores -->
<div id="lista-deudores">
<?php foreach ($deudores as $d):
    $inicial = strtoupper(substr($d['nombre'], 0, 1));
    $estado  = $d['prestamo_estado'] ?? null;
    $vencidas= (int)($d['cuotas_vencidas'] ?? 0);
    $mora    = (int)($d['dias_mora'] ?? 0);
?>
<a href="/cobrador/cobrar.php?deudor=<?= $d['id'] ?>"
   class="cob-deudor-row"
   data-lat="<?= $d['lat'] ?? '' ?>"
   data-lng="<?= $d['lng'] ?? '' ?>"
   data-id="<?= $d['id'] ?>">

    <div class="cob-avatar" style="background:<?= $mora > 0 ? '#f97316' : ($estado ? 'var(--accent)' : 'var(--muted)') ?>">
        <?= $inicial ?>
    </div>

    <div style="flex:1;min-width:0">
        <div style="font-weight:600;font-size:0.95rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            <?= htmlspecialchars($d['nombre']) ?>
        </div>
        <div style="font-size:0.72rem;color:var(--muted);font-family:var(--font-mono)">
            <?php if ($d['telefono']): ?>
                <?= htmlspecialchars($d['telefono']) ?>
            <?php endif; ?>
            <?php if ($d['barrio']): ?>
                · <?= htmlspecialchars($d['barrio']) ?>
            <?php endif; ?>
        </div>
    </div>

    <div style="text-align:right;flex-shrink:0">
        <?php if ($estado === 'en_mora'): ?>
            <span class="cob-badge cob-badge-mora"><?= $mora ?>d mora</span>
        <?php elseif ($estado === 'en_acuerdo'): ?>
            <span class="cob-badge cob-badge-acuerdo">Acuerdo</span>
        <?php elseif ($estado === 'activo' && $vencidas > 0): ?>
            <span class="cob-badge cob-badge-mora"><?= $vencidas ?> vencida<?= $vencidas > 1 ? 's' : '' ?></span>
        <?php elseif ($estado === 'activo'): ?>
            <span class="cob-badge cob-badge-ok">Al día</span>
        <?php else: ?>
            <span class="cob-badge" style="background:rgba(255,255,255,.08);color:var(--muted)">Sin préstamo</span>
        <?php endif; ?>

        <?php if ($d['valor_cuota']): ?>
        <div style="font-size:0.85rem;font-weight:700;color:var(--accent);margin-top:2px">
            $<?= number_format($d['valor_cuota'], 0, ',', '.') ?>
        </div>
        <?php endif; ?>

        <!-- Distancia (se llena con JS) -->
        <div class="dist-label" style="font-size:0.65rem;color:var(--muted);font-family:var(--font-mono)"></div>
    </div>
</a>
<?php endforeach; ?>

<?php if (empty($deudores)): ?>
<div style="text-align:center;padding:3rem 1rem;color:var(--muted)">
    <div style="font-size:2rem;margin-bottom:0.5rem">◎</div>
    <div style="font-family:var(--font-mono);font-size:0.8rem">Sin deudores en este cobro</div>
</div>
<?php endif; ?>
</div>

<?php
$extraScript = <<<'JS'
<script>
// Fórmula Haversine — distancia en km entre dos coordenadas
function haversine(lat1, lng1, lat2, lng2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI/180) * Math.cos(lat2 * Math.PI/180) *
              Math.sin(dLng/2) * Math.sin(dLng/2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

function ordenarPorGPS() {
    const btn = document.getElementById('btn-ordenar-gps');

    if (!navigator.geolocation) {
        alert('Tu dispositivo no soporta geolocalización');
        return;
    }

    btn.textContent = '⏳ Obteniendo ubicación...';
    btn.disabled = true;

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const miLat = pos.coords.latitude;
            const miLng = pos.coords.longitude;

            const lista = document.getElementById('lista-deudores');
            const filas = Array.from(lista.querySelectorAll('.cob-deudor-row'));

            // Calcular distancia de cada fila
            filas.forEach(fila => {
                const lat = parseFloat(fila.dataset.lat);
                const lng = parseFloat(fila.dataset.lng);
                if (lat && lng) {
                    const dist = haversine(miLat, miLng, lat, lng);
                    fila.dataset.dist = dist;
                    const label = fila.querySelector('.dist-label');
                    if (label) {
                        label.textContent = dist < 1
                            ? Math.round(dist * 1000) + 'm'
                            : dist.toFixed(1) + 'km';
                    }
                } else {
                    fila.dataset.dist = 99999; // sin coordenadas van al final
                    const label = fila.querySelector('.dist-label');
                    if (label) label.textContent = 'Sin ubicación';
                }
            });

            // Ordenar por distancia
            filas.sort((a, b) => parseFloat(a.dataset.dist) - parseFloat(b.dataset.dist));
            filas.forEach(f => lista.appendChild(f));

            btn.textContent = '✓ Ruta ordenada por proximidad';
            btn.style.background = 'var(--green, #22c55e)';
            btn.disabled = false;
        },
        (err) => {
            btn.textContent = '📍 Ordenar ruta por mi ubicación';
            btn.disabled = false;
            alert('No se pudo obtener tu ubicación. Verifica los permisos del navegador.');
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}
</script>
JS;

require_once __DIR__ . '/footer.php';
?>