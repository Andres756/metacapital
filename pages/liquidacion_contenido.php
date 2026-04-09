<?php
// ============================================================
// liquidacion_contenido.php
// Se incluye desde liquidacion_detalle.php
// Requiere: $liquidacion, $cobrador, $cobroData, $pagosHoy,
//           $prestamosHoy, $gastosHoy, $totalPagos,
//           $totalPrestamos, $totalGastos, $totalPapeleria,
//           $base, $base_trabajado, $efectivo_esperado, $nueva_base
// ============================================================
?>

<?php if (!$cobrador): ?>
<div class="alert alert-danger">No hay cobrador asignado a este cobro.</div>
<?php else: ?>

<!-- PASO 1 — Estado cobrador + Base -->
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

            <!-- Toggle cobrador -->
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
                    <div style="display:flex;align-items:center;gap:0.5rem">
                        <span style="font-family:var(--font-mono);font-size:0.72rem;color:<?= $cobrador['activo'] ? '#22c55e' : '#ef4444' ?>">
                            <?= $cobrador['activo'] ? 'ACTIVO' : 'INACTIVO' ?>
                        </span>
                        <?php if ($liquidacion['estado'] === 'borrador'): ?>
                        <button class="btn btn-sm <?= $cobrador['activo'] ? 'btn-danger' : 'btn-success' ?>"
                                onclick="toggleCobrador(<?= $cobrador['id'] ?>, <?= $cobrador['activo'] ? 0 : 1 ?>)">
                            <?= $cobrador['activo'] ? 'Inactivar' : 'Activar' ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Base trabajado -->
            <div>
                <div style="font-family:var(--font-mono);font-size:0.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.75rem">
                    Base entregada al cobrador
                </div>
                <div style="font-size:1.8rem;font-weight:700;color:var(--accent);font-family:var(--font-mono)">
                    <?= fmt($base_trabajado) ?>
                </div>
                <div style="font-size:0.72rem;color:var(--muted);margin-top:0.25rem">
                    Base caja: <?= fmt($base) ?> · Entregado: <?= fmt($base_trabajado) ?>
                </div>
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
    <div class="empty-state"><span class="empty-icon">◈</span><p>Sin gastos registrados</p></div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Descripción</th><th>Categoría</th><th>Registrado por</th><th>Monto</th><th>Estado</th>
                    <?php if ($liquidacion['estado'] === 'borrador'): ?><th></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($gastosHoy as $g):
                    $estadoClass = match($g['estado']) {
                        'aprobado'  => 'badge-green',
                        'rechazado' => 'badge-red',
                        default     => 'badge-orange'
                    };
                ?>
                <tr>
                    <td><?= htmlspecialchars($g['descripcion']) ?></td>
                    <td class="text-muted"><?= htmlspecialchars($g['categoria_nombre'] ?? '—') ?></td>
                    <td class="text-muted"><?= htmlspecialchars($g['usuario_nombre'] ?? '—') ?></td>
                    <td class="text-mono fw-600" style="color:#f97316"><?= fmt($g['monto']) ?></td>
                    <td><span class="badge <?= $estadoClass ?>"><?= strtoupper($g['estado']) ?></span></td>
                    <?php if ($liquidacion['estado'] === 'borrador'): ?>
                    <td>
                        <?php if ($g['estado'] === 'pendiente'): ?>
                        <div class="btn-group">
                            <button class="btn btn-success btn-sm" onclick="aprobarGasto(<?= $g['id'] ?>)">✓</button>
                            <button class="btn btn-danger btn-sm"  onclick="rechazarGasto(<?= $g['id'] ?>)">✕</button>
                        </div>
                        <?php elseif ($g['estado'] === 'aprobado'): ?>
                        <button class="btn btn-ghost btn-sm red" onclick="rechazarGasto(<?= $g['id'] ?>)">Rechazar</button>
                        <?php else: ?>
                        <button class="btn btn-ghost btn-sm" onclick="aprobarGasto(<?= $g['id'] ?>)">Aprobar</button>
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

