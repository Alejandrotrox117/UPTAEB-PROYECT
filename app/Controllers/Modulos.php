<?php

use App\Models\ModulosModel;
use App\Helpers\PermisosModuloVerificar;

/**
 * Obtener instancia del modelo de Módulos
 */
function getModulosModel(): ModulosModel
{
    static $model = null;
    if ($model === null) {
        $model = new ModulosModel();
    }
    return $model;
}

/**
 * Obtener ID de usuario de la sesión
 */
function obtenerUsuarioSesionModulos(): int
{
    return intval($_SESSION['usuario_id'] ?? 0);
}

/**
 * Renderiza una vista de módulos
 */
function renderModulosView($view, $data = []) {
    renderView('modulos', $view, $data);
}

/**
 * Vista principal de módulos
 */
function modulos_index()
{
    // Verificar acceso al módulo
    verificarAccesoModulo('modulos');
    
    // Verificar permiso de ver
    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('modulos', 'ver')) {
        renderView('errors', 'permisos');
        exit();
    }

    // Registrar acceso al módulo
    $idusuario = obtenerUsuarioSesionModulos();
    registrarAccesoModulo('Modulos', $idusuario);

    $data['page_tag'] = "Módulos";
    $data['page_title'] = "Gestión de Módulos";
    $data['page_name'] = "modulos";
    $data['page_content'] = "Gestión integral de módulos del sistema";
    $data['page_functions_js'] = "functions_modulos.js";
    
    renderModulosView("modulos", $data);
}

/**
 * Obtener todos los módulos activos (DataTables)
 */
function modulos_getModulosData()
{
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $model = getModulosModel();
        $arrData = $model->selectAllModulosActivos();
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Crear un nuevo módulo
 */
function modulos_createModulo()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Verificar permisos
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('modulos', 'crear')) {
            echo json_encode(['status' => false, 'message' => 'No tiene permisos para crear módulos.']);
            exit();
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$data) {
            echo json_encode(['status' => false, 'message' => 'Datos no válidos']);
            die();
        }

        $moduloData = [
            'titulo' => trim($data['titulo'] ?? ''),
            'descripcion' => trim($data['descripcion'] ?? '')
        ];

        if (empty($moduloData['titulo'])) {
            echo json_encode(['status' => false, 'message' => 'El título del módulo es obligatorio']);
            die();
        }

        $model = getModulosModel();
        $request = $model->insertModulo($moduloData);
        echo json_encode($request, JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Obtener módulo por ID
 */
function modulos_getModuloById(int $idmodulo)
{
    if ($idmodulo > 0) {
        $model = getModulosModel();
        $arrData = $model->selectModuloById($idmodulo);
        
        if (empty($arrData)) {
            $response = ["status" => false, "message" => "Datos no encontrados."];
        } else {
            $response = ["status" => true, "data" => $arrData];
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Actualizar un módulo existente
 */
function modulos_updateModulo()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            // Verificar permisos
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('modulos', 'editar')) {
                echo json_encode(['status' => false, 'message' => 'No tiene permisos para editar módulos.']);
                exit();
            }

            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                echo json_encode(['status' => false, 'message' => 'Datos no válidos']);
                die();
            }

            $idModulo = intval($input['idmodulo'] ?? 0);
            if ($idModulo <= 0) {
                echo json_encode(['status' => false, 'message' => 'ID de módulo no válido']);
                die();
            }

            $dataParaModelo = [
                'titulo' => trim($input['titulo'] ?? ''),
                'descripcion' => trim($input['descripcion'] ?? '')
            ];

            if (empty($dataParaModelo['titulo'])) {
                echo json_encode(['status' => false, 'message' => 'El título del módulo es obligatorio']);
                die();
            }

            $model = getModulosModel();
            $resultado = $model->updateModulo($idModulo, $dataParaModelo);
            
            echo json_encode($resultado);

        } catch (Exception $e) {
            error_log("Error en updateModulo: " . $e->getMessage());
            echo json_encode([
                'status' => false, 
                'message' => 'Error interno del servidor'
            ]);
        }
    }
    die();
}

/**
 * Desactivar un módulo
 */
function modulos_deleteModulo()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Verificar permisos
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('modulos', 'eliminar')) {
            echo json_encode(['status' => false, 'message' => 'No tiene permisos para eliminar módulos.']);
            exit();
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $idmodulo = isset($data['idmodulo']) ? intval($data['idmodulo']) : 0;

        if ($idmodulo > 0) {
            $model = getModulosModel();
            $requestDelete = $model->deleteModuloById($idmodulo);
            
            if ($requestDelete) {
                $response = ["status" => true, "message" => "Módulo desactivado correctamente."];
            } else {
                $response = ["status" => false, "message" => "Error al desactivar el módulo."];
            }
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } else {
            $response = ["status" => false, "message" => "ID de módulo no válido."];
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
    }
    die();
}

/**
 * Obtener lista de controladores disponibles
 */
function modulos_getControladores()
{
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $model = getModulosModel();
        $arrData = $model->getControlladoresDisponibles();
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
    }
    die();
}

?>
