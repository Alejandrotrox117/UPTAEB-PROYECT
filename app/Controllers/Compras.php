<?php

use App\Models\ComprasModel;
use App\Models\BitacoraModel;
use App\Models\NotificacionesModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\PermisosModuloVerificar;

/**
 * Controlador Compras - Estilo Funcional
 */

// Helper para obtener modelos
function compras_getModels()
{
    return [
        'compras' => new ComprasModel(),
        'bitacora' => new BitacoraModel(),
        'notificaciones' => new NotificacionesModel()
    ];
}

// Helper para verificar acceso común
function compras_verificarAcceso($accion = 'ver')
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!obtenerUsuarioSesion()) {
        header('Location: ' . base_url() . '/login');
        die();
    }

    if ($accion === 'acceso_modulo') {
        if (!PermisosModuloVerificar::verificarAccesoModulo('compras')) {
            renderView('errors', "permisos");
            exit();
        }
    } else {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('compras', $accion)) {
            renderView('errors', "permisos");
            exit();
        }
    }
}

function compras_verFactura($idcompra)
{
    compras_factura($idcompra);
}

function compras_index()
{
    compras_verificarAcceso('acceso_modulo');

    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'ver')) {
        renderView('errors', "permisos");
        exit();
    }

    $idusuario = obtenerUsuarioSesion();
    registrarAccesoModulo('compras', $idusuario);

    $permisos = [
        'puedeVer' => PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'ver'),
        'puedeCrear' => PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'crear'),
        'puedeEditar' => PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'editar'),
        'puedeEliminar' => PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'eliminar'),
        'puedeExportar' => PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'exportar')
    ];

    $data['page_title'] = "Gestión de Compras";
    $data['page_name'] = "Listado de Compras";
    $data['page_functions_js'] = "functions_compras.js";
    $data['permisos'] = $permisos;
    $data['idRolUsuarioAutenticado'] = $_SESSION['rol_id'] ?? 0;
    $data['rolUsuarioAutenticado'] = $_SESSION['rol_nombre'] ?? '';

    renderView("compras", "compras", $data);
}

function compras_getComprasDataTable()
{
    header('Content-Type: application/json');
    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'ver')) {
        echo json_encode(['data' => []], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $models = compras_getModels();
    $idusuario = obtenerUsuarioSesion();
    $arrData = $models['compras']->selectAllCompras($idusuario);
    echo json_encode(['data' => $arrData], JSON_UNESCAPED_UNICODE);
    exit();
}

function compras_getListaMonedasParaFormulario()
{
    header('Content-Type: application/json');
    $models = compras_getModels();
    $monedas = $models['compras']->getMonedasActivas();
    echo json_encode($monedas);
    exit();
}

function compras_getTasasMonedasPorFecha()
{
    header('Content-Type: application/json');
    if (!isset($_GET['fecha'])) {
        echo json_encode(['status' => false, 'message' => 'Fecha requerida']);
        exit();
    }
    $models = compras_getModels();
    $tasas = $models['compras']->getTasasPorFecha($_GET['fecha']);
    echo json_encode(['status' => true, 'tasas' => $tasas]);
    exit();
}

function compras_getListaProductosParaFormulario()
{
    header('Content-Type: application/json');
    $models = compras_getModels();
    $productos = $models['compras']->getProductosConCategoria();
    echo json_encode($productos);
    exit();
}

function compras_buscarProveedores()
{
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['term'])) {
        $models = compras_getModels();
        $proveedores = $models['compras']->buscarProveedor($_GET['term']);
        echo json_encode($proveedores);
    } else {
        echo json_encode([]);
    }
    exit();
}

function compras_getUltimoPesoRomana()
{
    $filePath = 'C:\com_data\peso_mysql.json';
    if (!file_exists($filePath)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => false, 'message' => 'Archivo de peso no encontrado']);
        return;
    }
    $data = json_decode(file_get_contents($filePath), true);
    header('Content-Type: application/json');
    echo json_encode(['status' => true, 'peso' => $data["peso_numerico"], 'fecha_hora' => $data["fecha_hora"]]);
}

