-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         10.4.32-MariaDB - mariadb.org binary distribution
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.10.0.7000
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para project
CREATE DATABASE IF NOT EXISTS `project` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
USE `project`;

-- Volcando estructura para tabla project.bitacora
CREATE TABLE IF NOT EXISTS `bitacora` (
  `idbitacora` int(11) NOT NULL,
  `tabla` varchar(20) NOT NULL,
  `accion` varchar(20) NOT NULL,
  `idusuario` int(11) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla project.bitacora: ~0 rows (aproximadamente)

-- Volcando estructura para tabla project.categoria
CREATE TABLE IF NOT EXISTS `categoria` (
  `idcategoria` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estatus` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `ultima_modificacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idcategoria`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla project.categoria: ~2 rows (aproximadamente)
INSERT INTO `categoria` (`idcategoria`, `nombre`, `descripcion`, `estatus`, `fecha_creacion`, `ultima_modificacion`) VALUES
	(1, 'Pacas', 'Producto Final', 'activo', '2025-04-22 16:38:09', '2025-04-22 16:38:09'),
	(2, 'Materiales', 'Materiales que se compran para reciclar', 'activo', '2025-04-22 16:39:56', '2025-04-22 16:39:56');

-- Volcando estructura para tabla project.clasificaciones
CREATE TABLE IF NOT EXISTS `clasificaciones` (
  `idclasificacion` int(11) NOT NULL,
  `idproceso` int(2) NOT NULL,
  `idempleado` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_final` date NOT NULL,
  `proceso_producto` varchar(20) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `ultima_modificacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idclasificacion`),
  KEY `idproceso` (`idproceso`),
  KEY `idempleado` (`idempleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla project.clasificaciones: ~0 rows (aproximadamente)

-- Volcando estructura para tabla project.compras
CREATE TABLE IF NOT EXISTS `compras` (
  `idcompra` int(11) NOT NULL AUTO_INCREMENT,
  `nro_compra` int(11) NOT NULL,
  `fecha` date DEFAULT NULL,
  `estatus` enum('activo','inactivo') NOT NULL,
  `idproveedor` int(11) NOT NULL,
  `idproducto` int(11) NOT NULL,
  `peso_vehiculo` decimal(10,2) NOT NULL,
  `peso_bruto` decimal(10,2) NOT NULL,
  `peso_neto` decimal(10,2) NOT NULL,
  `precio_kg` decimal(10,2) NOT NULL,
  `idmoneda` int(11) NOT NULL,
  `descuento_porcentaje` decimal(10,2) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idcompra`),
  KEY `idproveedor` (`idproveedor`),
  KEY `idmaterial` (`idproducto`),
  KEY `idmoneda` (`idmoneda`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla project.compras: ~0 rows (aproximadamente)

-- Volcando estructura para tabla project.costos_produccion
CREATE TABLE IF NOT EXISTS `costos_produccion` (
  `idcostoproduccion` int(11) NOT NULL,
  `idcosto` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `nombre` varchar(20) NOT NULL,
  `descripcion` varchar(200) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `ultima_modificacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idcostoproduccion`),
  KEY `idcosto` (`idcosto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla project.costos_produccion: ~0 rows (aproximadamente)

-- Volcando estructura para tabla project.modulos
CREATE TABLE IF NOT EXISTS `modulos` (
  `idmodulo` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(20) NOT NULL,
  `descripcion` longtext NOT NULL,
  `estatus` enum('Activo','Inactivo') NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `fecha_modificacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idmodulo`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla project.modulos: ~6 rows (aproximadamente)
INSERT INTO `modulos` (`idmodulo`, `titulo`, `descripcion`, `estatus`, `fecha_creacion`, `fecha_modificacion`) VALUES
	(1, 'Inventario', 'Gestión de inventarios', 'Activo', '2025-05-05 14:05:50', '2025-05-05 20:05:50'),
	(2, 'Personas', 'aquí se registra toda persona que trabaje sea cliente de la empresa', 'Activo', '2025-05-05 07:10:33', '2025-05-05 07:10:33'),
	(3, 'Categorias', 'aca se va a registrar todas las categorias de cartones', 'Activo', '2025-05-05 14:14:33', '2025-05-05 20:14:33'),
	(4, 'productos', 'todos los productos de la empresa', 'Activo', '2025-05-05 14:13:25', '2025-05-05 20:13:25'),
	(5, 'Compra de materiales', 'acá se compra toda la materia prima', 'Activo', '2025-05-05 07:41:52', '2025-05-05 07:41:52'),
	(6, 'ventas de materias', 'acá se va a vender toda la materia prima y a tu prima', 'Activo', '2025-05-05 15:30:50', '2025-05-05 21:30:50');

-- Volcando estructura para tabla project.monedas
CREATE TABLE IF NOT EXISTS `monedas` (
  `idmoneda` int(11) NOT NULL,
  `nombre_moneda` varchar(20) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `estado` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `ultima_modificacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idmoneda`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla project.monedas: ~0 rows (aproximadamente)

-- Volcando estructura para tabla project.movimientos_existencia
CREATE TABLE IF NOT EXISTS `movimientos_existencia` (
  `idmovimiento` int(11) NOT NULL AUTO_INCREMENT,
  `idproducto` int(11) NOT NULL,
  `total` float NOT NULL,
  `estatus` enum('activo','inactivo') NOT NULL,
  `entrada` float NOT NULL,
  `salida` float NOT NULL,
  PRIMARY KEY (`idmovimiento`),
  KEY `idmaterial` (`idproducto`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla project.movimientos_existencia: ~1 rows (aproximadamente)
INSERT INTO `movimientos_existencia` (`idmovimiento`, `idproducto`, `total`, `estatus`, `entrada`, `salida`) VALUES
	(1, 1, 100, 'activo', 0, 0);

-- Volcando estructura para tabla project.pagos
CREATE TABLE IF NOT EXISTS `pagos` (
  `idpago` int(11) NOT NULL AUTO_INCREMENT,
  `idpersona` int(11) NOT NULL,
  `idtipo_pago` int(11) NOT NULL,
  `idventa` int(11) DEFAULT NULL,
  `idcompra` int(11) DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `fecha_pago` date NOT NULL,
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`idpago`),
  KEY `idpersona` (`idpersona`),
  KEY `idcompra` (`idcompra`),
  KEY `idventa` (`idventa`),
  KEY `idtipo_pago` (`idtipo_pago`),
  CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`idpersona`) REFERENCES `personas` (`idpersona`),
  CONSTRAINT `pagos_ibfk_2` FOREIGN KEY (`idcompra`) REFERENCES `compras` (`idcompra`),
  CONSTRAINT `pagos_ibfk_3` FOREIGN KEY (`idventa`) REFERENCES `venta` (`idventa`),
  CONSTRAINT `pagos_ibfk_4` FOREIGN KEY (`idtipo_pago`) REFERENCES `tipos_pagos` (`idtipo_pago`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla project.pagos: ~0 rows (aproximadamente)

-- Volcando estructura para tabla project.permisos
CREATE TABLE IF NOT EXISTS `permisos` (
  `idpermiso` int(11) NOT NULL AUTO_INCREMENT,
  `idrol` int(20) NOT NULL,
  `idmodulo` int(11) NOT NULL,
  `idusuario` int(11) NOT NULL,
  `nombre` varchar(20) NOT NULL,
  `estatus` enum('Activo','Inactivo') NOT NULL,
  `fecha_creacion` int(11) NOT NULL,
  `ultima_modificacion` int(11) NOT NULL,
  PRIMARY KEY (`idpermiso`),
  KEY `idrol` (`idrol`,`idmodulo`,`idusuario`),
  KEY `idusuario` (`idusuario`),
  KEY `idmodulo` (`idmodulo`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla project.permisos: ~0 rows (aproximadamente)

-- Volcando estructura para tabla project.personas
CREATE TABLE IF NOT EXISTS `personas` (
  `idpersona` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `cedula` varchar(30) NOT NULL,
  `rif` varchar(30) NOT NULL,
  `tipo` varchar(30) NOT NULL,
  `genero` enum('masculino','femenino','otro') DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `correo_electronico` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `ciudad` text DEFAULT NULL,
  `estado` text DEFAULT NULL,
  `pais` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `estatus` enum('Activo','Inactivo') DEFAULT 'Activo',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `telefono` varchar(25) NOT NULL,
  PRIMARY KEY (`idpersona`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla project.personas: ~7 rows (aproximadamente)
INSERT INTO `personas` (`idpersona`, `nombre`, `apellido`, `cedula`, `rif`, `tipo`, `genero`, `fecha_nacimiento`, `correo_electronico`, `direccion`, `ciudad`, `estado`, `pais`, `observaciones`, `estatus`, `fecha_creacion`, `fecha_modificacion`, `telefono`) VALUES
	(1, 'agustin', 'gonzalez', '26556514', '265565146', 'empleado', 'masculino', '1998-09-14', 'gagustin780@gmail.com', 'calle 13 entre carrera 14 y 15 ', 'Barquisimeto', 'Lara', 'Venezuela', 'soy el mejor por que dios lo quiso', 'Activo', '2025-05-11 15:36:46', '2025-05-11 19:11:03', '04125106352'),
	(2, 'mariannys', 'Gonzalez', '26556514', '265565146', 'empleado', 'femenino', '2025-05-11', 'gagustin780@gmail.com', 'calle 13', 'Barquisimeto', 'Lara', 'Venezuela', 'hola soy bata', 'Inactivo', '2025-05-11 19:10:46', '2025-05-12 01:32:02', '04145789963'),
	(4, 'agustin', 'gonzalez', '26556514', '90129391291', 'comprador', 'masculino', '1998-09-14', NULL, NULL, 'Barquisimeto', 'Lara', 'Venezuela', 'hola que tal', 'Activo', '2025-05-11 22:18:31', '2025-05-11 22:18:31', '04125106352'),
	(5, 'agustin', 'gonzalez', '26556514', '90129391291', 'comprador', 'masculino', '1998-09-14', NULL, NULL, 'Barquisimeto', 'Lara', 'Venezuela', 'hola que tal', 'Activo', '2025-05-11 22:25:17', '2025-05-11 22:25:17', '04125106352'),
	(6, 'agustin', 'gonzalez', '26556514', '90129391291', 'comprador', 'masculino', '1998-09-14', NULL, NULL, 'Barquisimeto', 'Lara', 'Venezuela', 'hola', 'Activo', '2025-05-11 22:26:09', '2025-05-11 22:26:09', '04125106352'),
	(7, 'agustin', 'gonzalez', '26556514', '90129391291', 'comprador', 'masculino', '1998-09-14', NULL, NULL, 'Barquisimeto', 'Lara', 'Venezuela', 'hola', 'Activo', '2025-05-11 22:31:24', '2025-05-11 22:31:24', '04125106352'),
	(11, 'agustin', 'gonzalez', '26556514', '90129391291', 'comprador', 'masculino', '1998-09-14', NULL, NULL, 'Barquisimeto', 'Lara', 'Venezuela', 'hola', 'Activo', '2025-05-11 22:45:47', '2025-05-11 22:45:47', '04125106352');

-- Volcando estructura para tabla project.procesos
CREATE TABLE IF NOT EXISTS `procesos` (
  `idproceso` int(11) NOT NULL,
  `nombre_proceso` int(11) NOT NULL,
  `idproducto` int(11) NOT NULL,
  `sueldo_proceso` int(11) NOT NULL,
  `secuencia` varchar(25) NOT NULL,
  `estatus` enum('activo','inactivo') NOT NULL,
  `proceso_producto` varchar(20) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idproceso`),
  KEY `idmaterial` (`idproducto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla project.procesos: ~0 rows (aproximadamente)

-- Volcando estructura para tabla project.produccion
CREATE TABLE IF NOT EXISTS `produccion` (
  `idproduccion` int(11) NOT NULL AUTO_INCREMENT,
  `idempleado` int(11) NOT NULL,
  `idproceso` int(11) NOT NULL,
  `fecha_inicio` int(11) NOT NULL,
  `fecha_final` int(11) NOT NULL,
  `proceso_producto` float NOT NULL,
  `cantidad` int(11) NOT NULL,
  `estatus` enum('activo','inactivo') NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `fecha_modificacion` datetime NOT NULL,
  PRIMARY KEY (`idproduccion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla project.produccion: ~0 rows (aproximadamente)

-- Volcando estructura para tabla project.producto
CREATE TABLE IF NOT EXISTS `producto` (
  `idproducto` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `unidad_medida` varchar(20) DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `existencia` int(11) DEFAULT 0,
  `idcategoria` int(11) DEFAULT NULL,
  `estatus` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `ultima_modificacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idproducto`),
  KEY `idcategoria` (`idcategoria`),
  CONSTRAINT `producto_ibfk_1` FOREIGN KEY (`idcategoria`) REFERENCES `categoria` (`idcategoria`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla project.producto: ~1 rows (aproximadamente)
INSERT INTO `producto` (`idproducto`, `nombre`, `descripcion`, `unidad_medida`, `precio`, `existencia`, `idcategoria`, `estatus`, `fecha_creacion`, `ultima_modificacion`) VALUES
	(1, 'Carton', 'Carton', 'kg', 2.50, 1000, 2, 'activo', '2025-04-22 16:40:25', '2025-04-22 16:40:25');

-- Volcando estructura para tabla project.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `idrol` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `estatus` enum('Activo','Inactivo') NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ultima_modificacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idrol`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla project.roles: ~3 rows (aproximadamente)
INSERT INTO `roles` (`idrol`, `nombre`, `estatus`, `descripcion`, `fecha_creacion`, `ultima_modificacion`) VALUES
	(1, 'Administrador', 'Inactivo', 'hola soy bata', '2025-05-05 03:30:39', '2025-05-05 09:30:39'),
	(2, 'Empleado', 'Activo', 'Empleado de bata', '2025-05-01 23:23:07', '2025-05-02 05:23:07'),
	(3, 'Root', 'Inactivo', 'hola', '2025-05-05 03:28:35', '2025-05-05 09:28:35');

-- Volcando estructura para tabla project.sueldos_temporales
CREATE TABLE IF NOT EXISTS `sueldos_temporales` (
  `idsueldotemp` int(11) NOT NULL,
  `idempleado` int(11) NOT NULL,
  `idproduccion` int(11) NOT NULL,
  `produccion_cantidad` float NOT NULL,
  `produccion_sueldo` decimal(10,2) NOT NULL,
  `produccion_producto` decimal(10,2) NOT NULL,
  `estatus` enum('activo','inactivo') NOT NULL,
  `sueldo` decimal(10,2) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idsueldotemp`),
  KEY `idclasificacion` (`idproduccion`),
  KEY `idempleado` (`idempleado`),
  KEY `idproduccion` (`idproduccion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla project.sueldos_temporales: ~0 rows (aproximadamente)

-- Volcando estructura para tabla project.tipos_pagos
CREATE TABLE IF NOT EXISTS `tipos_pagos` (
  `idtipo_pago` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(25) NOT NULL,
  `estatus` enum('activo','inactivo') NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `fecha_modificacion` datetime NOT NULL,
  PRIMARY KEY (`idtipo_pago`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla project.tipos_pagos: ~0 rows (aproximadamente)

-- Volcando estructura para tabla project.usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `idusuario` int(11) NOT NULL AUTO_INCREMENT,
  `idpersona` int(11) NOT NULL,
  `idrol` int(11) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `token` longtext NOT NULL,
  `status1` int(11) NOT NULL DEFAULT 1,
  `correo` varchar(255) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ultima_modificacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idusuario`),
  KEY `idrol` (`idrol`),
  KEY `FK_usuarios_personas` (`idpersona`),
  CONSTRAINT `FK_usuarios_personas` FOREIGN KEY (`idpersona`) REFERENCES `personas` (`idpersona`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_usuarios_roles` FOREIGN KEY (`idrol`) REFERENCES `roles` (`idrol`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla project.usuarios: ~2 rows (aproximadamente)
INSERT INTO `usuarios` (`idusuario`, `idpersona`, `idrol`, `clave`, `token`, `status1`, `correo`, `fecha_creacion`, `ultima_modificacion`) VALUES
	(1, 1, 3, '$2y$10$kYcpgz6vHLtXpPxg5WwNfubXOTUvBstaRMlIvZVEw1PbV8T6fGmF2', '', 1, 'gagustin780@gmail.com', '2025-05-11 19:42:26', '2025-05-11 19:39:03'),
	(4, 11, 1, '$2y$10$lR6i76.Lj8PUySng6xdZWOGtV.imMBxQhvUYHuw9kLEYQ7NrtDErO', '', 1, 'gagustin780@gmail.com', '2025-05-12 02:45:47', '2025-05-12 02:45:47');

-- Volcando estructura para tabla project.venta
CREATE TABLE IF NOT EXISTS `venta` (
  `idventa` int(11) NOT NULL AUTO_INCREMENT,
  `idmaterial` int(11) NOT NULL,
  `idcliente` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `cantidad` float NOT NULL,
  `estado` int(11) NOT NULL,
  `descuento` int(11) NOT NULL,
  `total` float NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `ultima_modificacion` datetime NOT NULL,
  PRIMARY KEY (`idventa`),
  KEY `idmaterial` (`idmaterial`),
  KEY `idcliente` (`idcliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla project.venta: ~0 rows (aproximadamente)

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
