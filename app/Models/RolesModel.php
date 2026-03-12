<?php
namespace App\Models;

use App\Core\Conexion;
use PDO;
use PDOException;

class RolesModel 
{
    const SUPER_USUARIO_ROL_ID = 1;
    
    private $objRolesModel = null;

    public function __construct()
    {
        // Constructor vacío. La conexión se gestiona por método.
    }

    /**
     * Obtiene la instancia interna del modelo (Lazy Load)
     */
    private function getInstanciaModel()
    {
        if ($this->objRolesModel == null) {
            $this->objRolesModel = new RolesModel();
        }
        return $this->objRolesModel;
    }

    private function esSuperUsuario(int $idusuario): bool
    {
        $conexion = new Conexion();
        try {
            $conexion->connect();
            $db = $conexion->get_conectSeguridad();
            $sql = "SELECT COUNT(*) as total FROM usuario WHERE idusuario = ? AND idrol = ? AND estatus = 'ACTIVO'";
            $stmt = $db->prepare($sql);
            $stmt->execute([$idusuario, self::SUPER_USUARIO_ROL_ID]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && $result['total'] > 0;
        } catch (Exception $e) {
            error_log("Error en RolesModel::esSuperUsuario -> " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarVerificacionNombreExistente(string $nombre, int $idrolExcluir = 0): bool
    {
        $conexion = new Conexion();
        try {
            $conexion->connect();
            $db = $conexion->get_conectSeguridad();
            $sql = "SELECT idrol FROM roles WHERE nombre = ? AND estatus = 'ACTIVO'";
            $params = [$nombre];
            if ($idrolExcluir > 0) {
                $sql .= " AND idrol != ?";
                $params[] = $idrolExcluir;
            }
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            error_log("Error en ejecutarVerificacionNombreExistente: " . $e->getMessage());
            return true; // Asumir que existe en caso de error para prevenir duplicados
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarVerificacionUsoRol(int $idrol): bool
    {
        $conexion = new Conexion();
        try {
            $conexion->connect();
            $db = $conexion->get_conectSeguridad();
            $sql = "SELECT COUNT(*) as count FROM usuario WHERE idrol = ? AND estatus = 'ACTIVO'";
            $stmt = $db->prepare($sql);
            $stmt->execute([$idrol]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Error en ejecutarVerificacionUsoRol: " . $e->getMessage());
            return true; // Asumir que está en uso en caso de error por seguridad
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarInsercionRol(array $data)
    {
        $conexion = new Conexion();
        try {
            $conexion->connect();
            $db = $conexion->get_conectSeguridad();
            $db->beginTransaction();
            $sql = "INSERT INTO roles (nombre, descripcion, estatus, fecha_creacion, ultima_modificacion) VALUES (?, ?, ?, NOW(), NOW())";
            $stmt = $db->prepare($sql);
            $stmt->execute([$data['nombre'], $data['descripcion'], $data['estatus']]);
            $idRolInsertado = $db->lastInsertId();
            $db->commit();
            return ['status' => true, 'message' => 'Rol registrado exitosamente.', 'rol_id' => $idRolInsertado];
        } catch (PDOException $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            error_log("Error en ejecutarInsercionRol: " . $e->getMessage());
            return ['status' => false, 'message' => 'Error de base de datos al registrar el rol: ' . $e->getMessage()];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarActualizacionRol(int $idrol, array $data)
    {
        $conexion = new Conexion();
        try {
            $conexion->connect();
            $db = $conexion->get_conectSeguridad();
            $sql = "UPDATE roles SET nombre = ?, descripcion = ?, estatus = ?, ultima_modificacion = NOW() WHERE idrol = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$data['nombre'], $data['descripcion'], $data['estatus'], $idrol]);
            
            if ($stmt->rowCount() > 0) {
                return ['status' => true, 'message' => 'Rol actualizado exitosamente.'];
            }
            return ['status' => true, 'message' => 'No se realizaron cambios (datos idénticos).'];
        } catch (PDOException $e) {
            error_log("Error en ejecutarActualizacionRol: " . $e->getMessage());
            return ['status' => false, 'message' => 'Error de base de datos al actualizar el rol: ' . $e->getMessage()];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarBusquedaRolPorId(int $idrol)
    {
        $conexion = new Conexion();
        try {
            $conexion->connect();
            $db = $conexion->get_conectSeguridad();
            $sql = "SELECT idrol, nombre, descripcion, estatus, 
                           DATE_FORMAT(fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion, 
                           DATE_FORMAT(ultima_modificacion, '%d/%m/%Y %H:%i') as ultima_modificacion 
                    FROM roles WHERE idrol = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$idrol]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("RolesModel::ejecutarBusquedaRolPorId -> " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarDesactivacionRol(int $idrol)
    {
        $conexion = new Conexion();
        try {
            $conexion->connect();
            $db = $conexion->get_conectSeguridad();
            $sql = "UPDATE roles SET estatus = 'INACTIVO', ultima_modificacion = NOW() WHERE idrol = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$idrol]);
            if ($stmt->rowCount() > 0) {
                return ['status' => true, 'message' => 'Rol desactivado correctamente.'];
            }
            return ['status' => false, 'message' => 'No se encontró el rol o ya estaba inactivo.'];
        } catch (PDOException $e) {
            error_log("RolesModel::ejecutarDesactivacionRol -> " . $e->getMessage());
            return ['status' => false, 'message' => 'Error de base de datos al desactivar el rol.'];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarBusquedaTodosRoles(bool $esSuperUsuario)
    {
        $conexion = new Conexion();
        try {
            $conexion->connect();
            $db = $conexion->get_conectSeguridad();
            $sql = "SELECT idrol, nombre, descripcion, estatus, 
                           DATE_FORMAT(fecha_creacion, '%d/%m/%Y') as fecha_creacion, 
                           DATE_FORMAT(ultima_modificacion, '%d/%m/%Y') as ultima_modificacion 
                    FROM roles";
            if (!$esSuperUsuario) {
                $sql .= " WHERE estatus = 'ACTIVO'";
            }
            $sql .= " ORDER BY nombre ASC";
            
            $stmt = $db->query($sql);
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "message" => "Roles obtenidos.", "data" => $roles];
        } catch (PDOException $e) {
            error_log("RolesModel::ejecutarBusquedaTodosRoles - Error: " . $e->getMessage());
            return ["status" => false, "message" => "Error al obtener roles.", "data" => []];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarReactivacionRol(int $idrol)
    {
        $conexion = new Conexion();
        try {
            $conexion->connect();
            $db = $conexion->get_conectSeguridad();
            $sql = "UPDATE roles SET estatus = 'ACTIVO', ultima_modificacion = NOW() WHERE idrol = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$idrol]);
            if ($stmt->rowCount() > 0) {
                return ['status' => true, 'message' => 'Rol reactivado correctamente.'];
            }
            return ['status' => false, 'message' => 'No se pudo reactivar el rol.'];
        } catch (PDOException $e) {
            error_log("RolesModel::ejecutarReactivacionRol -> " . $e->getMessage());
            return ['status' => false, 'message' => 'Error de base de datos al reactivar el rol.'];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarBusquedaRolesParaSelect()
    {
        $conexion = new Conexion();
        try {
            $conexion->connect();
            $db = $conexion->get_conectSeguridad();
            $sql = "SELECT idrol, nombre FROM roles WHERE estatus = 'ACTIVO' ORDER BY nombre ASC";
            $stmt = $db->query($sql);
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "message" => "Roles obtenidos.", "data" => $roles];
        } catch (PDOException $e) {
            error_log("RolesModel::ejecutarBusquedaRolesParaSelect - Error: " . $e->getMessage());
            return ["status" => false, "message" => "Error al obtener roles.", "data" => []];
        } finally {
            $conexion->disconnect();
        }
    }

    public function insertRol(array $data)
    {
        $objRolesModel = $this->getInstanciaModel();
        if ($objRolesModel->ejecutarVerificacionNombreExistente($data['nombre'])) {
            return ['status' => false, 'message' => 'Ya existe un rol activo con ese nombre.'];
        }
        return $objRolesModel->ejecutarInsercionRol($data);
    }

    public function updateRol(int $idrol, array $data): array
    {
        $objRolesModel = $this->getInstanciaModel();
        if ($objRolesModel->ejecutarVerificacionNombreExistente($data['nombre'], $idrol)) {
            return ['status' => false, 'message' => 'Ya existe otro rol activo con ese nombre.'];
        }
        return $objRolesModel->ejecutarActualizacionRol($idrol, $data);
    }

    public function selectRolById(int $idrol)
    {
        $objRolesModel = $this->getInstanciaModel();
        return $objRolesModel->ejecutarBusquedaRolPorId($idrol);
    }

    public function deleteRolById(int $idrol): array
    {
        $objRolesModel = $this->getInstanciaModel();
        if ($objRolesModel->ejecutarVerificacionUsoRol($idrol)) {
            return ['status' => false, 'message' => 'No se puede desactivar el rol porque está siendo usado por usuarios activos.'];
        }
        return $objRolesModel->ejecutarDesactivacionRol($idrol);
    }

    public function selectAllRoles(int $idUsuarioSesion): array
    {
        $objRolesModel = $this->getInstanciaModel();
        $esSuperUsuario = $objRolesModel->verificarEsSuperUsuario($idUsuarioSesion);
        return $objRolesModel->ejecutarBusquedaTodosRoles($esSuperUsuario);
    }

    public function reactivarRol(int $idrol): array
    {
        $objRolesModel = $this->getInstanciaModel();
        $rol = $objRolesModel->selectRolById($idrol);
        if (!$rol) {
            return ['status' => false, 'message' => 'El rol no existe.'];
        }
        if ($rol['estatus'] === 'ACTIVO') {
            return ['status' => false, 'message' => 'El rol ya se encuentra activo.'];
        }
        return $objRolesModel->ejecutarReactivacionRol($idrol);
    }

    public function verificarEsSuperUsuario(int $idusuario): bool
    {
        $objRolesModel = $this->getInstanciaModel();
        return $objRolesModel->esSuperUsuario($idusuario);
    }

    public function selectAllRolesForSelect()
    {
        $objRolesModel = $this->getInstanciaModel();
        return $objRolesModel->ejecutarBusquedaRolesParaSelect();
    }
}
?>