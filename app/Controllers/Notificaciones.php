<?php

use App\Models\NotificacionesModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\PermisosModuloVerificar;

/**
 * Controlador Notificaciones - Estilo Funcional
 */

/**
 * Función de fábrica para obtener el modelo NotificacionesModel
 */
function getNotificacionesModel()
{
    return new NotificacionesModel();
}

function notificaciones_verificarAutenticacion()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $usuarioId = obtenerUsuarioSesion();

    if (!$usuarioId) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => false, 'message' => 'No autenticado']);
            die();
        }
        header('Location: ' . base_url() . '/login');
        die();
    }

    return $usuarioId;
}

function notificaciones_getNotificaciones()
{
    $usuarioId = notificaciones_verificarAutenticacion();
    header('Content-Type: application/json; charset=utf-8');

    try {
        $objNotificaciones = getNotificacionesModel();
        $rolId = $objNotificaciones->obtenerRolPorUsuario($usuarioId);

        if (!$rolId) {
            echo json_encode(['status' => false, 'message' => 'No se pudo obtener el rol'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $arrResponse = $objNotificaciones->obtenerNotificacionesPorUsuario($usuarioId, $rolId);

        if (!isset($arrResponse['status']))
            $arrResponse['status'] = true;
        if (!isset($arrResponse['data']))
            $arrResponse['data'] = [];

        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => 'Error interno'], JSON_UNESCAPED_UNICODE);
    }
    die();
}

function notificaciones_getContadorNotificaciones()
{
    $usuarioId = notificaciones_verificarAutenticacion();
    header('Content-Type: application/json; charset=utf-8');

    try {
        $objNotificaciones = getNotificacionesModel();
        $rolId = $objNotificaciones->obtenerRolPorUsuario($usuarioId);
        if (!$rolId) {
            echo json_encode(['count' => 0], JSON_UNESCAPED_UNICODE);
            die();
        }

        $count = $objNotificaciones->contarNotificacionesNoLeidas($usuarioId, $rolId);
        echo json_encode(['count' => $count], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['count' => 0], JSON_UNESCAPED_UNICODE);
    }
    die();
}

function notificaciones_marcarLeida()
{
    $usuarioId = notificaciones_verificarAutenticacion();
    header('Content-Type: application/json; charset=utf-8');

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $notificacionId = intval($data['idnotificacion'] ?? 0);

        if ($notificacionId <= 0) {
            echo json_encode(['status' => false, 'message' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $objNotificaciones = getNotificacionesModel();
        $resultado = $objNotificaciones->marcarComoLeida($notificacionId, $usuarioId);
        echo json_encode(['status' => (bool) $resultado], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => 'Error interno'], JSON_UNESCAPED_UNICODE);
    }
    die();
}

function notificaciones_marcarComoLeida()
{
    notificaciones_marcarLeida();
}

function notificaciones_generarNotificacionesProductos()
{
    notificaciones_verificarAutenticacion();
    header('Content-Type: application/json; charset=utf-8');

    try {
        $objNotificaciones = getNotificacionesModel();
        $resultado = $objNotificaciones->generarNotificacionesProductos();
        echo json_encode(['status' => (bool) $resultado], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => 'Error interno'], JSON_UNESCAPED_UNICODE);
    }
    die();
}

function notificaciones_marcarTodasLeidas()
{
    $usuarioId = notificaciones_verificarAutenticacion();
    header('Content-Type: application/json; charset=utf-8');

    try {
        $objNotificaciones = getNotificacionesModel();
        $rolId = $objNotificaciones->obtenerRolPorUsuario($usuarioId);
        $resultado = $objNotificaciones->marcarTodasComoLeidasCompleto($usuarioId, $rolId);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => $e->getMessage()]);
    }
    die();
}

function notificaciones_eliminarNotificacion()
{
    $usuarioId = notificaciones_verificarAutenticacion();
    header('Content-Type: application/json; charset=utf-8');

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $objNotificaciones = getNotificacionesModel();
        $rolId = $objNotificaciones->obtenerRolPorUsuario($usuarioId);
        $resultado = $objNotificaciones->eliminarNotificacion($data['idnotificacion'], $usuarioId, $rolId);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => $e->getMessage()]);
    }
    die();
}