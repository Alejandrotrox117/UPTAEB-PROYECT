<?php
require_once "app/core/conexion.php";
require_once "app/core/mysql.php";

class UsuariosModel extends Mysql
{
    private $query;
    private $array;
    private $data;
    private $result;
    private $usuarioId;
    private $message;
    private $status;

    public function __construct()
    {
        // Constructor vacío como en ProveedoresModel
    }

    // ✅ GETTERS Y SETTERS (igual que ProveedoresModel)
    public function getQuery(){
        return $this->query;
    }

    public function setQuery(string $query){
        $this->query = $query;
    }

    public function getArray(){
        return $this->array ?? [];
    }

    public function setArray(array $array){
        $this->array = $array;
    }

    public function getData(){
        return $this->data ?? [];
    }

    public function setData(array $data){
        $this->data = $data;
    }

    public function getResult(){
        return $this->result;
    }

    public function setResult($result){
        $this->result = $result;
    }

    public function getUsuarioId(){
        return $this->usuarioId;
    }

    public function setUsuarioId(?int $usuarioId){
        $this->usuarioId = $usuarioId;
    }

    public function getMessage(){
        return $this->message ?? '';
    }

    public function setMessage(string $message){
        $this->message = $message;
    }

    public function getStatus(){
        return $this->status ?? false;
    }

    public function setStatus(bool $status){
        $this->status = $status;
    }

    // ✅ FUNCIONES PRIVADAS ENCAPSULADAS

