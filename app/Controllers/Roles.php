<?php

use App\Models\RolesModel;
use App\Models\BitacoraModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\PermisosModuloVerificar;
use App\Helpers\Validation\ExpresionesRegulares;

// =============================================================================
// FUNCIONES AUXILIARES DEL CONTROLADOR
// =============================================================================

/**
 * Obtiene el modelo de roles
 */
function getRolesModel() {
    return new RolesModel();
}

/**
 * Renderiza una vista de roles
 */
function renderRolesView($view, $data = []) {
    renderView('roles', $view, $data);
}

// =============================================================================
// FUNCIONES PÚBLICAS DEL CONTROLADOR
// =============================================================================

/**
 * Página principal del módulo de roles
 */
function roles_index() {
    // Verificar acceso al módulo
    verificarAccesoModulo('roles');
    
    // Verificar permiso de ver
    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'ver')) {
        renderView('errors', 'permisos');
        exit();
    }

    // Registrar acceso al módulo
    $idusuario = obtenerUsuarioSesion();
    registrarAccesoModulo('Roles', $idusuario);

    $data['page_tag'] = "Roles";
    $data['page_title'] = "Gestión de Roles";
    $data['page_name'] = "roles";
    $data['page_content'] = "Gestión integral de roles del sistema";
    $data['page_functions_js'] = "functions_roles.js";
    
    renderRolesView("roles", $data);
}

/**
 * Obtener listado de roles
 */
function roles_getRolesData() {
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'ver')) {
            echo json_encode(['status' => false, 'message' => 'No tiene permisos para ver roles', 'data' => []]);
            exit();
        }

        try {
            $idusuario = obtenerUsuarioSesion();
            $model = getRolesModel();
            $arrData = $model->selectAllRoles($idusuario);
            
            $bitacoraModel = new BitacoraModel();
            BitacoraHelper::registrarAccion('Roles', 'CONSULTA_LISTADO', $idusuario, $bitacoraModel, 'Consulta de listado de roles');
            
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error en getRolesData: " . $e->getMessage());
            echo json_encode(['status' => false, 'message' => 'Error al obtener los roles', 'data' => []]);
        }
    }
    die();
}

/**
 * Crear un nuevo rol
 */
function roles_createRol() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'crear')) {
            echo json_encode(['status' => false, 'message' => 'No tiene permisos para crear roles']);
            exit();
        }

        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            // Validar JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo json_encode(['status' => false, 'message' => 'Datos JSON inválidos']);
                die();
            }

            // Sanitizar datos con ExpresionesRegulares
            $datosLimpios = [
                'nombre' => ExpresionesRegulares::limpiar($data['nombre'] ?? '', 'rol'),
                'descripcion' => ExpresionesRegulares::limpiar($data['descripcion'] ?? '', 'descripcion'),
                'estatus' => strtolower(trim($data['estatus'] ?? 'activo'))
            ];

            // Validar nombre obligatorio
            if (empty($datosLimpios['nombre'])) {
                echo json_encode(['status' => false, 'message' => 'El nombre del rol es obligatorio']);
                die();
            }

            // Validar longitud del nombre
            if (strlen($datosLimpios['nombre']) < 3 || strlen($datosLimpios['nombre']) > 50) {
                echo json_encode(['status' => false, 'message' => 'El nombre del rol debe tener entre 3 y 50 caracteres']);
                die();
            }

            // Validar longitud de descripción
            if (strlen($datosLimpios['descripcion']) > 255) {
                echo json_encode(['status' => false, 'message' => 'La descripción no puede exceder 255 caracteres']);
                die();
            }

            // Validar estatus
            if (!in_array($datosLimpios['estatus'], ['activo', 'inactivo'])) {
                echo json_encode(['status' => false, 'message' => 'El estatus debe ser activo o inactivo']);
                die();
            }

            $rolData = [
                'nombre' => $datosLimpios['nombre'],
                'descripcion' => $datosLimpios['descripcion'],
                'estatus' => $datosLimpios['estatus']
            ];

            $model = getRolesModel();
            $request = $model->insertRol($rolData);
            $idusuario = obtenerUsuarioSesion();
            $bitacoraModel = new BitacoraModel();

            if ($request['status']) {
                BitacoraHelper::registrarAccion('Roles', 'CREAR_ROL', $idusuario, $bitacoraModel, 
                    "Rol creado: {$rolData['nombre']}", $request['rol_id'] ?? null);
            } else {
                BitacoraHelper::registrarAccion('Roles', 'CREAR_ROL_ERROR', $idusuario, $bitacoraModel, 
                    "Error al crear rol: {$rolData['nombre']} - {$request['message']}");
            }

            echo json_encode($request, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Error en createRol: " . $e->getMessage());
            $idusuario = obtenerUsuarioSesion();
            $bitacoraModel = new BitacoraModel();
            BitacoraHelper::registrarError('Roles', "Error en createRol: " . $e->getMessage(), $idusuario, $bitacoraModel);
            echo json_encode(['status' => false, 'message' => 'Error interno del servidor']);
        }
    }
    die();
}

