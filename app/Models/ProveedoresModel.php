<?php
namespace App\Models;

use App\Core\Conexion;
use PDO;
use Exception;

class ProveedoresModel
{
    private $proveedorId;
    private $message;
    private $status;

    // Propiedad privada para manejar la instancia interna
    private $objModelProveedor;

    // Definir constante para el rol de super usuario
    const SUPER_USUARIO_ROL_ID = 1;

    public function __construct()
    {
        // Constructor vacío
    }

    // Método para obtener la instancia interna (una sola vez por objeto)
    private function getInstanciaModel()
    {
        if ($this->objModelProveedor == null) {
            $this->objModelProveedor = new ProveedoresModel();
        }
        return $this->objModelProveedor;
    }

    // Getters y Setters
    public function getProveedorId()
    {
        return $this->proveedorId;
    }

    public function setProveedorId(?int $proveedorId)
    {
        $this->proveedorId = $proveedorId;
    }

    public function getMessage()
    {
        return $this->message ?? '';
    }

    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    public function getStatus()
    {
        return $this->status ?? false;
    }

    public function setStatus(bool $status)
    {
        $this->status = $status;
    }

    // --- MÉTODOS PRIVADOS DE LÓGICA (TRABAJADORES) ---

    private function ejecutarVerificacionProveedor(string $identificacion, ?int $idProveedorExcluir = null)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        $exists = false;

