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
        $resultadoInsercion = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);
        $this->assertTrue($resultadoInsercion['status'], "La inserción inicial de la compra falló.");
        $idCompra = $resultadoInsercion['id'];

        $datosEditados = $datosCompra;
        $datosEditados['observaciones_compra'] = 'Compra editada correctamente.';
        $detallesEditados = $detallesCompra;
        $detallesEditados[0]['cantidad'] = 10;
        $detallesEditados[0]['subtotal_linea'] = 100;
        $datosEditados['subtotal_general_compra'] = 100;
        $datosEditados['total_general_compra'] = 100;

        $resultado = $this->comprasModel->actualizarCompra($idCompra, $datosEditados, $detallesEditados);
        
        $this->assertIsArray($resultado, "La función actualizarCompra debe devolver un array.");
        $this->assertTrue($resultado['status'], "La edición de la compra debería ser exitosa: " . ($resultado['message'] ?? 'Error desconocido'));
        echo $resultado['message'];

        $compraEditada = $this->comprasModel->getCompraById($idCompra);
        $this->assertEquals('Compra editada correctamente.', $compraEditada['observaciones_compra'], "La observación no fue actualizada correctamente.");
        $this->assertEquals(100, $compraEditada['total_general'], "El total de la compra editada no coincide.");
    }

    public function testEditarCompraConProveedorInexistente()
    {
        // 1. Crear una compra válida inicial
        $producto = $this->productosModel->selectProductoById(1);
        $resultadoProveedores = $this->proveedoresModel->selectAllProveedores(1);
        $proveedor = $resultadoProveedores['data'][0];

        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => $proveedor['idproveedor'],
            'idmoneda_general' => 3,
            'total_general_compra' => 50,
            'observaciones_compra' => 'Compra original para prueba de edición.',
        ];
        $detallesCompra = [[
            'idproducto' => $producto['idproducto'],
            'descripcion_temporal_producto' => $producto['nombre'],
            'cantidad' => 5, 'precio_unitario_compra' => 10,
            'idmoneda_detalle' => 3, 'subtotal_linea' => 50,
            'peso_vehiculo' => null, 'peso_bruto' => null, 'peso_neto' => null,
        ]];
        $resultadoInsercion = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);
        $this->assertTrue($resultadoInsercion['status'], "La inserción inicial de la compra falló.");
        $idCompra = $resultadoInsercion['id'];

        // 2. Intentar editar con un proveedor inexistente
        $datosEditados = $datosCompra;
        $datosEditados['idproveedor'] = 99999; // ID de proveedor inexistente

        $resultado = $this->comprasModel->actualizarCompra($idCompra, $datosEditados, $detallesCompra);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status'], "El sistema no debería permitir editar una compra con un proveedor inexistente.");
        $this->assertStringContainsString("El proveedor con ID 99999 no existe.", $resultado['message']);
    }

    public function testEditarCompraConProductoInexistente()
    {
        // 1. Crear una compra válida inicial
        $producto = $this->productosModel->selectProductoById(1);
        $resultadoProveedores = $this->proveedoresModel->selectAllProveedores(1);
        $proveedor = $resultadoProveedores['data'][0];
        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => $proveedor['idproveedor'],
            'idmoneda_general' => 3, 'total_general_compra' => 50,
            'observaciones_compra' => 'Compra original.',
        ];
        $detallesCompra = [[
            'idproducto' => $producto['idproducto'], 'cantidad' => 5, 'precio_unitario_compra' => 10,
            'descripcion_temporal_producto' => $producto['nombre'], 'idmoneda_detalle' => 3, 'subtotal_linea' => 50,
            'peso_vehiculo' => null, 'peso_bruto' => null, 'peso_neto' => null,
        ]];
        $resultadoInsercion = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);
        $this->assertTrue($resultadoInsercion['status'], "La inserción inicial de la compra falló.");
        $idCompra = $resultadoInsercion['id'];

        // 2. Intentar editar con un producto inexistente
        $detallesEditados = $detallesCompra;
        $detallesEditados[0]['idproducto'] = 99999; // ID de producto inexistente

        $resultado = $this->comprasModel->actualizarCompra($idCompra, $datosCompra, $detallesEditados);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status'], "El sistema no debería permitir editar una compra con un producto inexistente.");
        $this->assertStringContainsString("El producto con ID 99999 no existe.", $resultado['message']);
    }

    public function testEditarCompraConValoresNegativos()
    {
        // 1. Crear una compra válida inicial
        $producto = $this->productosModel->selectProductoById(1);
        $resultadoProveedores = $this->proveedoresModel->selectAllProveedores(1);
        $proveedor = $resultadoProveedores['data'][0];
        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => $proveedor['idproveedor'],
            'idmoneda_general' => 3, 'total_general_compra' => 50,
            'observaciones_compra' => 'Compra original.',
        ];
        $detallesCompra = [[
            'idproducto' => $producto['idproducto'], 'cantidad' => 5, 'precio_unitario_compra' => 10,
            'descripcion_temporal_producto' => $producto['nombre'], 'idmoneda_detalle' => 3, 'subtotal_linea' => 50,
            'peso_vehiculo' => null, 'peso_bruto' => null, 'peso_neto' => null,
        ]];
        $resultadoInsercion = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);
        $this->assertTrue($resultadoInsercion['status'], "La inserción inicial de la compra falló.");
        $idCompra = $resultadoInsercion['id'];

        // 2. Intentar editar con valores negativos
        $detallesEditados = $detallesCompra;
        $detallesEditados[0]['cantidad'] = -5; // Cantidad negativa

        $resultado = $this->comprasModel->actualizarCompra($idCompra, $datosCompra, $detallesEditados);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status'], "El sistema no debería permitir editar una compra con valores negativos.");
        $this->assertStringContainsString("La cantidad o el precio unitario no pueden ser negativos o cero.", $resultado['message']);
    }
}
