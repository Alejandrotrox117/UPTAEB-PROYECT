<?php

require_once "app/core/Controllers.php";
require_once "app/models/proveedoresModel.php";
require_once "helpers/helpers.php";
require_once "helpers/permisosVerificar.php";
require_once "helpers/PermisosHelper.php";
require_once "app/models/bitacoraModel.php";

class Proveedores extends Controllers
{
    private $bitacoraModel;


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
        $this->model = new ProveedoresModel();
        $this->bitacoraModel = new BitacoraModel();


        // Verificar si el usuario está logueado antes de verificar permisos
        if (!$this->obtenerUsuarioSesion()) {
            header('Location: ' . base_url() . '/login');
            die();
        }
    }


    public function createProveedor()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {

                $postdata = file_get_contents("php://input");
                $request = json_decode($postdata, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $arrResponse = array('status' => false, 'message' => 'Datos JSON inválidos');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Validaciones básicas
                $strNombre = strClean($request['nombre'] ?? '');
                $strApellido = strClean($request['apellido'] ?? '');
                $strIdentificacion = strClean($request['identificacion'] ?? '');
                $strTelefono = strClean($request['telefono_principal'] ?? '');

                if (empty($strNombre) || empty($strApellido) || empty($strIdentificacion) || empty($strTelefono)) {
                    $arrResponse = array('status' => false, 'message' => 'Todos los campos obligatorios deben ser completados');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Preparar datos para inserción
                $arrData = array(
                    'nombre' => $strNombre,
                    'apellido' => $strApellido,
                    'identificacion' => $strIdentificacion,
                    'telefono_principal' => $strTelefono,
                    'correo_electronico' => strClean($request['correo_electronico'] ?? ''),
                    'direccion' => strClean($request['direccion'] ?? ''),
                    'fecha_nacimiento' => $request['fecha_nacimiento'] ?? null,
                    'genero' => $request['genero'] ?? null,
                    'observaciones' => strClean($request['observaciones'] ?? '')
                );

                // Obtener ID de usuario
                $idusuario = $this->obtenerUsuarioSesion();

                // Si no hay ID de usuario, mostrar error
                if (!$idusuario) {
                    error_log("ERROR: No se encontró ID de usuario en la sesión durante createProveedor()");
                    error_log("Datos de sesión: " . print_r($_SESSION, true));
                    $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Insertar proveedor
                $arrResponse = $this->model->insertProveedor($arrData, $idusuario);

                // Registrar en bitácora si la inserción fue exitosa
                if ($arrResponse['status'] === true) {
                    // Registrar acción en bitácora
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('proveedor', 'INSERTAR', $idusuario);

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

    private function obtenerUsuarioSesion()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Buscar en todas las posibles variables de sesión donde podría estar el ID
        if (isset($_SESSION['idusuario'])) {
            return $_SESSION['idusuario'];
        } elseif (isset($_SESSION['idUser'])) {
            return $_SESSION['idUser'];
        } elseif (isset($_SESSION['usuario_id'])) {
            return $_SESSION['usuario_id'];
        } else {
            // Si llegamos aquí, no hay ID de usuario en la sesión
            error_log("ERROR: No se encontró ID de usuario en la sesión");
            return null;
        }
    }

    public function index()
    {
        $data['page_tag'] = "Proveedores";
        $data['page_title'] = "Administración de Proveedores";
        $data['page_name'] = "proveedores";
        $data['page_content'] = "Gestión integral de proveedores del sistema";
        $data['page_functions_js'] = "functions_proveedores.js";
        $this->views->getView($this, "proveedores", $data);
    }

    public function getProveedoresData()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrResponse = $this->model->selectAllProveedores();
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getProveedoresData: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getProveedorById($idproveedor)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (empty($idproveedor) || !is_numeric($idproveedor)) {
                $arrResponse = array('status' => false, 'message' => 'ID de proveedor inválido');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $arrData = $this->model->selectProveedorById(intval($idproveedor));
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




    public function updateProveedor()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents("php://input");
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

                // Validaciones básicas
                $strNombre = strClean($request['nombre'] ?? '');
                $strApellido = strClean($request['apellido'] ?? '');
                $strIdentificacion = strClean($request['identificacion'] ?? '');
                $strTelefono = strClean($request['telefono_principal'] ?? '');

                if (empty($strNombre) || empty($strApellido) || empty($strIdentificacion) || empty($strTelefono)) {
                    $arrResponse = array('status' => false, 'message' => 'Los campos nombre, apellido, identificación y teléfono son obligatorios');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Preparar datos para actualización
                $arrData = array(
                    'nombre' => $strNombre,
                    'apellido' => $strApellido,
                    'identificacion' => $strIdentificacion,
                    'fecha_nacimiento' => $request['fecha_nacimiento'] ?? '',
                    'direccion' => strClean($request['direccion'] ?? ''),
                    'correo_electronico' => strClean($request['correo_electronico'] ?? ''),
                    'telefono_principal' => $strTelefono,
                    'observaciones' => strClean($request['observaciones'] ?? ''),
                    'genero' => strClean($request['genero'] ?? '')
                );

                // CAMBIO IMPORTANTE: Pasar el ID del usuario al modelo
                $idusuario = $this->obtenerUsuarioSesion();
                $arrResponse = $this->model->updateProveedor($intIdProveedor, $arrData, $idusuario);
                // Registrar en bitácora si la inserción fue exitosa
                if ($arrResponse['status'] === true) {
                    // Registrar acción en bitácora
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('proveedor', 'ACTUALIZAR', $idusuario);

                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la actualización del proveedor ID: " .
                            ($arrResponse['proveedor_id'] ?? 'desconocido'));
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

    public function deleteProveedor()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents("php://input");
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

                // CAMBIO IMPORTANTE: Pasar el ID del usuario al modelo
                $idusuario = $this->obtenerUsuarioSesion();
                $requestDelete = $this->model->deleteProveedorById($intIdProveedor, $idusuario);
                if ($requestDelete) {
                    $arrResponse = array('status' => true, 'message' => 'Proveedor desactivado correctamente');
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Error al desactivar el proveedor');
                }
                 if ($arrResponse['status'] === true) {
                    // Registrar acción en bitácora
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('proveedor', 'ELIMINAR', $idusuario);

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

    public function getProveedoresActivos()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrResponse = $this->model->selectProveedoresActivos();
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getProveedoresActivos: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    // Método para activar proveedor (si existe)
    public function activarProveedor()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents("php://input");
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

               
                $idusuario = $this->obtenerUsuarioSesion();
                $requestActivar = $this->model->activarProveedorById($intIdProveedor, $idusuario);
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
    public function exportarProveedores()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrData = $this->get_model()->selectAllProveedores();

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

    public function buscarProveedor()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postdata = file_get_contents("php://input");
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

                $arrData = $this->get_model()->buscarProveedores($strTermino);
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
}
