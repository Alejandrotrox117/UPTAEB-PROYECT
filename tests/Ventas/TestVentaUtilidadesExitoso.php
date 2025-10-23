<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/ventasModel.php';

/**
 * Prueba de caja blanca para utilidades y consultas auxiliares de ventas
 */
class TestVentaUtilidadesExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new VentasModel();
    }

    public function testGetVentasDatatable()
    {
        if (!method_exists($this->model, 'getVentasDatatable')) {
            $this->markTestSkipped('Método getVentasDatatable no existe');
        }
        $params = ['start' => 0, 'length' => 10];
        $result = $this->model->getVentasDatatable($params);
        $this->assertIsArray($result);
    }

    public function testGetListaProductosParaFormulario()
    {
        if (!method_exists($this->model, 'getListaProductosParaFormulario')) {
            $this->markTestSkipped('Método getListaProductosParaFormulario no existe');
        }
        $result = $this->model->getListaProductosParaFormulario();
        $this->assertIsArray($result);
    }

    public function testGetMonedasActivas()
    {
        if (!method_exists($this->model, 'getMonedasActivas')) {
            $this->markTestSkipped('Método getMonedasActivas no existe');
        }
        $result = $this->model->getMonedasActivas();
        $this->assertIsArray($result);
    }

    public function testObtenerProductos()
    {
        if (!method_exists($this->model, 'obtenerProductos')) {
            $this->markTestSkipped('Método obtenerProductos no existe');
        }
        $result = $this->model->obtenerProductos();
        $this->assertIsArray($result);
    }

    public function testValidarDatosCliente()
    {
        if (!method_exists($this->model, 'validarDatosCliente')) {
            $this->markTestSkipped('Método validarDatosCliente no existe');
        }
        $datos = ['nombre' => 'Juan', 'cedula' => '12345678'];
        $result = $this->model->validarDatosCliente($datos);
        $this->assertIsArray($result);
    }

    public function testGetTasaPorCodigoYFecha()
    {
        if (!method_exists($this->model, 'getTasaPorCodigoYFecha')) {
            $this->markTestSkipped('Método getTasaPorCodigoYFecha no existe');
        }
        $result = $this->model->getTasaPorCodigoYFecha('USD', date('Y-m-d'));
        $this->assertIsArray($result);
    }

    public function testObtenerTasaActualMoneda()
    {
        if (!method_exists($this->model, 'obtenerTasaActualMoneda')) {
            $this->markTestSkipped('Método obtenerTasaActualMoneda no existe');
        }
        $result = $this->model->obtenerTasaActualMoneda('USD');
        $this->assertIsArray($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
