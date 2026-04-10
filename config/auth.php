<?php
require_once __DIR__ . '/db.php';

session_start();

function requireLogin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (empty($_SESSION['usuario_id'])) {
        header('Location: /login.php');
        exit;
    }

    $db  = getDB();
    $uid = (int)$_SESSION['usuario_id'];
    $rol = $_SESSION['rol'] ?? '';

    // ── Verificar session_token — detecta si el admin cerró la sesión ──
    if ($rol === 'cobrador') {
        $stmtTok = $db->prepare("SELECT session_token FROM usuarios WHERE id=? AND activo=1");
        $stmtTok->execute([$uid]);
        $row = $stmtTok->fetch();

        // Usuario inactivo o token no coincide → sesión expirada
        if (!$row || $row['session_token'] !== ($_SESSION['session_token'] ?? '')) {
            session_destroy();
            header('Location: /login.php?msg=sesion_cerrada');
            exit;
        }

        // ── Verificar liquidación de hoy ─────────────────────────────
        $cobro = (int)($_SESSION['cobro_activo'] ?? 0);
        if ($cobro) {
            $stmtLiq = $db->prepare("
                SELECT id, cobrador_bloqueado FROM liquidaciones
                WHERE cobro_id = ? AND estado = 'borrador' AND fecha = CURDATE()
                LIMIT 1
            ");
            $stmtLiq->execute([$cobro]);
            $liqHoy = $stmtLiq->fetch();

            if (!$liqHoy) {
                // No hay liquidación abierta hoy
                session_destroy();
                header('Location: /login.php?msg=sin_base');
                exit;
            }

            if ($liqHoy['cobrador_bloqueado']) {
                // Admin bloqueó al cobrador para liquidar
                session_destroy();
                header('Location: /login.php?msg=bloqueado');
                exit;
            }
        }
    }
}

function isLoggedIn(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return !empty($_SESSION['usuario_id']);
}

function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array($_SESSION['rol'], $roles)) {
        include __DIR__ . '/../pages/403.php';
        exit;
    }
}

function canDo(string $permiso): bool {
    if ($_SESSION['rol'] === 'superadmin') return true;
    return !empty($_SESSION['permisos'][$permiso]);
}

function cobroActivo(): int {
    return (int)($_SESSION['cobro_activo'] ?? 0);
}

function setCobro(int $cobro_id): void {
    if ($_SESSION['rol'] === 'superadmin') {
        $_SESSION['cobro_activo'] = $cobro_id;
        cargarPermisos($cobro_id);
        return;
    }
    if (in_array($cobro_id, $_SESSION['cobros_asignados'] ?? [])) {
        $_SESSION['cobro_activo'] = $cobro_id;
        cargarPermisos($cobro_id);
    }
}

function cargarPermisos(int $cobro_id): void {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT
            puede_ver, puede_crear, puede_editar, puede_eliminar,
            puede_ver_capital, puede_registrar_pago,
            puede_ver_dashboard, puede_ver_deudores, puede_ver_prestamos,
            puede_ver_pagos, puede_ver_capital AS puede_ver_capital_seccion,
            puede_ver_cuentas, puede_ver_salidas, puede_ver_movimientos,
            puede_ver_proyeccion, puede_ver_reportes,
            puede_ver_configuracion, puede_ver_usuarios, puede_ver_cobros,
            puede_crear_deudor, puede_editar_deudor, puede_eliminar_deudor,
            puede_crear_prestamo, puede_editar_prestamo, puede_anular_prestamo,
            puede_anular_pago,
            puede_crear_capitalista, puede_editar_capitalista,
            puede_registrar_movimiento_capital, puede_ver_historial_capitalista,
            puede_crear_cuenta, puede_editar_cuenta,
            puede_crear_salida, puede_eliminar_salida,
            puede_exportar
        FROM usuario_cobro
        WHERE usuario_id = ? AND cobro_id = ?
    ");
    $stmt->execute([$_SESSION['usuario_id'], $cobro_id]);
    $permisos = $stmt->fetch();
    if ($permisos) {
        $permisos['puede_ver_capital'] = $permisos['puede_ver_capital_seccion'] ?? $permisos['puede_ver_capital'];
        unset($permisos['puede_ver_capital_seccion']);
    }
    $_SESSION['permisos'] = $permisos ?: [];
}

