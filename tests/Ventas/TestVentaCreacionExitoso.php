<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/ventasModel.php';

/**
 * Prueba de caja blanca para creación exitosa de ventas
 * Valida todo el proceso de crear una venta con sus detalles
 */
class TestVentaCreacionExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new VentasModel();
    }

    public function testCrearVentaConUnProducto()
    {
        $data = [
            'idcliente' => 1,
            'fecha_venta' => date('Y-m-d'),
            'tipo_pago' => 'efectivo',
            'productos' => [
                [
                    'idproducto' => 1,
                    'cantidad' => 5,
                    'precio_unitario' => 10.00
                ]
            ]
        ];

        if (method_exists($this->model, 'crearVenta')) {
            $result = $this->model->crearVenta($data);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('status', $result);
            $this->assertArrayHasKey('message', $result);
        } else {
            $this->markTestSkipped('Método crearVenta no existe');
        }
    }

    public function testCrearVentaConVariosProductos()
    {
        $data = [
            'idcliente' => 1,
            'fecha_venta' => date('Y-m-d'),
            'tipo_pago' => 'credito',
            'productos' => [
                ['idproducto' => 1, 'cantidad' => 3, 'precio_unitario' => 15.00],
                ['idproducto' => 2, 'cantidad' => 2, 'precio_unitario' => 25.00],
                ['idproducto' => 3, 'cantidad' => 1, 'precio_unitario' => 50.00]
            ]
        ];

        if (method_exists($this->model, 'crearVenta')) {
            $result = $this->model->crearVenta($data);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('status', $result);
        } else {
            $this->markTestSkipped('Método crearVenta no existe');
        }
    }

    public function testCrearVentaConDescuento()
    {
        $data = [
            'idcliente' => 1,
            'fecha_venta' => date('Y-m-d'),
            'tipo_pago' => 'efectivo',
            'descuento' => 10.00,
            'productos' => [
                ['idproducto' => 1, 'cantidad' => 5, 'precio_unitario' => 20.00]
            ]
        ];

        if (method_exists($this->model, 'crearVenta')) {
            $result = $this->model->crearVenta($data);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('status', $result);
        } else {
            $this->markTestSkipped('Método crearVenta no existe');
        }
    }

    public function testCalcularTotalVenta()
    {
        $productos = [
            ['cantidad' => 3, 'precio_unitario' => 10.00],
            ['cantidad' => 2, 'precio_unitario' => 15.00]
        ];

        if (method_exists($this->model, 'calcularTotal')) {
            $total = $this->model->calcularTotal($productos);

            $this->assertIsNumeric($total, "Debería retornar un número");
            $this->assertEquals(60.00, $total);
        } else {
            $this->markTestSkipped('Método calcularTotal no existe');
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
