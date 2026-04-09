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

// ============================================================
// REGISTRAR SALIDA
// ============================================================
if ($action === 'salida') {
    $categoria_id = (int)($data['categoria_id'] ?? 0);
    $descripcion  = trim($data['descripcion']   ?? '');
    $monto        = (float)($data['monto']       ?? 0);
    $fecha        = $data['fecha']               ?? date('Y-m-d');

    if (!$categoria_id) { echo json_encode(['ok'=>false,'msg'=>'Selecciona una categoría']); exit; }
    if (!$descripcion)  { echo json_encode(['ok'=>false,'msg'=>'La descripción es obligatoria']); exit; }
    if ($monto <= 0)    { echo json_encode(['ok'=>false,'msg'=>'El monto debe ser mayor a 0']); exit; }
    if (!strtotime($fecha)) { echo json_encode(['ok'=>false,'msg'=>'Fecha inválida']); exit; }

    // Verificar categoría pertenece al cobro
    $chk = $db->prepare("SELECT id FROM papeleria_categorias WHERE id=? AND cobro_id=? AND activa=1");
    $chk->execute([$categoria_id, $cobro]);
    if (!$chk->fetch()) {
        echo json_encode(['ok'=>false,'msg'=>'Categoría no encontrada']); exit;
    }

    // Verificar saldo suficiente
    $stmtE = $db->prepare("SELECT COALESCE(SUM(monto_papeleria),0) FROM papeleria WHERE cobro_id=?");
    $stmtE->execute([$cobro]);
    $entradas = (float)$stmtE->fetchColumn();

    $stmtS = $db->prepare("SELECT COALESCE(SUM(monto),0) FROM papeleria_salidas WHERE cobro_id=?");
    $stmtS->execute([$cobro]);
    $salidas = (float)$stmtS->fetchColumn();

    $saldo = $entradas - $salidas;
    if ($monto > $saldo) {
        echo json_encode([
            'ok'  => false,
            'msg' => 'Saldo insuficiente. Disponible: '.fmt($saldo).' · Requerido: '.fmt($monto)
        ]); exit;
    }

    $db->prepare("INSERT INTO papeleria_salidas
        (cobro_id, categoria_id, descripcion, monto, fecha, usuario_id)
        VALUES (?,?,?,?,?,?)")
    ->execute([
        $cobro, $categoria_id, $descripcion,
        $monto, $fecha, $_SESSION['usuario_id']
    ]);

    echo json_encode(['ok'=>true,'msg'=>'Salida registrada. Saldo restante: '.fmt($saldo - $monto)]);

// ============================================================
// CREAR CATEGORÍA
// ============================================================
} elseif ($action === 'crear_categoria') {
    $nombre = trim($data['nombre'] ?? '');
    if (!$nombre) { echo json_encode(['ok'=>false,'msg'=>'El nombre es obligatorio']); exit; }

    $chk = $db->prepare("SELECT id FROM papeleria_categorias WHERE cobro_id=? AND nombre=?");
    $chk->execute([$cobro, $nombre]);
    if ($chk->fetch()) {
        echo json_encode(['ok'=>false,'msg'=>'Ya existe una categoría con ese nombre']); exit;
    }

    $db->prepare("INSERT INTO papeleria_categorias (cobro_id, nombre) VALUES (?,?)")
       ->execute([$cobro, $nombre]);

    echo json_encode(['ok'=>true,'msg'=>'Categoría creada']);

// ============================================================
// HISTORIAL — entradas y salidas por rango de fechas
// ============================================================
} elseif ($action === 'historial') {
    $fecha_ini = $data['fecha_ini'] ?? '';
    $fecha_fin = $data['fecha_fin'] ?? '';

    if (!$fecha_ini || !$fecha_fin) {
        echo json_encode(['ok'=>false,'msg'=>'Fechas requeridas']); exit;
    }

    // Entradas (papelería cobrada) día a día
    $stmtE = $db->prepare("
        SELECT fecha, monto_papeleria, prestamo_id
        FROM papeleria
        WHERE cobro_id=? AND fecha BETWEEN ? AND ?
        ORDER BY fecha ASC
    ");
    $stmtE->execute([$cobro, $fecha_ini, $fecha_fin]);
    $entradas = $stmtE->fetchAll();

    // Salidas día a día
    $stmtS = $db->prepare("
        SELECT ps.fecha, ps.monto, ps.descripcion, cat.nombre AS categoria
        FROM papeleria_salidas ps
        LEFT JOIN papeleria_categorias cat ON cat.id = ps.categoria_id
        WHERE ps.cobro_id=? AND ps.fecha BETWEEN ? AND ?
        ORDER BY ps.fecha ASC
    ");
    $stmtS->execute([$cobro, $fecha_ini, $fecha_fin]);
    $salidas = $stmtS->fetchAll();

    echo json_encode([
        'ok'       => true,
        'entradas' => $entradas,
        'salidas'  => $salidas
    ]);

// ============================================================
// TOGGLE CATEGORÍA
// ============================================================
} elseif ($action === 'toggle_categoria') {
    $id     = (int)($data['id']     ?? 0);
    $activa = (int)($data['activa'] ?? 0);

    $chk = $db->prepare("SELECT id FROM papeleria_categorias WHERE id=? AND cobro_id=?");
    $chk->execute([$id, $cobro]);
    if (!$chk->fetch()) {
        echo json_encode(['ok'=>false,'msg'=>'Categoría no encontrada']); exit;
    }

    $db->prepare("UPDATE papeleria_categorias SET activa=? WHERE id=?")->execute([$activa, $id]);
    echo json_encode(['ok'=>true,'msg'=>$activa ? 'Categoría activada' : 'Categoría inactivada']);

} else {
    echo json_encode(['ok'=>false,'msg'=>'Acción no reconocida']);
}