-- Script para inicializar el módulo de backups en la base de datos

-- Insertar el módulo de backups si no existe
INSERT IGNORE INTO modulos (titulo, descripcion, estatus) 
VALUES ('backups', 'Gestión de copias de seguridad del sistema', 'activo');

-- Obtener el ID del módulo de backups
SET @idmodulo_backups = (SELECT idmodulo FROM modulos WHERE titulo = 'backups');

-- Insertar permisos básicos para el módulo de backups si no existen
INSERT IGNORE INTO permisos (nombre_permiso, descripcion) VALUES 
('ver', 'Ver y consultar backups'),
('crear', 'Crear nuevos backups'),
('eliminar', 'Eliminar backups existentes'),
('editar', 'Restaurar backups');

-- Asignar permisos específicos al rol de administrador para el módulo de backups
INSERT IGNORE INTO rol_modulo_permisos (idrol, idmodulo, idpermiso, activo) 
SELECT 1, @idmodulo_backups, p.idpermiso, 1
FROM permisos p 
WHERE p.nombre_permiso IN ('ver', 'crear', 'eliminar', 'editar');

SELECT 'Módulo de backups inicializado correctamente' as resultado;
