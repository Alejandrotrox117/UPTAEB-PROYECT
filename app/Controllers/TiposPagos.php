<?php

use App\Models\TiposPagosModel;
use App\Models\BitacoraModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\Validation\ExpresionesRegulares;

/**
 * Controlador TiposPagos - Estilo Funcional
 */

/**
 * Función de fábrica para obtener el modelo TiposPagosModel
 */
function getTiposPagosModel()
{
    return new TiposPagosModel();
}

function tipospagos_verificarSesion()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $idusuario = obtenerUsuarioSesion();
    if (!$idusuario) {
        header('Location: ' . base_url() . '/login');
        die();
    }
    return $idusuario;
}

function tipospagos_index()
{
    $idusuario = tipospagos_verificarSesion();
    registrarAccesoModulo('tipos_pagos', $idusuario);

    $data['page_tag'] = "Tipos de Pagos";
    $data['page_title'] = "Administración de Tipos de Pagos";
    $data['page_name'] = "tipos_pagos";
    $data['page_content'] = "Gestión integral de tipos de pagos del sistema";
    $data['page_functions_js'] = "functions_tipos_pagos.js";
    renderView("tipos_pagos", "tipos_pagos", $data);
}

function tipospagos_getTiposPagosData()
{
    tipospagos_verificarSesion();
    try {
        $objTiposPagos = getTiposPagosModel();
        $arrResponse = $objTiposPagos->selectAllTiposPagos();
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => 'Error interno', 'data' => []], JSON_UNESCAPED_UNICODE);
    }
    die();
}

function tipospagos_createTipoPago()
{
    $idusuario = tipospagos_verificarSesion();
    try {
        $request = json_decode(file_get_contents('php://input'), true);
        $nombre = trim($request['nombre'] ?? '');
        if (empty($nombre)) {
            echo json_encode(['status' => false, 'message' => 'Nombre obligatorio']);
            die();
        }

        $objTiposPagos = getTiposPagosModel();
        $arrResponse = $objTiposPagos->insertTipoPago(['nombre' => $nombre]);

        if ($arrResponse['status']) {
            registrarEnBitacora('tipos_pagos', 'INSERTAR');
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => 'Error interno']);
    }
    die();
}

function tipospagos_getTipoPagoById($idtipo_pago)
{
    tipospagos_verificarSesion();
    try {
        $objTiposPagos = getTiposPagosModel();
        $arrData = $objTiposPagos->selectTipoPagoById(intval($idtipo_pago));
        echo json_encode($arrData ? ['status' => true, 'data' => $arrData] : ['status' => false, 'message' => 'No encontrado']);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => 'Error interno']);
    }
    die();
}

function tipospagos_updateTipoPago()
{
    $idusuario = tipospagos_verificarSesion();
    try {
        $request = json_decode(file_get_contents('php://input'), true);
        $id = intval($request['idtipo_pago'] ?? 0);
        $nombre = trim($request['nombre'] ?? '');

        $objTiposPagos = getTiposPagosModel();
        $arrResponse = $objTiposPagos->updateTipoPago($id, ['nombre' => $nombre]);

        if ($arrResponse['status']) {
            registrarEnBitacora('tipos_pagos', 'ACTUALIZAR');
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => 'Error interno']);
    }
    die();
}

function tipospagos_deleteTipoPago()
{
    $idusuario = tipospagos_verificarSesion();
    try {
        $request = json_decode(file_get_contents('php://input'), true);
        $id = intval($request['idtipo_pago'] ?? 0);

        $objTiposPagos = getTiposPagosModel();
        $res = $objTiposPagos->deleteTipoPagoById($id);

        if ($res) {
            registrarEnBitacora('tipos_pagos', 'ELIMINAR');
            echo json_encode(['status' => true, 'message' => 'Desactivado']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Error']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => 'Error interno']);
    }
    die();
}

function tipospagos_getTiposPagosActivos()
{
    tipospagos_verificarSesion();
    try {
        $objTiposPagos = getTiposPagosModel();
        $arrResponse = $objTiposPagos->selectTiposPagosActivos();
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => 'Error interno', 'data' => []]);
    }
    die();
}