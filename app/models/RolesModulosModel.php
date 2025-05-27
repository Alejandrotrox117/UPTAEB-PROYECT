<?php
require_once "app/core/conexion.php";
require_once "app/core/mysql.php";

class RolesModulosModel extends mysql
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

    private function verificarRolModuloExiste(int $idrol, int $idmodulo, int $idRolModuloExcluir = null): bool
    {
        $sql = "SELECT COUNT(*) as total FROM rol_modulo WHERE idrol = ? AND idmodulo = ?";
        $params = [$idrol, $idmodulo];
        
        if ($idRolModuloExcluir !== null) {
            $sql .= " AND idrolmodulo != ?";
            $params[] = $idRolModuloExcluir;
        }
        
        try {
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] > 0;
        } catch (Exception $e) {
            error_log("Error al verificar rol-módulo existente: " . $e->getMessage());
            return true;
        }
    }

    public function insertRolModulo(array $data): array
    {
        try {
            $idrol = $data['idrol'];
            $idmodulo = $data['idmodulo'];

            if ($this->verificarRolModuloExiste($idrol, $idmodulo)) {
                return [
                    'status' => false,
                    'message' => 'Ya existe una asignación para este rol y módulo.',
                    'rolmodulo_id' => null
                ];
            }

            $this->dbSeguridad->beginTransaction();

            $sql = "INSERT INTO rol_modulo (idrol, idmodulo) VALUES (?, ?)";
            
            $valores = [
                $idrol,
                $idmodulo
            ];
            
            $stmt = $this->dbSeguridad->prepare($sql);
            $insertExitoso = $stmt->execute($valores);

            $idRolModuloInsertado = $this->dbSeguridad->lastInsertId();

            if (!$idRolModuloInsertado) {
                $this->dbSeguridad->rollBack();
                error_log("Error: No se pudo obtener el lastInsertId para rol-módulo.");
                return [
                    'status' => false, 
                    'message' => 'Error al obtener ID de asignación tras registro.',
                    'rolmodulo_id' => null
                ];
            }

            $this->dbSeguridad->commit();

            return [
                'status' => true, 
                'message' => 'Asignación registrada exitosamente (ID: ' . $idRolModuloInsertado . ').',
                'rolmodulo_id' => $idRolModuloInsertado
            ];

        } catch (PDOException $e) {
            if ($this->dbSeguridad->inTransaction()) {
                $this->dbSeguridad->rollBack();
            }
            error_log("Error al insertar rol-módulo: " . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Error de base de datos al registrar asignación: ' . $e->getMessage(),
                'rolmodulo_id' => null
            ];
        }
    }

    public function updateRolModulo(int $idrolmodulo, array $data): array
    {
        try {
            $idrol = $data['idrol'];
            $idmodulo = $data['idmodulo'];

            if ($this->verificarRolModuloExiste($idrol, $idmodulo, $idrolmodulo)) {
                return [
                    'status' => false,
                    'message' => 'Ya existe otra asignación para este rol y módulo.'
                ];
            }

            $this->dbSeguridad->beginTransaction();

            $sql = "UPDATE rol_modulo SET idrol = ?, idmodulo = ? WHERE idrolmodulo = ?";
            
            $valores = [
                $idrol,
                $idmodulo,
                $idrolmodulo
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
            error_log("Error al actualizar rol-módulo: " . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Error de base de datos al actualizar asignación: ' . $e->getMessage()
            ];
        }
    }

    public function selectRolModuloById(int $idrolmodulo)
    {
        $sql = "SELECT 
                    rm.idrolmodulo,
                    rm.idrol,
                    rm.idmodulo,
                    r.nombre as nombre_rol,
                    r.descripcion as descripcion_rol,
                    m.titulo as titulo_modulo,
                    m.descripcion as descripcion_modulo
                FROM rol_modulo rm
                INNER JOIN roles r ON rm.idrol = r.idrol
                INNER JOIN modulos m ON rm.idmodulo = m.idmodulo
                WHERE rm.idrolmodulo = ?";
        try {
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute([$idrolmodulo]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("RolesModulosModel::selectRolModuloById -> " . $e->getMessage());
            return false;
        }
    }

    public function deleteRolModuloById(int $idrolmodulo): bool
    {
        try {
            $this->dbSeguridad->beginTransaction();

            $sql = "DELETE FROM rol_modulo WHERE idrolmodulo = ?";
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute([$idrolmodulo]);
            
            $this->dbSeguridad->commit();
            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            $this->dbSeguridad->rollBack();
            error_log("RolesModulosModel::deleteRolModuloById -> " . $e->getMessage());
            return false;
        }
    }

    public function selectAllRolesModulosActivos()
    {
        $sql = "SELECT 
                    rm.idrolmodulo,
                    rm.idrol,
                    rm.idmodulo,
                    r.nombre as nombre_rol,
                    r.descripcion as descripcion_rol,
                    r.estatus as estatus_rol,
                    m.titulo as titulo_modulo,
                    m.descripcion as descripcion_modulo,
                    m.estatus as estatus_modulo
                FROM rol_modulo rm
                INNER JOIN roles r ON rm.idrol = r.idrol
                INNER JOIN modulos m ON rm.idmodulo = m.idmodulo
                WHERE r.estatus = 'ACTIVO' AND m.estatus = 'ACTIVO'
                ORDER BY r.nombre ASC, m.titulo ASC";

        try {
            $stmt = $this->dbSeguridad->query($sql);
            $rolesModulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "message" => "Asignaciones obtenidas.", "data" => $rolesModulos];
        } catch (PDOException $e) {
            error_log("RolesModulosModel::selectAllRolesModulosActivos - Error al seleccionar asignaciones: " . $e->getMessage());
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
            error_log("RolesModulosModel::selectAllRolesActivos - Error al seleccionar roles: " . $e->getMessage());
            return ["status" => false, "message" => "Error al obtener roles: " . $e->getMessage(), "data" => []];
        }
    }

    public function selectAllModulosActivos()
    {
        $sql = "SELECT 
                    idmodulo,
                    titulo,
                    descripcion,
                    estatus
                FROM modulos 
                WHERE estatus = 'ACTIVO'
                ORDER BY titulo ASC";

        try {
            $stmt = $this->dbSeguridad->query($sql);
            $modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "message" => "Módulos obtenidos.", "data" => $modulos];
        } catch (PDOException $e) {
            error_log("RolesModulosModel::selectAllModulosActivos - Error al seleccionar módulos: " . $e->getMessage());
            return ["status" => false, "message" => "Error al obtener módulos: " . $e->getMessage(), "data" => []];
        }
    }
}
?>
