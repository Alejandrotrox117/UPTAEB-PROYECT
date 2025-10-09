   
<?php

require_once "app/core/Controllers.php";
require_once "app/models/produccionModel.php";
require_once "helpers/helpers.php";
require_once "helpers/PermisosModuloVerificar.php";
require_once "app/models/bitacoraModel.php";
require_once "helpers/PermisosHelper.php";
require_once "helpers/expresiones_regulares.php";
require_once "helpers/bitacora_helper.php";

class Produccion extends Controllers
{
    private $bitacoraModel;
    private $BitacoraHelper;

    public function __construct()
    {
        parent::__construct();
        
        $this->bitacoraModel = new BitacoraModel();
        $this->BitacoraHelper = new BitacoraHelper();

        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        if (!PermisosModuloVerificar::verificarAccesoModulo('produccion')) {
            $this->views->getView($this, "permisos");
            exit();
        }
    }

    public function index()
    {
        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        BitacoraHelper::registrarAccesoModulo('produccion', $idusuario, $this->bitacoraModel);

        $data['page_tag'] = "Producción";
        $data['page_title'] = "Gestión de Producción";
        $data['page_name'] = "produccion";
        $data['page_content'] = "Control de lotes, operarios y procesos de producción";
        $data['page_functions_js'] = "functions_produccion.js";
        $this->views->getView($this, "produccion", $data);
    }


    public function createLote()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents('php://input');
                $request = json_decode($postdata, true);

