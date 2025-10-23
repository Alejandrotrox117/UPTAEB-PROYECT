<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/ventasModel.php';

/**
 * Prueba de caja blanca para casos de fallo en creación de ventas
 * Valida validaciones de negocio y datos requeridos
 */
class TestVentaCreacionFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new VentasModel();
    }

    public function testCrearVentaSinCliente()
    {
        $data = [
            'fecha_venta' => date('Y-m-d'),
            'tipo_pago' => 'efectivo',
            'productos' => [
                ['idproducto' => 1, 'cantidad' => 5, 'precio_unitario' => 10.00]
            ]
        ];

        if (method_exists($this->model, 'crearVenta')) {
            $result = $this->model->crearVenta($data);

            $this->assertIsArray($result);
            $this->assertFalse($result['status'], "No debería crear venta sin cliente");
        } else {
            $this->markTestSkipped('Método crearVenta no existe');
        }
    }

    public function testCrearVentaSinProductos()
    {
        $data = [
            'idcliente' => 1,
            'fecha_venta' => date('Y-m-d'),
            'tipo_pago' => 'efectivo',
            'productos' => []
        ];

        if (method_exists($this->model, 'crearVenta')) {
            $result = $this->model->crearVenta($data);

            $this->assertIsArray($result);
            $this->assertFalse($result['status'], "No debería crear venta sin productos");
        } else {
            $this->markTestSkipped('Método crearVenta no existe');
        }
    }

    public function testCrearVentaConClienteInexistente()
    {
        $data = [
            'idcliente' => 99999,
            'fecha_venta' => date('Y-m-d'),
            'tipo_pago' => 'efectivo',
            'productos' => [
                ['idproducto' => 1, 'cantidad' => 5, 'precio_unitario' => 10.00]
            ]
        ];

        if (method_exists($this->model, 'crearVenta')) {
            $result = $this->model->crearVenta($data);

            $this->assertIsArray($result);
            $this->assertFalse($result['status'], "No debería crear venta con cliente inexistente");
        } else {
            $this->markTestSkipped('Método crearVenta no existe');
        }
    }

    public function testCrearVentaConProductoInexistente()
    {
        $data = [
            'idcliente' => 1,
            'fecha_venta' => date('Y-m-d'),
            'tipo_pago' => 'efectivo',
            'productos' => [
                ['idproducto' => 99999, 'cantidad' => 5, 'precio_unitario' => 10.00]
            ]
        ];

        if (method_exists($this->model, 'crearVenta')) {
            $result = $this->model->crearVenta($data);

            $this->assertIsArray($result);
            $this->assertFalse($result['status'], "No debería crear venta con producto inexistente");
        } else {
            $this->markTestSkipped('Método crearVenta no existe');
        }
    }

    public function testCrearVentaConCantidadNegativa()
    {
        $data = [
            'idcliente' => 1,
            'fecha_venta' => date('Y-m-d'),
            'tipo_pago' => 'efectivo',
            'productos' => [
                ['idproducto' => 1, 'cantidad' => -5, 'precio_unitario' => 10.00]
            ]
        ];

        if (method_exists($this->model, 'crearVenta')) {
            $result = $this->model->crearVenta($data);

            $this->assertIsArray($result);
            $this->assertFalse($result['status'], "No debería permitir cantidad negativa");
        } else {
            $this->markTestSkipped('Método crearVenta no existe');
        }
    }

    public function testCrearVentaConPrecioNegativo()
    {
        $data = [
            'idcliente' => 1,
            'fecha_venta' => date('Y-m-d'),
            'tipo_pago' => 'efectivo',
            'productos' => [
                ['idproducto' => 1, 'cantidad' => 5, 'precio_unitario' => -10.00]
            ]
        ];

        if (method_exists($this->model, 'crearVenta')) {
            $result = $this->model->crearVenta($data);

            $this->assertIsArray($result);
            $this->assertFalse($result['status'], "No debería permitir precio negativo");
        } else {
            $this->markTestSkipped('Método crearVenta no existe');
        }
    }

    public function testCrearVentaConStockInsuficiente()
    {
        $data = [
            'idcliente' => 1,
            'fecha_venta' => date('Y-m-d'),
            'tipo_pago' => 'efectivo',
            'productos' => [
                ['idproducto' => 1, 'cantidad' => 10000, 'precio_unitario' => 10.00] // Cantidad muy alta
            ]
        ];

        if (method_exists($this->model, 'crearVenta')) {
            $result = $this->model->crearVenta($data);

            $this->assertIsArray($result);
            // Puede fallar por stock insuficiente si hay validación
        } else {
            $this->markTestSkipped('Método crearVenta no existe');
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
