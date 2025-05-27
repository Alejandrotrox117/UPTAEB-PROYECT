<?php
require_once "app/core/Controllers.php";
require_once "helpers/permisosVerificar.php";
require_once "helpers/PermisosHelper.php";
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
        
        // Asegurar que la sesión esté iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar si el usuario está logueado antes de verificar permisos
        if (!$this->verificarUsuarioLogueado()) {
            $this->redirigirLogin();
            return;
        }
        
        // Solo verificar permisos si está logueado
        permisosVerificar::verificarAccesoModulo('Ventas');
    }

    /**
     * Verifica si el usuario está logueado
     */
    private function verificarUsuarioLogueado(): bool
    {
        // Verificar múltiples formas de identificar al usuario logueado
        $tieneLogin = isset($_SESSION['login']) && $_SESSION['login'] === true;
        $tieneIdUser = isset($_SESSION['idUser']) && !empty($_SESSION['idUser']);
        $tieneUsuarioId = isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
        
        return $tieneLogin && ($tieneIdUser || $tieneUsuarioId);
    }

    /**
     * Obtiene el ID del usuario de la sesión
     */
    private function obtenerIdUsuario(): ?int
    {
        if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
            return intval($_SESSION['usuario_id']);
        } elseif (isset($_SESSION['idUser']) && !empty($_SESSION['idUser'])) {
            return intval($_SESSION['idUser']);
        }
        return null;
    }

    /**
     * Redirige al login
     */
    private function redirigirLogin()
    {
        if (function_exists('base_url')) {
            $loginUrl = base_url() . 'login';
        } else {
            $loginUrl = '/project/login';
        }
        
        header('Location: ' . $loginUrl);
        exit;
    }

    public function index()
    {
        // Doble verificación de seguridad
        if (!$this->verificarUsuarioLogueado()) {
            $this->redirigirLogin();
            return;
        }

        // Obtener ID del usuario
        $idUsuario = $this->obtenerIdUsuario();
        
        if (!$idUsuario) {
            error_log("Ventas::index - No se pudo obtener ID de usuario");
            $this->redirigirLogin();
            return;
        }

        try {
            $permisos = PermisosHelper::getPermisosDetalle($idUsuario, 'Ventas');
        } catch (Exception $e) {
            error_log("Error al obtener permisos: " . $e->getMessage());
            // Permisos por defecto (sin acceso)
            $permisos = [
                'puede_ver' => false,
                'puede_crear' => false,
                'puede_editar' => false,
                'puede_eliminar' => false,
                'acceso_total' => false
            ];
        }

        $data['page_id'] = 2;
        $data['page_tag'] = "Ventas";
        $data['page_title'] = "Página - Ventas";
        $data['page_name'] = "ventas";
        $data['page_functions_js'] = "functions_ventas.js";
        
        // Pasar permisos a la vista
        $data['permisos'] = $permisos;
        
        $this->views->getView($this, "ventas", $data);
    }

    public function getventasData()
    {
        if (!$this->verificarUsuarioLogueado()) {
            header('Content-Type: application/json');
            echo json_encode([
                "status" => false,
                "message" => "Usuario no autenticado",
                "data" => []
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        // Usar el nombre del módulo consistente con PermisosHelper
        if (!permisosVerificar::verificarPermisoAccion('Ventas', 'ver')) {
            header('Content-Type: application/json');
            echo json_encode([
                "status" => false,
                "message" => "No tiene permisos para ver las ventas",
                "data" => []
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        try {
            $arrData = $this->model->obtenerTodasLasVentasConCliente();

            // Para DataTables del lado del cliente, no necesitas draw, recordsTotal, recordsFiltered
            // Solo necesitas el array de datos directamente si serverSide es false.
            // Si tu DataTable está configurado con serverSide: false, la respuesta debería ser:
            // echo json_encode(["data" => $arrData ?: []], JSON_UNESCAPED_UNICODE);

            // Si mantienes serverSide: true o quieres una respuesta más completa:
            $response = [
                "draw" => intval($_GET['draw'] ?? 0) + 1, // Opcional si serverSide es false
                "recordsTotal" => count($arrData),      // Opcional si serverSide es false
                "recordsFiltered" => count($arrData),   // Opcional si serverSide es false
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
    public function insertVenta()
    {
        if (!$this->verificarUsuarioLogueado()) {
            echo json_encode([
                'status' => false, 
                'message' => 'Usuario no autenticado.'
            ]);
            return;
        }

        if (!permisosVerificar::verificarPermisoAccion('Ventas', 'crear')) {
            echo json_encode([
                'status' => false, 
                'message' => 'No tiene permisos para crear ventas.'
            ]);
            return;
        }

        if ($_POST) {
            try {
                // Obtener datos del formulario
                $idcliente = $_POST['idcliente'] ?? 0;
                $fecha_venta = $_POST['fecha_venta'] ?? '';
                $subtotal_general = floatval($_POST['subtotal_general'] ?? 0);
                $monto_descuento_general = floatval($_POST['monto_descuento_general'] ?? 0);
                $total_general = floatval($_POST['total_general'] ?? 0);
                $observaciones = $_POST['observaciones'] ?? '';
                
                // Procesar detalles
                $detalles = [];
                if (isset($_POST['detalle']) && is_array($_POST['detalle'])) {
                    foreach ($_POST['detalle'] as $item) {
                        $detalles[] = [
                            'detalle_idproducto' => intval($item['idproducto']),
                            'detalle_cantidad' => intval($item['cantidad']),
                            'detalle_precio' => floatval($item['precio_unitario']),
                            'detalle_total' => floatval($item['cantidad']) * floatval($item['precio_unitario'])
                        ];
                    }
                }

                $resultado = $this->model->crearVenta($idcliente, $fecha_venta, $total_general, $detalles);
                
                if ($resultado['success']) {
                    echo json_encode([
                        'status' => true,
                        'message' => $resultado['message']
                    ]);
                } else {
                    echo json_encode([
                        'status' => false,
                        'message' => $resultado['message']
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    'status' => false,
                    'message' => 'Error al procesar la venta: ' . $e->getMessage()
                ]);
            }
        }
        exit();
    }

    public function buscarClientes()
    {
        if (!$this->verificarUsuarioLogueado()) {
            echo json_encode([
                'status' => false,
                'message' => 'Usuario no autenticado'
            ]);
            return;
        }

        if (!permisosVerificar::verificarPermisoAccion('Ventas', 'ver')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para buscar clientes'
            ]);
            return;
        }

        if ($_POST) {
            $criterio = $_POST['criterio'] ?? '';
            
            if (strlen($criterio) >= 2) {
                try {
                    $clientes = $this->model->buscarClientes($criterio);
                    echo json_encode([
                        'status' => true,
                        'data' => $clientes
                    ]);
                } catch (Exception $e) {
                    echo json_encode([
                        'status' => false,
                        'message' => 'Error al buscar clientes: ' . $e->getMessage()
                    ]);
                }
            } else {
                echo json_encode([
                    'status' => false,
                    'message' => 'Ingrese al menos 2 caracteres'
                ]);
            }
        }
        exit();
    }

    public function getProductosDisponibles()
    {
        if (!$this->verificarUsuarioLogueado()) {
            echo json_encode([
                'status' => false,
                'message' => 'Usuario no autenticado'
            ]);
            return;
        }

        if (!permisosVerificar::verificarPermisoAccion('Ventas', 'ver')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para ver productos'
            ]);
            return;
        }

        try {
            $productos = $this->model->getListaProductosParaFormulario();
            echo json_encode([
                'status' => true,
                'data' => $productos
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'message' => 'Error al obtener productos: ' . $e->getMessage()
            ]);
        }
        exit();
    }

    public function getMonedasDisponibles()
    {
        if (!$this->verificarUsuarioLogueado()) {
            echo json_encode([
                'status' => false,
                'message' => 'Usuario no autenticado'
            ]);
            return;
        }

        if (!permisosVerificar::verificarPermisoAccion('Ventas', 'ver')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para ver monedas'
            ]);
            return;
        }

        try {
            $monedas = $this->model->getMonedasActivas();
            echo json_encode([
                'status' => true,
                'data' => $monedas
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'message' => 'Error al obtener monedas: ' . $e->getMessage()
            ]);
        }
        exit();
    }

    public function deleteVenta()
    {
        if (!$this->verificarUsuarioLogueado()) {
            echo json_encode([
                'status' => false,
                'message' => 'Usuario no autenticado'
            ]);
            return;
        }

        if (!permisosVerificar::verificarPermisoAccion('Ventas', 'eliminar')) {
            echo json_encode([
                'status' => false, 
                'message' => 'No tiene permisos para eliminar ventas.'
            ]);
            return;
        }

        if ($_POST) {
            $id = intval($_POST['id'] ?? 0);
            
            if ($id > 0) {
                $resultado = $this->model->eliminarVenta($id);
                
                if ($resultado['success']) {
                    echo json_encode([
                        'status' => true,
                        'message' => $resultado['message']
                    ]);
                } else {
                    echo json_encode([
                        'status' => false,
                        'message' => $resultado['message']
                    ]);
                }
            } else {
                echo json_encode([
                    'status' => false,
                    'message' => 'ID de venta no válido'
                ]);
            }
        }
        exit();
    }
}
?>
