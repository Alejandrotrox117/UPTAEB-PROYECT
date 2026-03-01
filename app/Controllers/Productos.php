<?php

use App\Models\ProductosModel;
use App\Models\BitacoraModel;
use App\Models\NotificacionesModel;
use App\Models\EmpleadosModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\PermisosModuloVerificar;
use App\Helpers\Validation\ExpresionesRegulares;

// =============================================================================
// FUNCIONES AUXILIARES DEL CONTROLADOR
// =============================================================================

/**
 * Obtiene el modelo de productos
 */
function getProductosModel()
{
    return new ProductosModel();
}

/**
 * Obtiene el modelo de bitácora
 */
function getProductosBitacoraModel()
{
    return new BitacoraModel();
}

/**
 * Obtiene el modelo de notificaciones
 */
function getProductosNotificacionesModel()
{
    return new NotificacionesModel();
}

/**
 * Renderiza una vista de productos
 */
function renderProductosView($view, $data = [])
{
    renderView('productos', $view, $data);
}

/**
 * Valida y limpia los datos de un producto
 */
function validarDatosProducto($request)
{
    $datosLimpios = [
        'nombre' => ExpresionesRegulares::limpiar($request['nombre'] ?? '', 'nombre'),
        'descripcion' => trim($request['descripcion'] ?? ''),
        'unidad_medida' => strtoupper(trim($request['unidad_medida'] ?? '')),
        'precio' => floatval($request['precio'] ?? 0),
        'idcategoria' => intval($request['idcategoria'] ?? 0),
        'moneda' => strtoupper(trim($request['moneda'] ?? 'BS'))
    ];

    $errores = [];

    if (empty($datosLimpios['nombre']))
        $errores[] = 'El nombre es obligatorio.';
    if (empty($datosLimpios['unidad_medida']))
        $errores[] = 'La unidad de medida es obligatoria.';
    if ($datosLimpios['precio'] <= 0)
        $errores[] = 'El precio debe ser mayor a 0.';
    if (empty($datosLimpios['idcategoria']))
        $errores[] = 'La categoría es obligatoria.';

    $resultadosValidacion = ExpresionesRegulares::validarCampos($datosLimpios, ['nombre' => 'nombre']);
    if (!$resultadosValidacion['nombre']['valido']) {
        $errores[] = ExpresionesRegulares::obtenerMensajeError('nombre', 'nombre');
    }

    if (!empty($datosLimpios['descripcion'])) {
        $resultadosValidacionDesc = ExpresionesRegulares::validarCampos($datosLimpios, ['descripcion' => 'textoGeneral']);
        if (!$resultadosValidacionDesc['descripcion']['valido']) {
            $errores[] = ExpresionesRegulares::obtenerMensajeError('descripcion', 'textoGeneral');
        }
    }

    if (!in_array($datosLimpios['unidad_medida'], ['UNIDAD', 'KG', 'GRAMO', 'LITRO', 'ML', 'METRO', 'CM', 'CAJA', 'PAQUETE'])) {
        $errores[] = 'Unidad de medida inválida.';
    }

    if (!in_array($datosLimpios['moneda'], ['BS', 'USD', 'EUR'])) {
        $errores[] = 'Moneda inválida.';
    }

    if (!empty($errores)) {
        throw new Exception(implode(' | ', $errores));
    }

    return $datosLimpios;
}

// =============================================================================
// FUNCIONES PÚBLICAS DEL CONTROLADOR
// =============================================================================

/**
 * Página principal del módulo de productos
 */
function productos_index()
{
    // Verificar autenticación
    if (!obtenerUsuarioSesion()) {
        header('Location: ' . base_url() . '/login');
        die();
    }

    // Verificar acceso al módulo
    if (!PermisosModuloVerificar::verificarAccesoModulo('productos')) {
        renderProductosView("permisos");
        exit();
    }

    if (!PermisosModuloVerificar::verificarPermisoModuloAccion('productos', 'ver')) {
        renderProductosView("permisos");
        exit();
    }

    $idusuario = obtenerUsuarioSesion();
    $bitacoraModel = getProductosBitacoraModel();
    BitacoraHelper::registrarAccesoModulo('productos', $idusuario, $bitacoraModel);

    $data['page_tag'] = "Productos";
    $data['page_title'] = "Administración de Productos";
    $data['page_name'] = "productos";
    $data['page_content'] = "Gestión integral de productos del sistema";
    $data['page_functions_js'] = "functions_productos.js";
    renderProductosView("productos", $data);
}

