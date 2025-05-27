<?php
require_once "app/core/Controllers.php";

require_once "helpers/helpers.php";

class Ventas extends Controllers
{
     public function set_model($model)
    {
        $this->model = $model;
    }

    public function get_model()
    {
        return $this->model;
    }

    public function __construct()
    {
     parent::__construct();
    // Cargar el modelo aquí si no se hace automáticamente
    $this->load_model('VentasModel'); // Asumiendo que tienes un método loadModel
    // O directamente:
    // require_once "models/VentasModel.php"; // Ajusta la ruta
    // $this->set_model(new VentasModel());
    }


    public function index()
    {
        $data['page_title'] = "Listado de Ventas";
        $data['page_name'] = "Ventas";
        $data['page_functions_js'] = "functions_ventas.js";
        $this->views->getView($this, "ventas", $data);
    }

    // Método para obtener datos de ventas para DataTables
    public function getventasData()
    {
        try {
            $arrData = $this->model->obtenerTodasLasVentasConCliente();

            $response = [
                "draw" => intval($_GET['draw'] ?? 0) + 1,
                "recordsTotal" => count($arrData),
                "recordsFiltered" => count($arrData),
                "data" => $arrData ?: []
            ];

            header('Content-Type: application/json');
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                "status" => false,
                "message" => "Error al obtener los datos de ventas: " . $e->getMessage(),
                "data" => []
            ], JSON_UNESCAPED_UNICODE);
        }
        exit();
    }

    // Método para procesar las peticiones tipo API
    public function procesarPeticion()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        
        if ($method === 'GET') {
            $this->manejarGET();
        } elseif ($method === 'POST') {
            $this->manejarPOST();
        } else {
            $this->responderError("Método no permitido", 405);
        }
    }

    private function manejarGET()
    {
        $accion = $_GET['accion'] ?? '';
        
        try {
            switch ($accion) {
                case 'listar':
                    $ventas = $this->model->obtenerVentas();
                    $this->responderExito(['ventas' => $ventas]);
                    break;
                    
                case 'obtener_clientes':
                    $clientes = $this->model->obtenerClientes();
                    $this->responderJSON($clientes);
                    break;
                    
                case 'obtener_productos':
                    $productos = $this->model->obtenerProductos();
                    $this->responderJSON($productos);
                    break;
                    
                case 'obtener_detalle':
                    $id = $_GET['id'] ?? 0;
                    if ($id > 0) {
                        $detalle = $this->model->obtenerDetalleVenta($id);
                        $venta = $this->model->obtenerVentaPorId($id);
                        $this->responderExito(['detalle' => $detalle, 'venta' => $venta]);
                    } else {
                        $this->responderError('ID de venta no válido');
                    }
                    break;
                    
                default:
                    $this->responderError('Acción no válida');
                    break;
            }
        } catch (Exception $e) {
            error_log("Error en manejarGET: " . $e->getMessage());
            $this->responderError($e->getMessage());
        }
    }

    private function manejarPOST()
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            $this->responderError('Datos JSON no válidos');
            return;
        }
        
        $accion = $data['accion'] ?? '';
        
        try {
            switch ($accion) {
                case 'crear':
                    $this->crearVenta($data);
                    break;
                    
                case 'eliminar':
                    $this->eliminarVenta($data);
                    break;
                    
                default:
                    $this->responderError('Acción no válida');
                    break;
            }
        } catch (Exception $e) {
            error_log("Error en manejarPOST: " . $e->getMessage());
            $this->responderError($e->getMessage());
        }
    }

    // Método para crear venta (compatible con el frontend JavaScript)
    private function crearVenta($data)
    {
        // Validar datos requeridos
        if (empty($data['idcliente']) || empty($data['fecha_venta']) || empty($data['detalles'])) {
            $this->responderError('Faltan datos requeridos');
            return;
        }

        $idcliente = $data['idcliente'];
        $fecha_venta = $data['fecha_venta'];
        $total_venta = $data['total_venta'] ?? 0;
        $detalles = $data['detalles'];

        // Validar que hay detalles
        if (!is_array($detalles) || empty($detalles)) {
            $this->responderError('La venta debe tener al menos un producto');
            return;
        }

        $resultado = $this->model->crearVenta($idcliente, $fecha_venta, $total_venta, $detalles);
        
        if ($resultado['success']) {
            $this->responderExito($resultado);
        } else {
            $this->responderError($resultado['message']);
        }
    }

    // Método para eliminar/desactivar venta
    private function eliminarVenta($data)
    {
        $id = $data['id'] ?? 0;
        
        if ($id <= 0) {
            $this->responderError('ID de venta no válido');
            return;
        }

        $resultado = $this->model->eliminarVenta($id);
        
        if ($resultado['success']) {
            $this->responderExito($resultado);
        } else {
            $this->responderError($resultado['message']);
        }
    }

    // Métodos para endpoints específicos (compatibles con el nuevo frontend)
    public function buscar()
    {
        try {
            $criterio = $_GET['criterio'] ?? '';
            
            if (strlen($criterio) < 2) {
                $this->responderJSON([]);
                return;
            }

            $clientes = $this->model->buscarClientes($criterio);
            $this->responderJSON($clientes);
        } catch (Exception $e) {
            error_log("Error en buscar clientes: " . $e->getMessage());
            $this->responderError($e->getMessage());
        }
    }

    public function getListaProductosParaFormulario()
    {
        try {
            $productos = $this->model->getListaProductosParaFormulario();
            $this->responderJSON($productos);
        } catch (Exception $e) {
            error_log("Error al obtener productos: " . $e->getMessage());
            $this->responderError($e->getMessage());
        }
    }

    public function getMonedasActivas()
    {
        try {
            $monedas = $this->model->getMonedasActivas();
            $this->responderJSON($monedas);
        } catch (Exception $e) {
            error_log("Error al obtener monedas: " . $e->getMessage());
            $this->responderError($e->getMessage());
        }
    }

    // Métodos públicos para compatibilidad con el frontend anterior
    public function createventa()
    {
        $this->procesarPeticion();
    }

    public function deleteventa()
    {
        $this->procesarPeticion();
    }

    // Métodos de respuesta
    private function responderJSON($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }

    private function responderExito($data = [], $mensaje = 'Operación exitosa')
    {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $mensaje,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    private function responderError($mensaje, $codigo = 400)
    {
        http_response_code($codigo);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $mensaje
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
}
?>
