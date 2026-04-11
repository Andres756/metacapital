<?php
$pageTitle   = $pageTitle   ?? 'META Capital';
$pageSection = $pageSection ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> — META Capital</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Mono:wght@300;400;500&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
  <?php
    $faviconPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/logo.ico';
    $faviconUrl  = file_exists($faviconPath) ? '/assets/img/logo.ico' : '/favicon.ico';
  ?>
  <link rel="icon" type="image/x-icon" href="<?= $faviconUrl ?>">
  <?= $extraHead ?? '' ?>
  <script>
    var BASE_URL = '<?= rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\') ?>';
  </script>
  <script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_KEY ?>&libraries=places&language=es&region=CO" async defer></script>
</head>
<body>
<div class="layout">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <?php
        $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/logo.png';
        if (file_exists($logoPath)):
      ?>
        <img src="/assets/img/logo.png?v=<?= filemtime($logoPath) ?>" alt="Logo" class="sidebar-logo-img">
      <?php else: ?>
        <span>META</span><small>CAPITAL</small>
      <?php endif; ?>
    </div>

    <nav class="sidebar-nav">

      <!-- ── PRINCIPAL ── -->
      <?php if (canDo('puede_ver_dashboard')): ?>
      <div class="nav-section-label">Principal</div>
      <a href="/pages/dashboard.php" class="nav-item <?= $pageSection==='Dashboard'?'active':'' ?>">
        <span class="nav-icon">⌂</span> Dashboard
      </a>
      <?php endif; ?>

      <!-- ── CARTERA ── -->
      <?php
        $verDeudores  = canDo('puede_ver_deudores');
        $verPrestamos = canDo('puede_ver_prestamos');
        $verPagos     = canDo('puede_registrar_pago');
        if ($verDeudores || $verPrestamos || $verPagos):
      ?>
      <div class="nav-section-label">Cartera</div>

      <?php if ($verDeudores): ?>
      <a href="/pages/deudores.php" class="nav-item <?= $pageSection==='Deudores'?'active':'' ?>">
        <span class="nav-icon">⊙</span> Deudores
      </a>
      <?php endif; ?>

      <?php if ($verPrestamos): ?>
      <a href="/pages/prestamos.php" class="nav-item <?= $pageSection==='Préstamos'?'active':'' ?>">
        <span class="nav-icon">≡</span> Préstamos
        <?php
          $db  = getDB();
          $cid = cobroActivo();
          if ($cid) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM cuotas WHERE cobro_id=? AND estado IN ('pendiente','parcial') AND fecha_vencimiento <= CURDATE()");
            $stmt->execute([$cid]);
            $vencidas = $stmt->fetchColumn();
            if ($vencidas > 0) echo "<span class='nav-badge'>$vencidas</span>";
          }
        ?>
      </a>
      <?php endif; ?>

      <?php if ($verPagos): ?>
      <a href="/pages/pagos.php" class="nav-item <?= $pageSection==='Pagos'?'active':'' ?>">
        <span class="nav-icon">$</span> Registrar Pago
      </a>
      <?php endif; ?>

      <?php endif; ?>

      <!-- ── FINANZAS ── -->
      <?php
        $verLiq     = canDo('puede_ver_configuracion');
        $verPap     = canDo('puede_ver_configuracion');
        $verSalidas = canDo('puede_ver_salidas');
        if ($verLiq || $verPap || $verSalidas):
      ?>
      <div class="nav-section-label">Finanzas</div>

      <?php if ($verLiq): ?>
      <a href="/pages/liquidacion.php" class="nav-item <?= $pageSection==='Liquidación'?'active':'' ?>">
        <span class="nav-icon">⊟</span> Liquidación
      </a>
      <?php endif; ?>

      <?php if ($verPap): ?>
      <a href="/pages/papeleria.php" class="nav-item <?= $pageSection==='Papelería'?'active':'' ?>">
        <span class="nav-icon">▤</span> Papelería
      </a>
      <?php endif; ?>

      <?php if ($verSalidas): ?>
      <a href="/pages/salidas.php" class="nav-item <?= $pageSection==='Salidas'?'active':'' ?>">
        <span class="nav-icon">↑</span> Salidas
      </a>
      <?php endif; ?>

      <?php endif; ?>

      <!-- ── ANÁLISIS ── -->
      <?php
        $verProyeccion = canDo('puede_ver_proyeccion');
        $verReportes   = canDo('puede_ver_reportes');
        if ($verProyeccion || $verReportes):
      ?>
      <div class="nav-section-label">Análisis</div>

      <?php if ($verProyeccion): ?>
      <a href="/pages/proyeccion.php" class="nav-item <?= $pageSection==='Proyeccion'?'active':'' ?>">
        <span class="nav-icon">△</span> Proyección
      </a>
      <?php endif; ?>

      <?php if ($verReportes): ?>
      <a href="/pages/reportes.php" class="nav-item <?= $pageSection==='Reportes'?'active':'' ?>">
        <span class="nav-icon">▣</span> Reportes
      </a>
      <?php endif; ?>

      <?php endif; ?>

      <!-- ── ADMINISTRACIÓN ── -->
      <?php
        $verCapital  = canDo('puede_ver_capital');
        $verCobros   = canDo('puede_ver_cobros');
        $verUsuarios = canDo('puede_ver_usuarios');
        $verConfig   = canDo('puede_ver_configuracion');
        if ($verCapital || $verCobros || $verUsuarios || $verConfig):
      ?>
      <div class="nav-section-label">Administración</div>

      <?php if ($verCapital): ?>
      <a href="/pages/capital.php" class="nav-item <?= $pageSection==='Capital'?'active':'' ?>">
        <span class="nav-icon">◆</span> Capital
      </a>
      <?php endif; ?>

      <?php if ($verCobros): ?>
      <a href="/pages/cobros.php" class="nav-item <?= $pageSection==='Cobros'?'active':'' ?>">
        <span class="nav-icon">↺</span> Cobros
      </a>
      <?php endif; ?>

      <?php if ($verUsuarios): ?>
      <a href="/pages/usuarios.php" class="nav-item <?= $pageSection==='Usuarios'?'active':'' ?>">
        <span class="nav-icon">⊛</span> Usuarios
      </a>
      <?php endif; ?>

      <?php if ($verConfig): ?>
      <a href="/pages/configuracion.php" class="nav-item <?= $pageSection==='Configuracion'?'active':'' ?>">
        <span class="nav-icon">✦</span> Configuración
      </a>
      <?php endif; ?>

      <?php endif; ?>

    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-version">META CAPITAL v1.0</div>
    </div>
  </aside>

  <!-- TOPBAR -->
  <header class="topbar">
    <button class="btn btn-ghost btn-sm btn-hamburger" onclick="toggleSidebar()" id="btn-menu">☰</button>
    <span class="topbar-title"><?= htmlspecialchars($pageSection ?: $pageTitle) ?></span>

    <div class="topbar-right">
      <?php
        $cobroNombre = '—';
        $cobro_id    = cobroActivo();
        if ($cobro_id) {
          $stmtC = getDB()->prepare("SELECT nombre FROM cobros WHERE id = ?");
          $stmtC->execute([$cobro_id]);
          $cobroNombre = $stmtC->fetchColumn() ?: '—';
        }
      ?>
      <!-- Campana notificaciones -->
      <div style="position:relative" id="campana-wrap">
        <button class="btn btn-ghost btn-sm" id="btn-campana"
                onclick="toggleNotifs()"
                style="position:relative;width:34px;height:34px;padding:0;display:flex;align-items:center;justify-content:center;font-size:1rem">
          ◉
          <?php
            $db2 = getDB();
            $cid2 = cobroActivo();
            if ($cid2) {
              $nQ = $db2->prepare("SELECT COUNT(*) FROM alertas_admin WHERE cobro_id=? AND leida=0");
              $nQ->execute([$cid2]);
              $nAlertas = (int)$nQ->fetchColumn();
              if ($nAlertas > 0) echo "<span id='badge-notif' style='position:absolute;top:-3px;right:-3px;background:var(--danger);color:#fff;font-family:var(--font-mono);font-size:0.55rem;font-weight:700;min-width:16px;height:16px;border-radius:8px;display:flex;align-items:center;justify-content:center;padding:0 3px'>$nAlertas</span>";
            }
          ?>
        </button>
        <!-- Panel de notificaciones -->
        <div id="notif-panel" style="display:none;position:absolute;right:0;top:calc(100% + 8px);width:320px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);box-shadow:0 8px 32px rgba(0,0,0,0.35);z-index:999">
          <div style="padding:0.75rem 1rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
            <span style="font-family:var(--font-mono);font-size:0.7rem;text-transform:uppercase;letter-spacing:1px">Notificaciones</span>
            <button class="btn btn-ghost btn-sm" style="font-size:0.65rem" onclick="marcarLeidas()">Marcar todas leídas</button>
          </div>
          <div id="notif-lista" style="max-height:320px;overflow-y:auto">
            <?php
              if ($cid2 ?? false) {
                $nAll = $db2->prepare("SELECT a.*, d.nombre AS deudor_nombre, u.nombre AS cobrador FROM alertas_admin a LEFT JOIN deudores d ON d.id=a.deudor_id LEFT JOIN usuarios u ON u.id=a.usuario_id WHERE a.cobro_id=? AND a.leida=0 ORDER BY a.created_at DESC LIMIT 20");
                $nAll->execute([$cid2]);
                $notifs = $nAll->fetchAll();
                if (empty($notifs)) {
                  echo '<div style="padding:1.5rem;text-align:center;font-family:var(--font-mono);font-size:0.72rem;color:var(--muted)">Sin notificaciones</div>';
                } else {
                  foreach ($notifs as $n) {
                    $deudor = htmlspecialchars($n['deudor_nombre'] ?? '—');
                    $cobrador = htmlspecialchars($n['cobrador'] ?? '—');
                    $hora = date('d M H:i', strtotime($n['created_at']));
                    echo "<div style='padding:0.75rem 1rem;border-bottom:1px solid var(--border);display:flex;gap:0.75rem;align-items:flex-start'>
                      <span style='color:var(--danger);font-size:1rem;flex-shrink:0'>⚠</span>
                      <div style='flex:1;min-width:0'>
                        <div style='font-size:0.8rem;font-weight:600'>$deudor</div>
                        <div style='font-family:var(--font-mono);font-size:0.62rem;color:var(--muted);margin-top:0.15rem'>Cobrador: $cobrador · $hora</div>
                      </div>
                    </div>";
                  }
                }
              }
            ?>
          </div>
        </div>
      </div>

      <div class="cobro-selector" onclick="openModal('modal-cobro')">
        <div class="dot-active"></div>
        <?= htmlspecialchars($cobroNombre) ?>
        <?php if ($_SESSION['rol'] === 'superadmin'): ?>
        <span style="color:var(--muted)">▾</span>
        <?php endif; ?>
      </div>

      <div style="position:relative">
        <div class="user-avatar" data-dropdown="user-menu">
          <?= strtoupper(substr($_SESSION['usuario_nombre'], 0, 1)) ?>
        </div>
        <div class="user-dropdown" id="user-menu">
          <div style="padding:0.5rem 0.75rem;font-family:var(--font-mono);font-size:0.7rem;color:var(--muted)">
            <?= htmlspecialchars($_SESSION['usuario_nombre']) ?><br>
            <span style="font-size:0.6rem;color:var(--accent)"><?= strtoupper($_SESSION['rol']) ?></span>
          </div>
          <div class="divider"></div>
          <a href="/pages/perfil.php">◉ Mi perfil</a>
          <div class="divider"></div>
          <button onclick="window.location='/logout.php'">✕ Cerrar sesión</button>
        </div>
      </div>
    </div>
  </header>

  <!-- Overlay mobile -->
  <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

  <!-- CONTENT -->
  <main class="content">
    <div id="toast-container" class="toast-container"></div>

    <!-- Modal selector de cobro -->
    <?php if ($_SESSION['rol'] === 'superadmin' || count($_SESSION['cobros_asignados'] ?? []) > 1): ?>
    <div class="modal-overlay" id="modal-cobro">
      <div class="modal">
        <div class="modal-header">
          <h2>SELECCIONAR COBRO</h2>
          <button class="modal-close" onclick="closeModal('modal-cobro')">✕</button>
        </div>
        <div class="modal-body">
          <?php
            $db = getDB();
            if ($_SESSION['rol'] === 'superadmin') {
              $cobros = $db->query("SELECT id, nombre, descripcion FROM cobros WHERE activo=1 ORDER BY nombre")->fetchAll();
            } else {
              $ids    = implode(',', array_map('intval', $_SESSION['cobros_asignados']));
              $cobros = $db->query("SELECT id, nombre, descripcion FROM cobros WHERE id IN ($ids) AND activo=1 ORDER BY nombre")->fetchAll();
            }
          ?>
          <div class="cobro-select-grid">
            <?php foreach ($cobros as $c): ?>
            <div class="cobro-select-card" onclick="cambiarCobro(<?= $c['id'] ?>)">
              <div class="csc-icon">⬡</div>
              <div class="csc-name"><?= htmlspecialchars($c['nombre']) ?></div>
              <?php if ($c['descripcion']): ?>
              <div class="csc-meta"><?= htmlspecialchars($c['descripcion']) ?></div>
              <?php endif; ?>
              <?php if ($c['id'] == cobroActivo()): ?>
              <div style="margin-top:0.5rem"><span class="badge badge-green">Activo</span></div>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); endif; ?>

<script>
function toggleNotifs() {
    var p = document.getElementById('notif-panel');
    p.style.display = p.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', function(e) {
    var wrap = document.getElementById('campana-wrap');
    if (wrap && !wrap.contains(e.target)) {
        var p = document.getElementById('notif-panel');
        if (p) p.style.display = 'none';
    }
});
async function marcarLeidas() {
    await apiPost('/api/configuracion.php', { action: 'marcar_alertas_leidas' });
    document.getElementById('badge-notif') && (document.getElementById('badge-notif').style.display='none');
    document.getElementById('notif-lista').innerHTML = '<div style="padding:1.5rem;text-align:center;font-family:var(--font-mono);font-size:0.72rem;color:var(--muted)">Sin notificaciones</div>';
}
</script>