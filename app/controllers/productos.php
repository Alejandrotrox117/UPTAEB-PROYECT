<?php
require_once "app/core/Controllers.php";
require_once "app/models/productosModel.php";
require_once "helpers/helpers.php";
require_once "helpers/permisosVerificar.php";
require_once "helpers/PermisosHelper.php";
require_once "app/models/bitacoraModel.php";
require_once "app/models/notificacionesModel.php";
require_once "helpers/expresiones_regulares.php";

class Productos extends Controllers
{
    private $bitacoraModel;
    private $notificacionesModel;

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
        $this->model = new ProductosModel();
        $this->bitacoraModel = new BitacoraModel();
        $this->notificacionesModel = new NotificacionesModel();

        // Verificar si el usuario está logueado antes de verificar permisos
        if (!$this->obtenerUsuarioSesion()) {
            header('Location: ' . base_url() . '/login');
            die();
        }
    }

    
    public function index()
    {
        $data['page_tag'] = "Productos";
        $data['page_title'] = "Administración de Productos";
        $data['page_name'] = "productos";
        $data['page_content'] = "Gestión integral de productos del sistema";
        $data['page_functions_js'] = "functions_productos.js";
        $this->views->getView($this, "productos", $data);
    }


    public function createProducto()
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

                $datosLimpios = [
                    'nombre' => ExpresionesRegulares::limpiar($request['nombre'] ?? '', 'nombre'),
                    'descripcion' => trim($request['descripcion'] ?? ''),
                    'unidad_medida' => strtoupper(trim($request['unidad_medida'] ?? '')),
                    'precio' => floatval($request['precio'] ?? 0),
                    'idcategoria' => intval($request['idcategoria'] ?? 0),
                    'moneda' => strtoupper(trim($request['moneda'] ?? 'BS'))
                ];
                $camposObligatorios = ['nombre', 'unidad_medida', 'precio', 'idcategoria'];
                foreach ($camposObligatorios as $campo) {
                    if (empty($datosLimpios[$campo]) || ($campo === 'precio' && $datosLimpios[$campo] <= 0)) {
                        $arrResponse = array('status' => false, 'message' => 'Todos los campos obligatorios deben ser completados correctamente');
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }
                }
                $reglasValidacion = [
                    'nombre' => 'nombre'
                ];
                if (!empty($datosLimpios['descripcion'])) {
                    $reglasValidacion['descripcion'] = 'textoGeneral';
                }
                $resultadosValidacion = ExpresionesRegulares::validarCampos($datosLimpios, $reglasValidacion);
                $errores = [];
                foreach ($resultadosValidacion as $campo => $resultado) {
                    if (!$resultado['valido']) {
                        $errores[] = ExpresionesRegulares::obtenerMensajeError($campo, $reglasValidacion[$campo]);
                    }
                }
                if ($datosLimpios['precio'] <= 0) {
                    $errores[] = 'El precio debe ser mayor a 0';
                }

                if (!in_array($datosLimpios['unidad_medida'], ['UNIDAD', 'KG', 'GRAMO', 'LITRO', 'ML', 'METRO', 'CM', 'CAJA', 'PAQUETE'])) {
                    $errores[] = 'Unidad de medida inválida';
                }

                if (!in_array($datosLimpios['moneda'], ['BS', 'USD', 'EUR'])) {
                    $errores[] = 'Moneda inválida';
                }

                if (!empty($errores)) {
                    $arrResponse = array(
                        'status' => false,
                        'message' => 'Errores de validación: ' . implode(' | ', $errores)
                    );
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrData = array(
                    'nombre' => $datosLimpios['nombre'],
                    'descripcion' => $datosLimpios['descripcion'],
                    'unidad_medida' => $datosLimpios['unidad_medida'],
                    'precio' => $datosLimpios['precio'],
                    'idcategoria' => $datosLimpios['idcategoria'],
                    'moneda' => $datosLimpios['moneda']
                );

                $idusuario = $this->obtenerUsuarioSesion();

                if (!$idusuario) {
                    error_log("ERROR: No se encontró ID de usuario en la sesión durante createProducto()");
                    $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrResponse = $this->model->insertProducto($arrData);

                if ($arrResponse['status'] === true) {
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('producto', 'INSERTAR', $idusuario);

                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la creación del producto ID: " .
                            ($arrResponse['producto_id'] ?? 'desconocido'));
                    }
                    $this->notificacionesModel->generarNotificacionesProductos();
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en createProducto: " . $e->getMessage());
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
            error_log("ERROR: No se encontró ID de usuario en la sesión");
            return null;
        }
    }

    public function getProductosData()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrResponse = $this->model->selectAllProductos();
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getProductosData: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getProductoById($idproducto)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (empty($idproducto) || !is_numeric($idproducto)) {
                $arrResponse = array('status' => false, 'message' => 'ID de producto inválido');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $arrData = $this->model->selectProductoById(intval($idproducto));
                if (!empty($arrData)) {
                    $arrResponse = array('status' => true, 'data' => $arrData);
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Producto no encontrado');
                }
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getProductoById: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function updateProducto()
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

                $intIdProducto = intval($request['idproducto'] ?? 0);
                if ($intIdProducto <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'ID de producto inválido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $datosLimpios = [
                    'nombre' => ExpresionesRegulares::limpiar($request['nombre'] ?? '', 'nombre'),
                    'descripcion' => trim($request['descripcion'] ?? ''),
                    'unidad_medida' => strtoupper(trim($request['unidad_medida'] ?? '')),
                    'precio' => floatval($request['precio'] ?? 0),
                    'idcategoria' => intval($request['idcategoria'] ?? 0),
                    'moneda' => strtoupper(trim($request['moneda'] ?? 'BS'))
                ];

                $camposObligatorios = ['nombre', 'unidad_medida', 'precio', 'idcategoria'];
                foreach ($camposObligatorios as $campo) {
                    if (empty($datosLimpios[$campo]) || ($campo === 'precio' && $datosLimpios[$campo] <= 0)) {
                        $arrResponse = array('status' => false, 'message' => 'Todos los campos obligatorios deben ser completados correctamente');
                        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                        die();
                    }
                }

                $reglasValidacion = ['nombre' => 'nombre'];

                if (!empty($datosLimpios['descripcion'])) {
                    $reglasValidacion['descripcion'] = 'textoGeneral';
                }

                $resultadosValidacion = ExpresionesRegulares::validarCampos($datosLimpios, $reglasValidacion);

                $errores = [];
                foreach ($resultadosValidacion as $campo => $resultado) {
                    if (!$resultado['valido']) {
                        $errores[] = ExpresionesRegulares::obtenerMensajeError($campo, $reglasValidacion[$campo]);
                    }
                }

                // Validaciones adicionales
                if ($datosLimpios['precio'] <= 0) {
                    $errores[] = 'El precio debe ser mayor a 0';
                }

                if (!in_array($datosLimpios['unidad_medida'], ['UNIDAD', 'KG', 'GRAMO', 'LITRO', 'ML', 'METRO', 'CM', 'CAJA', 'PAQUETE'])) {
                    $errores[] = 'Unidad de medida inválida';
                }

                if (!in_array($datosLimpios['moneda'], ['BS', 'USD', 'EUR'])) {
                    $errores[] = 'Moneda inválida';
                }

                if (!empty($errores)) {
                    $arrResponse = array(
                        'status' => false,
                        'message' => 'Errores de validación: ' . implode(' | ', $errores)
                    );
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrData = array(
                    'nombre' => $datosLimpios['nombre'],
                    'descripcion' => $datosLimpios['descripcion'],
                    'unidad_medida' => $datosLimpios['unidad_medida'],
                    'precio' => $datosLimpios['precio'],
                    'idcategoria' => $datosLimpios['idcategoria'],
                    'moneda' => $datosLimpios['moneda']
                );

                $idusuario = $this->obtenerUsuarioSesion();

                if (!$idusuario) {
                    error_log("ERROR: No se encontró ID de usuario en la sesión durante updateProducto()");
                    $arrResponse = array('status' => false, 'message' => 'Error: Usuario no autenticado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $arrResponse = $this->model->updateProducto($intIdProducto, $arrData);

                if ($arrResponse['status'] === true) {
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('producto', 'ACTUALIZAR', $idusuario);

                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la actualización del producto ID: " . $intIdProducto);
                    }

                    $this->notificacionesModel->generarNotificacionesProductos();
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en updateProducto: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function deleteProducto()
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

                $intIdProducto = intval($request['idproducto'] ?? 0);
                if ($intIdProducto <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'ID de producto inválido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idusuario = $this->obtenerUsuarioSesion();
                $requestDelete = $this->model->deleteProductoById($intIdProducto, $idusuario);
                
                if ($requestDelete) {
                    $arrResponse = array('status' => true, 'message' => 'Producto desactivado correctamente');
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Error al desactivar el producto');
                }
                
                if ($arrResponse['status'] === true) {
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('producto', 'ELIMINAR', $idusuario);

                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la eliminación del producto ID: " . $intIdProducto);
                    }

                    $this->notificacionesModel->generarNotificacionesProductos();
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en deleteProducto: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getProductosActivos()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrResponse = $this->model->selectProductosActivos();
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getProductosActivos: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getCategorias()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrResponse = $this->model->selectCategoriasActivas();
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getCategorias: " . $e->getMessage());
                $response = array('status' => false, 'message' => 'Error interno del servidor', 'data' => []);
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function activarProducto()
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

                $intIdProducto = intval($request['idproducto'] ?? 0);
                if ($intIdProducto <= 0) {
                    $arrResponse = array('status' => false, 'message' => 'ID de producto inválido');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idusuario = $this->obtenerUsuarioSesion();
                $requestActivar = $this->model->activarProductoById($intIdProducto, $idusuario);
                
                if ($requestActivar) {
                    $arrResponse = array('status' => true, 'message' => 'Producto activado correctamente');
                    $this->notificacionesModel->generarNotificacionesProductos();
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Error al activar el producto');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en activarProducto: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function exportarProductos()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrData = $this->get_model()->selectAllProductos();

                if ($arrData['status']) {
                    $data['productos'] = $arrData['data'];
                    $data['page_title'] = "Reporte de Productos";
                    $data['fecha_reporte'] = date('d/m/Y H:i:s');

                    $arrResponse = array('status' => true, 'message' => 'Datos preparados para exportación', 'data' => $data);
                } else {
                    $arrResponse = array('status' => false, 'message' => 'No se pudieron obtener los datos');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en exportarProductos: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function buscarProducto()
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

                $arrData = $this->get_model()->buscarProductos($strTermino);
                if ($arrData['status']) {
                    $arrResponse = array('status' => true, 'data' => $arrData['data']);
                } else {
                    $arrResponse = array('status' => false, 'message' => 'No se encontraron resultados');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en buscarProducto: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function generarNotificacionesProductos()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $resultado = $this->notificacionesModel->generarNotificacionesProductos();
                
                if ($resultado) {
                    $arrResponse = array('status' => true, 'message' => 'Notificaciones de stock actualizadas correctamente');
                } else {
                    $arrResponse = array('status' => false, 'message' => 'Error al generar notificaciones de stock');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en generarNotificacionesProductos: " . $e->getMessage());
                $arrResponse = array('status' => false, 'message' => 'Error interno del servidor');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }
}
?>