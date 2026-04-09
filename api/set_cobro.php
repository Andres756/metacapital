<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
header('Content-Type: application/json');

$data     = json_decode(file_get_contents('php://input'), true) ?? [];
$cobro_id = (int)($data['cobro_id'] ?? 0);

if (!$cobro_id) {
    echo json_encode(['ok'=>false,'msg'=>'Cobro inválido']); exit;
}

// Verificar que el cobro existe y está activo
$db  = getDB();
$chk = $db->prepare("SELECT id FROM cobros WHERE id=? AND activo=1");
$chk->execute([$cobro_id]);
if (!$chk->fetch()) {
    echo json_encode(['ok'=>false,'msg'=>'Cobro no encontrado o inactivo']); exit;
}

// Verificar que el usuario tiene acceso a ese cobro
$cobro_anterior = cobroActivo();
setCobro($cobro_id);

// Si después de setCobro() el cobro activo no cambió, el usuario no tenía acceso
if (cobroActivo() !== $cobro_id) {
    echo json_encode(['ok'=>false,'msg'=>'No tienes acceso a este cobro']); exit;
}

echo json_encode(['ok'=>true,'msg'=>'Cobro cambiado correctamente']);