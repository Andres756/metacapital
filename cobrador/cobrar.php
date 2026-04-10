<?php
$pageTitle = 'Cobrar';
$pageNav   = 'cobrar';
require_once __DIR__ . '/header.php';

$db        = getDB();
$deudor_id = (int)($_GET['deudor'] ?? 0);
$filtro    = $_GET['filtro'] ?? 'hoy';

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
        $stmtP = $db->prepare("
            SELECT p.*,
                (SELECT COUNT(*) FROM cuotas WHERE prestamo_id=p.id AND estado IN ('pendiente','parcial')) AS cuotas_pendientes,
                (SELECT COUNT(*) FROM cuotas WHERE prestamo_id=p.id AND estado='pagado') AS cuotas_pagadas,
                (SELECT COUNT(*) FROM cuotas WHERE prestamo_id=p.id) AS cuotas_total
            FROM prestamos p
            WHERE p.deudor_id = ? AND p.cobro_id = ?
            AND p.estado IN ('activo','en_mora','en_acuerdo')
            ORDER BY p.created_at DESC
        ");
        $stmtP->execute([$deudor_id, $cobro]);
        $prestamos = $stmtP->fetchAll();

        foreach ($prestamos as &$p) {
            $whereEstado = match($filtro) {
                'hoy'      => "AND c.estado IN ('pendiente','parcial') AND c.fecha_vencimiento <= CURDATE()",
                'pendiente'=> "AND c.estado IN ('pendiente','parcial')",
                default    => "",
            };
            $stmtC = $db->prepare("SELECT c.* FROM cuotas c WHERE c.prestamo_id = ? $whereEstado ORDER BY c.numero_cuota ASC");
            $stmtC->execute([$p['id']]);
            $p['cuotas'] = $stmtC->fetchAll();
        }
        unset($p);
    }
}

// Lista buscador — todos los deudores del cobro
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
    ORDER BY
        CASE WHEN p.id IS NOT NULL THEN 0 ELSE 1 END,
        p.dias_mora DESC,
        d.nombre ASC
");
$stmtD->execute([$cobro, $cobro]);
$deudores = $stmtD->fetchAll();

$totalConPrestamo = count(array_filter($deudores, fn($d) => $d['prestamo_estado']));
$totalSinPrestamo = count($deudores) - $totalConPrestamo;
?>

<div class="cob-header">
    <div class="cob-title">COBRAR</div>
</div>

<!-- Buscador -->
<div style="margin-bottom:0.75rem">
    <input type="text" id="buscador-deudor"
           placeholder="🔍 Buscar cliente..."
           style="width:100%;padding:0.85rem;font-size:1rem;border-radius:var(--radius);border:1px solid var(--border);background:var(--card);color:var(--text)"
           oninput="filtrarBusqueda(this.value)"
           value="<?= $deudorPre ? htmlspecialchars($deudorPre['nombre']) : '' ?>">
</div>

<!-- Filtro con/sin préstamo -->
<?php if (!$deudorPre): ?>
<div style="display:flex;gap:0.4rem;margin-bottom:0.75rem" id="barra-filtro">
    <button onclick="setFiltro('todos')" id="f-todos"
            style="flex:1;padding:0.5rem;border-radius:var(--radius);border:1px solid var(--accent);background:var(--accent);color:#fff;font-family:var(--font-mono);font-size:0.72rem;font-weight:700;cursor:pointer">
        TODOS <span style="opacity:.7">(<?= count($deudores) ?>)</span>
    </button>
    <button onclick="setFiltro('con')" id="f-con"
            style="flex:1;padding:0.5rem;border-radius:var(--radius);border:1px solid var(--border);background:transparent;color:var(--muted);font-family:var(--font-mono);font-size:0.72rem;font-weight:700;cursor:pointer">
        CON PRÉSTAMO <span style="opacity:.7">(<?= $totalConPrestamo ?>)</span>
    </button>
    <button onclick="setFiltro('sin')" id="f-sin"
            style="flex:1;padding:0.5rem;border-radius:var(--radius);border:1px solid var(--border);background:transparent;color:var(--muted);font-family:var(--font-mono);font-size:0.72rem;font-weight:700;cursor:pointer">
        SIN PRÉSTAMO <span style="opacity:.7">(<?= $totalSinPrestamo ?>)</span>
    </button>
