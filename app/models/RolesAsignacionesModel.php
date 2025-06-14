<?php
require_once "app/core/conexion.php";
require_once "app/core/mysql.php";

class RolesAsignacionesModel extends mysql
{
    private $conexion;
    private $dbPrincipal;
    private $dbSeguridad;

    public function __construct()
    {
        $this->conexion = new Conexion();
        $this->conexion->connect();
        $this->dbPrincipal = $this->conexion->get_conectGeneral();
        $this->dbSeguridad = $this->conexion->get_conectSeguridad();
    }

    // ==================== MÉTODOS PARA ROLES ====================
    public function selectAllRolesActivos()
    {
        $sql = "SELECT idrol, nombre, descripcion, estatus 
                FROM roles 
                WHERE estatus = 'activo' 
                ORDER BY nombre ASC";

        try {
            $stmt = $this->dbSeguridad->query($sql);
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "message" => "Roles obtenidos.", "data" => $roles];
        } catch (PDOException $e) {
            error_log("RolesAsignacionesModel::selectAllRolesActivos - Error: " . $e->getMessage());
            return ["status" => false, "message" => "Error al obtener roles: " . $e->getMessage(), "data" => []];
        }
    }

    // ==================== MÉTODOS PARA MÓDULOS ====================
    public function selectAllModulosActivos()
    {
        $sql = "SELECT idmodulo, titulo as nombre_modulo, descripcion 
                FROM modulos 
                WHERE estatus = 'activo' 
                ORDER BY titulo ASC";

        try {
            $stmt = $this->dbSeguridad->query($sql);
            $modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "message" => "Módulos obtenidos.", "data" => $modulos];
        } catch (PDOException $e) {
            error_log("RolesAsignacionesModel::selectAllModulosActivos - Error: " . $e->getMessage());
            return ["status" => false, "message" => "Error al obtener módulos: " . $e->getMessage(), "data" => []];
        }
    }

    public function selectModulosByRol(int $idrol)
    {
        $sql = "SELECT 
                    rm.idrolmodulo,
                    m.idmodulo,
                    m.titulo as nombre_modulo,
                    m.descripcion
                FROM rol_modulo rm
                INNER JOIN modulos m ON rm.idmodulo = m.idmodulo
                WHERE rm.idrol = ? AND m.estatus = 'activo'
                ORDER BY m.titulo ASC";

        try {
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute([$idrol]);
            $modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "message" => "Módulos del rol obtenidos.", "data" => $modulos];
        } catch (PDOException $e) {
            error_log("RolesAsignacionesModel::selectModulosByRol - Error: " . $e->getMessage());
            return ["status" => false, "message" => "Error al obtener módulos del rol: " . $e->getMessage(), "data" => []];
        }
    }

    // ==================== MÉTODOS PARA PERMISOS ====================
    public function selectAllPermisos()
    {
        $sql = "SELECT idpermiso, nombre_permiso 
                FROM permisos 
                ORDER BY nombre_permiso ASC";

        try {
            $stmt = $this->dbSeguridad->query($sql);
            $permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "message" => "Permisos obtenidos.", "data" => $permisos];
        } catch (PDOException $e) {
            error_log("RolesAsignacionesModel::selectAllPermisos - Error: " . $e->getMessage());
            return ["status" => false, "message" => "Error al obtener permisos: " . $e->getMessage(), "data" => []];
        }
    }

    public function selectPermisosByRol(int $idrol)
    {
        $sql = "SELECT 
                    rp.idrolpermiso,
                    p.idpermiso,
                    p.nombre_permiso
                FROM rol_permiso rp
                INNER JOIN permisos p ON rp.idpermiso = p.idpermiso
                WHERE rp.idrol = ?
                ORDER BY p.nombre_permiso ASC";

        try {
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute([$idrol]);
            $permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "message" => "Permisos del rol obtenidos.", "data" => $permisos];
        } catch (PDOException $e) {
            error_log("RolesAsignacionesModel::selectPermisosByRol - Error: " . $e->getMessage());
            return ["status" => false, "message" => "Error al obtener permisos del rol: " . $e->getMessage(), "data" => []];
        }
    }

    // ==================== ASIGNACIÓN MASIVA ====================
    public function asignarModulosYPermisos(int $idrol, array $modulos, array $permisos): array
    {
        try {
            $this->dbSeguridad->beginTransaction();

            // Eliminar asignaciones existentes
            $this->eliminarAsignacionesExistentes($idrol);

            // Asignar nuevos módulos
            if (!empty($modulos)) {
                foreach ($modulos as $idmodulo) {
                    $this->insertarRolModulo($idrol, $idmodulo);
                }
            }

            // Asignar nuevos permisos
            if (!empty($permisos)) {
                foreach ($permisos as $idpermiso) {
                    $this->insertarRolPermiso($idrol, $idpermiso);
                }
            }

            $this->dbSeguridad->commit();

            return [
                'status' => true,
                'message' => 'Asignaciones actualizadas correctamente.',
                'modulos_asignados' => count($modulos),
                'permisos_asignados' => count($permisos)
            ];

        } catch (PDOException $e) {
            if ($this->dbSeguridad->inTransaction()) {
                $this->dbSeguridad->rollBack();
            }
            error_log("Error en asignarModulosYPermisos: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al actualizar asignaciones: ' . $e->getMessage()
            ];
        }
    }

    private function eliminarAsignacionesExistentes(int $idrol): void
    {
        // Eliminar módulos existentes del rol
        $sqlModulos = "DELETE FROM rol_modulo WHERE idrol = ?";
        $stmtModulos = $this->dbSeguridad->prepare($sqlModulos);
        $stmtModulos->execute([$idrol]);

        // Eliminar permisos existentes del rol
        $sqlPermisos = "DELETE FROM rol_permiso WHERE idrol = ?";
        $stmtPermisos = $this->dbSeguridad->prepare($sqlPermisos);
        $stmtPermisos->execute([$idrol]);
    }

    private function insertarRolModulo(int $idrol, int $idmodulo): void
    {
        $sql = "INSERT INTO rol_modulo (idrol, idmodulo) VALUES (?, ?)";
        $stmt = $this->dbSeguridad->prepare($sql);
        $stmt->execute([$idrol, $idmodulo]);
    }

    private function insertarRolPermiso(int $idrol, int $idpermiso): void
    {
        $sql = "INSERT INTO rol_permiso (idrol, idpermiso) VALUES (?, ?)";
        $stmt = $this->dbSeguridad->prepare($sql);
        $stmt->execute([$idrol, $idpermiso]);
    }

    // ==================== CONSULTA COMPLETA DE ASIGNACIONES ====================
    public function selectAsignacionesCompletas(int $idrol)
    {
        $resultado = [
            'rol' => $this->selectRolById($idrol),
            'modulos' => $this->selectModulosByRol($idrol),
            'permisos' => $this->selectPermisosByRol($idrol)
        ];

        return [
            'status' => true,
            'message' => 'Asignaciones obtenidas correctamente.',
            'data' => $resultado
        ];
    }

    private function selectRolById(int $idrol)
    {
        $sql = "SELECT idrol, nombre, descripcion, estatus FROM roles WHERE idrol = ?";
        try {
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute([$idrol]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener rol por ID: " . $e->getMessage());
            return null;
        }
    }
}
?>