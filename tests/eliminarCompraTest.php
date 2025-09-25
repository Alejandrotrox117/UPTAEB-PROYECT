<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../app/models/ComprasModel.php';
require_once __DIR__ . '/../app/models/productosModel.php';
require_once __DIR__ . '/../app/models/proveedoresModel.php';

class eliminarCompraTest extends TestCase
{
    private $comprasModel;
    private $productosModel;
    private $proveedoresModel;

    public function setUp(): void
    {
        $this->comprasModel = new ComprasModel();
        $this->productosModel = new ProductosModel();
        $this->proveedoresModel = new ProveedoresModel();
    }

    public function testEliminarCompraExitosa()
    {
        // Crear compra inicial
        $producto = $this->productosModel->selectProductoById(1);
        $resultadoProveedores = $this->proveedoresModel->selectAllProveedores(1);
        $proveedor = $resultadoProveedores['data'][0];

        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => $proveedor['idproveedor'],
            'idmoneda_general' => 3,
            'subtotal_general_compra' => 50,
            'descuento_porcentaje_compra' => 0,
            'monto_descuento_compra' => 0,
            'total_general_compra' => 50,
            'observaciones_compra' => 'Compra para eliminar.',
        ];
        $detallesCompra = [
            [
                'idproducto' => $producto['idproducto'],
                'descripcion_temporal_producto' => $producto['nombre'],
                'cantidad' => 5,
                'descuento' => 0,
                'precio_unitario_compra' => 10,
                'idmoneda_detalle' => 3,
                'subtotal_linea' => 50,
                'subtotal_original_linea' => 50,
                'monto_descuento_linea' => 0,
                'peso_vehiculo' => null,
                'peso_bruto' => null,
                'peso_neto' => null,
            ]
        ];
        $idCompra = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);

        // Eliminar compra
        $resultado = $this->comprasModel->deleteCompraById($idCompra);
        $this->assertTrue($resultado, "La eliminación de la compra debería ser exitosa.");

        $compraEliminada = $this->comprasModel->getCompraById($idCompra);
        $this->assertEquals('inactivo', $compraEliminada['estatus_compra'], "La compra no fue marcada como inactivo correctamente.");
    }
}
