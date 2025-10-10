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

        $resultado = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);

        $this->assertIsArray($resultado, "La función insertarCompra debe devolver un array.");
        $this->assertTrue($resultado['status'], "La inserción de la compra falló: " . ($resultado['message'] ?? 'Error desconocido'));
        $this->assertArrayHasKey('id', $resultado, "El resultado no contiene el ID de la compra insertada.");
        $this->assertGreaterThan(0, $resultado['id'], "La inserción de la compra devolvió un ID no válido.");

        $idCompraInsertada = $resultado['id'];
        $compraCreada = $this->comprasModel->getCompraById($idCompraInsertada);
        $this->assertNotEmpty($compraCreada, "No se pudo encontrar la compra recién creada en la base de datos.");
        $this->assertEquals($datosCompra['total_general_compra'], $compraCreada['total_general'], "El total de la compra creada no coincide.");

        echo $resultado['message'];
    }

    public function testCrearCompraConProveedorInexistente()
    {
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto, "El producto de prueba con ID 1 no pudo ser encontrado.");

        $precioUnitario = 10.5;
        $cantidad = 5;
        $subtotal = $precioUnitario * $cantidad;

        // Datos generales de la compra con un proveedor que no existe
        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => 99999, // ID de proveedor inexistente
            'idmoneda_general' => 3,
            'subtotal_general_compra' => $subtotal,
            'descuento_porcentaje_compra' => 0,
            'monto_descuento_compra' => 0,
            'total_general_compra' => $subtotal,
            'observaciones_compra' => 'Prueba unitaria con proveedor inexistente.',
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

        $resultado = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);
        
        $this->assertIsArray($resultado, "La función debe devolver un array para indicar el fallo.");
        $this->assertFalse($resultado['status'], "El sistema no debería permitir crear una compra con un proveedor inexistente.");
        $this->assertStringContainsString("El proveedor con ID 99999 no existe.", $resultado['message']);
    }

    public function testCrearCompraConProductoInexistente()
    {
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
            'idmoneda_general' => 3,
            'subtotal_general_compra' => $subtotal,
            'descuento_porcentaje_compra' => 0,
            'monto_descuento_compra' => 0,
            'total_general_compra' => $subtotal,
            'observaciones_compra' => 'Prueba unitaria con producto inexistente.',
        ];

        $detallesCompra = [
            [
                'idproducto' => 99999, // ID de producto inexistente
                'descripcion_temporal_producto' => 'Producto Inexistente',
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

        $resultado = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);

        $this->assertIsArray($resultado, "La función debe devolver un array para indicar el fallo.");
        $this->assertFalse($resultado['status'], "El sistema no debería permitir crear una compra con un producto inexistente.");
        $this->assertStringContainsString("El producto con ID 99999 no existe.", $resultado['message']);
    }

    public function testCrearCompraConValoresNegativos()
    {
        $resultadoProveedores = $this->proveedoresModel->selectAllProveedores(1);
        $this->assertTrue($resultadoProveedores['status'], "La consulta de proveedores falló.");
        $proveedores = $resultadoProveedores['data'];
        $this->assertNotEmpty($proveedores, "No se encontraron proveedores en la base de datos para realizar la prueba.");
        $proveedor = $proveedores[0];

        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto, "El producto de prueba con ID 1 no pudo ser encontrado.");

        $precioUnitario = -10.5; // Precio negativo
        $cantidad = 5;
        $subtotal = $precioUnitario * $cantidad;

        // Datos generales de la compra
        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => $proveedor['idproveedor'],
            'idmoneda_general' => 3,
            'subtotal_general_compra' => $subtotal,
            'descuento_porcentaje_compra' => 0,
            'monto_descuento_compra' => 0,
            'total_general_compra' => $subtotal,
            'observaciones_compra' => 'Prueba unitaria con valores negativos.',
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

        $resultado = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);
        
        $this->assertIsArray($resultado, "La función debe devolver un array para indicar el fallo.");
        $this->assertFalse($resultado['status'], "El sistema no debería permitir crear una compra con valores negativos.");
        $this->assertStringContainsString("La cantidad o el precio unitario no pueden ser negativos o cero.", $resultado['message']);
    }
}