</div>
<?php endif; ?>

<!-- Lista buscador -->
<div id="lista-busqueda" style="<?= $deudorPre ? 'display:none' : '' ?>">
<?php foreach ($deudores as $d):
    $tienePrestamo = !empty($d['prestamo_estado']);
    $mora          = (int)($d['dias_mora'] ?? 0);
    $vencidas      = (int)($d['cuotas_vencidas'] ?? 0);
?>
<div class="cob-deudor-row deudor-item"
     data-nombre="<?= strtolower(htmlspecialchars($d['nombre'])) ?>"
     data-tiene="<?= $tienePrestamo ? 'con' : 'sin' ?>"
     onclick="window.location='/cobrador/cobrar.php?deudor=<?= $d['id'] ?>'"
     style="cursor:pointer">

    <div class="cob-avatar"
         style="background:<?= $mora > 0 ? '#f97316' : ($tienePrestamo ? 'var(--accent)' : 'var(--muted)') ?>;flex-shrink:0">
        <?= strtoupper(substr($d['nombre'], 0, 1)) ?>
    </div>

    <div style="flex:1;min-width:0">
        <div style="font-weight:600"><?= htmlspecialchars($d['nombre']) ?></div>
        <div style="font-size:0.72rem;color:var(--muted);font-family:var(--font-mono)">
            <?= htmlspecialchars($d['telefono'] ?? '—') ?>
        </div>
    </div>

    <div style="text-align:right;flex-shrink:0">
        <?php if ($mora > 0): ?>
            <span class="cob-badge cob-badge-mora"><?= $mora ?>d mora</span>
        <?php elseif ($vencidas > 0): ?>
            <span class="cob-badge cob-badge-mora"><?= $vencidas ?> venc.</span>
        <?php elseif ($tienePrestamo): ?>
            <span class="cob-badge cob-badge-ok">Al día</span>
        <?php else: ?>
            <span class="cob-badge" style="background:rgba(255,255,255,.07);color:var(--muted)">Sin préstamo</span>
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

    <div class="cob-card" style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem">
        <div class="cob-avatar" style="width:50px;height:50px;font-size:1.3rem;flex-shrink:0">
            <?= strtoupper(substr($deudorPre['nombre'], 0, 1)) ?>
        </div>
        <div style="flex:1;min-width:0">
            <div style="font-weight:700;font-size:1.05rem"><?= htmlspecialchars($deudorPre['nombre']) ?></div>
            <div style="font-size:0.75rem;color:var(--muted);font-family:var(--font-mono)">
                CC: <?= htmlspecialchars($deudorPre['documento'] ?? '—') ?>
                <?php if ($deudorPre['telefono']): ?> · <?= htmlspecialchars($deudorPre['telefono']) ?><?php endif; ?>
            </div>
        </div>
        <a href="/cobrador/prestamo.php?deudor=<?= $deudorPre['id'] ?>"
           style="flex-shrink:0;padding:0.5rem 0.75rem;background:rgba(124,106,255,.15);border:1px solid rgba(124,106,255,.3);border-radius:var(--radius);color:var(--accent);font-size:0.75rem;font-family:var(--font-mono);text-decoration:none;font-weight:600">
            + Préstamo
        </a>
        <a href="/cobrador/cobrar.php"
           style="background:none;border:none;color:var(--muted);font-size:1.2rem;text-decoration:none;padding:0.25rem">✕</a>
    </div>

    <!-- Filtro cuotas -->
    <div style="display:flex;gap:0.5rem;margin-bottom:1rem">
        <?php foreach (['hoy'=>'HOY','pendiente'=>'PENDIENTES','todas'=>'TODAS'] as $f=>$label): ?>
        <a href="/cobrador/cobrar.php?deudor=<?= $deudor_id ?>&filtro=<?= $f ?>"
           style="flex:1;text-align:center;padding:0.55rem;border-radius:var(--radius);font-family:var(--font-mono);font-size:0.72rem;font-weight:600;text-decoration:none;
                  background:<?= $filtro===$f?'var(--accent)':'var(--card)' ?>;
                  color:<?= $filtro===$f?'#fff':'var(--muted)' ?>;
                  border:1px solid <?= $filtro===$f?'var(--accent)':'var(--border)' ?>">
            <?= $label ?>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($prestamos)): ?>
    <div class="cob-card" style="text-align:center;padding:2rem;color:var(--muted)">
        <div style="font-size:1.5rem;margin-bottom:0.5rem">◎</div>
        <div style="font-family:var(--font-mono);font-size:0.8rem">Sin préstamos activos</div>
        <a href="/cobrador/prestamo.php?deudor=<?= $deudorPre['id'] ?>"
           style="display:inline-block;margin-top:1rem;padding:0.6rem 1.25rem;background:var(--accent);color:#fff;border-radius:var(--radius);text-decoration:none;font-family:var(--font-mono);font-size:0.8rem;font-weight:700">
            + Crear préstamo
        </a>
    </div>
    <?php endif; ?>

    <?php foreach ($prestamos as $prest):
        $pct = $prest['cuotas_total'] > 0
            ? round($prest['cuotas_pagadas'] / $prest['cuotas_total'] * 100) : 0;
        $estadoColor = match($prest['estado']) {
            'en_mora'    => '#f97316',
            'en_acuerdo' => '#3b82f6',
            default      => 'var(--accent)'
        };
    ?>
    <div style="margin-bottom:1.25rem">
        <div class="cob-card" style="border-left:3px solid <?= $estadoColor ?>;border-radius:0 var(--radius) var(--radius) 0;margin-bottom:0.5rem">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.5rem">
                <div>
                    <div style="font-family:var(--font-mono);font-size:0.63rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px">
                        Préstamo #<?= $prest['id'] ?>
                    </div>
                    <div style="font-size:1.3rem;font-weight:700;color:<?= $estadoColor ?>">
                        $<?= number_format($prest['monto_prestado'], 0, ',', '.') ?>
                    </div>
                    <div style="font-size:0.7rem;color:var(--muted);font-family:var(--font-mono)">
                        <?= $prest['interes_valor'] ?><?= $prest['tipo_interes']==='porcentaje'?'%':' fijo' ?> ·
                        <?= ucfirst($prest['frecuencia_pago']) ?> ·
                        Cuota $<?= number_format($prest['valor_cuota'], 0, ',', '.') ?>
                    </div>
                </div>
                <div style="text-align:right">
                    <span class="cob-badge <?= $prest['estado']==='en_mora'?'cob-badge-mora':($prest['estado']==='en_acuerdo'?'cob-badge-acuerdo':'cob-badge-ok') ?>">
                        <?= strtoupper($prest['estado']) ?>
                    </span>
                    <div style="font-size:0.72rem;color:var(--muted);margin-top:4px;font-family:var(--font-mono)">
                        Saldo $<?= number_format($prest['saldo_pendiente'], 0, ',', '.') ?>
                    </div>
                </div>
            </div>
            <div style="background:var(--border);border-radius:4px;height:5px;overflow:hidden">
                <div style="background:<?= $estadoColor ?>;width:<?= $pct ?>%;height:100%;border-radius:4px"></div>
            </div>
            <div style="font-size:0.63rem;color:var(--muted);font-family:var(--font-mono);margin-top:3px">
                <?= $prest['cuotas_pagadas'] ?>/<?= $prest['cuotas_total'] ?> cuotas (<?= $pct ?>%)
            </div>
        </div>

        <?php if (empty($prest['cuotas'])): ?>
        <div style="padding:0.75rem 1rem;color:var(--muted);font-size:0.8rem;font-family:var(--font-mono);text-align:center">
            <?= $filtro==='hoy' ? 'Sin cuotas para hoy ✓' : ($filtro==='pendiente' ? 'Sin cuotas pendientes ✓' : 'Sin cuotas') ?>
        </div>
        <?php else: ?>
        <?php foreach ($prest['cuotas'] as $c):
            $esPagada  = $c['estado'] === 'pagado';
            $esVencida = !$esPagada && $c['fecha_vencimiento'] < date('Y-m-d');
            $diasMora  = $esVencida ? (new DateTime())->diff(new DateTime($c['fecha_vencimiento']))->days : 0;
        ?>
        <div class="cob-card"
             style="margin-bottom:0.4rem;
                    border-color:<?= $esPagada?'rgba(34,197,94,.3)':($esVencida?'#f97316':'var(--border)') ?>;
                    opacity:<?= $esPagada?'0.6':'1' ?>;cursor:<?= $esPagada?'default':'pointer' ?>"
             <?= !$esPagada ? "onclick=\"abrirPago({$c['id']},{$c['saldo_cuota']},{$prest['id']})\"" : '' ?>>
            <div style="display:flex;justify-content:space-between;align-items:center">
                <div>
                    <div style="font-weight:600;display:flex;align-items:center;gap:0.4rem">
                        Cuota #<?= $c['numero_cuota'] ?>
                        <?php if ($esPagada): ?><span style="color:#22c55e;font-size:0.8rem">✓</span>
                        <?php elseif ($esVencida): ?><span style="color:#f97316;font-size:0.72rem;font-family:var(--font-mono)"><?= $diasMora ?>d mora</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:0.68rem;color:var(--muted);font-family:var(--font-mono)">
                        <?= date('d M Y', strtotime($c['fecha_vencimiento'])) ?>
                        <?php if ($c['estado']==='parcial'): ?><span style="color:#f59e0b"> · Parcial</span><?php endif; ?>
                    </div>
                </div>
                <div style="text-align:right">
                    <div style="font-size:1rem;font-weight:700;color:<?= $esPagada?'#22c55e':($esVencida?'#f97316':'var(--accent)') ?>">
                        $<?= number_format($c['saldo_cuota'], 0, ',', '.') ?>
                    </div>
                    <?php if (!$esPagada): ?>
                    <div style="font-size:0.62rem;color:var(--muted);margin-top:2px">Toca para cobrar →</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <!-- Panel pago -->
    <div id="form-pago-wrap" style="display:none;position:fixed;bottom:0;left:0;right:0;
         background:var(--card);border-top:2px solid var(--accent);padding:1.25rem 1rem 2rem;
         z-index:200;box-shadow:0 -4px 20px rgba(0,0,0,.3)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
            <div>
                <div style="font-family:var(--font-mono);font-size:0.63rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px">Registrar pago</div>
                <div id="pago-label" style="font-weight:600;font-size:0.9rem"></div>
            </div>
            <button onclick="cerrarPago()" style="background:none;border:none;color:var(--muted);font-size:1.4rem;cursor:pointer">✕</button>
        </div>
        <div class="field-lg">
            <label>Monto recibido</label>
            <input type="number" id="pago-monto" placeholder="0" step="1000" min="1"
                   style="font-size:1.8rem;font-weight:700;text-align:center;color:var(--accent)">
        </div>
        <div class="field-lg" style="margin-bottom:0.75rem">
            <label>Método</label>
            <select id="pago-metodo">
                <option value="efectivo">Efectivo</option>
                <option value="banco">Banco</option>
            </select>
        </div>
        <input type="hidden" id="pago-fecha">
        <input type="hidden" id="pago-cuota-id">
        <input type="hidden" id="pago-prestamo-id">
        <button id="btn-pagar" class="cob-btn cob-btn-success" onclick="registrarPago()">
            💰 REGISTRAR PAGO
        </button>
    </div>
    <div id="overlay-pago" onclick="cerrarPago()"
         style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:199"></div>
