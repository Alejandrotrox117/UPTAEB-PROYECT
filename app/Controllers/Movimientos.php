<?php
namespace App\Controllers;

use App\Core\Controllers;
use App\Models\MovimientosModel;
use App\Models\BitacoraModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\PermisosModuloVerificar;

class Movimientos extends Controllers
{
    private $bitacoraModel;
    private $BitacoraHelper;

    public function __construct()
    {
        parent::__construct();
        
        $this->bitacoraModel = new BitacoraModel();
        $this->BitacoraHelper = new BitacoraHelper();

        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        
        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        
        if (!PermisosModuloVerificar::verificarAccesoModulo('movimientos')) {
            $this->views->getView($this, "permisos");
            exit();
        }
    }

    public function index()
    {
        
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('movimientos', 'ver')) {
            $this->views->getView($this, "permisos");
            exit();
        }

        
        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        BitacoraHelper::registrarAccesoModulo('movimientos', $idusuario, $this->bitacoraModel);

        
        $permisos = PermisosModuloVerificar::getPermisosUsuarioModulo('movimientos');

        $data['page_tag'] = "Movimientos";
        $data['page_title'] = "Gestión de Movimientos";
        $data['page_name'] = "movimientos";
        $data['page_content'] = "Gestión integral de movimientos de inventario";
        $data['page_functions_js'] = "functions_movimientos.js";
        $data['permisos'] = $permisos; 
        