function compras_setCompra()
{
    header('Content-Type: application/json');
    $idusuario = obtenerUsuarioSesion();
    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'crear')) {
        echo json_encode(['status' => false, 'message' => 'No tiene permisos para registrar compras.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $models = compras_getModels();
    $idproveedor = intval($_POST['idproveedor_seleccionado'] ?? 0);
    $fecha_compra = $_POST['fecha_compra'] ?? date('Y-m-d');
    $total_general_compra = floatval($_POST['total_general_input'] ?? 0);

    if (empty($idproveedor)) {
        echo json_encode(['status' => false, 'message' => 'Falta seleccionar proveedor.']);
        exit();
    }

    $nro_compra = $models['compras']->generarNumeroCompra();
    $datosCompra = [
        'nro_compra' => $nro_compra,
        'fecha_compra' => $fecha_compra,
        'idproveedor' => $idproveedor,
        'idmoneda_general' => 3,
        'subtotal_general_compra' => $total_general_compra,
        'descuento_porcentaje_compra' => 0,
        'monto_descuento_compra' => 0,
        'total_general_compra' => $total_general_compra,
        'observaciones_compra' => $_POST['observaciones_compra'] ?? '',
    ];

    $detallesCompraInput = json_decode($_POST['productos_detalle'], true);
    $detallesParaGuardar = [];
    if (is_array($detallesCompraInput)) {
        foreach ($detallesCompraInput as $item) {
            $idProductoItem = intval($item['idproducto'] ?? 0);
            $detallesParaGuardar[] = [
                'idproducto' => $idProductoItem,
                'descripcion_temporal_producto' => $item['nombre_producto'],
                'cantidad' => floatval($item['cantidad'] ?? 0),
                'descuento' => floatval($item['descuento'] ?? 0),
                'precio_unitario_compra' => floatval($item['precio_unitario_compra'] ?? 0),
                'idmoneda_detalle' => !empty($item['moneda']) ? intval($item['moneda']) : 3,
                'subtotal_linea' => floatval($item['subtotal_linea'] ?? 0),
                'subtotal_original_linea' => floatval($item['subtotal_original_linea'] ?? 0),
                'monto_descuento_linea' => floatval($item['monto_descuento_linea'] ?? 0),
                'peso_vehiculo' => $item['peso_vehiculo'] ?? null,
                'peso_bruto' => $item['peso_bruto'] ?? null,
                'peso_neto' => $item['peso_neto'] ?? null,
            ];
        }
    }

    $resultado = $models['compras']->insertarCompra($datosCompra, $detallesParaGuardar);
    if ($resultado['status']) {
        registrarEnBitacora('compras', 'INSERTAR');
        echo json_encode(['status' => true, 'message' => 'Compra registrada.', 'idcompra' => $resultado['idcompra'] ?? 0], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['status' => false, 'message' => $resultado['message'] ?? 'Error al registrar.'], JSON_UNESCAPED_UNICODE);
    }
    exit();
}

function compras_getCompraById($idcompra)
{
    header('Content-Type: application/json');
    $id = is_array($idcompra) ? ($idcompra[0] ?? 0) : $idcompra;
    $id = intval($id);

    $models = compras_getModels();
    $compra = $models['compras']->getCompraById($id);
    $detalles = $models['compras']->getDetalleCompraById($id);

    echo json_encode(['status' => true, 'data' => ['compra' => $compra, 'detalles' => $detalles]], JSON_UNESCAPED_UNICODE);
    exit();
}

function compras_updateCompra()
{
    header('Content-Type: application/json');
    $idusuario = obtenerUsuarioSesion();
    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'editar')) {
        echo json_encode(['status' => false, 'message' => 'No tiene permisos para editar compras.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $models = compras_getModels();
    $idcompra = intval($_POST['idcompra'] ?? 0);
    $idproveedor = intval($_POST['idproveedor_seleccionado'] ?? 0);
    $fecha_compra = $_POST['fecha_compra'] ?? date('Y-m-d');
    $total_general_compra = floatval($_POST['total_general_input'] ?? 0);

    if ($idcompra <= 0 || empty($idproveedor)) {
        echo json_encode(['status' => false, 'message' => 'Faltan datos obligatorios para actualizar la compra.']);
        exit();
    }

    $datosCompra = [
        'fecha_compra' => $fecha_compra,
        'idproveedor' => $idproveedor,
        'idmoneda_general' => intval($_POST['idmoneda_general_compra'] ?? 3),
        'subtotal_general_compra' => $total_general_compra,
        'total_general_compra' => $total_general_compra,
        'observaciones_compra' => $_POST['observacionesActualizar'] ?? '',
    ];

    $detallesCompraInput = json_decode($_POST['productos_detalle'], true);
    $detallesParaGuardar = [];
    if (is_array($detallesCompraInput)) {
        foreach ($detallesCompraInput as $item) {
            $detallesParaGuardar[] = [
                'idproducto' => intval($item['idproducto']),
                'descripcion_temporal_producto' => $item['nombre_producto'],
                'cantidad' => floatval($item['cantidad']),
                'descuento' => floatval($item['descuento'] ?? 0),
                'precio_unitario_compra' => floatval($item['precio_unitario_compra']),
                'idmoneda_detalle' => intval($item['moneda'] ?? 3),
                'subtotal_linea' => floatval($item['subtotal_linea']),
                'peso_vehiculo' => $item['peso_vehiculo'] ?? null,
                'peso_bruto' => $item['peso_bruto'] ?? null,
                'peso_neto' => $item['peso_neto'] ?? null,
            ];
        }
    }

    try {
        $resultado = $models['compras']->actualizarCompra($idcompra, $datosCompra, $detallesParaGuardar);
        if ($resultado['status']) {
            registrarEnBitacora('compras', 'ACTUALIZAR', null, "ID Compra: $idcompra");
            echo json_encode(['status' => true, 'message' => 'Compra actualizada correctamente.'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['status' => false, 'message' => $resultado['message'] ?? 'Error al actualizar la compra.'], JSON_UNESCAPED_UNICODE);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => 'Error de servidor: ' . $e->getMessage()]);
    }
    exit();
}

function compras_deleteCompra()
{
    header('Content-Type: application/json');
    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'eliminar')) {
        echo json_encode(['status' => false, 'message' => 'No tiene permisos para eliminar.'], JSON_UNESCAPED_UNICODE);
        exit();
    }
    $data = json_decode(file_get_contents('php://input'), true);
    $idcompra = intval($data['idcompra'] ?? 0);

    $models = compras_getModels();
    $res = $models['compras']->deleteCompraById($idcompra);
    if ($res['status']) {
        registrarEnBitacora('compras', 'ELIMINAR', null, "ID Compra: $idcompra");
    }
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit();
}

function compras_reactivarCompra()
{
    header('Content-Type: application/json');
    if (($_SESSION['rol_id'] ?? 0) != 1) {
        echo json_encode(['status' => false, 'message' => 'Solo superusuarios']);
        exit();
    }
    $data = json_decode(file_get_contents('php://input'), true);
    $idcompra = intval($data['idcompra'] ?? 0);

    $models = compras_getModels();
    $res = $models['compras']->reactivarCompra($idcompra);
    if ($res['status']) {
        registrarEnBitacora('compras', 'REACTIVAR', null, "ID Compra: $idcompra");
    }
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit();
}

function compras_cambiarEstadoCompra()
{
    header('Content-Type: application/json');
    $idusuario = obtenerUsuarioSesion();
    $data = json_decode(file_get_contents('php://input'), true);
    $idcompra = intval($data['idcompra'] ?? 0);
    $nuevoEstado = $data['nuevo_estado'] ?? '';

    $models = compras_getModels();
    $estadoAnterior = $models['compras']->obtenerEstadoCompra($idcompra);
    $res = $models['compras']->cambiarEstadoCompra($idcompra, $nuevoEstado, $idusuario);

    if ($res['status']) {
        registrarEnBitacora('compras', 'CAMBIO_ESTADO', null, "De $estadoAnterior a $nuevoEstado");
    }
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit();
}

function compras_validarProducto()
{
    header('Content-Type: application/json');
    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'crear')) {
        echo json_encode(['status' => false, 'message' => 'No tiene permisos para realizar esta acción.']);
        exit();
    }

    $idproducto = isset($_POST['idproducto']) ? intval($_POST['idproducto']) : 0;
    if ($idproducto <= 0) {
        echo json_encode(['status' => false, 'message' => 'ID de producto no válido.']);
        exit();
    }

    $models = compras_getModels();
    try {
        $producto = $models['compras']->getProductoById($idproducto);
        if ($producto) {
            echo json_encode([
                'status' => true,
                'producto' => [
                    'idproducto' => $producto['idproducto'],
                    'nombre' => $producto['nombre'],
                    'idcategoria' => $producto['idcategoria'],
                    'precio' => $producto['precio']
                ]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['status' => false, 'message' => 'Producto no encontrado.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

function compras_guardarPesoRomana()
{
    header('Content-Type: application/json');
    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'crear')) {
        echo json_encode(['status' => false, 'message' => 'No tiene permisos.'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data)
        $data = $_POST;

    $peso = floatval($data['peso'] ?? 0);
    $fecha = $data['fecha'] ?? date('Y-m-d H:i:s');

    if ($peso <= 0) {
        echo json_encode(['status' => false, 'message' => 'Peso no válido.']);
        exit();
    }

    $models = compras_getModels();
    try {
        $resultado = $models['compras']->guardarPesoRomana($peso, $fecha);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

function compras_factura($idcompra)
{
    $id = is_array($idcompra) ? ($idcompra[0] ?? 0) : $idcompra;
    $id = intval($id);

    compras_verificarAcceso('exportar');

    if ($id <= 0) {
        header('Location: ' . base_url() . '/compras?error=sin_id');
        exit();
    }

    try {
        $models = compras_getModels();
        $compraData = $models['compras']->getCompraById($id);

        if (!$compraData) {
            header('Location: ' . base_url() . '/compras?error=no_encontrada');
            exit();
        }

        $detalles = $models['compras']->getDetalleCompraById($id);
        $tasasBcv = $models['compras']->getTasaBcvDelDia();

        $data = [];
        $data['page_tag'] = "Nota de Recepción";
        $data['page_title'] = "Nota de Recepción - " . $compraData['nro_compra'];
        $data['arrCompra'] = [
            'solicitud' => $compraData,
            'detalles' => $detalles
        ];
        $data['tasaDelDia'] = $tasasBcv;

        registrarEnBitacora('compras', 'VER_FACTURA', null, "Compra #{$compraData['nro_compra']}");

        renderView("compras", "factura_compra", $data);
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        header('Location: ' . base_url() . '/compras?error=error_interno');
        exit();
    }
}