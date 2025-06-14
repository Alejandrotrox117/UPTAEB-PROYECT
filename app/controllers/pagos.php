<?php
require_once "app/core/Controllers.php";
require_once "app/models/pagosModel.php";
require_once "helpers/helpers.php";
require_once "helpers/permisosVerificar.php";
require_once "helpers/PermisosHelper.php";
require_once "app/models/bitacoraModel.php";
require_once "helpers/expresiones_regulares.php";
require_once "helpers/bitacora_helper.php";

class Pagos extends Controllers
{
    private $bitacoraModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = new PagosModel();
        $this->bitacoraModel = new BitacoraModel();

        if (!$this->obtenerUsuarioSesion()) {
            header('Location: ' . base_url() . '/login');
            die();
        }
    }

    public function index()
    {
        $idusuario = $this->obtenerUsuarioSesion();
        if (class_exists('BitacoraHelper')) {
            BitacoraHelper::registrarAccesoModulo('pago', $idusuario, $this->bitacoraModel);
        }

        $data['page_tag'] = "Pagos";
        $data['page_title'] = "Administración de Pagos";
        $data['page_name'] = "pagos";
        $data['page_content'] = "Gestión integral de pagos del sistema";
        $data['page_functions_js'] = "functions_pagos.js";
        $this->views->getView($this, "pagos", $data);
    }

    public function createPago()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode(['status' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $postdata = file_get_contents("php://input");
            $request = json_decode($postdata, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error en el formato de datos JSON');
            }

            // Debug: Log de datos recibidos
            error_log("Datos recibidos en createPago: " . print_r($request, true));

            // Validar y limpiar datos
            $datosLimpios = $this->validarDatosPago($request);
            
            // Debug: Log de datos limpios
            error_log("Datos limpios: " . print_r($datosLimpios, true));
            
            // Obtener información del destinatario
            $infoDestinatario = $this->obtenerInformacionDestinatario($datosLimpios);
            
            // Debug: Log de info destinatario
            error_log("Info destinatario: " . print_r($infoDestinatario, true));

            // Preparar datos para inserción
            $arrData = [
                'idpersona' => $infoDestinatario['idpersona'] ?? null,
                'idtipo_pago' => $datosLimpios['idtipo_pago'],
                'idventa' => $datosLimpios['idventa'] ?? null,
                'idcompra' => $datosLimpios['idcompra'] ?? null,
                'idsueldotemp' => $datosLimpios['idsueldotemp'] ?? null,
                'monto' => $datosLimpios['monto'],
                'referencia' => $datosLimpios['referencia'] ?: null,
                'fecha_pago' => $datosLimpios['fecha_pago'],
                'observaciones' => $datosLimpios['observaciones'] ?: null
            ];

            // Debug: Log de datos finales
            error_log("Datos para insertar: " . print_r($arrData, true));

            $resultado = $this->model->insertPago($arrData);
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Error en createPago: " . $e->getMessage());
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function updatePago()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode(['status' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $postdata = file_get_contents("php://input");
            $request = json_decode($postdata, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error en el formato de datos JSON');
            }

            if (empty($request['idpago'])) {
                throw new Exception('ID de pago requerido');
            }

            $idpago = intval($request['idpago']);

            // Verificar que el pago existe y está activo
            $pagoExistente = $this->model->selectPagoById($idpago);
            if (!$pagoExistente['status'] || !$pagoExistente['data']) {
                throw new Exception('El pago no existe');
            }

            if (strtolower($pagoExistente['data']['estatus']) !== 'activo') {
                throw new Exception('Solo se pueden editar pagos con estatus activo');
            }

            // Debug: Log de datos recibidos
            error_log("Datos recibidos en updatePago: " . print_r($request, true));

            // Validar y limpiar datos
            $datosLimpios = $this->validarDatosPago($request);
            
            // Obtener información del destinatario
            $infoDestinatario = $this->obtenerInformacionDestinatario($datosLimpios);

            // Preparar datos para actualización
            $arrData = [
                'idpersona' => $infoDestinatario['idpersona'] ?? null,
                'idtipo_pago' => $datosLimpios['idtipo_pago'],
                'idventa' => $datosLimpios['idventa'] ?? null,
                'idcompra' => $datosLimpios['idcompra'] ?? null,
                'idsueldotemp' => $datosLimpios['idsueldotemp'] ?? null,
                'monto' => $datosLimpios['monto'],
                'referencia' => $datosLimpios['referencia'] ?: null,
                'fecha_pago' => $datosLimpios['fecha_pago'],
                'observaciones' => $datosLimpios['observaciones'] ?: null
            ];

            $resultado = $this->model->updatePago($idpago, $arrData);
            
            // Registrar en bitácora si se actualizó exitosamente
            if ($resultado['status'] && class_exists('BitacoraHelper')) {
                $idusuario = $this->obtenerUsuarioSesion();
                $this->bitacoraModel->registrarAccion(
                    $idusuario,
                    'pago',
                    'actualizar',
                    "Pago ID: $idpago actualizado",
                    json_encode($arrData)
                );
            }

            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Error en updatePago: " . $e->getMessage());
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function conciliarPago()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode(['status' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $postdata = file_get_contents("php://input");
            $request = json_decode($postdata, true);

            if (json_last_error() !== JSON_ERROR_NONE || empty($request['idpago'])) {
                throw new Exception('Datos inválidos');
            }

            $idpago = intval($request['idpago']);
            $idusuario = $this->obtenerUsuarioSesion();

            if (!$idusuario) {
                throw new Exception('Usuario no autenticado');
            }

            $resultado = $this->model->conciliarPago($idpago, $idusuario);
            
            // Registrar en bitácora si se concilió exitosamente
            if ($resultado['status'] && class_exists('BitacoraHelper')) {
                $this->bitacoraModel->registrarAccion(
                    $idusuario,
                    'pago',
                    'conciliar',
                    "Pago ID: $idpago conciliado",
                    json_encode(['idpago' => $idpago])
                );
            }

            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Error en conciliarPago: " . $e->getMessage());
            echo json_encode([
                'status' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getPagosData()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $resultado = $this->model->selectAllPagos();
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getPagosData: " . $e->getMessage());
                echo json_encode([
                    'status' => false,
                    'message' => 'Error al obtener datos de pagos'
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getPagoById($idpago)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (empty($idpago) || !is_numeric($idpago)) {
                echo json_encode([
                    'status' => false,
                    'message' => 'ID de pago inválido'
                ], JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $resultado = $this->model->selectPagoById(intval($idpago));
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getPagoById: " . $e->getMessage());
                echo json_encode([
                    'status' => false,
                    'message' => 'Error al obtener datos del pago'
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function deletePago()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents("php://input");
                $request = json_decode($postdata, true);

                if (json_last_error() !== JSON_ERROR_NONE || empty($request['idpago'])) {
                    throw new Exception('Datos inválidos');
                }

                $idpago = intval($request['idpago']);
                
                // Verificar que el pago existe y está activo
                $pagoExistente = $this->model->selectPagoById($idpago);
                if (!$pagoExistente['status'] || !$pagoExistente['data']) {
                    throw new Exception('El pago no existe');
                }

                if (strtolower($pagoExistente['data']['estatus']) !== 'activo') {
                    throw new Exception('Solo se pueden eliminar pagos con estatus activo');
                }

                $resultado = $this->model->deletePagoById($idpago);
                
                // Registrar en bitácora si se eliminó exitosamente
                if ($resultado['status'] && class_exists('BitacoraHelper')) {
                    $idusuario = $this->obtenerUsuarioSesion();
                    $this->bitacoraModel->registrarAccion(
                        $idusuario,
                        'pago',
                        'eliminar',
                        "Pago ID: $idpago eliminado",
                        json_encode(['idpago' => $idpago])
                    );
                }

                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

            } catch (Exception $e) {
                error_log("Error en deletePago: " . $e->getMessage());
                echo json_encode([
                    'status' => false,
                    'message' => $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    // Métodos para obtener datos relacionados
    public function getTiposPago()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $resultado = $this->model->selectTiposPago();
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getTiposPago: " . $e->getMessage());
                echo json_encode([
                    'status' => false,
                    'message' => 'Error al obtener tipos de pago'
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getComprasPendientes()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $resultado = $this->model->selectComprasPendientes();
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getComprasPendientes: " . $e->getMessage());
                echo json_encode([
                    'status' => false,
                    'message' => 'Error al obtener compras pendientes'
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getVentasPendientes()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $resultado = $this->model->selectVentasPendientes();
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getVentasPendientes: " . $e->getMessage());
                echo json_encode([
                    'status' => false,
                    'message' => 'Error al obtener ventas pendientes'
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getSueldosPendientes()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $resultado = $this->model->selectSueldosPendientes();
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getSueldosPendientes: " . $e->getMessage());
                echo json_encode([
                    'status' => false,
                    'message' => 'Error al obtener sueldos pendientes'
                ], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    // Métodos auxiliares privados
    private function validarDatosPago($request)
    {
        $datosLimpios = [
            'tipo_pago' => trim($request['tipo_pago'] ?? ''),
            'monto' => floatval($request['monto'] ?? 0),
            'idtipo_pago' => intval($request['idtipo_pago'] ?? 0),
            'fecha_pago' => trim($request['fecha_pago'] ?? ''),
            'referencia' => trim($request['referencia'] ?? ''),
            'observaciones' => trim($request['observaciones'] ?? '')
        ];

        // Validaciones básicas
        if (empty($datosLimpios['tipo_pago'])) {
            throw new Exception('Debe seleccionar un tipo de pago');
        }

        if ($datosLimpios['monto'] <= 0) {
            throw new Exception('El monto debe ser mayor a 0');
        }

        if (empty($datosLimpios['idtipo_pago'])) {
            throw new Exception('Debe seleccionar un método de pago');
        }

        if (!$this->validarFecha($datosLimpios['fecha_pago'])) {
            throw new Exception('Fecha de pago inválida');
        }

        // Validaciones específicas por tipo
        switch ($datosLimpios['tipo_pago']) {
            case 'compra':
                $datosLimpios['idcompra'] = intval($request['idcompra'] ?? 0);
                if (empty($datosLimpios['idcompra'])) {
                    throw new Exception('Debe seleccionar una compra');
                }
                break;
                
            case 'venta':
                $datosLimpios['idventa'] = intval($request['idventa'] ?? 0);
                if (empty($datosLimpios['idventa'])) {
                    throw new Exception('Debe seleccionar una venta');
                }
                break;
                
            case 'sueldo':
                $datosLimpios['idsueldotemp'] = intval($request['idsueldotemp'] ?? 0);
                if (empty($datosLimpios['idsueldotemp'])) {
                    throw new Exception('Debe seleccionar un sueldo');
                }
                break;
                
            case 'otro':
                $datosLimpios['descripcion'] = trim($request['descripcion'] ?? '');
                if (empty($datosLimpios['descripcion'])) {
                    throw new Exception('La descripción es obligatoria para otros pagos');
                }
                break;
                
            default:
                throw new Exception('Tipo de pago no válido');
        }

        return $datosLimpios;
    }

    private function obtenerInformacionDestinatario($datos)
    {
        switch ($datos['tipo_pago']) {
            case 'compra':
                if (!empty($datos['idcompra'])) {
                    return $this->model->getInfoCompra($datos['idcompra']);
                }
                break;
                
            case 'venta':
                if (!empty($datos['idventa'])) {
                    return $this->model->getInfoVenta($datos['idventa']);
                }
                break;
                
            case 'sueldo':
                if (!empty($datos['idsueldotemp'])) {
                    return $this->model->getInfoSueldo($datos['idsueldotemp']);
                }
                break;
                
            case 'otro':
                // Para tipo "otro" no hay destinatario específico
                return ['idpersona' => null];
                
            default:
                return ['idpersona' => null];
        }
        
        return ['idpersona' => null];
    }

    private function obtenerUsuarioSesion()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['idusuario'] ?? $_SESSION['idUser'] ?? $_SESSION['usuario_id'] ?? null;
    }

    private function validarFecha($fecha)
    {
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }
}
?>