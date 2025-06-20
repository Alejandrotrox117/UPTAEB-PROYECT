<?php

require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
require_once "helpers/PermisosModuloVerificar.php";
require_once "app/models/bitacoraModel.php";
require_once "helpers/bitacora_helper.php";

class Roles extends Controllers
{
    private $bitacoraModel;
    private $BitacoraHelper;

    public function get_model()
    {
        return $this->model;
    }

    public function set_model($model)
    {
        $this->model = $model;
    }

    public function __construct()
    {
        parent::__construct();
        
        $this->bitacoraModel = new BitacoraModel();
        $this->BitacoraHelper = new BitacoraHelper();

        
        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        
        if (!PermisosModuloVerificar::verificarAccesoModulo('roles')) {
            $this->views->getView($this, "permisos");
            exit();
        }
    }

    /**
     * Vista principal del módulo roles
     */
    public function index()
    {
        
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'ver')) {
            $this->views->getView($this, "permisos");
            exit();
        }

        
        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        BitacoraHelper::registrarAccesoModulo('Roles', $idusuario, $this->bitacoraModel);

        $data['page_tag'] = "Roles";
        $data['page_title'] = "Gestión de Roles";
        $data['page_name'] = "roles";
        $data['page_content'] = "Gestión integral de roles del sistema";
        $data['page_functions_js'] = "functions_roles.js";
        
        $this->views->getView($this, "roles", $data);
    }

    /**
     * Obtener todos los roles para DataTable
     */
    public function getRolesData()
    {
        
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'ver')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para ver roles',
                'data' => []
            ]);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrData = $this->model->selectAllRolesActivos();
                
                
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                BitacoraHelper::registrarAccion('Roles', 'consulta', $idusuario, $this->bitacoraModel, 'Consulta de roles');
                
                echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getRolesData: " . $e->getMessage());
                echo json_encode([
                    'status' => false,
                    'message' => 'Error al obtener los roles',
                    'data' => []
                ]);
            }
        }
        die();
    }

    /**
     * Crear nuevo rol
     */
    public function createRol()
    {
        
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'crear')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para crear roles'
            ]);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);

                if (!$data) {
                    echo json_encode(['status' => false, 'message' => 'Datos no válidos']);
                    return;
                }

                
                if (empty(trim($data['nombre'] ?? ''))) {
                    echo json_encode(['status' => false, 'message' => 'El nombre del rol es requerido']);
                    return;
                }

                $rolData = [
                    'nombre' => trim($data['nombre']),
                    'descripcion' => trim($data['descripcion'] ?? ''),
                    'estatus' => $data['estatus'] ?? 'ACTIVO'
                ];

                $request = $this->model->insertRol($rolData);

                
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                if ($request['status']) {
                    BitacoraHelper::registrarAccion('Roles', 'crear', $idusuario, $this->bitacoraModel, 
                        "Rol creado: {$rolData['nombre']}", $request['idrol'] ?? null);
                } else {
                    BitacoraHelper::registrarAccion('Roles', 'crear_error', $idusuario, $this->bitacoraModel, 
                        "Error al crear rol: {$rolData['nombre']} - {$request['message']}");
                }

                echo json_encode($request, JSON_UNESCAPED_UNICODE);

            } catch (Exception $e) {
                error_log("Error en createRol: " . $e->getMessage());
                
                
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                BitacoraHelper::registrarError('Roles', "Error en createRol: " . $e->getMessage(), $idusuario, $this->bitacoraModel);
                
                echo json_encode([
                    'status' => false,
                    'message' => 'Error interno del servidor'
                ]);
            }
        }
        die();
    }

    /**
     * Obtener rol por ID
     */
    public function getRolById(int $idrol)
    {
        
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'ver')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para ver detalles de roles'
            ]);
            exit();
        }

        if ($idrol > 0) {
            try {
                $arrData = $this->model->selectRolById($idrol);
                
                if (empty($arrData)) {
                    $response = ["status" => false, "message" => "Rol no encontrado."];
                } else {
                    $response = ["status" => true, "data" => $arrData];
                    
                    
                    $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                    BitacoraHelper::registrarAccion('Roles', 'consulta_detalle', $idusuario, $this->bitacoraModel, 
                        "Consulta detalle rol ID: {$idrol}", $idrol);
                }
                
                echo json_encode($response, JSON_UNESCAPED_UNICODE);

            } catch (Exception $e) {
                error_log("Error en getRolById: " . $e->getMessage());
                
                
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                BitacoraHelper::registrarError('Roles', "Error en getRolById ID {$idrol}: " . $e->getMessage(), $idusuario, $this->bitacoraModel);
                
                echo json_encode([
                    'status' => false,
                    'message' => 'Error al obtener el rol'
                ]);
            }
        } else {
            echo json_encode([
                'status' => false,
                'message' => 'ID de rol no válido'
            ]);
        }
        die();
    }

    /**
     * Actualizar rol
     */
    public function updateRol()
    {
        
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'editar')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para editar roles'
            ]);
            exit();
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                echo json_encode(['status' => false, 'message' => 'Datos no válidos']);
                return;
            }

            $idRol = intval($input['idrol'] ?? 0);
            if ($idRol <= 0) {
                echo json_encode(['status' => false, 'message' => 'ID de rol no válido']);
                return;
            }

            
            if (empty(trim($input['nombre'] ?? ''))) {
                echo json_encode(['status' => false, 'message' => 'El nombre del rol es requerido']);
                return;
            }

            
            $rolAnterior = $this->model->selectRolById($idRol);

            $dataParaModelo = [
                'nombre' => trim($input['nombre']),
                'descripcion' => trim($input['descripcion'] ?? ''),
                'estatus' => trim($input['estatus'] ?? 'ACTIVO'),
            ];

            $resultado = $this->model->updateRol($idRol, $dataParaModelo);

            
            $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
            if ($resultado['status']) {
                $cambios = [];
                if ($rolAnterior && is_array($rolAnterior)) {
                    foreach ($dataParaModelo as $campo => $valor) {
                        if (isset($rolAnterior[$campo]) && $rolAnterior[$campo] != $valor) {
                            $cambios[] = "{$campo}: '{$rolAnterior[$campo]}' → '{$valor}'";
                        }
                    }
                }
                
                $detalleCambios = !empty($cambios) ? implode(', ', $cambios) : 'Actualización general';
                BitacoraHelper::registrarAccion('Roles', 'editar', $idusuario, $this->bitacoraModel, 
                    "Rol editado ID: {$idRol} - {$detalleCambios}", $idRol);
            } else {
                BitacoraHelper::registrarAccion('Roles', 'editar_error', $idusuario, $this->bitacoraModel, 
                    "Error al editar rol ID: {$idRol} - {$resultado['message']}", $idRol);
            }
            
            echo json_encode($resultado);

        } catch (Exception $e) {
            error_log("Error en updateRol: " . $e->getMessage());
            
            
            $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
            BitacoraHelper::registrarError('Roles', "Error en updateRol: " . $e->getMessage(), $idusuario, $this->bitacoraModel);
            
            echo json_encode([
                'status' => false, 
                'message' => 'Error interno del servidor'
            ]);
        }
    }

    /**
     * Eliminar rol
     */
    public function deleteRol()
    {
        
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'eliminar')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para eliminar roles'
            ]);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);
                $idrol = isset($data['idrol']) ? intval($data['idrol']) : 0;

                if ($idrol <= 0) {
                    echo json_encode(['status' => false, 'message' => 'ID de rol no válido']);
                    return;
                }

                
                $rolAnterior = $this->model->selectRolById($idrol);

                $requestDelete = $this->model->deleteRolById($idrol);

                
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                if ($requestDelete['status']) {
                    $nombreRol = $rolAnterior['nombre'] ?? "ID: {$idrol}";
                    BitacoraHelper::registrarAccion('Roles', 'eliminar', $idusuario, $this->bitacoraModel, 
                        "Rol eliminado: {$nombreRol}", $idrol);
                    
                    $response = ["status" => true, "message" => $requestDelete['message']];
                } else {
                    BitacoraHelper::registrarAccion('Roles', 'eliminar_error', $idusuario, $this->bitacoraModel, 
                        "Error al eliminar rol ID: {$idrol} - {$requestDelete['message']}", $idrol);
                    
                    $response = ["status" => false, "message" => $requestDelete['message']];
                }

                echo json_encode($response, JSON_UNESCAPED_UNICODE);

            } catch (Exception $e) {
                error_log("Error en deleteRol: " . $e->getMessage());
                
                
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                BitacoraHelper::registrarError('Roles', "Error en deleteRol: " . $e->getMessage(), $idusuario, $this->bitacoraModel);
                
                echo json_encode([
                    'status' => false,
                    'message' => 'Error interno del servidor'
                ]);
            }
        }
        die();
    }

    /**
     * Obtener todos los roles (para select/dropdown)
     */
    public function getAllRoles()
    {
        
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'ver')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para ver roles',
                'data' => []
            ]);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrData = $this->model->selectAllRolesActivos();
                echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getAllRoles: " . $e->getMessage());
                echo json_encode([
                    'status' => false,
                    'message' => 'Error al obtener los roles',
                    'data' => []
                ]);
            }
        }
        die();
    }

    /**
     * Método de debug para verificar permisos
     */
    public function debugPermisos()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $debug = [
            'sesion_activa' => isset($_SESSION['login']) && $_SESSION['login'] === true,
            'usuario_id' => $_SESSION['usuario_id'] ?? 'no definido',
            'rol_id' => $_SESSION['user']['idrol'] ?? $_SESSION['rol_id'] ?? 'no definido',
            'usuario_nombre' => $_SESSION['usuario_nombre'] ?? 'no definido',
        ];

        
        $permisos = PermisosModuloVerificar::getPermisosUsuarioModulo('roles');
        $debug['permisos_roles'] = $permisos;

        
        $debug['puede_ver'] = PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'ver');
        $debug['puede_crear'] = PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'crear');
        $debug['puede_editar'] = PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'editar');
        $debug['puede_eliminar'] = PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'eliminar');

        header('Content-Type: application/json');
        echo json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }
}
?>