        try {
            $query = "SELECT COUNT(*) as total FROM proveedor WHERE identificacion = ?";
            if ($idProveedorExcluir !== null) {
                $query .= " AND idproveedor != ?";
            }

            $stmt = $db->prepare($query);
            $params = [$identificacion];
            if ($idProveedorExcluir !== null) {
                $params[] = $idProveedorExcluir;
            }

            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $exists = $result && $result['total'] > 0;

        } catch (Exception $e) {
            error_log("Error al verificar proveedor existente: " . $e->getMessage());
            $exists = true;
        } finally {
            $conexion->disconnect();
        }
        return $exists;
    }

    private function ejecutarInsercionProveedor(array $data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $query = "INSERT INTO proveedor (
                        nombre, apellido, identificacion, fecha_nacimiento, 
                        direccion, correo_electronico, estatus, telefono_principal, 
                        observaciones, genero, fecha_cracion, fecha_modificacion
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $params = [
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

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $insertId = $db->lastInsertId();

            if ($insertId) {
                $this->setProveedorId($insertId);
                $this->setStatus(true);
                $this->setMessage('Proveedor registrado exitosamente.');
            } else {
                $this->setStatus(false);
                $this->setMessage('Error al obtener ID de proveedor tras registro.');
            }

        } catch (Exception $e) {
            error_log("Error al insertar proveedor: " . $e->getMessage());
            $this->setStatus(false);
            $this->setMessage('Error de base de datos al registrar proveedor');
        } finally {
            $conexion->disconnect();
        }

        return [
            'status' => $this->getStatus(),
            'message' => $this->getMessage(),
            'proveedor_id' => $this->getProveedorId()
        ];
    }

    private function ejecutarActualizacionProveedor(int $idproveedor, array $data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $query = "UPDATE proveedor SET 
                        nombre = ?, apellido = ?, identificacion = ?, 
                        fecha_nacimiento = ?, direccion = ?, correo_electronico = ?, 
                        telefono_principal = ?, observaciones = ?, genero = ?, 
                        fecha_modificacion = NOW() 
                    WHERE idproveedor = ?";

            $params = [
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

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $this->setStatus(true);
            $this->setMessage('Proveedor actualizado exitosamente.');

        } catch (Exception $e) {
            error_log("Error al actualizar proveedor: " . $e->getMessage());
            $this->setStatus(false);
            $this->setMessage('Error de base de datos al actualizar proveedor');
        } finally {
            $conexion->disconnect();
        }

        return ['status' => $this->getStatus(), 'message' => $this->getMessage()];
    }

    private function ejecutarBusquedaProveedorPorId(int $idproveedor)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        $resultado = false;

        try {
            $query = "SELECT *, 
                        DATE_FORMAT(fecha_nacimiento, '%d/%m/%Y') as fecha_nacimiento_formato,
                        DATE_FORMAT(fecha_cracion, '%d/%m/%Y %H:%i') as fecha_creacion_formato,
                        DATE_FORMAT(fecha_modificacion, '%d/%m/%Y %H:%i') as fecha_modificacion_formato
                    FROM proveedor WHERE idproveedor = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$idproveedor]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en búsqueda por ID: " . $e->getMessage());
        } finally {
            $conexion->disconnect();
        }
        return $resultado;
    }

    private function ejecutarEliminacionProveedor(int $idproveedor)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        $resultado = false;

        try {
            $query = "UPDATE proveedor SET estatus = ?, fecha_modificacion = NOW() WHERE idproveedor = ?";
            $stmt = $db->prepare($query);
            $stmt->execute(['INACTIVO', $idproveedor]);
            $resultado = $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Error en eliminación: " . $e->getMessage());
        } finally {
            $conexion->disconnect();
        }
        return $resultado;
    }

    private function ejecutarBusquedaTodosProveedores(int $idUsuarioSesion = 0)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $esSuper = $this->esSuperUsuario($idUsuarioSesion);
            $whereClause = (!$esSuper) ? " WHERE estatus = 'ACTIVO'" : "";

            $query = "SELECT *, 
                        DATE_FORMAT(fecha_nacimiento, '%d/%m/%Y') as fecha_nacimiento_formato,
                        DATE_FORMAT(fecha_cracion, '%d/%m/%Y') as fecha_creacion_formato
                    FROM proveedor" . $whereClause . " ORDER BY idproveedor DESC";

            $stmt = $db->prepare($query);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ["status" => true, "data" => $data];
        } catch (Exception $e) {
            return ["status" => false, "message" => $e->getMessage()];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarBusquedaProveedoresActivos()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $query = "SELECT idproveedor, identificacion, CONCAT(nombre, ' ', apellido) as nombre_completo 
                    FROM proveedor WHERE estatus = 'ACTIVO' ORDER BY nombre ASC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            return ["status" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ["status" => false, "data" => []];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarBusquedaProveedores(string $termino)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $query = "SELECT * FROM proveedor WHERE nombre LIKE ? OR apellido LIKE ? OR identificacion LIKE ?";
            $stmt = $db->prepare($query);
            $term = "%$termino%";
            $stmt->execute([$term, $term, $term]);
            return ["status" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ["status" => false, "data" => []];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarReactivacionProveedor(int $idproveedor)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $query = "UPDATE proveedor SET estatus = 'ACTIVO', fecha_modificacion = NOW() WHERE idproveedor = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$idproveedor]);
            return ["status" => $stmt->rowCount() > 0, "message" => "Proveedor reactivado"];
        } catch (Exception $e) {
            return ["status" => false, "message" => $e->getMessage()];
        } finally {
            $conexion->disconnect();
        }
    }

    private function esSuperUsuario(int $idusuario)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $dbSeguridad = $conexion->get_conectSeguridad();
        $esSuper = false;

        try {
            $query = "SELECT idrol FROM usuario WHERE idusuario = ? AND estatus = 'ACTIVO'";
            $stmt = $dbSeguridad->prepare($query);
            $stmt->execute([$idusuario]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($usuario) {
                $esSuper = intval($usuario['idrol']) === self::SUPER_USUARIO_ROL_ID;
            }
        } catch (Exception $e) {
            error_log("Error en verificación de rol: " . $e->getMessage());
        } finally {
            $conexion->disconnect();
        }
        return $esSuper;
    }

    // --- MÉTODOS PÚBLICOS (PROXIES) ---

    public function verificarEsSuperUsuario(int $idusuario)
    {
        $objModelProveedor = $this->getInstanciaModel();
        return $objModelProveedor->esSuperUsuario($idusuario);
    }

    public function insertProveedor(array $data)
    {
        $objModelProveedor = $this->getInstanciaModel();
        if ($objModelProveedor->ejecutarVerificacionProveedor($data['identificacion'])) {
            return ['status' => false, 'message' => 'Identificación duplicada'];
        }
        return $objModelProveedor->ejecutarInsercionProveedor($data);
    }

    public function updateProveedor(int $idproveedor, array $data)
    {
        $objModelProveedor = $this->getInstanciaModel();
        if ($objModelProveedor->ejecutarVerificacionProveedor($data['identificacion'], $idproveedor)) {
            return ['status' => false, 'message' => 'Identificación duplicada'];
        }
        return $objModelProveedor->ejecutarActualizacionProveedor($idproveedor, $data);
    }

    public function selectProveedorById(int $idproveedor)
    {
        $objModelProveedor = $this->getInstanciaModel();
        return $objModelProveedor->ejecutarBusquedaProveedorPorId($idproveedor);
    }

    public function deleteProveedorById(int $idproveedor)
    {
        $objModelProveedor = $this->getInstanciaModel();
        return $objModelProveedor->ejecutarEliminacionProveedor($idproveedor);
    }

    public function selectAllProveedores(int $idUsuarioSesion = 0)
    {
        $objModelProveedor = $this->getInstanciaModel();
        return $objModelProveedor->ejecutarBusquedaTodosProveedores($idUsuarioSesion);
    }

    public function selectProveedoresActivos()
    {
        $objModelProveedor = $this->getInstanciaModel();
        return $objModelProveedor->ejecutarBusquedaProveedoresActivos();
    }

    public function buscarProveedores(string $termino)
    {
        $objModelProveedor = $this->getInstanciaModel();
        return $objModelProveedor->ejecutarBusquedaProveedores($termino);
    }

    public function reactivarProveedor(int $idproveedor)
    {
        $objModelProveedor = $this->getInstanciaModel();
        return $objModelProveedor->ejecutarReactivacionProveedor($idproveedor);
    }
}
?>