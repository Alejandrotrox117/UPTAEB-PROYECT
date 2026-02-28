<?php

require_once __DIR__ . '/../../helpers/controller_helpers.php';

use App\Models\ClientesModel;
use App\Models\BitacoraModel;
use App\Models\UsuariosModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\PermisosModuloVerificar;

// =============================================================================
// FUNCIONES AUXILIARES DEL CONTROLADOR
// =============================================================================

/**
 * Obtiene una instancia del modelo ClientesModel.
 */
function getClientesModel()
{
    return new ClientesModel();
}

/**
 * Obtiene una instancia del modelo BitacoraModel.
 */
function getClientesBitacoraModel()
{
    return new BitacoraModel();
}

/**
 * Renderiza una vista del módulo Clientes.
 */
function renderClientesView(string $view, array $data = [])
{
    renderView('clientes', $view, $data);
}

// =============================================================================
// FUNCIONES PÚBLICAS DEL CONTROLADOR
// =============================================================================

/**
 * Vista principal del módulo Clientes.
 */
function clientes_index()
{
    if (!obtenerUsuarioSesion()) {
        header('Location: ' . base_url() . '/login');
        die();
    }

    if (!PermisosModuloVerificar::verificarAccesoModulo('clientes')) {
        renderClientesView("permisos");
        exit();
    }

    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('clientes', 'ver')) {
        renderClientesView("permisos");
        exit();
    }

    $idusuario = obtenerUsuarioSesion();
    $bitacoraModel = getClientesBitacoraModel();
    BitacoraHelper::registrarAccesoModulo('clientes', $idusuario, $bitacoraModel);

    $permisos = PermisosModuloVerificar::getPermisosUsuarioModulo('clientes');

    $data['page_tag'] = "Clientes";
    $data['page_title'] = "Administración de Clientes";
    $data['page_name'] = "clientes";
    $data['page_content'] = "Gestión integral de clientes del sistema";
    $data['page_functions_js'] = "functions_clientes.js";
    $data['permisos'] = $permisos;

    renderClientesView("clientes", $data);
}

/**
 * Obtiene todos los clientes (JSON).
 */
