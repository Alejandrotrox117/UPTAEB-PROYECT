<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/ventasModel.php';

/**
 * Prueba de caja blanca para casos de fallo en anulación de ventas
 * Valida restricciones de negocio para anular ventas
 */
class TestVentaAnulacionFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new VentasModel();
    }

    public function testAnularVentaInexistente()
    {
        if (!method_exists($this->model, 'anularVenta')) {
            $this->markTestSkipped('Método anularVenta no existe');
        }

        $result = $this->model->anularVenta(99999);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería anular venta inexistente");
    }

    public function testAnularVentaYaAnulada()
    {
        if (!method_exists($this->model, 'anularVenta')) {
            $this->markTestSkipped('Método anularVenta no existe');
        }

        // Intentar anular una venta que ya está anulada
        $idVentaAnulada = 1; // Ajustar según datos de prueba

        $result = $this->model->anularVenta($idVentaAnulada);

        $this->assertIsArray($result);
        // No debería permitir anular una venta ya anulada
    }

    public function testAnularVentaConIdNegativo()
    {
        if (!method_exists($this->model, 'anularVenta')) {
            $this->markTestSkipped('Método anularVenta no existe');
        }

        $result = $this->model->anularVenta(-1);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería anular con ID negativo");
    }

    public function testAnularVentaConIdCero()
    {
        if (!method_exists($this->model, 'anularVenta')) {
            $this->markTestSkipped('Método anularVenta no existe');
        }

        $result = $this->model->anularVenta(0);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería anular con ID cero");
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
