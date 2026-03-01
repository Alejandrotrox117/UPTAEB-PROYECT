<?php

use App\Models\EmpleadosModel;
use App\Models\BitacoraModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\PermisosModuloVerificar;
use App\Helpers\Validation\ExpresionesRegulares;

// =============================================================================
// FUNCIONES AUXILIARES DEL CONTROLADOR
// =============================================================================

/**
 * Obtiene el modelo de empleados
 */
function getEmpleadosModel()
{
    return new EmpleadosModel();
}

/**
 * Renderiza una vista de empleados
 */
function renderEmpleadosView($view, $data = [])
{
    renderView('empleados', $view, $data);
}

// =============================================================================
// FUNCIONES PÚBLICAS DEL CONTROLADOR
// =============================================================================

/**
 * Página principal del módulo de empleados
 */
function empleados_index()
{
    // Verificar autenticación
    if (!obtenerUsuarioSesion()) {
        header('Location: ' . base_url() . '/login');
        die();
    }

    // Verificar acceso al módulo
    if (!PermisosModuloVerificar::verificarAccesoModulo('empleados')) {
        renderEmpleadosView("permisos");
        exit();
    }

    $data['page_title'] = "Gestión de Empleados";
    $data['page_name'] = "empleados";
    $data['page_functions_js'] = "functions_empleado.js";
    renderEmpleadosView("empleados", $data);
}

/**
 * Obtener listado de empleados
 */
