<?php
require_once "app/core/conexion.php"; // Asegúrate que la ruta sea correcta
require_once "app/core/mysql.php"; // Asegúrate que la ruta sea correcta

class PersonasModel extends mysql
{
    private $conexionObjetoPrincipal;
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


    public function get_conect_principal(){
        return $this->principal;
    }


    public function __construct()
    {
        $this->conexionObjetoPrincipal = new Conexion();
        $this->conexionObjetoPrincipal->connect();
        $this->dbPrincipal = $this->conexionObjetoPrincipal->get_conectGeneral();  // Asumo que esta función devuelve la conexión principal

        $this->conexionObjetoSeguridad = new Conexion();

        $this->dbSeguridad = $this->conexionObjetoSeguridad->get_conectSeguridad();
    }

    public function __destruct()
    {
        if ($this->conexionObjetoPrincipal) {
            $this->conexionObjetoPrincipal->disconnect();
        }
        if ($this->conexionObjetoSeguridad && $this->conexionObjetoSeguridad !== $this->conexionObjetoPrincipal) {
            $this->conexionObjetoSeguridad->disconnect();
        }
    }

    // Getters y Setters para propiedades de Persona (igual que antes)
    // ... (coloca aquí todos los getters y setters que tenías)

    public function insertPersonaConUsuario(array $data): array
    {
        if (!$this->dbPrincipal || !$this->dbSeguridad) {
            return ["status" => false, "message" => "Error de conexión a una o ambas bases de datos."];
        }

        $this->dbPrincipal->beginTransaction();
        $this->dbSeguridad->beginTransaction();
        try {
            // Insertar en tabla personas (DB Principal)
            // Usaré 'identificacion' para la cédula en la tabla 'personas' según tu DDL de seguridad.
            $sqlPersona = "INSERT INTO personas (
                                nombre, apellido, identificacion, rif, genero, fecha_nacimiento, 
                                correo_electronico, direccion, estado_residencia, ciudad_residencia, pais_residencia,
                                tipo_persona, observaciones, estatus, telefono_principal, fecha_creacion, fecha_modificacion
                           ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmtPersona = $this->dbPrincipal->prepare($sqlPersona);
            $arrValuesPersona = [
                $data['nombre'],
                $data['apellido'],
                $data['cedula'], // Este valor va al campo 'identificacion' de la tabla 'personas'
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
                'ACTIVO', // Estatus por defecto
                $data['telefono_principal']
            ];
            $stmtPersona->execute($arrValuesPersona);
            $idPersonaInsertada = $this->dbPrincipal->lastInsertId();

            if (!$idPersonaInsertada) {
                $this->dbPrincipal->rollBack();
                $this->dbSeguridad->rollBack();
                return ["status" => false, "message" => "Error al registrar la persona."];
            }

            // Si se marca "crear_usuario"
            if (isset($data['crear_usuario']) && $data['crear_usuario'] == '1') {
                if (empty($data['correo_electronico_usuario']) || empty($data['clave_usuario']) || empty($data['idrol_usuario'])) {
                    $this->dbPrincipal->rollBack();
                    $this->dbSeguridad->rollBack();
                    return ["status" => false, "message" => "Faltan datos para crear el usuario (correo, clave o rol)."];
                }

                // 'personaId' en la tabla 'usuario' es la cédula/identificación de la persona.
                $personaIdParaUsuario = $data['cedula'];
                $claveHasheada = password_hash($data['clave_usuario'], PASSWORD_DEFAULT);

                $sqlUsuario = "INSERT INTO usuario (idrol, usuario, clave, correo, personaId, estatus, token) 
                               VALUES (?, ?, ?, ?, ?, ?, '')"; // Token vacío inicialmente
                $stmtUsuario = $this->dbSeguridad->prepare($sqlUsuario);
                $arrValuesUsuario = [
                    $data['idrol_usuario'],
                    $data['correo_electronico_usuario'], // Usando correo como nombre de usuario
                    $claveHasheada,
                    $data['correo_electronico_usuario'],
                    $personaIdParaUsuario,
                    'ACTIVO'
                ];
                $stmtUsuario->execute($arrValuesUsuario);

                if ($stmtUsuario->rowCount() == 0) {
                    $this->dbPrincipal->rollBack();
                    $this->dbSeguridad->rollBack();
                    return ["status" => false, "message" => "Error al registrar el usuario asociado."];
                }
            }

            $this->dbPrincipal->commit();
            $this->dbSeguridad->commit();
            return ["status" => true, "message" => "Persona registrada exitosamente.", "idpersona_pk" => $idPersonaInsertada];

        } catch (PDOException $e) {
            $this->dbPrincipal->rollBack();
            $this->dbSeguridad->rollBack();
            error_log("PersonasModel::insertPersonaConUsuario -> " . $e->getMessage());
            $errorMessage = "Error interno del servidor";
             if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                if (strpos($e->getMessage(), "'usuario'") !== false || strpos($e->getMessage(), "'correo'") !== false) {
                    $errorMessage = "El nombre de usuario o correo electrónico para el usuario ya existe.";
                } elseif (strpos($e->getMessage(), "'identificacion'") !== false) { // Asumiendo que 'identificacion' es la cédula en 'personas' y es UNIQUE
                    $errorMessage = "La cédula/identificación de la persona ya existe.";
                } else {
                    $errorMessage = "Error de duplicidad: " . $e->getMessage();
                }
            } else {
                $errorMessage = "Error interno del servidor: " . $e->getMessage();
            }
            return ["status" => false, "message" => $errorMessage];
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
                LEFT JOIN {$this->conexionObjetoPrincipal->getDatabaseSeguridad()}.usuario u ON p.identificacion = u.personaId
                LEFT JOIN {$this->conexionObjetoPrincipal->getDatabaseSeguridad()}.roles r ON u.idrol = r.idrol
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
