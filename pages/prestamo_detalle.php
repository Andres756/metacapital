<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
if (!canDo('puede_ver_prestamos')) { include __DIR__ . '/403.php'; exit; }

$db    = getDB();
$cobro = cobroActivo();
$id    = (int)($_GET['id'] ?? 0);

// Sin filtro de cobro_id — el admin puede ver cualquier préstamo
$stmt = $db->prepare("
    SELECT p.*, d.nombre AS deudor_nombre, d.telefono AS deudor_tel, d.id AS deudor_id,
        co.nombre AS cobro_nombre,
        padre.id AS padre_id, padre_d.nombre AS padre_deudor
    FROM prestamos p
    JOIN deudores d            ON d.id   = p.deudor_id
    JOIN cobros co             ON co.id  = p.cobro_id
    LEFT JOIN prestamos padre  ON padre.id = p.prestamo_padre_id
    LEFT JOIN deudores padre_d ON padre_d.id = padre.deudor_id
    WHERE p.id=?
");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { header('Location: /pages/prestamos.php'); exit; }

// Verificar que el usuario tiene acceso al cobro de ese préstamo
if ($_SESSION['rol'] !== 'superadmin') {
    $chk = $db->prepare("SELECT 1 FROM usuario_cobro WHERE usuario_id=? AND cobro_id=?");
    $chk->execute([$_SESSION['usuario_id'], $p['cobro_id']]);
    if (!$chk->fetch()) { include __DIR__ . '/403.php'; exit; }
}

// Cuotas
$stmtC = $db->prepare("SELECT * FROM cuotas WHERE prestamo_id=? ORDER BY numero_cuota ASC");
$stmtC->execute([$id]);
$cuotas = $stmtC->fetchAll();

// Pagos
$stmtPag = $db->prepare("
    SELECT pg.*, cu.numero_cuota, u.nombre AS usuario
    FROM pagos pg
    JOIN cuotas cu   ON cu.id = pg.cuota_id
    LEFT JOIN usuarios u ON u.id = pg.usuario_id
    WHERE pg.prestamo_id=? ORDER BY pg.fecha_pago DESC
");
$stmtPag->execute([$id]);
$pagos = $stmtPag->fetchAll();
$tienePagos = count(array_filter($pagos, fn($pg) => !$pg['anulado'])) > 0;

// Gestiones
$stmtG = $db->prepare("
    SELECT gc.*, u.nombre AS usuario FROM gestiones_cobro gc
    LEFT JOIN usuarios u ON u.id=gc.usuario_id
    WHERE gc.prestamo_id=? ORDER BY gc.fecha_gestion DESC LIMIT 10
");
$stmtG->execute([$id]);
$gestiones = $stmtG->fetchAll();

// Hijo (si fue renovado/refinanciado)
$stmtHijo = $db->prepare("SELECT id, estado, monto_prestado, tipo_origen FROM prestamos WHERE prestamo_padre_id=? LIMIT 1");
$stmtHijo->execute([$id]);
$hijo = $stmtHijo->fetch();

$pct = count($cuotas) > 0 ? round(count(array_filter($cuotas, fn($c)=>$c['estado']==='pagado')) / count($cuotas) * 100) : 0;

$estadoClass = match($p['estado']) {
    'activo'                  => 'badge-purple',
    'en_mora'                 => 'badge-orange',
    'en_acuerdo'              => 'badge-blue',
    'pagado'                  => 'badge-green',
    'renovado','refinanciado' => 'badge-muted',
    'incobrable'              => 'badge-red',
    default                   => 'badge-muted'
};

$pageTitle   = 'Préstamo #' . $id;
$pageSection = 'Préstamos / #' . $id;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="breadcrumb">
  <a href="/pages/prestamos.php">Préstamos</a>
  <span class="sep">›</span>
  <a href="/pages/deudor_detalle.php?id=<?= $p['deudor_id'] ?>"><?= htmlspecialchars($p['deudor_nombre']) ?></a>
  <span class="sep">›</span>
  <span class="current">#<?= $id ?></span>
</div>

<div class="page-header page-header-row">
  <div>
    <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.35rem;flex-wrap:wrap">
      <h1 style="font-size:1.8rem">PRÉSTAMO #<?= $id ?></h1>
      <span class="badge <?= $estadoClass ?>"><?= strtoupper($p['estado']) ?></span>
      <?php if ($p['tipo_origen'] !== 'nuevo'): ?>
      <span class="badge badge-muted"><?= strtoupper($p['tipo_origen']) ?></span>
      <?php endif; ?>
      <span class="badge badge-muted" style="font-size:0.62rem"><?= htmlspecialchars($p['cobro_nombre']) ?></span>
    </div>
    <p>// <?= htmlspecialchars($p['deudor_nombre']) ?> · <?= htmlspecialchars($p['deudor_tel'] ?? '—') ?></p>
  </div>
  <div class="btn-group">
    <?php if (canDo('puede_registrar_pago') && in_array($p['estado'],['activo','en_mora','en_acuerdo'])): ?>
    <button class="btn btn-success" onclick="openModal('modal-pago')">💰 Registrar Pago</button>
    <?php endif; ?>
    <?php if (canDo('puede_editar_prestamo') && in_array($p['estado'],['activo','en_mora','en_acuerdo'])): ?>
    <button class="btn btn-primary" onclick="openModal('modal-renovar')">🔄 Renovar</button>
    <button class="btn btn-warning" onclick="openModal('modal-acuerdo')">📋 Acuerdo de pago</button>
    <?php endif; ?>
    <?php if (canDo('puede_editar_prestamo') && $p['estado'] !== 'anulado' && $p['estado'] !== 'pagado' && !$tienePagos): ?>
    <button class="btn btn-info" onclick="openModal('modal-editar-prestamo')">✏ Editar</button>
    <?php endif; ?>
    <?php if (canDo('puede_anular_prestamo') && $p['estado'] !== 'anulado' && $p['estado'] !== 'pagado' && !$tienePagos): ?>
    <button class="btn btn-danger" onclick="confirmarAnular(<?= $p['id'] ?>)">🗑 Anular</button>
    <?php endif; ?>
  </div>
</div>

<?php if ($p['padre_id'] || $hijo): ?>
<div class="alert alert-info mb-2" style="font-family:var(--font-mono);font-size:0.72rem">
  <?php if ($p['padre_id']): ?>
  ← Viene del préstamo <a href="/pages/prestamo_detalle.php?id=<?=$p['padre_id']?>">#<?=$p['padre_id']?></a>
  <?php endif; ?>
  <?php if ($hijo): ?>
  → Continuó en <a href="/pages/prestamo_detalle.php?id=<?=$hijo['id']?>">#<?=$hijo['id']?></a>
  (<?= strtoupper($hijo['tipo_origen']) ?> · <?= fmt($hijo['monto_prestado']) ?>)
  <?php endif; ?>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem">
  <div>
    <div class="card mb-2">
      <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1rem">
          <div>
            <div class="text-xs text-muted">Prestado</div>
            <div style="font-family:var(--font-display);font-size:1.5rem;color:var(--accent)"><?= fmt($p['monto_prestado']) ?></div>
          </div>
          <div>
            <div class="text-xs text-muted">Interés</div>
            <div style="font-family:var(--font-display);font-size:1.5rem;color:var(--warn)">+<?= fmt($p['interes_calculado']) ?></div>
            <div class="text-xs text-muted"><?= $p['interes_valor'] ?><?= $p['tipo_interes']==='porcentaje'?'%':' fijo' ?></div>
          </div>
          <div>
            <div class="text-xs text-muted">Total a pagar</div>
            <div style="font-family:var(--font-display);font-size:1.5rem"><?= fmt($p['total_a_pagar']) ?></div>
          </div>
          <div>
            <div class="text-xs text-muted">Saldo pendiente</div>
            <div style="font-family:var(--font-display);font-size:1.5rem;color:<?= $p['saldo_pendiente']>0?'var(--warn)':'var(--accent)' ?>"><?= fmt($p['saldo_pendiente']) ?></div>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:0.75rem">
          <div class="progress" style="flex:1;height:8px">
            <div class="progress-bar <?= $p['estado']==='en_mora'?'orange':'' ?>" style="width:<?= $pct ?>%"></div>
          </div>
          <span class="text-mono text-xs text-muted"><?= $pct ?>% pagado</span>
        </div>
        <div style="display:flex;gap:2rem;margin-top:0.75rem;font-family:var(--font-mono);font-size:0.72rem;color:var(--muted)">
          <span>📅 Inicio: <?= date('d M Y',strtotime($p['fecha_inicio'])) ?></span>
          <span>🏁 Fin: <?= date('d M Y',strtotime($p['fecha_fin_esperada'])) ?></span>
          <span>📆 <?= ucfirst($p['frecuencia_pago']) ?> · <?= $p['num_cuotas'] ?> cuotas de <?= fmt($p['valor_cuota']) ?></span>
        </div>
      </div>
    </div>

    <?php if ($p['estado']==='en_acuerdo' && $p['nota_acuerdo']): ?>
    <div class="alert alert-blue mb-2">
      📋 <strong>Acuerdo activo:</strong> <?= htmlspecialchars($p['nota_acuerdo']) ?>
      — Compromiso: <strong><?= $p['fecha_compromiso'] ?></strong>
    </div>
    <?php endif; ?>

    <!-- CRONOGRAMA -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">CRONOGRAMA DE CUOTAS</span>
        <span class="text-mono text-xs text-muted"><?= count(array_filter($cuotas,fn($c)=>$c['estado']==='pagado')) ?>/<?= count($cuotas) ?> pagadas</span>
      </div>
      <div style="padding:0.5rem 0">
        <?php foreach ($cuotas as $c):
          $vencida  = $c['estado'] !== 'pagado' && $c['fecha_vencimiento'] < date('Y-m-d');
          $dotClass = $c['estado']==='pagado' ? 'paid' : ($vencida ? 'vencida' : 'pending');
          $rowClass = $c['estado']==='pagado' ? 'paid' : ($vencida ? 'vencida' : '');
        ?>
        <div class="schedule-row <?= $rowClass ?>">
          <div class="schedule-dot <?= $dotClass ?>"></div>
          <div style="font-family:var(--font-mono);font-size:0.72rem;color:var(--muted);width:24px"><?= $c['numero_cuota'] ?></div>
          <div style="flex:1">
            <span class="text-mono"><?= date('d M Y',strtotime($c['fecha_vencimiento'])) ?></span>
            <?php if ($vencida): ?>
            <span style="font-size:0.65rem;color:var(--warn);margin-left:0.5rem">
              <?= (new DateTime())->diff(new DateTime($c['fecha_vencimiento']))->days ?> días vencida
            </span>
            <?php endif; ?>
            <?php if ($c['monto_pagado'] > 0 && $c['estado']==='parcial'): ?>
            <span style="font-size:0.65rem;color:var(--accent2);margin-left:0.5rem">Abono: <?= fmt($c['monto_pagado']) ?></span>
            <?php endif; ?>
          </div>
          <div style="text-align:right;margin-right:0.75rem">
            <div class="text-mono fw-600 <?= $c['estado']==='pagado'?'green':($vencida?'orange':'') ?>">
              <?= fmt($c['saldo_cuota']) ?>
            </div>
            <?php if ($c['fecha_pago']): ?>
            <div class="text-xs text-muted">Pagó: <?= date('d M',strtotime($c['fecha_pago'])) ?></div>
            <?php endif; ?>
          </div>
          <?php if ($c['estado'] !== 'pagado' && canDo('puede_registrar_pago') && in_array($p['estado'],['activo','en_mora','en_acuerdo'])): ?>
          <button class="btn btn-success btn-sm" onclick="pagarCuota(<?=$c['id']?>, <?=$c['saldo_cuota']?>)">Pagar</button>
          <?php elseif ($c['estado']==='pagado'): ?>
          <span style="font-family:var(--font-mono);font-size:0.65rem;color:var(--accent)">✓ PAGADO</span>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if (!empty($pagos)): ?>
    <div class="card mt-2">
      <div class="card-header"><span class="card-title">PAGOS RECIBIDOS</span></div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Fecha</th><th>Cuota</th><th>Monto</th><th>Método</th><th>Usuario</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($pagos as $pg): ?>
            <tr style="<?= $pg['anulado'] ? 'opacity:0.45;text-decoration:line-through' : '' ?>">
              <td class="text-mono"><?= date('d M Y',strtotime($pg['fecha_pago'])) ?></td>
              <td class="text-muted">#<?= $pg['numero_cuota'] ?></td>
              <td class="<?= $pg['anulado'] ? 'text-muted' : 'green' ?> text-mono fw-600"><?= fmt($pg['monto_pagado']) ?></td>
              <td><span class="badge badge-muted"><?= ucfirst($pg['metodo_pago'] ?? 'efectivo') ?></span></td>
              <td class="text-muted text-xs"><?= htmlspecialchars($pg['usuario']??'—') ?></td>
              <td>
                <?php if ($pg['anulado']): ?>
                  <span class="badge" style="background:rgba(239,68,68,.15);color:#ef4444">ANULADO</span>
                <?php elseif (canDo('puede_anular_pago')): ?>
                  <button class="btn btn-ghost btn-sm" style="color:#ef4444;padding:2px 8px"
                    onclick="anularPago(<?= $pg['id'] ?>, <?= $pg['numero_cuota'] ?>)">✕</button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- PANEL LATERAL -->
  <div>
    <div class="card mb-2">
      <div class="card-header"><span class="card-title">DATOS</span></div>
      <div class="card-body">
        <?php
        $rows = [
          'Deudor'  => '<a href="/pages/deudor_detalle.php?id='.$p['deudor_id'].'">'.htmlspecialchars($p['deudor_nombre']).'</a>',
          'Cobro'   => htmlspecialchars($p['cobro_nombre']),
          'Teléfono'=> htmlspecialchars($p['deudor_tel']??'—'),
          'Mora'    => $p['dias_mora']>0 ? '<span class="text-orange">'.$p['dias_mora'].' días</span>' : '<span class="text-green">Sin mora</span>',
        ];
        foreach ($rows as $l => $v):
        ?>
        <div style="display:flex;justify-content:space-between;padding:0.4rem 0;border-bottom:1px solid var(--border);font-family:var(--font-mono);font-size:0.72rem">
          <span style="color:var(--muted)"><?= $l ?></span>
          <span><?= $v ?></span>
        </div>
        <?php endforeach; ?>
        <?php if ($p['observaciones']): ?>
        <div class="mt-1 text-xs text-muted"><?= htmlspecialchars($p['observaciones']) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <span class="card-title">GESTIONES</span>
        <button class="btn btn-ghost btn-sm" onclick="openModal('modal-gestion')">+ Agregar</button>
      </div>
      <?php if (empty($gestiones)): ?>
      <div class="empty-state" style="padding:1.5rem"><span class="empty-icon" style="font-size:1.5rem">◎</span><p>Sin gestiones</p></div>
      <?php else: ?>
      <div style="padding:0.25rem 0">
        <?php foreach ($gestiones as $g): ?>
        <div style="padding:0.65rem 1rem;border-bottom:1px solid rgba(37,37,53,0.5)">
          <div style="display:flex;justify-content:space-between;margin-bottom:0.2rem">
            <span class="badge badge-muted"><?= ucfirst($g['tipo']) ?></span>
            <span class="text-mono text-xs text-muted"><?= date('d M',strtotime($g['fecha_gestion'])) ?></span>
          </div>
          <div style="font-size:0.78rem;color:var(--text-soft)"><?= htmlspecialchars($g['nota']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- MODALES — igual que el original -->
<div class="modal-overlay" id="modal-pago">
  <div class="modal">
    <div class="modal-header">
      <h2>REGISTRAR PAGO</h2>
      <button class="modal-close" onclick="closeModal('modal-pago')">✕</button>
    </div>
    <div class="modal-body">
      <form id="form-pago">
        <input type="hidden" name="prestamo_id" value="<?= $id ?>">
        <div class="form-grid mb-2">
          <div class="field field-span2">
            <label>Cuota a pagar</label>
            <select name="cuota_id" id="select-cuota" onchange="setCuotaMonto(this)">
              <?php foreach ($cuotas as $c): if ($c['estado']==='pagado') continue; ?>
              <option value="<?=$c['id']?>" data-monto="<?=$c['saldo_cuota']?>">
                Cuota #<?=$c['numero_cuota']?> — <?=date('d M Y',strtotime($c['fecha_vencimiento']))?> — <?=fmt($c['saldo_cuota'])?>
                <?= $c['estado']==='parcial' ? ' (PARCIAL)' : '' ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field"><label>Monto recibido <span class="required">*</span></label>
            <input type="number" id="pago-monto" name="monto_pagado" step="1000" min="1" required></div>
          <div class="field"><label>Fecha de pago</label>
            <input type="date" name="fecha_pago" value="<?= date('Y-m-d') ?>"></div>
          <div class="field"><label>Método de pago</label>
            <select name="metodo_pago"><option value="efectivo">Efectivo</option><option value="banco">Banco</option></select></div>
          <div class="field field-span2"><label>Observación</label>
            <input type="text" name="observacion" placeholder="Opcional"></div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-pago')">Cancelar</button>
      <button class="btn btn-primary" id="btn-pagar" onclick="registrarPago()">REGISTRAR PAGO</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modal-renovar">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <h2>RENOVAR PRÉSTAMO #<?= $p['id'] ?></h2>
      <button class="modal-close" onclick="closeModal('modal-renovar')">✕</button>
    </div>
    <div class="modal-body">
      <form id="form-renovar">
        <input type="hidden" name="prestamo_id" value="<?= $p['id'] ?>">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.75rem;margin-bottom:1.25rem">
          <div style="padding:0.75rem;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);text-align:center">
            <div style="font-family:var(--font-mono);font-size:0.62rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.25rem">Saldo pendiente</div>
            <div style="font-weight:700;color:var(--warn);font-size:1rem"><?= fmt($p['saldo_pendiente']) ?></div>
          </div>
          <div style="padding:0.75rem;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);text-align:center">
            <div style="font-family:var(--font-mono);font-size:0.62rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.25rem">Monto original</div>
            <div style="font-weight:700;font-size:1rem"><?= fmt($p['monto_prestado']) ?></div>
          </div>
          <div style="padding:0.75rem;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);text-align:center">
            <div style="font-family:var(--font-mono);font-size:0.62rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.25rem">Sale de caja</div>
            <div style="font-weight:700;font-size:1rem;color:var(--danger)" id="ren-diferencia-display">—</div>
          </div>
        </div>
        <div class="form-grid">
          <div class="field">
            <label>Monto de renovación <span class="required">*</span></label>
            <input type="number" id="ren-monto" name="monto_renovacion"
                   value="<?= max($p['monto_prestado'], $p['saldo_pendiente']) ?>"
                   min="<?= $p['saldo_pendiente'] ?>" step="1000" oninput="calcRenovacion()" required>
            <div style="font-size:0.68rem;color:var(--muted);margin-top:0.25rem;font-family:var(--font-mono)">Mínimo: <?= fmt($p['saldo_pendiente']) ?></div>
          </div>
          <div class="field"><label>Método de pago</label>
            <select name="metodo_pago"><option value="efectivo">Efectivo</option><option value="banco">Banco</option></select></div>
          <div class="field">
            <label id="ren-label-interes">Interés (%)</label>
            <div style="display:flex;gap:0.5rem">
              <select id="ren-tipo-int" name="tipo_interes" onchange="calcRenovacion()" style="width:130px;flex-shrink:0">
                <option value="porcentaje" <?= $p['tipo_interes']==='porcentaje'?'selected':'' ?>>% Porcentaje</option>
                <option value="valor_fijo" <?= $p['tipo_interes']==='valor_fijo'?'selected':'' ?>>$ Valor fijo</option>
              </select>
              <input type="number" id="ren-interes" name="interes_valor" value="<?= $p['interes_valor'] ?>" step="0.5" min="0" oninput="calcRenovacion()" style="flex:1">
            </div>
          </div>
          <div class="field"><label>Número de cuotas</label>
            <input type="number" id="ren-cuotas" name="num_cuotas" value="<?= $p['num_cuotas'] ?>" min="1" oninput="calcRenovacion()"></div>
          <div class="field"><label>Frecuencia de pago</label>
            <select id="ren-frecuencia" name="frecuencia_pago" onchange="onRenFrecuenciaChange()">
              <option value="diario"    <?= $p['frecuencia_pago']==='diario'   ?'selected':'' ?>>Diario</option>
              <option value="semanal"   <?= $p['frecuencia_pago']==='semanal'  ?'selected':'' ?>>Semanal</option>
              <option value="quincenal" <?= $p['frecuencia_pago']==='quincenal'?'selected':'' ?>>Quincenal</option>
              <option value="mensual"   <?= $p['frecuencia_pago']==='mensual'  ?'selected':'' ?>>Mensual</option>
            </select></div>
          <div class="field" id="ren-domingo-wrap" style="display:<?= $p['frecuencia_pago']==='diario'?'flex':'none' ?>;align-items:center;padding-top:1.5rem">
            <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-weight:normal;margin:0">
              <input type="checkbox" id="ren-domingos" name="omitir_domingos" value="1" <?= $p['omitir_domingos'] ? 'checked' : '' ?>>
              Omitir domingos
            </label></div>
          <div class="field field-span2"><label>Nota <span style="color:var(--muted);font-weight:400">(opcional)</span></label>
            <input type="text" name="nota_gestion" placeholder="Ej: Cliente solicita más plazo"></div>
        </div>
        <div id="ren-preview" style="display:none;margin-top:0.75rem;padding:0.85rem 1rem;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius)">
          <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.5rem;text-align:center;font-size:0.78rem">
            <div><div style="font-family:var(--font-mono);font-size:0.6rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px">Interés</div><div style="font-weight:700" id="ren-prev-interes">—</div></div>
            <div><div style="font-family:var(--font-mono);font-size:0.6rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px">Total</div><div style="font-weight:700" id="ren-prev-total">—</div></div>
            <div><div style="font-family:var(--font-mono);font-size:0.6rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px">Valor cuota</div><div style="font-weight:700;color:var(--accent)" id="ren-prev-cuota">—</div></div>
            <div><div style="font-family:var(--font-mono);font-size:0.6rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px">Fecha fin</div><div style="font-weight:700" id="ren-prev-fecha">—</div></div>
          </div>
        </div>
        <div id="ren-aviso-sinpagos" style="display:none;margin-top:0.75rem;padding:0.75rem;background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.4);border-radius:var(--radius);font-size:0.78rem;color:#d97706">
          ⚠ Este préstamo no tiene pagos registrados. El plazo venció el <strong><?= $p['fecha_fin_esperada'] ? date('d/m/Y', strtotime($p['fecha_fin_esperada'])) : '—' ?></strong>.
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-renovar')">Cancelar</button>
      <button class="btn btn-primary" id="btn-renovar" onclick="guardarRenovacion()">RENOVAR PRÉSTAMO</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modal-acuerdo">
  <div class="modal" style="max-width:420px">
    <div class="modal-header"><h2>ACUERDO DE PAGO</h2><button class="modal-close" onclick="closeModal('modal-acuerdo')">✕</button></div>
    <div class="modal-body">
      <form id="form-acuerdo">
        <input type="hidden" name="prestamo_id" value="<?= $p['id'] ?>">
        <div class="form-grid">
          <div class="field field-span2"><label>Nota del acuerdo <span class="required">*</span></label>
            <input type="text" name="nota_acuerdo" required placeholder="Ej: Llama el viernes, paga el lunes..."></div>
          <div class="field"><label>Fecha compromiso</label>
            <input type="date" name="fecha_compromiso" value="<?= date('Y-m-d', strtotime('+7 days')) ?>"></div>
          <div class="field field-span2"><label>Nota de gestión <span style="color:var(--muted);font-weight:400">(opcional)</span></label>
            <textarea name="nota_gestion" placeholder="Descripción adicional..." style="min-height:60px"></textarea></div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-acuerdo')">Cancelar</button>
      <button class="btn btn-warning" onclick="guardarAcuerdo()">REGISTRAR ACUERDO</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modal-gestion">
  <div class="modal">
    <div class="modal-header"><h2>NUEVA GESTIÓN</h2><button class="modal-close" onclick="closeModal('modal-gestion')">✕</button></div>
    <div class="modal-body">
      <form id="form-gestion-rapida">
        <input type="hidden" name="prestamo_id" value="<?= $id ?>">
        <input type="hidden" name="deudor_id" value="<?= $p['deudor_id'] ?>">
        <input type="hidden" name="action" value="gestion">
        <div class="form-grid mb-2">
          <div class="field"><label>Tipo</label>
            <select name="tipo"><option value="llamada">Llamada</option><option value="visita">Visita</option><option value="whatsapp">WhatsApp</option><option value="acuerdo">Acuerdo</option><option value="nota">Nota</option></select></div>
          <div class="field"><label>Resultado</label>
            <select name="resultado"><option value="contactado">Contactado</option><option value="no_contesto">No contestó</option><option value="promesa_pago">Promesa de pago</option><option value="sin_resultado">Sin resultado</option></select></div>
          <div class="field"><label>Fecha</label><input type="date" name="fecha_gestion" value="<?= date('Y-m-d') ?>"></div>
          <div class="field field-span2"><label>Nota <span class="required">*</span></label>
            <textarea name="nota" required placeholder="Describe la gestión..."></textarea></div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-gestion')">Cancelar</button>
      <button class="btn btn-primary" onclick="guardarGestionRapida()">GUARDAR</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modal-editar-prestamo">
  <div class="modal">
    <div class="modal-header"><h2>EDITAR PRÉSTAMO #<?= $p['id'] ?></h2><button class="modal-close" onclick="closeModal('modal-editar-prestamo')">✕</button></div>
    <div class="modal-body">
      <div class="alert alert-warning" style="margin-bottom:1rem;font-size:0.8rem">⚠ Solo se puede editar mientras no tenga pagos. Las cuotas se regenerarán.</div>
      <form id="form-editar-prestamo">
        <input type="hidden" name="id" value="<?= $p['id'] ?>">
        <div class="form-grid">
          <div class="field"><label>Monto prestado <span class="required">*</span></label><input type="number" name="monto_prestado" value="<?= $p['monto_prestado'] ?>" step="1000" min="1" required></div>
          <div class="field"><label>Tipo interés</label><select name="tipo_interes"><option value="porcentaje" <?= $p['tipo_interes']==='porcentaje'?'selected':'' ?>>Porcentaje (%)</option><option value="valor_fijo" <?= $p['tipo_interes']==='valor_fijo'?'selected':'' ?>>Valor fijo ($)</option></select></div>
          <div class="field"><label>Interés</label><input type="number" name="interes_valor" value="<?= $p['interes_valor'] ?>" step="0.01" min="0"></div>
          <div class="field"><label>Frecuencia</label><select name="frecuencia_pago"><option value="diario" <?= $p['frecuencia_pago']==='diario'?'selected':'' ?>>Diario</option><option value="semanal" <?= $p['frecuencia_pago']==='semanal'?'selected':'' ?>>Semanal</option><option value="quincenal" <?= $p['frecuencia_pago']==='quincenal'?'selected':'' ?>>Quincenal</option><option value="mensual" <?= $p['frecuencia_pago']==='mensual'?'selected':'' ?>>Mensual</option></select></div>
          <div class="field"><label>Número de cuotas</label><input type="number" name="num_cuotas" value="<?= $p['num_cuotas'] ?>" min="1" required></div>
          <div class="field"><label>Fecha inicio</label><input type="date" name="fecha_inicio" value="<?= $p['fecha_inicio'] ?>"></div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-editar-prestamo')">Cancelar</button>
      <button class="btn btn-primary" onclick="guardarEdicionPrestamo()">GUARDAR CAMBIOS</button>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
function setCuotaMonto(sel) {
    var opt = sel.options[sel.selectedIndex];
    document.getElementById('pago-monto').value = opt.dataset.monto || '';
}
document.addEventListener('DOMContentLoaded', function() {
    var sel = document.getElementById('select-cuota');
    if (sel && sel.options.length) setCuotaMonto(sel);
    calcRenovacion();
});

function pagarCuota(cuotaId, monto) {
    document.getElementById('select-cuota').value = cuotaId;
    document.getElementById('pago-monto').value   = monto;
    openModal('modal-pago');
}

async function registrarPago() {
    var btn  = document.getElementById('btn-pagar');
    var data = Object.fromEntries(new FormData(document.getElementById('form-pago')));
    if (!data.monto_pagado || parseFloat(data.monto_pagado) <= 0) { toast('Ingresa el monto', 'error'); return; }
    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>';
    var res = await apiPost('/api/prestamos.php', Object.assign({ action: 'pagar' }, data));
    btn.disabled = false; btn.innerHTML = 'REGISTRAR PAGO';
    if (res.ok) { toast('Pago registrado'); closeModal('modal-pago'); setTimeout(function(){ location.reload(); }, 800); }
    else toast(res.msg || 'Error', 'error');
}

async function anularPago(pagoId, numeroCuota) {
    if (!confirm('¿Anular el pago de la cuota #' + numeroCuota + '?')) return;
    const res = await apiPost('/api/pagos.php', { action: 'anular', pago_id: pagoId });
    if (res.ok) { toast(res.msg); setTimeout(() => location.reload(), 1200); }
    else toast(res.msg || 'Error', 'error');
}

async function confirmarAnular(id) {
    if (!confirm('¿Anular este préstamo? Esta acción no se puede deshacer.')) return;
    var res = await apiPost('/api/prestamos.php', { action: 'anular', id: id });
    if (res.ok) { toast(res.msg); setTimeout(() => location.reload(), 1200); }
    else toast(res.msg || 'Error', 'error');
}

async function guardarEdicionPrestamo() {
    var data = Object.fromEntries(new FormData(document.getElementById('form-editar-prestamo')));
    data.action = 'editar';
    var btn = event.target;
    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Guardando...';
    var res = await apiPost('/api/prestamos.php', data);
    btn.disabled = false; btn.innerHTML = 'GUARDAR CAMBIOS';
    if (res.ok) { toast(res.msg); closeModal('modal-editar-prestamo'); setTimeout(() => location.reload(), 1000); }
    else toast(res.msg || 'Error', 'error');
}

async function guardarGestionRapida() {
    var data = Object.fromEntries(new FormData(document.getElementById('form-gestion-rapida')));
    if (!data.nota || !data.nota.trim()) { toast('La nota es obligatoria', 'error'); return; }
    var res = await apiPost('/api/deudores.php', data);
    if (res.ok) { toast('Gestión registrada'); closeModal('modal-gestion'); setTimeout(function(){ location.reload(); }, 800); }
    else toast(res.msg || 'Error', 'error');
}

const REN_SALDO       = <?= (float)$p['saldo_pendiente'] ?>;
const REN_TIENE_PAGOS = <?= count($pagos) > 0 ? 'true' : 'false' ?>;
const MONTO_ORIGINAL  = <?= (float)$p['monto_prestado'] ?>;

function onRenFrecuenciaChange() {
    const freq = document.getElementById('ren-frecuencia').value;
    document.getElementById('ren-domingo-wrap').style.display = freq === 'diario' ? 'flex' : 'none';
    if (freq !== 'diario') document.getElementById('ren-domingos').checked = false;
    calcRenovacion();
}

function calcRenovacion() {
    const monto   = parseFloat(document.getElementById('ren-monto').value)   || 0;
    const tipoInt = document.getElementById('ren-tipo-int').value;
    const intVal  = parseFloat(document.getElementById('ren-interes').value)  || 0;
    const cuotas  = parseInt(document.getElementById('ren-cuotas').value)     || 1;
    const freq    = document.getElementById('ren-frecuencia').value;

    document.getElementById('ren-label-interes').textContent =
        tipoInt === 'porcentaje' ? 'Interés (%)' : 'Interés ($ valor fijo total)';

    const dif    = monto - REN_SALDO;
    const divDif = document.getElementById('ren-diferencia-display');
    if (!monto)             { divDif.textContent = '—'; divDif.style.color = ''; }
    else if (Math.abs(dif) < 1) { divDif.textContent = '$0 sin movimiento'; divDif.style.color = 'var(--muted)'; }
    else { divDif.textContent = '- ' + fmt(dif) + ' sale de caja'; divDif.style.color = 'var(--danger)'; }

    document.getElementById('ren-aviso-sinpagos').style.display = !REN_TIENE_PAGOS ? 'block' : 'none';
    if (monto < REN_SALDO) { document.getElementById('ren-preview').style.display = 'none'; return; }

    const intCalc  = tipoInt === 'porcentaje' ? monto * (intVal / 100) : intVal;
    const total    = monto + intCalc;
    const valCuota = cuotas > 0 ? Math.round(total / cuotas) : total;
    const diasMap  = {diario:1, semanal:7, quincenal:15, mensual:30};
    const fechaFin = new Date();
    fechaFin.setDate(fechaFin.getDate() + (diasMap[freq] || 30) * cuotas);

    document.getElementById('ren-prev-interes').textContent = fmt(intCalc);
    document.getElementById('ren-prev-total').textContent   = fmt(total);
    document.getElementById('ren-prev-cuota').textContent   = fmt(valCuota) + ' × ' + cuotas;
    document.getElementById('ren-prev-fecha').textContent   =
        fechaFin.toLocaleDateString('es-CO',{day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('ren-preview').style.display = 'block';
}

async function guardarRenovacion() {
    const monto = parseFloat(document.getElementById('ren-monto').value) || 0;
    if (!monto || monto <= 0) { toast('Ingresa el monto de renovación', 'error'); return; }
    if (monto < REN_SALDO)    { toast('El monto no puede ser menor al saldo pendiente (' + fmt(REN_SALDO) + ')', 'error'); return; }
    if (!REN_TIENE_PAGOS && !confirm('⚠ Sin pagos registrados y plazo vencido. ¿Confirmas renovar?')) return;
    if (monto > MONTO_ORIGINAL && !confirm('⚠ Monto mayor al original. ¿Confirmas?')) return;
    const dif = monto - REN_SALDO;
    if (dif > 0 && !confirm('Sale de caja: ' + fmt(dif) + '. ¿Confirmas?')) return;

    const btn = document.getElementById('btn-renovar');
    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Procesando...';
    const data = Object.fromEntries(new FormData(document.getElementById('form-renovar')));
    data.action = 'renovar';
    const res = await apiPost('/api/prestamos.php', data);
    btn.disabled = false; btn.innerHTML = 'RENOVAR PRÉSTAMO';
    if (res.ok) { toast(res.msg); setTimeout(() => window.location = '/pages/prestamo_detalle.php?id=' + res.nuevo_id, 1200); }
    else toast(res.msg || 'Error al renovar', 'error');
}

async function guardarAcuerdo() {
    const data = Object.fromEntries(new FormData(document.getElementById('form-acuerdo')));
    if (!(data.nota_acuerdo || '').trim()) { toast('La nota es obligatoria', 'error'); return; }
    data.action = 'acuerdo';
    const res = await apiPost('/api/prestamos.php', data);
    if (res.ok) { toast(res.msg); closeModal('modal-acuerdo'); setTimeout(() => location.reload(), 900); }
    else toast(res.msg || 'Error', 'error');
}
</script>