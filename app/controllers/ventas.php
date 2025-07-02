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


        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }


        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            header('Location: ' . base_url() . '/login');
            die();
        }


        if (!PermisosModuloVerificar::verificarAccesoModulo('ventas')) {
            $this->views->getView($this, "permisos");
            exit();
        }
    }

    public function index()
    {

        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            header('Location: ' . base_url() . '/login');
            die();
        }


        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'ver')) {
            $this->views->getView($this, "permisos");
            exit();
        }


        $idUsuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        BitacoraHelper::registrarAccesoModulo('Ventas', $idUsuario, $this->bitacoraModel);

        if (!$idUsuario) {
            error_log("Ventas::index - No se pudo obtener ID de usuario");
            header('Location: ' . base_url() . '/login');
            die();
        }


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


        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'crear')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para crear ventas.'
            ]);
            return;
        }


        $json = file_get_contents('php://input'); //input');
        $data = json_decode($json, true);

        if (!$data) {
            echo json_encode(['status' => false, 'message' => 'Datos no válidos.']);
            return;
        }

        try {

            $datosClienteNuevo = $data['cliente_nuevo'] ?? null;
            $detalles = $data['detalles'] ?? [];


            unset($data['cliente_nuevo']);
            unset($data['detalles']);


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


            if (empty($data['idcliente']) && !$datosClienteNuevo) {
                echo json_encode([
                    'status' => false,
                    'message' => 'Debe seleccionar un cliente existente o proporcionar datos del cliente nuevo.'
                ]);
                return;
            }


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


            if (empty($detalles)) {
                echo json_encode([
                    'status' => false,
                    'message' => 'La venta debe tener al menos un producto.'
                ]);
                return;
            }


            foreach ($detalles as $index => $detalle) {
                if (empty($detalle['idproducto']) || empty($detalle['cantidad']) || empty($detalle['precio_unitario_venta'])) {
                    echo json_encode([
                        'status' => false,
                        'message' => "Detalle " . ($index + 1) . " tiene datos incompletos."
                    ]);
                    return;
                }


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


            $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();


            $resultado = $this->model->insertVenta($data, $detalles, $datosClienteNuevo);

            if ($resultado['success']) {

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

        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'ver')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para buscar clientes'
            ]);
            return;
        }

        // Cambiar para aceptar tanto GET como POST
        $criterio = '';
        if ($_POST && isset($_POST['criterio'])) {
            $criterio = $_POST['criterio'];
        } elseif ($_GET && isset($_GET['criterio'])) {
            $criterio = $_GET['criterio'];
        }

        if (strlen($criterio) >= 2) {
            try {
                $clientes = $this->model->buscarClientes($criterio);


                $idUsuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                if ($idUsuario) {
                    $this->bitacoraModel->registrarAccion('ventas', 'BUSCAR_CLIENTES', $idUsuario);
                }

                // Devolver directamente el array de clientes para mantener compatibilidad
                echo json_encode($clientes);
            } catch (Exception $e) {
                echo json_encode([]);
            }
        } else {
            echo json_encode([]);
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


        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'ver')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para ver productos'
            ]);
            return;
        }

        try {

            $productos = $this->model->obtenerProductos();


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


        $tasa = $this->model->getTasaPorCodigoYFecha($codigo, $fecha);


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


        if (
            !PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'ver') &&
            !PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'crear')
        ) {
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

            $venta = $this->model->obtenerVentaPorId($idventa);

            if (!$venta) {
                echo json_encode([
                    'status' => false,
                    'message' => 'Venta no encontrada'
                ]);
                exit();
            }


            $detalle = $this->model->obtenerDetalleVenta($idventa);


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


        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'ver')) {
            echo json_encode([
                'status' => false,
                'message' => 'No tiene permisos para ver monedas'
            ]);
            return;
        }

        try {
            $monedas = $this->model->getMonedasActivas();


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


            $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();


            $resultado = $this->model->eliminarVenta($idventa);

            if ($resultado['success']) {

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


            $ventaExistente = $this->model->obtenerVentaPorId($idventa);
            if (!$ventaExistente) {
                echo json_encode(['status' => false, 'message' => 'Venta no encontrada.']);
                return;
            }


            $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();


            $resultado = $this->model->updateVenta($idventa, $data);

            if ($resultado['success']) {

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

    public function cambiarEstadoVenta(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // Verificar permisos
                $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
                
                // Leer datos JSON del cuerpo de la petición
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);
                
                // Extraer los datos
                $idventa = isset($data['idventa']) ? intval($data['idventa']) : 0;
                $nuevoEstado = isset($data['nuevo_estado']) ? trim($data['nuevo_estado']) : '';
                
                // Validar datos básicos
                if ($idventa <= 0) {
                    $response = [
                        "status" => false, 
                        "message" => "ID de venta no válido."
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
                $estadoAnterior = $this->model->obtenerEstadoVenta($idventa);
                
                if (!$this->verificarPermisosCambioEstadoVenta($estadoAnterior, $nuevoEstado)) {
                    echo json_encode([
                        'status' => false, 
                        'message' => 'No tiene permisos para realizar esta acción.'
                    ], JSON_UNESCAPED_UNICODE);
                    exit();
                }
                
                // Cambiar el estado
                $resultado = $this->model->cambiarEstadoVenta($idventa, $nuevoEstado);
                
                if ($resultado['status']) {
                    // Registrar en bitácora el cambio de estado
                    $detalle = "Cambio de estado de venta ID: $idventa de '$estadoAnterior' a '$nuevoEstado'";
                    BitacoraHelper::registrarAccion('ventas', 'CAMBIO_ESTADO', $idusuario, $this->bitacoraModel, $detalle, $idventa);
                    
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
                error_log("Error en cambiarEstadoVenta: " . $e->getMessage());
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

    private function verificarPermisosCambioEstadoVenta($estadoAnterior, $nuevoEstado)
    {
        // Enviar a pago: pueden hacerlo usuarios con permisos de crear o editar
        if ($estadoAnterior === 'BORRADOR' && $nuevoEstado === 'POR_PAGAR') {
            return (PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'crear') ||
                    PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'editar'));
        }
        
        // Marcar como pagada o devolver a borrador: usuarios con permisos de editar
        if ($estadoAnterior === 'POR_PAGAR' && 
            ($nuevoEstado === 'PAGADA' || $nuevoEstado === 'BORRADOR')) {
            return PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'editar');
        }
        
        // Cualquier otro cambio de estado: requiere permisos de editar
        return PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'editar');
    }

    /**
     * Obtiene la tasa de cambio actual de una moneda específica
     */
    public function getTasaMoneda()
    {
        header('Content-Type: application/json');

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
                'message' => 'No tiene permisos para consultar tasas'
            ]);
            return;
        }

        try {
            $codigoMoneda = $_GET['codigo'] ?? '';
            
            if (empty($codigoMoneda)) {
                echo json_encode([
                    'status' => false,
                    'message' => 'Código de moneda requerido'
                ]);
                return;
            }

            $tasa = $this->model->obtenerTasaActualMoneda($codigoMoneda);

            $idUsuario = $this->BitacoraHelper->obtenerUsuarioSesion();
            if ($idUsuario) {
                $this->bitacoraModel->registrarAccion('ventas', 'CONSULTA_TASA_MONEDA', $idUsuario);
            }

            echo json_encode([
                'status' => true,
                'data' => $tasa
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => false,
                'message' => 'Error al obtener tasa: ' . $e->getMessage()
            ]);
        }
        exit();
    }
}
