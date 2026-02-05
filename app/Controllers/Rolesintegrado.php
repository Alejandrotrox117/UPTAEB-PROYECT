<?php

use App\Models\RolesintegradoModel;
use App\Helpers\PermisosModuloVerificar;

/**
 * Obtener instancia del modelo de Rolesintegrado
 */
function getRolesintegradoModel(): RolesintegradoModel
{
    static $model = null;
    if ($model === null) {
        $model = new RolesintegradoModel();
    }
    return $model;
}

/**
 * Renderiza una vista de rolesintegrado
 */
function renderRolesintegradoView($view, $data = [])
{
    renderView('rolesintegrado', $view, $data);
}

/**
 * Vista principal de gestión integral de permisos
 */
function rolesintegrado_index()
{
    // Verificar acceso al módulo
    verificarAccesoModulo('rolesintegrado');
    
    // Verificar permiso de ver
    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('rolesintegrado', 'ver')) {
        renderView('errors', 'permisos');
        exit();
    }

    // Registrar acceso al módulo
    $idusuario = obtenerUsuarioSesion();
    registrarAccesoModulo('RolesIntegrado', $idusuario);

    $data['page_tag'] = "Permisos";
    $data['page_title'] = "Gestión Integral de Permisos";
    $data['page_name'] = "rolesintegrado";
    $data['page_content'] = "Gestión completa de roles, módulos y permisos del sistema";
    $data['page_functions_js'] = "functions_rolesintegrado.js";
    
    renderRolesintegradoView("rolesintegrado", $data);
}

/**
 * Obtener todos los roles
 */
function rolesintegrado_getRoles()
{
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $model = getRolesintegradoModel();
        $arrData = $model->selectAllRoles();
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Obtener módulos disponibles
 */
function rolesintegrado_getModulosDisponibles()
{
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $model = getRolesintegradoModel();
        $arrData = $model->selectAllModulosActivos();
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Obtener permisos disponibles
 */
function rolesintegrado_getPermisosDisponibles()
{
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $model = getRolesintegradoModel();
        $arrData = $model->selectAllPermisosActivos();
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Obtener asignaciones completas de un rol
 */
function rolesintegrado_getAsignacionesRol(int $idrol)
{
    if ($idrol > 0) {
        $model = getRolesintegradoModel();
        $arrData = $model->selectAsignacionesRolCompletas($idrol);
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['status' => false, 'message' => 'ID de rol no válido']);
    }
    die();
}

/**
 * Guardar asignaciones completas de un rol
 */
function rolesintegrado_guardarAsignacionesCompletas()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Verificar permisos
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('rolesintegrado', 'editar')) {
            echo json_encode(['status' => false, 'message' => 'No tiene permisos para editar asignaciones.']);
            exit();
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$data) {
            echo json_encode(['status' => false, 'message' => 'Datos no válidos']);
            die();
        }

        $model = getRolesintegradoModel();
        $request = $model->guardarAsignacionesRolCompletas($data);
        echo json_encode($request, JSON_UNESCAPED_UNICODE);
    }
    die();
}

?>