function login(string $email, string $password): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ? AND activo = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['ok' => false, 'msg' => 'Correo o contraseña incorrectos'];
    }

    // Si es cobrador — verificar que tiene liquidación abierta hoy
    // (solo bloquear login si ya hubo al menos una liquidación en este cobro)
    if ($user['rol'] === 'cobrador') {
        $stmt2 = $db->prepare("SELECT cobro_id FROM usuario_cobro WHERE usuario_id=? LIMIT 1");
        $stmt2->execute([$user['id']]);
        $cobro_id = (int)($stmt2->fetchColumn() ?: 0);

        if ($cobro_id) {
            $stmtLiq = $db->prepare("
                SELECT id, cobrador_bloqueado FROM liquidaciones
                WHERE cobro_id=? AND estado='borrador' AND fecha=CURDATE()
                LIMIT 1
            ");
            $stmtLiq->execute([$cobro_id]);
            $liqHoy = $stmtLiq->fetch();

            if ($liqHoy) {
                // Hay liquidación hoy — verificar si está bloqueado
                if ($liqHoy['cobrador_bloqueado']) {
                    return [
                        'ok'  => false,
                        'msg' => '🔒 El administrador ha bloqueado el acceso para procesar la liquidación. Espera a que termine.'
                    ];
                }
                // No bloqueado → puede entrar
            } else {
                // No hay liquidación abierta hoy
                $stmtHistorial = $db->prepare("SELECT COUNT(*) FROM liquidaciones WHERE cobro_id=?");
                $stmtHistorial->execute([$cobro_id]);
                $totalLiqs = (int)$stmtHistorial->fetchColumn();

                if ($totalLiqs > 0) {
                    return [
                        'ok'  => false,
                        'msg' => '⏳ No puedes ingresar — el administrador aún no ha abierto la liquidación del día.'
                    ];
                }
            }
        }
    }

    // Generar session_token único para cobradores
    $session_token = null;
    if ($user['rol'] === 'cobrador') {
        $session_token = bin2hex(random_bytes(32));
        $db->prepare("UPDATE usuarios SET session_token=?, ultimo_login=NOW() WHERE id=?")
           ->execute([$session_token, $user['id']]);
    } else {
        $db->prepare("UPDATE usuarios SET ultimo_login=NOW() WHERE id=?")->execute([$user['id']]);
    }

    // Cargar cobros asignados
    $stmt3 = $db->prepare("SELECT cobro_id FROM usuario_cobro WHERE usuario_id=?");
    $stmt3->execute([$user['id']]);
    $cobros = array_column($stmt3->fetchAll(), 'cobro_id');

    // Guardar sesión
    $_SESSION['usuario_id']      = $user['id'];
    $_SESSION['usuario_nombre']  = $user['nombre'];
    $_SESSION['rol']             = $user['rol'];
    $_SESSION['cobros_asignados']= $cobros;
    $_SESSION['session_token']   = $session_token;

    // Seleccionar cobro activo
    if ($user['rol'] === 'superadmin') {
        $stmt4 = $db->prepare("SELECT id FROM cobros WHERE activo=1 ORDER BY id LIMIT 1");
        $stmt4->execute();
        $primerCobro = $stmt4->fetchColumn();
        $_SESSION['cobro_activo'] = $primerCobro ?: 0;
        $_SESSION['permisos'] = [
            'puede_ver' => 1, 'puede_crear' => 1, 'puede_editar' => 1,
            'puede_eliminar' => 1, 'puede_ver_capital' => 1, 'puede_registrar_pago' => 1,
            'puede_ver_dashboard' => 1, 'puede_ver_deudores' => 1, 'puede_ver_prestamos' => 1,
            'puede_ver_pagos' => 1, 'puede_ver_cuentas' => 1,
            'puede_ver_salidas' => 1, 'puede_ver_movimientos' => 1, 'puede_ver_proyeccion' => 1,
            'puede_ver_reportes' => 1, 'puede_ver_configuracion' => 1,
            'puede_ver_usuarios' => 1, 'puede_ver_cobros' => 1,
            'puede_crear_deudor' => 1, 'puede_editar_deudor' => 1, 'puede_eliminar_deudor' => 1,
            'puede_crear_prestamo' => 1, 'puede_editar_prestamo' => 1, 'puede_anular_prestamo' => 1,
            'puede_anular_pago' => 1,
            'puede_crear_capitalista' => 1, 'puede_editar_capitalista' => 1,
            'puede_registrar_movimiento_capital' => 1, 'puede_ver_historial_capitalista' => 1,
            'puede_crear_cuenta' => 1, 'puede_editar_cuenta' => 1,
            'puede_crear_salida' => 1, 'puede_eliminar_salida' => 1,
            'puede_exportar' => 1,
        ];
    } elseif (count($cobros) === 1) {
        $_SESSION['cobro_activo'] = $cobros[0];
        cargarPermisos($cobros[0]);
    } else {
        $_SESSION['cobro_activo'] = 0;
    }

    return ['ok' => true, 'cobros' => count($cobros)];
}

function logout(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();

    // Si es cobrador, limpiar el session_token de la BD
    if (!empty($_SESSION['usuario_id']) && ($_SESSION['rol'] ?? '') === 'cobrador') {
        $db = getDB();
        $db->prepare("UPDATE usuarios SET session_token=NULL WHERE id=?")
           ->execute([$_SESSION['usuario_id']]);
    }

    session_destroy();
    header('Location: /login.php');
    exit;
}