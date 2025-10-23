<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../app/models/ventasModel.php';
require_once __DIR__ . '/../app/models/productosModel.php';
require_once __DIR__ . '/../app/models/clientesModel.php';

class crearVentaTest extends TestCase
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

    public function testCrearVentaExitosa()
    {
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto);

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
        $this->assertNotNull($clienteActivo);

        $precioUnitario = 20.0;
        $cantidad = 3;
        $subtotal = $precioUnitario * $cantidad;

        $datosVenta = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $clienteActivo['idcliente'],
            'idmoneda_general' => 3, 
            'subtotal_general' => $subtotal,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => $subtotal,
            'observaciones' => 'Prueba de caja blanca',
            'tasa_usada' => 1
        ];

        $detallesVenta = [[
            'idproducto' => $producto['idproducto'],
            'cantidad' => $cantidad,
            'precio_unitario_venta' => $precioUnitario,
            'subtotal_general' => $subtotal,
            'id_moneda_detalle' => 3
        ]];

        $resultado = $this->ventasModel->insertVenta($datosVenta, $detallesVenta);

        // Verificar estructura de respuesta exitosa (lógica interna del modelo)
        $this->assertTrue($resultado['success']);
        $this->assertArrayHasKey('idventa', $resultado);
        $this->assertArrayHasKey('idcliente', $resultado);
        $this->assertArrayHasKey('nro_venta', $resultado);
        $this->assertArrayHasKey('message', $resultado);
        $this->assertGreaterThan(0, $resultado['idventa']);
        
        // Verificar que el nro_venta fue generado (método generarNumeroVenta())
        $this->assertNotEmpty($resultado['nro_venta']);
        $this->assertMatchesRegularExpression('/^VT\d+$/', $resultado['nro_venta']);

        // Verificar que la venta se insertó correctamente en la BD (commit exitoso)
        $ventaCreada = $this->ventasModel->obtenerVentaPorId($resultado['idventa']);
        $this->assertNotEmpty($ventaCreada);
        $this->assertEquals((float)$datosVenta['total_general'], (float)$ventaCreada['total_general']);
        $this->assertEquals((float)$datosVenta['total_general'], (float)$ventaCreada['balance']);
        $this->assertEquals($clienteActivo['idcliente'], $ventaCreada['idcliente']);
        $this->assertEquals($resultado['nro_venta'], $ventaCreada['nro_venta']);
        
        // Verificar que los detalles se insertaron (método insertarDetallesVenta())
        $detalles = $this->ventasModel->obtenerDetalleVenta($resultado['idventa']);
        $this->assertNotEmpty($detalles);
        $this->assertCount(1, $detalles);
        $this->assertEquals($producto['idproducto'], $detalles[0]['idproducto']);
        $this->assertEquals($cantidad, $detalles[0]['cantidad']);
        $this->assertEquals($precioUnitario, $detalles[0]['precio_unitario_venta']);
    }

    public function testCrearVentaConClienteInactivo()
    {
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto);

        $resultado = $this->clientesModel->selectAllClientes();
        $clientes = $resultado['data'];
        $clienteInactivo = null;
        foreach ($clientes as $c) {
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'inactivo') {
                $clienteInactivo = $c;
                break;
            }
        }
        $this->assertNotNull($clienteInactivo);

        $datosVenta = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $clienteInactivo['idcliente'],
            'idmoneda_general' => 3,
            'subtotal_general' => 100,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 100,
            'observaciones' => 'Test validación cliente inactivo',
            'tasa_usada' => 1
        ];
        $detallesVenta = [[
            'idproducto' => $producto['idproducto'],
            'cantidad' => 1,
            'precio_unitario_venta' => 100
        ]];
        
        $resultado = $this->ventasModel->insertVenta($datosVenta, $detallesVenta);
        
        // Verificar que la validación de cliente inactivo funciona (rama de validación interna)
        $this->assertFalse($resultado['success']);
        $this->assertArrayHasKey('message', $resultado);
        $this->assertStringContainsString('inactivo', strtolower($resultado['message']));
        
        // Verificar que NO se insertó nada en la BD (rollback ejecutado)
        $this->assertArrayNotHasKey('idventa', $resultado);
    }

    public function testCrearVentaConProductoInexistente()
    {
        $resultado = $this->clientesModel->selectAllClientes();
        $clientes = $resultado['data'];
        $cliente = null;
        foreach ($clientes as $c) {
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'activo') {
                $cliente = $c;
                break;
            }
        }
        $this->assertNotNull($cliente);

        $datosVenta = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $cliente['idcliente'],
            'idmoneda_general' => 3,
            'subtotal_general' => 100,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 100,
            'observaciones' => 'Test validación producto inexistente',
            'tasa_usada' => 1
        ];
        $detallesVenta = [[
            'idproducto' => 999999,
            'cantidad' => 1,
            'precio_unitario_venta' => 100
        ]];
        
        $resultado = $this->ventasModel->insertVenta($datosVenta, $detallesVenta);
        
      
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('no existe', strtolower($resultado['message']));
        $this->assertStringContainsString('999999', $resultado['message']);
        
      
        $this->assertArrayNotHasKey('idventa', $resultado);
    }

    public function testCrearVentaConCantidadNegativa()
    {
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto, "Producto de prueba no encontrado.");
        $resultado = $this->clientesModel->selectAllClientes();
        $clientes = $resultado['data'];
        $cliente = null;
        foreach ($clientes as $c) {
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'activo') {
                $cliente = $c;
                break;
            }
        }
        $this->assertNotNull($cliente, "No se encontró un cliente activo para la prueba.");
        $datosVenta = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $cliente['idcliente'],
            'idmoneda_general' => 3,
            'subtotal_general' => 50,  // Subtotal positivo
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 50,  // Total positivo
            'observaciones' => 'Venta con cantidad negativa',
            'tasa_usada' => 1
        ];
        $detallesVenta = [[
            'idproducto' => $producto['idproducto'],
            'descripcion_temporal_producto' => $producto['nombre'],
            'cantidad' => -5,  // Cantidad negativa - esto debe fallar
            'descuento' => 0,
            'precio_unitario_venta' => 10,  // Precio positivo
            'idmoneda_detalle' => 3,
            'subtotal_linea' => 50,  // Subtotal línea positivo
            'subtotal_original_linea' => 50,
            'monto_descuento_linea' => 0,
            'peso_vehiculo' => null,
            'peso_bruto' => null,
            'peso_neto' => null,
        ]];
        $resultado = $this->ventasModel->insertVenta($datosVenta, $detallesVenta);
        $this->assertFalse($resultado['success'], "La venta con cantidad negativa no debe permitirse. Mensaje: " . ($resultado['message'] ?? ''));
    }

    public function testCrearVentaConPrecioNegativo()
    {
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto, "Producto de prueba no encontrado.");
        $resultado = $this->clientesModel->selectAllClientes();
        $clientes = $resultado['data'];
        $cliente = null;
        foreach ($clientes as $c) {
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'activo') {
                $cliente = $c;
                break;
            }
        }
        $this->assertNotNull($cliente, "No se encontró un cliente activo para la prueba.");
        $datosVenta = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $cliente['idcliente'],
            'idmoneda_general' => 3,
            'subtotal_general' => 100,  // Subtotal positivo
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 100,  // Total positivo
            'observaciones' => 'Venta con precio negativo',
            'tasa_usada' => 1
        ];
        $detallesVenta = [[
            'idproducto' => $producto['idproducto'],
            'descripcion_temporal_producto' => $producto['nombre'],
            'cantidad' => 1,  // Cantidad positiva
            'descuento' => 0,
            'precio_unitario_venta' => -100,  // Precio negativo - esto debe fallar
            'idmoneda_detalle' => 3,
            'subtotal_linea' => 100,  // Subtotal línea positivo
            'subtotal_original_linea' => 100,
            'monto_descuento_linea' => 0,
            'peso_vehiculo' => null,
            'peso_bruto' => null,
            'peso_neto' => null,
        ]];
        $resultado = $this->ventasModel->insertVenta($datosVenta, $detallesVenta);
        $this->assertFalse($resultado['success'], "La venta con precio negativo no debe permitirse. Mensaje: " . ($resultado['message'] ?? ''));
    }

    public function testCrearVentaSinCliente()
    {
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto, "Producto de prueba no encontrado.");
        $datosVenta = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => null,
            'idmoneda_general' => 3,
            'subtotal_general' => 100,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 100,
            'observaciones' => 'Venta sin cliente',
            'tasa_usada' => 1
        ];
        $detallesVenta = [[
            'idproducto' => $producto['idproducto'],
            'descripcion_temporal_producto' => $producto['nombre'],
            'cantidad' => 1,
            'descuento' => 0,
            'precio_unitario_venta' => 100,
            'idmoneda_detalle' => 3,
            'subtotal_linea' => 100,
            'subtotal_original_linea' => 100,
            'monto_descuento_linea' => 0,
            'peso_vehiculo' => null,
            'peso_bruto' => null,
            'peso_neto' => null,
        ]];
        $resultado = $this->ventasModel->insertVenta($datosVenta, $detallesVenta);
        $this->assertFalse($resultado['success'], "La venta sin cliente no debe permitirse. Mensaje: " . ($resultado['message'] ?? ''));
    }

    public function testCrearVentaConDescuentoMayorAlTotal()
    {
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto, "Producto de prueba no encontrado.");
        $resultado = $this->clientesModel->selectAllClientes();
        $clientes = $resultado['data'];
        $cliente = null;
        foreach ($clientes as $c) {
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'activo') {
                $cliente = $c;
                break;
            }
        }
        $this->assertNotNull($cliente, "No se encontró un cliente activo para la prueba.");
        $datosVenta = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $cliente['idcliente'],
            'idmoneda_general' => 3,
            'subtotal_general' => 100,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 150,
            'estatus' => 'BORRADOR',
            'total_general' => -50,
            'observaciones' => 'Venta con descuento mayor al total',
            'tasa_usada' => 1
        ];
        $detallesVenta = [[
            'idproducto' => $producto['idproducto'],
            'descripcion_temporal_producto' => $producto['nombre'],
            'cantidad' => 1,
            'descuento' => 0,
            'precio_unitario_venta' => 100,
            'idmoneda_detalle' => 3,
            'subtotal_linea' => 100,
            'subtotal_original_linea' => 100,
            'monto_descuento_linea' => 0,
            'peso_vehiculo' => null,
            'peso_bruto' => null,
            'peso_neto' => null,
        ]];
        $resultado = $this->ventasModel->insertVenta($datosVenta, $detallesVenta);
        $this->assertFalse($resultado['success'], "La venta con descuento mayor al total no debe permitirse. Mensaje: " . ($resultado['message'] ?? ''));
    }

    public function testCrearVentaConMonedaInvalida()
    {
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto, "Producto de prueba no encontrado.");
        $resultado = $this->clientesModel->selectAllClientes();
        $clientes = $resultado['data'];
        $cliente = null;
        foreach ($clientes as $c) {
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'activo') {
                $cliente = $c;
                break;
            }
        }
        $this->assertNotNull($cliente, "No se encontró un cliente activo para la prueba.");
        $datosVenta = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $cliente['idcliente'],
            'idmoneda_general' => 999999, // Moneda que no existe
            'subtotal_general' => 100,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 100,
            'observaciones' => 'Venta con moneda inválida',
            'tasa_usada' => 1
        ];
        $detallesVenta = [[
            'idproducto' => $producto['idproducto'],
            'descripcion_temporal_producto' => $producto['nombre'],
            'cantidad' => 1,
            'descuento' => 0,
            'precio_unitario_venta' => 100,
            'idmoneda_detalle' => 999999, // Moneda que no existe
            'subtotal_linea' => 100,
            'subtotal_original_linea' => 100,
            'monto_descuento_linea' => 0,
            'peso_vehiculo' => null,
            'peso_bruto' => null,
            'peso_neto' => null,
        ]];
        $resultado = $this->ventasModel->insertVenta($datosVenta, $detallesVenta);
        $this->assertFalse($resultado['success'], "La venta con moneda inválida no debe permitirse. Mensaje: " . ($resultado['message'] ?? ''));
    }

    public function testValidacionClienteActivoEnModelo()
    {
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto);
        $resultado = $this->clientesModel->selectAllClientes();
        $clientes = $resultado['data'];
        $clienteActivo = null;
        $clienteInactivo = null;
        foreach ($clientes as $c) {
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'activo') {
                $clienteActivo = $c;
            }
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'inactivo') {
                $clienteInactivo = $c;
            }
        }
        $this->assertNotNull($clienteActivo);
        $this->assertNotNull($clienteInactivo);
        
        $datosVenta = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $clienteActivo['idcliente'],
            'idmoneda_general' => 3,
            'subtotal_general' => 100,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 100,
            'observaciones' => 'Test rama validación cliente activo',
            'tasa_usada' => 1
        ];
        $detallesVenta = [[
            'idproducto' => $producto['idproducto'],
            'cantidad' => 1,
            'precio_unitario_venta' => 100
        ]];
        
        // Probar rama: cliente activo (debe pasar validación)
        $resultado = $this->ventasModel->insertVenta($datosVenta, $detallesVenta);
        $this->assertTrue($resultado['success']);
        $this->assertArrayHasKey('idventa', $resultado);
        $this->assertEquals($clienteActivo['idcliente'], $resultado['idcliente']);
        
        // Probar rama: cliente inactivo (debe fallar en validación específica)
        $datosVenta['idcliente'] = $clienteInactivo['idcliente'];
        $resultado = $this->ventasModel->insertVenta($datosVenta, $detallesVenta);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('inactivo', strtolower($resultado['message']));
        $this->assertArrayNotHasKey('idventa', $resultado);
    }

    public function testValidacionProductoExistenteEnModelo()
    {
        $resultado = $this->clientesModel->selectAllClientes();
        $clientes = $resultado['data'];
        $cliente = null;
        foreach ($clientes as $c) {
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'activo') {
                $cliente = $c;
                break;
            }
        }
        $this->assertNotNull($cliente);
        $datosVenta = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $cliente['idcliente'],
            'idmoneda_general' => 3,
            'subtotal_general' => 100,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 100,
            'observaciones' => 'Test rama validación producto',
            'tasa_usada' => 1
        ];
        
        // Probar rama: producto válido (pasa validación en insertarDetallesVenta)
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto);
        $detallesVenta = [[
            'idproducto' => $producto['idproducto'],
            'cantidad' => 1,
            'precio_unitario_venta' => 100
        ]];
        $resultado = $this->ventasModel->insertVenta($datosVenta, $detallesVenta);
        $this->assertTrue($resultado['success']);
        $this->assertArrayHasKey('idventa', $resultado);
        
        // Probar rama: producto inexistente (falla en validación específica)
        $detallesVenta = [[
            'idproducto' => 999999,
            'cantidad' => 1,
            'precio_unitario_venta' => 100
        ]];
        $resultado = $this->ventasModel->insertVenta($datosVenta, $detallesVenta);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('no existe', strtolower($resultado['message']));
        $this->assertStringContainsString('#1', $resultado['message']); // Número de detalle
    }

    public function testValidacionCantidadYPrecioEnModelo()
    {
        $resultado = $this->clientesModel->selectAllClientes();
        $clientes = $resultado['data'];
        $cliente = null;
        foreach ($clientes as $c) {
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'activo') {
                $cliente = $c;
                break;
            }
        }
        $this->assertNotNull($cliente);
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto);
        $datosVenta = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $cliente['idcliente'],
            'idmoneda_general' => 3,
            'subtotal_general' => 100,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 100,
            'observaciones' => 'Test rama validación cantidad/precio',
            'tasa_usada' => 1
        ];
        
        // Probar rama: cantidad negativa (validación en insertarDetallesVenta)
        $detallesVenta = [[
            'idproducto' => $producto['idproducto'],
            'cantidad' => -5,
            'precio_unitario_venta' => 10
        ]];
        $resultado = $this->ventasModel->insertVenta($datosVenta, $detallesVenta);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('cantidad', strtolower($resultado['message']));
        $this->assertStringContainsString('mayor a 0', strtolower($resultado['message']));
        
        // Probar rama: precio negativo o inválido (validación específica de precio)
        $detallesVenta = [[
            'idproducto' => $producto['idproducto'],
            'cantidad' => 1,
            'precio_unitario_venta' => -100
        ]];
        $resultado = $this->ventasModel->insertVenta($datosVenta, $detallesVenta);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('precio', strtolower($resultado['message']));
        
        // Probar rama: cantidad y precio válidos (debe pasar validación)
        $detallesVenta = [[
            'idproducto' => $producto['idproducto'],
            'cantidad' => 5,
            'precio_unitario_venta' => 20
        ]];
        $resultado = $this->ventasModel->insertVenta($datosVenta, $detallesVenta);
        $this->assertTrue($resultado['success']);
    }

    
}