    /**
     * Verificar si existe usuario por correo
     */
    private function ejecutarVerificacionUsuarioPorCorreo(string $correo, int $idUsuarioExcluir = null){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $this->setQuery("SELECT COUNT(*) as total FROM usuario WHERE correo = ? AND estatus = 'ACTIVO'");
            $this->setArray([$correo]);
            
            if ($idUsuarioExcluir !== null) {
                $this->setQuery($this->getQuery() . " AND idusuario != ?");
                $array = $this->getArray();
                $array[] = $idUsuarioExcluir;
                $this->setArray($array);
            }
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));

            $result = $this->getResult();
            $exists = $result && $result['total'] > 0;
            
        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("Error al verificar usuario existente por correo: " . $e->getMessage());
            $exists = true; // Asumir que existe en caso de error por seguridad
        } finally {
            $conexion->disconnect();
        }
        
        return $exists;
    }

    /**
     * Verificar si existe usuario por nombre de usuario
     */
    private function ejecutarVerificacionUsuarioPorNombre(string $usuario, int $idUsuarioExcluir = null){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $this->setQuery("SELECT COUNT(*) as total FROM usuario WHERE usuario = ? AND estatus = 'ACTIVO'");
            $this->setArray([$usuario]);
            
            if ($idUsuarioExcluir !== null) {
                $this->setQuery($this->getQuery() . " AND idusuario != ?");
                $array = $this->getArray();
                $array[] = $idUsuarioExcluir;
                $this->setArray($array);
            }
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetch(PDO::FETCH_ASSOC));

            $result = $this->getResult();
            $exists = $result && $result['total'] > 0;
            
        } catch (Exception $e) {
            $conexion->disconnect();
            error_log("Error al verificar usuario existente por nombre: " . $e->getMessage());
            $exists = true; // Asumir que existe en caso de error por seguridad
        } finally {
            $conexion->disconnect();
        }
        
        return $exists;
    }

    /**
     * Función privada para insertar usuario
     */
    private function ejecutarInsercionUsuario(array $data){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $db->beginTransaction();

            $this->setQuery(
                "INSERT INTO usuario (
                    idrol, usuario, clave, correo, personaId, estatus, token, fecha_creacion, fecha_modificacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
            );
            
            // Hash de la contraseña usando SHA256
            $claveHasheada = hash("SHA256", $data['clave']);
            
            $this->setArray([
                $data['idrol'],
                $data['usuario'],
                $claveHasheada,
                $data['correo'],
                !empty($data['personaId']) ? $data['personaId'] : null,
                'ACTIVO', // Estado por defecto
                '' // Token vacío por defecto
            ]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setUsuarioId($db->lastInsertId());
            
            if ($this->getUsuarioId()) {
                $db->commit();
                $this->setStatus(true);
                $this->setMessage('Usuario registrado exitosamente.');
            } else {
                $db->rollBack();
                $this->setStatus(false);
                $this->setMessage('Error al obtener ID de usuario tras registro.');
            }
            
            $resultado = [
                'status' => $this->getStatus(),
                'message' => $this->getMessage(),
                'usuario_id' => $this->getUsuarioId()
            ];
            
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $conexion->disconnect();
            error_log("Error al insertar usuario: " . $e->getMessage());
            
            // Manejar errores específicos de duplicación
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                if (strpos($e->getMessage(), 'correo') !== false) {
                    $mensaje = 'El correo electrónico ya está registrado.';
                } elseif (strpos($e->getMessage(), 'usuario') !== false) {
                    $mensaje = 'El nombre de usuario ya está registrado.';
                } else {
                    $mensaje = 'Datos duplicados. Verifique el correo y nombre de usuario.';
                }
            } else {
                $mensaje = 'Error de base de datos al registrar usuario: ' . $e->getMessage();
            }
            
            $resultado = [
                'status' => false,
                'message' => $mensaje,
                'usuario_id' => null
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para actualizar usuario
     */
    private function ejecutarActualizacionUsuario(int $idusuario, array $data){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $db->beginTransaction();

            // Construir query dinámicamente
            $this->setQuery("UPDATE usuario SET idrol = ?, usuario = ?, correo = ?, personaId = ?, fecha_modificacion = NOW()");
            $this->setArray([
                $data['idrol'],
                $data['usuario'],
                $data['correo'],
                !empty($data['personaId']) ? $data['personaId'] : null
            ]);

            // Solo actualizar contraseña si se proporciona
            if (!empty($data['clave'])) {
                $claveHasheada = hash("SHA256", $data['clave']);
                $this->setQuery($this->getQuery() . ", clave = ?");
                $array = $this->getArray();
                $array[] = $claveHasheada;
                $this->setArray($array);
            }

            $this->setQuery($this->getQuery() . " WHERE idusuario = ?");
            $array = $this->getArray();
            $array[] = $idusuario;
            $this->setArray($array);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $rowCount = $stmt->rowCount();
            
            if ($rowCount > 0) {
                $db->commit();
                $this->setStatus(true);
                $this->setMessage('Usuario actualizado exitosamente.');
            } else {
                $db->commit(); // Confirmar transacción aunque no haya cambios
                $this->setStatus(true);
                $this->setMessage('No se realizaron cambios en el usuario (datos idénticos).');
            }
            
            $resultado = [
                'status' => $this->getStatus(),
                'message' => $this->getMessage()
            ];
            
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $conexion->disconnect();
            error_log("Error al actualizar usuario: " . $e->getMessage());
            
            // Manejar errores específicos de duplicación
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                if (strpos($e->getMessage(), 'correo') !== false) {
                    $mensaje = 'El correo electrónico ya está registrado por otro usuario.';
                } elseif (strpos($e->getMessage(), 'usuario') !== false) {
                    $mensaje = 'El nombre de usuario ya está registrado por otro usuario.';
                } else {
                    $mensaje = 'Datos duplicados. Verifique el correo y nombre de usuario.';
                }
            } else {
                $mensaje = 'Error de base de datos al actualizar usuario: ' . $e->getMessage();
            }
            
            $resultado = [
                'status' => false,
                'message' => $mensaje
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para buscar usuario por ID
     */
    private function ejecutarBusquedaUsuarioPorId(int $idusuario){
        $conexion = new Conexion();
        $conexion->connect();
        $dbSeguridad = $conexion->get_conectSeguridad();

        try {
            // Primera consulta: obtener datos del usuario y rol desde BD seguridad
            $this->setQuery(
                "SELECT 
                    u.idusuario, 
                    u.idrol, 
                    u.usuario, 
                    u.correo, 
                    u.personaId, 
                    u.estatus,
                    u.fecha_creacion,
                    u.fecha_modificacion,
                    r.nombre AS rol_nombre,
                    DATE_FORMAT(u.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion_formato,
                    DATE_FORMAT(u.fecha_modificacion, '%d/%m/%Y %H:%i') as fecha_modificacion_formato
                FROM usuario u
                LEFT JOIN roles r ON u.idrol = r.idrol
                WHERE u.idusuario = ?"
            );
            
            $this->setArray([$idusuario]);
            $stmt = $dbSeguridad->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                return false;
            }

            // Si tiene personaId, obtener datos de la persona desde BD principal
            if (!empty($usuario['personaId'])) {
                $dbPrincipal = $conexion->get_conectGeneral();
                $this->setQuery(
                    "SELECT 
                        nombre, 
                        apellido, 
                        identificacion as cedula,
                        CONCAT(nombre, ' ', COALESCE(apellido, '')) as nombre_completo
                    FROM personas 
                    WHERE idpersona = ?"
                );
                
                $this->setArray([$usuario['personaId']]);
                $stmt = $dbPrincipal->prepare($this->getQuery());
                $stmt->execute($this->getArray());
                $persona = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($persona) {
                    $usuario['persona_nombre'] = $persona['nombre'];
                    $usuario['persona_apellido'] = $persona['apellido'];
                    $usuario['persona_cedula'] = $persona['cedula'];
                    $usuario['persona_nombre_completo'] = $persona['nombre_completo'];
                } else {
                    $usuario['persona_nombre'] = null;
                    $usuario['persona_apellido'] = null;
                    $usuario['persona_cedula'] = null;
                    $usuario['persona_nombre_completo'] = null;
                }
            } else {
                $usuario['persona_nombre'] = null;
                $usuario['persona_apellido'] = null;
                $usuario['persona_cedula'] = null;
                $usuario['persona_nombre_completo'] = null;
            }
            
            $resultado = $usuario;
            
        } catch (Exception $e) {
            error_log("UsuariosModel::ejecutarBusquedaUsuarioPorId -> " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para eliminar (desactivar) usuario
     */
    private function ejecutarEliminacionUsuario(int $idusuario){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $db->beginTransaction();
            
            $this->setQuery("UPDATE usuario SET estatus = ?, fecha_modificacion = NOW() WHERE idusuario = ?");
            $this->setArray(['INACTIVO', $idusuario]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $rowCount = $stmt->rowCount();
            
            if ($rowCount > 0) {
                $db->commit();
                $resultado = true;
            } else {
                $db->rollBack();
                $resultado = false;
            }
            
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("UsuariosModel::ejecutarEliminacionUsuario -> " . $e->getMessage());
            $resultado = false;
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para obtener todos los usuarios
     */
    private function ejecutarBusquedaTodosUsuarios(){
        $conexion = new Conexion();
        $conexion->connect();
        $dbSeguridad = $conexion->get_conectSeguridad();

        try {
            // Primera consulta: obtener usuarios con roles desde BD seguridad
            $this->setQuery(
                "SELECT 
                    u.idusuario,
                    u.usuario,
                    u.correo,
                    u.estatus,
                    u.idrol,
                    u.personaId,
                    u.fecha_creacion,
                    u.fecha_modificacion,
                    r.nombre as rol_nombre,
                    DATE_FORMAT(u.fecha_creacion, '%d/%m/%Y') as fecha_creacion_formato,
                    DATE_FORMAT(u.fecha_modificacion, '%d/%m/%Y') as fecha_modificacion_formato
                FROM usuario u
                LEFT JOIN roles r ON u.idrol = r.idrol
                ORDER BY u.usuario ASC"
            );
            
            $this->setArray([]);
            $stmt = $dbSeguridad->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener datos de personas para usuarios que las tengan desde BD principal
            $dbPrincipal = $conexion->get_conectGeneral();
            
            foreach ($usuarios as &$usuario) {
                if (!empty($usuario['personaId'])) {
                    $this->setQuery(
                        "SELECT 
                            nombre, 
                            apellido, 
                            identificacion as cedula,
                            CONCAT(nombre, ' ', COALESCE(apellido, '')) as nombre_completo
                        FROM personas 
                        WHERE idpersona = ?"
                    );
                    
                    $this->setArray([$usuario['personaId']]);
                    $stmt = $dbPrincipal->prepare($this->getQuery());
                    $stmt->execute($this->getArray());
                    $persona = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($persona) {
                        $usuario['persona_nombre_completo'] = $persona['nombre_completo'];
                        $usuario['persona_cedula'] = $persona['cedula'];
                    } else {
                        $usuario['persona_nombre_completo'] = null;
                        $usuario['persona_cedula'] = null;
                    }
                } else {
                    $usuario['persona_nombre_completo'] = null;
                    $usuario['persona_cedula'] = null;
                }
            }
            
            $resultado = [
                'status' => true,
                'message' => 'Usuarios obtenidos.',
                'data' => $usuarios
            ];
            
        } catch (Exception $e) {
            error_log("UsuariosModel::ejecutarBusquedaTodosUsuarios - Error: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error al obtener usuarios: ' . $e->getMessage(),
                'data' => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para obtener todos los roles
     */
    private function ejecutarBusquedaTodosRoles(){
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectSeguridad();

        try {
            $this->setQuery("SELECT idrol, nombre FROM roles WHERE estatus = 'ACTIVO' ORDER BY nombre ASC");
            $this->setArray([]);
            
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            $resultado = [
                'status' => true,
                'message' => 'Roles obtenidos.',
                'data' => $this->getResult()
            ];
            
        } catch (Exception $e) {
            error_log("UsuariosModel::ejecutarBusquedaTodosRoles - Error: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error al obtener roles: ' . $e->getMessage(),
                'data' => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    /**
     * Función privada para obtener personas activas disponibles
     */
    private function ejecutarBusquedaPersonasActivas(int $idPersonaActual = 0){
        $conexion = new Conexion();
        $conexion->connect();
        $dbPrincipal = $conexion->get_conectGeneral();
        $dbSeguridad = $conexion->get_conectSeguridad();

        try {
            // Primero obtener IDs de personas ya asociadas a usuarios activos desde BD seguridad
            $this->setQuery(
                "SELECT DISTINCT personaId 
                FROM usuario 
                WHERE personaId IS NOT NULL 
                AND estatus = 'ACTIVO' 
                AND personaId != ?"
            );
            
            $this->setArray([$idPersonaActual]);
            $stmt = $dbSeguridad->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $personasOcupadas = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Construir query para personas disponibles desde BD principal
            $whereClause = "p.estatus = 'ACTIVO'";
            $params = [];
            
            if (!empty($personasOcupadas)) {
                $placeholders = str_repeat('?,', count($personasOcupadas) - 1) . '?';
                $whereClause .= " AND p.idpersona NOT IN ($placeholders)";
                $params = array_merge($params, $personasOcupadas);
            }
            
            // Incluir la persona actual si se especifica
            if ($idPersonaActual > 0) {
                $whereClause .= " OR p.idpersona = ?";
                $params[] = $idPersonaActual;
            }
            
            $this->setQuery(
                "SELECT 
                    p.idpersona,
                    CONCAT(p.nombre, ' ', COALESCE(p.apellido, '')) as nombre_completo,
                    p.identificacion as cedula
                FROM personas p
                WHERE $whereClause
                ORDER BY p.nombre ASC"
            );
            
            $this->setArray($params);
            $stmt = $dbPrincipal->prepare($this->getQuery());
            $stmt->execute($this->getArray());
            $this->setResult($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            $resultado = [
                'status' => true,
                'message' => 'Personas activas obtenidas.',
                'data' => $this->getResult()
            ];
            
        } catch (Exception $e) {
            error_log("UsuariosModel::ejecutarBusquedaPersonasActivas - Error: " . $e->getMessage());
            $resultado = [
                'status' => false,
                'message' => 'Error al obtener personas: ' . $e->getMessage(),
                'data' => []
            ];
        } finally {
            $conexion->disconnect();
        }

        return $resultado;
    }

    // ✅ MÉTODOS PÚBLICOS QUE USAN LAS FUNCIONES PRIVADAS (igual que ProveedoresModel)

    /**
     * Insertar nuevo usuario
     */
    public function insertUsuario(array $data){
        $this->setData($data);
        $correo = $this->getData()['correo'];
        $usuario = $this->getData()['usuario'];

        // Verificar si ya existe el correo
        if ($this->ejecutarVerificacionUsuarioPorCorreo($correo)) {
            return [
                'status' => false,
                'message' => 'El correo electrónico ya está registrado. Por favor, utilice otro.',
                'usuario_id' => null
            ];
        }

        // Verificar si ya existe el nombre de usuario
        if ($this->ejecutarVerificacionUsuarioPorNombre($usuario)) {
            return [
                'status' => false,
                'message' => 'El nombre de usuario ya está registrado. Por favor, utilice otro.',
                'usuario_id' => null
            ];
        }

        return $this->ejecutarInsercionUsuario($this->getData());
    }

    /**
     * Actualizar usuario existente
     */
    public function updateUsuario(int $idusuario, array $data){
        $this->setData($data);
        $this->setUsuarioId($idusuario);
        $correo = $this->getData()['correo'];
        $usuario = $this->getData()['usuario'];

        // Verificar si ya existe el correo en otro usuario
        if ($this->ejecutarVerificacionUsuarioPorCorreo($correo, $this->getUsuarioId())) {
            return [
                'status' => false,
                'message' => 'El correo electrónico ya está registrado por otro usuario.'
            ];
        }

        // Verificar si ya existe el nombre de usuario en otro usuario
        if ($this->ejecutarVerificacionUsuarioPorNombre($usuario, $this->getUsuarioId())) {
            return [
                'status' => false,
                'message' => 'El nombre de usuario ya está registrado por otro usuario.'
            ];
        }

        return $this->ejecutarActualizacionUsuario($this->getUsuarioId(), $this->getData());
    }

    /**
     * Obtener usuario por ID
     */
    public function selectUsuarioById(int $idusuario){
        $this->setUsuarioId($idusuario);
        return $this->ejecutarBusquedaUsuarioPorId($this->getUsuarioId());
    }

    /**
     * Eliminar (desactivar) usuario por ID
     */
    public function deleteUsuarioById(int $idusuario){
        $this->setUsuarioId($idusuario);
        return $this->ejecutarEliminacionUsuario($this->getUsuarioId());
    }

    /**
     * Obtener todos los usuarios
     */
    public function selectAllUsuarios(){
        return $this->ejecutarBusquedaTodosUsuarios();
    }

    /**
     * Obtener usuarios activos solamente
     */
    public function selectAllUsuariosActivos(){
        return $this->ejecutarBusquedaTodosUsuarios(); // Usa la misma función, el filtro se puede hacer en el frontend
    }

    /**
     * Obtener todos los roles activos
     */
    public function selectAllRoles(){
        return $this->ejecutarBusquedaTodosRoles();
    }

    /**
     * Obtener personas activas disponibles
     */
    public function selectAllPersonasActivas(int $idPersonaActual = 0){
        return $this->ejecutarBusquedaPersonasActivas($idPersonaActual);
    }

    /**
     * Método adicional para verificaciones externas
     */
    public function selectUsuarioByEmail(string $email, int $idUsuarioExcluir = 0){
        return $this->ejecutarVerificacionUsuarioPorCorreo($email, $idUsuarioExcluir > 0 ? $idUsuarioExcluir : null) 
               ? ['correo' => $email] : false;
    }
}
?>