/**
 * Obtener un rol por ID
 */
function roles_getRolById($idrol) {
    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'ver')) {
        echo json_encode(['status' => false, 'message' => 'No tiene permisos para ver detalles de roles']);
        exit();
    }

    $idrol = intval($idrol);
    
    if ($idrol > 0) {
        try {
            $model = getRolesModel();
            $arrData = $model->selectRolById($idrol);
            $response = (empty($arrData))
                ? ["status" => false, "message" => "Rol no encontrado."]
                : ["status" => true, "data" => $arrData];
            
            if ($response['status']) {
                $idusuario = obtenerUsuarioSesion();
                $bitacoraModel = new BitacoraModel();
                BitacoraHelper::registrarAccion('Roles', 'CONSULTA_DETALLE', $idusuario, $bitacoraModel, 
                    "Consulta detalle rol ID: {$idrol}", $idrol);
            }
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Error en getRolById: " . $e->getMessage());
            $idusuario = obtenerUsuarioSesion();
            $bitacoraModel = new BitacoraModel();
            BitacoraHelper::registrarError('Roles', "Error en getRolById ID {$idrol}: " . $e->getMessage(), $idusuario, $bitacoraModel);
            echo json_encode(['status' => false, 'message' => 'Error al obtener el rol']);
        }
    } else {
        echo json_encode(['status' => false, 'message' => 'ID de rol no válido']);
    }
    die();
}

/**
 * Actualizar un rol
 */
function roles_updateRol() {
    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'editar')) {
        echo json_encode(['status' => false, 'message' => 'No tiene permisos para editar roles']);
        exit();
    }

    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validar JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['status' => false, 'message' => 'Datos JSON inválidos']);
            die();
        }

        $idRol = intval($input['idrol'] ?? 0);

        // Validar ID de rol
        if ($idRol <= 0) {
            echo json_encode(['status' => false, 'message' => 'ID de rol inválido']);
            die();
        }

        // Sanitizar datos con ExpresionesRegulares
        $datosLimpios = [
            'nombre' => ExpresionesRegulares::limpiar($input['nombre'] ?? '', 'rol'),
            'descripcion' => ExpresionesRegulares::limpiar($input['descripcion'] ?? '', 'descripcion'),
            'estatus' => strtoupper(trim($input['estatus'] ?? 'ACTIVO'))
        ];

        // Validar nombre obligatorio
        if (empty($datosLimpios['nombre'])) {
            echo json_encode(['status' => false, 'message' => 'El nombre del rol es obligatorio']);
            die();
        }

        // Validar longitud del nombre
        if (strlen($datosLimpios['nombre']) < 3 || strlen($datosLimpios['nombre']) > 50) {
            echo json_encode(['status' => false, 'message' => 'El nombre del rol debe tener entre 3 y 50 caracteres']);
            die();
        }

        // Validar longitud de descripción
        if (strlen($datosLimpios['descripcion']) > 255) {
            echo json_encode(['status' => false, 'message' => 'La descripción no puede exceder 255 caracteres']);
            die();
        }

        // Validar estatus
        if (!in_array($datosLimpios['estatus'], ['ACTIVO', 'INACTIVO'])) {
            echo json_encode(['status' => false, 'message' => 'El estatus debe ser ACTIVO o INACTIVO']);
            die();
        }

        $model = getRolesModel();
        $rolAnterior = $model->selectRolById($idRol);
        $dataParaModelo = [
            'nombre' => $datosLimpios['nombre'],
            'descripcion' => $datosLimpios['descripcion'],
            'estatus' => $datosLimpios['estatus'],
        ];

        $resultado = $model->updateRol($idRol, $dataParaModelo);
        $idusuario = obtenerUsuarioSesion();
        $bitacoraModel = new BitacoraModel();

        if ($resultado['status']) {
            $cambios = [];
            if ($rolAnterior && is_array($rolAnterior)) {
                foreach ($dataParaModelo as $campo => $valor) {
                    if (isset($rolAnterior[$campo]) && $rolAnterior[$campo] != $valor) {
                        $cambios[] = "{$campo}: '{$rolAnterior[$campo]}' -> '{$valor}'";
                    }
                }
            }
            $detalleCambios = !empty($cambios) ? implode(', ', $cambios) : 'Actualización sin cambios de datos';
            BitacoraHelper::registrarAccion('Roles', 'EDITAR_ROL', $idusuario, $bitacoraModel, 
                "Rol editado ID: {$idRol} - {$detalleCambios}", $idRol);
        } else {
            BitacoraHelper::registrarAccion('Roles', 'EDITAR_ROL_ERROR', $idusuario, $bitacoraModel, 
                "Error al editar rol ID: {$idRol} - {$resultado['message']}", $idRol);
        }
        
        echo json_encode($resultado);

    } catch (Exception $e) {
        error_log("Error en updateRol: " . $e->getMessage());
        $idusuario = obtenerUsuarioSesion();
        $bitacoraModel = new BitacoraModel();
        BitacoraHelper::registrarError('Roles', "Error en updateRol: " . $e->getMessage(), $idusuario, $bitacoraModel);
        echo json_encode(['status' => false, 'message' => 'Error interno del servidor']);
    }
    die();
}

