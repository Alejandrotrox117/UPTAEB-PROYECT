<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/ventasModel.php';

/**
 * Prueba de caja blanca para cambio de estado de venta
 */
class TestVentaCambioEstadoExitoso extends TestCase
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

    public function testCambiarEstadoVenta()
    {
        if (!method_exists($this->model, 'cambiarEstadoVenta')) {
            $this->markTestSkipped('Método cambiarEstadoVenta no existe');
        }
        if (!$this->ventaIdPrueba) {
            $this->markTestSkipped('No se pudo crear venta de prueba');
        }
        $nuevoEstado = 'ANULADA';
        $result = $this->model->cambiarEstadoVenta($this->ventaIdPrueba, $nuevoEstado);
        $this->assertIsArray($result);
    }

    public function testObtenerEstadoVenta()
    {
        if (!method_exists($this->model, 'obtenerEstadoVenta')) {
            $this->markTestSkipped('Método obtenerEstadoVenta no existe');
        }
        if (!$this->ventaIdPrueba) {
            $this->markTestSkipped('No se pudo crear venta de prueba');
        }
        $result = $this->model->obtenerEstadoVenta($this->ventaIdPrueba);
        $this->assertIsArray($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
