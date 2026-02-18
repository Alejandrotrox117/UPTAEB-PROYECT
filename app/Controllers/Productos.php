<?php
namespace App\Controllers;

use App\Core\Controllers;
use App\Models\ProductosModel;
use App\Models\BitacoraModel;
use App\Models\NotificacionesModel;
use App\Helpers\BitacoraHelper;
use App\Helpers\PermisosModuloVerificar;
use App\Helpers\Validation\ExpresionesRegulares;
use Exception;

class Productos extends Controllers
{
    private $bitacoraModel;
    private $BitacoraHelper;
    private $notificacionesModel;

    public function __construct()
    {
        parent::__construct();
        

        $this->bitacoraModel = new BitacoraModel();
        $this->BitacoraHelper = new BitacoraHelper();
        $this->notificacionesModel = new NotificacionesModel();

        if (!$this->BitacoraHelper->obtenerUsuarioSesion()) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        
        if (!PermisosModuloVerificar::verificarAccesoModulo('productos')) {
            $this->views->getView($this, "permisos");
            exit();
        }
    }

    public function index()
    {
        if (!PermisosModuloVerificar::verificarPermisoModuloAccion('productos', 'ver')) {
            $this->views->getView($this, "permisos");
            exit();
        }

        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();
        BitacoraHelper::registrarAccesoModulo('productos', $idusuario, $this->bitacoraModel);

        $data['page_tag'] = "Productos";
        $data['page_title'] = "Administración de Productos";
        $data['page_name'] = "productos";
        $data['page_content'] = "Gestión integral de productos del sistema";
        $data['page_functions_js'] = "functions_productos.js";
        $this->views->getView($this, "productos", $data);
    }