        $this->views->getView($this, "movimientos", $data);
    }

    public function getMovimientos()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('movimientos', 'ver')) {
                $arr = array(
                    "status" => false,
                    "message" => "No tienes permisos para ver movimientos.",
                    "data" => null
                );
                echo json_encode($arr, JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                
                $movimientosResponse = $this->model->selectAllMovimientos();
                echo json_encode($movimientosResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getMovimientos: " . $e->getMessage());
                $arr = array(
                    "status" => false,
                    "message" => "Error al obtener movimientos.",
                    "data" => null
                );
                echo json_encode($arr, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getMovimientoById($id)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('movimientos', 'ver')) {
                echo json_encode([
                    'status' => false, 
                    'message' => 'No tienes permisos para ver detalles de movimientos.',
                    'data' => null
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            try {
                $idmovimiento = intval($id);
                if ($idmovimiento <= 0) {
                    echo json_encode([
                        'status' => false, 
                        'message' => 'ID inválido',
                        'data' => null
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                
                $movimientoResponse = $this->model->selectMovimientoById($idmovimiento);
                echo json_encode($movimientoResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getMovimientoById: " . $e->getMessage());
                echo json_encode([
                    'status' => false, 
                    'message' => 'Error al obtener detalle',
                    'data' => null
                ], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
    }

    
    public function getTiposMovimiento()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('movimientos', 'ver')) {
                $arr = array(
                    "status" => false,
                    "message" => "No tienes permisos para ver tipos de movimiento.",
                    "data" => null
                );
                echo json_encode($arr, JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                
                $tiposResponse = $this->model->getTiposMovimientoActivos();
                echo json_encode($tiposResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getTiposMovimiento: " . $e->getMessage());
                $arr = array(
                    "status" => false,
                    "message" => "Error al obtener tipos de movimiento: " . $e->getMessage(),
                    "data" => null
                );
                echo json_encode($arr, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function createMovimiento()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('movimientos', 'crear')) {
                $arr = array(
                    "status" => false,
                    "message" => "No tienes permisos para crear movimientos.",
                    "data" => null
                );
                echo json_encode($arr, JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);

                if (!$data) {
                    $arr = array(
                        "status" => false,
                        "message" => "Datos inválidos recibidos.",
                        "data" => null
                    );
                    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Validación de seguridad: verificar que los IDs sean válidos
                if (!isset($data['idproducto']) || !isset($data['idtipomovimiento'])) {
                    $arr = array(
                        "status" => false,
                        "message" => "Datos incompletos: se requieren producto y tipo de movimiento.",
                        "data" => null
                    );
                    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Validar que el producto existe y está activo
                $productosActivos = $this->model->getProductosActivos();
                $productoValido = false;
                if ($productosActivos['status'] && $productosActivos['data']) {
                    foreach ($productosActivos['data'] as $producto) {
                        if ($producto['idproducto'] == $data['idproducto']) {
                            $productoValido = true;
                            break;
                        }
                    }
                }
                
                if (!$productoValido) {
                    $arr = array(
                        "status" => false,
                        "message" => "El producto seleccionado no es válido o no está disponible.",
                        "data" => null
                    );
                    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
                    die();
                }

                // Validar que el tipo de movimiento existe y está activo
                $tiposActivos = $this->model->getTiposMovimientoActivos();
                $tipoValido = false;
                if ($tiposActivos['status'] && $tiposActivos['data']) {
                    foreach ($tiposActivos['data'] as $tipo) {
                        if ($tipo['idtipomovimiento'] == $data['idtipomovimiento']) {
                            $tipoValido = true;
                            break;
                        }
                    }
                }
                
                if (!$tipoValido) {
                    $arr = array(
                        "status" => false,
                        "message" => "El tipo de movimiento seleccionado no es válido.",
                        "data" => null
                    );
                    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
                    die();
                }

                
                $result = $this->model->insertMovimiento($data);

                if ($result['status']) {
                    
                    $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                    $this->bitacoraModel->registrarAccion('movimientos', 'CREAR_MOVIMIENTO', $idusuario);
                }

                echo json_encode($result, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en createMovimiento: " . $e->getMessage());
                $arr = array(
                    "status" => false,
                    "message" => "Error al crear movimiento: " . $e->getMessage(),
                    "data" => null
                );
                echo json_encode($arr, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function updateMovimiento()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('movimientos', 'editar')) {
                $arr = array(
                    "status" => false,
                    "message" => "No tienes permisos para editar movimientos.",
                    "data" => null
                );
                echo json_encode($arr, JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);

                if (!$data || !isset($data['idmovimiento']) || !isset($data['nuevos_datos'])) {
                    $arr = array(
                        "status" => false,
                        "message" => "Datos inválidos. Se requiere idmovimiento y nuevos_datos.",
                        "data" => null
                    );
                    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idmovimiento = intval($data['idmovimiento']);
                $nuevosDatos = $data['nuevos_datos'];

                // Validación de seguridad: verificar que los IDs sean válidos si están presentes
                if (isset($nuevosDatos['idproducto'])) {
                    $productosActivos = $this->model->getProductosActivos();
                    $productoValido = false;
                    if ($productosActivos['status'] && $productosActivos['data']) {
                        foreach ($productosActivos['data'] as $producto) {
                            if ($producto['idproducto'] == $nuevosDatos['idproducto']) {
                                $productoValido = true;
                                break;
                            }
                        }
                    }
                    
                    if (!$productoValido) {
                        $arr = array(
                            "status" => false,
                            "message" => "El producto seleccionado no es válido o no está disponible.",
                            "data" => null
                        );
                        echo json_encode($arr, JSON_UNESCAPED_UNICODE);
                        die();
                    }
                }

                if (isset($nuevosDatos['idtipomovimiento'])) {
                    $tiposActivos = $this->model->getTiposMovimientoActivos();
                    $tipoValido = false;
                    if ($tiposActivos['status'] && $tiposActivos['data']) {
                        foreach ($tiposActivos['data'] as $tipo) {
                            if ($tipo['idtipomovimiento'] == $nuevosDatos['idtipomovimiento']) {
                                $tipoValido = true;
                                break;
                            }
                        }
                    }
                    
                    if (!$tipoValido) {
                        $arr = array(
                            "status" => false,
                            "message" => "El tipo de movimiento seleccionado no es válido.",
                            "data" => null
                        );
                        echo json_encode($arr, JSON_UNESCAPED_UNICODE);
                        die();
                    }
                }

                $result = $this->model->anularYCorregirMovimiento($idmovimiento, $nuevosDatos);

                if ($result['status']) {
                    $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                    $this->bitacoraModel->registrarAccion('movimientos', 'ANULAR_Y_CORREGIR_MOVIMIENTO', $idusuario);
                }

                echo json_encode($result, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en updateMovimiento: " . $e->getMessage());
                $arr = array(
                    "status" => false,
                    "message" => "Error al anular y corregir movimiento: " . $e->getMessage(),
                    "data" => null
                );
                echo json_encode($arr, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function anularMovimiento()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('movimientos', 'eliminar')) {
                $arr = array(
                    "status" => false,
                    "message" => "No tienes permisos para anular movimientos.",
                    "data" => null
                );
                echo json_encode($arr, JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);

                if (!$data || !isset($data['idmovimiento'])) {
                    $arr = array(
                        "status" => false,
                        "message" => "ID de movimiento no proporcionado.",
                        "data" => null
                    );
                    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idmovimiento = intval($data['idmovimiento']);

                $result = $this->model->anularMovimientoById($idmovimiento);

                if ($result['status']) {
                    $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                    $this->bitacoraModel->registrarAccion('movimientos', 'ANULAR_MOVIMIENTO', $idusuario);
                }

                echo json_encode($result, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en anularMovimiento: " . $e->getMessage());
                $arr = array(
                    "status" => false,
                    "message" => "Error al anular movimiento: " . $e->getMessage(),
                    "data" => null
                );
                echo json_encode($arr, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function deleteMovimiento()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('movimientos', 'eliminar')) {
                $arr = array(
                    "status" => false,
                    "message" => "No tienes permisos para eliminar movimientos.",
                    "data" => null
                );
                echo json_encode($arr, JSON_UNESCAPED_UNICODE);
                die();
            }

            $arr = array(
                "status" => false,
                "message" => "La eliminación directa no está disponible. Use la función de anular movimiento.",
                "data" => null
            );
            echo json_encode($arr, JSON_UNESCAPED_UNICODE);
            die();
        }
    }

    public function exportarMovimientos()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('movimientos', 'exportar')) {
                $arr = array(
                    "status" => false,
                    "message" => "No tienes permisos para exportar movimientos.",
                    "data" => null
                );
                echo json_encode($arr, JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                
                $movimientosResponse = $this->model->selectAllMovimientos();
                
                if ($movimientosResponse['status']) {
                    
                    $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                    $this->bitacoraModel->registrarAccion('movimientos', 'EXPORTAR_MOVIMIENTOS', $idusuario);
                }

                echo json_encode($movimientosResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en exportarMovimientos: " . $e->getMessage());
                $arr = array(
                    "status" => false,
                    "message" => "Error al exportar movimientos: " . $e->getMessage(),
                    "data" => null
                );
                echo json_encode($arr, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    
    public function getDatosFormulario()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('movimientos', 'ver')) {
                $arr = array(
                    "status" => false,
                    "message" => "No tienes permisos para ver datos de formularios.",
                    "data" => null
                );
                echo json_encode($arr, JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                
                $productosResponse = $this->model->getProductosActivos();
                $tiposResponse = $this->model->getTiposMovimientoActivos();

                $arr = array(
                    "status" => true,
                    "message" => "Datos obtenidos correctamente.",
                    "data" => [
                        'productos' => $productosResponse['data'] ?? [],
                        'tipos_movimiento' => $tiposResponse['data'] ?? []
                    ]
                );

                echo json_encode($arr, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getDatosFormulario: " . $e->getMessage());
                $arr = array(
                    "status" => false,
                    "message" => "Error al obtener datos del formulario.",
                    "data" => null
                );
                echo json_encode($arr, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    
    public function buscarMovimientos()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('movimientos', 'ver')) {
                $arr = array(
                    "status" => false,
                    "message" => "No tienes permisos para buscar movimientos.",
                    "data" => null
                );
                echo json_encode($arr, JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $criterio = $_GET['criterio'] ?? '';
                
                
                $movimientosResponse = $this->model->buscarMovimientos($criterio);
                echo json_encode($movimientosResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en buscarMovimientos: " . $e->getMessage());
                $arr = array(
                    "status" => false,
                    "message" => "Error en la búsqueda.",
                    "data" => null
                );
                echo json_encode($arr, JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }
}
?>