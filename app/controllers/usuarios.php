<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
require_once "helpers/PermisosModuloVerificar.php";
require_once "app/models/bitacoraModel.php";
require_once "helpers/bitacora_helper.php";

class Usuarios extends Controllers
{
    private $bitacoraModel;
    private $BitacoraHelper;

    public function get_model()
    {
        return $this->model;
    }

    public function set_model($model)
    {
        $this->model = $model;
    }

    public function __construct()
    {
        parent::__construct();
        
        $this->bitacoraModel = new BitacoraModel();
        $this->BitacoraHelper = new BitacoraHelper();

        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        if (!PermisosModuloVerificar::verificarAccesoModulo('usuarios')) {
            $this->views->getView($this, "permisos");
            exit();
        }
    }

    public function index()
    {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('usuarios', 'ver')) {
            $this->views->getView($this, "permisos");
            exit();
        }

        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        BitacoraHelper::registrarAccesoModulo('Usuarios', $idusuario, $this->bitacoraModel);

        $data['page_tag'] = "Usuarios";
        $data['page_title'] = "Administración de Usuarios";
        $data['page_name'] = "usuarios";
        $data['page_content'] = "Gestión integral de usuarios del sistema";
        $data['page_functions_js'] = "functions_usuarios.js";
        
        $this->views->getView($this, "usuarios", $data);
    }

    public function createUsuario()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Usuarios', 'crear')) {
                    $arrResponse = array('status' => false, 'message' => 'No tienes permisos para crear usuarios');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $postdata = file_get_contents('php://input');
                $request = json_decode($postdata, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $datosLimpios = [
                    'usuario' => strClean($request['usuario'] ?? ''),
                    'correo' => filter_var($request['correo'] ?? '', FILTER_SANITIZE_EMAIL),
                    'clave' => $request['clave'] ?? '',
                    'idrol' => intval($request['idrol'] ?? 0),
                    'personaId' => !empty($request['personaId']) ? intval($request['personaId']) : null
                ];

                $camposObligatorios = ['usuario', 'correo', 'clave', 'idrol'];
                foreach ($camposObligatorios as $campo) {
                    if (empty($datosLimpios[$campo])) {
                        $arrResponse = array('status' => false, 'message' => 'Todos los campos obligatorios deben ser completados');
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }
                }

                if (strlen($datosLimpios['usuario']) < 3 || strlen($datosLimpios['usuario']) > 20) {
                    $arrResponse = array('status' => false, 'message' => 'El nombre de usuario debe tener entre 3 y 20 caracteres');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                if (!filter_var($datosLimpios['correo'], FILTER_VALIDATE_EMAIL)) {
                    $arrResponse = array('status' => false, 'message' => 'El correo electrónico no es válido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                if (strlen($datosLimpios['clave']) < 6) {
                    $arrResponse = array('status' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                if ($datosLimpios['idrol'] <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'Debe seleccionar un rol válido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrData = array(
                    'usuario' => $datosLimpios['usuario'],
                    'correo' => $datosLimpios['correo'], 
                    'clave' => $datosLimpios['clave'],
                    'idrol' => $datosLimpios['idrol'],
                    'personaId' => $datosLimpios['personaId']
                );

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

                if (!$idusuario) {
                    error_log("ERROR: No se encontró ID de usuario en la sesión durante createUsuario()");
                    $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrResponse = $this->model->insertUsuario($arrData);

                if ($arrResponse['status'] === true) {
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('Usuarios', 'CREAR_USUARIO', $idusuario);

                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la creación del usuario ID: " .
                            ($arrResponse['usuario_id'] ?? 'desconocido'));
                    }
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en createUsuario: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getUsuariosData()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Usuarios', 'ver')) {
                    $response = array('status' => false, 'message' => 'No tienes permisos para ver usuarios', 'data' => []);
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Obtener ID del usuario actual para filtrar correctamente
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                
                // Obtener usuarios (activos para usuarios normales, todos para super usuarios)
                $arrResponse = $this->model->selectAllUsuariosActivos($idusuario);

                if ($arrResponse['status']) {
                    $this->bitacoraModel->registrarAccion('Usuarios', 'CONSULTA_LISTADO', $idusuario);
                }
                
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getUsuariosData: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getUsuarioById($idusuario)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Usuarios', 'ver')) {
                $arrResponse = array('status' => false, 'message' => 'No tienes permisos para ver usuarios');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            if (empty($idusuario) || !is_numeric($idusuario)) {
                $arrResponse = array('status' => false, 'message' => 'ID de usuario inválido');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                // Obtener ID del usuario actual para controlar acceso a super usuarios
                $idUsuarioSesion = $this->BitacoraHelper->obtenerUsuarioSesion();
                
                $arrData = $this->model->selectUsuarioById(intval($idusuario), $idUsuarioSesion);
                if ($arrData !== false) {
                    $this->bitacoraModel->registrarAccion('Usuarios', 'VER_USUARIO', $idUsuarioSesion);
                    
                    $arrResponse = array('status' => true, 'data' => $arrData);
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Usuario no encontrado o no tienes permisos para verlo');
                }
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getUsuarioById: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function updateUsuario()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Usuarios', 'editar')) {
                    $arrResponse = array('status' => false, 'message' => 'No tienes permisos para editar usuarios');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $postdata = file_get_contents('php://input');
                $request = json_decode($postdata, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $intIdUsuario = intval($request['idusuario'] ?? 0);
                if ($intIdUsuario <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'ID de usuario inválido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $datosLimpios = [
                    'usuario' => strClean($request['usuario'] ?? ''),
                    'correo' => filter_var($request['correo'] ?? '', FILTER_SANITIZE_EMAIL),
                    'clave' => $request['clave'] ?? '', 
                    'idrol' => intval($request['idrol'] ?? 0),
                    'personaId' => !empty($request['personaId']) ? intval($request['personaId']) : null
                ];

                $camposObligatorios = ['usuario', 'correo', 'idrol'];
                foreach ($camposObligatorios as $campo) {
                    if (empty($datosLimpios[$campo])) {
                        $arrResponse = array('status' => false, 'message' => 'Todos los campos obligatorios deben ser completados');
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }
                }

                if (strlen($datosLimpios['usuario']) < 3 || strlen($datosLimpios['usuario']) > 20) {
                    $arrResponse = array('status' => false, 'message' => 'El nombre de usuario debe tener entre 3 y 20 caracteres');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                if (!filter_var($datosLimpios['correo'], FILTER_VALIDATE_EMAIL)) {
                    $arrResponse = array('status' => false, 'message' => 'El correo electrónico no es válido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                if (!empty($datosLimpios['clave']) && strlen($datosLimpios['clave']) < 6) {
                    $arrResponse = array('status' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                if ($datosLimpios['idrol'] <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'Debe seleccionar un rol válido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrData = array(
                    'usuario' => $datosLimpios['usuario'],
                    'correo' => $datosLimpios['correo'],
                    'idrol' => $datosLimpios['idrol'],
                    'personaId' => $datosLimpios['personaId']
                );

                if (!empty($datosLimpios['clave'])) {
                    $arrData['clave'] = $datosLimpios['clave'];
                }

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

                if (!$idusuario) {
                    error_log("ERROR: No se encontró ID de usuario en la sesión durante updateUsuario()");
                    $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Pasar el ID del usuario actual al modelo para verificaciones de super usuario
                $arrResponse = $this->model->updateUsuario($intIdUsuario, $arrData, $idusuario);

                if ($arrResponse['status'] === true) {
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('Usuarios', 'ACTUALIZAR_USUARIO', $idusuario);

                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la actualización del usuario ID: " . $intIdUsuario);
                    }
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en updateUsuario: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function deleteUsuario()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Usuarios', 'eliminar')) {
                    $arrResponse = array('status' => false, 'message' => 'No tienes permisos para eliminar usuarios');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $postdata = file_get_contents('php://input');
                $request = json_decode($postdata, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $intIdUsuario = intval($request['idusuario'] ?? 0);
                if ($intIdUsuario <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'ID de usuario inválido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                
                // Verificar si puede eliminar el usuario usando el nuevo método del modelo
                $verificacion = $this->model->puedeEliminarUsuario($intIdUsuario, $idusuario);
                if (!$verificacion['puede_eliminar']) {
                    $arrResponse = array('status' => false, 'message' => $verificacion['razon']);
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Pasar el ID del usuario actual al modelo para verificaciones de super usuario
                $requestDelete = $this->model->deleteUsuarioById($intIdUsuario, $idusuario);
                if ($requestDelete) {
                    $arrResponse = array('status' => true, 'message' => 'Usuario desactivado correctamente');
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Error al desactivar el usuario o el usuario no puede ser eliminado');
                }

                if ($arrResponse['status'] === true) {
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('Usuarios', 'ELIMINAR_USUARIO', $idusuario);

                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la eliminación del usuario ID: " . $intIdUsuario);
                    }
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en deleteUsuario: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getRoles()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Usuarios', 'ver')) {
                    $response = array('status' => false, 'message' => 'No tienes permisos para acceder a esta información', 'data' => []);
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Obtener ID del usuario actual para filtrar roles según permisos
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                
                $arrResponse = $this->model->selectAllRoles($idusuario);
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getRoles: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getPersonas()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Usuarios', 'ver')) {
                    $response = array('status' => false, 'message' => 'No tienes permisos para acceder a esta información', 'data' => []);
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idPersonaActual = isset($_GET['idPersonaActual']) ? intval($_GET['idPersonaActual']) : 0;
                $arrResponse = $this->model->selectAllPersonasActivas($idPersonaActual);
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getPersonas: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function exportarUsuarios()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Usuarios', 'exportar')) {
                    $arrResponse = array('status' => false, 'message' => 'No tienes permisos para exportar usuarios');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Obtener ID del usuario actual para filtrar correctamente en la exportación
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                
                $arrData = $this->model->selectAllUsuariosActivos($idusuario);

                if ($arrData['status']) {
                    $data['usuarios'] = $arrData['data'];
                    $data['page_title'] = "Reporte de Usuarios";
                    $data['fecha_reporte'] = date('d/m/Y H:i:s');

                    $this->bitacoraModel->registrarAccion('Usuarios', 'EXPORTAR_USUARIOS', $idusuario);

                    $arrResponse = array('status' => true, 'message' => 'Datos preparados para exportación', 'data' => $data);
                } else {
                    $arrResponse = array('status' => false, 'message' => 'No se pudieron obtener los datos');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en exportarUsuarios: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function buscarUsuario()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Usuarios', 'ver')) {
                    $arrResponse = array('status' => false, 'message' => 'No tienes permisos para buscar usuarios');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $postdata = file_get_contents('php://input');
                $request = json_decode($postdata, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $strTermino = strClean($request['termino'] ?? '');
                if (empty($strTermino)) {
                    $arrResponse = array('status' => false, 'message' => 'Término de búsqueda requerido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrData = $this->model->buscarUsuarios($strTermino);
                if ($arrData['status']) {
                    $arrResponse = array('status' => true, 'data' => $arrData['data']);
                } else {
                    $arrResponse = array('status' => false, 'message' => 'No se encontraron resultados');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en buscarUsuario: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    /**
     * Nuevo método para verificar si un usuario es super usuario
     */
    public function verificarSuperUsuario()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $esSuperUsuario = $this->model->verificarEsSuperUsuario($idusuario);
                
                $arrResponse = array(
                    'status' => true, 
                    'es_super_usuario' => $esSuperUsuario,
                    'usuario_id' => $idusuario
                );
                
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en verificarSuperUsuario: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function debugPermisos()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $debug = [
            'session_completa' => $_SESSION,
            'session_login' => $_SESSION['login'] ?? 'no definido',
            'session_usuario_id' => $_SESSION['usuario_id'] ?? 'no definido', 
            'session_idrol' => $_SESSION['idrol'] ?? 'no definido',
            'session_usuario_nombre' => $_SESSION['usuario_nombre'] ?? 'no definido',
        ];

        $permisos = PermisosModuloVerificar::getPermisosUsuarioModulo('usuarios');
        
        $debug['permisos_obtenidos'] = $permisos;
        $debug['verificacion_ver'] = PermisosModuloVerificar::verificarPermisoModuloAccion('usuarios', 'ver');
        $debug['verificacion_crear'] = PermisosModuloVerificar::verificarPermisoModuloAccion('usuarios', 'crear');

        try {
            $conexion = new Conexion();
            $conexion->connect();
            $db = $conexion->get_conectSeguridad();

            $query = "
                SELECT 
                    u.idusuario,
                    u.usuario,
                    u.idrol,
                    r.nombre as rol_nombre,
                    m.titulo as modulo_nombre,
                    p.idpermiso,
                    p.nombre_permiso,
                    rmp.activo,
                    m.estatus as modulo_estatus
                FROM usuario u
                INNER JOIN roles r ON u.idrol = r.idrol
                INNER JOIN rol_modulo_permisos rmp ON r.idrol = rmp.idrol
                INNER JOIN modulos m ON rmp.idmodulo = m.idmodulo
                INNER JOIN permisos p ON rmp.idpermiso = p.idpermiso
                WHERE u.idusuario = ? 
                AND LOWER(m.titulo) = LOWER('usuarios')
                AND rmp.activo = 1
            ";

            $stmt = $db->prepare($query);
            $stmt->execute([$debug['session_usuario_id']]);
            $debug['consulta_directa'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $query2 = "
                SELECT 
                    m.titulo as modulo,
                    p.nombre_permiso as permiso,
                    rmp.activo
                FROM rol_modulo_permisos rmp
                INNER JOIN modulos m ON rmp.idmodulo = m.idmodulo
                INNER JOIN permisos p ON rmp.idpermiso = p.idpermiso
                WHERE rmp.idrol = ?
                AND rmp.activo = 1
                ORDER BY m.titulo, p.idpermiso
            ";

            $stmt2 = $db->prepare($query2);
            $stmt2->execute([$debug['session_idrol']]);
            $debug['todos_permisos_rol'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            $conexion->disconnect();

        } catch (Exception $e) {
            $debug['error_consulta'] = $e->getMessage();
        }

        header('Content-Type: application/json');
        echo json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Método para debug - verificar usuarios y roles en BD
     */
    public function debugUsuarios()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $conexion = new Conexion();
                $conexion->connect();
                $db = $conexion->get_conectSeguridad();

                // Obtener todos los usuarios sin filtros
                $query = "SELECT u.idusuario, u.usuario, u.idrol, r.nombre as rol_nombre 
                         FROM usuario u 
                         LEFT JOIN roles r ON u.idrol = r.idrol 
                         ORDER BY u.idusuario";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Obtener todos los roles
                $query2 = "SELECT idrol, nombre FROM roles ORDER BY idrol";
                $stmt2 = $db->prepare($query2);
                $stmt2->execute();
                $roles = $stmt2->fetchAll(PDO::FETCH_ASSOC);

                $conexion->disconnect();

                $debug = [
                    'super_usuario_rol_id_configurado' => 1, // Valor de la constante
                    'usuarios_en_bd' => $usuarios,
                    'roles_en_bd' => $roles,
                    'usuario_sesion' => $this->BitacoraHelper->obtenerUsuarioSesion()
                ];

                header('Content-Type: application/json');
                echo json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
            exit();
        }
    }

    /**
     * Reactivar un usuario (solo super usuarios)
     */
    public function reactivarUsuario()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $idusuarioSesion = $this->BitacoraHelper->obtenerUsuarioSesion();
                
                // Solo super usuarios pueden reactivar usuarios
                $esSuperUsuario = $this->model->verificarEsSuperUsuario($idusuarioSesion);
                if (!$esSuperUsuario) {
                    $arrResponse = array('status' => false, 'message' => 'Solo los super usuarios pueden reactivar usuarios');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }
                
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Usuarios', 'editar')) {
                    $arrResponse = array('status' => false, 'message' => 'No tienes permisos para reactivar usuarios');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $json = file_get_contents('php://input');
                $data = json_decode($json, true);

                if (empty($data['idusuario']) || !is_numeric($data['idusuario'])) {
                    $arrResponse = array('status' => false, 'message' => 'ID de usuario inválido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idusuario = intval($data['idusuario']);
                
                // Reactivar usuario directamente (el modelo se encarga de las validaciones)
                $arrResponse = $this->model->reactivarUsuario($idusuario);
                
                if ($arrResponse['status']) {
                    $this->bitacoraModel->registrarAccion('Usuarios', 'REACTIVAR', $idusuarioSesion, "Usuario ID: $idusuario reactivado");
                }
                
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en reactivarUsuario: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }
}
?>