<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

if (!in_array($_SESSION['rol'], ['admin','superadmin'])) {
    echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok'=>false,'msg'=>'Método no permitido']); exit;
}

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$db     = getDB();
$cobro  = cobroActivo();
$action = $data['action'] ?? '';
$hoy    = date('Y-m-d');

// ============================================================
// INICIAR LIQUIDACIÓN
// ============================================================
if ($action === 'iniciar') {
    $base_trabajado = (float)($data['base_trabajado'] ?? 0);

    // Aceptar fecha personalizada — no solo $hoy
    $fecha = $data['fecha'] ?? $hoy;
    if (!$fecha || !strtotime($fecha) || $fecha > $hoy) {
        echo json_encode(['ok'=>false,'msg'=>'Fecha inválida o futura']); exit;
    }

    // Verificar que no haya liquidación en esa fecha
    $chk = $db->prepare("SELECT id FROM liquidaciones WHERE cobro_id=? AND fecha=?");
    $chk->execute([$cobro, $fecha]);
    if ($chk->fetch()) {
        echo json_encode(['ok'=>false,'msg'=>'Ya existe una liquidación para esta fecha']); exit;
    }

    // FIX: verificar orden — no se puede saltar días sin liquidar
    $stmtUltima = $db->prepare("
        SELECT fecha FROM liquidaciones
        WHERE cobro_id=? AND estado='cerrada'
        ORDER BY fecha DESC
        LIMIT 1
    ");
    $stmtUltima->execute([$cobro]);
    $ultimaFecha = $stmtUltima->fetchColumn();

    if ($ultimaFecha) {
        $siguienteDia = date('Y-m-d', strtotime($ultimaFecha . ' +1 day'));

        if ($fecha < $siguienteDia) {
            echo json_encode([
                'ok'  => false,
                'msg' => 'Esta fecha es anterior o igual a la última liquidación (' . date('d M Y', strtotime($ultimaFecha)) . ')'
            ]); exit;
        }

        if ($fecha !== $siguienteDia) {
            // Hay días sin liquidar entre la última y la fecha elegida
            $diasPendientes = [];
            $cursor = new DateTime($siguienteDia);
            $limite = new DateTime($fecha);
            while ($cursor < $limite) {
                $diasPendientes[] = $cursor->format('d M Y');
                $cursor->modify('+1 day');
            }

            echo json_encode([
                'ok'              => false,
                'msg'             => 'Debes liquidar primero los días pendientes en orden. Próximo día a liquidar: ' . date('d M Y', strtotime($siguienteDia)),
                'dias_pendientes' => $diasPendientes,
                'siguiente'       => $siguienteDia
            ]); exit;
        }
    }
    // Si no hay liquidaciones previas — puede elegir cualquier fecha pasada
    // pero igualmente debe ir en orden desde esa fecha en adelante

    // Calcular base de caja al cierre del día anterior a la fecha
    $stmtBase = $db->prepare("
        SELECT COALESCE(SUM(CASE WHEN es_entrada=1 THEN monto ELSE -monto END), 0)
        FROM capital_movimientos
        WHERE cobro_id=?
          AND tipo NOT IN ('prestamo_proporcional','cobro_proporcional')
          AND anulado=0
          AND fecha < ?
    ");
    $stmtBase->execute([$cobro, $fecha]);
    $base = (float)$stmtBase->fetchColumn();

    // Validar saldo suficiente para base trabajado
    if ($base_trabajado > 0 && $base_trabajado > $base) {
        echo json_encode([
            'ok'  => false,
            'msg' => 'Saldo insuficiente en caja. Disponible: '.fmt($base).' · Base trabajado: '.fmt($base_trabajado)
        ]); exit;
    }

    $db->prepare("INSERT INTO liquidaciones
        (cobro_id, usuario_id, fecha, base, base_trabajado, estado)
        VALUES (?,?,?,?,?,'borrador')")
    ->execute([
        $cobro, $_SESSION['usuario_id'],
        $fecha, $base, $base_trabajado
    ]);

    $nuevo_id = (int)$db->lastInsertId();

    echo json_encode([
        'ok'  => true,
        'msg' => 'Liquidación iniciada. Base trabajado: '.fmt($base_trabajado),
        'id'  => $nuevo_id
    ]);

// ============================================================
// CERRAR LIQUIDACIÓN
// ============================================================
} elseif ($action === 'cerrar') {
    $liquidacion_id      = (int)($data['liquidacion_id']      ?? 0);
    $dinero_entregado    = (float)($data['dinero_entregado']   ?? 0);
    $papeleria_entregada = (float)($data['papeleria_entregada'] ?? 0);
    $papeleria_prestamos = $data['papeleria_prestamos']        ?? [];
    $notas               = trim($data['notas'] ?? '');

    if (!$liquidacion_id) { echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }
    if ($dinero_entregado <= 0) { echo json_encode(['ok'=>false,'msg'=>'Ingresa el dinero entregado']); exit; }

    // Cargar liquidación
    $stmtL = $db->prepare("SELECT * FROM liquidaciones WHERE id=? AND cobro_id=? AND estado='borrador'");
    $stmtL->execute([$liquidacion_id, $cobro]);
    $liq = $stmtL->fetch();
    if (!$liq) { echo json_encode(['ok'=>false,'msg'=>'Liquidación no encontrada o ya cerrada']); exit; }

    // FIX: usar la fecha de la liquidación, no $hoy
    $fecha_liq = $liq['fecha'];

    // Calcular totales del día de la liquidación
    $stmtPagos = $db->prepare("
        SELECT COALESCE(SUM(monto_pagado), 0) FROM pagos
        WHERE cobro_id=? AND fecha_pago=? AND (anulado=0 OR anulado IS NULL)
    ");
    $stmtPagos->execute([$cobro, $fecha_liq]);
    $total_pagos = (float)$stmtPagos->fetchColumn();

    $stmtPrest = $db->prepare("
        SELECT COALESCE(SUM(monto_prestado), 0) FROM prestamos
        WHERE cobro_id=? AND DATE(created_at)=? AND estado != 'anulado'
    ");
    $stmtPrest->execute([$cobro, $fecha_liq]);
    $total_prestamos = (float)$stmtPrest->fetchColumn();

    $stmtGastos = $db->prepare("
        SELECT COALESCE(SUM(monto), 0) FROM gastos_cobrador
        WHERE cobro_id=? AND fecha=? AND estado='aprobado'
    ");
    $stmtGastos->execute([$cobro, $fecha_liq]);
    $total_gastos = (float)$stmtGastos->fetchColumn();

    $base_trabajado    = (float)$liq['base_trabajado'];
    $base              = (float)$liq['base'];
    $efectivo_esperado = ($total_pagos + $base_trabajado) - $total_prestamos - $total_gastos;
    $diferencia        = $efectivo_esperado - $dinero_entregado;
    $nueva_base        = ($base - $base_trabajado) + $dinero_entregado;

    $db->beginTransaction();
    try {
        // Actualizar % y monto de papelería por préstamo si se modificaron
        foreach ($papeleria_prestamos as $prest_id => $pct) {
            $prest_id   = (int)$prest_id;
            $pct        = max(0, min(100, (float)$pct));

            $stmtM = $db->prepare("SELECT monto_prestado FROM prestamos WHERE id=? AND cobro_id=?");
            $stmtM->execute([$prest_id, $cobro]);
            $montoPrest = (float)($stmtM->fetchColumn() ?: 0);
            $nuevoMonto = round($montoPrest * ($pct / 100), 0);

            $db->prepare("UPDATE prestamos SET papeleria_pct=?, papeleria_monto=? WHERE id=? AND cobro_id=?")
               ->execute([$pct, $nuevoMonto, $prest_id, $cobro]);

            $db->prepare("UPDATE papeleria SET pct_aplicado=?, monto_papeleria=? WHERE prestamo_id=? AND cobro_id=?")
               ->execute([$pct, $nuevoMonto, $prest_id, $cobro]);
        }

        // Total papelería del día después de ajustes
        $stmtPap = $db->prepare("
            SELECT COALESCE(SUM(monto_papeleria), 0) FROM papeleria
            WHERE cobro_id=? AND fecha=?
        ");
        $stmtPap->execute([$cobro, $fecha_liq]);
        $total_papeleria = (float)$stmtPap->fetchColumn();

        // Vincular papelería del día a esta liquidación
        $db->prepare("UPDATE papeleria SET liquidacion_id=? WHERE cobro_id=? AND fecha=?")
           ->execute([$liquidacion_id, $cobro, $fecha_liq]);

        // Cerrar liquidación
        $db->prepare("UPDATE liquidaciones SET
            total_pagos=?, total_prestamos=?, total_gastos_aprobados=?,
            total_papeleria=?, papeleria_entregada=?,
            efectivo_esperado=?, dinero_entregado=?, diferencia=?,
            nueva_base=?, notas=?, estado='cerrada',
            cerrada_at=NOW(), cerrada_por=?
            WHERE id=?")
        ->execute([
            $total_pagos, $total_prestamos, $total_gastos,
            $total_papeleria, $papeleria_entregada,
            $efectivo_esperado, $dinero_entregado, $diferencia,
            $nueva_base, $notas ?: null, $_SESSION['usuario_id'],
            $liquidacion_id
        ]);

        // Vincular gastos aprobados
        $db->prepare("UPDATE gastos_cobrador SET liquidacion_id=? WHERE cobro_id=? AND fecha=? AND estado='aprobado'")
           ->execute([$liquidacion_id, $cobro, $fecha_liq]);

        // Activar cobrador automáticamente
        $stmtCob = $db->prepare("
            SELECT u.id FROM usuarios u
            JOIN usuario_cobro uc ON uc.usuario_id = u.id
            WHERE uc.cobro_id=? AND u.rol='cobrador' LIMIT 1
        ");
        $stmtCob->execute([$cobro]);
        $cobId = $stmtCob->fetchColumn();
        if ($cobId) {
            $db->prepare("UPDATE usuarios SET activo=1, updated_at=NOW() WHERE id=?")->execute([$cobId]);
        }

        $db->commit();

        $msg = 'Liquidación cerrada. Nueva base: '.fmt($nueva_base);
        if (abs($diferencia) > 0) {
            $msg .= $diferencia > 0
                ? ' · Cobrador debe '.fmt(abs($diferencia))
                : ' · Cobrador entregó '.fmt(abs($diferencia)).' de más';
        }
        if ($total_papeleria > 0) {
            $msg .= ' · Papelería: '.fmt($total_papeleria);
        }

        echo json_encode(['ok'=>true,'msg'=>$msg]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
    }

// ============================================================
// LISTAR LIQUIDACIONES
// ============================================================
} elseif ($action === 'lista') {
    $estado    = $data['estado']    ?? '';
    $fecha_ini = $data['fecha_ini'] ?? '';
    $fecha_fin = $data['fecha_fin'] ?? '';

    $where  = "WHERE l.cobro_id = ?";
    $params = [$cobro];

    if ($estado && in_array($estado, ['borrador','cerrada'])) {
        $where   .= " AND l.estado = ?";
        $params[] = $estado;
    }
    if ($fecha_ini && strtotime($fecha_ini)) {
        $where   .= " AND l.fecha >= ?";
        $params[] = $fecha_ini;
    }
    if ($fecha_fin && strtotime($fecha_fin)) {
        $where   .= " AND l.fecha <= ?";
        $params[] = $fecha_fin;
    }

    $stmt = $db->prepare("
        SELECT l.*, u.nombre AS cobrador_nombre
        FROM liquidaciones l
        LEFT JOIN usuarios u ON u.id = l.usuario_id
        $where
        ORDER BY l.fecha DESC
    ");
    $stmt->execute($params);
    echo json_encode(['ok'=>true,'liquidaciones'=>$stmt->fetchAll()]);

// ============================================================
// FECHAS YA LIQUIDADAS
// ============================================================
} elseif ($action === 'fechas_usadas') {
    $stmt = $db->prepare("SELECT fecha FROM liquidaciones WHERE cobro_id=?");
    $stmt->execute([$cobro]);
    $fechas = array_column($stmt->fetchAll(), 'fecha');
    echo json_encode(['ok'=>true,'fechas'=>$fechas]);

// ============================================================
// PREVIEW DÍA
// ============================================================
} elseif ($action === 'preview_dia') {
    $fecha = $data['fecha'] ?? '';
    if (!$fecha || !strtotime($fecha)) {
        echo json_encode(['ok'=>false,'msg'=>'Fecha inválida']); exit;
    }

    $stmtP = $db->prepare("SELECT COALESCE(SUM(monto_pagado),0) FROM pagos WHERE cobro_id=? AND fecha_pago=? AND (anulado=0 OR anulado IS NULL)");
    $stmtP->execute([$cobro, $fecha]);
    $total_pagos = (float)$stmtP->fetchColumn();

    $stmtPr = $db->prepare("SELECT COALESCE(SUM(monto_prestado),0) FROM prestamos WHERE cobro_id=? AND DATE(created_at)=? AND estado!='anulado'");
    $stmtPr->execute([$cobro, $fecha]);
    $total_prestamos = (float)$stmtPr->fetchColumn();

    $stmtG = $db->prepare("SELECT COALESCE(SUM(monto),0) FROM gastos_cobrador WHERE cobro_id=? AND fecha=? AND estado='aprobado'");
    $stmtG->execute([$cobro, $fecha]);
    $total_gastos = (float)$stmtG->fetchColumn();

    $stmtPap = $db->prepare("SELECT COALESCE(SUM(monto_papeleria),0) FROM papeleria WHERE cobro_id=? AND fecha=?");
    $stmtPap->execute([$cobro, $fecha]);
    $total_papeleria = (float)$stmtPap->fetchColumn();

    echo json_encode([
        'ok'              => true,
        'total_pagos'     => $total_pagos,
        'total_prestamos' => $total_prestamos,
        'total_gastos'    => $total_gastos,
        'total_papeleria' => $total_papeleria,
    ]);

} else {
    echo json_encode(['ok'=>false,'msg'=>'Acción no reconocida']);
}