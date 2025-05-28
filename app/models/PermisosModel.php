<?php
// app/Models/PermisosModel.php
require_once "app/core/conexion.php";
require_once "app/core/mysql.php";

class PermisosModel extends mysql
{
    private $conexion;
    private $dbSeguridad;

    public function __construct()
    {
        $this->conexion = new Conexion();
        $this->conexion->connect();
        $this->dbSeguridad = $this->conexion->get_conectSeguridad();
    }

    /**
     * Obtiene los permisos de un usuario para un módulo específico
     */
    public function getPermisosUsuarioModulo(int $idUsuario, string $nombreModulo): array
    {
        $sql = "SELECT 
                    p.idpermiso,
                    p.nombre_permiso,
                    m.idmodulo,
                    m.titulo as nombre_modulo
                FROM usuario u
                INNER JOIN roles r ON u.idrol = r.idrol
                INNER JOIN rol_modulo rm ON r.idrol = rm.idrol
                INNER JOIN modulos m ON rm.idmodulo = m.idmodulo
                INNER JOIN rol_permiso rp ON r.idrol = rp.idrol
                INNER JOIN permisos p ON rp.idpermiso = p.idpermiso
                WHERE u.idusuario = ? 
                AND LOWER(m.titulo) = LOWER(?)
                AND u.estatus = 'activo'
                AND r.estatus = 'activo'
                AND m.estatus = 'activo'";

        try {
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute([$idUsuario, $nombreModulo]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return [
                    'tiene_acceso' => true,
                    'permiso_id' => $result['idpermiso'],
                    'permiso_nombre' => $result['nombre_permiso'],
                    'modulo_id' => $result['idmodulo'],
                    'modulo_nombre' => $result['nombre_modulo']
                ];
            }
            
            return ['tiene_acceso' => false];
        } catch (Exception $e) {
            error_log("Error al obtener permisos: " . $e->getMessage());
            return ['tiene_acceso' => false];
        }
    }

   
    public function verificarPermisoEspecifico(int $idUsuario, string $nombreModulo, array $permisosRequeridos): bool
    {
        $permisos = $this->getPermisosUsuarioModulo($idUsuario, $nombreModulo);
        
        if (!$permisos['tiene_acceso']) {
            return false;
        }

        return in_array($permisos['permiso_id'], $permisosRequeridos);
    }

  
    public function getModulosUsuario(int $idUsuario): array
    {
        $sql = "SELECT DISTINCT
                    m.idmodulo,
                    m.titulo,
                    m.descripcion,
                    p.idpermiso,
                    p.nombre_permiso
                FROM usuario u
                INNER JOIN roles r ON u.idrol = r.idrol
                INNER JOIN rol_modulo rm ON r.idrol = rm.idrol
                INNER JOIN modulos m ON rm.idmodulo = m.idmodulo
                INNER JOIN rol_permiso rp ON r.idrol = rp.idrol
                INNER JOIN permisos p ON rp.idpermiso = p.idpermiso
                WHERE u.idusuario = ?
                AND u.estatus = 'activo'
                AND r.estatus = 'activo'
                AND m.estatus = 'activo'
                ORDER BY m.titulo ASC";

        try {
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute([$idUsuario]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener módulos de usuario: " . $e->getMessage());
            return [];
        }
    }
}
?>
