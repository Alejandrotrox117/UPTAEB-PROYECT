<?php
namespace App\Models;

use App\Core\Conexion;
use PDO;
use PDOException;
use Exception;

class EmpleadosModel
{
    private $objModelEmpleadosModel = null;
    const SUPER_USUARIO_ROL_ID = 1;

    public function __construct()
    {
    }

    private function getInstanciaModel()
    {
        if ($this->objModelEmpleadosModel == null) {
            $this->objModelEmpleadosModel = new EmpleadosModel();
        }
        return $this->objModelEmpleadosModel;
    }

    private function obtenerIdUsuarioSesion(): ?int
    {
        if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
            return intval($_SESSION['usuario_id']);
        } elseif (isset($_SESSION['idUser']) && !empty($_SESSION['idUser'])) {
            return intval($_SESSION['idUser']);
        }
        return null;
    }

    private function setUsuarioActual($db, int $idUsuario)
    {
        $sql = "SET @usuario_actual = " . intval($idUsuario);
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("No se pudo establecer @usuario_actual: " . $e->getMessage());
        }
    }

    public function selectAllEmpleados(int $idUsuarioSesion = 0)
    {
        $objModelEmpleadosModel = $this->getInstanciaModel();
        return $objModelEmpleadosModel->ejecutarSelectAllEmpleados($idUsuarioSesion);
    }

    private function ejecutarSelectAllEmpleados(int $idUsuarioSesion = 0)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $idUsuario = $this->obtenerIdUsuarioSesion();
            if ($idUsuario) {
                $this->setUsuarioActual($db, $idUsuario);
            }

            error_log("=== selectAllEmpleados llamado con Usuario ID: $idUsuarioSesion ===");

            $esSuperUsuarioActual = $this->esSuperUsuario($idUsuarioSesion);
            error_log("Es Super Usuario: " . ($esSuperUsuarioActual ? 'SI' : 'NO'));

            $whereClause = "";
            if (!$esSuperUsuarioActual) {
                $whereClause = " WHERE estatus = 'ACTIVO'";
                error_log("Aplicando filtro WHERE estatus = 'ACTIVO'");
            } else {
                error_log("Super Admin detectado - mostrando TODOS los empleados");
            }

            $query = "SELECT 
                    idempleado, nombre, apellido, identificacion, fecha_nacimiento,
                    direccion, correo_electronico, estatus, telefono_principal,
                    observaciones, genero, fecha_inicio, fecha_fin, puesto, salario,
                    tipo_empleado,
                    DATE_FORMAT(fecha_nacimiento, '%d/%m/%Y') as fecha_nacimiento_formato,
                    DATE_FORMAT(fecha_inicio, '%d/%m/%Y') as fecha_inicio_formato,
                    DATE_FORMAT(fecha_fin, '%d/%m/%Y') as fecha_fin_formato
                FROM empleado" . $whereClause . " 
                ORDER BY idempleado DESC";

            error_log("Query ejecutada: $query");

            $stmt = $db->prepare($query);
            $stmt->execute();
            $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("Total empleados encontrados: " . count($empleados));

            $resultado = [
                "status" => true,
                "message" => "Empleados obtenidos.",
                "data" => $empleados
            ];

        } catch (Exception $e) {
            error_log("EmpleadosModel::ejecutarSelectAllEmpleados - Error: " . $e->getMessage());
            $resultado = [
                "status" => false,
                "message" => "Error al obtener empleados: " . $e->getMessage(),
                "data" => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    public function insertEmpleado($data)
    {
        $objModelEmpleadosModel = $this->getInstanciaModel();
        return $objModelEmpleadosModel->ejecutarInsertEmpleado($data);
    }

    private function ejecutarInsertEmpleado($data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $idUsuario = $this->obtenerIdUsuarioSesion();
            if ($idUsuario) {
                $this->setUsuarioActual($db, $idUsuario);
            }

            $sql = "INSERT INTO empleado (
                        nombre, 
                        apellido, 
                        identificacion,
                        tipo_empleado,
                        estatus,
                        fecha_nacimiento, 
                        direccion, 
                        correo_electronico, 
                        telefono_principal, 
                        observaciones, 
                        genero, 
                        fecha_inicio, 
                        fecha_fin, 
                        puesto, 
                        salario
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $db->prepare($sql);
            $arrValues = [
                $data['nombre'],
                $data['apellido'],
                $data['identificacion'],
                $data['tipo_empleado'] ?? 'OPERARIO',
                $data['estatus'] ?? 'activo',
                !empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null,
                !empty($data['direccion']) ? $data['direccion'] : null,
                !empty($data['correo_electronico']) ? $data['correo_electronico'] : null,
                !empty($data['telefono_principal']) ? $data['telefono_principal'] : null,
                !empty($data['observaciones']) ? $data['observaciones'] : null,
                !empty($data['genero']) ? $data['genero'] : null,
                !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : null,
                !empty($data['fecha_fin']) ? $data['fecha_fin'] : null,
                !empty($data['puesto']) ? $data['puesto'] : null,
                !empty($data['salario']) ? $data['salario'] : 0.00
            ];

            return $stmt->execute($arrValues);
        } catch (PDOException $e) {
            error_log("Error al insertar empleado: " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    public function deleteEmpleado($idempleado)
    {
        $objModelEmpleadosModel = $this->getInstanciaModel();
        return $objModelEmpleadosModel->ejecutarDeleteEmpleado($idempleado);
    }

    private function ejecutarDeleteEmpleado($idempleado)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $idUsuario = $this->obtenerIdUsuarioSesion();
            if ($idUsuario) {
                $this->setUsuarioActual($db, $idUsuario);
            }

            $sql = "UPDATE empleado SET estatus = 'INACTIVO' WHERE idempleado = ?";
            $stmt = $db->prepare($sql);
            return $stmt->execute([$idempleado]);
        } catch (PDOException $e) {
            error_log("Error al eliminar empleado: " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    public function updateEmpleado($data)
    {
        $objModelEmpleadosModel = $this->getInstanciaModel();
        return $objModelEmpleadosModel->ejecutarUpdateEmpleado($data);
    }

    private function ejecutarUpdateEmpleado($data)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $idUsuario = $this->obtenerIdUsuarioSesion();
            if ($idUsuario) {
                $this->setUsuarioActual($db, $idUsuario);
            }

            $sql = "UPDATE empleado SET 
                        nombre = ?, 
                        apellido = ?, 
                        identificacion = ?,
                        tipo_empleado = ?,
                        estatus = ?,
                        fecha_nacimiento = ?, 
                        direccion = ?, 
                        correo_electronico = ?, 
                        telefono_principal = ?, 
                        observaciones = ?, 
                        genero = ?, 
                        fecha_inicio = ?, 
                        fecha_fin = ?, 
                        puesto = ?, 
                        salario = ? 
                    WHERE idempleado = ?";

            $stmt = $db->prepare($sql);
            $arrValues = [
                $data['nombre'],
                $data['apellido'],
                $data['identificacion'],
                $data['tipo_empleado'] ?? 'OPERARIO',
                $data['estatus'] ?? 'activo',
                !empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null,
                !empty($data['direccion']) ? $data['direccion'] : null,
                !empty($data['correo_electronico']) ? $data['correo_electronico'] : null,
                !empty($data['telefono_principal']) ? $data['telefono_principal'] : null,
                !empty($data['observaciones']) ? $data['observaciones'] : null,
                !empty($data['genero']) ? $data['genero'] : null,
                !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : null,
                !empty($data['fecha_fin']) ? $data['fecha_fin'] : null,
                !empty($data['puesto']) ? $data['puesto'] : null,
                !empty($data['salario']) ? $data['salario'] : 0.00,
                $data['idempleado']
            ];

            return $stmt->execute($arrValues);
        } catch (PDOException $e) {
            error_log("Error al actualizar empleado: " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    public function getEmpleadoById($idempleado)
    {
        $objModelEmpleadosModel = $this->getInstanciaModel();
        return $objModelEmpleadosModel->ejecutarGetEmpleadoById($idempleado);
    }

    private function ejecutarGetEmpleadoById($idempleado)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $idempleado = (int) $idempleado;

            $sql = "SELECT 
                        idempleado, 
                        nombre, 
                        apellido, 
                        identificacion, 
                        tipo_empleado,
                        fecha_nacimiento, 
                        direccion, 
                        correo_electronico, 
                        estatus, 
                        telefono_principal, 
                        observaciones, 
                        genero,  
                        fecha_modificacion, 
                        fecha_inicio, 
                        fecha_fin, 
                        puesto, 
                        salario 
                    FROM empleado 
                    WHERE idempleado = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$idempleado]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener empleado por ID: " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    public function verificarEsSuperUsuario(int $idusuario)
    {
        $objModelEmpleadosModel = $this->getInstanciaModel();
        return $objModelEmpleadosModel->esSuperUsuario($idusuario);
    }

    private function esSuperUsuario(int $idusuario)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $dbSeguridad = $conexion->get_conectSeguridad();

        try {
            error_log("EmpleadosModel::esSuperUsuario - Verificando usuario ID: $idusuario");

            $sql = "SELECT u.idrol, r.nombre as rol_nombre 
                    FROM usuario u
                    LEFT JOIN roles r ON u.idrol = r.idrol
                    WHERE u.idusuario = ? AND u.estatus = 'ACTIVO'";
            $stmt = $dbSeguridad->prepare($sql);
            $stmt->execute([$idusuario]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                $rolUsuario = intval($usuario['idrol']);
                $nombreRol = strtolower($usuario['rol_nombre'] ?? '');

                $esSuperUsuarioPorId = $rolUsuario === self::SUPER_USUARIO_ROL_ID;

                $rolesSuper = ['super admin', 'super usuario', 'superadmin', 'superusuario', 'administrador', 'admin'];
                $esSuperUsuarioPorNombre = in_array($nombreRol, $rolesSuper);

                $esSuperUsuario = $esSuperUsuarioPorId || $esSuperUsuarioPorNombre;

                return $esSuperUsuario;
            } else {
                return false;
            }
        } catch (Exception $e) {
            error_log("EmpleadosModel::esSuperUsuario - Error: " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    public function reactivarEmpleado(int $idempleado)
    {
        $objModelEmpleadosModel = $this->getInstanciaModel();
        return $objModelEmpleadosModel->ejecutarReactivarEmpleado($idempleado);
    }

    private function ejecutarReactivarEmpleado(int $idempleado)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $idUsuario = $this->obtenerIdUsuarioSesion();
            if ($idUsuario) {
                $this->setUsuarioActual($db, $idUsuario);
            }

            $sql = "SELECT idempleado, estatus FROM empleado WHERE idempleado = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$idempleado]);
            $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$empleado) {
                return ['status' => false, 'message' => 'Empleado no encontrado'];
            }

            if (strtoupper($empleado['estatus']) === 'ACTIVO') {
                return ['status' => false, 'message' => 'El empleado ya está activo'];
            }

            $sql = "UPDATE empleado SET estatus = 'ACTIVO', fecha_modificacion = NOW() WHERE idempleado = ?";
            $stmt = $db->prepare($sql);
            $resultado = $stmt->execute([$idempleado]);

            if ($resultado && $stmt->rowCount() > 0) {
                return ['status' => true, 'message' => 'Empleado reactivado exitosamente'];
            } else {
                return ['status' => false, 'message' => 'No se pudo reactivar el empleado'];
            }

        } catch (PDOException $e) {
            error_log("empleadosModel::reactivarEmpleado - Error: " . $e->getMessage());
            return ['status' => false, 'message' => 'Error al reactivar empleado: ' . $e->getMessage()];
        } finally {
            $conexion->disconnect();
        }
    }
}

