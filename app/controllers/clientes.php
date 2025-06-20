<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
require_once "helpers/PermisosModuloVerificar.php";
require_once "app/models/bitacoraModel.php";
require_once "helpers/bitacora_helper.php";
require_once "helpers/expresiones_regulares.php";

class Clientes extends Controllers
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

        if (!PermisosModuloVerificar::verificarAccesoModulo('clientes')) {
            $this->views->getView($this, "permisos");
            exit();
        }
    }

    public function index()
    {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('clientes', 'ver')) {
            $this->views->getView($this, "permisos");
            exit();
        }

        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        BitacoraHelper::registrarAccesoModulo('clientes', $idusuario, $this->bitacoraModel);

    
        $permisos = PermisosModuloVerificar::getPermisosUsuarioModulo('clientes');

        $data['page_tag'] = "Clientes";
        $data['page_title'] = "Administración de Clientes";
        $data['page_name'] = "clientes";
        $data['page_content'] = "Gestión integral de clientes del sistema";
        $data['page_functions_js'] = "functions_clientes.js";
        $data['permisos'] = $permisos; 
        
        $this->views->getView($this, "clientes", $data);
    }

    public function createCliente()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('clientes', 'crear')) {
                    $arrResponse = array('status' => false, 'message' => 'No tienes permisos para crear clientes');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $postdata = file_get_contents("php://input");
                $request = json_decode($postdata, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $datosLimpios = [
                    'nombre' => strClean($request['nombre'] ?? ''),
                    'apellido' => strClean($request['apellido'] ?? ''),
                    'cedula' => strClean($request['cedula'] ?? ''),
                    'telefono_principal' => strClean($request['telefono_principal'] ?? ''),
                    
                    'direccion' => strClean($request['direccion'] ?? ''),
                    'observaciones' => strClean($request['observaciones'] ?? '')
                ];

                $camposObligatorios = ['nombre', 'apellido', 'cedula', 'telefono_principal'];
                foreach ($camposObligatorios as $campo) {
                    if (empty($datosLimpios[$campo])) {
                        $arrResponse = array('status' => false, 'message' => 'Todos los campos obligatorios deben ser completados');
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

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

                if (!$idusuario) {
                    error_log("ERROR: No se encontró ID de usuario en la sesión durante createCliente()");
                    $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrResponse = $this->model->insertCliente($arrData);

                if ($arrResponse['status'] === true) {
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('clientes', 'CREAR_CLIENTE', $idusuario);

                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la creación del cliente ID: " .
                            ($arrResponse['cliente_id'] ?? 'desconocido'));
                    }
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en createCliente: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getClientesData()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('clientes', 'ver')) {
                    $response = array('status' => false, 'message' => 'No tienes permisos para ver clientes', 'data' => []);
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrResponse = $this->model->selectAllClientesActivos();

                if ($arrResponse['status']) {
                    $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                    $this->bitacoraModel->registrarAccion('clientes', 'CONSULTA_LISTADO', $idusuario);
                }
                
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getClientesData: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getClienteById($idcliente)
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
                $arrData = $this->model->selectClienteById(intval($idcliente));
                if (!empty($arrData)) {
                    $idUsuarioSesion = $this->BitacoraHelper->obtenerUsuarioSesion();
                    $this->bitacoraModel->registrarAccion('clientes', 'VER_CLIENTE', $idUsuarioSesion);
                    
                    $arrResponse = array('status' => true, 'data' => $arrData);
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Cliente no encontrado');
                }
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getClienteById: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function updateCliente()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('clientes', 'editar')) {
                    $arrResponse = array('status' => false, 'message' => 'No tienes permisos para editar clientes');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $postdata = file_get_contents("php://input");
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
                    'nombre' => strClean($request['nombre'] ?? ''),
                    'apellido' => strClean($request['apellido'] ?? ''),
                    'cedula' => strClean($request['cedula'] ?? ''),
                    'telefono_principal' => strClean($request['telefono_principal'] ?? ''),
                
                    'direccion' => strClean($request['direccion'] ?? ''),
                    'estatus' => strClean($request['estatus'] ?? 'ACTIVO'),
                    'observaciones' => strClean($request['observaciones'] ?? '')
                ];

                $camposObligatorios = ['nombre', 'apellido', 'cedula', 'telefono_principal'];
                foreach ($camposObligatorios as $campo) {
                    if (empty($datosLimpios[$campo])) {
                        $arrResponse = array('status' => false, 'message' => 'Todos los campos obligatorios deben ser completados');
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }
                }

                // Validaciones similares al create
                if (strlen($datosLimpios['nombre']) < 2 || strlen($datosLimpios['nombre']) > 50) {
                    $arrResponse = array('status' => false, 'message' => 'El nombre debe tener entre 2 y 50 caracteres');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }


                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

                if (!$idusuario) {
                    error_log("ERROR: No se encontró ID de usuario en la sesión durante updateCliente()");
                    $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrResponse = $this->model->updateCliente($intIdCliente, $datosLimpios);

                if ($arrResponse['status'] === true) {
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('clientes', 'ACTUALIZAR_CLIENTE', $idusuario);

                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la actualización del cliente ID: " . $intIdCliente);
                    }
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en updateCliente: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function deleteCliente()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('clientes', 'eliminar')) {
                    $arrResponse = array('status' => false, 'message' => 'No tienes permisos para eliminar clientes');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $postdata = file_get_contents("php://input");
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

                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

                $requestDelete = $this->model->deleteClienteById($intIdCliente);
                if ($requestDelete) {
                    $arrResponse = array('status' => true, 'message' => 'Cliente desactivado correctamente');
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Error al desactivar el cliente');
                }

                if ($arrResponse['status'] === true) {
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('clientes', 'ELIMINAR_CLIENTE', $idusuario);

                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la eliminación del cliente ID: " . $intIdCliente);
                    }
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en deleteCliente: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function buscarClientes()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('clientes', 'ver')) {
                    $arrResponse = array('status' => false, 'message' => 'No tienes permisos para buscar clientes');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $postdata = file_get_contents("php://input");
                $request = json_decode($postdata, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $strTermino = strClean($request['criterio'] ?? '');
                if (empty($strTermino)) {
                    $arrResponse = array('status' => false, 'message' => 'Término de búsqueda requerido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrData = $this->model->buscarClientes($strTermino);
                if ($arrData['status']) {
                    $arrResponse = array('status' => true, 'data' => $arrData['data']);
                } else {
                    $arrResponse = array('status' => false, 'message' => 'No se encontraron resultados');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en buscarClientes: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function exportarClientes()
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
               
                $clientesResponse = $this->model->selectAllClientes();
                
                if ($clientesResponse['status'] && !empty($clientesResponse['data'])) {
              
                    $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                    $this->bitacoraModel->registrarAccion('clientes', 'EXPORTAR_CLIENTES', $idusuario);
                    
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
            } catch (Exception $e) {
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

    public function getEstadisticas()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('clientes', 'ver')) {
                    $response = array('status' => false, 'message' => 'No tienes permisos para ver estadísticas', 'data' => []);
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $estadisticas = $this->model->getEstadisticasClientes();
                $arrResponse = array('status' => true, 'data' => $estadisticas);
                
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getEstadisticas: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }
}
?>