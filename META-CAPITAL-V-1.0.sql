-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 09-03-2026 a las 20:26:20
-- Versión del servidor: 11.8.3-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u504403321_capital`
--

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
(6, 1, 'UTILIDAD EXCEL', NULL, '#7c6aff', 'propio', 0.00, 'porcentaje', 0.0000, 'mensual', '2026-03-06', 'activo', '2026-03-06 23:12:15', '2026-03-06 23:12:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `capital_movimientos`
--

CREATE TABLE `capital_movimientos` (
  `id` int(11) NOT NULL,
  `cobro_id` int(11) NOT NULL,
  `tipo` enum('abono','retiro','liquidacion','redito_causado','redito_pagado','prestamo_salida','cobro_entrada','ingreso_capital','retiro_capital','prestamo_proporcional','cobro_proporcional','cobro_cuota','salida','redito') NOT NULL,
  `es_entrada` tinyint(1) NOT NULL,
  `monto` decimal(15,2) NOT NULL,
  `cuenta_id` int(11) NOT NULL,
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

INSERT INTO `capital_movimientos` (`id`, `cobro_id`, `tipo`, `es_entrada`, `monto`, `cuenta_id`, `capitalista_id`, `prestamo_id`, `pago_id`, `descripcion`, `anulado`, `anulado_at`, `anulado_por`, `fecha`, `usuario_id`, `created_at`) VALUES
(1, 2, 'ingreso_capital', 1, 2000000.00, 1, 3, NULL, NULL, NULL, 0, NULL, NULL, '2026-03-06', 1, '2026-03-06 20:01:34'),
(2, 2, 'ingreso_capital', 1, 271000.00, 1, 2, NULL, NULL, NULL, 0, NULL, NULL, '2026-03-06', 1, '2026-03-06 20:02:23'),
(3, 2, 'ingreso_capital', 1, 6579000.00, 1, 1, NULL, NULL, NULL, 0, NULL, NULL, '2026-03-06', 1, '2026-03-06 20:02:52'),
(4, 2, 'salida', 0, 600000.00, 1, NULL, 1, NULL, 'Préstamo #1 editado — nuevo monto', 0, NULL, NULL, '2026-02-15', 1, '2026-03-06 20:03:41'),
(6, 2, 'prestamo_proporcional', 0, 600000.00, 1, 1, 1, NULL, 'Préstamo #1 — descuento capital propio', 0, NULL, NULL, '2026-02-15', 1, '2026-03-06 20:04:18'),
(7, 2, 'salida', 0, 600000.00, 1, NULL, 2, NULL, 'Préstamo #2 editado — nuevo monto', 0, NULL, NULL, '2026-03-06', 1, '2026-03-06 20:05:10'),
(10, 2, 'prestamo_proporcional', 0, 600000.00, 1, 1, 2, NULL, 'Préstamo #2 — descuento capital propio', 0, NULL, NULL, '2026-02-20', 1, '2026-03-06 20:05:52'),
(11, 2, 'salida', 0, 1000000.00, 1, NULL, 3, NULL, 'Préstamo #3 a deudor #5', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:07:01'),
(12, 2, 'prestamo_proporcional', 0, 1000000.00, 1, 1, 3, NULL, 'Préstamo #3 — descuento capital propio', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:07:01'),
(13, 2, 'salida', 0, 470000.00, 1, NULL, 4, NULL, 'Préstamo #4 a deudor #8', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:19:34'),
(14, 2, 'prestamo_proporcional', 0, 470000.00, 1, 1, 4, NULL, 'Préstamo #4 — descuento capital propio', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:19:34'),
(15, 2, 'salida', 0, 400000.00, 1, NULL, 5, NULL, 'Préstamo #5 a deudor #9', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:19:56'),
(16, 2, 'prestamo_proporcional', 0, 400000.00, 1, 1, 5, NULL, 'Préstamo #5 — descuento capital propio', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:19:56'),
(17, 2, 'salida', 0, 600000.00, 1, NULL, 6, NULL, 'Préstamo #6 a deudor #10', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:21:18'),
(18, 2, 'prestamo_proporcional', 0, 600000.00, 1, 1, 6, NULL, 'Préstamo #6 — descuento capital propio', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:21:18'),
(19, 2, 'salida', 0, 500000.00, 1, NULL, 7, NULL, 'Préstamo #7 a deudor #11', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:21:59'),
(20, 2, 'prestamo_proporcional', 0, 500000.00, 1, 1, 7, NULL, 'Préstamo #7 — descuento capital propio', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:21:59'),
(21, 2, 'salida', 0, 800000.00, 1, NULL, 8, NULL, 'Préstamo #8 a deudor #12', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:22:49'),
(22, 2, 'prestamo_proporcional', 0, 800000.00, 1, 1, 8, NULL, 'Préstamo #8 — descuento capital propio', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:22:49'),
(23, 2, 'salida', 0, 1000000.00, 1, NULL, 9, NULL, 'Préstamo #9 a deudor #13', 0, NULL, NULL, '2026-02-22', 1, '2026-03-06 20:23:14'),
(24, 2, 'prestamo_proporcional', 0, 1000000.00, 1, 1, 9, NULL, 'Préstamo #9 — descuento capital propio', 0, NULL, NULL, '2026-02-22', 1, '2026-03-06 20:23:14'),
(25, 2, 'salida', 0, 2700000.00, 1, NULL, 10, NULL, 'Préstamo #10 a deudor #14', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:23:45'),
(26, 2, 'prestamo_proporcional', 0, 609000.00, 1, 1, 10, NULL, 'Préstamo #10 — descuento capital propio', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:23:45'),
(27, 2, 'prestamo_proporcional', 0, 271000.00, 1, 2, 10, NULL, 'Préstamo #10 — descuento capital propio', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:23:45'),
(28, 2, 'prestamo_proporcional', 0, 1820000.00, 1, 3, 10, NULL, 'Préstamo #10 — descuento capital prestado (100%)', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:23:45'),
(29, 2, 'salida', 0, 180000.00, 1, NULL, 11, NULL, 'Préstamo #11 a deudor #15', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:24:42'),
(30, 2, 'prestamo_proporcional', 0, 180000.00, 1, 3, 11, NULL, 'Préstamo #11 — descuento capital prestado (100%)', 0, NULL, NULL, '2026-03-01', 1, '2026-03-06 20:24:42'),
(123, 1, 'ingreso_capital', 1, 4500000.00, 4, 4, NULL, NULL, NULL, 0, NULL, NULL, '2025-12-01', 1, '2026-03-07 21:02:19'),
(124, 1, 'salida', 0, 1000000.00, 4, NULL, 32, NULL, 'Préstamo #32 a deudor #5', 0, NULL, NULL, '2025-12-26', 1, '2026-03-07 21:02:26'),
(125, 1, 'prestamo_proporcional', 0, 1000000.00, 4, 4, 32, NULL, 'Préstamo #32 — descuento capital propio', 0, NULL, NULL, '2025-12-26', 1, '2026-03-07 21:02:26'),
(126, 1, 'cobro_cuota', 1, 620000.00, 4, NULL, 32, 52, 'Cobro cuota préstamo #32', 0, NULL, NULL, '2026-01-24', 1, '2026-03-07 21:02:50'),
(127, 1, 'cobro_proporcional', 1, 620000.00, 4, 4, 32, 52, 'Retorno préstamo #32 — 100% capital', 0, NULL, NULL, '2026-01-24', 1, '2026-03-07 21:02:50'),
(128, 1, 'cobro_cuota', 1, 620000.00, 4, NULL, 32, 54, 'Cobro cuota préstamo #32', 0, NULL, NULL, '2026-02-28', 1, '2026-03-07 21:16:16'),
(129, 1, 'cobro_proporcional', 1, 620000.00, 4, 4, 32, 54, 'Retorno préstamo #32 — 100% capital', 0, NULL, NULL, '2026-02-28', 1, '2026-03-07 21:16:16'),
(130, 1, 'ingreso_capital', 1, 2000000.00, 4, 5, NULL, NULL, NULL, 0, NULL, NULL, '2026-02-01', 1, '2026-03-08 18:09:37'),
(131, 1, 'ingreso_capital', 1, 580000.00, 4, 5, NULL, NULL, NULL, 0, NULL, NULL, '2026-03-06', 1, '2026-03-08 18:11:08'),
(132, 1, '', 0, 150000.00, 4, NULL, 33, NULL, 'Préstamo #33 a deudor #23', 0, NULL, NULL, '2025-12-27', 1, '2026-03-08 18:13:27'),
(133, 1, 'prestamo_proporcional', 0, 150000.00, 4, 4, 33, NULL, 'Préstamo #33 — descuento capital propio', 0, NULL, NULL, '2025-12-27', 1, '2026-03-08 18:13:27'),
(134, 1, 'cobro_cuota', 1, 180000.00, 4, NULL, 33, 56, 'Cobro cuota préstamo #33', 0, NULL, NULL, '2026-01-26', 1, '2026-03-08 18:13:49'),
(135, 1, 'cobro_proporcional', 1, 180000.00, 4, 4, 33, 56, 'Retorno préstamo #33 — 100% capital', 0, NULL, NULL, '2026-01-26', 1, '2026-03-08 18:13:49'),
(136, 1, '', 0, 150000.00, 4, NULL, 34, NULL, 'Préstamo #34 a deudor #23', 0, NULL, NULL, '2026-01-04', 1, '2026-03-08 18:18:14'),
(137, 1, 'prestamo_proporcional', 0, 150000.00, 4, 4, 34, NULL, 'Préstamo #34 — descuento capital propio', 0, NULL, NULL, '2026-01-04', 1, '2026-03-08 18:18:14'),
(138, 1, 'cobro_cuota', 1, 180000.00, 4, NULL, 34, 57, 'Cobro cuota préstamo #34', 0, NULL, NULL, '2026-02-03', 1, '2026-03-08 18:18:40'),
(139, 1, 'cobro_proporcional', 1, 180000.00, 4, 4, 34, 57, 'Retorno préstamo #34 — 100% capital', 0, NULL, NULL, '2026-02-03', 1, '2026-03-08 18:18:40'),
(140, 1, '', 0, 250000.00, 4, NULL, 35, NULL, 'Préstamo #35 a deudor #19', 0, NULL, NULL, '2026-02-01', 1, '2026-03-08 18:20:01'),
(141, 1, 'prestamo_proporcional', 0, 250000.00, 4, 4, 35, NULL, 'Préstamo #35 — descuento capital propio', 0, NULL, NULL, '2026-02-01', 1, '2026-03-08 18:20:01'),
(142, 1, '', 0, 500000.00, 4, NULL, 36, NULL, 'Préstamo #36 a deudor #27', 0, NULL, NULL, '2026-01-24', 1, '2026-03-08 18:21:31'),
(143, 1, 'prestamo_proporcional', 0, 500000.00, 4, 4, 36, NULL, 'Préstamo #36 — descuento capital propio', 0, NULL, NULL, '2026-01-24', 1, '2026-03-08 18:21:31'),
(144, 1, 'cobro_cuota', 1, 210000.00, 4, NULL, 36, 58, 'Cobro cuota préstamo #36', 0, NULL, NULL, '2026-02-07', 1, '2026-03-08 18:22:02'),
(145, 1, 'cobro_proporcional', 1, 210000.00, 4, 4, 36, 58, 'Retorno préstamo #36 — 100% capital', 0, NULL, NULL, '2026-02-07', 1, '2026-03-08 18:22:02'),
(146, 1, '', 0, 500000.00, 4, NULL, 37, NULL, 'Préstamo #37 a deudor #28', 0, NULL, NULL, '2026-01-24', 1, '2026-03-08 18:22:56'),
(147, 1, 'prestamo_proporcional', 0, 500000.00, 4, 4, 37, NULL, 'Préstamo #37 — descuento capital propio', 0, NULL, NULL, '2026-01-24', 1, '2026-03-08 18:22:56'),
(148, 1, 'cobro_cuota', 1, 350000.00, 4, NULL, 37, 61, 'Cobro cuota préstamo #37', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 18:23:29'),
(149, 1, 'cobro_proporcional', 1, 350000.00, 4, 4, 37, 61, 'Retorno préstamo #37 — 100% capital', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 18:23:29'),
(150, 1, '', 0, 500000.00, 4, NULL, 38, NULL, 'Préstamo #38 a deudor #29', 0, NULL, NULL, '2026-01-24', 1, '2026-03-08 18:24:18'),
(151, 1, 'prestamo_proporcional', 0, 500000.00, 4, 4, 38, NULL, 'Préstamo #38 — descuento capital propio', 0, NULL, NULL, '2026-01-24', 1, '2026-03-08 18:24:18'),
(152, 1, 'cobro_cuota', 1, 350000.00, 4, NULL, 38, 66, 'Cobro cuota préstamo #38', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 18:24:39'),
(153, 1, 'cobro_proporcional', 1, 350000.00, 4, 4, 38, 66, 'Retorno préstamo #38 — 100% capital', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 18:24:39'),
(154, 1, '', 0, 100000.00, 4, NULL, 39, NULL, 'Préstamo #39 a deudor #6', 0, NULL, NULL, '2026-01-24', 1, '2026-03-08 18:35:12'),
(155, 1, 'prestamo_proporcional', 0, 100000.00, 4, 4, 39, NULL, 'Préstamo #39 — descuento capital propio', 0, NULL, NULL, '2026-01-24', 1, '2026-03-08 18:35:12'),
(156, 1, 'cobro_cuota', 1, 110000.00, 4, NULL, 39, 71, 'Cobro cuota préstamo #39', 0, NULL, NULL, '2026-02-08', 1, '2026-03-08 18:35:31'),
(157, 1, 'cobro_proporcional', 1, 110000.00, 4, 4, 39, 71, 'Retorno préstamo #39 — 100% capital', 0, NULL, NULL, '2026-02-08', 1, '2026-03-08 18:35:31'),
(158, 1, '', 0, 200000.00, 4, NULL, 40, NULL, 'Préstamo #40 a deudor #20', 0, NULL, NULL, '2026-03-05', 1, '2026-03-08 18:36:41'),
(159, 1, 'prestamo_proporcional', 0, 200000.00, 4, 4, 40, NULL, 'Préstamo #40 — descuento capital propio', 0, NULL, NULL, '2026-03-05', 1, '2026-03-08 18:36:41'),
(160, 1, '', 0, 200000.00, 4, NULL, 41, NULL, 'Préstamo #41 a deudor #33', 0, NULL, NULL, '2026-02-27', 1, '2026-03-08 18:45:05'),
(161, 1, 'prestamo_proporcional', 0, 200000.00, 4, 4, 41, NULL, 'Préstamo #41 — descuento capital propio', 0, NULL, NULL, '2026-02-27', 1, '2026-03-08 18:45:05'),
(162, 1, 'cobro_cuota', 1, 240000.00, 4, NULL, 41, 72, 'Cobro cuota préstamo #41', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 18:45:30'),
(163, 1, 'cobro_proporcional', 1, 240000.00, 4, 4, 41, 72, 'Retorno préstamo #41 — 100% capital', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 18:45:30'),
(164, 1, '', 0, 200000.00, 4, NULL, 42, NULL, 'Préstamo #42 a deudor #27', 0, NULL, NULL, '2026-03-08', 1, '2026-03-08 18:47:24'),
(165, 1, 'prestamo_proporcional', 0, 200000.00, 4, 4, 42, NULL, 'Préstamo #42 — descuento capital propio', 0, NULL, NULL, '2026-03-08', 1, '2026-03-08 18:47:24'),
(166, 1, 'cobro_cuota', 1, 24000.00, 4, NULL, 42, 73, 'Cobro cuota préstamo #42', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 18:49:24'),
(167, 1, 'cobro_proporcional', 1, 24000.00, 4, 4, 42, 73, 'Retorno préstamo #42 — 100% capital', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 18:49:24'),
(168, 1, '', 0, 500000.00, 4, NULL, 43, NULL, 'Préstamo #43 a deudor #31', 0, NULL, NULL, '2026-02-01', 1, '2026-03-08 18:53:53'),
(169, 1, 'prestamo_proporcional', 0, 500000.00, 4, 4, 43, NULL, 'Préstamo #43 — descuento capital propio', 0, NULL, NULL, '2026-02-01', 1, '2026-03-08 18:53:53'),
(170, 1, 'cobro_cuota', 1, 210000.00, 4, NULL, 43, 74, 'Cobro cuota préstamo #43', 0, NULL, NULL, '2026-02-22', 1, '2026-03-08 18:54:37'),
(171, 1, 'cobro_proporcional', 1, 210000.00, 4, 4, 43, 74, 'Retorno préstamo #43 — 100% capital', 0, NULL, NULL, '2026-02-22', 1, '2026-03-08 18:54:37'),
(172, 1, 'cobro_cuota', 1, 70000.00, 4, NULL, 43, 77, 'Cobro cuota préstamo #43', 0, NULL, NULL, '2026-03-01', 1, '2026-03-08 18:54:54'),
(173, 1, 'cobro_proporcional', 1, 70000.00, 4, 4, 43, 77, 'Retorno préstamo #43 — 100% capital', 0, NULL, NULL, '2026-03-01', 1, '2026-03-08 18:54:54'),
(174, 1, '', 0, 300000.00, 4, NULL, 44, NULL, 'Préstamo #44 a deudor #11', 0, NULL, NULL, '2026-02-02', 1, '2026-03-08 18:58:52'),
(175, 1, 'prestamo_proporcional', 0, 300000.00, 4, 4, 44, NULL, 'Préstamo #44 — descuento capital propio', 0, NULL, NULL, '2026-02-02', 1, '2026-03-08 18:58:52'),
(176, 1, 'cobro_cuota', 1, 360000.00, 4, NULL, 44, 78, 'Cobro cuota préstamo #44', 0, NULL, NULL, '2026-03-04', 1, '2026-03-08 18:59:05'),
(177, 1, 'cobro_proporcional', 1, 360000.00, 4, 4, 44, 78, 'Retorno préstamo #44 — 100% capital', 0, NULL, NULL, '2026-03-04', 1, '2026-03-08 18:59:05'),
(178, 1, '', 0, 100000.00, 4, NULL, 45, NULL, 'Préstamo #45 a deudor #34', 0, NULL, NULL, '2026-02-01', 1, '2026-03-08 19:12:33'),
(179, 1, 'prestamo_proporcional', 0, 100000.00, 4, 4, 45, NULL, 'Préstamo #45 — descuento capital propio', 0, NULL, NULL, '2026-02-01', 1, '2026-03-08 19:12:33'),
(180, 1, 'cobro_cuota', 1, 120000.00, 4, NULL, 45, 79, 'Cobro cuota préstamo #45', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 19:13:05'),
(181, 1, 'cobro_proporcional', 1, 120000.00, 4, 4, 45, 79, 'Retorno préstamo #45 — 100% capital', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 19:13:05'),
(182, 1, '', 0, 200000.00, 4, NULL, 46, NULL, 'Préstamo #46 a deudor #12', 0, NULL, NULL, '2026-02-01', 1, '2026-03-08 19:14:22'),
(183, 1, 'prestamo_proporcional', 0, 200000.00, 4, 4, 46, NULL, 'Préstamo #46 — descuento capital propio', 0, NULL, NULL, '2026-02-01', 1, '2026-03-08 19:14:22'),
(184, 1, 'cobro_cuota', 1, 240000.00, 4, NULL, 46, 80, 'Cobro cuota préstamo #46', 0, NULL, NULL, '2026-03-03', 1, '2026-03-08 19:15:01'),
(185, 1, 'cobro_proporcional', 1, 240000.00, 4, 4, 46, 80, 'Retorno préstamo #46 — 100% capital', 0, NULL, NULL, '2026-03-03', 1, '2026-03-08 19:15:01'),
(186, 1, '', 0, 500000.00, 4, NULL, 47, NULL, 'Préstamo #47 a deudor #32', 0, NULL, NULL, '2026-02-15', 1, '2026-03-08 19:16:36'),
(187, 1, 'prestamo_proporcional', 0, 500000.00, 4, 4, 47, NULL, 'Préstamo #47 — descuento capital propio', 0, NULL, NULL, '2026-02-15', 1, '2026-03-08 19:16:36'),
(188, 1, 'cobro_cuota', 1, 140000.00, 4, NULL, 47, 82, 'Cobro cuota préstamo #47', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 19:16:57'),
(189, 1, 'cobro_proporcional', 1, 140000.00, 4, 4, 47, 82, 'Retorno préstamo #47 — 100% capital', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 19:16:57'),
(190, 1, '', 0, 200000.00, 4, NULL, 48, NULL, 'Préstamo #48 a deudor #10', 0, NULL, NULL, '2026-02-05', 1, '2026-03-08 19:17:58'),
(191, 1, 'prestamo_proporcional', 0, 200000.00, 4, 4, 48, NULL, 'Préstamo #48 — descuento capital propio', 0, NULL, NULL, '2026-02-05', 1, '2026-03-08 19:17:58'),
(192, 1, 'cobro_cuota', 1, 240000.00, 4, NULL, 48, 83, 'Cobro cuota préstamo #48', 0, NULL, NULL, '2026-03-05', 1, '2026-03-08 19:18:13'),
(193, 1, 'cobro_proporcional', 1, 240000.00, 4, 4, 48, 83, 'Retorno préstamo #48 — 100% capital', 0, NULL, NULL, '2026-03-05', 1, '2026-03-08 19:18:13'),
(194, 1, '', 0, 700000.00, 4, NULL, 49, NULL, 'Préstamo #49 a deudor #30', 0, NULL, NULL, '2026-02-08', 1, '2026-03-08 19:19:12'),
(195, 1, 'prestamo_proporcional', 0, 700000.00, 4, 4, 49, NULL, 'Préstamo #49 — descuento capital propio', 0, NULL, NULL, '2026-02-08', 1, '2026-03-08 19:19:12'),
(196, 1, 'cobro_cuota', 1, 420000.00, 4, NULL, 49, 84, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 19:19:51'),
(197, 1, 'cobro_proporcional', 1, 420000.00, 4, 4, 49, 84, 'Retorno préstamo #49 — 100% capital', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 19:19:51'),
(198, 1, '', 0, 1100000.00, 4, NULL, 50, NULL, 'Préstamo #50 a deudor #21', 0, NULL, NULL, '2026-03-01', 1, '2026-03-08 19:23:24'),
(199, 1, 'prestamo_proporcional', 0, 1100000.00, 4, 4, 50, NULL, 'Préstamo #50 — descuento capital propio', 0, NULL, NULL, '2026-03-01', 1, '2026-03-08 19:23:24'),
(200, 1, 'cobro_cuota', 1, 281000.00, 4, NULL, 50, 105, 'Cobro cuota préstamo #50', 0, NULL, NULL, '2026-03-06', 1, '2026-03-08 19:24:21'),
(201, 1, 'cobro_proporcional', 1, 281000.00, 4, 4, 50, 105, 'Retorno préstamo #50 — 100% capital', 0, NULL, NULL, '2026-03-06', 1, '2026-03-08 19:24:21'),
(202, 1, '', 0, 300000.00, 4, NULL, 51, NULL, 'Préstamo #51 a deudor #10', 0, NULL, NULL, '2026-02-21', 1, '2026-03-08 19:25:55'),
(203, 1, 'prestamo_proporcional', 0, 300000.00, 4, 5, 51, NULL, 'Préstamo #51 — descuento capital propio', 0, NULL, NULL, '2026-02-21', 1, '2026-03-08 19:25:55'),
(204, 1, 'cobro_cuota', 1, 315000.00, 4, NULL, 51, 106, 'Cobro cuota préstamo #51', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 19:26:12'),
(205, 1, 'cobro_proporcional', 1, 315000.00, 4, 5, 51, 106, 'Retorno préstamo #51 — 100% capital', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 19:26:12'),
(206, 1, '', 0, 300000.00, 4, NULL, 52, NULL, 'Préstamo #52 a deudor #22', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 19:26:51'),
(207, 1, 'prestamo_proporcional', 0, 300000.00, 4, 5, 52, NULL, 'Préstamo #52 — descuento capital propio', 0, NULL, NULL, '2026-02-28', 1, '2026-03-08 19:26:51'),
(208, 1, '', 0, 500000.00, 4, NULL, 53, NULL, 'Préstamo #53 a deudor #10', 0, NULL, NULL, '2026-03-01', 1, '2026-03-08 19:27:42'),
(209, 1, 'prestamo_proporcional', 0, 500000.00, 4, 5, 53, NULL, 'Préstamo #53 — descuento capital propio', 0, NULL, NULL, '2026-03-01', 1, '2026-03-08 19:27:42'),
(210, 1, '', 0, 200000.00, 4, NULL, 54, NULL, 'Préstamo #54 a deudor #12', 0, NULL, NULL, '2026-03-01', 1, '2026-03-08 19:28:29'),
(211, 1, 'prestamo_proporcional', 0, 200000.00, 4, 4, 54, NULL, 'Préstamo #54 — descuento capital propio', 0, NULL, NULL, '2026-03-01', 1, '2026-03-08 19:28:29'),
(212, 1, '', 0, 100000.00, 4, NULL, 55, NULL, 'Préstamo #55 a deudor #23', 0, NULL, NULL, '2026-03-02', 1, '2026-03-08 19:29:04'),
(213, 1, 'prestamo_proporcional', 0, 100000.00, 4, 4, 55, NULL, 'Préstamo #55 — descuento capital propio', 0, NULL, NULL, '2026-03-02', 1, '2026-03-08 19:29:04'),
(214, 1, '', 0, 250000.00, 4, NULL, 56, NULL, 'Préstamo #56 a deudor #1', 0, NULL, NULL, '2026-03-02', 1, '2026-03-08 19:29:35'),
(215, 1, 'prestamo_proporcional', 0, 250000.00, 4, 4, 56, NULL, 'Préstamo #56 — descuento capital propio', 0, NULL, NULL, '2026-03-02', 1, '2026-03-08 19:29:35'),
(216, 1, '', 0, 800000.00, 4, NULL, 57, NULL, 'Préstamo #57 a deudor #5', 0, NULL, NULL, '2026-03-02', 1, '2026-03-08 19:30:29'),
(217, 1, 'prestamo_proporcional', 0, 800000.00, 4, 5, 57, NULL, 'Préstamo #57 — descuento capital propio', 0, NULL, NULL, '2026-03-02', 1, '2026-03-08 19:30:29'),
(218, 1, '', 0, 200000.00, 4, NULL, 58, NULL, 'Préstamo #58 a deudor #24', 0, NULL, NULL, '2026-03-04', 1, '2026-03-08 19:31:18'),
(219, 1, 'prestamo_proporcional', 0, 200000.00, 4, 4, 58, NULL, 'Préstamo #58 — descuento capital propio', 0, NULL, NULL, '2026-03-04', 1, '2026-03-08 19:31:18'),
(220, 1, 'cobro_cuota', 1, 220000.00, 4, NULL, 58, 107, 'Cobro cuota préstamo #58', 0, NULL, NULL, '2026-03-06', 1, '2026-03-08 19:31:30'),
(221, 1, 'cobro_proporcional', 1, 220000.00, 4, 4, 58, 107, 'Retorno préstamo #58 — 100% capital', 0, NULL, NULL, '2026-03-06', 1, '2026-03-08 19:31:30'),
(222, 1, '', 0, 500000.00, 4, NULL, 59, NULL, 'Préstamo #59 a deudor #25', 0, NULL, NULL, '2026-03-07', 1, '2026-03-08 19:35:31'),
(223, 1, 'prestamo_proporcional', 0, 500000.00, 4, 4, 59, NULL, 'Préstamo #59 — descuento capital propio', 0, NULL, NULL, '2026-03-07', 1, '2026-03-08 19:35:31'),
(224, 1, '', 0, 600000.00, 4, NULL, 60, NULL, 'Préstamo #60 a deudor #26', 0, NULL, NULL, '2026-03-01', 1, '2026-03-08 19:37:09'),
(225, 1, 'prestamo_proporcional', 0, 600000.00, 4, 4, 60, NULL, 'Préstamo #60 — descuento capital propio', 0, NULL, NULL, '2026-03-01', 1, '2026-03-08 19:37:09'),
(226, 1, '', 0, 200000.00, 4, NULL, 61, NULL, 'Préstamo #61 a deudor #34', 0, NULL, NULL, '2026-03-07', 1, '2026-03-08 19:37:55'),
(227, 1, 'prestamo_proporcional', 0, 200000.00, 4, 5, 61, NULL, 'Préstamo #61 — descuento capital propio', 0, NULL, NULL, '2026-03-07', 1, '2026-03-08 19:37:55'),
(228, 1, '', 0, 300000.00, 4, NULL, 62, NULL, 'Préstamo #62 a deudor #6', 0, NULL, NULL, '2026-03-07', 1, '2026-03-08 19:38:46'),
(229, 1, 'prestamo_proporcional', 0, 300000.00, 4, 5, 62, NULL, 'Préstamo #62 — descuento capital propio', 0, NULL, NULL, '2026-03-07', 1, '2026-03-08 19:38:46'),
(230, 1, '', 0, 400000.00, 4, NULL, 63, NULL, 'Préstamo #63 a deudor #10', 0, NULL, NULL, '2026-03-08', 1, '2026-03-08 19:39:47'),
(231, 1, 'prestamo_proporcional', 0, 400000.00, 4, 5, 63, NULL, 'Préstamo #63 — descuento capital propio', 0, NULL, NULL, '2026-03-08', 1, '2026-03-08 19:39:47'),
(232, 1, '', 0, 500000.00, 4, NULL, 64, NULL, 'Préstamo #64 a deudor #18', 0, NULL, NULL, '2026-03-01', 1, '2026-03-08 19:42:14'),
(233, 1, 'prestamo_proporcional', 0, 485000.00, 4, 4, 64, NULL, 'Préstamo #64 — descuento capital propio', 0, NULL, NULL, '2026-03-01', 1, '2026-03-08 19:42:14'),
(234, 1, 'prestamo_proporcional', 0, 15000.00, 4, 5, 64, NULL, 'Préstamo #64 — descuento capital propio', 0, NULL, NULL, '2026-03-01', 1, '2026-03-08 19:42:14'),
(235, 1, 'cobro_cuota', 1, 50000.00, 4, NULL, 35, NULL, 'Intereses renovación préstamo #35', 0, NULL, NULL, '2026-03-08', 1, '2026-03-08 20:44:42'),
(236, 1, 'cobro_cuota', 1, 195000.00, 4, NULL, 50, 109, 'Cobro cuota préstamo #50', 0, NULL, NULL, '2026-03-08', 1, '2026-03-08 20:52:57'),
(237, 1, 'cobro_proporcional', 1, 195000.00, 4, 4, 50, 109, 'Retorno préstamo #50 — 100% capital', 0, NULL, NULL, '2026-03-08', 1, '2026-03-08 20:52:57'),
(238, 1, 'cobro_cuota', 1, 20000.00, 4, NULL, 49, 110, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-03-05', 1, '2026-03-09 13:14:48'),
(239, 1, 'cobro_proporcional', 1, 20000.00, 4, 4, 49, 110, 'Retorno préstamo #49 — 100% capital', 0, NULL, NULL, '2026-03-05', 1, '2026-03-09 13:14:48'),
(240, 1, 'cobro_cuota', 1, 20000.00, 4, NULL, 49, 111, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-03-09', 1, '2026-03-09 13:14:57'),
(241, 1, 'cobro_proporcional', 1, 20000.00, 4, 4, 49, 111, 'Retorno préstamo #49 — 100% capital', 0, NULL, NULL, '2026-03-09', 1, '2026-03-09 13:14:57'),
(242, 1, 'cobro_cuota', 1, 20000.00, 4, NULL, 49, 112, 'Cobro cuota préstamo #49', 0, NULL, NULL, '2026-03-09', 1, '2026-03-09 13:15:05'),
(243, 1, 'cobro_proporcional', 1, 20000.00, 4, 4, 49, 112, 'Retorno préstamo #49 — 100% capital', 0, NULL, NULL, '2026-03-09', 1, '2026-03-09 13:15:05'),
(244, 1, 'cobro_cuota', 1, 70000.00, 4, NULL, 38, 113, 'Cobro cuota préstamo #38', 0, NULL, NULL, '2026-03-07', 1, '2026-03-09 13:16:03'),
(245, 1, 'cobro_proporcional', 1, 70000.00, 4, 4, 38, 113, 'Retorno préstamo #38 — 100% capital', 0, NULL, NULL, '2026-03-07', 1, '2026-03-09 13:16:03'),
(246, 1, 'cobro_cuota', 1, 70000.00, 4, NULL, 37, 114, 'Cobro cuota préstamo #37', 0, NULL, NULL, '2026-03-09', 1, '2026-03-09 13:16:32'),
(247, 1, 'cobro_proporcional', 1, 70000.00, 4, 4, 37, 114, 'Retorno préstamo #37 — 100% capital', 0, NULL, NULL, '2026-03-09', 1, '2026-03-09 13:16:32'),
(248, 2, 'ingreso_capital', 1, 250000.00, 1, 1, NULL, NULL, NULL, 0, NULL, NULL, '2026-03-09', 1, '2026-03-09 13:27:52');

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
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cobros`
--

