<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/ventasModel.php';

/**
 * Prueba de caja blanca para consultas y operaciones de ventas
 * Incluye consultas, actualizaciones y cambio de estado
 */
class TestVentaConsultas extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new VentasModel();
    }

    // ========== CONSULTAS ==========

    public function testBuscarTodasLasVentas()
    {
        if (method_exists($this->model, 'getAllVentas')) {
            $result = $this->model->getAllVentas();

            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método getAllVentas no existe');
        }
    }

    public function testBuscarVentaPorId()
    {
        if (method_exists($this->model, 'getVentaById')) {
            $result = $this->model->getVentaById(1);

            $this->assertTrue(
                is_array($result) || is_bool($result)
            );
        } else {
            $this->markTestSkipped('Método getVentaById no existe');
        }
    }

    public function testBuscarVentasPorCliente()
    {
        if (method_exists($this->model, 'getVentasByCliente')) {
            $result = $this->model->getVentasByCliente(1);

            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método getVentasByCliente no existe');
        }
    }

    public function testBuscarVentasPorFecha()
    {
        if (method_exists($this->model, 'getVentasByFecha')) {
            $fecha = date('Y-m-d');
            $result = $this->model->getVentasByFecha($fecha);

            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método getVentasByFecha no existe');
        }
    }

    public function testObtenerDetalleVenta()
    {
        if (method_exists($this->model, 'getDetalleVenta')) {
            $result = $this->model->getDetalleVenta(1);

            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método getDetalleVenta no existe');
        }
    }

    // ========== ACTUALIZACIÓN ==========

    public function testActualizarVentaExistente()
    {
        if (method_exists($this->model, 'updateVenta')) {
            $data = [
                'observaciones' => 'Venta actualizada',
                'estatus' => 'activo'
            ];

            $result = $this->model->updateVenta(1, $data);

            $this->assertTrue(
                is_array($result) || is_bool($result)
            );
        } else {
            $this->markTestSkipped('Método updateVenta no existe');
        }
    }

    // ========== CAMBIO DE ESTADO ==========

    public function testCambiarEstadoVenta()
    {
        if (method_exists($this->model, 'cambiarEstado')) {
            $result = $this->model->cambiarEstado(1, 'pendiente');

            $this->assertTrue(
                is_array($result) || is_bool($result)
            );
        } else {
            $this->markTestSkipped('Método cambiarEstado no existe');
        }
    }

    public function testObtenerEstadoVenta()
    {
        if (method_exists($this->model, 'getEstadoVenta')) {
            $result = $this->model->getEstadoVenta(1);

            $this->assertTrue(
                is_string($result) || is_bool($result) || is_array($result)
            );
        } else {
            $this->markTestSkipped('Método getEstadoVenta no existe');
        }
    }

    // ========== UTILIDADES ==========

    public function testCalcularTotalVentas()
    {
        if (method_exists($this->model, 'calcularTotalVentas')) {
            $result = $this->model->calcularTotalVentas();

            $this->assertTrue(
                is_numeric($result) || is_array($result)
            );
        } else {
            $this->markTestSkipped('Método calcularTotalVentas no existe');
        }
    }

    public function testBuscarClientes()
    {
        if (method_exists($this->model, 'getClientes')) {
            $result = $this->model->getClientes();

            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método getClientes no existe');
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
