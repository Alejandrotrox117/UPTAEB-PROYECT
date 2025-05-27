<?php
require_once "app/core/conexion.php";
require_once "app/core/mysql.php";

class RolesModel extends mysql
{
    private $conexion;
    private $dbSeguridad;

    public function __construct()
    {
        $this->conexion = new Conexion();
        $this->conexion->connect();
        $this->dbSeguridad = $this->conexion->get_conectSeguridad();
    }

    public function insertRol(array $data): array
    {
        try {
            $this->dbSeguridad->beginTransaction();

            // Verificar si ya existe un rol con el mismo nombre
            $sqlCheck = "SELECT idrol FROM roles WHERE nombre = ? AND estatus = 'ACTIVO'";
            $stmtCheck = $this->dbSeguridad->prepare($sqlCheck);
            $stmtCheck->execute([$data['nombre']]);
            
            if ($stmtCheck->fetch()) {
                $this->dbSeguridad->rollBack();
                return [
                    'status' => false, 
                    'message' => 'Ya existe un rol activo con ese nombre.',
                ];
            }

            $sqlRol = "INSERT INTO roles (nombre, descripcion, estatus, fecha_creacion, ultima_modificacion) VALUES (?, ?, ?, NOW(), NOW())";
            
            $valoresRol = [
                $data['nombre'],
                $data['descripcion'],
                $data['estatus']
            ];
            
            $stmtRol = $this->dbSeguridad->prepare($sqlRol);
            $insertExitoso = $stmtRol->execute($valoresRol);

            $idRolInsertado = $this->dbSeguridad->lastInsertId();

            if (!$idRolInsertado) {
                $this->dbSeguridad->rollBack();
                error_log("Error: No se pudo obtener el lastInsertId para el rol.");
                return [
                    'status' => false, 
                    'message' => 'Error al obtener ID de rol tras registro.',
                ];
            }

            $this->dbSeguridad->commit();

            return [
                'status' => true, 
                'message' => 'Rol registrado exitosamente.',
                'rol_id' => $idRolInsertado
            ];

        } catch (PDOException $e) {
            if ($this->dbSeguridad->inTransaction()) {
                $this->dbSeguridad->rollBack();
            }
            error_log("Error al insertar rol: " . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Error de base de datos al registrar rol: ' . $e->getMessage(),
            ];
        }
    }

    public function updateRol(int $idrol, array $data): array
    {
        try {
            $this->dbSeguridad->beginTransaction();

            // Verificar si ya existe otro rol con el mismo nombre
            $sqlCheck = "SELECT idrol FROM roles WHERE nombre = ? AND estatus = 'ACTIVO' AND idrol != ?";
            $stmtCheck = $this->dbSeguridad->prepare($sqlCheck);
            $stmtCheck->execute([$data['nombre'], $idrol]);
            
            if ($stmtCheck->fetch()) {
                $this->dbSeguridad->rollBack();
                return [
                    'status' => false, 
                    'message' => 'Ya existe otro rol activo con ese nombre.',
                ];
            }

            $sql = "UPDATE roles SET 
                    nombre = ?, 
                    descripcion = ?, 
                    estatus = ?, 
                    ultima_modificacion = NOW() 
                    WHERE idrol = ?";
            
            $valores = [
                $data['nombre'],
                $data['descripcion'],
                $data['estatus'],
                $idrol
            ];
            
            $stmt = $this->dbSeguridad->prepare($sql);
            $updateExitoso = $stmt->execute($valores);

            if (!$updateExitoso || $stmt->rowCount() === 0) {
                $this->dbSeguridad->rollBack();
                return [
                    'status' => false, 
                    'message' => 'No se pudo actualizar el rol o no se realizaron cambios.'
                ];
            }

            $this->dbSeguridad->commit();

            return [
                'status' => true, 
                'message' => 'Rol actualizado exitosamente.'
            ];

        } catch (PDOException $e) {
            if ($this->dbSeguridad->inTransaction()) {
                $this->dbSeguridad->rollBack();
            }
            error_log("Error al actualizar rol: " . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Error de base de datos al actualizar rol: ' . $e->getMessage()
            ];
        }
    }

    public function selectRolById(int $idrol)
    {
        $sql = "SELECT idrol, nombre, descripcion, estatus, fecha_creacion, ultima_modificacion 
                FROM roles 
                WHERE idrol = ?";
        try {
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute([$idrol]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("RolesModel::selectRolById -> " . $e->getMessage());
            return false;
        }
    }

    public function deleteRolById(int $idrol): array
    {
        if (!$this->dbSeguridad) {
            error_log("RolesModel::deleteRolById -> Conexión a la base de datos no establecida.");
            return ['status' => false, 'message' => 'Error de conexión a la base de datos.'];
        }
        
        try {
            $this->dbSeguridad->beginTransaction();

            // Verificar si el rol está siendo usado por usuarios
            $sqlCheckUsers = "SELECT COUNT(*) as count FROM usuario WHERE idrol = ? AND estatus = 'ACTIVO'";
            $stmtCheckUsers = $this->dbSeguridad->prepare($sqlCheckUsers);
            $stmtCheckUsers->execute([$idrol]);
            $userCount = $stmtCheckUsers->fetch(PDO::FETCH_ASSOC);

            if ($userCount['count'] > 0) {
                $this->dbSeguridad->rollBack();
                return [
                    'status' => false, 
                    'message' => 'No se puede eliminar el rol porque está siendo usado por usuarios activos.'
                ];
            }

            $sql = "UPDATE roles SET estatus = 'INACTIVO', ultima_modificacion = NOW() WHERE idrol = ?";
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute([$idrol]);
            
            if ($stmt->rowCount() > 0) {
                $this->dbSeguridad->commit();
                return ['status' => true, 'message' => 'Rol desactivado correctamente.'];
            } else {
                $this->dbSeguridad->rollBack();
                return ['status' => false, 'message' => 'No se encontró el rol o no se pudo desactivar.'];
            }

        } catch (PDOException $e) {
            $this->dbSeguridad->rollBack();
            error_log("RolesModel::deleteRolById -> " . $e->getMessage());
            return ['status' => false, 'message' => 'Error de base de datos al desactivar el rol.'];
        }
    }

    public function selectAllRolesActivos()
    {
        $sql = "SELECT idrol, nombre, descripcion, estatus, fecha_creacion, ultima_modificacion 
                FROM roles 
                WHERE estatus = 'ACTIVO' 
                ORDER BY nombre ASC";

        try {
            $stmt = $this->dbSeguridad->query($sql);
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "message" => "Roles obtenidos.", "data" => $roles];
        } catch (PDOException $e) {
            error_log("RolesModel::selectAllRolesActivos - Error al seleccionar roles: " . $e->getMessage());
            return ["status" => false, "message" => "Error al obtener roles: " . $e->getMessage(), "data" => []];
        }
    }

    public function selectAllRolesForSelect()
    {
        $sql = "SELECT idrol, nombre FROM roles WHERE estatus = 'ACTIVO' ORDER BY nombre ASC";
        
        try {
            $stmt = $this->dbSeguridad->query($sql);
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "message" => "Roles obtenidos.", "data" => $roles];
        } catch (PDOException $e) {
            error_log("RolesModel::selectAllRolesForSelect - Error al seleccionar roles: " . $e->getMessage());
            return ["status" => false, "message" => "Error al obtener roles: " . $e->getMessage(), "data" => []];
        }
    }
}
?>
