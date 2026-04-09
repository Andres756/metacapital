<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok'=>false]); exit;
}

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$db     = getDB();
$cobro  = cobroActivo();
$action = $data['action'] ?? '';

if ($action === 'marcar_leidas') {
    if (!in_array($_SESSION['rol'], ['admin','superadmin'])) {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }
    $db->prepare("UPDATE alertas_admin SET leida=1 WHERE cobro_id=? AND leida=0")
       ->execute([$cobro]);
    echo json_encode(['ok'=>true]);
} else {
    echo json_encode(['ok'=>false,'msg'=>'Acción no reconocida']);
}