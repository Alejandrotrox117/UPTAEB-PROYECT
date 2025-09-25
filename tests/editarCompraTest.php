<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../app/models/ComprasModel.php';
require_once __DIR__ . '/../app/models/productosModel.php';
require_once __DIR__ . '/../app/models/proveedoresModel.php';

class editarCompraTest extends TestCase
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

    public function testEditarCompraExitosa()
    {
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
            'observaciones_compra' => 'Compra original.',
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
        $datosEditados = $datosCompra;
        $datosEditados['observaciones_compra'] = 'Compra editada correctamente.';
        $detallesEditados = $detallesCompra;
        $detallesEditados[0]['cantidad'] = 10;
        $detallesEditados[0]['subtotal_linea'] = 100;
        $datosEditados['subtotal_general_compra'] = 100;
        $datosEditados['total_general_compra'] = 100;

        $resultado = $this->comprasModel->actualizarCompra($idCompra, $datosEditados, $detallesEditados);
        $this->assertTrue($resultado, "La edición de la compra debería ser exitosa.");

        $compraEditada = $this->comprasModel->getCompraById($idCompra);
        $this->assertEquals('Compra editada correctamente.', $compraEditada['observaciones_compra'], "La observación no fue actualizada correctamente.");
        $this->assertEquals(100, $compraEditada['total_general'], "El total de la compra editada no coincide.");
    }
}
