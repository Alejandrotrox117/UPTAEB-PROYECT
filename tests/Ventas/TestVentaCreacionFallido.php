<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/ventasModel.php';

/**
 * Prueba de caja blanca para casos de fallo en creación de ventas
 * Valida validaciones de negocio y datos requeridos
 * Usa el método insertVenta() del modelo VentasModel
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
            'idmoneda_general' => 3,
            'subtotal_general' => 50.00,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'total_general' => 50.00,
            'estatus' => 'activo'
            // idcliente faltante
        ];

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

        try {
            $result = $this->model->insertVenta($data, $detalles);
            // Si no lanza excepción, verificar que falle
            $this->assertFalse($result['success'] ?? true, "No debería crear venta sin cliente");
        } catch (Exception $e) {
            // Esperamos una excepción
            $this->assertStringContainsString('cliente', strtolower($e->getMessage()));
        }
    }

    public function testCrearVentaSinProductos()
    {
        $data = [
            'idcliente' => 1,
            'fecha_venta' => date('Y-m-d'),
            'idmoneda_general' => 3,
            'subtotal_general' => 0,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'total_general' => 0,
            'estatus' => 'activo'
        ];

        $detalles = []; // Sin productos

        try {
            $result = $this->model->insertVenta($data, $detalles);
            $this->assertFalse($result['success'] ?? true, "No debería crear venta sin productos");
        } catch (Exception $e) {
            // Esperamos una excepción
            $this->assertStringContainsString('producto', strtolower($e->getMessage()));
        }
    }

    public function testCrearVentaConClienteInexistente()
    {
        $data = [
            'idcliente' => 99999, // Cliente inexistente
            'fecha_venta' => date('Y-m-d'),
            'idmoneda_general' => 3,
            'subtotal_general' => 50.00,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'total_general' => 50.00,
            'estatus' => 'activo'
        ];

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

        try {
            $result = $this->model->insertVenta($data, $detalles);
            // Puede fallar por foreign key
            $this->assertFalse($result['success'] ?? true);
        } catch (Exception $e) {
            // Esperamos error de integridad referencial
            $this->assertTrue(true);
        }
    }

    public function testCrearVentaConProductoInexistente()
    {
        $data = [
            'idcliente' => 1,
            'fecha_venta' => date('Y-m-d'),
            'idmoneda_general' => 3,
            'subtotal_general' => 50.00,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'total_general' => 50.00,
            'estatus' => 'activo'
        ];

        $detalles = [
            [
                'idproducto' => 99999, // Producto inexistente
                'cantidad' => 5,
                'precio_unitario_venta' => 10.00,
                'descuento' => 0,
                'subtotal_linea' => 50.00,
                'idmoneda_detalle' => 3
            ]
        ];

        try {
            $result = $this->model->insertVenta($data, $detalles);
            $this->assertFalse($result['success'] ?? true);
        } catch (Exception $e) {
            // Esperamos error de foreign key
            $this->assertTrue(true);
        }
    }

    public function testCrearVentaConCantidadNegativa()
    {
        $data = [
            'idcliente' => 1,
            'fecha_venta' => date('Y-m-d'),
            'idmoneda_general' => 3,
            'subtotal_general' => -50.00,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'total_general' => -50.00,
            'estatus' => 'activo'
        ];

        $detalles = [
            [
                'idproducto' => 1,
                'cantidad' => -5, // Cantidad negativa
                'precio_unitario_venta' => 10.00,
                'descuento' => 0,
                'subtotal_linea' => -50.00,
                'idmoneda_detalle' => 3
            ]
        ];

        try {
            $result = $this->model->insertVenta($data, $detalles);
            // Validar que no permite cantidades negativas
            $this->assertFalse($result['success'] ?? true);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testCrearVentaConPrecioNegativo()
    {
        $data = [
            'idcliente' => 1,
            'fecha_venta' => date('Y-m-d'),
            'idmoneda_general' => 3,
            'subtotal_general' => -50.00,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'total_general' => -50.00,
            'estatus' => 'activo'
        ];

        $detalles = [
            [
                'idproducto' => 1,
                'cantidad' => 5,
                'precio_unitario_venta' => -10.00, // Precio negativo
                'descuento' => 0,
                'subtotal_linea' => -50.00,
                'idmoneda_detalle' => 3
            ]
        ];

        try {
            $result = $this->model->insertVenta($data, $detalles);
            $this->assertFalse($result['success'] ?? true);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testCrearVentaSinDatosCamposRequeridos()
    {
        // Test para validar que faltan campos obligatorios
        $data = [
            'idcliente' => 1
            // Faltan muchos campos requeridos
        ];

        $detalles = [
            [
                'idproducto' => 1,
                'cantidad' => 5
                // Faltan campos de detalle
            ]
        ];

        try {
            $result = $this->model->insertVenta($data, $detalles);
            // Debería fallar por campos faltantes
            $this->assertFalse($result['success'] ?? true);
        } catch (Exception $e) {
            // Esperamos excepción por campos faltantes
            $this->assertTrue(true);
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
