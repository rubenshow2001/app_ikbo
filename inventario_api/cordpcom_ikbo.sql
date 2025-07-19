-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 19-07-2025 a las 17:03:02
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `cordpcom_ikbo`
--
CREATE DATABASE IF NOT EXISTS `cordpcom_ikbo` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `cordpcom_ikbo`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lotes`
--

DROP TABLE IF EXISTS `lotes`;
CREATE TABLE `lotes` (
  `id_lote` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `id_proveedor` int(11) DEFAULT NULL,
  `numero_lote` varchar(100) NOT NULL,
  `cantidad_ingresada` int(11) NOT NULL,
  `fecha_entrada` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `ubicacion_almacen` varchar(255) DEFAULT NULL,
  `costo_compra_unitario` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `lotes`
--

INSERT INTO `lotes` (`id_lote`, `id_producto`, `id_proveedor`, `numero_lote`, `cantidad_ingresada`, `fecha_entrada`, `fecha_vencimiento`, `ubicacion_almacen`, `costo_compra_unitario`) VALUES
(1, 1, 1, 'AQL18072025-1', 200, '2025-07-15', '2025-07-15', '', NULL),
(2, 1, 1, 'ALQ18072025-2', 500, '2025-07-19', '2025-07-28', '', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_inventario`
--

DROP TABLE IF EXISTS `movimientos_inventario`;
CREATE TABLE `movimientos_inventario` (
  `id_movimiento` int(11) NOT NULL,
  `id_lote` int(11) NOT NULL,
  `tipo_movimiento` enum('entrada','salida','ajuste') NOT NULL,
  `cantidad_movida` int(11) NOT NULL,
  `fecha_movimiento` timestamp NOT NULL DEFAULT current_timestamp(),
  `razon_movimiento` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `movimientos_inventario`
--

INSERT INTO `movimientos_inventario` (`id_movimiento`, `id_lote`, `tipo_movimiento`, `cantidad_movida`, `fecha_movimiento`, `razon_movimiento`) VALUES
(1, 1, 'entrada', 200, '2025-07-19 04:36:44', 'Entrada de inventario por compra/producción'),
(2, 2, 'entrada', 500, '2025-07-19 04:52:16', 'Entrada de inventario por compra/producción');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

DROP TABLE IF EXISTS `productos`;
CREATE TABLE `productos` (
  `id_producto` int(11) NOT NULL,
  `nombre_producto` varchar(255) NOT NULL,
  `descripcion_producto` text DEFAULT NULL,
  `sku` varchar(100) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `unidad_medida` varchar(50) DEFAULT NULL,
  `stock_minimo` int(11) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id_producto`, `nombre_producto`, `descripcion_producto`, `sku`, `precio_unitario`, `unidad_medida`, `stock_minimo`, `fecha_creacion`) VALUES
(1, 'Leche entera', 'LECHE', 'ALQ8798798', 1500.00, 'L', 100, '2025-07-19 04:34:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL,
  `nombre_proveedor` varchar(255) NOT NULL,
  `contacto_proveedor` varchar(255) DEFAULT NULL,
  `telefono_proveedor` varchar(20) DEFAULT NULL,
  `email_proveedor` varchar(255) DEFAULT NULL,
  `direccion_proveedor` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id_proveedor`, `nombre_proveedor`, `contacto_proveedor`, `telefono_proveedor`, `email_proveedor`, `direccion_proveedor`, `fecha_registro`) VALUES
(1, 'La Alqueria', 'Pedrito', '', 'Pedrito@alqueria.com', '', '2025-07-19 04:23:30'),
(2, 'La Alqueria', 'Juan|', '87897', 'juan@alqueria.com', 'Bogota', '2025-07-19 04:24:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stock_actual`
--

DROP TABLE IF EXISTS `stock_actual`;
CREATE TABLE `stock_actual` (
  `id_stock` int(11) NOT NULL,
  `id_lote` int(11) NOT NULL,
  `cantidad_actual` int(11) NOT NULL,
  `fecha_ultima_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `stock_actual`
--

INSERT INTO `stock_actual` (`id_stock`, `id_lote`, `cantidad_actual`, `fecha_ultima_actualizacion`) VALUES
(1, 1, 200, '2025-07-19 04:36:44'),
(2, 2, 500, '2025-07-19 04:52:16');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `lotes`
--
ALTER TABLE `lotes`
  ADD PRIMARY KEY (`id_lote`),
  ADD UNIQUE KEY `id_producto` (`id_producto`,`numero_lote`),
  ADD KEY `id_proveedor` (`id_proveedor`);

--
-- Indices de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD PRIMARY KEY (`id_movimiento`),
  ADD KEY `id_lote` (`id_lote`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id_producto`),
  ADD UNIQUE KEY `sku` (`sku`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id_proveedor`),
  ADD UNIQUE KEY `email_proveedor` (`email_proveedor`);

--
-- Indices de la tabla `stock_actual`
--
ALTER TABLE `stock_actual`
  ADD PRIMARY KEY (`id_stock`),
  ADD UNIQUE KEY `id_lote` (`id_lote`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `lotes`
--
ALTER TABLE `lotes`
  MODIFY `id_lote` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  MODIFY `id_movimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id_proveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `stock_actual`
--
ALTER TABLE `stock_actual`
  MODIFY `id_stock` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `lotes`
--
ALTER TABLE `lotes`
  ADD CONSTRAINT `lotes_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  ADD CONSTRAINT `lotes_ibfk_2` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`);

--
-- Filtros para la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD CONSTRAINT `movimientos_inventario_ibfk_1` FOREIGN KEY (`id_lote`) REFERENCES `lotes` (`id_lote`);

--
-- Filtros para la tabla `stock_actual`
--
ALTER TABLE `stock_actual`
  ADD CONSTRAINT `stock_actual_ibfk_1` FOREIGN KEY (`id_lote`) REFERENCES `lotes` (`id_lote`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
