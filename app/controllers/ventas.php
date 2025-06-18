<?php
require_once "app/core/Controllers.php";
require_once "app/models/ventasModel.php";
require_once "helpers/PermisosModuloVerificar.php";
require_once "helpers/PermisosHelper.php";
require_once "helpers/helpers.php";
require_once "app/models/bitacoraModel.php";
require_once "helpers/bitacora_helper.php";
require_once "helpers/expresiones_regulares.php";

class Ventas extends Controllers
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
        $this->model = new VentasModel();
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

        //  Verificar acceso al módulo de ventas
        if (!PermisosModuloVerificar::verificarAccesoModulo('ventas')) {
            $this->views->getView($this, "permisos");
            exit();
        }
    }

    public function index()
    {
        // Doble verificación de seguridad
        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        //  Verificar permisos para VER ventas
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'ver')) {
            $this->views->getView($this, "permisos");
            exit();
        }

        // Obtener ID del usuario y registrar acceso al módulo
        $idUsuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        BitacoraHelper::registrarAccesoModulo('Ventas', $idUsuario, $this->bitacoraModel);

        if (!$idUsuario) {
            error_log("Ventas::index - No se pudo obtener ID de usuario");
            header('Location: ' . base_url() . '/login');
            die();
        }

        //   PERMISOS CORRECTOS
        try {
            $permisos = [
                'puede_ver' => PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'ver'),
                'puede_crear' => PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'crear'),
                'puede_editar' => PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'editar'),
                'puede_eliminar' => PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'eliminar'),
                'puede_exportar' => PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'exportar'),
                'acceso_total' => PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'total')
            ];
        } catch (Exception $e) {
            error_log("Error al obtener permisos: " . $e->getMessage());
            // Permisos por defecto (sin acceso)
            $permisos = [
                'puede_ver' => false,
                'puede_crear' => false,
                'puede_editar' => false,
                'puede_eliminar' => false,
                'puede_exportar' => false,
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
        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            header('Content-Type: application/json');
            echo json_encode([
                "status" => false,
                "message" => "Usuario no autenticado",
                "data" => []
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        //  Verificar permisos correctos
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'ver')) {
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

            //  REGISTRAR CONSULTA EN BITÁCORA
            $idUsuario = $this->BitacoraHelper->obtenerUsuarioSesion();
            if ($idUsuario) {
                $this->bitacoraModel->registrarAccion('ventas', 'CONSULTA_DATOS', $idUsuario);
            }

            $response = [
                "draw" => intval($_GET['draw'] ?? 0) + 1,
                "recordsTotal" => count($arrData),
                "recordsFiltered" => count($arrData),
                "data" => $arrData ?: []
            ];

            header('Content-Type: application/json');
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error en getventasData: " . $e->getMessage());
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
        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            echo json_encode([
                'status' => false,
                'message' => 'Usuario no autenticado.'
            ]);
            return;
        }

        //  Verificar permisos correctos para CREAR
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'crear')) {
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
                'observaciones',
            ];

            foreach ($camposObligatorios as $campo) {
                if (!isset($data[$campo]) || $data[$campo] === '') {
                    echo json_encode(['status' => false, 'message' => "Falta el campo obligatorio: $campo"]);
                    return;
                }
            }

            // Validar campos numéricos de la venta
            $validacionesNumericas = [
                'subtotal_general' => 'subtotal',
                'descuento_porcentaje_general' => 'descuentoPorcentaje',
                'monto_descuento_general' => 'montoDescuento',
                'total_general' => 'total'
            ];

            $erroresValidacion = [];

            foreach ($validacionesNumericas as $campo => $tipoValidacion) {
                if (isset($data[$campo])) {
                    try {
                        // Usar el método validarConDetalle que devuelve array
                        $resultado = ExpresionesRegulares::validarConDetalle(
                            strval($data[$campo]), 
                            $tipoValidacion
                        );
                        
                        if (!$resultado['valido']) {
                            $erroresValidacion[] = "$campo: {$resultado['mensaje']}";
                        }
                    } catch (Exception $e) {
                        $erroresValidacion[] = "$campo: Error en validación - " . $e->getMessage();
                    }
                }
            }

            if (!empty($erroresValidacion)) {
                echo json_encode([
                    'status' => false,
                    'message' => 'Errores de validación: ' . implode('; ', $erroresValidacion)
                ]);
                return;
            }

            // Validar que hay un cliente seleccionado O datos de cliente nuevo
            if (empty($data['idcliente']) && !$datosClienteNuevo) {
                echo json_encode([
                    'status' => false,
                    'message' => 'Debe seleccionar un cliente existente o proporcionar datos del cliente nuevo.'
                ]);
                return;
            }

            // Validar datos del cliente nuevo si se proporcionan
            if ($datosClienteNuevo) {
                $validacionesCliente = [
                    'cedula' => 'cedula',
                    'nombre' => 'nombre',
                    'apellido' => 'apellido',
                    'telefono_principal' => 'telefono',
                    'direccion' => 'direccion'
                ];

                $erroresCliente = [];
                foreach ($validacionesCliente as $campo => $tipoValidacion) {
                    if (isset($datosClienteNuevo[$campo]) && !empty($datosClienteNuevo[$campo])) {
                        try {
                            $resultado = ExpresionesRegulares::validarConDetalle(
                                strval($datosClienteNuevo[$campo]), 
                                $tipoValidacion
                            );
                            
                            if (!$resultado['valido']) {
                                $erroresCliente[] = "$campo: {$resultado['mensaje']}";
                            }
                        } catch (Exception $e) {
                            $erroresCliente[] = "$campo: Error en validación - " . $e->getMessage();
                        }
                    }
                }

                if (!empty($erroresCliente)) {
                    echo json_encode([
                        'status' => false,
                        'message' => 'Errores en datos del cliente: ' . implode('; ', $erroresCliente)
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

                // Validar cantidad usando validarConDetalle
                try {
                    $validacionCantidad = ExpresionesRegulares::validarConDetalle(
                        strval($detalle['cantidad']), 
                        'cantidad'
                    );
                    
                    if (!$validacionCantidad['valido']) {
                        echo json_encode([
                            'status' => false,
                            'message' => "Detalle " . ($index + 1) . " - Cantidad: {$validacionCantidad['mensaje']}"
                        ]);
                        return;
                    }
                } catch (Exception $e) {
                    echo json_encode([
                        'status' => false,
                        'message' => "Detalle " . ($index + 1) . " - Error validando cantidad: " . $e->getMessage()
                    ]);
                    return;
                }

                // Validar precio usando validarConDetalle
                try {
                    $validacionPrecio = ExpresionesRegulares::validarConDetalle(
                        strval($detalle['precio_unitario_venta']), 
                        'precio'
                    );
                    
                    if (!$validacionPrecio['valido']) {
                        echo json_encode([
                            'status' => false,
                            'message' => "Detalle " . ($index + 1) . " - Precio: {$validacionPrecio['mensaje']}"
                        ]);
                        return;
                    }
                } catch (Exception $e) {
                    echo json_encode([
                        'status' => false,
                        'message' => "Detalle " . ($index + 1) . " - Error validando precio: " . $e->getMessage()
                    ]);
                    return;
                }

                // Validar subtotal del detalle si está presente
                if (isset($detalle['subtotal']) && !empty($detalle['subtotal'])) {
                    try {
                        $validacionSubtotal = ExpresionesRegulares::validarConDetalle(
                            strval($detalle['subtotal']), 
                            'subtotal'
                        );
                        
                        if (!$validacionSubtotal['valido']) {
                            echo json_encode([
                                'status' => false,
                                'message' => "Detalle " . ($index + 1) . " - Subtotal: {$validacionSubtotal['mensaje']}"
                            ]);
                            return;
                        }
                    } catch (Exception $e) {
                        echo json_encode([
                            'status' => false,
                            'message' => "Detalle " . ($index + 1) . " - Error validando subtotal: " . $e->getMessage()
                        ]);
                        return;
                    }
                }
            }

            // Obtener ID de usuario para bitácora
            $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

            // Crear la venta con cliente (nuevo o existente)
            $resultado = $this->model->insertVenta($data, $detalles, $datosClienteNuevo);

            if ($resultado['success']) {
                //  REGISTRAR EN BITÁCORA si la inserción fue exitosa
                if ($idusuario) {
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('ventas', 'CREAR', $idusuario);
                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la creación de la venta ID: " . $resultado['idventa']);
                    }
                }

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
            error_log("Error en setVenta: " . $e->getMessage());
            echo json_encode([
                'status' => false,
                'message' => 'Error al procesar la venta: ' . $e->getMessage()
            ]);
        }
        exit();
    }

    public function getProductosLista()
    {
        header('Content-Type: application/json');
        
        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            echo json_encode([
                'status' => false,
                'message' => 'Usuario no autenticado',
                'data' => []
            ]);
            return;
        }

        //  AGREGAR VERIFICACIÓN DE PERMISOS
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'ver')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para ver productos',
                'data' => []
            ]);
            return;
        }

        try {
            $productos = $this->model->obtenerProductos();
            
            //  REGISTRAR CONSULTA EN BITÁCORA
            $idUsuario = $this->BitacoraHelper->obtenerUsuarioSesion();
            if ($idUsuario) {
                $this->bitacoraModel->registrarAccion('ventas', 'CONSULTA_PRODUCTOS', $idUsuario);
            }

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

    public function getMonedas()
    {
        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            echo json_encode([
                'status' => false,
                'message' => 'Usuario no autenticado'
            ]);
            return;
        }

    
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'ver')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para ver monedas'
            ]);
            return;
        }

        try {
            $monedas = $this->model->getMonedasActivas();
            
            //  REGISTRAR CONSULTA EN BITÁCORA
            $idUsuario = $this->BitacoraHelper->obtenerUsuarioSesion();
            if ($idUsuario) {
                $this->bitacoraModel->registrarAccion('ventas', 'CONSULTA_MONEDAS', $idUsuario);
            }

            echo json_encode(["status" => true, "data" => $monedas]);
        } catch (Exception $e) {
            echo json_encode(["status" => false, "message" => "Error inesperado: " . $e->getMessage()]);
        }
        exit();
    }

    public function buscarClientes()
    {
        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            echo json_encode([
                'status' => false,
                'message' => 'Usuario no autenticado'
            ]);
            return;
        }

        //  Verificar permisos correctos
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'ver')) {
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
                    
                    //  REGISTRAR CONSULTA EN BITÁCORA
                    $idUsuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                    if ($idUsuario) {
                        $this->bitacoraModel->registrarAccion('ventas', 'BUSCAR_CLIENTES', $idUsuario);
                    }

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
        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            echo json_encode([
                'status' => false,
                'message' => 'Usuario no autenticado'
            ]);
            return;
        }

        //  Verificar permisos correctos
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'ver')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para ver productos'
            ]);
            return;
        }

        try {
            //  Usar método del modelo de ventas
            $productos = $this->model->obtenerProductos(); // En lugar de getListaProductosParaFormulario
            
            //  REGISTRAR CONSULTA EN BITÁCORA
            $idUsuario = $this->BitacoraHelper->obtenerUsuarioSesion();
            if ($idUsuario) {
                $this->bitacoraModel->registrarAccion('ventas', 'CONSULTA_PRODUCTOS_DISPONIBLES', $idUsuario);
            }

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
        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            echo json_encode(['tasa' => 1]);
            exit;
        }

        //  AGREGAR VERIFICACIÓN DE PERMISOS
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'ver')) {
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
        
        //  REGISTRAR CONSULTA EN BITÁCORA
        $idUsuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        if ($idUsuario) {
            $this->bitacoraModel->registrarAccion('ventas', 'CONSULTA_TASA', $idUsuario);
        }

        echo json_encode(['tasa' => $tasa]);
        exit;
    }

    public function getVentaDetalle()
    {
        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            echo json_encode([
                'status' => false, 
                'message' => 'Usuario no autenticado'
            ]);
            exit();
        }
        
        //  MODIFICADO: Permitir ver detalle si tiene permisos de VER o CREAR
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'ver') && 
            !PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'crear')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para ver detalles de ventas'
            ]);
            exit();
        }
        
        $idventa = intval($_GET['idventa'] ?? 0);
        if ($idventa <= 0) {
            echo json_encode([
                'status' => false, 
                'message' => 'ID de venta no válido'
            ]);
            exit();
        }
        
        try {
            // Obtener datos de la venta
            $venta = $this->model->obtenerVentaPorId($idventa);
            
            if (!$venta) {
                echo json_encode([
                    'status' => false,
                    'message' => 'Venta no encontrada'
                ]);
                exit();
            }
            
            // Obtener detalles de la venta con información de productos
            $detalle = $this->model->obtenerDetalleVenta($idventa);
            
            //  REGISTRAR VER DETALLE EN BITÁCORA
            $idUsuario = $this->BitacoraHelper->obtenerUsuarioSesion();
            if ($idUsuario) {
                $this->bitacoraModel->registrarAccion('ventas', 'VER_DETALLE', $idUsuario);
            }
            echo json_encode([
                'status' => true,
                'data' => [
                    'venta' => $venta,
                    'detalle' => $detalle
                ]
            ]);
        } catch (Exception $e) {
            error_log("Error en getVentaDetalle: " . $e->getMessage());
            echo json_encode([
                'status' => false, 
                'message' => 'Error al obtener detalle de venta: ' . $e->getMessage()
            ]);
        }
        exit();
    }

    public function getMonedasDisponibles()
    {
        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            echo json_encode([
                'status' => false,
                'message' => 'Usuario no autenticado'
            ]);
            return;
        }

        //  Verificar permisos correctos
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'ver')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para ver monedas'
            ]);
            return;
        }

        try {
            $monedas = $this->model->getMonedasActivas();
            
            //  REGISTRAR CONSULTA EN BITÁCORA
            $idUsuario = $this->BitacoraHelper->obtenerUsuarioSesion();
            if ($idUsuario) {
                $this->bitacoraModel->registrarAccion('ventas', 'CONSULTA_MONEDAS_DISPONIBLES', $idUsuario);
            }

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
        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            echo json_encode([
                'status' => false,
                'message' => 'Usuario no autenticado'
            ]);
            return;
        }

        //  Verificar permisos correctos para ELIMINAR
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'eliminar')) {
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

        $idventa = intval($data['id'] ?? 0);
        
        if ($idventa <= 0) {
            echo json_encode([
                'status' => false,
                'message' => 'ID de venta no válido'
            ]);
            return;
        }

        try {
            // Verificar que la venta existe y no está ya eliminada
            $venta = $this->model->obtenerVentaPorId($idventa);
            
            if (!$venta) {
                echo json_encode([
                    'status' => false,
                    'message' => 'Venta no encontrada'
                ]);
                return;
            }

            if (strtolower($venta['estatus']) === 'inactivo') {
                echo json_encode([
                    'status' => false,
                    'message' => 'Esta venta ya está desactivada'
                ]);
                return;
            }

            // Obtener ID de usuario para bitácora
            $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

            // Eliminar (desactivar) la venta
            $resultado = $this->model->eliminarVenta($idventa);

            if ($resultado['success']) {
                //  REGISTRAR EN BITÁCORA si la eliminación fue exitosa
                if ($idusuario) {
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('ventas', 'ELIMINAR', $idusuario);
                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la eliminación de la venta ID: " . $idventa);
                    }
                }

                echo json_encode([
                    'status' => true,
                    'message' => $resultado['message'] ?? 'Venta desactivada correctamente'
                ]);
            } else {
                echo json_encode([
                    'status' => false,
                    'message' => $resultado['message'] ?? 'No se pudo desactivar la venta'
                ]);
            }

        } catch (Exception $e) {
            error_log("Error en deleteVenta: " . $e->getMessage());
            echo json_encode([
                'status' => false,
                'message' => 'Error al procesar la eliminación: ' . $e->getMessage()
            ]);
        }
        exit();
    }

    //  AGREGAR MÉTODO PARA ACTUALIZAR VENTAS
    public function updateVenta()
    {
        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            echo json_encode([
                'status' => false,
                'message' => 'Usuario no autenticado'
            ]);
            return;
        }

        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'editar')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para editar ventas.'
            ]);
            return;
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$data) {
            echo json_encode(['status' => false, 'message' => 'Datos no válidos.']);
            return;
        }

        try {
            $idventa = intval($data['idventa'] ?? 0);
            if ($idventa <= 0) {
                echo json_encode(['status' => false, 'message' => 'ID de venta no válido.']);
                return;
            }

            // Verificar que la venta existe
            $ventaExistente = $this->model->obtenerVentaPorId($idventa);
            if (!$ventaExistente) {
                echo json_encode(['status' => false, 'message' => 'Venta no encontrada.']);
                return;
            }

            // Obtener ID de usuario para bitácora
            $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

            // Actualizar la venta (implementar en el modelo)
            $resultado = $this->model->updateVenta($idventa, $data);

            if ($resultado['success']) {
                // Registrar en bitácora si la actualización fue exitosa
                if ($idusuario) {
                    $resultadoBitacora = $this->bitacoraModel->registrarAccion('ventas', 'ACTUALIZAR', $idusuario);
                    if (!$resultadoBitacora) {
                        error_log("Warning: No se pudo registrar en bitácora la actualización de la venta ID: " . $idventa);
                    }
                }

                echo json_encode([
                    'status' => true,
                    'message' => $resultado['message'] ?? 'Venta actualizada correctamente'
                ]);
            } else {
                echo json_encode([
                    'status' => false,
                    'message' => $resultado['message'] ?? 'Error al actualizar la venta'
                ]);
            }

        } catch (Exception $e) {
            error_log("Error en updateVenta: " . $e->getMessage());
            echo json_encode([
                'status' => false,
                'message' => 'Error al procesar la actualización: ' . $e->getMessage()
            ]);
        }
        exit();
    }

    
    public function exportarVentas()
    {
        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            echo json_encode([
                'status' => false,
                'message' => 'Usuario no autenticado'
            ]);
            return;
        }

        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'exportar')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para exportar ventas.'
            ]);
            return;
        }

        try {
            $ventasData = $this->model->getVentasDatatable();
            
            // Obtener ID de usuario para bitácora
            $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
            
            if ($idusuario) {
                $resultadoBitacora = $this->bitacoraModel->registrarAccion('ventas', 'EXPORTAR', $idusuario);
                if (!$resultadoBitacora) {
                    error_log("Warning: No se pudo registrar en bitácora la exportación de ventas");
                }
            }

            echo json_encode([
                'status' => true,
                'data' => $ventasData,
                'message' => 'Datos preparados para exportación'
            ]);

        } catch (Exception $e) {
            error_log("Error en exportarVentas: " . $e->getMessage());
            echo json_encode([
                'status' => false,
                'message' => 'Error al exportar ventas: ' . $e->getMessage()
            ]);
        }
        exit();
    }
}
?>