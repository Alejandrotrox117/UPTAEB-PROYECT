<?php
require_once("app/core/conexion.php");
require_once("app/core/mysql.php");

class PersonasModel extends Mysql
{
    private $db;
    private $conexion;

    // Propiedades privadas de la persona
    private $idpersona;
    private $nombre;
    private $apellido;
    private $cedula;
    private $rif;
    private $tipo;
    private $genero;
    private $fecha_nacimiento;
    private $telefono_principal;
    private $correo_electronico;
    private $direccion;
    private $ciudad;
    private $estado;
    private $pais;
    private $estatus;
    private $ultima_modificacion;
    private $fecha_creacion;

    public function __construct()
    {
        parent::__construct();
        $this->db = (new Conexion())->connect();
    }

    // Métodos Getters y Setters
    public function getIdpersona()
    {
        return $this->idpersona;
    }

    public function setIdpersona($idpersona)
    {
        $this->idpersona = $idpersona;
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

    public function getCedula()
    {
        return $this->cedula;
    }

    public function setCedula($cedula)
    {
        $this->cedula = $cedula;
    }

    public function getRif()
    {
        return $this->rif;
    }

    public function setRif($rif)
    {
        $this->rif = $rif;
    }

    public function getTipo()
    {
        return $this->tipo;
    }

    public function setTipo($tipo)
    {
        $this->tipo = $tipo;
    }

    public function getGenero()
    {
        return $this->genero;
    }

    public function setGenero($genero)
    {
        $this->genero = $genero;
    }

    public function getFechaNacimiento()
    {
        return $this->fecha_nacimiento;
    }

    public function setFechaNacimiento($fecha_nacimiento)
    {
        $this->fecha_nacimiento = $fecha_nacimiento;
    }

    public function getTelefonoPrincipal()
    {
        return $this->telefono_principal;
    }

    public function setTelefonoPrincipal($telefono_principal)
    {
        $this->telefono_principal = $telefono_principal;
    }

    public function getCorreoElectronico()
    {
        return $this->correo_electronico;
    }

    public function setCorreoElectronico($correo_electronico)
    {
        $this->correo_electronico = $correo_electronico;
    }

    public function getDireccion()
    {
        return $this->direccion;
    }

    public function setDireccion($direccion)
    {
        $this->direccion = $direccion;
    }

    public function getCiudad()
    {
        return $this->ciudad;
    }

    public function setCiudad($ciudad)
    {
        $this->ciudad = $ciudad;
    }

    public function getEstado()
    {
        return $this->estado;
    }

    public function setEstado($estado)
    {
        $this->estado = $estado;
    }

    public function getPais()
    {
        return $this->pais;
    }

    public function setPais($pais)
    {
        $this->pais = $pais;
    }

    public function getEstatus()
    {
        return $this->estatus;
    }

    public function setEstatus($estatus)
    {
        $this->estatus = $estatus;
    }

    public function getFechaCreacion()
    {
        return $this->fecha_creacion;
    }

    public function getUltimaModificacion()
    {
        return $this->ultima_modificacion;
    }

    public function setFechaCreacion($fecha)
    {
        $this->fecha_creacion = $fecha;
    }

    public function setUltimaModificacion($fecha)
    {
        $this->ultima_modificacion = $fecha;
    }

    // Método para consultar personas basado en el rol del usuario
    public function ConsultarPersonas($userRole)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']) || !isset($_SESSION['user']['idpersona'])) {
            echo json_encode(['success' => false, 'message' => 'No estás autenticado.']);
            return;
        }

        $usuarioAutenticadoId = $_SESSION['user']['idpersona'];

        // Consulta dependiendo del rol del usuario
        if ($userRole == 3) {
            $sql = "SELECT u.idusuario, p.idpersona, p.nombre AS persona_nombre, p.genero, p.apellido AS persona_apellido,
                           p.cedula, p.rif, p.tipo, p.telefono, u.correo, r.nombre AS rol, p.estatus AS persona_estatus,
                           u.status1 AS usuario_estatus
                    FROM personas p
                    LEFT JOIN usuarios u ON p.idpersona = u.idpersona
                    LEFT JOIN roles r ON u.idrol = r.idrol";
        } elseif ($userRole == 1) {
            $sql = "SELECT u.idusuario, p.idpersona, p.nombre AS persona_nombre, p.genero, p.apellido AS persona_apellido,
                           p.cedula, p.rif, p.tipo, p.telefono, u.correo, r.nombre AS rol, p.estatus AS persona_estatus,
                           u.status1 AS usuario_estatus
                    FROM personas p
                    LEFT JOIN usuarios u ON p.idpersona = u.idpersona
                    LEFT JOIN roles r ON u.idrol = r.idrol
                    WHERE p.estatus = 'Activo' AND (u.status1 = 1 OR u.idusuario IS NULL) AND u.idrol != 3";
        } else {
            return null;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Modificar resultados según lógica adicional
        foreach ($resultados as &$usuario) {
            if (!$usuario['idusuario']) {
                $usuario['correo'] = 'N/A';
                $usuario['rol'] = 'No asignado';
                $usuario['usuario_estatus'] = 'N/A';
                $usuario['mostrar_boton_eliminar'] = false;
            }

            if ($usuario['idpersona'] == $usuarioAutenticadoId || $usuario['rol'] == 'root') {
                $usuario['mostrar_boton_eliminar'] = false;
            } else {
                $usuario['mostrar_boton_eliminar'] = true;
            }
        }

        return $resultados;
    }

