<?php
// Requiere que $pageTitle esté definido antes de incluir
$pageTitle = $pageTitle ?? 'META Capital';
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
        <img src="/assets/img/logo.png?v=<?= filemtime($logoPath) ?>"
             alt="Logo" class="sidebar-logo-img">
      <?php else: ?>
        <span>META</span>
        <small>CAPITAL</small>
      <?php endif; ?>
    </div>

    <nav class="sidebar-nav">

      <!-- ── PRINCIPAL ──────────────────────────────────── -->
      <?php if (canDo('puede_ver_dashboard')): ?>
      <div class="nav-section-label">Principal</div>
      <a href="/pages/dashboard.php" class="nav-item <?= $pageSection==='Dashboard'?'active':'' ?>">
        <span class="nav-icon">◈</span> Dashboard
      </a>
      <?php endif; ?>

      <!-- ── CARTERA ─────────────────────────────────────── -->
      <?php
        $verDeudores  = canDo('puede_ver_deudores');
        $verPrestamos = canDo('puede_ver_prestamos');
        $verPagos     = canDo('puede_registrar_pago');
        if ($verDeudores || $verPrestamos || $verPagos):
      ?>
      <div class="nav-section-label">Cartera</div>

      <?php if ($verDeudores): ?>
      <a href="/pages/deudores.php" class="nav-item <?= $pageSection==='Deudores'?'active':'' ?>">
        <span class="nav-icon">◉</span> Deudores
      </a>
      <?php endif; ?>

      <?php if ($verPrestamos): ?>
      <a href="/pages/prestamos.php" class="nav-item <?= $pageSection==='Préstamos'?'active':'' ?>">
        <span class="nav-icon">◎</span> Préstamos
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
        <span class="nav-icon">◈</span> Registrar Pago
      </a>
      <?php endif; ?>

      <?php endif; // cartera ?>

      <!-- ── FINANZAS ────────────────────────────────────── -->
      <?php
        $verCapital    = canDo('puede_ver_capital');
        $verCuentas    = canDo('puede_ver_cuentas');
        $verSalidas    = canDo('puede_ver_salidas');
        $verMovimientos= canDo('puede_ver_movimientos');
        if ($verCapital || $verCuentas || $verSalidas || $verMovimientos):
      ?>
      <div class="nav-section-label">Finanzas</div>

      <?php if ($verCapital): ?>
      <a href="/pages/capital.php" class="nav-item <?= $pageSection==='Capital'?'active':'' ?>">
        <span class="nav-icon">◆</span> Capital
      </a>
      <?php endif; ?>

      <?php if ($verCuentas): ?>
      <a href="/pages/cuentas.php" class="nav-item <?= $pageSection==='Cuentas'?'active':'' ?>">
        <span class="nav-icon">◇</span> Cuentas
      </a>
      <?php endif; ?>

      <?php if ($verSalidas): ?>
      <a href="/pages/salidas.php" class="nav-item <?= $pageSection==='Salidas'?'active':'' ?>">
        <span class="nav-icon">▽</span> Salidas
      </a>
      <?php endif; ?>

      <?php if ($verMovimientos): ?>
      <a href="/pages/movimientos.php" class="nav-item <?= $pageSection==='Movimientos'?'active':'' ?>">
        <span class="nav-icon">≡</span> Movimientos
      </a>
      <?php endif; ?>

      <?php endif; // finanzas ?>

      <!-- ── ANÁLISIS ────────────────────────────────────── -->
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

      <?php endif; // análisis ?>

      <!-- ── ADMINISTRACIÓN ──────────────────────────────── -->
      <?php
        $verCobros    = canDo('puede_ver_cobros');
        $verUsuarios  = canDo('puede_ver_usuarios');
        $verConfig    = canDo('puede_ver_configuracion');
        if ($verCobros || $verUsuarios || $verConfig):
      ?>
      <div class="nav-section-label">Administración</div>

      <?php if (canDo('puede_ver_configuracion')): ?>
      <a href="/pages/liquidacion.php" class="nav-item <?= $pageSection==='Liquidación'?'active':'' ?>">
          <span class="nav-icon">⊟</span> Liquidación
      </a>
      <?php endif; ?>

      <?php if (canDo('puede_ver_configuracion')): ?>
      <a href="/pages/papeleria.php" class="nav-item <?= $pageSection==='Papelería'?'active':'' ?>">
          <span class="nav-icon">📋</span> Papelería
      </a>
      <?php endif; ?>

      <?php if ($verCobros): ?>
      <a href="/pages/cobros.php" class="nav-item <?= $pageSection==='Cobros'?'active':'' ?>">
        <span class="nav-icon">⬡</span> Cobros
      </a>
      <?php endif; ?>

      <?php if ($verUsuarios): ?>
      <a href="/pages/usuarios.php" class="nav-item <?= $pageSection==='Usuarios'?'active':'' ?>">
        <span class="nav-icon">◉</span> Usuarios
      </a>
      <?php endif; ?>

      <?php if ($verConfig): ?>
      <a href="/pages/configuracion.php" class="nav-item <?= $pageSection==='Configuracion'?'active':'' ?>">
        <span class="nav-icon">⚙</span> Configuración
      </a>
      <?php endif; ?>

      <?php endif; // administración ?>

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
        $cobro_id = cobroActivo();
        if ($cobro_id) {
          $stmtC = getDB()->prepare("SELECT nombre FROM cobros WHERE id = ?");
          $stmtC->execute([$cobro_id]);
          $cobroNombre = $stmtC->fetchColumn() ?: '—';
        }
      ?>
      <div class="cobro-selector" onclick="openModal('modal-cobro')">
        <div class="dot-active"></div>
        <?= htmlspecialchars($cobroNombre) ?>
        <?php if ($_SESSION['rol'] === 'superadmin'): ?><span style="color:var(--muted)">▾</span><?php endif; ?>
      </div>

      <div style="position:relative">
        <div class="user-avatar" data-dropdown="user-menu">
          <?= strtoupper(substr($_SESSION['usuario_nombre'], 0, 1)) ?>
        </div>
        <div class="user-dropdown" id="user-menu">
          <div style="padding:0.5rem 0.75rem 0.5rem; font-family:var(--font-mono); font-size:0.7rem; color:var(--muted);">
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
              $cobros = $db->query("SELECT id, nombre, descripcion FROM cobros WHERE activo = 1 ORDER BY nombre")->fetchAll();
            } else {
              $ids = implode(',', array_map('intval', $_SESSION['cobros_asignados']));
              $cobros = $db->query("SELECT id, nombre, descripcion FROM cobros WHERE id IN ($ids) AND activo = 1 ORDER BY nombre")->fetchAll();
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
      <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
      <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>