<?php
// ============================================================
// liquidacion_contenido.php
// ============================================================

// Estado de sesión del cobrador (tiene session_token = está activo en el sistema)
$cobradorConSesion = !empty($cobrador) && !empty($cobrador['session_token']);
?>

<?php if (!$cobrador): ?>
<div class="alert alert-danger">No hay cobrador asignado a este cobro.</div>
<?php else: ?>

<!-- PASO 1 — Inicio del día -->
<div class="card mb-2">
    <div class="card-header">
        <span class="card-title">PASO 1 — INICIO DEL DÍA</span>
        <?php if ($liquidacion['estado'] === 'borrador'): ?>
        <span class="badge badge-orange">En curso</span>
        <?php else: ?>
        <span class="badge badge-green">Cerrada</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start">

            <!-- Estado cobrador -->
            <div>
                <div style="font-family:var(--font-mono);font-size:0.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.75rem">
                    Estado del cobrador
                </div>
                <div style="display:flex;align-items:center;gap:1rem;padding:1rem;background:var(--bg);border-radius:var(--radius);border:1px solid var(--border)">
                    <div class="avatar"><?= strtoupper(substr($cobrador['nombre'],0,1)) ?></div>
                    <div style="flex:1">
                        <div style="font-weight:600"><?= htmlspecialchars($cobrador['nombre']) ?></div>
                        <div style="font-size:0.75rem;color:var(--muted);font-family:var(--font-mono)">Cobrador</div>
                    </div>
                    <div>
                        <?php if ($liquidacion['estado'] === 'cerrada'): ?>
                            <span style="font-family:var(--font-mono);font-size:0.72rem;color:var(--muted)">
                                🔒 BLOQUEADO
                            </span>
                        <?php elseif (!empty($liquidacion['cobrador_bloqueado'])): ?>
                            <div style="display:flex;flex-direction:column;gap:0.35rem;align-items:flex-end">
                                <span style="font-family:var(--font-mono);font-size:0.72rem;color:#ef4444;font-weight:700">
                                    🔒 BLOQUEADO
                                </span>
                                <button class="btn btn-success btn-sm"
                                        onclick="toggleBloqueo(<?= $liquidacion['id'] ?>)">
                                    🔓 Desbloquear
                                </button>
                            </div>
                        <?php else: ?>
                            <div style="display:flex;flex-direction:column;gap:0.35rem;align-items:flex-end">
                                <span style="font-family:var(--font-mono);font-size:0.72rem;color:<?= $cobradorConSesion ? '#22c55e' : '#f59e0b' ?>">
                                    <?= $cobradorConSesion ? '🟢 EN RUTA' : '⏳ SIN SESIÓN' ?>
                                </span>
                                <button class="btn btn-danger btn-sm"
                                        onclick="toggleBloqueo(<?= $liquidacion['id'] ?>)">
                                    🔒 Bloquear
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($liquidacion['estado'] === 'borrador'): ?>
                    <?php if ($cobradorConSesion): ?>
                    <div style="margin-top:0.75rem">
                        <button class="btn btn-danger btn-sm" onclick="bloquearCobrador()"
                                style="width:100%">
                            🔒 Bloquear cobrador para liquidar
                        </button>
                        <div style="font-size:0.65rem;color:var(--muted);font-family:var(--font-mono);margin-top:0.35rem">
                            Bloquéalo antes de empezar a liquidar para que no registre más movimientos.
                        </div>
                    </div>
                    <?php else: ?>
                    <div style="font-size:0.68rem;color:#22c55e;font-family:var(--font-mono);margin-top:0.5rem">
                        ✓ Cobrador bloqueado — puedes liquidar con tranquilidad.
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Base entregada -->
            <div>
                <div style="font-family:var(--font-mono);font-size:0.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.75rem">
                    Base entregada al cobrador
                </div>
                <div style="font-size:1.8rem;font-weight:700;color:var(--accent);font-family:var(--font-mono)">
                    <?= fmt($base_trabajado) ?>
                </div>
                <div style="font-size:0.72rem;color:var(--muted);margin-top:0.25rem">
                    Caja disponible: <?= fmt($base) ?> · Entregado: <?= fmt($base_trabajado) ?>
                </div>

                <?php if ($liquidacion['estado'] === 'borrador'): ?>
                <!-- Segunda entrega de base -->
                <div style="margin-top:1rem;padding:0.85rem;background:rgba(124,106,255,.06);border:1px solid rgba(124,106,255,.2);border-radius:var(--radius)">
                    <div style="font-family:var(--font-mono);font-size:0.62rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.5rem">
                        Entrega adicional de base
                    </div>
                    <div style="display:flex;gap:0.5rem;align-items:center">
                        <input type="number" id="input-base-adicional"
                               placeholder="Monto adicional"
                               min="1" step="10000"
                               style="flex:1;padding:0.5rem 0.75rem;border:1px solid var(--border);border-radius:var(--radius);background:var(--bg);color:var(--text);font-family:var(--font-mono);font-size:0.85rem">
                        <button class="btn btn-primary btn-sm" onclick="entregarBaseAdicional()">
                            + Entregar
                        </button>
                    </div>
                    <div style="font-size:0.65rem;color:var(--muted);font-family:var(--font-mono);margin-top:0.35rem">
                        Se suma a la base trabajado del día
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Stats del día -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem">
    <div class="stat-card" style="border-color:#22c55e33">
        <div class="stat-label">PAGOS COBRADOS</div>
        <div class="stat-value" style="color:#22c55e"><?= fmt($totalPagos) ?></div>
        <div class="stat-sub"><?= count($pagosHoy) ?> pagos</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">PRÉSTAMOS</div>
        <div class="stat-value" style="color:var(--accent)"><?= fmt($totalPrestamos) ?></div>
        <div class="stat-sub"><?= count($prestamosHoy) ?> préstamos</div>
    </div>
    <div class="stat-card orange">
        <div class="stat-label">GASTOS APROBADOS</div>
        <div class="stat-value"><?= fmt($totalGastos) ?></div>
        <div class="stat-sub"><?= count(array_filter($gastosHoy, fn($g) => $g['estado']==='aprobado')) ?> aprobados</div>
    </div>
    <div class="stat-card" style="border-color:#f9731633">
        <div class="stat-label">PAPELERÍA</div>
        <div class="stat-value" style="color:#f97316"><?= fmt($totalPapeleria) ?></div>
        <div class="stat-sub">Saldo separado</div>
    </div>
    <div class="stat-card" style="border-color:#22c55e55;background:rgba(34,197,94,.04)">
        <div class="stat-label">EFECTIVO ESPERADO</div>
        <div class="stat-value" style="color:#22c55e"><?= fmt($efectivo_esperado) ?></div>
        <div class="stat-sub">(Pagos + Base) − Préstamos − Gastos</div>
    </div>
