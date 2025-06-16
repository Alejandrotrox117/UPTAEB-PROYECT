<?php

require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
require_once "helpers/permisosVerificar.php";
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

        // Verificar si el usuario está logueado antes de verificar permisos
        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        // ✅ CAMBIAR A SISTEMA NUEVO (PermisosModuloVerificar)
        if (!PermisosModuloVerificar::verificarAccesoModulo('usuarios')) {
            // Mostrar página de error de permisos
            $this->views->getView($this, "permisos");
            exit();
        }
    }

    /**
     * Vista principal del módulo usuarios
     */
    public function index()
    {
        // Ya no es necesario verificar permisos aquí porque se hace en el constructor
        // Pero si quieres verificación adicional:
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('usuarios', 'ver')) {
            $this->views->getView($this, "permisos");
            exit();
        }

        // Obtener ID del usuario y registrar acceso
        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        BitacoraHelper::registrarAccesoModulo('Usuarios', $idusuario, $this->bitacoraModel);

        $data['page_tag'] = "Usuarios";
        $data['page_title'] = "Administración de Usuarios";
        $data['page_name'] = "usuarios";
        $data['page_content'] = "Gestión integral de usuarios del sistema";
        $data['page_functions_js'] = "functions_usuarios.js";
        
        $this->views->getView($this, "usuarios", $data);
    }

    /**
     * Crear nuevo usuario
     */
    public function createUsuario()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // ✅ VERIFICAR PERMISOS ANTES DE PROCESAR
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Usuarios', 'crear')) {
                    $arrResponse = array('status' => false, 'message' => 'No tienes permisos para crear usuarios');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $postdata = file_get_contents("php://input");
                $request = json_decode($postdata, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Limpiar y validar datos
                $datosLimpios = [
                    'usuario' => strClean($request['usuario'] ?? ''),
                    'correo' => filter_var($request['correo'] ?? '', FILTER_SANITIZE_EMAIL),
                    'clave' => $request['clave'] ?? '',
                    'idrol' => intval($request['idrol'] ?? 0),
                    'personaId' => !empty($request['personaId']) ? intval($request['personaId']) : null];

                // Validar campos obligatorios
                $camposObligatorios = ['usuario', 'correo', 'clave', 'idrol'];
                foreach ($camposObligatorios as $campo) {
                    if (empty($datosLimpios[$campo])) {
                        $arrResponse = array('status' => false, 'message' => 'Todos los campos obligatorios deben ser completados');
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }
                }

                // Validaciones de negocio
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

                // Obtener ID de usuario
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

                if (!$idusuario) {
                    error_log("ERROR: No se encontró ID de usuario en la sesión durante createUsuario()");
                    $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrResponse = $this->model->insertUsuario($arrData);

                // Registrar en bitácora si la inserción fue exitosa
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

    /**
     * Obtener datos de usuarios para DataTable
     */
    public function getUsuariosData()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                // ✅ VERIFICAR PERMISOS ANTES DE PROCESAR
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Usuarios', 'ver')) {
                    $response = array('status' => false, 'message' => 'No tienes permisos para ver usuarios', 'data' => []);
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrResponse = $this->model->selectAllUsuariosActivos();
                
                // Registrar consulta en bitácora si fue exitosa
                if ($arrResponse['status']) {
                    $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
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

    /**
     * Obtener usuario por ID
     */
    public function getUsuarioById($idusuario)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            // ✅ VERIFICAR PERMISOS ANTES DE PROCESAR
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
                $arrData = $this->model->selectUsuarioById(intval($idusuario));
                if (!empty($arrData)) {
                    // Registrar consulta individual en bitácora
                    $idUsuarioSesion = $this->BitacoraHelper->obtenerUsuarioSesion();
                    $this->bitacoraModel->registrarAccion('Usuarios', 'VER_USUARIO', $idUsuarioSesion);
                    
                    $arrResponse = array('status' => true, 'data' => $arrData);
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Usuario no encontrado');
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

    /**
     * Actualizar usuario existente
     */
    public function updateUsuario()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // ✅ VERIFICAR PERMISOS ANTES DE PROCESAR
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Usuarios', 'editar')) {
                    $arrResponse = array('status' => false, 'message' => 'No tienes permisos para editar usuarios');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $postdata = file_get_contents("php://input");
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

                // Limpiar datos
                $datosLimpios = [
                    'usuario' => strClean($request['usuario'] ?? ''),
                    'correo' => filter_var($request['correo'] ?? '', FILTER_SANITIZE_EMAIL),
                    'clave' => $request['clave'] ?? '', // Puede estar vacío para no cambiar
                    'idrol' => intval($request['idrol'] ?? 0),
                    'personaId' => !empty($request['personaId']) ? intval($request['personaId']) : null
                ];

                // Validar campos obligatorios
                $camposObligatorios = ['usuario', 'correo', 'idrol'];
                foreach ($camposObligatorios as $campo) {
                    if (empty($datosLimpios[$campo])) {
                        $arrResponse = array('status' => false, 'message' => 'Todos los campos obligatorios deben ser completados');
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }
                }

                // Validaciones de negocio
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

                // Validar contraseña solo si se proporciona
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

                // Solo incluir contraseña si se proporciona
                if (!empty($datosLimpios['clave'])) {
                    $arrData['clave'] = $datosLimpios['clave'];
                }

                // Obtener ID de usuario de sesión
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

                if (!$idusuario) {
                    error_log("ERROR: No se encontró ID de usuario en la sesión durante updateUsuario()");
                    $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrResponse = $this->model->updateUsuario($intIdUsuario, $arrData);

                // Registrar en bitácora si la actualización fue exitosa
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

    /**
     * Desactivar usuario (eliminar lógico)
     */
    public function deleteUsuario()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // ✅ VERIFICAR PERMISOS ANTES DE PROCESAR
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Usuarios', 'eliminar')) {
                    $arrResponse = array('status' => false, 'message' => 'No tienes permisos para eliminar usuarios');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $postdata = file_get_contents("php://input");
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

                // Validar que no se elimine a sí mismo
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                if ($intIdUsuario === $idusuario) {
                    $arrResponse = array('status' => false, 'message' => 'No puedes desactivar tu propia cuenta');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $requestDelete = $this->model->deleteUsuarioById($intIdUsuario);
                if ($requestDelete) {
                    $arrResponse = array('status' => true, 'message' => 'Usuario desactivado correctamente');
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Error al desactivar el usuario');
                }

                // Registrar en bitácora si la eliminación fue exitosa
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

    /**
     * Obtener roles para selects
     */
    public function getRoles()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                // ✅ VERIFICAR PERMISOS BÁSICOS
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Usuarios', 'ver')) {
                    $response = array('status' => false, 'message' => 'No tienes permisos para acceder a esta información', 'data' => []);
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrResponse = $this->model->selectAllRoles();
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getRoles: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    /**
     * Obtener personas disponibles para selects
     */
    public function getPersonas()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                // ✅ VERIFICAR PERMISOS BÁSICOS
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

    /**
     * Exportar usuarios (opcional)
     */
    public function exportarUsuarios()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                // ✅ VERIFICAR PERMISOS DE EXPORTACIÓN
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Usuarios', 'exportar')) {
                    $arrResponse = array('status' => false, 'message' => 'No tienes permisos para exportar usuarios');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrData = $this->model->selectAllUsuariosActivos();

                if ($arrData['status']) {
                    $data['usuarios'] = $arrData['data'];
                    $data['page_title'] = "Reporte de Usuarios";
                    $data['fecha_reporte'] = date('d/m/Y H:i:s');

                    // Registrar exportación en bitácora
                    $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
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

    /**
     * Buscar usuario por término
     */
    public function buscarUsuario()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // ✅ VERIFICAR PERMISOS DE BÚSQUEDA
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Usuarios', 'ver')) {
                    $arrResponse = array('status' => false, 'message' => 'No tienes permisos para buscar usuarios');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $postdata = file_get_contents("php://input");
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

                // Nota: Necesitarías implementar buscarUsuarios en el modelo
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
     * Método de debug temporal para permisos
     */
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

        // Obtener permisos directamente
        $permisos = PermisosModuloVerificar::getPermisosUsuarioModulo('usuarios');
        
        $debug['permisos_obtenidos'] = $permisos;
        $debug['verificacion_ver'] = PermisosModuloVerificar::verificarPermisoModuloAccion('usuarios', 'ver');
        $debug['verificacion_crear'] = PermisosModuloVerificar::verificarPermisoModuloAccion('usuarios', 'crear');

        // Verificar en base de datos directamente con tu estructura
        try {
            $conexion = new Conexion();
            $conexion->connect();
            $db = $conexion->get_conectSeguridad();

            // ✅ CONSULTA CORREGIDA CON TU ESTRUCTURA REAL
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

            // ✅ CONSULTA ADICIONAL PARA VER TODOS LOS MÓDULOS DEL USUARIO
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
     * Método de debug detallado para permisos
     */
    public function debugPermisosDetallado()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $debug = [
            'rol_id' => $_SESSION['user']['idrol'] ?? 0,
            'usuario_id' => $_SESSION['usuario_id'] ?? 0,
        ];

        try {
            $conexion = new Conexion();
            $conexion->connect();
            $db = $conexion->get_conectSeguridad();

            // 1. Verificar que el módulo existe
            $queryModulo = "SELECT * FROM modulos WHERE LOWER(titulo) = LOWER('usuarios')";
            $stmtModulo = $db->prepare($queryModulo);
            $stmtModulo->execute();
            $debug['modulo_existe'] = $stmtModulo->fetch(PDO::FETCH_ASSOC);

            // 2. Verificar todos los permisos del rol
            $queryPermisosRol = "
                SELECT 
                    rmp.*,
                    m.titulo as modulo_titulo,
                    p.nombre_permiso
                FROM rol_modulo_permisos rmp
                LEFT JOIN modulos m ON rmp.idmodulo = m.idmodulo
                LEFT JOIN permisos p ON rmp.idpermiso = p.idpermiso
                WHERE rmp.idrol = ?
            ";
            $stmtPermisosRol = $db->prepare($queryPermisosRol);
            $stmtPermisosRol->execute([$debug['rol_id']]);
            $debug['todos_permisos_rol'] = $stmtPermisosRol->fetchAll(PDO::FETCH_ASSOC);

            // 3. Buscar específicamente usuarios
            $queryUsuarios = "
                SELECT 
                    rmp.*,
                    m.titulo as modulo_titulo,
                    p.nombre_permiso
                FROM rol_modulo_permisos rmp
                INNER JOIN modulos m ON rmp.idmodulo = m.idmodulo
                INNER JOIN permisos p ON rmp.idpermiso = p.idpermiso
                WHERE rmp.idrol = ? 
                AND LOWER(m.titulo) = LOWER('usuarios')
            ";
            $stmtUsuarios = $db->prepare($queryUsuarios);
            $stmtUsuarios->execute([$debug['rol_id']]);
            $debug['permisos_usuarios_encontrados'] = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

            // 4. Obtener permisos usando el helper
            $debug['permisos_helper'] = PermisosModuloVerificar::getPermisosUsuarioModulo('usuarios');

            $conexion->disconnect();

        } catch (Exception $e) {
            $debug['error'] = $e->getMessage();
        }

        header('Content-Type: application/json');
        echo json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }
}
?>