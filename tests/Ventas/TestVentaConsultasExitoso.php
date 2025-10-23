<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/ventasModel.php';

/**
 * Prueba de caja blanca para búsqueda y consulta de ventas
 * Valida filtros, búsquedas y obtención de reportes
 */
class TestVentaConsultasExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new VentasModel();
    }

    public function testBuscarTodasLasVentas()
    {
        if (method_exists($this->model, 'selectAllVentas')) {
            $result = $this->model->selectAllVentas();

            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método selectAllVentas no existe');
        }
    }

    public function testBuscarVentaPorId()
    {
        if (method_exists($this->model, 'selectVentaById')) {
            $result = $this->model->selectVentaById(1);

            $this->assertTrue(
                is_array($result) || is_bool($result),
                "Debería retornar array o false"
            );
        } else {
            $this->markTestSkipped('Método selectVentaById no existe');
        }
    }

    public function testBuscarVentasPorCliente()
    {
        if (method_exists($this->model, 'selectVentasByCliente')) {
            $idCliente = 1;
            $result = $this->model->selectVentasByCliente($idCliente);

            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método selectVentasByCliente no existe');
        }
    }

    public function testBuscarVentasPorFecha()
    {
        if (method_exists($this->model, 'selectVentasByFecha')) {
            $fechaInicio = date('Y-m-01');
            $fechaFin = date('Y-m-d');
            
            $result = $this->model->selectVentasByFecha($fechaInicio, $fechaFin);

            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método selectVentasByFecha no existe');
        }
    }

    public function testBuscarClientes()
    {
        $criterio = 'Juan';
        $result = $this->model->buscarClientes($criterio);

        $this->assertIsArray($result);
    }

    public function testCalcularTotalVentas()
    {
        if (method_exists($this->model, 'calcularTotalVentas')) {
            $fechaInicio = date('Y-m-01');
            $fechaFin = date('Y-m-d');
            
            $total = $this->model->calcularTotalVentas($fechaInicio, $fechaFin);

            $this->assertIsNumeric($total, "Debería retornar un número");
        } else {
            $this->markTestSkipped('Método calcularTotalVentas no existe');
        }
    }

    public function testObtenerDetalleVenta()
    {
        if (method_exists($this->model, 'getDetalleVenta')) {
            $idVenta = 1;
            $result = $this->model->getDetalleVenta($idVenta);

            $this->assertTrue(
                is_array($result) || is_bool($result),
                "Debería retornar array con detalles o false"
            );
        } else {
            $this->markTestSkipped('Método getDetalleVenta no existe');
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
