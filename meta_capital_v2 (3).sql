-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 11-04-2026 a las 23:52:36
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `meta_capital_v2`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alertas_admin`
--

CREATE TABLE `alertas_admin` (
  `id` int(11) NOT NULL,
  `cobro_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `mensaje` varchar(500) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `deudor_id` int(11) DEFAULT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `alertas_admin`
--

INSERT INTO `alertas_admin` (`id`, `cobro_id`, `tipo`, `mensaje`, `usuario_id`, `deudor_id`, `leida`, `created_at`) VALUES
(1, 1, 'clavo_consultado', 'El cobrador ADMIN consultó al deudor CLAVO: WILSON TAXISTA (CC: 20)', 1, 27, 0, '2026-04-09 10:58:23'),
(2, 1, 'clavo_consultado', 'El cobrador COBRADOR 1 consultó al deudor CLAVO: WILSON TAXISTA (CC: 20)', 2, 27, 0, '2026-04-09 19:53:25'),
(3, 1, 'clavo_consultado', 'El cobrador COBRADOR 1 consultó al deudor CLAVO: WILSON TAXISTA (CC: 20)', 2, 27, 0, '2026-04-10 12:06:11'),
(4, 3, 'clavo_consultado', 'El cobrador COBRADOR 1 consultó al deudor CLAVO: WILSON TAXISTA (CC: 20)', 2, 27, 0, '2026-04-11 08:09:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `capitalistas`
--

CREATE TABLE `capitalistas` (
  `id` int(11) NOT NULL,
  `cobro_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `color` varchar(7) DEFAULT '#7c6aff',
  `tipo` enum('propio','prestado') NOT NULL DEFAULT 'propio',
  `monto_inicial` decimal(15,2) DEFAULT 0.00,
  `tipo_redito` enum('porcentaje','valor_fijo') DEFAULT 'porcentaje',
  `tasa_redito` decimal(8,4) DEFAULT 0.0000,
  `frecuencia_redito` enum('mensual','quincenal','libre') DEFAULT 'mensual',
  `fecha_inicio` date DEFAULT NULL,
  `estado` enum('activo','liquidado') NOT NULL DEFAULT 'activo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `capitalistas`
--

INSERT INTO `capitalistas` (`id`, `cobro_id`, `nombre`, `descripcion`, `color`, `tipo`, `monto_inicial`, `tipo_redito`, `tasa_redito`, `frecuencia_redito`, `fecha_inicio`, `estado`, `created_at`, `updated_at`) VALUES
(1, 2, 'SOCIEDAD AHUMADA', NULL, '#7c6aff', 'propio', 0.00, 'porcentaje', 0.0000, 'mensual', '2026-03-06', 'activo', '2026-03-06 10:35:01', '2026-03-06 10:35:01'),
(2, 2, 'THE FABRICA', NULL, '#141852', 'propio', 0.00, 'porcentaje', 0.0000, 'mensual', '2026-03-06', 'activo', '2026-03-06 10:35:23', '2026-03-06 10:35:23'),
(3, 2, 'PRESTAMO MOTO', NULL, '#ae1e1e', 'prestado', 2000000.00, 'porcentaje', 0.0000, 'mensual', '2026-03-06', 'activo', '2026-03-06 10:35:59', '2026-03-06 10:35:59'),
(4, 1, 'ANTHONY', NULL, '#7c6aff', 'propio', 0.00, 'porcentaje', 0.0000, 'mensual', '2026-03-06', 'activo', '2026-03-06 15:55:34', '2026-03-06 15:55:34'),
(5, 1, 'ISA AMOR', NULL, '#7c6aff', 'propio', 0.00, 'porcentaje', 0.0000, 'mensual', '2026-03-06', 'activo', '2026-03-06 15:55:46', '2026-03-06 15:55:46'),
(6, 1, 'UTILIDAD EXCEL', NULL, '#7c6aff', 'propio', 0.00, 'porcentaje', 0.0000, 'mensual', '2026-03-06', 'activo', '2026-03-06 23:12:15', '2026-03-06 23:12:15'),
(7, 2, 'ALQUILER MOTO', NULL, '#ed00d9', 'propio', 0.00, 'porcentaje', 0.0000, 'mensual', '2026-04-06', 'activo', '2026-04-06 23:16:32', '2026-04-06 23:16:32'),
(8, 3, 'ANTHONY', NULL, '#7c6aff', 'propio', 0.00, 'porcentaje', 0.0000, 'mensual', '2026-04-10', 'activo', '2026-04-10 15:26:11', '2026-04-10 15:26:11'),
(9, 3, 'MAMI', NULL, '#7c6aff', 'propio', 0.00, 'porcentaje', 0.0000, 'mensual', '2026-04-10', 'activo', '2026-04-10 15:26:22', '2026-04-10 15:26:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `capital_movimientos`
--

CREATE TABLE `capital_movimientos` (
  `id` int(11) NOT NULL,
  `cobro_id` int(11) NOT NULL,
  `tipo` enum('abono','retiro','liquidacion','redito_causado','redito_pagado','prestamo_salida','cobro_entrada','ingreso_capital','retiro_capital','prestamo_proporcional','cobro_proporcional','cobro_cuota','salida','redito','prestamo') NOT NULL,
  `tipo_salida_id` int(11) DEFAULT NULL,
  `origen` enum('capital','cobrador','liquidacion') DEFAULT 'capital',
  `es_entrada` tinyint(1) NOT NULL,
  `monto` decimal(15,2) NOT NULL,
  `cuenta_id` int(11) DEFAULT NULL,
  `metodo_pago` enum('efectivo','banco') NOT NULL DEFAULT 'efectivo',
  `capitalista_id` int(11) DEFAULT NULL,
  `prestamo_id` int(11) DEFAULT NULL,
  `pago_id` int(11) DEFAULT NULL,
  `descripcion` varchar(300) DEFAULT NULL,
  `anulado` tinyint(1) NOT NULL DEFAULT 0,
  `anulado_at` datetime DEFAULT NULL,
  `anulado_por` int(11) DEFAULT NULL,
  `fecha` date NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `capital_movimientos`
--

INSERT INTO `capital_movimientos` (`id`, `cobro_id`, `tipo`, `tipo_salida_id`, `origen`, `es_entrada`, `monto`, `cuenta_id`, `metodo_pago`, `capitalista_id`, `prestamo_id`, `pago_id`, `descripcion`, `anulado`, `anulado_at`, `anulado_por`, `fecha`, `usuario_id`, `created_at`) VALUES
(1, 2, 'ingreso_capital', NULL, 'capital', 1, 2000000.00, 1, 'efectivo', 3, NULL, NULL, NULL, 0, NULL, NULL, '2026-03-06', 1, '2026-03-06 20:01:34'),
(2, 2, 'ingreso_capital', NULL, 'capital', 1, 271000.00, 1, 'efectivo', 2, NULL, NULL, NULL, 0, NULL, NULL, '2026-03-06', 1, '2026-03-06 20:02:23'),
(3, 2, 'ingreso_capital', NULL, 'capital', 1, 6579000.00, 1, 'efectivo', 1, NULL, NULL, NULL, 0, NULL, NULL, '2026-03-06', 1, '2026-03-06 20:02:52'),
(4, 2, 'salida', NULL, 'capital', 0, 600000.00, 1, 'efectivo', NULL, 1, NULL, 'Préstamo #1 editado — nuevo monto', 0, NULL, NULL, '2026-02-15', 1, '2026-03-06 20:03:41'),
(6, 2, 'prestamo_proporcional', NULL, 'capital', 0, 600000.00, 1, 'efectivo', 1, 1, NULL, 'Préstamo #1 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-02-15', 1, '2026-03-06 20:04:18'),
(7, 2, 'salida', NULL, 'capital', 0, 600000.00, 1, 'efectivo', NULL, 2, NULL, 'Préstamo #2 editado — nuevo monto', 0, NULL, NULL, '2026-03-06', 1, '2026-03-06 20:05:10'),
(10, 2, 'prestamo_proporcional', NULL, 'capital', 0, 600000.00, 1, 'efectivo', 1, 2, NULL, 'Préstamo #2 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-02-20', 1, '2026-03-06 20:05:52'),
(11, 2, 'salida', NULL, 'capital', 0, 1000000.00, 1, 'efectivo', NULL, 3, NULL, 'Préstamo #3 a deudor #5', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:07:01'),
(12, 2, 'prestamo_proporcional', NULL, 'capital', 0, 1000000.00, 1, 'efectivo', 1, 3, NULL, 'Préstamo #3 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-01', 1, '2026-03-06 20:07:01'),
(13, 2, 'salida', NULL, 'capital', 0, 470000.00, 1, 'efectivo', NULL, 4, NULL, 'Préstamo #4 a deudor #8', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:19:34'),
(14, 2, 'prestamo_proporcional', NULL, 'capital', 0, 470000.00, 1, 'efectivo', 1, 4, NULL, 'Préstamo #4 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-01', 1, '2026-03-06 20:19:34'),
(15, 2, 'salida', NULL, 'capital', 0, 400000.00, 1, 'efectivo', NULL, 5, NULL, 'Préstamo #5 a deudor #9', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:19:56'),
(16, 2, 'prestamo_proporcional', NULL, 'capital', 0, 400000.00, 1, 'efectivo', 1, 5, NULL, 'Préstamo #5 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-01', 1, '2026-03-06 20:19:56'),
(17, 2, 'salida', NULL, 'capital', 0, 600000.00, 1, 'efectivo', NULL, 6, NULL, 'Préstamo #6 a deudor #10', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:21:18'),
(18, 2, 'prestamo_proporcional', NULL, 'capital', 0, 600000.00, 1, 'efectivo', 1, 6, NULL, 'Préstamo #6 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-01', 1, '2026-03-06 20:21:18'),
(19, 2, 'salida', NULL, 'capital', 0, 500000.00, 1, 'efectivo', NULL, 7, NULL, 'Préstamo #7 a deudor #11', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:21:59'),
(20, 2, 'prestamo_proporcional', NULL, 'capital', 0, 500000.00, 1, 'efectivo', 1, 7, NULL, 'Préstamo #7 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-01', 1, '2026-03-06 20:21:59'),
(21, 2, 'salida', NULL, 'capital', 0, 800000.00, 1, 'efectivo', NULL, 8, NULL, 'Préstamo #8 a deudor #12', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:22:49'),
(22, 2, 'prestamo_proporcional', NULL, 'capital', 0, 800000.00, 1, 'efectivo', 1, 8, NULL, 'Préstamo #8 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-01', 1, '2026-03-06 20:22:49'),
(23, 2, 'salida', NULL, 'capital', 0, 1000000.00, 1, 'efectivo', NULL, 9, NULL, 'Préstamo #9 a deudor #13', 0, NULL, NULL, '2026-02-22', 1, '2026-03-06 20:23:14'),
(24, 2, 'prestamo_proporcional', NULL, 'capital', 0, 1000000.00, 1, 'efectivo', 1, 9, NULL, 'Préstamo #9 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-02-22', 1, '2026-03-06 20:23:14'),
(25, 2, 'salida', NULL, 'capital', 0, 2700000.00, 1, 'efectivo', NULL, 10, NULL, 'Préstamo #10 a deudor #14', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:23:45'),
(26, 2, 'prestamo_proporcional', NULL, 'capital', 0, 609000.00, 1, 'efectivo', 1, 10, NULL, 'Préstamo #10 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-01', 1, '2026-03-06 20:23:45'),
(27, 2, 'prestamo_proporcional', NULL, 'capital', 0, 271000.00, 1, 'efectivo', 2, 10, NULL, 'Préstamo #10 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-01', 1, '2026-03-06 20:23:45'),
(28, 2, 'prestamo_proporcional', NULL, 'capital', 0, 1820000.00, 1, 'efectivo', 3, 10, NULL, 'Préstamo #10 — descuento capital prestado (100%)', 1, '2026-04-09 15:28:16', NULL, '2026-03-01', 1, '2026-03-06 20:23:45'),
(29, 2, 'salida', NULL, 'capital', 0, 180000.00, 1, 'efectivo', NULL, 11, NULL, 'Préstamo #11 a deudor #15', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:24:42'),
(30, 2, 'prestamo_proporcional', NULL, 'capital', 0, 180000.00, 1, 'efectivo', 3, 11, NULL, 'Préstamo #11 — descuento capital prestado (100%)', 1, '2026-04-09 15:28:16', NULL, '2026-03-01', 1, '2026-03-06 20:24:42'),
(123, 1, 'ingreso_capital', NULL, 'capital', 1, 4500000.00, 4, 'efectivo', 4, NULL, NULL, NULL, 0, NULL, NULL, '2025-12-01', 1, '2026-03-07 21:02:19'),
(124, 1, 'salida', NULL, 'capital', 0, 1000000.00, 4, 'efectivo', NULL, 32, NULL, 'Préstamo #32 a deudor #5', 0, NULL, NULL, '2025-12-26', 1, '2026-03-07 21:02:26'),
(125, 1, 'prestamo_proporcional', NULL, 'capital', 0, 1000000.00, 4, 'efectivo', 4, 32, NULL, 'Préstamo #32 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2025-12-26', 1, '2026-03-07 21:02:26'),
(126, 1, 'cobro_cuota', NULL, 'cobrador', 1, 620000.00, 4, 'efectivo', NULL, 32, 52, 'Cobro cuota préstamo #32', 0, NULL, NULL, '2026-01-24', 1, '2026-03-07 21:02:50'),
(127, 1, 'cobro_proporcional', NULL, 'capital', 1, 620000.00, 4, 'efectivo', 4, 32, 52, 'Retorno préstamo #32 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-01-24', 1, '2026-03-07 21:02:50'),
(128, 1, 'cobro_cuota', NULL, 'cobrador', 1, 620000.00, 4, 'efectivo', NULL, 32, 54, 'Cobro cuota préstamo #32', 0, NULL, NULL, '2026-02-28', 1, '2026-03-07 21:16:16'),
(129, 1, 'cobro_proporcional', NULL, 'capital', 1, 620000.00, 4, 'efectivo', 4, 32, 54, 'Retorno préstamo #32 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-02-28', 1, '2026-03-07 21:16:16'),
(130, 1, 'ingreso_capital', NULL, 'capital', 1, 2000000.00, 4, 'efectivo', 5, NULL, NULL, NULL, 0, NULL, NULL, '2026-02-01', 1, '2026-03-08 18:09:37'),
(131, 1, 'ingreso_capital', NULL, 'capital', 1, 580000.00, 4, 'efectivo', 5, NULL, NULL, NULL, 0, NULL, NULL, '2026-03-06', 1, '2026-03-08 18:11:08'),
(132, 1, 'prestamo', NULL, 'cobrador', 0, 150000.00, 4, 'efectivo', NULL, 33, NULL, 'Préstamo #33 a deudor #23', 0, NULL, NULL, '2025-12-27', 1, '2026-03-08 18:13:27'),
(133, 1, 'prestamo_proporcional', NULL, 'capital', 0, 150000.00, 4, 'efectivo', 4, 33, NULL, 'Préstamo #33 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2025-12-27', 1, '2026-03-08 18:13:27'),
(134, 1, 'cobro_cuota', NULL, 'cobrador', 1, 180000.00, 4, 'efectivo', NULL, 33, 56, 'Cobro cuota préstamo #33', 0, NULL, NULL, '2026-01-26', 1, '2026-03-08 18:13:49'),
(135, 1, 'cobro_proporcional', NULL, 'capital', 1, 180000.00, 4, 'efectivo', 4, 33, 56, 'Retorno préstamo #33 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-01-26', 1, '2026-03-08 18:13:49'),
(136, 1, 'prestamo', NULL, 'cobrador', 0, 150000.00, 4, 'efectivo', NULL, 34, NULL, 'Préstamo #34 a deudor #23', 0, NULL, NULL, '2026-01-04', 1, '2026-03-08 18:18:14'),
(137, 1, 'prestamo_proporcional', NULL, 'capital', 0, 150000.00, 4, 'efectivo', 4, 34, NULL, 'Préstamo #34 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-01-04', 1, '2026-03-08 18:18:14'),
(138, 1, 'cobro_cuota', NULL, 'cobrador', 1, 180000.00, 4, 'efectivo', NULL, 34, 57, 'Cobro cuota préstamo #34', 0, NULL, NULL, '2026-02-03', 1, '2026-03-08 18:18:40'),
(139, 1, 'cobro_proporcional', NULL, 'capital', 1, 180000.00, 4, 'efectivo', 4, 34, 57, 'Retorno préstamo #34 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-02-03', 1, '2026-03-08 18:18:40'),
(140, 1, 'prestamo', NULL, 'cobrador', 0, 250000.00, 4, 'efectivo', NULL, 35, NULL, 'Préstamo #35 a deudor #19', 0, NULL, NULL, '2026-02-01', 1, '2026-03-08 18:20:01'),
(141, 1, 'prestamo_proporcional', NULL, 'capital', 0, 250000.00, 4, 'efectivo', 4, 35, NULL, 'Préstamo #35 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-02-01', 1, '2026-03-08 18:20:01'),
(142, 1, 'prestamo', NULL, 'cobrador', 0, 500000.00, 4, 'efectivo', NULL, 36, NULL, 'Préstamo #36 a deudor #27', 0, NULL, NULL, '2026-01-24', 1, '2026-03-08 18:21:31'),
(143, 1, 'prestamo_proporcional', NULL, 'capital', 0, 500000.00, 4, 'efectivo', 4, 36, NULL, 'Préstamo #36 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-01-24', 1, '2026-03-08 18:21:31'),
(144, 1, 'cobro_cuota', NULL, 'cobrador', 1, 210000.00, 4, 'efectivo', NULL, 36, 58, 'Cobro cuota préstamo #36', 0, NULL, NULL, '2026-02-07', 1, '2026-03-08 18:22:02'),
(145, 1, 'cobro_proporcional', NULL, 'capital', 1, 210000.00, 4, 'efectivo', 4, 36, 58, 'Retorno préstamo #36 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-02-07', 1, '2026-03-08 18:22:02'),
(146, 1, 'prestamo', NULL, 'cobrador', 0, 500000.00, 4, 'efectivo', NULL, 37, NULL, 'Préstamo #37 a deudor #28', 0, NULL, NULL, '2026-01-24', 1, '2026-03-08 18:22:56'),
(147, 1, 'prestamo_proporcional', NULL, 'capital', 0, 500000.00, 4, 'efectivo', 4, 37, NULL, 'Préstamo #37 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-01-24', 1, '2026-03-08 18:22:56'),
(148, 1, 'cobro_cuota', NULL, 'cobrador', 1, 350000.00, 4, 'efectivo', NULL, 37, 61, 'Cobro cuota préstamo #37', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 18:23:29'),
(149, 1, 'cobro_proporcional', NULL, 'capital', 1, 350000.00, 4, 'efectivo', 4, 37, 61, 'Retorno préstamo #37 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-02-28', 1, '2026-03-08 18:23:29'),
(150, 1, 'prestamo', NULL, 'cobrador', 0, 500000.00, 4, 'efectivo', NULL, 38, NULL, 'Préstamo #38 a deudor #29', 0, NULL, NULL, '2026-01-24', 1, '2026-03-08 18:24:18'),
(151, 1, 'prestamo_proporcional', NULL, 'capital', 0, 500000.00, 4, 'efectivo', 4, 38, NULL, 'Préstamo #38 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-01-24', 1, '2026-03-08 18:24:18'),
(152, 1, 'cobro_cuota', NULL, 'cobrador', 1, 350000.00, 4, 'efectivo', NULL, 38, 66, 'Cobro cuota préstamo #38', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 18:24:39'),
(153, 1, 'cobro_proporcional', NULL, 'capital', 1, 350000.00, 4, 'efectivo', 4, 38, 66, 'Retorno préstamo #38 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-02-28', 1, '2026-03-08 18:24:39'),
(154, 1, 'prestamo', NULL, 'cobrador', 0, 100000.00, 4, 'efectivo', NULL, 39, NULL, 'Préstamo #39 a deudor #6', 0, NULL, NULL, '2026-01-24', 1, '2026-03-08 18:35:12'),
(155, 1, 'prestamo_proporcional', NULL, 'capital', 0, 100000.00, 4, 'efectivo', 4, 39, NULL, 'Préstamo #39 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-01-24', 1, '2026-03-08 18:35:12'),
(156, 1, 'cobro_cuota', NULL, 'cobrador', 1, 110000.00, 4, 'efectivo', NULL, 39, 71, 'Cobro cuota préstamo #39', 0, NULL, NULL, '2026-02-08', 1, '2026-03-08 18:35:31'),
(157, 1, 'cobro_proporcional', NULL, 'capital', 1, 110000.00, 4, 'efectivo', 4, 39, 71, 'Retorno préstamo #39 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-02-08', 1, '2026-03-08 18:35:31'),
(158, 1, 'prestamo', NULL, 'cobrador', 0, 200000.00, 4, 'efectivo', NULL, 40, NULL, 'Préstamo #40 a deudor #20', 0, NULL, NULL, '2026-03-05', 1, '2026-03-08 18:36:41'),
(159, 1, 'prestamo_proporcional', NULL, 'capital', 0, 200000.00, 4, 'efectivo', 4, 40, NULL, 'Préstamo #40 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-05', 1, '2026-03-08 18:36:41'),
(160, 1, 'prestamo', NULL, 'cobrador', 0, 200000.00, 4, 'efectivo', NULL, 41, NULL, 'Préstamo #41 a deudor #33', 0, NULL, NULL, '2026-02-27', 1, '2026-03-08 18:45:05'),
(161, 1, 'prestamo_proporcional', NULL, 'capital', 0, 200000.00, 4, 'efectivo', 4, 41, NULL, 'Préstamo #41 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-02-27', 1, '2026-03-08 18:45:05'),
(162, 1, 'cobro_cuota', NULL, 'cobrador', 1, 240000.00, 4, 'efectivo', NULL, 41, 72, 'Cobro cuota préstamo #41', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 18:45:30'),
(163, 1, 'cobro_proporcional', NULL, 'capital', 1, 240000.00, 4, 'efectivo', 4, 41, 72, 'Retorno préstamo #41 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-02-28', 1, '2026-03-08 18:45:30'),
(164, 1, 'prestamo', NULL, 'cobrador', 0, 200000.00, 4, 'efectivo', NULL, 42, NULL, 'Préstamo #42 a deudor #27', 0, NULL, NULL, '2026-03-08', 1, '2026-03-08 18:47:24'),
(165, 1, 'prestamo_proporcional', NULL, 'capital', 0, 200000.00, 4, 'efectivo', 4, 42, NULL, 'Préstamo #42 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-08', 1, '2026-03-08 18:47:24'),
(166, 1, 'cobro_cuota', NULL, 'cobrador', 1, 24000.00, 4, 'efectivo', NULL, 42, 73, 'Cobro cuota préstamo #42', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 18:49:24'),
(167, 1, 'cobro_proporcional', NULL, 'capital', 1, 24000.00, 4, 'efectivo', 4, 42, 73, 'Retorno préstamo #42 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-02-28', 1, '2026-03-08 18:49:24'),
(168, 1, 'prestamo', NULL, 'cobrador', 0, 500000.00, 4, 'efectivo', NULL, 43, NULL, 'Préstamo #43 a deudor #31', 0, NULL, NULL, '2026-02-01', 1, '2026-03-08 18:53:53'),
(169, 1, 'prestamo_proporcional', NULL, 'capital', 0, 500000.00, 4, 'efectivo', 4, 43, NULL, 'Préstamo #43 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-02-01', 1, '2026-03-08 18:53:53'),
(170, 1, 'cobro_cuota', NULL, 'cobrador', 1, 210000.00, 4, 'efectivo', NULL, 43, 74, 'Cobro cuota préstamo #43', 0, NULL, NULL, '2026-02-22', 1, '2026-03-08 18:54:37'),
(171, 1, 'cobro_proporcional', NULL, 'capital', 1, 210000.00, 4, 'efectivo', 4, 43, 74, 'Retorno préstamo #43 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-02-22', 1, '2026-03-08 18:54:37'),
(172, 1, 'cobro_cuota', NULL, 'cobrador', 1, 70000.00, 4, 'efectivo', NULL, 43, 77, 'Cobro cuota préstamo #43', 0, NULL, NULL, '2026-03-01', 1, '2026-03-08 18:54:54'),
(173, 1, 'cobro_proporcional', NULL, 'capital', 1, 70000.00, 4, 'efectivo', 4, 43, 77, 'Retorno préstamo #43 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-01', 1, '2026-03-08 18:54:54'),
(174, 1, 'prestamo', NULL, 'cobrador', 0, 300000.00, 4, 'efectivo', NULL, 44, NULL, 'Préstamo #44 a deudor #11', 0, NULL, NULL, '2026-02-02', 1, '2026-03-08 18:58:52'),
(175, 1, 'prestamo_proporcional', NULL, 'capital', 0, 300000.00, 4, 'efectivo', 4, 44, NULL, 'Préstamo #44 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-02-02', 1, '2026-03-08 18:58:52'),
(176, 1, 'cobro_cuota', NULL, 'cobrador', 1, 360000.00, 4, 'efectivo', NULL, 44, 78, 'Cobro cuota préstamo #44', 0, NULL, NULL, '2026-03-04', 1, '2026-03-08 18:59:05'),
(177, 1, 'cobro_proporcional', NULL, 'capital', 1, 360000.00, 4, 'efectivo', 4, 44, 78, 'Retorno préstamo #44 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-04', 1, '2026-03-08 18:59:05'),
(178, 1, 'prestamo', NULL, 'cobrador', 0, 100000.00, 4, 'efectivo', NULL, 45, NULL, 'Préstamo #45 a deudor #34', 0, NULL, NULL, '2026-02-01', 1, '2026-03-08 19:12:33'),
(179, 1, 'prestamo_proporcional', NULL, 'capital', 0, 100000.00, 4, 'efectivo', 4, 45, NULL, 'Préstamo #45 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-02-01', 1, '2026-03-08 19:12:33'),
(180, 1, 'cobro_cuota', NULL, 'cobrador', 1, 120000.00, 4, 'efectivo', NULL, 45, 79, 'Cobro cuota préstamo #45', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 19:13:05'),
(181, 1, 'cobro_proporcional', NULL, 'capital', 1, 120000.00, 4, 'efectivo', 4, 45, 79, 'Retorno préstamo #45 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-02-28', 1, '2026-03-08 19:13:05'),
(182, 1, 'prestamo', NULL, 'cobrador', 0, 200000.00, 4, 'efectivo', NULL, 46, NULL, 'Préstamo #46 a deudor #12', 0, NULL, NULL, '2026-02-01', 1, '2026-03-08 19:14:22'),
(183, 1, 'prestamo_proporcional', NULL, 'capital', 0, 200000.00, 4, 'efectivo', 4, 46, NULL, 'Préstamo #46 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-02-01', 1, '2026-03-08 19:14:22'),
(184, 1, 'cobro_cuota', NULL, 'cobrador', 1, 240000.00, 4, 'efectivo', NULL, 46, 80, 'Cobro cuota préstamo #46', 0, NULL, NULL, '2026-03-03', 1, '2026-03-08 19:15:01'),
(185, 1, 'cobro_proporcional', NULL, 'capital', 1, 240000.00, 4, 'efectivo', 4, 46, 80, 'Retorno préstamo #46 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-03', 1, '2026-03-08 19:15:01'),
(186, 1, 'prestamo', NULL, 'cobrador', 0, 500000.00, 4, 'efectivo', NULL, 47, NULL, 'Préstamo #47 a deudor #32', 0, NULL, NULL, '2026-02-15', 1, '2026-03-08 19:16:36'),
(187, 1, 'prestamo_proporcional', NULL, 'capital', 0, 500000.00, 4, 'efectivo', 4, 47, NULL, 'Préstamo #47 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-02-15', 1, '2026-03-08 19:16:36'),
(188, 1, 'cobro_cuota', NULL, 'cobrador', 1, 140000.00, 4, 'efectivo', NULL, 47, 82, 'Cobro cuota préstamo #47', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 19:16:57'),
(189, 1, 'cobro_proporcional', NULL, 'capital', 1, 140000.00, 4, 'efectivo', 4, 47, 82, 'Retorno préstamo #47 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-02-28', 1, '2026-03-08 19:16:57'),
(190, 1, 'prestamo', NULL, 'cobrador', 0, 200000.00, 4, 'efectivo', NULL, 48, NULL, 'Préstamo #48 a deudor #10', 0, NULL, NULL, '2026-02-05', 1, '2026-03-08 19:17:58'),
(191, 1, 'prestamo_proporcional', NULL, 'capital', 0, 200000.00, 4, 'efectivo', 4, 48, NULL, 'Préstamo #48 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-02-05', 1, '2026-03-08 19:17:58'),
(192, 1, 'cobro_cuota', NULL, 'cobrador', 1, 240000.00, 4, 'efectivo', NULL, 48, 83, 'Cobro cuota préstamo #48', 0, NULL, NULL, '2026-03-05', 1, '2026-03-08 19:18:13'),
(193, 1, 'cobro_proporcional', NULL, 'capital', 1, 240000.00, 4, 'efectivo', 4, 48, 83, 'Retorno préstamo #48 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-05', 1, '2026-03-08 19:18:13'),
(194, 1, 'prestamo', NULL, 'cobrador', 0, 700000.00, 4, 'efectivo', NULL, 49, NULL, 'Préstamo #49 a deudor #30', 0, NULL, NULL, '2026-02-08', 1, '2026-03-08 19:19:12'),
(195, 1, 'prestamo_proporcional', NULL, 'capital', 0, 700000.00, 4, 'efectivo', 4, 49, NULL, 'Préstamo #49 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-02-08', 1, '2026-03-08 19:19:12'),
(196, 1, 'cobro_cuota', NULL, 'cobrador', 1, 420000.00, 4, 'efectivo', NULL, 49, 84, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 19:19:51'),
(197, 1, 'cobro_proporcional', NULL, 'capital', 1, 420000.00, 4, 'efectivo', 4, 49, 84, 'Retorno préstamo #49 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-02-28', 1, '2026-03-08 19:19:51'),
(198, 1, 'prestamo', NULL, 'cobrador', 0, 1100000.00, 4, 'efectivo', NULL, 50, NULL, 'Préstamo #50 a deudor #21', 0, NULL, NULL, '2026-03-01', 1, '2026-03-08 19:23:24'),
(199, 1, 'prestamo_proporcional', NULL, 'capital', 0, 1100000.00, 4, 'efectivo', 4, 50, NULL, 'Préstamo #50 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-01', 1, '2026-03-08 19:23:24'),
(200, 1, 'cobro_cuota', NULL, 'cobrador', 1, 281000.00, 4, 'efectivo', NULL, 50, 105, 'Cobro cuota préstamo #50', 0, NULL, NULL, '2026-03-06', 1, '2026-03-08 19:24:21'),
(201, 1, 'cobro_proporcional', NULL, 'capital', 1, 281000.00, 4, 'efectivo', 4, 50, 105, 'Retorno préstamo #50 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-06', 1, '2026-03-08 19:24:21'),
(202, 1, 'prestamo', NULL, 'cobrador', 0, 300000.00, 4, 'efectivo', NULL, 51, NULL, 'Préstamo #51 a deudor #10', 0, NULL, NULL, '2026-02-21', 1, '2026-03-08 19:25:55'),
(203, 1, 'prestamo_proporcional', NULL, 'capital', 0, 300000.00, 4, 'efectivo', 5, 51, NULL, 'Préstamo #51 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-02-21', 1, '2026-03-08 19:25:55'),
(204, 1, 'cobro_cuota', NULL, 'cobrador', 1, 315000.00, 4, 'efectivo', NULL, 51, 106, 'Cobro cuota préstamo #51', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 19:26:12'),
(205, 1, 'cobro_proporcional', NULL, 'capital', 1, 315000.00, 4, 'efectivo', 5, 51, 106, 'Retorno préstamo #51 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-02-28', 1, '2026-03-08 19:26:12'),
(206, 1, 'prestamo', NULL, 'cobrador', 0, 300000.00, 4, 'efectivo', NULL, 52, NULL, 'Préstamo #52 a deudor #22', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 19:26:51'),
(207, 1, 'prestamo_proporcional', NULL, 'capital', 0, 300000.00, 4, 'efectivo', 5, 52, NULL, 'Préstamo #52 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-02-28', 1, '2026-03-08 19:26:51'),
(208, 1, 'prestamo', NULL, 'cobrador', 0, 500000.00, 4, 'efectivo', NULL, 53, NULL, 'Préstamo #53 a deudor #10', 0, NULL, NULL, '2026-03-01', 1, '2026-03-08 19:27:42'),
(209, 1, 'prestamo_proporcional', NULL, 'capital', 0, 500000.00, 4, 'efectivo', 5, 53, NULL, 'Préstamo #53 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-01', 1, '2026-03-08 19:27:42'),
(210, 1, 'prestamo', NULL, 'cobrador', 0, 200000.00, 4, 'efectivo', NULL, 54, NULL, 'Préstamo #54 a deudor #12', 0, NULL, NULL, '2026-03-01', 1, '2026-03-08 19:28:29'),
(211, 1, 'prestamo_proporcional', NULL, 'capital', 0, 200000.00, 4, 'efectivo', 4, 54, NULL, 'Préstamo #54 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-01', 1, '2026-03-08 19:28:29'),
(212, 1, 'prestamo', NULL, 'cobrador', 0, 100000.00, 4, 'efectivo', NULL, 55, NULL, 'Préstamo #55 a deudor #23', 0, NULL, NULL, '2026-03-02', 1, '2026-03-08 19:29:04'),
(213, 1, 'prestamo_proporcional', NULL, 'capital', 0, 100000.00, 4, 'efectivo', 4, 55, NULL, 'Préstamo #55 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-02', 1, '2026-03-08 19:29:04'),
(214, 1, 'prestamo', NULL, 'cobrador', 0, 250000.00, 4, 'efectivo', NULL, 56, NULL, 'Préstamo #56 a deudor #1', 0, NULL, NULL, '2026-03-02', 1, '2026-03-08 19:29:35'),
(215, 1, 'prestamo_proporcional', NULL, 'capital', 0, 250000.00, 4, 'efectivo', 4, 56, NULL, 'Préstamo #56 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-02', 1, '2026-03-08 19:29:35'),
(216, 1, 'prestamo', NULL, 'cobrador', 0, 800000.00, 4, 'efectivo', NULL, 57, NULL, 'Préstamo #57 a deudor #5', 0, NULL, NULL, '2026-03-02', 1, '2026-03-08 19:30:29'),
(217, 1, 'prestamo_proporcional', NULL, 'capital', 0, 800000.00, 4, 'efectivo', 5, 57, NULL, 'Préstamo #57 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-02', 1, '2026-03-08 19:30:29'),
(218, 1, 'prestamo', NULL, 'cobrador', 0, 200000.00, 4, 'efectivo', NULL, 58, NULL, 'Préstamo #58 a deudor #24', 0, NULL, NULL, '2026-03-04', 1, '2026-03-08 19:31:18'),
(219, 1, 'prestamo_proporcional', NULL, 'capital', 0, 200000.00, 4, 'efectivo', 4, 58, NULL, 'Préstamo #58 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-04', 1, '2026-03-08 19:31:18'),
(220, 1, 'cobro_cuota', NULL, 'cobrador', 1, 220000.00, 4, 'efectivo', NULL, 58, 107, 'Cobro cuota préstamo #58', 0, NULL, NULL, '2026-03-06', 1, '2026-03-08 19:31:30'),
(221, 1, 'cobro_proporcional', NULL, 'capital', 1, 220000.00, 4, 'efectivo', 4, 58, 107, 'Retorno préstamo #58 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-06', 1, '2026-03-08 19:31:30'),
(222, 1, 'prestamo', NULL, 'cobrador', 0, 500000.00, 4, 'efectivo', NULL, 59, NULL, 'Préstamo #59 a deudor #25', 0, NULL, NULL, '2026-03-07', 1, '2026-03-08 19:35:31'),
(223, 1, 'prestamo_proporcional', NULL, 'capital', 0, 500000.00, 4, 'efectivo', 4, 59, NULL, 'Préstamo #59 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-07', 1, '2026-03-08 19:35:31'),
(224, 1, 'prestamo', NULL, 'cobrador', 0, 600000.00, 4, 'efectivo', NULL, 60, NULL, 'Préstamo #60 a deudor #26', 0, NULL, NULL, '2026-03-01', 1, '2026-03-08 19:37:09'),
(225, 1, 'prestamo_proporcional', NULL, 'capital', 0, 600000.00, 4, 'efectivo', 4, 60, NULL, 'Préstamo #60 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-01', 1, '2026-03-08 19:37:09'),
(226, 1, 'prestamo', NULL, 'cobrador', 0, 200000.00, 4, 'efectivo', NULL, 61, NULL, 'Préstamo #61 a deudor #34', 0, NULL, NULL, '2026-03-07', 1, '2026-03-08 19:37:55'),
(227, 1, 'prestamo_proporcional', NULL, 'capital', 0, 200000.00, 4, 'efectivo', 5, 61, NULL, 'Préstamo #61 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-07', 1, '2026-03-08 19:37:55'),
(228, 1, 'prestamo', NULL, 'cobrador', 0, 300000.00, 4, 'efectivo', NULL, 62, NULL, 'Préstamo #62 a deudor #6', 0, NULL, NULL, '2026-03-07', 1, '2026-03-08 19:38:46'),
(229, 1, 'prestamo_proporcional', NULL, 'capital', 0, 300000.00, 4, 'efectivo', 5, 62, NULL, 'Préstamo #62 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-07', 1, '2026-03-08 19:38:46'),
(230, 1, 'prestamo', NULL, 'cobrador', 0, 400000.00, 4, 'efectivo', NULL, 63, NULL, 'Préstamo #63 a deudor #10', 0, NULL, NULL, '2026-03-08', 1, '2026-03-08 19:39:47'),
(231, 1, 'prestamo_proporcional', NULL, 'capital', 0, 400000.00, 4, 'efectivo', 5, 63, NULL, 'Préstamo #63 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-08', 1, '2026-03-08 19:39:47'),
(232, 1, 'prestamo', NULL, 'cobrador', 0, 500000.00, 4, 'efectivo', NULL, 64, NULL, 'Préstamo #64 a deudor #18', 0, NULL, NULL, '2026-03-01', 1, '2026-03-08 19:42:14'),
(233, 1, 'prestamo_proporcional', NULL, 'capital', 0, 485000.00, 4, 'efectivo', 4, 64, NULL, 'Préstamo #64 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-01', 1, '2026-03-08 19:42:14'),
(234, 1, 'prestamo_proporcional', NULL, 'capital', 0, 15000.00, 4, 'efectivo', 5, 64, NULL, 'Préstamo #64 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-01', 1, '2026-03-08 19:42:14'),
(235, 1, 'cobro_cuota', NULL, 'cobrador', 1, 50000.00, 4, 'efectivo', NULL, 35, NULL, 'Intereses renovación préstamo #35', 0, NULL, NULL, '2026-03-08', 1, '2026-03-08 20:44:42'),
(236, 1, 'cobro_cuota', NULL, 'cobrador', 1, 195000.00, 4, 'efectivo', NULL, 50, 109, 'Cobro cuota préstamo #50', 0, NULL, NULL, '2026-03-08', 1, '2026-03-08 20:52:57'),
(237, 1, 'cobro_proporcional', NULL, 'capital', 1, 195000.00, 4, 'efectivo', 4, 50, 109, 'Retorno préstamo #50 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-08', 1, '2026-03-08 20:52:57'),
(238, 1, 'cobro_cuota', NULL, 'cobrador', 1, 20000.00, 4, 'efectivo', NULL, 49, 110, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-03-05', 1, '2026-03-09 13:14:48'),
(239, 1, 'cobro_proporcional', NULL, 'capital', 1, 20000.00, 4, 'efectivo', 4, 49, 110, 'Retorno préstamo #49 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-05', 1, '2026-03-09 13:14:48'),
(240, 1, 'cobro_cuota', NULL, 'cobrador', 1, 20000.00, 4, 'efectivo', NULL, 49, 111, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-03-09', 1, '2026-03-09 13:14:57'),
(241, 1, 'cobro_proporcional', NULL, 'capital', 1, 20000.00, 4, 'efectivo', 4, 49, 111, 'Retorno préstamo #49 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-09', 1, '2026-03-09 13:14:57'),
(242, 1, 'cobro_cuota', NULL, 'cobrador', 1, 20000.00, 4, 'efectivo', NULL, 49, 112, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-03-09', 1, '2026-03-09 13:15:05'),
(243, 1, 'cobro_proporcional', NULL, 'capital', 1, 20000.00, 4, 'efectivo', 4, 49, 112, 'Retorno préstamo #49 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-09', 1, '2026-03-09 13:15:05'),
(244, 1, 'cobro_cuota', NULL, 'cobrador', 1, 70000.00, 4, 'efectivo', NULL, 38, 113, 'Cobro cuota préstamo #38', 0, NULL, NULL, '2026-03-07', 1, '2026-03-09 13:16:03'),
(245, 1, 'cobro_proporcional', NULL, 'capital', 1, 70000.00, 4, 'efectivo', 4, 38, 113, 'Retorno préstamo #38 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-07', 1, '2026-03-09 13:16:03'),
(246, 1, 'cobro_cuota', NULL, 'cobrador', 1, 70000.00, 4, 'efectivo', NULL, 37, 114, 'Cobro cuota préstamo #37', 0, NULL, NULL, '2026-03-09', 1, '2026-03-09 13:16:32'),
(247, 1, 'cobro_proporcional', NULL, 'capital', 1, 70000.00, 4, 'efectivo', 4, 37, 114, 'Retorno préstamo #37 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-09', 1, '2026-03-09 13:16:32'),
(248, 2, 'ingreso_capital', NULL, 'capital', 1, 250000.00, 1, 'efectivo', 1, NULL, NULL, NULL, 0, NULL, NULL, '2026-03-09', 1, '2026-03-09 13:27:52'),
(249, 1, 'cobro_cuota', NULL, 'cobrador', 1, 20000.00, 4, 'efectivo', NULL, 49, 115, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-03-09', 1, '2026-03-11 04:21:51'),
(250, 1, 'cobro_proporcional', NULL, 'capital', 1, 20000.00, 4, 'efectivo', 4, 49, 115, 'Retorno préstamo #49 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-09', 1, '2026-03-11 04:21:51'),
(251, 1, 'cobro_cuota', NULL, 'cobrador', 1, 20000.00, 4, 'efectivo', NULL, 49, 116, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-03-10', 1, '2026-03-11 04:22:03'),
(252, 1, 'cobro_proporcional', NULL, 'capital', 1, 20000.00, 4, 'efectivo', 4, 49, 116, 'Retorno préstamo #49 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-10', 1, '2026-03-11 04:22:03'),
(253, 1, 'ingreso_capital', NULL, 'capital', 1, 500000.00, 4, 'efectivo', 4, NULL, NULL, NULL, 0, NULL, NULL, '2026-03-11', 1, '2026-03-11 19:09:52'),
(254, 1, 'prestamo', NULL, 'cobrador', 0, 400000.00, 4, 'efectivo', NULL, 66, NULL, 'Préstamo #66 a deudor #7', 0, NULL, NULL, '2026-03-14', 1, '2026-03-11 19:44:55'),
(255, 1, 'prestamo_proporcional', NULL, 'capital', 0, 400000.00, 4, 'efectivo', 4, 66, NULL, 'Préstamo #66 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-14', 1, '2026-03-11 19:44:55'),
(256, 1, 'cobro_cuota', NULL, 'cobrador', 1, 20000.00, 4, 'efectivo', NULL, 49, 117, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-03-11', 1, '2026-03-12 11:54:08'),
(257, 1, 'cobro_proporcional', NULL, 'capital', 1, 20000.00, 4, 'efectivo', 4, 49, 117, 'Retorno préstamo #49 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-11', 1, '2026-03-12 11:54:08'),
(258, 1, 'cobro_cuota', NULL, 'cobrador', 1, 70000.00, 4, 'efectivo', NULL, 43, 118, 'Cobro cuota préstamo #43', 0, NULL, NULL, '2026-03-11', 1, '2026-03-12 11:54:43'),
(259, 1, 'cobro_proporcional', NULL, 'capital', 1, 70000.00, 4, 'efectivo', 4, 43, 118, 'Retorno préstamo #43 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-11', 1, '2026-03-12 11:54:43'),
(260, 1, 'cobro_cuota', NULL, 'cobrador', 1, 70000.00, 4, 'efectivo', NULL, 36, 119, 'Cobro cuota préstamo #36', 0, NULL, NULL, '2026-03-11', 1, '2026-03-12 11:55:20'),
(261, 1, 'cobro_proporcional', NULL, 'capital', 1, 70000.00, 4, 'efectivo', 4, 36, 119, 'Retorno préstamo #36 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-11', 1, '2026-03-12 11:55:20'),
(262, 1, 'cobro_cuota', NULL, 'cobrador', 1, 30000.00, 4, 'efectivo', NULL, 42, 120, 'Cobro cuota préstamo #42', 0, NULL, NULL, '2026-03-12', 1, '2026-03-12 11:55:54'),
(263, 1, 'cobro_proporcional', NULL, 'capital', 1, 30000.00, 4, 'efectivo', 4, 42, 120, 'Retorno préstamo #42 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-12', 1, '2026-03-12 11:55:54'),
(264, 1, 'ingreso_capital', NULL, 'capital', 1, 500000.00, 4, 'efectivo', 4, NULL, NULL, NULL, 0, NULL, NULL, '2026-03-12', 1, '2026-03-13 03:25:31'),
(265, 1, 'cobro_cuota', NULL, 'cobrador', 1, 150000.00, 4, 'efectivo', NULL, 56, 121, 'Cobro cuota préstamo #56', 0, NULL, NULL, '2026-03-13', 1, '2026-03-13 13:33:22'),
(266, 1, 'cobro_proporcional', NULL, 'capital', 1, 150000.00, 4, 'efectivo', 4, 56, 121, 'Retorno préstamo #56 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-13', 1, '2026-03-13 13:33:22'),
(267, 1, 'prestamo', NULL, 'cobrador', 0, 1000000.00, 4, 'efectivo', NULL, 67, NULL, 'Préstamo #67 a deudor #1', 0, NULL, NULL, '2026-03-15', 1, '2026-03-13 13:41:39'),
(268, 1, 'prestamo_proporcional', NULL, 'capital', 0, 1000000.00, 4, 'efectivo', 4, 67, NULL, 'Préstamo #67 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-15', 1, '2026-03-13 13:41:39'),
(269, 1, 'prestamo', NULL, 'cobrador', 0, 120000.00, 4, 'efectivo', NULL, 68, NULL, 'Préstamo #68 a deudor #7', 0, NULL, NULL, '2026-03-14', 1, '2026-03-13 13:42:55'),
(270, 1, 'prestamo_proporcional', NULL, 'capital', 0, 120000.00, 4, 'efectivo', 4, 68, NULL, 'Préstamo #68 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-14', 1, '2026-03-13 13:42:55'),
(271, 1, 'ingreso_capital', NULL, 'capital', 1, 600000.00, 4, 'efectivo', 4, NULL, NULL, NULL, 0, NULL, NULL, '2026-03-13', 1, '2026-03-13 14:06:27'),
(272, 1, 'prestamo', NULL, 'cobrador', 0, 975000.00, 4, 'efectivo', NULL, 69, NULL, 'Préstamo #69 a deudor #6', 0, NULL, NULL, '2026-03-05', 1, '2026-03-13 14:07:40'),
(273, 1, 'prestamo_proporcional', NULL, 'capital', 0, 855000.00, 4, 'efectivo', 4, 69, NULL, 'Préstamo #69 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-05', 1, '2026-03-13 14:07:40'),
(274, 1, 'prestamo_proporcional', NULL, 'capital', 0, 80000.00, 4, 'efectivo', 5, 69, NULL, 'Préstamo #69 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-05', 1, '2026-03-13 14:07:40'),
(275, 1, 'cobro_cuota', NULL, 'cobrador', 1, 20000.00, 4, 'efectivo', NULL, 49, 122, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-03-13', 1, '2026-03-14 12:08:50'),
(276, 1, 'cobro_proporcional', NULL, 'capital', 1, 20000.00, 4, 'efectivo', 4, 49, 122, 'Retorno préstamo #49 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-13', 1, '2026-03-14 12:08:50'),
(277, 1, 'cobro_cuota', NULL, 'cobrador', 1, 20000.00, 4, 'efectivo', NULL, 49, 123, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-03-13', 1, '2026-03-14 12:09:16'),
(278, 1, 'cobro_proporcional', NULL, 'capital', 1, 20000.00, 4, 'efectivo', 4, 49, 123, 'Retorno préstamo #49 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-13', 1, '2026-03-14 12:09:16'),
(279, 2, 'cobro_cuota', NULL, 'cobrador', 1, 50000.00, 1, 'efectivo', NULL, 11, 124, 'Cobro cuota préstamo #11', 0, NULL, NULL, '2026-03-14', 1, '2026-03-14 19:45:45'),
(280, 2, 'cobro_proporcional', NULL, 'capital', 1, 50000.00, 1, 'efectivo', 3, 11, 124, 'Retorno préstamo #11 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-14', 1, '2026-03-14 19:45:45'),
(281, 2, 'cobro_cuota', NULL, 'cobrador', 1, 880000.00, 1, 'efectivo', NULL, 8, 126, 'Cobro cuota préstamo #8', 0, NULL, NULL, '2026-03-14', 1, '2026-03-14 19:48:46'),
(282, 2, 'cobro_proporcional', NULL, 'capital', 1, 880000.00, 1, 'efectivo', 1, 8, 126, 'Retorno préstamo #8 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-14', 1, '2026-03-14 19:48:46'),
(283, 2, 'prestamo', NULL, 'cobrador', 0, 400000.00, 1, 'efectivo', NULL, 70, NULL, 'Préstamo #70 a deudor #12', 0, NULL, NULL, '2026-03-15', 1, '2026-03-14 19:49:21'),
(284, 2, 'prestamo_proporcional', NULL, 'capital', 0, 400000.00, 1, 'efectivo', 1, 70, NULL, 'Préstamo #70 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-15', 1, '2026-03-14 19:49:21'),
(285, 1, 'cobro_cuota', NULL, 'cobrador', 1, 20000.00, 4, 'efectivo', NULL, 49, 127, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-03-14', 1, '2026-03-16 13:25:23'),
(286, 1, 'cobro_proporcional', NULL, 'capital', 1, 20000.00, 4, 'efectivo', 4, 49, 127, 'Retorno préstamo #49 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-14', 1, '2026-03-16 13:25:23'),
(287, 1, 'cobro_cuota', NULL, 'cobrador', 1, 60000.00, 4, 'efectivo', NULL, 68, 128, 'Cobro cuota préstamo #68', 0, NULL, NULL, '2026-03-14', 1, '2026-03-16 13:26:56'),
(288, 1, 'cobro_proporcional', NULL, 'capital', 1, 60000.00, 4, 'efectivo', 4, 68, 128, 'Retorno préstamo #68 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-14', 1, '2026-03-16 13:26:56'),
(289, 1, 'cobro_cuota', NULL, 'cobrador', 1, 70000.00, 4, 'efectivo', NULL, 59, 129, 'Cobro cuota préstamo #59', 0, NULL, NULL, '2026-03-14', 1, '2026-03-16 13:27:24'),
(290, 1, 'cobro_proporcional', NULL, 'capital', 1, 70000.00, 4, 'efectivo', 4, 59, 129, 'Retorno préstamo #59 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-14', 1, '2026-03-16 13:27:24'),
(291, 1, 'cobro_cuota', NULL, 'cobrador', 1, 70000.00, 4, 'efectivo', NULL, 43, 130, 'Cobro cuota préstamo #43', 0, NULL, NULL, '2026-03-16', 1, '2026-03-16 13:27:43'),
(292, 1, 'cobro_proporcional', NULL, 'capital', 1, 70000.00, 4, 'efectivo', 4, 43, 130, 'Retorno préstamo #43 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-16', 1, '2026-03-16 13:27:43'),
(293, 2, 'prestamo', NULL, 'cobrador', 0, 200000.00, 1, 'efectivo', NULL, 71, NULL, 'Préstamo #71 a deudor #30', 0, NULL, NULL, '2026-03-14', 1, '2026-03-16 13:30:11'),
(294, 2, 'prestamo_proporcional', NULL, 'capital', 0, 200000.00, 1, 'efectivo', 1, 71, NULL, 'Préstamo #71 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-14', 1, '2026-03-16 13:30:11'),
(295, 1, 'ingreso_capital', NULL, 'capital', 1, 200000.00, 4, 'efectivo', 5, NULL, NULL, NULL, 0, NULL, NULL, '2026-03-16', 1, '2026-03-17 02:49:32'),
(296, 1, 'ingreso_capital', NULL, 'capital', 1, 70000.00, 4, 'efectivo', 5, NULL, NULL, NULL, 0, NULL, NULL, '2026-03-16', 1, '2026-03-17 02:51:05'),
(297, 1, 'cobro_cuota', NULL, 'cobrador', 1, 550000.00, 4, 'efectivo', NULL, 53, 131, 'Cobro cuota préstamo #53', 0, NULL, NULL, '2026-03-16', 1, '2026-03-17 03:02:07'),
(298, 1, 'cobro_proporcional', NULL, 'capital', 1, 550000.00, 4, 'efectivo', 5, 53, 131, 'Retorno préstamo #53 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-16', 1, '2026-03-17 03:02:07'),
(299, 1, 'prestamo', NULL, 'cobrador', 0, 50000.00, 4, 'efectivo', NULL, 72, NULL, 'Préstamo #72 a deudor #6', 0, NULL, NULL, '2026-03-16', 1, '2026-03-17 03:03:49'),
(301, 2, 'cobro_cuota', NULL, 'cobrador', 1, 200000.00, 1, 'efectivo', NULL, 1, 132, 'Cobro cuota préstamo #1', 0, NULL, NULL, '2026-03-16', 1, '2026-03-17 03:27:40'),
(302, 2, 'cobro_proporcional', NULL, 'capital', 1, 200000.00, 1, 'efectivo', 1, 1, 132, 'Retorno préstamo #1 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-16', 1, '2026-03-17 03:27:40'),
(303, 1, 'cobro_cuota', NULL, 'cobrador', 1, 50000.00, 4, 'efectivo', NULL, 37, 134, 'Cobro cuota préstamo #37', 0, NULL, NULL, '2026-03-16', 1, '2026-03-17 04:06:12'),
(304, 1, 'cobro_proporcional', NULL, 'capital', 1, 50000.00, 4, 'efectivo', 4, 37, 134, 'Retorno préstamo #37 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-16', 1, '2026-03-17 04:06:12'),
(305, 1, 'cobro_cuota', NULL, 'cobrador', 1, 460000.00, 4, 'efectivo', NULL, 57, 135, 'Cobro cuota préstamo #57', 0, NULL, NULL, '2026-03-17', 1, '2026-03-18 03:40:26'),
(306, 1, 'cobro_proporcional', NULL, 'capital', 1, 460000.00, 4, 'efectivo', 5, 57, 135, 'Retorno préstamo #57 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-17', 1, '2026-03-18 03:40:26'),
(307, 1, 'cobro_cuota', NULL, 'cobrador', 1, 140000.00, 4, 'efectivo', NULL, 47, 136, 'Cobro cuota préstamo #47', 0, NULL, NULL, '2026-03-17', 1, '2026-03-18 03:42:07'),
(308, 1, 'cobro_proporcional', NULL, 'capital', 1, 140000.00, 4, 'efectivo', 4, 47, 136, 'Retorno préstamo #47 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-17', 1, '2026-03-18 03:42:07'),
(309, 1, 'cobro_cuota', NULL, 'cobrador', 1, 20000.00, 4, 'efectivo', NULL, 49, 137, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-03-17', 1, '2026-03-18 03:43:24'),
(310, 1, 'cobro_proporcional', NULL, 'capital', 1, 20000.00, 4, 'efectivo', 4, 49, 137, 'Retorno préstamo #49 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-17', 1, '2026-03-18 03:43:24'),
(311, 2, 'ingreso_capital', NULL, 'capital', 1, 50000.00, 1, 'efectivo', 2, NULL, NULL, NULL, 0, NULL, NULL, '2026-03-17', 1, '2026-03-18 03:49:09'),
(312, 1, 'cobro_cuota', NULL, 'cobrador', 1, 20000.00, 4, 'efectivo', NULL, 49, 138, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-03-18', 1, '2026-03-19 01:29:48'),
(313, 1, 'cobro_proporcional', NULL, 'capital', 1, 20000.00, 4, 'efectivo', 4, 49, 138, 'Retorno préstamo #49 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-18', 1, '2026-03-19 01:29:48'),
(314, 2, 'cobro_cuota', NULL, 'cobrador', 1, 660000.00, 1, 'efectivo', NULL, 6, 139, 'Cobro cuota préstamo #6', 0, NULL, NULL, '2026-03-18', 1, '2026-03-19 01:30:53'),
(315, 2, 'cobro_proporcional', NULL, 'capital', 1, 660000.00, 1, 'efectivo', 1, 6, 139, 'Retorno préstamo #6 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-18', 1, '2026-03-19 01:30:53'),
(316, 1, 'cobro_cuota', NULL, 'cobrador', 1, 20000.00, 4, 'efectivo', NULL, 49, 140, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-03-18', 1, '2026-03-20 13:56:31'),
(317, 1, 'cobro_proporcional', NULL, 'capital', 1, 20000.00, 4, 'efectivo', 4, 49, 140, 'Retorno préstamo #49 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-18', 1, '2026-03-20 13:56:31'),
(318, 1, 'cobro_cuota', NULL, 'cobrador', 1, 60000.00, 4, 'efectivo', NULL, 68, 141, 'Cobro cuota préstamo #68', 0, NULL, NULL, '2026-03-20', 1, '2026-03-20 13:57:57'),
(319, 1, 'cobro_proporcional', NULL, 'capital', 1, 60000.00, 4, 'efectivo', 4, 68, 141, 'Retorno préstamo #68 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-20', 1, '2026-03-20 13:57:57'),
(320, 1, 'cobro_cuota', NULL, 'cobrador', 1, 90000.00, 4, 'efectivo', NULL, 66, 142, 'Cobro cuota préstamo #66', 0, NULL, NULL, '2026-03-20', 1, '2026-03-20 13:58:24'),
(321, 1, 'cobro_proporcional', NULL, 'capital', 1, 90000.00, 4, 'efectivo', 4, 66, 142, 'Retorno préstamo #66 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-20', 1, '2026-03-20 13:58:24'),
(322, 1, 'cobro_cuota', NULL, 'cobrador', 1, 120000.00, 4, 'efectivo', NULL, 55, 144, 'Cobro cuota préstamo #55', 0, NULL, NULL, '2026-03-20', 1, '2026-03-20 13:59:18'),
(323, 1, 'cobro_proporcional', NULL, 'capital', 1, 120000.00, 4, 'efectivo', 4, 55, 144, 'Retorno préstamo #55 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-20', 1, '2026-03-20 13:59:18'),
(324, 1, 'cobro_cuota', NULL, 'cobrador', 1, 325000.00, 4, 'efectivo', NULL, 69, 145, 'Cobro cuota préstamo #69', 0, NULL, NULL, '2026-03-20', 1, '2026-03-20 21:18:46'),
(325, 1, 'cobro_proporcional', NULL, 'capital', 1, 297193.00, 4, 'efectivo', 4, 69, 145, 'Retorno préstamo #69 — 91.4% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-20', 1, '2026-03-20 21:18:46'),
(326, 1, 'cobro_proporcional', NULL, 'capital', 1, 27807.00, 4, 'efectivo', 5, 69, 145, 'Retorno préstamo #69 — 8.6% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-20', 1, '2026-03-20 21:18:46'),
(327, 1, 'cobro_cuota', NULL, 'cobrador', 1, 275000.00, 4, 'efectivo', NULL, 62, 146, 'Cobro cuota préstamo #62', 1, '2026-03-20 16:25:59', 1, '2026-03-20', 1, '2026-03-20 21:19:47'),
(328, 1, 'cobro_proporcional', NULL, 'capital', 1, 275000.00, 4, 'efectivo', 5, 62, 146, 'Retorno préstamo #62 — 100% capital', 1, '2026-04-09 15:28:16', 1, '2026-03-20', 1, '2026-03-20 21:19:47'),
(329, 1, 'cobro_cuota', NULL, 'cobrador', 1, 55000.00, 4, 'efectivo', NULL, 62, 147, 'Cobro cuota préstamo #62', 1, '2026-03-20 16:25:16', 1, '2026-03-20', 1, '2026-03-20 21:19:57'),
(330, 1, 'cobro_proporcional', NULL, 'capital', 1, 55000.00, 4, 'efectivo', 5, 62, 147, 'Retorno préstamo #62 — 100% capital', 1, '2026-04-09 15:28:16', 1, '2026-03-20', 1, '2026-03-20 21:19:57'),
(332, 1, 'prestamo_proporcional', NULL, 'capital', 0, 50000.00, 4, 'efectivo', 5, 72, NULL, 'Préstamo #72 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-16', 1, '2026-03-20 21:24:33'),
(333, 1, 'cobro_cuota', NULL, 'cobrador', 1, 275000.00, 4, 'efectivo', NULL, 62, 148, 'Cobro cuota préstamo #62', 0, NULL, NULL, '2026-03-20', 1, '2026-03-20 21:26:32'),
(334, 1, 'cobro_proporcional', NULL, 'capital', 1, 275000.00, 4, 'efectivo', 5, 62, 148, 'Retorno préstamo #62 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-20', 1, '2026-03-20 21:26:32'),
(335, 1, 'prestamo', NULL, 'cobrador', 0, 200000.00, 4, 'efectivo', NULL, 73, NULL, 'Préstamo #73 a deudor #34', 0, NULL, NULL, '2026-03-20', 1, '2026-03-21 03:41:42'),
(336, 1, 'prestamo_proporcional', NULL, 'capital', 0, 200000.00, 4, 'efectivo', 5, 73, NULL, 'Préstamo #73 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-20', 1, '2026-03-21 03:41:42'),
(337, 1, 'cobro_cuota', NULL, 'cobrador', 1, 220000.00, 4, 'efectivo', NULL, 61, 149, 'Cobro cuota préstamo #61', 0, NULL, NULL, '2026-03-20', 1, '2026-03-21 03:42:07'),
(338, 1, 'cobro_proporcional', NULL, 'capital', 1, 220000.00, 4, 'efectivo', 5, 61, 149, 'Retorno préstamo #61 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-20', 1, '2026-03-21 03:42:07'),
(339, 1, 'cobro_cuota', NULL, 'cobrador', 1, 20000.00, 4, 'efectivo', NULL, 49, 150, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-03-20', 1, '2026-03-21 03:42:52'),
(340, 1, 'cobro_proporcional', NULL, 'capital', 1, 20000.00, 4, 'efectivo', 4, 49, 150, 'Retorno préstamo #49 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-20', 1, '2026-03-21 03:42:52'),
(341, 1, 'cobro_cuota', NULL, 'cobrador', 1, 20000.00, 4, 'efectivo', NULL, 49, 151, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-03-20', 1, '2026-03-21 03:43:03'),
(342, 1, 'cobro_proporcional', NULL, 'capital', 1, 20000.00, 4, 'efectivo', 4, 49, 151, 'Retorno préstamo #49 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-20', 1, '2026-03-21 03:43:03'),
(343, 2, 'cobro_cuota', NULL, 'cobrador', 1, 50000.00, 1, 'efectivo', NULL, 71, 152, 'Cobro cuota préstamo #71', 0, NULL, NULL, '2026-03-20', 1, '2026-03-21 03:45:11'),
(344, 2, 'cobro_proporcional', NULL, 'capital', 1, 50000.00, 1, 'efectivo', 1, 71, 152, 'Retorno préstamo #71 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-20', 1, '2026-03-21 03:45:11'),
(345, 1, 'prestamo', NULL, 'cobrador', 0, 1200000.00, 4, 'efectivo', NULL, 74, NULL, 'Préstamo #74 a deudor #3', 0, NULL, NULL, '2026-03-20', 1, '2026-03-21 03:50:06'),
(346, 1, 'prestamo_proporcional', NULL, 'capital', 0, 1200000.00, 4, 'efectivo', 5, 74, NULL, 'Préstamo #74 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-20', 1, '2026-03-21 03:50:06'),
(347, 1, 'prestamo', NULL, 'cobrador', 0, 100000.00, 4, 'efectivo', NULL, 75, NULL, 'Préstamo #75 a deudor #10', 0, NULL, NULL, '2026-03-21', 1, '2026-03-21 13:41:18'),
(348, 1, 'prestamo_proporcional', NULL, 'capital', 0, 100000.00, 4, 'efectivo', 4, 75, NULL, 'Préstamo #75 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-21', 1, '2026-03-21 13:41:18'),
(349, 1, 'prestamo', NULL, 'cobrador', 0, 200000.00, 4, 'efectivo', NULL, 76, NULL, 'Préstamo #76 a deudor #35', 0, NULL, NULL, '2026-03-25', 1, '2026-03-21 20:11:29'),
(350, 1, 'prestamo_proporcional', NULL, 'capital', 0, 200000.00, 4, 'efectivo', 4, 76, NULL, 'Préstamo #76 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-25', 1, '2026-03-21 20:11:29'),
(351, 1, 'prestamo', NULL, 'cobrador', 0, 200000.00, 4, 'efectivo', NULL, 77, NULL, 'Préstamo #77 a deudor #33', 0, NULL, NULL, '2026-03-21', 1, '2026-03-21 20:49:49'),
(352, 1, 'prestamo_proporcional', NULL, 'capital', 0, 200000.00, 4, 'efectivo', 4, 77, NULL, 'Préstamo #77 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-21', 1, '2026-03-21 20:49:49'),
(353, 1, 'prestamo', NULL, 'cobrador', 0, 400000.00, 4, 'efectivo', NULL, 78, NULL, 'Préstamo #78 a deudor #10', 0, NULL, NULL, '2026-03-21', 1, '2026-03-21 22:55:32'),
(354, 1, 'prestamo_proporcional', NULL, 'capital', 0, 400000.00, 4, 'efectivo', 5, 78, NULL, 'Préstamo #78 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-21', 1, '2026-03-21 22:55:32'),
(355, 1, 'cobro_cuota', NULL, 'cobrador', 1, 20000.00, 4, 'efectivo', NULL, 49, 153, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-03-21', 1, '2026-03-22 13:12:25'),
(356, 1, 'cobro_proporcional', NULL, 'capital', 1, 20000.00, 4, 'efectivo', 4, 49, 153, 'Retorno préstamo #49 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-21', 1, '2026-03-22 13:12:25'),
(357, 1, 'cobro_cuota', NULL, 'cobrador', 1, 70000.00, 4, 'efectivo', NULL, 38, 154, 'Cobro cuota préstamo #38', 0, NULL, NULL, '2026-03-21', 1, '2026-03-22 13:13:05'),
(358, 1, 'cobro_proporcional', NULL, 'capital', 1, 70000.00, 4, 'efectivo', 4, 38, 154, 'Retorno préstamo #38 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-21', 1, '2026-03-22 13:13:05'),
(359, 1, 'cobro_cuota', NULL, 'cobrador', 1, 450000.00, 4, 'efectivo', NULL, 74, 155, 'Cobro cuota préstamo #74', 0, NULL, NULL, '2026-03-22', 1, '2026-03-22 13:17:13'),
(360, 1, 'cobro_proporcional', NULL, 'capital', 1, 450000.00, 4, 'efectivo', 5, 74, 155, 'Retorno préstamo #74 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-22', 1, '2026-03-22 13:17:13'),
(361, 1, 'cobro_cuota', NULL, 'cobrador', 1, 70000.00, 4, 'efectivo', NULL, 59, 157, 'Cobro cuota préstamo #59', 0, NULL, NULL, '2026-03-22', 1, '2026-03-23 12:05:00'),
(362, 1, 'cobro_proporcional', NULL, 'capital', 1, 70000.00, 4, 'efectivo', 4, 59, 157, 'Retorno préstamo #59 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-22', 1, '2026-03-23 12:05:00'),
(363, 2, 'ingreso_capital', NULL, 'capital', 1, 30000.00, 1, 'efectivo', 2, NULL, NULL, NULL, 0, NULL, NULL, '2026-03-23', 1, '2026-03-23 12:08:06'),
(364, 1, 'prestamo', NULL, 'cobrador', 0, 200000.00, 4, 'efectivo', NULL, 79, NULL, 'Préstamo #79 a deudor #34', 0, NULL, NULL, '2026-03-23', 1, '2026-03-23 21:42:36'),
(365, 1, 'prestamo_proporcional', NULL, 'capital', 0, 200000.00, 4, 'efectivo', 4, 79, NULL, 'Préstamo #79 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-23', 1, '2026-03-23 21:42:36'),
(366, 1, 'cobro_cuota', NULL, 'cobrador', 1, 150000.00, 4, 'efectivo', NULL, 74, 158, 'Cobro cuota préstamo #74', 0, NULL, NULL, '2026-03-24', 1, '2026-03-24 16:40:49'),
(367, 1, 'cobro_proporcional', NULL, 'capital', 1, 150000.00, 4, 'efectivo', 5, 74, 158, 'Retorno préstamo #74 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-24', 1, '2026-03-24 16:40:49'),
(368, 2, 'cobro_cuota', NULL, 'cobrador', 1, 80000.00, 1, 'efectivo', NULL, 11, 159, 'Cobro cuota préstamo #11', 0, NULL, NULL, '2026-03-24', 1, '2026-03-24 23:53:21');
INSERT INTO `capital_movimientos` (`id`, `cobro_id`, `tipo`, `tipo_salida_id`, `origen`, `es_entrada`, `monto`, `cuenta_id`, `metodo_pago`, `capitalista_id`, `prestamo_id`, `pago_id`, `descripcion`, `anulado`, `anulado_at`, `anulado_por`, `fecha`, `usuario_id`, `created_at`) VALUES
(369, 2, 'cobro_proporcional', NULL, 'capital', 1, 80000.00, 1, 'efectivo', 3, 11, 159, 'Retorno préstamo #11 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-24', 1, '2026-03-24 23:53:21'),
(370, 1, 'prestamo', NULL, 'cobrador', 0, 500000.00, 4, 'efectivo', NULL, 80, NULL, 'Préstamo #80 a deudor #29', 0, NULL, NULL, '2026-03-24', 1, '2026-03-25 03:02:00'),
(371, 1, 'prestamo_proporcional', NULL, 'capital', 0, 500000.00, 4, 'efectivo', 5, 80, NULL, 'Préstamo #80 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-24', 1, '2026-03-25 03:02:00'),
(372, 1, 'cobro_cuota', NULL, 'cobrador', 1, 70000.00, 4, 'efectivo', NULL, 43, 161, 'Cobro cuota préstamo #43', 0, NULL, NULL, '2026-03-24', 1, '2026-03-25 03:04:20'),
(373, 1, 'cobro_proporcional', NULL, 'capital', 1, 70000.00, 4, 'efectivo', 4, 43, 161, 'Retorno préstamo #43 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-24', 1, '2026-03-25 03:04:20'),
(374, 2, 'ingreso_capital', NULL, 'capital', 1, 100000.00, 1, 'efectivo', 2, NULL, NULL, NULL, 0, NULL, NULL, '2026-03-24', 1, '2026-03-25 03:07:14'),
(375, 2, 'prestamo', NULL, 'cobrador', 0, 2000000.00, 1, 'efectivo', NULL, 81, NULL, 'Préstamo #81 a deudor #23', 0, NULL, NULL, '2026-03-25', 1, '2026-03-25 20:09:14'),
(376, 2, 'prestamo_proporcional', NULL, 'capital', 0, 1440000.00, 1, 'efectivo', 1, 81, NULL, 'Préstamo #81 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-25', 1, '2026-03-25 20:09:14'),
(377, 2, 'prestamo_proporcional', NULL, 'capital', 0, 180000.00, 1, 'efectivo', 2, 81, NULL, 'Préstamo #81 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-25', 1, '2026-03-25 20:09:14'),
(378, 2, 'prestamo_proporcional', NULL, 'capital', 0, 130000.00, 1, 'efectivo', 3, 81, NULL, 'Préstamo #81 — descuento capital prestado (100%)', 1, '2026-04-09 15:28:16', NULL, '2026-03-25', 1, '2026-03-25 20:09:14'),
(379, 1, 'cobro_cuota', NULL, 'cobrador', 1, 20000.00, 4, 'efectivo', NULL, 49, 162, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-03-25', 1, '2026-03-26 15:04:01'),
(380, 1, 'cobro_proporcional', NULL, 'capital', 1, 20000.00, 4, 'efectivo', 4, 49, 162, 'Retorno préstamo #49 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-25', 1, '2026-03-26 15:04:01'),
(381, 1, 'cobro_cuota', NULL, 'cobrador', 1, 20000.00, 4, 'efectivo', NULL, 49, 163, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-03-25', 1, '2026-03-26 15:04:11'),
(382, 1, 'cobro_proporcional', NULL, 'capital', 1, 20000.00, 4, 'efectivo', 4, 49, 163, 'Retorno préstamo #49 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-25', 1, '2026-03-26 15:04:11'),
(383, 1, 'cobro_cuota', NULL, 'cobrador', 1, 10000.00, 4, 'efectivo', NULL, 80, 164, 'Cobro cuota préstamo #80', 0, NULL, NULL, '2026-03-25', 1, '2026-03-26 15:05:20'),
(384, 1, 'cobro_proporcional', NULL, 'capital', 1, 10000.00, 4, 'efectivo', 5, 80, 164, 'Retorno préstamo #80 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-25', 1, '2026-03-26 15:05:20'),
(385, 2, 'cobro_cuota', NULL, 'cobrador', 1, 1000000.00, 1, 'efectivo', NULL, 9, 165, 'Cobro cuota préstamo #9', 0, NULL, NULL, '2026-03-26', 1, '2026-03-26 15:05:59'),
(386, 2, 'cobro_proporcional', NULL, 'capital', 1, 1000000.00, 1, 'efectivo', 1, 9, 165, 'Retorno préstamo #9 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-26', 1, '2026-03-26 15:05:59'),
(387, 1, 'ingreso_capital', NULL, 'capital', 1, 250000.00, 4, 'efectivo', 4, NULL, NULL, NULL, 0, NULL, NULL, '2026-03-26', 1, '2026-03-26 15:27:10'),
(388, 1, 'prestamo', NULL, 'cobrador', 0, 1900000.00, 4, 'efectivo', NULL, 82, NULL, 'Préstamo #82 a deudor #36', 0, NULL, NULL, '2026-03-27', 1, '2026-03-27 15:26:59'),
(389, 1, 'prestamo_proporcional', NULL, 'capital', 0, 937193.00, 4, 'efectivo', 4, 82, NULL, 'Préstamo #82 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-27', 1, '2026-03-27 15:26:59'),
(390, 1, 'prestamo_proporcional', NULL, 'capital', 0, 392807.00, 4, 'efectivo', 5, 82, NULL, 'Préstamo #82 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-27', 1, '2026-03-27 15:26:59'),
(391, 2, 'prestamo', NULL, 'cobrador', 0, 2000000.00, 1, 'efectivo', NULL, 83, NULL, 'Préstamo #83 a deudor #37', 0, NULL, NULL, '2026-03-27', 1, '2026-03-27 15:28:32'),
(392, 2, 'prestamo_proporcional', NULL, 'capital', 0, 1000000.00, 1, 'efectivo', 1, 83, NULL, 'Préstamo #83 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-27', 1, '2026-03-27 15:28:32'),
(393, 1, 'cobro_cuota', NULL, 'cobrador', 1, 200000.00, 4, 'efectivo', NULL, 82, 166, 'Cobro cuota préstamo #82', 0, NULL, NULL, '2026-03-27', 1, '2026-03-27 15:31:12'),
(394, 1, 'cobro_proporcional', NULL, 'capital', 1, 140931.00, 4, 'efectivo', 4, 82, 166, 'Retorno préstamo #82 — 70.5% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-27', 1, '2026-03-27 15:31:12'),
(395, 1, 'cobro_proporcional', NULL, 'capital', 1, 59069.00, 4, 'efectivo', 5, 82, 166, 'Retorno préstamo #82 — 29.5% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-27', 1, '2026-03-27 15:31:12'),
(396, 1, 'cobro_cuota', NULL, 'cobrador', 1, 10000.00, 4, 'efectivo', NULL, 80, 167, 'Cobro cuota préstamo #80', 0, NULL, NULL, '2026-03-26', 1, '2026-03-27 15:36:58'),
(397, 1, 'cobro_proporcional', NULL, 'capital', 1, 10000.00, 4, 'efectivo', 5, 80, 167, 'Retorno préstamo #80 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-26', 1, '2026-03-27 15:36:58'),
(398, 1, 'cobro_cuota', NULL, 'cobrador', 1, 20000.00, 4, 'efectivo', NULL, 49, 168, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-03-26', 1, '2026-03-27 15:37:26'),
(399, 1, 'cobro_proporcional', NULL, 'capital', 1, 20000.00, 4, 'efectivo', 4, 49, 168, 'Retorno préstamo #49 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-26', 1, '2026-03-27 15:37:26'),
(400, 1, 'cobro_cuota', NULL, 'cobrador', 1, 200000.00, 4, 'efectivo', NULL, 60, 169, 'Cobro cuota préstamo #60', 0, NULL, NULL, '2026-03-27', 1, '2026-03-28 01:23:01'),
(401, 1, 'cobro_proporcional', NULL, 'capital', 1, 200000.00, 4, 'efectivo', 4, 60, 169, 'Retorno préstamo #60 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-27', 1, '2026-03-28 01:23:01'),
(402, 1, 'cobro_cuota', NULL, 'cobrador', 1, 20000.00, 4, 'efectivo', NULL, 49, 172, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-03-27', 1, '2026-03-28 05:49:58'),
(403, 1, 'cobro_proporcional', NULL, 'capital', 1, 20000.00, 4, 'efectivo', 4, 49, 172, 'Retorno préstamo #49 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-27', 1, '2026-03-28 05:49:58'),
(404, 1, 'cobro_cuota', NULL, 'cobrador', 1, 10000.00, 4, 'efectivo', NULL, 80, 173, 'Cobro cuota préstamo #80', 0, NULL, NULL, '2026-03-27', 1, '2026-03-28 05:50:43'),
(405, 1, 'cobro_proporcional', NULL, 'capital', 1, 10000.00, 4, 'efectivo', 5, 80, 173, 'Retorno préstamo #80 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-27', 1, '2026-03-28 05:50:43'),
(406, 2, 'cobro_cuota', NULL, 'cobrador', 1, 50000.00, 1, 'efectivo', NULL, 71, 174, 'Cobro cuota préstamo #71', 0, NULL, NULL, '2026-03-27', 1, '2026-03-28 05:51:37'),
(407, 2, 'cobro_proporcional', NULL, 'capital', 1, 50000.00, 1, 'efectivo', 1, 71, 174, 'Retorno préstamo #71 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-27', 1, '2026-03-28 05:51:37'),
(408, 1, 'cobro_cuota', NULL, 'cobrador', 1, 70000.00, 4, 'efectivo', NULL, 66, 175, 'Cobro cuota préstamo #66', 0, NULL, NULL, '2026-03-28', 1, '2026-03-28 18:30:01'),
(409, 1, 'cobro_proporcional', NULL, 'capital', 1, 70000.00, 4, 'efectivo', 4, 66, 175, 'Retorno préstamo #66 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-28', 1, '2026-03-28 18:30:01'),
(410, 1, 'cobro_cuota', NULL, 'cobrador', 1, 360000.00, 4, 'efectivo', NULL, 52, 177, 'Cobro cuota préstamo #52', 0, NULL, NULL, '2026-03-28', 1, '2026-03-28 21:21:07'),
(411, 1, 'cobro_proporcional', NULL, 'capital', 1, 360000.00, 4, 'efectivo', 5, 52, 177, 'Retorno préstamo #52 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-28', 1, '2026-03-28 21:21:07'),
(412, 1, 'prestamo', NULL, 'cobrador', 0, 300000.00, 4, 'efectivo', NULL, 84, NULL, 'Préstamo #84 a deudor #22', 0, NULL, NULL, '2026-03-28', 1, '2026-03-28 21:21:46'),
(413, 1, 'prestamo_proporcional', NULL, 'capital', 0, 300000.00, 4, 'efectivo', 4, 84, NULL, 'Préstamo #84 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-28', 1, '2026-03-28 21:21:46'),
(414, 2, 'cobro_cuota', NULL, 'cobrador', 1, 600000.00, 1, 'efectivo', NULL, 7, 178, 'Cobro cuota préstamo #7', 0, NULL, NULL, '2026-03-28', 1, '2026-03-28 21:27:54'),
(415, 2, 'cobro_proporcional', NULL, 'capital', 1, 600000.00, 1, 'efectivo', 1, 7, 178, 'Retorno préstamo #7 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-28', 1, '2026-03-28 21:27:54'),
(416, 2, 'prestamo', NULL, 'cobrador', 0, 450000.00, 1, 'efectivo', NULL, 85, NULL, 'Préstamo #85 a deudor #11', 0, NULL, NULL, '2026-03-28', 1, '2026-03-28 21:28:23'),
(417, 2, 'prestamo_proporcional', NULL, 'capital', 0, 450000.00, 1, 'efectivo', 1, 85, NULL, 'Préstamo #85 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-28', 1, '2026-03-28 21:28:23'),
(418, 1, 'cobro_cuota', NULL, 'cobrador', 1, 10000.00, 4, 'efectivo', NULL, 80, 179, 'Cobro cuota préstamo #80', 0, NULL, NULL, '2026-03-28', 1, '2026-03-29 05:20:12'),
(419, 1, 'cobro_proporcional', NULL, 'capital', 1, 10000.00, 4, 'efectivo', 5, 80, 179, 'Retorno préstamo #80 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-28', 1, '2026-03-29 05:20:12'),
(420, 1, 'cobro_cuota', NULL, 'cobrador', 1, 70000.00, 4, 'efectivo', NULL, 59, 180, 'Cobro cuota préstamo #59', 0, NULL, NULL, '2026-03-28', 1, '2026-03-29 05:20:37'),
(421, 1, 'cobro_proporcional', NULL, 'capital', 1, 70000.00, 4, 'efectivo', 4, 59, 180, 'Retorno préstamo #59 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-28', 1, '2026-03-29 05:20:37'),
(422, 1, 'cobro_cuota', NULL, 'cobrador', 1, 70000.00, 4, 'efectivo', NULL, 38, 181, 'Cobro cuota préstamo #38', 0, NULL, NULL, '2026-03-28', 1, '2026-03-29 05:21:16'),
(423, 1, 'cobro_proporcional', NULL, 'capital', 1, 70000.00, 4, 'efectivo', 4, 38, 181, 'Retorno préstamo #38 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-28', 1, '2026-03-29 05:21:16'),
(424, 1, 'prestamo', NULL, 'cobrador', 0, 500000.00, 4, 'efectivo', NULL, 86, NULL, 'Préstamo #86 a deudor #38', 0, NULL, NULL, '2026-04-05', 1, '2026-03-29 19:31:40'),
(425, 1, 'prestamo_proporcional', NULL, 'capital', 0, 449069.00, 4, 'efectivo', 5, 86, NULL, 'Préstamo #86 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-04-05', 1, '2026-03-29 19:31:40'),
(426, 1, 'prestamo_proporcional', NULL, 'capital', 0, 50931.00, 4, 'efectivo', 4, 86, NULL, 'Préstamo #86 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-04-05', 1, '2026-03-29 19:31:40'),
(427, 1, 'cobro_cuota', NULL, 'cobrador', 1, 500000.00, 4, 'efectivo', NULL, 82, 182, 'Cobro cuota préstamo #82', 0, NULL, NULL, '2026-03-29', 1, '2026-03-29 19:32:37'),
(428, 1, 'cobro_proporcional', NULL, 'capital', 1, 352328.00, 4, 'efectivo', 4, 82, 182, 'Retorno préstamo #82 — 70.5% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-29', 1, '2026-03-29 19:32:37'),
(429, 1, 'cobro_proporcional', NULL, 'capital', 1, 147672.00, 4, 'efectivo', 5, 82, 182, 'Retorno préstamo #82 — 29.5% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-29', 1, '2026-03-29 19:32:37'),
(430, 1, 'prestamo', NULL, 'cobrador', 0, 1200000.00, 4, 'efectivo', NULL, 87, NULL, 'Préstamo #87 a deudor #30', 0, NULL, NULL, '2026-03-29', 1, '2026-03-30 02:04:58'),
(431, 1, 'prestamo_proporcional', NULL, 'capital', 0, 592328.00, 4, 'efectivo', 4, 87, NULL, 'Préstamo #87 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-29', 1, '2026-03-30 02:04:58'),
(432, 1, 'prestamo_proporcional', NULL, 'capital', 0, 147672.00, 4, 'efectivo', 5, 87, NULL, 'Préstamo #87 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-29', 1, '2026-03-30 02:04:58'),
(433, 1, 'cobro_cuota', NULL, 'cobrador', 1, 140000.00, 4, 'efectivo', NULL, 49, 185, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-03-29', 1, '2026-03-30 02:05:34'),
(434, 1, 'cobro_proporcional', NULL, 'capital', 1, 140000.00, 4, 'efectivo', 4, 49, 185, 'Retorno préstamo #49 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-29', 1, '2026-03-30 02:05:34'),
(435, 1, 'prestamo', NULL, 'cobrador', 0, 100000.00, 4, 'efectivo', NULL, 88, NULL, 'Préstamo #88 a deudor #39', 0, NULL, NULL, '2026-03-30', 1, '2026-03-30 13:49:28'),
(436, 1, 'prestamo_proporcional', NULL, 'capital', 0, 100000.00, 4, 'efectivo', 4, 88, NULL, 'Préstamo #88 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-30', 1, '2026-03-30 13:49:28'),
(437, 2, 'cobro_cuota', NULL, 'cobrador', 1, 480000.00, 1, 'efectivo', NULL, 5, 192, 'Cobro cuota préstamo #5', 0, NULL, NULL, '2026-03-30', 1, '2026-03-30 19:55:46'),
(438, 2, 'cobro_proporcional', NULL, 'capital', 1, 480000.00, 1, 'efectivo', 1, 5, 192, 'Retorno préstamo #5 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-30', 1, '2026-03-30 19:55:46'),
(439, 2, 'prestamo', NULL, 'cobrador', 0, 280000.00, 1, 'efectivo', NULL, 89, NULL, 'Préstamo #89 a deudor #9', 0, NULL, NULL, '2026-03-30', 1, '2026-03-30 19:57:57'),
(440, 2, 'prestamo_proporcional', NULL, 'capital', 0, 280000.00, 1, 'efectivo', 1, 89, NULL, 'Préstamo #89 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-30', 1, '2026-03-30 19:57:57'),
(441, 1, 'cobro_cuota', NULL, 'cobrador', 1, 120000.00, 4, 'efectivo', NULL, 77, 193, 'Cobro cuota préstamo #77', 0, NULL, NULL, '2026-03-30', 1, '2026-03-30 19:59:20'),
(442, 1, 'cobro_proporcional', NULL, 'capital', 1, 120000.00, 4, 'efectivo', 4, 77, 193, 'Retorno préstamo #77 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-30', 1, '2026-03-30 19:59:20'),
(443, 1, 'cobro_cuota', NULL, 'cobrador', 1, 70000.00, 4, 'efectivo', NULL, 43, 194, 'Cobro cuota préstamo #43', 0, NULL, NULL, '2026-03-30', 1, '2026-03-30 23:58:30'),
(444, 1, 'cobro_proporcional', NULL, 'capital', 1, 70000.00, 4, 'efectivo', 4, 43, 194, 'Retorno préstamo #43 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-30', 1, '2026-03-30 23:58:30'),
(445, 1, 'cobro_cuota', NULL, 'cobrador', 1, 150000.00, 4, 'efectivo', NULL, 56, 195, 'Cobro cuota préstamo #56', 0, NULL, NULL, '2026-03-30', 1, '2026-03-30 23:59:17'),
(446, 1, 'cobro_proporcional', NULL, 'capital', 1, 150000.00, 4, 'efectivo', 4, 56, 195, 'Retorno préstamo #56 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-30', 1, '2026-03-30 23:59:17'),
(447, 1, 'cobro_cuota', NULL, 'cobrador', 1, 260000.00, 4, 'efectivo', NULL, 67, 196, 'Cobro cuota préstamo #67', 0, NULL, NULL, '2026-03-30', 1, '2026-03-30 23:59:57'),
(448, 1, 'cobro_proporcional', NULL, 'capital', 1, 260000.00, 4, 'efectivo', 4, 67, 196, 'Retorno préstamo #67 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-30', 1, '2026-03-30 23:59:57'),
(449, 1, 'cobro_cuota', NULL, 'cobrador', 1, 35000.00, 4, 'efectivo', NULL, 87, 197, 'Cobro cuota préstamo #87', 0, NULL, NULL, '2026-03-31', 1, '2026-03-31 14:01:12'),
(450, 1, 'cobro_proporcional', NULL, 'capital', 1, 28016.00, 4, 'efectivo', 4, 87, 197, 'Retorno préstamo #87 — 80% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-31', 1, '2026-03-31 14:01:12'),
(451, 1, 'cobro_proporcional', NULL, 'capital', 1, 6984.00, 4, 'efectivo', 5, 87, 197, 'Retorno préstamo #87 — 20% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-31', 1, '2026-03-31 14:01:12'),
(452, 2, 'salida', NULL, 'capital', 0, 390000.00, 1, 'efectivo', NULL, NULL, NULL, 'GPS DE LA MOTO', 0, NULL, NULL, '2026-03-31', 1, '2026-03-31 14:09:09'),
(453, 1, 'cobro_cuota', NULL, 'cobrador', 1, 460000.00, 4, 'efectivo', NULL, 57, 198, 'Cobro cuota préstamo #57', 0, NULL, NULL, '2026-03-31', 1, '2026-03-31 16:16:59'),
(454, 1, 'cobro_proporcional', NULL, 'capital', 1, 460000.00, 4, 'efectivo', 5, 57, 198, 'Retorno préstamo #57 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-31', 1, '2026-03-31 16:16:59'),
(455, 2, 'cobro_cuota', NULL, 'cobrador', 1, 454000.00, 1, 'efectivo', NULL, 3, 199, 'Cobro cuota préstamo #3', 0, NULL, NULL, '2026-03-31', 1, '2026-03-31 16:17:45'),
(456, 2, 'cobro_proporcional', NULL, 'capital', 1, 454000.00, 1, 'efectivo', 1, 3, 199, 'Retorno préstamo #3 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-31', 1, '2026-03-31 16:17:45'),
(457, 1, 'prestamo', NULL, 'cobrador', 0, 200000.00, 4, 'efectivo', NULL, 90, NULL, 'Préstamo #90 a deudor #22', 0, NULL, NULL, '2026-03-31', 1, '2026-03-31 16:25:47'),
(458, 1, 'prestamo_proporcional', NULL, 'capital', 0, 200000.00, 4, 'efectivo', 4, 90, NULL, 'Préstamo #90 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-31', 1, '2026-03-31 16:25:47'),
(459, 2, 'cobro_cuota', NULL, 'cobrador', 1, 564000.00, 1, 'efectivo', NULL, 4, 201, 'Cobro cuota préstamo #4', 0, NULL, NULL, '2026-03-31', 1, '2026-03-31 16:46:43'),
(460, 2, 'cobro_proporcional', NULL, 'capital', 1, 564000.00, 1, 'efectivo', 1, 4, 201, 'Retorno préstamo #4 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-31', 1, '2026-03-31 16:46:43'),
(461, 2, 'prestamo', NULL, 'cobrador', 0, 364000.00, 1, 'efectivo', NULL, 91, NULL, 'Préstamo #91 a deudor #8', 0, NULL, NULL, '2026-03-31', 1, '2026-03-31 16:50:39'),
(462, 2, 'prestamo_proporcional', NULL, 'capital', 0, 364000.00, 1, 'efectivo', 1, 91, NULL, 'Préstamo #91 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-31', 1, '2026-03-31 16:50:39'),
(463, 1, 'cobro_cuota', NULL, 'cobrador', 1, 20000.00, 4, 'efectivo', NULL, 80, 203, 'Cobro cuota préstamo #80', 0, NULL, NULL, '2026-03-31', 1, '2026-03-31 16:51:43'),
(464, 1, 'cobro_proporcional', NULL, 'capital', 1, 20000.00, 4, 'efectivo', 5, 80, 203, 'Retorno préstamo #80 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-31', 1, '2026-03-31 16:51:43'),
(465, 1, 'cobro_cuota', NULL, 'cobrador', 1, 110000.00, 4, 'efectivo', NULL, 75, 205, 'Cobro cuota préstamo #75', 0, NULL, NULL, '2026-03-31', 1, '2026-03-31 19:39:25'),
(466, 1, 'cobro_proporcional', NULL, 'capital', 1, 110000.00, 4, 'efectivo', 4, 75, 205, 'Retorno préstamo #75 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-31', 1, '2026-03-31 19:39:25'),
(467, 2, 'prestamo', NULL, 'cobrador', 0, 1500000.00, 1, 'efectivo', NULL, 92, NULL, 'Préstamo #92 a deudor #5', 0, NULL, NULL, '2026-03-31', 1, '2026-03-31 21:18:12'),
(468, 2, 'prestamo_proporcional', NULL, 'capital', 0, 1054000.00, 1, 'efectivo', 1, 92, NULL, 'Préstamo #92 — descuento capital propio', 1, '2026-04-09 15:28:16', 1, '2026-03-31', 1, '2026-03-31 21:18:12'),
(469, 1, 'cobro_cuota', NULL, 'cobrador', 1, 140000.00, 4, 'efectivo', NULL, 37, 206, 'Cobro cuota préstamo #37', 0, NULL, NULL, '2026-03-31', 1, '2026-03-31 23:36:41'),
(470, 1, 'cobro_proporcional', NULL, 'capital', 1, 140000.00, 4, 'efectivo', 4, 37, 206, 'Retorno préstamo #37 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-31', 1, '2026-03-31 23:36:41'),
(471, 1, 'cobro_cuota', NULL, 'cobrador', 1, 35000.00, 4, 'efectivo', NULL, 87, 209, 'Cobro cuota préstamo #87', 0, NULL, NULL, '2026-03-31', 1, '2026-04-02 14:46:40'),
(472, 1, 'cobro_proporcional', NULL, 'capital', 1, 28016.00, 4, 'efectivo', 4, 87, 209, 'Retorno préstamo #87 — 80% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-31', 1, '2026-04-02 14:46:40'),
(473, 1, 'cobro_proporcional', NULL, 'capital', 1, 6984.00, 4, 'efectivo', 5, 87, 209, 'Retorno préstamo #87 — 20% capital', 1, '2026-04-09 15:28:16', NULL, '2026-03-31', 1, '2026-04-02 14:46:40'),
(474, 1, 'cobro_cuota', NULL, 'cobrador', 1, 35000.00, 4, 'efectivo', NULL, 87, 210, 'Cobro cuota préstamo #87', 0, NULL, NULL, '2026-04-01', 1, '2026-04-02 14:47:06'),
(475, 1, 'cobro_proporcional', NULL, 'capital', 1, 28016.00, 4, 'efectivo', 4, 87, 210, 'Retorno préstamo #87 — 80% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-01', 1, '2026-04-02 14:47:06'),
(476, 1, 'cobro_proporcional', NULL, 'capital', 1, 6984.00, 4, 'efectivo', 5, 87, 210, 'Retorno préstamo #87 — 20% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-01', 1, '2026-04-02 14:47:06'),
(477, 1, 'cobro_cuota', NULL, 'cobrador', 1, 55000.00, 4, 'efectivo', NULL, 62, 211, 'Cobro cuota préstamo #62', 0, NULL, NULL, '2026-04-01', 1, '2026-04-02 14:49:03'),
(478, 1, 'cobro_proporcional', NULL, 'capital', 1, 55000.00, 4, 'efectivo', 5, 62, 211, 'Retorno préstamo #62 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-01', 1, '2026-04-02 14:49:03'),
(479, 1, 'cobro_cuota', NULL, 'cobrador', 1, 55000.00, 4, 'efectivo', NULL, 72, 212, 'Cobro cuota préstamo #72', 0, NULL, NULL, '2026-04-01', 1, '2026-04-02 14:49:28'),
(480, 1, 'cobro_proporcional', NULL, 'capital', 1, 55000.00, 4, 'efectivo', 5, 72, 212, 'Retorno préstamo #72 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-01', 1, '2026-04-02 14:49:28'),
(481, 1, 'cobro_cuota', NULL, 'cobrador', 1, 190000.00, 4, 'efectivo', NULL, 69, 213, 'Cobro cuota préstamo #69', 0, NULL, NULL, '2026-04-01', 1, '2026-04-02 14:50:24'),
(482, 1, 'cobro_proporcional', NULL, 'capital', 1, 173743.00, 4, 'efectivo', 4, 69, 213, 'Retorno préstamo #69 — 91.4% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-01', 1, '2026-04-02 14:50:24'),
(483, 1, 'cobro_proporcional', NULL, 'capital', 1, 16257.00, 4, 'efectivo', 5, 69, 213, 'Retorno préstamo #69 — 8.6% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-01', 1, '2026-04-02 14:50:24'),
(484, 1, 'cobro_cuota', NULL, 'cobrador', 1, 20000.00, 4, 'efectivo', NULL, 80, 214, 'Cobro cuota préstamo #80', 0, NULL, NULL, '2026-04-02', 1, '2026-04-03 17:56:25'),
(485, 1, 'cobro_proporcional', NULL, 'capital', 1, 20000.00, 4, 'efectivo', 5, 80, 214, 'Retorno préstamo #80 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-02', 1, '2026-04-03 17:56:25'),
(486, 1, 'cobro_cuota', NULL, 'cobrador', 1, 480000.00, 4, 'efectivo', NULL, 63, 216, 'Cobro cuota préstamo #63', 0, NULL, NULL, '2026-04-03', 1, '2026-04-03 18:01:21'),
(487, 1, 'cobro_proporcional', NULL, 'capital', 1, 480000.00, 4, 'efectivo', 5, 63, 216, 'Retorno préstamo #63 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-03', 1, '2026-04-03 18:01:21'),
(488, 1, 'prestamo', NULL, 'cobrador', 0, 200000.00, 4, 'efectivo', NULL, 93, NULL, 'Préstamo #93 a deudor #10', 0, NULL, NULL, '2026-04-03', 1, '2026-04-03 18:05:25'),
(489, 1, 'prestamo_proporcional', NULL, 'capital', 0, 200000.00, 4, 'efectivo', 5, 93, NULL, 'Préstamo #93 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-04-03', 1, '2026-04-03 18:05:25'),
(490, 1, 'cobro_cuota', NULL, 'cobrador', 1, 400000.00, 4, 'efectivo', NULL, 78, 217, 'Cobro cuota préstamo #78', 0, NULL, NULL, '2026-04-03', 1, '2026-04-03 18:07:24'),
(491, 1, 'cobro_proporcional', NULL, 'capital', 1, 400000.00, 4, 'efectivo', 5, 78, 217, 'Retorno préstamo #78 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-03', 1, '2026-04-03 18:07:24'),
(492, 1, 'prestamo', NULL, 'cobrador', 0, 400000.00, 4, 'efectivo', NULL, 94, NULL, 'Préstamo #94 a deudor #19', 0, NULL, NULL, '2026-03-29', 1, '2026-04-03 18:09:14'),
(493, 1, 'prestamo_proporcional', NULL, 'capital', 0, 400000.00, 4, 'efectivo', 5, 94, NULL, 'Préstamo #94 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-03-29', 1, '2026-04-03 18:09:14'),
(494, 1, 'prestamo', NULL, 'cobrador', 0, 500000.00, 4, 'efectivo', NULL, 95, NULL, 'Préstamo #95 a deudor #28', 0, NULL, NULL, '2026-04-01', 1, '2026-04-03 18:25:14'),
(495, 1, 'prestamo_proporcional', NULL, 'capital', 0, 500000.00, 4, 'efectivo', 4, 95, NULL, 'Préstamo #95 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-04-01', 1, '2026-04-03 18:25:15'),
(496, 1, 'cobro_cuota', NULL, 'cobrador', 1, 70000.00, 4, 'efectivo', NULL, 38, 218, 'Cobro cuota préstamo #38', 0, NULL, NULL, '2026-04-04', 1, '2026-04-05 04:27:12'),
(497, 1, 'cobro_proporcional', NULL, 'capital', 1, 70000.00, 4, 'efectivo', 4, 38, 218, 'Retorno préstamo #38 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-04', 1, '2026-04-05 04:27:12'),
(498, 1, 'cobro_cuota', NULL, 'cobrador', 1, 35000.00, 4, 'efectivo', NULL, 87, 219, 'Cobro cuota préstamo #87', 0, NULL, NULL, '2026-04-04', 1, '2026-04-05 04:32:20'),
(499, 1, 'cobro_proporcional', NULL, 'capital', 1, 28016.00, 4, 'efectivo', 4, 87, 219, 'Retorno préstamo #87 — 80% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-04', 1, '2026-04-05 04:32:20'),
(500, 1, 'cobro_proporcional', NULL, 'capital', 1, 6984.00, 4, 'efectivo', 5, 87, 219, 'Retorno préstamo #87 — 20% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-04', 1, '2026-04-05 04:32:20'),
(501, 1, 'cobro_cuota', NULL, 'cobrador', 1, 70000.00, 4, 'efectivo', NULL, 66, 220, 'Cobro cuota préstamo #66', 0, NULL, NULL, '2026-04-04', 1, '2026-04-05 04:34:52'),
(502, 1, 'cobro_proporcional', NULL, 'capital', 1, 70000.00, 4, 'efectivo', 4, 66, 220, 'Retorno préstamo #66 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-04', 1, '2026-04-05 04:34:52'),
(503, 1, 'cobro_cuota', NULL, 'cobrador', 1, 100000.00, 4, 'efectivo', NULL, 50, 222, 'Cobro cuota préstamo #50', 0, NULL, NULL, '2026-04-04', 1, '2026-04-05 04:36:14'),
(504, 1, 'cobro_proporcional', NULL, 'capital', 1, 100000.00, 4, 'efectivo', 4, 50, 222, 'Retorno préstamo #50 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-04', 1, '2026-04-05 04:36:14'),
(505, 1, 'cobro_cuota', NULL, 'cobrador', 1, 140000.00, 4, 'efectivo', NULL, 47, 223, 'Cobro cuota préstamo #47', 0, NULL, NULL, '2026-04-05', 1, '2026-04-06 02:30:47'),
(506, 1, 'cobro_proporcional', NULL, 'capital', 1, 140000.00, 4, 'efectivo', 4, 47, 223, 'Retorno préstamo #47 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-05', 1, '2026-04-06 02:30:47'),
(507, 2, 'cobro_cuota', NULL, 'cobrador', 1, 50000.00, 1, 'efectivo', NULL, 71, 224, 'Cobro cuota préstamo #71', 0, NULL, NULL, '2026-04-05', 1, '2026-04-06 02:32:08'),
(508, 2, 'cobro_proporcional', NULL, 'capital', 1, 50000.00, 1, 'efectivo', 1, 71, 224, 'Retorno préstamo #71 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-05', 1, '2026-04-06 02:32:08'),
(509, 2, 'ingreso_capital', NULL, 'capital', 1, 2000000.00, 1, 'efectivo', 1, NULL, NULL, NULL, 0, NULL, NULL, '2026-04-05', 1, '2026-04-06 04:39:38'),
(510, 2, 'cobro_cuota', NULL, 'cobrador', 1, 100000.00, 1, 'efectivo', NULL, 2, 225, 'Cobro cuota préstamo #2', 0, NULL, NULL, '2026-04-06', 1, '2026-04-07 03:58:05'),
(511, 2, 'cobro_proporcional', NULL, 'capital', 1, 100000.00, 1, 'efectivo', 1, 2, 225, 'Retorno préstamo #2 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-06', 1, '2026-04-07 03:58:05'),
(512, 1, 'cobro_cuota', NULL, 'cobrador', 1, 50000.00, 4, 'efectivo', NULL, 50, 226, 'Cobro cuota préstamo #50', 0, NULL, NULL, '2026-04-06', 1, '2026-04-07 03:59:20'),
(513, 1, 'cobro_proporcional', NULL, 'capital', 1, 50000.00, 4, 'efectivo', 4, 50, 226, 'Retorno préstamo #50 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-06', 1, '2026-04-07 03:59:20'),
(514, 1, 'cobro_cuota', NULL, 'cobrador', 1, 35000.00, 4, 'efectivo', NULL, 87, 227, 'Cobro cuota préstamo #87', 0, NULL, NULL, '2026-04-06', 1, '2026-04-07 04:00:27'),
(515, 1, 'cobro_proporcional', NULL, 'capital', 1, 28016.00, 4, 'efectivo', 4, 87, 227, 'Retorno préstamo #87 — 80% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-06', 1, '2026-04-07 04:00:27'),
(516, 1, 'cobro_proporcional', NULL, 'capital', 1, 6984.00, 4, 'efectivo', 5, 87, 227, 'Retorno préstamo #87 — 20% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-06', 1, '2026-04-07 04:00:27'),
(517, 1, 'cobro_cuota', NULL, 'cobrador', 1, 20000.00, 4, 'efectivo', NULL, 80, 228, 'Cobro cuota préstamo #80', 0, NULL, NULL, '2026-04-05', 1, '2026-04-07 04:01:33'),
(518, 1, 'cobro_proporcional', NULL, 'capital', 1, 20000.00, 4, 'efectivo', 5, 80, 228, 'Retorno préstamo #80 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-05', 1, '2026-04-07 04:01:33'),
(519, 1, 'prestamo', NULL, 'cobrador', 0, 600000.00, 4, 'efectivo', NULL, 96, NULL, 'Préstamo #96 a deudor #40', 0, NULL, NULL, '2026-04-01', 1, '2026-04-07 04:13:18'),
(520, 1, 'prestamo_proporcional', NULL, 'capital', 0, 600000.00, 4, 'efectivo', 5, 96, NULL, 'Préstamo #96 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-04-01', 1, '2026-04-07 04:13:18'),
(521, 2, 'ingreso_capital', NULL, 'capital', 1, 20000.00, 1, 'efectivo', 7, NULL, NULL, NULL, 0, NULL, NULL, '2026-04-06', 1, '2026-04-07 04:16:56'),
(522, 1, 'prestamo', NULL, 'cobrador', 0, 500000.00, 4, 'efectivo', NULL, 97, NULL, 'Préstamo #97 a deudor #41', 0, NULL, NULL, '2026-04-07', 1, '2026-04-08 03:19:23'),
(523, 1, 'prestamo_proporcional', NULL, 'capital', 0, 500000.00, 4, 'efectivo', 4, 97, NULL, 'Préstamo #97 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-04-07', 1, '2026-04-08 03:19:23'),
(524, 1, 'cobro_cuota', NULL, 'cobrador', 1, 20000.00, 4, 'efectivo', NULL, 80, 230, 'Cobro cuota préstamo #80', 0, NULL, NULL, '2026-04-07', 1, '2026-04-08 03:27:11'),
(525, 1, 'cobro_proporcional', NULL, 'capital', 1, 20000.00, 4, 'efectivo', 5, 80, 230, 'Retorno préstamo #80 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-07', 1, '2026-04-08 03:27:11'),
(526, 1, 'cobro_cuota', NULL, 'cobrador', 1, 220000.00, 4, 'efectivo', NULL, 73, 232, 'Cobro cuota préstamo #73', 0, NULL, NULL, '2026-04-07', 1, '2026-04-08 03:27:48'),
(527, 1, 'cobro_proporcional', NULL, 'capital', 1, 220000.00, 4, 'efectivo', 5, 73, 232, 'Retorno préstamo #73 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-07', 1, '2026-04-08 03:27:48'),
(528, 1, 'cobro_cuota', NULL, 'cobrador', 1, 220000.00, 4, 'efectivo', NULL, 79, 233, 'Cobro cuota préstamo #79', 0, NULL, NULL, '2026-04-07', 1, '2026-04-08 03:28:05'),
(529, 1, 'cobro_proporcional', NULL, 'capital', 1, 220000.00, 4, 'efectivo', 4, 79, 233, 'Retorno préstamo #79 — 100% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-07', 1, '2026-04-08 03:28:05'),
(530, 1, 'cobro_cuota', NULL, 'cobrador', 1, 35000.00, 4, 'efectivo', NULL, 87, 234, 'Cobro cuota préstamo #87', 0, NULL, NULL, '2026-04-07', 1, '2026-04-08 04:02:42'),
(531, 1, 'cobro_proporcional', NULL, 'capital', 1, 28016.00, 4, 'efectivo', 4, 87, 234, 'Retorno préstamo #87 — 80% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-07', 1, '2026-04-08 04:02:42'),
(532, 1, 'cobro_proporcional', NULL, 'capital', 1, 6984.00, 4, 'efectivo', 5, 87, 234, 'Retorno préstamo #87 — 20% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-07', 1, '2026-04-08 04:02:42'),
(533, 1, 'cobro_cuota', NULL, 'cobrador', 1, 250000.00, 4, 'efectivo', NULL, 64, 235, 'Cobro cuota préstamo #64', 0, NULL, NULL, '2026-04-09', 1, '2026-04-09 15:18:24'),
(534, 1, 'cobro_proporcional', NULL, 'capital', 1, 242500.00, 4, 'efectivo', 4, 64, 235, 'Retorno préstamo #64 — 97% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-09', 1, '2026-04-09 15:18:24'),
(535, 1, 'cobro_proporcional', NULL, 'capital', 1, 7500.00, 4, 'efectivo', 5, 64, 235, 'Retorno préstamo #64 — 3% capital', 1, '2026-04-09 15:28:16', NULL, '2026-04-09', 1, '2026-04-09 15:18:24'),
(536, 1, 'prestamo', NULL, 'cobrador', 0, 200000.00, 4, 'efectivo', NULL, 98, NULL, 'Préstamo #98 a deudor #44', 0, NULL, NULL, '2026-04-09', 1, '2026-04-09 16:04:22'),
(537, 1, 'prestamo_proporcional', NULL, 'capital', 0, 200000.00, 4, 'efectivo', 4, 98, NULL, 'Préstamo #98 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-04-09', 1, '2026-04-09 16:04:22'),
(538, 1, 'prestamo', NULL, 'cobrador', 0, 200000.00, 4, 'efectivo', NULL, 99, NULL, 'Préstamo #99 a deudor #44', 0, NULL, NULL, '2026-04-09', 1, '2026-04-09 16:08:20'),
(539, 1, 'prestamo_proporcional', NULL, 'capital', 0, 200000.00, 4, 'efectivo', 4, 99, NULL, 'Préstamo #99 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-04-09', 1, '2026-04-09 16:08:20'),
(540, 1, 'prestamo', NULL, 'cobrador', 0, 150000.00, 4, 'efectivo', NULL, 100, NULL, 'Préstamo #100 a deudor #44', 0, NULL, NULL, '2026-04-09', 1, '2026-04-09 16:17:47'),
(541, 1, 'prestamo_proporcional', NULL, 'capital', 0, 150000.00, 4, 'efectivo', 5, 100, NULL, 'Préstamo #100 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-04-09', 1, '2026-04-09 16:17:47'),
(542, 1, 'prestamo', NULL, 'cobrador', 0, 150000.00, 4, 'efectivo', NULL, 101, NULL, 'Préstamo #101 a deudor #44', 0, NULL, NULL, '2026-04-09', 1, '2026-04-09 16:22:32'),
(543, 1, 'prestamo_proporcional', NULL, 'capital', 0, 150000.00, 4, 'efectivo', 4, 101, NULL, 'Préstamo #101 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-04-09', 1, '2026-04-09 16:22:32'),
(544, 1, 'ingreso_capital', NULL, 'capital', 1, 5000000.00, 4, 'efectivo', 6, NULL, NULL, NULL, 0, NULL, NULL, '2026-04-09', 1, '2026-04-09 17:21:36'),
(545, 1, 'ingreso_capital', NULL, 'capital', 1, 1000000.00, 4, 'efectivo', 6, NULL, NULL, NULL, 0, NULL, NULL, '2026-04-07', 1, '2026-04-09 17:32:48'),
(546, 1, 'prestamo', NULL, 'cobrador', 0, 100000.00, 4, 'efectivo', NULL, 102, NULL, 'Préstamo #102 a deudor #18', 0, NULL, NULL, '2026-04-09', 1, '2026-04-09 19:41:42'),
(547, 1, 'prestamo_proporcional', NULL, 'capital', 0, 100000.00, 4, 'efectivo', 6, 102, NULL, 'Préstamo #102 — descuento capital propio', 1, '2026-04-09 15:28:16', NULL, '2026-04-09', 1, '2026-04-09 19:41:42'),
(548, 1, 'retiro', NULL, 'capital', 0, 1000000.00, NULL, 'efectivo', 6, NULL, NULL, NULL, 0, NULL, NULL, '2026-04-09', 1, '2026-04-09 20:43:27'),
(549, 1, 'redito', NULL, 'capital', 0, 2000000.00, NULL, 'efectivo', 6, NULL, NULL, 'Redito capitalista', 0, NULL, NULL, '2026-04-09', 1, '2026-04-09 20:44:34'),
(550, 1, 'prestamo', NULL, 'cobrador', 0, 25000.00, NULL, 'efectivo', NULL, 103, NULL, 'Préstamo #103 a deudor #3', 0, NULL, NULL, '2026-04-09', 1, '2026-04-09 21:15:34'),
(551, 1, 'cobro_cuota', NULL, 'cobrador', 1, 7500.00, NULL, 'efectivo', NULL, 103, 236, 'Cobro cuota préstamo #103', 0, NULL, NULL, '2026-04-09', 1, '2026-04-09 21:24:32'),
(552, 1, 'cobro_cuota', NULL, 'cobrador', 1, 120000.00, NULL, 'efectivo', NULL, 102, 237, 'Cobro cuota préstamo #102', 0, NULL, NULL, '2026-04-09', 2, '2026-04-09 21:51:26'),
(553, 1, 'prestamo', NULL, 'cobrador', 0, 520000.00, NULL, 'efectivo', NULL, 104, NULL, 'Préstamo #104 a deudor #46', 0, NULL, NULL, '2026-04-09', 2, '2026-04-10 00:56:12'),
(554, 1, 'prestamo', NULL, 'cobrador', 0, 20000.00, NULL, 'efectivo', NULL, 105, NULL, 'Préstamo #105 a deudor #27', 0, NULL, NULL, '2026-04-10', 2, '2026-04-10 18:13:29'),
(555, 1, 'prestamo', NULL, 'cobrador', 0, 26000.00, NULL, 'efectivo', NULL, 106, NULL, 'Préstamo #106 a deudor #27', 0, NULL, NULL, '2026-04-10', 2, '2026-04-10 18:17:27'),
(556, 3, 'ingreso_capital', NULL, 'capital', 1, 2500000.00, NULL, 'efectivo', 8, NULL, NULL, NULL, 0, NULL, NULL, '2026-04-10', 1, '2026-04-10 20:26:34'),
(557, 3, 'ingreso_capital', NULL, 'capital', 1, 2500000.00, NULL, 'efectivo', 9, NULL, NULL, NULL, 0, NULL, NULL, '2026-04-10', 1, '2026-04-10 20:26:46'),
(558, 3, 'salida', NULL, 'liquidacion', 0, 1000000.00, NULL, 'efectivo', NULL, NULL, NULL, 'Base entregada al cobrador — liquidación #9 del 10/04/2026', 0, NULL, NULL, '2026-04-10', 1, '2026-04-10 20:27:09'),
(559, 3, 'prestamo', NULL, 'cobrador', 0, 500000.00, NULL, 'efectivo', NULL, 107, NULL, 'Préstamo #107 a deudor #47', 0, NULL, NULL, '2026-04-10', 2, '2026-04-10 20:31:16'),
(560, 3, 'cobro_cuota', NULL, 'cobrador', 1, 15000.00, NULL, 'efectivo', NULL, 107, 238, 'Cobro cuota préstamo #107', 0, NULL, NULL, '2026-04-10', 2, '2026-04-10 20:31:31'),
(561, 3, 'prestamo', NULL, 'cobrador', 0, 500000.00, NULL, 'efectivo', NULL, 108, NULL, 'Préstamo #108 a deudor #48', 0, NULL, NULL, '2026-04-10', 2, '2026-04-10 20:32:37'),
(562, 3, 'prestamo', NULL, 'cobrador', 0, 200000.00, NULL, 'efectivo', NULL, 109, NULL, 'Préstamo #109 a deudor #49', 0, NULL, NULL, '2026-04-10', 2, '2026-04-10 20:33:43'),
(563, 3, 'cobro_cuota', NULL, 'cobrador', 1, 250000.00, NULL, 'efectivo', NULL, 108, 239, 'Cobro cuota préstamo #108', 0, NULL, NULL, '2026-04-10', 2, '2026-04-10 20:35:03'),
(564, 3, 'prestamo', NULL, 'cobrador', 0, 10000.00, NULL, 'efectivo', NULL, 110, NULL, 'Préstamo #110 a deudor #49', 0, NULL, NULL, '2026-04-10', 2, '2026-04-10 21:52:07'),
(565, 3, 'cobro_cuota', NULL, 'liquidacion', 1, 40000.00, NULL, 'efectivo', NULL, NULL, NULL, 'Cobrador entregó efectivo — liquidación #9 del 10/04/2026', 0, NULL, NULL, '2026-04-10', 1, '2026-04-10 21:54:17'),
(566, 3, 'salida', NULL, 'liquidacion', 0, 2500000.00, NULL, 'efectivo', NULL, NULL, NULL, 'Base entregada al cobrador — liquidación #10 del 11/04/2026', 0, NULL, NULL, '2026-04-11', 1, '2026-04-11 13:08:12'),
(567, 3, 'cobro_cuota', NULL, 'cobrador', 1, 150000.00, NULL, 'efectivo', NULL, 107, 242, 'Cobro cuota préstamo #107', 0, NULL, NULL, '2026-04-11', 2, '2026-04-11 13:10:02'),
(568, 1, 'prestamo', NULL, 'cobrador', 0, 100000.00, NULL, 'efectivo', NULL, 111, NULL, 'Préstamo #111 a deudor #1', 0, NULL, NULL, '2026-04-11', 1, '2026-04-11 14:43:06'),
(569, 1, 'prestamo', NULL, 'cobrador', 0, 125000.00, NULL, 'efectivo', NULL, 112, NULL, 'Préstamo #112 a deudor #3', 0, NULL, NULL, '2026-04-11', 1, '2026-04-11 15:23:42'),
(570, 1, 'prestamo', NULL, 'cobrador', 0, 750000.00, NULL, 'efectivo', NULL, 113, NULL, 'Préstamo #113 a deudor #47', 0, NULL, NULL, '2026-04-11', 1, '2026-04-11 16:42:59'),
(571, 3, 'prestamo', NULL, 'cobrador', 0, 950000.00, NULL, 'efectivo', NULL, 114, NULL, 'Préstamo #114 a deudor #47', 0, NULL, NULL, '2026-04-11', 1, '2026-04-11 16:46:23'),
(572, 3, 'cobro_cuota', NULL, 'cobrador', 1, 12000.00, NULL, 'efectivo', NULL, 110, 252, 'Cobro cuota préstamo #110', 0, NULL, NULL, '2026-04-11', 2, '2026-04-11 19:36:59'),
(573, 3, 'cobro_cuota', NULL, 'cobrador', 1, 430000.00, NULL, 'efectivo', NULL, 108, 253, 'Cobro cuota préstamo #108', 0, NULL, NULL, '2026-04-11', 2, '2026-04-11 19:38:27'),
(574, 3, 'cobro_cuota', NULL, 'cobrador', 1, 300000.00, NULL, 'efectivo', NULL, 109, 259, 'Cobro cuota préstamo #109', 0, NULL, NULL, '2026-04-11', 2, '2026-04-11 19:39:00'),
(575, 3, 'prestamo', NULL, 'cobrador', 0, 100000.00, NULL, 'efectivo', NULL, 115, NULL, 'Préstamo #115 a deudor #48', 0, NULL, NULL, '2026-04-11', 2, '2026-04-11 19:39:58'),
(576, 3, 'cobro_cuota', NULL, 'cobrador', 1, 60000.00, NULL, 'efectivo', NULL, 115, 283, 'Cobro cuota préstamo #115', 0, NULL, NULL, '2026-04-11', 2, '2026-04-11 19:40:06'),
(577, 3, 'cobro_cuota', NULL, 'cobrador', 1, 120000.00, NULL, 'efectivo', NULL, 115, 284, 'Cobro cuota préstamo #115', 0, NULL, NULL, '2026-04-11', 2, '2026-04-11 19:40:37'),
(578, 3, 'prestamo', NULL, 'cobrador', 0, 100000.00, NULL, 'efectivo', NULL, 116, NULL, 'Préstamo #116 a deudor #49', 0, NULL, NULL, '2026-04-11', 2, '2026-04-11 19:41:40'),
(579, 3, 'cobro_cuota', NULL, 'cobrador', 1, 60000.00, NULL, 'efectivo', NULL, 116, 285, 'Cobro cuota préstamo #116', 0, NULL, NULL, '2026-04-11', 2, '2026-04-11 19:45:34'),
(580, 3, 'prestamo', NULL, 'cobrador', 0, 40000.00, NULL, 'efectivo', NULL, 116, NULL, 'Diferencia renovación préstamo #116 (cobrador entregó al deudor)', 0, NULL, NULL, '2026-04-11', 2, '2026-04-11 19:46:29'),
(581, 3, 'salida', 4, 'capital', 0, 250000.00, NULL, 'efectivo', NULL, NULL, NULL, 'Alimentación', 0, NULL, NULL, '2026-04-11', 1, '2026-04-11 21:40:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias_gasto`
--

CREATE TABLE `categorias_gasto` (
  `id` int(11) NOT NULL,
  `cobro_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias_gasto`
--

INSERT INTO `categorias_gasto` (`id`, `cobro_id`, `nombre`, `activa`) VALUES
(1, 1, 'GASOLINA', 1),
(2, 1, 'SALARIO', 1),
(3, 1, 'ESPICHADA', 1),
(4, 3, 'GASOLINA', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cobros`
--

CREATE TABLE `cobros` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `papeleria_pct` decimal(5,2) NOT NULL DEFAULT 10.00,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cobros`
--

INSERT INTO `cobros` (`id`, `nombre`, `descripcion`, `direccion`, `telefono`, `papeleria_pct`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'EL BABY', 'ISALAMAMI.COM', NULL, NULL, 5.00, 1, '2026-03-05 10:59:16', '2026-04-09 13:23:09'),
(2, 'LOS PATEA PUERTAS', NULL, 'calle 5', '3008787518', 10.00, 1, '2026-03-05 21:30:40', '2026-03-05 21:30:40'),
(3, 'LOS AHUMADA', NULL, NULL, NULL, 10.00, 1, '2026-04-10 10:32:40', '2026-04-10 10:32:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuentas`
--

CREATE TABLE `cuentas` (
  `id` int(11) NOT NULL,
  `cobro_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('efectivo','nequi','bancolombia','daviplata','transfiya','otro') NOT NULL DEFAULT 'efectivo',
  `numero` varchar(50) DEFAULT NULL,
  `titular` varchar(100) DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cuentas`
--

INSERT INTO `cuentas` (`id`, `cobro_id`, `nombre`, `tipo`, `numero`, `titular`, `activa`, `created_at`) VALUES
(1, 2, 'EFECTIVO', 'efectivo', NULL, NULL, 1, '2026-03-05 22:50:10'),
(2, 2, 'NEQUI ANTHONY', 'nequi', '3008787518', 'JHON URIBE', 1, '2026-03-05 22:50:28'),
(3, 2, 'BANCOLOMBIA ANTHONY', 'bancolombia', '@URIBE448', 'JHON URIBE', 1, '2026-03-05 22:50:52'),
(4, 1, 'EFECTIVO', 'efectivo', NULL, NULL, 1, '2026-03-06 16:27:04'),
(5, 1, 'ANTHONY NEQUI', 'nequi', '3008787518', 'JHON URIBE', 1, '2026-03-06 16:27:31'),
(6, 1, 'BANCOLOMBIA ANTHONY', 'bancolombia', '@URIBE488', 'JHON URIBE', 1, '2026-03-06 16:27:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuotas`
--

CREATE TABLE `cuotas` (
  `id` int(11) NOT NULL,
  `prestamo_id` int(11) NOT NULL,
  `cobro_id` int(11) NOT NULL,
  `numero_cuota` int(11) NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `monto_cuota` decimal(15,2) NOT NULL,
  `monto_pagado` decimal(15,2) NOT NULL DEFAULT 0.00,
  `saldo_cuota` decimal(15,2) NOT NULL,
  `fecha_pago` date DEFAULT NULL,
  `estado` enum('pendiente','parcial','pagado','vencido','anulado') NOT NULL DEFAULT 'pendiente',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cuotas`
--

INSERT INTO `cuotas` (`id`, `prestamo_id`, `cobro_id`, `numero_cuota`, `fecha_vencimiento`, `monto_cuota`, `monto_pagado`, `saldo_cuota`, `fecha_pago`, `estado`, `created_at`, `updated_at`) VALUES
(2, 1, 2, 1, '2026-03-17', 100000.00, 100000.00, 0.00, '2026-03-16', 'pagado', '2026-03-06 15:04:18', '2026-03-16 22:27:40'),
(3, 1, 2, 2, '2026-04-16', 100000.00, 100000.00, 0.00, '2026-03-16', 'pagado', '2026-03-06 15:04:18', '2026-03-16 22:27:40'),
(4, 1, 2, 3, '2026-05-16', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:04:18', '2026-03-06 15:04:18'),
(5, 1, 2, 4, '2026-06-15', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:04:18', '2026-03-06 15:04:18'),
(6, 1, 2, 5, '2026-07-15', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:04:18', '2026-03-06 15:04:18'),
(7, 1, 2, 6, '2026-08-14', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:04:18', '2026-03-06 15:04:18'),
(13, 2, 2, 1, '2026-03-07', 160000.00, 100000.00, 60000.00, '2026-04-06', 'parcial', '2026-03-06 15:05:52', '2026-04-06 22:58:05'),
(14, 2, 2, 2, '2026-03-22', 160000.00, 0.00, 160000.00, NULL, 'pendiente', '2026-03-06 15:05:52', '2026-03-06 15:05:52'),
(15, 2, 2, 3, '2026-04-06', 160000.00, 0.00, 160000.00, NULL, 'pendiente', '2026-03-06 15:05:52', '2026-03-06 15:05:52'),
(16, 2, 2, 4, '2026-04-21', 160000.00, 0.00, 160000.00, NULL, 'pendiente', '2026-03-06 15:05:52', '2026-03-06 15:05:52'),
(17, 2, 2, 5, '2026-05-06', 160000.00, 0.00, 160000.00, NULL, 'pendiente', '2026-03-06 15:05:52', '2026-03-06 15:05:52'),
(30, 3, 2, 1, '2026-03-16', 227000.00, 227000.00, 0.00, '2026-03-31', 'pagado', '2026-03-06 15:08:41', '2026-03-31 11:17:45'),
(31, 3, 2, 2, '2026-03-31', 227000.00, 227000.00, 0.00, '2026-03-31', 'pagado', '2026-03-06 15:08:41', '2026-03-31 11:17:45'),
(32, 3, 2, 3, '2026-04-15', 227000.00, 0.00, 227000.00, NULL, 'pendiente', '2026-03-06 15:08:41', '2026-03-06 15:08:41'),
(33, 3, 2, 4, '2026-04-30', 227000.00, 0.00, 227000.00, NULL, 'pendiente', '2026-03-06 15:08:41', '2026-03-06 15:08:41'),
(34, 3, 2, 5, '2026-05-15', 227000.00, 0.00, 227000.00, NULL, 'pendiente', '2026-03-06 15:08:41', '2026-03-06 15:08:41'),
(35, 3, 2, 6, '2026-05-30', 227000.00, 0.00, 227000.00, NULL, 'pendiente', '2026-03-06 15:08:41', '2026-03-06 15:08:41'),
(36, 4, 2, 1, '2026-03-16', 282000.00, 282000.00, 0.00, '2026-03-31', 'pagado', '2026-03-06 15:19:34', '2026-03-31 11:46:43'),
(37, 4, 2, 2, '2026-03-31', 282000.00, 282000.00, 0.00, '2026-03-31', 'pagado', '2026-03-06 15:19:34', '2026-03-31 11:46:43'),
(38, 5, 2, 1, '2026-03-31', 480000.00, 480000.00, 0.00, '2026-03-30', 'pagado', '2026-03-06 15:19:56', '2026-03-30 14:55:46'),
(39, 6, 2, 1, '2026-03-16', 660000.00, 660000.00, 0.00, '2026-03-18', 'pagado', '2026-03-06 15:21:18', '2026-03-18 20:30:53'),
(40, 7, 2, 1, '2026-03-31', 600000.00, 600000.00, 0.00, '2026-03-28', 'pagado', '2026-03-06 15:21:59', '2026-03-28 16:27:54'),
(41, 8, 2, 1, '2026-03-16', 880000.00, 880000.00, 0.00, '2026-03-14', 'pagado', '2026-03-06 15:22:49', '2026-03-14 14:48:46'),
(42, 9, 2, 1, '2026-03-24', 1000000.00, 1000000.00, 0.00, '2026-03-26', 'pagado', '2026-03-06 15:23:14', '2026-03-26 10:05:59'),
(70, 10, 2, 1, '2026-03-16', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(71, 10, 2, 2, '2026-03-31', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(72, 10, 2, 3, '2026-04-15', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(73, 10, 2, 4, '2026-04-30', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(74, 10, 2, 5, '2026-05-15', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(75, 10, 2, 6, '2026-05-30', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(76, 10, 2, 7, '2026-06-14', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(77, 10, 2, 8, '2026-06-29', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(78, 10, 2, 9, '2026-07-14', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(79, 10, 2, 10, '2026-07-29', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(80, 10, 2, 11, '2026-08-13', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(81, 10, 2, 12, '2026-08-28', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(82, 10, 2, 13, '2026-09-12', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(83, 10, 2, 14, '2026-09-27', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(84, 10, 2, 15, '2026-10-12', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(85, 10, 2, 16, '2026-10-27', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(86, 10, 2, 17, '2026-11-11', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(87, 10, 2, 18, '2026-11-26', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(88, 10, 2, 19, '2026-12-11', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(89, 10, 2, 20, '2026-12-26', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(90, 10, 2, 21, '2027-01-10', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(91, 10, 2, 22, '2027-01-25', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(92, 10, 2, 23, '2027-02-09', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(93, 10, 2, 24, '2027-02-24', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(94, 10, 2, 25, '2027-03-11', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(95, 10, 2, 26, '2027-03-26', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(96, 10, 2, 27, '2027-04-10', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:23:53', '2026-03-06 15:23:53'),
(97, 11, 2, 1, '2026-03-08', 45000.00, 45000.00, 0.00, '2026-03-14', 'pagado', '2026-03-06 15:24:42', '2026-03-14 14:45:45'),
(98, 11, 2, 2, '2026-03-15', 45000.00, 45000.00, 0.00, '2026-03-24', 'pagado', '2026-03-06 15:24:42', '2026-03-24 18:53:21'),
(99, 11, 2, 3, '2026-03-22', 45000.00, 40000.00, 5000.00, NULL, 'parcial', '2026-03-06 15:24:42', '2026-03-24 18:53:21'),
(100, 11, 2, 4, '2026-03-29', 45000.00, 0.00, 45000.00, NULL, 'pendiente', '2026-03-06 15:24:42', '2026-03-06 15:24:42'),
(235, 32, 1, 1, '2026-01-10', 310000.00, 310000.00, 0.00, '2026-01-24', 'pagado', '2026-03-07 16:02:26', '2026-03-07 16:02:50'),
(236, 32, 1, 2, '2026-01-25', 310000.00, 310000.00, 0.00, '2026-01-24', 'pagado', '2026-03-07 16:02:26', '2026-03-07 16:02:50'),
(237, 32, 1, 3, '2026-02-09', 310000.00, 310000.00, 0.00, '2026-02-28', 'pagado', '2026-03-07 16:02:26', '2026-03-07 16:16:16'),
(238, 32, 1, 4, '2026-02-24', 310000.00, 310000.00, 0.00, '2026-02-28', 'pagado', '2026-03-07 16:02:26', '2026-03-07 16:16:16'),
(239, 33, 1, 1, '2026-01-26', 180000.00, 180000.00, 0.00, '2026-01-26', 'pagado', '2026-03-08 13:13:27', '2026-03-08 13:13:49'),
(240, 34, 1, 1, '2026-02-03', 180000.00, 180000.00, 0.00, '2026-02-03', 'pagado', '2026-03-08 13:18:14', '2026-03-08 13:18:40'),
(241, 35, 1, 1, '2026-03-03', 300000.00, 0.00, 300000.00, NULL, 'pagado', '2026-03-08 13:20:01', '2026-03-08 15:44:42'),
(242, 36, 1, 1, '2026-01-31', 70000.00, 70000.00, 0.00, '2026-02-07', 'pagado', '2026-03-08 13:21:31', '2026-03-08 13:22:02'),
(243, 36, 1, 2, '2026-02-07', 70000.00, 70000.00, 0.00, '2026-02-07', 'pagado', '2026-03-08 13:21:31', '2026-03-08 13:22:02'),
(244, 36, 1, 3, '2026-02-14', 70000.00, 70000.00, 0.00, '2026-02-07', 'pagado', '2026-03-08 13:21:31', '2026-03-08 13:22:02'),
(245, 36, 1, 4, '2026-02-21', 70000.00, 70000.00, 0.00, '2026-03-11', 'pagado', '2026-03-08 13:21:31', '2026-03-12 06:55:20'),
(246, 36, 1, 5, '2026-02-28', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:21:31', '2026-03-08 13:21:31'),
(247, 36, 1, 6, '2026-03-07', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:21:31', '2026-03-08 13:21:31'),
(248, 36, 1, 7, '2026-03-14', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:21:31', '2026-03-08 13:21:31'),
(249, 36, 1, 8, '2026-03-21', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:21:31', '2026-03-08 13:21:31'),
(250, 36, 1, 9, '2026-03-28', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:21:31', '2026-03-08 13:21:31'),
(251, 36, 1, 10, '2026-04-04', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:21:31', '2026-03-08 13:21:31'),
(252, 37, 1, 1, '2026-01-31', 70000.00, 70000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 13:22:56', '2026-03-08 13:23:29'),
(253, 37, 1, 2, '2026-02-07', 70000.00, 70000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 13:22:56', '2026-03-08 13:23:29'),
(254, 37, 1, 3, '2026-02-14', 70000.00, 70000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 13:22:56', '2026-03-08 13:23:29'),
(255, 37, 1, 4, '2026-02-21', 70000.00, 70000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 13:22:56', '2026-03-08 13:23:29'),
(256, 37, 1, 5, '2026-02-28', 70000.00, 70000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 13:22:56', '2026-03-08 13:23:29'),
(257, 37, 1, 6, '2026-03-07', 70000.00, 70000.00, 0.00, '2026-03-09', 'pagado', '2026-03-08 13:22:56', '2026-03-09 08:16:32'),
(258, 37, 1, 7, '2026-03-14', 70000.00, 70000.00, 0.00, '2026-03-31', 'pagado', '2026-03-08 13:22:56', '2026-03-31 18:36:41'),
(259, 37, 1, 8, '2026-03-21', 70000.00, 70000.00, 0.00, '2026-03-31', 'pagado', '2026-03-08 13:22:56', '2026-03-31 18:36:41'),
(260, 37, 1, 9, '2026-03-28', 70000.00, 50000.00, 20000.00, NULL, 'parcial', '2026-03-08 13:22:56', '2026-03-31 18:36:41'),
(261, 37, 1, 10, '2026-04-04', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:22:56', '2026-03-08 13:22:56'),
(262, 38, 1, 1, '2026-01-31', 70000.00, 70000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 13:24:18', '2026-03-08 13:24:39'),
(263, 38, 1, 2, '2026-02-07', 70000.00, 70000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 13:24:18', '2026-03-08 13:24:39'),
(264, 38, 1, 3, '2026-02-14', 70000.00, 70000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 13:24:18', '2026-03-08 13:24:39'),
(265, 38, 1, 4, '2026-02-21', 70000.00, 70000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 13:24:18', '2026-03-08 13:24:39'),
(266, 38, 1, 5, '2026-02-28', 70000.00, 70000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 13:24:18', '2026-03-08 13:24:39'),
(267, 38, 1, 6, '2026-03-07', 70000.00, 70000.00, 0.00, '2026-03-07', 'pagado', '2026-03-08 13:24:18', '2026-03-09 08:16:03'),
(268, 38, 1, 7, '2026-03-14', 70000.00, 70000.00, 0.00, '2026-03-28', 'pagado', '2026-03-08 13:24:18', '2026-03-29 00:21:16'),
(269, 38, 1, 8, '2026-03-21', 70000.00, 70000.00, 0.00, '2026-03-21', 'pagado', '2026-03-08 13:24:18', '2026-03-22 08:13:05'),
(270, 38, 1, 9, '2026-03-28', 70000.00, 70000.00, 0.00, '2026-04-04', 'pagado', '2026-03-08 13:24:18', '2026-04-04 23:27:12'),
(271, 38, 1, 10, '2026-04-04', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:24:18', '2026-03-08 13:24:18'),
(272, 39, 1, 1, '2026-02-08', 110000.00, 110000.00, 0.00, '2026-02-08', 'pagado', '2026-03-08 13:35:12', '2026-03-08 13:35:31'),
(273, 40, 1, 1, '2026-03-20', 120000.00, 0.00, 120000.00, NULL, 'pendiente', '2026-03-08 13:36:41', '2026-03-08 13:36:41'),
(274, 40, 1, 2, '2026-04-04', 120000.00, 0.00, 120000.00, NULL, 'pendiente', '2026-03-08 13:36:41', '2026-03-08 13:36:41'),
(275, 41, 1, 1, '2026-03-29', 240000.00, 240000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 13:45:05', '2026-03-08 13:45:30'),
(284, 42, 1, 1, '2026-03-07', 70000.00, 54000.00, 16000.00, '2026-03-12', 'parcial', '2026-03-08 13:49:02', '2026-03-12 06:55:54'),
(285, 42, 1, 2, '2026-03-14', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:49:02', '2026-03-08 13:49:02'),
(286, 42, 1, 3, '2026-03-21', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:49:02', '2026-03-08 13:49:02'),
(287, 42, 1, 4, '2026-03-28', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:49:02', '2026-03-08 13:49:02'),
(288, 43, 1, 1, '2026-02-08', 70000.00, 70000.00, 0.00, '2026-02-22', 'pagado', '2026-03-08 13:53:53', '2026-03-08 13:54:37'),
(289, 43, 1, 2, '2026-02-15', 70000.00, 70000.00, 0.00, '2026-02-22', 'pagado', '2026-03-08 13:53:53', '2026-03-08 13:54:37'),
(290, 43, 1, 3, '2026-02-22', 70000.00, 70000.00, 0.00, '2026-02-22', 'pagado', '2026-03-08 13:53:53', '2026-03-08 13:54:37'),
(291, 43, 1, 4, '2026-03-01', 70000.00, 70000.00, 0.00, '2026-03-01', 'pagado', '2026-03-08 13:53:53', '2026-03-08 13:54:54'),
(292, 43, 1, 5, '2026-03-08', 70000.00, 70000.00, 0.00, '2026-03-11', 'pagado', '2026-03-08 13:53:53', '2026-03-12 06:54:43'),
(293, 43, 1, 6, '2026-03-15', 70000.00, 70000.00, 0.00, '2026-03-16', 'pagado', '2026-03-08 13:53:53', '2026-03-16 08:27:43'),
(294, 43, 1, 7, '2026-03-22', 70000.00, 70000.00, 0.00, '2026-03-24', 'pagado', '2026-03-08 13:53:53', '2026-03-24 22:04:20'),
(295, 43, 1, 8, '2026-03-29', 70000.00, 70000.00, 0.00, '2026-03-30', 'pagado', '2026-03-08 13:53:53', '2026-03-30 18:58:30'),
(296, 43, 1, 9, '2026-04-05', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:53:53', '2026-03-08 13:53:53'),
(297, 43, 1, 10, '2026-04-12', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:53:53', '2026-03-08 13:53:53'),
(298, 44, 1, 1, '2026-03-04', 360000.00, 360000.00, 0.00, '2026-03-04', 'pagado', '2026-03-08 13:58:52', '2026-03-08 13:59:05'),
(299, 45, 1, 1, '2026-03-03', 120000.00, 120000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:12:33', '2026-03-08 14:13:05'),
(300, 46, 1, 1, '2026-02-16', 120000.00, 120000.00, 0.00, '2026-03-03', 'pagado', '2026-03-08 14:14:22', '2026-03-08 14:15:01'),
(301, 46, 1, 2, '2026-03-03', 120000.00, 120000.00, 0.00, '2026-03-03', 'pagado', '2026-03-08 14:14:22', '2026-03-08 14:15:01'),
(302, 47, 1, 1, '2026-03-02', 140000.00, 140000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:16:36', '2026-03-08 14:16:57'),
(303, 47, 1, 2, '2026-03-17', 140000.00, 140000.00, 0.00, '2026-03-17', 'pagado', '2026-03-08 14:16:36', '2026-03-17 22:42:07'),
(304, 47, 1, 3, '2026-04-01', 140000.00, 140000.00, 0.00, '2026-04-05', 'pagado', '2026-03-08 14:16:36', '2026-04-05 21:30:47'),
(305, 47, 1, 4, '2026-04-16', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-03-08 14:16:36', '2026-03-08 14:16:36'),
(306, 47, 1, 5, '2026-05-01', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-03-08 14:16:36', '2026-03-08 14:16:36'),
(307, 48, 1, 1, '2026-03-07', 240000.00, 240000.00, 0.00, '2026-03-05', 'pagado', '2026-03-08 14:17:58', '2026-03-08 14:18:13'),
(308, 49, 1, 1, '2026-02-09', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(309, 49, 1, 2, '2026-02-10', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(310, 49, 1, 3, '2026-02-11', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(311, 49, 1, 4, '2026-02-12', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(312, 49, 1, 5, '2026-02-13', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(313, 49, 1, 6, '2026-02-14', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(314, 49, 1, 7, '2026-02-16', 20000.00, 20000.00, 0.00, '2026-03-13', 'pagado', '2026-03-08 14:19:12', '2026-03-14 07:09:16'),
(315, 49, 1, 8, '2026-02-17', 20000.00, 20000.00, 0.00, '2026-03-14', 'pagado', '2026-03-08 14:19:12', '2026-03-16 08:25:23'),
(316, 49, 1, 9, '2026-02-18', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(317, 49, 1, 10, '2026-02-19', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(318, 49, 1, 11, '2026-02-20', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(319, 49, 1, 12, '2026-02-21', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(320, 49, 1, 13, '2026-02-23', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(321, 49, 1, 14, '2026-02-24', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(322, 49, 1, 15, '2026-02-25', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(323, 49, 1, 16, '2026-02-26', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(324, 49, 1, 17, '2026-02-27', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(325, 49, 1, 18, '2026-02-28', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(326, 49, 1, 19, '2026-03-02', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(327, 49, 1, 20, '2026-03-03', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(328, 49, 1, 21, '2026-03-04', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(329, 49, 1, 22, '2026-03-05', 20000.00, 20000.00, 0.00, '2026-03-05', 'pagado', '2026-03-08 14:19:12', '2026-03-09 08:14:48'),
(330, 49, 1, 23, '2026-03-06', 20000.00, 20000.00, 0.00, '2026-03-09', 'pagado', '2026-03-08 14:19:12', '2026-03-09 08:14:57'),
(331, 49, 1, 24, '2026-03-07', 20000.00, 20000.00, 0.00, '2026-03-09', 'pagado', '2026-03-08 14:19:12', '2026-03-09 08:15:05'),
(332, 49, 1, 25, '2026-03-09', 20000.00, 20000.00, 0.00, '2026-03-09', 'pagado', '2026-03-08 14:19:12', '2026-03-10 23:21:51'),
(333, 49, 1, 26, '2026-03-10', 20000.00, 20000.00, 0.00, '2026-03-10', 'pagado', '2026-03-08 14:19:12', '2026-03-10 23:22:03'),
(334, 49, 1, 27, '2026-03-11', 20000.00, 20000.00, 0.00, '2026-03-11', 'pagado', '2026-03-08 14:19:12', '2026-03-12 06:54:08'),
(335, 49, 1, 28, '2026-03-12', 20000.00, 20000.00, 0.00, '2026-03-13', 'pagado', '2026-03-08 14:19:12', '2026-03-14 07:08:50'),
(336, 49, 1, 29, '2026-03-13', 20000.00, 20000.00, 0.00, '2026-03-17', 'pagado', '2026-03-08 14:19:12', '2026-03-17 22:43:24'),
(337, 49, 1, 30, '2026-03-14', 20000.00, 20000.00, 0.00, '2026-03-18', 'pagado', '2026-03-08 14:19:12', '2026-03-18 20:29:48'),
(338, 49, 1, 31, '2026-03-16', 20000.00, 20000.00, 0.00, '2026-03-18', 'pagado', '2026-03-08 14:19:12', '2026-03-20 08:56:31'),
(339, 49, 1, 32, '2026-03-17', 20000.00, 20000.00, 0.00, '2026-03-20', 'pagado', '2026-03-08 14:19:12', '2026-03-20 22:42:52'),
(340, 49, 1, 33, '2026-03-18', 20000.00, 20000.00, 0.00, '2026-03-20', 'pagado', '2026-03-08 14:19:12', '2026-03-20 22:43:03'),
(341, 49, 1, 34, '2026-03-19', 20000.00, 20000.00, 0.00, '2026-03-21', 'pagado', '2026-03-08 14:19:12', '2026-03-22 08:12:25'),
(342, 49, 1, 35, '2026-03-20', 20000.00, 20000.00, 0.00, '2026-03-25', 'pagado', '2026-03-08 14:19:12', '2026-03-26 10:04:01'),
(343, 49, 1, 36, '2026-03-21', 20000.00, 20000.00, 0.00, '2026-03-25', 'pagado', '2026-03-08 14:19:12', '2026-03-26 10:04:11'),
(344, 49, 1, 37, '2026-03-23', 20000.00, 20000.00, 0.00, '2026-03-26', 'pagado', '2026-03-08 14:19:12', '2026-03-27 10:37:26'),
(345, 49, 1, 38, '2026-03-24', 20000.00, 20000.00, 0.00, '2026-03-27', 'pagado', '2026-03-08 14:19:12', '2026-03-28 00:49:58'),
(346, 49, 1, 39, '2026-03-25', 20000.00, 20000.00, 0.00, '2026-03-29', 'pagado', '2026-03-08 14:19:12', '2026-03-29 21:05:34'),
(347, 49, 1, 40, '2026-03-26', 20000.00, 20000.00, 0.00, '2026-03-29', 'pagado', '2026-03-08 14:19:12', '2026-03-29 21:05:34'),
(348, 49, 1, 41, '2026-03-27', 20000.00, 20000.00, 0.00, '2026-03-29', 'pagado', '2026-03-08 14:19:12', '2026-03-29 21:05:34'),
(349, 49, 1, 42, '2026-03-28', 20000.00, 20000.00, 0.00, '2026-03-29', 'pagado', '2026-03-08 14:19:12', '2026-03-29 21:05:34'),
(350, 49, 1, 43, '2026-03-30', 20000.00, 20000.00, 0.00, '2026-03-29', 'pagado', '2026-03-08 14:19:12', '2026-03-29 21:05:34'),
(351, 49, 1, 44, '2026-03-31', 20000.00, 20000.00, 0.00, '2026-03-29', 'pagado', '2026-03-08 14:19:12', '2026-03-29 21:05:34'),
(352, 49, 1, 45, '2026-04-01', 20000.00, 20000.00, 0.00, '2026-03-29', 'pagado', '2026-03-08 14:19:12', '2026-03-29 21:05:34'),
(353, 50, 1, 1, '2026-03-31', 1210000.00, 626000.00, 584000.00, '2026-04-06', 'parcial', '2026-03-08 14:23:24', '2026-04-06 22:59:20'),
(354, 51, 1, 1, '2026-02-28', 315000.00, 315000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:25:55', '2026-03-08 14:26:12'),
(355, 52, 1, 1, '2026-03-30', 360000.00, 360000.00, 0.00, '2026-03-28', 'pagado', '2026-03-08 14:26:51', '2026-03-28 16:21:07'),
(356, 53, 1, 1, '2026-03-16', 550000.00, 550000.00, 0.00, '2026-03-16', 'pagado', '2026-03-08 14:27:42', '2026-03-16 22:02:07'),
(357, 54, 1, 1, '2026-03-31', 240000.00, 0.00, 240000.00, NULL, 'pendiente', '2026-03-08 14:28:29', '2026-03-08 14:28:29'),
(358, 55, 1, 1, '2026-04-01', 120000.00, 120000.00, 0.00, '2026-03-20', 'pagado', '2026-03-08 14:29:04', '2026-03-20 08:59:18'),
(359, 56, 1, 1, '2026-04-01', 300000.00, 300000.00, 0.00, '2026-03-30', 'pagado', '2026-03-08 14:29:35', '2026-03-30 18:59:17'),
(360, 57, 1, 1, '2026-03-17', 460000.00, 460000.00, 0.00, '2026-03-17', 'pagado', '2026-03-08 14:30:29', '2026-03-17 22:40:26'),
(361, 57, 1, 2, '2026-04-01', 460000.00, 460000.00, 0.00, '2026-03-31', 'pagado', '2026-03-08 14:30:29', '2026-03-31 11:16:59'),
(362, 58, 1, 1, '2026-03-19', 220000.00, 220000.00, 0.00, '2026-03-06', 'pagado', '2026-03-08 14:31:18', '2026-03-08 14:31:30'),
(363, 59, 1, 1, '2026-03-14', 70000.00, 70000.00, 0.00, '2026-03-14', 'pagado', '2026-03-08 14:35:31', '2026-03-16 08:27:24'),
(364, 59, 1, 2, '2026-03-21', 70000.00, 70000.00, 0.00, '2026-03-22', 'pagado', '2026-03-08 14:35:31', '2026-03-23 07:05:00'),
(365, 59, 1, 3, '2026-03-28', 70000.00, 70000.00, 0.00, '2026-03-28', 'pagado', '2026-03-08 14:35:31', '2026-03-29 00:20:37'),
(366, 59, 1, 4, '2026-04-04', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 14:35:31', '2026-03-08 14:35:31'),
(367, 59, 1, 5, '2026-04-11', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 14:35:31', '2026-03-08 14:35:31'),
(368, 59, 1, 6, '2026-04-18', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 14:35:31', '2026-03-08 14:35:31'),
(369, 59, 1, 7, '2026-04-25', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 14:35:31', '2026-03-08 14:35:31'),
(370, 59, 1, 8, '2026-05-02', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 14:35:31', '2026-03-08 14:35:31'),
(371, 59, 1, 9, '2026-05-09', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 14:35:31', '2026-03-08 14:35:31'),
(372, 59, 1, 10, '2026-05-16', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 14:35:31', '2026-03-08 14:35:31'),
(373, 60, 1, 1, '2026-03-16', 97500.00, 97500.00, 0.00, '2026-03-27', 'pagado', '2026-03-08 14:37:09', '2026-03-27 20:23:01'),
(374, 60, 1, 2, '2026-03-31', 97500.00, 97500.00, 0.00, '2026-03-27', 'pagado', '2026-03-08 14:37:09', '2026-03-27 20:23:01'),
(375, 60, 1, 3, '2026-04-15', 97500.00, 5000.00, 92500.00, NULL, 'parcial', '2026-03-08 14:37:09', '2026-03-27 20:23:01'),
(376, 60, 1, 4, '2026-04-30', 97500.00, 0.00, 97500.00, NULL, 'pendiente', '2026-03-08 14:37:09', '2026-03-08 14:37:09'),
(377, 60, 1, 5, '2026-05-15', 97500.00, 0.00, 97500.00, NULL, 'pendiente', '2026-03-08 14:37:09', '2026-03-08 14:37:09'),
(378, 60, 1, 6, '2026-05-30', 97500.00, 0.00, 97500.00, NULL, 'pendiente', '2026-03-08 14:37:09', '2026-03-08 14:37:09'),
(379, 60, 1, 7, '2026-06-14', 97500.00, 0.00, 97500.00, NULL, 'pendiente', '2026-03-08 14:37:09', '2026-03-08 14:37:09'),
(380, 60, 1, 8, '2026-06-29', 97500.00, 0.00, 97500.00, NULL, 'pendiente', '2026-03-08 14:37:09', '2026-03-08 14:37:09'),
(381, 61, 1, 1, '2026-03-22', 220000.00, 220000.00, 0.00, '2026-03-20', 'pagado', '2026-03-08 14:37:55', '2026-03-20 22:42:07'),
(382, 62, 1, 1, '2026-03-22', 330000.00, 330000.00, 0.00, '2026-04-01', 'pagado', '2026-03-08 14:38:46', '2026-04-02 09:49:03'),
(384, 64, 1, 1, '2026-03-31', 250000.00, 250000.00, 0.00, '2026-04-09', 'pagado', '2026-03-08 14:42:14', '2026-04-09 10:18:24'),
(385, 64, 1, 2, '2026-04-30', 250000.00, 0.00, 250000.00, NULL, 'pendiente', '2026-03-08 14:42:14', '2026-03-08 14:42:14'),
(386, 65, 1, 1, '2026-04-07', 300000.00, 0.00, 300000.00, NULL, 'pendiente', '2026-03-08 15:44:42', '2026-03-08 15:44:42'),
(387, 66, 1, 1, '2026-03-21', 70000.00, 70000.00, 0.00, '2026-03-20', 'pagado', '2026-03-11 14:44:55', '2026-03-20 08:58:24'),
(388, 66, 1, 2, '2026-03-28', 70000.00, 70000.00, 0.00, '2026-03-28', 'pagado', '2026-03-11 14:44:55', '2026-03-28 13:30:01'),
(389, 66, 1, 3, '2026-04-04', 70000.00, 70000.00, 0.00, '2026-04-04', 'pagado', '2026-03-11 14:44:55', '2026-04-04 23:34:52'),
(390, 66, 1, 4, '2026-04-11', 70000.00, 20000.00, 50000.00, NULL, 'parcial', '2026-03-11 14:44:55', '2026-04-04 23:34:52'),
(391, 66, 1, 5, '2026-04-18', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-11 14:44:55', '2026-03-11 14:44:55'),
(392, 66, 1, 6, '2026-04-25', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-11 14:44:55', '2026-03-11 14:44:55'),
(393, 66, 1, 7, '2026-05-02', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-11 14:44:55', '2026-03-11 14:44:55'),
(394, 66, 1, 8, '2026-05-09', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-11 14:44:55', '2026-03-11 14:44:55'),
(395, 67, 1, 1, '2026-03-30', 260000.00, 260000.00, 0.00, '2026-03-30', 'pagado', '2026-03-13 08:41:39', '2026-03-30 18:59:57'),
(396, 67, 1, 2, '2026-04-14', 260000.00, 0.00, 260000.00, NULL, 'pendiente', '2026-03-13 08:41:39', '2026-03-13 08:41:39'),
(397, 67, 1, 3, '2026-04-29', 260000.00, 0.00, 260000.00, NULL, 'pendiente', '2026-03-13 08:41:39', '2026-03-13 08:41:39'),
(398, 67, 1, 4, '2026-05-14', 260000.00, 0.00, 260000.00, NULL, 'pendiente', '2026-03-13 08:41:39', '2026-03-13 08:41:39'),
(399, 67, 1, 5, '2026-05-29', 260000.00, 0.00, 260000.00, NULL, 'pendiente', '2026-03-13 08:41:39', '2026-03-13 08:41:39'),
(400, 68, 1, 1, '2026-04-13', 120000.00, 120000.00, 0.00, '2026-03-20', 'pagado', '2026-03-13 08:42:55', '2026-03-20 08:57:57'),
(401, 69, 1, 1, '2026-03-20', 325000.00, 325000.00, 0.00, '2026-03-20', 'pagado', '2026-03-13 09:07:40', '2026-03-20 16:18:46'),
(402, 69, 1, 2, '2026-04-04', 325000.00, 190000.00, 135000.00, '2026-04-01', 'parcial', '2026-03-13 09:07:40', '2026-04-02 09:50:24'),
(403, 69, 1, 3, '2026-04-19', 325000.00, 0.00, 325000.00, NULL, 'pendiente', '2026-03-13 09:07:40', '2026-03-13 09:07:40'),
(404, 70, 2, 1, '2026-03-30', 440000.00, 0.00, 440000.00, NULL, 'pendiente', '2026-03-14 14:49:21', '2026-03-14 14:49:21'),
(405, 71, 2, 1, '2026-03-21', 50000.00, 50000.00, 0.00, '2026-03-20', 'pagado', '2026-03-16 08:30:11', '2026-03-20 22:45:11'),
(406, 71, 2, 2, '2026-03-28', 50000.00, 50000.00, 0.00, '2026-03-27', 'pagado', '2026-03-16 08:30:11', '2026-03-28 00:51:37'),
(407, 71, 2, 3, '2026-04-04', 50000.00, 50000.00, 0.00, '2026-04-05', 'pagado', '2026-03-16 08:30:11', '2026-04-05 21:32:08'),
(408, 71, 2, 4, '2026-04-11', 50000.00, 0.00, 50000.00, NULL, 'pendiente', '2026-03-16 08:30:11', '2026-03-16 08:30:11'),
(409, 71, 2, 5, '2026-04-18', 50000.00, 0.00, 50000.00, NULL, 'pendiente', '2026-03-16 08:30:11', '2026-03-16 08:30:11'),
(412, 72, 1, 1, '2026-03-31', 55000.00, 55000.00, 0.00, '2026-04-01', 'pagado', '2026-03-20 16:24:33', '2026-04-02 09:49:28'),
(414, 74, 1, 1, '2026-04-19', 300000.00, 300000.00, 0.00, '2026-03-22', 'pagado', '2026-03-20 22:50:06', '2026-03-22 08:17:13'),
(415, 74, 1, 2, '2026-05-19', 300000.00, 300000.00, 0.00, '2026-03-24', 'pagado', '2026-03-20 22:50:06', '2026-03-24 11:40:49'),
(416, 74, 1, 3, '2026-06-18', 300000.00, 0.00, 300000.00, NULL, 'pendiente', '2026-03-20 22:50:06', '2026-03-20 22:50:06'),
(417, 74, 1, 4, '2026-07-18', 300000.00, 0.00, 300000.00, NULL, 'pendiente', '2026-03-20 22:50:06', '2026-03-20 22:50:06'),
(418, 75, 1, 1, '2026-04-05', 110000.00, 110000.00, 0.00, '2026-03-31', 'pagado', '2026-03-21 08:41:18', '2026-03-31 14:39:25'),
(419, 76, 1, 1, '2026-04-09', 120000.00, 0.00, 120000.00, NULL, 'pendiente', '2026-03-21 15:11:29', '2026-03-21 15:11:29'),
(420, 76, 1, 2, '2026-04-24', 120000.00, 0.00, 120000.00, NULL, 'pendiente', '2026-03-21 15:11:29', '2026-03-21 15:11:29'),
(421, 77, 1, 1, '2026-04-20', 120000.00, 120000.00, 0.00, '2026-03-30', 'pagado', '2026-03-21 15:49:49', '2026-03-30 14:59:20'),
(422, 77, 1, 2, '2026-05-20', 120000.00, 0.00, 120000.00, NULL, 'pendiente', '2026-03-21 15:49:49', '2026-03-21 15:49:49'),
(423, 63, 1, 1, '2026-04-07', 480000.00, 480000.00, 0.00, '2026-04-03', 'pagado', '2026-03-21 17:54:26', '2026-04-03 13:01:21'),
(424, 78, 1, 1, '2026-04-05', 440000.00, 400000.00, 40000.00, '2026-04-03', 'parcial', '2026-03-21 17:55:32', '2026-04-03 13:07:24'),
(425, 79, 1, 1, '2026-04-07', 220000.00, 220000.00, 0.00, '2026-04-07', 'pagado', '2026-03-23 16:42:36', '2026-04-07 22:28:05'),
(426, 73, 1, 1, '2026-04-04', 220000.00, 220000.00, 0.00, '2026-04-07', 'pagado', '2026-03-23 16:43:13', '2026-04-07 22:27:48'),
(427, 80, 1, 1, '2026-03-25', 10000.00, 10000.00, 0.00, '2026-03-25', 'pagado', '2026-03-24 22:02:00', '2026-03-26 10:05:20'),
(428, 80, 1, 2, '2026-03-26', 10000.00, 10000.00, 0.00, '2026-03-26', 'pagado', '2026-03-24 22:02:00', '2026-03-27 10:36:58'),
(429, 80, 1, 3, '2026-03-27', 10000.00, 10000.00, 0.00, '2026-03-27', 'pagado', '2026-03-24 22:02:00', '2026-03-28 00:50:43'),
(430, 80, 1, 4, '2026-03-28', 10000.00, 10000.00, 0.00, '2026-03-28', 'pagado', '2026-03-24 22:02:00', '2026-03-29 00:20:12'),
(431, 80, 1, 5, '2026-03-29', 10000.00, 10000.00, 0.00, '2026-03-31', 'pagado', '2026-03-24 22:02:00', '2026-03-31 11:51:43'),
(432, 80, 1, 6, '2026-03-30', 10000.00, 10000.00, 0.00, '2026-03-31', 'pagado', '2026-03-24 22:02:00', '2026-03-31 11:51:43'),
(433, 80, 1, 7, '2026-03-31', 10000.00, 10000.00, 0.00, '2026-04-02', 'pagado', '2026-03-24 22:02:00', '2026-04-03 12:56:25'),
(434, 80, 1, 8, '2026-04-01', 10000.00, 10000.00, 0.00, '2026-04-02', 'pagado', '2026-03-24 22:02:00', '2026-04-03 12:56:25'),
(435, 80, 1, 9, '2026-04-02', 10000.00, 10000.00, 0.00, '2026-04-05', 'pagado', '2026-03-24 22:02:00', '2026-04-06 23:01:33'),
(436, 80, 1, 10, '2026-04-03', 10000.00, 10000.00, 0.00, '2026-04-05', 'pagado', '2026-03-24 22:02:00', '2026-04-06 23:01:33'),
(437, 80, 1, 11, '2026-04-04', 10000.00, 10000.00, 0.00, '2026-04-07', 'pagado', '2026-03-24 22:02:00', '2026-04-07 22:27:11'),
(438, 80, 1, 12, '2026-04-05', 10000.00, 10000.00, 0.00, '2026-04-07', 'pagado', '2026-03-24 22:02:00', '2026-04-07 22:27:11'),
(439, 80, 1, 13, '2026-04-06', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(440, 80, 1, 14, '2026-04-07', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(441, 80, 1, 15, '2026-04-08', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(442, 80, 1, 16, '2026-04-09', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(443, 80, 1, 17, '2026-04-10', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(444, 80, 1, 18, '2026-04-11', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(445, 80, 1, 19, '2026-04-12', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(446, 80, 1, 20, '2026-04-13', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(447, 80, 1, 21, '2026-04-14', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(448, 80, 1, 22, '2026-04-15', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(449, 80, 1, 23, '2026-04-16', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(450, 80, 1, 24, '2026-04-17', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(451, 80, 1, 25, '2026-04-18', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(452, 80, 1, 26, '2026-04-19', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(453, 80, 1, 27, '2026-04-20', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(454, 80, 1, 28, '2026-04-21', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(455, 80, 1, 29, '2026-04-22', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(456, 80, 1, 30, '2026-04-23', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(457, 80, 1, 31, '2026-04-24', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(458, 80, 1, 32, '2026-04-25', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(459, 80, 1, 33, '2026-04-26', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(460, 80, 1, 34, '2026-04-27', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(461, 80, 1, 35, '2026-04-28', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(462, 80, 1, 36, '2026-04-29', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(463, 80, 1, 37, '2026-04-30', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(464, 80, 1, 38, '2026-05-01', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(465, 80, 1, 39, '2026-05-02', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(466, 80, 1, 40, '2026-05-03', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(467, 80, 1, 41, '2026-05-04', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(468, 80, 1, 42, '2026-05-05', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(469, 80, 1, 43, '2026-05-06', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(470, 80, 1, 44, '2026-05-07', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(471, 80, 1, 45, '2026-05-08', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(472, 80, 1, 46, '2026-05-09', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(473, 80, 1, 47, '2026-05-10', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(474, 80, 1, 48, '2026-05-11', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(475, 80, 1, 49, '2026-05-12', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(476, 80, 1, 50, '2026-05-13', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(477, 80, 1, 51, '2026-05-14', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(478, 80, 1, 52, '2026-05-15', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(479, 80, 1, 53, '2026-05-16', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(480, 80, 1, 54, '2026-05-17', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(481, 80, 1, 55, '2026-05-18', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(482, 80, 1, 56, '2026-05-19', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(483, 80, 1, 57, '2026-05-20', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(484, 80, 1, 58, '2026-05-21', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(485, 80, 1, 59, '2026-05-22', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(486, 80, 1, 60, '2026-05-23', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(487, 80, 1, 61, '2026-05-24', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(488, 80, 1, 62, '2026-05-25', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(489, 80, 1, 63, '2026-05-26', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(490, 80, 1, 64, '2026-05-27', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(491, 80, 1, 65, '2026-05-28', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(492, 80, 1, 66, '2026-05-29', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(493, 80, 1, 67, '2026-05-30', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(494, 80, 1, 68, '2026-05-31', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(495, 80, 1, 69, '2026-06-01', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(496, 80, 1, 70, '2026-06-02', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-03-24 22:02:00', '2026-03-24 22:02:00'),
(497, 81, 2, 1, '2026-04-24', 465000.00, 0.00, 465000.00, NULL, 'pendiente', '2026-03-25 15:09:14', '2026-03-25 15:09:14'),
(498, 81, 2, 2, '2026-05-24', 465000.00, 0.00, 465000.00, NULL, 'pendiente', '2026-03-25 15:09:14', '2026-03-25 15:09:14'),
(499, 81, 2, 3, '2026-06-23', 465000.00, 0.00, 465000.00, NULL, 'pendiente', '2026-03-25 15:09:14', '2026-03-25 15:09:14'),
(500, 81, 2, 4, '2026-07-23', 465000.00, 0.00, 465000.00, NULL, 'pendiente', '2026-03-25 15:09:14', '2026-03-25 15:09:14'),
(501, 81, 2, 5, '2026-08-22', 465000.00, 0.00, 465000.00, NULL, 'pendiente', '2026-03-25 15:09:14', '2026-03-25 15:09:14'),
(502, 81, 2, 6, '2026-09-21', 465000.00, 0.00, 465000.00, NULL, 'pendiente', '2026-03-25 15:09:14', '2026-03-25 15:09:14'),
(503, 82, 1, 1, '2026-04-11', 270000.00, 270000.00, 0.00, '2026-03-29', 'pagado', '2026-03-27 10:26:59', '2026-03-29 14:32:37'),
(504, 82, 1, 2, '2026-04-26', 270000.00, 270000.00, 0.00, '2026-03-29', 'pagado', '2026-03-27 10:26:59', '2026-03-29 14:32:37'),
(505, 82, 1, 3, '2026-05-11', 270000.00, 160000.00, 110000.00, NULL, 'parcial', '2026-03-27 10:26:59', '2026-03-29 14:32:37'),
(506, 82, 1, 4, '2026-05-26', 270000.00, 0.00, 270000.00, NULL, 'pendiente', '2026-03-27 10:26:59', '2026-03-27 10:26:59'),
(507, 82, 1, 5, '2026-06-10', 270000.00, 0.00, 270000.00, NULL, 'pendiente', '2026-03-27 10:26:59', '2026-03-27 10:26:59'),
(508, 82, 1, 6, '2026-06-25', 270000.00, 0.00, 270000.00, NULL, 'pendiente', '2026-03-27 10:26:59', '2026-03-27 10:26:59'),
(509, 82, 1, 7, '2026-07-10', 270000.00, 0.00, 270000.00, NULL, 'pendiente', '2026-03-27 10:26:59', '2026-03-27 10:26:59'),
(510, 82, 1, 8, '2026-07-25', 270000.00, 0.00, 270000.00, NULL, 'pendiente', '2026-03-27 10:26:59', '2026-03-27 10:26:59'),
(511, 82, 1, 9, '2026-08-09', 270000.00, 0.00, 270000.00, NULL, 'pendiente', '2026-03-27 10:26:59', '2026-03-27 10:26:59'),
(512, 82, 1, 10, '2026-08-24', 270000.00, 0.00, 270000.00, NULL, 'pendiente', '2026-03-27 10:26:59', '2026-03-27 10:26:59'),
(513, 83, 2, 1, '2026-04-11', 250000.00, 0.00, 250000.00, NULL, 'pendiente', '2026-03-27 10:28:32', '2026-03-27 10:28:32'),
(514, 83, 2, 2, '2026-04-26', 250000.00, 0.00, 250000.00, NULL, 'pendiente', '2026-03-27 10:28:32', '2026-03-27 10:28:32'),
(515, 83, 2, 3, '2026-05-11', 250000.00, 0.00, 250000.00, NULL, 'pendiente', '2026-03-27 10:28:32', '2026-03-27 10:28:32'),
(516, 83, 2, 4, '2026-05-26', 250000.00, 0.00, 250000.00, NULL, 'pendiente', '2026-03-27 10:28:32', '2026-03-27 10:28:32'),
(517, 83, 2, 5, '2026-06-10', 250000.00, 0.00, 250000.00, NULL, 'pendiente', '2026-03-27 10:28:32', '2026-03-27 10:28:32'),
(518, 83, 2, 6, '2026-06-25', 250000.00, 0.00, 250000.00, NULL, 'pendiente', '2026-03-27 10:28:32', '2026-03-27 10:28:32'),
(519, 83, 2, 7, '2026-07-10', 250000.00, 0.00, 250000.00, NULL, 'pendiente', '2026-03-27 10:28:32', '2026-03-27 10:28:32'),
(520, 83, 2, 8, '2026-07-25', 250000.00, 0.00, 250000.00, NULL, 'pendiente', '2026-03-27 10:28:32', '2026-03-27 10:28:32'),
(521, 83, 2, 9, '2026-08-09', 250000.00, 0.00, 250000.00, NULL, 'pendiente', '2026-03-27 10:28:32', '2026-03-27 10:28:32'),
(522, 83, 2, 10, '2026-08-24', 250000.00, 0.00, 250000.00, NULL, 'pendiente', '2026-03-27 10:28:32', '2026-03-27 10:28:32'),
(523, 84, 1, 1, '2026-04-27', 360000.00, 0.00, 360000.00, NULL, 'pendiente', '2026-03-28 16:21:46', '2026-03-28 16:21:46'),
(524, 85, 2, 1, '2026-04-27', 540000.00, 0.00, 540000.00, NULL, 'pendiente', '2026-03-28 16:28:23', '2026-03-28 16:28:23'),
(525, 86, 1, 1, '2026-04-20', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-03-29 14:31:40', '2026-03-29 14:31:40'),
(526, 86, 1, 2, '2026-05-05', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-03-29 14:31:40', '2026-03-29 14:31:40'),
(527, 86, 1, 3, '2026-05-20', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-03-29 14:31:40', '2026-03-29 14:31:40'),
(528, 86, 1, 4, '2026-06-04', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-03-29 14:31:40', '2026-03-29 14:31:40'),
(529, 86, 1, 5, '2026-06-19', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-03-29 14:31:40', '2026-03-29 14:31:40'),
(530, 86, 1, 6, '2026-07-04', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-03-29 14:31:40', '2026-03-29 14:31:40'),
(531, 86, 1, 7, '2026-07-19', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-03-29 14:31:40', '2026-03-29 14:31:40'),
(532, 86, 1, 8, '2026-08-03', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-03-29 14:31:40', '2026-03-29 14:31:40'),
(533, 86, 1, 9, '2026-08-18', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-03-29 14:31:40', '2026-03-29 14:31:40'),
(534, 86, 1, 10, '2026-09-02', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-03-29 14:31:40', '2026-03-29 14:31:40'),
(535, 87, 1, 1, '2026-03-30', 35000.00, 35000.00, 0.00, '2026-03-31', 'pagado', '2026-03-29 21:04:58', '2026-03-31 09:01:12'),
(536, 87, 1, 2, '2026-03-31', 35000.00, 35000.00, 0.00, '2026-03-31', 'pagado', '2026-03-29 21:04:58', '2026-04-02 09:46:40'),
(537, 87, 1, 3, '2026-04-01', 35000.00, 35000.00, 0.00, '2026-04-01', 'pagado', '2026-03-29 21:04:58', '2026-04-02 09:47:06'),
(538, 87, 1, 4, '2026-04-02', 35000.00, 35000.00, 0.00, '2026-04-04', 'pagado', '2026-03-29 21:04:58', '2026-04-04 23:32:20'),
(539, 87, 1, 5, '2026-04-03', 35000.00, 35000.00, 0.00, '2026-04-06', 'pagado', '2026-03-29 21:04:58', '2026-04-06 23:00:27'),
(540, 87, 1, 6, '2026-04-04', 35000.00, 35000.00, 0.00, '2026-04-07', 'pagado', '2026-03-29 21:04:58', '2026-04-07 23:02:42'),
(541, 87, 1, 7, '2026-04-06', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(542, 87, 1, 8, '2026-04-07', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(543, 87, 1, 9, '2026-04-08', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(544, 87, 1, 10, '2026-04-09', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(545, 87, 1, 11, '2026-04-10', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(546, 87, 1, 12, '2026-04-11', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(547, 87, 1, 13, '2026-04-13', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(548, 87, 1, 14, '2026-04-14', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(549, 87, 1, 15, '2026-04-15', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(550, 87, 1, 16, '2026-04-16', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(551, 87, 1, 17, '2026-04-17', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(552, 87, 1, 18, '2026-04-18', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(553, 87, 1, 19, '2026-04-20', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(554, 87, 1, 20, '2026-04-21', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(555, 87, 1, 21, '2026-04-22', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(556, 87, 1, 22, '2026-04-23', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(557, 87, 1, 23, '2026-04-24', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(558, 87, 1, 24, '2026-04-25', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(559, 87, 1, 25, '2026-04-27', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(560, 87, 1, 26, '2026-04-28', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(561, 87, 1, 27, '2026-04-29', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(562, 87, 1, 28, '2026-04-30', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(563, 87, 1, 29, '2026-05-01', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(564, 87, 1, 30, '2026-05-02', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(565, 87, 1, 31, '2026-05-04', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(566, 87, 1, 32, '2026-05-05', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(567, 87, 1, 33, '2026-05-06', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(568, 87, 1, 34, '2026-05-07', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(569, 87, 1, 35, '2026-05-08', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(570, 87, 1, 36, '2026-05-09', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(571, 87, 1, 37, '2026-05-11', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(572, 87, 1, 38, '2026-05-12', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(573, 87, 1, 39, '2026-05-13', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(574, 87, 1, 40, '2026-05-14', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(575, 87, 1, 41, '2026-05-15', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(576, 87, 1, 42, '2026-05-16', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(577, 87, 1, 43, '2026-05-18', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(578, 87, 1, 44, '2026-05-19', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(579, 87, 1, 45, '2026-05-20', 35000.00, 0.00, 35000.00, NULL, 'pendiente', '2026-03-29 21:04:58', '2026-03-29 21:04:58'),
(580, 88, 1, 1, '2026-04-29', 120000.00, 0.00, 120000.00, NULL, 'pendiente', '2026-03-30 08:49:28', '2026-03-30 08:49:28'),
(581, 89, 2, 1, '2026-04-29', 336000.00, 0.00, 336000.00, NULL, 'pendiente', '2026-03-30 14:57:57', '2026-03-30 14:57:57'),
(582, 90, 1, 1, '2026-04-30', 230000.00, 0.00, 230000.00, NULL, 'pendiente', '2026-03-31 11:25:47', '2026-03-31 11:25:47'),
(584, 92, 2, 1, '2026-04-15', 355000.00, 0.00, 355000.00, NULL, 'anulado', '2026-03-31 16:18:12', '2026-03-31 16:19:55'),
(585, 92, 2, 2, '2026-04-30', 355000.00, 0.00, 355000.00, NULL, 'anulado', '2026-03-31 16:18:12', '2026-03-31 16:19:55'),
(586, 92, 2, 3, '2026-05-15', 355000.00, 0.00, 355000.00, NULL, 'anulado', '2026-03-31 16:18:12', '2026-03-31 16:19:55'),
(587, 92, 2, 4, '2026-05-30', 355000.00, 0.00, 355000.00, NULL, 'anulado', '2026-03-31 16:18:12', '2026-03-31 16:19:55'),
(588, 92, 2, 5, '2026-06-14', 355000.00, 0.00, 355000.00, NULL, 'anulado', '2026-03-31 16:18:12', '2026-03-31 16:19:55'),
(589, 92, 2, 6, '2026-06-29', 355000.00, 0.00, 355000.00, NULL, 'anulado', '2026-03-31 16:18:12', '2026-03-31 16:19:55'),
(590, 91, 2, 1, '2026-04-15', 218000.00, 0.00, 218000.00, NULL, 'pendiente', '2026-03-31 16:19:10', '2026-03-31 16:19:10'),
(591, 91, 2, 2, '2026-04-30', 218000.00, 0.00, 218000.00, NULL, 'pendiente', '2026-03-31 16:19:10', '2026-03-31 16:19:10'),
(592, 93, 1, 1, '2026-04-18', 220000.00, 0.00, 220000.00, NULL, 'pendiente', '2026-04-03 13:05:25', '2026-04-03 13:05:25'),
(593, 94, 1, 1, '2026-04-05', 85000.00, 0.00, 85000.00, NULL, 'pendiente', '2026-04-03 13:09:14', '2026-04-03 13:09:14'),
(594, 94, 1, 2, '2026-04-12', 85000.00, 0.00, 85000.00, NULL, 'pendiente', '2026-04-03 13:09:14', '2026-04-03 13:09:14'),
(595, 94, 1, 3, '2026-04-19', 85000.00, 0.00, 85000.00, NULL, 'pendiente', '2026-04-03 13:09:14', '2026-04-03 13:09:14'),
(596, 94, 1, 4, '2026-04-26', 85000.00, 0.00, 85000.00, NULL, 'pendiente', '2026-04-03 13:09:14', '2026-04-03 13:09:14');
INSERT INTO `cuotas` (`id`, `prestamo_id`, `cobro_id`, `numero_cuota`, `fecha_vencimiento`, `monto_cuota`, `monto_pagado`, `saldo_cuota`, `fecha_pago`, `estado`, `created_at`, `updated_at`) VALUES
(597, 94, 1, 5, '2026-05-03', 85000.00, 0.00, 85000.00, NULL, 'pendiente', '2026-04-03 13:09:14', '2026-04-03 13:09:14'),
(598, 94, 1, 6, '2026-05-10', 85000.00, 0.00, 85000.00, NULL, 'pendiente', '2026-04-03 13:09:14', '2026-04-03 13:09:14'),
(599, 95, 1, 1, '2026-04-16', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-04-03 13:25:14', '2026-04-03 13:25:14'),
(600, 95, 1, 2, '2026-05-01', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-04-03 13:25:14', '2026-04-03 13:25:14'),
(601, 95, 1, 3, '2026-05-16', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-04-03 13:25:14', '2026-04-03 13:25:14'),
(602, 95, 1, 4, '2026-05-31', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-04-03 13:25:14', '2026-04-03 13:25:14'),
(603, 95, 1, 5, '2026-06-15', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-04-03 13:25:14', '2026-04-03 13:25:14'),
(614, 96, 1, 1, '2026-04-16', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-04-06 23:14:44', '2026-04-06 23:14:44'),
(615, 96, 1, 2, '2026-05-01', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-04-06 23:14:44', '2026-04-06 23:14:44'),
(616, 96, 1, 3, '2026-05-16', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-04-06 23:14:44', '2026-04-06 23:14:44'),
(617, 96, 1, 4, '2026-05-31', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-04-06 23:14:44', '2026-04-06 23:14:44'),
(618, 96, 1, 5, '2026-06-15', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-04-06 23:14:44', '2026-04-06 23:14:44'),
(619, 96, 1, 6, '2026-06-30', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-04-06 23:14:44', '2026-04-06 23:14:44'),
(620, 96, 1, 7, '2026-07-15', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-04-06 23:14:44', '2026-04-06 23:14:44'),
(621, 96, 1, 8, '2026-07-30', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-04-06 23:14:44', '2026-04-06 23:14:44'),
(622, 96, 1, 9, '2026-08-14', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-04-06 23:14:44', '2026-04-06 23:14:44'),
(623, 96, 1, 10, '2026-08-29', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-04-06 23:14:44', '2026-04-06 23:14:44'),
(624, 97, 1, 1, '2026-04-14', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-04-07 22:19:23', '2026-04-07 22:19:23'),
(625, 97, 1, 2, '2026-04-21', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-04-07 22:19:23', '2026-04-07 22:19:23'),
(626, 97, 1, 3, '2026-04-28', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-04-07 22:19:23', '2026-04-07 22:19:23'),
(627, 97, 1, 4, '2026-05-05', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-04-07 22:19:23', '2026-04-07 22:19:23'),
(628, 97, 1, 5, '2026-05-12', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-04-07 22:19:23', '2026-04-07 22:19:23'),
(629, 97, 1, 6, '2026-05-19', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-04-07 22:19:23', '2026-04-07 22:19:23'),
(630, 97, 1, 7, '2026-05-26', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-04-07 22:19:23', '2026-04-07 22:19:23'),
(631, 97, 1, 8, '2026-06-02', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-04-07 22:19:23', '2026-04-07 22:19:23'),
(632, 97, 1, 9, '2026-06-09', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-04-07 22:19:23', '2026-04-07 22:19:23'),
(633, 97, 1, 10, '2026-06-16', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-04-07 22:19:23', '2026-04-07 22:19:23'),
(634, 98, 1, 1, '2026-04-10', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(635, 98, 1, 2, '2026-04-11', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(636, 98, 1, 3, '2026-04-13', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(637, 98, 1, 4, '2026-04-14', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(638, 98, 1, 5, '2026-04-15', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(639, 98, 1, 6, '2026-04-16', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(640, 98, 1, 7, '2026-04-17', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(641, 98, 1, 8, '2026-04-18', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(642, 98, 1, 9, '2026-04-20', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(643, 98, 1, 10, '2026-04-21', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(644, 98, 1, 11, '2026-04-22', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(645, 98, 1, 12, '2026-04-23', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(646, 98, 1, 13, '2026-04-24', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(647, 98, 1, 14, '2026-04-25', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(648, 98, 1, 15, '2026-04-27', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(649, 98, 1, 16, '2026-04-28', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(650, 98, 1, 17, '2026-04-29', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(651, 98, 1, 18, '2026-04-30', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(652, 98, 1, 19, '2026-05-01', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(653, 98, 1, 20, '2026-05-02', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(654, 98, 1, 21, '2026-05-04', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(655, 98, 1, 22, '2026-05-05', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(656, 98, 1, 23, '2026-05-06', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(657, 98, 1, 24, '2026-05-07', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:04:22', '2026-04-09 11:04:22'),
(658, 99, 1, 1, '2026-04-10', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(659, 99, 1, 2, '2026-04-11', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(660, 99, 1, 3, '2026-04-13', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(661, 99, 1, 4, '2026-04-14', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(662, 99, 1, 5, '2026-04-15', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(663, 99, 1, 6, '2026-04-16', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(664, 99, 1, 7, '2026-04-17', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(665, 99, 1, 8, '2026-04-18', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(666, 99, 1, 9, '2026-04-20', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(667, 99, 1, 10, '2026-04-21', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(668, 99, 1, 11, '2026-04-22', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(669, 99, 1, 12, '2026-04-23', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(670, 99, 1, 13, '2026-04-24', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(671, 99, 1, 14, '2026-04-25', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(672, 99, 1, 15, '2026-04-27', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(673, 99, 1, 16, '2026-04-28', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(674, 99, 1, 17, '2026-04-29', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(675, 99, 1, 18, '2026-04-30', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(676, 99, 1, 19, '2026-05-01', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(677, 99, 1, 20, '2026-05-02', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(678, 99, 1, 21, '2026-05-04', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(679, 99, 1, 22, '2026-05-05', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(680, 99, 1, 23, '2026-05-06', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(681, 99, 1, 24, '2026-05-07', 10000.00, 0.00, 10000.00, NULL, 'pendiente', '2026-04-09 11:08:20', '2026-04-09 11:08:20'),
(682, 100, 1, 1, '2026-05-09', 180000.00, 0.00, 180000.00, NULL, 'pendiente', '2026-04-09 11:17:47', '2026-04-09 11:17:47'),
(683, 101, 1, 1, '2026-05-09', 180000.00, 0.00, 180000.00, NULL, 'pendiente', '2026-04-09 11:22:32', '2026-04-09 11:22:32'),
(684, 102, 1, 1, '2026-05-09', 120000.00, 120000.00, 0.00, '2026-04-09', 'pagado', '2026-04-09 14:41:42', '2026-04-09 16:51:26'),
(685, 103, 1, 1, '2026-04-16', 7500.00, 7500.00, 0.00, '2026-04-09', 'pagado', '2026-04-09 16:15:34', '2026-04-09 16:24:32'),
(686, 103, 1, 2, '2026-04-23', 7500.00, 0.00, 7500.00, NULL, 'pendiente', '2026-04-09 16:15:34', '2026-04-09 16:15:34'),
(687, 103, 1, 3, '2026-04-30', 7500.00, 0.00, 7500.00, NULL, 'pendiente', '2026-04-09 16:15:34', '2026-04-09 16:15:34'),
(688, 103, 1, 4, '2026-05-07', 7500.00, 0.00, 7500.00, NULL, 'pendiente', '2026-04-09 16:15:34', '2026-04-09 16:15:34'),
(689, 104, 1, 1, '2026-04-16', 156000.00, 0.00, 156000.00, NULL, 'pendiente', '2026-04-09 19:56:12', '2026-04-09 19:56:12'),
(690, 104, 1, 2, '2026-04-23', 156000.00, 0.00, 156000.00, NULL, 'pendiente', '2026-04-09 19:56:12', '2026-04-09 19:56:12'),
(691, 104, 1, 3, '2026-04-30', 156000.00, 0.00, 156000.00, NULL, 'pendiente', '2026-04-09 19:56:12', '2026-04-09 19:56:12'),
(692, 104, 1, 4, '2026-05-07', 156000.00, 0.00, 156000.00, NULL, 'pendiente', '2026-04-09 19:56:12', '2026-04-09 19:56:12'),
(693, 105, 1, 1, '2026-05-10', 24000.00, 0.00, 24000.00, NULL, 'pendiente', '2026-04-10 13:13:29', '2026-04-10 13:13:29'),
(694, 106, 1, 1, '2026-05-10', 31200.00, 0.00, 31200.00, NULL, 'pendiente', '2026-04-10 13:17:27', '2026-04-10 13:17:27'),
(695, 107, 3, 1, '2026-04-11', 15000.00, 15000.00, 0.00, '2026-04-10', 'pagado', '2026-04-10 15:31:16', '2026-04-10 15:31:31'),
(696, 107, 3, 2, '2026-04-13', 15000.00, 15000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:31:16', '2026-04-11 08:10:02'),
(697, 107, 3, 3, '2026-04-14', 15000.00, 15000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:31:16', '2026-04-11 08:10:02'),
(698, 107, 3, 4, '2026-04-15', 15000.00, 15000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:31:16', '2026-04-11 08:10:02'),
(699, 107, 3, 5, '2026-04-16', 15000.00, 15000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:31:16', '2026-04-11 08:10:02'),
(700, 107, 3, 6, '2026-04-17', 15000.00, 15000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:31:16', '2026-04-11 08:10:02'),
(701, 107, 3, 7, '2026-04-18', 15000.00, 15000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:31:16', '2026-04-11 08:10:02'),
(702, 107, 3, 8, '2026-04-20', 15000.00, 15000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:31:16', '2026-04-11 08:10:02'),
(703, 107, 3, 9, '2026-04-21', 15000.00, 15000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:31:16', '2026-04-11 08:10:02'),
(704, 107, 3, 10, '2026-04-22', 15000.00, 15000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:31:16', '2026-04-11 08:10:02'),
(705, 107, 3, 11, '2026-04-23', 15000.00, 15000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:31:16', '2026-04-11 08:10:02'),
(706, 107, 3, 12, '2026-04-24', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(707, 107, 3, 13, '2026-04-25', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(708, 107, 3, 14, '2026-04-27', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(709, 107, 3, 15, '2026-04-28', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(710, 107, 3, 16, '2026-04-29', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(711, 107, 3, 17, '2026-04-30', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(712, 107, 3, 18, '2026-05-01', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(713, 107, 3, 19, '2026-05-02', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(714, 107, 3, 20, '2026-05-04', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(715, 107, 3, 21, '2026-05-05', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(716, 107, 3, 22, '2026-05-06', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(717, 107, 3, 23, '2026-05-07', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(718, 107, 3, 24, '2026-05-08', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(719, 107, 3, 25, '2026-05-09', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(720, 107, 3, 26, '2026-05-11', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(721, 107, 3, 27, '2026-05-12', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(722, 107, 3, 28, '2026-05-13', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(723, 107, 3, 29, '2026-05-14', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(724, 107, 3, 30, '2026-05-15', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(725, 107, 3, 31, '2026-05-16', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(726, 107, 3, 32, '2026-05-18', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(727, 107, 3, 33, '2026-05-19', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(728, 107, 3, 34, '2026-05-20', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(729, 107, 3, 35, '2026-05-21', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(730, 107, 3, 36, '2026-05-22', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(731, 107, 3, 37, '2026-05-23', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(732, 107, 3, 38, '2026-05-25', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(733, 107, 3, 39, '2026-05-26', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(734, 107, 3, 40, '2026-05-27', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(735, 107, 3, 41, '2026-05-28', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(736, 107, 3, 42, '2026-05-29', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(737, 107, 3, 43, '2026-05-30', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(738, 107, 3, 44, '2026-06-01', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(739, 107, 3, 45, '2026-06-02', 15000.00, 0.00, 15000.00, NULL, 'pendiente', '2026-04-10 15:31:16', '2026-04-10 15:31:16'),
(740, 108, 3, 1, '2026-04-17', 85000.00, 85000.00, 0.00, '2026-04-10', 'pagado', '2026-04-10 15:32:37', '2026-04-10 15:35:03'),
(741, 108, 3, 2, '2026-04-24', 85000.00, 85000.00, 0.00, '2026-04-10', 'pagado', '2026-04-10 15:32:37', '2026-04-10 15:35:03'),
(742, 108, 3, 3, '2026-05-01', 85000.00, 85000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:32:37', '2026-04-11 14:38:27'),
(743, 108, 3, 4, '2026-05-08', 85000.00, 85000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:32:37', '2026-04-11 14:38:27'),
(744, 108, 3, 5, '2026-05-15', 85000.00, 85000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:32:37', '2026-04-11 14:38:27'),
(745, 108, 3, 6, '2026-05-22', 85000.00, 85000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:32:37', '2026-04-11 14:38:27'),
(746, 108, 3, 7, '2026-05-29', 85000.00, 85000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:32:37', '2026-04-11 14:38:27'),
(747, 108, 3, 8, '2026-06-05', 85000.00, 85000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:32:37', '2026-04-11 14:38:27'),
(748, 109, 3, 1, '2026-04-11', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(749, 109, 3, 2, '2026-04-13', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(750, 109, 3, 3, '2026-04-14', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(751, 109, 3, 4, '2026-04-15', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(752, 109, 3, 5, '2026-04-16', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(753, 109, 3, 6, '2026-04-17', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(754, 109, 3, 7, '2026-04-18', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(755, 109, 3, 8, '2026-04-20', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(756, 109, 3, 9, '2026-04-21', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(757, 109, 3, 10, '2026-04-22', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(758, 109, 3, 11, '2026-04-23', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(759, 109, 3, 12, '2026-04-24', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(760, 109, 3, 13, '2026-04-25', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(761, 109, 3, 14, '2026-04-27', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(762, 109, 3, 15, '2026-04-28', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(763, 109, 3, 16, '2026-04-29', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(764, 109, 3, 17, '2026-04-30', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(765, 109, 3, 18, '2026-05-01', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(766, 109, 3, 19, '2026-05-02', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(767, 109, 3, 20, '2026-05-04', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(768, 109, 3, 21, '2026-05-05', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(769, 109, 3, 22, '2026-05-06', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(770, 109, 3, 23, '2026-05-07', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(771, 109, 3, 24, '2026-05-08', 10000.00, 10000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(772, 110, 3, 1, '2026-04-11', 12000.00, 12000.00, 0.00, '2026-04-11', 'pagado', '2026-04-10 16:52:07', '2026-04-11 14:36:59'),
(773, 111, 1, 1, '2026-05-11', 120000.00, 0.00, 120000.00, NULL, 'pendiente', '2026-04-11 09:43:06', '2026-04-11 09:43:06'),
(774, 112, 1, 1, '2026-05-11', 150000.00, 0.00, 150000.00, NULL, 'pendiente', '2026-04-11 10:23:42', '2026-04-11 10:23:42'),
(775, 113, 1, 1, '2026-04-18', 225000.00, 0.00, 225000.00, NULL, 'pendiente', '2026-04-11 11:42:59', '2026-04-11 11:42:59'),
(776, 113, 1, 2, '2026-04-25', 225000.00, 0.00, 225000.00, NULL, 'pendiente', '2026-04-11 11:42:59', '2026-04-11 11:42:59'),
(777, 113, 1, 3, '2026-05-02', 225000.00, 0.00, 225000.00, NULL, 'pendiente', '2026-04-11 11:42:59', '2026-04-11 11:42:59'),
(778, 113, 1, 4, '2026-05-09', 225000.00, 0.00, 225000.00, NULL, 'pendiente', '2026-04-11 11:42:59', '2026-04-11 11:42:59'),
(779, 114, 3, 1, '2026-04-12', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(780, 114, 3, 2, '2026-04-13', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(781, 114, 3, 3, '2026-04-14', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(782, 114, 3, 4, '2026-04-15', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(783, 114, 3, 5, '2026-04-16', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(784, 114, 3, 6, '2026-04-17', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(785, 114, 3, 7, '2026-04-18', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(786, 114, 3, 8, '2026-04-19', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(787, 114, 3, 9, '2026-04-20', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(788, 114, 3, 10, '2026-04-21', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(789, 114, 3, 11, '2026-04-22', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(790, 114, 3, 12, '2026-04-23', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(791, 114, 3, 13, '2026-04-24', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(792, 114, 3, 14, '2026-04-25', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(793, 114, 3, 15, '2026-04-26', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(794, 114, 3, 16, '2026-04-27', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(795, 114, 3, 17, '2026-04-28', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(796, 114, 3, 18, '2026-04-29', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(797, 114, 3, 19, '2026-04-30', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(798, 114, 3, 20, '2026-05-01', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(799, 114, 3, 21, '2026-05-02', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(800, 114, 3, 22, '2026-05-03', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(801, 114, 3, 23, '2026-05-04', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(802, 114, 3, 24, '2026-05-05', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(803, 114, 3, 25, '2026-05-06', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(804, 114, 3, 26, '2026-05-07', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(805, 114, 3, 27, '2026-05-08', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(806, 114, 3, 28, '2026-05-09', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(807, 114, 3, 29, '2026-05-10', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(808, 114, 3, 30, '2026-05-11', 38000.00, 0.00, 38000.00, NULL, 'pendiente', '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(809, 115, 3, 1, '2026-05-11', 120000.00, 120000.00, 0.00, '2026-04-11', 'pagado', '2026-04-11 14:39:58', '2026-04-11 14:40:37'),
(810, 116, 3, 1, '2026-04-18', 60000.00, 60000.00, 0.00, '2026-04-11', 'pagado', '2026-04-11 14:41:40', '2026-04-11 14:45:34'),
(811, 116, 3, 2, '2026-04-25', 60000.00, 0.00, 60000.00, NULL, 'pagado', '2026-04-11 14:41:40', '2026-04-11 14:46:29'),
(812, 117, 3, 1, '2026-04-18', 60000.00, 0.00, 60000.00, NULL, 'pendiente', '2026-04-11 14:46:29', '2026-04-11 14:46:29'),
(813, 117, 3, 2, '2026-04-25', 60000.00, 0.00, 60000.00, NULL, 'pendiente', '2026-04-11 14:46:29', '2026-04-11 14:46:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `deudores`
--

CREATE TABLE `deudores` (
  `id` int(11) NOT NULL,
  `cobro_id` int(11) DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `telefono_alt` varchar(20) DEFAULT NULL,
  `ocupacion` varchar(150) DEFAULT NULL,
  `documento` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `place_id` varchar(255) DEFAULT NULL,
  `barrio` varchar(100) DEFAULT NULL,
  `codeudor_nombre` varchar(150) DEFAULT NULL,
  `codeudor_telefono` varchar(20) DEFAULT NULL,
  `codeudor_documento` varchar(20) DEFAULT NULL,
  `garantia_descripcion` varchar(255) DEFAULT NULL,
  `comportamiento` enum('bueno','regular','clavo') DEFAULT 'bueno',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `notas` text DEFAULT NULL,
  `saldo_favor` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `deudores`
--

INSERT INTO `deudores` (`id`, `cobro_id`, `nombre`, `telefono`, `telefono_alt`, `ocupacion`, `documento`, `direccion`, `lat`, `lng`, `place_id`, `barrio`, `codeudor_nombre`, `codeudor_telefono`, `codeudor_documento`, `garantia_descripcion`, `comportamiento`, `activo`, `notas`, `saldo_favor`, `created_at`, `updated_at`) VALUES
(1, 2, 'RAFAEL ACOSTA', '3015684343', NULL, NULL, '1', 'CALLE 48 #24-95', NULL, NULL, NULL, 'PCA', NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:27:16', '2026-03-05 22:27:16'),
(2, 2, 'CARLOS TALLER', '3185943676', NULL, NULL, '2', 'CARRERA 3C CALLE 42 # 15', NULL, NULL, NULL, 'LA 4', NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:29:29', '2026-03-05 22:29:29'),
(3, 2, 'ANTHONY PATRON', '3008787518', NULL, NULL, '1121534448', 'CARRERA 5', NULL, NULL, NULL, 'VILLA SAN PEDRO 2', NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:31:22', '2026-03-05 22:31:22'),
(4, 2, 'CARLOS H. MARLON', '3052807210', NULL, NULL, '3', 'Cementerio', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:32:49', '2026-03-05 22:32:49'),
(5, 2, 'Cristian', '3007081492', NULL, NULL, '4', NULL, NULL, NULL, NULL, 'San Pedro 3', NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:35:05', '2026-03-05 22:35:05'),
(6, 2, 'Miguel Marlon', '3206606755', NULL, NULL, '5', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:35:57', '2026-03-05 22:35:57'),
(7, 2, 'Juan Tec. Cristian', '3013688790', NULL, NULL, '1001997109', 'Calle 98b#6e-75', NULL, NULL, NULL, 'la cordialidad', NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:37:35', '2026-03-05 22:38:14'),
(8, 2, 'RUBEN DARIO', '3005013405', NULL, NULL, '6', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:41:08', '2026-03-05 22:41:08'),
(9, 2, 'EDGAR ALTAMAR', '3057595825', NULL, NULL, '7', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:41:49', '2026-03-05 22:41:49'),
(10, 2, 'EL NEGRO', '3508821971', NULL, NULL, '8', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:42:42', '2026-03-05 22:42:42'),
(11, 2, 'ANDREW INCATEC', '3046486873', NULL, NULL, '9', 'Parque Central, Avenida 20 de Julio, Norte Centro Historico, Barranquilla, Atlántico', 10.9845217, -74.7892265, 'ChIJVVUhS2Qt9I4R5OMLYkSjlyI', NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:43:32', '2026-04-10 12:07:48'),
(12, 2, 'MARLON YEPEZ', '3206606755', NULL, NULL, '10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:44:35', '2026-03-05 22:44:35'),
(13, 2, 'KEVIN LLANOS HERMANO', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:45:04', '2026-03-05 22:45:04'),
(14, 2, 'STEVENSON', '3216080449', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:45:54', '2026-03-05 22:47:40'),
(15, 2, 'LUIS LLANOS', '3021191522', NULL, NULL, '11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 1, NULL, 0.00, '2026-03-05 22:46:49', '2026-03-31 11:53:15'),
(16, 2, 'ARREGLO DE CASA PETI', '3114326407', NULL, NULL, '14', 'LA CASA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 09:13:42', '2026-03-06 09:13:42'),
(18, 1, 'ANDRES AMADOR', '3007285084', NULL, NULL, '16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 16:36:58', '2026-03-06 16:36:58'),
(19, 1, 'NATALYA ANDREW', '3007436041', NULL, NULL, '16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 16:40:49', '2026-03-06 16:40:49'),
(20, 1, 'PADRASTO ISAIAS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 16:43:02', '2026-03-06 16:43:02'),
(21, 1, 'ISA SHOP', NULL, NULL, NULL, NULL, 'Paseo Bolívar, Carrera 44, Norte Centro Historico, Barranquilla, Atlántico', 10.9829417, -74.7776380, 'ChIJveMg1Z0y9I4RBA3z91CT26k', NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 16:44:12', '2026-04-10 12:09:39'),
(22, 1, 'DANNA CASIANI', '3017323914', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 16:46:19', '2026-03-06 16:46:19'),
(23, 1, 'MERA WO', '3218080874', NULL, NULL, '17', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 21:55:32', '2026-03-06 21:55:32'),
(24, 1, 'SUEGRA', '3207410617', NULL, NULL, '15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 22:00:32', '2026-03-06 22:00:32'),
(25, 1, 'RONAL', NULL, NULL, NULL, '18', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 22:03:33', '2026-03-06 22:03:33'),
(26, 1, 'ROSSI', '3163570665', NULL, NULL, '19', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 22:05:24', '2026-03-06 22:05:24'),
(27, 1, 'WILSON TAXISTA', '3122624844', NULL, NULL, '20', 'Politecnico Costa Atlantica, Carrera 59, Norte Centro Historico, Barranquilla, Atlántico', 11.0069199, -74.8016280, 'ChIJ_-ZzDwAt9I4RF6QJr89UAPA', NULL, NULL, NULL, NULL, NULL, 'clavo', 1, NULL, 0.00, '2026-03-06 22:23:18', '2026-04-10 13:22:07'),
(28, 1, 'NAIR VIGILANTE', NULL, NULL, NULL, NULL, 'Parque Central, Avenida 20 de Julio, Norte Centro Historico, Barranquilla, Atlántico', 10.9845217, -74.7892265, 'ChIJVVUhS2Qt9I4R5OMLYkSjlyI', NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 22:59:37', '2026-04-10 12:08:12'),
(29, 1, 'JORGE MOTO', '3052846387', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 23:03:17', '2026-03-06 23:03:17'),
(30, 1, 'MUJER DE JORGE', '3014123344', NULL, NULL, NULL, 'Villa San Pedro, Metropolitana, Barranquilla, Atlántico', 10.9404684, -74.8309741, 'ChIJG_jeMSHT9Y4RzklzHASHVIY', NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 23:03:58', '2026-04-11 08:51:17'),
(31, 1, 'NATALYA CAMARGO', '3012905501', NULL, NULL, '21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 23:09:39', '2026-03-06 23:09:39'),
(32, 1, 'SUPER GIROS', '3156156216', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 23:14:59', '2026-03-06 23:14:59'),
(33, 1, 'VIVIANA CADRASCO', '3017896857', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-08 13:43:43', '2026-03-08 13:43:43'),
(34, 1, 'Freisi', '3332236660', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-08 14:11:34', '2026-03-08 14:11:34'),
(35, 1, 'Deivi Esteban mejia Jiménez', '3046632801', NULL, NULL, '1111638444', 'Carrera 5 # 99 A 10', NULL, NULL, NULL, 'VILLA SAN PEDRO 2', NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-21 14:47:14', '2026-03-21 14:47:14'),
(36, 1, 'EL MAÑE', '3151374444', NULL, NULL, '1129491876', 'Calle 77 #21B-189', NULL, NULL, NULL, 'Nueva Colombia', NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-27 10:04:05', '2026-03-27 10:05:35'),
(37, 2, 'CAMILO', '3243828232', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-27 10:27:48', '2026-03-27 10:27:48'),
(38, 1, 'LUIS CAMILO GAMARRA', '3027616629', NULL, NULL, '1002136296', 'Calle 99d#6c-39', NULL, NULL, NULL, 'Villa valeria', NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-29 14:28:14', '2026-03-29 14:28:14'),
(39, 1, 'Tia lili', '3245675196', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-30 08:38:55', '2026-03-30 08:38:55'),
(40, 1, 'HERMANA YESICA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-04-06 23:11:49', '2026-04-06 23:11:49'),
(41, 1, 'CUÑADA DE NATALIA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-04-07 22:18:32', '2026-04-07 22:18:32'),
(42, 1, 'COBRADOR 1', NULL, NULL, NULL, NULL, 'Edificio Ágora, Carrera 5b #99-33, Metropolitana, Barranquilla, Atlántico', 10.9423676, -74.8302049, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-04-09 08:57:01', '2026-04-09 08:57:01'),
(43, 1, 'PROBANDO', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-04-09 10:31:46', '2026-04-09 10:31:46'),
(44, 1, 'PROBANDO', '3214621', NULL, NULL, '284', 'INCATEC Barranquilla, Calle 57, Norte Centro Historico, Barranquilla, Atlántico', 10.9861165, -74.7944370, 'ChIJHdtWsnYt9I4RnbXSiRIaU_s', 'AGORA', NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-04-09 11:02:14', '2026-04-09 11:02:14'),
(45, 1, 'PROBANDO 2', '3333', NULL, NULL, '78', 'Corporación Universitaria Politécnico Costa Atlántica, Carrera 38, Suroccidente, Barranquilla, Atlántico', 10.9868453, -74.8193321, 'ChIJB7r4BeIs9I4RcG251gdKntc', 'NORTE', NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-04-09 11:40:04', '2026-04-09 11:40:04'),
(46, 1, 'julio', '3214621', NULL, NULL, '125', 'Carrera 5 # 99-33, Metropolitana, Barranquilla, Atlántico', 10.9423429, -74.8301862, NULL, 'villa san pedro', NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-04-09 19:55:25', '2026-04-09 19:55:25'),
(47, 3, 'CLIENTE 1', '3214621', NULL, NULL, '1121', 'Edificio Ágora, Carrera 5b #99-33, Metropolitana, Barranquilla, Atlántico', 10.9423676, -74.8302049, NULL, 'VILLA SAN PEDRO', NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-04-10 15:29:15', '2026-04-10 15:29:15'),
(48, 3, 'EDUARDO JOSE', '3214621', NULL, NULL, '1122', 'INCATEC Barranquilla, Calle 57, Norte Centro Historico, Barranquilla, Atlántico', 10.9861165, -74.7944370, 'ChIJHdtWsnYt9I4RnbXSiRIaU_s', 'NORTE', NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 60000.00, '2026-04-10 15:32:08', '2026-04-11 14:40:37'),
(49, 3, 'CLIENTE 32', '356', NULL, NULL, '123', 'Miramar, Norte Centro Historico, Barranquilla, Atlántico', 11.0031109, -74.8346296, 'ChIJDeIuYVYs9I4RH8HfjIDVNwI', 'NO SE', NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 60000.00, '2026-04-10 15:33:18', '2026-04-11 14:39:00'),
(50, 3, 'JUANCHO', '300', NULL, 'VENDEDOR DE BONICE', '99', 'Carretera de la Cordialidad, Barranquilla, Atlántico', 10.9524434, -74.8100170, 'EjlDci4gZGUgbGEgQ29yZGlhbGlkYWQsIEJhcnJhbnF1aWxsYSwgQXRsw6FudGljbywgQ29sb21iaWEiLiosChQKEglxUJ9qUC30jhGm0TKPemZ6xhIUChIJORHUIlAs9I4R7Hv0XI18dwc', 'ARRIBA', NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-04-11 15:00:06', '2026-04-11 15:00:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `deudor_cobro`
--

CREATE TABLE `deudor_cobro` (
  `id` int(11) NOT NULL,
  `deudor_id` int(11) NOT NULL,
  `cobro_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `deudor_cobro`
--

INSERT INTO `deudor_cobro` (`id`, `deudor_id`, `cobro_id`, `created_at`) VALUES
(1, 1, 2, '2026-03-06 15:48:56'),
(2, 2, 2, '2026-03-06 15:48:56'),
(3, 3, 2, '2026-03-06 15:48:56'),
(4, 4, 2, '2026-03-06 15:48:56'),
(5, 5, 2, '2026-03-06 15:48:56'),
(6, 6, 2, '2026-03-06 15:48:56'),
(7, 7, 2, '2026-03-06 15:48:56'),
(8, 8, 2, '2026-03-06 15:48:56'),
(9, 9, 2, '2026-03-06 15:48:56'),
(10, 10, 2, '2026-03-06 15:48:56'),
(11, 11, 2, '2026-03-06 15:48:56'),
(12, 12, 2, '2026-03-06 15:48:56'),
(13, 13, 2, '2026-03-06 15:48:56'),
(14, 14, 2, '2026-03-06 15:48:56'),
(15, 15, 2, '2026-03-06 15:48:56'),
(16, 16, 2, '2026-03-06 15:48:56'),
(32, 1, 1, '2026-03-06 15:50:43'),
(33, 2, 1, '2026-03-06 15:50:43'),
(34, 3, 1, '2026-03-06 15:50:43'),
(35, 4, 1, '2026-03-06 15:50:43'),
(36, 5, 1, '2026-03-06 15:50:43'),
(37, 6, 1, '2026-03-06 15:50:43'),
(38, 7, 1, '2026-03-06 15:50:43'),
(39, 8, 1, '2026-03-06 15:50:43'),
(40, 9, 1, '2026-03-06 15:50:43'),
(41, 10, 1, '2026-03-06 15:50:43'),
(42, 11, 1, '2026-03-06 15:50:43'),
(43, 12, 1, '2026-03-06 15:50:43'),
(44, 13, 1, '2026-03-06 15:50:43'),
(45, 14, 1, '2026-03-06 15:50:43'),
(46, 15, 1, '2026-03-06 15:50:43'),
(47, 16, 1, '2026-03-06 15:50:43'),
(63, 18, 2, '2026-03-06 16:36:58'),
(64, 18, 1, '2026-03-06 16:36:58'),
(65, 19, 2, '2026-03-06 16:40:49'),
(66, 19, 1, '2026-03-06 16:40:49'),
(67, 20, 2, '2026-03-06 16:43:02'),
(68, 20, 1, '2026-03-06 16:43:02'),
(69, 21, 2, '2026-03-06 16:44:12'),
(70, 21, 1, '2026-03-06 16:44:12'),
(71, 22, 2, '2026-03-06 16:46:19'),
(72, 22, 1, '2026-03-06 16:46:19'),
(73, 23, 2, '2026-03-06 21:55:32'),
(74, 23, 1, '2026-03-06 21:55:32'),
(75, 24, 2, '2026-03-06 22:00:32'),
(76, 24, 1, '2026-03-06 22:00:32'),
(77, 25, 2, '2026-03-06 22:03:33'),
(78, 25, 1, '2026-03-06 22:03:33'),
(79, 26, 2, '2026-03-06 22:05:24'),
(80, 26, 1, '2026-03-06 22:05:24'),
(81, 27, 2, '2026-03-06 22:23:18'),
(82, 27, 1, '2026-03-06 22:23:18'),
(83, 28, 2, '2026-03-06 22:59:37'),
(84, 28, 1, '2026-03-06 22:59:37'),
(85, 29, 2, '2026-03-06 23:03:17'),
(86, 29, 1, '2026-03-06 23:03:17'),
(87, 30, 2, '2026-03-06 23:03:58'),
(88, 30, 1, '2026-03-06 23:03:58'),
(89, 31, 2, '2026-03-06 23:09:39'),
(90, 31, 1, '2026-03-06 23:09:39'),
(91, 32, 2, '2026-03-06 23:14:59'),
(92, 32, 1, '2026-03-06 23:14:59'),
(93, 33, 1, '2026-03-08 13:43:43'),
(94, 33, 2, '2026-03-08 13:43:43'),
(95, 34, 1, '2026-03-08 14:11:34'),
(96, 34, 2, '2026-03-08 14:11:34'),
(97, 35, 1, '2026-03-21 14:47:14'),
(98, 35, 2, '2026-03-21 14:47:14'),
(99, 36, 1, '2026-03-27 10:04:05'),
(100, 36, 2, '2026-03-27 10:04:05'),
(103, 37, 1, '2026-03-27 10:27:48'),
(104, 37, 2, '2026-03-27 10:27:48'),
(105, 38, 1, '2026-03-29 14:28:14'),
(106, 38, 2, '2026-03-29 14:28:14'),
(107, 39, 1, '2026-03-30 08:38:55'),
(108, 39, 2, '2026-03-30 08:38:55'),
(109, 40, 1, '2026-04-06 23:11:49'),
(110, 40, 2, '2026-04-06 23:11:49'),
(111, 41, 1, '2026-04-07 22:18:32'),
(112, 41, 2, '2026-04-07 22:18:32'),
(113, 42, 1, '2026-04-09 08:57:01'),
(114, 43, 1, '2026-04-09 10:31:46'),
(115, 44, 1, '2026-04-09 11:02:14'),
(116, 45, 1, '2026-04-09 11:40:04'),
(117, 46, 1, '2026-04-09 19:55:25'),
(128, 47, 3, '2026-04-10 15:29:15'),
(129, 48, 3, '2026-04-10 15:32:08'),
(130, 49, 3, '2026-04-10 15:33:18'),
(131, 50, 3, '2026-04-11 15:00:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gastos_cobrador`
--

CREATE TABLE `gastos_cobrador` (
  `id` int(11) NOT NULL,
  `cobro_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `monto` decimal(15,2) NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `estado` enum('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  `liquidacion_id` int(11) DEFAULT NULL,
  `aprobado_por` int(11) DEFAULT NULL,
  `aprobado_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `gastos_cobrador`
--

INSERT INTO `gastos_cobrador` (`id`, `cobro_id`, `usuario_id`, `fecha`, `descripcion`, `monto`, `categoria_id`, `estado`, `liquidacion_id`, `aprobado_por`, `aprobado_at`, `created_at`) VALUES
(1, 1, 1, '2026-04-09', 'clavo', 20000.00, 3, 'aprobado', 7, NULL, NULL, '2026-04-09 12:06:40'),
(2, 1, 2, '2026-04-10', 'la policia', 52000.00, 1, 'pendiente', NULL, NULL, NULL, '2026-04-09 19:59:31'),
(3, 3, 2, '2026-04-10', 'GASOLINA', 15000.00, 4, 'aprobado', 9, 1, '2026-04-10 16:53:52', '2026-04-10 15:36:17'),
(4, 3, 2, '2026-04-11', 'EXTRA', 20000.00, 4, 'pendiente', NULL, NULL, NULL, '2026-04-11 14:47:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gestiones_cobro`
--

CREATE TABLE `gestiones_cobro` (
  `id` int(11) NOT NULL,
  `cobro_id` int(11) NOT NULL,
  `prestamo_id` int(11) NOT NULL,
  `deudor_id` int(11) NOT NULL,
  `tipo` enum('llamada','visita','whatsapp','acuerdo','nota') NOT NULL DEFAULT 'nota',
  `resultado` enum('contactado','no_contesto','promesa_pago','sin_resultado','otro') DEFAULT NULL,
  `nota` text NOT NULL,
  `fecha_gestion` date NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `gestiones_cobro`
--

INSERT INTO `gestiones_cobro` (`id`, `cobro_id`, `prestamo_id`, `deudor_id`, `tipo`, `resultado`, `nota`, `fecha_gestion`, `usuario_id`, `created_at`) VALUES
(1, 1, 35, 19, 'nota', 'otro', 'Préstamo renovado. Nuevo: #65', '2026-03-08', 1, '2026-03-08 15:44:42'),
(2, 3, 110, 49, 'llamada', 'contactado', 'Que mañana', '2026-04-11', 2, '2026-04-11 14:35:57'),
(3, 3, 116, 49, 'nota', 'otro', 'Refinanciacion por $100.000. Cobrador entregó $40.000. Nuevo préstamo #117.', '2026-04-11', 2, '2026-04-11 14:46:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `liquidaciones`
--

CREATE TABLE `liquidaciones` (
  `id` int(11) NOT NULL,
  `cobro_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `base` decimal(15,2) NOT NULL DEFAULT 0.00,
  `base_trabajado` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_pagos` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_prestamos` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_gastos_aprobados` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_papeleria` decimal(15,2) NOT NULL DEFAULT 0.00,
  `papeleria_entregada` decimal(15,2) NOT NULL DEFAULT 0.00,
  `efectivo_esperado` decimal(15,2) NOT NULL DEFAULT 0.00,
  `dinero_entregado` decimal(15,2) NOT NULL DEFAULT 0.00,
  `diferencia` decimal(15,2) NOT NULL DEFAULT 0.00,
  `nueva_base` decimal(15,2) NOT NULL DEFAULT 0.00,
  `estado` enum('borrador','cerrada') NOT NULL DEFAULT 'borrador',
  `notas` text DEFAULT NULL,
  `cerrada_at` datetime DEFAULT NULL,
  `cerrada_por` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `cobrador_bloqueado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `liquidaciones`
--

INSERT INTO `liquidaciones` (`id`, `cobro_id`, `usuario_id`, `fecha`, `base`, `base_trabajado`, `total_pagos`, `total_prestamos`, `total_gastos_aprobados`, `total_papeleria`, `papeleria_entregada`, `efectivo_esperado`, `dinero_entregado`, `diferencia`, `nueva_base`, `estado`, `notas`, `cerrada_at`, `cerrada_por`, `created_at`, `cobrador_bloqueado`) VALUES
(3, 1, 1, '2026-04-05', 200000.00, 0.00, 160000.00, 0.00, 0.00, 0.00, 0.00, 160000.00, 160000.00, 0.00, 360000.00, 'cerrada', NULL, '2026-04-09 13:59:18', 1, '2026-04-09 13:53:05', 0),
(4, 1, 1, '2026-04-06', -140000.00, 0.00, 85000.00, 600000.00, 0.00, 0.00, 0.00, -515000.00, 600000.00, -1115000.00, 460000.00, 'cerrada', NULL, '2026-04-09 14:01:30', 1, '2026-04-09 14:00:15', 0),
(5, 1, 1, '2026-04-07', -55000.00, 0.00, 495000.00, 500000.00, 0.00, 50000.00, 0.00, -5000.00, 10000.00, -15000.00, -45000.00, 'cerrada', NULL, '2026-04-09 14:02:00', 1, '2026-04-09 14:01:41', 0),
(6, 1, 1, '2026-04-08', 940000.00, 500000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 500000.00, 500000.00, 0.00, 940000.00, 'cerrada', NULL, '2026-04-09 16:49:33', 1, '2026-04-09 16:49:18', 0),
(7, 1, 1, '2026-04-09', 940000.00, 500000.00, 377500.00, 825000.00, 20000.00, 142500.00, 142500.00, 32500.00, 32500.00, 0.00, 472500.00, 'cerrada', NULL, '2026-04-09 19:45:09', 1, '2026-04-09 16:50:00', 0),
(9, 3, 1, '2026-04-10', 5000000.00, 1000000.00, 265000.00, 1210000.00, 15000.00, 121000.00, 121000.00, 40000.00, 40000.00, 0.00, 4040000.00, 'cerrada', NULL, '2026-04-10 16:54:17', 1, '2026-04-10 15:27:09', 1),
(10, 3, 1, '2026-04-11', 4040000.00, 2500000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'borrador', NULL, NULL, NULL, '2026-04-11 08:08:12', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `liquidacion_entregas`
--

CREATE TABLE `liquidacion_entregas` (
  `id` int(11) NOT NULL,
  `liquidacion_id` int(11) NOT NULL,
  `cobro_id` int(11) NOT NULL,
  `monto` decimal(15,2) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(11) NOT NULL,
  `cobro_id` int(11) NOT NULL,
  `prestamo_id` int(11) NOT NULL,
  `cuota_id` int(11) NOT NULL,
  `deudor_id` int(11) NOT NULL,
  `monto_pagado` decimal(15,2) NOT NULL,
  `fecha_pago` date NOT NULL,
  `cuenta_id` int(11) DEFAULT NULL,
  `metodo_pago` enum('efectivo','banco') NOT NULL DEFAULT 'efectivo',
  `es_parcial` tinyint(1) NOT NULL DEFAULT 0,
  `referencia` varchar(100) DEFAULT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `anulado` tinyint(1) NOT NULL DEFAULT 0,
  `anulado_at` datetime DEFAULT NULL,
  `anulado_por` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id`, `cobro_id`, `prestamo_id`, `cuota_id`, `deudor_id`, `monto_pagado`, `fecha_pago`, `cuenta_id`, `metodo_pago`, `es_parcial`, `referencia`, `observacion`, `usuario_id`, `anulado`, `anulado_at`, `anulado_por`, `created_at`) VALUES
(52, 1, 32, 235, 5, 310000.00, '2026-01-24', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-07 16:02:50'),
(53, 1, 32, 236, 5, 310000.00, '2026-01-24', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-07 16:02:50'),
(54, 1, 32, 237, 5, 310000.00, '2026-02-28', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-07 16:16:16'),
(55, 1, 32, 238, 5, 310000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #3', 1, 0, NULL, NULL, '2026-03-07 16:16:16'),
(56, 1, 33, 239, 23, 180000.00, '2026-01-26', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-08 13:13:49'),
(57, 1, 34, 240, 23, 180000.00, '2026-02-03', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-08 13:18:40'),
(58, 1, 36, 242, 27, 70000.00, '2026-02-07', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-08 13:22:02'),
(59, 1, 36, 243, 27, 70000.00, '2026-02-07', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 13:22:02'),
(60, 1, 36, 244, 27, 70000.00, '2026-02-07', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 13:22:02'),
(61, 1, 37, 252, 28, 70000.00, '2026-02-28', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-08 13:23:29'),
(62, 1, 37, 253, 28, 70000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 13:23:29'),
(63, 1, 37, 254, 28, 70000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 13:23:29'),
(64, 1, 37, 255, 28, 70000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 13:23:29'),
(65, 1, 37, 256, 28, 70000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 13:23:29'),
(66, 1, 38, 262, 29, 70000.00, '2026-02-28', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-08 13:24:39'),
(67, 1, 38, 263, 29, 70000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 13:24:39'),
(68, 1, 38, 264, 29, 70000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 13:24:39'),
(69, 1, 38, 265, 29, 70000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 13:24:39'),
(70, 1, 38, 266, 29, 70000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 13:24:39'),
(71, 1, 39, 272, 6, 110000.00, '2026-02-08', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-08 13:35:31'),
(72, 1, 41, 275, 33, 240000.00, '2026-02-28', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-08 13:45:30'),
(73, 1, 42, 284, 27, 24000.00, '2026-02-28', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-08 13:49:24'),
(74, 1, 43, 288, 31, 70000.00, '2026-02-22', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-08 13:54:37'),
(75, 1, 43, 289, 31, 70000.00, '2026-02-22', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 13:54:37'),
(76, 1, 43, 290, 31, 70000.00, '2026-02-22', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 13:54:37'),
(77, 1, 43, 291, 31, 70000.00, '2026-03-01', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-08 13:54:54'),
(78, 1, 44, 298, 11, 360000.00, '2026-03-04', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-08 13:59:05'),
(79, 1, 45, 299, 34, 120000.00, '2026-02-28', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-08 14:13:05'),
(80, 1, 46, 300, 12, 120000.00, '2026-03-03', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-08 14:15:01'),
(81, 1, 46, 301, 12, 120000.00, '2026-03-03', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 14:15:01'),
(82, 1, 47, 302, 32, 140000.00, '2026-02-28', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-08 14:16:57'),
(83, 1, 48, 307, 10, 240000.00, '2026-03-05', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-08 14:18:13'),
(84, 1, 49, 308, 30, 20000.00, '2026-02-28', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-08 14:19:51'),
(85, 1, 49, 309, 30, 20000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 14:19:51'),
(86, 1, 49, 310, 30, 20000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 14:19:51'),
(87, 1, 49, 311, 30, 20000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 14:19:51'),
(88, 1, 49, 312, 30, 20000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 14:19:51'),
(89, 1, 49, 313, 30, 20000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 14:19:51'),
(90, 1, 49, 314, 30, 20000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 1, '2026-03-09 08:12:38', 1, '2026-03-08 14:19:51'),
(91, 1, 49, 315, 30, 20000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 1, '2026-03-09 08:12:45', 1, '2026-03-08 14:19:51'),
(92, 1, 49, 316, 30, 20000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 14:19:51'),
(93, 1, 49, 317, 30, 20000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 14:19:51'),
(94, 1, 49, 318, 30, 20000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 14:19:51'),
(95, 1, 49, 319, 30, 20000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 14:19:51'),
(96, 1, 49, 320, 30, 20000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 14:19:51'),
(97, 1, 49, 321, 30, 20000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 14:19:51'),
(98, 1, 49, 322, 30, 20000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 14:19:51'),
(99, 1, 49, 323, 30, 20000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 14:19:51'),
(100, 1, 49, 324, 30, 20000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 14:19:51'),
(101, 1, 49, 325, 30, 20000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 14:19:51'),
(102, 1, 49, 326, 30, 20000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 14:19:51'),
(103, 1, 49, 327, 30, 20000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 14:19:51'),
(104, 1, 49, 328, 30, 20000.00, '2026-02-28', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-08 14:19:51'),
(105, 1, 50, 353, 21, 281000.00, '2026-03-06', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-08 14:24:21'),
(106, 1, 51, 354, 10, 315000.00, '2026-02-28', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-08 14:26:12'),
(107, 1, 58, 362, 24, 220000.00, '2026-03-06', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-08 14:31:30'),
(108, 1, 35, 241, 19, 50000.00, '2026-03-08', 4, 'efectivo', 0, NULL, 'Renovación - pago de intereses', 1, 0, NULL, NULL, '2026-03-08 15:44:42'),
(109, 1, 50, 353, 21, 195000.00, '2026-03-08', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-08 15:52:57'),
(110, 1, 49, 329, 30, 20000.00, '2026-03-05', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-09 08:14:48'),
(111, 1, 49, 330, 30, 20000.00, '2026-03-09', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-09 08:14:57'),
(112, 1, 49, 331, 30, 20000.00, '2026-03-09', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-09 08:15:05'),
(113, 1, 38, 267, 29, 70000.00, '2026-03-07', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-09 08:16:03'),
(114, 1, 37, 257, 28, 70000.00, '2026-03-09', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-09 08:16:32'),
(115, 1, 49, 332, 30, 20000.00, '2026-03-09', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-10 23:21:51'),
(116, 1, 49, 333, 30, 20000.00, '2026-03-10', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-10 23:22:03'),
(117, 1, 49, 334, 30, 20000.00, '2026-03-11', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-12 06:54:08'),
(118, 1, 43, 292, 31, 70000.00, '2026-03-11', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-12 06:54:43'),
(119, 1, 36, 245, 27, 70000.00, '2026-03-11', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-12 06:55:20'),
(120, 1, 42, 284, 27, 30000.00, '2026-03-12', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-12 06:55:54'),
(121, 1, 56, 359, 1, 150000.00, '2026-03-13', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-13 08:33:22'),
(122, 1, 49, 335, 30, 20000.00, '2026-03-13', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-14 07:08:50'),
(123, 1, 49, 314, 30, 20000.00, '2026-03-13', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-14 07:09:16'),
(124, 2, 11, 97, 15, 45000.00, '2026-03-14', 1, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-14 14:45:45'),
(125, 2, 11, 98, 15, 5000.00, '2026-03-14', 1, 'efectivo', 1, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-14 14:45:45'),
(126, 2, 8, 41, 12, 880000.00, '2026-03-14', 1, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-14 14:48:46'),
(127, 1, 49, 315, 30, 20000.00, '2026-03-14', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-16 08:25:23'),
(128, 1, 68, 400, 7, 60000.00, '2026-03-14', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-16 08:26:56'),
(129, 1, 59, 363, 25, 70000.00, '2026-03-14', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-16 08:27:24'),
(130, 1, 43, 293, 31, 70000.00, '2026-03-16', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-16 08:27:43'),
(131, 1, 53, 356, 10, 550000.00, '2026-03-16', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-16 22:02:07'),
(132, 2, 1, 2, 16, 100000.00, '2026-03-16', 1, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-16 22:27:40'),
(133, 2, 1, 3, 16, 100000.00, '2026-03-16', 1, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-16 22:27:40'),
(134, 1, 37, 258, 28, 50000.00, '2026-03-16', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-16 23:06:12'),
(135, 1, 57, 360, 5, 460000.00, '2026-03-17', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-17 22:40:26'),
(136, 1, 47, 303, 32, 140000.00, '2026-03-17', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-17 22:42:07'),
(137, 1, 49, 336, 30, 20000.00, '2026-03-17', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-17 22:43:24'),
(138, 1, 49, 337, 30, 20000.00, '2026-03-18', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-18 20:29:48'),
(139, 2, 6, 39, 10, 660000.00, '2026-03-18', 1, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-18 20:30:53'),
(140, 1, 49, 338, 30, 20000.00, '2026-03-18', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-20 08:56:31'),
(141, 1, 68, 400, 7, 60000.00, '2026-03-20', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-20 08:57:57'),
(142, 1, 66, 387, 7, 70000.00, '2026-03-20', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-20 08:58:24'),
(143, 1, 66, 388, 7, 20000.00, '2026-03-20', 4, 'efectivo', 1, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-20 08:58:24'),
(144, 1, 55, 358, 23, 120000.00, '2026-03-20', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-20 08:59:18'),
(145, 1, 69, 401, 6, 325000.00, '2026-03-20', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-20 16:18:46'),
(146, 1, 62, 382, 6, 275000.00, '2026-03-20', 4, 'efectivo', 0, NULL, '', 1, 1, '2026-03-20 16:25:59', 1, '2026-03-20 16:19:47'),
(147, 1, 62, 382, 6, 55000.00, '2026-03-20', 4, 'efectivo', 0, NULL, '', 1, 1, '2026-03-20 16:25:16', 1, '2026-03-20 16:19:57'),
(148, 1, 62, 382, 6, 275000.00, '2026-03-20', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-20 16:26:32'),
(149, 1, 61, 381, 34, 220000.00, '2026-03-20', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-20 22:42:07'),
(150, 1, 49, 339, 30, 20000.00, '2026-03-20', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-20 22:42:52'),
(151, 1, 49, 340, 30, 20000.00, '2026-03-20', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-20 22:43:03'),
(152, 2, 71, 405, 30, 50000.00, '2026-03-20', 1, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-20 22:45:11'),
(153, 1, 49, 341, 30, 20000.00, '2026-03-21', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-22 08:12:25'),
(154, 1, 38, 269, 29, 70000.00, '2026-03-21', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-22 08:13:05'),
(155, 1, 74, 414, 3, 300000.00, '2026-03-22', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-22 08:17:13'),
(156, 1, 74, 415, 3, 150000.00, '2026-03-22', 4, 'efectivo', 1, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-22 08:17:13'),
(157, 1, 59, 364, 25, 70000.00, '2026-03-22', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-23 07:05:00'),
(158, 1, 74, 415, 3, 150000.00, '2026-03-24', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-24 11:40:49'),
(159, 2, 11, 98, 15, 40000.00, '2026-03-24', 1, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-24 18:53:21'),
(160, 2, 11, 99, 15, 40000.00, '2026-03-24', 1, 'efectivo', 1, NULL, 'Excedente de cuota #2', 1, 0, NULL, NULL, '2026-03-24 18:53:21'),
(161, 1, 43, 294, 31, 70000.00, '2026-03-24', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-24 22:04:20'),
(162, 1, 49, 342, 30, 20000.00, '2026-03-25', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-26 10:04:01'),
(163, 1, 49, 343, 30, 20000.00, '2026-03-25', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-26 10:04:11'),
(164, 1, 80, 427, 29, 10000.00, '2026-03-25', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-26 10:05:20'),
(165, 2, 9, 42, 13, 1000000.00, '2026-03-26', 1, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-26 10:05:59'),
(166, 1, 82, 503, 36, 200000.00, '2026-03-27', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-27 10:31:12'),
(167, 1, 80, 428, 29, 10000.00, '2026-03-26', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-27 10:36:58'),
(168, 1, 49, 344, 30, 20000.00, '2026-03-26', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-27 10:37:26'),
(169, 1, 60, 373, 26, 97500.00, '2026-03-27', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-27 20:23:01'),
(170, 1, 60, 374, 26, 97500.00, '2026-03-27', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-27 20:23:01'),
(171, 1, 60, 375, 26, 5000.00, '2026-03-27', 4, 'efectivo', 1, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-27 20:23:01'),
(172, 1, 49, 345, 30, 20000.00, '2026-03-27', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-28 00:49:58'),
(173, 1, 80, 429, 29, 10000.00, '2026-03-27', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-28 00:50:43'),
(174, 2, 71, 406, 30, 50000.00, '2026-03-27', 1, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-28 00:51:37'),
(175, 1, 66, 388, 7, 50000.00, '2026-03-28', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-28 13:30:01'),
(176, 1, 66, 389, 7, 20000.00, '2026-03-28', 4, 'efectivo', 1, NULL, 'Excedente de cuota #2', 1, 0, NULL, NULL, '2026-03-28 13:30:01'),
(177, 1, 52, 355, 22, 360000.00, '2026-03-28', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-28 16:21:07'),
(178, 2, 7, 40, 11, 600000.00, '2026-03-28', 1, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-28 16:27:54'),
(179, 1, 80, 430, 29, 10000.00, '2026-03-28', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-29 00:20:12'),
(180, 1, 59, 365, 25, 70000.00, '2026-03-28', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-29 00:20:37'),
(181, 1, 38, 268, 29, 70000.00, '2026-03-28', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-29 00:21:16'),
(182, 1, 82, 503, 36, 70000.00, '2026-03-29', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-29 14:32:37'),
(183, 1, 82, 504, 36, 270000.00, '2026-03-29', 4, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-29 14:32:37'),
(184, 1, 82, 505, 36, 160000.00, '2026-03-29', 4, 'efectivo', 1, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-29 14:32:37'),
(185, 1, 49, 346, 30, 20000.00, '2026-03-29', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-29 21:05:34'),
(186, 1, 49, 347, 30, 20000.00, '2026-03-29', 4, 'efectivo', 0, NULL, 'Excedente de cuota #39', 1, 0, NULL, NULL, '2026-03-29 21:05:34'),
(187, 1, 49, 348, 30, 20000.00, '2026-03-29', 4, 'efectivo', 0, NULL, 'Excedente de cuota #39', 1, 0, NULL, NULL, '2026-03-29 21:05:34'),
(188, 1, 49, 349, 30, 20000.00, '2026-03-29', 4, 'efectivo', 0, NULL, 'Excedente de cuota #39', 1, 0, NULL, NULL, '2026-03-29 21:05:34'),
(189, 1, 49, 350, 30, 20000.00, '2026-03-29', 4, 'efectivo', 0, NULL, 'Excedente de cuota #39', 1, 0, NULL, NULL, '2026-03-29 21:05:34'),
(190, 1, 49, 351, 30, 20000.00, '2026-03-29', 4, 'efectivo', 0, NULL, 'Excedente de cuota #39', 1, 0, NULL, NULL, '2026-03-29 21:05:34'),
(191, 1, 49, 352, 30, 20000.00, '2026-03-29', 4, 'efectivo', 0, NULL, 'Excedente de cuota #39', 1, 0, NULL, NULL, '2026-03-29 21:05:34'),
(192, 2, 5, 38, 9, 480000.00, '2026-03-30', 1, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-30 14:55:46'),
(193, 1, 77, 421, 33, 120000.00, '2026-03-30', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-30 14:59:20'),
(194, 1, 43, 295, 31, 70000.00, '2026-03-30', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-30 18:58:30'),
(195, 1, 56, 359, 1, 150000.00, '2026-03-30', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-30 18:59:17'),
(196, 1, 67, 395, 1, 260000.00, '2026-03-30', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-30 18:59:57'),
(197, 1, 87, 535, 30, 35000.00, '2026-03-31', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-31 09:01:12'),
(198, 1, 57, 361, 5, 460000.00, '2026-03-31', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-31 11:16:59'),
(199, 2, 3, 30, 5, 227000.00, '2026-03-31', 1, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-31 11:17:45'),
(200, 2, 3, 31, 5, 227000.00, '2026-03-31', 1, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-31 11:17:45'),
(201, 2, 4, 36, 8, 282000.00, '2026-03-31', 1, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-31 11:46:43'),
(202, 2, 4, 37, 8, 282000.00, '2026-03-31', 1, 'efectivo', 0, NULL, 'Excedente de cuota #1', 1, 0, NULL, NULL, '2026-03-31 11:46:43'),
(203, 1, 80, 431, 29, 10000.00, '2026-03-31', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-31 11:51:43'),
(204, 1, 80, 432, 29, 10000.00, '2026-03-31', 4, 'efectivo', 0, NULL, 'Excedente de cuota #5', 1, 0, NULL, NULL, '2026-03-31 11:51:43'),
(205, 1, 75, 418, 10, 110000.00, '2026-03-31', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-31 14:39:25'),
(206, 1, 37, 258, 28, 20000.00, '2026-03-31', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-31 18:36:41'),
(207, 1, 37, 259, 28, 70000.00, '2026-03-31', 4, 'efectivo', 0, NULL, 'Excedente de cuota #7', 1, 0, NULL, NULL, '2026-03-31 18:36:41'),
(208, 1, 37, 260, 28, 50000.00, '2026-03-31', 4, 'efectivo', 1, NULL, 'Excedente de cuota #7', 1, 0, NULL, NULL, '2026-03-31 18:36:41'),
(209, 1, 87, 536, 30, 35000.00, '2026-03-31', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-02 09:46:40'),
(210, 1, 87, 537, 30, 35000.00, '2026-04-01', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-02 09:47:06'),
(211, 1, 62, 382, 6, 55000.00, '2026-04-01', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-02 09:49:03'),
(212, 1, 72, 412, 6, 55000.00, '2026-04-01', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-02 09:49:28'),
(213, 1, 69, 402, 6, 190000.00, '2026-04-01', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-02 09:50:24'),
(214, 1, 80, 433, 29, 10000.00, '2026-04-02', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-03 12:56:25'),
(215, 1, 80, 434, 29, 10000.00, '2026-04-02', 4, 'efectivo', 0, NULL, 'Excedente de cuota #7', 1, 0, NULL, NULL, '2026-04-03 12:56:25'),
(216, 1, 63, 423, 10, 480000.00, '2026-04-03', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-03 13:01:21'),
(217, 1, 78, 424, 10, 400000.00, '2026-04-03', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-03 13:07:24'),
(218, 1, 38, 270, 29, 70000.00, '2026-04-04', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-04 23:27:12'),
(219, 1, 87, 538, 30, 35000.00, '2026-04-04', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-04 23:32:20'),
(220, 1, 66, 389, 7, 50000.00, '2026-04-04', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-04 23:34:52'),
(221, 1, 66, 390, 7, 20000.00, '2026-04-04', 4, 'efectivo', 1, NULL, 'Excedente de cuota #3', 1, 0, NULL, NULL, '2026-04-04 23:34:52'),
(222, 1, 50, 353, 21, 100000.00, '2026-04-04', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-04 23:36:14'),
(223, 1, 47, 304, 32, 140000.00, '2026-04-05', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-05 21:30:47'),
(224, 2, 71, 407, 30, 50000.00, '2026-04-05', 1, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-05 21:32:08'),
(225, 2, 2, 13, 4, 100000.00, '2026-04-06', 1, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-06 22:58:05'),
(226, 1, 50, 353, 21, 50000.00, '2026-04-06', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-06 22:59:20'),
(227, 1, 87, 539, 30, 35000.00, '2026-04-06', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-06 23:00:27'),
(228, 1, 80, 435, 29, 10000.00, '2026-04-05', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-06 23:01:33'),
(229, 1, 80, 436, 29, 10000.00, '2026-04-05', 4, 'efectivo', 0, NULL, 'Excedente de cuota #9', 1, 0, NULL, NULL, '2026-04-06 23:01:33'),
(230, 1, 80, 437, 29, 10000.00, '2026-04-07', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-07 22:27:11'),
(231, 1, 80, 438, 29, 10000.00, '2026-04-07', 4, 'efectivo', 0, NULL, 'Excedente de cuota #11', 1, 0, NULL, NULL, '2026-04-07 22:27:11'),
(232, 1, 73, 426, 34, 220000.00, '2026-04-07', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-07 22:27:48'),
(233, 1, 79, 425, 34, 220000.00, '2026-04-07', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-07 22:28:05'),
(234, 1, 87, 540, 30, 35000.00, '2026-04-07', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-07 23:02:42'),
(235, 1, 64, 384, 18, 250000.00, '2026-04-09', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-09 10:18:24'),
(236, 1, 103, 685, 3, 7500.00, '2026-04-09', NULL, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-04-09 16:24:32'),
(237, 1, 102, 684, 18, 120000.00, '2026-04-09', NULL, 'efectivo', 0, NULL, NULL, 2, 0, NULL, NULL, '2026-04-09 16:51:26'),
(238, 3, 107, 695, 47, 15000.00, '2026-04-10', NULL, 'efectivo', 0, NULL, NULL, 2, 0, NULL, NULL, '2026-04-10 15:31:31'),
(239, 3, 108, 740, 48, 85000.00, '2026-04-10', NULL, 'efectivo', 0, NULL, NULL, 2, 0, NULL, NULL, '2026-04-10 15:35:03'),
(240, 3, 108, 741, 48, 85000.00, '2026-04-10', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-10 15:35:03'),
(241, 3, 108, 742, 48, 80000.00, '2026-04-10', NULL, 'efectivo', 1, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-10 15:35:03'),
(242, 3, 107, 696, 47, 15000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, NULL, 2, 0, NULL, NULL, '2026-04-11 08:10:02'),
(243, 3, 107, 697, 47, 15000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #2', 2, 0, NULL, NULL, '2026-04-11 08:10:02'),
(244, 3, 107, 698, 47, 15000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #2', 2, 0, NULL, NULL, '2026-04-11 08:10:02'),
(245, 3, 107, 699, 47, 15000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #2', 2, 0, NULL, NULL, '2026-04-11 08:10:02'),
(246, 3, 107, 700, 47, 15000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #2', 2, 0, NULL, NULL, '2026-04-11 08:10:02'),
(247, 3, 107, 701, 47, 15000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #2', 2, 0, NULL, NULL, '2026-04-11 08:10:02'),
(248, 3, 107, 702, 47, 15000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #2', 2, 0, NULL, NULL, '2026-04-11 08:10:02'),
(249, 3, 107, 703, 47, 15000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #2', 2, 0, NULL, NULL, '2026-04-11 08:10:02'),
(250, 3, 107, 704, 47, 15000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #2', 2, 0, NULL, NULL, '2026-04-11 08:10:02'),
(251, 3, 107, 705, 47, 15000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #2', 2, 0, NULL, NULL, '2026-04-11 08:10:02'),
(252, 3, 110, 772, 49, 12000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, NULL, 2, 0, NULL, NULL, '2026-04-11 14:36:59'),
(253, 3, 108, 742, 48, 5000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, NULL, 2, 0, NULL, NULL, '2026-04-11 14:38:27'),
(254, 3, 108, 743, 48, 85000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #3', 2, 0, NULL, NULL, '2026-04-11 14:38:27'),
(255, 3, 108, 744, 48, 85000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #3', 2, 0, NULL, NULL, '2026-04-11 14:38:27'),
(256, 3, 108, 745, 48, 85000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #3', 2, 0, NULL, NULL, '2026-04-11 14:38:27'),
(257, 3, 108, 746, 48, 85000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #3', 2, 0, NULL, NULL, '2026-04-11 14:38:27'),
(258, 3, 108, 747, 48, 85000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #3', 2, 0, NULL, NULL, '2026-04-11 14:38:27'),
(259, 3, 109, 748, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, NULL, 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(260, 3, 109, 749, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(261, 3, 109, 750, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(262, 3, 109, 751, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(263, 3, 109, 752, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(264, 3, 109, 753, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(265, 3, 109, 754, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(266, 3, 109, 755, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(267, 3, 109, 756, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(268, 3, 109, 757, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(269, 3, 109, 758, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(270, 3, 109, 759, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(271, 3, 109, 760, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(272, 3, 109, 761, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(273, 3, 109, 762, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(274, 3, 109, 763, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(275, 3, 109, 764, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(276, 3, 109, 765, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(277, 3, 109, 766, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(278, 3, 109, 767, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(279, 3, 109, 768, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(280, 3, 109, 769, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(281, 3, 109, 770, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(282, 3, 109, 771, 49, 10000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, 'Excedente de cuota #1', 2, 0, NULL, NULL, '2026-04-11 14:39:00'),
(283, 3, 115, 809, 48, 60000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, NULL, 2, 0, NULL, NULL, '2026-04-11 14:40:06'),
(284, 3, 115, 809, 48, 60000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, NULL, 2, 0, NULL, NULL, '2026-04-11 14:40:37'),
(285, 3, 116, 810, 49, 60000.00, '2026-04-11', NULL, 'efectivo', 0, NULL, NULL, 2, 0, NULL, NULL, '2026-04-11 14:45:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `papeleria`
--

CREATE TABLE `papeleria` (
  `id` int(11) NOT NULL,
  `cobro_id` int(11) NOT NULL,
  `prestamo_id` int(11) NOT NULL,
  `cobrador_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `monto_prestado` decimal(15,2) NOT NULL,
  `pct_aplicado` decimal(5,2) NOT NULL,
  `monto_papeleria` decimal(15,2) NOT NULL,
  `liquidacion_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `papeleria`
--

INSERT INTO `papeleria` (`id`, `cobro_id`, `prestamo_id`, `cobrador_id`, `fecha`, `monto_prestado`, `pct_aplicado`, `monto_papeleria`, `liquidacion_id`, `created_at`) VALUES
(1, 1, 38, 1, '2026-01-24', 500000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(2, 1, 40, 1, '2026-03-05', 200000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(3, 1, 43, 1, '2026-02-01', 500000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(4, 1, 47, 1, '2026-02-15', 500000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(5, 1, 54, 1, '2026-03-01', 200000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(6, 1, 59, 1, '2026-03-07', 500000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(7, 1, 60, 1, '2026-03-01', 600000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(8, 1, 64, 1, '2026-03-01', 500000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(9, 1, 65, 1, '2026-03-08', 250000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(10, 1, 66, 1, '2026-03-14', 400000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(11, 1, 67, 1, '2026-03-15', 1000000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(12, 1, 69, 1, '2026-03-05', 975000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(13, 1, 74, 1, '2026-03-20', 1200000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(14, 1, 76, 1, '2026-03-25', 200000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(15, 1, 77, 1, '2026-03-21', 200000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(16, 1, 78, 1, '2026-03-21', 400000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(17, 1, 82, 1, '2026-03-27', 1900000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(18, 1, 84, 1, '2026-03-28', 300000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(19, 1, 86, 1, '2026-04-05', 500000.00, 5.00, 0.00, 3, '2026-04-09 13:29:15'),
(20, 1, 88, 1, '2026-03-30', 100000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(21, 1, 90, 1, '2026-03-31', 200000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(22, 1, 93, 1, '2026-04-03', 200000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(23, 1, 94, 1, '2026-03-29', 400000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(24, 1, 95, 1, '2026-04-01', 500000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(25, 1, 96, 1, '2026-04-01', 600000.00, 5.00, 30000.00, NULL, '2026-04-09 13:29:15'),
(26, 1, 97, 1, '2026-04-07', 500000.00, 10.00, 50000.00, 5, '2026-04-09 13:29:15'),
(27, 1, 98, 1, '2026-04-09', 200000.00, 20.00, 40000.00, 7, '2026-04-09 13:29:15'),
(28, 1, 99, 1, '2026-04-09', 200000.00, 20.00, 40000.00, 7, '2026-04-09 13:29:15'),
(29, 1, 100, 1, '2026-04-09', 150000.00, 5.00, 7500.00, 7, '2026-04-09 13:29:15'),
(30, 1, 101, 1, '2026-04-09', 150000.00, 20.00, 30000.00, 7, '2026-04-09 13:29:15'),
(31, 1, 36, 1, '2026-01-24', 500000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(32, 1, 37, 1, '2026-01-24', 500000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(33, 1, 42, 1, '2026-02-28', 200000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(34, 1, 50, 1, '2026-03-01', 1100000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(35, 1, 80, 1, '2026-03-24', 500000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(36, 1, 87, 1, '2026-03-29', 1200000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(37, 1, 35, 1, '2026-02-01', 250000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(38, 1, 32, 1, '2025-12-26', 1000000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(39, 1, 33, 1, '2025-12-27', 150000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(40, 1, 34, 1, '2026-01-04', 150000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(41, 1, 39, 1, '2026-01-24', 100000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(42, 1, 41, 1, '2026-02-27', 200000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(43, 1, 44, 1, '2026-02-02', 300000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(44, 1, 45, 1, '2026-02-01', 100000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(45, 1, 46, 1, '2026-02-01', 200000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(46, 1, 48, 1, '2026-02-05', 200000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(47, 1, 49, 1, '2026-02-08', 700000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(48, 1, 51, 1, '2026-02-21', 300000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(49, 1, 52, 1, '2026-02-28', 300000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(50, 1, 53, 1, '2026-03-01', 500000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(51, 1, 55, 1, '2026-03-02', 100000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(52, 1, 56, 1, '2026-03-02', 250000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(53, 1, 57, 1, '2026-03-02', 800000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(54, 1, 58, 1, '2026-03-04', 200000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(55, 1, 61, 1, '2026-03-07', 200000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(56, 1, 62, 1, '2026-03-07', 300000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(57, 1, 63, 1, '2026-03-08', 400000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(58, 1, 68, 1, '2026-03-14', 120000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(59, 1, 72, 1, '2026-03-16', 50000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(60, 1, 73, 1, '2026-03-20', 200000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(61, 1, 75, 1, '2026-03-21', 100000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(62, 1, 79, 1, '2026-03-23', 200000.00, 5.00, 0.00, NULL, '2026-04-09 13:29:15'),
(63, 2, 1, 1, '2026-02-15', 600000.00, 10.00, 0.00, NULL, '2026-04-09 13:29:15'),
(64, 2, 3, 1, '2026-03-01', 1000000.00, 10.00, 0.00, NULL, '2026-04-09 13:29:15'),
(65, 2, 10, 1, '2026-03-01', 2700000.00, 10.00, 0.00, NULL, '2026-04-09 13:29:15'),
(66, 2, 70, 1, '2026-03-15', 400000.00, 10.00, 0.00, NULL, '2026-04-09 13:29:15'),
(67, 2, 71, 1, '2026-03-14', 200000.00, 10.00, 0.00, NULL, '2026-04-09 13:29:15'),
(68, 2, 81, 1, '2026-03-25', 2000000.00, 10.00, 0.00, NULL, '2026-04-09 13:29:15'),
(69, 2, 83, 1, '2026-03-27', 2000000.00, 10.00, 0.00, NULL, '2026-04-09 13:29:15'),
(70, 2, 85, 1, '2026-03-28', 450000.00, 10.00, 0.00, NULL, '2026-04-09 13:29:15'),
(71, 2, 89, 1, '2026-03-30', 280000.00, 10.00, 0.00, NULL, '2026-04-09 13:29:15'),
(72, 2, 91, 1, '2026-03-31', 364000.00, 10.00, 0.00, NULL, '2026-04-09 13:29:15'),
(73, 2, 92, 1, '2026-03-31', 1500000.00, 10.00, 0.00, NULL, '2026-04-09 13:29:15'),
(74, 2, 2, 1, '2026-02-20', 600000.00, 10.00, 0.00, NULL, '2026-04-09 13:29:15'),
(75, 2, 11, 1, '2026-03-01', 180000.00, 10.00, 0.00, NULL, '2026-04-09 13:29:15'),
(76, 2, 4, 1, '2026-03-01', 470000.00, 10.00, 0.00, NULL, '2026-04-09 13:29:15'),
(77, 2, 5, 1, '2026-03-01', 400000.00, 10.00, 0.00, NULL, '2026-04-09 13:29:15'),
(78, 2, 6, 1, '2026-03-01', 600000.00, 10.00, 0.00, NULL, '2026-04-09 13:29:15'),
(79, 2, 7, 1, '2026-03-01', 500000.00, 10.00, 0.00, NULL, '2026-04-09 13:29:15'),
(80, 2, 8, 1, '2026-03-01', 800000.00, 10.00, 0.00, NULL, '2026-04-09 13:29:15'),
(81, 2, 9, 1, '2026-02-22', 1000000.00, 10.00, 0.00, NULL, '2026-04-09 13:29:15'),
(128, 1, 102, 2, '2026-04-09', 100000.00, 20.00, 20000.00, 7, '2026-04-09 14:41:42'),
(129, 1, 103, 2, '2026-04-09', 25000.00, 20.00, 5000.00, 7, '2026-04-09 16:15:34'),
(130, 1, 104, 2, '2026-04-09', 520000.00, 5.00, 26000.00, NULL, '2026-04-09 19:56:12'),
(131, 1, 105, 2, '2026-04-10', 20000.00, 5.00, 1000.00, NULL, '2026-04-10 13:13:29'),
(132, 1, 106, 2, '2026-04-10', 26000.00, 5.00, 1300.00, NULL, '2026-04-10 13:17:27'),
(133, 3, 107, 2, '2026-04-10', 500000.00, 10.00, 50000.00, 9, '2026-04-10 15:31:16'),
(134, 3, 108, 2, '2026-04-10', 500000.00, 10.00, 50000.00, 9, '2026-04-10 15:32:37'),
(135, 3, 109, 2, '2026-04-10', 200000.00, 10.00, 20000.00, 9, '2026-04-10 15:33:43'),
(136, 3, 110, 2, '2026-04-10', 10000.00, 10.00, 1000.00, 9, '2026-04-10 16:52:07'),
(137, 1, 111, 1, '2026-04-11', 100000.00, 5.00, 5000.00, NULL, '2026-04-11 09:43:06'),
(138, 1, 112, 1, '2026-04-11', 125000.00, 5.00, 6250.00, NULL, '2026-04-11 10:23:42'),
(139, 1, 113, 1, '2026-04-11', 750000.00, 5.00, 37500.00, NULL, '2026-04-11 11:42:59'),
(140, 3, 114, 2, '2026-04-11', 950000.00, 10.00, 95000.00, NULL, '2026-04-11 11:46:23'),
(141, 3, 115, 2, '2026-04-11', 100000.00, 10.00, 10000.00, NULL, '2026-04-11 14:39:58'),
(142, 3, 116, 2, '2026-04-11', 100000.00, 10.00, 10000.00, NULL, '2026-04-11 14:41:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `papeleria_categorias`
--

CREATE TABLE `papeleria_categorias` (
  `id` int(11) NOT NULL,
  `cobro_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `papeleria_categorias`
--

INSERT INTO `papeleria_categorias` (`id`, `cobro_id`, `nombre`, `activa`) VALUES
(1, 1, 'COBRADOR', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `papeleria_liquidaciones`
--

CREATE TABLE `papeleria_liquidaciones` (
  `id` int(11) NOT NULL,
  `cobro_id` int(11) NOT NULL,
  `cobrador_id` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `total_papeleria` decimal(15,2) NOT NULL DEFAULT 0.00,
  `monto_cobrador` decimal(15,2) NOT NULL DEFAULT 0.00,
  `pct_cobrador` decimal(5,2) NOT NULL DEFAULT 0.00,
  `monto_empresa` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notas` text DEFAULT NULL,
  `cerrada_at` datetime DEFAULT NULL,
  `cerrada_por` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `papeleria_salidas`
--

CREATE TABLE `papeleria_salidas` (
  `id` int(11) NOT NULL,
  `cobro_id` int(11) NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `monto` decimal(15,2) NOT NULL,
  `fecha` date NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `papeleria_salidas`
--

INSERT INTO `papeleria_salidas` (`id`, `cobro_id`, `categoria_id`, `descripcion`, `monto`, `fecha`, `usuario_id`, `created_at`) VALUES
(1, 1, 1, 'PAGO DEL 50%', 50000.00, '2026-04-09', 1, '2026-04-09 14:14:53'),
(2, 1, 1, 'pROBADNO', 20000.00, '2026-04-02', 1, '2026-04-09 14:15:14'),
(3, 1, 1, 'pROBADNO', 10000.00, '2026-03-12', 1, '2026-04-09 14:15:41'),
(4, 1, 1, 'pago del 50%', 49000.00, '2026-04-09', 1, '2026-04-09 19:49:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamos`
--

CREATE TABLE `prestamos` (
  `id` int(11) NOT NULL,
  `cobro_id` int(11) NOT NULL,
  `deudor_id` int(11) NOT NULL,
  `capitalista_id` int(11) DEFAULT NULL,
  `monto_prestado` decimal(15,2) NOT NULL,
  `tipo_interes` enum('porcentaje','valor_fijo') NOT NULL DEFAULT 'porcentaje',
  `interes_valor` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `interes_calculado` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_a_pagar` decimal(15,2) NOT NULL,
  `frecuencia_pago` enum('diario','semanal','quincenal','mensual') NOT NULL DEFAULT 'mensual',
  `num_cuotas` int(11) NOT NULL DEFAULT 1,
  `valor_cuota` decimal(15,2) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin_esperada` date NOT NULL,
  `cuenta_desembolso_id` int(11) DEFAULT NULL,
  `metodo_desembolso` enum('efectivo','transferencia','nequi','otro') DEFAULT 'efectivo',
  `saldo_pendiente` decimal(15,2) NOT NULL,
  `dias_mora` int(11) NOT NULL DEFAULT 0,
  `estado` enum('activo','en_mora','en_acuerdo','renovado','refinanciado','pagado','incobrable','anulado') NOT NULL DEFAULT 'activo',
  `fecha_acuerdo` date DEFAULT NULL,
  `fecha_compromiso` date DEFAULT NULL,
  `nota_acuerdo` varchar(255) DEFAULT NULL,
  `tipo_origen` enum('nuevo','renovacion','refinanciacion') NOT NULL DEFAULT 'nuevo',
  `prestamo_padre_id` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `papeleria_pct` decimal(5,2) DEFAULT NULL,
  `papeleria_monto` decimal(15,2) DEFAULT 0.00,
  `anulado_at` datetime DEFAULT NULL,
  `anulado_por` int(11) DEFAULT NULL,
  `editado_at` datetime DEFAULT NULL,
  `editado_por` int(11) DEFAULT NULL,
  `historial` text DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `omitir_domingos` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `prestamos`
--

INSERT INTO `prestamos` (`id`, `cobro_id`, `deudor_id`, `capitalista_id`, `monto_prestado`, `tipo_interes`, `interes_valor`, `interes_calculado`, `total_a_pagar`, `frecuencia_pago`, `num_cuotas`, `valor_cuota`, `fecha_inicio`, `fecha_fin_esperada`, `cuenta_desembolso_id`, `metodo_desembolso`, `saldo_pendiente`, `dias_mora`, `estado`, `fecha_acuerdo`, `fecha_compromiso`, `nota_acuerdo`, `tipo_origen`, `prestamo_padre_id`, `observaciones`, `papeleria_pct`, `papeleria_monto`, `anulado_at`, `anulado_por`, `editado_at`, `editado_por`, `historial`, `usuario_id`, `omitir_domingos`, `created_at`, `updated_at`) VALUES
(1, 2, 16, NULL, 600000.00, 'porcentaje', 0.0000, 0.00, 600000.00, 'mensual', 6, 100000.00, '2026-02-15', '2026-08-14', 1, 'efectivo', 400000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, '2026-03-06 15:04:18', 1, '[{\"fecha\":\"2026-03-06 15:04:18\",\"usuario\":1,\"cambios\":{\"monto_prestado\":[\"6000000.00\",600000],\"interes_valor\":[\"20.0000\",0],\"num_cuotas\":[1,6],\"frecuencia_pago\":[\"mensual\",\"mensual\"]}}]', 1, 0, '2026-03-06 15:03:41', '2026-03-16 22:27:40'),
(2, 2, 4, NULL, 600000.00, 'valor_fijo', 200000.0000, 200000.00, 800000.00, 'quincenal', 5, 160000.00, '2026-02-20', '2026-05-06', 1, 'efectivo', 700000.00, 30, 'en_mora', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, '2026-03-06 15:05:52', 1, '[{\"fecha\":\"2026-03-06 15:05:52\",\"usuario\":1,\"cambios\":{\"monto_prestado\":[\"6000000.00\",600000],\"interes_valor\":[\"200000.0000\",200000],\"num_cuotas\":[5,5],\"frecuencia_pago\":[\"quincenal\",\"quincenal\"]}}]', 1, 0, '2026-03-06 15:05:10', '2026-04-06 22:58:05'),
(3, 2, 5, NULL, 1000000.00, 'valor_fijo', 362000.0000, 362000.00, 1362000.00, 'quincenal', 6, 227000.00, '2026-03-01', '2026-05-30', 1, 'efectivo', 908000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, '2026-03-06 15:08:41', 1, NULL, 1, 0, '2026-03-06 15:07:01', '2026-03-31 11:17:45'),
(4, 2, 8, NULL, 470000.00, 'porcentaje', 20.0000, 94000.00, 564000.00, 'quincenal', 2, 282000.00, '2026-03-01', '2026-03-31', 1, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-06 15:19:34', '2026-03-31 11:46:43'),
(5, 2, 9, NULL, 400000.00, 'porcentaje', 20.0000, 80000.00, 480000.00, 'mensual', 1, 480000.00, '2026-03-01', '2026-03-31', 1, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-06 15:19:56', '2026-03-30 14:55:46'),
(6, 2, 10, NULL, 600000.00, 'porcentaje', 10.0000, 60000.00, 660000.00, 'quincenal', 1, 660000.00, '2026-03-01', '2026-03-16', 1, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-06 15:21:18', '2026-03-18 20:30:53'),
(7, 2, 11, NULL, 500000.00, 'porcentaje', 20.0000, 100000.00, 600000.00, 'mensual', 1, 600000.00, '2026-03-01', '2026-03-31', 1, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-06 15:21:59', '2026-03-28 16:27:54'),
(8, 2, 12, NULL, 800000.00, 'porcentaje', 10.0000, 80000.00, 880000.00, 'quincenal', 1, 880000.00, '2026-03-01', '2026-03-16', 1, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-06 15:22:49', '2026-03-14 14:48:46'),
(9, 2, 13, NULL, 1000000.00, 'porcentaje', 0.0000, 0.00, 1000000.00, 'mensual', 1, 1000000.00, '2026-02-22', '2026-03-24', 1, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-06 15:23:14', '2026-03-26 10:05:59'),
(10, 2, 14, NULL, 2700000.00, 'porcentaje', 0.0000, 0.00, 2700000.00, 'quincenal', 27, 100000.00, '2026-03-01', '2027-04-10', 1, 'efectivo', 2700000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, '2026-03-06 15:23:53', 1, NULL, 1, 0, '2026-03-06 15:23:45', '2026-03-06 15:23:53'),
(11, 2, 15, NULL, 180000.00, 'porcentaje', 0.0000, 0.00, 180000.00, 'semanal', 4, 45000.00, '2026-03-01', '2026-03-29', 1, 'efectivo', 50000.00, 2, 'en_mora', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-06 15:24:42', '2026-03-24 18:53:21'),
(32, 1, 5, NULL, 1000000.00, 'porcentaje', 24.0000, 240000.00, 1240000.00, 'quincenal', 4, 310000.00, '2025-12-26', '2026-02-24', 4, 'efectivo', 0.00, 26, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-07 16:02:26', '2026-03-07 16:16:16'),
(33, 1, 23, NULL, 150000.00, 'porcentaje', 20.0000, 30000.00, 180000.00, 'mensual', 1, 180000.00, '2025-12-27', '2026-01-26', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 13:13:27', '2026-03-08 13:13:49'),
(34, 1, 23, NULL, 150000.00, 'porcentaje', 20.0000, 30000.00, 180000.00, 'mensual', 1, 180000.00, '2026-01-04', '2026-02-03', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 13:18:14', '2026-03-08 13:18:40'),
(35, 1, 19, NULL, 250000.00, 'porcentaje', 20.0000, 50000.00, 300000.00, 'mensual', 1, 300000.00, '2026-02-01', '2026-03-03', 4, 'efectivo', 0.00, 0, 'renovado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 13:20:01', '2026-03-08 15:44:42'),
(36, 1, 27, NULL, 500000.00, 'valor_fijo', 200000.0000, 200000.00, 700000.00, 'semanal', 10, 70000.00, '2026-01-24', '2026-04-04', 4, 'efectivo', 420000.00, 12, 'en_mora', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 13:21:31', '2026-03-12 06:55:20'),
(37, 1, 28, NULL, 500000.00, 'valor_fijo', 200000.0000, 200000.00, 700000.00, 'semanal', 10, 70000.00, '2026-01-24', '2026-04-04', 4, 'efectivo', 90000.00, 3, 'en_mora', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 13:22:56', '2026-03-31 18:36:41'),
(38, 1, 29, NULL, 500000.00, 'valor_fijo', 200000.0000, 200000.00, 700000.00, 'semanal', 10, 70000.00, '2026-01-24', '2026-04-04', 4, 'efectivo', 70000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 13:24:18', '2026-04-04 23:27:12'),
(39, 1, 6, NULL, 100000.00, 'porcentaje', 10.0000, 10000.00, 110000.00, 'quincenal', 1, 110000.00, '2026-01-24', '2026-02-08', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 13:35:12', '2026-03-08 13:35:31'),
(40, 1, 20, NULL, 200000.00, 'porcentaje', 20.0000, 40000.00, 240000.00, 'quincenal', 2, 120000.00, '2026-03-05', '2026-04-04', 4, 'efectivo', 240000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 13:36:41', '2026-03-08 13:36:41'),
(41, 1, 33, NULL, 200000.00, 'porcentaje', 20.0000, 40000.00, 240000.00, 'mensual', 1, 240000.00, '2026-02-27', '2026-03-29', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 13:45:05', '2026-03-08 13:45:30'),
(42, 1, 27, NULL, 200000.00, 'porcentaje', 40.0000, 80000.00, 280000.00, 'semanal', 4, 70000.00, '2026-02-28', '2026-03-28', 4, 'efectivo', 226000.00, 5, 'en_mora', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, '2026-03-08 13:49:02', 1, '[{\"fecha\":\"2026-03-08 13:48:37\",\"usuario\":1,\"cambios\":{\"monto_prestado\":[\"200000.00\",200000],\"interes_valor\":[\"40.0000\",40],\"num_cuotas\":[4,4],\"frecuencia_pago\":[\"semanal\",\"semanal\"]}},{\"fecha\":\"2026-03-08 13:49:02\",\"usuario\":1,\"cambios\":{\"monto_prestado\":[\"200000.00\",200000],\"interes_valor\":[\"40.0000\",40],\"num_cuotas\":[4,4],\"frecuencia_pago\":[\"semanal\",\"semanal\"]}}]', 1, 0, '2026-03-08 13:47:24', '2026-03-12 06:55:54'),
(43, 1, 31, NULL, 500000.00, 'valor_fijo', 200000.0000, 200000.00, 700000.00, 'semanal', 10, 70000.00, '2026-02-01', '2026-04-12', 4, 'efectivo', 140000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 13:53:53', '2026-03-30 18:58:30'),
(44, 1, 11, NULL, 300000.00, 'porcentaje', 20.0000, 60000.00, 360000.00, 'mensual', 1, 360000.00, '2026-02-02', '2026-03-04', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 13:58:52', '2026-03-08 13:59:05'),
(45, 1, 34, NULL, 100000.00, 'porcentaje', 20.0000, 20000.00, 120000.00, 'mensual', 1, 120000.00, '2026-02-01', '2026-03-03', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:12:33', '2026-03-08 14:13:05'),
(46, 1, 12, NULL, 200000.00, 'porcentaje', 20.0000, 40000.00, 240000.00, 'quincenal', 2, 120000.00, '2026-02-01', '2026-03-03', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:14:22', '2026-03-08 14:15:01'),
(47, 1, 32, NULL, 500000.00, 'valor_fijo', 200000.0000, 200000.00, 700000.00, 'quincenal', 5, 140000.00, '2026-02-15', '2026-05-01', 4, 'efectivo', 280000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:16:36', '2026-04-05 21:30:47'),
(48, 1, 10, NULL, 200000.00, 'porcentaje', 20.0000, 40000.00, 240000.00, 'mensual', 1, 240000.00, '2026-02-05', '2026-03-07', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:17:58', '2026-03-08 14:18:13'),
(49, 1, 30, NULL, 700000.00, 'valor_fijo', 200000.0000, 200000.00, 900000.00, 'diario', 45, 20000.00, '2026-02-08', '2026-03-25', 4, 'efectivo', 0.00, 3, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 1, '2026-03-08 14:19:12', '2026-03-29 21:05:34'),
(50, 1, 21, NULL, 1100000.00, 'porcentaje', 10.0000, 110000.00, 1210000.00, 'mensual', 1, 1210000.00, '2026-03-01', '2026-03-31', 4, 'efectivo', 584000.00, 6, 'en_mora', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:23:24', '2026-04-06 22:59:20'),
(51, 1, 10, NULL, 300000.00, 'porcentaje', 5.0000, 15000.00, 315000.00, 'semanal', 1, 315000.00, '2026-02-21', '2026-02-28', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:25:55', '2026-03-08 14:26:12'),
(52, 1, 22, NULL, 300000.00, 'porcentaje', 20.0000, 60000.00, 360000.00, 'mensual', 1, 360000.00, '2026-02-28', '2026-03-30', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:26:51', '2026-03-28 16:21:07'),
(53, 1, 10, NULL, 500000.00, 'porcentaje', 10.0000, 50000.00, 550000.00, 'quincenal', 1, 550000.00, '2026-03-01', '2026-03-16', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:27:42', '2026-03-16 22:02:07'),
(54, 1, 12, NULL, 200000.00, 'porcentaje', 20.0000, 40000.00, 240000.00, 'mensual', 1, 240000.00, '2026-03-01', '2026-03-31', 4, 'efectivo', 240000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:28:29', '2026-03-08 14:28:29'),
(55, 1, 23, NULL, 100000.00, 'porcentaje', 20.0000, 20000.00, 120000.00, 'mensual', 1, 120000.00, '2026-03-02', '2026-04-01', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:29:04', '2026-03-20 08:59:18'),
(56, 1, 1, NULL, 250000.00, 'porcentaje', 20.0000, 50000.00, 300000.00, 'mensual', 1, 300000.00, '2026-03-02', '2026-04-01', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:29:35', '2026-03-30 18:59:17'),
(57, 1, 5, NULL, 800000.00, 'porcentaje', 15.0000, 120000.00, 920000.00, 'quincenal', 2, 460000.00, '2026-03-02', '2026-04-01', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:30:29', '2026-03-31 11:16:59'),
(58, 1, 24, NULL, 200000.00, 'porcentaje', 10.0000, 20000.00, 220000.00, 'quincenal', 1, 220000.00, '2026-03-04', '2026-03-19', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:31:18', '2026-03-08 14:31:30'),
(59, 1, 25, NULL, 500000.00, 'valor_fijo', 200000.0000, 200000.00, 700000.00, 'semanal', 10, 70000.00, '2026-03-07', '2026-05-16', 4, 'efectivo', 490000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:35:31', '2026-03-29 00:20:37'),
(60, 1, 26, NULL, 600000.00, 'valor_fijo', 180000.0000, 180000.00, 780000.00, 'quincenal', 8, 97500.00, '2026-03-01', '2026-06-29', 4, 'efectivo', 580000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:37:09', '2026-03-27 20:23:01'),
(61, 1, 34, NULL, 200000.00, 'porcentaje', 10.0000, 20000.00, 220000.00, 'quincenal', 1, 220000.00, '2026-03-07', '2026-03-22', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:37:55', '2026-03-20 22:42:07'),
(62, 1, 6, NULL, 300000.00, 'porcentaje', 10.0000, 30000.00, 330000.00, 'quincenal', 1, 330000.00, '2026-03-07', '2026-03-22', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:38:46', '2026-04-02 09:49:03'),
(63, 1, 10, NULL, 400000.00, 'porcentaje', 20.0000, 80000.00, 480000.00, 'mensual', 1, 480000.00, '2026-03-08', '2026-04-07', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, '2026-03-21 17:54:26', 1, '[{\"fecha\":\"2026-03-21 17:54:26\",\"usuario\":1,\"cambios\":{\"monto_prestado\":[\"400000.00\",400000],\"interes_valor\":[\"10.0000\",20],\"num_cuotas\":[1,1],\"frecuencia_pago\":[\"quincenal\",\"mensual\"]}}]', 1, 0, '2026-03-08 14:39:47', '2026-04-03 13:01:21'),
(64, 1, 18, NULL, 500000.00, 'porcentaje', 0.0000, 0.00, 500000.00, 'mensual', 2, 250000.00, '2026-03-01', '2026-04-30', 4, 'efectivo', 250000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:42:14', '2026-04-09 10:18:24'),
(65, 1, 19, NULL, 250000.00, 'porcentaje', 20.0000, 50000.00, 300000.00, 'mensual', 1, 300000.00, '2026-03-08', '2026-04-07', 4, 'efectivo', 300000.00, 0, 'activo', NULL, NULL, NULL, 'renovacion', 35, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 15:44:42', '2026-03-08 15:44:42'),
(66, 1, 7, NULL, 400000.00, 'porcentaje', 40.0000, 160000.00, 560000.00, 'semanal', 8, 70000.00, '2026-03-14', '2026-05-09', 4, 'efectivo', 330000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-11 14:44:55', '2026-04-04 23:34:52'),
(67, 1, 1, NULL, 1000000.00, 'porcentaje', 30.0000, 300000.00, 1300000.00, 'quincenal', 5, 260000.00, '2026-03-15', '2026-05-29', 4, 'efectivo', 1040000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-13 08:41:39', '2026-03-30 18:59:57'),
(68, 1, 7, NULL, 120000.00, 'porcentaje', 0.0000, 0.00, 120000.00, 'mensual', 1, 120000.00, '2026-03-14', '2026-04-13', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-13 08:42:55', '2026-03-20 08:57:57'),
(69, 1, 6, NULL, 975000.00, 'porcentaje', 0.0000, 0.00, 975000.00, 'quincenal', 3, 325000.00, '2026-03-05', '2026-04-19', 4, 'efectivo', 460000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-13 09:07:40', '2026-04-02 09:50:24'),
(70, 2, 12, NULL, 400000.00, 'porcentaje', 10.0000, 40000.00, 440000.00, 'quincenal', 1, 440000.00, '2026-03-15', '2026-03-30', 1, 'efectivo', 440000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-14 14:49:21', '2026-03-14 14:49:21'),
(71, 2, 30, NULL, 200000.00, 'porcentaje', 25.0000, 50000.00, 250000.00, 'semanal', 5, 50000.00, '2026-03-14', '2026-04-18', 1, 'efectivo', 100000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-16 08:30:11', '2026-04-05 21:32:08'),
(72, 1, 6, NULL, 50000.00, 'porcentaje', 10.0000, 5000.00, 55000.00, 'quincenal', 1, 55000.00, '2026-03-16', '2026-03-31', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, '2026-03-20 16:24:33', 1, '[{\"fecha\":\"2026-03-20 16:23:19\",\"usuario\":1,\"cambios\":{\"monto_prestado\":[\"50000.00\",105000],\"interes_valor\":[\"10.0000\",10000],\"num_cuotas\":[1,1],\"frecuencia_pago\":[\"quincenal\",\"quincenal\"]}},{\"fecha\":\"2026-03-20 16:24:33\",\"usuario\":1,\"cambios\":{\"monto_prestado\":[\"105000.00\",50000],\"interes_valor\":[\"10000.0000\",10],\"num_cuotas\":[1,1],\"frecuencia_pago\":[\"quincenal\",\"quincenal\"]}}]', 1, 0, '2026-03-16 22:03:49', '2026-04-02 09:49:28'),
(73, 1, 34, NULL, 200000.00, 'porcentaje', 10.0000, 20000.00, 220000.00, 'quincenal', 1, 220000.00, '2026-03-20', '2026-04-04', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, '2026-03-23 16:43:13', 1, '[{\"fecha\":\"2026-03-23 16:43:13\",\"usuario\":1,\"cambios\":{\"monto_prestado\":[\"200000.00\",200000],\"interes_valor\":[\"20.0000\",10],\"num_cuotas\":[1,1],\"frecuencia_pago\":[\"mensual\",\"quincenal\"]}}]', 1, 0, '2026-03-20 22:41:42', '2026-04-07 22:27:48'),
(74, 1, 3, NULL, 1200000.00, 'porcentaje', 0.0000, 0.00, 1200000.00, 'mensual', 4, 300000.00, '2026-03-20', '2026-07-18', 4, 'efectivo', 600000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-20 22:50:06', '2026-03-24 11:40:49'),
(75, 1, 10, NULL, 100000.00, 'porcentaje', 10.0000, 10000.00, 110000.00, 'quincenal', 1, 110000.00, '2026-03-21', '2026-04-05', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-21 08:41:18', '2026-03-31 14:39:25'),
(76, 1, 35, NULL, 200000.00, 'porcentaje', 20.0000, 40000.00, 240000.00, 'quincenal', 2, 120000.00, '2026-03-25', '2026-04-24', 4, 'efectivo', 240000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-21 15:11:29', '2026-03-21 15:11:29'),
(77, 1, 33, NULL, 200000.00, 'porcentaje', 20.0000, 40000.00, 240000.00, 'mensual', 2, 120000.00, '2026-03-21', '2026-05-20', 4, 'efectivo', 120000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-21 15:49:49', '2026-03-30 14:59:20'),
(78, 1, 10, NULL, 400000.00, 'porcentaje', 10.0000, 40000.00, 440000.00, 'quincenal', 1, 440000.00, '2026-03-21', '2026-04-05', 4, 'efectivo', 40000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-21 17:55:32', '2026-04-03 13:07:24'),
(79, 1, 34, NULL, 200000.00, 'porcentaje', 10.0000, 20000.00, 220000.00, 'quincenal', 1, 220000.00, '2026-03-23', '2026-04-07', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-23 16:42:36', '2026-04-07 22:28:05'),
(80, 1, 29, NULL, 500000.00, 'porcentaje', 40.0000, 200000.00, 700000.00, 'diario', 70, 10000.00, '2026-03-24', '2026-06-02', 4, 'efectivo', 580000.00, 1, 'en_mora', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-24 22:02:00', '2026-04-07 22:27:11'),
(81, 2, 23, NULL, 2000000.00, 'valor_fijo', 790000.0000, 790000.00, 2790000.00, 'mensual', 6, 465000.00, '2026-03-25', '2026-09-21', 1, 'efectivo', 2790000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-25 15:09:14', '2026-03-25 15:09:14'),
(82, 1, 36, NULL, 1900000.00, 'valor_fijo', 800000.0000, 800000.00, 2700000.00, 'quincenal', 10, 270000.00, '2026-03-27', '2026-08-24', 4, 'efectivo', 2000000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-27 10:26:59', '2026-03-29 14:32:37'),
(83, 2, 37, NULL, 2000000.00, 'porcentaje', 25.0000, 500000.00, 2500000.00, 'quincenal', 10, 250000.00, '2026-03-27', '2026-08-24', 1, 'efectivo', 2500000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-27 10:28:32', '2026-03-27 10:28:32'),
(84, 1, 22, NULL, 300000.00, 'porcentaje', 20.0000, 60000.00, 360000.00, 'mensual', 1, 360000.00, '2026-03-28', '2026-04-27', 4, 'efectivo', 360000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-28 16:21:46', '2026-03-28 16:21:46'),
(85, 2, 11, NULL, 450000.00, 'porcentaje', 20.0000, 90000.00, 540000.00, 'mensual', 1, 540000.00, '2026-03-28', '2026-04-27', 1, 'efectivo', 540000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-28 16:28:23', '2026-03-28 16:28:23'),
(86, 1, 38, NULL, 500000.00, 'valor_fijo', 900000.0000, 900000.00, 1400000.00, 'quincenal', 10, 140000.00, '2026-04-05', '2026-09-02', 4, 'efectivo', 1400000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-29 14:31:40', '2026-03-29 14:31:40'),
(87, 1, 30, NULL, 1200000.00, 'valor_fijo', 375000.0000, 375000.00, 1575000.00, 'diario', 45, 35000.00, '2026-03-29', '2026-05-13', 4, 'efectivo', 1365000.00, 1, 'en_mora', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 1, '2026-03-29 21:04:58', '2026-04-07 23:02:42'),
(88, 1, 39, NULL, 100000.00, 'porcentaje', 20.0000, 20000.00, 120000.00, 'mensual', 1, 120000.00, '2026-03-30', '2026-04-29', 4, 'efectivo', 120000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-30 08:49:28', '2026-03-30 08:49:28'),
(89, 2, 9, NULL, 280000.00, 'porcentaje', 20.0000, 56000.00, 336000.00, 'mensual', 1, 336000.00, '2026-03-30', '2026-04-29', 1, 'efectivo', 336000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-30 14:57:57', '2026-03-30 14:57:57'),
(90, 1, 22, NULL, 200000.00, 'porcentaje', 15.0000, 30000.00, 230000.00, 'mensual', 1, 230000.00, '2026-03-31', '2026-04-30', 4, 'efectivo', 230000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-31 11:25:47', '2026-03-31 11:25:47'),
(91, 2, 8, NULL, 364000.00, 'valor_fijo', 72000.0000, 72000.00, 436000.00, 'quincenal', 2, 218000.00, '2026-03-31', '2026-04-30', 1, 'efectivo', 436000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, '2026-03-31 16:19:10', 1, '[{\"fecha\":\"2026-03-31 16:19:10\",\"usuario\":1,\"cambios\":{\"monto_prestado\":[\"364000.00\",364000],\"interes_valor\":[\"10.0000\",72000],\"num_cuotas\":[1,2],\"frecuencia_pago\":[\"quincenal\",\"quincenal\"]}}]', 1, 0, '2026-03-31 11:50:39', '2026-03-31 16:19:10'),
(92, 2, 5, NULL, 1500000.00, 'valor_fijo', 630000.0000, 630000.00, 2130000.00, 'quincenal', 6, 355000.00, '2026-03-31', '2026-06-29', 1, 'efectivo', 2130000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-31 16:18:12', '2026-03-31 21:34:11'),
(93, 1, 10, NULL, 200000.00, 'porcentaje', 10.0000, 20000.00, 220000.00, 'quincenal', 1, 220000.00, '2026-04-03', '2026-04-18', 4, 'efectivo', 220000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-04-03 13:05:25', '2026-04-03 13:05:25'),
(94, 1, 19, NULL, 400000.00, 'valor_fijo', 110000.0000, 110000.00, 510000.00, 'semanal', 6, 85000.00, '2026-03-29', '2026-05-10', 4, 'efectivo', 510000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-04-03 13:09:14', '2026-04-03 13:09:14'),
(95, 1, 28, NULL, 500000.00, 'porcentaje', 40.0000, 200000.00, 700000.00, 'quincenal', 5, 140000.00, '2026-04-01', '2026-06-15', 4, 'efectivo', 700000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-04-03 13:25:14', '2026-04-03 13:25:14'),
(96, 1, 40, NULL, 600000.00, 'valor_fijo', 800000.0000, 800000.00, 1400000.00, 'quincenal', 10, 140000.00, '2026-04-01', '2026-08-29', 4, 'efectivo', 1400000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', 5.00, 30000.00, NULL, NULL, '2026-04-06 23:14:44', 1, '[{\"fecha\":\"2026-04-06 23:14:44\",\"usuario\":1,\"cambios\":{\"monto_prestado\":[\"600000.00\",600000],\"interes_valor\":[\"1400000.0000\",800000],\"num_cuotas\":[10,10],\"frecuencia_pago\":[\"quincenal\",\"quincenal\"]}}]', 1, 0, '2026-04-06 23:13:18', '2026-04-09 14:01:30'),
(97, 1, 41, NULL, 500000.00, 'porcentaje', 40.0000, 200000.00, 700000.00, 'semanal', 10, 70000.00, '2026-04-07', '2026-06-16', 4, 'efectivo', 700000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', 10.00, 50000.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-04-07 22:19:23', '2026-04-09 14:02:00'),
(98, 1, 44, NULL, 200000.00, 'porcentaje', 20.0000, 40000.00, 240000.00, 'diario', 24, 10000.00, '2026-04-09', '2026-05-03', 4, 'efectivo', 240000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', 20.00, 40000.00, NULL, NULL, NULL, NULL, NULL, 1, 1, '2026-04-09 11:04:22', '2026-04-09 19:45:09'),
(99, 1, 44, NULL, 200000.00, 'porcentaje', 20.0000, 40000.00, 240000.00, 'diario', 24, 10000.00, '2026-04-09', '2026-05-03', 4, 'efectivo', 240000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', 20.00, 40000.00, NULL, NULL, NULL, NULL, NULL, 1, 1, '2026-04-09 11:08:20', '2026-04-09 19:45:09'),
(100, 1, 44, NULL, 150000.00, 'porcentaje', 20.0000, 30000.00, 180000.00, 'mensual', 1, 180000.00, '2026-04-09', '2026-05-09', 4, 'efectivo', 180000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', 5.00, 7500.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-04-09 11:17:47', '2026-04-09 19:45:09'),
(101, 1, 44, NULL, 150000.00, 'porcentaje', 20.0000, 30000.00, 180000.00, 'mensual', 1, 180000.00, '2026-04-09', '2026-05-09', 4, 'efectivo', 180000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', 20.00, 30000.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-04-09 11:22:32', '2026-04-09 19:45:09'),
(102, 1, 18, NULL, 100000.00, 'porcentaje', 20.0000, 20000.00, 120000.00, 'mensual', 1, 120000.00, '2026-04-09', '2026-05-09', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', 20.00, 20000.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-04-09 14:41:42', '2026-04-09 19:45:09'),
(103, 1, 3, NULL, 25000.00, 'porcentaje', 20.0000, 5000.00, 30000.00, 'semanal', 4, 7500.00, '2026-04-09', '2026-05-07', NULL, 'efectivo', 22500.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', 20.00, 5000.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-04-09 16:15:34', '2026-04-09 19:45:09'),
(104, 1, 46, NULL, 520000.00, 'porcentaje', 20.0000, 104000.00, 624000.00, 'semanal', 4, 156000.00, '2026-04-09', '2026-05-07', NULL, 'efectivo', 624000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', 5.00, 26000.00, NULL, NULL, NULL, NULL, NULL, 2, 0, '2026-04-09 19:56:12', '2026-04-09 19:56:12'),
(105, 1, 27, NULL, 20000.00, 'porcentaje', 20.0000, 4000.00, 24000.00, 'mensual', 1, 24000.00, '2026-04-10', '2026-05-10', NULL, 'efectivo', 24000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', 5.00, 1000.00, NULL, NULL, NULL, NULL, NULL, 2, 0, '2026-04-10 13:13:29', '2026-04-10 13:13:29'),
(106, 1, 27, NULL, 26000.00, 'porcentaje', 20.0000, 5200.00, 31200.00, 'mensual', 1, 31200.00, '2026-04-10', '2026-05-10', NULL, 'efectivo', 31200.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', 5.00, 1300.00, NULL, NULL, NULL, NULL, NULL, 2, 0, '2026-04-10 13:17:27', '2026-04-10 13:17:27'),
(107, 3, 47, NULL, 500000.00, 'porcentaje', 35.0000, 175000.00, 675000.00, 'diario', 45, 15000.00, '2026-04-10', '2026-05-25', NULL, 'efectivo', 510000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', 10.00, 50000.00, NULL, NULL, NULL, NULL, NULL, 2, 1, '2026-04-10 15:31:16', '2026-04-11 08:10:02'),
(108, 3, 48, NULL, 500000.00, 'porcentaje', 36.0000, 180000.00, 680000.00, 'semanal', 8, 85000.00, '2026-04-10', '2026-06-05', NULL, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', 10.00, 50000.00, NULL, NULL, NULL, NULL, NULL, 2, 0, '2026-04-10 15:32:37', '2026-04-11 14:38:27'),
(109, 3, 49, NULL, 200000.00, 'porcentaje', 20.0000, 40000.00, 240000.00, 'diario', 24, 10000.00, '2026-04-10', '2026-05-04', NULL, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', 10.00, 20000.00, NULL, NULL, NULL, NULL, NULL, 2, 1, '2026-04-10 15:33:43', '2026-04-11 14:39:00'),
(110, 3, 49, NULL, 10000.00, 'porcentaje', 20.0000, 2000.00, 12000.00, 'diario', 1, 12000.00, '2026-04-10', '2026-04-11', NULL, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', 10.00, 1000.00, NULL, NULL, NULL, NULL, NULL, 2, 0, '2026-04-10 16:52:07', '2026-04-11 14:36:59'),
(111, 1, 1, NULL, 100000.00, 'porcentaje', 20.0000, 20000.00, 120000.00, 'mensual', 1, 120000.00, '2026-04-11', '2026-05-11', NULL, 'efectivo', 120000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, NULL, 5.00, 5000.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-04-11 09:43:06', '2026-04-11 09:43:06'),
(112, 1, 3, NULL, 125000.00, 'porcentaje', 20.0000, 25000.00, 150000.00, 'mensual', 1, 150000.00, '2026-04-11', '2026-05-11', NULL, 'efectivo', 150000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', 5.00, 6250.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-04-11 10:23:42', '2026-04-11 10:23:42'),
(113, 1, 47, NULL, 750000.00, 'porcentaje', 20.0000, 150000.00, 900000.00, 'semanal', 4, 225000.00, '2026-04-11', '2026-05-09', NULL, 'efectivo', 900000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', 5.00, 37500.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-04-11 11:42:59', '2026-04-11 11:42:59'),
(114, 3, 47, NULL, 950000.00, 'porcentaje', 20.0000, 190000.00, 1140000.00, 'diario', 30, 38000.00, '2026-04-11', '2026-05-11', NULL, 'efectivo', 1140000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', 10.00, 95000.00, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-04-11 11:46:23', '2026-04-11 11:46:23'),
(115, 3, 48, NULL, 100000.00, 'porcentaje', 20.0000, 20000.00, 120000.00, 'mensual', 1, 120000.00, '2026-04-11', '2026-05-11', NULL, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', 10.00, 10000.00, NULL, NULL, NULL, NULL, NULL, 2, 0, '2026-04-11 14:39:58', '2026-04-11 14:40:37'),
(116, 3, 49, NULL, 100000.00, 'porcentaje', 20.0000, 20000.00, 120000.00, 'semanal', 2, 60000.00, '2026-04-11', '2026-04-25', NULL, 'efectivo', 0.00, 0, 'renovado', NULL, NULL, NULL, 'nuevo', NULL, '', 10.00, 10000.00, NULL, NULL, NULL, NULL, NULL, 2, 0, '2026-04-11 14:41:40', '2026-04-11 14:46:29'),
(117, 3, 49, NULL, 100000.00, 'porcentaje', 20.0000, 20000.00, 120000.00, 'semanal', 2, 60000.00, '2026-04-11', '2026-04-25', NULL, 'efectivo', 120000.00, 0, 'activo', NULL, NULL, NULL, 'refinanciacion', 116, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 2, 0, '2026-04-11 14:46:29', '2026-04-11 14:46:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_salida`
--

CREATE TABLE `tipos_salida` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_salida`
--

INSERT INTO `tipos_salida` (`id`, `nombre`, `descripcion`, `activo`, `created_at`) VALUES
(1, 'Gasto operativo', NULL, 1, '2026-04-11 21:35:00'),
(2, 'Retiro socio', NULL, 1, '2026-04-11 21:35:00'),
(3, 'Transporte', NULL, 1, '2026-04-11 21:35:00'),
(4, 'Alimentación', NULL, 1, '2026-04-11 21:35:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('superadmin','admin','cobrador','consulta') NOT NULL DEFAULT 'cobrador',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `ultimo_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `session_token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password_hash`, `rol`, `activo`, `ultimo_login`, `created_at`, `updated_at`, `session_token`) VALUES
(1, 'ADMIN', 'admin@gmail.com', '$2y$10$wtzGAguljZQ9isHD2qFJI.5rIJ1rliVmV5BstCFU/OCDN8l/mLTBq', 'superadmin', 1, '2026-04-09 10:14:06', '2026-03-05 10:59:15', '2026-04-09 10:14:06', NULL),
(2, 'COBRADOR 1', 'cobrador@gmail.com', '$2y$10$UF8mbiYldAFa8/fweIrB3uWTc9Q6/V0mvnG94yYbrjqyzihFfy.Fm', 'cobrador', 1, '2026-04-11 11:43:22', '2026-03-05 20:33:52', '2026-04-11 11:43:22', '8e885bc14b69cc6036c5f46ffb2e3d8a0c88c5adee5de21e5f4f58642d3c853f'),
(3, 'Isa', 'isa@gmail.com', '$2y$10$OIvL9BHWX8TryhI8nvjjh.Q4eunFfrdwW54kxucCOlIuf9OeAcMQ2', 'admin', 1, '2026-03-09 11:49:07', '2026-03-05 20:34:21', '2026-03-09 14:54:24', NULL),
(4, 'COBRADOR 2', 'cobrador2@gmail.com', '$2y$10$6U2R1bNZUPUL.pb7H.9GZ.UCl5j.71P72DQ3F/KLVI6XCLFKGhKSG', 'cobrador', 0, '2026-04-10 14:44:41', '2026-03-09 11:42:34', '2026-04-10 14:44:45', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_cobro`
--

CREATE TABLE `usuario_cobro` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `cobro_id` int(11) NOT NULL,
  `puede_ver` tinyint(1) NOT NULL DEFAULT 1,
  `puede_crear` tinyint(1) NOT NULL DEFAULT 0,
  `puede_editar` tinyint(1) NOT NULL DEFAULT 0,
  `puede_eliminar` tinyint(1) NOT NULL DEFAULT 0,
  `puede_ver_capital` tinyint(1) NOT NULL DEFAULT 0,
  `puede_ver_cuentas` tinyint(1) NOT NULL DEFAULT 0,
  `puede_ver_salidas` tinyint(1) NOT NULL DEFAULT 0,
  `puede_ver_movimientos` tinyint(1) NOT NULL DEFAULT 0,
  `puede_ver_proyeccion` tinyint(1) NOT NULL DEFAULT 1,
  `puede_ver_reportes` tinyint(1) NOT NULL DEFAULT 1,
  `puede_ver_configuracion` tinyint(1) NOT NULL DEFAULT 0,
  `puede_ver_usuarios` tinyint(1) NOT NULL DEFAULT 0,
  `puede_ver_cobros` tinyint(1) NOT NULL DEFAULT 0,
  `puede_crear_deudor` tinyint(1) NOT NULL DEFAULT 0,
  `puede_editar_deudor` tinyint(1) NOT NULL DEFAULT 0,
  `puede_eliminar_deudor` tinyint(1) NOT NULL DEFAULT 0,
  `puede_crear_prestamo` tinyint(1) NOT NULL DEFAULT 0,
  `puede_editar_prestamo` tinyint(1) NOT NULL DEFAULT 0,
  `puede_anular_prestamo` tinyint(1) NOT NULL DEFAULT 0,
  `puede_anular_pago` tinyint(1) NOT NULL DEFAULT 0,
  `puede_crear_capitalista` tinyint(1) NOT NULL DEFAULT 0,
  `puede_editar_capitalista` tinyint(1) NOT NULL DEFAULT 0,
  `puede_registrar_movimiento_capital` tinyint(1) NOT NULL DEFAULT 0,
  `puede_ver_historial_capitalista` tinyint(1) NOT NULL DEFAULT 0,
  `puede_crear_cuenta` tinyint(1) NOT NULL DEFAULT 0,
  `puede_editar_cuenta` tinyint(1) NOT NULL DEFAULT 0,
  `puede_crear_salida` tinyint(1) NOT NULL DEFAULT 0,
  `puede_eliminar_salida` tinyint(1) NOT NULL DEFAULT 0,
  `puede_exportar` tinyint(1) NOT NULL DEFAULT 0,
  `puede_registrar_pago` tinyint(1) NOT NULL DEFAULT 1,
  `puede_ver_dashboard` tinyint(1) NOT NULL DEFAULT 1,
  `puede_ver_deudores` tinyint(1) NOT NULL DEFAULT 1,
  `puede_ver_prestamos` tinyint(1) NOT NULL DEFAULT 1,
  `puede_ver_pagos` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario_cobro`
--

INSERT INTO `usuario_cobro` (`id`, `usuario_id`, `cobro_id`, `puede_ver`, `puede_crear`, `puede_editar`, `puede_eliminar`, `puede_ver_capital`, `puede_ver_cuentas`, `puede_ver_salidas`, `puede_ver_movimientos`, `puede_ver_proyeccion`, `puede_ver_reportes`, `puede_ver_configuracion`, `puede_ver_usuarios`, `puede_ver_cobros`, `puede_crear_deudor`, `puede_editar_deudor`, `puede_eliminar_deudor`, `puede_crear_prestamo`, `puede_editar_prestamo`, `puede_anular_prestamo`, `puede_anular_pago`, `puede_crear_capitalista`, `puede_editar_capitalista`, `puede_registrar_movimiento_capital`, `puede_ver_historial_capitalista`, `puede_crear_cuenta`, `puede_editar_cuenta`, `puede_crear_salida`, `puede_eliminar_salida`, `puede_exportar`, `puede_registrar_pago`, `puede_ver_dashboard`, `puede_ver_deudores`, `puede_ver_prestamos`, `puede_ver_pagos`, `created_at`) VALUES
(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-03-05 10:59:16'),
(3, 3, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, '2026-03-05 20:34:21'),
(4, 1, 2, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-03-05 21:30:40'),
(8, 1, 3, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-04-10 10:32:40'),
(13, 4, 3, 1, 0, 0, 0, 0, 0, 1, 1, 1, 1, 0, 0, 0, 1, 1, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 1, 1, 1, 1, 1, '2026-04-10 14:43:13'),
(14, 2, 3, 1, 0, 0, 0, 0, 0, 1, 1, 1, 1, 0, 0, 0, 1, 1, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 1, 1, 1, 1, 1, '2026-04-10 15:27:54');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_cartera_cobro`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_cartera_cobro` (
`cobro_id` int(11)
,`total_prestamos` bigint(21)
,`total_prestado` decimal(37,2)
,`total_por_cobrar` decimal(37,2)
,`prestamos_activos` decimal(22,0)
,`prestamos_en_mora` decimal(22,0)
,`prestamos_en_acuerdo` decimal(22,0)
,`prestamos_pagados` decimal(22,0)
,`prestamos_incobrables` decimal(22,0)
,`cartera_riesgo` decimal(37,2)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_cuotas_hoy`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_cuotas_hoy` (
`cuota_id` int(11)
,`cobro_id` int(11)
,`prestamo_id` int(11)
,`numero_cuota` int(11)
,`fecha_vencimiento` date
,`monto_cuota` decimal(15,2)
,`saldo_cuota` decimal(15,2)
,`estado_cuota` enum('pendiente','parcial','pagado','vencido','anulado')
,`estado_prestamo` enum('activo','en_mora','en_acuerdo','renovado','refinanciado','pagado','incobrable','anulado')
,`dias_mora` int(11)
,`deudor_id` int(11)
,`deudor_nombre` varchar(150)
,`deudor_telefono` varchar(20)
,`deudor_barrio` varchar(100)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_saldo_capitalistas`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_saldo_capitalistas` (
`capitalista_id` int(11)
,`cobro_id` int(11)
,`nombre` varchar(100)
,`tipo` enum('propio','prestado')
,`tasa_redito` decimal(8,4)
,`tipo_redito` enum('porcentaje','valor_fijo')
,`frecuencia_redito` enum('mensual','quincenal','libre')
,`estado` enum('activo','liquidado')
,`color` varchar(7)
,`saldo_actual` decimal(37,2)
,`total_aportado` decimal(37,2)
,`total_retirado` decimal(37,2)
,`total_reditos_pagados` decimal(37,2)
,`total_retiros_utilidades` decimal(37,2)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_saldo_cuentas`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_saldo_cuentas` (
`cuenta_id` int(11)
,`cobro_id` int(11)
,`nombre` varchar(100)
,`tipo` enum('efectivo','nequi','bancolombia','daviplata','transfiya','otro')
,`total_entradas` decimal(37,2)
,`total_salidas` decimal(37,2)
,`saldo_actual` decimal(37,2)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_cartera_cobro`
--
DROP TABLE IF EXISTS `v_cartera_cobro`;

CREATE ALGORITHM=UNDEFINED  SQL SECURITY DEFINER VIEW `v_cartera_cobro`  AS SELECT `prestamos`.`cobro_id` AS `cobro_id`, count(0) AS `total_prestamos`, sum(`prestamos`.`monto_prestado`) AS `total_prestado`, sum(`prestamos`.`saldo_pendiente`) AS `total_por_cobrar`, sum(case when `prestamos`.`estado` = 'activo' then 1 else 0 end) AS `prestamos_activos`, sum(case when `prestamos`.`estado` = 'en_mora' then 1 else 0 end) AS `prestamos_en_mora`, sum(case when `prestamos`.`estado` = 'en_acuerdo' then 1 else 0 end) AS `prestamos_en_acuerdo`, sum(case when `prestamos`.`estado` = 'pagado' then 1 else 0 end) AS `prestamos_pagados`, sum(case when `prestamos`.`estado` = 'incobrable' then 1 else 0 end) AS `prestamos_incobrables`, sum(case when `prestamos`.`estado` in ('en_mora','incobrable') then `prestamos`.`saldo_pendiente` else 0 end) AS `cartera_riesgo` FROM `prestamos` WHERE `prestamos`.`estado` not in ('renovado','refinanciado','anulado') GROUP BY `prestamos`.`cobro_id` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_cuotas_hoy`
--
DROP TABLE IF EXISTS `v_cuotas_hoy`;

CREATE ALGORITHM=UNDEFINED  SQL SECURITY DEFINER VIEW `v_cuotas_hoy`  AS SELECT `cu`.`id` AS `cuota_id`, `cu`.`cobro_id` AS `cobro_id`, `cu`.`prestamo_id` AS `prestamo_id`, `cu`.`numero_cuota` AS `numero_cuota`, `cu`.`fecha_vencimiento` AS `fecha_vencimiento`, `cu`.`monto_cuota` AS `monto_cuota`, `cu`.`saldo_cuota` AS `saldo_cuota`, `cu`.`estado` AS `estado_cuota`, `p`.`estado` AS `estado_prestamo`, `p`.`dias_mora` AS `dias_mora`, `d`.`id` AS `deudor_id`, `d`.`nombre` AS `deudor_nombre`, `d`.`telefono` AS `deudor_telefono`, `d`.`barrio` AS `deudor_barrio` FROM ((`cuotas` `cu` join `prestamos` `p` on(`p`.`id` = `cu`.`prestamo_id`)) join `deudores` `d` on(`d`.`id` = `p`.`deudor_id`)) WHERE `cu`.`estado` in ('pendiente','parcial','vencido') AND `cu`.`fecha_vencimiento` <= curdate() AND `p`.`estado` <> 'anulado' ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_saldo_capitalistas`
--
DROP TABLE IF EXISTS `v_saldo_capitalistas`;

CREATE ALGORITHM=UNDEFINED  SQL SECURITY DEFINER VIEW `v_saldo_capitalistas`  AS SELECT `cap`.`id` AS `capitalista_id`, `cap`.`cobro_id` AS `cobro_id`, `cap`.`nombre` AS `nombre`, `cap`.`tipo` AS `tipo`, `cap`.`tasa_redito` AS `tasa_redito`, `cap`.`tipo_redito` AS `tipo_redito`, `cap`.`frecuencia_redito` AS `frecuencia_redito`, `cap`.`estado` AS `estado`, `cap`.`color` AS `color`, coalesce(sum(case when `cm`.`anulado` = 0 and `cm`.`tipo` = 'ingreso_capital' then `cm`.`monto` when `cm`.`anulado` = 0 and `cm`.`tipo` in ('retiro_capital','retiro','redito') then -`cm`.`monto` else 0 end),0) AS `saldo_actual`, coalesce(sum(case when `cm`.`anulado` = 0 and `cm`.`tipo` = 'ingreso_capital' then `cm`.`monto` else 0 end),0) AS `total_aportado`, coalesce(sum(case when `cm`.`anulado` = 0 and `cm`.`tipo` = 'retiro_capital' then `cm`.`monto` else 0 end),0) AS `total_retirado`, coalesce(sum(case when `cm`.`anulado` = 0 and `cm`.`tipo` = 'redito' then `cm`.`monto` else 0 end),0) AS `total_reditos_pagados`, coalesce(sum(case when `cm`.`anulado` = 0 and `cm`.`tipo` = 'retiro' then `cm`.`monto` else 0 end),0) AS `total_retiros_utilidades` FROM (`capitalistas` `cap` left join `capital_movimientos` `cm` on(`cm`.`capitalista_id` = `cap`.`id`)) GROUP BY `cap`.`id`, `cap`.`cobro_id`, `cap`.`nombre`, `cap`.`tipo`, `cap`.`tasa_redito`, `cap`.`tipo_redito`, `cap`.`frecuencia_redito`, `cap`.`estado`, `cap`.`color` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_saldo_cuentas`
--
DROP TABLE IF EXISTS `v_saldo_cuentas`;

CREATE ALGORITHM=UNDEFINED  SQL SECURITY DEFINER VIEW `v_saldo_cuentas`  AS SELECT `c`.`id` AS `cuenta_id`, `c`.`cobro_id` AS `cobro_id`, `c`.`nombre` AS `nombre`, `c`.`tipo` AS `tipo`, coalesce(sum(case when `cm`.`es_entrada` = 1 and `cm`.`anulado` = 0 then `cm`.`monto` else 0 end),0) AS `total_entradas`, coalesce(sum(case when `cm`.`es_entrada` = 0 and `cm`.`anulado` = 0 then `cm`.`monto` else 0 end),0) AS `total_salidas`, coalesce(sum(case when `cm`.`anulado` = 0 then case when `cm`.`es_entrada` = 1 then `cm`.`monto` else -`cm`.`monto` end else 0 end),0) AS `saldo_actual` FROM (`cuentas` `c` left join `capital_movimientos` `cm` on(`cm`.`cuenta_id` = `c`.`id` and `cm`.`tipo` not in ('prestamo_proporcional','cobro_proporcional'))) WHERE `c`.`activa` = 1 GROUP BY `c`.`id`, `c`.`cobro_id`, `c`.`nombre`, `c`.`tipo` ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alertas_admin`
--
ALTER TABLE `alertas_admin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cobro` (`cobro_id`),
  ADD KEY `idx_leida` (`leida`);

--
-- Indices de la tabla `capitalistas`
--
ALTER TABLE `capitalistas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cap_cobro` (`cobro_id`);

--
-- Indices de la tabla `capital_movimientos`
--
ALTER TABLE `capital_movimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cobro` (`cobro_id`),
  ADD KEY `idx_cuenta` (`cuenta_id`),
  ADD KEY `idx_cap` (`capitalista_id`),
  ADD KEY `idx_prestamo` (`prestamo_id`);

--
-- Indices de la tabla `categorias_gasto`
--
ALTER TABLE `categorias_gasto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cobro` (`cobro_id`);

--
-- Indices de la tabla `cobros`
--
ALTER TABLE `cobros`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cuentas`
--
ALTER TABLE `cuentas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cuenta_cobro` (`cobro_id`);

--
-- Indices de la tabla `cuotas`
--
ALTER TABLE `cuotas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cuotas_estado_fecha` (`estado`,`fecha_vencimiento`),
  ADD KEY `idx_cuotas_prestamo` (`prestamo_id`),
  ADD KEY `fk_cuota_cobro` (`cobro_id`);

--
-- Indices de la tabla `deudores`
--
ALTER TABLE `deudores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_deudor_cobro` (`cobro_id`);

--
-- Indices de la tabla `deudor_cobro`
--
ALTER TABLE `deudor_cobro`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_deudor_cobro` (`deudor_id`,`cobro_id`),
  ADD KEY `fk_dc_deudor` (`deudor_id`),
  ADD KEY `fk_dc_cobro` (`cobro_id`);

--
-- Indices de la tabla `gastos_cobrador`
--
ALTER TABLE `gastos_cobrador`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cobro_fecha` (`cobro_id`,`fecha`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `gestiones_cobro`
--
ALTER TABLE `gestiones_cobro`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_gc_cobro` (`cobro_id`),
  ADD KEY `fk_gc_deudor` (`deudor_id`),
  ADD KEY `fk_gc_usuario` (`usuario_id`),
  ADD KEY `idx_gc_prestamo_fecha` (`prestamo_id`,`fecha_gestion`);

--
-- Indices de la tabla `liquidaciones`
--
ALTER TABLE `liquidaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cobro_fecha` (`cobro_id`,`fecha`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `liquidacion_entregas`
--
ALTER TABLE `liquidacion_entregas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pagos_fecha` (`cobro_id`,`fecha_pago`),
  ADD KEY `fk_pago_prestamo` (`prestamo_id`),
  ADD KEY `fk_pago_cuota` (`cuota_id`),
  ADD KEY `fk_pago_deudor` (`deudor_id`),
  ADD KEY `fk_pago_cuenta` (`cuenta_id`),
  ADD KEY `fk_pago_usuario` (`usuario_id`);

--
-- Indices de la tabla `papeleria`
--
ALTER TABLE `papeleria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cobro_fecha` (`cobro_id`,`fecha`),
  ADD KEY `idx_prestamo` (`prestamo_id`);

--
-- Indices de la tabla `papeleria_categorias`
--
ALTER TABLE `papeleria_categorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cobro` (`cobro_id`);

--
-- Indices de la tabla `papeleria_liquidaciones`
--
ALTER TABLE `papeleria_liquidaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cobro` (`cobro_id`);

--
-- Indices de la tabla `papeleria_salidas`
--
ALTER TABLE `papeleria_salidas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cobro_fecha` (`cobro_id`,`fecha`);

--
-- Indices de la tabla `prestamos`
--
ALTER TABLE `prestamos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_prestamos_cobro_estado` (`cobro_id`,`estado`),
  ADD KEY `idx_prestamos_deudor` (`deudor_id`),
  ADD KEY `fk_prest_capitalista` (`capitalista_id`),
  ADD KEY `fk_prest_cuenta` (`cuenta_desembolso_id`),
  ADD KEY `fk_prest_padre` (`prestamo_padre_id`),
  ADD KEY `fk_prest_usuario` (`usuario_id`);

--
-- Indices de la tabla `tipos_salida`
--
ALTER TABLE `tipos_salida`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_email` (`email`);

--
-- Indices de la tabla `usuario_cobro`
--
ALTER TABLE `usuario_cobro`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_usuario_cobro` (`usuario_id`,`cobro_id`),
  ADD KEY `fk_uc_cobro` (`cobro_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alertas_admin`
--
ALTER TABLE `alertas_admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `capitalistas`
--
ALTER TABLE `capitalistas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `capital_movimientos`
--
ALTER TABLE `capital_movimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=582;

--
-- AUTO_INCREMENT de la tabla `categorias_gasto`
--
ALTER TABLE `categorias_gasto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `cobros`
--
ALTER TABLE `cobros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `cuentas`
--
ALTER TABLE `cuentas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `cuotas`
--
ALTER TABLE `cuotas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=814;

--
-- AUTO_INCREMENT de la tabla `deudores`
--
ALTER TABLE `deudores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `deudor_cobro`
--
ALTER TABLE `deudor_cobro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

--
-- AUTO_INCREMENT de la tabla `gastos_cobrador`
--
ALTER TABLE `gastos_cobrador`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `gestiones_cobro`
--
ALTER TABLE `gestiones_cobro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `liquidaciones`
--
ALTER TABLE `liquidaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `liquidacion_entregas`
--
ALTER TABLE `liquidacion_entregas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=286;

--
-- AUTO_INCREMENT de la tabla `papeleria`
--
ALTER TABLE `papeleria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=143;

--
-- AUTO_INCREMENT de la tabla `papeleria_categorias`
--
ALTER TABLE `papeleria_categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `papeleria_liquidaciones`
--
ALTER TABLE `papeleria_liquidaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `papeleria_salidas`
--
ALTER TABLE `papeleria_salidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `prestamos`
--
ALTER TABLE `prestamos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT de la tabla `tipos_salida`
--
ALTER TABLE `tipos_salida`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuario_cobro`
--
ALTER TABLE `usuario_cobro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `capitalistas`
--
ALTER TABLE `capitalistas`
  ADD CONSTRAINT `fk_cap_cobro` FOREIGN KEY (`cobro_id`) REFERENCES `cobros` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `capital_movimientos`
--
ALTER TABLE `capital_movimientos`
  ADD CONSTRAINT `capital_movimientos_ibfk_1` FOREIGN KEY (`cobro_id`) REFERENCES `cobros` (`id`),
  ADD CONSTRAINT `capital_movimientos_ibfk_3` FOREIGN KEY (`capitalista_id`) REFERENCES `capitalistas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `capital_movimientos_ibfk_4` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `cuentas`
--
ALTER TABLE `cuentas`
  ADD CONSTRAINT `fk_cuenta_cobro` FOREIGN KEY (`cobro_id`) REFERENCES `cobros` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cuotas`
--
ALTER TABLE `cuotas`
  ADD CONSTRAINT `fk_cuota_cobro` FOREIGN KEY (`cobro_id`) REFERENCES `cobros` (`id`),
  ADD CONSTRAINT `fk_cuota_prestamo` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `deudores`
--
ALTER TABLE `deudores`
  ADD CONSTRAINT `fk_deudor_cobro` FOREIGN KEY (`cobro_id`) REFERENCES `cobros` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `deudor_cobro`
--
ALTER TABLE `deudor_cobro`
  ADD CONSTRAINT `fk_dc_cobro` FOREIGN KEY (`cobro_id`) REFERENCES `cobros` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dc_deudor` FOREIGN KEY (`deudor_id`) REFERENCES `deudores` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `gestiones_cobro`
--
ALTER TABLE `gestiones_cobro`
  ADD CONSTRAINT `fk_gc_cobro` FOREIGN KEY (`cobro_id`) REFERENCES `cobros` (`id`),
  ADD CONSTRAINT `fk_gc_deudor` FOREIGN KEY (`deudor_id`) REFERENCES `deudores` (`id`),
  ADD CONSTRAINT `fk_gc_prestamo` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gc_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pago_cobro` FOREIGN KEY (`cobro_id`) REFERENCES `cobros` (`id`),
  ADD CONSTRAINT `fk_pago_cuota` FOREIGN KEY (`cuota_id`) REFERENCES `cuotas` (`id`),
  ADD CONSTRAINT `fk_pago_deudor` FOREIGN KEY (`deudor_id`) REFERENCES `deudores` (`id`),
  ADD CONSTRAINT `fk_pago_prestamo` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`),
  ADD CONSTRAINT `fk_pago_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `prestamos`
--
ALTER TABLE `prestamos`
  ADD CONSTRAINT `fk_prest_capitalista` FOREIGN KEY (`capitalista_id`) REFERENCES `capitalistas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_prest_cobro` FOREIGN KEY (`cobro_id`) REFERENCES `cobros` (`id`),
  ADD CONSTRAINT `fk_prest_cuenta` FOREIGN KEY (`cuenta_desembolso_id`) REFERENCES `cuentas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_prest_deudor` FOREIGN KEY (`deudor_id`) REFERENCES `deudores` (`id`),
  ADD CONSTRAINT `fk_prest_padre` FOREIGN KEY (`prestamo_padre_id`) REFERENCES `prestamos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_prest_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `usuario_cobro`
--
ALTER TABLE `usuario_cobro`
  ADD CONSTRAINT `fk_uc_cobro` FOREIGN KEY (`cobro_id`) REFERENCES `cobros` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_uc_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