function clientes_getClientesData()
{
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        try {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('clientes', 'ver')) {
                $response = array('status' => false, 'message' => 'No tienes permisos para ver clientes', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                die();
            }

            $idUsuarioSesion = obtenerUsuarioSesion();
            $model = getClientesModel();
            $arrResponse = $model->selectAllClientes($idUsuarioSesion);

            if ($arrResponse['status']) {
                $bitacoraModel = getClientesBitacoraModel();
                $bitacoraModel->registrarAccion('clientes', 'CONSULTA_LISTADO', $idUsuarioSesion);
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            error_log("Error en getClientesData: " . $e->getMessage());
            $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Crea un nuevo cliente.
 */
function clientes_createCliente()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('clientes', 'crear')) {
                $arrResponse = array('status' => false, 'message' => 'No tienes permisos para crear clientes');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $postdata = file_get_contents('php://input');
            $request = json_decode($postdata, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $datosLimpios = [
                'nombre' => trim($request['nombre'] ?? ''),
                'apellido' => trim($request['apellido'] ?? ''),
                'cedula' => $request['cedula'] ?? '',
                'telefono_principal' => trim($request['telefono_principal'] ?? ''),
                'direccion' => trim($request['direccion'] ?? ''),
                'observaciones' => trim($request['observaciones'] ?? '')
            ];

            $camposObligatorios = ['nombre', 'apellido', 'cedula', 'telefono_principal'];
            foreach ($camposObligatorios as $campo) {
                if (empty($datosLimpios[$campo])) {
                    $arrResponse = array('status' => false, 'message' => 'Todos los campos obligatorios deben ser completados');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }
            }

            if (strlen($datosLimpios['nombre']) < 2 || strlen($datosLimpios['nombre']) > 50) {
                $arrResponse = array('status' => false, 'message' => 'El nombre debe tener entre 2 y 50 caracteres');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            if (strlen($datosLimpios['cedula']) < 6 || strlen($datosLimpios['cedula']) > 20) {
                $arrResponse = array('status' => false, 'message' => 'La cédula debe tener entre 6 y 20 caracteres');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $arrData = array(
                'nombre' => $datosLimpios['nombre'],
                'apellido' => $datosLimpios['apellido'],
                'cedula' => $datosLimpios['cedula'],
                'telefono_principal' => $datosLimpios['telefono_principal'],
                'direccion' => $datosLimpios['direccion'],
                'observaciones' => $datosLimpios['observaciones']
            );

            $idusuario = obtenerUsuarioSesion();

            if (!$idusuario) {
                error_log("ERROR: No se encontró ID de usuario en la sesión durante createCliente()");
                $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $model = getClientesModel();
            $arrResponse = $model->insertCliente($arrData);

            if ($arrResponse['status'] === true) {
                $bitacoraModel = getClientesBitacoraModel();
                $resultadoBitacora = $bitacoraModel->registrarAccion('clientes', 'CREAR_CLIENTE', $idusuario);

                if (!$resultadoBitacora) {
                    error_log("Warning: No se pudo registrar en bitácora la creación del cliente ID: " .
                        ($arrResponse['cliente_id'] ?? 'desconocido'));
                }
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            error_log("Error en createCliente: " . $e->getMessage());
            $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Obtiene un cliente por su ID (JSON).
 */
function clientes_getClienteById($idcliente)
{
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('clientes', 'ver')) {
            $arrResponse = array('status' => false, 'message' => 'No tienes permisos para ver clientes');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        if (empty($idcliente) || !is_numeric($idcliente)) {
            $arrResponse = array('status' => false, 'message' => 'ID de cliente inválido');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $model = getClientesModel();
            $arrData = $model->selectClienteById(intval($idcliente));
            if (!empty($arrData)) {
                $idUsuarioSesion = obtenerUsuarioSesion();
                $bitacoraModel = getClientesBitacoraModel();
                $bitacoraModel->registrarAccion('clientes', 'VER_CLIENTE', $idUsuarioSesion);

                $arrResponse = array('status' => true, 'data' => $arrData);
            } else {
                $arrResponse = array('status' => false, 'message' => 'Cliente no encontrado');
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            error_log("Error en getClienteById: " . $e->getMessage());
            $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Actualiza un cliente existente.
 */
function clientes_updateCliente()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('clientes', 'editar')) {
                $arrResponse = array('status' => false, 'message' => 'No tienes permisos para editar clientes');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $postdata = file_get_contents('php://input');
            $request = json_decode($postdata, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $intIdCliente = intval($request['idcliente'] ?? 0);
            if ($intIdCliente <= 0) {
                $arrResponse = array('status' => false, 'message' => 'ID de cliente inválido');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $datosLimpios = [
                'nombre' => trim($request['nombre'] ?? ''),
                'apellido' => trim($request['apellido'] ?? ''),
                'cedula' => $request['cedula'],
                'telefono_principal' => $request['telefono_principal'],
                'direccion' => trim($request['direccion'] ?? ''),
                'estatus' => trim($request['estatus'] ?? 'activo'),
                'observaciones' => trim($request['observaciones'] ?? '')
            ];

            $camposObligatorios = ['nombre', 'apellido', 'cedula', 'telefono_principal'];
            foreach ($camposObligatorios as $campo) {
                if (empty($datosLimpios[$campo])) {
                    $arrResponse = array('status' => false, 'message' => 'Todos los campos obligatorios deben ser completados');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }
            }

            if (strlen($datosLimpios['nombre']) < 2 || strlen($datosLimpios['nombre']) > 50) {
                $arrResponse = array('status' => false, 'message' => 'El nombre debe tener entre 2 y 50 caracteres');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $idusuario = obtenerUsuarioSesion();

            if (!$idusuario) {
                error_log("ERROR: No se encontró ID de usuario en la sesión durante updateCliente()");
                $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $model = getClientesModel();
            $arrResponse = $model->updateCliente($intIdCliente, $datosLimpios);

            if ($arrResponse['status'] === true) {
                $bitacoraModel = getClientesBitacoraModel();
                $resultadoBitacora = $bitacoraModel->registrarAccion('clientes', 'ACTUALIZAR_CLIENTE', $idusuario);

                if (!$resultadoBitacora) {
                    error_log("Warning: No se pudo registrar en bitácora la actualización del cliente ID: " . $intIdCliente);
                }
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            error_log("Error en updateCliente: " . $e->getMessage());
            $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Desactiva (elimina lógicamente) un cliente.
 */
function clientes_deleteCliente()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('clientes', 'eliminar')) {
                $arrResponse = array('status' => false, 'message' => 'No tienes permisos para eliminar clientes');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $postdata = file_get_contents('php://input');
            $request = json_decode($postdata, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $intIdCliente = intval($request['idcliente'] ?? 0);
            if ($intIdCliente <= 0) {
                $arrResponse = array('status' => false, 'message' => 'ID de cliente inválido');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $idusuario = obtenerUsuarioSesion();
            $model = getClientesModel();

            $requestDelete = $model->deleteClienteById($intIdCliente);
            if ($requestDelete) {
                $arrResponse = array('status' => true, 'message' => 'Cliente desactivado correctamente');
            } else {
                $arrResponse = array('status' => false, 'message' => 'Error al desactivar el cliente');
            }

            if ($arrResponse['status'] === true) {
                $bitacoraModel = getClientesBitacoraModel();
                $resultadoBitacora = $bitacoraModel->registrarAccion('clientes', 'ELIMINAR_CLIENTE', $idusuario);

                if (!$resultadoBitacora) {
                    error_log("Warning: No se pudo registrar en bitácora la eliminación del cliente ID: " . $intIdCliente);
                }
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            error_log("Error en deleteCliente: " . $e->getMessage());
            $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Busca clientes por criterio.
 */
function clientes_buscarClientes()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('clientes', 'ver')) {
                $arrResponse = array('status' => false, 'message' => 'No tienes permisos para buscar clientes');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $postdata = file_get_contents('php://input');
            $request = json_decode($postdata, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $strTermino = trim($request['criterio'] ?? '');
            if (empty($strTermino)) {
                $arrResponse = array('status' => false, 'message' => 'Término de búsqueda requerido');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $model = getClientesModel();
            $arrData = $model->buscarClientes($strTermino);
            if ($arrData['status']) {
                $arrResponse = array('status' => true, 'data' => $arrData['data']);
            } else {
                $arrResponse = array('status' => false, 'message' => 'No se encontraron resultados');
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            error_log("Error en buscarClientes: " . $e->getMessage());
            $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Exporta los datos de clientes.
 */
function clientes_exportarClientes()
{
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('clientes', 'exportar')) {
            $arr = array(
                "status" => false,
                "message" => "No tienes permisos para exportar clientes.",
                "data" => null
            );
            echo json_encode($arr, JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $model = getClientesModel();
            $clientesResponse = $model->selectAllClientes();

            if ($clientesResponse['status'] && !empty($clientesResponse['data'])) {
                $idusuario = obtenerUsuarioSesion();
                $bitacoraModel = getClientesBitacoraModel();
                $bitacoraModel->registrarAccion('clientes', 'EXPORTAR_CLIENTES', $idusuario);

                $arr = array(
                    "status" => true,
                    "message" => "Datos de clientes obtenidos correctamente.",
                    "data" => $clientesResponse['data']
                );
            } else {
                $arr = array(
                    "status" => false,
                    "message" => "No hay clientes para exportar.",
                    "data" => []
                );
            }
        } catch (\Exception $e) {
            error_log("Error en exportarClientes: " . $e->getMessage());
            $arr = array(
                "status" => false,
                "message" => "Error al obtener datos: " . $e->getMessage(),
                "data" => null
            );
        }

        echo json_encode($arr, JSON_UNESCAPED_UNICODE);
        die();
    }
}

/**
 * Obtiene estadísticas de clientes.
 */
function clientes_getEstadisticas()
{
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        try {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('clientes', 'ver')) {
                $response = array('status' => false, 'message' => 'No tienes permisos para ver estadísticas', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                die();
            }

            $model = getClientesModel();
            $estadisticas = $model->getEstadisticasClientes();
            $arrResponse = array('status' => true, 'data' => $estadisticas);

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            error_log("Error en getEstadisticas: " . $e->getMessage());
            $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Registra un cliente desde un modal (con campos extendidos).
 */
function clientes_registrarClienteModal()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('clientes', 'crear')) {
                $arrResponse = array('status' => false, 'message' => 'No tienes permisos para crear clientes');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $postdata = file_get_contents('php://input');
            $request = json_decode($postdata, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            // Limpiar y validar datos de entrada
            $datosLimpios = [
                'nombre' => trim($request['nombre'] ?? ''),
                'apellido' => trim($request['apellido'] ?? ''),
                'identificacion' => trim($request['identificacion'] ?? ''),
                'telefono_principal' => trim($request['telefono_principal'] ?? ''),
                'fecha_nacimiento' => trim($request['fecha_nacimiento'] ?? ''),
                'genero' => trim($request['genero'] ?? ''),
                'correo_electronico' => trim($request['correo_electronico'] ?? ''),
                'direccion' => trim($request['direccion'] ?? ''),
                'observaciones' => trim($request['observaciones'] ?? '')
            ];

            // Validar campos obligatorios
            $camposObligatorios = ['nombre', 'apellido', 'identificacion', 'telefono_principal', 'direccion'];
            foreach ($camposObligatorios as $campo) {
                if (empty($datosLimpios[$campo])) {
                    $nombreCampo = str_replace('_', ' ', ucfirst($campo));
                    $arrResponse = array('status' => false, 'message' => "El campo {$nombreCampo} es obligatorio");
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }
            }

            // Validaciones específicas
            if (strlen($datosLimpios['nombre']) < 2 || strlen($datosLimpios['nombre']) > 50) {
                $arrResponse = array('status' => false, 'message' => 'El nombre debe tener entre 2 y 50 caracteres');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            if (strlen($datosLimpios['apellido']) < 2 || strlen($datosLimpios['apellido']) > 50) {
                $arrResponse = array('status' => false, 'message' => 'El apellido debe tener entre 2 y 50 caracteres');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            if (strlen($datosLimpios['identificacion']) < 6 || strlen($datosLimpios['identificacion']) > 20) {
                $arrResponse = array('status' => false, 'message' => 'La identificación debe tener entre 6 y 20 caracteres');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            // Validar email si se proporciona
            if (!empty($datosLimpios['correo_electronico']) && !filter_var($datosLimpios['correo_electronico'], FILTER_VALIDATE_EMAIL)) {
                $arrResponse = array('status' => false, 'message' => 'El formato del correo electrónico no es válido');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            // Validar fecha si se proporciona
            if (!empty($datosLimpios['fecha_nacimiento'])) {
                $fecha = \DateTime::createFromFormat('Y-m-d', $datosLimpios['fecha_nacimiento']);
                if (!$fecha || $fecha->format('Y-m-d') !== $datosLimpios['fecha_nacimiento']) {
                    $arrResponse = array('status' => false, 'message' => 'El formato de fecha de nacimiento no es válido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }
            }

            $arrData = array(
                'nombre' => $datosLimpios['nombre'],
                'apellido' => $datosLimpios['apellido'],
                'cedula' => $datosLimpios['identificacion'],
                'telefono_principal' => $datosLimpios['telefono_principal'],
                'fecha_nacimiento' => $datosLimpios['fecha_nacimiento'],
                'genero' => $datosLimpios['genero'],
                'correo_electronico' => $datosLimpios['correo_electronico'],
                'direccion' => $datosLimpios['direccion'],
                'observaciones' => $datosLimpios['observaciones'],
                'estatus' => 'Activo'
            );

            $idusuario = obtenerUsuarioSesion();

            if (!$idusuario) {
                error_log("ERROR: No se encontró ID de usuario en la sesión durante registrarClienteModal()");
                $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $model = getClientesModel();
            $arrResponse = $model->insertClienteCompleto($arrData);

            if ($arrResponse['status'] === true) {
                $bitacoraModel = getClientesBitacoraModel();
                $resultadoBitacora = $bitacoraModel->registrarAccion('clientes', 'CREAR_CLIENTE_MODAL', $idusuario);

                if (!$resultadoBitacora) {
                    error_log("Warning: No se pudo registrar en bitácora la creación del cliente ID: " .
                        ($arrResponse['cliente_id'] ?? 'desconocido'));
                }
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            error_log("Error en registrarClienteModal: " . $e->getMessage());
            $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Reactivar un cliente inactivo (solo super usuarios).
 */
function clientes_reactivarCliente()
{
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        echo json_encode(['status' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
        die();
    }

    $idusuario = obtenerUsuarioSesion();
    $bitacoraModel = getClientesBitacoraModel();

    try {
        $usuariosModel = new UsuariosModel();
        $esSuperAdmin = $usuariosModel->verificarEsSuperUsuario($idusuario);

        if (!$esSuperAdmin) {
            BitacoraHelper::registrarError('clientes', 'Intento de reactivar cliente sin ser super usuario', $idusuario, $bitacoraModel);
            echo json_encode(['status' => false, 'message' => 'Solo los super usuarios pueden reactivar clientes'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($data['idcliente'])) {
            throw new \Exception('Datos inválidos');
        }

        $intIdCliente = intval($data['idcliente']);
        $model = getClientesModel();
        $resultado = $model->reactivarCliente($intIdCliente);

        if (isset($resultado['status']) && $resultado['status'] === true) {
            $detalle = "Cliente reactivado con ID: " . $intIdCliente;
            BitacoraHelper::registrarAccion('clientes', 'REACTIVAR_CLIENTE', $idusuario, $bitacoraModel, $detalle, $intIdCliente);
        }

        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

    } catch (\Exception $e) {
        error_log("Error en reactivarCliente: " . $e->getMessage());
        BitacoraHelper::registrarError('clientes', $e->getMessage(), $idusuario, $bitacoraModel);
        echo json_encode(['status' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Verificar si el usuario actual es super usuario.
 */
function clientes_verificarSuperUsuario()
{
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $idusuario = obtenerUsuarioSesion();

            if (!$idusuario) {
                echo json_encode([
                    'status' => false,
                    'message' => 'Usuario no autenticado',
                    'es_super_usuario' => false,
                    'usuario_id' => 0
                ], JSON_UNESCAPED_UNICODE);
                die();
            }

            $usuariosModel = new UsuariosModel();
            $esSuperAdmin = $usuariosModel->verificarEsSuperUsuario($idusuario);

            echo json_encode([
                'status' => true,
                'es_super_usuario' => $esSuperAdmin,
                'usuario_id' => $idusuario,
                'message' => 'Verificación completada'
            ], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            error_log("Error en verificarSuperUsuario (Clientes): " . $e->getMessage());
            echo json_encode([
                'status' => false,
                'message' => 'Error al verificar permisos',
                'es_super_usuario' => false,
                'usuario_id' => 0
            ], JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}
