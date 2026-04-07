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

if ($action === 'crear') {
    if (!canDo('puede_crear_salida')) { echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit; }

    $tipo      = $data['tipo']      ?? '';
    $monto     = (float)($data['monto'] ?? 0);
    $fecha     = $data['fecha']     ?? date('Y-m-d');
    $cuenta_id = (int)($data['cuenta_id'] ?? 0);

    // Mapeo tipo salida → tipo movimiento
    $tipoMap = [
        'redito_capitalista' => 'redito',
        'devolucion_capital' => 'devolucion',
        'liquidacion'        => 'devolucion',
        'gasto_operativo'    => 'gasto',
        'retiro_socio'       => 'retiro_socio',
    ];

    if (!isset($tipoMap[$tipo])) {
        echo json_encode(['ok'=>false,'msg'=>'Tipo inválido']); exit;
    }
    if ($monto <= 0) {
        echo json_encode(['ok'=>false,'msg'=>'El monto debe ser mayor a 0']); exit;
    }
    if (!$cuenta_id) {
        echo json_encode(['ok'=>false,'msg'=>'Selecciona la cuenta']); exit;
    }

    // Validar saldo de la cuenta
    validarSaldoCuenta($db, $cuenta_id, $cobro, $monto);

    $capitalista_id = (int)($data['capitalista_id'] ?? 0) ?: null;

    // Si hay capitalista, validar su saldo también
    if ($capitalista_id && in_array($tipo, ['devolucion_capital','liquidacion'])) {
        validarSaldoCapitalista($db, $capitalista_id, $monto);
    }

    $tipo_mov   = $tipoMap[$tipo];
    $descripcion= trim($data['descripcion'] ?? '') ?: ucfirst(str_replace('_',' ',$tipo));

    $db->beginTransaction();
    try {
        // Un solo registro — en capital_movimientos
        $db->prepare("INSERT INTO capital_movimientos
            (cobro_id, tipo, es_entrada, monto, cuenta_id, capitalista_id, descripcion, fecha, usuario_id)
            VALUES (?,?,0,?,?,?,?,?,?)")
        ->execute([
            $cobro, $tipo_mov, $monto, $cuenta_id, $capitalista_id,
            $descripcion, $fecha, $_SESSION['usuario_id']
        ]);

        // Si es liquidación → marcar capitalista como liquidado
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
        echo json_encode(['ok'=>true, 'msg'=>$msgs[$tipo]]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['ok'=>false, 'msg'=>'Error: '.$e->getMessage()]);
    }

} elseif ($action === 'eliminar') {
    if (!canDo('puede_eliminar_salida')) { echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit; }
    $id = (int)($data['id'] ?? 0);

    $check = $db->prepare("SELECT * FROM capital_movimientos WHERE id=? AND cobro_id=? AND es_entrada=0");
    $check->execute([$id, $cobro]);
    $mov = $check->fetch();
    if (!$mov) { echo json_encode(['ok'=>false,'msg'=>'Registro no encontrado']); exit; }

    // Los préstamos NO se eliminan desde salidas — deben anularse desde el detalle
    if ($mov['tipo'] === 'prestamo') {
        echo json_encode([
            'ok'  => false,
            'msg' => 'Para anular un préstamo ve al detalle del préstamo y usa el botón "Anular". Esto garantiza que las cuotas y el capital queden correctamente revertidos.'
        ]);
        exit;
    }

    // Para otros tipos: marcar como anulado (no DELETE físico)
    $db->prepare("UPDATE capital_movimientos SET anulado=1, anulado_at=NOW(), anulado_por=? WHERE id=?")
       ->execute([$_SESSION['usuario_id'], $id]);

    echo json_encode(['ok'=>true, 'msg'=>'Movimiento anulado. El saldo fue corregido.']);

} else {
    echo json_encode(['ok'=>false, 'msg'=>'Acción no reconocida']);
}