/**
 * Eliminar (desactivar) un rol
 */
function roles_deleteRol() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'eliminar')) {
            echo json_encode(['status' => false, 'message' => 'No tiene permisos para eliminar roles']);
            exit();
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $idrol = intval($data['idrol'] ?? 0);

            if ($idrol <= 0) {
                echo json_encode(['status' => false, 'message' => 'ID de rol no válido']);
                die();
            }

            $model = getRolesModel();
            $rolAnterior = $model->selectRolById($idrol);
            $requestDelete = $model->deleteRolById($idrol);
            $idusuario = obtenerUsuarioSesion();
            $bitacoraModel = new BitacoraModel();

            if ($requestDelete['status']) {
                $nombreRol = $rolAnterior['nombre'] ?? "ID: {$idrol}";
                BitacoraHelper::registrarAccion('Roles', 'ELIMINAR_ROL', $idusuario, $bitacoraModel, 
                    "Rol desactivado: {$nombreRol}", $idrol);
            } else {
                BitacoraHelper::registrarAccion('Roles', 'ELIMINAR_ROL_ERROR', $idusuario, $bitacoraModel, 
                    "Error al desactivar rol ID: {$idrol} - {$requestDelete['message']}", $idrol);
            }

            echo json_encode($requestDelete, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Error en deleteRol: " . $e->getMessage());
            $idusuario = obtenerUsuarioSesion();
            $bitacoraModel = new BitacoraModel();
            BitacoraHelper::registrarError('Roles', "Error en deleteRol: " . $e->getMessage(), $idusuario, $bitacoraModel);
            echo json_encode(['status' => false, 'message' => 'Error interno del servidor']);
        }
    }
    die();
}

/**
 * Reactivar un rol
 */
function roles_reactivarRol() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            $idusuario = obtenerUsuarioSesion();
            $model = getRolesModel();
            
            $esSuperUsuario = $model->verificarEsSuperUsuario($idusuario);
            if (!$esSuperUsuario) {
                echo json_encode(['status' => false, 'message' => 'Acción no permitida.']);
                exit();
            }
            
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'editar')) {
                echo json_encode(['status' => false, 'message' => 'No tiene permisos para editar roles.']);
                exit();
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $idrol = intval($data['idrol'] ?? 0);

            if ($idrol <= 0) {
                echo json_encode(['status' => false, 'message' => 'ID de rol no válido']);
                die();
            }

            $rolAnterior = $model->selectRolById($idrol);
            $requestReactivar = $model->reactivarRol($idrol);
            $bitacoraModel = new BitacoraModel();

            if ($requestReactivar['status']) {
                $nombreRol = $rolAnterior['nombre'] ?? "ID: {$idrol}";
                BitacoraHelper::registrarAccion('Roles', 'REACTIVAR_ROL', $idusuario, $bitacoraModel, 
                    "Rol reactivado: {$nombreRol}", $idrol);
            } else {
                BitacoraHelper::registrarAccion('Roles', 'REACTIVAR_ROL_ERROR', $idusuario, $bitacoraModel, 
                    "Error al reactivar rol ID: {$idrol} - {$requestReactivar['message']}", $idrol);
            }

            echo json_encode($requestReactivar, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Error en reactivarRol: " . $e->getMessage());
            $idusuario = obtenerUsuarioSesion();
            $bitacoraModel = new BitacoraModel();
            BitacoraHelper::registrarError('Roles', "Error en reactivarRol: " . $e->getMessage(), $idusuario, $bitacoraModel);
            echo json_encode(['status' => false, 'message' => 'Error interno del servidor']);
        }
    }
    die();
}

?>