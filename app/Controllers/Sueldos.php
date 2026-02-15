<?php

use App\Models\SueldosModel;
use App\Models\BitacoraModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\PermisosModuloVerificar;

// =============================================================================
// FUNCIONES AUXILIARES DEL CONTROLADOR
// =============================================================================

/**
 * Obtiene el modelo de sueldos
 */
function getSueldosModel() {
    return new SueldosModel();
}

/**
 * Obtiene el modelo de bitácora para sueldos
 */
function getSueldosBitacoraModel() {
    return new BitacoraModel();
}

/**
 * Renderiza una vista de sueldos
 */
function renderSueldosView($view, $data = []) {
    renderView('sueldos', $view, $data);
}

// =============================================================================
// FUNCIONES PÚBLICAS DEL CONTROLADOR
// =============================================================================

/**
 * Vista principal de sueldos
 */
function sueldos_index() {
    $bitacoraModel = getSueldosBitacoraModel();
    $bitacoraHelper = new BitacoraHelper();

    if (!$bitacoraHelper->obtenerUsuarioSesion()) {
        die();
    }

    if (!PermisosModuloVerificar::verificarAccesoModulo('sueldos')) {
        renderSueldosView("permisos");
        exit();
    }

    $idusuario = $bitacoraHelper->obtenerUsuarioSesion();
    BitacoraHelper::registrarAccesoModulo('Sueldos', $idusuario, $bitacoraModel);

    $data['page_tag'] = "Sueldos";
    $data['page_title'] = "Administración de Sueldos";
    $data['page_name'] = "sueldos";
    $data['page_content'] = "Gestión integral de sueldos del sistema";
    $data['page_functions_js'] = "functions_sueldos.js";
    renderSueldosView("sueldos", $data);
}

/**
 * Crear un nuevo sueldo
 */
