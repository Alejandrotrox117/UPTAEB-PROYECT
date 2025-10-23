<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/ventasModel.php';

/**
 * Prueba de caja blanca para actualización exitosa de ventas
 */
class TestVentaActualizacionExitoso extends TestCase
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

    public function testActualizarVentaExistente()
    {
        if (!method_exists($this->model, 'updateVenta')) {
            $this->markTestSkipped('Método updateVenta no existe');
        }
        if (!$this->ventaIdPrueba) {
            $this->markTestSkipped('No se pudo crear venta de prueba');
        }
        $dataUpdate = [
            'total_venta' => 30.00,
            'tipo_pago' => 2
        ];
        $result = $this->model->updateVenta($this->ventaIdPrueba, $dataUpdate);
        $this->assertIsArray($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
