<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class RolesModel extends Mysql {
    public function __construct() {
        parent::__construct();
    }

    // Obtener todos los roles
    public function getRoles() {
        $sql = "SELECT * FROM roles WHERE estatus = 'activo'";
        $request = $this->searchAll($sql);
        return $request;
    }

    // Obtener permisos por usuario
    public function getPermisosPorUsuario($idUsuario) {
        $sql = "SELECT p.idpermiso, p.idmodulo, p.lectura, p.escritura, p.actualizar, p.eliminar 
                FROM permisos p
                WHERE p.idusuario = $idUsuario AND p.estatus = 'activo'";
        $request = $this->searchAll($sql);
        return $request;
    }

    // Obtener permisos por rol
    public function getPermisosPorRol($idRol) {
        $sql = "SELECT p.idpermiso, p.idmodulo, p.lectura, p.escritura, p.actualizar, p.eliminar 
                FROM permisos p
                WHERE p.idrol = $idRol AND p.estatus = 'activo'";
        $request = $this->searchAll($sql);
        return $request;
    }

    // Asignar permisos a un rol
    public function asignarPermisosARol($idRol, $idModulo, $lectura, $escritura, $actualizar, $eliminar) {
        $sql = "INSERT INTO permisos (idrol, idmodulo, lectura, escritura, actualizar, eliminar, estatus, fecha_creacion, ultima_modificacion) 
                VALUES (?, ?, ?, ?, ?, ?, 'activo', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";
        $arrData = [$idRol, $idModulo, $lectura, $escritura, $actualizar, $eliminar];
        $request = $this->insert($sql, $arrData);
        return $request;
    }

    // Actualizar permisos de un rol
    public function actualizarPermisos($idPermiso, $lectura, $escritura, $actualizar, $eliminar) {
        $sql = "UPDATE permisos 
                SET lectura = ?, escritura = ?, actualizar = ?, eliminar = ?, ultima_modificacion = UNIX_TIMESTAMP() 
                WHERE idpermiso = ?";
        $arrData = [$lectura, $escritura, $actualizar, $eliminar, $idPermiso];
        $request = $this->update($sql, $arrData);
        return $request;
    }

    // Eliminar permisos (cambiar estatus a inactivo)
    public function eliminarPermisos($idPermiso) {
        $sql = "UPDATE permisos 
                SET estatus = 'inactivo', ultima_modificacion = UNIX_TIMESTAMP() 
                WHERE idpermiso = ?";
        $arrData = [$idPermiso];
        $request = $this->update($sql, $arrData);
        return $request;
    }
}
?>