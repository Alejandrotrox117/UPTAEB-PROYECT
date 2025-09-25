<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../app/models/ComprasModel.php';
require_once __DIR__ . '/../app/models/productosModel.php';
require_once __DIR__ . '/../app/models/proveedoresModel.php';

class crearCompraTest extends TestCase
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

    public function testCrearCompraExitosa()
    {
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto, "El producto de prueba con ID 1 no pudo ser encontrado.");

        $resultadoProveedores = $this->proveedoresModel->selectAllProveedores(1);
        $this->assertTrue($resultadoProveedores['status'], "La consulta de proveedores falló.");
        $proveedores = $resultadoProveedores['data'];
        $this->assertNotEmpty($proveedores, "No se encontraron proveedores en la base de datos para realizar la prueba.");
        $proveedor = $proveedores[0]; 

        $precioUnitario = 10.5;
        $cantidad = 5;
        $subtotal = $precioUnitario * $cantidad;

        // Datos generales de la compra
        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => $proveedor['idproveedor'], 
            'idmoneda_general' => 3, // Moneda por defecto
            'subtotal_general_compra' => $subtotal,
            'descuento_porcentaje_compra' => 0,
            'monto_descuento_compra' => 0,
            'total_general_compra' => $subtotal,
            'observaciones_compra' => 'Prueba unitaria de creación de compra.',
        ];

        $detallesCompra = [
            [
                'idproducto' => $producto['idproducto'],
                'descripcion_temporal_producto' => $producto['nombre'],
                'cantidad' => $cantidad,
                'descuento' => 0,
                'precio_unitario_compra' => $precioUnitario,
                'idmoneda_detalle' => 3,
                'subtotal_linea' => $subtotal,
                'subtotal_original_linea' => $subtotal,
                'monto_descuento_linea' => 0,
                'peso_vehiculo' => null,
                'peso_bruto' => null,
                'peso_neto' => null,
            ]
        ];

        $idCompraInsertada = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);

        $this->assertIsNumeric($idCompraInsertada, "El ID de la compra insertada no es un número.");
        $this->assertGreaterThan(0, $idCompraInsertada, "La inserción de la compra falló o devolvió un ID no válido.");

        $compraCreada = $this->comprasModel->getCompraById($idCompraInsertada);
        $this->assertNotEmpty($compraCreada, "No se pudo encontrar la compra recién creada en la base de datos.");
        $this->assertEquals($datosCompra['total_general_compra'], $compraCreada['total_general'], "El total de la compra creada no coincide.");
    }
}