    public function createProducto()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode(['status' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

        try {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('productos', 'crear')) {
                BitacoraHelper::registrarError('productos', 'Intento de crear producto sin permisos', $idusuario, $this->bitacoraModel);
                echo json_encode(['status' => false, 'message' => 'No tienes permisos para crear productos'], JSON_UNESCAPED_UNICODE);
                die();
            }

            $postdata = file_get_contents('php://input');
            $request = json_decode($postdata, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Datos JSON inválidos');
            }

            $datosLimpios = $this->validarDatosProducto($request);

            $resultado = $this->model->insertProducto($datosLimpios);

            if ($resultado['status'] === true) {
                $productoId = $resultado['producto_id'] ?? null;
                $detalle = "Producto creado con ID: " . ($productoId ?? 'desconocido');
                BitacoraHelper::registrarAccion('productos', 'CREAR_PRODUCTO', $idusuario, $this->bitacoraModel, $detalle, $productoId);
                $this->notificacionesModel->generarNotificacionesProductos();
            }

            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Error en createProducto: " . $e->getMessage());
            BitacoraHelper::registrarError('productos', $e->getMessage(), $idusuario, $this->bitacoraModel);
            echo json_encode(['status' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function updateProducto()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode(['status' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

        try {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('productos', 'editar')) {
                BitacoraHelper::registrarError('productos', 'Intento de editar producto sin permisos', $idusuario, $this->bitacoraModel);
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

            $datosLimpios = $this->validarDatosProducto($request);

            $resultado = $this->model->updateProducto($idProducto, $datosLimpios);

            if ($resultado['status'] === true) {
                $detalle = "Producto actualizado con ID: " . $idProducto;
                BitacoraHelper::registrarAccion('productos', 'ACTUALIZAR_PRODUCTO', $idusuario, $this->bitacoraModel, $detalle, $idProducto);
                $this->notificacionesModel->generarNotificacionesProductos();
            }

            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Error en updateProducto: " . $e->getMessage());
            BitacoraHelper::registrarError('productos', $e->getMessage(), $idusuario, $this->bitacoraModel);
            echo json_encode(['status' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function deleteProducto()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode(['status' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

        try {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('productos', 'eliminar')) {
                BitacoraHelper::registrarError('productos', 'Intento de eliminar producto sin permisos', $idusuario, $this->bitacoraModel);
                echo json_encode(['status' => false, 'message' => 'No tienes permisos para eliminar productos'], JSON_UNESCAPED_UNICODE);
                die();
            }

            $postdata = file_get_contents('php://input');
            $request = json_decode($postdata, true);

            if (json_last_error() !== JSON_ERROR_NONE || empty($request['idproducto'])) {
                throw new Exception('Datos inválidos');
            }

            $idProducto = intval($request['idproducto']);
            $resultado = $this->model->deleteProductoById($idProducto, $idusuario);

            if ($resultado['status'] === true) {
                $detalle = "Producto desactivado con ID: " . $idProducto;
                BitacoraHelper::registrarAccion('productos', 'ELIMINAR_PRODUCTO', $idusuario, $this->bitacoraModel, $detalle, $idProducto);
                $this->notificacionesModel->generarNotificacionesProductos();
            }

            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Error en deleteProducto: " . $e->getMessage());
            BitacoraHelper::registrarError('productos', $e->getMessage(), $idusuario, $this->bitacoraModel);
            echo json_encode(['status' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function activarProducto()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode(['status' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $idusuario = $this->BitacoraHelper->obtenerUsuarioSesion();

        try {
            if (!PermisosModuloVerificar::verificarPermisoModuloAccion('productos', 'editar')) {
                BitacoraHelper::registrarError('productos', 'Intento de activar producto sin permisos', $idusuario, $this->bitacoraModel);
                echo json_encode(['status' => false, 'message' => 'No tienes permisos para activar productos'], JSON_UNESCAPED_UNICODE);
                die();
            }

            $postdata = file_get_contents('php://input');
            $request = json_decode($postdata, true);

            if (json_last_error() !== JSON_ERROR_NONE || empty($request['idproducto'])) {
                throw new Exception('Datos inválidos');
            }

            $idProducto = intval($request['idproducto']);
            $resultado = $this->model->activarProductoById($idProducto, $idusuario);

            if ($resultado['status'] === true) {
                $detalle = "Producto activado con ID: " . $idProducto;
                BitacoraHelper::registrarAccion('productos', 'ACTIVAR_PRODUCTO', $idusuario, $this->bitacoraModel, $detalle, $idProducto);
                $this->notificacionesModel->generarNotificacionesProductos();
            }

            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Error en activarProducto: " . $e->getMessage());
            BitacoraHelper::registrarError('productos', $e->getMessage(), $idusuario, $this->bitacoraModel);
            echo json_encode(['status' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    

    public function getProductosData()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                if (!PermisosModuloVerificar::verificarPermisoModuloAccion('productos', 'ver')) {
                    echo json_encode(['status' => false, 'message' => 'No tienes permisos para ver los productos'], JSON_UNESCAPED_UNICODE);
                    die();
                }
                $arrResponse = $this->model->selectAllProductos();
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getProductosData: " . $e->getMessage());
                echo json_encode(['status' => false, 'message' => 'Error interno del servidor'], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getProductoById($idproducto)
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
                $arrData = $this->model->selectProductoById(intval($idproducto));
                echo json_encode(['status' => true, 'data' => $arrData], JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getProductoById: " . $e->getMessage());
                echo json_encode(['status' => false, 'message' => 'Error al obtener el producto'], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    public function getCategorias()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrResponse = $this->model->selectCategoriasActivas();
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error en getCategorias: " . $e->getMessage());
                echo json_encode(['status' => false, 'message' => 'Error al obtener categorías'], JSON_UNESCAPED_UNICODE);
            }
            die();
        }
    }

    

    private function validarDatosProducto($request)
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

        if (empty($datosLimpios['nombre'])) $errores[] = 'El nombre es obligatorio.';
        if (empty($datosLimpios['unidad_medida'])) $errores[] = 'La unidad de medida es obligatoria.';
        if ($datosLimpios['precio'] <= 0) $errores[] = 'El precio debe ser mayor a 0.';
        if (empty($datosLimpios['idcategoria'])) $errores[] = 'La categoría es obligatoria.';

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
}
?>