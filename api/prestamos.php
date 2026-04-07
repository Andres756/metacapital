<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok'=>false,'msg'=>'Método no permitido']); exit;
}

$data  = json_decode(file_get_contents('php://input'), true) ?? [];
$db    = getDB();
$cobro = cobroActivo();
$action= $data['action'] ?? '';

// ============================================================
// CREAR PRÉSTAMO
// ============================================================
if ($action === 'crear') {
    if (!canDo('puede_crear_prestamo')) { echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit; }

    $deudor_id   = (int)($data['deudor_id'] ?? 0);
    $monto       = (float)($data['monto_prestado'] ?? 0);
    $tipo_int    = in_array($data['tipo_interes']??'', ['porcentaje','valor_fijo']) ? $data['tipo_interes'] : 'porcentaje';
    $interes_val = (float)($data['interes_valor'] ?? 0);
    $fecha_ini   = $data['fecha_inicio'] ?? date('Y-m-d');
    $frecuencia  = in_array($data['frecuencia_pago']??'', ['diario','semanal','quincenal','mensual']) ? $data['frecuencia_pago'] : 'mensual';
    $num_cuotas  = max(1, (int)($data['num_cuotas'] ?? 1));

    if (!$deudor_id || $monto <= 0) {
        echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit;
    }

    // Cuenta obligatoria
    $cuenta_desembolso_id = (int)($data['cuenta_desembolso_id'] ?? 0);
    if (!$cuenta_desembolso_id) {
        echo json_encode(['ok'=>false,'msg'=>'Debes seleccionar una cuenta de desembolso']); exit;
    }

    // Verificar saldo suficiente en la cuenta física
    validarSaldoCuenta($db, $cuenta_desembolso_id, $cobro, $monto);

    // El préstamo se valida contra la CAJA (capital global)
    // El capitalista es solo referencia de origen, no limita el monto



    // Calcular interés y total
    $interes_calc = $tipo_int === 'porcentaje'
        ? $monto * ($interes_val / 100)
        : (float)$interes_val;
    $total = $monto + $interes_calc;

    // Valor cuota: si el usuario la sobreescribió, recalcular cuotas
    if (!empty($data['valor_cuota_override']) && (float)$data['valor_cuota_override'] > 0) {
        $valor_cuota = (float)$data['valor_cuota_override'];
        $num_cuotas  = max(1, (int)ceil($total / $valor_cuota));
    } else {
        $valor_cuota = round($total / $num_cuotas, 2);
    }

    // Fecha fin
    $diasMap = ['diario'=>1,'semanal'=>7,'quincenal'=>15,'mensual'=>30];
    $dias    = $diasMap[$frecuencia] ?? 30;
    $fechaFin= (new DateTime($fecha_ini))->modify("+".($dias * $num_cuotas)." days")->format('Y-m-d');

    $db->beginTransaction();
    try {
        // Insertar préstamo
        $db->prepare("
            INSERT INTO prestamos
              (cobro_id, deudor_id, capitalista_id, monto_prestado, tipo_interes, interes_valor,
               interes_calculado, total_a_pagar, frecuencia_pago, num_cuotas, valor_cuota,
               fecha_inicio, fecha_fin_esperada, cuenta_desembolso_id, saldo_pendiente,
               tipo_origen, observaciones, omitir_domingos, usuario_id)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ")->execute([
            $cobro, $deudor_id,
            ($data['capitalista_id'] ?: null),
            $monto, $tipo_int, $interes_val, $interes_calc, $total,
            $frecuencia, $num_cuotas, $valor_cuota,
            $fecha_ini, $fechaFin,
            ($data['cuenta_desembolso_id'] ?: null),
            $total,
            $data['tipo_origen'] ?? 'nuevo',
            $data['observaciones'] ?? null,
            !empty($data['omitir_domingos']) ? 1 : 0,
            $_SESSION['usuario_id']
        ]);
        $prestamo_id = $db->lastInsertId();
        $omitir_domingos = !empty($data['omitir_domingos']);

        // Generar cuotas
        generarCuotas($db, $prestamo_id, $cobro, $fecha_ini, $frecuencia, $num_cuotas, $valor_cuota, $total, $omitir_domingos);

        // Movimiento: prestamo (salida de cuenta)
        $db->prepare("INSERT INTO capital_movimientos
                (cobro_id, tipo, es_entrada, monto, cuenta_id, capitalista_id, prestamo_id, descripcion, fecha, usuario_id)
                VALUES (?, 'prestamo', 0, ?, ?, ?, ?, ?, ?, ?)")->execute([
            $cobro, $monto, $cuenta_desembolso_id,
            ($data['capitalista_id'] ?: null),
            $prestamo_id,
            "Préstamo #$prestamo_id a deudor #$deudor_id",
            $fecha_ini, $_SESSION['usuario_id']
        ]);

        // Descuento informativo por capitalista:
        // 1. Propios: se gastan primero, por orden de mayor saldo disponible
        // 2. Prestados: proporcional a su aporte histórico, solo si quedan fondos
        $capsQ = $db->prepare("
            SELECT c.id, c.tipo,
                COALESCE(SUM(CASE WHEN m.es_entrada=1 AND m.tipo!='prestamo_proporcional' THEN m.monto ELSE 0 END),0) AS total_aporte,
                COALESCE(SUM(CASE WHEN m.es_entrada=1 THEN m.monto ELSE -m.monto END),0) AS saldo_actual
            FROM capitalistas c
            LEFT JOIN capital_movimientos m ON m.capitalista_id=c.id AND m.cobro_id=c.cobro_id
            WHERE c.cobro_id=? AND c.estado='activo'
            GROUP BY c.id, c.tipo
            HAVING saldo_actual > 0
            ORDER BY FIELD(c.tipo,'propio','prestado'), saldo_actual DESC
        ");
        $capsQ->execute([$cobro]);
        $capsData   = $capsQ->fetchAll();
        $propios    = array_filter($capsData, fn($c) => $c['tipo'] === 'propio');
        $prestados  = array_filter($capsData, fn($c) => $c['tipo'] === 'prestado');

        $montoRestante = (float)$monto;

        // 1. Agotar propios primero (mayor saldo primero)
        foreach ($propios as $cap) {
            if ($montoRestante <= 0) break;
            $descuento = min($montoRestante, (float)$cap['saldo_actual']);
            $montoRestante -= $descuento;
            if ($descuento <= 0) continue;
            $db->prepare("INSERT INTO capital_movimientos
                (cobro_id, tipo, es_entrada, monto, cuenta_id, capitalista_id, prestamo_id, descripcion, fecha, usuario_id)
                VALUES (?, 'prestamo_proporcional', 0, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([
                $cobro, round($descuento), $cuenta_desembolso_id,
                $cap['id'], $prestamo_id,
                "Préstamo #$prestamo_id — descuento capital propio",
                $fecha_ini, $_SESSION['usuario_id']
            ]);
        }

        // 2. Si queda monto, repartir entre prestados proporcional a aporte
        if ($montoRestante > 0 && count($prestados) > 0) {
            $totalAportesPrest = array_sum(array_column(array_values($prestados), 'total_aporte'));
            $lastPrest = array_values($prestados);
            $lastIdx   = count($lastPrest) - 1;
            foreach ($lastPrest as $idx => $cap) {
                if ($montoRestante <= 0) break;
                $pct       = $totalAportesPrest > 0 ? $cap['total_aporte'] / $totalAportesPrest : 1;
                $descuento = ($idx === $lastIdx) ? $montoRestante : round($montoRestante * $pct);
                $descuento = min($descuento, (float)$cap['saldo_actual']);
                $montoRestante -= $descuento;
                if ($descuento <= 0) continue;
                $pctLabel  = round($pct * 100);
                $db->prepare("INSERT INTO capital_movimientos
                    (cobro_id, tipo, es_entrada, monto, cuenta_id, capitalista_id, prestamo_id, descripcion, fecha, usuario_id)
                    VALUES (?, 'prestamo_proporcional', 0, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([
                    $cobro, round($descuento), $cuenta_desembolso_id,
                    $cap['id'], $prestamo_id,
                    "Préstamo #$prestamo_id — descuento capital prestado ({$pctLabel}%)",
                    $fecha_ini, $_SESSION['usuario_id']
                ]);
            }
        }

        $db->commit();
        echo json_encode(['ok'=>true,'msg'=>'Préstamo registrado','id'=>$prestamo_id]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['ok'=>false,'msg'=>'Error al guardar: '.$e->getMessage()]);
    }

// ============================================================
// REGISTRAR PAGO
// ============================================================
} elseif ($action === 'pagar') {
    if (!canDo('puede_registrar_pago')) { echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit; }

    $prestamo_id = (int)($data['prestamo_id'] ?? 0);
    $cuota_id    = (int)($data['cuota_id']    ?? 0);
    $monto       = (float)($data['monto_pagado'] ?? 0);
    $fecha_pago  = $data['fecha_pago'] ?? date('Y-m-d');

    if (!$prestamo_id || !$cuota_id || $monto <= 0) {
        echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit;
    }
    $cuenta_id_pago = (int)($data['cuenta_id'] ?? 0);
    if (!$cuenta_id_pago) {
        echo json_encode(['ok'=>false,'msg'=>'Selecciona la cuenta donde entró el dinero']); exit;
    }

    // Verificar préstamo y cuota
    $stmtP = $db->prepare("SELECT * FROM prestamos WHERE id=? AND cobro_id=?");
    $stmtP->execute([$prestamo_id, $cobro]);
    $prestamo = $stmtP->fetch();
    if (!$prestamo) { echo json_encode(['ok'=>false,'msg'=>'Préstamo no encontrado']); exit; }

    $stmtC = $db->prepare("SELECT * FROM cuotas WHERE id=? AND prestamo_id=?");
    $stmtC->execute([$cuota_id, $prestamo_id]);
    $cuota = $stmtC->fetch();
    if (!$cuota) { echo json_encode(['ok'=>false,'msg'=>'Cuota no encontrada']); exit; }

    $db->beginTransaction();
    try {
        $excedente = 0;
        $msg_extra = '';
        $monto_aplicar = $monto;

        // ── Aplicar pago a cuota actual ──────────────────────
        $monto_pagado_nuevo = $cuota['monto_pagado'] + $monto_aplicar;
        $saldo_cuota_nuevo  = $cuota['monto_cuota'] - $monto_pagado_nuevo;

        if ($saldo_cuota_nuevo < 0) {
            // Hay excedente
            $excedente         = abs($saldo_cuota_nuevo);
            $monto_aplicar     = $cuota['monto_cuota'] - $cuota['monto_pagado']; // solo lo que faltaba
            $monto_pagado_nuevo = $cuota['monto_cuota'];
            $saldo_cuota_nuevo  = 0;
        }

        $estado_cuota = $saldo_cuota_nuevo <= 0 ? 'pagado' : 'parcial';

        $db->prepare("UPDATE cuotas SET monto_pagado=?, saldo_cuota=?, estado=?, fecha_pago=?, updated_at=NOW() WHERE id=?")
           ->execute([$monto_pagado_nuevo, $saldo_cuota_nuevo, $estado_cuota, $fecha_pago, $cuota_id]);

        // Registrar pago cuota actual
        $db->prepare("INSERT INTO pagos (cobro_id,prestamo_id,cuota_id,deudor_id,monto_pagado,fecha_pago,cuenta_id,metodo_pago,es_parcial,observacion,usuario_id)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)")->execute([
            $cobro, $prestamo_id, $cuota_id, $prestamo['deudor_id'],
            $monto_aplicar, $fecha_pago,
            ($data['cuenta_id'] ?: null),
            $data['metodo_pago'] ?? 'efectivo',
            0,
            $data['observacion'] ?? null,
            $_SESSION['usuario_id']
        ]);
        // Capturar ID del pago principal ANTES de los inserts de excedente
        $pago_id_principal = $db->lastInsertId();

        // ── Aplicar excedente en cascada a cuotas siguientes ──────────────
        if ($excedente > 0) {
            $saldoExcedente = $excedente;
            $cuotasAplicadas = [];

            // Traer todas las cuotas pendientes siguientes ordenadas
            $sigQ = $db->prepare("
                SELECT * FROM cuotas
                WHERE prestamo_id=? AND estado IN ('pendiente','parcial','vencido')
                AND id != ?
                ORDER BY numero_cuota ASC
            ");
            $sigQ->execute([$prestamo_id, $cuota_id]);
            $cuotasSig = $sigQ->fetchAll();

            foreach ($cuotasSig as $sc) {
                if ($saldoExcedente <= 0) break;

                $saldoPendienteCuota = $sc['saldo_cuota'];
                $aplicar = min($saldoExcedente, $saldoPendienteCuota);
                $saldoExcedente -= $aplicar;

                $mp2  = $sc['monto_pagado'] + $aplicar;
                $sc2  = max(0, $sc['saldo_cuota'] - $aplicar);
                $est2 = $sc2 <= 0 ? 'pagado' : 'parcial';

                $db->prepare("UPDATE cuotas SET monto_pagado=?, saldo_cuota=?, estado=?, fecha_pago=?, updated_at=NOW() WHERE id=?")
                   ->execute([$mp2, $sc2, $est2, ($sc2<=0 ? $fecha_pago : null), $sc['id']]);

                $db->prepare("INSERT INTO pagos (cobro_id,prestamo_id,cuota_id,deudor_id,monto_pagado,fecha_pago,cuenta_id,metodo_pago,es_parcial,observacion,usuario_id)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?)")->execute([
                    $cobro, $prestamo_id, $sc['id'], $prestamo['deudor_id'],
                    $aplicar, $fecha_pago,
                    ($data['cuenta_id'] ?: null),
                    $data['metodo_pago'] ?? 'efectivo',
                    $sc2 > 0 ? 1 : 0,
                    'Excedente de cuota #'.$cuota['numero_cuota'],
                    $_SESSION['usuario_id']
                ]);

                $cuotasAplicadas[] = $sc['numero_cuota'];
            }

            if (!empty($cuotasAplicadas)) {
                $msg_extra = " Excedente aplicado a cuota(s) #".implode(', #', $cuotasAplicadas).".";
            }

            // Si aún queda excedente → saldo a favor del deudor
            if ($saldoExcedente > 0) {
                $db->prepare("UPDATE deudores SET saldo_favor = saldo_favor + ? WHERE id=?")
                   ->execute([$saldoExcedente, $prestamo['deudor_id']]);
                $msg_extra .= " $".number_format($saldoExcedente,0,',','.')." guardado como saldo a favor.";
            }
        }

        // ── Actualizar saldo del préstamo ────────────────────
        $nuevo_saldo = max(0, $prestamo['saldo_pendiente'] - $monto);
        $nuevo_estado = $nuevo_saldo <= 0 ? 'pagado' : actualizarEstadoMora($db, $prestamo_id);
        $db->prepare("UPDATE prestamos SET saldo_pendiente=?, estado=?, updated_at=NOW() WHERE id=?")
           ->execute([$nuevo_saldo, $nuevo_estado, $prestamo_id]);

        // ── Movimiento en capital (general) ─────────────────
        if (!empty($data['cuenta_id'])) {
            $db->prepare("INSERT INTO capital_movimientos
                    (cobro_id, tipo, es_entrada, monto, cuenta_id, capitalista_id, prestamo_id, pago_id, descripcion, fecha, usuario_id)
                    VALUES (?, 'cobro_cuota', 1, ?, ?, ?, ?, ?, ?, ?, ?)")->execute([
                $cobro, $monto, (int)$data['cuenta_id'],
                ($prestamo['capitalista_id'] ?: null),
                $prestamo_id,
                $pago_id_principal,
                "Cobro cuota préstamo #$prestamo_id",
                $fecha_pago, $_SESSION['usuario_id']
            ]);
        }

        // ── Retorno proporcional a capitalistas ──────────────
        // Buscar cómo se distribuyó el capital de este préstamo entre capitalistas
        $propQ = $db->prepare("
            SELECT capitalista_id, monto
            FROM capital_movimientos
            WHERE prestamo_id=? AND tipo='prestamo_proporcional' AND anulado=0 AND capitalista_id IS NOT NULL
        ");
        $propQ->execute([$prestamo_id]);
        $proporcionales = $propQ->fetchAll();

        if (!empty($proporcionales)) {
            // Total que salió en proporcionales para calcular porcentajes
            $totalProp = array_sum(array_column($proporcionales, 'monto'));

            if ($totalProp > 0) {
                $montoRetorno  = $monto; // El pago completo regresa proporcionalmente
                $acumulado     = 0;
                $lastIdx       = count($proporcionales) - 1;

                foreach ($proporcionales as $idx => $prop) {
                    $pct        = $prop['monto'] / $totalProp;
                    // Al último se le da el resto para evitar diferencias de redondeo
                    $retorno    = ($idx === $lastIdx)
                        ? ($montoRetorno - $acumulado)
                        : round($montoRetorno * $pct);
                    if ($retorno <= 0) continue;
                    $acumulado += $retorno;
                    $pctLabel   = round($pct * 100, 1);

                    $db->prepare("INSERT INTO capital_movimientos
                        (cobro_id, tipo, es_entrada, monto, cuenta_id, capitalista_id, prestamo_id, pago_id, descripcion, fecha, usuario_id)
                        VALUES (?, 'cobro_proporcional', 1, ?, ?, ?, ?, ?, ?, ?, ?)"
                    )->execute([
                        $cobro,
                        $retorno,
                        (int)($data['cuenta_id'] ?? 0),
                        $prop['capitalista_id'],
                        $prestamo_id,
                        $pago_id_principal,
                        "Retorno préstamo #$prestamo_id — {$pctLabel}% capital",
                        $fecha_pago,
                        $_SESSION['usuario_id']
                    ]);
                }
            }
        }

        $db->commit();
        echo json_encode(['ok'=>true,'msg'=>'Pago registrado correctamente.'.$msg_extra]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
    }

// ============================================================
// RENOVAR
// ============================================================
} elseif ($action === 'renovar') {
    if (!canDo('puede_editar_prestamo')) { echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit; }

    $prestamo_id    = (int)($data['prestamo_id'] ?? 0);
    $monto_int      = (float)($data['monto_intereses'] ?? 0);
    $nuevo_interes  = (float)($data['nuevo_interes'] ?? 20);
    $nuevas_cuotas  = max(1,(int)($data['nuevas_cuotas'] ?? 1));

    $stmtP = $db->prepare("SELECT * FROM prestamos WHERE id=? AND cobro_id=?");
    $stmtP->execute([$prestamo_id, $cobro]);
    $prestamo = $stmtP->fetch();
    if (!$prestamo) { echo json_encode(['ok'=>false,'msg'=>'Préstamo no encontrado']); exit; }

    $db->beginTransaction();
    try {
        $cuenta_renovar = (int)($data['cuenta_id_renovar'] ?? $prestamo['cuenta_desembolso_id']);

        // 1. Registrar pago de intereses → entra dinero a la cuenta
        if ($monto_int > 0 && $cuenta_renovar) {
            // En tabla pagos (para historial del préstamo)
            $stmtCuota = $db->prepare("SELECT id FROM cuotas WHERE prestamo_id=? AND estado!='pagado' ORDER BY numero_cuota LIMIT 1");
            $stmtCuota->execute([$prestamo_id]);
            $cuota_id_ren = $stmtCuota->fetchColumn();
            if ($cuota_id_ren) {
                $db->prepare("INSERT INTO pagos (cobro_id,prestamo_id,cuota_id,deudor_id,monto_pagado,fecha_pago,cuenta_id,metodo_pago,observacion,usuario_id)
                    VALUES (?,?,?,?,?,CURDATE(),?,'efectivo','Renovación - pago de intereses',?)")
                   ->execute([$cobro,$prestamo_id,$cuota_id_ren,$prestamo['deudor_id'],$monto_int,$cuenta_renovar,$_SESSION['usuario_id']]);
            }
            // En capital_movimientos (para cuadre de caja y capital)
            $db->prepare("INSERT INTO capital_movimientos
                (cobro_id, tipo, es_entrada, monto, cuenta_id, capitalista_id, prestamo_id, descripcion, fecha, usuario_id)
                VALUES (?, 'cobro_cuota', 1, ?, ?, ?, ?, ?, CURDATE(), ?)")
               ->execute([
                   $cobro, $monto_int, $cuenta_renovar,
                   $prestamo['capitalista_id'] ?: null,
                   $prestamo_id,
                   "Intereses renovación préstamo #$prestamo_id",
                   $_SESSION['usuario_id']
               ]);
        }

        // 2. Cerrar préstamo actual
        $db->prepare("UPDATE prestamos SET estado='renovado', saldo_pendiente=0, updated_at=NOW() WHERE id=?")->execute([$prestamo_id]);
        $db->prepare("UPDATE cuotas SET estado='pagado', updated_at=NOW() WHERE prestamo_id=? AND estado!='pagado'")->execute([$prestamo_id]);

        // 3. Nuevo capital = saldo_pendiente - intereses que se pagan ahora
        //    Lógica: el cliente pagó cuotas (reduciendo saldo) + ahora paga el interés
        //    lo que queda es solo capital puro a renovar
        $nuevo_capital_base = max(0, $prestamo['saldo_pendiente'] - $monto_int);
        if ($nuevo_capital_base <= 0) {
            // Si el interés cubre todo el saldo, el préstamo queda saldado
            $db->prepare("UPDATE prestamos SET estado='pagado', saldo_pendiente=0, updated_at=NOW() WHERE id=?")->execute([$prestamo_id]);
            $db->commit();
            echo json_encode(['ok'=>true,'msg'=>'Préstamo saldado completamente con el pago de intereses']);
            exit;
        }

        $interes_calc_nuevo = $nuevo_capital_base * ($nuevo_interes / 100);
        $total_nuevo        = $nuevo_capital_base + $interes_calc_nuevo;
        $valor_cuota_nuevo  = round($total_nuevo / $nuevas_cuotas, 2);
        $dias    = ['diario'=>1,'semanal'=>7,'quincenal'=>15,'mensual'=>30][$prestamo['frecuencia_pago']] ?? 30;
        $fechaFin= (new DateTime())->modify("+".($dias*$nuevas_cuotas)." days")->format('Y-m-d');

        $db->prepare("INSERT INTO prestamos
            (cobro_id,deudor_id,capitalista_id,monto_prestado,tipo_interes,interes_valor,interes_calculado,
             total_a_pagar,frecuencia_pago,num_cuotas,valor_cuota,fecha_inicio,fecha_fin_esperada,
             cuenta_desembolso_id,saldo_pendiente,tipo_origen,prestamo_padre_id,usuario_id)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,CURDATE(),?,?,?,?,?,?)")->execute([
            $cobro, $prestamo['deudor_id'], $prestamo['capitalista_id'],
            $nuevo_capital_base, $prestamo['tipo_interes'], $nuevo_interes,
            $interes_calc_nuevo, $total_nuevo, $prestamo['frecuencia_pago'],
            $nuevas_cuotas, $valor_cuota_nuevo, $fechaFin,
            $prestamo['cuenta_desembolso_id'], $total_nuevo, 'renovacion', $prestamo_id,
            $_SESSION['usuario_id']
        ]);
        $nuevo_id = $db->lastInsertId();
        generarCuotas($db, $nuevo_id, $cobro, date('Y-m-d'), $prestamo['frecuencia_pago'], $nuevas_cuotas, $valor_cuota_nuevo, $total_nuevo);

        // 4. El nuevo préstamo NO genera prestamo_salida en capital_movimientos
        //    porque el capital nunca salió físicamente — simplemente se renovó
        // (el dinero ya estaba en la calle, solo se renueva el acuerdo)

        registrarGestion($db, $cobro, $prestamo_id, $prestamo['deudor_id'], 'nota', 'Préstamo renovado. Nuevo: #'.$nuevo_id, $data['nota_gestion']??null);

        $db->commit();
        echo json_encode(['ok'=>true,'msg'=>'Préstamo renovado. Nuevo #'.$nuevo_id,'nuevo_id'=>$nuevo_id]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
    }

// ============================================================
// REFINANCIAR
// ============================================================
} elseif ($action === 'refinanciar') {
    if (!canDo('puede_editar_prestamo')) { echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit; }

    $prestamo_id   = (int)($data['prestamo_id']    ?? 0);
    $nuevo_capital = (float)($data['nuevo_capital'] ?? 0);
    $nuevo_interes = (float)($data['nuevo_interes_r']  ?? 20);
    $abono         = (float)($data['abono_cliente'] ?? 0);
    $nuevas_cuotas = max(1,(int)($data['nuevas_cuotas_r'] ?? 1));

    $stmtP = $db->prepare("SELECT * FROM prestamos WHERE id=? AND cobro_id=?");
    $stmtP->execute([$prestamo_id, $cobro]);
    $prestamo = $stmtP->fetch();
    if (!$prestamo) { echo json_encode(['ok'=>false,'msg'=>'Préstamo no encontrado']); exit; }

    $base = max(0, $nuevo_capital - $abono);
    if ($base <= 0) { echo json_encode(['ok'=>false,'msg'=>'El capital base debe ser mayor a 0']); exit; }

    $db->beginTransaction();
    try {
        // Registrar abono si lo hay
        if ($abono > 0 && !empty($data['cuenta_id_refin'])) {
            $cuenta_refin = (int)$data['cuenta_id_refin'];
            $stmtCuota = $db->prepare("SELECT id FROM cuotas WHERE prestamo_id=? AND estado!='pagado' ORDER BY numero_cuota LIMIT 1");
            $stmtCuota->execute([$prestamo_id]);
            $cuota_id = $stmtCuota->fetchColumn();
            if ($cuota_id) {
                $db->prepare("INSERT INTO pagos (cobro_id,prestamo_id,cuota_id,deudor_id,monto_pagado,fecha_pago,cuenta_id,metodo_pago,observacion,usuario_id)
                    VALUES (?,?,?,?,?,CURDATE(),?,'efectivo','Refinanciación - abono parcial',?)")
                   ->execute([$cobro,$prestamo_id,$cuota_id,$prestamo['deudor_id'],$abono,$cuenta_refin,$_SESSION['usuario_id']]);
            }
            // Registrar en capital_movimientos (entra dinero a la cuenta)
            $db->prepare("INSERT INTO capital_movimientos
                (cobro_id, tipo, es_entrada, monto, cuenta_id, capitalista_id, prestamo_id, descripcion, fecha, usuario_id)
                VALUES (?, 'cobro_cuota', 1, ?, ?, ?, ?, ?, CURDATE(), ?)")
               ->execute([
                   $cobro, $abono, $cuenta_refin,
                   $prestamo['capitalista_id'] ?: null,
                   $prestamo_id,
                   "Abono refinanciación préstamo #$prestamo_id",
                   $_SESSION['usuario_id']
               ]);
        }

        // Cerrar préstamo actual
        $db->prepare("UPDATE prestamos SET estado='refinanciado', updated_at=NOW() WHERE id=?")->execute([$prestamo_id]);
        $db->prepare("UPDATE cuotas SET estado='pagado', updated_at=NOW() WHERE prestamo_id=? AND estado!='pagado'")->execute([$prestamo_id]);

        // Nuevo préstamo con capital refinanciado
        $interes_calc = $base * ($nuevo_interes / 100);
        $total_nuevo  = $base + $interes_calc;
        $valor_cuota  = round($total_nuevo / $nuevas_cuotas, 2);
        $dias    = ['diario'=>1,'semanal'=>7,'quincenal'=>15,'mensual'=>30][$prestamo['frecuencia_pago']] ?? 30;
        $fechaFin= (new DateTime())->modify("+".($dias*$nuevas_cuotas)." days")->format('Y-m-d');

        $db->prepare("INSERT INTO prestamos
            (cobro_id,deudor_id,capitalista_id,monto_prestado,tipo_interes,interes_valor,interes_calculado,
             total_a_pagar,frecuencia_pago,num_cuotas,valor_cuota,fecha_inicio,fecha_fin_esperada,
             cuenta_desembolso_id,saldo_pendiente,tipo_origen,prestamo_padre_id,usuario_id)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,CURDATE(),?,?,?,?,?,?)")->execute([
            $cobro, $prestamo['deudor_id'], $prestamo['capitalista_id'],
            $base, $prestamo['tipo_interes'], $nuevo_interes,
            $interes_calc, $total_nuevo, $prestamo['frecuencia_pago'],
            $nuevas_cuotas, $valor_cuota, $fechaFin,
            $prestamo['cuenta_desembolso_id'], $total_nuevo, 'refinanciacion', $prestamo_id,
            $_SESSION['usuario_id']
        ]);
        $nuevo_id = $db->lastInsertId();
        generarCuotas($db, $nuevo_id, $cobro, date('Y-m-d'), $prestamo['frecuencia_pago'], $nuevas_cuotas, $valor_cuota, $total_nuevo);

        registrarGestion($db, $cobro, $prestamo_id, $prestamo['deudor_id'], 'nota', 'Préstamo refinanciado. Nuevo: #'.$nuevo_id.' Capital: $'.$base, $data['nota_gestion']??null);

        $db->commit();
        echo json_encode(['ok'=>true,'msg'=>'Préstamo refinanciado. Nuevo #'.$nuevo_id,'nuevo_id'=>$nuevo_id]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
    }

// ============================================================
// ACUERDO DE PAGO
// ============================================================
} elseif ($action === 'acuerdo') {
    if (!canDo('puede_editar_prestamo')) { echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit; }

    $prestamo_id      = (int)($data['prestamo_id'] ?? 0);
    $nota_acuerdo     = trim($data['nota_acuerdo'] ?? '');
    $fecha_compromiso = $data['fecha_compromiso'] ?? '';

    if (!$prestamo_id || !$nota_acuerdo) {
        echo json_encode(['ok'=>false,'msg'=>'La nota del acuerdo es obligatoria']); exit;
    }

    $db->prepare("UPDATE prestamos SET estado='en_acuerdo', nota_acuerdo=?, fecha_acuerdo=CURDATE(), fecha_compromiso=?, updated_at=NOW() WHERE id=? AND cobro_id=?")
       ->execute([$nota_acuerdo, $fecha_compromiso ?: null, $prestamo_id, $cobro]);

    // Cargar deudor_id
    $did = $db->prepare("SELECT deudor_id FROM prestamos WHERE id=?"); $did->execute([$prestamo_id]);
    $deudor_id = $did->fetchColumn();

    registrarGestion($db, $cobro, $prestamo_id, $deudor_id, 'acuerdo', $nota_acuerdo, $data['nota_gestion']??null);
    echo json_encode(['ok'=>true,'msg'=>'Acuerdo registrado']);

} elseif (in_array($action, ['anular','editar'])) {
    // continúa abajo
} else {
    echo json_encode(['ok'=>false,'msg'=>'Acción no reconocida']);
}

// ============================================================
// HELPERS
// ============================================================
function generarCuotas(PDO $db, int $prestamo_id, int $cobro, string $fecha_ini, string $frecuencia, int $num_cuotas, float $valor_cuota, float $total, bool $omitir_domingos = false): void {
    $diasMap = ['diario'=>1,'semanal'=>7,'quincenal'=>15,'mensual'=>30];
    $dias    = $diasMap[$frecuencia] ?? 30;
    $saldo   = $total;

    for ($i = 1; $i <= $num_cuotas; $i++) {
        if ($omitir_domingos && $frecuencia === 'diario') {
            // Contar $dias días hábiles (sin domingos) desde fecha_ini
            $fecha_venc = new DateTime($fecha_ini);
            $diasContados = 0;
            while ($diasContados < $dias * $i) {
                $fecha_venc->modify('+1 day');
                if ($fecha_venc->format('N') != 7) { // N=7 es domingo
                    $diasContados++;
                }
            }
        } else {
            $fecha_venc = (new DateTime($fecha_ini))->modify("+".($dias * $i)." days");
        }
        $fecha_venc_str = $fecha_venc->format('Y-m-d');
        $monto_esta = ($i === $num_cuotas) ? round($saldo, 2) : $valor_cuota;
        $db->prepare("INSERT INTO cuotas (prestamo_id,cobro_id,numero_cuota,fecha_vencimiento,monto_cuota,saldo_cuota) VALUES (?,?,?,?,?,?)")
           ->execute([$prestamo_id, $cobro, $i, $fecha_venc_str, $monto_esta, $monto_esta]);
        $saldo -= $valor_cuota;
    }
}

function actualizarEstadoMora(PDO $db, int $prestamo_id): string {
    $stmt = $db->prepare("SELECT MIN(fecha_vencimiento) FROM cuotas WHERE prestamo_id=? AND estado IN ('pendiente','parcial')");
    $stmt->execute([$prestamo_id]);
    $proxima = $stmt->fetchColumn();
    if (!$proxima) return 'pagado';
    $dias_mora = max(0, (new DateTime())->diff(new DateTime($proxima))->days * ((new DateTime()) > (new DateTime($proxima)) ? 1 : -1));
    if ($dias_mora > 0) {
        $db->prepare("UPDATE prestamos SET dias_mora=?, estado='en_mora', updated_at=NOW() WHERE id=?")->execute([$dias_mora, $prestamo_id]);
        return 'en_mora';
    }
    $db->prepare("UPDATE prestamos SET dias_mora=0, updated_at=NOW() WHERE id=?")->execute([$prestamo_id]);
    return 'activo';
}

function registrarGestion(PDO $db, int $cobro, int $prestamo_id, int $deudor_id, string $tipo, string $nota, ?string $nota_extra): void {
    $nota_final = $nota . ($nota_extra ? ' — ' . $nota_extra : '');
    $db->prepare("INSERT INTO gestiones_cobro (cobro_id,prestamo_id,deudor_id,tipo,resultado,nota,fecha_gestion,usuario_id) VALUES (?,?,?,?,'otro',?,CURDATE(),?)")
       ->execute([$cobro, $prestamo_id, $deudor_id, $tipo, $nota_final, $_SESSION['usuario_id']]);
}

// ============================================================
// ANULAR PRÉSTAMO
// ============================================================
if ($action === 'anular') {
    if (!canDo('puede_anular_prestamo')) { echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit; }

    $prestamo_id = (int)($data['id'] ?? 0);
    if (!$prestamo_id) { echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

    $pQ = $db->prepare("SELECT * FROM prestamos WHERE id=? AND cobro_id=?");
    $pQ->execute([$prestamo_id, $cobro]);
    $prestamo = $pQ->fetch();
    if (!$prestamo) { echo json_encode(['ok'=>false,'msg'=>'Préstamo no encontrado']); exit; }

    if ($prestamo['estado'] === 'anulado') {
        echo json_encode(['ok'=>false,'msg'=>'El préstamo ya está anulado']); exit;
    }

    // Verificar que no tenga pagos
    $pagosQ = $db->prepare("SELECT COUNT(*) FROM pagos WHERE prestamo_id=?");
    $pagosQ->execute([$prestamo_id]);
    if ((int)$pagosQ->fetchColumn() > 0) {
        echo json_encode(['ok'=>false,'msg'=>'No se puede anular: el préstamo tiene pagos registrados']); exit;
    }

    try {
        $db->beginTransaction();

        // 1. Marcar préstamo como anulado
        $db->prepare("UPDATE prestamos SET estado='anulado', anulado_at=NOW(), anulado_por=?, saldo_pendiente=0 WHERE id=?")
           ->execute([$_SESSION['usuario_id'], $prestamo_id]);

        // 2. Anular cuotas
        $db->prepare("UPDATE cuotas SET estado='anulado' WHERE prestamo_id=?")
           ->execute([$prestamo_id]);

        // 3. Marcar movimiento principal del préstamo como anulado
        //    Las vistas filtran anulado=0, así el saldo se corrige automáticamente
        $db->prepare("UPDATE capital_movimientos
                      SET anulado=1, anulado_at=NOW(), anulado_por=?
                      WHERE prestamo_id=? AND tipo='prestamo' AND (anulado=0 OR anulado IS NULL)")
           ->execute([$_SESSION['usuario_id'], $prestamo_id]);

        // 4. Marcar proporcionales como anulados también
        $db->prepare("UPDATE capital_movimientos
                      SET anulado=1, anulado_at=NOW(), anulado_por=?
                      WHERE prestamo_id=? AND tipo='prestamo_proporcional' AND (anulado=0 OR anulado IS NULL)")
           ->execute([$_SESSION['usuario_id'], $prestamo_id]);

        $db->commit();
        echo json_encode(['ok'=>true,'msg'=>'Préstamo anulado. El capital y los saldos fueron revertidos automáticamente.']);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
    }
    exit;
}

// ============================================================
// EDITAR PRÉSTAMO (solo si no tiene pagos)
// ============================================================
if ($action === 'editar') {
    if (!canDo('puede_editar_prestamo')) { echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit; }

    $prestamo_id = (int)($data['id'] ?? 0);
    if (!$prestamo_id) { echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

    $pQ = $db->prepare("SELECT * FROM prestamos WHERE id=? AND cobro_id=?");
    $pQ->execute([$prestamo_id, $cobro]);
    $prestamo = $pQ->fetch();
    if (!$prestamo) { echo json_encode(['ok'=>false,'msg'=>'Préstamo no encontrado']); exit; }

    // Verificar pagos
    $pagosQ = $db->prepare("SELECT COUNT(*) FROM pagos WHERE prestamo_id=?");
    $pagosQ->execute([$prestamo_id]);
    if ((int)$pagosQ->fetchColumn() > 0) {
        echo json_encode(['ok'=>false,'msg'=>'No se puede editar: el préstamo ya tiene pagos registrados']); exit;
    }

    $monto      = (float)($data['monto_prestado']    ?? $prestamo['monto_prestado']);
    $tipo_int   = $data['tipo_interes']               ?? $prestamo['tipo_interes'];
    $interes_v  = (float)($data['interes_valor']      ?? $prestamo['interes_valor']);
    $frecuencia = $data['frecuencia_pago']             ?? $prestamo['frecuencia_pago'];
    $num_cuotas = (int)($data['num_cuotas']           ?? $prestamo['num_cuotas']);
    $fecha_ini  = $data['fecha_inicio']                ?? $prestamo['fecha_inicio'];
    $cuenta_id  = (int)($data['cuenta_desembolso_id'] ?? $prestamo['cuenta_desembolso_id']);

    $interes_calc = $tipo_int === 'porcentaje' ? $monto * ($interes_v/100) : (float)$interes_v;
    $total        = $monto + $interes_calc;
    $valor_cuota  = $num_cuotas > 0 ? round($total / $num_cuotas) : $total;

    // Calcular fecha fin
    $diasMap  = ['diario'=>1,'semanal'=>7,'quincenal'=>15,'mensual'=>30];
    $diasEdit = $diasMap[$frecuencia] ?? 30;
    $fechaFin = (new DateTime($fecha_ini))->modify("+".($diasEdit * $num_cuotas)." days")->format('Y-m-d');

    try {
        $db->beginTransaction();

        // Guardar historial de cambios
        $historial = json_decode($prestamo['historial'] ?? '[]', true) ?: [];
        $historial[] = [
            'fecha'   => date('Y-m-d H:i:s'),
            'usuario' => $_SESSION['usuario_id'],
            'cambios' => [
                'monto_prestado'  => [$prestamo['monto_prestado'],  $monto],
                'interes_valor'   => [$prestamo['interes_valor'],    $interes_v],
                'num_cuotas'      => [$prestamo['num_cuotas'],       $num_cuotas],
                'frecuencia_pago' => [$prestamo['frecuencia_pago'],  $frecuencia],
            ]
        ];

        // Actualizar préstamo
        $db->prepare("UPDATE prestamos SET
            monto_prestado=?, tipo_interes=?, interes_valor=?, interes_calculado=?,
            total_a_pagar=?, frecuencia_pago=?, num_cuotas=?, valor_cuota=?,
            fecha_inicio=?, fecha_fin_esperada=?, cuenta_desembolso_id=?,
            saldo_pendiente=?, editado_at=NOW(), editado_por=?, historial=?,
            updated_at=NOW()
            WHERE id=?")
        ->execute([
            $monto, $tipo_int, $interes_v, $interes_calc,
            $total, $frecuencia, $num_cuotas, $valor_cuota,
            $fecha_ini, $fechaFin, $cuenta_id,
            $total,
            $_SESSION['usuario_id'], json_encode($historial),
            $prestamo_id
        ]);

        // Regenerar cuotas
        $db->prepare("DELETE FROM cuotas WHERE prestamo_id=?")->execute([$prestamo_id]);
        $omitir_dom_edit = !empty($data['omitir_domingos']) ? true : (bool)($prestamo['omitir_domingos'] ?? false);
        $db->prepare("UPDATE prestamos SET omitir_domingos=? WHERE id=?")->execute([$omitir_dom_edit?1:0, $prestamo_id]);
        generarCuotas($db, $prestamo_id, $cobro, $fecha_ini, $frecuencia, $num_cuotas, $valor_cuota, $total, $omitir_dom_edit);

        // Actualizar movimientos de capital si cambió el monto
        if ((float)$prestamo['monto_prestado'] != $monto) {
            // 1. Actualizar movimiento principal del préstamo
            $db->prepare("UPDATE capital_movimientos
                SET monto=?, descripcion='Préstamo #{$prestamo_id} editado — nuevo monto'
                WHERE prestamo_id=? AND tipo='prestamo' AND (anulado=0 OR anulado IS NULL) AND es_entrada=0")
            ->execute([$monto, $prestamo_id]);

            // 2. Recalcular y reemplazar proporcionales
            $db->prepare("DELETE FROM capital_movimientos WHERE prestamo_id=? AND tipo='prestamo_proporcional'")
               ->execute([$prestamo_id]);

            // Recalcular descuentos proporcionales con nuevo monto
            $capsQ = $db->prepare("
                SELECT c.id, c.tipo,
                    COALESCE(SUM(CASE WHEN m.es_entrada=1 AND m.tipo!='prestamo_proporcional' THEN m.monto ELSE 0 END),0) AS total_aporte,
                    COALESCE(SUM(CASE WHEN m.es_entrada=1 THEN m.monto ELSE -m.monto END),0) AS saldo_actual
                FROM capitalistas c
                LEFT JOIN capital_movimientos m ON m.capitalista_id=c.id AND m.cobro_id=c.cobro_id
                WHERE c.cobro_id=? AND c.estado='activo'
                GROUP BY c.id, c.tipo
                HAVING saldo_actual > 0
                ORDER BY FIELD(c.tipo,'propio','prestado'), saldo_actual DESC
            ");
            $capsQ->execute([$cobro]);
            $capsData  = $capsQ->fetchAll();
            $propios   = array_filter($capsData, fn($c) => $c['tipo']==='propio');
            $prestados = array_filter($capsData, fn($c) => $c['tipo']==='prestado');

            $montoRestante = (float)$monto;
            foreach ($propios as $cap) {
                if ($montoRestante <= 0) break;
                $desc = min($montoRestante, (float)$cap['saldo_actual']);
                $montoRestante -= $desc;
                if ($desc <= 0) continue;
                $db->prepare("INSERT INTO capital_movimientos
                    (cobro_id,tipo,es_entrada,monto,cuenta_id,capitalista_id,prestamo_id,descripcion,fecha,usuario_id)
                    VALUES (?,'prestamo_proporcional',0,?,?,?,?,?,?,?)")
                ->execute([$cobro,round($desc),$prestamo['cuenta_desembolso_id'],
                    $cap['id'],$prestamo_id,
                    "Préstamo #{$prestamo_id} — descuento capital propio",
                    $fecha_ini,$_SESSION['usuario_id']]);
            }
            if ($montoRestante > 0) {
                $totalAportesPrest = array_sum(array_column(array_values($prestados),'total_aporte'));
                $lastPrest = array_values($prestados);
                foreach ($lastPrest as $idx => $cap) {
                    if ($montoRestante <= 0) break;
                    $pct  = $totalAportesPrest > 0 ? $cap['total_aporte']/$totalAportesPrest : 1;
                    $desc = ($idx===count($lastPrest)-1) ? $montoRestante : round($montoRestante*$pct);
                    $desc = min($desc,(float)$cap['saldo_actual']);
                    $montoRestante -= $desc;
                    if ($desc <= 0) continue;
                    $db->prepare("INSERT INTO capital_movimientos
                        (cobro_id,tipo,es_entrada,monto,cuenta_id,capitalista_id,prestamo_id,descripcion,fecha,usuario_id)
                        VALUES (?,'prestamo_proporcional',0,?,?,?,?,?,?,?)")
                    ->execute([$cobro,round($desc),$prestamo['cuenta_desembolso_id'],
                        $cap['id'],$prestamo_id,
                        "Préstamo #{$prestamo_id} — descuento capital prestado (".round($pct*100)."%)",
                        $fecha_ini,$_SESSION['usuario_id']]);
                }
            }
        }

        $db->commit();
        echo json_encode(['ok'=>true,'msg'=>'Préstamo actualizado correctamente']);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
    }
    exit;
}