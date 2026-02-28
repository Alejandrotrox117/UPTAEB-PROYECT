<?php

require_once __DIR__ . '/../../helpers/controller_helpers.php';

use App\Models\PersonasModel;
use App\Models\BitacoraModel;
use App\Models\UsuariosModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\PermisosModuloVerificar;

// =============================================================================
// FUNCIONES AUXILIARES DEL CONTROLADOR
// =============================================================================

/**
 * Obtiene una instancia del modelo PersonasModel.
 */
function getPersonasModel()
{
    return new PersonasModel();
}

/**
 * Obtiene una instancia del modelo BitacoraModel.
 */
function getPersonasBitacoraModel()
{
    return new BitacoraModel();
}

/**
 * Renderiza una vista del módulo Personas.
 */
function renderPersonasView(string $view, array $data = [])
{
    renderView('personas', $view, $data);
}

// =============================================================================
// FUNCIONES PÚBLICAS DEL CONTROLADOR
// =============================================================================

/**
 * Vista principal del módulo Personas.
 */
function personas_index()
{
    if (!obtenerUsuarioSesion()) {
        header('Location: ' . base_url() . '/login');
        die();
    }

    if (!PermisosModuloVerificar::verificarAccesoModulo('personas')) {
        renderPersonasView("permisos");
        exit();
    }

    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('personas', 'ver')) {
        renderPersonasView("permisos");
        exit();
    }

    $idusuario = obtenerUsuarioSesion();
    $bitacoraModel = getPersonasBitacoraModel();
    BitacoraHelper::registrarAccesoModulo('personas', $idusuario, $bitacoraModel);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $data['page_tag'] = "Personas";
    $data['page_title'] = "Gestión de Personas";
    $data['page_name'] = "Personas";
    $data['page_content'] = "Gestión integral de personas del sistema";
    $data['page_functions_js'] = "functions_personas.js";
    $data['rolUsuarioAutenticado'] = $_SESSION['rol_nombre'] ?? '';
    $data['idRolUsuarioAutenticado'] = $_SESSION['rol_id'] ?? 0;
    renderPersonasView("personas", $data);
}

/**
 * Obtiene todas las personas (JSON).
 */
