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
        $data['page_content'] = "Control integral de lotes, operarios y nómina de producción";
        $data['page_functions_js'] = "functions_produccion.js";
        $this->views->getView($this, "produccion", $data);
    }

    // ========================================
    // GESTIÓN DE LOTES
    // ========================================

    public function createLote()
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

                // Validación de campos obligatorios
                $camposRequeridos = ['fecha_jornada', 'volumen_estimado', 'idsupervisor'];
                foreach ($camposRequeridos as $campo) {
                    if (empty($request[$campo])) {
                        $arrResponse = array('status' => false, 'message' => "El campo {$campo} es obligatorio");
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }
                }

                // Validar fecha no sea anterior a hoy
                $fechaJornada = $request['fecha_jornada'];
                $fechaHoy = date('Y-m-d');
                if ($fechaJornada < $fechaHoy) {
                    $arrResponse = array('status' => false, 'message' => 'La fecha de jornada no puede ser anterior a hoy');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Validar volumen estimado
                $volumenEstimado = floatval($request['volumen_estimado']);
                if ($volumenEstimado <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'El volumen estimado debe ser mayor a 0');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrData = array(
                    'fecha_jornada' => $fechaJornada,
                    'volumen_estimado' => $volumenEstimado,
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

    public function getLoteById($idlote)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (empty($idlote) || !is_numeric($idlote)) {
                $arrResponse = array('status' => false, 'message' => 'ID de lote inválido');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
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

    public function iniciarLote()
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

                $idlote = intval($request['idlote'] ?? 0);
                if ($idlote <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'ID de lote inválido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
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

    // ========================================
    // GESTIÓN DE ASIGNACIONES DE OPERARIOS
    // ========================================

    public function asignarOperarios()
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

                $idlote = intval($request['idlote'] ?? 0);
                $operarios = $request['operarios'] ?? [];

                if ($idlote <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'ID de lote inválido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                if (empty($operarios) || !is_array($operarios)) {
                    $arrResponse = array('status' => false, 'message' => 'Debe asignar al menos un operario');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $arrResponse = $this->model->asignarOperariosLote($idlote, $operarios);

                if ($arrResponse['status'] === true) {
                    $this->bitacoraModel->registrarAccion('asignacion_operarios', 'INSERTAR', $idusuario);
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en asignarOperarios: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getOperariosDisponibles()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $fecha = $_GET['fecha'] ?? date('Y-m-d');
                $arrResponse = $this->model->selectOperariosDisponibles($fecha);
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getOperariosDisponibles: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getAsignacionesLote($idlote)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (empty($idlote) || !is_numeric($idlote)) {
                $arrResponse = array('status' => false, 'message' => 'ID de lote inválido');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $arrResponse = $this->model->selectAsignacionesLote(intval($idlote));
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getAsignacionesLote: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    // ========================================
    // REGISTRO DE PROCESOS DE PRODUCCIÓN
    // ========================================

    public function registrarClasificacion()
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

                // Validaciones
                $camposRequeridos = ['idlote', 'idempleado', 'idproducto_origen', 'idproducto_clasificado', 'kg_procesados', 'kg_limpios', 'kg_contaminantes'];
                foreach ($camposRequeridos as $campo) {
                    if (!isset($request[$campo]) || $request[$campo] === '') {
                        $arrResponse = array('status' => false, 'message' => "El campo {$campo} es obligatorio");
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }
                }

                $kgProcesados = floatval($request['kg_procesados']);
                $kgLimpios = floatval($request['kg_limpios']);
                $kgContaminantes = floatval($request['kg_contaminantes']);

                // Validar que kg_limpios + kg_contaminantes = kg_procesados
                if (abs(($kgLimpios + $kgContaminantes) - $kgProcesados) > 0.01) {
                    $arrResponse = array('status' => false, 'message' => 'La suma de kg limpios y contaminantes debe igual al total procesado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrData = array(
                    'idlote' => intval($request['idlote']),
                    'idempleado' => intval($request['idempleado']),
                    'idproducto_origen' => intval($request['idproducto_origen']),
                    'idproducto_clasificado' => intval($request['idproducto_clasificado']),
                    'kg_procesados' => $kgProcesados,
                    'kg_limpios' => $kgLimpios,
                    'kg_contaminantes' => $kgContaminantes,
                    'observaciones' => trim($request['observaciones'] ?? '')
                );

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $arrResponse = $this->model->registrarProcesoClasiificacion($arrData);

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

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Validaciones
                $camposRequeridos = ['idlote', 'idempleado', 'idproducto_clasificado', 'peso_paca', 'calidad'];
                foreach ($camposRequeridos as $campo) {
                    if (!isset($request[$campo]) || $request[$campo] === '') {
                        $arrResponse = array('status' => false, 'message' => "El campo {$campo} es obligatorio");
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }
                }

                $pesoPaca = floatval($request['peso_paca']);
                if ($pesoPaca <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'El peso de la paca debe ser mayor a 0');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrData = array(
                    'idlote' => intval($request['idlote']),
                    'idempleado' => intval($request['idempleado']),
                    'idproducto_clasificado' => intval($request['idproducto_clasificado']),
                    'peso_paca' => $pesoPaca,
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

    // ========================================
    // GESTIÓN DE REGISTRO DIARIO Y NÓMINA
    // ========================================

    public function registrarProduccionDiaria()
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

                $idlote = intval($request['idlote'] ?? 0);
                $registros = $request['registros'] ?? [];

                if ($idlote <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'ID de lote inválido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                if (empty($registros) || !is_array($registros)) {
                    $arrResponse = array('status' => false, 'message' => 'Debe proporcionar los registros de producción');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
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

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $fechaInicio = $request['fecha_inicio'] ?? '';
                $fechaFin = $request['fecha_fin'] ?? '';

                if (empty($fechaInicio) || empty($fechaFin)) {
                    $arrResponse = array('status' => false, 'message' => 'Debe especificar el rango de fechas');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
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

    public function cerrarLote()
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

                $idlote = intval($request['idlote'] ?? 0);
                if ($idlote <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'ID de lote inválido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
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

    // ========================================
    // REPORTES Y CONSULTAS
    // ========================================

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

    public function getProductividadOperarios()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
                $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
                $arrResponse = $this->model->selectProductividadOperarios($fechaInicio, $fechaFin);
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getProductividadOperarios: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getAlertasProduccion()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $estatus = $_GET['estatus'] ?? 'PENDIENTE';
                $arrResponse = $this->model->selectAlertasProduccion($estatus);
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getAlertasProduccion: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getPacasProducidas()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
                $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
                $arrResponse = $this->model->selectPacasProducidas($fechaInicio, $fechaFin);
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getPacasProducidas: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    // ========================================
    // CONFIGURACIÓN
    // ========================================

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

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrData = array(
                    'productividad_clasificacion' => floatval($request['productividad_clasificacion'] ?? 150),
                    'capacidad_maxima_planta' => intval($request['capacidad_maxima_planta'] ?? 50),
                    'salario_base' => floatval($request['salario_base'] ?? 30),
                    'beta_clasificacion' => floatval($request['beta_clasificacion'] ?? 0.25),
                    'gamma_empaque' => floatval($request['gamma_empaque'] ?? 5),
                    'umbral_error_maximo' => floatval($request['umbral_error_maximo'] ?? 5),
                    'penalizacion_beta' => floatval($request['penalizacion_beta'] ?? 0.10),
                    'peso_minimo_paca' => floatval($request['peso_minimo_paca'] ?? 25),
                    'peso_maximo_paca' => floatval($request['peso_maximo_paca'] ?? 35)
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

    // ========================================
    // MÉTODOS AUXILIARES
    // ========================================

    public function resolverAlerta()
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

                $idalerta = intval($request['idalerta'] ?? 0);
                $observaciones = trim($request['observaciones_revision'] ?? '');

                if ($idalerta <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'ID de alerta inválido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $arrResponse = $this->model->resolverAlerta($idalerta, $idusuario, $observaciones);

                if ($arrResponse['status'] === true) {
                    $this->bitacoraModel->registrarAccion('alerta_produccion', 'RESOLVER', $idusuario);
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en resolverAlerta: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function exportarReporteProduccion()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
                $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
                $tipo = $_GET['tipo'] ?? 'general';

                $arrData = $this->model->generarReporteProduccion($fechaInicio, $fechaFin, $tipo);

                if ($arrData['status']) {
                    $data['reporte'] = $arrData['data'];
                    $data['page_title'] = "Reporte de Producción";
                    $data['fecha_reporte'] = date('d/m/Y H:i:s');
                    $data['periodo'] = "$fechaInicio al $fechaFin";

                    $arrResponse = array('status' => true, 'message' => 'Reporte generado exitosamente', 'data' => $data);
                } else {
                    $arrResponse = array('status' => false, 'message' => 'No se pudo generar el reporte');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en exportarReporteProduccion: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }
}