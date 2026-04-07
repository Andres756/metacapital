<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$cobro_id = (int)($data['cobro_id'] ?? 0);

if (!$cobro_id) {
    echo json_encode(['ok' => false, 'msg' => 'Cobro inválido']);
    exit;
}

setCobro($cobro_id);
echo json_encode(['ok' => true]);