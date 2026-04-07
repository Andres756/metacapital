<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
header('Content-Type: application/json');

$db    = getDB();
$cobro = cobroActivo();
$method = $_SERVER['REQUEST_METHOD'];

// GET - historial de un capitalista
if ($method === 'GET') {
    if (!canDo('puede_ver_historial_capitalista')) {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }

    $action         = $_GET['action'] ?? '';
    $capitalista_id = (int)($_GET['capitalista_id'] ?? 0);

    if ($action === 'historial') {
        $stmtC = $db->prepare("SELECT * FROM capitalistas WHERE id=? AND cobro_id=?");
        $stmtC->execute([$capitalista_id, $cobro]);
        $cap = $stmtC->fetch();
        if (!$cap) { echo json_encode(['ok'=>false,'msg'=>'No encontrado']); exit; }

        $stmtM = $db->prepare("
            SELECT m.*, c.nombre AS cuenta_nombre
            FROM capital_movimientos m
            LEFT JOIN cuentas c ON c.id = m.cuenta_id
            WHERE m.capitalista_id=? AND m.cobro_id=?
              AND (m.anulado=0 OR m.anulado IS NULL)
            ORDER BY m.fecha DESC, m.id DESC LIMIT 60
        ");
        $stmtM->execute([$capitalista_id, $cobro]);
        $movs = $stmtM->fetchAll();

        $stmtS = $db->prepare("SELECT saldo_actual FROM v_saldo_capitalistas WHERE capitalista_id=?");
        $stmtS->execute([$capitalista_id]);
        $saldo = $stmtS->fetchColumn() ?? 0;

        echo json_encode(['ok'=>true, 'capitalista'=>$cap, 'movimientos'=>$movs, 'saldo'=>$saldo]);
    } else {
        echo json_encode(['ok'=>false,'msg'=>'Acción no reconocida']);
    }
    exit;
}

if ($method !== 'POST') { echo json_encode(['ok'=>false,'msg'=>'Método no permitido']); exit; }

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $data['action'] ?? '';

// ---- CREAR CAPITALISTA ----
if ($action === 'crear_capitalista') {
    if (!canDo('puede_crear_capitalista')) { echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit; }

    $nombre = trim($data['nombre'] ?? '');
    if (!$nombre) { echo json_encode(['ok'=>false,'msg'=>'El nombre es obligatorio']); exit; }

    $tipo          = in_array($data['tipo']??'',['propio','prestado']) ? $data['tipo'] : 'propio';
    $monto_inicial = (float)($data['monto_inicial'] ?? 0);
    $tipo_redito   = in_array($data['tipo_redito']??'',['porcentaje','valor_fijo']) ? $data['tipo_redito'] : 'porcentaje';
    $tasa_redito   = (float)($data['tasa_redito'] ?? 0);
    $frecuencia    = in_array($data['frecuencia_redito']??'',['mensual','quincenal','libre']) ? $data['frecuencia_redito'] : 'mensual';
    $fecha_inicio  = $data['fecha_inicio'] ?? date('Y-m-d');
    $color         = $data['color'] ?? '#7c6aff';
    $cuenta_id     = (int)($data['cuenta_id'] ?? 0);

    $db->beginTransaction();
    try {
        $db->prepare("INSERT INTO capitalistas
            (cobro_id, nombre, tipo, monto_inicial, tipo_redito, tasa_redito, frecuencia_redito, fecha_inicio, color, descripcion, estado)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([
            $cobro, $nombre, $tipo, $monto_inicial, $tipo_redito, $tasa_redito,
            $frecuencia, $fecha_inicio, $color,
            trim($data['descripcion'] ?? '') ?: null, 'activo'
        ]);
        $cap_id = $db->lastInsertId();

        if ($monto_inicial > 0 && $cuenta_id) {
            $db->prepare("INSERT INTO capital_movimientos
                (cobro_id, tipo, es_entrada, monto, cuenta_id, capitalista_id, descripcion, fecha, usuario_id)
                VALUES (?,?,1,?,?,?,?,?,?)")
            ->execute([
                $cobro, 'ingreso_capital', $monto_inicial, $cuenta_id, $cap_id,
                'Saldo inicial — ' . $nombre, $fecha_inicio, $_SESSION['usuario_id']
            ]);
        }

        $db->commit();
        echo json_encode(['ok'=>true, 'msg'=>'Capitalista registrado', 'id'=>$cap_id]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['ok'=>false, 'msg'=>'Error: '.$e->getMessage()]);
    }

// ---- REGISTRAR MOVIMIENTO MANUAL ----
} elseif ($action === 'movimiento') {
    if (!canDo('puede_registrar_movimiento_capital')) { echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit; }

    $capitalista_id = (int)($data['capitalista_id'] ?? 0);
    $tipo           = $data['tipo'] ?? '';
    $monto          = (float)($data['monto'] ?? 0);
    $fecha          = $data['fecha'] ?? date('Y-m-d');
    $cuenta_id      = (int)($data['cuenta_id'] ?? 0);

    $tipoMap = [
        'abono'           => 'ingreso_capital',
        'retiro'          => 'retiro',
        'redito'          => 'redito',
        'devolucion'      => 'devolucion',
        'ingreso_capital' => 'ingreso_capital',
    ];
    if (isset($tipoMap[$tipo])) $tipo = $tipoMap[$tipo];

    $tiposEntrada = ['ingreso_capital'];
    $tiposSalida  = ['retiro', 'redito', 'devolucion'];
    $tiposValidos = array_merge($tiposEntrada, $tiposSalida);

    if (!$capitalista_id || !in_array($tipo, $tiposValidos) || $monto <= 0) {
        echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit;
    }
    if (!$cuenta_id) {
        echo json_encode(['ok'=>false,'msg'=>'Selecciona la cuenta']); exit;
    }

    $check = $db->prepare("SELECT nombre FROM capitalistas WHERE id=? AND cobro_id=?");
    $check->execute([$capitalista_id, $cobro]);
    $capRow = $check->fetch();
    if (!$capRow) { echo json_encode(['ok'=>false,'msg'=>'Capitalista no encontrado']); exit; }

    $es_entrada = in_array($tipo, $tiposEntrada) ? 1 : 0;

    if (!$es_entrada) {
        validarSaldoCuenta($db, $cuenta_id, $cobro, $monto);
        validarSaldoCapitalista($db, $capitalista_id, $monto, $capRow['nombre']);
    }

    $db->prepare("INSERT INTO capital_movimientos
        (cobro_id, tipo, es_entrada, monto, cuenta_id, capitalista_id, descripcion, fecha, usuario_id)
        VALUES (?,?,?,?,?,?,?,?,?)")
    ->execute([
        $cobro, $tipo, $es_entrada, $monto, $cuenta_id, $capitalista_id,
        trim($data['descripcion'] ?? '') ?: null, $fecha, $_SESSION['usuario_id']
    ]);

    $msgs = [
        'ingreso_capital' => 'Ingreso registrado',
        'retiro'          => 'Retiro registrado',
        'redito'          => 'Rédito pagado registrado',
        'devolucion'      => 'Devolución registrada',
    ];
    echo json_encode(['ok'=>true, 'msg'=>$msgs[$tipo] ?? 'Movimiento registrado']);

// ---- CREAR / EDITAR CUENTA ----
} elseif ($action === 'cuenta') {
    if (!canDo($data['id'] ? 'puede_editar_cuenta' : 'puede_crear_cuenta')) {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }

    $nombre = trim($data['nombre'] ?? '');
    if (!$nombre) { echo json_encode(['ok'=>false,'msg'=>'El nombre es obligatorio']); exit; }

    $tipo    = in_array($data['tipo']??'',['efectivo','nequi','bancolombia','daviplata','transfiya','otro']) ? $data['tipo'] : 'efectivo';
    $numero  = trim($data['numero']  ?? '') ?: null;
    $titular = trim($data['titular'] ?? '') ?: null;
    $id      = (int)($data['id'] ?? 0);

    if ($id) {
        $check = $db->prepare("SELECT id FROM cuentas WHERE id=? AND cobro_id=?");
        $check->execute([$id, $cobro]);
        if (!$check->fetch()) { echo json_encode(['ok'=>false,'msg'=>'Cuenta no encontrada']); exit; }
        $db->prepare("UPDATE cuentas SET nombre=?, tipo=?, numero=?, titular=?, updated_at=NOW() WHERE id=?")
           ->execute([$nombre, $tipo, $numero, $titular, $id]);
        echo json_encode(['ok'=>true, 'msg'=>'Cuenta actualizada']);
    } else {
        $db->prepare("INSERT INTO cuentas (cobro_id, nombre, tipo, numero, titular) VALUES (?,?,?,?,?)")
           ->execute([$cobro, $nombre, $tipo, $numero, $titular]);
        echo json_encode(['ok'=>true, 'msg'=>'Cuenta creada']);
    }

} else {
    echo json_encode(['ok'=>false, 'msg'=>'Acción no reconocida']);
}