</div>

<!-- PASO 2: Gastos -->
<div class="card mb-2">
    <div class="card-header">
        <span class="card-title">PASO 2 — GASTOS DEL DÍA</span>
        <span class="text-mono text-xs text-muted"><?= count($gastosHoy) ?> registros</span>
    </div>
    <?php if (empty($gastosHoy)): ?>
    <div class="empty-state" style="padding:1.5rem">
        <span class="empty-icon" style="font-size:1.5rem">◎</span>
        <p>Sin gastos registrados hoy</p>
    </div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Categoría</th><th>Descripción</th><th>Monto</th><th>Estado</th>
                <?php if ($liquidacion['estado'] === 'borrador'): ?><th></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($gastosHoy as $g): ?>
                <tr>
                    <td><?= htmlspecialchars($g['categoria_nombre'] ?? '—') ?></td>
                    <td class="text-muted" style="font-size:0.8rem"><?= htmlspecialchars($g['descripcion'] ?? '') ?></td>
                    <td class="text-mono fw-600 orange"><?= fmt($g['monto']) ?></td>
                    <td>
                        <?php if ($g['estado'] === 'aprobado'): ?>
                        <span class="badge badge-green">Aprobado</span>
                        <?php elseif ($g['estado'] === 'rechazado'): ?>
                        <span class="badge badge-muted">Rechazado</span>
                        <?php else: ?>
                        <span class="badge badge-orange">Pendiente</span>
                        <?php endif; ?>
                    </td>
                    <?php if ($liquidacion['estado'] === 'borrador'): ?>
                    <td>
                        <?php if ($g['estado'] === 'pendiente'): ?>
                        <div class="btn-group">
                            <button class="btn btn-success btn-sm" onclick="aprobarGasto(<?= $g['id'] ?>)">✓ Aprobar</button>
                            <button class="btn btn-danger btn-sm" onclick="rechazarGasto(<?= $g['id'] ?>)">✕ Rechazar</button>
                        </div>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- PASO 3: Préstamos del día -->
