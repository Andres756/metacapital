<?php
$pageTitle = 'Cobrar';
$pageNav   = 'cobrar';
require_once __DIR__ . '/header.php';

$db        = getDB();
$deudor_id = (int)($_GET['deudor'] ?? 0);

$deudorPre = null;
$prestamos = [];

if ($deudor_id) {
    // Permitir acceso si tiene préstamo activo aunque sea clavo
    // Un clavo sin préstamo no debe verse, pero si debe plata hay que cobrarle
    $stmt = $db->prepare("
        SELECT d.* FROM deudores d
        JOIN deudor_cobro dc ON dc.deudor_id = d.id
        WHERE d.id = ? AND dc.cobro_id = ? AND d.activo = 1
          AND (
            d.comportamiento != 'clavo'
            OR EXISTS (
                SELECT 1 FROM prestamos p
                WHERE p.deudor_id = d.id AND p.cobro_id = ?
                  AND p.estado IN ('activo','en_mora','en_acuerdo')
            )
          )
    ");
    $stmt->execute([$deudor_id, $cobro, $cobro]);
    $deudorPre = $stmt->fetch();

    if ($deudorPre) {
        // Préstamos activos con resumen de cuotas
        $stmtP = $db->prepare("
            SELECT p.*,
                (SELECT COUNT(*) FROM cuotas WHERE prestamo_id=p.id AND estado='pagado')              AS cuotas_pagadas,
                (SELECT COUNT(*) FROM cuotas WHERE prestamo_id=p.id)                                  AS cuotas_total,
                (SELECT COUNT(*) FROM cuotas WHERE prestamo_id=p.id AND estado IN ('pendiente','parcial')
                    AND fecha_vencimiento <= CURDATE())                                                AS cuotas_vencidas,
                (SELECT MAX(fecha_pago) FROM pagos WHERE prestamo_id=p.id AND (anulado=0 OR anulado IS NULL)) AS ultimo_pago
            FROM prestamos p
            WHERE p.deudor_id = ? AND p.cobro_id = ?
              AND p.estado IN ('activo','en_mora','en_acuerdo')
            ORDER BY p.created_at DESC
        ");
        $stmtP->execute([$deudor_id, $cobro]);
        $prestamos = $stmtP->fetchAll();

        // Cargar TODAS las cuotas de cada préstamo
        foreach ($prestamos as &$p) {
            $stmtC = $db->prepare("
                SELECT c.* FROM cuotas c
                WHERE c.prestamo_id = ?
                ORDER BY c.numero_cuota ASC
            ");
            $stmtC->execute([$p['id']]);
            $p['cuotas'] = $stmtC->fetchAll();
        }
        unset($p);
    }
}

// Lista buscador
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
      AND (d.comportamiento != 'clavo' OR p.id IS NOT NULL)
    ORDER BY
        CASE WHEN p.id IS NOT NULL THEN 0 ELSE 1 END,
        p.dias_mora DESC,
        d.nombre ASC
");
$stmtD->execute([$cobro, $cobro]);
$deudores = $stmtD->fetchAll();

$totalConPrestamo = count(array_filter($deudores, fn($d) => $d['prestamo_estado']));
$totalSinPrestamo = count($deudores) - $totalConPrestamo;

// Teléfono limpio para llamada y WPP
$telLimpio = preg_replace('/\D/', '', $deudorPre['telefono'] ?? '');
if ($telLimpio && !str_starts_with($telLimpio, '57')) $telLimpio = '57' . $telLimpio;
?>

<div class="cob-header">
    <div class="cob-title">COBRAR</div>
</div>

<!-- ══ BUSCADOR ══════════════════════════════════════════════ -->
<div style="margin-bottom:0.75rem">
    <input type="text" id="buscador-deudor"
           placeholder="🔍 Buscar cliente..."
           style="width:100%;padding:0.85rem;font-size:1rem;border-radius:var(--radius);border:1px solid var(--border);background:var(--card);color:var(--text)"
           oninput="filtrarBusqueda(this.value)"
           value="<?= $deudorPre ? htmlspecialchars($deudorPre['nombre']) : '' ?>">
</div>

<!-- Filtro con/sin préstamo -->
<?php if (!$deudorPre): ?>
<div style="display:flex;gap:0.4rem;margin-bottom:0.75rem">
    <button onclick="setFiltro('todos')" id="f-todos"
            style="flex:1;padding:0.5rem;border-radius:var(--radius);border:1px solid var(--accent);background:var(--accent);color:#fff;font-family:var(--font-mono);font-size:0.7rem;font-weight:700;cursor:pointer">
        TODOS (<?= count($deudores) ?>)
    </button>
    <button onclick="setFiltro('con')" id="f-con"
            style="flex:1;padding:0.5rem;border-radius:var(--radius);border:1px solid var(--border);background:transparent;color:var(--muted);font-family:var(--font-mono);font-size:0.7rem;font-weight:700;cursor:pointer">
        CON PRÉSTAMO (<?= $totalConPrestamo ?>)
    </button>
    <button onclick="setFiltro('sin')" id="f-sin"
            style="flex:1;padding:0.5rem;border-radius:var(--radius);border:1px solid var(--border);background:transparent;color:var(--muted);font-family:var(--font-mono);font-size:0.7rem;font-weight:700;cursor:pointer">
        SIN PRÉSTAMO (<?= $totalSinPrestamo ?>)
    </button>
</div>
<?php endif; ?>

<!-- ══ LISTA BUSCADOR ═════════════════════════════════════════ -->
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
        <div style="font-size:0.72rem;color:var(--muted);font-family:var(--font-mono)"><?= htmlspecialchars($d['telefono'] ?? '—') ?></div>
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

<!-- ══ PANEL CLIENTE SELECCIONADO ════════════════════════════ -->
<?php if ($deudorPre): ?>
<div id="panel-cobro">

    <!-- Header cliente -->
    <div class="cob-card" style="margin-bottom:1rem">
        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.85rem">
            <div class="cob-avatar" style="width:48px;height:48px;font-size:1.25rem;flex-shrink:0">
                <?= strtoupper(substr($deudorPre['nombre'], 0, 1)) ?>
            </div>
            <div style="flex:1;min-width:0">
                <div style="font-weight:700;font-size:1rem;line-height:1.2"><?= htmlspecialchars($deudorPre['nombre']) ?></div>
                <div style="font-size:0.7rem;color:var(--muted);font-family:var(--font-mono);margin-top:2px">
                    CC: <?= htmlspecialchars($deudorPre['documento'] ?? '—') ?>
                    <?php if ($deudorPre['telefono']): ?> · <?= htmlspecialchars($deudorPre['telefono']) ?><?php endif; ?>
                    <?php if ($deudorPre['ocupacion']): ?>
                    <div style="margin-top:2px;color:var(--accent);font-weight:600">
                        💼 <?= htmlspecialchars($deudorPre['ocupacion']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <a href="/cobrador/cobrar.php"
               style="color:var(--muted);font-size:1.2rem;text-decoration:none;padding:0.25rem;flex-shrink:0">✕</a>
        </div>

        <!-- Botones de acción -->
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:0.4rem">
            <!-- Nuevo préstamo — bloqueado si es clavo -->
            <?php if ($deudorPre['comportamiento'] !== 'clavo'): ?>
            <a href="/cobrador/prestamo.php?deudor=<?= $deudorPre['id'] ?>"
               style="display:flex;flex-direction:column;align-items:center;gap:0.2rem;padding:0.6rem 0.25rem;background:rgba(124,106,255,.12);border:1px solid rgba(124,106,255,.25);border-radius:var(--radius);text-decoration:none">
                <span style="font-size:1.1rem">💳</span>
                <span style="font-family:var(--font-mono);font-size:0.58rem;color:var(--accent);font-weight:700;text-align:center">PRÉSTAMO</span>
            </a>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;align-items:center;gap:0.2rem;padding:0.6rem 0.25rem;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:var(--radius);position:relative" title="CLAVO — sin nuevos préstamos">
                <span style="font-size:1.1rem">🚫</span>
                <span style="font-family:var(--font-mono);font-size:0.58rem;color:#ef4444;font-weight:700;text-align:center">CLAVO</span>
            </div>
            <?php endif; ?>
            <!-- Llamar -->
            <?php if ($deudorPre['telefono']): ?>
            <a href="tel:<?= htmlspecialchars($deudorPre['telefono']) ?>"
               style="display:flex;flex-direction:column;align-items:center;gap:0.2rem;padding:0.6rem 0.25rem;background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);border-radius:var(--radius);text-decoration:none">
                <span style="font-size:1.1rem">📞</span>
                <span style="font-family:var(--font-mono);font-size:0.58rem;color:#22c55e;font-weight:700">LLAMAR</span>
            </a>
            <!-- WhatsApp -->
            <a href="https://wa.me/<?= $telLimpio ?>?text=<?= urlencode('Hola ' . $deudorPre['nombre'] . ', le contactamos por su préstamo.') ?>"
               target="_blank"
               style="display:flex;flex-direction:column;align-items:center;gap:0.2rem;padding:0.6rem 0.25rem;background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.25);border-radius:var(--radius);text-decoration:none">
                <span style="font-size:1.1rem">💬</span>
                <span style="font-family:var(--font-mono);font-size:0.58rem;color:#25d366;font-weight:700">WHATSAPP</span>
            </a>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;align-items:center;gap:0.2rem;padding:0.6rem 0.25rem;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);opacity:0.4">
                <span style="font-size:1.1rem">📞</span>
                <span style="font-family:var(--font-mono);font-size:0.58rem;color:var(--muted);font-weight:700">SIN TEL</span>
            </div>
            <div style="display:flex;flex-direction:column;align-items:center;gap:0.2rem;padding:0.6rem 0.25rem;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);opacity:0.4">
                <span style="font-size:1.1rem">💬</span>
                <span style="font-family:var(--font-mono);font-size:0.58rem;color:var(--muted);font-weight:700">SIN TEL</span>
            </div>
            <?php endif; ?>
            <!-- Seguimiento -->
            <button onclick="abrirSeguimiento()"
               style="display:flex;flex-direction:column;align-items:center;gap:0.2rem;padding:0.6rem 0.25rem;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.25);border-radius:var(--radius);cursor:pointer">
                <span style="font-size:1.1rem">📋</span>
                <span style="font-family:var(--font-mono);font-size:0.58rem;color:#f59e0b;font-weight:700">GESTIÓN</span>
            </button>
        </div>
    </div>

    <!-- Sin préstamos -->
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

    <!-- ══ PRÉSTAMOS ══ -->
    <?php foreach ($prestamos as $prest):
        $estadoColor = match($prest['estado']) {
            'en_mora'    => '#f97316',
            'en_acuerdo' => '#3b82f6',
            default      => '#22c55e'
        };
        $estadoLabel = match($prest['estado']) {
            'en_mora'    => 'EN MORA',
            'en_acuerdo' => 'ACUERDO',
            default      => 'ACTIVO'
        };
        $ultimoPago = $prest['ultimo_pago']
            ? date('d M Y', strtotime($prest['ultimo_pago']))
            : 'SIN CUOTAS';
        $diasAtraso = (int)($prest['dias_mora'] ?? 0);
        $pct = $prest['cuotas_total'] > 0
            ? round($prest['cuotas_pagadas'] / $prest['cuotas_total'] * 100) : 0;
    ?>
    <div class="cob-card" style="margin-bottom:1rem;border-left:3px solid <?= $estadoColor ?>;border-radius:0 var(--radius) var(--radius) 0;padding:0">

        <!-- Cabecera del préstamo -->
        <div style="padding:0.9rem 1rem 0.75rem">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.75rem">
                <div>
                    <div style="font-family:var(--font-mono);font-size:0.6rem;color:var(--muted);letter-spacing:1px;text-transform:uppercase">
                        Préstamo #<?= $prest['id'] ?>
                    </div>
                    <div style="font-size:1.5rem;font-weight:700;color:<?= $estadoColor ?>;line-height:1.1">
                        $<?= number_format($prest['monto_prestado'], 0, ',', '.') ?>
                    </div>
                </div>
                <span style="background:<?= $estadoColor ?>22;color:<?= $estadoColor ?>;font-family:var(--font-mono);font-size:0.65rem;font-weight:700;padding:0.3rem 0.6rem;border-radius:20px;border:1px solid <?= $estadoColor ?>55">
                    <?= $estadoLabel ?>
                </span>
            </div>

            <!-- Tabla de datos al estilo de la imagen -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.3rem 0;border-top:1px solid var(--border);padding-top:0.65rem">
                <?php
                $filas = [
                    ['Valor venta',   '$' . number_format($prest['monto_prestado'], 0, ',', '.')],
                    ['Saldo actual',  '$' . number_format($prest['saldo_pendiente'], 0, ',', '.')],
                    ['Valor cuota',   '$' . number_format($prest['valor_cuota'], 0, ',', '.')],
                    ['Fecha inicio',  date('d M Y', strtotime($prest['fecha_inicio']))],
                    ['Frecuencia',    ucfirst($prest['frecuencia_pago'])],
                    ['Ult. abono',    $ultimoPago],
                    ['Días atraso',   $diasAtraso],
                ];
                foreach ($filas as [$label, $valor]):
                ?>
                <div style="font-size:0.72rem;color:var(--muted);font-family:var(--font-mono);padding:0.2rem 0"><?= $label ?></div>
                <div style="font-size:0.82rem;font-weight:600;text-align:right;padding:0.2rem 0;color:<?= $label === 'Días atraso' && $diasAtraso > 0 ? '#f97316' : 'var(--text)' ?>">
                    <?= $valor ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Barra progreso -->
            <div style="margin-top:0.65rem">
                <div style="background:var(--border);border-radius:4px;height:4px;overflow:hidden">
                    <div style="background:<?= $estadoColor ?>;width:<?= $pct ?>%;height:100%;border-radius:4px"></div>
                </div>
                <div style="font-size:0.6rem;color:var(--muted);font-family:var(--font-mono);margin-top:3px">
                    <?= $prest['cuotas_pagadas'] ?>/<?= $prest['cuotas_total'] ?> cuotas pagadas
                </div>
            </div>
        </div>

        <!-- Botones VER CUOTAS | ABONAR | RENOVAR -->
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;border-top:1px solid var(--border)">
            <button onclick="toggleCuotas(<?= $prest['id'] ?>)"
                    id="btn-cuotas-<?= $prest['id'] ?>"
                    style="padding:0.75rem;background:transparent;border:none;border-right:1px solid var(--border);color:var(--accent);font-family:var(--font-mono);font-size:0.72rem;font-weight:700;cursor:pointer;letter-spacing:0.5px">
                🧾 VER CUOTAS
            </button>
            <button onclick="abonarPrestamo(<?= $prest['id'] ?>)"
                    style="padding:0.75rem;background:transparent;border:none;border-right:1px solid var(--border);color:#22c55e;font-family:var(--font-mono);font-size:0.72rem;font-weight:700;cursor:pointer;letter-spacing:0.5px">
                💰 ABONAR
            </button>
            <?php if ($deudorPre['comportamiento'] !== 'clavo'): ?>
            <a href="/cobrador/renovar.php?prestamo=<?= $prest['id'] ?>"
               style="display:flex;align-items:center;justify-content:center;padding:0.75rem;background:transparent;color:#f59e0b;font-family:var(--font-mono);font-size:0.72rem;font-weight:700;text-decoration:none;letter-spacing:0.5px">
                🔄 RENOVAR
            </a>
            <?php else: ?>
            <div style="display:flex;align-items:center;justify-content:center;padding:0.75rem;color:var(--muted);font-family:var(--font-mono);font-size:0.72rem;font-weight:700;opacity:0.4">
                RENOVAR
            </div>
            <?php endif; ?>
        </div>

        <!-- Cuotas (ocultas por defecto) -->
        <div id="cuotas-<?= $prest['id'] ?>" style="display:none;border-top:1px solid var(--border)">
            <?php if (empty($prest['cuotas'])): ?>
            <div style="padding:1rem;text-align:center;color:var(--muted);font-family:var(--font-mono);font-size:0.75rem">Sin cuotas generadas</div>
            <?php else: ?>
            <!-- Sub-filtro dentro de cuotas -->
            <div style="display:flex;gap:0;border-bottom:1px solid var(--border)">
                <?php foreach (['vencidas'=>'VENCIDAS','pendientes'=>'PENDIENTES','todas'=>'TODAS'] as $sf=>$slabel): ?>
                <button onclick="filtrarCuotas(<?= $prest['id'] ?>, '<?= $sf ?>')"
                        id="sf-<?= $prest['id'] ?>-<?= $sf ?>"
                        style="flex:1;padding:0.5rem 0;background:<?= $sf==='vencidas'?'var(--accent)':'transparent' ?>;color:<?= $sf==='vencidas'?'#fff':'var(--muted)' ?>;border:none;border-right:1px solid var(--border);font-family:var(--font-mono);font-size:0.6rem;font-weight:700;cursor:pointer">
                    <?= $slabel ?>
                </button>
                <?php endforeach; ?>
            </div>

            <div id="lista-cuotas-<?= $prest['id'] ?>" style="padding:0.5rem">
            <?php foreach ($prest['cuotas'] as $c):
                $esPagada  = $c['estado'] === 'pagado';
                $esParcial = $c['estado'] === 'parcial';
                $esVencida = !$esPagada && $c['fecha_vencimiento'] < date('Y-m-d');
                $diasMora  = $esVencida
                    ? (new DateTime())->diff(new DateTime($c['fecha_vencimiento']))->days : 0;
                $claseEstado = $esPagada ? 'pagada' : ($esVencida ? 'vencida' : 'pendiente');
            ?>
            <div class="cuota-item cuota-<?= $claseEstado ?>"
                 data-prestamo="<?= $prest['id'] ?>"
                 style="display:flex;align-items:center;justify-content:space-between;
                        padding:0.6rem 0.5rem;margin-bottom:0.3rem;
                        border-radius:var(--radius);
                        background:<?= $esPagada ? 'rgba(34,197,94,.06)' : ($esVencida ? 'rgba(249,115,22,.08)' : 'var(--bg)') ?>;
                        border:1px solid <?= $esPagada ? 'rgba(34,197,94,.2)' : ($esVencida ? 'rgba(249,115,22,.3)' : 'var(--border)') ?>;
                        opacity:<?= $esPagada ? '0.7' : '1' ?>;
                        cursor:<?= $esPagada ? 'default' : 'pointer' ?>"
                 <?= !$esPagada ? "onclick=\"abrirPago({$c['id']},{$c['saldo_cuota']},{$prest['id']})\"" : '' ?>>

                <div>
                    <div style="font-weight:600;font-size:0.82rem;display:flex;align-items:center;gap:0.4rem">
                        Cuota #<?= $c['numero_cuota'] ?>
                        <?php if ($esPagada): ?>
                            <span style="color:#22c55e;font-size:0.75rem">✓ Pagada</span>
                        <?php elseif ($esParcial): ?>
                            <span style="color:#f59e0b;font-size:0.7rem;font-family:var(--font-mono)">Parcial</span>
                        <?php elseif ($esVencida): ?>
                            <span style="color:#f97316;font-size:0.7rem;font-family:var(--font-mono)"><?= $diasMora ?>d mora</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:0.65rem;color:var(--muted);font-family:var(--font-mono)">
                        <?= date('d M Y', strtotime($c['fecha_vencimiento'])) ?>
                    </div>
                </div>

                <div style="text-align:right">
                    <div style="font-size:0.95rem;font-weight:700;color:<?= $esPagada?'#22c55e':($esVencida?'#f97316':'var(--accent)') ?>">
                        $<?= number_format($c['saldo_cuota'], 0, ',', '.') ?>
                    </div>
                    <?php if (!$esPagada): ?>
                    <div style="font-size:0.6rem;color:var(--muted)">Toca →</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- .cob-card préstamo -->
    <?php endforeach; ?>

    <!-- ══ PANEL DE PAGO (slide up) ══ -->
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
        <input type="hidden" id="pago-cuota-id">
        <input type="hidden" id="pago-prestamo-id">
        <button id="btn-pagar" class="cob-btn cob-btn-success" onclick="registrarPago()">
            💰 REGISTRAR PAGO
        </button>
    </div>
    <div id="overlay-pago" onclick="cerrarPago()"
         style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:199"></div>

    <!-- ══ PANEL SEGUIMIENTO (slide up) ══ -->
    <div id="form-seguimiento-wrap" style="display:none;position:fixed;bottom:0;left:0;right:0;
         background:var(--card);border-top:2px solid #f59e0b;padding:1.25rem 1rem 2rem;
         z-index:200;box-shadow:0 -4px 20px rgba(0,0,0,.3)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
            <div>
                <div style="font-family:var(--font-mono);font-size:0.63rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px">Registrar gestión</div>
                <div style="font-weight:600;font-size:0.9rem"><?= htmlspecialchars($deudorPre['nombre'] ?? '') ?></div>
            </div>
            <button onclick="cerrarSeguimiento()" style="background:none;border:none;color:var(--muted);font-size:1.4rem;cursor:pointer">✕</button>
        </div>

        <!-- Tipo de gestión — botones grandes -->
        <div style="margin-bottom:0.75rem">
            <div style="font-family:var(--font-mono);font-size:0.65rem;color:var(--muted);margin-bottom:0.4rem;text-transform:uppercase">Tipo</div>
            <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:0.35rem" id="tipos-gestion">
                <?php foreach (['llamada'=>'📞','visita'=>'🚶','whatsapp'=>'💬','acuerdo'=>'🤝','nota'=>'📝'] as $t=>$ic): ?>
                <button onclick="selTipo('<?= $t ?>')" id="tipo-<?= $t ?>"
                        style="padding:0.6rem 0.2rem;border-radius:var(--radius);border:1px solid var(--border);background:var(--bg);cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:0.2rem">
                    <span style="font-size:1.1rem"><?= $ic ?></span>
                    <span style="font-family:var(--font-mono);font-size:0.55rem;color:var(--muted);font-weight:600"><?= strtoupper($t) ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Resultado -->
        <div style="margin-bottom:0.75rem">
            <div style="font-family:var(--font-mono);font-size:0.65rem;color:var(--muted);margin-bottom:0.4rem;text-transform:uppercase">Resultado</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.35rem" id="resultados-gestion">
                <?php foreach (['contactado'=>'Contactado','no_contesto'=>'No contestó','promesa_pago'=>'Promesa de pago','sin_resultado'=>'Sin resultado'] as $r=>$rl): ?>
                <button onclick="selResultado('<?= $r ?>')" id="res-<?= $r ?>"
                        style="padding:0.5rem;border-radius:var(--radius);border:1px solid var(--border);background:var(--bg);cursor:pointer;font-family:var(--font-mono);font-size:0.68rem;color:var(--muted);font-weight:600">
                    <?= $rl ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Nota -->
        <div class="field-lg" style="margin-bottom:0.75rem">
            <label>Nota <span style="color:var(--muted);font-weight:400">(opcional)</span></label>
            <textarea id="seg-nota" rows="2"
                      style="width:100%;padding:0.65rem;border-radius:var(--radius);border:1px solid var(--border);background:var(--bg);color:var(--text);font-size:0.9rem;resize:none"
                      placeholder="Observaciones..."></textarea>
        </div>

        <input type="hidden" id="seg-tipo" value="nota">
        <input type="hidden" id="seg-resultado" value="">

        <button id="btn-seguimiento" class="cob-btn" onclick="registrarSeguimiento()"
                style="background:#f59e0b;color:#fff;width:100%">
            📋 REGISTRAR GESTIÓN
        </button>
    </div>
    <div id="overlay-seguimiento" onclick="cerrarSeguimiento()"
         style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:199"></div>

</div><!-- #panel-cobro -->
<?php endif; ?>

<?php
$deudorId_js = (int)($deudorPre['id'] ?? 0);
$cuotasData = [];
foreach ($prestamos as $prest) {
    foreach ($prest['cuotas'] as $c) {
        if ($c['estado'] !== 'pagado') {
            $cuotasData[$prest['id']] = [
                'id'          => $c['id'],
                'saldo'       => (float)$c['saldo_cuota'],
                'numero'      => $c['numero_cuota'],
                'vencimiento' => $c['fecha_vencimiento'],
            ];
            break;
        }
    }
}
error_log(print_r($cuotasData, true));
$cuotasJson = json_encode($cuotasData);

$extraScript = <<<JS
<script>
const CUOTAS_DATA = $cuotasJson;
// ─── Filtro lista buscador ────────────────────────────────────
let filtroActual = 'todos';

function setFiltro(f) {
    filtroActual = f;
    ['todos','con','sin'].forEach(k => {
        const btn = document.getElementById('f-' + k);
        if (!btn) return;
        const activo = k === f;
        btn.style.background  = activo ? 'var(--accent)' : 'transparent';
        btn.style.color       = activo ? '#fff' : 'var(--muted)';
        btn.style.borderColor = activo ? 'var(--accent)' : 'var(--border)';
    });
    aplicarFiltros();
}

function filtrarBusqueda(q) { aplicarFiltros(q); }

function aplicarFiltros(q) {
    const busq  = (q !== undefined ? q : document.getElementById('buscador-deudor').value).toLowerCase().trim();
    document.querySelectorAll('#lista-busqueda .deudor-item').forEach(item => {
        const mb = !busq || item.dataset.nombre.includes(busq);
        const mf = filtroActual === 'todos' || item.dataset.tiene === filtroActual;
        item.style.display = (mb && mf) ? 'flex' : 'none';
    });
    document.getElementById('lista-busqueda').style.display = 'block';
}

function abonarPrestamo(prestId) {
    const cuota = CUOTAS_DATA[prestId];
    if (!cuota) {
        alert('No hay cuotas pendientes en este préstamo');
        return;
    }
    abrirPago(cuota.id, cuota.saldo, prestId);
    document.getElementById('pago-label').textContent =
        'Cuota #' + cuota.numero + ' · Vence: ' + cuota.vencimiento;
}

// ─── Toggle cuotas ────────────────────────────────────────────
function toggleCuotas(prestId) {
    const panel = document.getElementById('cuotas-' + prestId);
    const btn   = document.getElementById('btn-cuotas-' + prestId);
    const abierto = panel.style.display !== 'none';
    panel.style.display = abierto ? 'none' : 'block';
    btn.textContent     = abierto ? 'VER CUOTAS' : 'OCULTAR';
    // Al abrir mostrar vencidas por defecto
    if (!abierto) filtrarCuotas(prestId, 'vencidas');
}

// ─── Filtro cuotas dentro del préstamo ───────────────────────
function filtrarCuotas(prestId, sub) {
    ['vencidas','pendientes','todas'].forEach(s => {
        const btn = document.getElementById('sf-' + prestId + '-' + s);
        if (!btn) return;
        const activo = s === sub;
        btn.style.background = activo ? 'var(--accent)' : 'transparent';
        btn.style.color      = activo ? '#fff' : 'var(--muted)';
    });

    document.querySelectorAll('#lista-cuotas-' + prestId + ' .cuota-item').forEach(el => {
        const clase = el.classList;
        let visible = false;
        if (sub === 'todas')      visible = true;
        if (sub === 'vencidas')   visible = clase.contains('cuota-vencida');
        if (sub === 'pendientes') visible = clase.contains('cuota-vencida') || clase.contains('cuota-pendiente');
        el.style.display = visible ? 'flex' : 'none';
    });
}

// ─── Panel de pago ────────────────────────────────────────────
function abrirPago(cuotaId, saldo, prestId) {
    document.getElementById('pago-cuota-id').value    = cuotaId;
    document.getElementById('pago-prestamo-id').value = prestId;
    document.getElementById('pago-monto').value       = saldo;
    document.getElementById('pago-label').textContent = 'Cuota · \$' + saldo.toLocaleString('es-CO');
    document.getElementById('form-pago-wrap').style.display = 'block';
    document.getElementById('overlay-pago').style.display   = 'block';
    setTimeout(() => {
        document.getElementById('pago-monto').focus();
        document.getElementById('pago-monto').select();
    }, 100);
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
    btn.textContent = '⏳ Guardando...'; btn.disabled = true;

    try {
        const res  = await fetch('/api/prestamos.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({
                action: 'pagar', prestamo_id: parseInt(prestId),
                cuota_id: parseInt(cuotaId), monto_pagado: monto,
                metodo_pago: metodo, fecha_pago: fecha
            })
        });
        const data = await res.json();
        if (data.ok) {
            btn.textContent = '✓ Registrado'; btn.style.background = '#22c55e';
            setTimeout(() => location.reload(), 900);
        } else {
            alert(data.msg || 'Error'); btn.textContent = '💰 REGISTRAR PAGO'; btn.disabled = false;
        }
    } catch(e) {
        alert('Error de conexión'); btn.textContent = '💰 REGISTRAR PAGO'; btn.disabled = false;
    }
}

