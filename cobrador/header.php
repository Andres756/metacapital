<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();

// Solo cobradores — admins van al sistema principal
if (!in_array($_SESSION['rol'], ['cobrador', 'admin', 'superadmin'])) {
    header('Location: /login.php'); exit;
}

$cobro = cobroActivo();
if (!$cobro) {
    // Cobrador sin cobro asignado
    header('Location: /login.php?error=sin_cobro'); exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title><?= $pageTitle ?? 'Cobrador' ?> — Meta Capital</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        /* Overrides mobile para el portal cobrador */
        body { font-size: 16px; }

        .cob-nav {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: var(--card);
            border-top: 1px solid var(--border);
            display: flex;
            z-index: 100;
            padding-bottom: env(safe-area-inset-bottom);
        }
        .cob-nav a {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0.6rem 0.25rem;
            font-size: 0.62rem;
            font-family: var(--font-mono);
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--muted);
            text-decoration: none;
            gap: 0.2rem;
            transition: color .15s;
        }
        .cob-nav a.active { color: var(--accent); }
        .cob-nav a svg { width: 22px; height: 22px; }

        .cob-page {
            min-height: 100vh;
            padding: 1rem 1rem 5rem; /* espacio para la nav fija */
        }
        .cob-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
        }
        .cob-title {
            font-family: var(--font-display);
            font-size: 1.4rem;
            letter-spacing: 2px;
        }
        .cob-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem;
            margin-bottom: 0.75rem;
        }
        .cob-btn {
            display: block;
            width: 100%;
            padding: 0.9rem;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            border: none;
            margin-bottom: 0.75rem;
        }
        .cob-btn-primary  { background: var(--accent); color: #fff; }
        .cob-btn-success  { background: var(--green, #22c55e); color: #fff; }
        .cob-btn-ghost    { background: transparent; border: 1px solid var(--border); color: var(--text); }
        .cob-deudor-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            margin-bottom: 0.5rem;
            text-decoration: none;
            color: var(--text);
        }
        .cob-deudor-row:active { opacity: 0.7; }
        .cob-avatar {
            width: 42px; height: 42px;
            border-radius: 50%;
            background: var(--accent);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 1.1rem; color: #fff;
            flex-shrink: 0;
        }
        .cob-badge {
            font-size: 0.65rem;
            padding: 2px 7px;
            border-radius: 20px;
            font-family: var(--font-mono);
            font-weight: 600;
        }
        .cob-badge-mora   { background: rgba(255,100,0,.15); color: #f97316; }
        .cob-badge-ok     { background: rgba(34,197,94,.15);  color: #22c55e; }
        .cob-badge-acuerdo{ background: rgba(59,130,246,.15); color: #3b82f6; }
        .field-lg label   { font-size: 0.9rem; margin-bottom: 0.4rem; display:block; color: var(--muted); }
        .field-lg input,
        .field-lg select,
        .field-lg textarea {
            width: 100%;
            font-size: 1.1rem;
            padding: 0.75rem;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            background: var(--bg);
            color: var(--text);
            margin-bottom: 1rem;
        }
    </style>
    <?php if (!empty($usaMapa)): ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_KEY ?>&libraries=places&language=es&region=CO" async defer></script>
    <?php endif; ?>
</head>
<body>
<div class="cob-page">