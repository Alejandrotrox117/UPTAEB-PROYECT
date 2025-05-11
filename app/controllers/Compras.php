<?php
require_once "app/core/Controllers.php"; // Asegúrate que la ruta sea correcta
require_once "app/models/ComprasModel.php"; // Asegúrate que la ruta sea correcta
require_once "helpers/helpers.php"; 

class Compras extends Controllers
{
    // El constructor se mantiene como lo tenías,
    // asumiendo que la clase base Controllers o getModelInstance se encarga del modelo.
    public function __construct() {
        parent::__construct();
    }

    // Método para asegurar que el modelo está cargado
    private function getModelInstance() {
        if (!isset($this->model) || !($this->model instanceof ComprasModel)) {
            $this->model = new ComprasModel();
        }
        return $this->model;
    }

    public function index() // Vista del listado
    {
        $data['page_title'] = "Gestión de Compras";
        $data['page_name'] = "Listado de Compras";
        $data['page_functions_js'] = "functions_compras.js";
        $this->views->getView($this, "compras", $data);
    }

    public function getComprasDataTable()
    {
        header('Content-Type: application/json');
        $modelo = $this->getModelInstance();

        $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 0;
        $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
        $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
        $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

        $orderColumnIndex = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
        $orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'asc';
        $columnMapping = [
            0 => 'c.nro_compra',
            1 => 'c.fecha',
            2 => 'p.nombre', 
            3 => 'c.total_general',
        ];
        $orderColumnName = $columnMapping[$orderColumnIndex] ?? 'c.nro_compra'; // Orden por defecto

        $datosFilas = $modelo->getComprasServerSide(
            $start,
            $length,
            $searchValue,
            $orderColumnName,
            $orderDir
        );

        $recordsTotal = $modelo->countAllCompras();
        $recordsFiltered = $modelo->countFilteredCompras($searchValue);

        $dataParaEnviar = [];
        foreach ($datosFilas as $fila) {
            $fila['total_general'] = ($fila['moneda_simbolo'] ?? '$') . ' ' . number_format(floatval($fila['total_general']), 2);
            $dataParaEnviar[] = $fila;
        }

        $response = [
            "draw" => $draw,
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $dataParaEnviar,
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    public function getListaMonedasParaFormulario() {
        header('Content-Type: application/json');
        $modelo = $this->getModelInstance();
        $monedas = $modelo->getMonedasActivas();
        echo json_encode($monedas);
        exit();
    }

    public function getListaProductosParaFormulario() {
        header('Content-Type: application/json');
        $modelo = $this->getModelInstance();
        $productos = $modelo->getProductosConCategoria();
        echo json_encode($productos);
        exit();
    }

    public function buscarProveedores() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['term'])) {
            // SIN limpiarCadena(): $_GET['term'] se usa directamente.
            // El modelo DEBE usar sentencias preparadas para evitar Inyección SQL.
            $termino = $_GET['term'];
            $modelo = $this->getModelInstance();
            $proveedores = $modelo->buscarProveedor($termino);
            echo json_encode($proveedores);
        } else {
            echo json_encode([]); // Devolver array vacío si no hay término
        }
        exit();
    }

    public function registrarNuevoProveedor() {
        header('Content-Type: application/json');
        $response = ["status" => false, "message" => "Datos incompletos o incorrectos."];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $identificacion = $_POST['identificacion_proveedor_nuevo'] ?? '';
            $nombre = $_POST['nombre_proveedor_nuevo'] ?? '';

            if (empty($identificacion) || empty($nombre)) {
                $response['message'] = "Identificación y nombre del proveedor son obligatorios.";
                echo json_encode($response);
                exit();
            }

            $modelo = $this->getModelInstance();
            $existente = $modelo->getProveedorByIdentificacion($identificacion);

            if ($existente && isset($existente['idproveedor'])) {
                $response['message'] = "Proveedor con esta identificación ya existe.";
                $response['idproveedor'] = $existente['idproveedor'];
                $response['nombre'] = htmlspecialchars(($existente['nombre'] ?? '') . ' ' . ($existente['apellido'] ?? ''), ENT_QUOTES, 'UTF-8');
                echo json_encode($response);
                exit();
            }

            // Pasar todo el $_POST al modelo. El modelo debe ser selectivo con los campos que usa.
            $idNuevoProveedor = $modelo->registrarProveedor($_POST);

            if ($idNuevoProveedor) {
                $response = [
                    "status" => true,
                    "message" => "Proveedor registrado con éxito.",
                    "idproveedor" => $idNuevoProveedor,
                    // Sanitizar nombre para devolverlo al cliente si se va a mostrar directamente
                    "nombre" => htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8')
                ];
            } else {
                $response['message'] = "Error al registrar el proveedor. Verifique los logs del servidor.";
            }
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    public function setCompra()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $modelo = $this->getModelInstance();
            $response = ["status" => false, "message" => "Error desconocido."];

            // SIN limpiarCadena(): Los datos de $_POST se usan directamente.
            // El modelo DEBE usar sentencias preparadas.
            // Se mantienen las conversiones de tipo y valores por defecto.
            $idproveedor = intval($_POST['idproveedor_seleccionado'] ?? 0);
            $fecha_compra = $_POST['fecha_compra'] ?? date('Y-m-d'); // Validar formato de fecha si es necesario
            $idmoneda_general = intval($_POST['idmoneda_general_compra'] ?? 0);
            $observaciones_compra = $_POST['observaciones_compra'] ?? '';
            $subtotal_general_compra = floatval($_POST['subtotal_general_input'] ?? 0);
            $descuento_porcentaje_compra = floatval($_POST['descuento_porcentaje_input'] ?? 0);
            $monto_descuento_compra = floatval($_POST['monto_descuento_input'] ?? 0);
            $total_general_compra = floatval($_POST['total_general_input'] ?? 0);

            // Validaciones básicas
            if (empty($idproveedor) || empty($fecha_compra) || empty($idmoneda_general) || !isset($_POST['productos_detalle'])) {
                $response['message'] = "Faltan datos obligatorios para la compra (proveedor, fecha, moneda o detalles).";
                echo json_encode($response);
                exit();
            }
            // Podrías añadir más validaciones aquí (ej. formato de fecha, rangos numéricos)

            $nro_compra = $modelo->generarNumeroCompra();
            if (strpos($nro_compra, "ERROR") !== false) { // Chequeo si la generación de nro_compra falló
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
                "observaciones_compra" => $observaciones_compra // El modelo debe manejar la sanitización para SQL
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

                if ($idCategoriaProducto === 1) { // Materiales por Peso
                    // La conversión a booleano de 'no_usa_vehiculo' debe ser robusta
                    $noUsaVehiculo = filter_var($item['no_usa_vehiculo'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    if ($noUsaVehiculo) {
                        $peso_neto_calculado = floatval($item['peso_neto_directo'] ?? 0);
                    } else {
                        $peso_bruto_val = floatval($item['peso_bruto'] ?? 0);
                        $peso_vehiculo_val = floatval($item['peso_vehiculo'] ?? 0);
                        $peso_neto_calculado = $peso_bruto_val - $peso_vehiculo_val;
                    }
                    $cantidad_base = $peso_neto_calculado;
                } else { // Productos por Unidad
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
                    "descripcion_temporal_producto" => $productoInfo['nombre_producto'], // El modelo debe sanitizar para SQL
                    "cantidad" => $cantidad_base,
                    "precio_unitario_compra" => floatval($item['precio_unitario'] ?? 0),
                    "idmoneda_detalle" => intval($item['idmoneda_item'] ?? $idmoneda_general),
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
            }
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } else {
            $response = ["status" => false, "message" => "Método no permitido."];
            echo json_encode($response);
        }
        exit();
    }
}
