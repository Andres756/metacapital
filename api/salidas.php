<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok'=>false,'msg'=>'Método no permitido']); exit;
}

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$db     = getDB();
$cobro  = cobroActivo();
$action = $data['action'] ?? '';

// ============================================================
// CREAR SALIDA
// ============================================================
if ($action === 'crear') {
    if (!canDo('puede_crear_salida')) { echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit; }

    $tipo        = $data['tipo']        ?? '';
    $monto       = (float)($data['monto'] ?? 0);
    $fecha       = $data['fecha']       ?? date('Y-m-d');
    $metodo_pago = in_array($data['metodo_pago']??'',['efectivo','banco']) ? $data['metodo_pago'] : 'efectivo';

    $tiposValidos = [
        'redito_capitalista' => 'redito',
        'devolucion_capital' => 'retiro_capital',
        'liquidacion'        => 'liquidacion',
        'gasto_operativo'    => 'salida',
        'retiro_socio'       => 'retiro',
    ];

    if (!isset($tiposValidos[$tipo])) {
        echo json_encode(['ok'=>false,'msg'=>'Tipo de salida inválido']); exit;
    }
    if ($monto <= 0) {
        echo json_encode(['ok'=>false,'msg'=>'El monto debe ser mayor a 0']); exit;
    }
    if (!$fecha || !strtotime($fecha)) {
        echo json_encode(['ok'=>false,'msg'=>'Fecha inválida']); exit;
    }

    // Validar saldo de caja
    $saldo = getSaldoCaja($db, $cobro);
    if ($saldo < $monto) {
        echo json_encode([
            'ok'  => false,
            'msg' => 'Saldo insuficiente en caja. Disponible: '.fmt($saldo).' · Requerido: '.fmt($monto)
        ]); exit;
    }

    $capitalista_id = (int)($data['capitalista_id'] ?? 0) ?: null;

    if ($capitalista_id) {
        $chkCap = $db->prepare("SELECT id FROM capitalistas WHERE id=? AND cobro_id=? AND estado='activo'");
        $chkCap->execute([$capitalista_id, $cobro]);
        if (!$chkCap->fetch()) {
            echo json_encode(['ok'=>false,'msg'=>'Capitalista no encontrado o inactivo']); exit;
        }

        if (in_array($tipo, ['devolucion_capital','liquidacion'])) {
            validarSaldoCapitalista($db, $capitalista_id, $monto);
        }
    }

    $tipo_mov    = $tiposValidos[$tipo];
    $descripcion = trim($data['descripcion'] ?? '') ?: ucfirst(str_replace('_', ' ', $tipo));

    $db->beginTransaction();
    try {
        $db->prepare("INSERT INTO capital_movimientos
            (cobro_id, tipo, es_entrada, monto, metodo_pago, capitalista_id, descripcion, fecha, usuario_id)
            VALUES (?, ?, 0, ?, ?, ?, ?, ?, ?)")
        ->execute([
            $cobro, $tipo_mov, $monto, $metodo_pago,
            $capitalista_id, $descripcion, $fecha, $_SESSION['usuario_id']
        ]);

        if ($tipo === 'liquidacion' && $capitalista_id) {
            $db->prepare("UPDATE capitalistas SET estado='liquidado', updated_at=NOW() WHERE id=? AND cobro_id=?")
               ->execute([$capitalista_id, $cobro]);
        }

        $db->commit();

        $msgs = [
            'redito_capitalista' => 'Rédito registrado',
            'devolucion_capital' => 'Devolución registrada',
            'liquidacion'        => 'Liquidación registrada',
            'gasto_operativo'    => 'Gasto registrado',
            'retiro_socio'       => 'Retiro registrado',
        ];
        echo json_encode(['ok'=>true,'msg'=>$msgs[$tipo]]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
    }

// ============================================================
// ELIMINAR SALIDA
// ============================================================
} elseif ($action === 'eliminar') {
    if (!canDo('puede_eliminar_salida')) { echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit; }

    $id = (int)($data['id'] ?? 0);
    if (!$id) { echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

    $check = $db->prepare("SELECT * FROM capital_movimientos WHERE id=? AND cobro_id=? AND es_entrada=0");
    $check->execute([$id, $cobro]);
    $mov = $check->fetch();
    if (!$mov) { echo json_encode(['ok'=>false,'msg'=>'Registro no encontrado']); exit; }

    if ($mov['anulado']) {
        echo json_encode(['ok'=>false,'msg'=>'Este movimiento ya estaba anulado']); exit;
    }

    if (in_array($mov['tipo'], ['prestamo','salida']) && $mov['prestamo_id']) {
        echo json_encode([
            'ok'  => false,
            'msg' => 'Para anular un préstamo ve al detalle del préstamo y usa el botón "Anular".'
        ]); exit;
    }

    $db->prepare("UPDATE capital_movimientos SET anulado=1, anulado_at=NOW(), anulado_por=? WHERE id=?")
       ->execute([$_SESSION['usuario_id'], $id]);

    echo json_encode(['ok'=>true,'msg'=>'Movimiento anulado. El saldo fue corregido.']);

} else {
    echo json_encode(['ok'=>false,'msg'=>'Acción no reconocida']);
}