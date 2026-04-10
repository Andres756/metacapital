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

// Estados que se consideran "activos" — usados en múltiples validaciones
const ESTADOS_ACTIVOS = ['activo','en_mora','en_acuerdo'];
const ESTADOS_CERRADOS = ['pagado','renovado','refinanciado','anulado','incobrable'];

// ============================================================
// GUARDAR / EDITAR DEUDOR
// ============================================================
if ($action === 'guardar' || !isset($data['action'])) {

    // FIX: castear a int antes de evaluar el permiso
    $id = (int)($data['id'] ?? 0);

    if (!canDo($id ? 'puede_editar_deudor' : 'puede_crear_deudor')) {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }

    $nombre = trim($data['nombre'] ?? '');
    if (!$nombre) { echo json_encode(['ok'=>false,'msg'=>'El nombre es obligatorio']); exit; }

    // FIX: validar comportamiento contra ENUM
    $comportamiento = in_array($data['comportamiento'] ?? '', ['bueno','regular','clavo'])
        ? $data['comportamiento'] : 'bueno';

    $campos = [
        'nombre','telefono','telefono_alt','documento','direccion',
        'lat','lng','place_id',  // ← agregar estos tres
        'barrio','codeudor_nombre','codeudor_telefono','codeudor_documento',
        'garantia_descripcion','notas'
    ];

    if ($id) {
        // FIX: verificar que el deudor pertenece al cobro activo
        $check = $db->prepare("
            SELECT d.id FROM deudores d
            JOIN deudor_cobro dc ON dc.deudor_id = d.id
            WHERE d.id=? AND dc.cobro_id=?
        ");
        $check->execute([$id, $cobro]);
        if (!$check->fetch()) {
            echo json_encode(['ok'=>false,'msg'=>'Deudor no encontrado']); exit;
        }

        $sets = implode(', ', array_map(fn($c) => "`$c` = ?", $campos));
        $sets .= ', `comportamiento` = ?';
        $vals  = array_map(fn($c) => trim($data[$c] ?? '') ?: null, $campos);
        $vals[] = $comportamiento;
        $vals[] = $id;

        $db->prepare("UPDATE deudores SET $sets, updated_at=NOW() WHERE id=?")->execute($vals);

        // Actualizar cobros asociados
        $cobrosSeleccionados = array_filter(array_map('intval', (array)($data['cobros'] ?? [])));
        if (!empty($cobrosSeleccionados)) {
            $actualesQ = $db->prepare("SELECT cobro_id FROM deudor_cobro WHERE deudor_id=?");
            $actualesQ->execute([$id]);
            $actuales = array_column($actualesQ->fetchAll(), 'cobro_id');

            foreach ($actuales as $cid) {
                if (!in_array($cid, $cobrosSeleccionados)) {
                    // FIX: incluir 'incobrable' en estados cerrados
                    $chkP = $db->prepare("SELECT COUNT(*) FROM prestamos WHERE deudor_id=? AND cobro_id=? AND estado NOT IN ('pagado','renovado','refinanciado','anulado','incobrable')");
                    $chkP->execute([$id, $cid]);
                    if ((int)$chkP->fetchColumn() === 0) {
                        $db->prepare("DELETE FROM deudor_cobro WHERE deudor_id=? AND cobro_id=?")->execute([$id, $cid]);
                    }
                }
            }
            foreach ($cobrosSeleccionados as $cid) {
                $db->prepare("INSERT IGNORE INTO deudor_cobro (deudor_id, cobro_id) VALUES (?,?)")->execute([$id, $cid]);
            }
        }

        echo json_encode(['ok'=>true,'msg'=>'Deudor actualizado correctamente']);

    } else {
        // FIX: usar transacción para crear deudor + deudor_cobro
        $db->beginTransaction();
        try {
            $cols = implode(', ', array_map(fn($c) => "`$c`", $campos)) . ', `comportamiento`, `cobro_id`';
            $placeholders = implode(', ', array_fill(0, count($campos) + 2, '?'));
            $vals = array_map(fn($c) => trim($data[$c] ?? '') ?: null, $campos);
            $vals[] = $comportamiento;
            $vals[] = $cobro;

            $db->prepare("INSERT INTO deudores ($cols) VALUES ($placeholders)")->execute($vals);
            $newId = (int)$db->lastInsertId();

            $cobrosSeleccionados = array_filter(array_map('intval', (array)($data['cobros'] ?? [])));
            if (empty($cobrosSeleccionados)) $cobrosSeleccionados = [$cobro];
            foreach ($cobrosSeleccionados as $cid) {
                $db->prepare("INSERT IGNORE INTO deudor_cobro (deudor_id, cobro_id) VALUES (?,?)")->execute([$newId, $cid]);
            }

            $db->commit();
            echo json_encode(['ok'=>true,'msg'=>'Deudor registrado correctamente','id'=>$newId]);

        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]);
        }
    }