    // Método para obtener una persona por ID
    public function getpersonaById($id)
    {
        $sql = "SELECT a.idpersona, a.nombre, a.apellido, b.idusuario, b.correo
                FROM personas a
                LEFT JOIN usuarios b ON a.idpersona = b.idpersona
                WHERE a.idpersona = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $persona = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($persona['idusuario'] === null) {
                $persona['correo'] = null;
            }
            return $persona;
        } else {
            return false;
        }
    }

    // Método para eliminar un usuario
    public function eliminarUsuario($id)
    {
        $this->setUltimaModificacion(date('Y-m-d H:i:s'));
        $fechaModificacion = $this->getUltimaModificacion();  // Asignar a una variable antes de pasarlo

        $this->db->beginTransaction();

        try {
            // Actualizar estado de usuario
            $sqlUsuario = "UPDATE usuarios SET status1 = 0, ultima_modificacion = :fecha WHERE idpersona = :idpersona";
            $stmtUsuario = $this->db->prepare($sqlUsuario);
            $stmtUsuario->bindParam(':idpersona', $id, PDO::PARAM_INT);
            $stmtUsuario->bindParam(':fecha', $fechaModificacion, PDO::PARAM_STR);  // Usar la variable
            $stmtUsuario->execute();

            // Actualizar estado de persona
            $sqlPersona = "UPDATE personas SET estatus = 'Inactivo', fecha_modificacion = :fecha WHERE idpersona = :idpersona";
            $stmtPersona = $this->db->prepare($sqlPersona);
            $stmtPersona->bindParam(':idpersona', $id, PDO::PARAM_INT);
            $stmtPersona->bindParam(':fecha', $fechaModificacion, PDO::PARAM_STR);  // Usar la variable
            $stmtPersona->execute();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error al desactivar usuario y persona: ' . $e->getMessage()]);
            return false;
        }
    }

    public function guardar_usuario($datos)
    {
        try {
            // Comenzamos la transacción
            $this->db->beginTransaction();
    
            // Insertar en la tabla persona
            $sqlPersona = "INSERT INTO personas (
                nombre, apellido, cedula, rif, telefono, tipo, genero, fecha_nacimiento, estado, ciudad, pais, observaciones, fecha_creacion, fecha_modificacion
            ) VALUES (
                :nombre, :apellido, :cedula, :rif, :telefono, :tipo, :genero, :fecha_nacimiento, :estado, :ciudad, :pais, :observaciones, NOW(), NOW()
            )";
            
    
            $stmt = $this->db->prepare($sqlPersona);
    
            $stmt->bindParam(':nombre', $datos['nombre']);
            $stmt->bindParam(':apellido', $datos['apellido']);
            $stmt->bindParam(':cedula', $datos['cedula']);
            $stmt->bindParam(':rif', $datos['rif']);
            $stmt->bindParam(':telefono', $datos['telefono']);
            $stmt->bindParam(':tipo', $datos['tipo']);
            $stmt->bindParam(':genero', $datos['genero']);
            $stmt->bindParam(':fecha_nacimiento', $datos['fecha_nacimiento']);
            $stmt->bindParam(':estado', $datos['estado']);
            $stmt->bindParam(':ciudad', $datos['ciudad']);
            $stmt->bindParam(':pais', $datos['pais']);
            $stmt->bindParam(':observaciones', $datos['observaciones']);
    
            $stmt->execute();
    
            // Obtener el ID de la persona insertada
            $persona_id = $this->db->lastInsertId();
    
            // Si es necesario, insertar en la tabla usuario
            if ($datos['crear_usuario'] == '1') {
                $sqlUsuario = "INSERT INTO usuarios (idpersona, idrol, correo, clave)
                    VALUES (:persona_id, :rol, :correo, :clave)";
    
                $stmt = $this->db->prepare($sqlUsuario);
                $stmt->bindParam(':persona_id', $persona_id);
                $stmt->bindParam(':rol', $datos['rol']);
                $stmt->bindParam(':correo', $datos['correo']);
                $stmt->bindParam(':clave', $datos['clave']);
                
    
                $stmt->execute();
            }
    
            // Confirmar transacción
            $this->db->commit();
    
            return true;
        } catch (PDOException $e) {
            // Si ocurre un error, deshacer la transacción
            $this->db->rollBack();
            return false;
        }
    }
    

}
