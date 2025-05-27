<?php
require_once "app/core/conexion.php";
require_once "app/core/mysql.php";

class UsuariosModel extends mysql
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

    public function insertUsuario(array $data): array
    {
        try {
            $this->dbSeguridad->beginTransaction();

            $claveHasheada = password_hash($data['clave'], PASSWORD_DEFAULT);

            $sql = "INSERT INTO usuario (idrol, usuario, clave, correo, personaId, estatus, token) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $valores = [
                $data['idrol'],
                $data['usuario'],
                $claveHasheada,
                $data['correo'],
                $data['personaId'] ?: null,
                'ACTIVO',
                ''
            ];
            
            $stmt = $this->dbSeguridad->prepare($sql);
            $insertExitoso = $stmt->execute($valores);

            $idUsuarioInsertado = $this->dbSeguridad->lastInsertId();

            if (!$idUsuarioInsertado) {
                $this->dbSeguridad->rollBack();
                error_log("Error: No se pudo obtener el lastInsertId para el usuario.");
                return [
                    'status' => false, 
                    'message' => 'Error al obtener ID de usuario tras registro.',
                    'usuario_id' => null
                ];
            }

            $this->dbSeguridad->commit();

            return [
                'status' => true, 
                'message' => 'Usuario registrado exitosamente (ID: ' . $idUsuarioInsertado . ').',
                'usuario_id' => $idUsuarioInsertado
            ];

        } catch (PDOException $e) {
            if ($this->dbSeguridad->inTransaction()) {
                $this->dbSeguridad->rollBack();
            }
            error_log("Error al insertar usuario: " . $e->getMessage());
            return [
                'status' => false, 
                'message' => 'Error de base de datos al registrar usuario: ' . $e->getMessage(),
                'usuario_id' => null
            ];
        }
    }

    public function updateUsuario(int $idusuario, array $data): array
    {
        try {
            $this->dbSeguridad->beginTransaction();

            $sql = "UPDATE usuario SET idrol = ?, usuario = ?, correo = ?, personaId = ?";
            $valores = [
                $data['idrol'],
                $data['usuario'],
                $data['correo'],
                $data['personaId'] ?: null
            ];

            // Solo actualizar clave si se proporciona
            if (!empty($data['clave'])) {
                $claveHasheada = password_hash($data['clave'], PASSWORD_DEFAULT);
                $sql .= ", clave = ?";
                $valores[] = $claveHasheada;
            }

            $sql .= " WHERE idusuario = ?";
            $valores[] = $idusuario;
            
            $stmt = $this->dbSeguridad->prepare($sql);
            $updateExitoso = $stmt->execute($valores);

            if (!$updateExitoso || $stmt->rowCount() === 0) {
                $this->dbSeguridad->rollBack();
                return [
                    'status' => false, 
                    'message' => 'No se pudo actualizar el usuario o no se realizaron cambios.'
                ];
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

    public function selectUsuarioById(int $idusuario)
    {
        $sql = "SELECT 
                    u.idusuario, 
                    u.idrol, 
                    u.usuario, 
                    u.correo, 
                    u.personaId, 
                    u.estatus,
                    r.nombre AS rol_nombre,
                    p.nombre AS persona_nombre,
                    p.apellido AS persona_apellido,
                    p.identificacion AS persona_cedula,
                    CONCAT(p.nombre, ' ', p.apellido) AS persona_nombre_completo
                FROM usuario u
                LEFT JOIN roles r ON u.idrol = r.idrol
                LEFT JOIN {$this->conexion->getDatabaseGeneral()}.personas p ON u.personaId = p.identificacion
                WHERE u.idusuario = ?";
        try {
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute([$idusuario]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("UsuariosModel::selectUsuarioById -> " . $e->getMessage());
            return false;
        }
    }

    public function deleteUsuarioById(int $idusuario): bool
    {
        try {
            $this->dbSeguridad->beginTransaction();

            $sql = "UPDATE usuario SET estatus = 'INACTIVO' WHERE idusuario = ?";
            $stmt = $this->dbSeguridad->prepare($sql);
            $stmt->execute([$idusuario]);
            
            $this->dbSeguridad->commit();
            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            $this->dbSeguridad->rollBack();
            error_log("UsuariosModel::deleteUsuarioById -> " . $e->getMessage());
            return false;
        }
    }

    public function selectAllUsuariosActivos()
    {
        $sql = "SELECT 
                    u.idusuario, 
                    u.usuario, 
                    u.correo, 
                    u.estatus,
                    r.nombre AS rol_nombre,
                    p.nombre AS persona_nombre,
                    p.apellido AS persona_apellido,
                    CONCAT(p.nombre, ' ', p.apellido) AS persona_nombre_completo
                FROM usuario u
                LEFT JOIN roles r ON u.idrol = r.idrol
                LEFT JOIN {$this->conexion->getDatabaseGeneral()}.personas p ON u.personaId = p.identificacion
                WHERE u.estatus = 'ACTIVO'
                ORDER BY u.usuario ASC";

        try {
            $stmt = $this->dbSeguridad->query($sql);
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "message" => "Usuarios obtenidos.", "data" => $usuarios];
        } catch (PDOException $e) {
            error_log("UsuariosModel::selectAllUsuariosActivos - Error al seleccionar usuarios: " . $e->getMessage());
            return ["status" => false, "message" => "Error al obtener usuarios: " . $e->getMessage(), "data" => []];
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
            error_log("UsuariosModel::selectAllRoles - Error al seleccionar roles: " . $e->getMessage());
            return ["status" => false, "message" => "Error al obtener roles: " . $e->getMessage(), "data" => []];
        }
    }

    public function selectAllPersonasActivas()
    {
        $sql = "SELECT 
                    idpersona, 
                    identificacion, 
                    CONCAT(nombre, ' ', apellido) AS nombre_completo,
                    nombre,
                    apellido
                FROM {$this->conexion->getDatabaseGeneral()}.personas 
                WHERE estatus = 'ACTIVO'
                ORDER BY nombre ASC, apellido ASC";
        
        try {
            $stmt = $this->dbSeguridad->query($sql);
            $personas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["status" => true, "message" => "Personas obtenidas.", "data" => $personas];
        } catch (PDOException $e) {
            error_log("UsuariosModel::selectAllPersonasActivas - Error al seleccionar personas: " . $e->getMessage());
            return ["status" => false, "message" => "Error al obtener personas: " . $e->getMessage(), "data" => []];
        }
    }
}
?>
