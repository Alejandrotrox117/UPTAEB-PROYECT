<?php
namespace App\Controllers;

use App\Core\Controllers;
use App\Models\PagosModel;
use App\Models\BitacoraModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\PermisosModuloVerificar;
use DateTime;
use Exception;

class Pagos extends Controllers
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

        
        if (!PermisosModuloVerificar::verificarAccesoModulo('pagos')) {
            
            $this->views->getView($this, "permisos");
            exit();
        }
    }

    public function index()
    {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('pagos','ver')
        ) {
            $this->views->getView($this, "permisos");
            exit();
        }

        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        BitacoraHelper::registrarAccesoModulo(
            'pagos',
            $idusuario,
            $this->bitacoraModel
        );

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
            echo json_encode(
                ['status' => false, 'message' => 'Método no permitido'],
                JSON_UNESCAPED_UNICODE
            );
            die();
        }

        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

        try {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('pagos', 'crear')) {
                BitacoraHelper::registrarError('pagos','Intento de crear pago sin permisos',$idusuario,$this->bitacoraModel);
                $arrResponse = array('status' => false,'message' => 'No tienes permisos para crear pagos',);
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $postdata = file_get_contents('php://input');
            $request = json_decode($postdata, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error en el formato de datos JSON');
            }

            $datosLimpios = $this->validarDatosPago($request);
            $infoDestinatario = $this->obtenerInformacionDestinatario(
                $datosLimpios
            );

            $arrData = [
                'idpersona' => $infoDestinatario['idpersona'] ?? null,
                'idtipo_pago' => $datosLimpios['idtipo_pago'],
                'idventa' => $datosLimpios['idventa'] ?? null,
                'idcompra' => $datosLimpios['idcompra'] ?? null,
                'idsueldotemp' => $datosLimpios['idsueldotemp'] ?? null,
                'monto' => $datosLimpios['monto'],
                'referencia' => $datosLimpios['referencia'] ?: null,
                'fecha_pago' => $datosLimpios['fecha_pago'],
                'observaciones' => $datosLimpios['observaciones'] ?: null,
            ];

            $resultado = $this->model->insertPago($arrData);

            if ($resultado['status'] === true) {
                $pagoId = $resultado['pago_id'] ?? null;
                $detalle = "Pago creado con ID: " . ($pagoId ?? 'desconocido');
                BitacoraHelper::registrarAccion('pagos','CREAR_PAGO',$idusuario,$this->bitacoraModel,$detalle,$pagoId);
            }

            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error en createPago: " . $e->getMessage());
            BitacoraHelper::registrarError('pagos',$e->getMessage(),$idusuario,$this->bitacoraModel);
            echo json_encode(['status' => false, 'message' => $e->getMessage()],JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function updatePago()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode(
                ['status' => false, 'message' => 'Método no permitido'],
                JSON_UNESCAPED_UNICODE
            );
            die();
        }

        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

        try {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('pagos', 'editar')) {
                BitacoraHelper::registrarError('pagos', 'Intento de editar pago sin permisos', $idusuario, $this->bitacoraModel);
                $arrResponse = array('status' => false, 'message' => 'No tienes permisos para editar pagos');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $postdata = file_get_contents('php://input');
            $request = json_decode($postdata, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error en el formato de datos JSON');
            }

            if (empty($request['idpago'])) {
                throw new Exception('ID de pago requerido');
            }

            $idpago = intval($request['idpago']);
            $pagoExistente = $this->model->selectPagoById($idpago);
            if (!$pagoExistente['status'] || !$pagoExistente['data']) {
                throw new Exception('El pago no existe');
            }
            if (strtolower($pagoExistente['data']['estatus']) !== 'activo') {
                throw new Exception(
                    'Solo se pueden editar pagos con estatus activo'
                );
            }

            $datosLimpios = $this->validarDatosPago($request);
            $infoDestinatario = $this->obtenerInformacionDestinatario(
                $datosLimpios
            );

            $arrData = [
                'idpersona' => $infoDestinatario['idpersona'] ?? null,
                'idtipo_pago' => $datosLimpios['idtipo_pago'],
                'idventa' => $datosLimpios['idventa'] ?? null,
                'idcompra' => $datosLimpios['idcompra'] ?? null,
                'idsueldotemp' => $datosLimpios['idsueldotemp'] ?? null,
                'monto' => $datosLimpios['monto'],
                'referencia' => $datosLimpios['referencia'] ?: null,
                'fecha_pago' => $datosLimpios['fecha_pago'],
                'observaciones' => $datosLimpios['observaciones'] ?: null,
            ];

            $resultado = $this->model->updatePago($idpago, $arrData);

            if ($resultado['status'] === true) {
                $detalle = "Pago actualizado con ID: " . $idpago;
                BitacoraHelper::registrarAccion('pagos', 'ACTUALIZAR_PAGO', $idusuario, $this->bitacoraModel, $detalle, $idpago);
            }

            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error en updatePago: " . $e->getMessage());
            BitacoraHelper::registrarError(
                'pagos',
                $e->getMessage(),
                $idusuario,
                $this->bitacoraModel
            );
            echo json_encode(
                ['status' => false, 'message' => $e->getMessage()],
                JSON_UNESCAPED_UNICODE
            );
        }
        die();
    }

    public function conciliarPago()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode(
                ['status' => false, 'message' => 'Método no permitido'],
                JSON_UNESCAPED_UNICODE
            );
            die();
        }

        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

        try {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('pagos', 'editar')) {
                BitacoraHelper::registrarError('pagos', 'Intento de conciliar pago sin permisos', $idusuario, $this->bitacoraModel);
                $arrResponse = array('status' => false, 'message' => 'No tienes permisos para conciliar pagos');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            // Manejar tanto datos JSON como form-urlencoded
            $request = [];
            
            // Intentar primero leer como JSON
            $postdata = file_get_contents('php://input');
            if (!empty($postdata)) {
                $jsonRequest = json_decode($postdata, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonRequest)) {
                    $request = $jsonRequest;
                } else {
                    // Si no es JSON válido, intentar parsear como form-urlencoded
                    parse_str($postdata, $request);
                }
            }
            
            // Si no hay datos en php://input, usar $_POST
            if (empty($request) && !empty($_POST)) {
                $request = $_POST;
            }

            if (empty($request) || !is_array($request)) {
                throw new Exception('No se recibieron datos válidos');
            }

            if (!isset($request['idpago']) || 
                ($request['idpago'] === '' || $request['idpago'] === null || $request['idpago'] === 0)) {
                throw new Exception('ID de pago requerido para la conciliación');
            }

            $idpago = intval($request['idpago']);
            if ($idpago <= 0) {
                throw new Exception('ID de pago inválido: debe ser un número mayor a 0');
            }

            if (!$idusuario) {
                throw new Exception('Usuario no autenticado');
            }

            // Verificar que el pago existe antes de conciliar
            $pagoExistente = $this->model->selectPagoById($idpago);
            if (!$pagoExistente['status'] || !$pagoExistente['data']) {
                throw new Exception('El pago no existe o no se pudo obtener la información');
            }

            // Verificar que el pago no esté ya conciliado
            if (isset($pagoExistente['data']['estatus']) && strtolower($pagoExistente['data']['estatus']) === 'conciliado') {
                throw new Exception('El pago ya está conciliado');
            }

            $resultado = $this->model->conciliarPago($idpago);

            if ($resultado['status'] === true) {
                $detalle = "Pago conciliado con ID: " . $idpago;
                BitacoraHelper::registrarAccion('pagos', 'CONCILIAR_PAGO', $idusuario, $this->bitacoraModel, $detalle, $idpago);
            }

            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error en conciliarPago: " . $e->getMessage());
            BitacoraHelper::registrarError('pagos', $e->getMessage(), $idusuario, $this->bitacoraModel);
            echo json_encode(['status' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function deletePago()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
            try {
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('pagos', 'eliminar')) {
                    BitacoraHelper::registrarError('pagos', 'Intento de eliminar pago sin permisos', $idusuario, $this->bitacoraModel);
                    $arrResponse = array(
                        'status' => false,
                        'message' => 'No tienes permisos para eliminar pagos',
                    );
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $postdata = file_get_contents('php://input');
                $request = json_decode($postdata, true);

                if (
                    json_last_error() !== JSON_ERROR_NONE ||
                    empty($request['idpago'])
                ) {
                    throw new Exception('Datos inválidos');
                }

                $idpago = intval($request['idpago']);
                $pagoExistente = $this->model->selectPagoById($idpago);
                if (!$pagoExistente['status'] || !$pagoExistente['data']) {
                    throw new Exception('El pago no existe');
                }
                if (
                    strtolower($pagoExistente['data']['estatus']) !== 'activo'
                ) {
                    throw new Exception(
                        'Solo se pueden eliminar pagos con estatus activo'
                    );
                }

                $resultado = $this->model->deletePagoById($idpago);

                if ($resultado['status'] === true) {
                    $detalle = "Pago eliminado con ID: " . $idpago;
                    BitacoraHelper::registrarAccion('pagos', 'ELIMINAR_PAGO', $idusuario, $this->bitacoraModel, $detalle, $idpago);
                }

                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en deletePago: " . $e->getMessage());
                BitacoraHelper::registrarError('pagos', $e->getMessage(), $idusuario, $this->bitacoraModel);
                echo json_encode(
                    ['status' => false, 'message' => $e->getMessage()],
                    JSON_UNESCAPED_UNICODE
                );
            }
            die();
        }
    }

    public function getPagosData()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('pagos', 'ver')) {
                    echo json_encode(['status' => false, 'message' => 'No tiene permiso para ver los datos'], JSON_UNESCAPED_UNICODE);
                    die();
                }
                $resultado = $this->model->selectAllPagos();
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getPagosData: " . $e->getMessage());
                echo json_encode(['status' => false, 'message' => 'Error al obtener datos de pagos'], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getPagoById($idpago)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('pagos', 'ver')) {
                    echo json_encode(['status' => false, 'message' => 'No tiene permiso para ver el dato'], JSON_UNESCAPED_UNICODE);
                    die();
                }
                if (empty($idpago) || !is_numeric($idpago)) {
                    throw new Exception('ID de pago inválido');
                }
                $resultado = $this->model->selectPagoById(intval($idpago));
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getPagoById: " . $e->getMessage());
                echo json_encode(['status' => false, 'message' => 'Error al obtener datos del pago'], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getTiposPago()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $resultado = $this->model->selectTiposPago();
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getTiposPago: " . $e->getMessage());
                echo json_encode(['status' => false, 'message' => 'Error al obtener tipos de pago'], JSON_UNESCAPED_UNICODE);
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
                error_log(
                    "Error en getComprasPendientes: " . $e->getMessage()
                );
                echo json_encode(['status' => false, 'message' => 'Error al obtener compras pendientes'], JSON_UNESCAPED_UNICODE);
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
                echo json_encode(['status' => false, 'message' => 'Error al obtener ventas pendientes'], JSON_UNESCAPED_UNICODE);
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
                error_log(
                    "Error en getSueldosPendientes: " . $e->getMessage()
                );
                echo json_encode(['status' => false, 'message' => 'Error al obtener sueldos pendientes'], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    private function validarDatosPago($request)
    {
        $datosLimpios = [
            'tipo_pago' => trim($request['tipo_pago'] ?? ''),
            'monto' => floatval($request['monto'] ?? 0),
            'idtipo_pago' => intval($request['idtipo_pago'] ?? 0),
            'fecha_pago' => trim($request['fecha_pago'] ?? ''),
            'referencia' => trim($request['referencia'] ?? ''),
            'observaciones' => trim($request['observaciones'] ?? ''),
        ];

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
                $datosLimpios['idsueldotemp'] = intval(
                    $request['idsueldotemp'] ?? 0
                );
                if (empty($datosLimpios['idsueldotemp'])) {
                    throw new Exception('Debe seleccionar un sueldo');
                }
                break;
            case 'otro':
                $datosLimpios['descripcion'] = trim(
                    $request['descripcion'] ?? ''
                );
                if (empty($datosLimpios['descripcion'])) {
                    throw new Exception(
                        'La descripción es obligatoria para otros pagos'
                    );
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
            default:
                return ['idpersona' => null];
        }
        return ['idpersona' => null];
    }

    private function validarFecha($fecha)
    {
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }
}
?>