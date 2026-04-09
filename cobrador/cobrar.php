<?php
$pageTitle = 'Cobrar';
$pageNav   = 'cobrar';
require_once __DIR__ . '/header.php';

$db        = getDB();
$deudor_id = (int)($_GET['deudor'] ?? 0);
$filtro    = $_GET['filtro'] ?? 'hoy'; // hoy | pendiente | todas

$deudorPre = null;
$prestamos = [];

if ($deudor_id) {
    $stmt = $db->prepare("
        SELECT d.* FROM deudores d
        JOIN deudor_cobro dc ON dc.deudor_id = d.id
        WHERE d.id = ? AND dc.cobro_id = ? AND d.activo = 1
    ");
    $stmt->execute([$deudor_id, $cobro]);
    $deudorPre = $stmt->fetch();

    if ($deudorPre) {
        // Cargar préstamos activos con sus cuotas
        $stmtP = $db->prepare("
            SELECT p.*,
                cap.nombre AS capitalista_nombre,
                (SELECT COUNT(*) FROM cuotas WHERE prestamo_id=p.id AND estado IN ('pendiente','parcial')) AS cuotas_pendientes,
                (SELECT COUNT(*) FROM cuotas WHERE prestamo_id=p.id AND estado='pagado') AS cuotas_pagadas,
                (SELECT COUNT(*) FROM cuotas WHERE prestamo_id=p.id) AS cuotas_total
            FROM prestamos p
            LEFT JOIN capitalistas cap ON cap.id = p.capitalista_id
            WHERE p.deudor_id = ? AND p.cobro_id = ?
              AND p.estado IN ('activo','en_mora','en_acuerdo')
            ORDER BY p.created_at DESC
        ");
        $stmtP->execute([$deudor_id, $cobro]);
        $prestamos = $stmtP->fetchAll();

        // Cargar cuotas de cada préstamo según filtro
        foreach ($prestamos as &$p) {
            if ($filtro === 'hoy') {
                // Cuotas de hoy + vencidas anteriores (mora)
                $whereEstado = "AND c.estado IN ('pendiente','parcial')
                                AND c.fecha_vencimiento <= CURDATE()";
            } elseif ($filtro === 'pendiente') {
                // Todas las pendientes sin importar fecha
                $whereEstado = "AND c.estado IN ('pendiente','parcial')";
            } else {
                // Todas incluyendo pagadas
                $whereEstado = "";
            }

            $stmtC = $db->prepare("
                SELECT c.*
                FROM cuotas c
                WHERE c.prestamo_id = ? $whereEstado
                ORDER BY c.numero_cuota ASC
            ");
            $stmtC->execute([$p['id']]);
            $p['cuotas'] = $stmtC->fetchAll();
        }
        unset($p);
    }
}

// Lista de deudores para el buscador
$stmtD = $db->prepare("
    SELECT d.id, d.nombre, d.telefono,
           p.estado AS prestamo_estado,
           p.valor_cuota,
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
$stmtD->execute([$cobro, $cobro]);
$deudores = $stmtD->fetchAll();

// Cuentas
$stmtCuentas = $db->prepare("SELECT id, nombre, tipo FROM cuentas WHERE cobro_id=? AND activa=1 ORDER BY nombre");
$stmtCuentas->execute([$cobro]);
$cuentas = $stmtCuentas->fetchAll();
?>

<div class="cob-header">
    <div class="cob-title">COBRAR</div>
</div>

<!-- Buscador -->
<div style="margin-bottom:1rem">
    <input type="text" id="buscador-deudor"
           placeholder="🔍 Buscar deudor..."
           style="width:100%;padding:0.85rem;font-size:1rem;border-radius:var(--radius);border:1px solid var(--border);background:var(--card);color:var(--text)"
           oninput="filtrarDeudores(this.value)"
           value="<?= $deudorPre ? htmlspecialchars($deudorPre['nombre']) : '' ?>">
</div>

<!-- Lista buscador -->
<div id="lista-busqueda" style="<?= $deudorPre ? 'display:none' : '' ?>">
<?php foreach ($deudores as $d):
    $mora     = $d['prestamo_estado'] === 'en_mora';
    $vencidas = (int)($d['cuotas_vencidas'] ?? 0);
?>
<div class="cob-deudor-row deudor-item"
     data-nombre="<?= strtolower(htmlspecialchars($d['nombre'])) ?>"
     onclick="seleccionarDeudor(<?= $d['id'] ?>)"
     style="cursor:pointer">
    <div class="cob-avatar" style="background:<?= $mora ? '#f97316' : 'var(--accent)' ?>">
        <?= strtoupper(substr($d['nombre'], 0, 1)) ?>
    </div>
    <div style="flex:1;min-width:0">
        <div style="font-weight:600"><?= htmlspecialchars($d['nombre']) ?></div>
        <div style="font-size:0.72rem;color:var(--muted);font-family:var(--font-mono)">
            <?= htmlspecialchars($d['telefono'] ?? '—') ?>
        </div>
    </div>
    <div style="text-align:right;flex-shrink:0">
        <?php if ($mora): ?>
            <span class="cob-badge cob-badge-mora"><?= $d['dias_mora'] ?>d mora</span>
        <?php elseif ($vencidas > 0): ?>
            <span class="cob-badge cob-badge-mora"><?= $vencidas ?> vencida<?= $vencidas > 1 ? 's':'' ?></span>
        <?php elseif ($d['prestamo_estado']): ?>
            <span class="cob-badge cob-badge-ok">Al día</span>
        <?php else: ?>
            <span class="cob-badge" style="background:rgba(255,255,255,.08);color:var(--muted)">Sin préstamo</span>
        <?php endif; ?>
        <?php if ($d['valor_cuota']): ?>
        <div style="font-size:0.85rem;font-weight:700;color:var(--accent);margin-top:2px">
            $<?= number_format($d['valor_cuota'], 0, ',', '.') ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- Panel deudor seleccionado -->
<?php if ($deudorPre): ?>
<div id="panel-cobro">

    <!-- Info deudor -->
    <!-- Cabecera del deudor seleccionado -->
    <div class="cob-card" style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem">
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

        <!-- FIX: botón nuevo préstamo -->
        <a href="/cobrador/prestamo.php?deudor=<?= $deudorPre['id'] ?>"
        style="flex-shrink:0;padding:0.5rem 0.75rem;background:rgba(124,106,255,.15);border:1px solid rgba(124,106,255,.3);border-radius:var(--radius);color:var(--accent);font-size:0.75rem;font-family:var(--font-mono);text-decoration:none;font-weight:600">
            + Préstamo
        </a>

        <a href="/cobrador/cobrar.php"
        style="background:none;border:none;color:var(--muted);font-size:1.2rem;text-decoration:none;padding:0.25rem">
            ✕
        </a>
    </div>

    <!-- Filtro cuotas -->
    <div style="display:flex;gap:0.5rem;margin-bottom:1rem">
        <a href="/cobrador/cobrar.php?deudor=<?= $deudor_id ?>&filtro=hoy"
        style="flex:1;text-align:center;padding:0.6rem;border-radius:var(--radius);font-family:var(--font-mono);font-size:0.75rem;font-weight:600;text-decoration:none;
                background:<?= $filtro==='hoy' ? 'var(--accent)' : 'var(--card)' ?>;
                color:<?= $filtro==='hoy' ? '#fff' : 'var(--muted)' ?>;
                border:1px solid <?= $filtro==='hoy' ? 'var(--accent)' : 'var(--border)' ?>">
            HOY
        </a>
        <a href="/cobrador/cobrar.php?deudor=<?= $deudor_id ?>&filtro=pendiente"
        style="flex:1;text-align:center;padding:0.6rem;border-radius:var(--radius);font-family:var(--font-mono);font-size:0.75rem;font-weight:600;text-decoration:none;
                background:<?= $filtro==='pendiente' ? 'var(--accent)' : 'var(--card)' ?>;
                color:<?= $filtro==='pendiente' ? '#fff' : 'var(--muted)' ?>;
                border:1px solid <?= $filtro==='pendiente' ? 'var(--accent)' : 'var(--border)' ?>">
            PENDIENTES
        </a>
        <a href="/cobrador/cobrar.php?deudor=<?= $deudor_id ?>&filtro=todas"
        style="flex:1;text-align:center;padding:0.6rem;border-radius:var(--radius);font-family:var(--font-mono);font-size:0.75rem;font-weight:600;text-decoration:none;
                background:<?= $filtro==='todas' ? 'var(--accent)' : 'var(--card)' ?>;
                color:<?= $filtro==='todas' ? '#fff' : 'var(--muted)' ?>;
                border:1px solid <?= $filtro==='todas' ? 'var(--accent)' : 'var(--border)' ?>">
            TODAS
        </a>
    </div>

    <?php if (empty($prestamos)): ?>
    <div class="cob-card" style="text-align:center;padding:2rem;color:var(--muted)">
        <div style="font-size:1.5rem;margin-bottom:0.5rem">◎</div>
        <div style="font-family:var(--font-mono);font-size:0.8rem">Sin préstamos activos</div>
    </div>
    <?php endif; ?>

    <!-- Préstamos -->
    <?php foreach ($prestamos as $prest): ?>
    <?php
        $pct = $prest['cuotas_total'] > 0
            ? round($prest['cuotas_pagadas'] / $prest['cuotas_total'] * 100)
            : 0;
        $estadoColor = match($prest['estado']) {
            'en_mora'    => '#f97316',
            'en_acuerdo' => '#3b82f6',
            default      => 'var(--accent)'
        };
    ?>
    <div style="margin-bottom:1.25rem">

        <!-- Cabecera del préstamo -->
        <div class="cob-card" style="border-left:3px solid <?= $estadoColor ?>;border-radius:0 var(--radius) var(--radius) 0;margin-bottom:0.5rem">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.5rem">
                <div>
                    <div style="font-family:var(--font-mono);font-size:0.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px">
                        Préstamo #<?= $prest['id'] ?>
                        <?php if ($prest['capitalista_nombre']): ?>
                         · <?= htmlspecialchars($prest['capitalista_nombre']) ?>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:1.3rem;font-weight:700;color:<?= $estadoColor ?>">
                        $<?= number_format($prest['monto_prestado'], 0, ',', '.') ?>
                    </div>
                    <div style="font-size:0.72rem;color:var(--muted);font-family:var(--font-mono)">
                        <?= $prest['interes_valor'] ?><?= $prest['tipo_interes']==='porcentaje'?'%':' fijo' ?> ·
                        <?= ucfirst($prest['frecuencia_pago']) ?> ·
                        Cuota $<?= number_format($prest['valor_cuota'], 0, ',', '.') ?>
                    </div>
                </div>
                <div style="text-align:right">
                    <span class="cob-badge <?= $prest['estado']==='en_mora' ? 'cob-badge-mora' : ($prest['estado']==='en_acuerdo' ? 'cob-badge-acuerdo' : 'cob-badge-ok') ?>">
                        <?= strtoupper($prest['estado']) ?>
                    </span>
                    <div style="font-size:0.75rem;color:var(--muted);margin-top:4px;font-family:var(--font-mono)">
                        Saldo: $<?= number_format($prest['saldo_pendiente'], 0, ',', '.') ?>
                    </div>
                </div>
            </div>

            <!-- Barra de progreso -->
            <div style="background:var(--border);border-radius:4px;height:5px;overflow:hidden">
                <div style="background:<?= $estadoColor ?>;width:<?= $pct ?>%;height:100%;border-radius:4px;transition:width .3s"></div>
            </div>
            <div style="font-size:0.65rem;color:var(--muted);font-family:var(--font-mono);margin-top:3px">
                <?= $prest['cuotas_pagadas'] ?>/<?= $prest['cuotas_total'] ?> cuotas pagadas (<?= $pct ?>%)
            </div>
        </div>

        <!-- Cuotas del préstamo -->
        <?php if (empty($prest['cuotas'])): ?>
        <div style="padding:0.75rem 1rem;color:var(--muted);font-size:0.8rem;font-family:var(--font-mono);text-align:center">
            <?= $filtro === 'hoy' ? 'Sin cuotas para hoy ✓' : ($filtro === 'pendiente' ? 'Sin cuotas pendientes ✓' : 'Sin cuotas') ?>
        </div>
        <?php else: ?>
        <?php foreach ($prest['cuotas'] as $c):
            $esPagada  = $c['estado'] === 'pagado';
            $esVencida = !$esPagada && $c['fecha_vencimiento'] < date('Y-m-d');
            $diasMora  = $esVencida ? (new DateTime())->diff(new DateTime($c['fecha_vencimiento']))->days : 0;
        ?>
        <div class="cob-card"
             style="margin-bottom:0.4rem;
                    border-color:<?= $esPagada ? 'rgba(34,197,94,.3)' : ($esVencida ? '#f97316' : 'var(--border)') ?>;
                    opacity:<?= $esPagada ? '0.65' : '1' ?>;
                    cursor:<?= $esPagada ? 'default' : 'pointer' ?>"
             <?= !$esPagada ? "onclick=\"seleccionarCuota({$c['id']}, {$c['saldo_cuota']}, {$prest['id']})\"" : '' ?>>

            <div style="display:flex;justify-content:space-between;align-items:center">
                <div>
                    <div style="font-weight:600;display:flex;align-items:center;gap:0.4rem">
                        Cuota #<?= $c['numero_cuota'] ?>
                        <?php if ($esPagada): ?>
                            <span style="color:#22c55e;font-size:0.8rem">✓ Pagada</span>
                        <?php elseif ($esVencida): ?>
                            <span style="color:#f97316;font-size:0.75rem;font-family:var(--font-mono)"><?= $diasMora ?>d mora</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:0.7rem;color:var(--muted);font-family:var(--font-mono)">
                        <?= date('d M Y', strtotime($c['fecha_vencimiento'])) ?>
                        <?php if ($c['estado'] === 'parcial'): ?>
                            <span style="color:#f59e0b"> · Pago parcial</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="text-align:right">
                    <div style="font-size:1rem;font-weight:700;color:<?= $esPagada ? '#22c55e' : ($esVencida ? '#f97316' : 'var(--accent)') ?>">
                        $<?= number_format($c['saldo_cuota'], 0, ',', '.') ?>
                    </div>
                    <?php if ($c['monto_pagado'] > 0 && !$esPagada): ?>
                    <div style="font-size:0.65rem;color:var(--muted);font-family:var(--font-mono)">
                        Abonado: $<?= number_format($c['monto_pagado'], 0, ',', '.') ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!$esPagada): ?>
                    <div style="font-size:0.65rem;color:var(--muted);margin-top:2px">Toca para cobrar →</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <!-- Modal / panel de pago -->
    <div id="form-pago-wrap"
         style="display:none;position:fixed;bottom:0;left:0;right:0;
                background:var(--card);border-top:2px solid var(--accent);
                padding:1.25rem 1rem 2rem;z-index:200;
                box-shadow:0 -4px 20px rgba(0,0,0,.3)">

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
            <div>
                <div style="font-family:var(--font-mono);font-size:0.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px">
                    Registrar pago
                </div>
                <div id="pago-cuota-label" style="font-weight:600;font-size:0.9rem"></div>
            </div>
            <button onclick="cancelarPago()"
                    style="background:none;border:none;color:var(--muted);font-size:1.4rem;cursor:pointer">✕</button>
        </div>

        <div class="field-lg">
            <label>Monto recibido</label>
            <input type="number" id="pago-monto" placeholder="0"
                   step="1000" min="1"
                   style="font-size:1.8rem;font-weight:700;text-align:center;color:var(--accent)">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-bottom:0.75rem">
            <div class="field-lg" style="margin:0">
                <label>Cuenta</label>
                <select id="pago-cuenta">
                    <?php foreach ($cuentas as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field-lg" style="margin:0">
                <label>Método</label>
                <select id="pago-metodo">
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="nequi">Nequi</option>
                    <option value="daviplata">Daviplata</option>
                </select>
            </div>
        </div>

        <div class="field-lg">
            <label>Fecha</label>
            <input type="date" id="pago-fecha" value="<?= date('Y-m-d') ?>">
        </div>

        <input type="hidden" id="pago-cuota-id"    value="">
        <input type="hidden" id="pago-prestamo-id" value="">

        <button id="btn-registrar-pago" class="cob-btn cob-btn-success" onclick="registrarPago()">
            💰 REGISTRAR PAGO
        </button>
    </div>

    <!-- Overlay oscuro detrás del panel de pago -->
    <div id="overlay-pago"
         onclick="cancelarPago()"
         style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:199"></div>

</div>
<?php endif; ?>

<?php
$extraScript = <<<'JS'
<script>
function filtrarDeudores(q) {
    const lista  = document.getElementById('lista-busqueda');
    const items  = lista.querySelectorAll('.deudor-item');
    const busq   = q.toLowerCase().trim();
    lista.style.display = busq ? 'block' : 'none';
    items.forEach(item => {
        item.style.display = item.dataset.nombre.includes(busq) ? 'flex' : 'none';
    });
}

function seleccionarDeudor(id) {
    window.location = '/cobrador/cobrar.php?deudor=' + id;
}

function seleccionarCuota(cuotaId, saldo, prestamoId) {
    document.getElementById('pago-cuota-id').value    = cuotaId;
    document.getElementById('pago-prestamo-id').value = prestamoId;
    document.getElementById('pago-monto').value       = saldo;
    document.getElementById('pago-cuota-label').textContent = 'Cuota seleccionada · $' + saldo.toLocaleString('es-CO');
    document.getElementById('form-pago-wrap').style.display = 'block';
    document.getElementById('overlay-pago').style.display   = 'block';
    document.getElementById('pago-monto').focus();
}

function cancelarPago() {
    document.getElementById('form-pago-wrap').style.display = 'none';
    document.getElementById('overlay-pago').style.display   = 'none';
}

async function registrarPago() {
    const monto    = parseFloat(document.getElementById('pago-monto').value) || 0;
    const cuotaId  = document.getElementById('pago-cuota-id').value;
    const prestId  = document.getElementById('pago-prestamo-id').value;
    const cuentaId = document.getElementById('pago-cuenta').value;
    const metodo   = document.getElementById('pago-metodo').value;
    const fecha    = document.getElementById('pago-fecha').value;

    if (!monto || monto <= 0) { alert('Ingresa el monto'); return; }
    if (!cuentaId)            { alert('Selecciona la cuenta'); return; }

    const btn = document.getElementById('btn-registrar-pago');
    btn.textContent = '⏳ Guardando...';
    btn.disabled = true;

    try {
        const res = await fetch('/api/prestamos.php', {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({
                action      : 'pagar',
                prestamo_id : parseInt(prestId),
                cuota_id    : parseInt(cuotaId),
                monto_pagado: monto,
                cuenta_id   : parseInt(cuentaId),
                metodo_pago : metodo,
                fecha_pago  : fecha
            })
        });
        const data = await res.json();

        if (data.ok) {
            btn.textContent = '✓ Pago registrado';
            btn.style.background = '#22c55e';
            setTimeout(() => window.location.reload(), 1000);
        } else {
            alert(data.msg || 'Error al registrar el pago');
            btn.textContent = '💰 REGISTRAR PAGO';
            btn.disabled = false;
        }
    } catch(e) {
        alert('Error de conexión');
        btn.textContent = '💰 REGISTRAR PAGO';
        btn.disabled = false;
    }
}
</script>
JS;

require_once __DIR__ . '/footer.php';
?>