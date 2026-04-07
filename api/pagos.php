<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
header('Content-Type: application/json');

$db    = getDB();
$cobro = cobroActivo();

// GET: cuotas pendientes de un préstamo
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!canDo('puede_ver_prestamos')) {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }

    $action     = $_GET['action'] ?? '';
    $prestamoId = (int)($_GET['prestamo_id'] ?? 0);

    if ($action === 'cuotas') {
        if (!$prestamoId) { echo json_encode(['ok'=>false,'msg'=>'Falta prestamo_id']); exit; }

        $check = $db->prepare("SELECT id FROM prestamos WHERE id=? AND cobro_id=?");
        $check->execute([$prestamoId, $cobro]);
        if (!$check->fetch()) { echo json_encode(['ok'=>false,'msg'=>'Préstamo no encontrado']); exit; }

        $stmt = $db->prepare("
            SELECT id, numero_cuota, fecha_vencimiento, monto_cuota, monto_pagado, saldo_cuota, estado
            FROM cuotas
            WHERE prestamo_id=? AND estado IN ('pendiente','parcial')
            ORDER BY numero_cuota ASC
        ");
        $stmt->execute([$prestamoId]);
        $cuotas = $stmt->fetchAll();

        echo json_encode(['ok'=>true, 'cuotas'=>$cuotas]);
        exit;
    }

    if ($action === 'resumen_dia') {
        if (!canDo('puede_ver_prestamos')) {
            echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
        }
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $stmt  = $db->prepare("
            SELECT pg.*, d.nombre AS deudor, c.nombre AS cuenta, cu.numero_cuota
            FROM pagos pg
            JOIN deudores d ON d.id=pg.deudor_id
            JOIN cuotas cu  ON cu.id=pg.cuota_id
            LEFT JOIN cuentas c ON c.id=pg.cuenta_id
            WHERE pg.cobro_id=? AND pg.fecha_pago=? AND (pg.anulado=0 OR pg.anulado IS NULL)
            ORDER BY pg.created_at DESC
        ");
        $stmt->execute([$cobro, $fecha]);
        $pagos = $stmt->fetchAll();
        $total = array_sum(array_column(array_filter($pagos, fn($p) => !$p['anulado']), 'monto_pagado'));
        echo json_encode(['ok'=>true, 'pagos'=>$pagos, 'total'=>$total]);
        exit;
    }

    echo json_encode(['ok'=>false,'msg'=>'Acción no reconocida']);
    exit;
}

// POST: anular pago
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!canDo('puede_anular_pago')) {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }

    $data    = json_decode(file_get_contents('php://input'), true) ?? [];
    $action  = $data['action'] ?? '';
    $pago_id = (int)($data['pago_id'] ?? 0);

    if ($action === 'anular' && $pago_id) {
        try {
            $db->beginTransaction();

            $stmtP = $db->prepare("SELECT * FROM pagos WHERE id=? AND cobro_id=?");
            $stmtP->execute([$pago_id, $cobro]);
            $pago = $stmtP->fetch();
            if (!$pago) { echo json_encode(['ok'=>false,'msg'=>'Pago no encontrado']); exit; }
            if ($pago['anulado']) { echo json_encode(['ok'=>false,'msg'=>'El pago ya está anulado']); exit; }

            $db->prepare("UPDATE pagos SET anulado=1, anulado_at=NOW(), anulado_por=? WHERE id=?")
               ->execute([$_SESSION['usuario_id'], $pago_id]);

            $db->prepare("UPDATE cuotas
                SET estado='pendiente', monto_pagado=0, saldo_cuota=monto_cuota, fecha_pago=NULL, updated_at=NOW()
                WHERE id=?")
               ->execute([$pago['cuota_id']]);

            $db->prepare("UPDATE capital_movimientos SET anulado=1, anulado_at=NOW(), anulado_por=?
                WHERE pago_id=? AND cobro_id=?")
               ->execute([$_SESSION['usuario_id'], $pago_id, $cobro]);

            $db->prepare("UPDATE capital_movimientos SET anulado=1, anulado_at=NOW(), anulado_por=?
                WHERE pago_id IS NULL AND tipo='cobro_cuota'
                  AND prestamo_id=? AND monto=? AND fecha=? AND cobro_id=? AND (anulado=0 OR anulado IS NULL)
                LIMIT 1")
               ->execute([$_SESSION['usuario_id'], $pago['prestamo_id'], $pago['monto_pagado'], $pago['fecha_pago'], $cobro]);

            $stmtSaldo = $db->prepare("
                SELECT COALESCE(SUM(saldo_cuota),0) AS saldo
                FROM cuotas WHERE prestamo_id=? AND estado NOT IN ('anulado')
            ");
            $stmtSaldo->execute([$pago['prestamo_id']]);
            $nuevo_saldo = (float)$stmtSaldo->fetchColumn();

            $stmtEst = $db->prepare("
                SELECT
                    SUM(CASE WHEN estado IN ('pendiente','parcial') THEN 1 ELSE 0 END) AS pendientes,
                    SUM(CASE WHEN estado='vencido' THEN 1 ELSE 0 END) AS vencidas
                FROM cuotas WHERE prestamo_id=? AND estado != 'anulado'
            ");
            $stmtEst->execute([$pago['prestamo_id']]);
            $est = $stmtEst->fetch();

            $nuevo_estado = $est['vencidas'] > 0 ? 'en_mora'
                : ($est['pendientes'] > 0 ? 'activo' : 'pagado');

            $db->prepare("UPDATE prestamos SET saldo_pendiente=?, estado=?, updated_at=NOW() WHERE id=?")
               ->execute([$nuevo_saldo, $nuevo_estado, $pago['prestamo_id']]);

            $db->commit();
            echo json_encode(['ok'=>true,'msg'=>'Pago anulado correctamente. Cuota revertida a pendiente.']);

        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['ok'=>false,'msg'=>'Acción no reconocida']);
    exit;
}

echo json_encode(['ok'=>false,'msg'=>'Método no permitido']);