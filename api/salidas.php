<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok'=>false,'msg'=>'Método no permitido']); exit;
}

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$db     = getDB();
$cobro  = (int)($data['cobro_id'] ?? 0) ?: cobroActivo();
$action = $data['action'] ?? '';

// Verificar acceso al cobro
if ($cobro && $_SESSION['rol'] !== 'superadmin') {
    $chk = $db->prepare("SELECT 1 FROM usuario_cobro WHERE usuario_id=? AND cobro_id=?");
    $chk->execute([$_SESSION['usuario_id'], $cobro]);
    if (!$chk->fetch()) {
        echo json_encode(['ok'=>false,'msg'=>'Sin acceso a ese cobro']); exit;
    }
}

// ============================================================
// CREAR SALIDA
// ============================================================
if ($action === 'crear') {
    if (!canDo('puede_crear_salida')) { echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit; }

    $tipo           = $data['tipo']        ?? '';
    $tipo_salida_id = (int)($data['tipo_salida_id'] ?? 0) ?: null;
    $monto          = (float)($data['monto'] ?? 0);
    $fecha          = $data['fecha']       ?? date('Y-m-d');
    $metodo_pago    = in_array($data['metodo_pago']??'',['efectivo','banco']) ? $data['metodo_pago'] : 'efectivo';

    // Tipos que afectan lógica de capitalistas
    $tiposFijos = [
        'redito_capitalista' => 'redito',
        'devolucion_capital' => 'retiro_capital',
        'liquidacion'        => 'liquidacion',
    ];

    if ($monto <= 0) { echo json_encode(['ok'=>false,'msg'=>'El monto debe ser mayor a 0']); exit; }
    if (!$fecha || !strtotime($fecha)) { echo json_encode(['ok'=>false,'msg'=>'Fecha inválida']); exit; }

    $saldo = getSaldoCaja($db, $cobro);
    if ($saldo < $monto) {
        echo json_encode(['ok'=>false,'msg'=>'Saldo insuficiente. Disponible: '.fmt($saldo).' · Requerido: '.fmt($monto)]); exit;
    }

    $capitalista_id = (int)($data['capitalista_id'] ?? 0) ?: null;
    $descripcion    = trim($data['descripcion'] ?? '');

    // ── Tipo fijo con lógica de capitalista ──
    if (isset($tiposFijos[$tipo])) {
        $tipo_mov = $tiposFijos[$tipo];

        if ($capitalista_id) {
            $chkCap = $db->prepare("SELECT id FROM capitalistas WHERE id=? AND cobro_id=? AND estado='activo'");
            $chkCap->execute([$capitalista_id, $cobro]);
            if (!$chkCap->fetch()) {
                echo json_encode(['ok'=>false,'msg'=>'Capitalista no encontrado']); exit;
            }
        }

        $db->beginTransaction();
        try {
            $db->prepare("INSERT INTO capital_movimientos
                (cobro_id, tipo, es_entrada, monto, metodo_pago, capitalista_id, descripcion, fecha, usuario_id)
                VALUES (?, ?, 0, ?, ?, ?, ?, ?, ?)")
            ->execute([$cobro, $tipo_mov, $monto, $metodo_pago,
                       $capitalista_id, $descripcion ?: ucfirst(str_replace('_',' ',$tipo)), $fecha, $_SESSION['usuario_id']]);

            if ($tipo === 'liquidacion' && $capitalista_id) {
                $db->prepare("UPDATE capitalistas SET estado='liquidado', updated_at=NOW() WHERE id=? AND cobro_id=?")
                   ->execute([$capitalista_id, $cobro]);
            }

            $db->commit();
            $msgs = [
                'redito_capitalista' => 'Rédito registrado',
                'devolucion_capital' => 'Devolución registrada',
                'liquidacion'        => 'Liquidación registrada',
            ];
            echo json_encode(['ok'=>true,'msg'=>$msgs[$tipo]]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
        }

    // ── Tipo personalizado ──
    } elseif ($tipo === 'gasto_operativo' && $tipo_salida_id) {
        // Verificar que el tipo existe
        $chkTipo = $db->prepare("SELECT nombre FROM tipos_salida WHERE id=? AND activo=1");
        $chkTipo->execute([$tipo_salida_id]);
        $tipoRow = $chkTipo->fetch();
        if (!$tipoRow) { echo json_encode(['ok'=>false,'msg'=>'Tipo de salida no encontrado']); exit; }

        $db->prepare("INSERT INTO capital_movimientos
            (cobro_id, tipo, tipo_salida_id, es_entrada, monto, metodo_pago, descripcion, fecha, usuario_id)
            VALUES (?, 'salida', ?, 0, ?, ?, ?, ?, ?)")
        ->execute([$cobro, $tipo_salida_id, $monto, $metodo_pago,
                   $descripcion ?: $tipoRow['nombre'], $fecha, $_SESSION['usuario_id']]);

        echo json_encode(['ok'=>true,'msg'=>htmlspecialchars($tipoRow['nombre']).' registrado']);

    } else {
        echo json_encode(['ok'=>false,'msg'=>'Tipo de salida inválido']);
    }

// ============================================================
// ELIMINAR SALIDA
// ============================================================
} elseif ($action === 'eliminar') {
    if (!canDo('puede_eliminar_salida')) { echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit; }

    $id = (int)($data['id'] ?? 0);
    if (!$id) { echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

    $check = $db->prepare("SELECT * FROM capital_movimientos WHERE id=? AND es_entrada=0");
    $check->execute([$id]);
    $mov = $check->fetch();
    if (!$mov) { echo json_encode(['ok'=>false,'msg'=>'Registro no encontrado']); exit; }

    if ($_SESSION['rol'] !== 'superadmin') {
        $chk2 = $db->prepare("SELECT 1 FROM usuario_cobro WHERE usuario_id=? AND cobro_id=?");
        $chk2->execute([$_SESSION['usuario_id'], $mov['cobro_id']]);
        if (!$chk2->fetch()) { echo json_encode(['ok'=>false,'msg'=>'Sin acceso']); exit; }
    }

    if ($mov['anulado']) { echo json_encode(['ok'=>false,'msg'=>'Ya estaba anulado']); exit; }

    $db->prepare("UPDATE capital_movimientos SET anulado=1, anulado_at=NOW(), anulado_por=? WHERE id=?")
       ->execute([$_SESSION['usuario_id'], $id]);

    echo json_encode(['ok'=>true,'msg'=>'Movimiento anulado']);

// ============================================================
// CREAR TIPO DE SALIDA
// ============================================================
} elseif ($action === 'crear_tipo') {
    if (!in_array($_SESSION['rol'], ['admin','superadmin'])) {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }

    $nombre = trim($data['nombre'] ?? '');
    if (!$nombre) { echo json_encode(['ok'=>false,'msg'=>'El nombre es obligatorio']); exit; }

    $chk = $db->prepare("SELECT id FROM tipos_salida WHERE nombre=?");
    $chk->execute([$nombre]);
    if ($chk->fetch()) { echo json_encode(['ok'=>false,'msg'=>'Ya existe un tipo con ese nombre']); exit; }

    $db->prepare("INSERT INTO tipos_salida (nombre) VALUES (?)")->execute([$nombre]);
    echo json_encode(['ok'=>true,'msg'=>'Tipo creado: '.$nombre]);

// ============================================================
// ELIMINAR TIPO DE SALIDA
// ============================================================
} elseif ($action === 'eliminar_tipo') {
    if (!in_array($_SESSION['rol'], ['admin','superadmin'])) {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }

    $id = (int)($data['id'] ?? 0);
    if (!$id) { echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

    // Verificar que no tenga movimientos asociados
    $chkUso = $db->prepare("SELECT COUNT(*) FROM capital_movimientos WHERE tipo_salida_id=? AND (anulado=0 OR anulado IS NULL)");
    $chkUso->execute([$id]);
    if ((int)$chkUso->fetchColumn() > 0) {
        echo json_encode(['ok'=>false,'msg'=>'No se puede eliminar — tiene movimientos registrados. Puedes inactivarlo.']); exit;
    }

    $db->prepare("DELETE FROM tipos_salida WHERE id=?")->execute([$id]);
    echo json_encode(['ok'=>true,'msg'=>'Tipo eliminado']);

} else {
    echo json_encode(['ok'=>false,'msg'=>'Acción no reconocida']);
}