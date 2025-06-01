<?php
require_once "app/core/conexion.php";
require_once "app/core/mysql.php";

class ProveedoresModel extends mysql
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

    private function verificarProveedorExiste(string $identificacion, int $idProveedorExcluir = null): bool
    {
        $sql = "SELECT COUNT(*) as total FROM proveedor WHERE identificacion = ?";
        $params = [trim($identificacion)];
        
        if ($idProveedorExcluir !== null) {
            $sql .= " AND idproveedor != ?";
            $params[] = $idProveedorExcluir;
        }
        
        try {
            $stmt = $this->dbPrincipal->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] > 0;
        } catch (Exception $e) {
            error_log("Error al verificar proveedor existente: " . $e->getMessage());
            return true;
        }
    }

    public function insertProveedor(array $data): array
    {
        try {
            $identificacion = $data['identificacion'];

            if ($this->verificarProveedorExiste($identificacion)) {
                return [
                    'status' => false,
                    'message' => 'Ya existe un proveedor con esa identificación.',
                    'proveedor_id' => null
                ];
            }

            $this->dbPrincipal->beginTransaction();

            $sql = "INSERT INTO proveedor (
                        nombre, apellido, identificacion, fecha_nacimiento, 
                        direccion, correo_electronico, estatus, telefono_principal, 
                        observaciones, genero, fecha_cracion, fecha_modificacion
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $valores = [
                $data['nombre'],
                $data['apellido'],
                $data['identificacion'],
                !empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null,
                $data['direccion'],
                $data['correo_electronico'],
                'ACTIVO',
                $data['telefono_principal'],
                $data['observaciones'],
                $data['genero']
            ];
            
            $stmt = $this->dbPrincipal->prepare($sql);
            $insertExitoso = $stmt->execute($valores);

            $idProveedorInsertado = $this->dbPrincipal->lastInsertId();

            if (!$idProveedorInsertado) {
                $this->dbPrincipal->rollBack();
                error_log("Error: No se pudo obtener el lastInsertId para el proveedor.");
                return [
                    'status' => false, 
                    'message' => 'Error al obtener ID de proveedor tras registro.',
                    'proveedor_id' => null
                ];
            }

            $this->dbPrincipal->commit();

            return [
                'status' => true, 
                'message' => 'Proveedor registrado exitosamente.',
                'proveedor_id' => $idProveedorInsertado
            ];

        } catch (PDOException $e) {
            if ($this->dbPrincipal->inTransaction()) {
                $this->dbPrincipal->rollBack();
            }
            error_log("Error al insertar proveedor: " . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Error de base de datos al registrar proveedor: ' . $e->getMessage(),
                'proveedor_id' => null
            ];
        }
    }

    public function updateProveedor(int $idproveedor, array $data): array
    {
        try {
            $identificacion = $data['identificacion'];

            if ($this->verificarProveedorExiste($identificacion, $idproveedor)) {
                return [
                    'status' => false,
                    'message' => 'Ya existe otro proveedor con esa identificación.'
                ];
            }

            $this->dbPrincipal->beginTransaction();

            $sql = "UPDATE proveedor SET 
                        nombre = ?, apellido = ?, identificacion = ?, 
                        fecha_nacimiento = ?, direccion = ?, correo_electronico = ?, 
                        telefono_principal = ?, observaciones = ?, genero = ?, 
                        fecha_modificacion = NOW() 
                    WHERE idproveedor = ?";
            
            $valores = [
                $data['nombre'],
                $data['apellido'],
                $data['identificacion'],
                !empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null,
                $data['direccion'],
                $data['correo_electronico'],
                $data['telefono_principal'],
                $data['observaciones'],
                $data['genero'],
                $idproveedor
            ];
            
            $stmt = $this->dbPrincipal->prepare($sql);
            $updateExitoso = $stmt->execute($valores);

            if (!$updateExitoso || $stmt->rowCount() === 0) {
                $this->dbPrincipal->rollBack();
                return [
                    'status' => false, 
                    'message' => 'No se pudo actualizar el proveedor o no se realizaron cambios.'
                ];
            }

            $this->dbPrincipal->commit();

            return [
                'status' => true, 
                'message' => 'Proveedor actualizado exitosamente.'
            ];

        } catch (PDOException $e) {
            if ($this->dbPrincipal->inTransaction()) {
                $this->dbPrincipal->rollBack();
            }
            error_log("Error al actualizar proveedor: " . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Error de base de datos al actualizar proveedor: ' . $e->getMessage()
            ];
        }
    }

    public function selectProveedorById(int $idproveedor)
    {
        $sql = "SELECT 
                    idproveedor, nombre, apellido, identificacion, fecha_nacimiento,
                    direccion, correo_electronico, estatus, telefono_principal,
                    observaciones, genero, fecha_cracion, fecha_modificacion,
                    DATE_FORMAT(fecha_nacimiento, '%d/%m/%Y') as fecha_nacimiento_formato,
                    DATE_FORMAT(fecha_cracion, '%d/%m/%Y %H:%i') as fecha_creacion_formato,
                    DATE_FORMAT(fecha_modificacion, '%d/%m/%Y %H:%i') as fecha_modificacion_formato
                FROM proveedor 
                WHERE idproveedor = ?";
        try {
            $stmt = $this->dbPrincipal->prepare($sql);
            $stmt->execute([$idproveedor]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ProveedoresModel::selectProveedorById -> " . $e->getMessage());
            return false;
        }
    }

    public function deleteProveedorById(int $idproveedor): bool
    {
        try {
            $this->dbPrincipal->beginTransaction();

            $sql = "UPDATE proveedor SET estatus = 'INACTIVO', fecha_modificacion = NOW() WHERE idproveedor = ?";
            $stmt = $this->dbPrincipal->prepare($sql);
            $stmt->execute([$idproveedor]);
            
            $this->dbPrincipal->commit();
            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            $this->dbPrincipal->rollBack();
            error_log("ProveedoresModel::deleteProveedorById -> " . $e->getMessage());
            return false;
        }
    }

    public function selectAllProveedores()
    {
        $sql = "SELECT 
                    idproveedor, nombre, apellido, identificacion, fecha_nacimiento,
                    direccion, correo_electronico, estatus, telefono_principal,
                    observaciones, genero, fecha_cracion, fecha_modificacion,
                    DATE_FORMAT(fecha_nacimiento, '%d/%m/%Y') as fecha_nacimiento_formato,
                    DATE_FORMAT(fecha_cracion, '%d/%m/%Y') as fecha_creacion_formato,
                    DATE_FORMAT(fecha_modificacion, '%d/%m/%Y') as fecha_modificacion_formato
                FROM proveedor 
                ORDER BY nombre ASC, apellido ASC";

        try {
            $stmt = $this->dbPrincipal->query($sql);
            $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "message" => "Proveedores obtenidos.", "data" => $proveedores];
        } catch (PDOException $e) {
            error_log("ProveedoresModel::selectAllProveedores - Error al seleccionar proveedores: " . $e->getMessage());
            return ["status" => false, "message" => "Error al obtener proveedores: " . $e->getMessage(), "data" => []];
        }
    }

    public function selectProveedoresActivos()
    {
        $sql = "SELECT 
                    idproveedor, nombre, apellido, identificacion, telefono_principal,
                    CONCAT(nombre, ' ', apellido) as nombre_completo
                FROM proveedor 
                WHERE estatus = 'ACTIVO'
                ORDER BY nombre ASC, apellido ASC";

        try {
            $stmt = $this->dbPrincipal->query($sql);
            $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "message" => "Proveedores activos obtenidos.", "data" => $proveedores];
        } catch (PDOException $e) {
            error_log("ProveedoresModel::selectProveedoresActivos - Error: " . $e->getMessage());
            return ["status" => false, "message" => "Error al obtener proveedores activos: " . $e->getMessage(), "data" => []];
        }
    }
}
?>