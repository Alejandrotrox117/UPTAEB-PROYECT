<?php
require_once "app/core/Controllers.php";
require_once "app/models/ComprasModel.php";
require_once "helpers/helpers.php";

class Compras extends Controllers
{
    public function __construct() {
        parent::__construct();
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
        $data['page_title'] = "Gestión de Compras";
        $data['page_name'] = "Listado de Compras";
        $data['page_functions_js'] = "functions_compras.js";
        $this->views->getView($this, "compras", $data);
    }

    //DATATABLE DE COMPRAS
    public function getComprasDataTable(){
        header('Content-Type: application/json');
        $arrData = $this->get_model()->selectAllCompras();
        echo json_encode(['data' => $arrData], JSON_UNESCAPED_UNICODE);
        exit();
    }

    //BUSCAR MONEDAS
    public function getListaMonedasParaFormulario() {
        header('Content-Type: application/json');
        $modelo = $this->get_model();
        $monedas = $modelo->getMonedasActivas();
        echo json_encode($monedas);
        exit();
    }

    //BUSCAR TASAS DE MONEDAS POR FECHA
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

    //BUSCAR PRODUCTOS
    public function getListaProductosParaFormulario() {
        header('Content-Type: application/json');
        $modelo = $this->get_model();
        $productos = $modelo->getProductosConCategoria();
        echo json_encode($productos);
        exit();
    }

    //BUSCAR PROVEEDORES
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

    //BUSCAR ULTIMO PESO DE ROMANA
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