</div>
<?php endif; ?>

<?php
$extraScript = <<<'JS'
<script>
// ─── Filtro con/sin préstamo ──────────────────────────────────
let filtroActual = 'todos';

function setFiltro(f) {
    filtroActual = f;
    const btns = { todos: 'f-todos', con: 'f-con', sin: 'f-sin' };
    Object.entries(btns).forEach(([k, id]) => {
        const btn = document.getElementById(id);
        if (!btn) return;
        const activo = k === f;
        btn.style.background   = activo ? 'var(--accent)' : 'transparent';
        btn.style.color        = activo ? '#fff' : 'var(--muted)';
        btn.style.borderColor  = activo ? 'var(--accent)' : 'var(--border)';
    });
    aplicarFiltros();
}

function filtrarBusqueda(q) {
    aplicarFiltros(q);
}

function aplicarFiltros(q) {
    const busq  = (q !== undefined ? q : document.getElementById('buscador-deudor').value).toLowerCase().trim();
    const items = document.querySelectorAll('#lista-busqueda .deudor-item');
    let lista   = document.getElementById('lista-busqueda');

    items.forEach(item => {
        const matchBusq  = !busq || item.dataset.nombre.includes(busq);
        const matchFiltro = filtroActual === 'todos' || item.dataset.tiene === filtroActual;
        item.style.display = (matchBusq && matchFiltro) ? 'flex' : 'none';
    });

    lista.style.display = 'block';
}

