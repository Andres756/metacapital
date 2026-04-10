<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
header('Content-Type: application/json');

$documento = trim($_GET['documento'] ?? '');
if (!$documento) {
    echo json_encode(['ok'=>false,'msg'=>'Documento requerido']); exit;
}

$db    = getDB();
$cobro = cobroActivo();

$stmt = $db->prepare("
    SELECT d.id, d.nombre, d.telefono, d.telefono_alt, d.documento,
           d.direccion, d.barrio, d.lat, d.lng, d.place_id,
           d.comportamiento, d.activo, d.notas
    FROM deudores d
    WHERE d.documento = ?
    LIMIT 1
");
$stmt->execute([$documento]);
$deudor = $stmt->fetch();

if (!$deudor) {
    echo json_encode(['ok'=>true,'existe'=>false]); exit;
}

// Si es clavo, registrar alerta para el admin
if ($deudor['comportamiento'] === 'clavo') {
    $db->prepare("
        INSERT INTO alertas_admin
            (cobro_id, tipo, mensaje, usuario_id, deudor_id, created_at)
        VALUES (?, 'clavo_consultado', ?, ?, ?, NOW())
    ")->execute([
        $cobro,
        'El cobrador ' . $_SESSION['usuario_nombre'] . ' consultó al deudor CLAVO: ' . $deudor['nombre'] . ' (CC: ' . $deudor['documento'] . ')',
        $_SESSION['usuario_id'],
        $deudor['id']
    ]);
}

// Verificar si el deudor está en el cobro actual
$stmtCobro = $db->prepare("
    SELECT c.nombre
    FROM deudor_cobro dc
    JOIN cobros c ON c.id = dc.cobro_id
    WHERE dc.deudor_id = ? AND dc.cobro_id = ?
    LIMIT 1
");
$stmtCobro->execute([$deudor['id'], $cobro]);
$enCobroActual = $stmtCobro->fetchColumn();

// Si no está en este cobro, buscar a cuáles pertenece
$cobrosAjenos = [];
if (!$enCobroActual) {
    $stmtAjenos = $db->prepare("
        SELECT c.nombre
        FROM deudor_cobro dc
        JOIN cobros c ON c.id = dc.cobro_id
        WHERE dc.deudor_id = ?
        ORDER BY c.nombre
    ");
    $stmtAjenos->execute([$deudor['id']]);
    $cobrosAjenos = array_column($stmtAjenos->fetchAll(), 'nombre');
}

echo json_encode([
    'ok'            => true,
    'existe'        => true,
    'en_cobro_actual' => (bool)$enCobroActual,
    'cobros_ajenos' => $cobrosAjenos,
    'deudor'        => $deudor
]);