// ─── Panel seguimiento ────────────────────────────────────────
function abrirSeguimiento() {
    // Reset
    selTipo('nota'); selResultado('');
    document.getElementById('seg-nota').value = '';
    document.getElementById('form-seguimiento-wrap').style.display = 'block';
    document.getElementById('overlay-seguimiento').style.display   = 'block';
}

function cerrarSeguimiento() {
    document.getElementById('form-seguimiento-wrap').style.display = 'none';
    document.getElementById('overlay-seguimiento').style.display   = 'none';
}

function selTipo(t) {
    document.getElementById('seg-tipo').value = t;
    document.querySelectorAll('#tipos-gestion button').forEach(btn => {
        const activo = btn.id === 'tipo-' + t;
        btn.style.background  = activo ? 'var(--accent)' : 'var(--bg)';
        btn.style.borderColor = activo ? 'var(--accent)' : 'var(--border)';
        btn.querySelector('span:last-child').style.color = activo ? '#fff' : 'var(--muted)';
    });
}

function selResultado(r) {
    document.getElementById('seg-resultado').value = r;
    document.querySelectorAll('#resultados-gestion button').forEach(btn => {
        const activo = btn.id === 'res-' + r;
        btn.style.background  = activo ? 'rgba(245,158,11,.2)' : 'var(--bg)';
        btn.style.borderColor = activo ? '#f59e0b' : 'var(--border)';
        btn.style.color       = activo ? '#f59e0b' : 'var(--muted)';
    });
}