// ============================================================
// REGISTRAR GESTIÓN
// ============================================================
} elseif ($action === 'gestion') {

    // FIX: permiso correcto para gestiones
    if (!canDo('puede_editar_deudor')) {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }

    $deudorId = (int)($data['deudor_id'] ?? 0);
    $nota     = trim($data['nota'] ?? '');
    if (!$deudorId || !$nota) {
        echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit;
    }

    // FIX: validar tipo y resultado contra ENUM
    $tiposValidos     = ['llamada','visita','whatsapp','acuerdo','nota'];
    $resultadosValidos= ['contactado','no_contesto','promesa_pago','sin_resultado','otro'];
    $tipo      = in_array($data['tipo'] ?? '', $tiposValidos)      ? $data['tipo']      : 'nota';
    $resultado = in_array($data['resultado'] ?? '', $resultadosValidos) ? $data['resultado'] : null;

    // FIX: validar fecha
    $fecha_gestion = $data['fecha_gestion'] ?? date('Y-m-d');
    if (!strtotime($fecha_gestion)) $fecha_gestion = date('Y-m-d');

    $stmtP = $db->prepare("SELECT id FROM prestamos WHERE deudor_id=? AND cobro_id=? AND estado IN ('activo','en_mora','en_acuerdo') ORDER BY id DESC LIMIT 1");
    $stmtP->execute([$deudorId, $cobro]);
    $prestamo_id = $stmtP->fetchColumn() ?: null;

    $db->prepare("INSERT INTO gestiones_cobro (cobro_id, prestamo_id, deudor_id, tipo, resultado, nota, fecha_gestion, usuario_id)
        VALUES (?,?,?,?,?,?,?,?)")
       ->execute([$cobro, $prestamo_id, $deudorId, $tipo, $resultado, $nota, $fecha_gestion, $_SESSION['usuario_id']]);

    echo json_encode(['ok'=>true,'msg'=>'Gestión registrada']);

// ============================================================
// ELIMINAR DEUDOR
// ============================================================
} elseif ($action === 'eliminar') {

    if (!canDo('puede_eliminar_deudor')) {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }

    $id = (int)($data['id'] ?? 0);
    if (!$id) { echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

    // FIX: verificar que el deudor pertenece al cobro activo
    $chk = $db->prepare("SELECT d.id FROM deudores d JOIN deudor_cobro dc ON dc.deudor_id=d.id WHERE d.id=? AND dc.cobro_id=?");
    $chk->execute([$id, $cobro]);
    if (!$chk->fetch()) {
        echo json_encode(['ok'=>false,'msg'=>'Deudor no encontrado']); exit;
    }

    // FIX: incluir 'incobrable' y 'anulado' en estados cerrados
    $check = $db->prepare("SELECT COUNT(*) FROM prestamos WHERE deudor_id=? AND estado NOT IN ('pagado','renovado','refinanciado','anulado','incobrable')");
    $check->execute([$id]);
    if ((int)$check->fetchColumn() > 0) {
        echo json_encode(['ok'=>false,'msg'=>'No se puede eliminar: tiene préstamos activos']); exit;
    }

    $db->prepare("UPDATE deudores SET activo=0, updated_at=NOW() WHERE id=?")->execute([$id]);
    echo json_encode(['ok'=>true,'msg'=>'Deudor eliminado']);

// ============================================================
// VINCULAR COBRO
// ============================================================
} elseif ($action === 'vincular_cobro') {
    if (!canDo('puede_editar_deudor')) {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }

    $deudor_id = (int)($data['deudor_id'] ?? 0);
    $cobro_id  = (int)($data['cobro_id']  ?? 0);
    if (!$deudor_id || !$cobro_id) {
        echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit;
    }

    // FIX: verificar que el cobro existe y está activo
    $chkC = $db->prepare("SELECT id FROM cobros WHERE id=? AND activo=1");
    $chkC->execute([$cobro_id]);
    if (!$chkC->fetch()) {
        echo json_encode(['ok'=>false,'msg'=>'Cobro no encontrado o inactivo']); exit;
    }

    // FIX: verificar que el deudor existe
    $chkD = $db->prepare("SELECT id FROM deudores WHERE id=?");
    $chkD->execute([$deudor_id]);
    if (!$chkD->fetch()) {
        echo json_encode(['ok'=>false,'msg'=>'Deudor no encontrado']); exit;
    }

    $db->prepare("INSERT IGNORE INTO deudor_cobro (deudor_id, cobro_id) VALUES (?,?)")
       ->execute([$deudor_id, $cobro_id]);
    echo json_encode(['ok'=>true,'msg'=>'Deudor vinculado al cobro']);

// ============================================================
// DESVINCULAR COBRO
// ============================================================
} elseif ($action === 'desvincular_cobro') {
    if (!canDo('puede_editar_deudor')) {
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']); exit;
    }

    $deudor_id = (int)($data['deudor_id'] ?? 0);
    $cobro_id  = (int)($data['cobro_id']  ?? 0);
    if (!$deudor_id || !$cobro_id) {
        echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit;
    }

    // FIX: incluir 'incobrable' en estados cerrados
    $check = $db->prepare("SELECT COUNT(*) FROM prestamos WHERE deudor_id=? AND cobro_id=? AND estado NOT IN ('pagado','renovado','refinanciado','anulado','incobrable')");
    $check->execute([$deudor_id, $cobro_id]);
    if ((int)$check->fetchColumn() > 0) {
        echo json_encode(['ok'=>false,'msg'=>'No se puede desvincular: tiene préstamos activos en ese cobro']); exit;
    }

    $db->prepare("DELETE FROM deudor_cobro WHERE deudor_id=? AND cobro_id=?")->execute([$deudor_id, $cobro_id]);
    echo json_encode(['ok'=>true,'msg'=>'Deudor desvinculado del cobro']);

} else {
    echo json_encode(['ok'=>false,'msg'=>'Acción no reconocida']);
}