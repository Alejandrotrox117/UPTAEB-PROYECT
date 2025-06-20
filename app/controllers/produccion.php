<?php
require_once "app/core/Controllers.php";
require_once "app/models/produccionModel.php";
require_once "app/models/tareaProduccionModel.php";
require_once "helpers/helpers.php";
require_once "helpers/PermisosModuloVerificar.php";
require_once "helpers/PermisosHelper.php";
require_once "app/models/bitacoraModel.php";
require_once "helpers/expresiones_regulares.php";
require_once "helpers/bitacora_helper.php";

class Produccion extends Controllers
{
    private $bitacoraModel;
    private $BitacoraHelper;
    private $tarea_model;

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

       

        $this->model = new ProduccionModel();
        $this->tarea_model = new TareaProduccionModel();
    }

    public function index()
    {
        // Obtener ID de usuario para bitácora
        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        BitacoraHelper::registrarAccesoModulo('produccion', $idusuario, $this->bitacoraModel);

        $data['page_tag'] = "Producción";
        $data['page_title'] = "Gestión de Producción";
        $data['page_name'] = "produccion";
        $data['page_content'] = "Gestión integral de procesos de producción";
        $data['page_functions_js'] = "functions_produccion.js";
        $this->views->getView($this, "produccion", $data);
    }

    public function createProduccion()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents("php://input");
                $request = json_decode($postdata, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Limpiar y validar datos de entrada
                $datosLimpios = [
                    'idempleado' => intval($request['idempleado'] ?? 0),
                    'idproducto' => intval($request['idproducto'] ?? 0),
                    'cantidad_a_realizar' => floatval($request['cantidad_a_realizar'] ?? 0),
                    'fecha_inicio' => trim($request['fecha_inicio'] ?? ''),
                    'fecha_fin' => trim($request['fecha_fin'] ?? ''),
                    'estado' => ExpresionesRegulares::limpiar($request['estado'] ?? '', 'texto_simple'),
                    'insumos' => $request['insumos'] ?? []
                ];

                // Validar campos obligatorios
                $camposObligatorios = ['idempleado', 'idproducto', 'cantidad_a_realizar', 'fecha_inicio', 'estado'];
                foreach ($camposObligatorios as $campo) {
                    if (empty($datosLimpios[$campo]) || ($campo === 'cantidad_a_realizar' && $datosLimpios[$campo] <= 0)) {
                        $arrResponse = array('status' => false, 'message' => 'Todos los campos obligatorios deben ser completados correctamente');
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }
                }

                // Validar que los IDs sean números válidos
                if ($datosLimpios['idempleado'] <= 0 || $datosLimpios['idproducto'] <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'Los IDs de empleado y producto deben ser válidos');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Validar cantidad
                if ($datosLimpios['cantidad_a_realizar'] <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'La cantidad a realizar debe ser mayor a cero');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Validar fecha de inicio
                if (!empty($datosLimpios['fecha_inicio'])) {
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datosLimpios['fecha_inicio'])) {
                        $arrResponse = array('status' => false, 'message' => 'El formato de fecha de inicio debe ser YYYY-MM-DD');
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }

                    $fechaInicio = DateTime::createFromFormat('Y-m-d', $datosLimpios['fecha_inicio']);
                    if (!$fechaInicio) {
                        $arrResponse = array('status' => false, 'message' => 'Formato de fecha de inicio inválido');
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }
                }

                // Validar fecha fin si se proporciona
                if (!empty($datosLimpios['fecha_fin'])) {
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datosLimpios['fecha_fin'])) {
                        $arrResponse = array('status' => false, 'message' => 'El formato de fecha fin debe ser YYYY-MM-DD');
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }

                    $fechaFin = DateTime::createFromFormat('Y-m-d', $datosLimpios['fecha_fin']);
                    if (!$fechaFin) {
                        $arrResponse = array('status' => false, 'message' => 'Formato de fecha fin inválido');
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }

                    // Validar que fecha fin no sea anterior a fecha inicio
                    if ($fechaFin < $fechaInicio) {
                        $arrResponse = array('status' => false, 'message' => 'La fecha fin no puede ser anterior a la fecha de inicio');
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }
                } else {
                    $datosLimpios['fecha_fin'] = null;
                }

                // Validar estados permitidos
                $estadosPermitidos = ['borrador', 'planificado', 'en_proceso', 'en_clasificacion', 'empacando', 'realizado'];
                if (!in_array($datosLimpios['estado'], $estadosPermitidos)) {
                    $arrResponse = array('status' => false, 'message' => 'Estado de producción no válido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrData = array(
                    'idempleado' => $datosLimpios['idempleado'],
                    'idproducto' => $datosLimpios['idproducto'],
                    'cantidad_a_realizar' => $datosLimpios['cantidad_a_realizar'],
                    'fecha_inicio' => $datosLimpios['fecha_inicio'],
                    'fecha_fin' => $datosLimpios['fecha_fin'],
                    'estado' => $datosLimpios['estado'],
                    'insumos' => $datosLimpios['insumos']
                );

                // Obtener ID de usuario
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

                if (!$idusuario) {
                    error_log("ERROR: No se encontró ID de usuario en la sesión durante createProduccion()");
                    $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrResponse = $this->model->insertProduccion($arrData);

                // Registrar en bitácora si la inserción fue exitosa
                if ($arrResponse['status'] === true) {
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('produccion', 'INSERTAR', $idusuario);

                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la creación de la producción ID: " .
                            ($arrResponse['produccion_id'] ?? 'desconocido'));
                    }
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en createProduccion: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function updateProduccion()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents("php://input");
                $request = json_decode($postdata, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $intIdProduccion = intval($request['idproduccion'] ?? 0);
                if ($intIdProduccion <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'ID de producción inválido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Limpiar y validar datos de entrada
                $datosLimpios = [
                    'idempleado' => intval($request['idempleado'] ?? 0),
                    'idproducto' => intval($request['idproducto'] ?? 0),
                    'cantidad_a_realizar' => floatval($request['cantidad_a_realizar'] ?? 0),
                    'fecha_inicio' => trim($request['fecha_inicio'] ?? ''),
                    'fecha_fin' => trim($request['fecha_fin'] ?? ''),
                    'estado' => ExpresionesRegulares::limpiar($request['estado'] ?? '', 'texto_simple'),
                    'insumos' => $request['insumos'] ?? []
                ];

                // Validar campos obligatorios
                $camposObligatorios = ['idempleado', 'idproducto', 'cantidad_a_realizar', 'fecha_inicio', 'estado'];
                foreach ($camposObligatorios as $campo) {
                    if (empty($datosLimpios[$campo]) || ($campo === 'cantidad_a_realizar' && $datosLimpios[$campo] <= 0)) {
                        $arrResponse = array('status' => false, 'message' => 'Todos los campos obligatorios deben ser completados correctamente');
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }
                }

                // Validaciones similares a createProduccion
                if ($datosLimpios['idempleado'] <= 0 || $datosLimpios['idproducto'] <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'Los IDs de empleado y producto deben ser válidos');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                if ($datosLimpios['cantidad_a_realizar'] <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'La cantidad a realizar debe ser mayor a cero');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Validar fechas
                if (!empty($datosLimpios['fecha_inicio'])) {
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datosLimpios['fecha_inicio'])) {
                        $arrResponse = array('status' => false, 'message' => 'El formato de fecha de inicio debe ser YYYY-MM-DD');
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }
                }

                if (!empty($datosLimpios['fecha_fin'])) {
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datosLimpios['fecha_fin'])) {
                        $arrResponse = array('status' => false, 'message' => 'El formato de fecha fin debe ser YYYY-MM-DD');
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }
                } else {
                    $datosLimpios['fecha_fin'] = null;
                }

                // Validar estados permitidos
                $estadosPermitidos = ['borrador', 'planificado', 'en_proceso', 'en_clasificacion', 'empacando', 'realizado'];
                if (!in_array($datosLimpios['estado'], $estadosPermitidos)) {
                    $arrResponse = array('status' => false, 'message' => 'Estado de producción no válido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrData = array(
                    'idempleado' => $datosLimpios['idempleado'],
                    'idproducto' => $datosLimpios['idproducto'],
                    'cantidad_a_realizar' => $datosLimpios['cantidad_a_realizar'],
                    'fecha_inicio' => $datosLimpios['fecha_inicio'],
                    'fecha_fin' => $datosLimpios['fecha_fin'],
                    'estado' => $datosLimpios['estado'],
                    'insumos' => $datosLimpios['insumos']
                );

                // Obtener ID de usuario
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

                if (!$idusuario) {
                    error_log("ERROR: No se encontró ID de usuario en la sesión durante updateProduccion()");
                    $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrResponse = $this->model->updateProduccion($intIdProduccion, $arrData);

                // Registrar en bitácora si la actualización fue exitosa
                if ($arrResponse['status'] === true) {
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('produccion', 'ACTUALIZAR', $idusuario);

                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la actualización de la producción ID: " . $intIdProduccion);
                    }
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en updateProduccion: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getProduccionData()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrResponse = $this->model->selectAllProducciones();
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getProduccionData: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getProduccionById($idproduccion)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (empty($idproduccion) || !is_numeric($idproduccion)) {
                $arrResponse = array('status' => false, 'message' => 'ID de producción inválido');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $arrData = $this->model->selectProduccionById(intval($idproduccion));
                if (!empty($arrData)) {
                    $arrResponse = array('status' => true, 'data' => $arrData);
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Producción no encontrada');
                }
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getProduccionById: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getDetalleProduccionData($idproduccion)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (empty($idproduccion) || !is_numeric($idproduccion)) {
                $arrResponse = array('status' => false, 'message' => 'ID de producción inválido');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $arrResponse = $this->model->selectDetalleProduccion(intval($idproduccion));
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getDetalleProduccionData: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function deleteProduccion()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents("php://input");
                $request = json_decode($postdata, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $intIdProduccion = intval($request['idproduccion'] ?? 0);
                if ($intIdProduccion <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'ID de producción inválido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $requestDelete = $this->model->deleteProduccionById($intIdProduccion);
                
                if ($requestDelete) {
                    $arrResponse = array('status' => true, 'message' => 'Producción desactivada correctamente');
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Error al desactivar la producción');
                }

                if ($arrResponse['status'] === true) {
                    // Registrar acción en bitácora
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('produccion', 'ELIMINAR', $idusuario);

                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la eliminación de la producción ID: " . $intIdProduccion);
                    }
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en deleteProduccion: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getEmpleado()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrResponse = $this->model->selectEmpleadosActivos();
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getEmpleado: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getProductos()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrResponse = $this->model->selectProductosActivos();
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getProductos: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getEstadisticas()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $total = $this->model->getTotalProducciones();
                $clasificacion = $this->model->getProduccionesEnClasificacion();
                $finalizadas = $this->model->getProduccionesFinalizadas();

                $arrResponse = array(
                    'status' => true,
                    'message' => 'Estadísticas obtenidas correctamente',
                    'data' => array(
                        'total' => $total,
                        'clasificacion' => $clasificacion,
                        'finalizadas' => $finalizadas
                    )
                );

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getEstadisticas: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getTareasByProduccion($idproduccion)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (empty($idproduccion) || !is_numeric($idproduccion)) {
                $arrResponse = array('status' => false, 'message' => 'ID de producción inválido');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $tareas = $this->tarea_model->getTareasByProduccion(intval($idproduccion));
                $arrResponse = array('status' => true, 'data' => $tareas);
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getTareasByProduccion: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function updateTarea()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents("php://input");
                $request = json_decode($postdata, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Obtener ID de usuario
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                if (!$idusuario) {
                    $arrResponse = array('status' => false, 'message' => 'Usuario no autenticado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $result = $this->tarea_model->updateTarea($request);

                if ($result) {
                    // Registrar en bitácora
                    $this->bitacoraModel->registrarAccion('tarea_produccion', 'ACTUALIZAR', $idusuario);
                    $arrResponse = array('status' => true, 'message' => 'Tarea actualizada correctamente');
                } else {
                    $arrResponse = array('status' => false, 'message' => 'No se pudo actualizar la tarea');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en updateTarea: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function asignarTarea()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents("php://input");
                $request = json_decode($postdata, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Validar campos obligatorios
                $camposObligatorios = ['idproduccion', 'idempleado', 'cantidad_asignada'];
                foreach ($camposObligatorios as $campo) {
                    if (!isset($request[$campo]) || empty($request[$campo])) {
                        $arrResponse = array('status' => false, 'message' => "El campo $campo es obligatorio");
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }
                }

                // Obtener ID de usuario
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                if (!$idusuario) {
                    $arrResponse = array('status' => false, 'message' => 'Usuario no autenticado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $result = $this->tarea_model->insertTarea($request);

                if ($result) {
                    // Registrar en bitácora
                    $this->bitacoraModel->registrarAccion('tarea_produccion', 'INSERTAR', $idusuario);
                    $arrResponse = array('status' => true, 'message' => 'Tarea asignada correctamente');
                } else {
                    $arrResponse = array('status' => false, 'message' => 'No se pudo asignar la tarea');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en asignarTarea: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function exportarProducciones()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrData = $this->model->selectAllProducciones();

                if ($arrData['status']) {
                    $data['producciones'] = $arrData['data'];
                    $data['page_title'] = "Reporte de Producciones";
                    $data['fecha_reporte'] = date('d/m/Y H:i:s');

                    $arrResponse = array('status' => true, 'message' => 'Datos preparados para exportación', 'data' => $data);
                } else {
                    $arrResponse = array('status' => false, 'message' => 'No se pudieron obtener los datos');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en exportarProducciones: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }
}
?>