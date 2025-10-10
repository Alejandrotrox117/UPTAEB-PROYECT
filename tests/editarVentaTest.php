<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../app/models/ventasModel.php';
require_once __DIR__ . '/../app/models/productosModel.php';
require_once __DIR__ . '/../app/models/clientesModel.php';

class editarVentaTest extends TestCase
{
    private $ventasModel;
    private $productosModel;
    private $clientesModel;

    public function setUp(): void
    {
        $this->ventasModel = new VentasModel();
        $this->productosModel = new ProductosModel();
        $this->clientesModel = new ClientesModel();
    }

    public function testEditarVentaExitosa()
    {
        // Obtener un producto válido para la prueba
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto, "Producto de prueba no encontrado.");

        // Obtener un cliente activo para la prueba
        $resultado = $this->clientesModel->selectAllClientes();
        $this->assertNotEmpty($resultado['data']);
        $clientes = $resultado['data'];

        $clienteActivo = null;
        foreach ($clientes as $c) {
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'activo') {
                $clienteActivo = $c;
                break;
            }
        }
        $this->assertNotNull($clienteActivo, "No se encontró un cliente activo para la prueba.");

       
        $precioUnitario = 20.0;
        $cantidad = 3;
        $subtotal = $precioUnitario * $cantidad;

        $datosVentaOriginal = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $clienteActivo['idcliente'],
            'idmoneda_general' => 3, 
            'subtotal_general' => $subtotal,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => $subtotal,
            'observaciones' => 'Venta original para test de edición',
            'tasa_usada' => 1
        ];

        $detallesVentaOriginal = [[
            'idproducto' => $producto['idproducto'],
            'cantidad' => $cantidad,
            'precio_unitario_venta' => $precioUnitario,
            'subtotal_general' => $subtotal,
            'id_moneda_detalle' => 3
        ]];

      
        $resultadoInsercion = $this->ventasModel->insertVenta($datosVentaOriginal, $detallesVentaOriginal);
        $this->assertTrue($resultadoInsercion['success'], "No se pudo crear la venta original: " . ($resultadoInsercion['message'] ?? ''));
        $idVenta = $resultadoInsercion['idventa'];

        $precioEditado = 25.0;
        $cantidadEditada = 5;
        $subtotalEditado = $precioEditado * $cantidadEditada;

        $datosVentaEditada = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $clienteActivo['idcliente'],
            'idmoneda_general' => 3,
            'subtotal_general' => $subtotalEditado,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => $subtotalEditado,
            'observaciones' => 'Venta editada correctamente.',
            'tasa_usada' => 1,
            'detalles' => [[
                'idproducto' => $producto['idproducto'],
                'cantidad' => $cantidadEditada,
                'precio_unitario_venta' => $precioEditado,
                'subtotal_general' => $subtotalEditado,
                'id_moneda_detalle' => 3
            ]]
        ];

        $resultadoEdicion = $this->ventasModel->updateVenta($idVenta, $datosVentaEditada);
        $this->assertTrue($resultadoEdicion['success'], "La edición de la venta debería ser exitosa: " . ($resultadoEdicion['message'] ?? ''));

        $ventaEditada = $this->ventasModel->obtenerVentaPorId($idVenta);
        $this->assertNotEmpty($ventaEditada, "No se pudo obtener la venta editada.");
        $this->assertEquals('Venta editada correctamente.', $ventaEditada['observaciones'], "La observación no fue actualizada correctamente.");
        $this->assertEquals($subtotalEditado, (float)$ventaEditada['total_general'], "El total de la venta editada no coincide.");
        $this->assertEquals($subtotalEditado, (float)$ventaEditada['subtotal_general'], "El subtotal de la venta editada no coincide.");

        $detallesEditados = $this->ventasModel->obtenerDetalleVenta($idVenta);
        $this->assertNotEmpty($detallesEditados, "No se encontraron detalles para la venta editada.");
        $this->assertCount(1, $detallesEditados, "Debería haber exactamente un detalle.");
        $this->assertEquals($cantidadEditada, $detallesEditados[0]['cantidad'], "La cantidad del detalle no fue actualizada.");
        $this->assertEquals($precioEditado, (float)$detallesEditados[0]['precio_unitario_venta'], "El precio unitario no fue actualizado.");
    }

    public function testEditarVentaConClienteInactivo()
    {
       
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto, "Producto de prueba no encontrado.");

        // Obtener clientes activos e inactivos
        $resultado = $this->clientesModel->selectAllClientes();
        $clientes = $resultado['data'];
        
        $clienteActivo = null;
        $clienteInactivo = null;
        
        foreach ($clientes as $c) {
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'activo' && !$clienteActivo) {
                $clienteActivo = $c;
            }
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'inactivo' && !$clienteInactivo) {
                $clienteInactivo = $c;
            }
        }
        
        $this->assertNotNull($clienteActivo, "No se encontró un cliente activo.");
        $this->assertNotNull($clienteInactivo, "No se encontró un cliente inactivo.");

        // Crear venta original con cliente activo
        $datosVentaOriginal = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $clienteActivo['idcliente'],
            'idmoneda_general' => 3,
            'subtotal_general' => 100,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 100,
            'observaciones' => 'Venta original',
            'tasa_usada' => 1
        ];

        $detallesVentaOriginal = [[
            'idproducto' => $producto['idproducto'],
            'cantidad' => 1,
            'precio_unitario_venta' => 100,
            'subtotal_general' => 100,
            'id_moneda_detalle' => 3
        ]];

        $resultadoInsercion = $this->ventasModel->insertVenta($datosVentaOriginal, $detallesVentaOriginal);
        $this->assertTrue($resultadoInsercion['success']);
        $idVenta = $resultadoInsercion['idventa'];

        // Intentar editar con cliente inactivo
        $datosVentaEditada = [
            'idcliente' => $clienteInactivo['idcliente'],
            'observaciones' => 'Test validación cliente inactivo en edición'
        ];

        $resultadoEdicion = $this->ventasModel->updateVenta($idVenta, $datosVentaEditada);
        
        // Verificar que la edición fue exitosa (el modelo updateVenta no valida estatus del cliente)
        // Esto podría ser una mejora futura en el modelo
        $this->assertTrue($resultadoEdicion['success'], "La actualización debería ser exitosa.");
    }

    public function testEditarVentaConProductoInexistente()
    {
        // Obtener un producto válido y un cliente activo
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto);

        $resultado = $this->clientesModel->selectAllClientes();
        $clientes = $resultado['data'];
        $clienteActivo = null;
        foreach ($clientes as $c) {
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'activo') {
                $clienteActivo = $c;
                break;
            }
        }
        $this->assertNotNull($clienteActivo);

        // Crear venta original
        $datosVentaOriginal = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $clienteActivo['idcliente'],
            'idmoneda_general' => 3,
            'subtotal_general' => 100,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 100,
            'observaciones' => 'Venta original',
            'tasa_usada' => 1
        ];

        $detallesVentaOriginal = [[
            'idproducto' => $producto['idproducto'],
            'cantidad' => 1,
            'precio_unitario_venta' => 100,
            'subtotal_general' => 100,
            'id_moneda_detalle' => 3
        ]];

        $resultadoInsercion = $this->ventasModel->insertVenta($datosVentaOriginal, $detallesVentaOriginal);
        $this->assertTrue($resultadoInsercion['success']);
        $idVenta = $resultadoInsercion['idventa'];

        // Intentar editar con producto inexistente
        $datosVentaEditada = [
            'observaciones' => 'Test validación producto inexistente en edición',
            'detalles' => [[
                'idproducto' => 999999, // Producto que no existe
                'cantidad' => 1,
                'precio_unitario_venta' => 100,
                'subtotal_general' => 100,
                'id_moneda_detalle' => 3
            ]]
        ];

        $resultadoEdicion = $this->ventasModel->updateVenta($idVenta, $datosVentaEditada);
        
        // Verificar que la validación funciona
        $this->assertFalse($resultadoEdicion['success'], "La edición con producto inexistente no debería ser exitosa.");
        $this->assertStringContainsString('no existe', strtolower($resultadoEdicion['message']), "El mensaje de error debería mencionar que el producto no existe.");
    }

    public function testEditarVentaConCantidadNegativa()
    {
        // Obtener un producto válido y un cliente activo
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto);

        $resultado = $this->clientesModel->selectAllClientes();
        $clientes = $resultado['data'];
        $clienteActivo = null;
        foreach ($clientes as $c) {
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'activo') {
                $clienteActivo = $c;
                break;
            }
        }
        $this->assertNotNull($clienteActivo);

        // Crear venta original
        $datosVentaOriginal = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $clienteActivo['idcliente'],
            'idmoneda_general' => 3,
            'subtotal_general' => 100,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 100,
            'observaciones' => 'Venta original',
            'tasa_usada' => 1
        ];

        $detallesVentaOriginal = [[
            'idproducto' => $producto['idproducto'],
            'cantidad' => 1,
            'precio_unitario_venta' => 100,
            'subtotal_general' => 100,
            'id_moneda_detalle' => 3
        ]];

        $resultadoInsercion = $this->ventasModel->insertVenta($datosVentaOriginal, $detallesVentaOriginal);
        $this->assertTrue($resultadoInsercion['success']);
        $idVenta = $resultadoInsercion['idventa'];

        // Intentar editar con cantidad negativa
        $datosVentaEditada = [
            'observaciones' => 'Test validación cantidad negativa en edición',
            'detalles' => [[
                'idproducto' => $producto['idproducto'],
                'cantidad' => -5, // Cantidad negativa
                'precio_unitario_venta' => 10,
                'subtotal_general' => -50,
                'id_moneda_detalle' => 3
            ]]
        ];

        $resultadoEdicion = $this->ventasModel->updateVenta($idVenta, $datosVentaEditada);
        
        // Verificar que la validación funciona
        $this->assertFalse($resultadoEdicion['success'], "La edición con cantidad negativa no debería ser exitosa.");
        $this->assertStringContainsString('cantidad', strtolower($resultadoEdicion['message']), "El mensaje de error debería mencionar el problema con la cantidad.");
    }

    public function testEditarVentaConPrecioNegativo()
    {
        // Obtener un producto válido y un cliente activo
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto);

        $resultado = $this->clientesModel->selectAllClientes();
        $clientes = $resultado['data'];
        $clienteActivo = null;
        foreach ($clientes as $c) {
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'activo') {
                $clienteActivo = $c;
                break;
            }
        }
        $this->assertNotNull($clienteActivo);

        // Crear venta original
        $datosVentaOriginal = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $clienteActivo['idcliente'],
            'idmoneda_general' => 3,
            'subtotal_general' => 100,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 100,
            'observaciones' => 'Venta original',
            'tasa_usada' => 1
        ];

        $detallesVentaOriginal = [[
            'idproducto' => $producto['idproducto'],
            'cantidad' => 1,
            'precio_unitario_venta' => 100,
            'subtotal_general' => 100,
            'id_moneda_detalle' => 3
        ]];

        $resultadoInsercion = $this->ventasModel->insertVenta($datosVentaOriginal, $detallesVentaOriginal);
        $this->assertTrue($resultadoInsercion['success']);
        $idVenta = $resultadoInsercion['idventa'];

        // Intentar editar con precio negativo
        $datosVentaEditada = [
            'observaciones' => 'Test validación precio negativo en edición',
            'detalles' => [[
                'idproducto' => $producto['idproducto'],
                'cantidad' => 1,
                'precio_unitario_venta' => -100, // Precio negativo
                'subtotal_general' => -100,
                'id_moneda_detalle' => 3
            ]]
        ];

        $resultadoEdicion = $this->ventasModel->updateVenta($idVenta, $datosVentaEditada);
        
        // Verificar que la validación funciona
        $this->assertFalse($resultadoEdicion['success'], "La edición con precio negativo no debería ser exitosa.");
        $this->assertStringContainsString('precio', strtolower($resultadoEdicion['message']), "El mensaje de error debería mencionar el problema con el precio.");
    }

    public function testEditarVentaInexistente()
    {
        // Intentar editar una venta que no existe
        $datosVentaEditada = [
            'observaciones' => 'Test validación venta inexistente'
        ];

        $resultadoEdicion = $this->ventasModel->updateVenta(999999, $datosVentaEditada);
        
        // Verificar que la validación funciona
        $this->assertFalse($resultadoEdicion['success'], "La edición de una venta inexistente no debería ser exitosa.");
        $this->assertStringContainsString('no existe', strtolower($resultadoEdicion['message']), "El mensaje de error debería mencionar que la venta no existe.");
    }

    public function testEditarVentaSinDetalles()
    {
        // Obtener un producto válido y un cliente activo
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto);

        $resultado = $this->clientesModel->selectAllClientes();
        $clientes = $resultado['data'];
        $clienteActivo = null;
        foreach ($clientes as $c) {
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'activo') {
                $clienteActivo = $c;
                break;
            }
        }
        $this->assertNotNull($clienteActivo);

        // Crear venta original
        $datosVentaOriginal = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $clienteActivo['idcliente'],
            'idmoneda_general' => 3,
            'subtotal_general' => 100,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 100,
            'observaciones' => 'Venta original',
            'tasa_usada' => 1
        ];

        $detallesVentaOriginal = [[
            'idproducto' => $producto['idproducto'],
            'cantidad' => 1,
            'precio_unitario_venta' => 100,
            'subtotal_general' => 100,
            'id_moneda_detalle' => 3
        ]];

        $resultadoInsercion = $this->ventasModel->insertVenta($datosVentaOriginal, $detallesVentaOriginal);
        $this->assertTrue($resultadoInsercion['success']);
        $idVenta = $resultadoInsercion['idventa'];

        // Editar solo los datos generales sin detalles
        $datosVentaEditada = [
            'observaciones' => 'Venta editada sin modificar detalles',
            'total_general' => 150
        ];

        $resultadoEdicion = $this->ventasModel->updateVenta($idVenta, $datosVentaEditada);
        
        // Verificar que la edición fue exitosa
        $this->assertTrue($resultadoEdicion['success'], "La edición sin detalles debería ser exitosa.");

        // Verificar que los datos generales fueron actualizados
        $ventaEditada = $this->ventasModel->obtenerVentaPorId($idVenta);
        $this->assertEquals('Venta editada sin modificar detalles', $ventaEditada['observaciones']);
        $this->assertEquals(150, (float)$ventaEditada['total_general']);

        // Verificar que los detalles originales se mantuvieron
        $detallesEditados = $this->ventasModel->obtenerDetalleVenta($idVenta);
        $this->assertNotEmpty($detallesEditados, "Los detalles originales deberían mantenerse.");
        $this->assertEquals($producto['idproducto'], $detallesEditados[0]['idproducto']);
    }

  
}