<?php

use App\Models\ProveedoresModel;
use App\Models\BitacoraModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\PermisosModuloVerificar;
use App\Helpers\Validation\ExpresionesRegulares;

// =============================================================================
// FUNCIONES AUXILIARES DEL CONTROLADOR
// =============================================================================

/**
 * Obtiene el modelo de proveedores
 */
function getProveedoresModel()
{
    return new ProveedoresModel();
}

/**
 * Renderiza una vista de proveedores
 */
function renderProveedoresView($view, $data = [])
{
    renderView('proveedores', $view, $data);
}

// =============================================================================
// FUNCIONES PÚBLICAS DEL CONTROLADOR
// =============================================================================

/**
 * Página principal del módulo de proveedores
 */
function proveedores_index()
{
    // Verificar autenticación
    if (!obtenerUsuarioSesion()) {
        header('Location: ' . base_url() . '/login');
        die();
    }

    // Verificar acceso al módulo
    if (!PermisosModuloVerificar::verificarAccesoModulo('proveedores')) {
        renderProveedoresView("permisos");
        exit();
    }

    // Registrar acceso al módulo
    $idusuario = obtenerUsuarioSesion();
    registrarAccesoModulo('proveedor', $idusuario);

    $data['page_tag'] = "Proveedores";
    $data['page_title'] = "Administración de Proveedores";
    $data['page_name'] = "proveedores";
    $data['page_content'] = "Gestión integral de proveedores del sistema";
    $data['page_functions_js'] = "functions_proveedores.js";
    renderProveedoresView("proveedores", $data);
}

/**
 * Crear un nuevo proveedor
 */
