<?php
function fmt($n): string {
    if ($n === null || $n === '' || !is_numeric($n)) return '$0';
    return '$' . number_format((float)$n, 0, ',', '.');
}

function fmtFecha($fecha, string $formato = 'd M Y'): string {
    if (!$fecha) return '—';
    return date($formato, strtotime($fecha));
}

/**
 * Saldo real de la base (caja general del cobro).
 * Solo suma movimientos de origen 'capital' y 'liquidacion'.
 * Los movimientos del cobrador (prestamos, cobros del día)
 * NO afectan la base — solo se registran para auditoría.
 */
function getSaldoCaja(PDO $db, int $cobro_id): float {
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(CASE WHEN es_entrada=1 THEN monto ELSE -monto END), 0)
        FROM capital_movimientos
        WHERE cobro_id=? AND anulado=0
          AND origen IN ('capital','liquidacion')
    ");
    $stmt->execute([$cobro_id]);
    return (float)$stmt->fetchColumn();
}

/**
 * Saldo de la base por método de pago (informativo).
 * Solo cuenta movimientos de origen capital/liquidacion.
 */
function getSaldoPorMetodo(PDO $db, int $cobro_id): array {
    $stmt = $db->prepare("
        SELECT metodo_pago,
               COALESCE(SUM(CASE WHEN es_entrada=1 THEN monto ELSE -monto END), 0) AS saldo
        FROM capital_movimientos
        WHERE cobro_id=? AND anulado=0
          AND origen IN ('capital','liquidacion')
        GROUP BY metodo_pago
    ");
    $stmt->execute([$cobro_id]);
    $result = ['efectivo' => 0.0, 'banco' => 0.0];
    foreach ($stmt->fetchAll() as $row) {
        $result[$row['metodo_pago']] = (float)$row['saldo'];
    }
    return $result;
}

/**
 * Valida saldo de la base — sale con JSON error si no alcanza.
 * Usado por el admin al registrar salidas directas.
 */
function validarSaldoCaja(PDO $db, int $cobro_id, float $monto, string $metodo = 'efectivo'): void {
    $saldo = getSaldoCaja($db, $cobro_id);
    if ($saldo < $monto) {
        echo json_encode([
            'ok'  => false,
            'msg' => 'Saldo insuficiente en caja. Disponible: ' . fmt($saldo) . ' · Requerido: ' . fmt($monto)
        ]); exit;
    }
}

/**
 * Saldo real de un capitalista = entradas - salidas en capital_movimientos
 */
function getSaldoCapitalista(PDO $db, int $capitalista_id): float {
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(CASE WHEN es_entrada=1 THEN monto ELSE -monto END), 0)
        FROM capital_movimientos
        WHERE capitalista_id=? AND anulado=0
          AND origen IN ('capital','liquidacion')
    ");
    $stmt->execute([$capitalista_id]);
    return (float)$stmt->fetchColumn();
}

/**
 * Valida saldo de capitalista — sale con JSON error si no alcanza.
 */
function validarSaldoCapitalista(PDO $db, int $capitalista_id, float $monto, string $nombre = ''): void {
    $saldo = getSaldoCapitalista($db, $capitalista_id);
    if ($saldo < $monto) {
        if (!$nombre) {
            $s = $db->prepare("SELECT nombre FROM capitalistas WHERE id=?");
            $s->execute([$capitalista_id]);
            $nombre = $s->fetchColumn() ?: 'Capitalista';
        }
        echo json_encode([
            'ok'  => false,
            'msg' => 'Saldo insuficiente de "' . $nombre . '". Disponible: ' . fmt($saldo) . ' · Requerido: ' . fmt($monto)
        ]); exit;
    }
}

/**
 * Saldo de la base del cobrador para el día actual.
 * = base_trabajado + pagos cobrados - préstamos entregados - gastos aprobados
 * Se calcula en tiempo real desde los registros del día.
 */
function getSaldoCobrador(PDO $db, int $cobro_id, string $fecha = ''): float {
    if (!$fecha) $fecha = date('Y-m-d');

    // Base entregada hoy (desde la liquidación borrador)
    $stmtBase = $db->prepare("
        SELECT COALESCE(base_trabajado, 0)
        FROM liquidaciones
        WHERE cobro_id=? AND fecha=? AND estado='borrador'
        LIMIT 1
    ");
    $stmtBase->execute([$cobro_id, $fecha]);
    $base = (float)$stmtBase->fetchColumn();

    // Pagos cobrados hoy
    $stmtPagos = $db->prepare("
        SELECT COALESCE(SUM(monto_pagado), 0)
        FROM pagos
        WHERE cobro_id=? AND fecha_pago=? AND (anulado=0 OR anulado IS NULL)
    ");
    $stmtPagos->execute([$cobro_id, $fecha]);
    $pagos = (float)$stmtPagos->fetchColumn();

    // Préstamos entregados hoy
    $stmtPrest = $db->prepare("
        SELECT COALESCE(SUM(monto_prestado), 0)
        FROM prestamos
        WHERE cobro_id=? AND DATE(created_at)=? AND estado != 'anulado'
    ");
    $stmtPrest->execute([$cobro_id, $fecha]);
    $prestamos = (float)$stmtPrest->fetchColumn();

    // Gastos aprobados hoy
    $stmtGastos = $db->prepare("
        SELECT COALESCE(SUM(monto), 0)
        FROM gastos_cobrador
        WHERE cobro_id=? AND fecha=? AND estado='aprobado'
    ");
    $stmtGastos->execute([$cobro_id, $fecha]);
    $gastos = (float)$stmtGastos->fetchColumn();

    return $base + $pagos - $prestamos - $gastos;
}