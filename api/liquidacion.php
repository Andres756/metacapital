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

    // Verificar orden — no se puede saltar días sin liquidar
    $stmtUltima = $db->prepare("
        SELECT fecha FROM liquidaciones
        WHERE cobro_id=? AND estado='cerrada'
        ORDER BY fecha DESC LIMIT 1
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

    // Base actual de caja — usa getSaldoCaja que filtra origen capital/liquidacion
    $base = getSaldoCaja($db, $cobro);

    // Validar que hay saldo suficiente para entregar la base trabajado
    if ($base_trabajado > 0 && $base_trabajado > $base) {
        echo json_encode([
            'ok'  => false,
            'msg' => 'Saldo insuficiente en caja. Disponible: ' . fmt($base) . ' · Base a entregar: ' . fmt($base_trabajado)
        ]); exit;
    }

    $db->beginTransaction();
    try {
        // Crear la liquidación
        $db->prepare("INSERT INTO liquidaciones
            (cobro_id, usuario_id, fecha, base, base_trabajado, estado)
            VALUES (?,?,?,?,?,'borrador')")
        ->execute([$cobro, $_SESSION['usuario_id'], $fecha, $base, $base_trabajado]);

        $nuevo_id = (int)$db->lastInsertId();

        // Registrar salida de caja = base entregada al cobrador
        // origen='liquidacion' → sí afecta la base general
        if ($base_trabajado > 0) {
            $db->prepare("INSERT INTO capital_movimientos
                (cobro_id, tipo, origen, es_entrada, monto, metodo_pago,
                 descripcion, fecha, usuario_id)
                VALUES (?, 'salida', 'liquidacion', 0, ?, 'efectivo',
                 ?, ?, ?)")
            ->execute([
                $cobro,
                $base_trabajado,
                "Base entregada al cobrador — liquidación #{$nuevo_id} del " . date('d/m/Y', strtotime($fecha)),
                $fecha,
                $_SESSION['usuario_id']
            ]);
        }

        $db->commit();

        echo json_encode([
            'ok'  => true,
            'msg' => 'Liquidación iniciada. Base entregada: ' . fmt($base_trabajado),
            'id'  => $nuevo_id
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
    }

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
    if ($dinero_entregado < 0) { echo json_encode(['ok'=>false,'msg'=>'El dinero entregado no puede ser negativo']); exit; }

    $stmtL = $db->prepare("SELECT * FROM liquidaciones WHERE id=? AND cobro_id=? AND estado='borrador'");
    $stmtL->execute([$liquidacion_id, $cobro]);
    $liq = $stmtL->fetch();
    if (!$liq) { echo json_encode(['ok'=>false,'msg'=>'Liquidación no encontrada o ya cerrada']); exit; }

    $fecha_liq = $liq['fecha'];

    // Calcular totales del día
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

    // efectivo_esperado = lo que debería tener el cobrador en mano
    // = base que llevó + lo que cobró - lo que prestó - gastos
    $efectivo_esperado = ($base_trabajado + $total_pagos) - $total_prestamos - $total_gastos;

    // diferencia = lo esperado vs lo que entregó (positivo = debe, negativo = entregó de más)
    $diferencia = $efectivo_esperado - $dinero_entregado;

    // nueva_base = la caja antes de entregar la base + lo que devolvió el cobrador
    // = (base - base_trabajado) ya fue descontado al iniciar, ahora se suma lo entregado
    // Como el movimiento de salida ya bajó la base, la nueva_base solo suma dinero_entregado
    // La base actual ya bajó con el movimiento de iniciar, así que:
    // nueva_base = base - base_trabajado + dinero_entregado (para mostrar en UI)
    $nueva_base = ($base - $base_trabajado) + $dinero_entregado;

    $db->beginTransaction();
    try {
        // Actualizar % de papelería por préstamo si se modificaron
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

        // Total papelería del día
        $stmtPap = $db->prepare("
            SELECT COALESCE(SUM(monto_papeleria), 0) FROM papeleria
            WHERE cobro_id=? AND fecha=?
        ");
        $stmtPap->execute([$cobro, $fecha_liq]);
        $total_papeleria = (float)$stmtPap->fetchColumn();

        // Vincular papelería a esta liquidación
        $db->prepare("UPDATE papeleria SET liquidacion_id=? WHERE cobro_id=? AND fecha=?")
           ->execute([$liquidacion_id, $cobro, $fecha_liq]);

        // Cerrar la liquidación — cobrador_bloqueado=1 automáticamente
        $db->prepare("UPDATE liquidaciones SET
            total_pagos=?, total_prestamos=?, total_gastos_aprobados=?,
            total_papeleria=?, papeleria_entregada=?,
            efectivo_esperado=?, dinero_entregado=?, diferencia=?,
            nueva_base=?, notas=?, estado='cerrada', cobrador_bloqueado=1,
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

        // Registrar entrada de caja = dinero que devuelve el cobrador
        // origen='liquidacion' → sí afecta la base general
        if ($dinero_entregado > 0) {
            $db->prepare("INSERT INTO capital_movimientos
                (cobro_id, tipo, origen, es_entrada, monto, metodo_pago,
                 descripcion, fecha, usuario_id)
                VALUES (?, 'cobro_cuota', 'liquidacion', 1, ?, 'efectivo',
                 ?, ?, ?)")
            ->execute([
                $cobro,
                $dinero_entregado,
                "Cobrador entregó efectivo — liquidación #{$liquidacion_id} del " . date('d/m/Y', strtotime($fecha_liq)),
                $fecha_liq,
                $_SESSION['usuario_id']
            ]);
        }

        // Cerrar sesión del cobrador al liquidar —
        // borra el session_token → próximo request lo saca del sistema
        // El cobrador no puede volver a entrar hasta que el admin abra la liquidación del día siguiente
        $stmtCob = $db->prepare("
            SELECT u.id FROM usuarios u
            JOIN usuario_cobro uc ON uc.usuario_id = u.id
            WHERE uc.cobro_id=? AND u.rol='cobrador' LIMIT 1
        ");
        $stmtCob->execute([$cobro]);
        $cobId = $stmtCob->fetchColumn();
        if ($cobId) {
            $db->prepare("UPDATE usuarios SET session_token=NULL, updated_at=NOW() WHERE id=?")
               ->execute([$cobId]);
        }

        $db->commit();

        $msg = 'Liquidación cerrada. Nueva base: ' . fmt($nueva_base);
        if (abs($diferencia) > 1) {
            $msg .= $diferencia > 0
                ? ' · ⚠ Cobrador debe ' . fmt(abs($diferencia))
                : ' · Cobrador entregó ' . fmt(abs($diferencia)) . ' de más';
        }
        if ($total_papeleria > 0) {
            $msg .= ' · Papelería: ' . fmt($total_papeleria);
        }

        echo json_encode(['ok'=>true,'msg'=>$msg]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
    }

// ============================================================
// BLOQUEAR / DESBLOQUEAR COBRADOR
// ============================================================
} elseif ($action === 'bloquear_cobrador') {
    $liquidacion_id = (int)($data['liquidacion_id'] ?? 0);
    if (!$liquidacion_id) { echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

    // Leer estado actual para hacer toggle
    $stmtL = $db->prepare("SELECT * FROM liquidaciones WHERE id=? AND cobro_id=? AND estado='borrador'");
    $stmtL->execute([$liquidacion_id, $cobro]);
    $liq = $stmtL->fetch();
    if (!$liq) { echo json_encode(['ok'=>false,'msg'=>'Liquidación no encontrada o ya cerrada']); exit; }

    // Toggle: si estaba bloqueado lo desbloquea, si estaba libre lo bloquea
    $nuevo_estado = $liq['cobrador_bloqueado'] ? 0 : 1;

    try {
        // 1. Primero guardar en BD
        $db->prepare("UPDATE liquidaciones SET cobrador_bloqueado=? WHERE id=?")
           ->execute([$nuevo_estado, $liquidacion_id]);

        // 2. Si bloquea → destruir session_token del cobrador
        if ($nuevo_estado === 1) {
            $stmtCob = $db->prepare("
                SELECT u.id FROM usuarios u
                JOIN usuario_cobro uc ON uc.usuario_id = u.id
                WHERE uc.cobro_id=? AND u.rol='cobrador' LIMIT 1
            ");
            $stmtCob->execute([$cobro]);
            $cobId = $stmtCob->fetchColumn();
            if ($cobId) {
                $db->prepare("UPDATE usuarios SET session_token=NULL WHERE id=?")
                   ->execute([$cobId]);
            }
        }

        echo json_encode([
            'ok'        => true,
            'msg'       => $nuevo_estado === 1 ? 'Cobrador bloqueado. Su sesión fue cerrada.' : 'Cobrador desbloqueado.',
            'bloqueado' => $nuevo_estado
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
    }

// ============================================================
// ENTREGAR BASE ADICIONAL
// ============================================================
} elseif ($action === 'entregar_base_adicional') {
    $liquidacion_id = (int)($data['liquidacion_id'] ?? 0);
    $monto          = (float)($data['monto'] ?? 0);

    if (!$liquidacion_id || $monto <= 0) {
        echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit;
    }

    $stmtL = $db->prepare("SELECT * FROM liquidaciones WHERE id=? AND cobro_id=? AND estado='borrador'");
    $stmtL->execute([$liquidacion_id, $cobro]);
    $liq = $stmtL->fetch();
    if (!$liq) { echo json_encode(['ok'=>false,'msg'=>'Liquidación no encontrada o ya cerrada']); exit; }

    // Validar saldo de caja suficiente
    $saldo = getSaldoCaja($db, $cobro);
    if ($saldo < $monto) {
        echo json_encode([
            'ok'  => false,
            'msg' => 'Saldo insuficiente en caja. Disponible: ' . fmt($saldo) . ' · Requerido: ' . fmt($monto)
        ]); exit;
    }

    $db->beginTransaction();
    try {
        // Sumar a base_trabajado
        $nuevo_base_trabajado = (float)$liq['base_trabajado'] + $monto;
        $db->prepare("UPDATE liquidaciones SET base_trabajado=? WHERE id=?")
           ->execute([$nuevo_base_trabajado, $liquidacion_id]);

        // Registrar salida de caja
        $db->prepare("INSERT INTO capital_movimientos
            (cobro_id, tipo, origen, es_entrada, monto, metodo_pago, descripcion, fecha, usuario_id)
            VALUES (?, 'salida', 'liquidacion', 0, ?, 'efectivo', ?, CURDATE(), ?)")
        ->execute([
            $cobro, $monto,
            "Entrega adicional de base — liquidación #{$liquidacion_id}",
            $_SESSION['usuario_id']
        ]);

        $db->commit();
        echo json_encode([
            'ok'  => true,
            'msg' => 'Base adicional entregada: ' . fmt($monto) . '. Nueva base trabajado: ' . fmt($nuevo_base_trabajado)
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
    }

// ============================================================
// PREVIEW DEL DÍA (para validar fechas antes de iniciar)
// ============================================================
} elseif ($action === 'preview_dia') {
    $fecha = $data['fecha'] ?? $hoy;
    if (!$fecha || !strtotime($fecha)) {
        echo json_encode(['ok'=>false,'msg'=>'Fecha inválida']); exit;
    }

    $stmtPagos = $db->prepare("
        SELECT COALESCE(SUM(monto_pagado), 0) FROM pagos
        WHERE cobro_id=? AND fecha_pago=? AND (anulado=0 OR anulado IS NULL)
    ");
    $stmtPagos->execute([$cobro, $fecha]);
    $total_pagos = (float)$stmtPagos->fetchColumn();

    $stmtPrest = $db->prepare("
        SELECT COALESCE(SUM(monto_prestado), 0) FROM prestamos
        WHERE cobro_id=? AND DATE(created_at)=? AND estado != 'anulado'
    ");
    $stmtPrest->execute([$cobro, $fecha]);
    $total_prestamos = (float)$stmtPrest->fetchColumn();

    $stmtGastos = $db->prepare("
        SELECT COALESCE(SUM(monto), 0) FROM gastos_cobrador
        WHERE cobro_id=? AND fecha=? AND estado='aprobado'
    ");
    $stmtGastos->execute([$cobro, $fecha]);
    $total_gastos = (float)$stmtGastos->fetchColumn();

    $stmtPap = $db->prepare("
        SELECT COALESCE(SUM(monto_papeleria), 0) FROM papeleria
        WHERE cobro_id=? AND fecha=?
    ");
    $stmtPap->execute([$cobro, $fecha]);
    $total_papeleria = (float)$stmtPap->fetchColumn();

    echo json_encode([
        'ok'              => true,
        'total_pagos'     => $total_pagos,
        'total_prestamos' => $total_prestamos,
        'total_gastos'    => $total_gastos,
        'total_papeleria' => $total_papeleria,
    ]);

// ============================================================
// FECHAS USADAS
// ============================================================
} elseif ($action === 'fechas_usadas') {
    $stmt = $db->prepare("SELECT fecha FROM liquidaciones WHERE cobro_id=? ORDER BY fecha DESC");
    $stmt->execute([$cobro]);
    echo json_encode(['ok'=>true,'fechas'=>array_column($stmt->fetchAll(),'fecha')]);

// ============================================================
// LISTAR LIQUIDACIONES
// ============================================================
} elseif ($action === 'lista') {
    $estado    = $data['estado']    ?? '';
    $fecha_ini = $data['fecha_ini'] ?? '';
    $fecha_fin = $data['fecha_fin'] ?? '';

    $where  = "WHERE l.cobro_id = ?";
    $params = [$cobro];

    if ($estado)    { $where .= " AND l.estado = ?";     $params[] = $estado; }
    if ($fecha_ini) { $where .= " AND l.fecha >= ?";     $params[] = $fecha_ini; }
    if ($fecha_fin) { $where .= " AND l.fecha <= ?";     $params[] = $fecha_fin; }

    $stmt = $db->prepare("
        SELECT l.*, u.nombre AS usuario_nombre
        FROM liquidaciones l
        LEFT JOIN usuarios u ON u.id = l.usuario_id
        $where
        ORDER BY l.fecha DESC
        LIMIT 50
    ");
    $stmt->execute($params);
    echo json_encode(['ok'=>true,'liquidaciones'=>$stmt->fetchAll()]);

} else {
    echo json_encode(['ok'=>false,'msg'=>'Acción no reconocida']);
}