INSERT INTO `cobros` (`id`, `nombre`, `descripcion`, `direccion`, `telefono`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'EL BABY', 'ISALAMAMI.COM', NULL, NULL, 1, '2026-03-05 10:59:16', '2026-03-08 13:08:32'),
(2, 'LOS PATEA PUERTAS', NULL, 'calle 5', '3008787518', 1, '2026-03-05 21:30:40', '2026-03-05 21:30:40');

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
(2, 1, 2, 1, '2026-03-17', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:04:18', '2026-03-06 15:04:18'),
(3, 1, 2, 2, '2026-04-16', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:04:18', '2026-03-06 15:04:18'),
(4, 1, 2, 3, '2026-05-16', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:04:18', '2026-03-06 15:04:18'),
(5, 1, 2, 4, '2026-06-15', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:04:18', '2026-03-06 15:04:18'),
(6, 1, 2, 5, '2026-07-15', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:04:18', '2026-03-06 15:04:18'),
(7, 1, 2, 6, '2026-08-14', 100000.00, 0.00, 100000.00, NULL, 'pendiente', '2026-03-06 15:04:18', '2026-03-06 15:04:18'),
(13, 2, 2, 1, '2026-03-07', 160000.00, 0.00, 160000.00, NULL, 'pendiente', '2026-03-06 15:05:52', '2026-03-06 15:05:52'),
(14, 2, 2, 2, '2026-03-22', 160000.00, 0.00, 160000.00, NULL, 'pendiente', '2026-03-06 15:05:52', '2026-03-06 15:05:52'),
(15, 2, 2, 3, '2026-04-06', 160000.00, 0.00, 160000.00, NULL, 'pendiente', '2026-03-06 15:05:52', '2026-03-06 15:05:52'),
(16, 2, 2, 4, '2026-04-21', 160000.00, 0.00, 160000.00, NULL, 'pendiente', '2026-03-06 15:05:52', '2026-03-06 15:05:52'),
(17, 2, 2, 5, '2026-05-06', 160000.00, 0.00, 160000.00, NULL, 'pendiente', '2026-03-06 15:05:52', '2026-03-06 15:05:52'),
(30, 3, 2, 1, '2026-03-16', 227000.00, 0.00, 227000.00, NULL, 'pendiente', '2026-03-06 15:08:41', '2026-03-06 15:08:41'),
(31, 3, 2, 2, '2026-03-31', 227000.00, 0.00, 227000.00, NULL, 'pendiente', '2026-03-06 15:08:41', '2026-03-06 15:08:41'),
(32, 3, 2, 3, '2026-04-15', 227000.00, 0.00, 227000.00, NULL, 'pendiente', '2026-03-06 15:08:41', '2026-03-06 15:08:41'),
(33, 3, 2, 4, '2026-04-30', 227000.00, 0.00, 227000.00, NULL, 'pendiente', '2026-03-06 15:08:41', '2026-03-06 15:08:41'),
(34, 3, 2, 5, '2026-05-15', 227000.00, 0.00, 227000.00, NULL, 'pendiente', '2026-03-06 15:08:41', '2026-03-06 15:08:41'),
(35, 3, 2, 6, '2026-05-30', 227000.00, 0.00, 227000.00, NULL, 'pendiente', '2026-03-06 15:08:41', '2026-03-06 15:08:41'),
(36, 4, 2, 1, '2026-03-16', 282000.00, 0.00, 282000.00, NULL, 'pendiente', '2026-03-06 15:19:34', '2026-03-06 15:19:34'),
(37, 4, 2, 2, '2026-03-31', 282000.00, 0.00, 282000.00, NULL, 'pendiente', '2026-03-06 15:19:34', '2026-03-06 15:19:34'),
(38, 5, 2, 1, '2026-03-31', 480000.00, 0.00, 480000.00, NULL, 'pendiente', '2026-03-06 15:19:56', '2026-03-06 15:19:56'),
(39, 6, 2, 1, '2026-03-16', 660000.00, 0.00, 660000.00, NULL, 'pendiente', '2026-03-06 15:21:18', '2026-03-06 15:21:18'),
(40, 7, 2, 1, '2026-03-31', 600000.00, 0.00, 600000.00, NULL, 'pendiente', '2026-03-06 15:21:59', '2026-03-06 15:21:59'),
(41, 8, 2, 1, '2026-03-16', 880000.00, 0.00, 880000.00, NULL, 'pendiente', '2026-03-06 15:22:49', '2026-03-06 15:22:49'),
(42, 9, 2, 1, '2026-03-24', 1000000.00, 0.00, 1000000.00, NULL, 'pendiente', '2026-03-06 15:23:14', '2026-03-06 15:23:14'),
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
(97, 11, 2, 1, '2026-03-08', 45000.00, 0.00, 45000.00, NULL, 'pendiente', '2026-03-06 15:24:42', '2026-03-06 15:24:42'),
(98, 11, 2, 2, '2026-03-15', 45000.00, 0.00, 45000.00, NULL, 'pendiente', '2026-03-06 15:24:42', '2026-03-06 15:24:42'),
(99, 11, 2, 3, '2026-03-22', 45000.00, 0.00, 45000.00, NULL, 'pendiente', '2026-03-06 15:24:42', '2026-03-06 15:24:42'),
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
(245, 36, 1, 4, '2026-02-21', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:21:31', '2026-03-08 13:21:31'),
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
(258, 37, 1, 7, '2026-03-14', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:22:56', '2026-03-08 13:22:56'),
(259, 37, 1, 8, '2026-03-21', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:22:56', '2026-03-08 13:22:56'),
(260, 37, 1, 9, '2026-03-28', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:22:56', '2026-03-08 13:22:56'),
(261, 37, 1, 10, '2026-04-04', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:22:56', '2026-03-08 13:22:56'),
(262, 38, 1, 1, '2026-01-31', 70000.00, 70000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 13:24:18', '2026-03-08 13:24:39'),
(263, 38, 1, 2, '2026-02-07', 70000.00, 70000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 13:24:18', '2026-03-08 13:24:39'),
(264, 38, 1, 3, '2026-02-14', 70000.00, 70000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 13:24:18', '2026-03-08 13:24:39'),
(265, 38, 1, 4, '2026-02-21', 70000.00, 70000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 13:24:18', '2026-03-08 13:24:39'),
(266, 38, 1, 5, '2026-02-28', 70000.00, 70000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 13:24:18', '2026-03-08 13:24:39'),
(267, 38, 1, 6, '2026-03-07', 70000.00, 70000.00, 0.00, '2026-03-07', 'pagado', '2026-03-08 13:24:18', '2026-03-09 08:16:03'),
(268, 38, 1, 7, '2026-03-14', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:24:18', '2026-03-08 13:24:18'),
(269, 38, 1, 8, '2026-03-21', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:24:18', '2026-03-08 13:24:18'),
(270, 38, 1, 9, '2026-03-28', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:24:18', '2026-03-08 13:24:18'),
(271, 38, 1, 10, '2026-04-04', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:24:18', '2026-03-08 13:24:18'),
(272, 39, 1, 1, '2026-02-08', 110000.00, 110000.00, 0.00, '2026-02-08', 'pagado', '2026-03-08 13:35:12', '2026-03-08 13:35:31'),
(273, 40, 1, 1, '2026-03-20', 120000.00, 0.00, 120000.00, NULL, 'pendiente', '2026-03-08 13:36:41', '2026-03-08 13:36:41'),
(274, 40, 1, 2, '2026-04-04', 120000.00, 0.00, 120000.00, NULL, 'pendiente', '2026-03-08 13:36:41', '2026-03-08 13:36:41'),
(275, 41, 1, 1, '2026-03-29', 240000.00, 240000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 13:45:05', '2026-03-08 13:45:30'),
(284, 42, 1, 1, '2026-03-07', 70000.00, 24000.00, 46000.00, '2026-02-28', 'parcial', '2026-03-08 13:49:02', '2026-03-08 13:49:24'),
(285, 42, 1, 2, '2026-03-14', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:49:02', '2026-03-08 13:49:02'),
(286, 42, 1, 3, '2026-03-21', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:49:02', '2026-03-08 13:49:02'),
(287, 42, 1, 4, '2026-03-28', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:49:02', '2026-03-08 13:49:02'),
(288, 43, 1, 1, '2026-02-08', 70000.00, 70000.00, 0.00, '2026-02-22', 'pagado', '2026-03-08 13:53:53', '2026-03-08 13:54:37'),
(289, 43, 1, 2, '2026-02-15', 70000.00, 70000.00, 0.00, '2026-02-22', 'pagado', '2026-03-08 13:53:53', '2026-03-08 13:54:37'),
(290, 43, 1, 3, '2026-02-22', 70000.00, 70000.00, 0.00, '2026-02-22', 'pagado', '2026-03-08 13:53:53', '2026-03-08 13:54:37'),
(291, 43, 1, 4, '2026-03-01', 70000.00, 70000.00, 0.00, '2026-03-01', 'pagado', '2026-03-08 13:53:53', '2026-03-08 13:54:54'),
(292, 43, 1, 5, '2026-03-08', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:53:53', '2026-03-08 13:53:53'),
(293, 43, 1, 6, '2026-03-15', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:53:53', '2026-03-08 13:53:53'),
(294, 43, 1, 7, '2026-03-22', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:53:53', '2026-03-08 13:53:53'),
(295, 43, 1, 8, '2026-03-29', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:53:53', '2026-03-08 13:53:53'),
(296, 43, 1, 9, '2026-04-05', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:53:53', '2026-03-08 13:53:53'),
(297, 43, 1, 10, '2026-04-12', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 13:53:53', '2026-03-08 13:53:53'),
(298, 44, 1, 1, '2026-03-04', 360000.00, 360000.00, 0.00, '2026-03-04', 'pagado', '2026-03-08 13:58:52', '2026-03-08 13:59:05'),
(299, 45, 1, 1, '2026-03-03', 120000.00, 120000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:12:33', '2026-03-08 14:13:05'),
(300, 46, 1, 1, '2026-02-16', 120000.00, 120000.00, 0.00, '2026-03-03', 'pagado', '2026-03-08 14:14:22', '2026-03-08 14:15:01'),
(301, 46, 1, 2, '2026-03-03', 120000.00, 120000.00, 0.00, '2026-03-03', 'pagado', '2026-03-08 14:14:22', '2026-03-08 14:15:01'),
(302, 47, 1, 1, '2026-03-02', 140000.00, 140000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:16:36', '2026-03-08 14:16:57'),
(303, 47, 1, 2, '2026-03-17', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-03-08 14:16:36', '2026-03-08 14:16:36'),
(304, 47, 1, 3, '2026-04-01', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-03-08 14:16:36', '2026-03-08 14:16:36'),
(305, 47, 1, 4, '2026-04-16', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-03-08 14:16:36', '2026-03-08 14:16:36'),
(306, 47, 1, 5, '2026-05-01', 140000.00, 0.00, 140000.00, NULL, 'pendiente', '2026-03-08 14:16:36', '2026-03-08 14:16:36'),
(307, 48, 1, 1, '2026-03-07', 240000.00, 240000.00, 0.00, '2026-03-05', 'pagado', '2026-03-08 14:17:58', '2026-03-08 14:18:13'),
(308, 49, 1, 1, '2026-02-09', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(309, 49, 1, 2, '2026-02-10', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(310, 49, 1, 3, '2026-02-11', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(311, 49, 1, 4, '2026-02-12', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(312, 49, 1, 5, '2026-02-13', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(313, 49, 1, 6, '2026-02-14', 20000.00, 20000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:19:12', '2026-03-08 14:19:51'),
(314, 49, 1, 7, '2026-02-16', 20000.00, 0.00, 20000.00, NULL, 'pendiente', '2026-03-08 14:19:12', '2026-03-09 08:12:38'),
(315, 49, 1, 8, '2026-02-17', 20000.00, 0.00, 20000.00, NULL, 'pendiente', '2026-03-08 14:19:12', '2026-03-09 08:12:45'),
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
(332, 49, 1, 25, '2026-03-09', 20000.00, 0.00, 20000.00, NULL, 'pendiente', '2026-03-08 14:19:12', '2026-03-08 14:19:12'),
(333, 49, 1, 26, '2026-03-10', 20000.00, 0.00, 20000.00, NULL, 'pendiente', '2026-03-08 14:19:12', '2026-03-08 14:19:12'),
(334, 49, 1, 27, '2026-03-11', 20000.00, 0.00, 20000.00, NULL, 'pendiente', '2026-03-08 14:19:12', '2026-03-08 14:19:12'),
(335, 49, 1, 28, '2026-03-12', 20000.00, 0.00, 20000.00, NULL, 'pendiente', '2026-03-08 14:19:12', '2026-03-08 14:19:12'),
(336, 49, 1, 29, '2026-03-13', 20000.00, 0.00, 20000.00, NULL, 'pendiente', '2026-03-08 14:19:12', '2026-03-08 14:19:12'),
(337, 49, 1, 30, '2026-03-14', 20000.00, 0.00, 20000.00, NULL, 'pendiente', '2026-03-08 14:19:12', '2026-03-08 14:19:12'),
(338, 49, 1, 31, '2026-03-16', 20000.00, 0.00, 20000.00, NULL, 'pendiente', '2026-03-08 14:19:12', '2026-03-08 14:19:12'),
(339, 49, 1, 32, '2026-03-17', 20000.00, 0.00, 20000.00, NULL, 'pendiente', '2026-03-08 14:19:12', '2026-03-08 14:19:12'),
(340, 49, 1, 33, '2026-03-18', 20000.00, 0.00, 20000.00, NULL, 'pendiente', '2026-03-08 14:19:12', '2026-03-08 14:19:12'),
(341, 49, 1, 34, '2026-03-19', 20000.00, 0.00, 20000.00, NULL, 'pendiente', '2026-03-08 14:19:12', '2026-03-08 14:19:12'),
(342, 49, 1, 35, '2026-03-20', 20000.00, 0.00, 20000.00, NULL, 'pendiente', '2026-03-08 14:19:12', '2026-03-08 14:19:12'),
(343, 49, 1, 36, '2026-03-21', 20000.00, 0.00, 20000.00, NULL, 'pendiente', '2026-03-08 14:19:12', '2026-03-08 14:19:12'),
(344, 49, 1, 37, '2026-03-23', 20000.00, 0.00, 20000.00, NULL, 'pendiente', '2026-03-08 14:19:12', '2026-03-08 14:19:12'),
(345, 49, 1, 38, '2026-03-24', 20000.00, 0.00, 20000.00, NULL, 'pendiente', '2026-03-08 14:19:12', '2026-03-08 14:19:12'),
(346, 49, 1, 39, '2026-03-25', 20000.00, 0.00, 20000.00, NULL, 'pendiente', '2026-03-08 14:19:12', '2026-03-08 14:19:12'),
(347, 49, 1, 40, '2026-03-26', 20000.00, 0.00, 20000.00, NULL, 'pendiente', '2026-03-08 14:19:12', '2026-03-08 14:19:12'),
(348, 49, 1, 41, '2026-03-27', 20000.00, 0.00, 20000.00, NULL, 'pendiente', '2026-03-08 14:19:12', '2026-03-08 14:19:12'),
(349, 49, 1, 42, '2026-03-28', 20000.00, 0.00, 20000.00, NULL, 'pendiente', '2026-03-08 14:19:12', '2026-03-08 14:19:12'),
(350, 49, 1, 43, '2026-03-30', 20000.00, 0.00, 20000.00, NULL, 'pendiente', '2026-03-08 14:19:12', '2026-03-08 14:19:12'),
(351, 49, 1, 44, '2026-03-31', 20000.00, 0.00, 20000.00, NULL, 'pendiente', '2026-03-08 14:19:12', '2026-03-08 14:19:12'),
(352, 49, 1, 45, '2026-04-01', 20000.00, 0.00, 20000.00, NULL, 'pendiente', '2026-03-08 14:19:12', '2026-03-08 14:19:12'),
(353, 50, 1, 1, '2026-03-31', 1210000.00, 476000.00, 734000.00, '2026-03-08', 'parcial', '2026-03-08 14:23:24', '2026-03-08 15:52:57'),
(354, 51, 1, 1, '2026-02-28', 315000.00, 315000.00, 0.00, '2026-02-28', 'pagado', '2026-03-08 14:25:55', '2026-03-08 14:26:12'),
(355, 52, 1, 1, '2026-03-30', 360000.00, 0.00, 360000.00, NULL, 'pendiente', '2026-03-08 14:26:51', '2026-03-08 14:26:51'),
(356, 53, 1, 1, '2026-03-16', 550000.00, 0.00, 550000.00, NULL, 'pendiente', '2026-03-08 14:27:42', '2026-03-08 14:27:42'),
(357, 54, 1, 1, '2026-03-31', 240000.00, 0.00, 240000.00, NULL, 'pendiente', '2026-03-08 14:28:29', '2026-03-08 14:28:29'),
(358, 55, 1, 1, '2026-04-01', 120000.00, 0.00, 120000.00, NULL, 'pendiente', '2026-03-08 14:29:04', '2026-03-08 14:29:04'),
(359, 56, 1, 1, '2026-04-01', 300000.00, 0.00, 300000.00, NULL, 'pendiente', '2026-03-08 14:29:35', '2026-03-08 14:29:35'),
(360, 57, 1, 1, '2026-03-17', 460000.00, 0.00, 460000.00, NULL, 'pendiente', '2026-03-08 14:30:29', '2026-03-08 14:30:29'),
(361, 57, 1, 2, '2026-04-01', 460000.00, 0.00, 460000.00, NULL, 'pendiente', '2026-03-08 14:30:29', '2026-03-08 14:30:29'),
(362, 58, 1, 1, '2026-03-19', 220000.00, 220000.00, 0.00, '2026-03-06', 'pagado', '2026-03-08 14:31:18', '2026-03-08 14:31:30'),
(363, 59, 1, 1, '2026-03-14', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 14:35:31', '2026-03-08 14:35:31'),
(364, 59, 1, 2, '2026-03-21', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 14:35:31', '2026-03-08 14:35:31'),
(365, 59, 1, 3, '2026-03-28', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 14:35:31', '2026-03-08 14:35:31'),
(366, 59, 1, 4, '2026-04-04', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 14:35:31', '2026-03-08 14:35:31'),
(367, 59, 1, 5, '2026-04-11', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 14:35:31', '2026-03-08 14:35:31'),
(368, 59, 1, 6, '2026-04-18', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 14:35:31', '2026-03-08 14:35:31'),
(369, 59, 1, 7, '2026-04-25', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 14:35:31', '2026-03-08 14:35:31'),
(370, 59, 1, 8, '2026-05-02', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 14:35:31', '2026-03-08 14:35:31'),
(371, 59, 1, 9, '2026-05-09', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 14:35:31', '2026-03-08 14:35:31'),
(372, 59, 1, 10, '2026-05-16', 70000.00, 0.00, 70000.00, NULL, 'pendiente', '2026-03-08 14:35:31', '2026-03-08 14:35:31'),
(373, 60, 1, 1, '2026-03-16', 97500.00, 0.00, 97500.00, NULL, 'pendiente', '2026-03-08 14:37:09', '2026-03-08 14:37:09'),
(374, 60, 1, 2, '2026-03-31', 97500.00, 0.00, 97500.00, NULL, 'pendiente', '2026-03-08 14:37:09', '2026-03-08 14:37:09'),
(375, 60, 1, 3, '2026-04-15', 97500.00, 0.00, 97500.00, NULL, 'pendiente', '2026-03-08 14:37:09', '2026-03-08 14:37:09'),
(376, 60, 1, 4, '2026-04-30', 97500.00, 0.00, 97500.00, NULL, 'pendiente', '2026-03-08 14:37:09', '2026-03-08 14:37:09'),
(377, 60, 1, 5, '2026-05-15', 97500.00, 0.00, 97500.00, NULL, 'pendiente', '2026-03-08 14:37:09', '2026-03-08 14:37:09'),
(378, 60, 1, 6, '2026-05-30', 97500.00, 0.00, 97500.00, NULL, 'pendiente', '2026-03-08 14:37:09', '2026-03-08 14:37:09'),
(379, 60, 1, 7, '2026-06-14', 97500.00, 0.00, 97500.00, NULL, 'pendiente', '2026-03-08 14:37:09', '2026-03-08 14:37:09'),
(380, 60, 1, 8, '2026-06-29', 97500.00, 0.00, 97500.00, NULL, 'pendiente', '2026-03-08 14:37:09', '2026-03-08 14:37:09'),
(381, 61, 1, 1, '2026-03-22', 220000.00, 0.00, 220000.00, NULL, 'pendiente', '2026-03-08 14:37:55', '2026-03-08 14:37:55'),
(382, 62, 1, 1, '2026-03-22', 330000.00, 0.00, 330000.00, NULL, 'pendiente', '2026-03-08 14:38:46', '2026-03-08 14:38:46'),
(383, 63, 1, 1, '2026-03-23', 440000.00, 0.00, 440000.00, NULL, 'pendiente', '2026-03-08 14:39:47', '2026-03-08 14:39:47'),
(384, 64, 1, 1, '2026-03-31', 250000.00, 0.00, 250000.00, NULL, 'pendiente', '2026-03-08 14:42:14', '2026-03-08 14:42:14'),
(385, 64, 1, 2, '2026-04-30', 250000.00, 0.00, 250000.00, NULL, 'pendiente', '2026-03-08 14:42:14', '2026-03-08 14:42:14'),
(386, 65, 1, 1, '2026-04-07', 300000.00, 0.00, 300000.00, NULL, 'pendiente', '2026-03-08 15:44:42', '2026-03-08 15:44:42');

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
  `documento` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `barrio` varchar(100) DEFAULT NULL,
  `codeudor_nombre` varchar(150) DEFAULT NULL,
  `codeudor_telefono` varchar(20) DEFAULT NULL,
  `codeudor_documento` varchar(20) DEFAULT NULL,
  `garantia_descripcion` varchar(255) DEFAULT NULL,
  `comportamiento` enum('bueno','regular','malo') DEFAULT 'bueno',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `notas` text DEFAULT NULL,
  `saldo_favor` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `deudores`
--

INSERT INTO `deudores` (`id`, `cobro_id`, `nombre`, `telefono`, `telefono_alt`, `documento`, `direccion`, `barrio`, `codeudor_nombre`, `codeudor_telefono`, `codeudor_documento`, `garantia_descripcion`, `comportamiento`, `activo`, `notas`, `saldo_favor`, `created_at`, `updated_at`) VALUES
(1, 2, 'RAFAEL ACOSTA', '3015684343', NULL, '1', 'CALLE 48 #24-95', 'PCA', NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:27:16', '2026-03-05 22:27:16'),
(2, 2, 'CARLOS TALLER', '3185943676', NULL, '2', 'CARRERA 3C CALLE 42 # 15', 'LA 4', NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:29:29', '2026-03-05 22:29:29'),
(3, 2, 'ANTHONY PATRON', '3008787518', NULL, '1121534448', 'CARRERA 5', 'VILLA SAN PEDRO 2', NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:31:22', '2026-03-05 22:31:22'),
(4, 2, 'CARLOS H. MARLON', '3052807210', NULL, '3', 'Cementerio', NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:32:49', '2026-03-05 22:32:49'),
(5, 2, 'Cristian', '3007081492', NULL, '4', NULL, 'San Pedro 3', NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:35:05', '2026-03-05 22:35:05'),
(6, 2, 'Miguel Marlon', '3206606755', NULL, '5', NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:35:57', '2026-03-05 22:35:57'),
(7, 2, 'Juan Tec. Cristian', '3013688790', NULL, '1001997109', 'Calle 98b#6e-75', 'la cordialidad', NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:37:35', '2026-03-05 22:38:14'),
(8, 2, 'RUBEN DARIO', '3005013405', NULL, '6', NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:41:08', '2026-03-05 22:41:08'),
(9, 2, 'EDGAR ALTAMAR', '3057595825', NULL, '7', NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:41:49', '2026-03-05 22:41:49'),
(10, 2, 'EL NEGRO', '3508821971', NULL, '8', NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:42:42', '2026-03-05 22:42:42'),
(11, 2, 'ANDREW INCATEC', '3046486873', NULL, '9', NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:43:32', '2026-03-05 22:43:32'),
(12, 2, 'MARLON YEPEZ', '3206606755', NULL, '10', NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:44:35', '2026-03-05 22:44:35'),
(13, 2, 'KEVIN LLANOS HERMANO', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:45:04', '2026-03-05 22:45:04'),
(14, 2, 'STEVENSON', '3216080449', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:45:54', '2026-03-05 22:47:40'),
(15, 2, 'LUIS LLANOS', '3021191522', NULL, '11', NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-05 22:46:49', '2026-03-05 22:46:49'),
(16, 2, 'ARREGLO DE CASA PETI', '3114326407', NULL, '14', 'LA CASA', NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 09:13:42', '2026-03-06 09:13:42'),
(18, 1, 'ANDRES AMADOR', '3007285084', NULL, '16', NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 16:36:58', '2026-03-06 16:36:58'),
(19, 1, 'NATALYA ANDREW', '3007436041', NULL, '16', NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 16:40:49', '2026-03-06 16:40:49'),
(20, 1, 'PADRASTO ISAIAS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 16:43:02', '2026-03-06 16:43:02'),
(21, 1, 'ISA SHOP', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 16:44:12', '2026-03-06 16:44:12'),
(22, 1, 'DANNA CASIANI', '3017323914', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 16:46:19', '2026-03-06 16:46:19'),
(23, 1, 'MERA WO', '3218080874', NULL, '17', NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 21:55:32', '2026-03-06 21:55:32'),
(24, 1, 'SUEGRA', '3207410617', NULL, '15', NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 22:00:32', '2026-03-06 22:00:32'),
(25, 1, 'RONAL', NULL, NULL, '18', NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 22:03:33', '2026-03-06 22:03:33'),
(26, 1, 'ROSSI', '3163570665', NULL, '19', NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 22:05:24', '2026-03-06 22:05:24'),
(27, 1, 'WILSON TAXISTA', '3122624844', NULL, '20', NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 22:23:18', '2026-03-06 22:23:18'),
(28, 1, 'NAIR VIGILANTE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 22:59:37', '2026-03-06 22:59:37'),
(29, 1, 'JORGE MOTO', '3052846387', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 23:03:17', '2026-03-06 23:03:17'),
(30, 1, 'MUJER DE JORGE', '3014123344', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 23:03:58', '2026-03-06 23:03:58'),
(31, 1, 'NATALYA CAMARGO', '3012905501', NULL, '21', NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 23:09:39', '2026-03-06 23:09:39'),
(32, 1, 'SUPER GIROS', '3156156216', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-06 23:14:59', '2026-03-06 23:14:59'),
(33, 1, 'VIVIANA CADRASCO', '3017896857', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-08 13:43:43', '2026-03-08 13:43:43'),
(34, 1, 'Freisi', '3332236660', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'bueno', 1, NULL, 0.00, '2026-03-08 14:11:34', '2026-03-08 14:11:34');

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
(96, 34, 2, '2026-03-08 14:11:34');

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
(1, 1, 35, 19, 'nota', 'otro', 'Préstamo renovado. Nuevo: #65', '2026-03-08', 1, '2026-03-08 15:44:42');

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
  `metodo_pago` enum('efectivo','transferencia','nequi','bancolombia','daviplata','otro') DEFAULT 'efectivo',
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
(114, 1, 37, 257, 28, 70000.00, '2026-03-09', 4, 'efectivo', 0, NULL, '', 1, 0, NULL, NULL, '2026-03-09 08:16:32');

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

INSERT INTO `prestamos` (`id`, `cobro_id`, `deudor_id`, `capitalista_id`, `monto_prestado`, `tipo_interes`, `interes_valor`, `interes_calculado`, `total_a_pagar`, `frecuencia_pago`, `num_cuotas`, `valor_cuota`, `fecha_inicio`, `fecha_fin_esperada`, `cuenta_desembolso_id`, `metodo_desembolso`, `saldo_pendiente`, `dias_mora`, `estado`, `fecha_acuerdo`, `fecha_compromiso`, `nota_acuerdo`, `tipo_origen`, `prestamo_padre_id`, `observaciones`, `anulado_at`, `anulado_por`, `editado_at`, `editado_por`, `historial`, `usuario_id`, `omitir_domingos`, `created_at`, `updated_at`) VALUES
(1, 2, 16, NULL, 600000.00, 'porcentaje', 0.0000, 0.00, 600000.00, 'mensual', 6, 100000.00, '2026-02-15', '2026-08-14', 1, 'efectivo', 600000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, '2026-03-06 15:04:18', 1, '[{\"fecha\":\"2026-03-06 15:04:18\",\"usuario\":1,\"cambios\":{\"monto_prestado\":[\"6000000.00\",600000],\"interes_valor\":[\"20.0000\",0],\"num_cuotas\":[1,6],\"frecuencia_pago\":[\"mensual\",\"mensual\"]}}]', 1, 0, '2026-03-06 15:03:41', '2026-03-06 15:04:18'),
(2, 2, 4, NULL, 600000.00, 'valor_fijo', 200000.0000, 200000.00, 800000.00, 'quincenal', 5, 160000.00, '2026-02-20', '2026-05-06', 1, 'efectivo', 800000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, '2026-03-06 15:05:52', 1, '[{\"fecha\":\"2026-03-06 15:05:52\",\"usuario\":1,\"cambios\":{\"monto_prestado\":[\"6000000.00\",600000],\"interes_valor\":[\"200000.0000\",200000],\"num_cuotas\":[5,5],\"frecuencia_pago\":[\"quincenal\",\"quincenal\"]}}]', 1, 0, '2026-03-06 15:05:10', '2026-03-06 15:05:52'),
(3, 2, 5, NULL, 1000000.00, 'valor_fijo', 362000.0000, 362000.00, 1362000.00, 'quincenal', 6, 227000.00, '2026-03-01', '2026-05-30', 1, 'efectivo', 1362000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, '2026-03-06 15:08:41', 1, NULL, 1, 0, '2026-03-06 15:07:01', '2026-03-06 15:08:41'),
(4, 2, 8, NULL, 470000.00, 'porcentaje', 20.0000, 94000.00, 564000.00, 'quincenal', 2, 282000.00, '2026-03-01', '2026-03-31', 1, 'efectivo', 564000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-06 15:19:34', '2026-03-06 15:19:34'),
(5, 2, 9, NULL, 400000.00, 'porcentaje', 20.0000, 80000.00, 480000.00, 'mensual', 1, 480000.00, '2026-03-01', '2026-03-31', 1, 'efectivo', 480000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-06 15:19:56', '2026-03-06 15:19:56'),
(6, 2, 10, NULL, 600000.00, 'porcentaje', 10.0000, 60000.00, 660000.00, 'quincenal', 1, 660000.00, '2026-03-01', '2026-03-16', 1, 'efectivo', 660000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-06 15:21:18', '2026-03-06 15:21:18'),
(7, 2, 11, NULL, 500000.00, 'porcentaje', 20.0000, 100000.00, 600000.00, 'mensual', 1, 600000.00, '2026-03-01', '2026-03-31', 1, 'efectivo', 600000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-06 15:21:59', '2026-03-06 15:21:59'),
(8, 2, 12, NULL, 800000.00, 'porcentaje', 10.0000, 80000.00, 880000.00, 'quincenal', 1, 880000.00, '2026-03-01', '2026-03-16', 1, 'efectivo', 880000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-06 15:22:49', '2026-03-06 15:22:49'),
(9, 2, 13, NULL, 1000000.00, 'porcentaje', 0.0000, 0.00, 1000000.00, 'mensual', 1, 1000000.00, '2026-02-22', '2026-03-24', 1, 'efectivo', 1000000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-06 15:23:14', '2026-03-06 15:23:14'),
(10, 2, 14, NULL, 2700000.00, 'porcentaje', 0.0000, 0.00, 2700000.00, 'quincenal', 27, 100000.00, '2026-03-01', '2027-04-10', 1, 'efectivo', 2700000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, '2026-03-06 15:23:53', 1, NULL, 1, 0, '2026-03-06 15:23:45', '2026-03-06 15:23:53'),
(11, 2, 15, NULL, 180000.00, 'porcentaje', 0.0000, 0.00, 180000.00, 'semanal', 4, 45000.00, '2026-03-01', '2026-03-29', 1, 'efectivo', 180000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-06 15:24:42', '2026-03-06 15:24:42'),
(32, 1, 5, NULL, 1000000.00, 'porcentaje', 24.0000, 240000.00, 1240000.00, 'quincenal', 4, 310000.00, '2025-12-26', '2026-02-24', 4, 'efectivo', 0.00, 26, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-07 16:02:26', '2026-03-07 16:16:16'),
(33, 1, 23, NULL, 150000.00, 'porcentaje', 20.0000, 30000.00, 180000.00, 'mensual', 1, 180000.00, '2025-12-27', '2026-01-26', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 13:13:27', '2026-03-08 13:13:49'),
(34, 1, 23, NULL, 150000.00, 'porcentaje', 20.0000, 30000.00, 180000.00, 'mensual', 1, 180000.00, '2026-01-04', '2026-02-03', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 13:18:14', '2026-03-08 13:18:40'),
(35, 1, 19, NULL, 250000.00, 'porcentaje', 20.0000, 50000.00, 300000.00, 'mensual', 1, 300000.00, '2026-02-01', '2026-03-03', 4, 'efectivo', 0.00, 0, 'renovado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 13:20:01', '2026-03-08 15:44:42'),
(36, 1, 27, NULL, 500000.00, 'valor_fijo', 200000.0000, 200000.00, 700000.00, 'semanal', 10, 70000.00, '2026-01-24', '2026-04-04', 4, 'efectivo', 490000.00, 15, 'en_mora', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 13:21:31', '2026-03-08 13:22:02'),
(37, 1, 28, NULL, 500000.00, 'valor_fijo', 200000.0000, 200000.00, 700000.00, 'semanal', 10, 70000.00, '2026-01-24', '2026-04-04', 4, 'efectivo', 280000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 13:22:56', '2026-03-09 08:16:32'),
(38, 1, 29, NULL, 500000.00, 'valor_fijo', 200000.0000, 200000.00, 700000.00, 'semanal', 10, 70000.00, '2026-01-24', '2026-04-04', 4, 'efectivo', 280000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 13:24:18', '2026-03-09 08:16:03'),
(39, 1, 6, NULL, 100000.00, 'porcentaje', 10.0000, 10000.00, 110000.00, 'quincenal', 1, 110000.00, '2026-01-24', '2026-02-08', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 13:35:12', '2026-03-08 13:35:31'),
(40, 1, 20, NULL, 200000.00, 'porcentaje', 20.0000, 40000.00, 240000.00, 'quincenal', 2, 120000.00, '2026-03-05', '2026-04-04', 4, 'efectivo', 240000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 13:36:41', '2026-03-08 13:36:41'),
(41, 1, 33, NULL, 200000.00, 'porcentaje', 20.0000, 40000.00, 240000.00, 'mensual', 1, 240000.00, '2026-02-27', '2026-03-29', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 13:45:05', '2026-03-08 13:45:30'),
(42, 1, 27, NULL, 200000.00, 'porcentaje', 40.0000, 80000.00, 280000.00, 'semanal', 4, 70000.00, '2026-02-28', '2026-03-28', 4, 'efectivo', 256000.00, 1, 'en_mora', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, '2026-03-08 13:49:02', 1, '[{\"fecha\":\"2026-03-08 13:48:37\",\"usuario\":1,\"cambios\":{\"monto_prestado\":[\"200000.00\",200000],\"interes_valor\":[\"40.0000\",40],\"num_cuotas\":[4,4],\"frecuencia_pago\":[\"semanal\",\"semanal\"]}},{\"fecha\":\"2026-03-08 13:49:02\",\"usuario\":1,\"cambios\":{\"monto_prestado\":[\"200000.00\",200000],\"interes_valor\":[\"40.0000\",40],\"num_cuotas\":[4,4],\"frecuencia_pago\":[\"semanal\",\"semanal\"]}}]', 1, 0, '2026-03-08 13:47:24', '2026-03-08 13:49:24'),
(43, 1, 31, NULL, 500000.00, 'valor_fijo', 200000.0000, 200000.00, 700000.00, 'semanal', 10, 70000.00, '2026-02-01', '2026-04-12', 4, 'efectivo', 420000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 13:53:53', '2026-03-08 13:54:54'),
(44, 1, 11, NULL, 300000.00, 'porcentaje', 20.0000, 60000.00, 360000.00, 'mensual', 1, 360000.00, '2026-02-02', '2026-03-04', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 13:58:52', '2026-03-08 13:59:05'),
(45, 1, 34, NULL, 100000.00, 'porcentaje', 20.0000, 20000.00, 120000.00, 'mensual', 1, 120000.00, '2026-02-01', '2026-03-03', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:12:33', '2026-03-08 14:13:05'),
(46, 1, 12, NULL, 200000.00, 'porcentaje', 20.0000, 40000.00, 240000.00, 'quincenal', 2, 120000.00, '2026-02-01', '2026-03-03', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:14:22', '2026-03-08 14:15:01'),
(47, 1, 32, NULL, 500000.00, 'valor_fijo', 200000.0000, 200000.00, 700000.00, 'quincenal', 5, 140000.00, '2026-02-15', '2026-05-01', 4, 'efectivo', 560000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:16:36', '2026-03-08 14:16:57'),
(48, 1, 10, NULL, 200000.00, 'porcentaje', 20.0000, 40000.00, 240000.00, 'mensual', 1, 240000.00, '2026-02-05', '2026-03-07', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:17:58', '2026-03-08 14:18:13'),
(49, 1, 30, NULL, 700000.00, 'valor_fijo', 200000.0000, 200000.00, 900000.00, 'diario', 45, 20000.00, '2026-02-08', '2026-03-25', 4, 'efectivo', 460000.00, 21, 'en_mora', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 1, '2026-03-08 14:19:12', '2026-03-09 08:15:05'),
(50, 1, 21, NULL, 1100000.00, 'porcentaje', 10.0000, 110000.00, 1210000.00, 'mensual', 1, 1210000.00, '2026-03-01', '2026-03-31', 4, 'efectivo', 734000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:23:24', '2026-03-08 15:52:57'),
(51, 1, 10, NULL, 300000.00, 'porcentaje', 5.0000, 15000.00, 315000.00, 'semanal', 1, 315000.00, '2026-02-21', '2026-02-28', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:25:55', '2026-03-08 14:26:12'),
(52, 1, 22, NULL, 300000.00, 'porcentaje', 20.0000, 60000.00, 360000.00, 'mensual', 1, 360000.00, '2026-02-28', '2026-03-30', 4, 'efectivo', 360000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:26:51', '2026-03-08 14:26:51'),
(53, 1, 10, NULL, 500000.00, 'porcentaje', 10.0000, 50000.00, 550000.00, 'quincenal', 1, 550000.00, '2026-03-01', '2026-03-16', 4, 'efectivo', 550000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:27:42', '2026-03-08 14:27:42'),
(54, 1, 12, NULL, 200000.00, 'porcentaje', 20.0000, 40000.00, 240000.00, 'mensual', 1, 240000.00, '2026-03-01', '2026-03-31', 4, 'efectivo', 240000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:28:29', '2026-03-08 14:28:29'),
(55, 1, 23, NULL, 100000.00, 'porcentaje', 20.0000, 20000.00, 120000.00, 'mensual', 1, 120000.00, '2026-03-02', '2026-04-01', 4, 'efectivo', 120000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:29:04', '2026-03-08 14:29:04'),
(56, 1, 1, NULL, 250000.00, 'porcentaje', 20.0000, 50000.00, 300000.00, 'mensual', 1, 300000.00, '2026-03-02', '2026-04-01', 4, 'efectivo', 300000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:29:35', '2026-03-08 14:29:35'),
(57, 1, 5, NULL, 800000.00, 'porcentaje', 15.0000, 120000.00, 920000.00, 'quincenal', 2, 460000.00, '2026-03-02', '2026-04-01', 4, 'efectivo', 920000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:30:29', '2026-03-08 14:30:29'),
(58, 1, 24, NULL, 200000.00, 'porcentaje', 10.0000, 20000.00, 220000.00, 'quincenal', 1, 220000.00, '2026-03-04', '2026-03-19', 4, 'efectivo', 0.00, 0, 'pagado', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:31:18', '2026-03-08 14:31:30'),
(59, 1, 25, NULL, 500000.00, 'valor_fijo', 200000.0000, 200000.00, 700000.00, 'semanal', 10, 70000.00, '2026-03-07', '2026-05-16', 4, 'efectivo', 700000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:35:31', '2026-03-08 14:35:31'),
(60, 1, 26, NULL, 600000.00, 'valor_fijo', 180000.0000, 180000.00, 780000.00, 'quincenal', 8, 97500.00, '2026-03-01', '2026-06-29', 4, 'efectivo', 780000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:37:09', '2026-03-08 14:37:09'),
(61, 1, 34, NULL, 200000.00, 'porcentaje', 10.0000, 20000.00, 220000.00, 'quincenal', 1, 220000.00, '2026-03-07', '2026-03-22', 4, 'efectivo', 220000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:37:55', '2026-03-08 14:37:55'),
(62, 1, 6, NULL, 300000.00, 'porcentaje', 10.0000, 30000.00, 330000.00, 'quincenal', 1, 330000.00, '2026-03-07', '2026-03-22', 4, 'efectivo', 330000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:38:46', '2026-03-08 14:38:46'),
(63, 1, 10, NULL, 400000.00, 'porcentaje', 10.0000, 40000.00, 440000.00, 'quincenal', 1, 440000.00, '2026-03-08', '2026-03-23', 4, 'efectivo', 440000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:39:47', '2026-03-08 14:39:47'),
(64, 1, 18, NULL, 500000.00, 'porcentaje', 0.0000, 0.00, 500000.00, 'mensual', 2, 250000.00, '2026-03-01', '2026-04-30', 4, 'efectivo', 500000.00, 0, 'activo', NULL, NULL, NULL, 'nuevo', NULL, '', NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 14:42:14', '2026-03-08 14:42:14'),
(65, 1, 19, NULL, 250000.00, 'porcentaje', 20.0000, 50000.00, 300000.00, 'mensual', 1, 300000.00, '2026-03-08', '2026-04-07', 4, 'efectivo', 300000.00, 0, 'activo', NULL, NULL, NULL, 'renovacion', 35, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-03-08 15:44:42', '2026-03-08 15:44:42');

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
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password_hash`, `rol`, `activo`, `ultimo_login`, `created_at`, `updated_at`) VALUES
(1, 'ADMIN', 'admin@gmail.com', '$2y$10$wtzGAguljZQ9isHD2qFJI.5rIJ1rliVmV5BstCFU/OCDN8l/mLTBq', 'superadmin', 1, '2026-03-09 15:19:25', '2026-03-05 10:59:15', '2026-03-09 15:19:25'),
(2, 'COBRADOR 1', 'cobrador@gmail.com', '$2y$10$ukXw7yeVpHUsk.kmbwJVAOTWXMwHGSx2y9FzID7AJw7ss1CkwUOHi', 'cobrador', 1, '2026-03-09 15:12:55', '2026-03-05 20:33:52', '2026-03-09 15:12:55'),
(3, 'Isa', 'isa@gmail.com', '$2y$10$OIvL9BHWX8TryhI8nvjjh.Q4eunFfrdwW54kxucCOlIuf9OeAcMQ2', 'admin', 1, '2026-03-09 11:49:07', '2026-03-05 20:34:21', '2026-03-09 14:54:24'),
(4, 'COBRADOR 2', 'cobrador2@gmail.com', '$2y$10$rnod.OW33aLcoyV6uzOJTu2KsGuOVxvvpXhLLywhQZlMRPQs5oGc.', 'cobrador', 0, '2026-03-09 11:44:00', '2026-03-09 11:42:34', '2026-03-09 14:55:13');

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
(2, 2, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 1, 1, '2026-03-05 20:33:52'),
(3, 3, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, '2026-03-05 20:34:21'),
(4, 1, 2, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-03-05 21:30:40'),
(5, 4, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 1, 1, '2026-03-09 11:42:34');

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
,`total_prestado` decimal(37,2)
,`total_retornado` decimal(37,2)
,`total_reditos_pagados` decimal(37,2)
,`ganancia_neta` decimal(38,2)
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

--
-- Índices para tablas volcadas
--

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
-- Indices de la tabla `gestiones_cobro`
--
ALTER TABLE `gestiones_cobro`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_gc_cobro` (`cobro_id`),
  ADD KEY `fk_gc_deudor` (`deudor_id`),
  ADD KEY `fk_gc_usuario` (`usuario_id`),
  ADD KEY `idx_gc_prestamo_fecha` (`prestamo_id`,`fecha_gestion`);

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
-- AUTO_INCREMENT de la tabla `capitalistas`
--
ALTER TABLE `capitalistas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `capital_movimientos`
--
ALTER TABLE `capital_movimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=249;

--
-- AUTO_INCREMENT de la tabla `cobros`
--
ALTER TABLE `cobros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `cuentas`
--
ALTER TABLE `cuentas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `cuotas`
--
ALTER TABLE `cuotas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=387;

--
-- AUTO_INCREMENT de la tabla `deudores`
--
ALTER TABLE `deudores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `deudor_cobro`
--
ALTER TABLE `deudor_cobro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT de la tabla `gestiones_cobro`
--
ALTER TABLE `gestiones_cobro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- AUTO_INCREMENT de la tabla `prestamos`
--
ALTER TABLE `prestamos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuario_cobro`
--
ALTER TABLE `usuario_cobro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_cartera_cobro`
--
DROP TABLE IF EXISTS `v_cartera_cobro`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u504403321_capital`@`127.0.0.1` SQL SECURITY DEFINER VIEW `v_cartera_cobro`  AS SELECT `prestamos`.`cobro_id` AS `cobro_id`, count(0) AS `total_prestamos`, sum(`prestamos`.`monto_prestado`) AS `total_prestado`, sum(`prestamos`.`saldo_pendiente`) AS `total_por_cobrar`, sum(case when `prestamos`.`estado` = 'activo' then 1 else 0 end) AS `prestamos_activos`, sum(case when `prestamos`.`estado` = 'en_mora' then 1 else 0 end) AS `prestamos_en_mora`, sum(case when `prestamos`.`estado` = 'en_acuerdo' then 1 else 0 end) AS `prestamos_en_acuerdo`, sum(case when `prestamos`.`estado` = 'pagado' then 1 else 0 end) AS `prestamos_pagados`, sum(case when `prestamos`.`estado` = 'incobrable' then 1 else 0 end) AS `prestamos_incobrables`, sum(case when `prestamos`.`estado` in ('en_mora','incobrable') then `prestamos`.`saldo_pendiente` else 0 end) AS `cartera_riesgo` FROM `prestamos` WHERE `prestamos`.`estado` not in ('renovado','refinanciado','anulado') GROUP BY `prestamos`.`cobro_id` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_cuotas_hoy`
--
DROP TABLE IF EXISTS `v_cuotas_hoy`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u504403321_capital`@`127.0.0.1` SQL SECURITY DEFINER VIEW `v_cuotas_hoy`  AS SELECT `cu`.`id` AS `cuota_id`, `cu`.`cobro_id` AS `cobro_id`, `cu`.`prestamo_id` AS `prestamo_id`, `cu`.`numero_cuota` AS `numero_cuota`, `cu`.`fecha_vencimiento` AS `fecha_vencimiento`, `cu`.`monto_cuota` AS `monto_cuota`, `cu`.`saldo_cuota` AS `saldo_cuota`, `cu`.`estado` AS `estado_cuota`, `p`.`estado` AS `estado_prestamo`, `p`.`dias_mora` AS `dias_mora`, `d`.`id` AS `deudor_id`, `d`.`nombre` AS `deudor_nombre`, `d`.`telefono` AS `deudor_telefono`, `d`.`barrio` AS `deudor_barrio` FROM ((`cuotas` `cu` join `prestamos` `p` on(`p`.`id` = `cu`.`prestamo_id`)) join `deudores` `d` on(`d`.`id` = `p`.`deudor_id`)) WHERE `cu`.`estado` in ('pendiente','parcial','vencido') AND `cu`.`fecha_vencimiento` <= curdate() AND `p`.`estado` <> 'anulado' ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_saldo_capitalistas`
--
DROP TABLE IF EXISTS `v_saldo_capitalistas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u504403321_capital`@`127.0.0.1` SQL SECURITY DEFINER VIEW `v_saldo_capitalistas`  AS SELECT `cap`.`id` AS `capitalista_id`, `cap`.`cobro_id` AS `cobro_id`, `cap`.`nombre` AS `nombre`, `cap`.`tipo` AS `tipo`, `cap`.`tasa_redito` AS `tasa_redito`, `cap`.`tipo_redito` AS `tipo_redito`, `cap`.`frecuencia_redito` AS `frecuencia_redito`, `cap`.`estado` AS `estado`, `cap`.`color` AS `color`, coalesce(sum(case when `cm`.`anulado` = 0 and `cm`.`tipo` in ('ingreso_capital','cobro_proporcional') then `cm`.`monto` when `cm`.`anulado` = 0 and `cm`.`tipo` in ('prestamo_proporcional','retiro_capital','redito') then -`cm`.`monto` else 0 end),0) AS `saldo_actual`, coalesce(sum(case when `cm`.`anulado` = 0 and `cm`.`tipo` = 'ingreso_capital' then `cm`.`monto` else 0 end),0) AS `total_aportado`, coalesce(sum(case when `cm`.`anulado` = 0 and `cm`.`tipo` = 'prestamo_proporcional' then `cm`.`monto` else 0 end),0) AS `total_prestado`, coalesce(sum(case when `cm`.`anulado` = 0 and `cm`.`tipo` = 'cobro_proporcional' then `cm`.`monto` else 0 end),0) AS `total_retornado`, coalesce(sum(case when `cm`.`anulado` = 0 and `cm`.`tipo` = 'redito' then `cm`.`monto` else 0 end),0) AS `total_reditos_pagados`, coalesce(sum(case when `cm`.`anulado` = 0 and `cm`.`tipo` = 'cobro_proporcional' then `cm`.`monto` else 0 end),0) - coalesce(sum(case when `cm`.`anulado` = 0 and `cm`.`tipo` = 'ingreso_capital' then `cm`.`monto` else 0 end),0) AS `ganancia_neta` FROM (`capitalistas` `cap` left join `capital_movimientos` `cm` on(`cm`.`capitalista_id` = `cap`.`id`)) GROUP BY `cap`.`id`, `cap`.`cobro_id`, `cap`.`nombre`, `cap`.`tipo`, `cap`.`tasa_redito`, `cap`.`tipo_redito`, `cap`.`frecuencia_redito`, `cap`.`estado`, `cap`.`color` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_saldo_cuentas`
--
DROP TABLE IF EXISTS `v_saldo_cuentas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u504403321_capital`@`127.0.0.1` SQL SECURITY DEFINER VIEW `v_saldo_cuentas`  AS SELECT `c`.`id` AS `cuenta_id`, `c`.`cobro_id` AS `cobro_id`, `c`.`nombre` AS `nombre`, `c`.`tipo` AS `tipo`, coalesce(sum(case when `cm`.`es_entrada` = 1 and `cm`.`anulado` = 0 then `cm`.`monto` else 0 end),0) AS `total_entradas`, coalesce(sum(case when `cm`.`es_entrada` = 0 and `cm`.`anulado` = 0 then `cm`.`monto` else 0 end),0) AS `total_salidas`, coalesce(sum(case when `cm`.`anulado` = 0 then case when `cm`.`es_entrada` = 1 then `cm`.`monto` else -`cm`.`monto` end else 0 end),0) AS `saldo_actual` FROM (`cuentas` `c` left join `capital_movimientos` `cm` on(`cm`.`cuenta_id` = `c`.`id` and `cm`.`tipo` not in ('prestamo_proporcional','cobro_proporcional'))) WHERE `c`.`activa` = 1 GROUP BY `c`.`id`, `c`.`cobro_id`, `c`.`nombre`, `c`.`tipo` ;

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
  ADD CONSTRAINT `capital_movimientos_ibfk_2` FOREIGN KEY (`cuenta_id`) REFERENCES `cuentas` (`id`),
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
  ADD CONSTRAINT `fk_pago_cuenta` FOREIGN KEY (`cuenta_id`) REFERENCES `cuentas` (`id`) ON DELETE SET NULL,
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
