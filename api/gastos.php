<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok'=>false,'msg'=>'Método no permitido']); exit;
}

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$db     = getDB();
$cobro  = cobroActivo();
$action = $data['action'] ?? '';

// ============================================================
// CREAR GASTO (cobrador o admin)
// ============================================================
if ($action === 'crear') {
    $categoria_id = (int)($data['categoria_id'] ?? 0);
    $descripcion  = trim($data['descripcion']   ?? '');
    $monto        = (float)($data['monto']       ?? 0);
    $fecha        = $data['fecha']               ?? date('Y-m-d');

    if (!$categoria_id) { echo json_encode(['ok'=>false,'msg'=>'Selecciona una categoría']); exit; }
    if (!$descripcion)  { echo json_encode(['ok'=>false,'msg'=>'La descripción es obligatoria']); exit; }
    if ($monto <= 0)    { echo json_encode(['ok'=>false,'msg'=>'El monto debe ser mayor a 0']); exit; }
    if (!strtotime($fecha)) { echo json_encode(['ok'=>false,'msg'=>'Fecha inválida']); exit; }

    // Verificar que la categoría pertenece al cobro
    $chkCat = $db->prepare("SELECT id FROM categorias_gasto WHERE id=? AND cobro_id=? AND activa=1");
    $chkCat->execute([$categoria_id, $cobro]);
    if (!$chkCat->fetch()) {
        echo json_encode(['ok'=>false,'msg'=>'Categoría no encontrada']); exit;
    }

    // Admin: sus gastos se aprueban automáticamente
    $esAdmin  = in_array($_SESSION['rol'], ['admin','superadmin']);
    $estado   = $esAdmin ? 'aprobado' : 'pendiente';

    $db->prepare("INSERT INTO gastos_cobrador
        (cobro_id, usuario_id, fecha, descripcion, monto, categoria_id, estado)
        VALUES (?,?,?,?,?,?,?)")
    ->execute([
        $cobro, $_SESSION['usuario_id'], $fecha,
        $descripcion, $monto, $categoria_id, $estado
    ]);

    $msg = $esAdmin
        ? 'Gasto registrado y aprobado automáticamente'
        : 'Gasto registrado — pendiente de aprobación del administrador';

    echo json_encode(['ok'=>true,'msg'=>$msg]);

// ============================================================
// APROBAR / RECHAZAR GASTO (solo admin)
// ============================================================
} elseif ($action === 'aprobar' || $action === 'rechazar') {
    if (!in_array($_SESSION['rol'], ['admin','superadmin'])) {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }

    $id = (int)($data['id'] ?? 0);
    if (!$id) { echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

    $chk = $db->prepare("SELECT id FROM gastos_cobrador WHERE id=? AND cobro_id=?");
    $chk->execute([$id, $cobro]);
    if (!$chk->fetch()) {
        echo json_encode(['ok'=>false,'msg'=>'Gasto no encontrado']); exit;
    }

    $estado = $action === 'aprobar' ? 'aprobado' : 'rechazado';

    $db->prepare("UPDATE gastos_cobrador
        SET estado=?, aprobado_por=?, aprobado_at=NOW()
        WHERE id=?")
    ->execute([$estado, $_SESSION['usuario_id'], $id]);

    echo json_encode(['ok'=>true,'msg'=>$action === 'aprobar' ? 'Gasto aprobado' : 'Gasto rechazado']);

// ============================================================
// CREAR CATEGORÍA (solo admin)
// ============================================================
} elseif ($action === 'crear_categoria') {
    if (!in_array($_SESSION['rol'], ['admin','superadmin'])) {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }

    $nombre = trim($data['nombre'] ?? '');
    if (!$nombre) { echo json_encode(['ok'=>false,'msg'=>'El nombre es obligatorio']); exit; }

    // Verificar que no exista ya
    $chk = $db->prepare("SELECT id FROM categorias_gasto WHERE cobro_id=? AND nombre=?");
    $chk->execute([$cobro, $nombre]);
    if ($chk->fetch()) {
        echo json_encode(['ok'=>false,'msg'=>'Ya existe una categoría con ese nombre']); exit;
    }

    $db->prepare("INSERT INTO categorias_gasto (cobro_id, nombre) VALUES (?,?)")
       ->execute([$cobro, $nombre]);

    echo json_encode(['ok'=>true,'msg'=>'Categoría creada correctamente']);

// ============================================================
// ACTIVAR / INACTIVAR CATEGORÍA (solo admin)
// ============================================================
} elseif ($action === 'toggle_categoria') {
    if (!in_array($_SESSION['rol'], ['admin','superadmin'])) {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }

    $id     = (int)($data['id']     ?? 0);
    $activa = (int)($data['activa'] ?? 0);
    if (!$id) { echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

    $chk = $db->prepare("SELECT id FROM categorias_gasto WHERE id=? AND cobro_id=?");
    $chk->execute([$id, $cobro]);
    if (!$chk->fetch()) {
        echo json_encode(['ok'=>false,'msg'=>'Categoría no encontrada']); exit;
    }

    $db->prepare("UPDATE categorias_gasto SET activa=? WHERE id=?")
       ->execute([$activa, $id]);

    echo json_encode(['ok'=>true,'msg'=>$activa ? 'Categoría activada' : 'Categoría inactivada']);

// ============================================================
// LISTAR GASTOS (para liquidación)
// ============================================================
} elseif ($action === 'listar') {
    if (!in_array($_SESSION['rol'], ['admin','superadmin'])) {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }

    $fecha = $data['fecha'] ?? date('Y-m-d');

    $stmt = $db->prepare("
        SELECT g.*, cat.nombre AS categoria_nombre,
               u.nombre AS usuario_nombre
        FROM gastos_cobrador g
        LEFT JOIN categorias_gasto cat ON cat.id = g.categoria_id
        LEFT JOIN usuarios u ON u.id = g.usuario_id
        WHERE g.cobro_id=? AND g.fecha=?
        ORDER BY g.created_at ASC
    ");
    $stmt->execute([$cobro, $fecha]);
    $gastos = $stmt->fetchAll();

    $totalAprobados = array_sum(array_column(
        array_filter($gastos, fn($g) => $g['estado'] === 'aprobado'),
        'monto'
    ));

    echo json_encode(['ok'=>true,'gastos'=>$gastos,'total_aprobados'=>$totalAprobados]);

} else {
    echo json_encode(['ok'=>false,'msg'=>'Acción no reconocida']);
}

