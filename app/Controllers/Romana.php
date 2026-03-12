<?php

use App\Models\RomanaModel;
use App\Helpers\PermisosModuloVerificar;

/**
 * Controlador Romana - Estilo Funcional
 */

/**
 * Función de fábrica para obtener la instancia del modelo RomanaModel
 */
function getRomanaModel()
{
    return new RomanaModel();
}

function romana_index()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!obtenerUsuarioSesion()) {
        header('Location: ' . base_url() . '/login');
        die();
    }
    if (!PermisosModuloVerificar::verificarAccesoModulo('romana')) {
        renderView('errors', "permisos");
        exit();
    }
    registrarAccesoModulo('romana', obtenerUsuarioSesion());

    $data['page_title'] = "Gestión de Romanas";
    $data['page_name'] = "Romana";
    $data['page_functions_js'] = "functions_romana.js";

    $data['permisos'] = [
        'puede_ver' => PermisosModuloVerificar::verificarPermisoModuloAccion('romana', 'ver'),
        'puede_crear' => PermisosModuloVerificar::verificarPermisoModuloAccion('romana', 'crear'),
        'puede_editar' => PermisosModuloVerificar::verificarPermisoModuloAccion('romana', 'editar'),
        'puede_eliminar' => PermisosModuloVerificar::verificarPermisoModuloAccion('romana', 'eliminar')
    ];

    renderView("romana", "romana", $data);
}

function romana_getRomanaData()
{
    $objRomana = getRomanaModel();
    $arrData = $objRomana->selectAllRomana();
    $data = isset($arrData['data']) ? $arrData['data'] : $arrData;

    echo json_encode([
        "recordsTotal" => count($data),
        "recordsFiltered" => count($data),
        "data" => $data
    ], JSON_UNESCAPED_UNICODE);
    exit();
}