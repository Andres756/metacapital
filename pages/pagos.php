<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
if (!canDo('puede_registrar_pago')) { include __DIR__ . '/403.php'; exit; }

$db    = getDB();
$cobro = cobroActivo();

// Filtros historial
$buscar     = trim($_GET['q'] ?? '');
$filtroFecha= $_GET['fecha'] ?? date('Y-m-d');
$verTodos   = isset($_GET['todos']);
$page       = max(1, (int)($_GET['page'] ?? 1));
$limit      = 20;
$offset     = ($page - 1) * $limit;

// Preselección desde URL
$prestamoId = (int)($_GET['prestamo'] ?? 0);
$cuotaId    = (int)($_GET['cuota']    ?? 0);

// Datos del préstamo preseleccionado
$prestamoInfo = null;
$cuotasDisp   = [];
if ($prestamoId) {
    $s = $db->prepare("SELECT p.*, d.nombre AS deudor_nombre, d.telefono AS deudor_tel
        FROM prestamos p JOIN deudores d ON d.id=p.deudor_id
        WHERE p.id=? AND p.cobro_id=? AND p.estado IN ('activo','en_mora','en_acuerdo')");
    $s->execute([$prestamoId, $cobro]);
    $prestamoInfo = $s->fetch();

    if ($prestamoInfo) {
        $sc = $db->prepare("SELECT * FROM cuotas WHERE prestamo_id=? AND estado IN ('pendiente','parcial') ORDER BY numero_cuota ASC");
        $sc->execute([$prestamoId]);
        $cuotasDisp = $sc->fetchAll();
    }
}

// Cuotas vencidas hoy para cobro rápido
$stmtHoy = $db->prepare("
    SELECT cu.*, d.nombre AS deudor, d.telefono AS tel, p.id AS prestamo_id,
           p.frecuencia_pago, p.valor_cuota, p.dias_mora
    FROM cuotas cu
    JOIN prestamos p ON p.id = cu.prestamo_id
    JOIN deudores  d ON d.id = p.deudor_id
    WHERE cu.cobro_id=? AND cu.estado IN ('pendiente','parcial')
      AND cu.fecha_vencimiento <= CURDATE()
      AND p.estado IN ('activo','en_mora','en_acuerdo')
    ORDER BY cu.fecha_vencimiento ASC, d.nombre ASC
    LIMIT 50
");
$stmtHoy->execute([$cobro]);
$cuotasHoy = $stmtHoy->fetchAll();

// Historial de pagos
$whereH  = ['pg.cobro_id = ?'];
$paramsH = [$cobro];
if ($buscar) {
    $whereH[]  = 'd.nombre LIKE ?';
    $paramsH[] = "%$buscar%";
}
if (!$verTodos) {
    $whereH[]  = 'pg.fecha_pago = ?';
    $paramsH[] = $filtroFecha;
}
$whereHSQL = implode(' AND ', $whereH);

$stmtTotal = $db->prepare("SELECT COUNT(*) FROM pagos pg JOIN deudores d ON d.id=pg.deudor_id WHERE $whereHSQL AND (pg.anulado=0 OR pg.anulado IS NULL)");
$stmtTotal->execute($paramsH);
$totalPags = ceil($stmtTotal->fetchColumn() / $limit);

$stmtH = $db->prepare("
    SELECT pg.*, d.nombre AS deudor, c.nombre AS cuenta, u.nombre AS usuario,
           cu.numero_cuota, pr.monto_prestado
    FROM pagos pg
    JOIN deudores d  ON d.id  = pg.deudor_id
    JOIN cuotas cu   ON cu.id = pg.cuota_id
    JOIN prestamos pr ON pr.id = pg.prestamo_id
    LEFT JOIN cuentas c  ON c.id  = pg.cuenta_id
    LEFT JOIN usuarios u ON u.id  = pg.usuario_id
    WHERE $whereHSQL AND (pg.anulado=0 OR pg.anulado IS NULL)
    ORDER BY pg.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmtH->execute($paramsH);
$historial = $stmtH->fetchAll();

// Stats del día
$stmtStats = $db->prepare("
    SELECT
        COUNT(*)                    AS total_pagos,
        COALESCE(SUM(monto_pagado),0) AS total_monto
    FROM pagos WHERE cobro_id=? AND fecha_pago=CURDATE() AND (anulado=0 OR anulado IS NULL)
");
$stmtStats->execute([$cobro]);
$statsHoy = $stmtStats->fetch();

// Cuentas para el form
$cuentasQ = $db->prepare("SELECT id, nombre, tipo FROM cuentas WHERE cobro_id=? AND activa=1 ORDER BY nombre");
$cuentasQ->execute([$cobro]);
$cuentas = $cuentasQ->fetchAll();

// Buscar préstamos activos (para búsqueda en modal)
$activosQ = $db->prepare("
    SELECT p.id, p.saldo_pendiente, p.valor_cuota, p.estado, d.nombre AS deudor, d.telefono
    FROM prestamos p JOIN deudores d ON d.id=p.deudor_id
    WHERE p.cobro_id=? AND p.estado IN ('activo','en_mora','en_acuerdo')
    ORDER BY d.nombre ASC LIMIT 100
");
$activosQ->execute([$cobro]);
$prestamosActivos = $activosQ->fetchAll();

$pageTitle   = 'Pagos';
$pageSection = 'Registrar Pago';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header page-header-row">
  <div>
    <h1>PAGOS</h1>
    <p>// Cobro del día · <?= date('d \d\e F \d\e Y') ?></p>
  </div>
  <button class="btn btn-primary" onclick="openModal('modal-pago')">+ Registrar Pago</button>
</div>

<!-- Stats hoy -->
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:1.5rem">
  <div class="stat-card purple">
    <div class="stat-label">Cobrado Hoy</div>
    <div class="stat-value"><?= fmt($statsHoy['total_monto']) ?></div>
    <div class="stat-sub"><?= $statsHoy['total_pagos'] ?> pagos registrados</div>
  </div>
  <div class="stat-card orange">
    <div class="stat-label">Cuotas Vencidas</div>
    <div class="stat-value"><?= count($cuotasHoy) ?></div>
    <div class="stat-sub">Pendientes de cobro</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Por Cobrar Hoy</div>
    <div class="stat-value"><?= fmt(array_sum(array_column($cuotasHoy, 'saldo_cuota'))) ?></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">

<!-- CUOTAS PENDIENTES -->
<div class="card">
  <div class="card-header">
    <span class="card-title">COBROS PENDIENTES</span>
    <span class="badge badge-orange"><?= count($cuotasHoy) ?></span>
  </div>
  <?php if (empty($cuotasHoy)): ?>
    <div class="empty-state"><span class="empty-icon">✓</span><p>Todo al día por hoy</p></div>
  <?php else: ?>
  <div style="max-height:520px;overflow-y:auto">
    <?php foreach ($cuotasHoy as $c):
      $dias = (int)$c['dias_mora'];
    ?>
    <div class="schedule-row vencida" style="padding:0.75rem 1rem">
      <div class="schedule-dot vencida"></div>
      <div style="flex:1;min-width:0">
        <div style="font-weight:600;font-size:0.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
          <?= htmlspecialchars($c['deudor']) ?>
        </div>
        <div style="font-family:var(--font-mono);font-size:0.65rem;color:var(--muted)">
          <?= $dias > 0 ? $dias.'d mora' : 'Vence hoy' ?> ·
          Cuota #<?= $c['numero_cuota'] ?> ·
          <?= htmlspecialchars($c['tel'] ?? '—') ?>
        </div>
      </div>
      <div style="text-align:right;margin-right:0.75rem">
        <div style="font-family:var(--font-mono);font-weight:600;color:var(--warn)"><?= fmt($c['saldo_cuota']) ?></div>
        <div style="font-size:0.62rem;color:var(--muted)"><?= date('d M', strtotime($c['fecha_vencimiento'])) ?></div>
      </div>
      <button class="btn btn-success btn-sm"
        onclick="pagarRapido(<?= $c['prestamo_id'] ?>, <?= $c['id'] ?>, <?= $c['saldo_cuota'] ?>, '<?= htmlspecialchars(addslashes($c['deudor'])) ?>')">
        Pagar
      </button>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- HISTORIAL DEL DÍA -->
<div class="card">
  <div class="card-header">
    <span class="card-title">HISTORIAL</span>
    <div style="display:flex;gap:0.5rem;align-items:center">
      <input type="date" id="filtro-fecha" value="<?= htmlspecialchars($filtroFecha) ?>"
             onchange="filtrarFecha(this.value)"
             style="width:auto;font-size:0.72rem;padding:0.3rem 0.5rem">
      <a href="?todos=1" class="btn btn-ghost btn-sm">Ver todos</a>
    </div>
  </div>
  <?php if (empty($historial)): ?>
    <div class="empty-state"><span class="empty-icon">◈</span><p>Sin pagos <?= $verTodos ? 'registrados' : 'en esta fecha' ?></p></div>
  <?php else: ?>
  <div style="max-height:520px;overflow-y:auto">
    <?php foreach ($historial as $pg): ?>
    <div class="schedule-row">
      <div class="schedule-dot paid"></div>
      <div style="flex:1;min-width:0">
        <div style="font-weight:600;font-size:0.82rem"><?= htmlspecialchars($pg['deudor']) ?></div>
        <div style="font-family:var(--font-mono);font-size:0.65rem;color:var(--muted)">
          Cuota #<?= $pg['numero_cuota'] ?> · <?= htmlspecialchars($pg['cuenta'] ?? 'Efectivo') ?> ·
          <span class="badge badge-muted" style="font-size:0.55rem"><?= ucfirst($pg['metodo_pago']) ?></span>
        </div>
      </div>
      <div style="text-align:right;margin-right:0.5rem">
        <div style="font-family:var(--font-mono);font-weight:600;color:var(--accent)"><?= fmt($pg['monto_pagado']) ?></div>
        <div style="font-size:0.62rem;color:var(--muted)"><?= date('H:i', strtotime($pg['created_at'])) ?></div>
      </div>
      <a href="/pages/prestamo_detalle.php?id=<?= $pg['prestamo_id'] ?>" class="btn btn-ghost btn-sm">Ver</a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if ($totalPags > 1): ?>
  <div class="pagination">
    <?php for ($i=1;$i<=$totalPags;$i++): ?>
    <a href="?fecha=<?=$filtroFecha?>&page=<?=$i?>" class="page-btn <?=$i==$page?'active':''?>"><?=$i?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

</div><!-- /grid -->

<!-- ====== MODAL REGISTRAR PAGO ====== -->
<div class="modal-overlay" id="modal-pago">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h2>REGISTRAR PAGO</h2>
      <button class="modal-close" onclick="closeModal('modal-pago')">&#10005;</button>
    </div>
    <div class="modal-body">

      <!-- Paso 1: buscar préstamo -->
      <div id="paso1">
        <div class="field mb-2">
          <label>Buscar deudor o # préstamo</label>
          <input type="text" id="buscar-prestamo" placeholder="Escribe nombre o número..."
                 oninput="filtrarPrestamos(this.value)" autocomplete="off">
        </div>
        <div id="lista-prestamos" style="max-height:300px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--radius)">
          <?php foreach ($prestamosActivos as $pr): ?>
          <div class="schedule-row" style="cursor:pointer;padding:0.75rem 1rem"
               onclick="seleccionarPrestamo(<?= $pr['id'] ?>, '<?= htmlspecialchars(addslashes($pr['deudor'])) ?>', <?= $pr['saldo_pendiente'] ?>, <?= $pr['valor_cuota'] ?>)"
               data-nombre="<?= htmlspecialchars(strtolower($pr['deudor'])) ?>"
               data-id="<?= $pr['id'] ?>">
            <div style="flex:1">
              <div style="font-weight:600;font-size:0.85rem"><?= htmlspecialchars($pr['deudor']) ?></div>
              <div style="font-family:var(--font-mono);font-size:0.65rem;color:var(--muted)">
                Préstamo #<?= $pr['id'] ?> · Saldo: <?= fmt($pr['saldo_pendiente']) ?>
              </div>
            </div>
            <span class="badge <?= $pr['estado']==='en_mora'?'badge-orange':($pr['estado']==='en_acuerdo'?'badge-blue':'badge-purple') ?>">
              <?= strtoupper($pr['estado']) ?>
            </span>
          </div>
          <?php endforeach; ?>
          <?php if (empty($prestamosActivos)): ?>
          <div class="empty-state" style="padding:2rem"><p>Sin préstamos activos</p></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Paso 2: registrar pago -->
      <div id="paso2" style="display:none">
        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem;padding:0.75rem;background:var(--bg);border-radius:var(--radius);border:1px solid var(--accent)">
          <div class="avatar avatar-sm" id="pago-avatar">?</div>
          <div style="flex:1">
            <div style="font-weight:600" id="pago-deudor-nombre">—</div>
            <div style="font-family:var(--font-mono);font-size:0.68rem;color:var(--muted)" id="pago-deudor-info">—</div>
          </div>
          <button class="btn btn-ghost btn-sm" onclick="volverPaso1()">Cambiar</button>
        </div>

        <form id="form-pago">
          <input type="hidden" name="prestamo_id" id="pago-prestamo-id">
          <div class="form-grid">
            <div class="field field-span2">
              <label>Cuota a aplicar <span class="required">*</span></label>
              <select name="cuota_id" id="pago-cuota-select" onchange="actualizarMontoCuota(this)">
                <option value="">— Cargando cuotas... —</option>
              </select>
            </div>
            <div class="field">
              <label>Monto recibido <span class="required">*</span></label>
              <input type="number" name="monto_pagado" id="pago-monto" step="1000" min="1"
                     placeholder="0" required>
            </div>
            <div class="field">
              <label>Fecha de pago</label>
              <input type="date" name="fecha_pago" id="pago-fecha" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="field">
              <label>Cuenta <span class="required">*</span></label>
              <select name="cuenta_id" id="pago-cuenta" required>
                <option value="">— Seleccionar —</option>
                <?php foreach ($cuentas as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label>Método</label>
              <select name="metodo_pago">
                <option value="efectivo">Efectivo</option>
                <option value="nequi">Nequi</option>
                <option value="transferencia">Transferencia</option>
                <option value="bancolombia">Bancolombia</option>
                <option value="daviplata">Daviplata</option>
              </select>
            </div>
            <div class="field field-span2">
              <label>Observación</label>
              <input type="text" name="observacion" placeholder="Opcional">
            </div>
          </div>

          <!-- Preview pago -->
          <div id="preview-pago" style="display:none;margin-top:1rem;padding:0.85rem 1rem;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);font-family:var(--font-mono);font-size:0.75rem">
            <div style="display:flex;justify-content:space-between;margin-bottom:0.35rem">
              <span style="color:var(--muted)">Saldo cuota</span>
              <span id="prev-saldo-cuota">$0</span>
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:0.35rem">
              <span style="color:var(--muted)">Monto a pagar</span>
              <span id="prev-monto" style="color:var(--accent)">$0</span>
            </div>
            <div style="display:flex;justify-content:space-between;border-top:1px solid var(--border);padding-top:0.35rem">
              <span style="color:var(--muted)">Quedará pendiente</span>
              <span id="prev-pendiente" style="color:var(--warn)">$0</span>
            </div>
          </div>
        </form>
      </div>

    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-pago')">Cancelar</button>
      <button class="btn btn-primary" id="btn-registrar-pago" onclick="registrarPago()" style="display:none">
        REGISTRAR PAGO
      </button>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
// ---- DATOS PRESTAMOS (para busqueda rapida) ----
var prestamosData = <?= json_encode($prestamosActivos) ?>;

function filtrarPrestamos(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('#lista-prestamos .schedule-row').forEach(function(row) {
        var nombre = row.dataset.nombre || '';
        var id     = row.dataset.id || '';
        row.style.display = (!q || nombre.includes(q) || id.includes(q)) ? '' : 'none';
    });
}

function seleccionarPrestamo(id, nombre, saldo, valorCuota) {
    document.getElementById('pago-prestamo-id').value  = id;
    document.getElementById('pago-avatar').textContent = nombre.charAt(0).toUpperCase();
    document.getElementById('pago-deudor-nombre').textContent = nombre;
    document.getElementById('pago-deudor-info').textContent   = 'Préstamo #' + id + ' · Saldo: ' + fmt(saldo);

    document.getElementById('paso1').style.display = 'none';
    document.getElementById('paso2').style.display = 'block';
    document.getElementById('btn-registrar-pago').style.display = 'inline-flex';

    // Cargar cuotas del préstamo
    cargarCuotas(id);
}

async function cargarCuotas(prestamoId) {
    var sel = document.getElementById('pago-cuota-select');
    sel.innerHTML = '<option value="">Cargando...</option>';
    var res = await apiGet('/api/pagos.php?action=cuotas&prestamo_id=' + prestamoId);
    sel.innerHTML = '';
    if (res.ok && res.cuotas.length) {
        res.cuotas.forEach(function(c) {
            var opt = document.createElement('option');
            opt.value = c.id;
            opt.dataset.saldo = c.saldo_cuota;
            opt.textContent = 'Cuota #' + c.numero_cuota + ' — ' + c.fecha_vencimiento + ' — ' + fmt(c.saldo_cuota) + (c.estado==='parcial'?' (PARCIAL)':'');
            sel.appendChild(opt);
        });
        actualizarMontoCuota(sel);
    } else {
        sel.innerHTML = '<option value="">Sin cuotas pendientes</option>';
    }
}

function actualizarMontoCuota(sel) {
    var opt   = sel.options[sel.selectedIndex];
    var saldo = parseFloat(opt ? opt.dataset.saldo : 0) || 0;
    document.getElementById('pago-monto').value = saldo || '';
    actualizarPreview();
}

document.getElementById('pago-monto').addEventListener('input', actualizarPreview);

function actualizarPreview() {
    var sel    = document.getElementById('pago-cuota-select');
    var opt    = sel.options[sel.selectedIndex];
    var saldo  = parseFloat(opt ? opt.dataset.saldo : 0) || 0;
    var monto  = parseFloat(document.getElementById('pago-monto').value) || 0;
    var pend   = Math.max(0, saldo - monto);

    document.getElementById('prev-saldo-cuota').textContent = fmt(saldo);
    document.getElementById('prev-monto').textContent       = fmt(monto);
    document.getElementById('prev-pendiente').textContent   = fmt(pend);
    document.getElementById('prev-pendiente').style.color   = pend > 0 ? 'var(--warn)' : 'var(--accent)';
    document.getElementById('preview-pago').style.display   = monto > 0 ? 'block' : 'none';
}

function volverPaso1() {
    document.getElementById('paso1').style.display = 'block';
    document.getElementById('paso2').style.display = 'none';
    document.getElementById('btn-registrar-pago').style.display = 'none';
    document.getElementById('buscar-prestamo').value = '';
    filtrarPrestamos('');
}

function pagarRapido(prestamoId, cuotaId, monto, deudor) {
    openModal('modal-pago');
    // Esperar a que abra el modal y luego seleccionar
    setTimeout(function() {
        seleccionarPrestamo(prestamoId, deudor, monto, monto);
        setTimeout(function() {
            var sel = document.getElementById('pago-cuota-select');
            // Esperar que carguen las cuotas
            var intento = setInterval(function() {
                for (var i = 0; i < sel.options.length; i++) {
                    if (sel.options[i].value == cuotaId) {
                        sel.value = cuotaId;
                        actualizarMontoCuota(sel);
                        clearInterval(intento);
                        break;
                    }
                }
            }, 200);
            setTimeout(function() { clearInterval(intento); }, 3000);
        }, 300);
    }, 100);
}

async function registrarPago() {
    var prestamoId = document.getElementById('pago-prestamo-id').value;
    var cuotaId    = document.getElementById('pago-cuota-select').value;
    var monto      = document.getElementById('pago-monto').value;
    var cuentaId   = document.getElementById('pago-cuenta').value;

    if (!prestamoId) { toast('Selecciona un préstamo', 'error'); return; }
    if (!cuotaId)    { toast('Selecciona la cuota', 'error'); return; }
    if (!monto || parseFloat(monto) <= 0) { toast('Ingresa el monto', 'error'); return; }
    if (!cuentaId)   { toast('Selecciona la cuenta donde entró el dinero', 'error'); return; }

    var btn = document.getElementById('btn-registrar-pago');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Registrando...';

    var data = Object.fromEntries(new FormData(document.getElementById('form-pago')));
    data.action = 'pagar';

    var res = await apiPost('/api/prestamos.php', data);
    btn.disabled = false;
    btn.innerHTML = 'REGISTRAR PAGO';

    if (res.ok) {
        toast('Pago registrado correctamente ✓');
        closeModal('modal-pago');
        setTimeout(function() { location.reload(); }, 800);
    } else {
        toast(res.msg || 'Error al registrar', 'error');
    }
}

function filtrarFecha(val) {
    window.location = '?fecha=' + val;
}

// Autoabrir si viene con prestamo preseleccionado
<?php if ($prestamoId && $prestamoInfo): ?>
document.addEventListener('DOMContentLoaded', function() {
    openModal('modal-pago');
    seleccionarPrestamo(
        <?= $prestamoInfo['id'] ?>,
        '<?= htmlspecialchars(addslashes($prestamoInfo['deudor_nombre'])) ?>',
        <?= $prestamoInfo['saldo_pendiente'] ?>,
        <?= $prestamoInfo['valor_cuota'] ?>
    );
});
<?php endif; ?>
</script>