<!-- PASO 3: Pagos -->
<div class="card mb-2">
    <div class="card-header">
        <span class="card-title">PASO 3 — PAGOS COBRADOS</span>
        <span class="text-mono text-xs text-muted"><?= fmt($totalPagos) ?></span>
    </div>
    <?php if (empty($pagosHoy)): ?>
    <div class="empty-state"><span class="empty-icon">◈</span><p>Sin pagos</p></div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Deudor</th><th>Cuota</th><th>Cuenta</th><th>Monto</th><th>Hora</th></tr></thead>
            <tbody>
                <?php foreach ($pagosHoy as $pg): ?>
                <tr>
                    <td><?= htmlspecialchars($pg['deudor']) ?></td>
                    <td class="text-muted">#<?= $pg['numero_cuota'] ?></td>
                    <td class="text-muted"><?= htmlspecialchars($pg['cuenta'] ?? 'Efectivo') ?></td>
                    <td class="text-mono fw-600 green"><?= fmt($pg['monto_pagado']) ?></td>
                    <td class="text-mono text-muted"><?= date('h:i a', strtotime($pg['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- PASO 4: Préstamos + Papelería -->
<div class="card mb-2">
    <div class="card-header">
        <span class="card-title">PASO 4 — PRÉSTAMOS DEL DÍA</span>
        <span class="text-mono text-xs text-muted"><?= fmt($totalPrestamos) ?></span>
    </div>

    <?php if ($liquidacion['estado'] === 'borrador' && !empty($prestamosHoy)): ?>
    <div style="display:flex;align-items:center;gap:1rem;padding:0.75rem 1.25rem;background:var(--bg);border-bottom:1px solid var(--border);flex-wrap:wrap">
        <span style="font-family:var(--font-mono);font-size:0.72rem;color:var(--muted)">% PAPELERÍA GLOBAL:</span>
        <input type="number" id="pap-pct-global"
               value="<?= $cobroData['papeleria_pct'] ?? 10 ?>"
               min="0" max="100" step="0.5"
               style="width:70px;padding:0.35rem 0.5rem;border-radius:var(--radius);border:1px solid var(--border);background:var(--card);color:var(--text);font-family:var(--font-mono)">
        <button class="btn btn-ghost btn-sm" onclick="aplicarPctGlobal()">Aplicar a todos</button>
        <span style="font-family:var(--font-mono);font-size:0.72rem;color:#f97316">
            Total papelería: <strong id="total-papeleria-display"><?= fmt($totalPapeleria) ?></strong>
        </span>
    </div>
    <?php endif; ?>

    <?php if (empty($prestamosHoy)): ?>
    <div class="empty-state"><span class="empty-icon">◈</span><p>Sin préstamos</p></div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Deudor</th><th>Monto</th><th>Cuotas</th><th>Frecuencia</th><th>Hora</th><th>Papelería</th></tr>
            </thead>
            <tbody>
                <?php foreach ($prestamosHoy as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['deudor']) ?></td>
                    <td class="text-mono fw-600" style="color:var(--accent)"><?= fmt($p['monto_prestado']) ?></td>
                    <td class="text-muted"><?= $p['num_cuotas'] ?> × <?= fmt($p['valor_cuota']) ?></td>
                    <td class="text-muted"><?= ucfirst($p['frecuencia_pago']) ?></td>
                    <td class="text-mono text-muted"><?= date('h:i a', strtotime($p['created_at'])) ?></td>
                    <td>
                        <?php if ($liquidacion['estado'] === 'borrador'): ?>
                        <div style="display:flex;align-items:center;gap:0.4rem">
                            <input type="number"
                                   id="pap-pct-<?= $p['id'] ?>"
                                   data-monto="<?= $p['monto_prestado'] ?>"
                                   value="<?= $p['papeleria_pct'] ?? ($cobroData['papeleria_pct'] ?? 10) ?>"
                                   min="0" max="100" step="0.5"
                                   style="width:55px;padding:0.25rem 0.4rem;border-radius:4px;border:1px solid var(--border);background:var(--bg);color:var(--text);font-family:var(--font-mono);font-size:0.8rem"
                                   oninput="actualizarPapeleria(<?= $p['id'] ?>, <?= $p['monto_prestado'] ?>)">
                            <span style="font-family:var(--font-mono);font-size:0.75rem;color:var(--muted)">%</span>
                            <span id="pap-monto-<?= $p['id'] ?>"
                                  style="font-family:var(--font-mono);font-size:0.8rem;color:#f97316;font-weight:600">
                                <?= fmt($p['papeleria_monto'] ?? 0) ?>
                            </span>
                        </div>
                        <?php else: ?>
                        <span class="text-mono" style="color:#f97316;font-weight:600">
                            <?= fmt($p['papeleria_monto'] ?? 0) ?>
                            <span style="color:var(--muted);font-size:0.75rem">(<?= $p['papeleria_pct'] ?? 0 ?>%)</span>
                        </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- PASO 5: Cerrar o resumen -->
<?php if ($liquidacion['estado'] === 'borrador'): ?>
<div class="card mb-2" style="border-color:#22c55e44">
    <div class="card-header"><span class="card-title">PASO 5 — CERRAR LIQUIDACIÓN</span></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem">
            <div style="padding:1rem;background:var(--bg);border-radius:var(--radius);border:1px solid var(--border)">
                <div style="font-family:var(--font-mono);font-size:0.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.4rem">Base</div>
                <div style="font-size:1.3rem;font-weight:700"><?= fmt($base) ?></div>
            </div>
            <div style="padding:1rem;background:var(--bg);border-radius:var(--radius);border:1px solid var(--border)">
                <div style="font-family:var(--font-mono);font-size:0.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.4rem">Base Trabajado</div>
                <div style="font-size:1.3rem;font-weight:700;color:var(--accent)"><?= fmt($base_trabajado) ?></div>
            </div>
            <div style="padding:1rem;background:var(--bg);border-radius:var(--radius);border:1px solid #22c55e33">
                <div style="font-family:var(--font-mono);font-size:0.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.4rem">Efectivo Esperado</div>
                <div style="font-size:1.3rem;font-weight:700;color:#22c55e"><?= fmt($efectivo_esperado) ?></div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
            <div>
                <label style="font-family:var(--font-mono);font-size:0.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;display:block;margin-bottom:0.4rem">
                    Dinero Entregado *
                </label>
                <input type="number" id="input-dinero-entregado"
                       placeholder="0" step="10000" min="0"
                       style="width:100%;padding:0.75rem;font-size:1.3rem;font-weight:700;border-radius:var(--radius);border:1px solid var(--border);background:var(--bg);color:var(--text)"
                       oninput="calcularDiferencia()">
                <div style="font-size:0.72rem;color:var(--muted);margin-top:0.3rem;font-family:var(--font-mono)">Lo que trajo del cobro</div>
            </div>
            <div>
                <label style="font-family:var(--font-mono);font-size:0.72rem;color:#f97316;text-transform:uppercase;letter-spacing:1px;display:block;margin-bottom:0.4rem">
                    Papelería Entregada
                </label>
                <input type="number" id="input-papeleria-entregada"
                       placeholder="0" step="1000" min="0"
                       style="width:100%;padding:0.75rem;font-size:1.3rem;font-weight:700;border-radius:var(--radius);border:1px solid #f9731644;background:var(--bg);color:#f97316">
                <div style="font-size:0.72rem;color:var(--muted);margin-top:0.3rem;font-family:var(--font-mono)">Va a saldo separado</div>
            </div>
            <div>
                <div style="font-family:var(--font-mono);font-size:0.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.4rem">Diferencia</div>
                <div id="display-diferencia" style="font-size:1.8rem;font-weight:700;font-family:var(--font-mono);color:var(--muted)">—</div>
                <div id="display-nueva-base" style="font-size:0.72rem;color:var(--muted);font-family:var(--font-mono);margin-top:0.25rem">Nueva base: —</div>
            </div>
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