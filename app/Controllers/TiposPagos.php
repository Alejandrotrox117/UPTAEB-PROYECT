<?php

require_once "app/core/Controllers.php";
require_once "app/models/tiposPagosModel.php";
require_once "helpers/helpers.php";
require_once "helpers/permisosVerificar.php";
require_once "helpers/PermisosHelper.php";
require_once "app/models/bitacoraModel.php";
require_once "helpers/expresiones_regulares.php";
require_once "helpers/bitacora_helper.php";

class TiposPagos extends Controllers
{
    private $bitacoraModel;

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
        $this->model = new TiposPagosModel();
        $this->bitacoraModel = new BitacoraModel();

        if (!$this->obtenerUsuarioSesion()) {
            header('Location: ' . base_url() . '/login');
            die();
        }
    }

    public function createTipoPago()
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

                $datosLimpios = [
                    'nombre' => ExpresionesRegulares::limpiar($request['nombre'] ?? '', 'nombre')
                ];

                if (empty($datosLimpios['nombre'])) {
                    $arrResponse = array('status' => false, 'message' => 'El nombre es obligatorio');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $resultadoValidacion = ExpresionesRegulares::validarCampos($datosLimpios, ['nombre' => 'nombre']);

                if (!$resultadoValidacion['nombre']['valido']) {
                    $arrResponse = array(
                        'status' => false,
                        'message' => ExpresionesRegulares::obtenerMensajeError('nombre', 'nombre')
                    );
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrData = array('nombre' => $datosLimpios['nombre']);
                $idusuario = $this->obtenerUsuarioSesion();

                if (!$idusuario) {
                    error_log("ERROR: No se encontró ID de usuario en la sesión durante createTipoPago()");
                    $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrResponse = $this->model->insertTipoPago($arrData);

                if ($arrResponse['status'] === true) {
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('tipos_pagos', 'INSERTAR', $idusuario);
                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la creación del tipo de pago ID: " .
                            ($arrResponse['tipo_pago_id'] ?? 'desconocido'));
                    }
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en createTipoPago: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    private function obtenerUsuarioSesion()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['idusuario'])) {
            return $_SESSION['idusuario'];
        } elseif (isset($_SESSION['idUser'])) {
            return $_SESSION['idUser'];
        } elseif (isset($_SESSION['usuario_id'])) {
            return $_SESSION['usuario_id'];
        } else {
            error_log("ERROR: No se encontró ID de usuario en la sesión");
            return null;
        }
    }

    public function index()
    {
        $idusuario = $this->obtenerUsuarioSesion();
        BitacoraHelper::registrarAccesoModulo('tipos_pagos', $idusuario, $this->bitacoraModel);

        $data['page_tag'] = "Tipos de Pagos";
        $data['page_title'] = "Administración de Tipos de Pagos";
        $data['page_name'] = "tipos_pagos";
        $data['page_content'] = "Gestión integral de tipos de pagos del sistema";
        $data['page_functions_js'] = "functions_tipos_pagos.js";
        $this->views->getView($this, "tipos_pagos", $data);
    }

    public function getTiposPagosData()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrResponse = $this->model->selectAllTiposPagos();
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getTiposPagosData: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getTipoPagoById($idtipo_pago)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (empty($idtipo_pago) || !is_numeric($idtipo_pago)) {
                $arrResponse = array('status' => false, 'message' => 'ID de tipo de pago inválido');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $arrData = $this->model->selectTipoPagoById(intval($idtipo_pago));
                if (!empty($arrData)) {
                    $arrResponse = array('status' => true, 'data' => $arrData);
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Tipo de pago no encontrado');
                }
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getTipoPagoById: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function updateTipoPago()
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

                $intIdTipoPago = intval($request['idtipo_pago'] ?? 0);
                if ($intIdTipoPago <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'ID de tipo de pago inválido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $datosLimpios = [
                    'nombre' => ExpresionesRegulares::limpiar($request['nombre'] ?? '', 'nombre')
                ];

                if (empty($datosLimpios['nombre'])) {
                    $arrResponse = array('status' => false, 'message' => 'El nombre es obligatorio');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $resultadoValidacion = ExpresionesRegulares::validarCampos($datosLimpios, ['nombre' => 'nombre']);

                if (!$resultadoValidacion['nombre']['valido']) {
                    $arrResponse = array(
                        'status' => false,
                        'message' => ExpresionesRegulares::obtenerMensajeError('nombre', 'nombre')
                    );
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrData = array('nombre' => $datosLimpios['nombre']);
                $idusuario = $this->obtenerUsuarioSesion();

                if (!$idusuario) {
                    error_log("ERROR: No se encontró ID de usuario en la sesión durante updateTipoPago()");
                    $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrResponse = $this->model->updateTipoPago($intIdTipoPago, $arrData);

                if ($arrResponse['status'] === true) {
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('tipos_pagos', 'ACTUALIZAR', $idusuario);
                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la actualización del tipo de pago ID: " . $intIdTipoPago);
                    }
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en updateTipoPago: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function deleteTipoPago()
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

                $intIdTipoPago = intval($request['idtipo_pago'] ?? 0);
                if ($intIdTipoPago <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'ID de tipo de pago inválido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idusuario = $this->obtenerUsuarioSesion();
                $requestDelete = $this->model->deleteTipoPagoById($intIdTipoPago);
                
                if ($requestDelete) {
                    $arrResponse = array('status' => true, 'message' => 'Tipo de pago desactivado correctamente');
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Error al desactivar el tipo de pago');
                }
                
                if ($arrResponse['status'] === true) {
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('tipos_pagos', 'ELIMINAR', $idusuario);
                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la eliminación del tipo de pago ID: " . $intIdTipoPago);
                    }
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en deleteTipoPago: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getTiposPagosActivos()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrResponse = $this->model->selectTiposPagosActivos();
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getTiposPagosActivos: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }
}
?>