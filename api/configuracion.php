<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();

$db     = getDB();
$cobro  = cobroActivo();
$method = $_SERVER['REQUEST_METHOD'];

// GET — exportar
if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    if ($action === 'exportar') {
        // Generar CSV simple con los datos principales
        if (!canDo('puede_exportar')) {
            http_response_code(403); echo 'Sin permiso'; exit;
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="meta_capital_backup_'.date('Y-m-d').'.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

        // Deudores
        fputcsv($out, ['=== DEUDORES ===']);
        fputcsv($out, ['ID','Nombre','Teléfono','Dirección','Estado','Creado']);
        $rows = $db->prepare("SELECT id,nombre,telefono,direccion,estado,created_at FROM deudores WHERE cobro_id=?");
        $rows->execute([$cobro]);
        foreach ($rows->fetchAll() as $r) fputcsv($out, $r);

        fputcsv($out, []);
        // Préstamos
        fputcsv($out, ['=== PRÉSTAMOS ===']);
        fputcsv($out, ['ID','Deudor','Capital','Interés','Total','Saldo','Estado','Inicio','Cuotas']);
        $rows = $db->prepare("SELECT p.id,d.nombre,p.monto_prestado,p.interes_calculado,p.total_a_pagar,p.saldo_pendiente,p.estado,p.fecha_inicio,p.num_cuotas FROM prestamos p JOIN deudores d ON d.id=p.deudor_id WHERE p.cobro_id=?");
        $rows->execute([$cobro]);
        foreach ($rows->fetchAll() as $r) fputcsv($out, $r);

        fputcsv($out, []);
        // Movimientos
        fputcsv($out, ['=== MOVIMIENTOS ===']);
        fputcsv($out, ['ID','Tipo','Entrada','Monto','Cuenta','Capitalista','Descripción','Fecha']);
        $rows = $db->prepare("SELECT m.id,m.tipo,m.es_entrada,m.monto,c.nombre,cap.nombre,m.descripcion,m.fecha FROM capital_movimientos m LEFT JOIN cuentas c ON c.id=m.cuenta_id LEFT JOIN capitalistas cap ON cap.id=m.capitalista_id WHERE m.cobro_id=? ORDER BY m.fecha");
        $rows->execute([$cobro]);
        foreach ($rows->fetchAll() as $r) fputcsv($out, $r);

        fclose($out);
        exit;
    }
    http_response_code(400); echo 'Acción no válida'; exit;
}

// POST
header('Content-Type: application/json');
$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $data['action'] ?? '';

// ---- EDITAR COBRO ----
if ($action === 'editar_cobro') {
    if ($_SESSION['rol'] !== 'superadmin' && $_SESSION['rol'] !== 'admin') {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }
    $nombre  = trim($data['nombre'] ?? '');
    $cobro_id = isset($data['id']) && $data['id'] ? (int)$data['id'] : $cobro;
    if (!$nombre) { echo json_encode(['ok'=>false,'msg'=>'El nombre es obligatorio']); exit; }

    $db->prepare("UPDATE cobros SET nombre=?, descripcion=?, telefono=?, direccion=?, updated_at=NOW() WHERE id=?")
       ->execute([
           $nombre,
           trim($data['descripcion'] ?? '') ?: null,
           trim($data['telefono']    ?? '') ?: null,
           trim($data['direccion']   ?? '') ?: null,
           $cobro_id
       ]);
    echo json_encode(['ok'=>true,'msg'=>'Cobro actualizado']);

// ---- MI PERFIL ----
} elseif ($action === 'perfil') {
    $uid    = $_SESSION['usuario_id'];
    $nombre = trim($data['nombre'] ?? '');
    $email  = trim($data['email']  ?? '');
    $pass   = $data['password'] ?? '';

    if (!$nombre || !$email) {
        echo json_encode(['ok'=>false,'msg'=>'Nombre y email son obligatorios']); exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['ok'=>false,'msg'=>'Email inválido']); exit;
    }

    // Email único
    $check = $db->prepare("SELECT id FROM usuarios WHERE email=? AND id!=?");
    $check->execute([$email, $uid]);
    if ($check->fetch()) {
        echo json_encode(['ok'=>false,'msg'=>'Ese email ya está en uso']); exit;
    }

    if ($pass) {
        if (strlen($pass) < 6) {
            echo json_encode(['ok'=>false,'msg'=>'La contraseña debe tener al menos 6 caracteres']); exit;
        }
        $db->prepare("UPDATE usuarios SET nombre=?, email=?, password_hash=?, updated_at=NOW() WHERE id=?")
           ->execute([$nombre, $email, password_hash($pass, PASSWORD_DEFAULT), $uid]);
    } else {
        $db->prepare("UPDATE usuarios SET nombre=?, email=?, updated_at=NOW() WHERE id=?")
           ->execute([$nombre, $email, $uid]);
    }
    $_SESSION['usuario_nombre'] = $nombre;
    echo json_encode(['ok'=>true,'msg'=>'Perfil actualizado']);

