<?php
// Página genérica "Próximamente" — usar para proyección y reportes
// Uso: $pageTitle = 'Proyección'; $pageSection = 'Proyeccion'; include 'proximamente.php';

$pageTitle   = $pageTitle   ?? 'Próximamente';
$pageSection = $pageSection ?? '';
require_once __DIR__ . '/../includes/header.php';
?>

<div style="min-height:60vh;display:flex;align-items:center;justify-content:center">
  <div style="text-align:center;max-width:480px;padding:2rem">

    <!-- Icono animado -->
    <div style="margin-bottom:2rem;position:relative;display:inline-block">
      <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="40" cy="40" r="38" stroke="var(--accent)" stroke-width="1.5" stroke-dasharray="6 4" opacity="0.4">
          <animateTransform attributeName="transform" type="rotate" from="0 40 40" to="360 40 40" dur="20s" repeatCount="indefinite"/>
        </circle>
        <circle cx="40" cy="40" r="28" stroke="var(--accent)" stroke-width="1" opacity="0.2"/>
        <text x="40" y="47" text-anchor="middle" font-size="26" fill="var(--accent)" font-family="var(--font-display)">⚙</text>
      </svg>
    </div>

    <!-- Título -->
    <div style="font-family:var(--font-display);font-size:2.5rem;letter-spacing:3px;color:var(--accent);margin-bottom:0.5rem">
      EN DESARROLLO
    </div>

    <!-- Sección actual -->
    <div style="font-family:var(--font-mono);font-size:0.72rem;color:var(--muted);letter-spacing:2px;text-transform:uppercase;margin-bottom:1.5rem">
      <?= htmlspecialchars($pageSection) ?> — próximamente disponible
    </div>

    <!-- Descripción -->
    <p style="color:var(--text-soft);font-size:0.9rem;line-height:1.7;margin-bottom:2rem">
      Estamos construyendo algo poderoso aquí.<br>
      Esta sección estará lista muy pronto.
    </p>

    <!-- Barra de progreso decorativa -->
    <div style="background:var(--border);border-radius:4px;height:3px;overflow:hidden;margin-bottom:2rem">
      <div style="height:100%;background:var(--accent);border-radius:4px;animation:progreso 2.5s ease-in-out infinite alternate"></div>
    </div>

    <!-- Botón volver -->
    <a href="/pages/dashboard.php"
       style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.65rem 1.5rem;border:1px solid var(--accent);border-radius:var(--radius);color:var(--accent);font-family:var(--font-mono);font-size:0.78rem;font-weight:700;text-decoration:none;letter-spacing:1px;transition:all 0.2s"
       onmouseover="this.style.background='var(--accent)';this.style.color='#fff'"
       onmouseout="this.style.background='transparent';this.style.color='var(--accent)'">
      ← VOLVER AL DASHBOARD
    </a>

  </div>
</div>

<style>
@keyframes progreso {
  0%   { width: 15%; margin-left: 0 }
  50%  { width: 60%; margin-left: 20% }
  100% { width: 25%; margin-left: 65% }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>