<?php if (!empty($prestamosHoy)): ?>
<div class="card mb-2">
    <div class="card-header">
        <span class="card-title">PRÉSTAMOS DEL DÍA</span>
        <span class="text-mono text-xs text-muted"><?= count($prestamosHoy) ?> préstamos</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Deudor</th><th>Monto</th><th>Cuotas</th><th>Papelería %</th><th>Papelería $</th></tr>
            </thead>
            <tbody>
                <?php foreach ($prestamosHoy as $pr): ?>
                <tr>
                    <td style="font-weight:600"><?= htmlspecialchars($pr['deudor']) ?></td>
                    <td class="text-mono"><?= fmt($pr['monto_prestado']) ?></td>
                    <td class="text-muted"><?= $pr['num_cuotas'] ?> × <?= fmt($pr['valor_cuota']) ?></td>
                    <td>
                        <?php if ($liquidacion['estado'] === 'borrador'): ?>
                        <input type="number" id="pap-pct-<?= $pr['id'] ?>"
                               value="<?= $pr['papeleria_pct'] ?? $cobroData['papeleria_pct'] ?>"
                               data-monto="<?= $pr['monto_prestado'] ?>"
                               min="0" max="100" step="1"
                               style="width:70px;padding:0.3rem 0.5rem;border:1px solid var(--border);border-radius:var(--radius);background:var(--bg);color:var(--text);font-family:var(--font-mono);font-size:0.8rem"
                               oninput="actualizarPapeleria(<?= $pr['id'] ?>, <?= $pr['monto_prestado'] ?>)">%
                        <?php else: ?>
                        <?= $pr['papeleria_pct'] ?? 0 ?>%
                        <?php endif; ?>
                    </td>
                    <td class="text-mono" style="color:#f97316" id="pap-monto-<?= $pr['id'] ?>">
                        <?= fmt($pr['papeleria_monto'] ?? 0) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($liquidacion['estado'] === 'borrador' && !empty($prestamosHoy)): ?>
    <div style="padding:0.75rem 1rem;display:flex;align-items:center;gap:1rem;border-top:1px solid var(--border)">
        <span style="font-family:var(--font-mono);font-size:0.72rem;color:var(--muted)">Aplicar % global:</span>
        <input type="number" id="pap-pct-global" placeholder="%" min="0" max="100" step="1"
               style="width:80px;padding:0.3rem 0.5rem;border:1px solid var(--border);border-radius:var(--radius);background:var(--bg);color:var(--text);font-family:var(--font-mono);font-size:0.8rem">
        <button class="btn btn-ghost btn-sm" onclick="aplicarPctGlobal()">Aplicar a todos</button>
        <span style="font-family:var(--font-mono);font-size:0.82rem;font-weight:700;color:#f97316">
            Total: <span id="total-papeleria-display"><?= fmt($totalPapeleria) ?></span>
        </span>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- PASO 4: Pagos del día -->
<?php if (!empty($pagosHoy)): ?>
<div class="card mb-2">
    <div class="card-header">
        <span class="card-title">PAGOS COBRADOS</span>
        <span class="text-mono text-xs text-muted"><?= fmt($totalPagos) ?></span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Deudor</th><th>Cuota</th><th>Monto</th><th>Método</th></tr>
            </thead>
            <tbody>
                <?php foreach ($pagosHoy as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['deudor']) ?></td>
                    <td class="text-muted">#<?= $p['numero_cuota'] ?></td>
                    <td class="text-mono fw-600 green"><?= fmt($p['monto_pagado']) ?></td>
                    <td><span class="badge badge-muted"><?= ucfirst($p['metodo_pago'] ?? 'efectivo') ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- PASO 5: Cierre -->