                if (empty($request['fecha_jornada']) || 
                    empty($request['volumen_estimado']) || 
                    empty($request['idsupervisor'])) {
                    echo json_encode([
                        'status' => false, 
                        'message' => 'Todos los campos obligatorios deben ser completados.'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrData = array(
                    'fecha_jornada' => $request['fecha_jornada'],
                    'volumen_estimado' => floatval($request['volumen_estimado']),
                    'idsupervisor' => intval($request['idsupervisor']),
                    'observaciones' => trim($request['observaciones'] ?? '')
                );

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $arrResponse = $this->model->insertLote($arrData);

                if ($arrResponse['status'] === true) {
                    $this->bitacoraModel->registrarAccion('lote_produccion', 'INSERTAR', $idusuario);
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en createLote: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getLotesData()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrResponse = $this->model->selectAllLotes();
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getLotesData: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getLoteById($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $idlote = is_array($params) ? ($params[0] ?? null) : $params;
                
                if (empty($idlote) || !is_numeric($idlote)) {
                    echo json_encode([
                        'status' => false,
                        'message' => 'ID de lote inválido'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrData = $this->model->selectLoteById(intval($idlote));
                if (!empty($arrData)) {
                    $arrResponse = array('status' => true, 'data' => $arrData);
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Lote no encontrado');
                }
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getLoteById: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function obtenerDetalleLote($idlote)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $idlote = intval($idlote);
                
                // Obtener datos del lote
                $lote = $this->model->selectLoteById($idlote);
                
                if (empty($lote)) {
                    $arrResponse = array(
                        'status' => 'error',
                        'message' => 'Lote no encontrado'
                    );
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }
                
                // Obtener procesos de clasificación del lote
                $procesosClasificacion = $this->model->obtenerProcesosClasificacionPorLote($idlote);
                
                // Obtener procesos de empaque del lote
                $procesosEmpaque = $this->model->obtenerProcesosEmpaquePorLote($idlote);
                
                $arrResponse = array(
                    'status' => 'success',
                    'data' => array(
                        'lote' => $lote,
                        'procesos' => array(
                            'clasificacion' => $procesosClasificacion,
                            'empaque' => $procesosEmpaque
                        )
                    )
                );
                
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en obtenerDetalleLote: " . $e->getMessage());
                $arrResponse = array(
                    'status' => 'error',
                    'message' => 'Error al obtener detalle del lote: ' . $e->getMessage()
                );
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function iniciarLote()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents('php://input');
                $request = json_decode($postdata, true);

                $idlote = intval($request['idlote'] ?? 0);
                
                if ($idlote <= 0) {
                    echo json_encode([
                        'status' => false, 
                        'message' => 'ID de lote no válido.'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $arrResponse = $this->model->iniciarLoteProduccion($idlote);

                if ($arrResponse['status'] === true) {
                    $this->bitacoraModel->registrarAccion('lote_produccion', 'INICIAR', $idusuario);
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en iniciarLote: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function cerrarLote()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents('php://input');
                $request = json_decode($postdata, true);

                $idlote = intval($request['idlote'] ?? 0);
                
                if ($idlote <= 0) {
                    echo json_encode([
                        'status' => false, 
                        'message' => 'ID de lote no válido.'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $arrResponse = $this->model->cerrarLoteProduccion($idlote);

                if ($arrResponse['status'] === true) {
                    $this->bitacoraModel->registrarAccion('lote_produccion', 'CERRAR', $idusuario);
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en cerrarLote: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }


    public function registrarClasificacion()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents('php://input');
                $request = json_decode($postdata, true);

                $camposRequeridos = ['idlote', 'idempleado', 'idproducto_origen', 'kg_procesados', 'kg_limpios', 'kg_contaminantes'];
                foreach ($camposRequeridos as $campo) {
                    if (!isset($request[$campo]) || $request[$campo] === '') {
                        echo json_encode([
                            'status' => false, 
                            'message' => "El campo {$campo} es obligatorio."
                        ], JSON_UNESCAPED_UNICODE);
                        die();
                    }
                }

                $procesados = floatval($request['kg_procesados']);
                $limpios = floatval($request['kg_limpios']);
                $contaminantes = floatval($request['kg_contaminantes']);
                
                if (abs(($limpios + $contaminantes) - $procesados) > 0.01) {
                    echo json_encode([
                        'status' => false, 
                        'message' => 'La suma de material limpio y contaminantes debe ser igual al total procesado.'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrData = array(
                    'idlote' => intval($request['idlote']),
                    'idempleado' => intval($request['idempleado']),
                    'idproducto_origen' => intval($request['idproducto_origen']),
                    'kg_procesados' => $procesados,
                    'kg_limpios' => $limpios,
                    'kg_contaminantes' => $contaminantes,
                    'observaciones' => trim($request['observaciones'] ?? '')
                );

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $arrResponse = $this->model->registrarProcesoClasificacion($arrData);

                if ($arrResponse['status'] === true) {
                    $this->bitacoraModel->registrarAccion('proceso_clasificacion', 'INSERTAR', $idusuario);
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en registrarClasificacion: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function registrarEmpaque()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents('php://input');
                $request = json_decode($postdata, true);

                $camposRequeridos = ['idlote', 'idempleado', 'idproducto_clasificado', 'peso_paca', 'calidad'];
                foreach ($camposRequeridos as $campo) {
                    if (!isset($request[$campo]) || $request[$campo] === '') {
                        echo json_encode([
                            'status' => false, 
                            'message' => "El campo {$campo} es obligatorio."
                        ], JSON_UNESCAPED_UNICODE);
                        die();
                    }
                }

                $arrData = array(
                    'idlote' => intval($request['idlote']),
                    'idempleado' => intval($request['idempleado']),
                    'idproducto_clasificado' => intval($request['idproducto_clasificado']),
                    'peso_paca' => floatval($request['peso_paca']),
                    'calidad' => trim($request['calidad']),
                    'observaciones' => trim($request['observaciones'] ?? '')
                );

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $arrResponse = $this->model->registrarProcesoEmpaque($arrData);

                if ($arrResponse['status'] === true) {
                    $this->bitacoraModel->registrarAccion('proceso_empaque', 'INSERTAR', $idusuario);
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en registrarEmpaque: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getProcesosRecientes()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $fecha = $_GET['fecha'] ?? date('Y-m-d');
                $arrResponse = $this->model->selectProcesosRecientes($fecha);
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getProcesosRecientes: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }


    public function registrarProduccionDiaria()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents('php://input');
                $request = json_decode($postdata, true);

                $idlote = intval($request['idlote'] ?? 0);
                $registros = $request['registros'] ?? [];

                if ($idlote <= 0) {
                    echo json_encode([
                        'status' => false, 
                        'message' => 'ID de lote no válido.'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                if (empty($registros)) {
                    echo json_encode([
                        'status' => false, 
                        'message' => 'No hay registros para procesar.'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $arrResponse = $this->model->registrarProduccionDiariaLote($idlote, $registros);

                if ($arrResponse['status'] === true) {
                    $this->bitacoraModel->registrarAccion('registro_produccion', 'INSERTAR_MASIVO', $idusuario);
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en registrarProduccionDiaria: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function calcularNomina()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents('php://input');
                $request = json_decode($postdata, true);

                $fechaInicio = $request['fecha_inicio'] ?? '';
                $fechaFin = $request['fecha_fin'] ?? '';

                if (empty($fechaInicio) || empty($fechaFin)) {
                    echo json_encode([
                        'status' => false, 
                        'message' => 'Las fechas de inicio y fin son obligatorias.'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                if ($fechaInicio > $fechaFin) {
                    echo json_encode([
                        'status' => false, 
                        'message' => 'La fecha de inicio no puede ser posterior a la fecha fin.'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $arrResponse = $this->model->calcularNominaProduccion($fechaInicio, $fechaFin);

                if ($arrResponse['status'] === true) {
                    $this->bitacoraModel->registrarAccion('nomina_produccion', 'CALCULAR', $idusuario);
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en calcularNomina: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getRegistrosNomina()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
               
                $arrResponse = $this->model->selectRegistrosNomina();
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getRegistrosNomina: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }


    public function getConfiguracionProduccion()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrResponse = $this->model->selectConfiguracionProduccion();
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getConfiguracionProduccion: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function updateConfiguracionProduccion()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents('php://input');
                $request = json_decode($postdata, true);

                $camposNumericos = [
                    'productividad_clasificacion', 'capacidad_maxima_planta', 'salario_base',
                    'beta_clasificacion', 'gamma_empaque', 'umbral_error_maximo',
                    'peso_minimo_paca', 'peso_maximo_paca'
                ];

                foreach ($camposNumericos as $campo) {
                    if (!isset($request[$campo]) || !is_numeric($request[$campo])) {
                        echo json_encode([
                            'status' => false, 
                            'message' => "El campo {$campo} debe ser un valor numérico válido."
                        ], JSON_UNESCAPED_UNICODE);
                        die();
                    }
                }

                if (floatval($request['peso_minimo_paca']) >= floatval($request['peso_maximo_paca'])) {
                    echo json_encode([
                        'status' => false, 
                        'message' => 'El peso mínimo debe ser menor al peso máximo.'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                if (floatval($request['umbral_error_maximo']) > 100) {
                    echo json_encode([
                        'status' => false, 
                        'message' => 'El umbral de error no puede ser mayor a 100%.'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrData = array(
                    'productividad_clasificacion' => floatval($request['productividad_clasificacion']),
                    'capacidad_maxima_planta' => intval($request['capacidad_maxima_planta']),
                    'salario_base' => floatval($request['salario_base']),
                    'beta_clasificacion' => floatval($request['beta_clasificacion']),
                    'gamma_empaque' => floatval($request['gamma_empaque']),
                    'umbral_error_maximo' => floatval($request['umbral_error_maximo']),
                    'peso_minimo_paca' => floatval($request['peso_minimo_paca']),
                    'peso_maximo_paca' => floatval($request['peso_maximo_paca'])
                );

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $arrResponse = $this->model->updateConfiguracionProduccion($arrData);

                if ($arrResponse['status'] === true) {
                    $this->bitacoraModel->registrarAccion('configuracion_produccion', 'ACTUALIZAR', $idusuario);
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en updateConfiguracionProduccion: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
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

    public function getProductos()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $tipo = $_GET['tipo'] ?? 'todos';
                
                $tiposPermitidos = ['todos', 'por_clasificar', 'clasificados'];
                if (!in_array($tipo, $tiposPermitidos)) {
                    $tipo = 'todos';
                }

                $arrResponse = $this->model->selectProductos($tipo);
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getProductos: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }


    public function getEstadisticasProduccion()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $fecha = $_GET['fecha'] ?? date('Y-m-d');
                $arrResponse = $this->model->selectEstadisticasProduccion($fecha);
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getEstadisticasProduccion: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getProduccionDiaria()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $fecha = $_GET['fecha'] ?? date('Y-m-d');
                $arrResponse = $this->model->selectProduccionDiaria($fecha);
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getProduccionDiaria: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }


    private function validarDatosLote($data)
    {
        $errores = [];

        if (empty($data['fecha_jornada'])) {
            $errores[] = 'La fecha de jornada es obligatoria.';
        } else {
            $fecha = strtotime($data['fecha_jornada']);
            if ($fecha === false || $fecha < strtotime('today')) {
                $errores[] = 'La fecha de jornada debe ser igual o posterior a hoy.';
            }
        }

        if (empty($data['volumen_estimado']) || floatval($data['volumen_estimado']) <= 0) {
            $errores[] = 'El volumen estimado debe ser mayor a cero.';
        }

        if (empty($data['idsupervisor']) || intval($data['idsupervisor']) <= 0) {
            $errores[] = 'Debe seleccionar un supervisor válido.';
        }

        return $errores;
    }

    private function validarDatosClasificacion($data)
    {
        $errores = [];

        $camposRequeridos = [
            'idlote' => 'Debe seleccionar un lote.',
            'idempleado' => 'Debe seleccionar un empleado.',
            'idproducto_origen' => 'Debe seleccionar un producto origen.',
            'kg_procesados' => 'Los kg procesados son obligatorios.',
            'kg_limpios' => 'Los kg limpios son obligatorios.',
            'kg_contaminantes' => 'Los kg contaminantes son obligatorios.'
        ];

        foreach ($camposRequeridos as $campo => $mensaje) {
            if (empty($data[$campo])) {
                $errores[] = $mensaje;
            }
        }

        if (!empty($data['kg_procesados']) && floatval($data['kg_procesados']) <= 0) {
            $errores[] = 'Los kg procesados deben ser mayor a cero.';
        }

        if (!empty($data['kg_limpios']) && floatval($data['kg_limpios']) < 0) {
            $errores[] = 'Los kg limpios no pueden ser negativos.';
        }

        if (!empty($data['kg_contaminantes']) && floatval($data['kg_contaminantes']) < 0) {
            $errores[] = 'Los kg contaminantes no pueden ser negativos.';
        }

        if (!empty($data['kg_procesados']) && !empty($data['kg_limpios']) && !empty($data['kg_contaminantes'])) {
            $procesados = floatval($data['kg_procesados']);
            $limpios = floatval($data['kg_limpios']);
            $contaminantes = floatval($data['kg_contaminantes']);
            
            if (abs(($limpios + $contaminantes) - $procesados) > 0.01) {
                $errores[] = 'La suma de material limpio y contaminantes debe ser igual al total procesado.';
            }
        }

        return $errores;
    }

    private function validarDatosEmpaque($data)
    {
        $errores = [];

        $camposRequeridos = [
            'idlote' => 'Debe seleccionar un lote.',
            'idempleado' => 'Debe seleccionar un empleado.',
            'idproducto_clasificado' => 'Debe seleccionar un producto clasificado.',
            'peso_paca' => 'El peso de la paca es obligatorio.',
            'calidad' => 'Debe seleccionar la calidad.'
        ];

        foreach ($camposRequeridos as $campo => $mensaje) {
            if (empty($data[$campo])) {
                $errores[] = $mensaje;
            }
        }

        if (!empty($data['peso_paca'])) {
            $peso = floatval($data['peso_paca']);
            if ($peso <= 0) {
                $errores[] = 'El peso de la paca debe ser mayor a cero.';
            }
        }

        if (!empty($data['calidad'])) {
            $calidadesPermitidas = ['PREMIUM', 'ESTANDAR', 'SEGUNDA'];
            if (!in_array($data['calidad'], $calidadesPermitidas)) {
                $errores[] = 'La calidad seleccionada no es válida.';
            }
        }

        return $errores;
    }


    private function respuestaError($mensaje, $codigo = 400)
    {
        http_response_code($codigo);
        return json_encode([
            'status' => false,
            'message' => $mensaje,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
    }

    private function respuestaExito($mensaje, $data = null)
    {
        $response = [
            'status' => true,
            'message' => $mensaje,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    private function limpiarDatos($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'limpiarDatos'], $data);
        }
        
        if (is_string($data)) {
            return trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8'));
        }
        
        return $data;
    }

    private function validarMetodoHTTP($metodo)
    {
        return $_SERVER['REQUEST_METHOD'] === $metodo;
    }

    private function obtenerDatosJSON()
    {
        $postdata = file_get_contents('php://input');
        $data = json_decode($postdata, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Datos JSON inválidos: ' . json_last_error_msg());
        }
        
        return $this->limpiarDatos($data);
    }


    public function verificarDisponibilidadOperario()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $data = $this->obtenerDatosJSON();
                
                $idempleado = intval($data['idempleado'] ?? 0);
                $fecha = $data['fecha'] ?? date('Y-m-d');

                if ($idempleado <= 0) {
                    echo $this->respuestaError('ID de empleado no válido.');
                    die();
                }

                $arrResponse = $this->model->verificarDisponibilidadOperario($idempleado, $fecha);
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en verificarDisponibilidadOperario: " . $e->getMessage());
                echo $this->respuestaError('Error interno del servidor.');
            }
            die();
        }
    }

    public function getProductividadOperario($idempleado)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
                $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
                
                $arrResponse = $this->model->selectProductividadOperario(
                    intval($idempleado), 
                    $fechaInicio, 
                    $fechaFin
                );
                
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getProductividadOperario: " . $e->getMessage());
                echo $this->respuestaError('Error interno del servidor.');
            }
            die();
        }
    }

    public function getPacasProducidas()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
                $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
                $idoperario = $_GET['idoperario'] ?? null;
                
                $arrResponse = $this->model->selectPacasProducidas($fechaInicio, $fechaFin, $idoperario);
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getPacasProducidas: " . $e->getMessage());
                echo $this->respuestaError('Error interno del servidor.');
            }
            die();
        }
    }

    public function getResumenLote($idlote)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrResponse = $this->model->selectResumenLote(intval($idlote));
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getResumenLote: " . $e->getMessage());
                echo $this->respuestaError('Error interno del servidor.');
            }
            die();
        }
    }


    public function validarStockProducto()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $data = $this->obtenerDatosJSON();
                
                $idproducto = intval($data['idproducto'] ?? 0);
                $cantidad = floatval($data['cantidad'] ?? 0);

                if ($idproducto <= 0 || $cantidad <= 0) {
                    echo $this->respuestaError('Parámetros inválidos.');
                    die();
                }

                $arrResponse = $this->model->validarStockProducto($idproducto, $cantidad);
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en validarStockProducto: " . $e->getMessage());
                echo $this->respuestaError('Error interno del servidor.');
            }
            die();
        }
    }

    public function validarCapacidadPlanta()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $data = $this->obtenerDatosJSON();
                
                $fecha = $data['fecha'] ?? date('Y-m-d');
                $operariosAdicionales = intval($data['operarios_adicionales'] ?? 0);

                $arrResponse = $this->model->validarCapacidadPlanta($fecha, $operariosAdicionales);
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en validarCapacidadPlanta: " . $e->getMessage());
                echo $this->respuestaError('Error interno del servidor.');
            }
            die();
        }
    }


    public function limpiarRegistrosTemporales()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                
                $arrResponse = $this->model->limpiarRegistrosTemporales();
                
                if ($arrResponse['status'] === true) {
                    $this->bitacoraModel->registrarAccion('sistema_produccion', 'LIMPIEZA', $idusuario);
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en limpiarRegistrosTemporales: " . $e->getMessage());
                echo $this->respuestaError('Error interno del servidor.');
            }
            die();
        }
    }

    public function recalcularEstadisticas()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $data = $this->obtenerDatosJSON();
                
                $fechaInicio = $data['fecha_inicio'] ?? date('Y-m-01');
                $fechaFin = $data['fecha_fin'] ?? date('Y-m-d');

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $arrResponse = $this->model->recalcularEstadisticas($fechaInicio, $fechaFin);

                if ($arrResponse['status'] === true) {
                    $this->bitacoraModel->registrarAccion('estadisticas_produccion', 'RECALCULAR', $idusuario);
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en recalcularEstadisticas: " . $e->getMessage());
                echo $this->respuestaError('Error interno del servidor.');
            }
            die();
        }
    }


    public function getEstadoSistema()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $fecha = $_GET['fecha'] ?? date('Y-m-d');
                
                $estado = [
                    'fecha_consulta' => $fecha,
                    'lotes_activos' => $this->model->contarLotesActivos($fecha),
                    'operarios_asignados' => $this->model->contarOperariosAsignados($fecha),
                    'procesos_registrados' => $this->model->contarProcesosDelDia($fecha),
                    'configuracion_valida' => $this->model->validarConfiguracion(),
                    'sistema_operativo' => true
                ];

                echo $this->respuestaExito('Estado del sistema obtenido.', $estado);
            } catch (Exception $e) {
                error_log("Error en getEstadoSistema: " . $e->getMessage());
                echo $this->respuestaError('Error interno del servidor.');
            }
            die();
        }
    }
    public function registrarSolicitudPago()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents('php://input');
                $request = json_decode($postdata, true);
                $registros = $request['registros'] ?? [];
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $arrResponse = $this->model->registrarSolicitudPago($registros);
                if ($arrResponse['status'] === true) {
                    $this->bitacoraModel->registrarAccion('sueldo_produccion', 'SOLICITUD_PAGO', $idusuario);
                }
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en registrarSolicitudPago: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    /**
     * Marca un registro de producción como PAGADO
     */
    public function marcarComoPagado()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents('php://input');
                $request = json_decode($postdata, true);
                
                if (empty($request['idregistro'])) {
                    echo json_encode([
                        'status' => false,
                        'message' => 'ID de registro no proporcionado'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idregistro = intval($request['idregistro']);
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                
                $arrResponse = $this->model->marcarRegistroComoPagado($idregistro);
                
                if ($arrResponse['status'] === true) {
                    $this->bitacoraModel->registrarAccion('registro_produccion', 'MARCAR_PAGADO', $idusuario);
                }
                
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en marcarComoPagado: " . $e->getMessage());
                echo json_encode([
                    'status' => false,
                    'message' => 'Error interno del servidor: ' . $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    /**
     * Cancela un registro de producción
     */
    public function cancelarRegistroNomina()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents('php://input');
                $request = json_decode($postdata, true);
                
                if (empty($request['idregistro'])) {
                    echo json_encode([
                        'status' => false,
                        'message' => 'ID de registro no proporcionado'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idregistro = intval($request['idregistro']);
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                
                $arrResponse = $this->model->cancelarRegistroProduccion($idregistro);
                
                if ($arrResponse['status'] === true) {
                    $this->bitacoraModel->registrarAccion('registro_produccion', 'CANCELAR', $idusuario);
                }
                
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en cancelarRegistroNomina: " . $e->getMessage());
                echo json_encode([
                    'status' => false,
                    'message' => 'Error interno del servidor: ' . $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    // ============================================================
    // MÉTODOS PARA REGISTRO_PRODUCCION
    // ============================================================

    /**
     * Crea un nuevo registro de producción
     */
    public function crearRegistroProduccion()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // Validaciones
                $camposRequeridos = [
                    'idlote', 'idempleado', 'fecha', 'tipo_proceso', 
                    'idproducto_inicial', 'idproducto_final', 
                    'cantidad_producida'
                ];

                foreach ($camposRequeridos as $campo) {
                    if (empty($_POST[$campo])) {
                        echo json_encode([
                            'status' => false,
                            'msg' => "El campo {$campo} es obligatorio"
                        ], JSON_UNESCAPED_UNICODE);
                        die();
                    }
                }

                // Validar tipo de proceso
                if (!in_array($_POST['tipo_proceso'], ['CLASIFICACION', 'EMPAQUE'])) {
                    echo json_encode([
                        'status' => false,
                        'msg' => 'Tipo de proceso inválido'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Validar cantidades
                if (floatval($_POST['cantidad_producida']) <= 0) {
                    echo json_encode([
                        'status' => false,
                        'msg' => 'La cantidad producida debe ser mayor a cero'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Mapear los campos del frontend a los del modelo
                $arrData = [
                    'idlote' => intval($_POST['idlote']),
                    'idempleado' => intval($_POST['idempleado']),
                    'fecha_jornada' => $_POST['fecha'],
                    'tipo_movimiento' => $_POST['tipo_proceso'],
                    'idproducto_producir' => intval($_POST['idproducto_inicial']),
                    'cantidad_producir' => floatval($_POST['cantidad_producida']),
                    'idproducto_terminado' => intval($_POST['idproducto_final']),
                    'cantidad_producida' => floatval($_POST['cantidad_producida']),
                    'observaciones' => trim($_POST['observaciones'] ?? '')
                ];

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $arrResponse = $this->model->insertarRegistroProduccion($arrData);

                if ($arrResponse['status'] === true) {
                    $this->bitacoraModel->registrarAccion('registro_produccion', 'INSERTAR', $idusuario);
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);

            } catch (Exception $e) {
                error_log("Error en crearRegistroProduccion: " . $e->getMessage());
                echo json_encode([
                    'status' => false,
                    'message' => 'Error interno del servidor'
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    /**
     * Obtiene todos los registros de producción (con filtros opcionales)
     */
    public function getRegistrosProduccion()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $filtros = [];
                
                if (!empty($_GET['fecha_desde'])) {
                    $filtros['fecha_desde'] = $_GET['fecha_desde'];
                }
                if (!empty($_GET['fecha_hasta'])) {
                    $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
                }
                if (!empty($_GET['tipo_movimiento'])) {
                    $filtros['tipo_movimiento'] = $_GET['tipo_movimiento'];
                }
                if (!empty($_GET['idlote'])) {
                    $filtros['idlote'] = intval($_GET['idlote']);
                }

                $arrResponse = $this->model->selectAllRegistrosProduccion($filtros);
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);

            } catch (Exception $e) {
                error_log("Error en getRegistrosProduccion: " . $e->getMessage());
                echo json_encode([
                    'status' => false,
                    'message' => 'Error interno del servidor',
                    'data' => []
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    /**
     * Obtiene los registros de producción de un lote específico
     */
    public function getRegistrosPorLote($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                error_log("=== getRegistrosPorLote iniciado ===");
                
                // Extraer idlote del array de parámetros o directamente si es un valor
                $idlote = is_array($params) ? ($params[0] ?? null) : $params;
                
                error_log("ID Lote recibido: " . var_export($idlote, true));
                error_log("Params completos: " . var_export($params, true));
                
                if (empty($idlote) || !is_numeric($idlote)) {
                    error_log("ERROR: ID de lote inválido: " . var_export($idlote, true));
                    echo json_encode([
                        'status' => false,
                        'message' => 'ID de lote inválido',
                        'data' => []
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrResponse = $this->model->obtenerRegistrosPorLote(intval($idlote));
                error_log("Respuesta del modelo: " . json_encode([
                    'status' => $arrResponse['status'],
                    'total_registros' => $arrResponse['totales']['total_registros'] ?? 0,
                    'total_producido' => $arrResponse['totales']['total_cantidad_producida'] ?? 0
                ]));
                
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);

            } catch (Exception $e) {
                error_log("ERROR en getRegistrosPorLote: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                echo json_encode([
                    'status' => false,
                    'message' => 'Error interno del servidor',
                    'data' => []
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    /**
     * Actualiza un registro de producción existente
     */
    public function actualizarRegistroProduccion($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents('php://input');
                $request = json_decode($postdata, true);

                $idregistro = is_array($params) ? ($params[0] ?? null) : $params;
                
                if (empty($idregistro) || !is_numeric($idregistro)) {
                    echo json_encode([
                        'status' => false,
                        'message' => 'ID de registro inválido'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrData = [
                    'fecha_jornada' => $request['fecha_jornada'],
                    'idproducto_producir' => intval($request['idproducto_producir']),
                    'cantidad_producir' => floatval($request['cantidad_producir']),
                    'idproducto_terminado' => intval($request['idproducto_terminado']),
                    'cantidad_producida' => floatval($request['cantidad_producida']),
                    'tipo_movimiento' => $request['tipo_movimiento'],
                    'observaciones' => trim($request['observaciones'] ?? '')
                ];

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $arrResponse = $this->model->actualizarRegistroProduccion(intval($idregistro), $arrData);

                if ($arrResponse['status'] === true) {
                    $this->bitacoraModel->registrarAccion('registro_produccion', 'ACTUALIZAR', $idusuario);
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);

            } catch (Exception $e) {
                error_log("Error en actualizarRegistroProduccion: " . $e->getMessage());
                echo json_encode([
                    'status' => false,
                    'message' => 'Error interno del servidor'
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    /**
     * Obtiene un registro de producción por ID
     */
    public function getRegistroById($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $idregistro = is_array($params) ? ($params[0] ?? null) : $params;
                
                if (empty($idregistro) || !is_numeric($idregistro)) {
                    echo json_encode([
                        'status' => false,
                        'message' => 'ID de registro inválido'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrResponse = $this->model->getRegistroById(intval($idregistro));
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);

            } catch (Exception $e) {
                error_log("Error en getRegistroById: " . $e->getMessage());
                echo json_encode([
                    'status' => false,
                    'message' => 'Error interno del servidor'
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    /**
     * Elimina un registro de producción
     */
    public function eliminarRegistroProduccion($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] == 'DELETE' || $_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $idregistro = is_array($params) ? ($params[0] ?? null) : $params;
                
                if (empty($idregistro) || !is_numeric($idregistro)) {
                    echo json_encode([
                        'status' => false,
                        'message' => 'ID de registro inválido'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $arrResponse = $this->model->eliminarRegistroProduccion(intval($idregistro));

                if ($arrResponse['status'] === true) {
                    $this->bitacoraModel->registrarAccion('registro_produccion', 'ELIMINAR', $idusuario);
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);

            } catch (Exception $e) {
                error_log("Error en eliminarRegistroProduccion: " . $e->getMessage());
                echo json_encode([
                    'status' => false,
                    'message' => 'Error interno del servidor'
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    /**
     * Actualiza un lote de producción
     */
    public function actualizarLote($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $idlote = is_array($params) ? ($params[0] ?? null) : $params;
                
                if (empty($idlote) || !is_numeric($idlote)) {
                    echo json_encode([
                        'status' => false,
                        'message' => 'ID de lote inválido'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                $data = json_decode(file_get_contents("php://input"), true);
                
                if (!$data) {
                    echo json_encode([
                        'status' => false,
                        'message' => 'Datos inválidos'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $arrResponse = $this->model->actualizarLote(intval($idlote), $data);

                if ($arrResponse['status'] === true) {
                    $this->bitacoraModel->registrarAccion('lotes_produccion', 'ACTUALIZAR', $idusuario);
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);

            } catch (Exception $e) {
                error_log("Error en actualizarLote: " . $e->getMessage());
                echo json_encode([
                    'status' => false,
                    'message' => 'Error interno del servidor'
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    /**
     * Elimina un lote de producción
     */
    public function eliminarLote($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] == 'DELETE' || $_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $idlote = is_array($params) ? ($params[0] ?? null) : $params;
                
                if (empty($idlote) || !is_numeric($idlote)) {
                    echo json_encode([
                        'status' => false,
                        'message' => 'ID de lote inválido'
                    ], JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $arrResponse = $this->model->eliminarLote(intval($idlote));

                if ($arrResponse['status'] === true) {
                    $this->bitacoraModel->registrarAccion('lotes_produccion', 'ELIMINAR', $idusuario);
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);

            } catch (Exception $e) {
                error_log("Error en eliminarLote: " . $e->getMessage());
                echo json_encode([
                    'status' => false,
                    'message' => 'Error interno del servidor'
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }
}
 
?>