function sueldos_createSueldo() {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') return;

    $bitacoraModel = getSueldosBitacoraModel();
    $bitacoraHelper = new BitacoraHelper();

    if (!$bitacoraHelper->obtenerUsuarioSesion()) {
        die();
    }

    if (!PermisosModuloVerificar::verificarAccesoModulo('sueldos')) {
        renderSueldosView("permisos");
        exit();
    }

    try {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Sueldos', 'crear')) {
            $arrResponse = array('status' => false, 'message' => 'No tienes permisos para crear sueldos');
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

        // Validar que al menos se especifique una persona o empleado
        if (empty($request['idpersona']) && empty($request['idempleado'])) {
            $arrResponse = array('status' => false, 'message' => 'Debe especificar una persona o empleado');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Validar que no se envíen ambos campos al mismo tiempo
        if (!empty($request['idpersona']) && !empty($request['idempleado'])) {
            $arrResponse = array('status' => false, 'message' => 'No se puede especificar persona y empleado al mismo tiempo');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Validar campos obligatorios
        if (empty($request['monto'])) {
            $arrResponse = array('status' => false, 'message' => 'El monto es obligatorio');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Validar que se especifique una moneda
        if (empty($request['idmoneda'])) {
            $arrResponse = array('status' => false, 'message' => 'Debe seleccionar una moneda');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Validar que monto sea numérico
        if (!is_numeric($request['monto'])) {
            $arrResponse = array('status' => false, 'message' => 'El monto debe ser un valor numérico');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Validar que el monto sea positivo
        if (floatval($request['monto']) <= 0) {
            $arrResponse = array('status' => false, 'message' => 'El monto debe ser un valor positivo');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Crear array de datos - asegurar que solo uno de los dos campos esté presente
        $arrData = array(
            'idpersona' => !empty($request['idpersona']) ? intval($request['idpersona']) : null,
            'idempleado' => !empty($request['idempleado']) ? intval($request['idempleado']) : null,
            'monto' => floatval($request['monto']),
            'idmoneda' => intval($request['idmoneda']),
            'observacion' => trim($request['observacion'] ?? '')
        );

        // Log para debugging
        error_log("Datos recibidos para sueldo: " . json_encode($request));
        error_log("Datos procesados para sueldo: " . json_encode($arrData));

        $idusuario = $bitacoraHelper->obtenerUsuarioSesion();

        if (!$idusuario) {
            error_log("ERROR: No se encontró ID de usuario en la sesión durante createSueldo()");
            $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        $model = getSueldosModel();
        $arrResponse = $model->insertSueldo($arrData);

        if ($arrResponse['status'] === true) {
            $resultadoBitacora = $bitacoraModel->registrarAccion('Sueldos', 'INSERTAR', $idusuario);

            if (!$resultadoBitacora) {
                error_log("Warning: No se pudo registrar en bitácora la inserción del sueldo");
            }
        }

        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        error_log("Error en createSueldo: " . $e->getMessage());
        $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Obtener monedas disponibles
 */
function sueldos_getMonedas() {
    if ($_SERVER['REQUEST_METHOD'] != 'GET') return;

    $bitacoraHelper = new BitacoraHelper();

    if (!$bitacoraHelper->obtenerUsuarioSesion()) {
        die();
    }

    if (!PermisosModuloVerificar::verificarAccesoModulo('sueldos')) {
        renderSueldosView("permisos");
        exit();
    }

    try {
        error_log("Sueldos::getMonedas - Iniciando...");

        $model = getSueldosModel();
        $arrResponse = $model->getMonedas();
        error_log("Sueldos::getMonedas - Respuesta del modelo: " . json_encode($arrResponse));

        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        error_log("Error en getMonedas: " . $e->getMessage());
        $arrResponse = array('status' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage(), 'data' => []);
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Debug de monedas - temporal
 */
function sueldos_debugMonedas() {
    $bitacoraHelper = new BitacoraHelper();

    if (!$bitacoraHelper->obtenerUsuarioSesion()) {
        die();
    }

    try {
        error_log("DEBUG: Intentando obtener monedas...");
        $model = getSueldosModel();
        $result = $model->getMonedas();
        error_log("DEBUG: Resultado: " . json_encode($result));

        header('Content-Type: application/json');
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        error_log("DEBUG ERROR: " . $e->getMessage());
        $response = array('status' => false, 'message' => 'Error: ' . $e->getMessage(), 'data' => []);
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Obtener todos los sueldos
 */
function sueldos_getSueldosData() {
    if ($_SERVER['REQUEST_METHOD'] != 'GET') return;

    $bitacoraModel = getSueldosBitacoraModel();
    $bitacoraHelper = new BitacoraHelper();

    if (!$bitacoraHelper->obtenerUsuarioSesion()) {
        die();
    }

    if (!PermisosModuloVerificar::verificarAccesoModulo('sueldos')) {
        renderSueldosView("permisos");
        exit();
    }

    try {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Sueldos', 'ver')) {
            $response = array('status' => false, 'message' => 'No tienes permisos para ver sueldos', 'data' => []);
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Obtener ID del usuario actual
        $idusuario = $bitacoraHelper->obtenerUsuarioSesion();

        // Obtener sueldos
        $model = getSueldosModel();
        $arrResponse = $model->selectAllSueldos($idusuario);

        if ($arrResponse['status']) {
            $bitacoraModel->registrarAccion('Sueldos', 'CONSULTA_LISTADO', $idusuario);
        }

        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        error_log("Error en getSueldosData: " . $e->getMessage());
        $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Obtener un sueldo por ID
 */
function sueldos_getSueldoById($idsueldo) {
    if ($_SERVER['REQUEST_METHOD'] != 'GET') return;

    $bitacoraHelper = new BitacoraHelper();

    if (!$bitacoraHelper->obtenerUsuarioSesion()) {
        die();
    }

    if (!PermisosModuloVerificar::verificarAccesoModulo('sueldos')) {
        renderSueldosView("permisos");
        exit();
    }

    if (empty($idsueldo) || !is_numeric($idsueldo)) {
        $arrResponse = array('status' => false, 'message' => 'ID de sueldo inválido');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    try {
        $model = getSueldosModel();
        $arrData = $model->selectSueldoById(intval($idsueldo));
        if (!empty($arrData)) {
            $arrResponse = array('status' => true, 'data' => $arrData);
        } else {
            $arrResponse = array('status' => false, 'message' => 'Sueldo no encontrado');
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        error_log("Error en getSueldoById: " . $e->getMessage());
        $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Obtener monto convertido a bolívares
 */
function sueldos_getMontoBolivares($idsueldo) {
    if ($_SERVER['REQUEST_METHOD'] != 'GET') return;

    $bitacoraHelper = new BitacoraHelper();

    if (!$bitacoraHelper->obtenerUsuarioSesion()) {
        die();
    }

    if (!PermisosModuloVerificar::verificarAccesoModulo('sueldos')) {
        renderSueldosView("permisos");
        exit();
    }

    if (empty($idsueldo) || !is_numeric($idsueldo)) {
        $arrResponse = array('status' => false, 'message' => 'ID de sueldo inválido');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    try {
        $model = getSueldosModel();
        $arrResponse = $model->convertirMontoABolivares(intval($idsueldo));
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        error_log("Error en getMontoBolivares: " . $e->getMessage());
        $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Actualizar un sueldo existente
 */
function sueldos_updateSueldo() {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') return;

    $bitacoraModel = getSueldosBitacoraModel();
    $bitacoraHelper = new BitacoraHelper();

    if (!$bitacoraHelper->obtenerUsuarioSesion()) {
        die();
    }

    if (!PermisosModuloVerificar::verificarAccesoModulo('sueldos')) {
        renderSueldosView("permisos");
        exit();
    }

    try {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Sueldos', 'editar')) {
            $arrResponse = array('status' => false, 'message' => 'No tienes permisos para editar sueldos');
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

        $intIdSueldo = intval($request['idsueldo'] ?? 0);
        if ($intIdSueldo <= 0) {
            $arrResponse = array('status' => false, 'message' => 'ID de sueldo inválido');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Validar que al menos se especifique una persona o empleado
        if (empty($request['idpersona']) && empty($request['idempleado'])) {
            $arrResponse = array('status' => false, 'message' => 'Debe especificar una persona o empleado');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Validar que no se envíen ambos campos al mismo tiempo
        if (!empty($request['idpersona']) && !empty($request['idempleado'])) {
            $arrResponse = array('status' => false, 'message' => 'No se puede especificar persona y empleado al mismo tiempo');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Validar campos obligatorios
        if (empty($request['monto'])) {
            $arrResponse = array('status' => false, 'message' => 'El monto es obligatorio');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Validar que se especifique una moneda
        if (empty($request['idmoneda'])) {
            $arrResponse = array('status' => false, 'message' => 'Debe seleccionar una moneda');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Validar que monto sea numérico
        if (!is_numeric($request['monto'])) {
            $arrResponse = array('status' => false, 'message' => 'El monto debe ser un valor numérico');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Validar que el monto sea positivo
        if (floatval($request['monto']) <= 0) {
            $arrResponse = array('status' => false, 'message' => 'El monto debe ser un valor positivo');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        // Crear array de datos
        $arrData = array(
            'idpersona' => !empty($request['idpersona']) ? intval($request['idpersona']) : null,
            'idempleado' => !empty($request['idempleado']) ? intval($request['idempleado']) : null,
            'monto' => floatval($request['monto']),
            'idmoneda' => intval($request['idmoneda']),
            'observacion' => trim($request['observacion'] ?? '')
        );

        error_log("Actualización - Datos recibidos para sueldo: " . json_encode($request));
        error_log("Actualización - Datos procesados para sueldo: " . json_encode($arrData));

        $idusuario = $bitacoraHelper->obtenerUsuarioSesion();

        if (!$idusuario) {
            error_log("ERROR: No se encontró ID de usuario en la sesión durante updateSueldo()");
            $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        $model = getSueldosModel();
        $arrResponse = $model->updateSueldo($intIdSueldo, $arrData);

        if ($arrResponse['status'] === true) {
            $resultadoBitacora = $bitacoraModel->registrarAccion('Sueldos', 'ACTUALIZAR', $idusuario);

            if (!$resultadoBitacora) {
                error_log("Warning: No se pudo registrar en bitácora la actualización del sueldo ID: " . $intIdSueldo);
            }
        }

        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        error_log("Error en updateSueldo: " . $e->getMessage());
        $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Eliminar (desactivar) un sueldo
 */
function sueldos_deleteSueldo() {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') return;

    $bitacoraModel = getSueldosBitacoraModel();
    $bitacoraHelper = new BitacoraHelper();

    if (!$bitacoraHelper->obtenerUsuarioSesion()) {
        die();
    }

    if (!PermisosModuloVerificar::verificarAccesoModulo('sueldos')) {
        renderSueldosView("permisos");
        exit();
    }

    try {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Sueldos', 'eliminar')) {
            $arrResponse = array('status' => false, 'message' => 'No tienes permisos para eliminar sueldos');
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

        $intIdSueldo = intval($request['idsueldo'] ?? 0);
        if ($intIdSueldo <= 0) {
            $arrResponse = array('status' => false, 'message' => 'ID de sueldo inválido');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        $idusuario = $bitacoraHelper->obtenerUsuarioSesion();
        $model = getSueldosModel();
        $requestDelete = $model->deleteSueldo($intIdSueldo);

        if ($requestDelete) {
            $arrResponse = array('status' => true, 'message' => 'Sueldo desactivado correctamente');
        } else {
            $arrResponse = array('status' => false, 'message' => 'Error al desactivar el sueldo');
        }

        if ($arrResponse['status'] === true) {
            $resultadoBitacora = $bitacoraModel->registrarAccion('Sueldos', 'ELIMINAR', $idusuario);

            if (!$resultadoBitacora) {
                error_log("Warning: No se pudo registrar en bitácora la eliminación del sueldo ID: " . $intIdSueldo);
            }
        }

        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        error_log("Error en deleteSueldo: " . $e->getMessage());
        $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Obtener personas activas
 */
function sueldos_getPersonasActivas() {
    if ($_SERVER['REQUEST_METHOD'] != 'GET') return;

    $bitacoraHelper = new BitacoraHelper();

    if (!$bitacoraHelper->obtenerUsuarioSesion()) {
        die();
    }

    if (!PermisosModuloVerificar::verificarAccesoModulo('sueldos')) {
        renderSueldosView("permisos");
        exit();
    }

    try {
        $model = getSueldosModel();
        $arrResponse = $model->selectPersonasActivas();
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        error_log("Error en getPersonasActivas: " . $e->getMessage());
        $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Obtener empleados activos
 */
function sueldos_getEmpleadosActivos() {
    if ($_SERVER['REQUEST_METHOD'] != 'GET') return;

    $bitacoraHelper = new BitacoraHelper();

    if (!$bitacoraHelper->obtenerUsuarioSesion()) {
        die();
    }

    if (!PermisosModuloVerificar::verificarAccesoModulo('sueldos')) {
        renderSueldosView("permisos");
        exit();
    }

    try {
        $model = getSueldosModel();
        $arrResponse = $model->selectEmpleadosActivos();
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        error_log("Error en getEmpleadosActivos: " . $e->getMessage());
        $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Buscar sueldos por término
 */
function sueldos_buscarSueldo() {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') return;

    $bitacoraHelper = new BitacoraHelper();

    if (!$bitacoraHelper->obtenerUsuarioSesion()) {
        die();
    }

    if (!PermisosModuloVerificar::verificarAccesoModulo('sueldos')) {
        renderSueldosView("permisos");
        exit();
    }

    try {
        $postdata = file_get_contents('php://input');
        $request = json_decode($postdata, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        $strTermino = strClean($request['termino'] ?? '');
        if (empty($strTermino)) {
            $arrResponse = array('status' => false, 'message' => 'Término de búsqueda requerido');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        $model = getSueldosModel();
        $arrData = $model->buscarSueldos($strTermino);
        if ($arrData['status']) {
            $arrResponse = array('status' => true, 'data' => $arrData['data']);
        } else {
            $arrResponse = array('status' => false, 'message' => 'No se encontraron resultados');
        }

        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        error_log("Error en buscarSueldo: " . $e->getMessage());
        $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Exportar datos de sueldos
 */
function sueldos_exportarSueldos() {
    if ($_SERVER['REQUEST_METHOD'] != 'GET') return;

    $bitacoraHelper = new BitacoraHelper();

    if (!$bitacoraHelper->obtenerUsuarioSesion()) {
        die();
    }

    if (!PermisosModuloVerificar::verificarAccesoModulo('sueldos')) {
        renderSueldosView("permisos");
        exit();
    }

    try {
        $idusuario = $bitacoraHelper->obtenerUsuarioSesion();
        $model = getSueldosModel();
        $arrData = $model->selectAllSueldos($idusuario);

        if ($arrData['status']) {
            $data['sueldos'] = $arrData['data'];
            $data['page_title'] = "Reporte de Sueldos";
            $data['fecha_reporte'] = date('d/m/Y H:i:s');

            $arrResponse = array('status' => true, 'message' => 'Datos preparados para exportación', 'data' => $data);
        } else {
            $arrResponse = array('status' => false, 'message' => 'No se pudieron obtener los datos');
        }

        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        error_log("Error en exportarSueldos: " . $e->getMessage());
        $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Verificar si el usuario es super usuario
 */
function sueldos_verificarSuperUsuario() {
    if ($_SERVER['REQUEST_METHOD'] != 'GET') return;

    $bitacoraHelper = new BitacoraHelper();

    if (!$bitacoraHelper->obtenerUsuarioSesion()) {
        die();
    }

    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $idusuario = $bitacoraHelper->obtenerUsuarioSesion();

        if (!$idusuario) {
            echo json_encode([
                'status' => false,
                'message' => 'Usuario no autenticado',
                'es_super_usuario' => false,
                'usuario_id' => 0,
                'debug_session' => $_SESSION
            ]);
            die();
        }

        $model = getSueldosModel();
        $esSuperAdmin = $model->verificarEsSuperUsuario($idusuario);

        echo json_encode([
            'status' => true,
            'es_super_usuario' => $esSuperAdmin,
            'usuario_id' => $idusuario,
            'message' => 'Verificación completada'
        ]);
    } catch (Exception $e) {
        error_log("Error en verificarSuperUsuario: " . $e->getMessage());
        echo json_encode([
            'status' => false,
            'message' => 'Error interno del servidor: ' . $e->getMessage(),
            'es_super_usuario' => false,
            'usuario_id' => 0
        ]);
    }
    die();
}

/**
 * Reactivar un sueldo inactivo
 */
function sueldos_reactivarSueldo() {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') return;

    $bitacoraModel = getSueldosBitacoraModel();
    $bitacoraHelper = new BitacoraHelper();

    if (!$bitacoraHelper->obtenerUsuarioSesion()) {
        die();
    }

    if (!PermisosModuloVerificar::verificarAccesoModulo('sueldos')) {
        renderSueldosView("permisos");
        exit();
    }

    try {
        // Verificar si es super usuario
        $idusuario = $bitacoraHelper->obtenerUsuarioSesion();
        $model = getSueldosModel();
        $esSuperUsuario = $model->verificarEsSuperUsuario($idusuario);

        if (!$esSuperUsuario) {
            $arrResponse = array('status' => false, 'message' => 'Solo los super usuarios pueden reactivar sueldos');
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

        $intIdSueldo = intval($request['idsueldo'] ?? 0);
        if ($intIdSueldo <= 0) {
            $arrResponse = array('status' => false, 'message' => 'ID de sueldo inválido');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        $requestReactivar = $model->reactivarSueldo($intIdSueldo);

        if ($requestReactivar) {
            $arrResponse = array('status' => true, 'message' => 'Sueldo reactivado correctamente');

            // Registrar en bitácora
            $resultadoBitacora = $bitacoraModel->registrarAccion('Sueldos', 'REACTIVAR', $idusuario);
            if (!$resultadoBitacora) {
                error_log("Warning: No se pudo registrar en bitácora la reactivación del sueldo ID: " . $intIdSueldo);
            }
        } else {
            $arrResponse = array('status' => false, 'message' => 'Error al reactivar el sueldo');
        }

        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        error_log("Error en reactivarSueldo: " . $e->getMessage());
        $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Procesar pago de sueldo
 */
function sueldos_procesarPagoSueldo() {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        echo json_encode(['status' => false, 'message' => 'Método no permitido']);
        return;
    }

    $bitacoraHelper = new BitacoraHelper();

    if (!$bitacoraHelper->obtenerUsuarioSesion()) {
        die();
    }

    if (!PermisosModuloVerificar::verificarAccesoModulo('sueldos')) {
        renderSueldosView("permisos");
        exit();
    }

    // Validar datos recibidos
    $requiredFields = ['idsueldo', 'monto', 'idtipo_pago', 'fecha_pago'];
    $data = [];

    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode([
                'status' => false,
                'message' => "El campo {$field} es requerido"
            ]);
            return;
        }
        $data[$field] = $_POST[$field];
    }

    // Campos opcionales
    $optionalFields = ['referencia', 'observaciones'];
    foreach ($optionalFields as $field) {
        if (isset($_POST[$field])) {
            $data[$field] = $_POST[$field];
        }
    }

    // Validar que el monto sea válido
    if (!is_numeric($data['monto']) || floatval($data['monto']) <= 0) {
        echo json_encode([
            'status' => false,
            'message' => 'El monto debe ser un número mayor a 0'
        ]);
        return;
    }

    // Procesar el pago
    $model = getSueldosModel();
    $result = $model->procesarPagoSueldo($data);

    // Si el pago fue exitoso, registrar en bitácora
    if ($result['status']) {
        $bitacoraHelper->registrarAccion(
            'Sueldos',
            'Procesar pago',
            "Pago procesado para sueldo ID: {$data['idsueldo']}, Monto: {$data['monto']} Bs., Pago ID: {$result['data']['pago_id']}",
            $data['idsueldo']
        );
    }

    echo json_encode($result);
}

/**
 * Obtener tipos de pagos activos
 */
function sueldos_getTiposPagos() {
    $bitacoraHelper = new BitacoraHelper();

    if (!$bitacoraHelper->obtenerUsuarioSesion()) {
        die();
    }

    require_once "app/models/tiposPagosModel.php";
    $tiposPagosModel = new TiposPagosModel();

    try {
        $result = $tiposPagosModel->selectTiposPagosActivos();
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode([
            'status' => false,
            'message' => 'Error al obtener tipos de pago: ' . $e->getMessage(),
            'data' => []
        ]);
    }
}

/**
 * Obtener pagos asociados a un sueldo
 */
function sueldos_getPagosSueldo($idsueldo = null) {
    $bitacoraHelper = new BitacoraHelper();

    if (!$bitacoraHelper->obtenerUsuarioSesion()) {
        die();
    }

    if (!PermisosModuloVerificar::verificarAccesoModulo('sueldos')) {
        renderSueldosView("permisos");
        exit();
    }

    if (!$idsueldo && isset($_GET['idsueldo'])) {
        $idsueldo = intval($_GET['idsueldo']);
    }

    if (!$idsueldo) {
        echo json_encode([
            'status' => false,
            'message' => 'ID de sueldo requerido',
            'data' => []
        ]);
        return;
    }

    try {
        $model = getSueldosModel();
        $result = $model->getPagosSueldo($idsueldo);
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode([
            'status' => false,
            'message' => 'Error al obtener pagos: ' . $e->getMessage(),
            'data' => []
        ]);
    }
}