function proveedores_createProveedor()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            $postdata = file_get_contents('php://input');
            $request = json_decode($postdata, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $datosLimpios = [
                'nombre' => ExpresionesRegulares::limpiar($request['nombre'] ?? '', 'nombre'),
                'apellido' => ExpresionesRegulares::limpiar($request['apellido'] ?? '', 'apellido'),
                'identificacion' => ExpresionesRegulares::limpiar($request['identificacion'] ?? '', 'cedula'),
                'telefono_principal' => ExpresionesRegulares::limpiar($request['telefono_principal'] ?? '', 'telefono'),
                'correo_electronico' => ExpresionesRegulares::limpiar($request['correo_electronico'] ?? '', 'email'),
                'direccion' => trim($request['direccion'] ?? ''),
                'fecha_nacimiento' => $request['fecha_nacimiento'] ?? null,
                'genero' => strtoupper(trim($request['genero'] ?? '')),
                'observaciones' => trim($request['observaciones'] ?? '')
            ];

            $camposObligatorios = ['nombre', 'identificacion', 'telefono_principal'];
            foreach ($camposObligatorios as $campo) {
                if (empty($datosLimpios[$campo])) {
                    $arrResponse = array('status' => false, 'message' => 'Todos los campos obligatorios deben ser completados');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }
            }

            $reglasValidacion = [
                'nombre' => 'nombre',
                'identificacion' => 'cedula',
                'telefono_principal' => 'telefono'
            ];

            if (!empty($datosLimpios['apellido'])) {
                $reglasValidacion['apellido'] = 'apellido';
            }

            if (!empty($datosLimpios['correo_electronico'])) {
                $reglasValidacion['correo_electronico'] = 'email';
            }

            if (!empty($datosLimpios['direccion'])) {
                $reglasValidacion['direccion'] = 'direccion';
            }

            if (!empty($datosLimpios['genero'])) {
                $reglasValidacion['genero'] = 'genero';

                // VALIDACIÓN DE SEGURIDAD: Whitelist de géneros permitidos
                $generosPermitidos = ['MASCULINO', 'FEMENINO', 'OTRO'];
                if (!in_array($datosLimpios['genero'], $generosPermitidos, true)) {
                    $arrResponse = array(
                        'status' => false,
                        'message' => 'Género inválido. Valor recibido: ' . htmlspecialchars($datosLimpios['genero']) . '. Los valores permitidos son: MASCULINO, FEMENINO, OTRO.'
                    );
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }
            }

            $resultadosValidacion = ExpresionesRegulares::validarCampos($datosLimpios, $reglasValidacion);

            $errores = [];
            foreach ($resultadosValidacion as $campo => $resultado) {
                if (!$resultado['valido']) {
                    $errores[] = ExpresionesRegulares::obtenerMensajeError($campo, $reglasValidacion[$campo]);
                }
            }

            if (!empty($errores)) {
                $arrResponse = array(
                    'status' => false,
                    'message' => 'Errores de validación: ' . implode(' | ', $errores)
                );
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            if (!empty($datosLimpios['fecha_nacimiento'])) {
                $fechaOriginal = $datosLimpios['fecha_nacimiento'];

                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaOriginal)) {
                    $arrResponse = array(
                        'status' => false,
                        'message' => 'El formato de fecha de nacimiento debe ser YYYY-MM-DD'
                    );
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $fechaNacimiento = DateTime::createFromFormat('Y-m-d', $fechaOriginal);
                if (!$fechaNacimiento) {
                    $arrResponse = array(
                        'status' => false,
                        'message' => 'Formato de fecha inválido'
                    );
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $fechaHoy = new DateTime();

                if ($fechaNacimiento > $fechaHoy) {
                    $arrResponse = array(
                        'status' => false,
                        'message' => 'La fecha de nacimiento no puede ser posterior a la fecha actual'
                    );
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $fechaMinima = clone $fechaHoy;
                $fechaMinima->sub(new DateInterval('P18Y'));

                if ($fechaNacimiento > $fechaMinima) {
                    $edad = $fechaHoy->diff($fechaNacimiento)->y;
                    $arrResponse = array(
                        'status' => false,
                        'message' => "La persona debe ser mayor de 18 años. Edad actual: {$edad} años"
                    );
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $datosLimpios['fecha_nacimiento'] = $fechaOriginal;
            } else {
                $datosLimpios['fecha_nacimiento'] = null;
            }

            $arrData = array(
                'nombre' => $datosLimpios['nombre'],
                'apellido' => $datosLimpios['apellido'],
                'identificacion' => $datosLimpios['identificacion'],
                'telefono_principal' => $datosLimpios['telefono_principal'],
                'correo_electronico' => $datosLimpios['correo_electronico'],
                'direccion' => $datosLimpios['direccion'],
                'fecha_nacimiento' => $datosLimpios['fecha_nacimiento'],
                'genero' => $datosLimpios['genero'],
                'observaciones' => $datosLimpios['observaciones']
            );

            $idusuario = obtenerUsuarioSesion();

            if (!$idusuario) {
                error_log("ERROR: No se encontró ID de usuario en la sesión durante createProveedor()");
                $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $objProveedor = getProveedoresModel();
            $arrResponse = $objProveedor->insertProveedor($arrData);

            if ($arrResponse['status'] === true) {
                $resultadoBitacora = registrarEnBitacora('proveedor', 'INSERTAR', $idusuario);

                if (!$resultadoBitacora) {
                    error_log("Warning: No se pudo registrar en bitácora la creación del proveedor ID: " .
                        ($arrResponse['proveedor_id'] ?? 'desconocido'));
                }
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error en createProveedor: " . $e->getMessage());
            $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Obtener listado de proveedores
 */
function proveedores_getProveedoresData()
{
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        try {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Proveedores', 'ver')) {
                $response = array('status' => false, 'message' => 'No tienes permisos para ver proveedores', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                die();
            }

            // Obtener ID del usuario actual
            $idusuario = obtenerUsuarioSesion();

            // Obtener proveedores (activos para usuarios normales, todos para super usuarios)
            $objProveedor = getProveedoresModel();
            $arrResponse = $objProveedor->selectAllProveedores($idusuario);

            if ($arrResponse['status']) {
                registrarEnBitacora('Proveedores', 'CONSULTA_LISTADO', $idusuario);
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error en getProveedoresData: " . $e->getMessage());
            $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Obtener un proveedor por ID
 */
function proveedores_getProveedorById($idproveedor)
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        if (empty($idproveedor) || !is_numeric($idproveedor)) {
            $arrResponse = array('status' => false, 'message' => 'ID de proveedor inválido');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $objProveedor = getProveedoresModel();
            $arrData = $objProveedor->selectProveedorById(intval($idproveedor));
            if (!empty($arrData)) {
                $arrResponse = array('status' => true, 'data' => $arrData);
            } else {
                $arrResponse = array('status' => false, 'message' => 'Proveedor no encontrado');
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error en getProveedorById: " . $e->getMessage());
            $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Actualizar un proveedor
 */
function proveedores_updateProveedor()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            $postdata = file_get_contents('php://input');
            $request = json_decode($postdata, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $intIdProveedor = intval($request['idproveedor'] ?? 0);
            if ($intIdProveedor <= 0) {
                $arrResponse = array('status' => false, 'message' => 'ID de proveedor inválido');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $datosLimpios = [
                'nombre' => ExpresionesRegulares::limpiar($request['nombre'] ?? '', 'nombre'),
                'apellido' => ExpresionesRegulares::limpiar($request['apellido'] ?? '', 'apellido'),
                'identificacion' => ExpresionesRegulares::limpiar($request['identificacion'] ?? '', 'cedula'),
                'telefono_principal' => ExpresionesRegulares::limpiar($request['telefono_principal'] ?? '', 'telefono'),
                'correo_electronico' => ExpresionesRegulares::limpiar($request['correo_electronico'] ?? '', 'email'),
                'direccion' => trim($request['direccion'] ?? ''),
                'fecha_nacimiento' => $request['fecha_nacimiento'] ?? null,
                'genero' => strtoupper(trim($request['genero'] ?? '')),
                'observaciones' => trim($request['observaciones'] ?? '')
            ];

            $camposObligatorios = ['nombre', 'identificacion', 'telefono_principal'];
            foreach ($camposObligatorios as $campo) {
                if (empty($datosLimpios[$campo])) {
                    $arrResponse = array('status' => false, 'message' => 'Todos los campos obligatorios deben ser completados');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }
            }

            $reglasValidacion = [
                'nombre' => 'nombre',
                'identificacion' => 'cedula',
                'telefono_principal' => 'telefono'
            ];

            if (!empty($datosLimpios['apellido'])) {
                $reglasValidacion['apellido'] = 'apellido';
            }

            if (!empty($datosLimpios['correo_electronico'])) {
                $reglasValidacion['correo_electronico'] = 'email';
            }

            if (!empty($datosLimpios['direccion'])) {
                $reglasValidacion['direccion'] = 'direccion';
            }

            if (!empty($datosLimpios['genero'])) {
                $reglasValidacion['genero'] = 'genero';

                // VALIDACIÓN DE SEGURIDAD: Whitelist de géneros permitidos
                $generosPermitidos = ['MASCULINO', 'FEMENINO', 'OTRO'];
                if (!in_array($datosLimpios['genero'], $generosPermitidos, true)) {
                    $arrResponse = array(
                        'status' => false,
                        'message' => 'Género inválido. Valor recibido: ' . htmlspecialchars($datosLimpios['genero']) . '. Los valores permitidos son: MASCULINO, FEMENINO, OTRO.'
                    );
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }
            }

            $resultadosValidacion = ExpresionesRegulares::validarCampos($datosLimpios, $reglasValidacion);

            $errores = [];
            foreach ($resultadosValidacion as $campo => $resultado) {
                if (!$resultado['valido']) {
                    $errores[] = ExpresionesRegulares::obtenerMensajeError($campo, $reglasValidacion[$campo]);
                }
            }

            if (!empty($errores)) {
                $arrResponse = array(
                    'status' => false,
                    'message' => 'Errores de validación: ' . implode(' | ', $errores)
                );
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            if (!empty($datosLimpios['fecha_nacimiento'])) {
                $fechaOriginal = $datosLimpios['fecha_nacimiento'];

                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaOriginal)) {
                    $arrResponse = array(
                        'status' => false,
                        'message' => 'El formato de fecha de nacimiento debe ser YYYY-MM-DD'
                    );
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $fechaNacimiento = DateTime::createFromFormat('Y-m-d', $fechaOriginal);
                if (!$fechaNacimiento) {
                    $arrResponse = array(
                        'status' => false,
                        'message' => 'Formato de fecha inválido'
                    );
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $fechaHoy = new DateTime();

                if ($fechaNacimiento > $fechaHoy) {
                    $arrResponse = array(
                        'status' => false,
                        'message' => 'La fecha de nacimiento no puede ser posterior a la fecha actual'
                    );
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $fechaMinima = clone $fechaHoy;
                $fechaMinima->sub(new DateInterval('P18Y'));

                if ($fechaNacimiento > $fechaMinima) {
                    $edad = $fechaHoy->diff($fechaNacimiento)->y;
                    $arrResponse = array(
                        'status' => false,
                        'message' => "La persona debe ser mayor de 18 años. Edad actual: {$edad} años"
                    );
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $datosLimpios['fecha_nacimiento'] = $fechaOriginal;
            } else {
                $datosLimpios['fecha_nacimiento'] = null;
            }

            $arrData = array(
                'nombre' => $datosLimpios['nombre'],
                'apellido' => $datosLimpios['apellido'],
                'identificacion' => $datosLimpios['identificacion'],
                'telefono_principal' => $datosLimpios['telefono_principal'],
                'correo_electronico' => $datosLimpios['correo_electronico'],
                'direccion' => $datosLimpios['direccion'],
                'fecha_nacimiento' => $datosLimpios['fecha_nacimiento'],
                'genero' => $datosLimpios['genero'],
                'observaciones' => $datosLimpios['observaciones']
            );

            $idusuario = obtenerUsuarioSesion();

            if (!$idusuario) {
                error_log("ERROR: No se encontró ID de usuario en la sesión durante updateProveedor()");
                $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $objProveedor = getProveedoresModel();
            $arrResponse = $objProveedor->updateProveedor($intIdProveedor, $arrData);

            if ($arrResponse['status'] === true) {
                $resultadoBitacora = registrarEnBitacora('proveedor', 'ACTUALIZAR', $idusuario);

                if (!$resultadoBitacora) {
                    error_log("Warning: No se pudo registrar en bitácora la actualización del proveedor ID: " . $intIdProveedor);
                }
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error en updateProveedor: " . $e->getMessage());
            $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Eliminar (desactivar) un proveedor
 */
function proveedores_deleteProveedor()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            $postdata = file_get_contents('php://input');
            $request = json_decode($postdata, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $intIdProveedor = intval($request['idproveedor'] ?? 0);
            if ($intIdProveedor <= 0) {
                $arrResponse = array('status' => false, 'message' => 'ID de proveedor inválido');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $idusuario = obtenerUsuarioSesion();
            $objProveedor = getProveedoresModel();
            $requestDelete = $objProveedor->deleteProveedorById($intIdProveedor);
            if ($requestDelete) {
                $arrResponse = array('status' => true, 'message' => 'Proveedor desactivado correctamente');
            } else {
                $arrResponse = array('status' => false, 'message' => 'Error al desactivar el proveedor');
            }
            if ($arrResponse['status'] === true) {
                $resultadoBitacora = registrarEnBitacora('proveedor', 'ELIMINAR', $idusuario);

                if (!$resultadoBitacora) {
                    error_log("Warning: No se pudo registrar en bitácora la actualización del proveedor ID: " .
                        ($arrResponse['proveedor_id'] ?? 'desconocido'));
                }
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error en deleteProveedor: " . $e->getMessage());
            $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Obtener proveedores activos
 */
function proveedores_getProveedoresActivos()
{
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        try {
            $objProveedor = getProveedoresModel();
            $arrResponse = $objProveedor->selectProveedoresActivos();
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error en getProveedoresActivos: " . $e->getMessage());
            $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Activar un proveedor
 */
function proveedores_activarProveedor()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            $postdata = file_get_contents('php://input');
            $request = json_decode($postdata, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $intIdProveedor = intval($request['idproveedor'] ?? 0);
            if ($intIdProveedor <= 0) {
                $arrResponse = array('status' => false, 'message' => 'ID de proveedor inválido');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $idusuario = obtenerUsuarioSesion();
            $objProveedor = getProveedoresModel();
            $requestActivar = $objProveedor->reactivarProveedor($intIdProveedor);
            if ($requestActivar) {
                $arrResponse = array('status' => true, 'message' => 'Proveedor activado correctamente');
            } else {
                $arrResponse = array('status' => false, 'message' => 'Error al activar el proveedor');
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error en activarProveedor: " . $e->getMessage());
            $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Exportar proveedores
 */
function proveedores_exportarProveedores()
{
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        try {
            $objProveedor = getProveedoresModel();
            $arrData = $objProveedor->selectAllProveedores();

            if ($arrData['status']) {
                $data['proveedores'] = $arrData['data'];
                $data['page_title'] = "Reporte de Proveedores";
                $data['fecha_reporte'] = date('d/m/Y H:i:s');

                $arrResponse = array('status' => true, 'message' => 'Datos preparados para exportación', 'data' => $data);
            } else {
                $arrResponse = array('status' => false, 'message' => 'No se pudieron obtener los datos');
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error en exportarProveedores: " . $e->getMessage());
            $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Buscar proveedores
 */
function proveedores_buscarProveedor()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            $postdata = file_get_contents('php://input');
            $request = json_decode($postdata, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $strTermino = ExpresionesRegulares::limpiar($request['termino'] ?? '');
            if (empty($strTermino)) {
                $arrResponse = array('status' => false, 'message' => 'Término de búsqueda requerido');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $objProveedor = getProveedoresModel();
            $arrData = $objProveedor->buscarProveedores($strTermino);
            if ($arrData['status']) {
                $arrResponse = array('status' => true, 'data' => $arrData['data']);
            } else {
                $arrResponse = array('status' => false, 'message' => 'No se encontraron resultados');
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error en buscarProveedor: " . $e->getMessage());
            $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Reactivar un proveedor (solo super usuarios)
 */
function proveedores_reactivarProveedor()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            $idusuarioSesion = obtenerUsuarioSesion();

            // Solo super usuarios pueden reactivar proveedores
            $objProveedor = getProveedoresModel();
            $esSuperUsuario = $objProveedor->verificarEsSuperUsuario($idusuarioSesion);

            if (!$esSuperUsuario) {
                $arrResponse = array('status' => false, 'message' => 'Solo los super usuarios pueden reactivar proveedores');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Proveedores', 'editar')) {
                $arrResponse = array('status' => false, 'message' => 'No tienes permisos para reactivar proveedores');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (empty($data['idproveedor']) || !is_numeric($data['idproveedor'])) {
                $arrResponse = array('status' => false, 'message' => 'ID de proveedor inválido');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $idproveedor = intval($data['idproveedor']);

            $arrResponse = $objProveedor->reactivarProveedor($idproveedor);

            if ($arrResponse['status']) {
                registrarEnBitacora('Proveedores', 'REACTIVAR', $idusuarioSesion, "Proveedor ID: $idproveedor reactivado");
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error en reactivarProveedor: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $arrResponse = array('status' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage());
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Verificar si el usuario actual es super usuario
 */
function proveedores_verificarSuperUsuario()
{
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        try {
            // Verificar si la sesión está iniciada (necesario en algunos contextos)
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
                ]);
                die();
            }

            $objProveedor = getProveedoresModel();
            $esSuperAdmin = $objProveedor->verificarEsSuperUsuario($idusuario);

            echo json_encode([
                'status' => true,
                'es_super_usuario' => $esSuperAdmin,
                'usuario_id' => $idusuario,
                'message' => 'Verificación completada'
            ]);
        } catch (Exception $e) {
            error_log("Error en verificarSuperUsuario: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode([
                'status' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage(),
                'es_super_usuario' => false,
                'usuario_id' => 0
            ]);
        }
        die();
    }
}