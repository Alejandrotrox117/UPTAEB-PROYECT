<?php

use App\Models\MovimientosModel;
use App\Models\BitacoraModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\PermisosModuloVerificar;

/**
 * Controlador Movimientos - Estilo Funcional
 */


function getMovimientosModel()
{
    return new MovimientosModel();
}

function movimientos_verificarSesion()
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

function movimientos_index()
{
    $idusuario = movimientos_verificarSesion();
    if (!PermisosModuloVerificar::verificarAccesoModulo('movimientos')) {
        renderView('errors', "permisos");
        exit();
    }
    registrarAccesoModulo('movimientos', $idusuario);

    $data['page_tag'] = "Movimientos";
    $data['page_title'] = "Gestión de Movimientos";
    $data['page_name'] = "movimientos";
    $data['page_content'] = "Gestión integral de movimientos de inventario";
    $data['page_functions_js'] = "functions_movimientos.js";
    $data['permisos'] = PermisosModuloVerificar::getPermisosUsuarioModulo('movimientos');

    renderView("movimientos", "movimientos", $data);
}

function movimientos_getMovimientos()
{
    movimientos_verificarSesion();
    header('Content-Type: application/json');
    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('movimientos', 'ver')) {
        echo json_encode(["status" => false, "message" => "No tienes permisos."]);
        die();
    }

    try {
        $objMovimientos = getMovimientosModel();
        echo json_encode($objMovimientos->selectAllMovimientos(), JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error."]);
    }
    die();
}

function movimientos_getMovimientoById($id)
{
    movimientos_verificarSesion();
    header('Content-Type: application/json');
    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('movimientos', 'ver')) {
        echo json_encode(['status' => false, 'message' => 'No permisos.']);
        exit;
    }

    try {
        $objMovimientos = getMovimientosModel();
        echo json_encode($objMovimientos->selectMovimientoById(intval($id)), JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => 'Error.']);
    }
    exit;
}

function movimientos_getTiposMovimiento()
{
    movimientos_verificarSesion();
    header('Content-Type: application/json');
    try {
        $objMovimientos = getMovimientosModel();
        echo json_encode($objMovimientos->getTiposMovimientoActivos(), JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error."]);
    }
    die();
}

function movimientos_createMovimiento()
{
    $idusuario = movimientos_verificarSesion();
    header('Content-Type: application/json');
    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('movimientos', 'crear')) {
        echo json_encode(["status" => false, "message" => "No permisos."]);
        die();
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $objMovimientos = getMovimientosModel();
        $result = $objMovimientos->insertMovimiento($data);
        if ($result['status'])
            registrarEnBitacora('movimientos', 'CREAR_MOVIMIENTO');
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error: " . $e->getMessage()]);
    }
    die();
}

function movimientos_updateMovimiento()
{
    $idusuario = movimientos_verificarSesion();
    header('Content-Type: application/json');
    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('movimientos', 'editar')) {
        echo json_encode(["status" => false, "message" => "No permisos."]);
        die();
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $objMovimientos = getMovimientosModel();
        $result = $objMovimientos->anularYCorregirMovimiento(intval($data['idmovimiento']), $data['nuevos_datos']);
        if ($result['status'])
            registrarEnBitacora('movimientos', 'ANULAR_Y_CORREGIR_MOVIMIENTO');
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error."]);
    }
    die();
}

function movimientos_anularMovimiento()
{
    $idusuario = movimientos_verificarSesion();
    header('Content-Type: application/json');
    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('movimientos', 'eliminar')) {
        echo json_encode(["status" => false, "message" => "No permisos."]);
        die();
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $objMovimientos = getMovimientosModel();
        $result = $objMovimientos->anularMovimientoById(intval($data['idmovimiento']));
        if ($result['status'])
            registrarEnBitacora('movimientos', 'ANULAR_MOVIMIENTO');
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error."]);
    }
    die();
}

function movimientos_exportarMovimientos()
{
    $idusuario = movimientos_verificarSesion();
    header('Content-Type: application/json');
    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('movimientos', 'exportar')) {
        echo json_encode(["status" => false, "message" => "No permisos."]);
        die();
    }

    try {
        $objMovimientos = getMovimientosModel();
        $res = $objMovimientos->selectAllMovimientos();
        if ($res['status'])
            registrarEnBitacora('movimientos', 'EXPORTAR_MOVIMIENTOS');
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error."]);
    }
    die();
}

function movimientos_getDatosFormulario()
{
    movimientos_verificarSesion();
    header('Content-Type: application/json');
    try {
        $objMovimientos = getMovimientosModel();
        $productosResponse = $objMovimientos->getProductosActivos();
        $tiposResponse = $objMovimientos->getTiposMovimientoActivos();

        echo json_encode([
            "status" => true,
            "message" => "Datos obtenidos correctamente.",
            "data" => [
                'productos' => $productosResponse['data'] ?? [],
                'tipos_movimiento' => $tiposResponse['data'] ?? []
            ]
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error."]);
    }
    die();
}

function movimientos_buscarMovimientos()
{
    movimientos_verificarSesion();
    header('Content-Type: application/json');
    try {
        $objMovimientos = getMovimientosModel();
        echo json_encode($objMovimientos->buscarMovimientos($_GET['criterio'] ?? ''), JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error."]);
    }
    die();
}