function personas_getPersonasData()
{
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        try {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('personas', 'ver')) {
                echo json_encode(['status' => false, 'message' => 'No tienes permisos para ver las personas'], JSON_UNESCAPED_UNICODE);
                die();
            }
            $model = getPersonasModel();
            $arrData = $model->selectAllPersonasActivas();
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            error_log("Error en getPersonasData: " . $e->getMessage());
            echo json_encode(['status' => false, 'message' => 'Error interno del servidor'], JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Crea una nueva persona (con usuario opcional).
 */
function personas_createPersona()
{
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        echo json_encode(['status' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
        die();
    }

    $idusuario = obtenerUsuarioSesion();
    $bitacoraModel = getPersonasBitacoraModel();

    try {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('personas', 'crear')) {
            BitacoraHelper::registrarError('personas', 'Intento de crear persona sin permisos', $idusuario, $bitacoraModel);
            echo json_encode(['status' => false, 'message' => 'No tienes permisos para crear personas'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Datos JSON inválidos');
        }

        $personaData = [
            'nombre' => trim($data['persona']['nombre']),
            'apellido' => trim($data['persona']['apellido']),
            'cedula' => trim($data['persona']['identificacion']),
            'genero' => $data['persona']['genero'] ?? null,
            'fecha_nacimiento' => $data['persona']['fecha_nacimiento'] ?: null,
            'correo_electronico_persona' => trim($data['persona']['correo_electronico'] ?? ''),
            'direccion' => trim($data['persona']['direccion'] ?? ''),
            'observaciones' => trim($data['persona']['observaciones'] ?? ''),
            'telefono_principal' => trim($data['persona']['telefono_principal']),
            'crear_usuario' => $data['crear_usuario_flag'] ?? '0',
            'correo_electronico_usuario' => trim($data['usuario']['correo_login'] ?? ''),
            'clave_usuario' => $data['usuario']['clave'] ?? '',
            'idrol_usuario' => $data['usuario']['idrol'] ?? null
        ];

        $model = getPersonasModel();

        // Validar correo duplicado si se va a crear usuario
        if (($personaData['crear_usuario'] ?? '0') === '1' && !empty($personaData['correo_electronico_usuario'])) {
            if ($model->existeCorreoUsuario($personaData['correo_electronico_usuario'])) {
                echo json_encode(['status' => false, 'message' => 'Ya existe un usuario registrado con ese correo electrónico.'], JSON_UNESCAPED_UNICODE);
                die();
            }
        }

        $request = $model->insertPersonaConUsuario($personaData);

        if (isset($request['status']) && $request['status'] === true) {
            $detalle = "Persona creada: " . trim($data['persona']['nombre']) . " " . trim($data['persona']['apellido']);
            BitacoraHelper::registrarAccion('personas', 'CREAR_PERSONA', $idusuario, $bitacoraModel, $detalle);
        }

        echo json_encode($request, JSON_UNESCAPED_UNICODE);

    } catch (\Exception $e) {
        error_log("Error en createPersona: " . $e->getMessage());
        BitacoraHelper::registrarError('personas', $e->getMessage(), $idusuario, $bitacoraModel);
        echo json_encode(['status' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Obtiene una persona por su ID (JSON).
 */
function personas_getPersonaById(int $idpersona_pk)
{
    if ($idpersona_pk > 0) {
        $model = getPersonasModel();
        $arrData = $model->selectPersonaById($idpersona_pk);
        if (empty($arrData)) {
            $response = ["status" => false, "message" => "Datos no encontrados."];
        } else {
            if (!empty($arrData['persona_fecha'])) {
                $arrData['fecha_nacimiento_formato'] = date('Y-m-d', strtotime($arrData['persona_fecha']));
            } else {
                $arrData['fecha_nacimiento_formato'] = '';
            }
            $response = ["status" => true, "data" => $arrData];
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Actualiza una persona (con usuario opcional).
 */
function personas_updatePersona()
{
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        echo json_encode(['status' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
        die();
    }

    $idusuario = obtenerUsuarioSesion();
    $bitacoraModel = getPersonasBitacoraModel();

    try {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('personas', 'editar')) {
            BitacoraHelper::registrarError('personas', 'Intento de editar persona sin permisos', $idusuario, $bitacoraModel);
            echo json_encode(['status' => false, 'message' => 'No tienes permisos para editar personas'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            echo json_encode(['status' => false, 'message' => 'Datos no válidos'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $idPersona = intval($input['idpersona_pk'] ?? 0);
        if ($idPersona <= 0) {
            echo json_encode(['status' => false, 'message' => 'ID de persona no válido'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $dataParaModelo = [];

        if (isset($input['persona'])) {
            $dataParaModelo = array_merge($dataParaModelo, [
                'nombre' => trim($input['persona']['nombre'] ?? ''),
                'apellido' => trim($input['persona']['apellido'] ?? ''),
                'identificacion' => trim($input['persona']['identificacion'] ?? ''),
                'genero' => trim($input['persona']['genero'] ?? ''),
                'fecha_nacimiento' => trim($input['persona']['fecha_nacimiento'] ?? ''),
                'correo_electronico_persona' => trim($input['persona']['correo_electronico'] ?? ''),
                'direccion' => trim($input['persona']['direccion'] ?? ''),
                'observaciones' => trim($input['persona']['observaciones'] ?? ''),
                'telefono_principal' => trim($input['persona']['telefono_principal'] ?? ''),
            ]);
        }

        if (isset($input['actualizar_usuario_flag']) && $input['actualizar_usuario_flag'] == "1" && isset($input['usuario'])) {
            $dataParaModelo = array_merge($dataParaModelo, [
                'actualizar_usuario' => "1",
                'correo_electronico_usuario' => trim($input['usuario']['correo_electronico_usuario'] ?? ''),
                'clave_usuario' => trim($input['usuario']['clave_usuario'] ?? ''),
                'idrol_usuario' => trim($input['usuario']['idrol_usuario'] ?? ''),
            ]);
        }

        $model = getPersonasModel();
        $resultado = $model->updatePersonaConUsuario($idPersona, $dataParaModelo);

        if (isset($resultado['status']) && $resultado['status'] === true) {
            $detalle = "Persona actualizada con ID: " . $idPersona;
            BitacoraHelper::registrarAccion('personas', 'ACTUALIZAR_PERSONA', $idusuario, $bitacoraModel, $detalle, $idPersona);
        }

        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

    } catch (\Exception $e) {
        error_log("Error en updatePersona: " . $e->getMessage());
        BitacoraHelper::registrarError('personas', $e->getMessage(), $idusuario, $bitacoraModel);
        echo json_encode([
            'status' => false,
            'message' => 'Error interno del servidor'
        ], JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Desactiva (eliminación lógica) una persona.
 */
function personas_deletePersona()
{
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        echo json_encode(['status' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
        die();
    }

    $idusuario = obtenerUsuarioSesion();
    $bitacoraModel = getPersonasBitacoraModel();

    try {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('personas', 'eliminar')) {
            BitacoraHelper::registrarError('personas', 'Intento de desactivar persona sin permisos', $idusuario, $bitacoraModel);
            echo json_encode(['status' => false, 'message' => 'No tienes permisos para desactivar personas'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($data['idpersona_pk'])) {
            throw new \Exception('Datos inválidos');
        }

        $idpersona_pk = intval($data['idpersona_pk']);

        // Verificar que no se desactive la persona asociada al usuario logueado
        $model = getPersonasModel();
        $personaData = $model->selectPersonaById($idpersona_pk);
        if (!empty($personaData) && isset($personaData['idusuario'])) {
            $idUsuarioAsociado = intval($personaData['idusuario']);
            if ($idUsuarioAsociado === $idusuario) {
                BitacoraHelper::registrarError('personas', 'Intento de desactivar su propia persona asociada', $idusuario, $bitacoraModel);
                echo json_encode(['status' => false, 'message' => 'No puedes desactivar tu propia persona asociada.'], JSON_UNESCAPED_UNICODE);
                die();
            }
        }

        $requestDelete = $model->deletePersonaById($idpersona_pk);

        if ($requestDelete) {
            $detalle = "Persona desactivada con ID: " . $idpersona_pk;
            BitacoraHelper::registrarAccion('personas', 'DESACTIVAR_PERSONA', $idusuario, $bitacoraModel, $detalle, $idpersona_pk);
            echo json_encode(['status' => true, 'message' => 'Persona desactivada correctamente.'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['status' => false, 'message' => 'Error al desactivar la persona.'], JSON_UNESCAPED_UNICODE);
        }

    } catch (\Exception $e) {
        error_log("Error en deletePersona: " . $e->getMessage());
        BitacoraHelper::registrarError('personas', $e->getMessage(), $idusuario, $bitacoraModel);
        echo json_encode(['status' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Reactiva una persona desactivada. Solo super usuarios.
 */
function personas_reactivarPersona()
{
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        echo json_encode(['status' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
        die();
    }

    $idusuario = obtenerUsuarioSesion();
    $bitacoraModel = getPersonasBitacoraModel();

    try {
        // Solo super usuarios pueden reactivar personas
        $usuariosModel = new UsuariosModel();
        $esSuperAdmin = $usuariosModel->verificarEsSuperUsuario($idusuario);

        if (!$esSuperAdmin) {
            BitacoraHelper::registrarError('personas', 'Intento de reactivar persona sin ser super usuario', $idusuario, $bitacoraModel);
            echo json_encode(['status' => false, 'message' => 'Solo los super usuarios pueden reactivar personas'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($data['idpersona_pk'])) {
            throw new \Exception('Datos inválidos');
        }

        $idpersona_pk = intval($data['idpersona_pk']);
        $model = getPersonasModel();
        $requestReactivar = $model->reactivarPersonaById($idpersona_pk);

        if ($requestReactivar) {
            $detalle = "Persona reactivada con ID: " . $idpersona_pk;
            BitacoraHelper::registrarAccion('personas', 'REACTIVAR_PERSONA', $idusuario, $bitacoraModel, $detalle, $idpersona_pk);
            echo json_encode(['status' => true, 'message' => 'Persona reactivada correctamente.'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['status' => false, 'message' => 'Error al reactivar la persona.'], JSON_UNESCAPED_UNICODE);
        }

    } catch (\Exception $e) {
        error_log("Error en reactivarPersona: " . $e->getMessage());
        BitacoraHelper::registrarError('personas', $e->getMessage(), $idusuario, $bitacoraModel);
        echo json_encode(['status' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Obtiene todos los roles disponibles (JSON).
 */
function personas_getRoles()
{
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $model = getPersonasModel();
        $arrData = $model->selectAllRoles();
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Verificar si el usuario actual es super usuario.
 */
function personas_verificarSuperUsuario()
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
            error_log("Error en verificarSuperUsuario (Personas): " . $e->getMessage());
            echo json_encode([
                'status' => false,
                'message' => 'Error interno del servidor',
                'es_super_usuario' => false,
                'usuario_id' => 0
            ], JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Desasocia el usuario vinculado a una persona.
 */
function personas_desasociarUsuario()
{
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        echo json_encode(['status' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
        die();
    }

    $idusuario = obtenerUsuarioSesion();
    $bitacoraModel = getPersonasBitacoraModel();

    try {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('personas', 'editar')) {
            BitacoraHelper::registrarError('personas', 'Intento de desasociar usuario sin permisos', $idusuario, $bitacoraModel);
            echo json_encode(['status' => false, 'message' => 'No tienes permisos para editar personas'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($data['idpersona_pk'])) {
            throw new \Exception('Datos inválidos');
        }

        $idpersona_pk = intval($data['idpersona_pk']);

        // Verificar que no se desasocie su propio usuario
        $model = getPersonasModel();
        $personaData = $model->selectPersonaById($idpersona_pk);
        if (!empty($personaData) && isset($personaData['idusuario'])) {
            $idUsuarioAsociado = intval($personaData['idusuario']);
            if ($idUsuarioAsociado === $idusuario) {
                echo json_encode(['status' => false, 'message' => 'No puedes desasociar tu propio usuario.'], JSON_UNESCAPED_UNICODE);
                die();
            }
        }

        $resultado = $model->desasociarUsuarioDePersona($idpersona_pk);

        if (isset($resultado['status']) && $resultado['status'] === true) {
            $detalle = "Usuario desasociado de persona ID: " . $idpersona_pk;
            if (!empty($resultado['usuario_desasociado'])) {
                $detalle .= " (" . $resultado['usuario_desasociado'] . ")";
            }
            BitacoraHelper::registrarAccion('personas', 'DESASOCIAR_USUARIO', $idusuario, $bitacoraModel, $detalle, $idpersona_pk);
        }

        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

    } catch (\Exception $e) {
        error_log("Error en desasociarUsuario: " . $e->getMessage());
        BitacoraHelper::registrarError('personas', $e->getMessage(), $idusuario, $bitacoraModel);
        echo json_encode(['status' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Asocia un nuevo usuario a una persona existente.
 */
function personas_asociarUsuario()
{
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        echo json_encode(['status' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
        die();
    }

    $idusuario = obtenerUsuarioSesion();
    $bitacoraModel = getPersonasBitacoraModel();

    try {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('personas', 'editar')) {
            BitacoraHelper::registrarError('personas', 'Intento de asociar usuario sin permisos', $idusuario, $bitacoraModel);
            echo json_encode(['status' => false, 'message' => 'No tienes permisos para editar personas'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($data['idpersona_pk'])) {
            throw new \Exception('Datos inválidos');
        }

        $idpersona_pk = intval($data['idpersona_pk']);
        $model = getPersonasModel();

        // Verificar que la persona no tenga ya un usuario asociado
        $personaData = $model->selectPersonaById($idpersona_pk);
        if (!empty($personaData) && !empty($personaData['idusuario'])) {
            echo json_encode(['status' => false, 'message' => 'Esta persona ya tiene un usuario asociado.'], JSON_UNESCAPED_UNICODE);
            die();
        }

        // Validar datos del usuario
        $correo = trim($data['correo_electronico_usuario'] ?? '');
        $clave = $data['clave_usuario'] ?? '';
        $idrol = $data['idrol_usuario'] ?? '';

        if (empty($correo) || empty($clave) || empty($idrol)) {
            echo json_encode(['status' => false, 'message' => 'Correo, contraseña y rol son obligatorios.'], JSON_UNESCAPED_UNICODE);
            die();
        }

        // Validar correo duplicado
        if ($model->existeCorreoUsuario($correo)) {
            echo json_encode(['status' => false, 'message' => 'Ya existe un usuario registrado con ese correo electrónico.'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $usuarioData = [
            'correo_electronico_usuario' => $correo,
            'clave_usuario' => $clave,
            'idrol_usuario' => $idrol
        ];

        $resultado = $model->insertUsuario($idpersona_pk, $usuarioData);

        if (isset($resultado['status']) && $resultado['status'] === true) {
            $detalle = "Usuario asociado a persona ID: " . $idpersona_pk . " (" . $correo . ")";
            BitacoraHelper::registrarAccion('personas', 'ASOCIAR_USUARIO', $idusuario, $bitacoraModel, $detalle, $idpersona_pk);
        }

        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

    } catch (\Exception $e) {
        error_log("Error en asociarUsuario: " . $e->getMessage());
        BitacoraHelper::registrarError('personas', $e->getMessage(), $idusuario, $bitacoraModel);
        echo json_encode(['status' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    die();
}
