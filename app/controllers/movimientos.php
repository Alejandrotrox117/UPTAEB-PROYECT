<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
require_once "helpers/PermisosModuloVerificar.php";
require_once "app/models/bitacoraModel.php";
require_once "helpers/bitacora_helper.php";

class Movimientos extends Controllers
{
    private $bitacoraModel;
    private $BitacoraHelper;

    public function __construct()
    {
        parent::__construct();
        
        $this->bitacoraModel = new BitacoraModel();
        $this->BitacoraHelper = new BitacoraHelper();

        // Asegurar que la sesión esté iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Verificar si el usuario está logueado antes de verificar permisos
        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        // ✅ USAR EL SISTEMA DE PERMISOS INTEGRADO
        if (!PermisosModuloVerificar::verificarAccesoModulo('movimientos')) {
            $this->views->getView($this, "permisos");
            exit();
        }
    }

    public function index()
    {
        // ✅ VERIFICAR PERMISOS PARA VER
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('movimientos', 'ver')) {
            $this->views->getView($this, "permisos");
            exit();
        }

        // ✅ REGISTRAR EN BITÁCORA
        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        BitacoraHelper::registrarAccesoModulo('movimientos', $idusuario, $this->bitacoraModel);

        // ✅ OBTENER PERMISOS USANDO EL SISTEMA INTEGRADO
        $permisos = PermisosModuloVerificar::getPermisosUsuarioModulo('movimientos');

        $data['page_tag'] = "Movimientos";
        $data['page_title'] = "Gestión de Movimientos";
        $data['page_name'] = "movimientos";
        $data['page_content'] = "Gestión integral de movimientos de inventario";
        $data['page_functions_js'] = "functions_movimientos.js";
        $data['permisos'] = $permisos; // ✅ PASAR PERMISOS AL DATA
        
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
                // ✅ USAR EL MÉTODO ENCAPSULADO DEL MODELO
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

                // ✅ USAR EL MÉTODO ENCAPSULADO DEL MODELO
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

    // ✅ MÉTODO FALTANTE AGREGADO
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
                // ✅ USAR EL MÉTODO ENCAPSULADO DEL MODELO
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

                // ✅ USAR EL MÉTODO ENCAPSULADO DEL MODELO
                $result = $this->model->insertMovimiento($data);

                if ($result['status']) {
                    // ✅ REGISTRAR EN BITÁCORA
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

                if (!$data || !isset($data['idmovimiento'])) {
                    $arr = array(
                        "status" => false,
                        "message" => "Datos inválidos recibidos.",
                        "data" => null
                    );
                    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idmovimiento = intval($data['idmovimiento']);
                unset($data['idmovimiento']); // ✅ REMOVER ID DE LOS DATOS

                // ✅ USAR EL MÉTODO ENCAPSULADO DEL MODELO
                $result = $this->model->updateMovimiento($idmovimiento, $data);

                if ($result['status']) {
                    // ✅ REGISTRAR EN BITÁCORA
                    $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                    $this->bitacoraModel->registrarAccion('movimientos', 'ACTUALIZAR_MOVIMIENTO', $idusuario);
                }

                echo json_encode($result, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en updateMovimiento: " . $e->getMessage());
                $arr = array(
                    "status" => false,
                    "message" => "Error al actualizar movimiento: " . $e->getMessage(),
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

            try {
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);

                if (!$data || !isset($data['idmovimiento'])) {
                    $arr = array(
                        "status" => false,
                        "message" => "ID de movimiento no especificado.",
                        "data" => null
                    );
                    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
                    die();
                }

                $idmovimiento = intval($data['idmovimiento']);

                // ✅ USAR EL MÉTODO ENCAPSULADO DEL MODELO
                $result = $this->model->deleteMovimientoById($idmovimiento);

                if ($result['status']) {
                    // ✅ REGISTRAR EN BITÁCORA
                    $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                    $this->bitacoraModel->registrarAccion('movimientos', 'ELIMINAR_MOVIMIENTO', $idusuario);
                }

                echo json_encode($result, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en deleteMovimiento: " . $e->getMessage());
                $arr = array(
                    "status" => false,
                    "message" => "Error al eliminar movimiento: " . $e->getMessage(),
                    "data" => null
                );
                echo json_encode($arr, JSON_UNESCAPED_UNICODE);
            }
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
                // ✅ USAR EL MÉTODO ENCAPSULADO DEL MODELO
                $movimientosResponse = $this->model->selectAllMovimientos();
                
                if ($movimientosResponse['status']) {
                    // ✅ REGISTRAR EN BITÁCORA
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

    // ✅ MÉTODO ADICIONAL PARA OBTENER DATOS PARA FORMULARIOS
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
                // ✅ USAR LOS MÉTODOS ENCAPSULADOS DEL MODELO
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

    // ✅ MÉTODO PARA BÚSQUEDA (OPCIONAL)
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
                
                // ✅ USAR EL MÉTODO ENCAPSULADO DEL MODELO
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