// ─── Panel de pago ────────────────────────────────────────────
function abrirPago(cuotaId, saldo, prestamoId) {
    document.getElementById('pago-cuota-id').value    = cuotaId;
    document.getElementById('pago-prestamo-id').value = prestamoId;
    document.getElementById('pago-monto').value       = saldo;
    document.getElementById('pago-label').textContent =
        'Cuota · $' + saldo.toLocaleString('es-CO');
    document.getElementById('form-pago-wrap').style.display = 'block';
    document.getElementById('overlay-pago').style.display   = 'block';
    document.getElementById('pago-monto').focus();
    document.getElementById('pago-monto').select();
}

function cerrarPago() {
    document.getElementById('form-pago-wrap').style.display = 'none';
    document.getElementById('overlay-pago').style.display   = 'none';
}

async function registrarPago() {
    const monto   = parseFloat(document.getElementById('pago-monto').value) || 0;
    const cuotaId = document.getElementById('pago-cuota-id').value;
    const prestId = document.getElementById('pago-prestamo-id').value;
    const metodo  = document.getElementById('pago-metodo').value;
    const fecha   = new Date().toISOString().slice(0, 10);

    if (!monto || monto <= 0) { alert('Ingresa el monto'); return; }

    const btn = document.getElementById('btn-pagar');
    btn.textContent = '⏳ Guardando...';
    btn.disabled    = true;

    try {
        const res  = await fetch('/api/prestamos.php', {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({
                action      : 'pagar',
                prestamo_id : parseInt(prestId),
                cuota_id    : parseInt(cuotaId),
                monto_pagado: monto,
                metodo_pago : metodo,
                fecha_pago  : fecha,
            })
        });
        const data = await res.json();
        if (data.ok) {
            btn.textContent      = '✓ Registrado';
            btn.style.background = '#22c55e';
            setTimeout(() => location.reload(), 900);
        } else {
            alert(data.msg || 'Error al registrar el pago');
            btn.textContent = '💰 REGISTRAR PAGO';
            btn.disabled    = false;
        }
    } catch(e) {
        alert('Error de conexión');
        btn.textContent = '💰 REGISTRAR PAGO';
        btn.disabled    = false;
    }
}
</script>
JS;

require_once __DIR__ . '/footer.php';
?>