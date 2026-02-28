<?php

use App\Models\BitacoraModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\PermisosModuloVerificar;

/**
 * Controlador Bitacora - Estilo Funcional
 */

function bitacora_verificarAcceso($accion = 'ver')
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!obtenerUsuarioSesion()) {
        header('Location: ' . base_url() . '/login');
        die();
    }

    if ($accion === 'acceso_modulo') {
        if (!PermisosModuloVerificar::verificarAccesoModulo('bitacora')) {
            renderView('errors', "permisos");
            exit();
        }
    } else {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', $accion)) {
            renderView('errors', "permisos");
            exit();
        }
    }
}

function bitacora_index()
{
    bitacora_verificarAcceso('acceso_modulo');

    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'ver')) {
        renderView('errors', "permisos");
        exit();
    }

    $idusuario = obtenerUsuarioSesion();

    $data['page_title'] = "Bitácora del Sistema";
    $data['page_name'] = "Bitácora";
    $data['page_functions_js'] = "functions_bitacora.js";

    $data['permisos'] = [
        'puede_ver' => PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'ver'),
        'puede_exportar' => PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'ver'),
        'puede_filtrar' => PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'ver')
    ];

    renderView("bitacora", "bitacora", $data);
}

function bitacora_getBitacoraData()
{
    header('Content-Type: application/json');

    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'ver')) {
        echo json_encode(["status" => false, "message" => "No tienes permisos para ver la bitácora"]);
        exit();
    }

    try {
        $model = new BitacoraModel();
        $filtros = [];
        if (!empty($_POST['modulo']))
            $filtros['tabla'] = $_POST['modulo'];
        if (!empty($_POST['fecha_desde']))
            $filtros['fecha_desde'] = $_POST['fecha_desde'];
        if (!empty($_POST['fecha_hasta']))
            $filtros['fecha_hasta'] = $_POST['fecha_hasta'];
        if (!empty($_POST['usuario']))
            $filtros['idusuario'] = $_POST['usuario'];

        $arrData = $model->obtenerHistorial($filtros);

        $data = [];
        foreach ($arrData as $row) {
            $data[] = [
                'idbitacora' => $row['idbitacora'],
                'tabla' => strtoupper($row['tabla']),
                'accion' => bitacora_formatearAccion($row['accion']),
                'usuario' => $row['nombre_usuario'] ?? 'Usuario desconocido',
                'fecha' => bitacora_formatearFecha($row['fecha']),
                'acciones' => bitacora_generarBotonesAccion($row['idbitacora'])
            ];
        }

        echo json_encode([
            "draw" => intval($_POST['draw'] ?? 1),
            "recordsTotal" => count($data),
            "recordsFiltered" => count($data),
            "data" => $data
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        error_log("Error en bitacora_getBitacoraData: " . $e->getMessage());
        echo json_encode(["status" => false, "message" => "Error al obtener datos de bitácora"]);
    }
    exit();
}

function bitacora_getBitacoraById($idbitacora)
{
    header('Content-Type: application/json');

    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'ver')) {
        echo json_encode(["status" => false, "message" => "No tienes permisos para ver detalles de bitácora"]);
        exit();
    }

    try {
        $model = new BitacoraModel();
        $idbitacora = intval($idbitacora);
        $bitacora = $model->obtenerRegistroPorId($idbitacora);

        if ($bitacora) {
            echo json_encode([
                "status" => true,
                "data" => [
                    'id' => $bitacora['idbitacora'],
                    'modulo' => strtoupper($bitacora['tabla']),
                    'accion' => $bitacora['accion'],
                    'usuario' => $bitacora['nombre_usuario'] ?? 'Usuario desconocido',
                    'fecha' => bitacora_formatearFecha($bitacora['fecha']),
                    'fecha_raw' => $bitacora['fecha']
                ]
            ]);
        } else {
            echo json_encode(["status" => false, "message" => "Registro de bitácora no encontrado"]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error interno del servidor"]);
    }
    exit();
}

function bitacora_getModulosDisponibles()
{
    header('Content-Type: application/json');

    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'ver')) {
        echo json_encode(["status" => false, "message" => "No tienes permisos"]);
        exit();
    }

    try {
        $model = new BitacoraModel();
        $modulos = $model->obtenerModulosDisponibles();
        echo json_encode(["status" => true, "data" => $modulos]);
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error al obtener módulos"]);
    }
    exit();
}

function bitacora_limpiarBitacora()
{
    header('Content-Type: application/json');

    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'eliminar')) {
        echo json_encode(["status" => false, "message" => "No tienes permisos para limpiar la bitácora"]);
        exit();
    }

    try {
        $model = new BitacoraModel();
        $dias = intval($_POST['dias']);
        $registrosEliminados = $model->limpiarRegistrosAntiguos($dias);

        registrarEnBitacora('Bitacora', 'LIMPIEZA', null, "Eliminados {$registrosEliminados} registros anteriores a {$dias} días");

        echo json_encode([
            "status" => true,
            "message" => "Se eliminaron {$registrosEliminados} registros antiguos",
            "registros_eliminados" => $registrosEliminados
        ]);
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error al limpiar la bitácora"]);
    }
    exit();
}

// Helpers locales

function bitacora_formatearAccion($accion)
{
    $acciones = [
        'ACCESO_MODULO' => '<span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">ACCESO</span>',
        'INSERTAR' => '<span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">CREAR</span>',
        'ACTUALIZAR' => '<span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">ACTUALIZAR</span>',
        'ELIMINAR' => '<span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">ELIMINAR</span>',
        'VER' => '<span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">VER</span>',
        'LOGIN' => '<span class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded-full">LOGIN</span>',
        'LOGOUT' => '<span class="px-2 py-1 text-xs font-medium bg-indigo-100 text-indigo-800 rounded-full">LOGOUT</span>',
        'ERROR' => '<span class="px-2 py-1 text-xs font-medium bg-red-200 text-red-900 rounded-full">ERROR</span>',
        'LIMPIEZA' => '<span class="px-2 py-1 text-xs font-medium bg-orange-100 text-orange-800 rounded-full">LIMPIEZA</span>'
    ];

    return $acciones[$accion] ?? '<span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">' . $accion . '</span>';
}

function bitacora_formatearFecha($fecha)
{
    try {
        return date('d/m/Y H:i:s', strtotime($fecha));
    } catch (Exception $e) {
        return $fecha;
    }
}

function bitacora_generarBotonesAccion($idbitacora)
{
    $botones = '';
    if (PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'ver')) {
        $botones .= '<button type="button" class="text-blue-600 hover:text-blue-800 p-1 transition-colors duration-150 btn-ver-detalle" data-id="' . $idbitacora . '" title="Ver detalles">
                        <i class="fas fa-eye"></i>
                    </button>';
    }
    return $botones;
}