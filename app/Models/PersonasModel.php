<?php
namespace App\Models;

use App\Core\Mysql;
use App\Core\Conexion;
use PDO;
use PDOException;

class PersonasModel extends mysql
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
    $idUsuario = $this->obtenerIdUsuarioSesion();

        if ($idUsuario) {
            // Establecer la variable de sesión SQL
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

    // Método para establecer @usuario_actual en MySQL
    private function setUsuarioActual(int $idUsuario)
    {
        $sql = "SET @usuario_actual = $idUsuario";
        try {
            $stmt = $this->dbPrincipal->prepare($sql);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("No se pudo establecer @usuario_actual: " . $e->getMessage());
        }
    }

    public function insertPersona(array $data): array
    {
        try {
            $this->dbPrincipal->beginTransaction();

            $sqlPersona = "INSERT INTO personas (nombre, apellido, identificacion, genero, fecha_nacimiento, correo_electronico, direccion, observaciones, telefono_principal, estatus, fecha_creacion, fecha_modificacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $valoresPersona = [
                $data['nombre'] ?? null,
                $data['apellido'] ?? null,
                $data['cedula'] ?? null,
                $data['genero'] ?? null,
                $data['fecha_nacimiento'] ?: null,
                $data['correo_electronico_persona'] ?? null,
                $data['direccion'] ?? null,
                $data['observaciones'] ?? null,
                $data['telefono_principal'] ?? null,
                'ACTIVO'
            ];
            
            $stmtPersona = $this->dbPrincipal->prepare($sqlPersona);
            $insertExitosoPersona = $stmtPersona->execute($valoresPersona);

            $idPersonaInsertada = $this->dbPrincipal->lastInsertId();

            if (!$idPersonaInsertada) {
                $this->dbPrincipal->rollBack();
                error_log("Error: No se pudo obtener el lastInsertId para la persona.");
                return [
                    'status' => false, 
                    'message' => 'Error al obtener ID de persona tras registro.',
                    'persona_id' => null
                ];
            }

            $this->dbPrincipal->commit();

            return [
                'status' => true, 
                'message' => 'Persona registrada exitosamente (ID: ' . $idPersonaInsertada . ').',
                'persona_id' => $idPersonaInsertada
            ];

        } catch (PDOException $e) {
            if ($this->dbPrincipal->inTransaction()) {
                $this->dbPrincipal->rollBack();
            }
            error_log("Error al insertar persona: " . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Error de base de datos al registrar persona: ' . $e->getMessage(),
                'persona_id' => null
            ];
        }
    }

    public function insertUsuario(int $personaId, array $dataUsuario): array
    {
        try {
            $this->dbSeguridad->beginTransaction();

            $claveHasheada = hash("SHA256", $dataUsuario['clave_usuario']);
            $correoTablaUsuario = $dataUsuario['correo_electronico_usuario'];  
            $rol = $dataUsuario['idrol_usuario'];

            $sqlUsuario = "INSERT INTO usuario (idrol, usuario, clave, correo, personaId, estatus, token) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $valoresUsuario = [
                $rol,
                $correoTablaUsuario,
                $claveHasheada,
                $correoTablaUsuario,  
                $personaId, 
                'ACTIVO',
                ''
            ];

            $stmtUsuario = $this->dbSeguridad->prepare($sqlUsuario);
            $insertExitosoUsuario = $stmtUsuario->execute($valoresUsuario);
            
            if (!$insertExitosoUsuario || $stmtUsuario->rowCount() === 0) {
                $this->dbSeguridad->rollBack();
                $errorInfo = $stmtUsuario->errorInfo();
                error_log("Error al insertar usuario: " . print_r($errorInfo, true));
                return [
                    'status' => false, 
                    'message' => 'Error SQL al registrar usuario: ' . $errorInfo[2]
                ];
            }

            $this->dbSeguridad->commit();

            return [
                'status' => true, 
                'message' => 'Usuario registrado exitosamente para persona ID: ' . $personaId
            ];

        } catch (PDOException $e) {
            if ($this->dbSeguridad->inTransaction()) {
                $this->dbSeguridad->rollBack();
            }
            error_log("Error al insertar usuario: " . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Error de base de datos al registrar usuario: ' . $e->getMessage()
            ];
        }
    }

    public function insertPersonaConUsuario(array $data): array
    {
        try {
            $resultadoPersona = $this->insertPersona($data);
            
            if (!$resultadoPersona['status']) {
                return $resultadoPersona;
            }

            $personaId = $resultadoPersona['persona_id'];
            
            if (isset($data['crear_usuario']) && $data['crear_usuario'] == "1") {
                $resultadoUsuario = $this->insertUsuario($personaId, $data);
                
                if (!$resultadoUsuario['status']) {
                    return [
                        'status' => false,
                        'message' => 'Error al crear usuario: ' . $resultadoUsuario['message'],
                        'persona_id' => null
                    ];
                }
                
                return [
                    'status' => true,
                    'message' => 'Persona (ID: ' . $personaId . ') y usuario registrados exitosamente.',
                    'persona_id' => $personaId
                ];
            }
            
            return [
                'status' => true,
                'message' => 'Persona registrada exitosamente (ID: ' . $personaId . '). No se creó usuario.',
                'persona_id' => $personaId
            ];

        } catch (Exception $e) {
            error_log("Error en insertPersonaConUsuario: " . $e->getMessage());
            
            return [
                'status' => false,
                'message' => 'Error general en el proceso.',
                'persona_id' => null
            ];
        }
    }

    public function updatePersona(int $idpersona_pk, array $data): array
    {
        try {
            $this->dbPrincipal->beginTransaction();

            $sql = "UPDATE personas SET 
                    nombre = ?, 
                    apellido = ?, 
                    identificacion = ?, 
                    genero = ?, 
                    fecha_nacimiento = ?, 
                    correo_electronico = ?, 
                    direccion = ?, 
                    observaciones = ?, 
                    telefono_principal = ?, 
                    fecha_modificacion = NOW() 
                    WHERE idpersona = ?";
            
            $valores = [
                $data['nombre'] ?? null,
                $data['apellido'] ?? null,
                $data['identificacion'] ?? null,
                $data['genero'] ?? null,
                $data['fecha_nacimiento'] ?: null,
                $data['correo_electronico_persona'] ?? null,
                $data['direccion'] ?? null,
                $data['observaciones'] ?? null,
                $data['telefono_principal'] ?? null,
                $idpersona_pk
            ];
            
            $stmt = $this->dbPrincipal->prepare($sql);
            $updateExitoso = $stmt->execute($valores);

            if (!$updateExitoso || $stmt->rowCount() === 0) {
                $this->dbPrincipal->rollBack();
                return [
                    'status' => false, 
                    'message' => 'No se pudo actualizar la persona o no se realizaron cambios.'
                ];
            }

            $this->dbPrincipal->commit();

            return [
                'status' => true, 
                'message' => 'Persona actualizada exitosamente.'
            ];

        } catch (PDOException $e) {
            if ($this->dbPrincipal->inTransaction()) {
                $this->dbPrincipal->rollBack();
            }
            error_log("Error al actualizar persona: " . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Error de base de datos al actualizar persona: ' . $e->getMessage()
            ];
        }
    }

    public function updateUsuario(int $idpersona_pk, array $dataUsuario): array
    {
        try {
            $this->dbSeguridad->beginTransaction();

            // Primero verificar si existe el usuario
            $sqlCheck = "SELECT idusuario FROM usuario WHERE personaId = ?";
            $stmtCheck = $this->dbSeguridad->prepare($sqlCheck);
            $stmtCheck->execute([$idpersona_pk]);
            $usuarioExiste = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if (!$usuarioExiste) {
                // Si no existe y se quiere crear
                if (!empty($dataUsuario['correo_electronico_usuario']) && !empty($dataUsuario['clave_usuario'])) {
                    $claveHasheada = password_hash($dataUsuario['clave_usuario'], PASSWORD_DEFAULT);
                    $sqlInsert = "INSERT INTO usuario (idrol, usuario, clave, correo, personaId, estatus, token) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    
                    $valoresInsert = [
                        $dataUsuario['idrol_usuario'],
                        $dataUsuario['correo_electronico_usuario'],
                        $claveHasheada,
                        $dataUsuario['correo_electronico_usuario'],
                        $idpersona_pk,
                        'ACTIVO',
                        ''
                    ];

                    $stmtInsert = $this->dbSeguridad->prepare($sqlInsert);
                    $stmtInsert->execute($valoresInsert);
                }
            } else {
              
                $sqlUpdate = "UPDATE usuario SET idrol = ?, usuario = ?, correo = ?";
                $valoresUpdate = [
                    $dataUsuario['idrol_usuario'],
                    $dataUsuario['correo_electronico_usuario'],
                    $dataUsuario['correo_electronico_usuario']
                ];

              
                if (!empty($dataUsuario['clave_usuario'])) {
                    $claveHasheada = hash("SHA256", $dataUsuario['clave_usuario']);
                    $sqlUpdate .= ", clave = ?";
                    $valoresUpdate[] = $claveHasheada;
                }

                $sqlUpdate .= " WHERE personaId = ?";
                $valoresUpdate[] = $idpersona_pk;

                $stmtUpdate = $this->dbSeguridad->prepare($sqlUpdate);
                $stmtUpdate->execute($valoresUpdate);
            }

            $this->dbSeguridad->commit();

            return [
                'status' => true, 
                'message' => 'Usuario actualizado exitosamente.'
            ];

        } catch (PDOException $e) {
            if ($this->dbSeguridad->inTransaction()) {
                $this->dbSeguridad->rollBack();
            }
            error_log("Error al actualizar usuario: " . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Error de base de datos al actualizar usuario: ' . $e->getMessage()
            ];
        }
    }

    public function updatePersonaConUsuario(int $idpersona_pk, array $data): array
    {
        try {
            $resultadoPersona = $this->updatePersona($idpersona_pk, $data);
            
            if (!$resultadoPersona['status']) {
                return $resultadoPersona;
            }

            // Actualizar usuario si se proporcionan datos
            if (isset($data['actualizar_usuario']) && $data['actualizar_usuario'] == "1") {
                $resultadoUsuario = $this->updateUsuario($idpersona_pk, $data);
                
                if (!$resultadoUsuario['status']) {
                    return [
                        'status' => false,
                        'message' => 'Persona actualizada, pero error al actualizar usuario: ' . $resultadoUsuario['message']
                    ];
                }
                
                return [
                    'status' => true,
                    'message' => 'Persona y usuario actualizados exitosamente.'
                ];
            }
            
            return [
                'status' => true,
                'message' => 'Persona actualizada exitosamente.'
            ];

        } catch (Exception $e) {
            error_log("Error en updatePersonaConUsuario: " . $e->getMessage());
            
            return [
                'status' => false,
                'message' => 'Error general en el proceso de actualización.'
            ];
        }
    }

    public function selectPersonaById(int $idpersona_pk)
    {
        $sql = "SELECT 
                        p.idpersona AS idpersona_pk, 
                        p.nombre AS persona_nombre, 
                        p.apellido AS persona_apellido, 
                        p.identificacion AS persona_cedula, 
                        p.genero AS persona_genero, 
                        p.fecha_nacimiento AS persona_fecha, 
                        p.correo_electronico AS persona_correo_info, 
                        p.direccion AS persona_direccion, 
                        p.observaciones AS persona_observaciones, 
                        p.estatus AS persona_estatus, 
                        p.telefono_principal,
                        u.idusuario, 
                        u.idrol, 
                        u.usuario AS nombre_usuario_login, 
                        u.correo AS correo_usuario_login, 
                        u.estatus AS estatus_usuario,
                        r.nombre AS rol_nombre
                    FROM personas p
                    LEFT JOIN {$this->conexion->getDatabaseSeguridad()}.usuario u ON p.idpersona = u.personaId
                    LEFT JOIN {$this->conexion->getDatabaseSeguridad()}.roles r ON u.idrol = r.idrol
                    WHERE p.idpersona = ?";
        try {
            $stmt = $this->dbPrincipal->prepare($sql);
            $stmt->execute([$idpersona_pk]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("PersonasModel::selectPersonaById -> " . $e->getMessage());
            return false;
        }
    }

    public function deletePersonaById(int $idpersona_pk): bool
    {
        if (!$this->dbPrincipal || !$this->dbSeguridad) {
            error_log("PersonasModel::deletePersonaById -> Conexión a la base de datos no establecida.");
            return false;
        }
        
        try {
            $this->dbPrincipal->beginTransaction();
            $this->dbSeguridad->beginTransaction();

            $sqlGetIdentificacion = "SELECT identificacion FROM personas WHERE idpersona = ?";
            $stmtGetIdentificacion = $this->dbPrincipal->prepare($sqlGetIdentificacion);
            $stmtGetIdentificacion->execute([$idpersona_pk]);
            $personaData = $stmtGetIdentificacion->fetch(PDO::FETCH_ASSOC);

            if (!$personaData) {
                $this->dbPrincipal->rollBack();
                $this->dbSeguridad->rollBack();
                return false;
            }
            
            $personaIdentificacion = $personaData['identificacion'];

            $sqlPersona = "UPDATE personas SET estatus = 'INACTIVO', fecha_modificacion = NOW() WHERE idpersona = ?";
            $stmtPersona = $this->dbPrincipal->prepare($sqlPersona);
            $stmtPersona->execute([$idpersona_pk]);

            $sqlUsuario = "UPDATE usuario SET estatus = 'INACTIVO' WHERE personaId = ?";
            $stmtUsuario = $this->dbSeguridad->prepare($sqlUsuario);
            $stmtUsuario->execute([$personaIdentificacion]);
            
            $this->dbPrincipal->commit();
            $this->dbSeguridad->commit();
            return $stmtPersona->rowCount() > 0;

        } catch (PDOException $e) {
            $this->dbPrincipal->rollBack();
            $this->dbSeguridad->rollBack();
            error_log("PersonasModel::deletePersonaById -> " . $e->getMessage());
            return false;
        }
    }

    public function selectAllPersonasActivas()
    {
        $sql = "SELECT p.idpersona as idpersona_pk, p.nombre as persona_nombre, p.apellido as persona_apellido,
                    p.identificacion as persona_cedula, p.genero as persona_genero,
                    u.correo as correo_usuario_login, p.telefono_principal, p.estatus as persona_estatus,
                    r.nombre as rol_nombre
                FROM personas p
                LEFT JOIN {$this->conexion->getDatabaseSeguridad()}.usuario u ON p.identificacion = u.personaId
                LEFT JOIN {$this->conexion->getDatabaseSeguridad()}.roles r ON u.idrol = r.idrol
                WHERE p.estatus = 'ACTIVO'
                ORDER BY p.nombre ASC, p.apellido ASC"; 

        try {
            $stmt = $this->dbPrincipal->query($sql); 
            $personas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "message" => "Personas obtenidas.", "data" => $personas];
        } catch (PDOException $e) {
            error_log("PersonasModel::selectAllPersonasActivas - Error al seleccionar personas: " . $e->getMessage());
            return ["status" => false, "message" => "Error al obtener personas: " . $e->getMessage(), "data" => []];
        }
    }

    public function selectAllRoles()
    {
        $sql = "SELECT idrol, nombre FROM roles WHERE estatus = 'ACTIVO' ORDER BY nombre ASC";
        
        try {
            $stmt = $this->dbSeguridad->query($sql);
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "message" => "Roles obtenidos.", "data" => $roles];
        } catch (PDOException $e) {
            error_log("PersonasModel::selectAllRoles - Error al seleccionar roles: " . $e->getMessage());
            return ["status" => false, "message" => "Error al obtener roles: " . $e->getMessage(), "data" => []];
        }
    }
}
?>
