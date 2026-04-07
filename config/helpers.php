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
 * Saldo real de una cuenta = entradas - salidas en capital_movimientos
 */
function getSaldoCuenta(PDO $db, int $cuenta_id, int $cobro_id): float {
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(CASE WHEN es_entrada=1 THEN monto ELSE -monto END), 0)
        FROM capital_movimientos
        WHERE cuenta_id=? AND cobro_id=? AND tipo != 'prestamo_proporcional' AND anulado=0
    ");
    $stmt->execute([$cuenta_id, $cobro_id]);
    return (float)$stmt->fetchColumn();
}

/**
 * Saldo real de un capitalista = entradas - salidas en capital_movimientos
 */
function getSaldoCapitalista(PDO $db, int $capitalista_id): float {
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(CASE WHEN es_entrada=1 THEN monto ELSE -monto END), 0)
        FROM capital_movimientos
        WHERE capitalista_id=? AND anulado=0
    ");
    $stmt->execute([$capitalista_id]);
    return (float)$stmt->fetchColumn();
}

/**
 * Valida saldo de cuenta — sale con JSON error si no alcanza
 */
function validarSaldoCuenta(PDO $db, int $cuenta_id, int $cobro_id, float $monto): void {
    $stmtC = $db->prepare("SELECT nombre FROM cuentas WHERE id=? AND cobro_id=? AND activa=1");
    $stmtC->execute([$cuenta_id, $cobro_id]);
    $cuenta = $stmtC->fetch();
    if (!$cuenta) {
        echo json_encode(['ok'=>false,'msg'=>'Cuenta no encontrada']); exit;
    }
    $saldo = getSaldoCuenta($db, $cuenta_id, $cobro_id);
    if ($saldo < $monto) {
        echo json_encode([
            'ok'  => false,
            'msg' => 'Saldo insuficiente en "'.$cuenta['nombre'].'". Disponible: '.fmt($saldo).' · Requerido: '.fmt($monto)
        ]); exit;
    }
}

/**
 * Valida saldo de capitalista — sale con JSON error si no alcanza
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
            'msg' => 'Saldo insuficiente de "'.$nombre.'". Disponible: '.fmt($saldo).' · Requerido: '.fmt($monto)
        ]); exit;
    }
}