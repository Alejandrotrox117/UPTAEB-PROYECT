<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class empleadosModel extends Mysql
{
    private $db;
    private $conexion;

    // Definir constante para el rol de super usuario
    const SUPER_USUARIO_ROL_ID = 1; // ID del rol de super usuario/admin en la BD

    // Atributos de la tabla `empleado`
    private $idempleado;
    private $nombre;
    private $apellido;
    private $identificacion;
    private $fecha_nacimiento;
    private $direccion;
    private $correo_electronico;
    private $estatus;
    private $telefono_principal;
    private $observaciones;
    private $genero;
    private $fecha_creacion;
    private $fecha_modificacion;
    private $fecha_inicio;
    private $fecha_fin;
    private $puesto;
    private $salario;

    public function __construct()
    {
        parent::__construct();
        $this->conexion = new Conexion();
        $this->conexion->connect();
        $this->db = $this->conexion->get_conectGeneral();
     
        $idUsuario = $this->obtenerIdUsuarioSesion();

        if ($idUsuario) {
           
            $this->setUsuarioActual($idUsuario);
        }
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

 
    private function setUsuarioActual(int $idUsuario)
    {
        $sql = "SET @usuario_actual = $idUsuario";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("No se pudo establecer @usuario_actual: " . $e->getMessage());
        }
    }


    // Métodos Getters y Setters
    public function getIdEmpleado()
    {
        return $this->idempleado;
    }

    public function setIdEmpleado($idempleado)
    {
        $this->idempleado = $idempleado;
    }

    public function getNombre()
    {
        return $this->nombre;
    }

    public function setNombre($nombre)
    {
        $this->nombre = $nombre;
    }

    public function getApellido()
    {
        return $this->apellido;
    }

    public function setApellido($apellido)
    {
        $this->apellido = $apellido;
    }

    public function getIdentificacion()
    {
        return $this->identificacion;
    }

    public function setIdentificacion($identificacion)
    {
        $this->identificacion = $identificacion;
    }

    public function getFechaNacimiento()
    {
        return $this->fecha_nacimiento;
    }

    public function setFechaNacimiento($fecha_nacimiento)
    {
        $this->fecha_nacimiento = $fecha_nacimiento;
    }

    public function getDireccion()
    {
        return $this->direccion;
    }

    public function setDireccion($direccion)
    {
        $this->direccion = $direccion;
    }

    public function getCorreoElectronico()
    {
        return $this->correo_electronico;
    }

    public function setCorreoElectronico($correo_electronico)
    {
        $this->correo_electronico = $correo_electronico;
    }

    public function getEstatus()
    {
        return $this->estatus;
    }

    public function setEstatus($estatus)
    {
        $this->estatus = $estatus;
    }

    public function getTelefonoPrincipal()
    {
        return $this->telefono_principal;
    }

    public function setTelefonoPrincipal($telefono_principal)
    {
        $this->telefono_principal = $telefono_principal;
    }

    public function getObservaciones()
    {
        return $this->observaciones;
    }

    public function setObservaciones($observaciones)
    {
        $this->observaciones = $observaciones;
    }

    public function getGenero()
    {
        return $this->genero;
    }

    public function setGenero($genero)
    {
        $this->genero = $genero;
    }

    public function getFechaCreacion()
    {
        return $this->fecha_creacion;
    }

    public function setFechaCreacion($fecha_creacion)
    {
        $this->fecha_creacion = $fecha_creacion;
    }

    public function getFechaModificacion()
    {
        return $this->fecha_modificacion;
    }

    public function setFechaModificacion($fecha_modificacion)
    {
        $this->fecha_modificacion = $fecha_modificacion;
    }

    public function getFechaInicio()
    {
        return $this->fecha_inicio;
    }

    public function setFechaInicio($fecha_inicio)
    {
        $this->fecha_inicio = $fecha_inicio;
    }

    public function getFechaFin()
    {
        return $this->fecha_fin;
    }

    public function setFechaFin($fecha_fin)
    {
        $this->fecha_fin = $fecha_fin;
    }

    public function getPuesto()
    {
        return $this->puesto;
    }

    public function setPuesto($puesto)
    {
        $this->puesto = $puesto;
    }

    public function getSalario()
    {
        return $this->salario;
    }

    public function setSalario($salario)
    {
        $this->salario = $salario;
    }

    // Método para seleccionar todos los empleados activos
    /**
     * Obtener todos los empleados (para super usuarios) o solo activos (para usuarios normales)
     */
    public function selectAllEmpleados(int $idUsuarioSesion = 0)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            error_log("=== selectAllEmpleados llamado con Usuario ID: $idUsuarioSesion ===");
            
            // Verificar si el usuario actual es super usuario
            $esSuperUsuarioActual = $this->esSuperUsuario($idUsuarioSesion);
            
            error_log("Es Super Usuario: " . ($esSuperUsuarioActual ? 'SI' : 'NO'));
            
            $whereClause = "";
            if (!$esSuperUsuarioActual) {
                // Si no es super usuario, solo mostrar empleados activos
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
            
            // Log de empleados con estatus
            foreach ($empleados as $emp) {
                error_log("Empleado ID: {$emp['idempleado']}, Nombre: {$emp['nombre']}, Estatus: {$emp['estatus']}");
            }
            
            $resultado = [
                "status" => true,
                "message" => "Empleados obtenidos.",
                "data" => $empleados
            ];
            
        } catch (Exception $e) {
            error_log("EmpleadosModel::selectAllEmpleados - Error: " . $e->getMessage());
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

    // Método para insertar un nuevo empleado
    public function insertEmpleado($data)
    {
        try {
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

            $stmt = $this->db->prepare($sql);
            $arrValues = [
                $data['nombre'],
                $data['apellido'],
                $data['identificacion'],
                $data['tipo_empleado'] ?? 'OPERARIO', // Por defecto OPERARIO
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
        }
    }

    // Método para eliminar lógicamente un empleado
    public function deleteEmpleado($idempleado)
    {
        try {
            $sql = "UPDATE empleado SET estatus = 'INACTIVO' WHERE idempleado = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$idempleado]);
        } catch (PDOException $e) {
            error_log("Error al eliminar empleado: " . $e->getMessage());
            return false;
        }
    }

    // Método para actualizar un empleado
    public function updateEmpleado($data)
    {
        try {
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

            $stmt = $this->db->prepare($sql);
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
        }
    }

    // Método para obtener un empleado por ID
    public function getEmpleadoById($idempleado)
    {
        try {
            // Asegurar que $idempleado sea un entero
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
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idempleado]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener empleado por ID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si un usuario es super usuario
     */
    /**
     * Verificar si un usuario es super usuario usando conexión independiente
     */
    private function esSuperUsuario(int $idusuario)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $dbSeguridad = $conexion->get_conectSeguridad();
        
        try {
            error_log("EmpleadosModel::esSuperUsuario - Verificando usuario ID: $idusuario");
            error_log("EmpleadosModel::esSuperUsuario - Constante SUPER_USUARIO_ROL_ID: " . self::SUPER_USUARIO_ROL_ID);
            
            // Primero intentar por ID de rol
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
                
                error_log("EmpleadosModel::esSuperUsuario - Rol del usuario ID: $rolUsuario, Nombre: $nombreRol");
                
                // Verificar por ID (método primario)
                $esSuperUsuarioPorId = $rolUsuario === self::SUPER_USUARIO_ROL_ID;
                
                // Verificar por nombre (método secundario/fallback)
                $rolesSuper = ['super admin', 'super usuario', 'superadmin', 'superusuario', 'administrador', 'admin'];
                $esSuperUsuarioPorNombre = in_array($nombreRol, $rolesSuper);
                
                $esSuperUsuario = $esSuperUsuarioPorId || $esSuperUsuarioPorNombre;
                
                error_log("EmpleadosModel::esSuperUsuario - Es super usuario (por ID): " . ($esSuperUsuarioPorId ? 'SÍ' : 'NO'));
                error_log("EmpleadosModel::esSuperUsuario - Es super usuario (por nombre): " . ($esSuperUsuarioPorNombre ? 'SÍ' : 'NO'));
                error_log("EmpleadosModel::esSuperUsuario - RESULTADO FINAL: " . ($esSuperUsuario ? 'SÍ' : 'NO'));
                
                return $esSuperUsuario;
            } else {
                error_log("EmpleadosModel::esSuperUsuario - Usuario no encontrado o inactivo");
                return false;
            }
        } catch (Exception $e) {
            error_log("EmpleadosModel::esSuperUsuario - Error: " . $e->getMessage());
            error_log("EmpleadosModel::esSuperUsuario - Stack trace: " . $e->getTraceAsString());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    /**
     * Verificar si un usuario es super usuario (método público)
     */
    public function verificarEsSuperUsuario(int $idusuario)
    {
        return $this->esSuperUsuario($idusuario);
    }

    /**
     * Reactivar un empleado (cambiar estatus a ACTIVO)
     */
    public function reactivarEmpleado(int $idempleado)
    {
        try {
            // Verificar que el empleado existe
            $sql = "SELECT idempleado, estatus FROM empleado WHERE idempleado = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idempleado]);
            $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$empleado) {
                return [
                    'status' => false,
                    'message' => 'Empleado no encontrado'
                ];
            }
            
            if (strtoupper($empleado['estatus']) === 'ACTIVO') {
                return [
                    'status' => false,
                    'message' => 'El empleado ya está activo'
                ];
            }
            
            // Reactivar empleado
            $sql = "UPDATE empleado SET estatus = 'ACTIVO', fecha_modificacion = NOW() WHERE idempleado = ?";
            $stmt = $this->db->prepare($sql);
            $resultado = $stmt->execute([$idempleado]);
            
            if ($resultado && $stmt->rowCount() > 0) {
                return [
                    'status' => true,
                    'message' => 'Empleado reactivado exitosamente'
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'No se pudo reactivar el empleado'
                ];
            }
            
        } catch (PDOException $e) {
            error_log("empleadosModel::reactivarEmpleado - Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al reactivar empleado: ' . $e->getMessage()
            ];
        }
    }
}
