<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
if (!canDo('puede_ver_deudores')) { include __DIR__ . '/403.php'; exit; }

$db    = getDB();
$cobro = cobroActivo();

// ---- Cobros disponibles para el filtro ----
if ($_SESSION['rol'] === 'superadmin') {
    $stmtCobros = $db->query("SELECT id, nombre FROM cobros WHERE activo=1 ORDER BY nombre");
} else {
    $stmtCobros = $db->prepare("
        SELECT c.id, c.nombre FROM cobros c
        JOIN usuario_cobro uc ON uc.cobro_id = c.id
        WHERE uc.usuario_id=? AND c.activo=1 ORDER BY c.nombre
    ");
    $stmtCobros->execute([$_SESSION['usuario_id']]);
}
$todosCobros = $stmtCobros->fetchAll();

// ---- Filtros ----
$buscar        = trim($_GET['q'] ?? '');
$filtroEstado  = $_GET['estado'] ?? '';
$filtroComport = $_GET['comportamiento'] ?? '';
$filtroCobro   = (int)($_GET['cobro'] ?? 0);
$page          = max(1, (int)($_GET['page'] ?? 1));
$limit         = 15;
$offset        = ($page - 1) * $limit;

// ---- WHERE base ----
if ($filtroCobro > 0) {
    $where  = ['EXISTS (SELECT 1 FROM deudor_cobro dc WHERE dc.deudor_id=d.id AND dc.cobro_id=?)'];
    $params = [$filtroCobro];
} elseif ($_SESSION['rol'] === 'superadmin') {
    $where  = ['d.activo=1'];
    $params = [];
} else {
    $cobrosIds = implode(',', array_map('intval', array_column($todosCobros, 'id')));
    $where  = $cobrosIds
        ? ["EXISTS (SELECT 1 FROM deudor_cobro dc WHERE dc.deudor_id=d.id AND dc.cobro_id IN ($cobrosIds))"]
        : ['1=0'];
    $params = [];
}

if (!in_array('d.activo=1', $where)) $where[] = 'd.activo=1';

if ($buscar) {
    $where[]  = '(d.nombre LIKE ? OR d.telefono LIKE ? OR d.documento LIKE ?)';
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}

if ($filtroEstado === 'con_deuda') {
    $where[] = 'EXISTS (SELECT 1 FROM prestamos p WHERE p.deudor_id=d.id AND p.estado NOT IN ("pagado","renovado","refinanciado","anulado","incobrable"))';
} elseif ($filtroEstado === 'al_dia') {
    $where[] = 'NOT EXISTS (SELECT 1 FROM prestamos p WHERE p.deudor_id=d.id AND p.estado="en_mora")';
} elseif ($filtroEstado === 'en_mora') {
    $where[] = 'EXISTS (SELECT 1 FROM prestamos p WHERE p.deudor_id=d.id AND p.estado="en_mora")';
} elseif ($filtroEstado === 'sin_prestamos') {
    $where[] = 'NOT EXISTS (SELECT 1 FROM prestamos p WHERE p.deudor_id=d.id)';
}

if (in_array($filtroComport, ['bueno','regular','clavo'])) {
    $where[]  = 'd.comportamiento = ?';
    $params[] = $filtroComport;
}

$whereSQL = implode(' AND ', $where);

// ---- Total ----
$stmtTotal = $db->prepare("SELECT COUNT(DISTINCT d.id) FROM deudores d WHERE $whereSQL");
$stmtTotal->execute($params);
$total     = (int)$stmtTotal->fetchColumn();
$totalPags = ceil($total / $limit);

// ---- Lista ----
$stmt = $db->prepare("
    SELECT d.*,
        COUNT(DISTINCT p.id) AS total_prestamos,
        COALESCE(SUM(CASE WHEN p.estado NOT IN ('pagado','renovado','refinanciado','anulado','incobrable')
            THEN p.saldo_pendiente END), 0) AS saldo_total,
        MAX(CASE WHEN p.estado='en_mora' THEN 1 ELSE 0 END) AS tiene_mora,
        GROUP_CONCAT(DISTINCT c.nombre ORDER BY c.nombre SEPARATOR ', ') AS cobros_nombres
    FROM deudores d
    LEFT JOIN prestamos p      ON p.deudor_id=d.id
    LEFT JOIN deudor_cobro dc2 ON dc2.deudor_id=d.id
    LEFT JOIN cobros c         ON c.id=dc2.cobro_id AND c.activo=1
    WHERE $whereSQL
    GROUP BY d.id
    ORDER BY d.nombre ASC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$deudores = $stmt->fetchAll();

// Para cada deudor, calcular qué cobros tienen saldo (para bloquear checkboxes)
foreach ($deudores as &$d) {
    $stmtCobrosD = $db->prepare("SELECT cobro_id FROM deudor_cobro WHERE deudor_id=?");
    $stmtCobrosD->execute([$d['id']]);
    $d['cobros_ids'] = array_column($stmtCobrosD->fetchAll(), 'cobro_id');

    // Cobros con saldo pendiente — no se pueden quitar
    $stmtSaldo = $db->prepare("
        SELECT DISTINCT cobro_id FROM prestamos
        WHERE deudor_id=? AND estado NOT IN ('pagado','renovado','refinanciado','anulado','incobrable')
    ");
    $stmtSaldo->execute([$d['id']]);
    $d['cobros_con_saldo'] = array_column($stmtSaldo->fetchAll(), 'cobro_id');
}
unset($d);

// ---- Stats ----
$stmtStats = $db->prepare("
    SELECT
        COUNT(DISTINCT d.id) AS total,
        COUNT(DISTINCT CASE WHEN p.estado='en_mora' THEN d.id END) AS en_mora,
        COUNT(DISTINCT CASE WHEN p.estado IN ('activo','en_mora','en_acuerdo') THEN d.id END) AS activos,
        COUNT(DISTINCT CASE WHEN d.comportamiento='clavo' THEN d.id END) AS clavos
    FROM deudores d
    LEFT JOIN prestamos p ON p.deudor_id=d.id AND p.estado NOT IN ('anulado')
    WHERE $whereSQL
");
$stmtStats->execute($params);
$stats = $stmtStats->fetch();

$pageTitle   = 'Deudores';
$pageSection = 'Deudores';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header-row">
  <div>
    <h1>DEUDORES</h1>
    <p>// <?= number_format($total) ?> registros encontrados</p>
  </div>
  <?php if (canDo('puede_crear_deudor')): ?>
  <button class="btn btn-primary" onclick="openModal('modal-deudor')">+ Nuevo Deudor</button>
  <?php endif; ?>
</div>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:1.5rem">
  <div class="stat-card">
    <div class="stat-label">Total</div>
    <div class="stat-value"><?= $stats['total'] ?></div>
  </div>
  <div class="stat-card purple">
    <div class="stat-label">Con Préstamo Activo</div>
    <div class="stat-value"><?= $stats['activos'] ?></div>
  </div>
  <div class="stat-card orange">
    <div class="stat-label">En Mora</div>
    <div class="stat-value"><?= $stats['en_mora'] ?></div>
  </div>
  <div class="stat-card" style="border-color:#ef444433">
    <div class="stat-label">Clavos</div>
    <div class="stat-value" style="color:#ef4444"><?= $stats['clavos'] ?></div>
  </div>
</div>

<!-- Filtros -->
<div class="filter-bar mb-2">
  <form method="GET" style="display:flex;gap:0.5rem;flex-wrap:wrap;width:100%">
    <div class="search-bar" style="flex:1;min-width:200px">
      <span class="search-icon">⌕</span>
      <input type="text" name="q" value="<?= htmlspecialchars($buscar) ?>"
             placeholder="Buscar nombre, teléfono, documento...">
    </div>

    <?php if (count($todosCobros) > 1): ?>
    <select name="cobro" onchange="this.form.submit()" style="width:auto">
      <option value="0">Todos los cobros</option>
      <?php foreach ($todosCobros as $cb): ?>
      <option value="<?= $cb['id'] ?>" <?= $filtroCobro===$cb['id']?'selected':'' ?>>
        <?= htmlspecialchars($cb['nombre']) ?>
      </option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>

    <select name="estado" onchange="this.form.submit()" style="width:auto">
      <option value="">Todos los estados</option>
      <option value="con_deuda"    <?= $filtroEstado==='con_deuda'    ?'selected':'' ?>>Con deuda activa</option>
      <option value="al_dia"       <?= $filtroEstado==='al_dia'       ?'selected':'' ?>>Al día</option>
      <option value="en_mora"      <?= $filtroEstado==='en_mora'      ?'selected':'' ?>>En mora</option>
      <option value="sin_prestamos"<?= $filtroEstado==='sin_prestamos'?'selected':'' ?>>Sin préstamos</option>
    </select>

    <select name="comportamiento" onchange="this.form.submit()" style="width:auto">
      <option value="">Todo comportamiento</option>
      <option value="bueno"   <?= $filtroComport==='bueno'  ?'selected':'' ?>>✅ Bueno</option>
      <option value="regular" <?= $filtroComport==='regular'?'selected':'' ?>>⚠ Regular</option>
      <option value="clavo"   <?= $filtroComport==='clavo'  ?'selected':'' ?>>🔴 Clavo</option>
    </select>

    <button type="submit" class="btn btn-secondary">Buscar</button>
    <?php if ($buscar || $filtroEstado || $filtroComport || $filtroCobro): ?>
    <a href="/pages/deudores.php" class="btn btn-ghost">✕ Limpiar</a>
    <?php endif; ?>
  </form>
</div>

<!-- Tabla -->
<div class="card">
  <?php if (empty($deudores)): ?>
    <div class="empty-state">
      <span class="empty-icon">◉</span>
      <p>No se encontraron deudores<?= $buscar ? " para \"".htmlspecialchars($buscar)."\"" : '' ?></p>
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Deudor</th>
          <th>Teléfono</th>
          <th>Documento</th>
          <th>Cobros</th>
          <th>Saldo Total</th>
          <th>Estado</th>
          <th>Comportamiento</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($deudores as $d):
          $esClavo = $d['comportamiento'] === 'clavo';
        ?>
        <tr <?= $esClavo ? 'style="background:rgba(239,68,68,.04)"' : '' ?>>
          <td>
            <div style="display:flex;align-items:center;gap:0.65rem">
              <div class="avatar avatar-sm" style="<?= $esClavo ? 'background:#ef4444' : '' ?>">
                <?= strtoupper(substr($d['nombre'],0,1)) ?>
              </div>
              <div>
                <div style="font-weight:600"><?= htmlspecialchars($d['nombre']) ?></div>
                <?php if ($d['barrio']): ?>
                <div class="text-xs text-muted"><?= htmlspecialchars($d['barrio']) ?></div>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td class="text-mono"><?= htmlspecialchars($d['telefono'] ?? '—') ?></td>
          <td class="text-mono text-muted"><?= htmlspecialchars($d['documento'] ?? '—') ?></td>
          <td class="text-xs text-muted"><?= htmlspecialchars($d['cobros_nombres'] ?? '—') ?></td>
          <td class="<?= $d['saldo_total'] > 0 ? 'orange' : 'green' ?> text-mono">
            <?= fmt($d['saldo_total']) ?>
          </td>
          <td>
            <?php if ($d['tiene_mora']): ?>
              <span class="badge badge-orange">En Mora</span>
            <?php elseif ($d['saldo_total'] > 0): ?>
              <span class="badge badge-purple">Activo</span>
            <?php elseif ($d['total_prestamos'] > 0): ?>
              <span class="badge badge-green">Al día</span>
            <?php else: ?>
              <span class="badge badge-muted">Sin préstamos</span>
            <?php endif; ?>
          </td>
          <td>
            <?php
              $comp = $d['comportamiento'];
              $compClass = match($comp) {
                  'bueno'   => 'badge-green',
                  'regular' => 'badge-orange',
                  'clavo'   => 'badge-red',
                  default   => 'badge-muted'
              };
            ?>
            <span class="badge <?= $compClass ?>"><?= ucfirst($comp) ?></span>
          </td>
          <td>
            <div class="btn-group">
              <a href="/pages/deudor_detalle.php?id=<?= $d['id'] ?>" class="btn btn-info btn-sm">Ver</a>
              <?php if (canDo('puede_editar_deudor')): ?>
              <button class="btn btn-ghost btn-sm"
                      onclick="editarDeudor(<?= htmlspecialchars(json_encode($d)) ?>)">
                Editar
              </button>
              <?php endif; ?>
              <?php if (canDo('puede_crear_prestamo') && !$esClavo): ?>
              <a href="/pages/prestamos.php?action=nuevo&deudor=<?= $d['id'] ?>"
                 class="btn btn-success btn-sm">+ Préstamo</a>
              <?php elseif ($esClavo): ?>
              <span title="Clavo — sin nuevos préstamos"
                    style="font-size:0.72rem;color:#ef4444;font-family:var(--font-mono)">🔴 CLAVO</span>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPags > 1): ?>
  <div class="pagination">
    <?php
    $qs = http_build_query(['q'=>$buscar,'estado'=>$filtroEstado,'comportamiento'=>$filtroComport,'cobro'=>$filtroCobro]);
    if ($page > 1): ?>
    <a href="?page=<?= $page-1 ?>&<?= $qs ?>" class="page-btn">‹</a>
    <?php endif; ?>
    <?php for ($i = max(1,$page-2); $i <= min($totalPags,$page+2); $i++): ?>
    <a href="?page=<?= $i ?>&<?= $qs ?>"
       class="page-btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($page < $totalPags): ?>
    <a href="?page=<?= $page+1 ?>&<?= $qs ?>" class="page-btn">›</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- ====== MODAL NUEVO / EDITAR DEUDOR ====== -->
<div class="modal-overlay" id="modal-deudor">
  <div class="modal">
    <div class="modal-header">
      <h2 id="modal-deudor-title">NUEVO DEUDOR</h2>
      <button class="modal-close" onclick="closeModal('modal-deudor')">✕</button>
    </div>
    <div class="modal-body">
      <form id="form-deudor" onsubmit="guardarDeudor(event)">
        <input type="hidden" id="d_id" name="id" value="">

        <div class="form-grid mb-2">
          <div class="field field-span2">
            <label>Nombre completo <span class="required">*</span></label>
            <input type="text" id="d_nombre" name="nombre" placeholder="Nombre del deudor" required>
          </div>
          <div class="field">
            <label>Teléfono</label>
            <input type="tel" id="d_telefono" name="telefono" placeholder="300 000 0000">
          </div>
          <div class="field">
            <label>Teléfono alternativo</label>
            <input type="tel" id="d_telefono_alt" name="telefono_alt" placeholder="Opcional">
          </div>
          <div class="field">
            <label>Documento</label>
            <input type="text" id="d_documento" name="documento" placeholder="CC / NIT">
          </div>
          <div class="field">
            <label>Barrio</label>
            <input type="text" id="d_barrio" name="barrio" placeholder="Barrio o sector">
          </div>
          <div class="field field-span2">
            <label>Dirección</label>
            <input type="text" id="d_direccion" name="direccion"
                   placeholder="Escribe la dirección para buscar..." autocomplete="off">
            <input type="hidden" id="d_lat"      name="lat">
            <input type="hidden" id="d_lng"      name="lng">
            <input type="hidden" id="d_place_id" name="place_id">
          </div>
          <div class="field field-span2" id="mapa-wrap" style="display:none">
            <div id="mapa-deudor"
                 style="width:100%;height:220px;border-radius:var(--radius);border:1px solid var(--border);overflow:hidden">
            </div>
            <div style="font-size:0.72rem;color:var(--muted);margin-top:0.4rem;font-family:var(--font-mono)">
              📍 Arrastra el pin si la ubicación no es exacta
            </div>
          </div>
        </div>

        <div class="divider"></div>
        <p style="font-family:var(--font-mono);font-size:0.68rem;color:var(--muted);margin-bottom:0.75rem;letter-spacing:1px;text-transform:uppercase">
          Codeudor (opcional)
        </p>
        <div class="form-grid mb-2">
          <div class="field">
            <label>Nombre codeudor</label>
            <input type="text" id="d_cod_nombre" name="codeudor_nombre" placeholder="Nombre">
          </div>
          <div class="field">
            <label>Teléfono codeudor</label>
            <input type="tel" id="d_cod_tel" name="codeudor_telefono" placeholder="300 000 0000">
          </div>
          <div class="field">
            <label>Documento codeudor</label>
            <input type="text" id="d_cod_doc" name="codeudor_documento" placeholder="CC">
          </div>
          <div class="field">
            <label>Garantía</label>
            <input type="text" id="d_garantia" name="garantia_descripcion" placeholder="Ej: Moto, TV, etc.">
          </div>
        </div>

        <div class="divider"></div>
        <div class="form-grid">
          <div class="field">
            <label>Comportamiento de pago</label>
            <select id="d_comportamiento" name="comportamiento">
              <option value="bueno">✅ Bueno</option>
              <option value="regular">⚠ Regular</option>
              <option value="clavo">🔴 Clavo</option>
            </select>
          </div>
          <div class="field field-span2">
            <label>Notas internas</label>
            <textarea id="d_notas" name="notas" placeholder="Observaciones..." style="min-height:60px"></textarea>
          </div>
          <div class="field field-span2">
            <label>Disponible en cobros <span class="required">*</span></label>
            <div style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-top:0.3rem">
              <?php foreach ($todosCobros as $cb): ?>
              <label id="label-cobro-<?= $cb['id'] ?>"
                     style="display:flex;align-items:center;gap:0.4rem;cursor:pointer;font-weight:normal;padding:0.35rem 0.65rem;border:1px solid var(--border);border-radius:var(--radius);font-size:0.82rem">
                <input type="checkbox" name="cobros[]" value="<?= $cb['id'] ?>"
                       data-cobro-id="<?= $cb['id'] ?>"
                       data-current="<?= $cb['id'] == $cobro ? '1' : '0' ?>"
                       <?= $cb['id'] == $cobro ? 'checked' : '' ?>>
                <?= htmlspecialchars($cb['nombre']) ?>
              </label>
              <?php endforeach; ?>
            </div>
            <div style="font-size:0.68rem;color:#f59e0b;font-family:var(--font-mono);margin-top:0.4rem">
              ⚠ No puedes quitar un cobro si el deudor tiene saldo pendiente en él.
            </div>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-deudor')">Cancelar</button>
      <button class="btn btn-primary" onclick="guardarDeudor(event)" id="btn-guardar-deudor">GUARDAR DEUDOR</button>
    </div>
  </div>
</div>

<?php
$extraScript = <<<JS
<script>
function editarDeudor(d) {
    document.getElementById('modal-deudor-title').textContent = 'EDITAR DEUDOR';
    document.getElementById('d_id').value            = d.id;
    document.getElementById('d_nombre').value        = d.nombre || '';
    document.getElementById('d_telefono').value      = d.telefono || '';
    document.getElementById('d_telefono_alt').value  = d.telefono_alt || '';
    document.getElementById('d_documento').value     = d.documento || '';
    document.getElementById('d_barrio').value        = d.barrio || '';
    document.getElementById('d_direccion').value     = d.direccion || '';
    document.getElementById('d_cod_nombre').value    = d.codeudor_nombre || '';
    document.getElementById('d_cod_tel').value       = d.codeudor_telefono || '';
    document.getElementById('d_cod_doc').value       = d.codeudor_documento || '';
    document.getElementById('d_garantia').value      = d.garantia_descripcion || '';
    document.getElementById('d_comportamiento').value= d.comportamiento || 'bueno';
    document.getElementById('d_notas').value         = d.notas || '';
    document.getElementById('d_lat').value           = d.lat || '';
    document.getElementById('d_lng').value           = d.lng || '';
    document.getElementById('d_place_id').value      = d.place_id || '';

    const cobrosConSaldo = d.cobros_con_saldo || [];

    document.querySelectorAll('input[name="cobros[]"]').forEach(cb => {
        const cid = parseInt(cb.value);
        cb.checked  = d.cobros_ids && d.cobros_ids.map(Number).includes(cid);
        const bloqueado = cobrosConSaldo.map(Number).includes(cid);
        cb.disabled = bloqueado;
        const lbl = document.getElementById('label-cobro-' + cid);
        if (lbl) {
            lbl.style.opacity = bloqueado ? '0.55' : '1';
            lbl.title = bloqueado ? '🔒 Tiene saldo pendiente — no se puede quitar' : '';
            lbl.style.borderColor = bloqueado ? '#f59e0b' : '';
        }
    });

    document.getElementById('mapa-wrap').style.display = 'block';
    openModal('modal-deudor');

    setTimeout(() => {
        if (typeof google === 'undefined') return;
        if (!mapaInstance) initMapaDeudor();
        else google.maps.event.trigger(mapaInstance, 'resize');
        if (d.lat && d.lng) {
            const pos = new google.maps.LatLng(parseFloat(d.lat), parseFloat(d.lng));
            mapaInstance.setCenter(pos); mapaInstance.setZoom(17); markerInstance.setPosition(pos);
        }
    }, 300);
}

async function guardarDeudor(e) {
    e.preventDefault();
    const btn    = document.getElementById('btn-guardar-deudor');
    const nombre = document.getElementById('d_nombre').value.trim();
    if (!nombre) { toast('El nombre es obligatorio', 'error'); return; }

    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner"></span> Guardando...';

    const formData = new FormData(document.getElementById('form-deudor'));
    const data     = Object.fromEntries(formData);
    data.cobros    = formData.getAll('cobros[]').map(Number);

    const res = await apiPost('/api/deudores.php', data);
    btn.disabled  = false;
    btn.innerHTML = 'GUARDAR DEUDOR';

    if (res.ok) {
        toast(res.msg || 'Deudor guardado');
        closeModal('modal-deudor');
        setTimeout(() => location.reload(), 800);
    } else {
        toast(res.msg || 'Error al guardar', 'error');
    }
}

// Reset al crear nuevo
document.querySelector('[onclick="openModal(\'modal-deudor\')"]')?.addEventListener('click', () => {
    document.getElementById('modal-deudor-title').textContent = 'NUEVO DEUDOR';
    document.getElementById('form-deudor').reset();
    document.getElementById('d_id').value = '';
    document.querySelectorAll('input[name="cobros[]"]').forEach(cb => {
        cb.checked  = cb.dataset.current === '1';
        cb.disabled = false;
        const lbl = document.getElementById('label-cobro-' + cb.dataset.cobroId);
        if (lbl) { lbl.style.opacity='1'; lbl.title=''; lbl.style.borderColor=''; }
    });
    setTimeout(() => { if (typeof google !== 'undefined') initMapaDeudor(); }, 300);
});

// ── Mapa ─────────────────────────────────────────────────────
let mapaInstance = null, markerInstance = null, autocompleteInstance = null;

function initMapaDeudor() {
    if (mapaInstance) return;
    const centro = { lat: 5.5353, lng: -73.3678 };
    mapaInstance = new google.maps.Map(document.getElementById('mapa-deudor'), {
        center: centro, zoom: 15,
        mapTypeControl: false, streetViewControl: false, fullscreenControl: false,
    });
    markerInstance = new google.maps.Marker({ position: centro, map: mapaInstance, draggable: true });
    markerInstance.addListener('dragend', () => {
        const pos = markerInstance.getPosition();
        document.getElementById('d_lat').value      = pos.lat().toFixed(7);
        document.getElementById('d_lng').value      = pos.lng().toFixed(7);
        document.getElementById('d_place_id').value = '';
    });
    autocompleteInstance = new google.maps.places.Autocomplete(
        document.getElementById('d_direccion'),
        { componentRestrictions: { country: 'co' }, fields: ['geometry','formatted_address','place_id'] }
    );
    autocompleteInstance.addListener('place_changed', () => {
        const place = autocompleteInstance.getPlace();
        if (!place.geometry?.location) return;
        const lat = place.geometry.location.lat();
        const lng = place.geometry.location.lng();
        document.getElementById('d_lat').value      = lat.toFixed(7);
        document.getElementById('d_lng').value      = lng.toFixed(7);
        document.getElementById('d_place_id').value = place.place_id || '';
        const pos = new google.maps.LatLng(lat, lng);
        mapaInstance.setCenter(pos); mapaInstance.setZoom(17); markerInstance.setPosition(pos);
        document.getElementById('mapa-wrap').style.display = 'block';
        setTimeout(() => google.maps.event.trigger(mapaInstance, 'resize'), 100);
    });
}
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>