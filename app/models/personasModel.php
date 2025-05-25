<?php
require_once "app/core/conexion.php"; // Asegúrate que la ruta sea correcta
require_once "app/core/mysql.php"; // Asegúrate que la ruta sea correcta

class PersonasModel extends mysql
{
    private $conexion;
    private $dbPrincipal; // Para la tabla 'personas'

    private $conexionObjetoSeguridad;
    private $dbSeguridad; // Para las tablas 'usuario', 'roles'
    private $principal; // Conexión a la base de datos principal

    // Propiedades de la tabla personas (en DB Principal)
    private $idpersona;
    private $nombre;
    private $apellido;
    private $cedula; // En la tabla 'personas' puede llamarse 'identificacion'
    private $rif;
    private $genero;
    private $fecha_nacimiento;
    private $correo_electronico_persona;
    private $direccion;
    private $estado_residencia;
    private $ciudad_residencia;
    private $pais_residencia;
    private $tipo_persona;
    private $observaciones;
    private $estatus_persona;
    private $telefono_principal;


    public function __construct()
    {
        $this->conexion = new Conexion();
        $this->conexion->connect();
        $this->dbPrincipal = $this->conexion->get_conectGeneral();  // Asumo que esta función devuelve la conexión principal
    }

    // Getters y Setters para propiedades de Persona (igual que antes)
    // ... (coloca aquí todos los getters y setters que tenías)

    // Asumiendo que tienes una instancia de Conexion
public function insertPersona(array $data): array
{
    try {
        $dbGeneral = $this->conexion->get_conectGeneral();
        $dbGeneral->beginTransaction();

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
            'activo'
        ];
        
        $stmtPersona = $dbGeneral->prepare($sqlPersona);
        $insertExitosoPersona = $stmtPersona->execute($valoresPersona);

        $idPersonaInsertada = $dbGeneral->lastInsertId();

        if (!$idPersonaInsertada) {
            $dbGeneral->rollBack();
            error_log("Error: No se pudo obtener el lastInsertId para la persona.");
            return [
                'status' => false, 
                'message' => 'Error al obtener ID de persona tras registro.',
                'persona_id' => null
            ];
        }

        $dbGeneral->commit();

        return [
            'status' => true, 
            'message' => 'Persona registrada exitosamente (ID: ' . $idPersonaInsertada . ').',
            'persona_id' => $idPersonaInsertada
        ];

    } catch (PDOException $e) {
        if ($dbGeneral->inTransaction()) {
            $dbGeneral->rollBack();
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
        $dbSeguridad = $this->conexion->get_conectSeguridad();
        $dbSeguridad->beginTransaction();

        $claveHasheada = password_hash($dataUsuario['clave_usuario'], PASSWORD_DEFAULT);
        $correoTablaUsuario = $dataUsuario['correo_electronico_usuario'];  
        $rol = $dataUsuario['idrol_usuario'];

        $sqlUsuario = "INSERT INTO usuario (idrol, clave, correo, personaId, estatus) VALUES (?, ?, ?, ?, ?)";
        
        $valoresUsuario = [
            $rol,
            $claveHasheada,
            $correoTablaUsuario,  
            $personaId, 
            'activo'
        ];

        $stmtUsuario = $dbSeguridad->prepare($sqlUsuario);
        $insertExitosoUsuario = $stmtUsuario->execute($valoresUsuario);
        
        if (!$insertExitosoUsuario || $stmtUsuario->rowCount() === 0) {
            $dbSeguridad->rollBack();
            $errorInfo = $stmtUsuario->errorInfo();
            error_log("Error al insertar usuario: " . print_r($errorInfo, true));
            return [
                'status' => false, 
                'message' => 'Error SQL al registrar usuario: ' . $errorInfo[2]
            ];
        }

        $dbSeguridad->commit();

        return [
            'status' => true, 
            'message' => 'Usuario registrado exitosamente para persona ID: ' . $personaId
        ];

    } catch (PDOException $e) {
        if ($dbSeguridad->inTransaction()) {
            $dbSeguridad->rollBack();
        }
        error_log("Error al insertar usuario: " . $e->getMessage());
        return [
            'status' => false, 
            'message' => 'Error de base de datos al registrar usuario: ' . $e->getMessage()
        ];
    }
}

// Función coordinadora que maneja transacciones distribuidas
public function insertPersonaConUsuario(array $data): array
{
    $personaId = null;
    $personaCreada = false;
    $usuarioCreado = false;

    try {
        // Registrar persona
        $resultadoPersona = $this->insertPersona($data);
        
        if (!$resultadoPersona['status']) {
            return $resultadoPersona;
        }

        $personaId = $resultadoPersona['persona_id'];
        $personaCreada = true;
        
        // Si se debe crear usuario
        if (isset($data['crear_usuario']) && $data['crear_usuario'] == "1") {
            $resultadoUsuario = $this->insertUsuario($personaId, $data);
            
            if (!$resultadoUsuario['status']) {
                
                return [
                    'status' => false,
                    'message' => 'Error al crear usuario. Se revirtieron todos los cambios: ' . $resultadoUsuario['message'],
                    'persona_id' => null
                ];
            }
            
            $usuarioCreado = true;
            
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
            'message' => 'Error general en el proceso. Se revirtieron todos los cambios.',
            'persona_id' => null
        ];
    }
}


