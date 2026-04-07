<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
header('Content-Type: application/json');

// Solo superadmin puede gestionar usuarios
if ($_SESSION['rol'] !== 'superadmin') {
    echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok'=>false,'msg'=>'Método no permitido']); exit;
}

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$db     = getDB();
$cobro  = cobroActivo();
$action = $data['action'] ?? '';

// ---- CREAR USUARIO ----
if ($action === 'crear') {
    $nombre = trim($data['nombre'] ?? '');
    $email  = trim($data['email']  ?? '');
    $pass   = $data['password'] ?? '';
    $rol    = in_array($data['rol']??'', ['superadmin','admin','cobrador','consulta'])
              ? $data['rol'] : 'cobrador';

    if (!$nombre || !$email) {
        echo json_encode(['ok'=>false,'msg'=>'Nombre y email son obligatorios']); exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['ok'=>false,'msg'=>'Email inválido']); exit;
    }
    if (strlen($pass) < 6) {
        echo json_encode(['ok'=>false,'msg'=>'La contraseña debe tener al menos 6 caracteres']); exit;
    }

    // Verificar email único
    $check = $db->prepare("SELECT id FROM usuarios WHERE email=?");
    $check->execute([$email]);
    if ($check->fetch()) {
        echo json_encode(['ok'=>false,'msg'=>'Ya existe un usuario con ese email']); exit;
    }

    $db->beginTransaction();
    try {
        $db->prepare("INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES (?,?,?,?)")
           ->execute([$nombre, $email, password_hash($pass, PASSWORD_DEFAULT), $rol]);
        $uid = $db->lastInsertId();

        // Asignar al cobro activo si se marcó
        if (!empty($data['asignar_cobro']) && $cobro) {
            $db->prepare("INSERT INTO usuario_cobro
                (usuario_id, cobro_id, puede_ver, puede_registrar_pago,
                 puede_ver_dashboard, puede_ver_deudores, puede_ver_prestamos, puede_ver_pagos,
                 puede_ver_proyeccion, puede_ver_reportes)
                VALUES (?,?,1,1,1,1,1,1,1,1)")
               ->execute([$uid, $cobro]);
        }

        $db->commit();
        echo json_encode(['ok'=>true,'msg'=>'Usuario creado correctamente','id'=>$uid]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
    }

// ---- EDITAR USUARIO ----
} elseif ($action === 'editar') {
    $id     = (int)($data['id'] ?? 0);
    $nombre = trim($data['nombre'] ?? '');
    $email  = trim($data['email']  ?? '');
    $pass   = $data['password'] ?? '';
    $rol    = in_array($data['rol']??'', ['superadmin','admin','cobrador','consulta'])
              ? $data['rol'] : 'cobrador';

    if (!$id || !$nombre || !$email) {
        echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['ok'=>false,'msg'=>'Email inválido']); exit;
    }

    // Verificar email único (excluyendo el mismo usuario)
    $check = $db->prepare("SELECT id FROM usuarios WHERE email=? AND id!=?");
    $check->execute([$email, $id]);
    if ($check->fetch()) {
        echo json_encode(['ok'=>false,'msg'=>'Ya existe otro usuario con ese email']); exit;
    }

    if ($pass) {
        if (strlen($pass) < 6) {
            echo json_encode(['ok'=>false,'msg'=>'La contraseña debe tener al menos 6 caracteres']); exit;
        }
        $db->prepare("UPDATE usuarios SET nombre=?, email=?, password_hash=?, rol=?, updated_at=NOW() WHERE id=?")
           ->execute([$nombre, $email, password_hash($pass, PASSWORD_DEFAULT), $rol, $id]);
    } else {
        $db->prepare("UPDATE usuarios SET nombre=?, email=?, rol=?, updated_at=NOW() WHERE id=?")
           ->execute([$nombre, $email, $rol, $id]);
    }
    echo json_encode(['ok'=>true,'msg'=>'Usuario actualizado']);

// ---- PERMISOS ----
} elseif ($action === 'permisos') {
    $uid      = (int)($data['usuario_id'] ?? 0);
    $cobro_id = (int)($data['cobro_id']   ?? $cobro);

    if (!$uid || !$cobro_id) {
        echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit;
    }

    // ── Permisos legacy ──────────────────────────────────────────
    $perms = [
        'puede_ver'            => (int)($data['puede_ver']            ?? 0),
        'puede_crear'          => (int)($data['puede_crear']          ?? 0),
        'puede_editar'         => (int)($data['puede_editar']         ?? 0),
        'puede_eliminar'       => (int)($data['puede_eliminar']       ?? 0),
        'puede_ver_capital'    => (int)($data['puede_ver_capital']    ?? 0),
        'puede_registrar_pago' => (int)($data['puede_registrar_pago'] ?? 0),
        // ── Vistas ───────────────────────────────────────────────
        'puede_ver_dashboard'     => (int)($data['puede_ver_dashboard']     ?? 0),
        'puede_ver_deudores'      => (int)($data['puede_ver_deudores']      ?? 0),
        'puede_ver_prestamos'     => (int)($data['puede_ver_prestamos']     ?? 0),
        'puede_ver_pagos'         => (int)($data['puede_ver_pagos']         ?? 0),
        'puede_ver_cuentas'       => (int)($data['puede_ver_cuentas']       ?? 0),
        'puede_ver_salidas'       => (int)($data['puede_ver_salidas']       ?? 0),
        'puede_ver_movimientos'   => (int)($data['puede_ver_movimientos']   ?? 0),
        'puede_ver_proyeccion'    => (int)($data['puede_ver_proyeccion']    ?? 0),
        'puede_ver_reportes'      => (int)($data['puede_ver_reportes']      ?? 0),
        'puede_ver_configuracion' => (int)($data['puede_ver_configuracion'] ?? 0),
        'puede_ver_usuarios'      => (int)($data['puede_ver_usuarios']      ?? 0),
        'puede_ver_cobros'        => (int)($data['puede_ver_cobros']        ?? 0),
        // ── Deudores ─────────────────────────────────────────────
        'puede_crear_deudor'    => (int)($data['puede_crear_deudor']    ?? 0),
        'puede_editar_deudor'   => (int)($data['puede_editar_deudor']   ?? 0),
        'puede_eliminar_deudor' => (int)($data['puede_eliminar_deudor'] ?? 0),
        // ── Préstamos ────────────────────────────────────────────
        'puede_crear_prestamo'  => (int)($data['puede_crear_prestamo']  ?? 0),
        'puede_editar_prestamo' => (int)($data['puede_editar_prestamo'] ?? 0),
        'puede_anular_prestamo' => (int)($data['puede_anular_prestamo'] ?? 0),
        // ── Pagos ────────────────────────────────────────────────
        'puede_anular_pago'     => (int)($data['puede_anular_pago']     ?? 0),
        // ── Capital ──────────────────────────────────────────────
        'puede_crear_capitalista'           => (int)($data['puede_crear_capitalista']           ?? 0),
        'puede_editar_capitalista'          => (int)($data['puede_editar_capitalista']          ?? 0),
        'puede_registrar_movimiento_capital'=> (int)($data['puede_registrar_movimiento_capital']?? 0),
        'puede_ver_historial_capitalista'   => (int)($data['puede_ver_historial_capitalista']   ?? 0),
        // ── Cuentas ──────────────────────────────────────────────
        'puede_crear_cuenta'    => (int)($data['puede_crear_cuenta']    ?? 0),
        'puede_editar_cuenta'   => (int)($data['puede_editar_cuenta']   ?? 0),
        // ── Salidas ──────────────────────────────────────────────
        'puede_crear_salida'    => (int)($data['puede_crear_salida']    ?? 0),
        'puede_eliminar_salida' => (int)($data['puede_eliminar_salida'] ?? 0),
        // ── Sistema ──────────────────────────────────────────────
        'puede_exportar'        => (int)($data['puede_exportar']        ?? 0),
    ];

    $cols   = implode(', ', array_keys($perms));
    $setStr = implode(', ', array_map(fn($k) => "$k=?", array_keys($perms)));
    $vals   = array_values($perms);

    // INSERT OR UPDATE
    $check = $db->prepare("SELECT id FROM usuario_cobro WHERE usuario_id=? AND cobro_id=?");
    $check->execute([$uid, $cobro_id]);

    if ($check->fetch()) {
        $db->prepare("UPDATE usuario_cobro SET $setStr WHERE usuario_id=? AND cobro_id=?")
           ->execute([...$vals, $uid, $cobro_id]);
    } else {
        $placeholders = implode(',', array_fill(0, count($perms) + 2, '?'));
        $db->prepare("INSERT INTO usuario_cobro (usuario_id, cobro_id, $cols) VALUES ($placeholders)")
           ->execute([$uid, $cobro_id, ...$vals]);
    }
    echo json_encode(['ok'=>true,'msg'=>'Permisos actualizados']);

// ---- ACTIVAR / DESACTIVAR ----
} elseif (in_array($action, ['activar','desactivar'])) {
    $id = (int)($data['id'] ?? 0);
    if (!$id) { echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }
    if ($id == $_SESSION['usuario_id']) {
        echo json_encode(['ok'=>false,'msg'=>'No puedes desactivarte a ti mismo']); exit;
    }
    $activo = $action === 'activar' ? 1 : 0;
    $db->prepare("UPDATE usuarios SET activo=?, updated_at=NOW() WHERE id=?")->execute([$activo, $id]);
    echo json_encode(['ok'=>true,'msg'=>$activo ? 'Usuario activado' : 'Usuario desactivado']);

} else {
    echo json_encode(['ok'=>false,'msg'=>'Acción no reconocida']);
}