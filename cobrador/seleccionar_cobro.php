<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();

// Solo cobradores
if ($_SESSION['rol'] !== 'cobrador') {
    header('Location: /pages/dashboard.php'); exit;
}

$cobros_asignados = $_SESSION['cobros_asignados'] ?? [];

// Si solo tiene 1 cobro → directo
if (count($cobros_asignados) === 1) {
    setCobro($cobros_asignados[0]);
    header('Location: /cobrador/dashboard.php'); exit;
}

// Si no tiene cobros → error
if (count($cobros_asignados) === 0) {
    header('Location: /login.php?error=sin_cobro'); exit;
}

$db = getDB();

// Cargar info de los cobros asignados
$placeholders = implode(',', array_fill(0, count($cobros_asignados), '?'));
$stmt = $db->prepare("
    SELECT c.id, c.nombre, c.descripcion,
        (SELECT COUNT(*) FROM prestamos p WHERE p.cobro_id=c.id AND p.estado IN ('activo','en_mora','en_acuerdo')) AS prestamos_activos,
        (SELECT COUNT(*) FROM prestamos p WHERE p.estado='en_mora' AND p.cobro_id=c.id) AS en_mora
    FROM cobros c
    WHERE c.id IN ($placeholders) AND c.activo=1
    ORDER BY c.nombre ASC
");
$stmt->execute($cobros_asignados);
$cobros = $stmt->fetchAll();

// Procesar selección
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cobro_id = (int)($_POST['cobro_id'] ?? 0);
    if (in_array($cobro_id, $cobros_asignados)) {
        setCobro($cobro_id);
        header('Location: /cobrador/dashboard.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Seleccionar Cobro — Meta Capital</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body { font-size: 16px; }
        .sel-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: var(--bg);
        }
        .sel-card {
            width: 100%;
            max-width: 420px;
        }
        .sel-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .sel-title {
            font-family: var(--font-display);
            font-size: 1.8rem;
            letter-spacing: 3px;
            color: var(--accent);
        }
        .sel-sub {
            font-family: var(--font-mono);
            font-size: 0.7rem;
            color: var(--muted);
            margin-top: 0.25rem;
        }
        .cobro-option {
            display: block;
            width: 100%;
            padding: 1.25rem 1rem;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            margin-bottom: 0.75rem;
            cursor: pointer;
            text-align: left;
            color: var(--text);
            transition: border-color .15s, background .15s;
            text-decoration: none;
        }
        .cobro-option:active,
        .cobro-option:hover {
            border-color: var(--accent);
            background: rgba(124,106,255,.08);
        }
        .cobro-nombre {
            font-family: var(--font-display);
            font-size: 1.2rem;
            letter-spacing: 1px;
            margin-bottom: 0.35rem;
        }
        .cobro-stats {
            display: flex;
            gap: 1rem;
            font-family: var(--font-mono);
            font-size: 0.68rem;
            color: var(--muted);
        }
        .cobro-mora { color: #f97316; }
        .salir-btn {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: var(--muted);
            text-decoration: none;
            padding: 0.5rem;
        }
        .salir-btn:hover { color: #ef4444; }
    </style>
</head>
<body>
<div class="sel-page">
  <div class="sel-card">

    <div class="sel-logo">
      <?php
        $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/logo.png';
        if (file_exists($logoPath)):
      ?>
        <img src="/assets/img/logo.png" alt="Logo"
             style="height:48px;object-fit:contain;margin-bottom:0.5rem">
      <?php else: ?>
        <div class="sel-title">META</div>
      <?php endif; ?>
      <div class="sel-sub">SISTEMA DE CAPITAL</div>
    </div>

    <div style="font-family:var(--font-mono);font-size:0.72rem;color:var(--muted);text-align:center;margin-bottom:1.5rem">
      Hola <strong style="color:var(--text)"><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></strong>
      — Selecciona con qué cobro vas a trabajar hoy
    </div>

    <?php foreach ($cobros as $c): ?>
    <form method="POST" action="">
      <input type="hidden" name="cobro_id" value="<?= $c['id'] ?>">
      <button type="submit" class="cobro-option">
        <div class="cobro-nombre"><?= htmlspecialchars($c['nombre']) ?></div>
        <?php if ($c['descripcion']): ?>
        <div style="font-size:0.78rem;color:var(--muted);margin-bottom:0.5rem">
          <?= htmlspecialchars($c['descripcion']) ?>
        </div>
        <?php endif; ?>
        <div class="cobro-stats">
          <span>📋 <?= $c['prestamos_activos'] ?> préstamos activos</span>
          <?php if ($c['en_mora'] > 0): ?>
          <span class="cobro-mora">⚠ <?= $c['en_mora'] ?> en mora</span>
          <?php endif; ?>
        </div>
      </button>
    </form>
    <?php endforeach; ?>

    <?php if (empty($cobros)): ?>
    <div style="text-align:center;padding:2rem;font-family:var(--font-mono);font-size:0.8rem;color:var(--muted)">
      No tienes cobros activos asignados.<br>Contacta al administrador.
    </div>
    <?php endif; ?>

    <a href="/logout.php" class="salir-btn">✕ Cerrar sesión</a>

  </div>
</div>
</body>
</html>