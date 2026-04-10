<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
header('Content-Type: application/json');

$db     = getDB();
$cobro  = cobroActivo();
$method = $_SERVER['REQUEST_METHOD'];

// ============================================================
// GET — historial capitalista
// ============================================================
if ($method === 'GET') {
    if (!canDo('puede_ver_historial_capitalista')) {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }

    $action         = $_GET['action'] ?? '';
    $capitalista_id = (int)($_GET['capitalista_id'] ?? 0);

    if ($action === 'historial') {
        if (!$capitalista_id) {
            echo json_encode(['ok'=>false,'msg'=>'Falta capitalista_id']); exit;
        }

        $stmtC = $db->prepare("SELECT * FROM capitalistas WHERE id=? AND cobro_id=?");
        $stmtC->execute([$capitalista_id, $cobro]);
        $cap = $stmtC->fetch();
        if (!$cap) { echo json_encode(['ok'=>false,'msg'=>'Capitalista no encontrado']); exit; }

        $stmtM = $db->prepare("
            SELECT m.*, m.metodo_pago
            FROM capital_movimientos m
            WHERE m.capitalista_id=? AND m.cobro_id=?
              AND (m.anulado=0 OR m.anulado IS NULL)
              AND m.origen = 'capital'
            ORDER BY m.fecha DESC, m.id DESC
            LIMIT 60
        ");
        $stmtM->execute([$capitalista_id, $cobro]);
        $movs = $stmtM->fetchAll();

        $stmtS = $db->prepare("SELECT saldo_actual FROM v_saldo_capitalistas WHERE capitalista_id=?");
        $stmtS->execute([$capitalista_id]);
        $saldo = $stmtS->fetchColumn() ?? 0;

        echo json_encode(['ok'=>true,'capitalista'=>$cap,'movimientos'=>$movs,'saldo'=>$saldo]);

    } else {
        echo json_encode(['ok'=>false,'msg'=>'Acción no reconocida']);
    }
    exit;
}

if ($method !== 'POST') {
    echo json_encode(['ok'=>false,'msg'=>'Método no permitido']); exit;
}

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $data['action'] ?? '';

// ============================================================
// CREAR CAPITALISTA
// ============================================================
if ($action === 'crear_capitalista') {
    if (!canDo('puede_crear_capitalista')) {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }

    $nombre        = trim($data['nombre'] ?? '');
    if (!$nombre) { echo json_encode(['ok'=>false,'msg'=>'El nombre es obligatorio']); exit; }

    $tipo          = in_array($data['tipo']??'',['propio','prestado']) ? $data['tipo'] : 'propio';
    $monto_inicial = (float)($data['monto_inicial'] ?? 0);
    $tipo_redito   = in_array($data['tipo_redito']??'',['porcentaje','valor_fijo']) ? $data['tipo_redito'] : 'porcentaje';
    $tasa_redito   = (float)($data['tasa_redito'] ?? 0);
    $frecuencia    = in_array($data['frecuencia_redito']??'',['mensual','quincenal','libre']) ? $data['frecuencia_redito'] : 'mensual';
    $metodo_pago   = in_array($data['metodo_pago']??'',['efectivo','banco']) ? $data['metodo_pago'] : 'efectivo';
    $fecha_inicio  = $data['fecha_inicio'] ?? date('Y-m-d');

    if (!$fecha_inicio || !strtotime($fecha_inicio)) {
        echo json_encode(['ok'=>false,'msg'=>'Fecha de inicio inválida']); exit;
    }

    $color = preg_match('/^#[0-9a-fA-F]{6}$/', $data['color'] ?? '') ? $data['color'] : '#7c6aff';

    $db->beginTransaction();
    try {
        $db->prepare("INSERT INTO capitalistas
            (cobro_id, nombre, tipo, monto_inicial, tipo_redito, tasa_redito,
             frecuencia_redito, fecha_inicio, color, descripcion, estado)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([
            $cobro, $nombre, $tipo, $monto_inicial, $tipo_redito,
            $tasa_redito, $frecuencia, $fecha_inicio, $color,
            trim($data['descripcion'] ?? '') ?: null, 'activo'
        ]);
        $cap_id = (int)$db->lastInsertId();

        // Saldo inicial — origen='capital' → sí entra a la base
        if ($monto_inicial > 0) {
            $db->prepare("INSERT INTO capital_movimientos
                (cobro_id, tipo, origen, es_entrada, monto, metodo_pago, capitalista_id, descripcion, fecha, usuario_id)
                VALUES (?, 'ingreso_capital', 'capital', 1, ?, ?, ?, ?, ?, ?)")
            ->execute([
                $cobro, $monto_inicial, $metodo_pago, $cap_id,
                'Saldo inicial — ' . $nombre, $fecha_inicio, $_SESSION['usuario_id']
            ]);
        }

        $db->commit();
        echo json_encode(['ok'=>true,'msg'=>'Capitalista registrado','id'=>$cap_id]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
    }

// ============================================================
// MOVIMIENTO MANUAL (abono, retiro, redito, devolucion)
// ============================================================
} elseif ($action === 'movimiento') {
    if (!canDo('puede_registrar_movimiento_capital')) {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }

    $capitalista_id = (int)($data['capitalista_id'] ?? 0);
    $tipo           = $data['tipo'] ?? '';
    $monto          = (float)($data['monto'] ?? 0);
    $metodo_pago    = in_array($data['metodo_pago']??'',['efectivo','banco']) ? $data['metodo_pago'] : 'efectivo';
    $fecha          = $data['fecha'] ?? date('Y-m-d');

    if (!$fecha || !strtotime($fecha)) {
        echo json_encode(['ok'=>false,'msg'=>'Fecha inválida']); exit;
    }

    $tipoMap = [
        'abono'           => 'ingreso_capital',
        'ingreso_capital' => 'ingreso_capital',
        'retiro'          => 'retiro',
        'redito'          => 'redito',
        'devolucion'      => 'retiro_capital',
    ];

    if (!isset($tipoMap[$tipo])) {
        echo json_encode(['ok'=>false,'msg'=>'Tipo de movimiento inválido']); exit;
    }

    $tipo_mov   = $tipoMap[$tipo];
    $es_entrada = in_array($tipo_mov, ['ingreso_capital']) ? 1 : 0;

    if (!$capitalista_id) { echo json_encode(['ok'=>false,'msg'=>'Falta capitalista']); exit; }
    if ($monto <= 0)      { echo json_encode(['ok'=>false,'msg'=>'El monto debe ser mayor a 0']); exit; }

    $check = $db->prepare("SELECT nombre FROM capitalistas WHERE id=? AND cobro_id=?");
    $check->execute([$capitalista_id, $cobro]);
    $capRow = $check->fetch();
    if (!$capRow) { echo json_encode(['ok'=>false,'msg'=>'Capitalista no encontrado']); exit; }

    // Las salidas validan saldo de caja y saldo del capitalista
    if (!$es_entrada) {
        $saldo = getSaldoCaja($db, $cobro);
        if ($saldo < $monto) {
            echo json_encode([
                'ok'  => false,
                'msg' => 'Saldo insuficiente en caja. Disponible: ' . fmt($saldo) . ' · Requerido: ' . fmt($monto)
            ]); exit;
        }
        validarSaldoCapitalista($db, $capitalista_id, $monto, $capRow['nombre']);
    }

    $db->beginTransaction();
    try {
        // origen='capital' → todos los movimientos de capitalistas afectan la base
        $db->prepare("INSERT INTO capital_movimientos
            (cobro_id, tipo, origen, es_entrada, monto, metodo_pago, capitalista_id, descripcion, fecha, usuario_id)
            VALUES (?,?,'capital',?,?,?,?,?,?,?)")
        ->execute([
            $cobro, $tipo_mov, $es_entrada, $monto, $metodo_pago, $capitalista_id,
            trim($data['descripcion'] ?? '') ?: null, $fecha, $_SESSION['usuario_id']
        ]);

        $db->commit();

        $msgs = [
            'ingreso_capital' => 'Ingreso registrado',
            'retiro'          => 'Retiro registrado',
            'redito'          => 'Rédito pagado registrado',
            'retiro_capital'  => 'Devolución registrada',
        ];
        echo json_encode(['ok'=>true,'msg'=>$msgs[$tipo_mov] ?? 'Movimiento registrado']);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
    }

} else {
    echo json_encode(['ok'=>false,'msg'=>'Acción no reconocida']);
}