// ---- CREAR COBRO ----
} elseif ($action === 'crear_cobro') {
    if ($_SESSION['rol'] !== 'superadmin') {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }
    $nombre = trim($data['nombre'] ?? '');
    if (!$nombre) { echo json_encode(['ok'=>false,'msg'=>'El nombre es obligatorio']); exit; }

    $db->prepare("INSERT INTO cobros (nombre, descripcion, telefono, direccion) VALUES (?,?,?,?)")
       ->execute([
           $nombre,
           trim($data['descripcion'] ?? '') ?: null,
           trim($data['telefono']    ?? '') ?: null,
           trim($data['direccion']   ?? '') ?: null,
       ]);
    $nuevo_id = $db->lastInsertId();

    // Asignar superadmin al nuevo cobro
    $db->prepare("INSERT INTO usuario_cobro (
                    usuario_id, cobro_id,
                    puede_ver, puede_crear, puede_editar, puede_eliminar,
                    puede_ver_capital, puede_registrar_pago,
                    puede_ver_dashboard, puede_ver_deudores, puede_ver_prestamos,
                    puede_ver_pagos, puede_ver_cuentas, puede_ver_salidas,
                    puede_ver_movimientos, puede_ver_proyeccion, puede_ver_reportes,
                    puede_ver_configuracion, puede_ver_usuarios, puede_ver_cobros,
                    puede_crear_deudor, puede_editar_deudor, puede_eliminar_deudor,
                    puede_crear_prestamo, puede_editar_prestamo, puede_anular_prestamo,
                    puede_anular_pago,
                    puede_crear_capitalista, puede_editar_capitalista,
                    puede_registrar_movimiento_capital, puede_ver_historial_capitalista,
                    puede_crear_cuenta, puede_editar_cuenta,
                    puede_crear_salida, puede_eliminar_salida,
                    puede_exportar
                  ) VALUES (?,?,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1)")
       ->execute([$_SESSION['usuario_id'], $nuevo_id]);

    echo json_encode(['ok'=>true,'msg'=>'Cobro creado','id'=>$nuevo_id]);

// ---- CAMBIAR COBRO ACTIVO ----
} elseif ($action === 'cambiar_cobro') {
    if ($_SESSION['rol'] !== 'superadmin') {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }
    $cobro_id = (int)($data['cobro_id'] ?? 0);
    $check = $db->prepare("SELECT id FROM cobros WHERE id=? AND activo=1");
    $check->execute([$cobro_id]);
    if (!$check->fetch()) {
        echo json_encode(['ok'=>false,'msg'=>'Cobro no encontrado']); exit;
    }
    $_SESSION['cobro_activo'] = $cobro_id;
    $_SESSION['permisos'] = [
        // Legacy
        'puede_ver'=>1,'puede_crear'=>1,'puede_editar'=>1,
        'puede_eliminar'=>1,'puede_ver_capital'=>1,'puede_registrar_pago'=>1,
        // Vistas
        'puede_ver_dashboard'=>1,'puede_ver_deudores'=>1,'puede_ver_prestamos'=>1,
        'puede_ver_pagos'=>1,'puede_ver_cuentas'=>1,'puede_ver_salidas'=>1,
        'puede_ver_movimientos'=>1,'puede_ver_proyeccion'=>1,'puede_ver_reportes'=>1,
        'puede_ver_configuracion'=>1,'puede_ver_usuarios'=>1,'puede_ver_cobros'=>1,
        // Acciones
        'puede_crear_deudor'=>1,'puede_editar_deudor'=>1,'puede_eliminar_deudor'=>1,
        'puede_crear_prestamo'=>1,'puede_editar_prestamo'=>1,'puede_anular_prestamo'=>1,
        'puede_anular_pago'=>1,
        'puede_crear_capitalista'=>1,'puede_editar_capitalista'=>1,
        'puede_registrar_movimiento_capital'=>1,'puede_ver_historial_capitalista'=>1,
        'puede_crear_cuenta'=>1,'puede_editar_cuenta'=>1,
        'puede_crear_salida'=>1,'puede_eliminar_salida'=>1,
        'puede_exportar'=>1,
    ];
    echo json_encode(['ok'=>true,'msg'=>'Cobro cambiado']);

// ---- VACIAR DATOS ----
} elseif ($action === 'vaciar') {
    if ($_SESSION['rol'] !== 'superadmin') {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }
    try {
        $db->beginTransaction();
        $db->prepare("DELETE FROM gestiones_cobro WHERE cobro_id=?")->execute([$cobro]);
        $db->prepare("DELETE FROM capital_movimientos WHERE cobro_id=?")->execute([$cobro]);
        $db->prepare("DELETE FROM pagos WHERE cobro_id=?")->execute([$cobro]);
        $db->prepare("DELETE FROM cuotas WHERE cobro_id=?")->execute([$cobro]);
        $db->prepare("DELETE FROM prestamos WHERE cobro_id=?")->execute([$cobro]);
        $db->prepare("DELETE FROM deudores WHERE cobro_id=?")->execute([$cobro]);
        $db->prepare("DELETE FROM capitalistas WHERE cobro_id=?")->execute([$cobro]);
        $db->prepare("DELETE FROM cuentas WHERE cobro_id=?")->execute([$cobro]);
        $db->commit();
        echo json_encode(['ok'=>true,'msg'=>'Datos del cobro vaciados correctamente']);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
    }


} elseif ($action === 'toggle_cobro') {
    if ($_SESSION['rol'] !== 'superadmin') {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }
    $id     = (int)($data['id']     ?? 0);
    $activo = (int)($data['activo'] ?? 0);
    if (!$id) { echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }
    $db->prepare("UPDATE cobros SET activo=?, updated_at=NOW() WHERE id=?")->execute([$activo, $id]);
    echo json_encode(['ok'=>true,'msg'=>$activo ? 'Cobro activado' : 'Cobro desactivado']);

} else {
    echo json_encode(['ok'=>false,'msg'=>'Acción no reconocida']);
}