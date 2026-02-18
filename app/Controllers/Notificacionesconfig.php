<?php

require_once "helpers/helpers.php";

use App\Models\NotificacionesConfigModel;
use App\Helpers\PermisosModuloVerificar;

$model = new NotificacionesConfigModel();

function notificacionesconfig_index() {
    verificarAccesoModulo('notificacionesconfig');
    
    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('notificacionesconfig', 'ver')) {
        renderView('errors', 'permisos');
        exit();
    }

    $idusuario = obtenerUsuarioSesion();
    registrarAccesoModulo('NotificacionesConfig', $idusuario);

    $data['page_tag'] = "Configuración Notificaciones";
    $data['page_title'] = "Notificaciones por Rol";
    $data['page_name'] = "notificacionesconfig";
    $data['page_content'] = "Configura qué notificaciones recibe cada rol";
    $data['page_functions_js'] = "functions_notificacionesconfig.js?v=" . time();
    
    renderView('NotificacionesConfig', 'notificacionesConfig', $data);
}

function notificacionesconfig_obtenerRoles() {
    global $model;
    
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $roles = $model->obtenerRoles();
        echo json_encode(['status' => true, 'data' => $roles], JSON_UNESCAPED_UNICODE);
        die();
    }
}

function notificacionesconfig_obtenerConfiguracion() {
    global $model;
    
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $rolId = filter_input(INPUT_GET, 'idrol', FILTER_VALIDATE_INT);
        
        if (!$rolId) {
            echo json_encode(['status' => false, 'message' => 'Rol inválido'], JSON_UNESCAPED_UNICODE);
            die();
        }
        
        $config = $model->obtenerConfiguracionRol($rolId);
        echo json_encode(['status' => true, 'data' => $config], JSON_UNESCAPED_UNICODE);
        die();
    }
}

function notificacionesconfig_guardar() {
    global $model;
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('notificacionesconfig', 'editar')) {
            echo json_encode(['status' => false, 'message' => 'Sin permisos para editar'], JSON_UNESCAPED_UNICODE);
            die();
        }
        
        $postdata = file_get_contents('php://input');
        $request = json_decode($postdata, true);
        
        $rolId = $request['idrol'] ?? null;
        $configuraciones = $request['configuraciones'] ?? [];
        
        if (!$rolId) {
            echo json_encode(['status' => false, 'message' => 'Rol inválido'], JSON_UNESCAPED_UNICODE);
            die();
        }
        
        $resultado = $model->guardarConfiguracion($rolId, $configuraciones);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        die();
    }
}
