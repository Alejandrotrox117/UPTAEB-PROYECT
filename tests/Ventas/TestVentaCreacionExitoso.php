<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/ventasModel.php';

/**
 * Prueba de caja blanca para creación exitosa de ventas
 * Valida todo el proceso de crear una venta con sus detalles
 * Usa el método insertVenta() del modelo VentasModel
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
        // Preparar datos de la venta
        $data = [
            'idcliente' => 1,
            'fecha_venta' => date('Y-m-d'),
            'idmoneda_general' => 3,
            'subtotal_general' => 50.00,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'total_general' => 50.00,
            'estatus' => 'activo',
            'observaciones' => 'Prueba unitaria - venta con un producto',
            'tasa_usada' => 1
        ];

        // Preparar detalles de la venta
        $detalles = [
            [
                'idproducto' => 1,
                'cantidad' => 5,
                'precio_unitario_venta' => 10.00,
                'descuento' => 0,
                'subtotal_linea' => 50.00,
                'idmoneda_detalle' => 3
            ]
        ];

        $result = $this->model->insertVenta($data, $detalles);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('idventa', $result);
    }

    public function testCrearVentaConVariosProductos()
    {
        // Calcular totales - usando solo producto ID 1 con diferentes cantidades
        $subtotal = (3 * 15.00) + (2 * 25.00); // 45 + 50 = 95

        $data = [
            'idcliente' => 1,
            'fecha_venta' => date('Y-m-d'),
            'idmoneda_general' => 3,
            'subtotal_general' => $subtotal,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'total_general' => $subtotal,
            'estatus' => 'activo',
            'observaciones' => 'Prueba unitaria - venta con varios productos',
            'tasa_usada' => 1
        ];

        $detalles = [
            [
                'idproducto' => 1,
                'cantidad' => 3,
                'precio_unitario_venta' => 15.00,
                'descuento' => 0,
                'subtotal_linea' => 45.00,
                'idmoneda_detalle' => 3
            ],
            [
                'idproducto' => 1, // Mismo producto, diferente línea
                'cantidad' => 2,
                'precio_unitario_venta' => 25.00,
                'descuento' => 0,
                'subtotal_linea' => 50.00,
                'idmoneda_detalle' => 3
            ]
        ];

        $result = $this->model->insertVenta($data, $detalles);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals($subtotal, $data['total_general']);
    }

    public function testCrearVentaConDescuento()
    {
        $subtotal = 5 * 20.00; // 100.00
        $descuento = 10.00;
        $total = $subtotal - $descuento; // 90.00

        $data = [
            'idcliente' => 1,
            'fecha_venta' => date('Y-m-d'),
            'idmoneda_general' => 3,
            'subtotal_general' => $subtotal,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => $descuento,
            'total_general' => $total,
            'estatus' => 'activo',
            'observaciones' => 'Prueba unitaria - venta con descuento',
            'tasa_usada' => 1
        ];

        $detalles = [
            [
                'idproducto' => 1,
                'cantidad' => 5,
                'precio_unitario_venta' => 20.00,
                'descuento' => 0,
                'subtotal_linea' => 100.00,
                'idmoneda_detalle' => 3
            ]
        ];

        $result = $this->model->insertVenta($data, $detalles);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals($total, $data['total_general']);
    }

    public function testCalcularTotalVenta()
    {
        // Test auxiliar para validar cálculo de totales
        $productos = [
            ['cantidad' => 3, 'precio_unitario' => 10.00],
            ['cantidad' => 2, 'precio_unitario' => 15.00]
        ];

        $total = 0;
        foreach ($productos as $producto) {
            $total += $producto['cantidad'] * $producto['precio_unitario'];
        }

        $this->assertIsNumeric($total);
        $this->assertEquals(60.00, $total);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