    public function updatePersonaConUsuario(array $data): array
    {
        if (!$this->dbPrincipal || !$this->dbSeguridad || !isset($data['idpersona_pk'])) {
            return ["status" => false, "message" => "ID de persona no proporcionado o error de conexión."];
        }

        $this->dbPrincipal->beginTransaction();
        $this->dbSeguridad->beginTransaction();
        try {
            // Actualizar tabla personas (DB Principal)
            $sqlPersona = "UPDATE personas SET
                                nombre = ?, apellido = ?, identificacion = ?, rif = ?, genero = ?, fecha_nacimiento = ?,
                                correo_electronico = ?, direccion = ?, estado_residencia = ?, ciudad_residencia = ?, pais_residencia = ?,
                                tipo_persona = ?, observaciones = ?, telefono_principal = ?, fecha_modificacion = NOW()
                           WHERE idpersona = ?";
            $arrValuesPersona = [
                $data['nombre'],
                $data['apellido'],
                $data['cedula'], // Nueva cédula/identificación para la tabla 'personas'
                $data['rif'] ?: null,
                $data['genero'] ?: null,
                $data['fecha_nacimiento'] ?: null,
                $data['correo_electronico_persona'] ?: null,
                $data['direccion'] ?: null,
                $data['estado_residencia'] ?: null,
                $data['ciudad_residencia'] ?: null,
                $data['pais_residencia'] ?: null,
                $data['tipo_persona'] ?: null,
                $data['observaciones'] ?: null,
                $data['telefono_principal'],
                $data['idpersona_pk'] // PK de la tabla personas
            ];
            $stmtPersona = $this->dbPrincipal->prepare($sqlPersona);
            $stmtPersona->execute($arrValuesPersona);

            // Actualizar usuario si se proporcionan datos (DB Seguridad)
            $personaIdOriginalParaUsuario = $data['cedula_original']; // Cédula original para buscar el usuario
            $nuevaPersonaIdParaUsuario = $data['cedula']; // Nueva cédula para actualizar 'personaId' en 'usuario'

            if (isset($data['correo_electronico_usuario']) && !empty($data['correo_electronico_usuario']) && isset($data['idrol_usuario'])) {
                $sqlCheckUser = "SELECT idusuario FROM usuario WHERE personaId = ?";
                $stmtCheck = $this->dbSeguridad->prepare($sqlCheckUser);
                $stmtCheck->execute([$personaIdOriginalParaUsuario]);
                $usuarioExistente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if ($usuarioExistente) {
                    $idusuario = $usuarioExistente['idusuario'];
                    $paramsUpdateUsuario = [];
                    $sqlBaseUpdateUsuario = "UPDATE usuario SET idrol = ?, usuario = ?, correo = ?, personaId = ?";
                    $paramsUpdateUsuario = [
                        $data['idrol_usuario'],
                        $data['correo_electronico_usuario'],
                        $data['correo_electronico_usuario'],
                        $nuevaPersonaIdParaUsuario
                    ];
                    
                    if (!empty($data['clave_usuario'])) {
                        $claveHasheada = password_hash($data['clave_usuario'], PASSWORD_DEFAULT);
                        $sqlBaseUpdateUsuario .= ", clave = ?";
                        $paramsUpdateUsuario[] = $claveHasheada;
                    }
                    $sqlBaseUpdateUsuario .= " WHERE idusuario = ?";
                    $paramsUpdateUsuario[] = $idusuario;

                    $stmtUpdateUsuario = $this->dbSeguridad->prepare($sqlBaseUpdateUsuario);
                    $stmtUpdateUsuario->execute($paramsUpdateUsuario);
                } else {
                     // Opcional: Crear usuario si no existe y se proporcionan datos de usuario completos
                    if (!empty($data['clave_usuario']) && !empty($data['correo_electronico_usuario']) && !empty($data['idrol_usuario'])) {
                        $claveHasheada = password_hash($data['clave_usuario'], PASSWORD_DEFAULT);
                        $sqlInsertUsuario = "INSERT INTO usuario (idrol, usuario, clave, correo, personaId, estatus, token) 
                                           VALUES (?, ?, ?, ?, ?, 'ACTIVO', '')";
                        $stmtInsertUsuario = $this->dbSeguridad->prepare($sqlInsertUsuario);
                        $stmtInsertUsuario->execute([
                            $data['idrol_usuario'],
                            $data['correo_electronico_usuario'],
                            $claveHasheada,
                            $data['correo_electronico_usuario'],
                            $nuevaPersonaIdParaUsuario,
                        ]);
                        if ($stmtInsertUsuario->rowCount() == 0) {
                            $this->dbPrincipal->rollBack();
                            $this->dbSeguridad->rollBack();
                            return ["status" => false, "message" => "Error al crear el usuario asociado durante la actualización."];
                        }
                    }
                }
            }

            $this->dbPrincipal->commit();
            $this->dbSeguridad->commit();
            return ["status" => true, "message" => "Persona actualizada exitosamente."];

        } catch (PDOException $e) {
            $this->dbPrincipal->rollBack();
            $this->dbSeguridad->rollBack();
            error_log("PersonasModel::updatePersonaConUsuario -> " . $e->getMessage());
            // ... (manejo de errores duplicados como antes) ...
            return ["status" => false, "message" => "Error interno del servidor al actualizar: " . $e->getMessage()];
        }
    }

