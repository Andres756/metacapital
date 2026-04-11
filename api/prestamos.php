<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok'=>false,'msg'=>'Método no permitido']); exit;
}

$data  = json_decode(file_get_contents('php://input'), true) ?? [];
$db    = getDB();
// Si viene cobro_id en el request (admin con filtro) usarlo, si no usar sesión
$cobro = (int)($data['cobro_id'] ?? 0) ?: cobroActivo();
$action= $data['action'] ?? '';

// ============================================================
// HELPER — verificar si deudor es clavo
// ============================================================
function esClavo(PDO $db, int $deudor_id): ?string {
    if (!$deudor_id) return null;
    $stmt = $db->prepare("SELECT nombre FROM deudores WHERE id=? AND comportamiento='clavo'");
    $stmt->execute([$deudor_id]);
    $row = $stmt->fetch();
    return $row ? $row['nombre'] : null;
}

// ============================================================
// CREAR PRÉSTAMO
// ============================================================
if ($action === 'crear') {
    if (!canDo('puede_crear_prestamo')) { echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit; }

    $deudor_id   = (int)($data['deudor_id']      ?? 0);
    $monto       = (float)($data['monto_prestado'] ?? 0);
    $tipo_int    = in_array($data['tipo_interes']??'', ['porcentaje','valor_fijo']) ? $data['tipo_interes'] : 'porcentaje';
    $interes_val = (float)($data['interes_valor']  ?? 0);
    $fecha_ini   = $data['fecha_inicio']            ?? date('Y-m-d');
    $frecuencia  = in_array($data['frecuencia_pago']??'', ['diario','semanal','quincenal','mensual']) ? $data['frecuencia_pago'] : 'mensual';
    $num_cuotas  = max(1, (int)($data['num_cuotas'] ?? 1));
    $metodo_pago = in_array($data['metodo_pago']??'', ['efectivo','banco']) ? $data['metodo_pago'] : 'efectivo';

    if (!$deudor_id || $monto <= 0) {
        echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit;
    }

    $nombreClavo = esClavo($db, $deudor_id);
    if ($nombreClavo) {
        echo json_encode(['ok'=>false,'msg'=>"No se puede crear un préstamo a $nombreClavo — está marcado como CLAVO."]); exit;
    }

    $interes_calc = $tipo_int === 'porcentaje'
        ? $monto * ($interes_val / 100)
        : (float)$interes_val;
    $total = $monto + $interes_calc;

    if (!empty($data['valor_cuota_override']) && (float)$data['valor_cuota_override'] > 0) {
        $valor_cuota = (float)$data['valor_cuota_override'];
        $num_cuotas  = max(1, (int)ceil($total / $valor_cuota));
    } else {
        $valor_cuota = round($total / $num_cuotas, 2);
    }

    $diasMap  = ['diario'=>1,'semanal'=>7,'quincenal'=>15,'mensual'=>30];
    $dias     = $diasMap[$frecuencia] ?? 30;
    $fechaFin = (new DateTime($fecha_ini))->modify("+".($dias * $num_cuotas)." days")->format('Y-m-d');

    // Papelería
    $stmtPap = $db->prepare("SELECT papeleria_pct FROM cobros WHERE id=?");
    $stmtPap->execute([$cobro]);
    $papeleria_pct_default = (float)($stmtPap->fetchColumn() ?? 10);
    $papeleria_pct   = isset($data['papeleria_pct']) && $data['papeleria_pct'] !== ''
        ? max(0, min(100, (float)$data['papeleria_pct']))
        : $papeleria_pct_default;
    $papeleria_monto = round($monto * ($papeleria_pct / 100), 0);

    $db->beginTransaction();
    try {
        // Vincular deudor al cobro automáticamente si no está vinculado
        $db->prepare("INSERT IGNORE INTO deudor_cobro (deudor_id, cobro_id) VALUES (?,?)")
           ->execute([$deudor_id, $cobro]);

        // Validar saldo de caja — solo base (origen capital/liquidacion)
        $saldo = getSaldoCaja($db, $cobro);
        if ($saldo < $monto) {
            $db->rollBack();
            echo json_encode([
                'ok'  => false,
                'msg' => 'Saldo insuficiente en caja. Disponible: '.fmt($saldo).' · Requerido: '.fmt($monto)
            ]); exit;
        }

        $db->prepare("
            INSERT INTO prestamos
            (cobro_id, deudor_id, capitalista_id, monto_prestado, tipo_interes, interes_valor,
            interes_calculado, total_a_pagar, frecuencia_pago, num_cuotas, valor_cuota,
            fecha_inicio, fecha_fin_esperada, saldo_pendiente,
            tipo_origen, observaciones, omitir_domingos, papeleria_pct, papeleria_monto,
            usuario_id)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ")->execute([
            $cobro, $deudor_id,
            ($data['capitalista_id'] ?? null) ?: null,
            $monto, $tipo_int, $interes_val, $interes_calc, $total,
            $frecuencia, $num_cuotas, $valor_cuota,
            $fecha_ini, $fechaFin, $total,
            $data['tipo_origen'] ?? 'nuevo',
            $data['observaciones'] ?? null,
            !empty($data['omitir_domingos']) ? 1 : 0,
            $papeleria_pct, $papeleria_monto,
            $_SESSION['usuario_id']
        ]);
        $prestamo_id     = (int)$db->lastInsertId();
        $omitir_domingos = !empty($data['omitir_domingos']);

        generarCuotas($db, $prestamo_id, $cobro, $fecha_ini, $frecuencia, $num_cuotas, $valor_cuota, $total, $omitir_domingos);

        // Papelería
        if ($papeleria_monto > 0) {
            $stmtCob = $db->prepare("
                SELECT u.id FROM usuarios u
                JOIN usuario_cobro uc ON uc.usuario_id = u.id
                WHERE uc.cobro_id = ? AND u.rol = 'cobrador' AND u.activo = 1
                LIMIT 1
            ");
            $stmtCob->execute([$cobro]);
            $cobrador_id = (int)($stmtCob->fetchColumn() ?: $_SESSION['usuario_id']);

            $db->prepare("INSERT INTO papeleria
                (cobro_id, prestamo_id, cobrador_id, fecha, monto_prestado, pct_aplicado, monto_papeleria)
                VALUES (?,?,?,?,?,?,?)")
            ->execute([
                $cobro, $prestamo_id, $cobrador_id,
                $fecha_ini, $monto, $papeleria_pct, $papeleria_monto
            ]);
        }

        // ── CAMBIO FASE 3: origen='cobrador' — NO afecta la base general
        // El préstamo sale de la base de trabajo del cobrador, no de la caja
        $db->prepare("INSERT INTO capital_movimientos
            (cobro_id, tipo, origen, es_entrada, monto, metodo_pago, capitalista_id, prestamo_id, descripcion, fecha, usuario_id)
            VALUES (?, 'prestamo', 'cobrador', 0, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([
            $cobro, $monto, $metodo_pago,
            ($data['capitalista_id'] ?? null) ?: null,
            $prestamo_id,
            "Préstamo #$prestamo_id a deudor #$deudor_id",
            $fecha_ini, $_SESSION['usuario_id']
        ]);

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

    $prestamo_id = (int)($data['prestamo_id']  ?? 0);
    $cuota_id    = (int)($data['cuota_id']      ?? 0);
    $monto       = (float)($data['monto_pagado'] ?? 0);
    $fecha_pago  = $data['fecha_pago']           ?? date('Y-m-d');
    $metodo_pago = in_array($data['metodo_pago']??'', ['efectivo','banco']) ? $data['metodo_pago'] : 'efectivo';

    if (!$prestamo_id || !$cuota_id || $monto <= 0) {
        echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit;
    }

    // Sin filtro cobro_id — admin puede pagar préstamos de cualquier cobro que tenga asignado
    $stmtP = $db->prepare("SELECT * FROM prestamos WHERE id=?");
    $stmtP->execute([$prestamo_id]);
    $prestamo = $stmtP->fetch();
    if (!$prestamo) { echo json_encode(['ok'=>false,'msg'=>'Préstamo no encontrado']); exit; }

    // Verificar acceso al cobro del préstamo
    if ($_SESSION['rol'] !== 'superadmin') {
        $chkAcceso = $db->prepare("SELECT 1 FROM usuario_cobro WHERE usuario_id=? AND cobro_id=?");
        $chkAcceso->execute([$_SESSION['usuario_id'], $prestamo['cobro_id']]);
        if (!$chkAcceso->fetch()) {
            echo json_encode(['ok'=>false,'msg'=>'Sin acceso a este préstamo']); exit;
        }
    }
    // Usar el cobro_id del préstamo para todos los INSERTs
    $cobro_pago = $prestamo['cobro_id'];

    $stmtC = $db->prepare("SELECT * FROM cuotas WHERE id=? AND prestamo_id=?");
    $stmtC->execute([$cuota_id, $prestamo_id]);
    $cuota = $stmtC->fetch();
    if (!$cuota) { echo json_encode(['ok'=>false,'msg'=>'Cuota no encontrada']); exit; }

    $db->beginTransaction();
    try {
        $excedente     = 0;
        $msg_extra     = '';
        $monto_aplicar = $monto;

        $monto_pagado_nuevo = $cuota['monto_pagado'] + $monto_aplicar;
        $saldo_cuota_nuevo  = $cuota['monto_cuota']  - $monto_pagado_nuevo;

        if ($saldo_cuota_nuevo < 0) {
            $excedente          = abs($saldo_cuota_nuevo);
            $monto_aplicar      = $cuota['monto_cuota'] - $cuota['monto_pagado'];
            $monto_pagado_nuevo = $cuota['monto_cuota'];
            $saldo_cuota_nuevo  = 0;
        }

        $estado_cuota = $saldo_cuota_nuevo <= 0 ? 'pagado' : 'parcial';

        $db->prepare("UPDATE cuotas SET monto_pagado=?, saldo_cuota=?, estado=?, fecha_pago=?, updated_at=NOW() WHERE id=?")
           ->execute([$monto_pagado_nuevo, $saldo_cuota_nuevo, $estado_cuota, $fecha_pago, $cuota_id]);

        $db->prepare("INSERT INTO pagos
            (cobro_id,prestamo_id,cuota_id,deudor_id,monto_pagado,fecha_pago,metodo_pago,es_parcial,observacion,usuario_id)
            VALUES (?,?,?,?,?,?,?,?,?,?)")
        ->execute([
            $cobro_pago, $prestamo_id, $cuota_id, $prestamo['deudor_id'],
            $monto_aplicar, $fecha_pago, $metodo_pago, 0,
            $data['observacion'] ?? null, $_SESSION['usuario_id']
        ]);
        $pago_id_principal = (int)$db->lastInsertId();

        // Excedente — aplicar a cuotas siguientes
        if ($excedente > 0) {
            $saldoExcedente  = $excedente;
            $cuotasAplicadas = [];

            $sigQ = $db->prepare("
                SELECT * FROM cuotas
                WHERE prestamo_id=? AND estado IN ('pendiente','parcial') AND id != ?
                ORDER BY numero_cuota ASC
            ");
            $sigQ->execute([$prestamo_id, $cuota_id]);
            $cuotasSig = $sigQ->fetchAll();

            foreach ($cuotasSig as $sc) {
                if ($saldoExcedente <= 0) break;
                $aplicar = min($saldoExcedente, $sc['saldo_cuota']);
                $saldoExcedente -= $aplicar;
                $mp2  = $sc['monto_pagado'] + $aplicar;
                $sc2  = max(0, $sc['saldo_cuota'] - $aplicar);
                $est2 = $sc2 <= 0 ? 'pagado' : 'parcial';
                $db->prepare("UPDATE cuotas SET monto_pagado=?, saldo_cuota=?, estado=?, fecha_pago=?, updated_at=NOW() WHERE id=?")
                   ->execute([$mp2, $sc2, $est2, ($sc2<=0 ? $fecha_pago : null), $sc['id']]);
                $db->prepare("INSERT INTO pagos
                    (cobro_id,prestamo_id,cuota_id,deudor_id,monto_pagado,fecha_pago,metodo_pago,es_parcial,observacion,usuario_id)
                    VALUES (?,?,?,?,?,?,?,?,?,?)")
                ->execute([
                    $cobro_pago, $prestamo_id, $sc['id'], $prestamo['deudor_id'],
                    $aplicar, $fecha_pago, $metodo_pago,
                    $sc2 > 0 ? 1 : 0,
                    'Excedente de cuota #'.$cuota['numero_cuota'],
                    $_SESSION['usuario_id']
                ]);
                $cuotasAplicadas[] = $sc['numero_cuota'];
            }

            if (!empty($cuotasAplicadas)) {
                $msg_extra = " Excedente aplicado a cuota(s) #".implode(', #', $cuotasAplicadas).".";
            }

            if ($saldoExcedente > 0) {
                $db->prepare("UPDATE deudores SET saldo_favor = saldo_favor + ? WHERE id=?")
                   ->execute([$saldoExcedente, $prestamo['deudor_id']]);
                $msg_extra .= " $".number_format($saldoExcedente,0,',','.')." guardado como saldo a favor.";
            }
        }

        $nuevo_saldo  = max(0, $prestamo['saldo_pendiente'] - $monto);
        $nuevo_estado = $nuevo_saldo <= 0 ? 'pagado' : actualizarEstadoMora($db, $prestamo_id);
        $db->prepare("UPDATE prestamos SET saldo_pendiente=?, estado=?, updated_at=NOW() WHERE id=?")
           ->execute([$nuevo_saldo, $nuevo_estado, $prestamo_id]);

        // ── CAMBIO FASE 3: origen='cobrador' — el cobro lo maneja el cobrador
        // Solo se registra para auditoría, NO afecta la base general
        $db->prepare("INSERT INTO capital_movimientos
            (cobro_id, tipo, origen, es_entrada, monto, metodo_pago, capitalista_id, prestamo_id, pago_id, descripcion, fecha, usuario_id)
            VALUES (?, 'cobro_cuota', 'cobrador', 1, ?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([
            $cobro_pago, $monto, $metodo_pago,
            ($prestamo['capitalista_id'] ?: null),
            $prestamo_id, $pago_id_principal,
            "Cobro cuota préstamo #$prestamo_id",
            $fecha_pago, $_SESSION['usuario_id']
        ]);

        $db->commit();
        echo json_encode(['ok'=>true,'msg'=>'Pago registrado correctamente.'.$msg_extra]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
    }

// ============================================================
// RENOVAR / REFINANCIAR
// ============================================================
} elseif ($action === 'renovar') {
    if (!canDo('puede_editar_prestamo')) {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }

    $prestamo_id      = (int)($data['prestamo_id']      ?? 0);
    $monto_renovacion = (float)($data['monto_renovacion'] ?? 0);
    $tipo_int         = in_array($data['tipo_interes']??'', ['porcentaje','valor_fijo']) ? $data['tipo_interes'] : 'porcentaje';
    $interes_val      = (float)($data['interes_valor']   ?? 0);
    $nuevas_cuotas    = max(1, (int)($data['num_cuotas'] ?? 1));
    $frecuencia       = in_array($data['frecuencia_pago']??'', ['diario','semanal','quincenal','mensual']) ? $data['frecuencia_pago'] : 'mensual';
    $omitir_domingos  = !empty($data['omitir_domingos']) && $frecuencia === 'diario';
    $metodo_pago      = in_array($data['metodo_pago']??'', ['efectivo','banco']) ? $data['metodo_pago'] : 'efectivo';

    if (!$prestamo_id || $monto_renovacion <= 0) {
        echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit;
    }

    $pQ = $db->prepare("SELECT * FROM prestamos WHERE id=? AND cobro_id=?");
    $pQ->execute([$prestamo_id, $cobro]);
    $prestamo = $pQ->fetch();
    if (!$prestamo) {
        echo json_encode(['ok'=>false,'msg'=>'Préstamo no encontrado']); exit;
    }

    $nombreClavo = esClavo($db, (int)$prestamo['deudor_id']);
    if ($nombreClavo) {
        echo json_encode(['ok'=>false,'msg'=>"No se puede renovar el préstamo de $nombreClavo — está marcado como CLAVO."]); exit;
    }

    $saldo_pendiente = (float)$prestamo['saldo_pendiente'];

    if ($monto_renovacion < $saldo_pendiente) {
        echo json_encode([
            'ok'  => false,
            'msg' => 'El monto de renovación no puede ser menor al saldo pendiente (' . fmt($saldo_pendiente) . ')'
        ]); exit;
    }

    $pagosQ = $db->prepare("SELECT COUNT(*) FROM pagos WHERE prestamo_id=? AND anulado=0");
    $pagosQ->execute([$prestamo_id]);
    $tiene_pagos = (int)$pagosQ->fetchColumn() > 0;

    if (!$tiene_pagos) {
        $ultQ = $db->prepare("SELECT MAX(fecha_vencimiento) FROM cuotas WHERE prestamo_id=? AND estado != 'anulado'");
        $ultQ->execute([$prestamo_id]);
        $ultima_fecha   = $ultQ->fetchColumn();
        $ultima_vencida = $ultima_fecha && $ultima_fecha < date('Y-m-d');
        if (!$ultima_vencida) {
            echo json_encode(['ok'=>false,'msg'=>'No se puede renovar: el préstamo no tiene pagos y aún hay cuotas vigentes.']); exit;
        }
    }

    $diferencia = $monto_renovacion - $saldo_pendiente;
    if ($diferencia > 0) {
        $saldo = getSaldoCaja($db, $cobro);
        if ($saldo < $diferencia) {
            echo json_encode([
                'ok'  => false,
                'msg' => 'Saldo insuficiente en caja. Disponible: '.fmt($saldo).' · Requerido: '.fmt($diferencia)
            ]); exit;
        }
    }

    $interes_calc = $tipo_int === 'porcentaje'
        ? $monto_renovacion * ($interes_val / 100)
        : (float)$interes_val;
    $total_nuevo  = $monto_renovacion + $interes_calc;
    $valor_cuota  = round($total_nuevo / $nuevas_cuotas, 2);
    $diasMap      = ['diario'=>1,'semanal'=>7,'quincenal'=>15,'mensual'=>30];
    $dias         = $diasMap[$frecuencia] ?? 30;
    $fechaFin     = (new DateTime())->modify("+".($dias * $nuevas_cuotas)." days")->format('Y-m-d');
    $tipo_origen  = $monto_renovacion > $saldo_pendiente ? 'refinanciacion' : 'renovacion';

    $db->beginTransaction();
    try {
        $db->prepare("UPDATE prestamos SET estado='renovado', saldo_pendiente=0, updated_at=NOW() WHERE id=?")
           ->execute([$prestamo_id]);
        $db->prepare("UPDATE cuotas SET estado='pagado', updated_at=NOW() WHERE prestamo_id=? AND estado NOT IN ('pagado','anulado')")
           ->execute([$prestamo_id]);

        // ── CAMBIO FASE 3: diferencia de renovación — origen='cobrador'
        // El cobrador maneja el efectivo, solo se registra para auditoría
        if (abs($diferencia) >= 1) {
            $es_entrada  = $diferencia < 0 ? 1 : 0;
            $monto_mov   = round(abs($diferencia), 2);
            $descripcion = $diferencia > 0
                ? "Diferencia renovación préstamo #$prestamo_id (cobrador entregó al deudor)"
                : "Diferencia renovación préstamo #$prestamo_id (deudor entregó saldo)";
            $db->prepare("INSERT INTO capital_movimientos
                (cobro_id, tipo, origen, es_entrada, monto, metodo_pago, capitalista_id, prestamo_id, descripcion, fecha, usuario_id)
                VALUES (?, 'prestamo', 'cobrador', ?, ?, ?, ?, ?, ?, CURDATE(), ?)")
               ->execute([
                   $cobro, $es_entrada, $monto_mov, $metodo_pago,
                   $prestamo['capitalista_id'] ?: null,
                   $prestamo_id, $descripcion, $_SESSION['usuario_id']
               ]);
        }

        $db->prepare("INSERT INTO prestamos
            (cobro_id, deudor_id, capitalista_id, monto_prestado, tipo_interes,
             interes_valor, interes_calculado, total_a_pagar, frecuencia_pago,
             num_cuotas, valor_cuota, fecha_inicio, fecha_fin_esperada,
             saldo_pendiente, tipo_origen, prestamo_padre_id, omitir_domingos, usuario_id)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,CURDATE(),?,?,?,?,?,?)")
           ->execute([
               $cobro, $prestamo['deudor_id'], $prestamo['capitalista_id'],
               $monto_renovacion, $tipo_int, $interes_val, $interes_calc, $total_nuevo,
               $frecuencia, $nuevas_cuotas, $valor_cuota, $fechaFin,
               $total_nuevo, $tipo_origen, $prestamo_id,
               $omitir_domingos ? 1 : 0, $_SESSION['usuario_id']
           ]);
        $nuevo_id = (int)$db->lastInsertId();

        generarCuotas($db, $nuevo_id, $cobro, date('Y-m-d'), $frecuencia, $nuevas_cuotas, $valor_cuota, $total_nuevo, $omitir_domingos);

        $nota_gestion = ucfirst($tipo_origen) . " por " . fmt($monto_renovacion) . ".";
        if ($diferencia > 0) $nota_gestion .= " Cobrador entregó " . fmt($diferencia) . ".";
        $nota_gestion .= " Nuevo préstamo #$nuevo_id.";

        registrarGestion($db, $cobro, $prestamo_id, $prestamo['deudor_id'], 'nota', $nota_gestion, $data['nota_gestion'] ?? null);

        $db->commit();

        $msg = "Préstamo {$tipo_origen} exitosamente. Nuevo #$nuevo_id.";
        if ($diferencia > 0) $msg .= " Salida de caja: " . fmt($diferencia) . ".";

        echo json_encode(['ok'=>true,'msg'=>$msg,'nuevo_id'=>$nuevo_id,'diferencia'=>$diferencia,'tipo_origen'=>$tipo_origen]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
    }

// ============================================================
// ACUERDO DE PAGO
// ============================================================
} elseif ($action === 'acuerdo') {
    if (!canDo('puede_editar_prestamo')) {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }

    $prestamo_id      = (int)($data['prestamo_id']    ?? 0);
    $nota_acuerdo     = trim($data['nota_acuerdo']    ?? '');
    $fecha_compromiso = trim($data['fecha_compromiso'] ?? '');

    if (!$prestamo_id) { echo json_encode(['ok'=>false,'msg'=>'Préstamo inválido']); exit; }
    if (!$nota_acuerdo) { echo json_encode(['ok'=>false,'msg'=>'La nota del acuerdo es obligatoria']); exit; }

    if ($fecha_compromiso && (!strtotime($fecha_compromiso) || $fecha_compromiso < date('Y-m-d'))) {
        echo json_encode(['ok'=>false,'msg'=>'La fecha de compromiso debe ser hoy o futura']); exit;
    }

    $pQ = $db->prepare("SELECT * FROM prestamos WHERE id=? AND cobro_id=?");
    $pQ->execute([$prestamo_id, $cobro]);
    $prestamo = $pQ->fetch();
    if (!$prestamo) { echo json_encode(['ok'=>false,'msg'=>'Préstamo no encontrado']); exit; }

    if (!in_array($prestamo['estado'], ['activo','en_mora','en_acuerdo'])) {
        echo json_encode(['ok'=>false,'msg'=>'Estado del préstamo no permite acuerdo']); exit;
    }

    try {
        $db->beginTransaction();
        $db->prepare("
            UPDATE prestamos
            SET estado='en_acuerdo', nota_acuerdo=?, fecha_acuerdo=CURDATE(), fecha_compromiso=?, updated_at=NOW()
            WHERE id=? AND cobro_id=?
        ")->execute([$nota_acuerdo, $fecha_compromiso ?: null, $prestamo_id, $cobro]);

        registrarGestion($db, $cobro, $prestamo_id, $prestamo['deudor_id'], 'acuerdo', $nota_acuerdo, $data['nota_gestion'] ?? null);

        $db->commit();
        echo json_encode(['ok'=>true,'msg'=>'Acuerdo de pago registrado']);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
    }
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
            $fecha_venc   = new DateTime($fecha_ini);
            $diasContados = 0;
            while ($diasContados < $dias * $i) {
                $fecha_venc->modify('+1 day');
                if ($fecha_venc->format('N') != 7) $diasContados++;
            }
        } else {
            $fecha_venc = (new DateTime($fecha_ini))->modify("+".($dias * $i)." days");
        }

        $monto_esta = ($i === $num_cuotas) ? max(0, round($saldo, 2)) : $valor_cuota;

        $db->prepare("INSERT INTO cuotas (prestamo_id,cobro_id,numero_cuota,fecha_vencimiento,monto_cuota,saldo_cuota) VALUES (?,?,?,?,?,?)")
           ->execute([$prestamo_id, $cobro, $i, $fecha_venc->format('Y-m-d'), $monto_esta, $monto_esta]);

        $saldo -= $valor_cuota;
    }
}

function actualizarEstadoMora(PDO $db, int $prestamo_id): string {
    $stmt = $db->prepare("SELECT MIN(fecha_vencimiento) FROM cuotas WHERE prestamo_id=? AND estado IN ('pendiente','parcial')");
    $stmt->execute([$prestamo_id]);
    $proxima = $stmt->fetchColumn();
    if (!$proxima) return 'pagado';
    $hoy      = new DateTime();
    $venc     = new DateTime($proxima);
    $diff     = (int)$hoy->diff($venc)->days * ($hoy > $venc ? 1 : -1);
    if ($diff > 0) {
        $db->prepare("UPDATE prestamos SET dias_mora=?, estado='en_mora', updated_at=NOW() WHERE id=?")->execute([$diff, $prestamo_id]);
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

    $pagosQ = $db->prepare("SELECT COUNT(*) FROM pagos WHERE prestamo_id=? AND anulado=0");
    $pagosQ->execute([$prestamo_id]);
    if ((int)$pagosQ->fetchColumn() > 0) {
        echo json_encode(['ok'=>false,'msg'=>'No se puede anular: el préstamo tiene pagos registrados']); exit;
    }

    $chkAntes = $db->prepare("SELECT COUNT(*) FROM capital_movimientos WHERE prestamo_id=? AND es_entrada=0 AND origen='cobrador' AND (anulado=0 OR anulado IS NULL)");
    $chkAntes->execute([$prestamo_id]);
    $movActivos = (int)$chkAntes->fetchColumn();

    try {
        $db->beginTransaction();

        $db->prepare("UPDATE prestamos SET estado='anulado', anulado_at=NOW(), anulado_por=?, saldo_pendiente=0, updated_at=NOW() WHERE id=?")
           ->execute([$_SESSION['usuario_id'], $prestamo_id]);

        $db->prepare("UPDATE cuotas SET estado='anulado', updated_at=NOW() WHERE prestamo_id=?")
           ->execute([$prestamo_id]);

        $db->prepare("UPDATE capital_movimientos SET anulado=1, anulado_at=NOW(), anulado_por=?
                      WHERE prestamo_id=? AND tipo='prestamo' AND origen='cobrador' AND (anulado=0 OR anulado IS NULL)")
           ->execute([$_SESSION['usuario_id'], $prestamo_id]);

        $db->commit();

        $msg = 'Préstamo anulado.';
        if ($movActivos === 0) {
            $msg .= ' (Sin movimiento de caja asociado — datos de auditoría correctos.)';
        }
        echo json_encode(['ok'=>true,'msg'=>$msg]);

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

    $pagosQ = $db->prepare("SELECT COUNT(*) FROM pagos WHERE prestamo_id=? AND anulado=0");
    $pagosQ->execute([$prestamo_id]);
    if ((int)$pagosQ->fetchColumn() > 0) {
        echo json_encode(['ok'=>false,'msg'=>'No se puede editar: el préstamo ya tiene pagos registrados']); exit;
    }

    $monto      = (float)($data['monto_prestado']  ?? $prestamo['monto_prestado']);
    $tipo_int   = $data['tipo_interes']             ?? $prestamo['tipo_interes'];
    $interes_v  = (float)($data['interes_valor']    ?? $prestamo['interes_valor']);
    $frecuencia = $data['frecuencia_pago']           ?? $prestamo['frecuencia_pago'];
    $num_cuotas = (int)($data['num_cuotas']         ?? $prestamo['num_cuotas']);
    $fecha_ini  = $data['fecha_inicio']              ?? $prestamo['fecha_inicio'];

    if ($monto <= 0)      { echo json_encode(['ok'=>false,'msg'=>'El monto debe ser mayor a 0']); exit; }
    if ($num_cuotas <= 0) { echo json_encode(['ok'=>false,'msg'=>'Las cuotas deben ser mayor a 0']); exit; }

    $interes_calc = $tipo_int === 'porcentaje' ? $monto * ($interes_v / 100) : (float)$interes_v;
    $total        = $monto + $interes_calc;
    $valor_cuota  = round($total / $num_cuotas, 2);
    $diasMap      = ['diario'=>1,'semanal'=>7,'quincenal'=>15,'mensual'=>30];
    $diasEdit     = $diasMap[$frecuencia] ?? 30;
    $fechaFin     = (new DateTime($fecha_ini))->modify("+".($diasEdit * $num_cuotas)." days")->format('Y-m-d');

    try {
        $db->beginTransaction();

        $historial   = json_decode($prestamo['historial'] ?? '[]', true) ?: [];
        $historial[] = [
            'fecha'   => date('Y-m-d H:i:s'),
            'usuario' => $_SESSION['usuario_id'],
            'cambios' => [
                'monto_prestado'  => [$prestamo['monto_prestado'], $monto],
                'interes_valor'   => [$prestamo['interes_valor'],   $interes_v],
                'num_cuotas'      => [$prestamo['num_cuotas'],      $num_cuotas],
                'frecuencia_pago' => [$prestamo['frecuencia_pago'], $frecuencia],
            ]
        ];

        $db->prepare("UPDATE prestamos SET
            monto_prestado=?, tipo_interes=?, interes_valor=?, interes_calculado=?,
            total_a_pagar=?, frecuencia_pago=?, num_cuotas=?, valor_cuota=?,
            fecha_inicio=?, fecha_fin_esperada=?,
            editado_at=NOW(), editado_por=?, historial=?, updated_at=NOW()
            WHERE id=?")
        ->execute([
            $monto, $tipo_int, $interes_v, $interes_calc,
            $total, $frecuencia, $num_cuotas, $valor_cuota,
            $fecha_ini, $fechaFin,
            $_SESSION['usuario_id'], json_encode($historial),
            $prestamo_id
        ]);

        $db->prepare("DELETE FROM cuotas WHERE prestamo_id=?")->execute([$prestamo_id]);
        $omitir_dom_edit = !empty($data['omitir_domingos']) ? true : (bool)($prestamo['omitir_domingos'] ?? false);
        $db->prepare("UPDATE prestamos SET omitir_domingos=? WHERE id=?")->execute([$omitir_dom_edit ? 1 : 0, $prestamo_id]);
        generarCuotas($db, $prestamo_id, $cobro, $fecha_ini, $frecuencia, $num_cuotas, $valor_cuota, $total, $omitir_dom_edit);

        $stmtSaldo = $db->prepare("SELECT COALESCE(SUM(saldo_cuota), 0) FROM cuotas WHERE prestamo_id=? AND estado NOT IN ('pagado','anulado')");
        $stmtSaldo->execute([$prestamo_id]);
        $saldo_real     = (float)$stmtSaldo->fetchColumn();
        $saldo_correcto = $saldo_real > 0 ? $saldo_real : $total;

        $db->prepare("UPDATE prestamos SET saldo_pendiente=? WHERE id=?")->execute([$saldo_correcto, $prestamo_id]);

        // Actualizar movimiento de cobrador si cambió el monto
        if ((float)$prestamo['monto_prestado'] != $monto) {
            $db->prepare("UPDATE capital_movimientos
                SET monto=?, descripcion='Préstamo #{$prestamo_id} editado — nuevo monto'
                WHERE prestamo_id=? AND tipo='prestamo' AND origen='cobrador' AND es_entrada=0 AND (anulado=0 OR anulado IS NULL)")
            ->execute([$monto, $prestamo_id]);
        }

        $db->commit();
        echo json_encode(['ok'=>true,'msg'=>'Préstamo actualizado correctamente']);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
    }
    exit;
}