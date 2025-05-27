<?php
require_once "app/core/conexion.php";
require_once "app/core/mysql.php";

class RolesPermisosModel extends mysql
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

    private function verificarRolPermisoExiste(int $idrol, int $idpermiso, int $idRolPermisoExcluir = null): bool
    {
        $sql = "SELECT COUNT(*) as total FROM rol_permiso WHERE idrol = ? AND idpermiso = ?";
        $params = [$idrol, $idpermiso];
        
        if ($idRolPermisoExcluir !== null) {
            $sql .= " AND idrolpermiso != ?";
            $params[] = $idRolPermisoExcluir;
        }
        
        try {
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] > 0;
        } catch (Exception $e) {
            error_log("Error al verificar rol-permiso existente: " . $e->getMessage());
            return true;
        }
    }

    public function insertRolPermiso(array $data): array
    {
        try {
            $idrol = $data['idrol'];
            $idpermiso = $data['idpermiso'];

            if ($this->verificarRolPermisoExiste($idrol, $idpermiso)) {
                return [
                    'status' => false,
                    'message' => 'Ya existe una asignación para este rol y permiso.',
                    'rolpermiso_id' => null
                ];
            }

            $this->dbSeguridad->beginTransaction();

            $sql = "INSERT INTO rol_permiso (idrol, idpermiso) VALUES (?, ?)";
            
            $valores = [
                $idrol,
                $idpermiso
            ];
            
            $stmt = $this->dbSeguridad->prepare($sql);
            $insertExitoso = $stmt->execute($valores);

            $idRolPermisoInsertado = $this->dbSeguridad->lastInsertId();

            if (!$idRolPermisoInsertado) {
                $this->dbSeguridad->rollBack();
                error_log("Error: No se pudo obtener el lastInsertId para rol-permiso.");
                return [
                    'status' => false, 
                    'message' => 'Error al obtener ID de asignación tras registro.',
                    'rolpermiso_id' => null
                ];
            }

            $this->dbSeguridad->commit();

            return [
                'status' => true, 
                'message' => 'Asignación registrada exitosamente (ID: ' . $idRolPermisoInsertado . ').',
                'rolpermiso_id' => $idRolPermisoInsertado
            ];

        } catch (PDOException $e) {
            if ($this->dbSeguridad->inTransaction()) {
                $this->dbSeguridad->rollBack();
            }
            error_log("Error al insertar rol-permiso: " . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Error de base de datos al registrar asignación: ' . $e->getMessage(),
                'rolpermiso_id' => null
            ];
        }
    }

    public function updateRolPermiso(int $idrolpermiso, array $data): array
    {
        try {
            $idrol = $data['idrol'];
            $idpermiso = $data['idpermiso'];

            if ($this->verificarRolPermisoExiste($idrol, $idpermiso, $idrolpermiso)) {
                return [
                    'status' => false,
                    'message' => 'Ya existe otra asignación para este rol y permiso.'
                ];
            }

            $this->dbSeguridad->beginTransaction();

            $sql = "UPDATE rol_permiso SET idrol = ?, idpermiso = ? WHERE idrolpermiso = ?";
            
            $valores = [
                $idrol,
                $idpermiso,
                $idrolpermiso
            ];
            
            $stmt = $this->dbSeguridad->prepare($sql);
            $updateExitoso = $stmt->execute($valores);

            if (!$updateExitoso || $stmt->rowCount() === 0) {
                $this->dbSeguridad->rollBack();
                return [
                    'status' => false, 
                    'message' => 'No se pudo actualizar la asignación o no se realizaron cambios.'
                ];
            }

            $this->dbSeguridad->commit();

            return [
                'status' => true, 
                'message' => 'Asignación actualizada exitosamente.'
            ];

        } catch (PDOException $e) {
            if ($this->dbSeguridad->inTransaction()) {
                $this->dbSeguridad->rollBack();
            }
            error_log("Error al actualizar rol-permiso: " . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Error de base de datos al actualizar asignación: ' . $e->getMessage()
            ];
        }
    }

    public function selectRolPermisoById(int $idrolpermiso)
    {
        $sql = "SELECT 
                    rp.idrolpermiso,
                    rp.idrol,
                    rp.idpermiso,
                    r.nombre as nombre_rol,
                    r.descripcion as descripcion_rol,
                    p.nombre_permiso
                FROM rol_permiso rp
                INNER JOIN roles r ON rp.idrol = r.idrol
                INNER JOIN permisos p ON rp.idpermiso = p.idpermiso
                WHERE rp.idrolpermiso = ?";
        try {
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute([$idrolpermiso]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("RolesPermisosModel::selectRolPermisoById -> " . $e->getMessage());
            return false;
        }
    }

    public function deleteRolPermisoById(int $idrolpermiso): bool
    {
        try {
            $this->dbSeguridad->beginTransaction();

            $sql = "DELETE FROM rol_permiso WHERE idrolpermiso = ?";
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute([$idrolpermiso]);
            
            $this->dbSeguridad->commit();
            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            $this->dbSeguridad->rollBack();
            error_log("RolesPermisosModel::deleteRolPermisoById -> " . $e->getMessage());
            return false;
        }
    }

    public function selectAllRolesPermisosActivos()
    {
        $sql = "SELECT 
                    rp.idrolpermiso,
                    rp.idrol,
                    rp.idpermiso,
                    r.nombre as nombre_rol,
                    r.descripcion as descripcion_rol,
                    r.estatus as estatus_rol,
                    p.nombre_permiso
                FROM rol_permiso rp
                INNER JOIN roles r ON rp.idrol = r.idrol
                INNER JOIN permisos p ON rp.idpermiso = p.idpermiso
                WHERE r.estatus = 'ACTIVO'
                ORDER BY r.nombre ASC, p.nombre_permiso ASC";

        try {
            $stmt = $this->dbSeguridad->query($sql);
            $rolesPermisos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "message" => "Asignaciones obtenidas.", "data" => $rolesPermisos];
        } catch (PDOException $e) {
            error_log("RolesPermisosModel::selectAllRolesPermisosActivos - Error al seleccionar asignaciones: " . $e->getMessage());
            return ["status" => false, "message" => "Error al obtener asignaciones: " . $e->getMessage(), "data" => []];
        }
    }

    public function selectAllRolesActivos()
    {
        $sql = "SELECT 
                    idrol,
                    nombre,
                    descripcion,
                    estatus
                FROM roles 
                WHERE estatus = 'ACTIVO'
                ORDER BY nombre ASC";

        try {
            $stmt = $this->dbSeguridad->query($sql);
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "message" => "Roles obtenidos.", "data" => $roles];
        } catch (PDOException $e) {
            error_log("RolesPermisosModel::selectAllRolesActivos - Error al seleccionar roles: " . $e->getMessage());
            return ["status" => false, "message" => "Error al obtener roles: " . $e->getMessage(), "data" => []];
        }
    }

    public function selectAllPermisos()
    {
        $sql = "SELECT 
                    idpermiso,
                    nombre_permiso
                FROM permisos 
                ORDER BY nombre_permiso ASC";

        try {
            $stmt = $this->dbSeguridad->query($sql);
            $permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "message" => "Permisos obtenidos.", "data" => $permisos];
        } catch (PDOException $e) {
            error_log("RolesPermisosModel::selectAllPermisos - Error al seleccionar permisos: " . $e->getMessage());
            return ["status" => false, "message" => "Error al obtener permisos: " . $e->getMessage(), "data" => []];
        }
    }
}
?>
