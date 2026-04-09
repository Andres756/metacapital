<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
header('Content-Type: application/json');

$db    = getDB();
$cobro = cobroActivo();

// ============================================================
// GET
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!canDo('puede_ver_prestamos')) {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }

    $action     = $_GET['action'] ?? '';
    $prestamoId = (int)($_GET['prestamo_id'] ?? 0);

    // ── Cuotas pendientes de un préstamo ─────────────────────
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
        echo json_encode(['ok'=>true, 'cuotas'=>$stmt->fetchAll()]);
        exit;
    }

    // ── Resumen del día ───────────────────────────────────────
    if ($action === 'resumen_dia') {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $stmt  = $db->prepare("
            SELECT pg.*, d.nombre AS deudor, c.nombre AS cuenta, cu.numero_cuota
            FROM pagos pg
            JOIN deudores d ON d.id = pg.deudor_id
            JOIN cuotas cu  ON cu.id = pg.cuota_id
            LEFT JOIN cuentas c ON c.id = pg.cuenta_id
            WHERE pg.cobro_id=? AND pg.fecha_pago=? AND (pg.anulado=0 OR pg.anulado IS NULL)
            ORDER BY pg.created_at DESC
        ");
        $stmt->execute([$cobro, $fecha]);
        $pagos = $stmt->fetchAll();

        // FIX: la query ya filtra anulados — no hace falta array_filter
        $total = array_sum(array_column($pagos, 'monto_pagado'));

        echo json_encode(['ok'=>true, 'pagos'=>$pagos, 'total'=>$total]);
        exit;
    }

    echo json_encode(['ok'=>false,'msg'=>'Acción no reconocida']);
    exit;
}

// ============================================================
// POST
// ============================================================
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

            // Cargar y validar el pago
            $stmtP = $db->prepare("SELECT * FROM pagos WHERE id=? AND cobro_id=?");
            $stmtP->execute([$pago_id, $cobro]);
            $pago = $stmtP->fetch();
            if (!$pago) { echo json_encode(['ok'=>false,'msg'=>'Pago no encontrado']); exit; }
            if ($pago['anulado']) { echo json_encode(['ok'=>false,'msg'=>'El pago ya está anulado']); exit; }

            // 1. Anular el pago
            $db->prepare("UPDATE pagos SET anulado=1, anulado_at=NOW(), anulado_por=? WHERE id=?")
               ->execute([$_SESSION['usuario_id'], $pago_id]);

            // 2. FIX: recalcular lo que queda pagado en la cuota
            //    considerando otros pagos activos — no resetear a 0 si hay parciales previos
            $stmtPagCuota = $db->prepare("
                SELECT COALESCE(SUM(monto_pagado), 0)
                FROM pagos
                WHERE cuota_id=? AND anulado=0 AND id != ?
            ");
            $stmtPagCuota->execute([$pago['cuota_id'], $pago_id]);
            $pagado_restante = (float)$stmtPagCuota->fetchColumn();

            $stmtCuota = $db->prepare("SELECT monto_cuota FROM cuotas WHERE id=?");
            $stmtCuota->execute([$pago['cuota_id']]);
            $monto_cuota = (float)$stmtCuota->fetchColumn();

            $saldo_cuota_nuevo  = max(0, $monto_cuota - $pagado_restante);
            $estado_cuota_nuevo = $pagado_restante <= 0 ? 'pendiente' : 'parcial';
            $fecha_pago_nueva   = $pagado_restante <= 0 ? null : date('Y-m-d');

            $db->prepare("UPDATE cuotas
                SET estado=?, monto_pagado=?, saldo_cuota=?, fecha_pago=?, updated_at=NOW()
                WHERE id=?")
               ->execute([
                   $estado_cuota_nuevo,
                   $pagado_restante,
                   $saldo_cuota_nuevo,
                   $fecha_pago_nueva,
                   $pago['cuota_id']
               ]);

            // 3. Anular movimiento de capital vinculado (por pago_id — exacto y seguro)
            $db->prepare("UPDATE capital_movimientos
                SET anulado=1, anulado_at=NOW(), anulado_por=?
                WHERE pago_id=? AND cobro_id=?")
               ->execute([$_SESSION['usuario_id'], $pago_id, $cobro]);

            // 4. Recalcular saldo del préstamo desde cuotas reales
            $stmtSaldo = $db->prepare("
                SELECT COALESCE(SUM(saldo_cuota), 0)
                FROM cuotas
                WHERE prestamo_id=? AND estado NOT IN ('anulado')
            ");
            $stmtSaldo->execute([$pago['prestamo_id']]);
            $nuevo_saldo = (float)$stmtSaldo->fetchColumn();

            // 5. FIX: usar actualizarEstadoMora() en lugar del estado 'vencido'
            //    que no existe en el ENUM de cuotas — la mora se detecta por fecha
            $nuevo_estado = $nuevo_saldo <= 0
                ? 'pagado'
                : actualizarEstadoMora($db, $pago['prestamo_id']);

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