async function registrarSeguimiento() {
    const tipo      = document.getElementById('seg-tipo').value;
    const resultado = document.getElementById('seg-resultado').value;
    const nota      = document.getElementById('seg-nota').value.trim() || tipo;
    const btn       = document.getElementById('btn-seguimiento');

    btn.textContent = '⏳ Guardando...'; btn.disabled = true;

    try {
        const res  = await fetch('/api/deudores.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({
                action: 'gestion', deudor_id: {$deudorId_js},
                tipo, resultado: resultado || null, nota,
                fecha_gestion: new Date().toISOString().slice(0, 10)
            })
        });
        const data = await res.json();
        if (data.ok) {
            btn.textContent = '✓ Registrado'; btn.style.background = '#22c55e';
            setTimeout(() => cerrarSeguimiento(), 1000);
            setTimeout(() => {
                btn.textContent = '📋 REGISTRAR GESTIÓN';
                btn.style.background = '#f59e0b';
                btn.disabled = false;
            }, 1100);
        } else {
            alert(data.msg || 'Error'); btn.textContent = '📋 REGISTRAR GESTIÓN'; btn.disabled = false;
        }
    } catch(e) {
        alert('Error de conexión'); btn.textContent = '📋 REGISTRAR GESTIÓN'; btn.disabled = false;
    }
}
</script>
JS;

require_once __DIR__ . '/footer.php';
?>