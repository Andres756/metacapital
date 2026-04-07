<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
if (!canDo('puede_ver_prestamos')) { include __DIR__ . '/403.php'; exit; }

$db    = getDB();
$cobro = cobroActivo();
$id    = (int)($_GET['id'] ?? 0);
$action= $_GET['action'] ?? '';

$stmt = $db->prepare("
    SELECT p.*, d.nombre AS deudor_nombre, d.telefono AS deudor_tel, d.id AS deudor_id,
        cap.nombre AS capitalista_nombre,
        c.nombre   AS cuenta_nombre,
        padre.id   AS padre_id, padre_d.nombre AS padre_deudor
    FROM prestamos p
    JOIN deudores d          ON d.id  = p.deudor_id
    LEFT JOIN capitalistas cap ON cap.id = p.capitalista_id
    LEFT JOIN cuentas c        ON c.id  = p.cuenta_desembolso_id
    LEFT JOIN prestamos padre  ON padre.id = p.prestamo_padre_id
    LEFT JOIN deudores padre_d ON padre_d.id = padre.deudor_id
    WHERE p.id=? AND p.cobro_id=?
");
$stmt->execute([$id, $cobro]);
$p = $stmt->fetch();
if (!$p) { header('Location: /pages/prestamos.php'); exit; }

// Cuotas
$stmtC = $db->prepare("SELECT * FROM cuotas WHERE prestamo_id=? ORDER BY numero_cuota ASC");
$stmtC->execute([$id]);
$cuotas = $stmtC->fetchAll();

// Pagos (todos, incluyendo anulados para trazabilidad)
$stmtPag = $db->prepare("
    SELECT pg.*, cu.numero_cuota, c.nombre AS cuenta, u.nombre AS usuario
    FROM pagos pg
    JOIN cuotas cu ON cu.id=pg.cuota_id
    LEFT JOIN cuentas c ON c.id=pg.cuenta_id
    LEFT JOIN usuarios u ON u.id=pg.usuario_id
    WHERE pg.prestamo_id=? ORDER BY pg.fecha_pago DESC
");
$stmtPag->execute([$id]);
$pagos = $stmtPag->fetchAll();
$tienePagos = count(array_filter($pagos, fn($p) => !$p['anulado'])) > 0;

// Gestiones
$stmtG = $db->prepare("SELECT gc.*, u.nombre AS usuario FROM gestiones_cobro gc LEFT JOIN usuarios u ON u.id=gc.usuario_id WHERE gc.prestamo_id=? ORDER BY gc.fecha_gestion DESC LIMIT 10");
$stmtG->execute([$id]);
$gestiones = $stmtG->fetchAll();

// Hijo (si fue renovado/refinanciado)
$stmtHijo = $db->prepare("SELECT id, estado, monto_prestado, tipo_origen FROM prestamos WHERE prestamo_padre_id=? LIMIT 1");
$stmtHijo->execute([$id]);
$hijo = $stmtHijo->fetch();

// Cuentas para pago
$cuentas = $db->prepare("SELECT id, nombre FROM cuentas WHERE cobro_id=? AND activa=1");
$cuentas->execute([$cobro]); $cuentas = $cuentas->fetchAll();

$pct = count($cuotas) > 0 ? round(count(array_filter($cuotas, fn($c)=>$c['estado']==='pagado')) / count($cuotas) * 100) : 0;

$estadoClass = match($p['estado']) {
    'activo'       => 'badge-purple',
    'en_mora'      => 'badge-orange',
    'en_acuerdo'   => 'badge-blue',
    'pagado'       => 'badge-green',
    'renovado','refinanciado' => 'badge-muted',
    'incobrable'   => 'badge-red',
    default        => 'badge-muted'
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

<!-- Header -->
<div class="page-header page-header-row">
  <div>
    <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.35rem">
      <h1 style="font-size:1.8rem">PRÉSTAMO #<?= $id ?></h1>
      <span class="badge <?= $estadoClass ?>"><?= strtoupper($p['estado']) ?></span>
      <?php if ($p['tipo_origen'] !== 'nuevo'): ?>
      <span class="badge badge-muted"><?= strtoupper($p['tipo_origen']) ?></span>
      <?php endif; ?>
    </div>
    <p>// <?= htmlspecialchars($p['deudor_nombre']) ?> · <?= htmlspecialchars($p['deudor_tel'] ?? '—') ?></p>
  </div>
  <div class="btn-group">
    <?php if (canDo('puede_registrar_pago') && in_array($p['estado'],['activo','en_mora','en_acuerdo'])): ?>
    <button class="btn btn-success" onclick="openModal('modal-pago')">💰 Registrar Pago</button>
    <?php endif; ?>
    <?php if (canDo('puede_editar_prestamo') && in_array($p['estado'],['activo','en_mora','en_acuerdo'])): ?>
    <button class="btn btn-warning" onclick="openModal('modal-gestionar')">⚠ Gestionar</button>
    <?php endif; ?>
    <?php if (canDo('puede_editar_prestamo') && $p['estado'] !== 'anulado' && $p['estado'] !== 'pagado' && !$tienePagos): ?>
    <button class="btn btn-info" onclick="openModal('modal-editar-prestamo')">✏ Editar</button>
    <?php endif; ?>
    <?php if (canDo('puede_anular_prestamo') && $p['estado'] !== 'anulado' && $p['estado'] !== 'pagado' && !$tienePagos): ?>
    <button class="btn btn-danger" onclick="confirmarAnular(<?= $p['id'] ?>)">🗑 Anular</button>
    <?php endif; ?>
  </div>
</div>

<!-- Trazabilidad -->
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

  <!-- IZQUIERDA: cuotas + pagos -->
  <div>

    <!-- Info préstamo -->
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
          <span>🏁 Fecha fin: <?= date('d M Y',strtotime($p['fecha_fin_esperada'])) ?></span>
          <span>📆 <?= ucfirst($p['frecuencia_pago']) ?> · <?= $p['num_cuotas'] ?> cuotas de <?= fmt($p['valor_cuota']) ?></span>
          <?php if ($p['capitalista_nombre']): ?><span>💰 <?= htmlspecialchars($p['capitalista_nombre']) ?></span><?php endif; ?>
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

    <!-- HISTORIAL DE PAGOS -->
    <?php if (!empty($pagos)): ?>
    <div class="card mt-2">
      <div class="card-header"><span class="card-title">PAGOS RECIBIDOS</span></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Fecha</th><th>Cuota</th><th>Monto</th><th>Cuenta</th><th>Método</th><th>Usuario</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($pagos as $pg): ?>
            <tr style="<?= $pg['anulado'] ? 'opacity:0.45;text-decoration:line-through' : '' ?>">
              <td class="text-mono"><?= date('d M Y',strtotime($pg['fecha_pago'])) ?></td>
              <td class="text-muted">#<?= $pg['numero_cuota'] ?></td>
              <td class="<?= $pg['anulado'] ? 'text-muted' : 'green' ?> text-mono fw-600"><?= fmt($pg['monto_pagado']) ?></td>
              <td><?= htmlspecialchars($pg['cuenta']??'Efectivo') ?></td>
              <td><span class="badge badge-muted"><?= ucfirst($pg['metodo_pago']) ?></span></td>
              <td class="text-muted text-xs"><?= htmlspecialchars($pg['usuario']??'—') ?></td>
              <td>
                <?php if ($pg['anulado']): ?>
                  <span class="badge" style="background:rgba(239,68,68,.15);color:#ef4444">ANULADO</span>
                <?php elseif (canDo('puede_anular_pago')): ?>
                  <button class="btn btn-ghost btn-sm" style="color:#ef4444;padding:2px 8px"
                    onclick="anularPago(<?= $pg['id'] ?>, <?= $pg['numero_cuota'] ?>)"
                    title="Anular pago">✕</button>
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

  <!-- DERECHA: datos + gestiones -->
  <div>
    <div class="card mb-2">
      <div class="card-header"><span class="card-title">DATOS</span></div>
      <div class="card-body">
        <?php
          $rows = [
            'Deudor'       => '<a href="/pages/deudor_detalle.php?id='.$p['deudor_id'].'">'.htmlspecialchars($p['deudor_nombre']).'</a>',
            'Teléfono'     => htmlspecialchars($p['deudor_tel']??'—'),
            'Capitalista'  => htmlspecialchars($p['capitalista_nombre']??'—'),
            'Desembolso'   => htmlspecialchars($p['cuenta_nombre']??'—'),
            'Mora'         => $p['dias_mora']>0 ? '<span class="text-orange">'.$p['dias_mora'].' días</span>' : '<span class="text-green">Sin mora</span>',
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

    <!-- Gestiones -->
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

<!-- ====== MODAL PAGO RÁPIDO ====== -->
<div class="modal-overlay" id="modal-pago">
  <div class="modal">
    <div class="modal-header">
      <h2>REGISTRAR PAGO</h2>
      <button class="modal-close" onclick="closeModal('modal-pago')">✕</button>
    </div>
    <div class="modal-body">
      <form id="form-pago">
        <input type="hidden" name="prestamo_id" value="<?= $id ?>">
        <input type="hidden" id="pago-cuota-id" name="cuota_id" value="">
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
          <div class="field">
            <label>Monto recibido <span class="required">*</span></label>
            <input type="number" id="pago-monto" name="monto_pagado" step="1000" min="1" required>
          </div>
          <div class="field">
            <label>Fecha de pago</label>
            <input type="date" name="fecha_pago" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="field">
            <label>Cuenta</label>
            <select name="cuenta_id">
              <option value="">— Sin asignar —</option>
              <?php foreach ($cuentas as $c): ?>
              <option value="<?=$c['id']?>"><?= htmlspecialchars($c['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Método</label>
            <select name="metodo_pago">
              <option value="efectivo">Efectivo</option>
              <option value="transferencia">Transferencia</option>
              <option value="nequi">Nequi</option>
              <option value="bancolombia">Bancolombia</option>
              <option value="daviplata">Daviplata</option>
            </select>
          </div>
          <div class="field field-span2">
            <label>Observación</label>
            <input type="text" name="observacion" placeholder="Opcional">
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-pago')">Cancelar</button>
      <button class="btn btn-primary" id="btn-pagar" onclick="registrarPago()">REGISTRAR PAGO</button>
    </div>
  </div>
</div>

<!-- ====== MODAL GESTIONAR MORA ====== -->
<div class="modal-overlay" id="modal-gestionar">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h2>GESTIONAR PRÉSTAMO</h2>
      <button class="modal-close" onclick="closeModal('modal-gestionar')">✕</button>
    </div>
    <div class="modal-body">
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-bottom:1.5rem">
        <div class="stat-card" style="cursor:pointer;border-color:var(--accent)" onclick="setAccion('renovar')">
          <div class="stat-label">🔄 Renovar</div>
          <div style="font-size:0.75rem;color:var(--text-soft);margin-top:0.35rem;font-family:var(--font-mono)">
            Paga intereses, capital se reinicia
          </div>
        </div>
        <div class="stat-card orange" style="cursor:pointer" onclick="setAccion('refinanciar')">
          <div class="stat-label">⚠ Refinanciar</div>
          <div style="font-size:0.75rem;color:var(--text-soft);margin-top:0.35rem;font-family:var(--font-mono)">
            No pagó nada, capitalizar mora
          </div>
        </div>
        <div class="stat-card blue" style="cursor:pointer" onclick="setAccion('acuerdo')">
          <div class="stat-label">📋 Acuerdo</div>
          <div style="font-size:0.75rem;color:var(--text-soft);margin-top:0.35rem;font-family:var(--font-mono)">
            Da plazo, registra compromiso
          </div>
        </div>
      </div>

      <form id="form-gestionar">
        <input type="hidden" name="prestamo_id" value="<?= $id ?>">
        <input type="hidden" name="action" id="g-accion" value="">

        <!-- RENOVAR -->
        <div id="panel-renovar" style="display:none">
          <div class="alert alert-success mb-2">
            Capital: <strong><?= fmt($p['monto_prestado']) ?></strong> · Interés a cobrar: <strong><?= fmt($p['interes_calculado']) ?></strong>
          </div>
          <div class="form-grid">
            <div class="field">
              <label>Monto interés recibido</label>
              <input type="number" name="monto_intereses" value="<?= $p['interes_calculado'] ?>" step="1000" oninput="calcPreviewRenovar()">
            </div>
            <div class="field">
              <label>Cuenta donde entró</label>
              <select name="cuenta_id_renovar">
                <?php foreach ($cuentas as $c): ?><option value="<?=$c['id']?>"><?=htmlspecialchars($c['nombre'])?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label>Nuevo % interés (siguiente período)</label>
              <input type="number" name="nuevo_interes" value="<?= $p['interes_valor'] ?>" step="1" oninput="calcPreviewRenovar()">
            </div>
            <div class="field">
              <label>Nuevas cuotas</label>
              <input type="number" name="nuevas_cuotas" value="<?= $p['num_cuotas'] ?>" min="1" oninput="calcPreviewRenovar()">
            </div>
          </div>
          <div id="preview-renovar" style="display:none;margin-top:0.75rem;padding:0.85rem 1rem;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);font-family:var(--font-mono);font-size:0.75rem;line-height:1.8"></div>
        </div>

        <!-- REFINANCIAR -->
        <div id="panel-refinanciar" style="display:none">
          <div class="alert alert-warning mb-2">
            Saldo actual: <strong><?= fmt($p['saldo_pendiente']) ?></strong>
          </div>
          <div class="form-grid">
            <div class="field">
              <label>Nuevo capital base</label>
              <input type="number" name="nuevo_capital" value="<?= $p['saldo_pendiente'] ?>" step="1000" oninput="calcRefin()">
            </div>
            <div class="field">
              <label>Nuevo % interés</label>
              <input type="number" name="nuevo_interes_r" value="<?= $p['interes_valor'] ?>" step="1" oninput="calcRefin()">
            </div>
            <div class="field">
              <label>Abono del cliente ($)</label>
              <input type="number" name="abono_cliente" value="0" step="1000" oninput="calcRefin()">
            </div>
            <div class="field">
              <label>Cuenta abono</label>
              <select name="cuenta_id_refin">
                <option value="">— Sin abono —</option>
                <?php foreach ($cuentas as $c): ?><option value="<?=$c['id']?>"><?=htmlspecialchars($c['nombre'])?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label>Nuevas cuotas</label>
              <input type="number" name="nuevas_cuotas_r" value="1" min="1">
            </div>
          </div>
          <div id="preview-refin" style="margin-top:0.75rem;font-family:var(--font-mono);font-size:0.75rem;color:var(--accent);display:none"></div>
        </div>

        <!-- ACUERDO -->
        <div id="panel-acuerdo" style="display:none">
          <div class="form-grid">
            <div class="field">
              <label>Fecha compromiso</label>
              <input type="date" name="fecha_compromiso" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
            </div>
            <div class="field field-span2">
              <label>Nota del acuerdo <span class="required">*</span></label>
              <input type="text" name="nota_acuerdo" placeholder="Ej: Llama el viernes, paga el lunes...">
            </div>
          </div>
        </div>

        <div class="field mt-2" id="campo-nota-gestion">
          <label>Nota de gestión</label>
          <textarea name="nota_gestion" placeholder="Descripción de lo acordado..."></textarea>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-gestionar')">Cancelar</button>
      <button class="btn btn-primary" id="btn-gestionar" onclick="ejecutarGestion()" disabled>CONFIRMAR</button>
    </div>
  </div>
</div>

<!-- Modal gestión rápida -->
<div class="modal-overlay" id="modal-gestion">
  <div class="modal">
    <div class="modal-header">
      <h2>NUEVA GESTIÓN</h2>
      <button class="modal-close" onclick="closeModal('modal-gestion')">✕</button>
    </div>
    <div class="modal-body">
      <form id="form-gestion-rapida">
        <input type="hidden" name="prestamo_id" value="<?= $id ?>">
        <input type="hidden" name="deudor_id" value="<?= $p['deudor_id'] ?>">
        <input type="hidden" name="action" value="gestion">
        <div class="form-grid mb-2">
          <div class="field">
            <label>Tipo</label>
            <select name="tipo">
              <option value="llamada">Llamada</option>
              <option value="visita">Visita</option>
              <option value="whatsapp">WhatsApp</option>
              <option value="acuerdo">Acuerdo</option>
              <option value="nota">Nota</option>
            </select>
          </div>
          <div class="field">
            <label>Resultado</label>
            <select name="resultado">
              <option value="contactado">Contactado</option>
              <option value="no_contesto">No contestó</option>
              <option value="promesa_pago">Promesa de pago</option>
              <option value="sin_resultado">Sin resultado</option>
            </select>
          </div>
          <div class="field">
            <label>Fecha</label>
            <input type="date" name="fecha_gestion" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="field field-span2">
            <label>Nota <span class="required">*</span></label>
            <textarea name="nota" required placeholder="Describe la gestión..."></textarea>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-gestion')">Cancelar</button>
      <button class="btn btn-primary" onclick="guardarGestionRapida()">GUARDAR</button>
    </div>
  </div>
</div>


<!-- MODAL EDITAR PRÉSTAMO -->
<div class="modal-overlay" id="modal-editar-prestamo">
  <div class="modal">
    <div class="modal-header">
      <h2>EDITAR PRÉSTAMO #<?= $p['id'] ?></h2>
      <button class="modal-close" onclick="closeModal('modal-editar-prestamo')">&#10005;</button>
    </div>
    <div class="modal-body">
      <div class="alert alert-warning" style="margin-bottom:1rem;font-size:0.8rem">
        ⚠ Solo se puede editar mientras no tenga pagos registrados. Las cuotas se regenerarán.
      </div>
      <form id="form-editar-prestamo">
        <input type="hidden" name="id" value="<?= $p['id'] ?>">
        <div class="form-grid">
          <div class="field">
            <label>Monto prestado <span class="required">*</span></label>
            <input type="number" name="monto_prestado" value="<?= $p['monto_prestado'] ?>" step="1000" min="1" required>
          </div>
          <div class="field">
            <label>Tipo interés</label>
            <select name="tipo_interes">
              <option value="porcentaje" <?= $p['tipo_interes']==='porcentaje'?'selected':'' ?>>Porcentaje (%)</option>
              <option value="valor_fijo" <?= $p['tipo_interes']==='valor_fijo'?'selected':'' ?>>Valor fijo ($)</option>
            </select>
          </div>
          <div class="field">
            <label>Interés</label>
            <input type="number" name="interes_valor" value="<?= $p['interes_valor'] ?>" step="0.01" min="0">
          </div>
          <div class="field">
            <label>Frecuencia</label>
            <select name="frecuencia_pago">
              <option value="diario"    <?= $p['frecuencia_pago']==='diario'?'selected':'' ?>>Diario</option>
              <option value="semanal"   <?= $p['frecuencia_pago']==='semanal'?'selected':'' ?>>Semanal</option>
              <option value="quincenal" <?= $p['frecuencia_pago']==='quincenal'?'selected':'' ?>>Quincenal</option>
              <option value="mensual"   <?= $p['frecuencia_pago']==='mensual'?'selected':'' ?>>Mensual</option>
            </select>
          </div>
          <div class="field">
            <label>Número de cuotas</label>
            <input type="number" name="num_cuotas" value="<?= $p['num_cuotas'] ?>" min="1" required>
          </div>
          <div class="field">
            <label>Fecha inicio</label>
            <input type="date" name="fecha_inicio" value="<?= $p['fecha_inicio'] ?>">
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-editar-prestamo')">Cancelar</button>
      <button class="btn btn-primary" onclick="guardarEdicionPrestamo()">GUARDAR CAMBIOS</button>
    </div>
  </div>
</div>

<script>
async function anularPago(pagoId, numeroCuota) {
    if (!confirm(`¿Anular el pago de la cuota #${numeroCuota}? La cuota volverá a estado pendiente y se revertirá el movimiento de caja.`)) return;
    const res = await apiPost('/api/pagos.php', { action: 'anular', pago_id: pagoId });
    if (res.ok) { toast(res.msg, 'success'); setTimeout(() => location.reload(), 1200); }
    else toast(res.msg || 'Error al anular pago', 'error');
}

async function confirmarAnular(id) {
    if (!confirm('¿Anular este préstamo? Se revertirán todos los movimientos de capital. Esta acción no se puede deshacer.')) return;
    var res = await apiPost('/api/prestamos.php', { action: 'anular', id: id });
    if (res.ok) {
        toast(res.msg, 'success');
        setTimeout(() => location.reload(), 1200);
    } else {
        toast(res.msg || 'Error al anular', 'error');
    }
}

async function guardarEdicionPrestamo() {
    var data = Object.fromEntries(new FormData(document.getElementById('form-editar-prestamo')));
    data.action = 'editar';
    var btn = event.target;
    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Guardando...';
    var res = await apiPost('/api/prestamos.php', data);
    btn.disabled = false; btn.innerHTML = 'GUARDAR CAMBIOS';
    if (res.ok) {
        toast(res.msg, 'success');
        closeModal('modal-editar-prestamo');
        setTimeout(() => location.reload(), 1000);
    } else {
        toast(res.msg || 'Error', 'error');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
function setCuotaMonto(sel) {
    var opt = sel.options[sel.selectedIndex];
    document.getElementById('pago-monto').value = opt.dataset.monto || '';
}
document.addEventListener('DOMContentLoaded', function() {
    var sel = document.getElementById('select-cuota');
    if (sel && sel.options.length) setCuotaMonto(sel);
});

function pagarCuota(cuotaId, monto) {
    document.getElementById('select-cuota').value = cuotaId;
    document.getElementById('pago-monto').value   = monto;
    openModal('modal-pago');
}

async function registrarPago() {
    var btn  = document.getElementById('btn-pagar');
    var form = document.getElementById('form-pago');
    var data = Object.fromEntries(new FormData(form));
    if (!data.monto_pagado || parseFloat(data.monto_pagado) <= 0) { toast('Ingresa el monto', 'error'); return; }
    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>';
    var res = await apiPost('/api/prestamos.php', Object.assign({ action: 'pagar' }, data));
    btn.disabled = false; btn.innerHTML = 'REGISTRAR PAGO';
    if (res.ok) { toast('Pago registrado'); closeModal('modal-pago'); setTimeout(function(){ location.reload(); }, 800); }
    else toast(res.msg || 'Error', 'error');
}

function setAccion(accion) {
    document.getElementById('g-accion').value = accion;
    ['renovar','refinanciar','acuerdo'].forEach(function(a) {
        document.getElementById('panel-'+a).style.display = a===accion ? 'block' : 'none';
    });
    document.getElementById('btn-gestionar').disabled = false;
    if (accion === 'renovar') calcPreviewRenovar();
}

function calcPreviewRenovar() {
    var saldo     = <?= $p['saldo_pendiente'] ?>;
    var intPagado = parseFloat(document.querySelector('[name=monto_intereses]').value) || 0;
    var nuevoCap  = Math.max(0, saldo - intPagado);
    var pct       = parseFloat(document.querySelector('[name=nuevo_interes]').value) || 0;
    var cuotas    = parseInt(document.querySelector('[name=nuevas_cuotas]').value) || 1;
    var intCalc   = nuevoCap * pct / 100;
    var total     = nuevoCap + intCalc;
    var valCuota  = total / cuotas;

    var prev = document.getElementById('preview-renovar');
    if (!prev) return;
    prev.style.display = 'block';
    prev.innerHTML =
        'Saldo actual: <strong>' + fmt(saldo) + '</strong> · ' +
        'Interés que paga: <strong style="color:var(--accent)">-' + fmt(intPagado) + '</strong><br>' +
        'Nuevo capital: <strong style="color:var(--accent)">' + fmt(nuevoCap) + '</strong> · ' +
        'Nuevo interés (' + pct + '%): +' + fmt(intCalc) + '<br>' +
        'Nuevo total: <strong>' + fmt(total) + '</strong> · ' +
        cuotas + ' cuotas de <strong>' + fmt(valCuota) + '</strong>';
}

function calcRefin() {
    var cap    = parseFloat(document.querySelector('[name=nuevo_capital]').value) || 0;
    var pct    = parseFloat(document.querySelector('[name=nuevo_interes_r]').value) || 0;
    var abono  = parseFloat(document.querySelector('[name=abono_cliente]').value) || 0;
    var base   = Math.max(0, cap - abono);
    var interes= base * pct / 100;
    var total  = base + interes;
    var p = document.getElementById('preview-refin');
    p.style.display = 'block';
    p.innerHTML = 'Nuevo capital: ' + fmt(base) + ' + Interes: ' + fmt(interes) + ' = <strong>Total: ' + fmt(total) + '</strong>';
}

async function ejecutarGestion() {
    var btn  = document.getElementById('btn-gestionar');
    var data = Object.fromEntries(new FormData(document.getElementById('form-gestionar')));
    if (!data.action) { toast('Selecciona una accion', 'error'); return; }
    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Procesando...';
    var res = await apiPost('/api/prestamos.php', data);
    btn.disabled = false; btn.innerHTML = 'CONFIRMAR';
    if (res.ok) {
        toast(res.msg || 'Operacion exitosa');
        closeModal('modal-gestionar');
        setTimeout(function() {
            if (res.nuevo_id) window.location = '/pages/prestamo_detalle.php?id=' + res.nuevo_id;
            else location.reload();
        }, 800);
    } else toast(res.msg || 'Error', 'error');
}

async function guardarGestionRapida() {
    var data = Object.fromEntries(new FormData(document.getElementById('form-gestion-rapida')));
    if (!data.nota || !data.nota.trim()) { toast('La nota es obligatoria', 'error'); return; }
    var res = await apiPost('/api/deudores.php', data);
    if (res.ok) { toast('Gestion registrada'); closeModal('modal-gestion'); setTimeout(function(){ location.reload(); }, 800); }
    else toast(res.msg || 'Error', 'error');
}
</script>