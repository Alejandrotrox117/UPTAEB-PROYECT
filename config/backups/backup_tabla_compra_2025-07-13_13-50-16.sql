-- ================================================
-- BACKUP TABLA: compra
-- Base de datos: general
-- Fecha: 2025-07-13 13:50:16
-- ================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Estructura de tabla para `compra`
DROP TABLE IF EXISTS `compra`;
CREATE TABLE `compra` (
  `idcompra` int(11) NOT NULL AUTO_INCREMENT,
  `nro_compra` varchar(25) NOT NULL,
  `fecha` date NOT NULL,
  `idproveedor` int(11) NOT NULL,
  `idmoneda_general` int(11) NOT NULL,
  `subtotal_general` decimal(15,2) DEFAULT 0.00,
  `descuento_porcentaje_general` decimal(5,2) DEFAULT 0.00,
  `monto_descuento_general` decimal(15,2) DEFAULT 0.00,
  `total_general` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) NOT NULL,
  `estatus_compra` varchar(20) DEFAULT 'Pendiente',
  `observaciones_compra` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idcompra`),
  UNIQUE KEY `nro_compra` (`nro_compra`),
  KEY `fk_compras_proveedor` (`idproveedor`),
  KEY `fk_compras_moneda_general` (`idmoneda_general`),
  CONSTRAINT `fk_compras_moneda_general` FOREIGN KEY (`idmoneda_general`) REFERENCES `monedas` (`idmoneda`) ON UPDATE CASCADE,
  CONSTRAINT `fk_compras_proveedor` FOREIGN KEY (`idproveedor`) REFERENCES `proveedor` (`idproveedor`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `compra`
LOCK TABLES `compra` WRITE;
INSERT INTO `compra` (`idcompra`, `nro_compra`, `fecha`, `idproveedor`, `idmoneda_general`, `subtotal_general`, `descuento_porcentaje_general`, `monto_descuento_general`, `total_general`, `balance`, `estatus_compra`, `observaciones_compra`, `fecha_creacion`, `fecha_modificacion`) VALUES ('83', 'C-2025-00001', '2025-06-11', '60', '3', '11037.40', '0.00', '0.00', '11037.40', '0.00', 'PAGADA', 'nada', '2025-06-11 21:09:01', '2025-06-11 22:11:33'),
('84', 'C-2025-00002', '2025-06-11', '52', '3', '5017.00', '0.00', '0.00', '5017.00', '0.00', 'PAGADA', 'nadaaa', '2025-06-11 21:17:40', '2025-06-11 22:04:38'),
('85', 'C-2025-00003', '2025-06-12', '21', '3', '36122.40', '0.00', '0.00', '36122.40', '0.00', 'BORRADOR', 'hola', '2025-06-12 10:34:57', '2025-06-12 10:34:57'),
('86', 'C-2025-00004', '2025-06-12', '21', '3', '405.00', '0.00', '0.00', '405.00', '0.00', 'BORRADOR', 'nada', '2025-06-12 10:56:09', '2025-06-12 10:56:09'),
('87', 'C-2025-00005', '2025-06-12', '60', '3', '4515.30', '0.00', '0.00', '4515.30', '0.00', 'BORRADOR', 'LISTA', '2025-06-12 10:58:16', '2025-06-12 10:59:01'),
('88', 'C-2025-00006', '2025-06-12', '21', '3', '130.00', '0.00', '0.00', '130.00', '0.00', 'BORRADOR', 'NADA', '2025-06-12 11:06:17', '2025-06-21 14:16:14'),
('89', 'C-2025-00007', '2025-06-12', '77', '3', '12391.99', '0.00', '0.00', '12391.99', '0.99', 'PAGADA', 'FALTA TARA', '2025-06-12 11:11:00', '2025-07-07 21:56:18'),
('90', 'C-2025-00008', '2025-06-12', '58', '3', '55187.00', '0.00', '0.00', '55187.00', '0.00', 'PAGADA', 'aa', '2025-06-12 11:44:09', '2025-06-14 19:18:18'),
('91', 'C-2025-00009', '2025-06-13', '52', '3', '40.00', '0.00', '0.00', '40.00', '0.00', 'PAGADA', 'nadaa', '2025-06-13 19:45:38', '2025-07-08 00:01:49'),
('92', 'C-2025-00010', '2025-06-13', '52', '3', '40.00', '0.00', '0.00', '40.00', '0.00', 'PAGADA', 'nadaa', '2025-06-13 19:53:59', '2025-06-13 19:59:50'),
('93', 'C-2025-00011', '2025-06-13', '52', '3', '10.00', '0.00', '0.00', '10.00', '0.00', 'PAGADA', 'nadaa', '2025-06-13 20:49:03', '2025-06-13 20:50:30'),
('94', 'C-2025-00012', '2025-06-20', '58', '3', '21600.00', '0.00', '0.00', '21600.00', '0.00', 'AUTORIZADA', 'materiales con contaminante ', '2025-06-20 09:05:40', '2025-06-20 09:06:05'),
('95', 'C-2025-00013', '2025-06-20', '58', '3', '24000.00', '0.00', '0.00', '24000.00', '0.00', 'AUTORIZADA', 'nada', '2025-06-20 09:14:50', '2025-06-20 09:16:27'),
('96', 'C-2025-00014', '2025-06-20', '58', '3', '24000.00', '0.00', '0.00', '24000.00', '0.00', 'PAGADA', 'nada', '2025-06-20 09:22:04', '2025-07-07 22:12:51'),
('97', 'C-2025-00015', '2025-07-03', '52', '3', '548.85', '0.00', '0.00', '548.85', '548.85', 'POR_PAGAR', 'nada', '2025-07-03 13:18:07', '2025-07-03 13:22:30'),
('98', 'C-2025-00016', '2025-07-03', '58', '3', '167957.98', '0.00', '0.00', '167957.98', '167957.98', 'BORRADOR', 'pago', '2025-07-03 13:35:25', '2025-07-03 13:35:25'),
('99', 'C-2025-00017', '2025-07-03', '58', '3', '1530.00', '0.00', '0.00', '1530.00', '0.00', 'PAGADA', 'Nada', '2025-07-03 13:36:43', '2025-07-03 14:02:41'),
('100', 'C-2025-00018', '2025-07-03', '58', '3', '69155.10', '0.00', '0.00', '69155.10', '0.00', 'PAGADA', 'pagoo', '2025-07-03 16:55:19', '2025-07-03 16:56:48'),
('101', 'C-2025-00019', '2025-07-05', '58', '3', '1305.00', '0.00', '0.00', '1305.00', '1305.00', 'BORRADOR', 'Pago', '2025-07-05 19:22:28', '2025-07-05 19:22:28'),
('102', 'C-2025-00020', '2025-07-05', '58', '3', '648.00', '0.00', '0.00', '648.00', '648.00', 'POR_PAGAR', 'Nada', '2025-07-05 19:26:28', '2025-07-12 12:46:44');
UNLOCK TABLES;


SET FOREIGN_KEY_CHECKS = 1;