<?php if ($liquidacion['estado'] === 'borrador'): ?>
<div class="card" style="border-color:rgba(124,106,255,.3)">
    <div class="card-header">
        <span class="card-title">PASO 3 — CIERRE DEL DÍA</span>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
            <div style="padding:1rem;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);text-align:center">
                <div style="font-family:var(--font-mono);font-size:0.62rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.25rem">Efectivo esperado</div>
                <div style="font-size:1.4rem;font-weight:700;color:#22c55e;font-family:var(--font-mono)"><?= fmt($efectivo_esperado) ?></div>
            </div>
            <div style="padding:1rem;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);text-align:center">
                <div style="font-family:var(--font-mono);font-size:0.62rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.25rem">Papelería a entregar</div>
                <div style="font-size:1.4rem;font-weight:700;color:#f97316;font-family:var(--font-mono)"><?= fmt($totalPapeleria) ?></div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">
            <div class="field">
                <label>Dinero entregado por el cobrador *</label>
                <input type="number" id="input-dinero-entregado"
                       step="1000" min="0"
                       placeholder="0"
                       style="font-size:1.3rem;font-weight:700;text-align:center"
                       oninput="calcularDiferencia()">
            </div>
            <div class="field">
                <label>Papelería entregada</label>
                <input type="number" id="input-papeleria-entregada"
                       step="1000" min="0"
                       value="<?= $totalPapeleria ?>"
                       style="font-size:1.3rem;font-weight:700;text-align:center">
            </div>
        </div>

        <div style="padding:0.85rem 1rem;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:1rem;font-family:var(--font-mono)">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:0.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px">Diferencia</span>
                <span id="display-diferencia" style="font-size:1.1rem;font-weight:700">—</span>
            </div>
            <div id="display-nueva-base" style="font-size:0.72rem;color:var(--muted);font-family:var(--font-mono);margin-top:0.25rem">Nueva base: —</div>
        </div>

        <div class="field mb-2">
            <label>Notas (opcional)</label>
            <textarea id="input-notas" placeholder="Observaciones..." style="min-height:60px"></textarea>
        </div>

        <button class="btn btn-primary btn-lg" onclick="cerrarLiquidacion()">✓ CERRAR LIQUIDACIÓN</button>
    </div>
</div>

<?php else: ?>
<!-- Resumen cerrada -->
<div class="card mb-2" style="border-color:#22c55e44">
    <div class="card-header">
        <span class="card-title" style="color:#22c55e">✓ LIQUIDACIÓN CERRADA</span>
        <span class="text-mono text-xs text-muted"><?= date('d M Y H:i', strtotime($liquidacion['cerrada_at'])) ?></span>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem">
            <?php
            $resumen = [
                ['Base',              $liquidacion['base'],                   'var(--text)'],
                ['Base trabajado',    $liquidacion['base_trabajado'],         'var(--accent)'],
                ['Total pagos',       $liquidacion['total_pagos'],            '#22c55e'],
                ['Total préstamos',   $liquidacion['total_prestamos'],        'var(--accent)'],
                ['Gastos aprobados',  $liquidacion['total_gastos_aprobados'], '#f97316'],
                ['Papelería',         $liquidacion['total_papeleria'],        '#f97316'],
                ['Efectivo esperado', $liquidacion['efectivo_esperado'],      '#22c55e'],
                ['Dinero entregado',  $liquidacion['dinero_entregado'],       '#22c55e'],
                ['Diferencia',        $liquidacion['diferencia'],             (float)$liquidacion['diferencia'] >= 0 ? '#22c55e' : '#ef4444'],
                ['Nueva base',        $liquidacion['nueva_base'],             'var(--accent)'],
            ];
            foreach ($resumen as [$label, $valor, $color]):
            ?>
            <div style="padding:0.75rem;background:var(--bg);border-radius:var(--radius);border:1px solid var(--border)">
                <div style="font-family:var(--font-mono);font-size:0.62rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.3rem"><?= $label ?></div>
                <div style="font-size:1.1rem;font-weight:700;color:<?= $color ?>"><?= fmt($valor) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if ($liquidacion['notas']): ?>
        <div style="margin-top:1rem;padding:0.75rem;background:var(--bg);border-radius:var(--radius);font-size:0.8rem;color:var(--muted)">
            📋 <?= htmlspecialchars($liquidacion['notas']) ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php endif; // fin cobrador ?>