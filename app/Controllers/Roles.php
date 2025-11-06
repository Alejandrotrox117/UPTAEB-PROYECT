<?php
namespace App\Controllers;

use App\Core\Controllers;
use App\Models\RolesModel;
use App\Models\BitacoraModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\PermisosModuloVerificar;

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


    public function getRolesData()
    {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'ver')) {
            echo json_encode(['status' => false, 'message' => 'No tiene permisos para ver roles', 'data' => []]);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                $arrData = $this->model->selectAllRoles($idusuario);
                
                BitacoraHelper::registrarAccion('Roles', 'CONSULTA_LISTADO', $idusuario, $this->bitacoraModel, 'Consulta de listado de roles');
                
                echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getRolesData: " . $e->getMessage());
                echo json_encode(['status' => false, 'message' => 'Error al obtener los roles', 'data' => []]);
            }
        }
        die();
    }

    public function createRol()
    {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'crear')) {
            echo json_encode(['status' => false, 'message' => 'No tiene permisos para crear roles']);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);

                if (!$data || empty(trim($data['nombre'] ?? ''))) {
                    echo json_encode(['status' => false, 'message' => 'Datos inválidos o nombre de rol vacío.']);
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
                    BitacoraHelper::registrarAccion('Roles', 'CREAR_ROL', $idusuario, $this->bitacoraModel, 
                        "Rol creado: {$rolData['nombre']}", $request['rol_id'] ?? null);
                } else {
                    BitacoraHelper::registrarAccion('Roles', 'CREAR_ROL_ERROR', $idusuario, $this->bitacoraModel, 
                        "Error al crear rol: {$rolData['nombre']} - {$request['message']}");
                }

                echo json_encode($request, JSON_UNESCAPED_UNICODE);

            } catch (Exception $e) {
                error_log("Error en createRol: " . $e->getMessage());
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                BitacoraHelper::registrarError('Roles', "Error en createRol: " . $e->getMessage(), $idusuario, $this->bitacoraModel);
                echo json_encode(['status' => false, 'message' => 'Error interno del servidor']);
            }
        }
        die();
    }

    public function getRolById(int $idrol)
    {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'ver')) {
            echo json_encode(['status' => false, 'message' => 'No tiene permisos para ver detalles de roles']);
            exit();
        }

        if ($idrol > 0) {
            try {
                $arrData = $this->model->selectRolById($idrol);
                $response = (empty($arrData))
                    ? ["status" => false, "message" => "Rol no encontrado."]
                    : ["status" => true, "data" => $arrData];
                
                if ($response['status']) {
                    $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                    BitacoraHelper::registrarAccion('Roles', 'CONSULTA_DETALLE', $idusuario, $this->bitacoraModel, 
                        "Consulta detalle rol ID: {$idrol}", $idrol);
                }
                
                echo json_encode($response, JSON_UNESCAPED_UNICODE);

            } catch (Exception $e) {
                error_log("Error en getRolById: " . $e->getMessage());
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                BitacoraHelper::registrarError('Roles', "Error en getRolById ID {$idrol}: " . $e->getMessage(), $idusuario, $this->bitacoraModel);
                echo json_encode(['status' => false, 'message' => 'Error al obtener el rol']);
            }
        } else {
            echo json_encode(['status' => false, 'message' => 'ID de rol no válido']);
        }
        die();
    }

    public function updateRol()
    {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'editar')) {
            echo json_encode(['status' => false, 'message' => 'No tiene permisos para editar roles']);
            exit();
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $idRol = intval($input['idrol'] ?? 0);

            if ($idRol <= 0 || empty(trim($input['nombre'] ?? ''))) {
                echo json_encode(['status' => false, 'message' => 'Datos no válidos.']);
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
                            $cambios[] = "{$campo}: '{$rolAnterior[$campo]}' -> '{$valor}'";
                        }
                    }
                }
                $detalleCambios = !empty($cambios) ? implode(', ', $cambios) : 'Actualización sin cambios de datos';
                BitacoraHelper::registrarAccion('Roles', 'EDITAR_ROL', $idusuario, $this->bitacoraModel, 
                    "Rol editado ID: {$idRol} - {$detalleCambios}", $idRol);
            } else {
                BitacoraHelper::registrarAccion('Roles', 'EDITAR_ROL_ERROR', $idusuario, $this->bitacoraModel, 
                    "Error al editar rol ID: {$idRol} - {$resultado['message']}", $idRol);
            }
            
            echo json_encode($resultado);

        } catch (Exception $e) {
            error_log("Error en updateRol: " . $e->getMessage());
            $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
            BitacoraHelper::registrarError('Roles', "Error en updateRol: " . $e->getMessage(), $idusuario, $this->bitacoraModel);
            echo json_encode(['status' => false, 'message' => 'Error interno del servidor']);
        }
        die();
    }

    public function deleteRol()
    {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'eliminar')) {
            echo json_encode(['status' => false, 'message' => 'No tiene permisos para eliminar roles']);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                $idrol = intval($data['idrol'] ?? 0);

                if ($idrol <= 0) {
                    echo json_encode(['status' => false, 'message' => 'ID de rol no válido']);
                    return;
                }

                $rolAnterior = $this->model->selectRolById($idrol);
                $requestDelete = $this->model->deleteRolById($idrol);
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

                if ($requestDelete['status']) {
                    $nombreRol = $rolAnterior['nombre'] ?? "ID: {$idrol}";
                    BitacoraHelper::registrarAccion('Roles', 'ELIMINAR_ROL', $idusuario, $this->bitacoraModel, 
                        "Rol desactivado: {$nombreRol}", $idrol);
                } else {
                    BitacoraHelper::registrarAccion('Roles', 'ELIMINAR_ROL_ERROR', $idusuario, $this->bitacoraModel, 
                        "Error al desactivar rol ID: {$idrol} - {$requestDelete['message']}", $idrol);
                }

                echo json_encode($requestDelete, JSON_UNESCAPED_UNICODE);

            } catch (Exception $e) {
                error_log("Error en deleteRol: " . $e->getMessage());
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                BitacoraHelper::registrarError('Roles', "Error en deleteRol: " . $e->getMessage(), $idusuario, $this->bitacoraModel);
                echo json_encode(['status' => false, 'message' => 'Error interno del servidor']);
            }
        }
        die();
    }


    public function reactivarRol()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                
                $esSuperUsuario = $this->model->verificarEsSuperUsuario($idusuario);
                if (!$esSuperUsuario) {
                    echo json_encode(['status' => false, 'message' => 'Acción no permitida.']);
                    exit();
                }
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'editar')) {
                    echo json_encode(['status' => false, 'message' => 'No tiene permisos para editar roles.']);
                    exit();
                }

                $data = json_decode(file_get_contents('php://input'), true);
                $idrol = intval($data['idrol'] ?? 0);

                if ($idrol <= 0) {
                    echo json_encode(['status' => false, 'message' => 'ID de rol no válido']);
                    return;
                }

                $rolAnterior = $this->model->selectRolById($idrol);
                $requestReactivar = $this->model->reactivarRol($idrol);

                if ($requestReactivar['status']) {
                    $nombreRol = $rolAnterior['nombre'] ?? "ID: {$idrol}";
                    BitacoraHelper::registrarAccion('Roles', 'REACTIVAR_ROL', $idusuario, $this->bitacoraModel, 
                        "Rol reactivado: {$nombreRol}", $idrol);
                } else {
                    BitacoraHelper::registrarAccion('Roles', 'REACTIVAR_ROL_ERROR', $idusuario, $this->bitacoraModel, 
                        "Error al reactivar rol ID: {$idrol} - {$requestReactivar['message']}", $idrol);
                }

                echo json_encode($requestReactivar, JSON_UNESCAPED_UNICODE);

            } catch (Exception $e) {
                error_log("Error en reactivarRol: " . $e->getMessage());
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                BitacoraHelper::registrarError('Roles', "Error en reactivarRol: " . $e->getMessage(), $idusuario, $this->bitacoraModel);
                echo json_encode(['status' => false, 'message' => 'Error interno del servidor']);
            }
        }
        die();
    }
}
?>