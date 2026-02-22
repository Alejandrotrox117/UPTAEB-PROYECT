<?php
namespace App\Controllers;

use App\Core\Controllers;
use App\Models\SueldosModel;
use App\Models\BitacoraModel;
use App\Models\TiposPagosModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\PermisosModuloVerificar;
use App\Helpers\Validation\ExpresionesRegulares;
use Exception;


class Sueldos extends Controllers
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
            die();
        }

        if (!PermisosModuloVerificar::verificarAccesoModulo('sueldos')) {
            $this->views->getView($this, "permisos");
            exit();
        }
    }

    public function createSueldo()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Sueldos', 'crear')) {
                    $arrResponse = array('status' => false, 'message' => 'No tienes permisos para crear sueldos');
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

                // Validar que al menos se especifique una persona o empleado
                if (empty($request['idpersona']) && empty($request['idempleado'])) {
                    $arrResponse = array('status' => false, 'message' => 'Debe especificar una persona o empleado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Validar que no se envíen ambos campos al mismo tiempo
                if (!empty($request['idpersona']) && !empty($request['idempleado'])) {
                    $arrResponse = array('status' => false, 'message' => 'No se puede especificar persona y empleado al mismo tiempo');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Validar campos obligatorios
                if (empty($request['monto'])) {
                    $arrResponse = array('status' => false, 'message' => 'El monto es obligatorio');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Validar que se especifique una moneda
                if (empty($request['idmoneda'])) {
                    $arrResponse = array('status' => false, 'message' => 'Debe seleccionar una moneda');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Validar que monto sea numérico
                if (!is_numeric($request['monto'])) {
                    $arrResponse = array('status' => false, 'message' => 'El monto debe ser un valor numérico');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Validar que el monto sea positivo
                if (floatval($request['monto']) <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'El monto debe ser un valor positivo');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Crear array de datos - asegurar que solo uno de los dos campos esté presente
                $arrData = array(
                    'idpersona' => !empty($request['idpersona']) ? intval($request['idpersona']) : null,
                    'idempleado' => !empty($request['idempleado']) ? intval($request['idempleado']) : null,
                    'monto' => floatval($request['monto']),
                    'idmoneda' => intval($request['idmoneda']),
                    'observacion' => trim($request['observacion'] ?? '')
                );

                // Log para debugging
                error_log("Datos recibidos para sueldo: " . json_encode($request));
                error_log("Datos procesados para sueldo: " . json_encode($arrData));

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

                if (!$idusuario) {
                    error_log("ERROR: No se encontró ID de usuario en la sesión durante createSueldo()");
                    $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrResponse = $this->model->insertSueldo($arrData);

                if ($arrResponse['status'] === true) {
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('Sueldos', 'INSERTAR', $idusuario);

                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la inserción del sueldo");
                    }
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en createSueldo: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function index()
    {
        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        BitacoraHelper::registrarAccesoModulo('Sueldos', $idusuario, $this->bitacoraModel);

        $data['page_tag'] = "Sueldos";
        $data['page_title'] = "Administración de Sueldos";
        $data['page_name'] = "sueldos";
        $data['page_content'] = "Gestión integral de sueldos del sistema";
        $data['page_functions_js'] = "functions_sueldos.js";
        $this->views->getView($this, "sueldos", $data);
    }

    public function getMonedas()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                error_log("Sueldos::getMonedas - Iniciando...");
                
                if (!$this->model) {
                    error_log("Sueldos::getMonedas - ERROR: Modelo no está disponible");
                    $arrResponse = array('status' => false, 'message' => 'Error interno: modelo no disponible', 'data' => []);
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }
                
                $arrResponse = $this->model->getMonedas();
                error_log("Sueldos::getMonedas - Respuesta del modelo: " . json_encode($arrResponse));
                
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getMonedas: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage(), 'data' => []);
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    // Función de debug temporal - eliminar en producción
    public function debugMonedas()
    {
        try {
            error_log("DEBUG: Intentando obtener monedas...");
            $result = $this->model->getMonedas();
            error_log("DEBUG: Resultado: " . json_encode($result));
            
            header('Content-Type: application/json');
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("DEBUG ERROR: " . $e->getMessage());
            $response = array('status' => false, 'message' => 'Error: ' . $e->getMessage(), 'data' => []);
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getSueldosData()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Sueldos', 'ver')) {
                    $response = array('status' => false, 'message' => 'No tienes permisos para ver sueldos', 'data' => []);
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Obtener ID del usuario actual
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                
                // Obtener sueldos
                $arrResponse = $this->model->selectAllSueldos($idusuario);
                
                if ($arrResponse['status']) {
                    $this->bitacoraModel->registrarAccion('Sueldos', 'CONSULTA_LISTADO', $idusuario);
                }
                
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getSueldosData: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getSueldoById($idsueldo)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (empty($idsueldo) || !is_numeric($idsueldo)) {
                $arrResponse = array('status' => false, 'message' => 'ID de sueldo inválido');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $arrData = $this->model->selectSueldoById(intval($idsueldo));
                if (!empty($arrData)) {
                    $arrResponse = array('status' => true, 'data' => $arrData);
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Sueldo no encontrado');
                }
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getSueldoById: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getMontoBolivares($idsueldo)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (empty($idsueldo) || !is_numeric($idsueldo)) {
                $arrResponse = array('status' => false, 'message' => 'ID de sueldo inválido');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $arrResponse = $this->model->convertirMontoABolivares(intval($idsueldo));
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getMontoBolivares: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function updateSueldo()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Sueldos', 'editar')) {
                    $arrResponse = array('status' => false, 'message' => 'No tienes permisos para editar sueldos');
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

                $intIdSueldo = intval($request['idsueldo'] ?? 0);
                if ($intIdSueldo <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'ID de sueldo inválido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Validar que al menos se especifique una persona o empleado
                if (empty($request['idpersona']) && empty($request['idempleado'])) {
                    $arrResponse = array('status' => false, 'message' => 'Debe especificar una persona o empleado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Validar que no se envíen ambos campos al mismo tiempo
                if (!empty($request['idpersona']) && !empty($request['idempleado'])) {
                    $arrResponse = array('status' => false, 'message' => 'No se puede especificar persona y empleado al mismo tiempo');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Validar campos obligatorios
                if (empty($request['monto'])) {
                    $arrResponse = array('status' => false, 'message' => 'El monto es obligatorio');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Validar que se especifique una moneda
                if (empty($request['idmoneda'])) {
                    $arrResponse = array('status' => false, 'message' => 'Debe seleccionar una moneda');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Validar que monto sea numérico
                if (!is_numeric($request['monto'])) {
                    $arrResponse = array('status' => false, 'message' => 'El monto debe ser un valor numérico');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Validar que el monto sea positivo
                if (floatval($request['monto']) <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'El monto debe ser un valor positivo');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Crear array de datos - asegurar que solo uno de los dos campos esté presente
                $arrData = array(
                    'idpersona' => !empty($request['idpersona']) ? intval($request['idpersona']) : null,
                    'idempleado' => !empty($request['idempleado']) ? intval($request['idempleado']) : null,
                    'monto' => floatval($request['monto']),
                    'idmoneda' => intval($request['idmoneda']),
                    'observacion' => trim($request['observacion'] ?? '')
                );

                // Log para debugging
                error_log("Actualización - Datos recibidos para sueldo: " . json_encode($request));
                error_log("Actualización - Datos procesados para sueldo: " . json_encode($arrData));

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

                if (!$idusuario) {
                    error_log("ERROR: No se encontró ID de usuario en la sesión durante updateSueldo()");
                    $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrResponse = $this->model->updateSueldo($intIdSueldo, $arrData);

                if ($arrResponse['status'] === true) {
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('Sueldos', 'ACTUALIZAR', $idusuario);

                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la actualización del sueldo ID: " . $intIdSueldo);
                    }
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en updateSueldo: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function deleteSueldo()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Sueldos', 'eliminar')) {
                    $arrResponse = array('status' => false, 'message' => 'No tienes permisos para eliminar sueldos');
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

                $intIdSueldo = intval($request['idsueldo'] ?? 0);
                if ($intIdSueldo <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'ID de sueldo inválido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $requestDelete = $this->model->deleteSueldoById($intIdSueldo);
                
                if ($requestDelete) {
                    $arrResponse = array('status' => true, 'message' => 'Sueldo eliminado correctamente');
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Error al eliminar el sueldo');
                }
                
                if ($arrResponse['status'] === true) {
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('Sueldos', 'ELIMINAR', $idusuario);

                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la eliminación del sueldo ID: " . $intIdSueldo);
                    }
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en deleteSueldo: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getPersonasActivas()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrResponse = $this->model->selectPersonasActivas();
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getPersonasActivas: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getEmpleadosActivos()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrResponse = $this->model->selectEmpleadosActivos();
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getEmpleadosActivos: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function buscarSueldo()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents('php://input');
                $request = json_decode($postdata, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $strTermino = ExpresionesRegulares::limpiar($request['termino'] ?? '', 'textoGeneral');
                if (empty($strTermino)) {
                    $arrResponse = array('status' => false, 'message' => 'Término de búsqueda requerido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrData = $this->model->buscarSueldos($strTermino);
                if ($arrData['status']) {
                    $arrResponse = array('status' => true, 'data' => $arrData['data']);
                } else {
                    $arrResponse = array('status' => false, 'message' => 'No se encontraron resultados');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en buscarSueldo: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function exportarSueldos()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $arrData = $this->model->selectAllSueldos($idusuario);

                if ($arrData['status']) {
                    $data['sueldos'] = $arrData['data'];
                    $data['page_title'] = "Reporte de Sueldos";
                    $data['fecha_reporte'] = date('d/m/Y H:i:s');

                    $arrResponse = array('status' => true, 'message' => 'Datos preparados para exportación', 'data' => $data);
                } else {
                    $arrResponse = array('status' => false, 'message' => 'No se pudieron obtener los datos');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en exportarSueldos: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function verificarSuperUsuario()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                error_log("=== Iniciando verificarSuperUsuario en Sueldos controller ===");
                
                // Debug: verificar si la sesión está iniciada
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                    error_log("Sesión iniciada en verificarSuperUsuario");
                } else {
                    error_log("Sesión ya estaba iniciada");
                }
                
                // Debug: mostrar contenido de $_SESSION
                error_log("Contenido de _SESSION: " . print_r($_SESSION, true));
                
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                error_log("BitacoraHelper devolvió usuario ID: " . ($idusuario ?: 'NULL'));
                
                if (!$idusuario) {
                    error_log("Usuario no autenticado - BitacoraHelper no devolvió usuario");
                    echo json_encode([
                        'status' => false,
                        'message' => 'Usuario no autenticado',
                        'es_super_usuario' => false,
                        'usuario_id' => 0,
                        'debug_session' => $_SESSION
                    ]);
                    die();
                }
                
                error_log("Verificando usuario ID: $idusuario con esSuperAdmin");
                
                $esSuperAdmin = $this->model->verificarEsSuperUsuario($idusuario);
                
                error_log("Resultado esSuperAdmin: " . ($esSuperAdmin ? 'SÍ' : 'NO'));
                
                echo json_encode([
                    'status' => true,
                    'es_super_usuario' => $esSuperAdmin,
                    'usuario_id' => $idusuario,
                    'message' => 'Verificación completada'
                ]);
            } catch (Exception $e) {
                error_log("Error en verificarSuperUsuario: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                echo json_encode([
                    'status' => false,
                    'message' => 'Error interno del servidor: ' . $e->getMessage(),
                    'es_super_usuario' => false,
                    'usuario_id' => 0
                ]);
            }
            die();
        }
    }

    public function reactivarSueldo()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // Verificar si es super usuario
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $esSuperUsuario = $this->model->verificarEsSuperUsuario($idusuario);
                
                if (!$esSuperUsuario) {
                    $arrResponse = array('status' => false, 'message' => 'Solo los super usuarios pueden reactivar sueldos');
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

                $intIdSueldo = intval($request['idsueldo'] ?? 0);
                if ($intIdSueldo <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'ID de sueldo inválido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $requestReactivar = $this->model->reactivarSueldoById($intIdSueldo);
                
                if ($requestReactivar) {
                    $arrResponse = array('status' => true, 'message' => 'Sueldo reactivado correctamente');
                    
                    // Registrar en bitácora
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('Sueldos', 'REACTIVAR', $idusuario);
                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la reactivación del sueldo ID: " . $intIdSueldo);
                    }
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Error al reactivar el sueldo');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en reactivarSueldo: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function procesarPagoSueldo()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Validar datos recibidos
            $requiredFields = ['idsueldo', 'monto', 'idtipo_pago', 'fecha_pago'];
            $data = [];
            
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    echo json_encode([
                        'status' => false,
                        'message' => "El campo {$field} es requerido"
                    ]);
                    return;
                }
                $data[$field] = $_POST[$field];
            }
            
            // Campos opcionales
            $optionalFields = ['referencia', 'observaciones'];
            foreach ($optionalFields as $field) {
                if (isset($_POST[$field])) {
                    $data[$field] = $_POST[$field];
                }
            }
            
            // Validar que el monto sea válido
            if (!is_numeric($data['monto']) || floatval($data['monto']) <= 0) {
                echo json_encode([
                    'status' => false,
                    'message' => 'El monto debe ser un número mayor a 0'
                ]);
                return;
            }
            
            // Procesar el pago
            $result = $this->model->procesarPagoSueldo($data);
            
            // Si el pago fue exitoso, registrar en bitácora
            if ($result['status']) {
                $this->BitacoraHelper->registrarAccion(
                    'Sueldos',
                    'Procesar pago',
                    "Pago procesado para sueldo ID: {$data['idsueldo']}, Monto: {$data['monto']} Bs., Pago ID: {$result['data']['pago_id']}",
                    $data['idsueldo']
                );
            }
            
            echo json_encode($result);
        } else {
            echo json_encode([
                'status' => false,
                'message' => 'Método no permitido'
            ]);
        }
    }

    public function getTiposPagos()
    {
        $tiposPagosModel = new TiposPagosModel();
        
        try {
            $result = $tiposPagosModel->selectTiposPagosActivos();
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'message' => 'Error al obtener tipos de pago: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }

    public function getPagosSueldo($idsueldo = null)
    {
        if (!$idsueldo && isset($_GET['idsueldo'])) {
            $idsueldo = intval($_GET['idsueldo']);
        }
        
        if (!$idsueldo) {
            echo json_encode([
                'status' => false,
                'message' => 'ID de sueldo requerido',
                'data' => []
            ]);
            return;
        }

        try {
            // Obtener pagos asociados al sueldo desde la tabla pagos
            $result = $this->model->getPagosSueldo($idsueldo);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'message' => 'Error al obtener pagos: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }
}
