<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
if (!canDo('puede_ver_deudores')) { include __DIR__ . '/403.php'; exit; }

$db    = getDB();
$cobro = cobroActivo();
$id    = (int)($_GET['id'] ?? 0);

// Cargar deudor
$stmt = $db->prepare("SELECT * FROM deudores WHERE id = ?");
$stmt->execute([$id]);
$deudor = $stmt->fetch();
if (!$deudor) { header('Location: /pages/deudores.php'); exit; }

// Préstamos del deudor
$stmtP = $db->prepare("
    SELECT p.*,
        cap.nombre AS capitalista_nombre,
        c.nombre   AS cuenta_nombre,
        (SELECT COUNT(*) FROM cuotas WHERE prestamo_id=p.id AND estado='pagado') AS cuotas_pagadas,
        (SELECT COUNT(*) FROM cuotas WHERE prestamo_id=p.id) AS cuotas_total,
        padre.id   AS padre_id
    FROM prestamos p
    LEFT JOIN capitalistas cap ON cap.id = p.capitalista_id
    LEFT JOIN cuentas c        ON c.id   = p.cuenta_desembolso_id
    LEFT JOIN prestamos padre  ON padre.id = p.prestamo_padre_id
    WHERE p.deudor_id = ? AND p.cobro_id = ?
    ORDER BY p.created_at DESC
");
$stmtP->execute([$id, $cobro]);
$prestamos = $stmtP->fetchAll();

// Pagos del deudor
$stmtPag = $db->prepare("
    SELECT pg.*, cu.numero_cuota, c.nombre AS cuenta
    FROM pagos pg
    JOIN cuotas cu ON cu.id = pg.cuota_id
    LEFT JOIN cuentas c ON c.id = pg.cuenta_id
    WHERE pg.deudor_id = ? AND pg.cobro_id = ?
    ORDER BY pg.fecha_pago DESC
    LIMIT 30
");
$stmtPag->execute([$id, $cobro]);
$pagos = $stmtPag->fetchAll();

// Gestiones
$stmtG = $db->prepare("
    SELECT gc.*, u.nombre AS usuario_nombre
    FROM gestiones_cobro gc
    LEFT JOIN usuarios u ON u.id = gc.usuario_id
    WHERE gc.deudor_id = ? AND gc.cobro_id = ?
    ORDER BY gc.fecha_gestion DESC
    LIMIT 20
");
$stmtG->execute([$id, $cobro]);
$gestiones = $stmtG->fetchAll();

// Totales resumen
$saldoTotal   = array_sum(array_column(array_filter($prestamos, fn($p) => !in_array($p['estado'],['pagado','renovado','refinanciado'])), 'saldo_pendiente'));
$totalPrestado= array_sum(array_column($prestamos, 'monto_prestado'));
$totalPagado  = array_sum(array_column($pagos, 'monto_pagado'));

$pageTitle   = $deudor['nombre'];
$pageSection = 'Deudores / ' . $deudor['nombre'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="breadcrumb">
  <a href="/pages/deudores.php">Deudores</a>
  <span class="sep">›</span>
  <span class="current"><?= htmlspecialchars($deudor['nombre']) ?></span>
</div>

<!-- Header deudor -->
<div class="page-header page-header-row">
  <div style="display:flex;align-items:center;gap:1rem">
    <div class="avatar avatar-lg"><?= strtoupper(substr($deudor['nombre'],0,1)) ?></div>
    <div>
      <h1><?= htmlspecialchars(strtoupper($deudor['nombre'])) ?></h1>
      <div style="display:flex;gap:0.75rem;margin-top:0.35rem;flex-wrap:wrap">
        <?php if ($deudor['telefono']): ?>
        <span class="text-mono text-xs text-muted">📞 <?= htmlspecialchars($deudor['telefono']) ?></span>
        <?php endif; ?>
        <?php if ($deudor['documento']): ?>
        <span class="text-mono text-xs text-muted">🪪 <?= htmlspecialchars($deudor['documento']) ?></span>
        <?php endif; ?>
        <?php if ($deudor['barrio']): ?>
        <span class="text-mono text-xs text-muted">📍 <?= htmlspecialchars($deudor['barrio']) ?></span>
        <?php endif; ?>
        <?php
          $comp = $deudor['comportamiento'];
          $cc = $comp==='bueno'?'badge-green':($comp==='regular'?'badge-orange':'badge-red');
        ?>
        <span class="badge <?= $cc ?>"><?= ucfirst($comp) ?></span>
      </div>
    </div>
  </div>
  <div class="btn-group">
    <?php if (canDo('puede_crear_prestamo')): ?>
    <a href="/pages/prestamos.php?action=nuevo&deudor=<?= $id ?>" class="btn btn-primary">+ Nuevo Préstamo</a>
    <?php endif; ?>
    <?php if (canDo('puede_editar_prestamo')): ?>
    <button class="btn btn-ghost" onclick="openModal('modal-editar')">Editar</button>
    <?php endif; ?>
  </div>
</div>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:1.5rem">
  <div class="stat-card">
    <div class="stat-label">Total Prestado</div>
    <div class="stat-value"><?= fmt($totalPrestado) ?></div>
    <div class="stat-sub"><?= count($prestamos) ?> préstamos históricos</div>
  </div>
  <div class="stat-card orange">
    <div class="stat-label">Saldo Pendiente</div>
    <div class="stat-value"><?= fmt($saldoTotal) ?></div>
  </div>
  <div class="stat-card purple">
    <div class="stat-label">Total Pagado</div>
    <div class="stat-value"><?= fmt($totalPagado) ?></div>
  </div>
  <div class="stat-card blue">
    <div class="stat-label">Préstamos Activos</div>
    <div class="stat-value"><?= count(array_filter($prestamos, fn($p)=>in_array($p['estado'],['activo','en_mora','en_acuerdo']))) ?></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem">

  <!-- PRÉSTAMOS -->
  <div>
    <div class="card">
      <div class="card-header">
        <span class="card-title">PRÉSTAMOS</span>
      </div>
      <?php if (empty($prestamos)): ?>
        <div class="empty-state"><span class="empty-icon">◎</span><p>Sin préstamos registrados</p></div>
      <?php else: ?>
      <?php foreach ($prestamos as $p): ?>
      <?php
        $estadoClass = match($p['estado']) {
          'activo'        => 'badge-purple',
          'en_mora'       => 'badge-orange',
          'en_acuerdo'    => 'badge-blue',
          'pagado'        => 'badge-green',
          'renovado'      => 'badge-muted',
          'refinanciado'  => 'badge-muted',
          'incobrable'    => 'badge-red',
          default         => 'badge-muted'
        };
        $pct = $p['cuotas_total'] > 0 ? round($p['cuotas_pagadas']/$p['cuotas_total']*100) : 0;
      ?>
      <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border)">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.75rem">
          <div>
            <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.25rem">
              <span class="text-mono text-xs text-muted">#<?= $p['id'] ?></span>
              <span class="badge <?= $estadoClass ?>"><?= strtoupper($p['estado']) ?></span>
              <?php if ($p['tipo_origen'] !== 'nuevo'): ?>
              <span class="badge badge-muted"><?= strtoupper($p['tipo_origen']) ?></span>
              <?php endif; ?>
            </div>
            <div style="font-family:var(--font-display);font-size:1.4rem;letter-spacing:1px;color:var(--accent)">
              <?= fmt($p['monto_prestado']) ?>
              <span style="font-size:0.9rem;color:var(--muted)">→ <?= fmt($p['total_a_pagar']) ?></span>
            </div>
            <div class="text-mono text-xs text-muted mt-1">
              <?= $p['interes_valor'] ?><?= $p['tipo_interes']==='porcentaje'?'%':' fijo' ?> ·
              <?= ucfirst($p['frecuencia_pago']) ?> · <?= $p['num_cuotas'] ?> cuotas de <?= fmt($p['valor_cuota']) ?> ·
              Inicio: <?= date('d M Y', strtotime($p['fecha_inicio'])) ?>
            </div>
          </div>
          <div style="text-align:right">
            <div class="text-mono text-xs text-muted">Saldo</div>
            <div style="font-family:var(--font-display);font-size:1.2rem;color:var(--warn)"><?= fmt($p['saldo_pendiente']) ?></div>
          </div>
        </div>

        <!-- Progress cuotas -->
        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem">
          <div class="progress" style="flex:1">
            <div class="progress-bar <?= $p['estado']==='en_mora'?'orange':'' ?>" style="width:<?= $pct ?>%"></div>
          </div>
          <span class="text-mono text-xs text-muted"><?= $p['cuotas_pagadas'] ?>/<?= $p['cuotas_total'] ?> cuotas</span>
        </div>

        <!-- Acciones según estado -->
        <div class="btn-group">
          <a href="/pages/prestamo_detalle.php?id=<?= $p['id'] ?>" class="btn btn-info btn-sm">Ver cuotas</a>
          <?php if (canDo('puede_registrar_pago') && in_array($p['estado'],['activo','en_mora','en_acuerdo'])): ?>
          <a href="/pages/pagos.php?prestamo=<?= $p['id'] ?>" class="btn btn-success btn-sm">💰 Pagar</a>
          <?php endif; ?>
          <?php if (canDo('puede_editar_prestamo') && in_array($p['estado'],['activo','en_mora','en_acuerdo'])): ?>
          <a href="/pages/prestamos.php?action=gestionar&id=<?= $p['id'] ?>" class="btn btn-warning btn-sm">Gestionar</a>
          <?php endif; ?>
        </div>

        <?php if ($p['nota_acuerdo']): ?>
        <div class="alert alert-info mt-1" style="margin-bottom:0;padding:0.5rem 0.75rem;font-size:0.7rem">
          📋 <?= htmlspecialchars($p['nota_acuerdo']) ?> — Compromiso: <?= $p['fecha_compromiso'] ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- HISTORIAL DE PAGOS -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">HISTORIAL DE PAGOS</span>
        <span class="text-mono text-xs text-muted"><?= count($pagos) ?> registros</span>
      </div>
      <?php if (empty($pagos)): ?>
        <div class="empty-state"><span class="empty-icon">◈</span><p>Sin pagos registrados</p></div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Fecha</th><th>Cuota</th><th>Monto</th><th>Cuenta</th><th>Método</th></tr>
          </thead>
          <tbody>
            <?php foreach ($pagos as $pg): ?>
            <tr>
              <td class="text-mono"><?= date('d M Y', strtotime($pg['fecha_pago'])) ?></td>
              <td class="text-muted">#<?= $pg['numero_cuota'] ?></td>
              <td class="green text-mono fw-600"><?= fmt($pg['monto_pagado']) ?></td>
              <td><?= htmlspecialchars($pg['cuenta'] ?? 'Efectivo') ?></td>
              <td><span class="badge badge-muted"><?= ucfirst($pg['metodo_pago']) ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- PANEL LATERAL -->
  <div>
    <!-- Datos personales -->
    <div class="card mb-2">
      <div class="card-header"><span class="card-title">DATOS</span></div>
      <div class="card-body">
        <?php
          $campos = [
            'Teléfono' => $deudor['telefono'],
            'Tel. Alt'  => $deudor['telefono_alt'],
            'Documento' => $deudor['documento'],
            'Dirección' => $deudor['direccion'],
            'Barrio'    => $deudor['barrio'],
          ];
          foreach ($campos as $label => $val):
            if (!$val) continue;
        ?>
        <div style="display:flex;justify-content:space-between;padding:0.4rem 0;border-bottom:1px solid var(--border);font-family:var(--font-mono);font-size:0.72rem">
          <span style="color:var(--muted)"><?= $label ?></span>
          <span><?= htmlspecialchars($val) ?></span>
        </div>
        <?php endforeach; ?>

        <?php if ($deudor['codeudor_nombre']): ?>
        <div class="divider"></div>
        <div class="text-mono text-xs text-muted mb-1" style="text-transform:uppercase;letter-spacing:1px">Codeudor</div>
        <div style="font-size:0.8rem"><?= htmlspecialchars($deudor['codeudor_nombre']) ?></div>
        <?php if ($deudor['codeudor_telefono']): ?>
        <div class="text-mono text-xs text-muted"><?= htmlspecialchars($deudor['codeudor_telefono']) ?></div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($deudor['garantia_descripcion']): ?>
        <div class="divider"></div>
        <div class="text-mono text-xs text-muted mb-1" style="text-transform:uppercase;letter-spacing:1px">Garantía</div>
        <div style="font-size:0.8rem"><?= htmlspecialchars($deudor['garantia_descripcion']) ?></div>
        <?php endif; ?>

        <?php if ($deudor['notas']): ?>
        <div class="divider"></div>
        <div class="text-mono text-xs text-muted mb-1" style="text-transform:uppercase;letter-spacing:1px">Notas</div>
        <div style="font-size:0.78rem;line-height:1.5;color:var(--text-soft)"><?= nl2br(htmlspecialchars($deudor['notas'])) ?></div>
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
        <div class="empty-state" style="padding:2rem"><span class="empty-icon" style="font-size:1.5rem">◎</span><p>Sin gestiones</p></div>
      <?php else: ?>
      <div style="padding:0.5rem 0">
        <?php foreach ($gestiones as $g): ?>
        <div style="padding:0.65rem 1rem;border-bottom:1px solid rgba(37,37,53,0.5)">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.25rem">
            <span class="badge badge-muted"><?= ucfirst($g['tipo']) ?></span>
            <span class="text-mono text-xs text-muted"><?= date('d M', strtotime($g['fecha_gestion'])) ?></span>
          </div>
          <div style="font-size:0.78rem;color:var(--text-soft)"><?= htmlspecialchars($g['nota']) ?></div>
          <?php if ($g['resultado']): ?>
          <div class="text-mono text-xs text-muted mt-1"><?= ucfirst(str_replace('_',' ',$g['resultado'])) ?></div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal gestión -->
<div class="modal-overlay" id="modal-gestion">
  <div class="modal">
    <div class="modal-header">
      <h2>NUEVA GESTIÓN</h2>
      <button class="modal-close" onclick="closeModal('modal-gestion')">✕</button>
    </div>
    <div class="modal-body">
      <form id="form-gestion" onsubmit="guardarGestion(event)">
        <input type="hidden" name="deudor_id" value="<?= $id ?>">
        <input type="hidden" name="action" value="gestion">
        <div class="form-grid mb-2">
          <div class="field">
            <label>Tipo</label>
            <select name="tipo">
              <option value="llamada">Llamada</option>
              <option value="visita">Visita</option>
              <option value="whatsapp">WhatsApp</option>
              <option value="acuerdo">Acuerdo</option>
              <option value="nota">Nota interna</option>
            </select>
          </div>
          <div class="field">
            <label>Resultado</label>
            <select name="resultado">
              <option value="">Sin resultado</option>
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
            <textarea name="nota" placeholder="Describe la gestión realizada..." required></textarea>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-gestion')">Cancelar</button>
      <button class="btn btn-primary" onclick="guardarGestion(event)">GUARDAR</button>
    </div>
  </div>
</div>

<!-- Modal editar deudor (reutiliza datos) -->
<!-- Modal editar deudor -->
<div class="modal-overlay" id="modal-editar">
  <div class="modal" style="max-width:640px">
    <div class="modal-header">
      <h2>EDITAR DEUDOR</h2>
      <button class="modal-close" onclick="closeModal('modal-editar')">✕</button>
    </div>
    <div class="modal-body">
      <form id="form-editar" onsubmit="guardarEdicion(event)">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="form-grid mb-2">
          <div class="field field-span2">
            <label>Nombre <span class="required">*</span></label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($deudor['nombre']) ?>" required>
          </div>
          <div class="field">
            <label>Teléfono</label>
            <input type="tel" name="telefono" value="<?= htmlspecialchars($deudor['telefono']??'') ?>">
          </div>
          <div class="field">
            <label>Teléfono Alt.</label>
            <input type="tel" name="telefono_alt" value="<?= htmlspecialchars($deudor['telefono_alt']??'') ?>">
          </div>
          <div class="field">
            <label>Documento</label>
            <input type="text" name="documento" value="<?= htmlspecialchars($deudor['documento']??'') ?>">
          </div>
          <div class="field">
            <label>Barrio</label>
            <input type="text" name="barrio" value="<?= htmlspecialchars($deudor['barrio']??'') ?>">
          </div>

          <div class="field field-span2">
            <label>Dirección</label>
            <input type="text" id="det_direccion" name="direccion"
                   value="<?= htmlspecialchars($deudor['direccion']??'') ?>"
                   placeholder="Escribe la dirección para buscar..."
                   autocomplete="off">
            <input type="hidden" id="det_lat"      name="lat"      value="<?= htmlspecialchars($deudor['lat']??'') ?>">
            <input type="hidden" id="det_lng"      name="lng"      value="<?= htmlspecialchars($deudor['lng']??'') ?>">
            <input type="hidden" id="det_place_id" name="place_id" value="<?= htmlspecialchars($deudor['place_id']??'') ?>">
          </div>
        </div>

        <!-- Mapa fuera del form-grid -->
        <div id="mapa-det-wrap" style="display:<?= ($deudor['lat'] && $deudor['lng']) ? 'block' : 'none' ?>;margin-bottom:1rem">
          <div id="mapa-detalle"
               style="width:100%;height:240px;border-radius:var(--radius);border:1px solid var(--border);overflow:hidden;min-height:240px">
          </div>
          <div style="font-size:0.72rem;color:var(--muted);margin-top:0.4rem;font-family:var(--font-mono)">
            📍 Arrastra el pin si la ubicación no es exacta
          </div>
        </div>

        <div class="form-grid">
          <div class="field">
            <label>Comportamiento</label>
            <select name="comportamiento">
              <option value="bueno"   <?= $deudor['comportamiento']==='bueno'   ?'selected':'' ?>>Bueno</option>
              <option value="regular" <?= $deudor['comportamiento']==='regular' ?'selected':'' ?>>Regular</option>
              <option value="malo"    <?= $deudor['comportamiento']==='malo'    ?'selected':'' ?>>Malo</option>
            </select>
          </div>
          <div class="field field-span2">
            <label>Notas</label>
            <textarea name="notas"><?= htmlspecialchars($deudor['notas']??'') ?></textarea>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-editar')">Cancelar</button>
      <button class="btn btn-primary" onclick="guardarEdicion(event)">GUARDAR CAMBIOS</button>
    </div>
  </div>
</div>

<?php
$extraScript = <<<JS
<script>
// ── Mapa del detalle del deudor ───────────────────────────────
let mapaDetInstance   = null;
let markerDetInstance = null;
let autocompleteDetInstance = null;

function initMapaDetalle() {
    if (mapaDetInstance) return;

    const lat = parseFloat(document.getElementById('det_lat').value) || 5.5353;
    const lng = parseFloat(document.getElementById('det_lng').value) || -73.3678;
    const centro = { lat, lng };

    mapaDetInstance = new google.maps.Map(document.getElementById('mapa-detalle'), {
        center            : centro,
        zoom              : lat === 5.5353 ? 6 : 17,
        mapTypeControl    : false,
        streetViewControl : false,
        fullscreenControl : false,
    });

    markerDetInstance = new google.maps.Marker({
        position : centro,
        map      : mapaDetInstance,
        draggable: true,
        title    : 'Arrastra para ajustar la ubicación',
    });

    markerDetInstance.addListener('dragend', function () {
        const pos = markerDetInstance.getPosition();
        document.getElementById('det_lat').value      = pos.lat().toFixed(7);
        document.getElementById('det_lng').value      = pos.lng().toFixed(7);
        document.getElementById('det_place_id').value = '';
    });

    autocompleteDetInstance = new google.maps.places.Autocomplete(
        document.getElementById('det_direccion'),
        {
            componentRestrictions: { country: 'co' },
            fields: ['geometry', 'formatted_address', 'place_id'],
        }
    );

    autocompleteDetInstance.addListener('place_changed', function () {
        const place = autocompleteDetInstance.getPlace();
        if (!place.geometry || !place.geometry.location) return;

        const lat = place.geometry.location.lat();
        const lng = place.geometry.location.lng();

        document.getElementById('det_lat').value      = lat.toFixed(7);
        document.getElementById('det_lng').value      = lng.toFixed(7);
        document.getElementById('det_place_id').value = place.place_id || '';

        const pos = new google.maps.LatLng(lat, lng);
        mapaDetInstance.setCenter(pos);
        mapaDetInstance.setZoom(17);
        markerDetInstance.setPosition(pos);

        document.getElementById('mapa-det-wrap').style.display = 'block';
        setTimeout(() => google.maps.event.trigger(mapaDetInstance, 'resize'), 100);
    });
}

// Inicializar mapa cuando se abre el modal editar
document.querySelector('[onclick="openModal(\'modal-editar\')"]')
    ?.addEventListener('click', () => {
        setTimeout(() => {
            if (typeof google === 'undefined') return;
            document.getElementById('mapa-det-wrap').style.display = 'block';
            if (!mapaDetInstance) {
                initMapaDetalle();
            } else {
                google.maps.event.trigger(mapaDetInstance, 'resize');
            }
        }, 300);
    });

async function guardarGestion(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(document.getElementById('form-gestion')));
    if (!data.nota?.trim()) { toast('La nota es obligatoria', 'error'); return; }
    const res = await apiPost('/api/deudores.php', data);
    if (res.ok) { toast('Gestión registrada'); closeModal('modal-gestion'); setTimeout(()=>location.reload(),800); }
    else toast(res.msg || 'Error', 'error');
}

async function guardarEdicion(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(document.getElementById('form-editar')));
    const res = await apiPost('/api/deudores.php', data);
    if (res.ok) { toast('Deudor actualizado'); closeModal('modal-editar'); setTimeout(()=>location.reload(),800); }
    else toast(res.msg || 'Error', 'error');
}
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>