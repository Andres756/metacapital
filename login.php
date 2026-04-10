<?php
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    header('Location: ' . redirectPostLogin());
    exit;
}

function redirectPostLogin(): string {
    if ($_SESSION['rol'] === 'cobrador') {
        return '/cobrador/dashboard.php';
    }
    if ($_SESSION['rol'] === 'superadmin' || $_SESSION['rol'] === 'admin') {
        return '/pages/dashboard.php';
    }
    $mapa = [
        'puede_ver_dashboard'  => '/pages/dashboard.php',
        'puede_registrar_pago' => '/pages/pagos.php',
        'puede_ver_prestamos'  => '/pages/prestamos.php',
        'puede_ver_deudores'   => '/pages/deudores.php',
        'puede_ver_reportes'   => '/pages/reportes.php',
        'puede_ver_capital'    => '/pages/capital.php',
    ];
    foreach ($mapa as $permiso => $url) {
        if (canDo($permiso)) return $url;
    }
    return '/pages/configuracion.php';
}

// Mensajes especiales por parámetro GET
$msgParam = $_GET['msg'] ?? '';
$alertaMsg   = '';
$alertaTipo  = 'warning';

if ($msgParam === 'sesion_cerrada') {
    $alertaMsg  = '🔒 Tu sesión fue cerrada por el administrador para procesar la liquidación del día.';
    $alertaTipo = 'warning';
} elseif ($msgParam === 'bloqueado') {
    $alertaMsg  = '🔒 El administrador ha bloqueado el acceso para procesar la liquidación. Espera a que termine.';
    $alertaTipo = 'warning';
} elseif ($msgParam === 'sin_base') {
    $alertaMsg  = '⏳ Aún no puedes ingresar — el administrador no ha abierto la liquidación del día. Espera a que te entreguen la base.';
    $alertaTipo = 'info';
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = login(trim($_POST['email'] ?? ''), $_POST['password'] ?? '');
    if ($result['ok']) {
        if (cobroActivo() === 0 && count($_SESSION['cobros_asignados']) > 1) {
            header('Location: /pages/select_cobro.php');
        } else {
            header('Location: ' . redirectPostLogin());
        }
        exit;
    }
    $error = $result['msg'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar Sesión — META Capital</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Mono:wght@300;400;500&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
  <?php
    $faviconPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/logo.ico';
    $faviconUrl  = file_exists($faviconPath) ? '/assets/img/logo.ico' : '/favicon.ico';
  ?>
  <link rel="icon" type="image/x-icon" href="<?= $faviconUrl ?>">
</head>
<body>
<div class="login-page">
  <div class="login-bg"></div>
  <div class="login-card">
    <div class="login-logo">
      <?php
        $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/logo.png';
        if (file_exists($logoPath)):
      ?>
        <img src="/assets/img/logo.png?v=<?= filemtime($logoPath) ?>"
             alt="Logo" class="login-logo-img">
      <?php else: ?>
        <span class="logo-text">META</span>
      <?php endif; ?>
      <span class="logo-sub">Sistema de Capital</span>
    </div>

    <?php if ($alertaMsg): ?>
    <div class="alert alert-<?= $alertaTipo ?>" style="margin-bottom:1rem;font-size:0.85rem;line-height:1.5">
      <?= $alertaMsg ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="field mb-2">
        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="usuario@metacapital.co" required autofocus>
      </div>
      <div class="field mb-3">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password"
               placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg">
        INGRESAR →
      </button>
    </form>
  </div>
</div>
</body>
</html>