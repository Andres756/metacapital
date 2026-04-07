<?php
$pageTitle = 'Sin acceso';
$pageSection = 'Error 403';
// Solo mostrar layout si hay sesión activa
if (function_exists('isLoggedIn') && isLoggedIn()):
require_once __DIR__ . '/../includes/header.php';
?>
<div style="display:flex;align-items:center;justify-content:center;min-height:60vh;flex-direction:column;gap:1rem;text-align:center">
  <div style="font-family:var(--font-display);font-size:6rem;letter-spacing:4px;color:var(--border);line-height:1">403</div>
  <div style="font-family:var(--font-mono);font-size:0.85rem;color:var(--muted)">No tienes permiso para acceder a esta sección</div>
  <a href="/pages/dashboard.php" class="btn btn-ghost mt-2">← Volver al Dashboard</a>
</div>
<?php
require_once __DIR__ . '/../includes/footer.php';
else:
http_response_code(403);
echo '403 Forbidden';
endif;
