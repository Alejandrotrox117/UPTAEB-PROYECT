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
            $loginUrl = base_url() . '/login';
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
            $arrData = $this->model->getVentasDatatable();


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
    public function setVenta()
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

        // Leer datos JSON
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$data) {
            echo json_encode(['status' => false, 'message' => 'Datos no válidos.']);
            return;
        }

        try {
            // Separar datos del cliente nuevo y detalles
            $datosClienteNuevo = $data['cliente_nuevo'] ?? null;
            $detalles = $data['detalles'] ?? [];

            // Remover datos que no van a la tabla venta
            unset($data['cliente_nuevo']);
            unset($data['detalles']);

            // Validar que los campos principales estén presentes
            $camposObligatorios = [
                'fecha_venta',
                'idmoneda_general',
                'subtotal_general',
                'descuento_porcentaje_general',
                'monto_descuento_general',
                'total_general',
                'estatus'
            ];

            foreach ($camposObligatorios as $campo) {
                if (!isset($data[$campo]) || $data[$campo] === '') {
                    echo json_encode(['status' => false, 'message' => "Falta el campo obligatorio: $campo"]);
                    return;
                }
            }

            // Validar que hay un cliente seleccionado O datos de cliente nuevo
            if (!$data['idcliente'] && !$datosClienteNuevo) {
                echo json_encode([
                    'status' => false,
                    'message' => 'Debe seleccionar un cliente existente o proporcionar datos del cliente nuevo.'
                ]);
                return;
            }

            // Validar datos del cliente nuevo si se proporcionan
            if ($datosClienteNuevo) {
                $erroresCliente = $this->model->validarDatosCliente($datosClienteNuevo);
                if (!empty($erroresCliente)) {
                    echo json_encode([
                        'status' => false,
                        'message' => 'Errores en datos del cliente: ' . implode(', ', $erroresCliente)
                    ]);
                    return;
                }
            }

            // Validar que hay detalles
            if (empty($detalles)) {
                echo json_encode([
                    'status' => false,
                    'message' => 'La venta debe tener al menos un producto.'
                ]);
                return;
            }

            // Validar cada detalle
            foreach ($detalles as $index => $detalle) {
                if (empty($detalle['idproducto']) || empty($detalle['cantidad']) || empty($detalle['precio_unitario_venta'])) {
                    echo json_encode([
                        'status' => false,
                        'message' => "Detalle " . ($index + 1) . " tiene datos incompletos."
                    ]);
                    return;
                }
            }

            // Crear la venta con cliente (nuevo o existente)
            $resultado = $this->model->insertVenta($data, $detalles, $datosClienteNuevo);

            if ($resultado['success']) {
                echo json_encode([
                    'status' => true,
                    'message' => $resultado['message'],
                    'data' => [
                        'idventa' => $resultado['idventa'],
                        'idcliente' => $resultado['idcliente'],
                        'nro_venta' => $resultado['nro_venta']
                    ]
                ]);
            } else {
                echo json_encode([
                    'status' => false,
                    'message' => $resultado['message'] ?? 'Error al crear la venta'
                ]);
            }
        } catch (Exception $e) {
            error_log("Error en insertVentaConCliente: " . $e->getMessage());
            echo json_encode([
                'status' => false,
                'message' => 'Error al procesar la venta: ' . $e->getMessage()
            ]);
        }
        exit();
    }
  

public function getProductosLista() {
    header('Content-Type: application/json');
    try {
        $modelo = $this->get_model();
        $productos = $modelo->obtenerProductos();
        echo json_encode([
            'status' => true,
            'data' => $productos
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => false,
            'message' => 'Error al obtener productos: ' . $e->getMessage(),
            'data' => []
        ]);
    }
    exit();
}
  public function getMonedas()
    {
        try {
            $monedas = $this->model->getMonedasActivas();
            echo json_encode(["status" => true, "data" => $monedas]);
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
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
    public function getTasa()
    {
        if (!$this->verificarUsuarioLogueado()) {
            echo json_encode(['tasa' => 1]);
            exit;
        }

        $codigo = $_GET['codigo_moneda'] ?? '';
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        if (!$codigo) {
            echo json_encode(['tasa' => 1]);
            exit;
        }

        // Llama al modelo para obtener la tasa
        $tasa = $this->model->getTasaPorCodigoYFecha($codigo, $fecha);
        echo json_encode(['tasa' => $tasa]);
        exit;
    }
    public function getVentaDetalle()
    {
        if (!$this->verificarUsuarioLogueado()) {
            echo json_encode(['status' => false, 'message' => 'Usuario no autenticado']);
            exit();
        }
        $idventa = intval($_GET['idventa'] ?? 0);
        if ($idventa <= 0) {
            echo json_encode(['status' => false, 'message' => 'ID de venta no válido']);
            exit();
        }
        try {
            $venta = $this->model->obtenerVentaPorId($idventa);
            $detalle = $this->model->obtenerDetalleVenta($idventa);
            echo json_encode([
                'status' => true,
                'venta' => $venta,
                'detalle' => $detalle
            ]);
        } catch (Exception $e) {
            echo json_encode(['status' => false, 'message' => $e->getMessage()]);
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

   
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        echo json_encode([
            'status' => false,
            'message' => 'Datos no válidos'
        ]);
        return;
    }

    $id = intval($data['id'] ?? 0);
    
    if ($id > 0) {
        try {
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
        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'message' => 'Error al desactivar la venta: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'ID de venta no válido'
        ]);
    }
    exit();
}

}