    //GUARDAR COMPRA
    public function setCompra(){
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $modelo = $this->get_model();
            $response = ["status" => false, "message" => "Error desconocido."];

            $idproveedor = intval($_POST['idproveedor_seleccionado'] ?? 0);
            date_default_timezone_set('America/Caracas');
            $fecha_compra = $_POST['fecha_compra'] ?? date('Y-m-d');
            $idmoneda_general = intval($_POST['idmoneda_general_compra'] ?? 0);
            $observaciones_compra = $_POST['observaciones_compra'] ?? '';
            $total_general_compra = floatval($_POST['total_general_input'] ?? 0);

            if (empty($idproveedor) || empty($fecha_compra) || empty($idmoneda_general) || !isset($_POST['productos_detalle'])) {
                $response['message'] = "Faltan datos obligatorios para la compra.";
                echo json_encode($response);
                exit();
            }

            $nro_compra = $modelo->generarNumeroCompra();
            if (strpos($nro_compra, "ERROR") !== false) { 
                $response['message'] = "Error al generar el número de compra.";
                echo json_encode($response);
                exit();
            }

            $subtotal_general_compra = $total_general_compra;
            $descuento_porcentaje_general = 0;
            $monto_descuento_general = 0;

            $datosCompra = [
                "nro_compra" => $nro_compra,
                "fecha_compra" => $fecha_compra,
                "idproveedor" => $idproveedor,
                "idmoneda_general" => $idmoneda_general,
                "subtotal_general_compra" => $subtotal_general_compra,
                "descuento_porcentaje_compra" => $descuento_porcentaje_general,
                "monto_descuento_compra" => $monto_descuento_general,
                "total_general_compra" => $total_general_compra,
                "observaciones_compra" => $observaciones_compra
            ];

            $detallesCompraInput = json_decode($_POST['productos_detalle'], true);
            if (json_last_error() !== JSON_ERROR_NONE || empty($detallesCompraInput)) {
                 $response['message'] = "No hay productos en el detalle o el formato es incorrecto.";
                 echo json_encode($response);
                 exit();
            }

            $detallesParaGuardar = [];
            foreach ($detallesCompraInput as $item) {
                $idProductoItem = intval($item['idproducto'] ?? 0);
                if ($idProductoItem <= 0) {
                    $response['message'] = "ID de producto inválido en el detalle.";
                    echo json_encode($response);
                    exit();
                }

                $productoInfo = $modelo->getProductoById($idProductoItem);
                if (!$productoInfo) {
                    $response['message'] = "Producto no encontrado: ID " . $idProductoItem;
                    echo json_encode($response);
                    exit();
                }

                $cantidad_final = floatval($item['cantidad'] ?? 0);
                $peso_vehiculo = isset($item['peso_vehiculo']) ? floatval($item['peso_vehiculo']) : null;
                $peso_bruto = isset($item['peso_bruto']) ? floatval($item['peso_bruto']) : null;
                $peso_neto = isset($item['peso_neto']) ? floatval($item['peso_neto']) : null;

                if ($cantidad_final <= 0) {
                    $response['message'] = "Cantidad debe ser mayor a cero para: " . htmlspecialchars($productoInfo['nombre'], ENT_QUOTES, 'UTF-8');
                    echo json_encode($response);
                    exit();
                }

                if (floatval($item['precio_unitario_compra'] ?? 0) <= 0) {
                    $response['message'] = "Precio debe ser mayor a cero para: " . htmlspecialchars($productoInfo['nombre'], ENT_QUOTES, 'UTF-8');
                    echo json_encode($response);
                    exit();
                }

                $detallesParaGuardar[] = [
                    "idproducto" => $productoInfo['idproducto'],
                    "descripcion_temporal_producto" => $item['nombre_producto'] ?? $productoInfo['nombre'],
                    "cantidad" => $cantidad_final,
                    "descuento" => floatval($item['descuento'] ?? 0),
                    "precio_unitario_compra" => floatval($item['precio_unitario_compra'] ?? 0),
                    "idmoneda_detalle" => $item['moneda'],
                    "subtotal_linea" => floatval($item['subtotal_linea'] ?? 0),
                    "subtotal_original_linea" => floatval($item['subtotal_original_linea'] ?? 0),
                    "monto_descuento_linea" => floatval($item['monto_descuento_linea'] ?? 0),
                    "peso_vehiculo" => $peso_vehiculo,
                    "peso_bruto" => $peso_bruto,
                    "peso_neto" => $peso_neto,
                ];
            }

            if (empty($detallesParaGuardar)) {
                $response['message'] = "No se procesaron productos válidos.";
                echo json_encode($response);
                exit();
            }

            $idCompraInsertada = $modelo->insertarCompra($datosCompra, $detallesParaGuardar);

            if ($idCompraInsertada) {
                $response = [
                    "status" => true, 
                    "message" => "Compra registrada correctamente con Nro: " . htmlspecialchars($nro_compra, ENT_QUOTES, 'UTF-8'), 
                    "idcompra" => $idCompraInsertada
                ];
            } else {
                $response = ["status" => false, "message" => "Error al registrar la compra."];
            }

            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(["status" => false, "message" => "Método no permitido."], JSON_UNESCAPED_UNICODE);
        }
        exit();
    }

    //VER DETALLE DE COMPRA
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

    //ELIMINAR COMPRA
    public function deleteCompra(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(
                ["status"  => false,"message" => "Método no permitido."],JSON_UNESCAPED_UNICODE);
            exit;
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
            $response = ["status"  => true,"message" => "Compra marcada como inactiva correctamente."];
        } else {
            $response = ["status"  => false,"message" => "Error al marcar la compra como inactiva."];
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    //CAMBIAR ESTADO DE COMPRA
    public function cambiarEstadoCompra(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            $idcompra = isset($data['idcompra']) ? intval($data['idcompra']) : 0;
            $nuevoEstado = isset($data['nuevo_estado']) ? trim($data['nuevo_estado']) : '';

            if ($idcompra > 0 && !empty($nuevoEstado)) {
                $resultado = $this->get_model()->cambiarEstadoCompra($idcompra, $nuevoEstado);
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            } else {
                $response = ["status" => false, "message" => "Datos incompletos para cambiar estado."];
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
        }
        die();
    }

    //REGISTRAR NUEVO PROVEEDOR EN COMPRAS
    public function createProveedorinCompras(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'nombre' => trim($_POST['nombre'] ?? ''),
                'apellido' => trim($_POST['apellido'] ?? ''),
                'identificacion' => trim($_POST['identificacion'] ?? ''),
                'telefono_principal' => trim($_POST['telefono_principal'] ?? ''),
                'correo_electronico' => trim($_POST['correo_electronico'] ?? ''),
                'direccion' => trim($_POST['direccion'] ?? ''),
                'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?: null,
                'genero' => $_POST['genero'] ?? null,
                'observaciones' => trim($_POST['observaciones'] ?? ''),
            ];

            $request = $this->get_model()->insertProveedor($data);
            echo json_encode($request, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    //OBTENER PROVEEDOR POR ID
    public function getProveedorById($idproveedor){
        if ($idproveedor > 0) {
            $proveedor = $this->get_model()->getProveedorById($idproveedor);
            if (empty($proveedor)) {
                $response = ["status" => false, "message" => "Proveedor no encontrado."];
            } else {
                $response = ["status" => true, "data" => $proveedor];
            }
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    //OBTENER COMPRA PARA EDITAR
    public function getCompraParaEditar(int $idcompra) {
        header('Content-Type: application/json');
        
        if ($idcompra > 0) {
            $datosCompletos = $this->get_model()->getCompraCompletaParaEditar($idcompra);
            
            if ($datosCompletos) {
                $response = [
                    "status" => true,
                    "data" => $datosCompletos
                ];
            } else {
                $response = [
                    "status" => false,
                    "message" => "Compra no encontrada para editar."
                ];
            }
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                "status" => false,
                "message" => "ID de compra inválido."
            ], JSON_UNESCAPED_UNICODE);
        }
        exit();
    }

    //ACTUALIZAR COMPRA
    public function updateCompra() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $modelo = $this->get_model();
            $response = ["status" => false, "message" => "Error desconocido."];

            $idcompra = intval($_POST['idcompra'] ?? 0);
            $idproveedor = intval($_POST['idproveedor_seleccionado'] ?? 0);
            $fecha_compra = $_POST['fechaActualizar'] ?? '';
            $idmoneda_general = intval($_POST['idmoneda_general_compra'] ?? 0);
            $observaciones_compra = $_POST['observacionesActualizar'] ?? '';
            $total_general_compra = floatval($_POST['total_general_input'] ?? 0);

            $subtotal_general_compra = $total_general_compra;
            $descuento_porcentaje_compra = 0;
            $monto_descuento_compra = 0;

            if (empty($idcompra) || empty($idproveedor) || empty($fecha_compra) || empty($idmoneda_general) || !isset($_POST['productos_detalle'])) {
                $response['message'] = "Faltan datos obligatorios para actualizar la compra.";
                echo json_encode($response);
                exit();
            }

            $datosCompra = [
                "fecha_compra" => $fecha_compra,
                "idproveedor" => $idproveedor,
                "idmoneda_general" => $idmoneda_general,
                "subtotal_general_compra" => $subtotal_general_compra,
                "descuento_porcentaje_compra" => $descuento_porcentaje_compra,
                "monto_descuento_compra" => $monto_descuento_compra,
                "total_general_compra" => $total_general_compra,
                "observaciones_compra" => $observaciones_compra
            ];

            $detallesCompraInput = json_decode($_POST['productos_detalle'], true);
            if (json_last_error() !== JSON_ERROR_NONE || empty($detallesCompraInput)) {
                $response['message'] = "No hay productos en el detalle o el formato es incorrecto.";
                echo json_encode($response);
                exit();
            }

            $detallesParaGuardar = [];
            foreach ($detallesCompraInput as $item) {
                $idProductoItem = intval($item['idproducto'] ?? 0);
                if ($idProductoItem <= 0) {
                    $response['message'] = "ID de producto inválido en el detalle.";
                    echo json_encode($response);
                    exit();
                }

                $productoInfo = $modelo->getProductoById($idProductoItem);
                if (!$productoInfo) {
                    $response['message'] = "Producto no encontrado: ID " . $idProductoItem;
                    echo json_encode($response);
                    exit();
                }

                $cantidad_final = floatval($item['cantidad'] ?? 0);
                $peso_vehiculo = isset($item['peso_vehiculo']) ? floatval($item['peso_vehiculo']) : null;
                $peso_bruto = isset($item['peso_bruto']) ? floatval($item['peso_bruto']) : null;
                $peso_neto = isset($item['peso_neto']) ? floatval($item['peso_neto']) : null;

                if ($cantidad_final <= 0) {
                    $response['message'] = "Cantidad debe ser mayor a cero para: " . htmlspecialchars($productoInfo['nombre'], ENT_QUOTES, 'UTF-8');
                    echo json_encode($response);
                    exit();
                }

                if (floatval($item['precio_unitario_compra'] ?? 0) <= 0) {
                    $response['message'] = "Precio debe ser mayor a cero para: " . htmlspecialchars($productoInfo['nombre'], ENT_QUOTES, 'UTF-8');
                    echo json_encode($response);
                    exit();
                }

                $detallesParaGuardar[] = [
                    "idproducto" => $productoInfo['idproducto'],
                    "descripcion_temporal_producto" => $item['nombre_producto'] ?? $productoInfo['nombre'],
                    "cantidad" => $cantidad_final,
                    "descuento" => floatval($item['descuento'] ?? 0),
                    "precio_unitario_compra" => floatval($item['precio_unitario_compra'] ?? 0),
                    "idmoneda_detalle" => $item['moneda'],
                    "subtotal_linea" => floatval($item['subtotal_linea'] ?? 0),
                    "peso_vehiculo" => $peso_vehiculo,
                    "peso_bruto" => $peso_bruto,
                    "peso_neto" => $peso_neto,
                ];
            }

            if (empty($detallesParaGuardar)) {
                $response['message'] = "No se procesaron productos válidos.";
                echo json_encode($response);
                exit();
            }

            $actualizacionExitosa = $modelo->actualizarCompra($idcompra, $datosCompra, $detallesParaGuardar);

            if ($actualizacionExitosa) {
                $response = ["status" => true, "message" => "Compra actualizada correctamente."];
            } else {
                $response = ["status" => false, "message" => "Error al actualizar la compra en la base de datos."];
            }

            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(["status" => false, "message" => "Método no permitido."], JSON_UNESCAPED_UNICODE);
        }
        exit();
    }

    // GENERAR NOTA DE ENTREGA/FACTURA
    public function factura($idcompra){
    $data['page_tag'] = "Compra - Sistema de Compras";
    $data['page_title'] = "Factura de Compra <small>Sistema de Compras</small>";
    $data['page_name'] = "Factura de Compra";
    $data['arrCompra'] = $this->get_model()->selectCompra($idcompra);
    $this->views->getView($this,"factura_compra",$data);
    }

    //GUARDAR PESO DE ROMANA
    public function guardarPesoRomana()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => false, 'message' => 'Método no permitido.']);
            exit();
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['status' => false, 'message' => 'JSON inválido.']);
            exit();
        }

        $peso = isset($data['peso']) ? $data['peso'] : null;
        $fecha = $data['fecha'] ?? date('Y-m-d H:i:s');
        $estatus = $data['estatus'] ?? 'activo';

        if ($peso === null) {
            echo json_encode(['status' => false, 'message' => 'El campo peso es obligatorio.']);
            exit();
        }

        $modelo = $this->get_model();
        $resultado = $modelo->guardarPesoRomana($peso, $fecha, $estatus);

        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        exit();
    }
}
?>