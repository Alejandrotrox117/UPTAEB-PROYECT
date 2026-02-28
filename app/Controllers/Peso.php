<?php

use App\Models\BitacoraModel;
use App\Models\PesoModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\PermisosModuloVerificar;

/**
 * Controlador Peso - Estilo Funcional
 */

function peso_verificarSesion()
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

function peso_index()
{
    $idusuario = peso_verificarSesion();
    if (!PermisosModuloVerificar::verificarAccesoModulo('compras')) {
        renderView('errors', "permisos");
        exit();
    }
    registrarAccesoModulo('peso', $idusuario);

    $model = new PesoModel();
    $resultadoPeso = $model->obtenerUltimoPeso();

    $data = [
        'page_tag' => 'Romana',
        'page_title' => 'Último Peso Registrado',
        'page_name' => 'peso',
        'page_functions_js' => 'functions_peso.js',
        'ultimo_peso' => $resultadoPeso['data'] ?? null,
        'ultimo_peso_status' => $resultadoPeso['status'] ?? false,
        'ultimo_peso_message' => $resultadoPeso['message'] ?? null,
    ];

    renderView("peso", "peso", $data);
}

function peso_getUltimoPeso()
{
    header('Content-Type: application/json');
    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'ver')) {
        echo json_encode(['status' => false, 'message' => 'No tiene permisos.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $filePath = 'C:\com_data\peso_mysql.json';
    if (!file_exists($filePath)) {
        echo json_encode(['status' => false, 'message' => 'Archivo no encontrado'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $data = json_decode(file_get_contents($filePath), true);
    if ($data === null) {
        echo json_encode(['status' => false, 'message' => 'Error al leer'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    echo json_encode([
        'status' => true,
        'data' => [
            'peso' => $data["peso_numerico"],
            'fecha' => $data["fecha_hora"],
            'estado' => $data["estado"] ?? 'ACTIVO',
            'variacion' => $data["variacion"] ?? '0',
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

function peso_guardarPesoRomana()
{
    header('Content-Type: application/json');
    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'crear')) {
        echo json_encode(['status' => false, 'message' => 'No permisos.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $filePath = 'C:\com_data\peso_mysql.json';
    $data = json_decode(file_get_contents($filePath), true);
    if ($data === null) {
        echo json_encode(['status' => false, 'message' => 'Error al leer'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $model = new PesoModel();
    $resultado = $model->guardarPesoRomana($data["peso_numerico"], $data["fecha_hora"]);

    if ($resultado['status'] && !$resultado['duplicado']) {
        registrarEnBitacora('Romana', 'GUARDAR_PESO', null, "Peso: {$data["peso_numerico"]}kg");
    }

    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    exit();
}
