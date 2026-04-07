<?php
require_once __DIR__ . '/db.php';

session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['usuario_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireRole(array $roles): void {
    requireLogin();
    if (!in_array($_SESSION['rol'], $roles)) {
        http_response_code(403);
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
    // Verificar que el usuario tiene acceso a ese cobro
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
            -- Permisos legacy (se mantienen para compatibilidad)
            puede_ver, puede_crear, puede_editar, puede_eliminar,
            puede_ver_capital, puede_registrar_pago,

            -- Vistas
            puede_ver_dashboard, puede_ver_deudores, puede_ver_prestamos,
            puede_ver_pagos, puede_ver_capital AS puede_ver_capital_seccion,
            puede_ver_cuentas, puede_ver_salidas, puede_ver_movimientos,
            puede_ver_proyeccion, puede_ver_reportes,
            puede_ver_configuracion, puede_ver_usuarios, puede_ver_cobros,

            -- Acciones — Deudores
            puede_crear_deudor, puede_editar_deudor, puede_eliminar_deudor,

            -- Acciones — Préstamos
            puede_crear_prestamo, puede_editar_prestamo, puede_anular_prestamo,

            -- Acciones — Pagos
            puede_anular_pago,

            -- Acciones — Capital
            puede_crear_capitalista, puede_editar_capitalista,
            puede_registrar_movimiento_capital, puede_ver_historial_capitalista,

            -- Acciones — Cuentas
            puede_crear_cuenta, puede_editar_cuenta,

            -- Acciones — Salidas
            puede_crear_salida, puede_eliminar_salida,

            -- Sistema
            puede_exportar

        FROM usuario_cobro
        WHERE usuario_id = ? AND cobro_id = ?
    ");
    $stmt->execute([$_SESSION['usuario_id'], $cobro_id]);
    $permisos = $stmt->fetch();
    // Normalizar: puede_ver_capital viene duplicado por el alias, lo corregimos
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

    // Cargar cobros asignados
    $stmt2 = $db->prepare("SELECT cobro_id FROM usuario_cobro WHERE usuario_id = ?");
    $stmt2->execute([$user['id']]);
    $cobros = array_column($stmt2->fetchAll(), 'cobro_id');

    // Actualizar último login
    $db->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?")->execute([$user['id']]);

    // Guardar sesión
    $_SESSION['usuario_id']      = $user['id'];
    $_SESSION['usuario_nombre']  = $user['nombre'];
    $_SESSION['rol']             = $user['rol'];
    $_SESSION['cobros_asignados']= $cobros;

    // Seleccionar cobro activo
    if ($user['rol'] === 'superadmin') {
        // Carga el primer cobro disponible
        $stmt3 = $db->prepare("SELECT id FROM cobros WHERE activo = 1 ORDER BY id LIMIT 1");
        $stmt3->execute();
        $primerCobro = $stmt3->fetchColumn();
        $_SESSION['cobro_activo'] = $primerCobro ?: 0;
        $_SESSION['permisos'] = [
            // Legacy
            'puede_ver' => 1, 'puede_crear' => 1, 'puede_editar' => 1,
            'puede_eliminar' => 1, 'puede_ver_capital' => 1, 'puede_registrar_pago' => 1,
            // Vistas
            'puede_ver_dashboard' => 1, 'puede_ver_deudores' => 1, 'puede_ver_prestamos' => 1,
            'puede_ver_pagos' => 1, 'puede_ver_capital' => 1, 'puede_ver_cuentas' => 1,
            'puede_ver_salidas' => 1, 'puede_ver_movimientos' => 1, 'puede_ver_proyeccion' => 1,
            'puede_ver_reportes' => 1, 'puede_ver_configuracion' => 1,
            'puede_ver_usuarios' => 1, 'puede_ver_cobros' => 1,
            // Acciones — Deudores
            'puede_crear_deudor' => 1, 'puede_editar_deudor' => 1, 'puede_eliminar_deudor' => 1,
            // Acciones — Préstamos
            'puede_crear_prestamo' => 1, 'puede_editar_prestamo' => 1, 'puede_anular_prestamo' => 1,
            // Acciones — Pagos
            'puede_anular_pago' => 1,
            // Acciones — Capital
            'puede_crear_capitalista' => 1, 'puede_editar_capitalista' => 1,
            'puede_registrar_movimiento_capital' => 1, 'puede_ver_historial_capitalista' => 1,
            // Acciones — Cuentas
            'puede_crear_cuenta' => 1, 'puede_editar_cuenta' => 1,
            // Acciones — Salidas
            'puede_crear_salida' => 1, 'puede_eliminar_salida' => 1,
            // Sistema
            'puede_exportar' => 1,
        ];
    } elseif (count($cobros) === 1) {
        $_SESSION['cobro_activo'] = $cobros[0];
        cargarPermisos($cobros[0]);
    } else {
        $_SESSION['cobro_activo'] = 0; // Debe seleccionar cobro
    }

    return ['ok' => true, 'cobros' => count($cobros)];
}

function logout(): void {
    session_destroy();
    header('Location: /login.php');
    exit;
}