/**
 * Crear un nuevo producto
 */
function productos_createProducto()
{
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        echo json_encode(['status' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
        die();
    }

    $idusuario = obtenerUsuarioSesion();
    $bitacoraModel = getProductosBitacoraModel();

    try {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('productos', 'crear')) {
            BitacoraHelper::registrarError('productos', 'Intento de crear producto sin permisos', $idusuario, $bitacoraModel);
            echo json_encode(['status' => false, 'message' => 'No tienes permisos para crear productos'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $postdata = file_get_contents('php://input');
        $request = json_decode($postdata, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Datos JSON inválidos');
        }

        $datosLimpios = validarDatosProducto($request);

        $objProductos = getProductosModel();
        $resultado = $objProductos->insertProducto($datosLimpios);

        if ($resultado['status'] === true) {
            $productoId = $resultado['producto_id'] ?? null;
            $detalle = "Producto creado con ID: " . ($productoId ?? 'desconocido');
            BitacoraHelper::registrarAccion('productos', 'CREAR_PRODUCTO', $idusuario, $bitacoraModel, $detalle, $productoId);
            $notificacionesModel = getProductosNotificacionesModel();
            $notificacionesModel->generarNotificacionesProductos();
        }

        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        error_log("Error en createProducto: " . $e->getMessage());
        BitacoraHelper::registrarError('productos', $e->getMessage(), $idusuario, $bitacoraModel);
        echo json_encode(['status' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Actualizar un producto
 */
function productos_updateProducto()
{
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        echo json_encode(['status' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
        die();
    }

    $idusuario = obtenerUsuarioSesion();
    $bitacoraModel = getProductosBitacoraModel();

    try {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('productos', 'editar')) {
            BitacoraHelper::registrarError('productos', 'Intento de editar producto sin permisos', $idusuario, $bitacoraModel);
            echo json_encode(['status' => false, 'message' => 'No tienes permisos para editar productos'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $postdata = file_get_contents('php://input');
        $request = json_decode($postdata, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Datos JSON inválidos');
        }

        $idProducto = intval($request['idproducto'] ?? 0);
        if ($idProducto <= 0) {
            throw new Exception('ID de producto inválido');
        }

        $datosLimpios = validarDatosProducto($request);

        $objProductos = getProductosModel();
        $resultado = $objProductos->updateProducto($idProducto, $datosLimpios);

        if ($resultado['status'] === true) {
            $detalle = "Producto actualizado con ID: " . $idProducto;
            BitacoraHelper::registrarAccion('productos', 'ACTUALIZAR_PRODUCTO', $idusuario, $bitacoraModel, $detalle, $idProducto);
            $notificacionesModel = getProductosNotificacionesModel();
            $notificacionesModel->generarNotificacionesProductos();
        }

        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        error_log("Error en updateProducto: " . $e->getMessage());
        BitacoraHelper::registrarError('productos', $e->getMessage(), $idusuario, $bitacoraModel);
        echo json_encode(['status' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Eliminar (desactivar) un producto
 */
function productos_deleteProducto()
{
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        echo json_encode(['status' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
        die();
    }

    $idusuario = obtenerUsuarioSesion();
    $bitacoraModel = getProductosBitacoraModel();

    try {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('productos', 'eliminar')) {
            BitacoraHelper::registrarError('productos', 'Intento de eliminar producto sin permisos', $idusuario, $bitacoraModel);
            echo json_encode(['status' => false, 'message' => 'No tienes permisos para eliminar productos'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $postdata = file_get_contents('php://input');
        $request = json_decode($postdata, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($request['idproducto'])) {
            throw new Exception('Datos inválidos');
        }

        $idProducto = intval($request['idproducto']);
        $objProductos = getProductosModel();
        $resultado = $objProductos->deleteProductoById($idProducto);

        if ($resultado) {
            $detalle = "Producto desactivado con ID: " . $idProducto;
            BitacoraHelper::registrarAccion('productos', 'ELIMINAR_PRODUCTO', $idusuario, $bitacoraModel, $detalle, $idProducto);
            $notificacionesModel = getProductosNotificacionesModel();
            $notificacionesModel->generarNotificacionesProductos();
            echo json_encode(['status' => true, 'message' => 'Producto desactivado correctamente'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['status' => false, 'message' => 'No se pudo desactivar el producto'], JSON_UNESCAPED_UNICODE);
        }

    } catch (Exception $e) {
        error_log("Error en deleteProducto: " . $e->getMessage());
        BitacoraHelper::registrarError('productos', $e->getMessage(), $idusuario, $bitacoraModel);
        echo json_encode(['status' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Activar un producto
 */
function productos_activarProducto()
{
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        echo json_encode(['status' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
        die();
    }

    $idusuario = obtenerUsuarioSesion();
    $bitacoraModel = getProductosBitacoraModel();

    try {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('productos', 'editar')) {
            BitacoraHelper::registrarError('productos', 'Intento de activar producto sin permisos', $idusuario, $bitacoraModel);
            echo json_encode(['status' => false, 'message' => 'No tienes permisos para activar productos'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $postdata = file_get_contents('php://input');
        $request = json_decode($postdata, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($request['idproducto'])) {
            throw new Exception('Datos inválidos');
        }

        $idProducto = intval($request['idproducto']);
        $objProductos = getProductosModel();
        $resultado = $objProductos->activarProductoById($idProducto);

        if ($resultado) {
            $detalle = "Producto activado con ID: " . $idProducto;
            BitacoraHelper::registrarAccion('productos', 'ACTIVAR_PRODUCTO', $idusuario, $bitacoraModel, $detalle, $idProducto);
            $notificacionesModel = getProductosNotificacionesModel();
            $notificacionesModel->generarNotificacionesProductos();
            echo json_encode(['status' => true, 'message' => 'Producto reactivado correctamente'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['status' => false, 'message' => 'No se pudo reactivar el producto'], JSON_UNESCAPED_UNICODE);
        }

    } catch (Exception $e) {
        error_log("Error en activarProducto: " . $e->getMessage());
        BitacoraHelper::registrarError('productos', $e->getMessage(), $idusuario, $bitacoraModel);
        echo json_encode(['status' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    die();
}

/**
 * Obtener listado de productos
 */
function productos_getProductosData()
{
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        try {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('productos', 'ver')) {
                echo json_encode(['status' => false, 'message' => 'No tienes permisos para ver los productos'], JSON_UNESCAPED_UNICODE);
                die();
            }
            $objProductos = getProductosModel();
            $arrResponse = $objProductos->selectAllProductos();
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error en getProductosData: " . $e->getMessage());
            echo json_encode(['status' => false, 'message' => 'Error interno del servidor'], JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Obtener un producto por ID
 */
function productos_getProductoById($idproducto)
{
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        try {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('productos', 'ver')) {
                echo json_encode(['status' => false, 'message' => 'No tienes permisos para ver este producto'], JSON_UNESCAPED_UNICODE);
                die();
            }
            if (empty($idproducto) || !is_numeric($idproducto)) {
                throw new Exception('ID de producto inválido');
            }
            $objProductos = getProductosModel();
            $arrData = $objProductos->selectProductoById(intval($idproducto));
            echo json_encode(['status' => true, 'data' => $arrData], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error en getProductoById: " . $e->getMessage());
            echo json_encode(['status' => false, 'message' => 'Error al obtener el producto'], JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Obtener categorías activas
 */
function productos_getCategorias()
{
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        try {
            $objProductos = getProductosModel();
            $arrResponse = $objProductos->selectCategoriasActivas();
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Error en getCategorias: " . $e->getMessage());
            echo json_encode(['status' => false, 'message' => 'Error al obtener categorías'], JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}

/**
 * Verificar si el usuario actual es super usuario
 */
function productos_verificarSuperUsuario()
{
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $idusuario = obtenerUsuarioSesion();

            if (!$idusuario) {
                echo json_encode([
                    'status' => false,
                    'message' => 'Usuario no autenticado',
                    'es_super_usuario' => false,
                    'usuario_id' => 0
                ]);
                die();
            }

            $empleadosModel = new EmpleadosModel();
            $esSuperAdmin = $empleadosModel->verificarEsSuperUsuario($idusuario);

            echo json_encode([
                'status' => true,
                'es_super_usuario' => $esSuperAdmin,
                'usuario_id' => $idusuario,
                'message' => 'Verificación completada'
            ]);
        } catch (Exception $e) {
            error_log("Error en verificarSuperUsuario (Productos): " . $e->getMessage());
            echo json_encode([
                'status' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage(),
                'es_super_usuario' => false,
                'usuario_id' => 0
            ]);
        }
        die();
    }
}

?>