<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
if (!canDo('puede_ver_deudores')) { include __DIR__ . '/403.php'; exit; }

$db    = getDB();
$cobro = cobroActivo();

// ---- Filtros ----
$buscar = trim($_GET['q'] ?? '');
$filtro = $_GET['estado'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 15;
$offset = ($page - 1) * $limit;

// ---- Query base ----
$where  = ['EXISTS (SELECT 1 FROM deudor_cobro dc WHERE dc.deudor_id=d.id AND dc.cobro_id=?)'];
$params = [$cobro];

if ($buscar) {
    $where[]  = '(d.nombre LIKE ? OR d.telefono LIKE ? OR d.documento LIKE ?)';
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}

if ($filtro === 'con_deuda') {
    $where[] = 'EXISTS (SELECT 1 FROM prestamos p WHERE p.deudor_id=d.id AND p.estado NOT IN ("pagado","renovado","refinanciado"))';
} elseif ($filtro === 'al_dia') {
    $where[] = 'NOT EXISTS (SELECT 1 FROM prestamos p WHERE p.deudor_id=d.id AND p.estado="en_mora")';
} elseif ($filtro === 'en_mora') {
    $where[] = 'EXISTS (SELECT 1 FROM prestamos p WHERE p.deudor_id=d.id AND p.estado="en_mora")';
}

$whereSQL = implode(' AND ', $where);

// ---- Total ----
$stmtTotal = $db->prepare("SELECT COUNT(*) FROM deudores d WHERE $whereSQL");
$stmtTotal->execute($params);
$total = $stmtTotal->fetchColumn();
$totalPags = ceil($total / $limit);

// ---- Lista ----
$sql = "
    SELECT d.*,
        COUNT(DISTINCT p.id)                                        AS total_prestamos,
        COALESCE(SUM(CASE WHEN p.estado NOT IN ('pagado','renovado','refinanciado') THEN p.saldo_pendiente END), 0) AS saldo_total,
        MAX(CASE WHEN p.estado='en_mora' THEN 1 ELSE 0 END)        AS tiene_mora,
        MAX(p.created_at)                                           AS ultimo_prestamo
    FROM deudores d
    LEFT JOIN prestamos p ON p.deudor_id = d.id AND p.cobro_id = ?
    WHERE $whereSQL
    GROUP BY d.id
    ORDER BY d.nombre ASC
    LIMIT $limit OFFSET $offset
";
$stmt = $db->prepare($sql);
$stmt->execute(array_merge([$cobro], $params));
$deudores = $stmt->fetchAll();

// ---- Stats rápidas ----
$stmtStats = $db->prepare("
    SELECT
        COUNT(DISTINCT d.id) AS total,
        COUNT(DISTINCT CASE WHEN p.estado='en_mora' THEN d.id END) AS en_mora,
        COUNT(DISTINCT CASE WHEN p.estado IN ('activo','en_mora','en_acuerdo') THEN d.id END) AS activos
    FROM deudores d
    LEFT JOIN prestamos p ON p.deudor_id=d.id AND p.cobro_id=? AND p.estado NOT IN ('anulado')
    WHERE EXISTS (SELECT 1 FROM deudor_cobro dc WHERE dc.deudor_id=d.id AND dc.cobro_id=?)
");
$stmtStats->execute([$cobro, $cobro]);
$stats = $stmtStats->fetch();

// Lista de todos los cobros para el form
$todosLosCobros = $db->prepare("SELECT id, nombre FROM cobros WHERE activo=1 ORDER BY nombre");
$todosLosCobros->execute();
$todosCobros = $todosLosCobros->fetchAll();

$pageTitle   = 'Deudores';
$pageSection = 'Deudores';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header-row">
  <div>
    <h1>DEUDORES</h1>
    <p>// <?= $total ?> registros encontrados</p>
  </div>
  <?php if (canDo('puede_crear_deudor')): ?>
  <button class="btn btn-primary" onclick="openModal('modal-deudor')">+ Nuevo Deudor</button>
  <?php endif; ?>
</div>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:1.5rem">
  <div class="stat-card">
    <div class="stat-label">Total Deudores</div>
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
</div>

<!-- Filtros -->
<div class="filter-bar mb-2">
  <form method="GET" style="display:flex;gap:0.5rem;flex-wrap:wrap;width:100%">
    <div class="search-bar" style="flex:1;min-width:200px">
      <span class="search-icon">⌕</span>
      <input type="text" name="q" value="<?= htmlspecialchars($buscar) ?>" placeholder="Buscar nombre, teléfono, documento...">
    </div>
    <select name="estado" onchange="this.form.submit()" style="width:auto">
      <option value="">Todos</option>
      <option value="con_deuda"  <?= $filtro==='con_deuda'  ? 'selected':'' ?>>Con deuda activa</option>
      <option value="al_dia"     <?= $filtro==='al_dia'     ? 'selected':'' ?>>Al día</option>
      <option value="en_mora"    <?= $filtro==='en_mora'    ? 'selected':'' ?>>En mora</option>
    </select>
    <button type="submit" class="btn btn-secondary">Buscar</button>
    <?php if ($buscar || $filtro): ?>
    <a href="/pages/deudores.php" class="btn btn-ghost">✕ Limpiar</a>
    <?php endif; ?>
  </form>
</div>

<!-- Tabla -->
<div class="card">
  <?php if (empty($deudores)): ?>
    <div class="empty-state">
      <span class="empty-icon">◉</span>
      <p>No se encontraron deudores<?= $buscar ? " para \"$buscar\"" : '' ?></p>
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Deudor</th>
          <th>Teléfono</th>
          <th>Documento</th>
          <th>Préstamos</th>
          <th>Saldo Total</th>
          <th>Estado</th>
          <th>Comportamiento</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($deudores as $d): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:0.65rem">
              <div class="avatar avatar-sm"><?= strtoupper(substr($d['nombre'],0,1)) ?></div>
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
          <td class="text-mono"><?= $d['total_prestamos'] ?></td>
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
              $compClass = $comp === 'bueno' ? 'badge-green' : ($comp === 'regular' ? 'badge-orange' : 'badge-red');
            ?>
            <span class="badge <?= $compClass ?>"><?= ucfirst($comp) ?></span>
          </td>
          <td>
            <div class="btn-group">
              <a href="/pages/deudor_detalle.php?id=<?= $d['id'] ?>" class="btn btn-info btn-sm">Ver</a>
              <?php if (canDo('puede_editar_deudor')): ?>
              <?php
                $cobrosD = $db->prepare("SELECT cobro_id FROM deudor_cobro WHERE deudor_id=?");
                $cobrosD->execute([$d['id']]);
                $d['cobros_ids'] = array_column($cobrosD->fetchAll(), 'cobro_id');
              ?>
              <button class="btn btn-ghost btn-sm" onclick="editarDeudor(<?= htmlspecialchars(json_encode($d)) ?>)">Editar</button>
              <?php endif; ?>
              <?php if (canDo('puede_crear_deudor')): ?>
              <a href="/pages/prestamos.php?action=nuevo&deudor=<?= $d['id'] ?>" class="btn btn-success btn-sm">+ Préstamo</a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Paginación -->
  <?php if ($totalPags > 1): ?>
  <div class="pagination">
    <?php if ($page > 1): ?>
    <a href="?page=<?= $page-1 ?>&q=<?= urlencode($buscar) ?>&estado=<?= $filtro ?>" class="page-btn">‹</a>
    <?php endif; ?>
    <?php for ($i = max(1,$page-2); $i <= min($totalPags,$page+2); $i++): ?>
    <a href="?page=<?= $i ?>&q=<?= urlencode($buscar) ?>&estado=<?= $filtro ?>"
       class="page-btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($page < $totalPags): ?>
    <a href="?page=<?= $page+1 ?>&q=<?= urlencode($buscar) ?>&estado=<?= $filtro ?>" class="page-btn">›</a>
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
                    placeholder="Escribe la dirección para buscar..."
                    autocomplete="off">
              <input type="hidden" id="d_lat"      name="lat">
              <input type="hidden" id="d_lng"      name="lng">
              <input type="hidden" id="d_place_id" name="place_id">
          </div>

          <!-- Mini mapa con pin draggable -->
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
        <p style="font-family:var(--font-mono);font-size:0.68rem;color:var(--muted);margin-bottom:0.75rem;letter-spacing:1px;text-transform:uppercase">Codeudor (opcional)</p>

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
              <option value="bueno">Bueno</option>
              <option value="regular">Regular</option>
              <option value="malo">Malo</option>
            </select>
          </div>
          <div class="field field-span2">
            <label>Notas internas</label>
            <textarea id="d_notas" name="notas" placeholder="Observaciones..." style="min-height:60px"></textarea>
          </div>
          <div class="field field-span2" id="campo-cobros-deudor">
            <label>Disponible en cobros <span class="required">*</span></label>
            <div style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-top:0.3rem">
              <?php foreach ($todosCobros as $cb): ?>
              <label style="display:flex;align-items:center;gap:0.4rem;cursor:pointer;font-weight:normal">
                <input type="checkbox" name="cobros[]" value="<?= $cb['id'] ?>"
                  data-current="<?= $cb['id'] == $cobro ? '1' : '0' ?>"
                  <?= $cb['id'] == $cobro ? 'checked' : '' ?>>
                <?= htmlspecialchars($cb['nombre']) ?>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-deudor')">Cancelar</button>
      <button class="btn btn-primary" onclick="guardarDeudor(event)" id="btn-guardar-deudor">
        GUARDAR DEUDOR
      </button>
    </div>
  </div>
</div>

<?php
$extraScript = <<<JS
<script>
function editarDeudor(d) {
    document.getElementById('modal-deudor-title').textContent = 'EDITAR DEUDOR';
    document.getElementById('d_id').value           = d.id;
    document.getElementById('d_nombre').value       = d.nombre || '';
    document.getElementById('d_telefono').value     = d.telefono || '';
    document.getElementById('d_telefono_alt').value = d.telefono_alt || '';
    document.getElementById('d_documento').value    = d.documento || '';
    document.getElementById('d_barrio').value       = d.barrio || '';
    document.getElementById('d_direccion').value    = d.direccion || '';
    document.getElementById('d_cod_nombre').value   = d.codeudor_nombre || '';
    document.getElementById('d_cod_tel').value      = d.codeudor_telefono || '';
    document.getElementById('d_cod_doc').value      = d.codeudor_documento || '';
    document.getElementById('d_garantia').value     = d.garantia_descripcion || '';
    document.getElementById('d_comportamiento').value = d.comportamiento || 'bueno';
    document.getElementById('d_notas').value        = d.notas || '';

    document.querySelectorAll('input[name="cobros[]"]').forEach(cb => {
        cb.checked = d.cobros_ids && d.cobros_ids.includes(parseInt(cb.value));
    });

    // ── Ubicación GPS ─────────────────────────────────────────
    document.getElementById('d_lat').value      = d.lat || '';
    document.getElementById('d_lng').value      = d.lng || '';
    document.getElementById('d_place_id').value = d.place_id || '';

    document.getElementById('mapa-wrap').style.display = 'block';

    openModal('modal-deudor');

    setTimeout(() => {
        if (typeof google === 'undefined') return;

        if (!mapaInstance) {
            initMapaDeudor();
        } else {
            google.maps.event.trigger(mapaInstance, 'resize');
        }

        if (d.lat && d.lng) {
            const pos = new google.maps.LatLng(parseFloat(d.lat), parseFloat(d.lng));
            mapaInstance.setCenter(pos);
            mapaInstance.setZoom(17);
            markerInstance.setPosition(pos);
        } else {
            mapaInstance.setCenter({ lat: 4.5709, lng: -74.2973 });
            mapaInstance.setZoom(6);
        }
    }, 300);
    // ──────────────────────────────────────────────────────────
}

async function guardarDeudor(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-guardar-deudor');
    const form = document.getElementById('form-deudor');
    const nombre = document.getElementById('d_nombre').value.trim();
    if (!nombre) { toast('El nombre es obligatorio', 'error'); return; }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Guardando...';

    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    // cobros[] viene como array — recolectar todos los checks
    data.cobros = formData.getAll('cobros[]').map(Number);
    const res  = await apiPost('/api/deudores.php', data);

    btn.disabled = false;
    btn.innerHTML = 'GUARDAR DEUDOR';

    if (res.ok) {
        toast(res.msg || 'Deudor guardado correctamente');
        closeModal('modal-deudor');
        setTimeout(() => location.reload(), 800);
    } else {
        toast(res.msg || 'Error al guardar', 'error');
    }
}

document.querySelector('[onclick="openModal(\'modal-deudor\')"]')?.addEventListener('click', () => {
    document.getElementById('modal-deudor-title').textContent = 'NUEVO DEUDOR';
    document.getElementById('form-deudor').reset();
    document.getElementById('d_id').value = '';
    // Resetear checkboxes — marcar solo cobro actual
    document.querySelectorAll('input[name="cobros[]"]').forEach(cb => {
        cb.checked = cb.dataset.current === '1';
    });
});


let mapaInstance   = null;
let markerInstance = null;
let autocompleteInstance = null;

function initMapaDeudor() {
    // Evitar doble inicialización
    if (mapaInstance) return;

    const centro = { lat: 5.5353, lng: -73.3678 }; // Tunja por defecto

    mapaInstance = new google.maps.Map(document.getElementById('mapa-deudor'), {
        center         : centro,
        zoom           : 15,
        mapTypeControl : false,
        streetViewControl: false,
        fullscreenControl: false,
    });

    markerInstance = new google.maps.Marker({
        position : centro,
        map      : mapaInstance,
        draggable: true,
        title    : 'Arrastra para ajustar la ubicación',
    });

    // Cuando el pin se arrastra → actualizar lat/lng
    markerInstance.addListener('dragend', function () {
        const pos = markerInstance.getPosition();
        document.getElementById('d_lat').value = pos.lat().toFixed(7);
        document.getElementById('d_lng').value = pos.lng().toFixed(7);
        document.getElementById('d_place_id').value = ''; // ya no es el place_id original
    });

    // Autocomplete en el campo de dirección
    autocompleteInstance = new google.maps.places.Autocomplete(
        document.getElementById('d_direccion'),
        {
            componentRestrictions: { country: 'co' },
            fields: ['geometry', 'formatted_address', 'place_id'],
        }
    );

    autocompleteInstance.addListener('place_changed', function () {
        const place = autocompleteInstance.getPlace();
        if (!place.geometry || !place.geometry.location) return;

        const lat = place.geometry.location.lat();
        const lng = place.geometry.location.lng();

        // Actualizar campos ocultos
        document.getElementById('d_lat').value      = lat.toFixed(7);
        document.getElementById('d_lng').value      = lng.toFixed(7);
        document.getElementById('d_place_id').value = place.place_id || '';

        // Mover mapa y pin
        const pos = new google.maps.LatLng(lat, lng);
        mapaInstance.setCenter(pos);
        mapaInstance.setZoom(17);
        markerInstance.setPosition(pos);

        // Mostrar el mapa si estaba oculto
        document.getElementById('mapa-wrap').style.display = 'block';

        // Pequeño delay para que el mapa redibuje bien
        setTimeout(() => google.maps.event.trigger(mapaInstance, 'resize'), 100);
    });
}

function mostrarMapaConUbicacion(lat, lng) {
    document.getElementById('mapa-wrap').style.display = 'block';
    setTimeout(() => {
        initMapaDeudor();
        const pos = new google.maps.LatLng(parseFloat(lat), parseFloat(lng));
        mapaInstance.setCenter(pos);
        mapaInstance.setZoom(17);
        markerInstance.setPosition(pos);
        google.maps.event.trigger(mapaInstance, 'resize');
    }, 150);
}


document.querySelector('[onclick="openModal(\'modal-deudor\')"]')
    ?.addEventListener('click', () => {
        setTimeout(() => {
            if (typeof google !== 'undefined') initMapaDeudor();
        }, 300);
    });
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>