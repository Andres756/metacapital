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
$action= $data['action'] ?? 'guardar';

// ---- GUARDAR / EDITAR DEUDOR ----
if ($action === 'guardar' || !isset($data['action'])) {

    if (!canDo($data['id'] ? 'puede_editar_deudor' : 'puede_crear_deudor')) {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }

    $nombre = trim($data['nombre'] ?? '');
    if (!$nombre) { echo json_encode(['ok'=>false,'msg'=>'El nombre es obligatorio']); exit; }

    $campos = [
        'nombre','telefono','telefono_alt','documento','direccion',
        'barrio','codeudor_nombre','codeudor_telefono','codeudor_documento',
        'garantia_descripcion','comportamiento','notas'
    ];

    $id = (int)($data['id'] ?? 0);

    if ($id) {
        // Verificar que pertenece al cobro
        $check = $db->prepare("SELECT id FROM deudores WHERE id=?");
        $check->execute([$id]);
        if (!$check->fetch()) { echo json_encode(['ok'=>false,'msg'=>'Deudor no encontrado']); exit; }

        $sets = implode(', ', array_map(fn($c) => "`$c` = ?", $campos));
        $vals = array_map(fn($c) => trim($data[$c] ?? '') ?: null, $campos);
        $vals[] = $id;
        $db->prepare("UPDATE deudores SET $sets, updated_at=NOW() WHERE id=?")->execute($vals);

        // Actualizar cobros: quitar los que ya no están, agregar los nuevos
        // (solo quitar cobros donde no tenga préstamos activos)
        $cobrosSeleccionados = array_filter(array_map('intval', (array)($data['cobros'] ?? [])));
        if (!empty($cobrosSeleccionados)) {
            // Obtener cobros actuales
            $actualesQ = $db->prepare("SELECT cobro_id FROM deudor_cobro WHERE deudor_id=?");
            $actualesQ->execute([$id]);
            $actuales = array_column($actualesQ->fetchAll(), 'cobro_id');

            // Quitar cobros desmarcados (solo si no tiene préstamos activos ahí)
            foreach ($actuales as $cid) {
                if (!in_array($cid, $cobrosSeleccionados)) {
                    $chkP = $db->prepare("SELECT COUNT(*) FROM prestamos WHERE deudor_id=? AND cobro_id=? AND estado NOT IN ('pagado','renovado','refinanciado','anulado')");
                    $chkP->execute([$id, $cid]);
                    if ((int)$chkP->fetchColumn() === 0) {
                        $db->prepare("DELETE FROM deudor_cobro WHERE deudor_id=? AND cobro_id=?")->execute([$id, $cid]);
                    }
                }
            }
            // Agregar cobros nuevos
            foreach ($cobrosSeleccionados as $cid) {
                $db->prepare("INSERT IGNORE INTO deudor_cobro (deudor_id, cobro_id) VALUES (?,?)")->execute([$id, $cid]);
            }
        }

        echo json_encode(['ok'=>true,'msg'=>'Deudor actualizado correctamente']);

    } else {
        $placeholders = implode(', ', array_fill(0, count($campos)+1, '?'));
        $cols = implode(', ', array_map(fn($c) => "`$c`", $campos)) . ', `cobro_id`';
        $vals = array_map(fn($c) => trim($data[$c] ?? '') ?: null, $campos);
        $vals[] = $cobro; // cobro de origen
        $db->prepare("INSERT INTO deudores ($cols) VALUES ($placeholders)")->execute($vals);
        $newId = $db->lastInsertId();

        // Asociar al cobro actual y a cobros adicionales seleccionados
        $cobrosSeleccionados = array_filter(array_map('intval', (array)($data['cobros'] ?? [])));
        if (empty($cobrosSeleccionados)) $cobrosSeleccionados = [$cobro];
        foreach ($cobrosSeleccionados as $cid) {
            $db->prepare("INSERT IGNORE INTO deudor_cobro (deudor_id, cobro_id) VALUES (?,?)")
               ->execute([$newId, $cid]);
        }
        echo json_encode(['ok'=>true,'msg'=>'Deudor registrado correctamente','id'=>$newId]);
    }

// ---- REGISTRAR GESTIÓN ----
} elseif ($action === 'gestion') {

    if (!canDo('puede_crear_deudor')) { echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit; }

    $deudorId = (int)($data['deudor_id'] ?? 0);
    $nota     = trim($data['nota'] ?? '');
    if (!$deudorId || !$nota) { echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit; }

    // Buscar un préstamo activo del deudor para asociar
    $stmtP = $db->prepare("SELECT id FROM prestamos WHERE deudor_id=? AND cobro_id=? AND estado IN ('activo','en_mora','en_acuerdo') ORDER BY id DESC LIMIT 1");
    $stmtP->execute([$deudorId, $cobro]);
    $prestamo_id = $stmtP->fetchColumn() ?: 0;

    $db->prepare("INSERT INTO gestiones_cobro (cobro_id, prestamo_id, deudor_id, tipo, resultado, nota, fecha_gestion, usuario_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)")->execute([
        $cobro, $prestamo_id, $deudorId,
        $data['tipo'] ?? 'nota',
        $data['resultado'] ?: null,
        $nota,
        $data['fecha_gestion'] ?? date('Y-m-d'),
        $_SESSION['usuario_id']
    ]);
    echo json_encode(['ok'=>true,'msg'=>'Gestión registrada']);

// ---- ELIMINAR DEUDOR ----
} elseif ($action === 'eliminar') {

    if (!canDo('puede_eliminar_deudor')) { echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit; }

    $id = (int)($data['id'] ?? 0);
    // Verificar que no tenga préstamos activos
    $check = $db->prepare("SELECT COUNT(*) FROM prestamos WHERE deudor_id=? AND estado NOT IN ('pagado','renovado','refinanciado')");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['ok'=>false,'msg'=>'No se puede eliminar: tiene préstamos activos']); exit;
    }
    $db->prepare("UPDATE deudores SET activo=0 WHERE id=?")->execute([$id]);
    echo json_encode(['ok'=>true,'msg'=>'Deudor eliminado']);

} elseif ($action === 'vincular_cobro') {
    // Agregar deudor existente a otro cobro
    if (!canDo('puede_editar_deudor')) { echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit; }
    $deudor_id = (int)($data['deudor_id'] ?? 0);
    $cobro_id  = (int)($data['cobro_id']  ?? 0);
    if (!$deudor_id || !$cobro_id) { echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit; }
    $db->prepare("INSERT IGNORE INTO deudor_cobro (deudor_id, cobro_id) VALUES (?,?)")
       ->execute([$deudor_id, $cobro_id]);
    echo json_encode(['ok'=>true,'msg'=>'Deudor vinculado al cobro']);

} elseif ($action === 'desvincular_cobro') {
    if (!canDo('puede_editar_deudor')) { echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit; }
    $deudor_id = (int)($data['deudor_id'] ?? 0);
    $cobro_id  = (int)($data['cobro_id']  ?? 0);
    // No desvincular si tiene préstamos activos en ese cobro
    $check = $db->prepare("SELECT COUNT(*) FROM prestamos WHERE deudor_id=? AND cobro_id=? AND estado NOT IN ('pagado','renovado','refinanciado','anulado')");
    $check->execute([$deudor_id, $cobro_id]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['ok'=>false,'msg'=>'No se puede desvincular: tiene préstamos activos en ese cobro']); exit;
    }
    $db->prepare("DELETE FROM deudor_cobro WHERE deudor_id=? AND cobro_id=?")->execute([$deudor_id, $cobro_id]);
    echo json_encode(['ok'=>true,'msg'=>'Deudor desvinculado del cobro']);

} else {
    echo json_encode(['ok'=>false,'msg'=>'Acción no reconocida']);
}