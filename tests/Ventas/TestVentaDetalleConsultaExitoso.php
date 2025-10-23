<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/ventasModel.php';

/**
 * Prueba de caja blanca para consulta de detalle de venta
 */
class TestVentaDetalleConsultaExitoso extends TestCase
{
    private $model;
    private $ventaIdPrueba;

    protected function setUp(): void
    {
        $this->model = new VentasModel();
        // Crear venta de prueba
        if (method_exists($this->model, 'insertVenta')) {
            $dataVenta = [
                'idcliente' => 1,
                'fecha_venta' => date('Y-m-d'),
                'tipo_pago' => 1,
                'total_venta' => 20.00
            ];
            $detalles = [
                ['idproducto' => 1, 'cantidad' => 2, 'precio_unitario' => 10.00]
            ];
            $result = $this->model->insertVenta($dataVenta, $detalles);
            if (isset($result['status']) && $result['status']) {
                $this->ventaIdPrueba = $result['idventa'] ?? null;
            }
        }
    }

    public function testObtenerDetalleVenta()
    {
        if (!method_exists($this->model, 'obtenerDetalleVenta')) {
            $this->markTestSkipped('Método obtenerDetalleVenta no existe');
        }
        if (!$this->ventaIdPrueba) {
            $this->markTestSkipped('No se pudo crear venta de prueba');
        }
        $result = $this->model->obtenerDetalleVenta($this->ventaIdPrueba);
        $this->assertIsArray($result);
    }

    public function testObtenerDetalleVentaCompleto()
    {
        if (!method_exists($this->model, 'obtenerDetalleVentaCompleto')) {
            $this->markTestSkipped('Método obtenerDetalleVentaCompleto no existe');
        }
        if (!$this->ventaIdPrueba) {
            $this->markTestSkipped('No se pudo crear venta de prueba');
        }
        $result = $this->model->obtenerDetalleVentaCompleto($this->ventaIdPrueba);
        $this->assertIsArray($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