function empleados_getEmpleadoData()
{
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        try {
            error_log("=== getEmpleadoData llamado ===");

            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Empleados', 'ver')) {
                $response = array('status' => false, 'message' => 'No tienes permisos para ver empleados', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                die();
            }

            // Obtener ID del usuario actual
            $idusuario = obtenerUsuarioSesion();
            error_log("Usuario ID obtenido: " . ($idusuario ?: 'NULL'));

            // Obtener empleados (activos para usuarios normales, todos para super usuarios)
            $objEmpleados = getEmpleadosModel();
            $arrResponse = $objEmpleados->selectAllEmpleados($idusuario);

            error_log("Empleados encontrados: " . count($arrResponse['data']));

            if ($arrResponse['status']) {
                registrarEnBitacora('Empleados', 'CONSULTA_LISTADO', $idusuario);
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error en getEmpleadoData: " . $e->getMessage());
            $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Crear un nuevo empleado
 */
function empleados_createEmpleado()
{
    try {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$data || !is_array($data)) {
            echo json_encode(["status" => false, "message" => "No se recibieron datos válidos."]);
            exit();
        }

        $nombre = ExpresionesRegulares::limpiar($data['nombre'] ?? '', 'nombre');
        $apellido = ExpresionesRegulares::limpiar($data['apellido'] ?? '', 'apellido');
        $identificacion = ExpresionesRegulares::limpiar($data['identificacion'] ?? '', 'cedula');
        $tipo_empleado = trim($data['tipo_empleado'] ?? 'OPERARIO');
        $fecha_nacimiento = trim($data['fecha_nacimiento'] ?? '');
        $direccion = trim($data['direccion'] ?? '');
        $correo_electronico = trim($data['correo_electronico'] ?? '');
        $estatus = trim($data['estatus'] ?? 'activo');
        $telefono_principal = trim($data['telefono_principal'] ?? '');
        $observaciones = trim($data['observaciones'] ?? '');
        $genero = trim($data['genero'] ?? '');
        $fecha_inicio = trim($data['fecha_inicio'] ?? '');
        $fecha_fin = trim($data['fecha_fin'] ?? '');
        $puesto = trim($data['puesto'] ?? '');
        $salario = trim($data['salario'] ?? '0.00');

        if (empty($nombre) || empty($apellido) || empty($identificacion)) {
            echo json_encode(["status" => false, "message" => "Datos incompletos. Por favor, llena todos los campos obligatorios."]);
            exit();
        }

        // Validar formato con expresiones regulares
        $reglasValidacion = [
            'nombre' => 'nombre',
            'apellido' => 'apellido',
            'identificacion' => 'cedula'
        ];

        $datosAValidar = [
            'nombre' => $nombre,
            'apellido' => $apellido,
            'identificacion' => $identificacion
        ];

        $resultadosValidacion = ExpresionesRegulares::validarCampos($datosAValidar, $reglasValidacion);

        $errores = [];
        foreach ($resultadosValidacion as $campo => $resultado) {
            if (!$resultado['valido']) {
                $errores[] = ExpresionesRegulares::obtenerMensajeError($campo, $reglasValidacion[$campo]);
            }
        }

        if (!empty($errores)) {
            echo json_encode(["status" => false, "message" => "Errores de validación: " . implode(' | ', $errores)]);
            exit();
        }

        $objEmpleados = getEmpleadosModel();
        $insertData = $objEmpleados->insertEmpleado([
            "nombre" => $nombre,
            "apellido" => $apellido,
            "identificacion" => $identificacion,
            "tipo_empleado" => $tipo_empleado,
            "fecha_nacimiento" => $fecha_nacimiento,
            "direccion" => $direccion,
            "correo_electronico" => $correo_electronico,
            "estatus" => $estatus,
            "telefono_principal" => $telefono_principal,
            "observaciones" => $observaciones,
            "genero" => $genero,
            "fecha_inicio" => $fecha_inicio,
            "fecha_fin" => $fecha_fin,
            "puesto" => $puesto,
            "salario" => $salario,
        ]);

        if ($insertData) {
            echo json_encode(["status" => true, "message" => "Empleado registrado correctamente."]);
        } else {
            echo json_encode(["status" => false, "message" => "Error al registrar el empleado. Intenta nuevamente."]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
    }
    exit();
}

/**
 * Eliminar (desactivar) un empleado
 */
function empleados_deleteEmpleado()
{
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $idempleado = trim($data['idempleado']) ?? null;

    if (empty($idempleado)) {
        $response = ["status" => false, "message" => "ID de empleado no proporcionado."];
        echo json_encode($response);
        return;
    }

    $objEmpleados = getEmpleadosModel();
    $deleteData = $objEmpleados->deleteEmpleado($idempleado);

    if ($deleteData) {
        $response = ["status" => true, "message" => "Empleado desactivado correctamente."];
    } else {
        $response = ["status" => false, "message" => "Error al desactivar el empleado. Intenta nuevamente."];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Actualizar un empleado
 */
function empleados_updateEmpleado()
{
    try {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $idempleado = trim($data['idempleado'] ?? '');
        $nombre = ExpresionesRegulares::limpiar($data['nombre'] ?? '', 'nombre');
        $apellido = ExpresionesRegulares::limpiar($data['apellido'] ?? '', 'apellido');
        $identificacion = ExpresionesRegulares::limpiar($data['identificacion'] ?? '', 'cedula');
        $tipo_empleado = trim($data['tipo_empleado'] ?? 'OPERARIO');
        $fecha_nacimiento = trim($data['fecha_nacimiento'] ?? '');
        $direccion = trim($data['direccion'] ?? '');
        $correo_electronico = trim($data['correo_electronico'] ?? '');
        $estatus = trim($data['estatus'] ?? 'activo');
        $telefono_principal = trim($data['telefono_principal'] ?? '');
        $observaciones = trim($data['observaciones'] ?? '');
        $genero = trim($data['genero'] ?? '');
        $fecha_modificacion = date('Y-m-d H:i:s');
        $fecha_inicio = trim($data['fecha_inicio'] ?? '');
        $fecha_fin = trim($data['fecha_fin'] ?? '');
        $puesto = trim($data['puesto'] ?? '');
        $salario = trim($data['salario'] ?? '0.00');

        if (empty($idempleado) || empty($nombre) || empty($apellido) || empty($identificacion)) {
            echo json_encode(["status" => false, "message" => "Datos incompletos. Nombre, apellido e identificación son obligatorios."]);
            exit();
        }

        // Validar formato con expresiones regulares
        $reglasValidacion = [
            'nombre' => 'nombre',
            'apellido' => 'apellido',
            'identificacion' => 'cedula'
        ];

        $datosAValidar = [
            'nombre' => $nombre,
            'apellido' => $apellido,
            'identificacion' => $identificacion
        ];

        $resultadosValidacion = ExpresionesRegulares::validarCampos($datosAValidar, $reglasValidacion);

        $errores = [];
        foreach ($resultadosValidacion as $campo => $resultado) {
            if (!$resultado['valido']) {
                $errores[] = ExpresionesRegulares::obtenerMensajeError($campo, $reglasValidacion[$campo]);
            }
        }

        if (!empty($errores)) {
            echo json_encode(["status" => false, "message" => "Errores de validación: " . implode(' | ', $errores)]);
            exit();
        }

        if (!empty($correo_electronico) && !filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["status" => false, "message" => "El correo electrónico no es válido."]);
            exit();
        }

        $objEmpleados = getEmpleadosModel();
        $updateData = $objEmpleados->updateEmpleado([
            "idempleado" => $idempleado,
            "nombre" => $nombre,
            "apellido" => $apellido,
            "identificacion" => $identificacion,
            "tipo_empleado" => $tipo_empleado,
            "fecha_nacimiento" => $fecha_nacimiento,
            "direccion" => $direccion,
            "correo_electronico" => $correo_electronico,
            "estatus" => $estatus,
            "telefono_principal" => $telefono_principal,
            "observaciones" => $observaciones,
            "genero" => $genero,
            "fecha_modificacion" => $fecha_modificacion,
            "fecha_inicio" => $fecha_inicio,
            "fecha_fin" => $fecha_fin,
            "puesto" => $puesto,
            "salario" => $salario,
        ]);

        if ($updateData) {
            echo json_encode(["status" => true, "message" => "Empleado actualizado correctamente."]);
        } else {
            echo json_encode(["status" => false, "message" => "Error al actualizar el empleado. Intenta nuevamente."]);
        }
    } catch (PDOException $e) {
        $errorMsg = $e->getMessage();
        if (strpos($errorMsg, 'tipo_empleado') !== false || strpos($errorMsg, 'Unknown column') !== false) {
            echo json_encode([
                "status" => false,
                "message" => "Error: El campo 'tipo_empleado' no existe en la base de datos. Debes ejecutar la migración SQL."
            ]);
        } else {
            echo json_encode([
                "status" => false,
                "message" => "Error de base de datos: " . $errorMsg
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
    }
    exit();
}

/**
 * Obtener un empleado por ID
 */
function empleados_getEmpleadoById($idempleado)
{
    try {
        // Si $idempleado es un array, tomar el primer elemento
        if (is_array($idempleado)) {
            $idempleado = $idempleado[0];
        }

        // Validar que sea un número
        if (!is_numeric($idempleado)) {
            echo json_encode(["status" => false, "message" => "ID de empleado inválido."]);
            exit();
        }

        $objEmpleados = getEmpleadosModel();
        $empleado = $objEmpleados->getEmpleadoById($idempleado);

        if ($empleado) {
            echo json_encode(["status" => true, "data" => $empleado]);
        } else {
            echo json_encode([
                "status" => false,
                "message" => "Empleado no encontrado con ID: {$idempleado}"
            ]);
        }
    } catch (PDOException $e) {
        $errorMsg = $e->getMessage();
        if (strpos($errorMsg, 'tipo_empleado') !== false) {
            echo json_encode([
                "status" => false,
                "message" => "Error: El campo 'tipo_empleado' no existe en la base de datos. Debes ejecutar la migración SQL."
            ]);
        } else {
            echo json_encode([
                "status" => false,
                "message" => "Error de base de datos: " . $errorMsg
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
    }
    exit();
}

/**
 * Reactivar un empleado (solo super usuarios)
 */
function empleados_reactivarEmpleado()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            error_log("=== Iniciando reactivarEmpleado ===");

            $idusuarioSesion = obtenerUsuarioSesion();
            error_log("Usuario de sesión: " . ($idusuarioSesion ?: 'NULL'));

            // Solo super usuarios pueden reactivar empleados
            $objEmpleados = getEmpleadosModel();
            $esSuperUsuario = $objEmpleados->verificarEsSuperUsuario($idusuarioSesion);
            error_log("Es super usuario: " . ($esSuperUsuario ? 'SÍ' : 'NO'));

            if (!$esSuperUsuario) {
                error_log("Acceso denegado - no es super usuario");
                $arrResponse = array('status' => false, 'message' => 'Solo los super usuarios pueden reactivar empleados');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('Empleados', 'editar')) {
                error_log("Acceso denegado - sin permisos de editar");
                $arrResponse = array('status' => false, 'message' => 'No tienes permisos para reactivar empleados');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            error_log("Datos recibidos: " . print_r($data, true));

            if (empty($data['idempleado']) || !is_numeric($data['idempleado'])) {
                error_log("ID de empleado inválido: " . print_r($data['idempleado'] ?? 'NULL', true));
                $arrResponse = array('status' => false, 'message' => 'ID de empleado inválido');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $idempleado = intval($data['idempleado']);
            error_log("Intentando reactivar empleado ID: $idempleado");

            $arrResponse = $objEmpleados->reactivarEmpleado($idempleado);
            error_log("Resultado del modelo: " . print_r($arrResponse, true));

            if ($arrResponse['status']) {
                registrarEnBitacora('Empleados', 'REACTIVAR', $idusuarioSesion, "Empleado ID: $idempleado reactivado");
                error_log("Acción registrada en bitácora");
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error en reactivarEmpleado: " . $e->getMessage());
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
function empleados_verificarSuperUsuario()
{
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        try {
            error_log("=== Iniciando verificarSuperUsuario en Empleados controller ===");

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
                error_log("Sesión iniciada en verificarSuperUsuario");
            } else {
                error_log("Sesión ya estaba iniciada");
            }

            error_log("Contenido de _SESSION: " . print_r($_SESSION, true));

            $idusuario = obtenerUsuarioSesion();
            error_log("obtenerUsuarioSesion devolvió usuario ID: " . ($idusuario ?: 'NULL'));

            if (!$idusuario) {
                error_log("Usuario no autenticado - obtenerUsuarioSesion no devolvió usuario");
                echo json_encode([
                    'status' => false,
                    'message' => 'Usuario no autenticado',
                    'es_super_usuario' => false,
                    'usuario_id' => 0,
                    'debug_session' => $_SESSION
                ]);
                die();
            }

            error_log("Verificando usuario ID: $idusuario con esSuperAdmin");

            $objEmpleados = getEmpleadosModel();
            $esSuperAdmin = $objEmpleados->verificarEsSuperUsuario($idusuario);

            error_log("Resultado esSuperAdmin: " . ($esSuperAdmin ? 'SÍ' : 'NO'));

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

?>