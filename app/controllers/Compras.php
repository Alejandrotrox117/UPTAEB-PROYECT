<?php
require_once "app/core/Controllers.php"; // Asegúrate que la ruta sea correcta
require_once "app/models/ComprasModel.php"; // Asegúrate que la ruta sea correcta
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

    public function getComprasDataTable(){
        header('Content-Type: application/json');
        $arrData = $this->get_model()->selectAllCompras();
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

    public function registrarNuevoProveedor() {
        header('Content-Type: application/json');
        $response = ["status" => false, "message" => "Datos incompletos o incorrectos."];

        if ($_POST) {
            $data = [
                'nombre' => $_POST['nombre'] ?? null,
                'apellido' => $_POST['apellido'] ?? null,
                'identificacion' => $_POST['identificacion'] ?? null,
                'telefono_principal' => $_POST['telefono_principal'] ?? null,
                'correo_electronico' => $_POST['correo_electronico'] ?? null, FILTER_SANITIZE_EMAIL,
                'direccion' => $_POST['direccion'] ?? null,
                'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
                'genero' => $_POST['genero'] ?? null,
                'estatus' => $_POST['estatus'] ?? 'ACTIVO',
                'observaciones' => $_POST['observaciones'] ?? null,
            ];
            var_dump($_POST);
            die();

        
            $modelo = $this->get_model();
            $existente = $modelo->getProveedorByIdentificacion($_POST['identificacion']);

            if ($existente && isset($existente['idproveedor'])) {
                $response['message'] = "Proveedor con esta identificación ya existe.";
                $response['idproveedor'] = $existente['idproveedor'];
                $response['nombre'] = htmlspecialchars(($existente['nombre'] ?? '') . ' ' . ($existente['apellido'] ?? ''), ENT_QUOTES, 'UTF-8');
                echo json_encode($response);
                exit();
            }

            $idNuevoProveedor = $modelo->registrarProveedor($data);

            if ($idNuevoProveedor) {
                $response = [
                    "status" => true,
                    "message" => "Proveedor registrado con éxito.",
                    "idproveedor" => $idNuevoProveedor,
                    "nombre" => htmlspecialchars($_POST['nombre'], ENT_QUOTES, 'UTF-8')
                ];
            } else {
                $response['message'] = "Error al registrar el proveedor. Verifique los logs del servidor.";
            }
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    public function getUltimoPesoRomana(){
        header('Content-Type: application/json');
        $modelo = $this->get_model();
        $peso = $modelo->getUltimoPesoRomana();
        if ($peso !== null) {
            echo json_encode(['status' => true, 'peso' => $peso]);
        } else {
            echo json_encode(['status' => false, 'message' => 'No hay registros de peso.']);
        }
        exit();
    }

    public function setCompra(){
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $modelo = $this->get_model();
            $response = ["status" => false, "message" => "Error desconocido."];

            $idproveedor = intval($_POST['idproveedor_seleccionado'] ?? 0);
            $fecha_compra = $_POST['fecha_compra'] ?? date('Y-m-d'); 
            $idmoneda_general = intval($_POST['idmoneda_general_compra'] ?? 0);
            $observaciones_compra = $_POST['observaciones_compra'] ?? '';
            $subtotal_general_compra = floatval($_POST['subtotal_general_input'] ?? 0);
            $descuento_porcentaje_compra = floatval($_POST['descuento_porcentaje_input'] ?? 0);
            $monto_descuento_compra = floatval($_POST['monto_descuento_input'] ?? 0);
            $total_general_compra = floatval($_POST['total_general_input'] ?? 0);

            if (empty($idproveedor) || empty($fecha_compra) || empty($idmoneda_general) || !isset($_POST['productos_detalle'])) {
                $response['message'] = "Faltan datos obligatorios para la compra (proveedor, fecha, moneda o detalles).";
                echo json_encode($response);
                exit();
            }

            $nro_compra = $modelo->generarNumeroCompra();
            if (strpos($nro_compra, "ERROR") !== false) { 
                $response['message'] = "Error al generar el número de compra.";
                echo json_encode($response);
                exit();
            }

            $datosCompra = [
                "nro_compra" => $nro_compra,
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
                    $response['message'] = "Producto no encontrado o inválido en el detalle: ID " . $idProductoItem;
                    echo json_encode($response);
                    exit();
                }

                $peso_neto_calculado = 0;
                $cantidad_base = 0;
                $idCategoriaProducto = intval($productoInfo['idcategoria'] ?? 0);

                if ($idCategoriaProducto === 1) { 
                    $noUsaVehiculo = filter_var($item['no_usa_vehiculo'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    if ($noUsaVehiculo) {
                        $peso_neto_calculado = floatval($item['peso_neto_directo'] ?? 0);
                    } else {
                        $peso_bruto_val = floatval($item['peso_bruto'] ?? 0);
                        $peso_vehiculo_val = floatval($item['peso_vehiculo'] ?? 0);
                        $peso_neto_calculado = $peso_bruto_val - $peso_vehiculo_val;
                    }
                    $cantidad_base = $peso_neto_calculado;
                } else { 
                    $cantidad_base = floatval($item['cantidad_unidad'] ?? 0);
                }

                if ($cantidad_base <= 0) {
                    $response['message'] = "Cantidad/Peso neto debe ser mayor a cero para el producto: " . htmlspecialchars($productoInfo['nombre_producto'], ENT_QUOTES, 'UTF-8');
                    echo json_encode($response);
                    exit();
                }
                if (floatval($item['precio_unitario'] ?? 0) <= 0) {
                    $response['message'] = "Precio unitario debe ser mayor a cero para el producto: " . htmlspecialchars($productoInfo['nombre_producto'], ENT_QUOTES, 'UTF-8');
                    echo json_encode($response);
                    exit();
                }

                $detallesParaGuardar[] = [
                    "idproducto" => $productoInfo['idproducto'],
                    "descripcion_temporal_producto" => $productoInfo['nombre'],
                    "cantidad" => $cantidad_base,
                    "precio_unitario_compra" => floatval($item['precio_unitario'] ?? 0),
                    "idmoneda_detalle" => intval($item['moneda'] ?? $idmoneda_general),
                    "subtotal_linea" => floatval($item['subtotal_linea'] ?? 0),
                    "peso_vehiculo" => ($idCategoriaProducto === 1 && !filter_var($item['no_usa_vehiculo'] ?? false, FILTER_VALIDATE_BOOLEAN)) ? floatval($item['peso_vehiculo'] ?? 0) : null,
                    "peso_bruto" => ($idCategoriaProducto === 1 && !filter_var($item['no_usa_vehiculo'] ?? false, FILTER_VALIDATE_BOOLEAN)) ? floatval($item['peso_bruto'] ?? 0) : null,
                    "peso_neto" => ($idCategoriaProducto === 1) ? $peso_neto_calculado : null,
                ];
            }

            if (empty($detallesParaGuardar)) {
                $response['message'] = "No se procesaron productos válidos para el detalle.";
                echo json_encode($response);
                exit();
            }

            $idCompraInsertada = $modelo->insertarCompra($datosCompra, $detallesParaGuardar);

            if ($idCompraInsertada) {
                $response = ["status" => true, "message" => "Compra registrada correctamente con Nro: " . htmlspecialchars($nro_compra, ENT_QUOTES, 'UTF-8'), "idcompra" => $idCompraInsertada];
            } else {
                $response = ["status" => false, "message" => "Error al registrar la compra en la base de datos. Revise los logs del servidor."];
                $response['debug'] = error_get_last();
            }
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } else {
            $response = ["status" => false, "message" => "Método no permitido."];
            echo json_encode($response);
        }
        exit();
    }

    public function getDetalleCompra($idcompra) {
        header('Content-Type: application/json');

        if (!$idcompra) {
            echo json_encode(['status' => false, 'message' => 'ID de compra inválido']);
            exit();
        }

        $this->get_model()->setIdCompra(intval($idcompra));
        $compra = $this->get_model()->getCompraById($idcompra);

        if (!$compra) {
            echo json_encode(['status' => false, 'message' => 'Compra no encontrada']);
            exit();
        }

        $detalles = $this->get_model()->getDetalleCompraById($idcompra);

        if (!$detalles) {
            echo json_encode(['status' => false, 'message' => 'Detalles de compra no encontrados']);
            exit();
        }

        // Solo un echo json_encode con toda la información
        echo json_encode([
            'status' => true,
            'compra' => $compra,
            'detalle' => $detalles
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    //EDITAR COMPRA
    public function editarCompra() {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $modelo = $this->get_model();
        $response = ["status" => false, "message" => "Error desconocido."];

        $idcompra = intval($_POST['idcompra'] ?? 0);
        $idproveedor = intval($_POST['idproveedor_seleccionado'] ?? 0);
        $fecha_compra = $_POST['fecha_compra'] ?? date('Y-m-d');
        $idmoneda_general = intval($_POST['idmoneda_general_compra'] ?? 0);
        $observaciones_compra = $_POST['observaciones_compra'] ?? '';
        $subtotal_general_compra = floatval($_POST['subtotal_general_input'] ?? 0);
        $descuento_porcentaje_compra = floatval($_POST['descuento_porcentaje_input'] ?? 0);
        $monto_descuento_compra = floatval($_POST['monto_descuento_input'] ?? 0);
        $total_general_compra = floatval($_POST['total_general_input'] ?? 0);
        $datosCompra = [
            "idcompra" => $idcompra,
            "fecha_compra" => $fecha_compra,
            "idproveedor" => $idproveedor,
            "idmoneda_general" => $idmoneda_general,
            "subtotal_general_compra" => $subtotal_general_compra,
            "descuento_porcentaje_compra" => $descuento_porcentaje_compra,
            "monto_descuento_compra" => $monto_descuento_compra,
            "total_general_compra" => $total_general_compra,
            "observaciones_compra" => $observaciones_compra,
        ];


        // Llama al modelo
        try {
            $modelo->editarCompra($datosCompra);
            $response = ["status" => true, "message" => "Compra actualizada correctamente."];
        } catch (Exception $e) {
            $response = ["status" => false, "message" => $e->getMessage()];
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }
    exit();
}



    

}
