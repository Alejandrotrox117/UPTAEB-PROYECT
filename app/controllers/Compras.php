<?php
require_once "app/core/Controllers.php";
require_once "app/models/ComprasModel.php";
require_once "helpers/helpers.php";
require_once "helpers/PermisosModuloVerificar.php";
require_once "app/models/bitacoraModel.php";
require_once "helpers/bitacora_helper.php";
require_once "app/models/notificacionesModel.php";

class Compras extends Controllers
{
    private $notificacionesModel;
    private $bitacoraModel;
    private $BitacoraHelper;

    public function __construct() {
        parent::__construct();
        
        $this->bitacoraModel = new BitacoraModel();
        $this->BitacoraHelper = new BitacoraHelper();
        $this->notificacionesModel = new NotificacionesModel();

        // Verificar sesión de usuario
        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        // Verificar acceso al módulo de compras
        if (!PermisosModuloVerificar::verificarAccesoModulo('compras')) {
            $this->views->getView($this, "permisos");
            exit();
        }
    }

    public function set_model($model)
    {
        $this->model = $model;
    }

    public function get_model()
    {
        return $this->model;
    }

    public function index(){
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'ver')) {
            $this->views->getView($this, "permisos");
            exit();
        }
        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        BitacoraHelper::registrarAccesoModulo('compras', $idusuario, $this->bitacoraModel);

        $permisos = [
            'puedeVer' => PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'ver'),
            'puedeCrear' => PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'crear'),
            'puedeEditar' => PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'editar'),
            'puedeEliminar' => PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'eliminar')
        ];

        $data['page_title'] = "Gestión de Compras";
        $data['page_name'] = "Listado de Compras";
        $data['page_functions_js'] = "functions_compras.js";
        $data['permisos'] = $permisos;
        
        // Agregar información del usuario autenticado para JavaScript
        $data['idRolUsuarioAutenticado'] = $_SESSION['rol_id'] ?? 0;
        $data['rolUsuarioAutenticado'] = $_SESSION['rol_nombre'] ?? '';
        
        $this->views->getView($this, "compras", $data);
    }

    
    public function getComprasDataTable(){
        header('Content-Type: application/json');
        
        // Verificar permiso específico para ver
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'ver')) {
            echo json_encode(['data' => []], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        // Obtener ID del usuario actual
        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        
        // Obtener compras (activas para usuarios normales, todas para super usuarios)
        $arrData = $this->get_model()->selectAllCompras($idusuario);
        echo json_encode(['data' => $arrData], JSON_UNESCAPED_UNICODE);
        exit();
    }

    
    public function getListaMonedasParaFormulario() {
        header('Content-Type: application/json');
        $modelo = $this->get_model();
        $monedas = $modelo->getMonedasActivas();
        echo json_encode($monedas);
        exit();
    }

    
    public function getTasasMonedasPorFecha(){
        header('Content-Type: application/json');
        if (!isset($_GET['fecha'])) {
            echo json_encode(['status' => false, 'message' => 'Fecha requerida']);
            exit();
        }
        $fecha = $_GET['fecha'];
        $tasas = $this->get_model()->getTasasPorFecha($fecha);
        echo json_encode(['status' => true, 'tasas' => $tasas]);
        exit();
    }

    
    public function getListaProductosParaFormulario() {
        header('Content-Type: application/json');
        $modelo = $this->get_model();
        $productos = $modelo->getProductosConCategoria();
        echo json_encode($productos);
        exit();
    }

    
    public function buscarProveedores() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['term'])) {
            $termino = $_GET['term'];
            $modelo = $this->get_model();
            $proveedores = $modelo->buscarProveedor($termino);
            echo json_encode($proveedores);
        } else {
            echo json_encode([]);
        }
        exit();
    }

    
    public function getUltimoPesoRomana() {
        $filePath = 'C:\com_data\peso_mysql.json';
        
        if (!file_exists($filePath)) {
            echo json_encode([
                'status' => false,
                'message' => 'Archivo de peso no encontrado'
            ]);
            return;
        }
        
        $jsonData = file_get_contents($filePath);
        $data = json_decode($jsonData, true);
        
        if ($data === null) {
            echo json_encode([
                'status' => false,
                'message' => 'Error al leer datos de peso'
            ]);
            return;
        }
        
        echo json_encode([
            'status' => true, 
            'peso' => $data["peso_numerico"],
            'fecha_hora' => $data["fecha_hora"],
        ]);
    }

    
    public function setCompra(){
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(
        ['status' => false, 'message' => 'Método no permitido.'],
        JSON_UNESCAPED_UNICODE
        );
        exit();
    }        // Verificar permisos de creación
        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'crear')) {
            echo json_encode([
                'status' => false, 
                'message' => 'No tiene permisos para registrar compras.'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

    $modelo = $this->get_model();
    $response = ['status' => false, 'message' => 'Error desconocido.'];

    $idproveedor = intval($_POST['idproveedor_seleccionado'] ?? 0);
    date_default_timezone_set('America/Caracas');
    $fecha_compra = $_POST['fecha_compra'] ?? date('Y-m-d');
    $idmoneda_general = 3;
    $observaciones_compra = $_POST['observaciones_compra'] ?? '';
    $total_general_compra = floatval(
        $_POST['total_general_input'] ?? 0
    );

    if (empty($idproveedor)) {
        $response['message'] = 'Falta seleccionar proveedor.';
        echo json_encode($response);
        exit();
    }
    if (empty($fecha_compra)) {
        $response['message'] = 'Falta fecha de compra.';
        echo json_encode($response);
        exit();
    }
    if (!isset($_POST['productos_detalle'])) {
        $response['message'] = 'Faltan productos en el detalle.';
        echo json_encode($response);
        exit();
    }

    $nro_compra = $modelo->generarNumeroCompra();
    if (strpos($nro_compra, 'ERROR') !== false) {
        $response['message'] = 'Error al generar el número de compra.';
        echo json_encode($response);
        exit();
    }

    $subtotal_general_compra = $total_general_compra;
    $descuento_porcentaje_general = 0;
    $monto_descuento_general = 0;

    $datosCompra = [
        'nro_compra' => $nro_compra,
        'fecha_compra' => $fecha_compra,
        'idproveedor' => $idproveedor,
        'idmoneda_general' => $idmoneda_general,
        'subtotal_general_compra' => $subtotal_general_compra,
        'descuento_porcentaje_compra' => $descuento_porcentaje_general,
        'monto_descuento_compra' => $monto_descuento_general,
        'total_general_compra' => $total_general_compra,
        'observaciones_compra' => $observaciones_compra,
    ];

    $detallesCompraInput = json_decode(
        $_POST['productos_detalle'], true
    );
    if (
        json_last_error() !== JSON_ERROR_NONE ||
        empty($detallesCompraInput)
    ) {
        $response['message'] =
        'No hay productos en el detalle o el formato es ' .
        'incorrecto.';
        echo json_encode($response);
        exit();
    }

    $detallesParaGuardar = [];
    foreach ($detallesCompraInput as $item) {
        $idProductoItem = intval($item['idproducto'] ?? 0);
        if ($idProductoItem <= 0) {
        $response['message'] =
            'ID de producto inválido en el detalle.';
        echo json_encode($response);
        exit();
        }

        $productoInfo = $modelo->getProductoById($idProductoItem);
        if (!$productoInfo) {
        $response['message'] = 'Producto no encontrado: ID ' . $idProductoItem;
        echo json_encode($response);
        exit();
        }

        $cantidad_final = floatval($item['cantidad'] ?? 0);
        $peso_vehiculo = isset($item['peso_vehiculo']) ? floatval($item['peso_vehiculo']) : null;
        $peso_bruto = isset($item['peso_bruto']) ? floatval($item['peso_bruto']) : null;
        $peso_neto = isset($item['peso_neto']) ? floatval($item['peso_neto']) : null;

        if ($cantidad_final <= 0) {
        $response['message'] = 'Cantidad debe ser mayor a cero para: ' .htmlspecialchars($productoInfo['nombre'], ENT_QUOTES, 'UTF-8');
        echo json_encode($response);
        exit();
        }
        if (floatval($item['precio_unitario_compra'] ?? 0) <= 0) {
        $response['message'] = 'Precio debe ser mayor a cero para: ' .htmlspecialchars($productoInfo['nombre'], ENT_QUOTES, 'UTF-8');
        echo json_encode($response);
        exit();
        }

        $moneda_detalle = !empty($item['moneda']) ? intval($item['moneda']) : 3;
        if ($moneda_detalle <= 0) {
        $moneda_detalle = 3;
        }

        $detallesParaGuardar[] = [
            'idproducto' => $productoInfo['idproducto'],
            'descripcion_temporal_producto' => $item['nombre_producto'] ?? $productoInfo['nombre'],
            'cantidad' => $cantidad_final,
            'descuento' => floatval($item['descuento'] ?? 0),
            'precio_unitario_compra' => floatval($item['precio_unitario_compra'] ?? 0),
            'idmoneda_detalle' => $moneda_detalle,
            'subtotal_linea' => floatval($item['subtotal_linea'] ?? 0),
            'subtotal_original_linea' => floatval($item['subtotal_original_linea'] ?? 0),
            'monto_descuento_linea' => floatval($item['monto_descuento_linea'] ?? 0),
            'peso_vehiculo' => $peso_vehiculo,
            'peso_bruto' => $peso_bruto,
            'peso_neto' => $peso_neto,
        ];
    }

    if (empty($detallesParaGuardar)) {
        $response['message'] = 'No se procesaron productos válidos.';
        echo json_encode($response);
        exit();
    }

    try {
        $idCompraInsertada = $modelo->insertarCompra($datosCompra, $detallesParaGuardar);
    } catch (Exception $e) {
        $response = ['status'  => false,'message' => 'Error técnico: ' . $e->getMessage()];
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    if ($idCompraInsertada) {
        // Registrar en bitácora la creación de la compra
        $resultadoBitacora = $this->bitacoraModel->registrarAccion('compras', 'INSERTAR', $idusuario);
        
        if (!$resultadoBitacora) {
            error_log("Warning: No se pudo registrar en bitácora la creación de la compra ID: $idCompraInsertada");
        }

        $response = ['status'  => true,'message' =>'Compra registrada correctamente con Nro: ' .htmlspecialchars($nro_compra, ENT_QUOTES, 'UTF-8'),'idcompra' => $idCompraInsertada,];
    } else {
        $response = ['status'  => false,'message' => 'Error al registrar la compra.'];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
    }
    
    public function getCompraById(int $idcompra)
    {
        if ($idcompra > 0) {
            $compra = $this->get_model()->getCompraById($idcompra);
            $detalles = $this->get_model()->getDetalleCompraById($idcompra);
            
            if (empty($compra)) {
                $response = ["status" => false, "message" => "Compra no encontrada."];
            } else {
                $response = [
                    "status" => true, 
                    "data" => [
                        "compra" => $compra,
                        "detalles" => $detalles
                    ]
                ];
            }
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    
    public function deleteCompra(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(
                ["status"  => false,"message" => "Método no permitido."],JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Verificar permisos de eliminación
        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'eliminar')) {
            echo json_encode([
                'status' => false, 
                'message' => 'No tiene permisos para eliminar compras.'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $idcompra = isset($data['idcompra']) ? intval($data['idcompra']) : 0;

        if ($idcompra <= 0) {
            echo json_encode(
                ["status"  => false,"message" => "ID de compra no válido."],JSON_UNESCAPED_UNICODE);
            exit;
        }

        $requestDelete = $this->get_model()->deleteCompraById($idcompra);

        if ($requestDelete) {
            // Registrar en bitácora la eliminación de la compra
            $resultadoBitacora = $this->bitacoraModel->registrarAccion('compras', 'ELIMINAR', $idusuario);
            
            if (!$resultadoBitacora) {
                error_log("Warning: No se pudo registrar en bitácora la eliminación de la compra ID: $idcompra");
            }

            $response = ["status"  => true,"message" => "Compra marcada como inactiva correctamente."];
        } else {
            $response = ["status"  => false,"message" => "Error al marcar la compra como inactiva."];
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function reactivarCompra(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(
                ["status"  => false,"message" => "Método no permitido."],JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Verificar que solo superusuarios pueden reactivar compras
        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        $rolId = $_SESSION['rol_id'] ?? 0;
        
        if ($rolId != 1) {
            echo json_encode([
                'status' => false, 
                'message' => 'Solo los superusuarios pueden reactivar compras.'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $idcompra = isset($data['idcompra']) ? intval($data['idcompra']) : 0;

        if ($idcompra <= 0) {
            echo json_encode(
                ["status"  => false,"message" => "ID de compra no válido."],JSON_UNESCAPED_UNICODE);
            exit;
        }

        $requestReactivar = $this->get_model()->reactivarCompra($idcompra);

        if ($requestReactivar['status']) {
            // Registrar en bitácora la reactivación de la compra
            $resultadoBitacora = $this->bitacoraModel->registrarAccion('compras', 'REACTIVAR', $idusuario);
            
            if (!$resultadoBitacora) {
                error_log("Warning: No se pudo registrar en bitácora la reactivación de la compra ID: $idcompra");
            }

            $response = ["status"  => true,"message" => "Compra reactivada correctamente."];
        } else {
            $response = ["status"  => false,"message" => $requestReactivar['message'] ?? "Error al reactivar la compra."];
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    
    public function cambiarEstadoCompra(){
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // Verificar permisos según el tipo de cambio de estado
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                
                // Leer datos JSON del cuerpo de la petición
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);
                
                // Extraer los datos (usar las claves que envía el JavaScript)
                $idcompra = isset($data['idcompra']) ? intval($data['idcompra']) : 0;
                $nuevoEstado = isset($data['nuevo_estado']) ? trim($data['nuevo_estado']) : '';
                
                // Validar datos básicos primero
                if ($idcompra <= 0) {
                    $response = [
                        "status" => false, 
                        "message" => "ID de compra no válido."
                    ];
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    die();
                }
                
                if (empty($nuevoEstado)) {
                    $response = [
                        "status" => false, 
                        "message" => "Estado no proporcionado."
                    ];
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    die();
                }
                
                // Verificar permisos específicos según la acción
                $estadoAnterior = $this->get_model()->obtenerEstadoCompra($idcompra);
                
                if (!$this->verificarPermisosCambioEstado($estadoAnterior, $nuevoEstado)) {
                    echo json_encode([
                        'status' => false, 
                        'message' => 'No tiene permisos para realizar esta acción.'
                    ], JSON_UNESCAPED_UNICODE);
                    exit();
                }
                
                // Cambiar el estado
                $resultado = $this->get_model()->cambiarEstadoCompra($idcompra, $nuevoEstado);
                
                if ($resultado['status']) {
                    // Registrar en bitácora el cambio de estado
                    $detalle = "Cambio de estado de compra ID: $idcompra de '$estadoAnterior' a '$nuevoEstado'";
                    BitacoraHelper::registrarAccion('compras', 'CAMBIO_ESTADO', $idusuario, $this->bitacoraModel, $detalle, $idcompra);

                    // Procesar notificaciones según el nuevo estado
                    $this->procesarNotificacionesCambioEstado($idcompra, $estadoAnterior, $nuevoEstado);

                    // Si la compra cambió a PAGADA desde otro estado, regenerar notificaciones
                    if ($nuevoEstado === 'PAGADA') {
                        $this->regenerarNotificacionesStock();
                    }
                    
                    $response = [
                        "status" => true, 
                        "message" => $resultado['message'] ?: "Estado cambiado correctamente."
                    ];
                } else {
                    $response = [
                        "status" => false, 
                        "message" => $resultado['message'] ?: "Error al cambiar el estado."
                    ];
                }
                
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                
            } catch (Exception $e) {
                error_log("Error en cambiarEstadoCompra: " . $e->getMessage());
                $response = [
                    "status" => false, 
                    "message" => "Error interno del servidor: " . $e->getMessage()
                ];
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
        } else {
            $response = [
                "status" => false, 
                "message" => "Método no permitido."
            ];
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    private function verificarPermisosCambioEstado($estadoAnterior, $nuevoEstado)
    {
        // Enviar a autorización: pueden hacerlo usuarios con permisos de crear o eliminar
        if ($estadoAnterior === 'BORRADOR' && $nuevoEstado === 'POR_AUTORIZAR') {
            return (PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'crear') ||
                    PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'eliminar'));
        }
        
        // Autorizar o devolver a borrador: solo usuarios con permisos de eliminar
        if ($estadoAnterior === 'POR_AUTORIZAR' && 
            ($nuevoEstado === 'AUTORIZADA' || $nuevoEstado === 'BORRADOR')) {
            return PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'eliminar');
        }
        
        // Cualquier otro cambio de estado: requiere permisos de eliminar
        return PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'eliminar');
    }

    private function regenerarNotificacionesStock()
    {
        try {
            // Si no existe el modelo de notificaciones, crearlo
            if (!isset($this->notificacionesModel)) {
                require_once 'app/models/NotificacionesModel.php';
                $this->notificacionesModel = new NotificacionesModel();
            }
            
            $resultado = $this->notificacionesModel->generarNotificacionesProductos();
            
            if ($resultado) {
                error_log("Notificaciones regeneradas correctamente después del cambio de estado de compra");
            } else {
                error_log("Error al regenerar notificaciones después del cambio de estado");
            }
            
            return $resultado;
        } catch (Exception $e) {
            error_log("Error en regenerarNotificacionesStock: " . $e->getMessage());
            return false;
        }
    }

    private function procesarNotificacionesCambioEstado($idcompra, $estadoAnterior, $nuevoEstado)
    {
        try {
            switch ($nuevoEstado) {
                case 'POR_AUTORIZAR':
                    $this->generarNotificacionAutorizacion($idcompra);
                    break;
                    
                case 'AUTORIZADA':
                    // Limpiar notificaciones de autorización pendientes
                    $this->notificacionesModel->limpiarNotificacionesCompra($idcompra, 'autorizar');
                    
                    // Generar notificación para registrar pago
                    $this->generarNotificacionPago($idcompra);
                    break;
                    
                case 'PAGADA':
                    // Limpiar todas las notificaciones de esta compra
                    $this->notificacionesModel->limpiarNotificacionesCompra($idcompra, 'completar');
                    break;
                    
                case 'BORRADOR':
                    // Si se devuelve a borrador, limpiar notificaciones de autorización
                    $this->notificacionesModel->limpiarNotificacionesCompra($idcompra, 'autorizar');
                    break;
            }
            
        } catch (Exception $e) {
            error_log("Error al procesar notificaciones de cambio de estado: " . $e->getMessage());
        }
    }

    private function generarNotificacionAutorizacion($idcompra)
    {
        try {
            // Obtener información de la compra
            $compraData = $this->get_model()->selectCompra($idcompra);
            if (!$compraData || !isset($compraData['solicitud'])) {
                throw new Exception("No se pudo obtener información de la compra");
            }
            
            $compraInfo = $compraData['solicitud'];
            
            // Verificar si ya existe una notificación de autorización para esta compra
            if ($this->notificacionesModel->verificarNotificacionExistente(
                'COMPRA_POR_AUTORIZAR', 
                'compras', 
                $idcompra
            )) {
                return; // Ya existe una notificación
            }
            
            // Obtener roles que pueden autorizar (eliminar)
            $rolesAutorizadores = $this->notificacionesModel->obtenerUsuariosConPermiso('compras', 'eliminar');
            
            if (empty($rolesAutorizadores)) {
                error_log("No se encontraron roles con permisos para autorizar compras");
                return;
            }
            
            // Crear notificación para cada rol autorizador
            foreach ($rolesAutorizadores as $rol) {
                $notificacionData = [
                    'tipo' => 'COMPRA_POR_AUTORIZAR',
                    'titulo' => 'Compra Pendiente de Autorización',
                    'mensaje' => "La compra #{$compraInfo['nro_compra']} por un total de " . 
                               number_format($compraInfo['total_general'], 2) . 
                               " está pendiente de autorización.",
                    'modulo' => 'compras',
                    'referencia_id' => $idcompra,
                    'rol_destinatario' => $rol['idrol'],
                    'prioridad' => 'ALTA'
                ];
                
                $this->notificacionesModel->crearNotificacion($notificacionData);
            }
            
        } catch (Exception $e) {
            error_log("Error al generar notificación de autorización: " . $e->getMessage());
        }
    }

    private function generarNotificacionPago($idcompra)
    {
        try {
            // Obtener información de la compra
            $compraData = $this->get_model()->selectCompra($idcompra);
            if (!$compraData || !isset($compraData['solicitud'])) {
                throw new Exception("No se pudo obtener información de la compra");
            }
            
            $compraInfo = $compraData['solicitud'];
            
            // Verificar si ya existe una notificación de pago para esta compra
            if ($this->notificacionesModel->verificarNotificacionExistente(
                'COMPRA_AUTORIZADA_PAGO', 
                'compras', 
                $idcompra
            )) {
                return; // Ya existe una notificación
            }
            
            // Obtener roles que pueden registrar pagos (crear)
            $rolesRegistradores = $this->notificacionesModel->obtenerUsuariosConPermiso('compras', 'crear');
            
            if (empty($rolesRegistradores)) {
                error_log("No se encontraron roles con permisos para crear/registrar en compras");
                return;
            }
            
            // Crear notificación para cada rol registrador
            foreach ($rolesRegistradores as $rol) {
                $notificacionData = [
                    'tipo' => 'COMPRA_AUTORIZADA_PAGO',
                    'titulo' => 'Compra Autorizada - Registrar Pago',
                    'mensaje' => "La compra #{$compraInfo['nro_compra']} por un total de " . 
                               number_format($compraInfo['total_general'], 2) . 
                               " ha sido autorizada y requiere registrar un pago.",
                    'modulo' => 'compras',
                    'referencia_id' => $idcompra,
                    'rol_destinatario' => $rol['idrol'],
                    'prioridad' => 'MEDIA'
                ];
                
                $this->notificacionesModel->crearNotificacion($notificacionData);
            }
            
        } catch (Exception $e) {
            error_log("Error al generar notificación de pago: " . $e->getMessage());
        }
    }

    // Método para obtener permisos del usuario autenticado
    public function getPermisosUsuario(){
        header('Content-Type: application/json');
        
        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        
        if (!$idusuario) {
            echo json_encode([
                'status' => false,
                'message' => 'Usuario no autenticado'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        $permisos = [
            'puedeVer' => PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'ver'),
            'puedeCrear' => PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'crear'),
            'puedeEditar' => PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'editar'),
            'puedeEliminar' => PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'eliminar')
        ];
        
        echo json_encode([
            'status' => true,
            'permisos' => $permisos
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Método para debug de permisos de compras
    public function debugPermisos()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $debug = [
            'session_completa' => $_SESSION,
            'session_login' => $_SESSION['login'] ?? 'no definido',
            'session_usuario_id' => $_SESSION['usuario_id'] ?? 'no definido', 
            'session_idrol' => $_SESSION['idrol'] ?? 'no definido',
            'session_usuario_nombre' => $_SESSION['usuario_nombre'] ?? 'no definido',
        ];

        // Obtener permisos usando el helper
        $permisos = PermisosModuloVerificar::getPermisosUsuarioModulo('compras');
        
        $debug['permisos_obtenidos'] = $permisos;
        $debug['verificacion_ver'] = PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'ver');
        $debug['verificacion_crear'] = PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'crear');
        $debug['verificacion_editar'] = PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'editar');
        $debug['verificacion_eliminar'] = PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'eliminar');

        // Obtener información de permisos usando el modelo
        try {
            $debug['consulta_directa'] = $this->get_model()->obtenerPermisosUsuarioModulo($debug['session_usuario_id'], 'compras');
            $debug['todos_permisos_rol'] = $this->get_model()->obtenerTodosPermisosRol($debug['session_idrol']);
        } catch (Exception $e) {
            $debug['error_consulta'] = $e->getMessage();
        }

        header('Content-Type: application/json');
        echo json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }

    public function updateCompra(){
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(
                ['status' => false, 'message' => 'Método no permitido.'],
                JSON_UNESCAPED_UNICODE
            );
            exit();
        }

        // Verificar permisos de edición
        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'editar')) {
            echo json_encode([
                'status' => false, 
                'message' => 'No tiene permisos para editar compras.'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $modelo = $this->get_model();
        $response = ['status' => false, 'message' => 'Error desconocido.'];

        // Obtener ID de compra a actualizar
        $idcompra = intval($_POST['idcompra'] ?? 0);
        if ($idcompra <= 0) {
            $response['message'] = 'ID de compra no válido.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit();
        }

        // Verificar que la compra existe y se puede editar
        $compraExistente = $modelo->getCompraById($idcompra);
        if (!$compraExistente) {
            $response['message'] = 'Compra no encontrada.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit();
        }

        // Solo se pueden editar compras en estado BORRADOR
        if ($compraExistente['estatus_compra'] !== 'BORRADOR') {
            $response['message'] = 'Solo se pueden editar compras en estado BORRADOR.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit();
        }

        $idproveedor = intval($_POST['idproveedor_seleccionado'] ?? 0);
        date_default_timezone_set('America/Caracas');
        $fecha_compra = $_POST['fecha_compra'] ?? date('Y-m-d');
        $observaciones_compra = $_POST['observaciones_compra'] ?? '';
        $total_general_compra = floatval($_POST['total_general_input'] ?? 0);

        if (empty($idproveedor)) {
            $response['message'] = 'Falta seleccionar proveedor.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit();
        }
        if (empty($fecha_compra)) {
            $response['message'] = 'Falta fecha de compra.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit();
        }
        if (!isset($_POST['productos_detalle'])) {
            $response['message'] = 'Faltan productos en el detalle.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit();
        }

        $idmoneda_general = 3;
        $subtotal_general_compra = $total_general_compra;
        $descuento_porcentaje_general = 0;
        $monto_descuento_general = 0;

        $datosCompra = [
            'idcompra' => $idcompra,
            'fecha_compra' => $fecha_compra,
            'idproveedor' => $idproveedor,
            'idmoneda_general' => $idmoneda_general,
            'subtotal_general_compra' => $subtotal_general_compra,
            'descuento_porcentaje_compra' => $descuento_porcentaje_general,
            'monto_descuento_compra' => $monto_descuento_general,
            'total_general_compra' => $total_general_compra,
            'observaciones_compra' => $observaciones_compra,
        ];

        $detallesCompraInput = json_decode($_POST['productos_detalle'], true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($detallesCompraInput)) {
            $response['message'] = 'No hay productos en el detalle o el formato es incorrecto.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit();
        }

        $detallesParaActualizar = [];
        foreach ($detallesCompraInput as $item) {
            $idProductoItem = intval($item['idproducto'] ?? 0);
            if ($idProductoItem <= 0) {
                $response['message'] = 'ID de producto inválido en el detalle.';
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit();
            }

            $productoInfo = $modelo->getProductoById($idProductoItem);
            if (!$productoInfo) {
                $response['message'] = 'Producto no encontrado: ID ' . $idProductoItem;
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit();
            }

            $cantidad_final = floatval($item['cantidad'] ?? 0);
            $peso_vehiculo = isset($item['peso_vehiculo']) ? floatval($item['peso_vehiculo']) : null;
            $peso_bruto = isset($item['peso_bruto']) ? floatval($item['peso_bruto']) : null;
            $peso_neto = isset($item['peso_neto']) ? floatval($item['peso_neto']) : null;

            if ($cantidad_final <= 0) {
                $response['message'] = 'Cantidad debe ser mayor a cero para: ' . htmlspecialchars($productoInfo['nombre'], ENT_QUOTES, 'UTF-8');
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit();
            }
            if (floatval($item['precio_unitario_compra'] ?? 0) <= 0) {
                $response['message'] = 'Precio debe ser mayor a cero para: ' . htmlspecialchars($productoInfo['nombre'], ENT_QUOTES, 'UTF-8');
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit();
            }

            $moneda_detalle = !empty($item['moneda']) ? intval($item['moneda']) : 3;
            if ($moneda_detalle <= 0) {
                $moneda_detalle = 3;
            }

            $detallesParaActualizar[] = [
                'idproducto' => $productoInfo['idproducto'],
                'descripcion_temporal_producto' => $item['nombre_producto'] ?? $productoInfo['nombre'],
                'cantidad' => $cantidad_final,
                'descuento' => floatval($item['descuento'] ?? 0),
                'precio_unitario_compra' => floatval($item['precio_unitario_compra'] ?? 0),
                'idmoneda_detalle' => $moneda_detalle,
                'subtotal_linea' => floatval($item['subtotal_linea'] ?? 0),
                'subtotal_original_linea' => floatval($item['subtotal_original_linea'] ?? 0),
                'monto_descuento_linea' => floatval($item['monto_descuento_linea'] ?? 0),
                'peso_vehiculo' => $peso_vehiculo,
                'peso_bruto' => $peso_bruto,
                'peso_neto' => $peso_neto,
            ];
        }

        if (empty($detallesParaActualizar)) {
            $response['message'] = 'No se procesaron productos válidos.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit();
        }

        try {
            $resultadoActualizacion = $modelo->actualizarCompra($idcompra, $datosCompra, $detallesParaActualizar);
        } catch (Exception $e) {
            $response = ['status' => false, 'message' => 'Error técnico: ' . $e->getMessage()];
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit();
        }

        if ($resultadoActualizacion) {
            // Registrar en bitácora la actualización de la compra
            $resultadoBitacora = $this->bitacoraModel->registrarAccion('compras', 'ACTUALIZAR', $idusuario);
            
            if (!$resultadoBitacora) {
                error_log("Warning: No se pudo registrar en bitácora la actualización de la compra ID: $idcompra");
            }

            $response = [
                'status' => true,
                'message' => 'Compra actualizada correctamente.',
                'idcompra' => $idcompra,
            ];
        } else {
            $response = ['status' => false, 'message' => 'Error al actualizar la compra.'];
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    public function limpiarNotificacionesProcesadas(){
        header('Content-Type: application/json');
        
        // Verificar permisos (solo usuarios con permiso de eliminar pueden hacer limpieza)
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'eliminar')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para realizar esta acción.'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        try {
            $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
            
            // Limpiar notificaciones de compras ya procesadas
            $resultado = $this->notificacionesModel->limpiarNotificacionesProcesadas();
            
            if ($resultado) {
                // Registrar en bitácora
                BitacoraHelper::registrarAccion(
                    'compras', 
                    'LIMPIEZA_NOTIFICACIONES', 
                    $idusuario, 
                    $this->bitacoraModel, 
                    'Limpieza manual de notificaciones procesadas de compras'
                );
                
                echo json_encode([
                    'status' => true,
                    'message' => 'Notificaciones procesadas limpiadas exitosamente.'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'status' => false,
                    'message' => 'Error al limpiar notificaciones o no había notificaciones para limpiar.'
                ], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            error_log("Error en limpiarNotificacionesProcesadas: " . $e->getMessage());
            echo json_encode([
                'status' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        
        exit();
    }

    public function inicializarProcedimientoNotificaciones(){
        header('Content-Type: application/json');
        
        // Verificar permisos (solo usuarios con permiso de eliminar pueden inicializar)
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'eliminar')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para realizar esta acción.'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        try {
            $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
            
            // Crear el procedimiento almacenado para limpiar notificaciones
            $resultado = $this->notificacionesModel->crearProcedimientoLimpiarNotificacionesCompra();
            
            if ($resultado) {
                // Registrar en bitácora
                BitacoraHelper::registrarAccion(
                    'compras', 
                    'INIT_PROCEDIMIENTO_NOTIFICACIONES', 
                    $idusuario, 
                    $this->bitacoraModel, 
                    'Inicialización del procedimiento de limpieza de notificaciones de compras'
                );
                
                echo json_encode([
                    'status' => true,
                    'message' => 'Procedimiento de limpieza de notificaciones inicializado exitosamente.',
                    'note' => 'Ahora ejecute el archivo sql/trigger_compra_pagada_actualizado.sql en su base de datos.'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'status' => false,
                    'message' => 'Error al inicializar el procedimiento de limpieza.'
                ], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            error_log("Error en inicializarProcedimientoNotificaciones: " . $e->getMessage());
            echo json_encode([
                'status' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        
        exit();
    }
}