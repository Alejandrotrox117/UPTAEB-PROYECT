<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../app/models/ventasModel.php';
class TestVentaCreacion extends TestCase
{
    private $model;
    private function showMessage(string $msg)
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }
    protected function setUp(): void
    {
        $this->model = new VentasModel();
    }
    public function testCrearVentaConUnProducto()
    {
        $data = [
            'idcliente' => 1,
            'fecha_venta' => date('Y-m-d'),
            'idmoneda_general' => 3,
            'subtotal_general' => 50.00,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'total_general' => 50.00,
            'estatus' => 'activo',
            'observaciones' => 'Prueba unitaria',
            'tasa_usada' => 1
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
        $result = $this->model->insertVenta($data, $detalles);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }
    public function testCrearVentaConDescuento()
    {
        $data = [
            'idcliente' => 1,
            'fecha_venta' => date('Y-m-d'),
            'idmoneda_general' => 3,
            'subtotal_general' => 100.00,
            'descuento_porcentaje_general' => 10,
            'monto_descuento_general' => 10.00,
            'total_general' => 90.00,
            'estatus' => 'activo',
            'tasa_usada' => 1
        ];
        $detalles = [
            [
                'idproducto' => 1,
                'cantidad' => 10,
                'precio_unitario_venta' => 10.00,
                'subtotal_linea' => 100.00,
                'idmoneda_detalle' => 3
            ]
        ];
        $result = $this->model->insertVenta($data, $detalles);
        $this->assertIsArray($result);
    }
    public function testCrearVentaSinCliente()
    {
        $data = [
            'fecha_venta' => date('Y-m-d'),
            'idmoneda_general' => 3,
            'total_general' => 50.00,
            'estatus' => 'activo'
        ];
        $detalles = [
            [
                'idproducto' => 1,
                'cantidad' => 5,
                'precio_unitario_venta' => 10.00,
                'subtotal_linea' => 50.00,
                'idmoneda_detalle' => 3
            ]
        ];
        try {
            $result = $this->model->insertVenta($data, $detalles);
            $this->assertFalse($result['success'] ?? true);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
        }
    }
    public function testCrearVentaSinProductos()
    {
        $data = [
            'idcliente' => 1,
            'fecha_venta' => date('Y-m-d'),
            'idmoneda_general' => 3,
            'total_general' => 0,
            'estatus' => 'activo'
        ];
        $detalles = [];
        try {
            $result = $this->model->insertVenta($data, $detalles);
            $this->assertFalse($result['success'] ?? true);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
        }
    }
    public function testCrearVentaConClienteInexistente()
    {
        $data = [
            'idcliente' => 99999,
            'fecha_venta' => date('Y-m-d'),
            'idmoneda_general' => 3,
            'total_general' => 50.00,
            'estatus' => 'activo'
        ];
        $detalles = [
            [
                'idproducto' => 1,
                'cantidad' => 5,
                'precio_unitario_venta' => 10.00,
                'subtotal_linea' => 50.00,
                'idmoneda_detalle' => 3
            ]
        ];
        $result = $this->model->insertVenta($data, $detalles);
        $this->assertIsArray($result);
        $this->assertFalse($result['success'] ?? true);
    }
    public function testCrearVentaConProductoInexistente()
    {
        $data = [
            'idcliente' => 1,
            'fecha_venta' => date('Y-m-d'),
            'idmoneda_general' => 3,
            'total_general' => 50.00,
            'estatus' => 'activo'
        ];
        $detalles = [
            [
                'idproducto' => 99999,
                'cantidad' => 5,
                'precio_unitario_venta' => 10.00,
                'subtotal_linea' => 50.00,
                'idmoneda_detalle' => 3
            ]
        ];
        $result = $this->model->insertVenta($data, $detalles);
        $this->assertIsArray($result);
        $this->assertFalse($result['success'] ?? true);
    }
    public function testCrearVentaConCantidadNegativa()
    {
        $data = [
            'idcliente' => 1,
            'fecha_venta' => date('Y-m-d'),
            'idmoneda_general' => 3,
            'total_general' => -50.00,
            'estatus' => 'activo'
        ];
        $detalles = [
            [
                'idproducto' => 1,
                'cantidad' => -5,
                'precio_unitario_venta' => 10.00,
                'subtotal_linea' => -50.00,
                'idmoneda_detalle' => 3
            ]
        ];
        $result = $this->model->insertVenta($data, $detalles);
        $this->assertIsArray($result);
        $this->assertFalse($result['success'] ?? true);
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