    public function selectPersonaById(int $idpersona_pk)
    {
        // Consulta principal a 'personas' y LEFT JOIN a 'usuario' y 'roles' en la BD de seguridad
        $sql = "SELECT p.idpersona as idpersona_pk, p.nombre as persona_nombre, p.apellido as persona_apellido, 
                       p.identificacion as persona_cedula, p.rif as persona_rif, p.genero as persona_genero, 
                       p.fecha_nacimiento as persona_fecha, p.correo_electronico as persona_correo_info,
                       p.direccion as persona_direccion, p.estado_residencia as persona_estado, p.ciudad_residencia as persona_ciudad,
                       p.pais_residencia as persona_pais, p.tipo_persona, p.observaciones as persona_observaciones,
                       p.estatus as persona_estatus, p.telefono_principal,
                       u.idusuario, u.idrol, u.usuario as nombre_usuario_login, u.correo as correo_usuario_login, u.estatus as estatus_usuario,
                       r.nombre as rol_nombre
                FROM personas p
                LEFT JOIN {$this->dbSeguridad->getDbName($this->dbSeguridad)}.usuario u ON p.identificacion = u.personaId 
                LEFT JOIN {$this->dbSeguridad->getDbName($this->dbSeguridad)}.roles r ON u.idrol = r.idrol
                WHERE p.idpersona = ?";
        try {
            if (!$this->dbPrincipal) {
                throw new Exception("Conexión a la base de datos principal no establecida.");
            }
            $stmt = $this->dbPrincipal->prepare($sql);
            $stmt->execute([$idpersona_pk]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("PersonasModel::selectPersonaById -> " . $e->getMessage());
            return false;
        } catch (PDOException $e) {
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
        $this->dbPrincipal->beginTransaction();
        $this->dbSeguridad->beginTransaction();
        try {
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
        // Consulta para obtener todas las personas activas y su información de usuario/rol
        $sql = "SELECT p.idpersona as idpersona_pk, p.nombre as persona_nombre, p.apellido as persona_apellido,
                    p.identificacion as persona_cedula, p.genero as persona_genero,
                    u.correo as correo_usuario_login, p.telefono_principal, p.estatus as persona_estatus,
                    r.nombre as rol_nombre
                FROM personas p
                LEFT JOIN {$this->conexion->getDatabaseSeguridad()}.usuario u ON p.identificacion = u.personaId
                LEFT JOIN {$this->conexion->getDatabaseSeguridad()}.roles r ON u.idrol = r.idrol
                WHERE p.estatus = 'ACTIVO'
                ORDER BY p.nombre ASC, p.apellido ASC"; // Un orden por defecto

        try {
            $stmt = $this->dbPrincipal->query($sql); // Usamos query() ya que no hay placeholders aquí
            $personas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "message" => "Personas obtenidas.", "data" => $personas];
        } catch (PDOException $e) {
            error_log("PersonasModel::selectAllPersonasActivas - Error al seleccionar personas: " . $e->getMessage());
            return ["status" => false, "message" => "Error al obtener personas: " . $e->getMessage(), "data" => []];
        }
    }
}
?>
