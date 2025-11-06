<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/produccionModel.php';
class TestNominaYSalarios extends TestCase
{
    private $model;
    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "\n[MODEL MESSAGE] " . $msg . "\n");
    }
    protected function setUp(): void
    {
        $this->model = new ProduccionModel();
    }
    public function testRegistrarSolicitudPagoSinRegistros()
    {
        $result = $this->model->registrarSolicitudPago([]);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        if (!$result['status']) {
            $this->showMessage("Validación correcta: " . $result['message']);
        }
    }
    public function testRegistrarSolicitudPagoConRegistrosInexistentes()
    {
        $registros = [99999, 99998, 99997];
        $result = $this->model->registrarSolicitudPago($registros);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }
    public function testSelectPreciosProceso()
    {
        $result = $this->model->selectPreciosProceso();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }
    public function testCreatePrecioProcesoSinTipo()
    {
        $data = [
            'idproducto' => 1,
            'salario_unitario' => 0.50,
            'moneda' => 'USD'
        ];
        $result = $this->model->createPrecioProceso($data);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->showMessage("Validación correcta: " . $result['message']);
    }
    public function testCreatePrecioProcesoSinProducto()
    {
        $data = [
            'tipo_proceso' => 'CLASIFICACION',
            'salario_unitario' => 0.50,
            'moneda' => 'USD'
        ];
        $result = $this->model->createPrecioProceso($data);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->showMessage("Validación correcta: " . $result['message']);
    }
    public function testCreatePrecioProcesoSinSalario()
    {
        $data = [
            'tipo_proceso' => 'CLASIFICACION',
            'idproducto' => 1,
            'moneda' => 'USD'
        ];
        $result = $this->model->createPrecioProceso($data);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
    public function testCreatePrecioProcesoConSalarioNegativo()
    {
        $data = [
            'tipo_proceso' => 'CLASIFICACION',
            'idproducto' => 1,
            'salario_unitario' => -0.50,
            'moneda' => 'USD'
        ];
        $result = $this->model->createPrecioProceso($data);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
    public function testCreatePrecioProcesoConSalarioCero()
    {
        $data = [
            'tipo_proceso' => 'CLASIFICACION',
            'idproducto' => 1,
            'salario_unitario' => 0,
            'moneda' => 'USD'
        ];
        $result = $this->model->createPrecioProceso($data);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
    public function testCreatePrecioProcesoConTipoInvalido()
    {
        $data = [
            'tipo_proceso' => 'TIPO_INVALIDO',
            'idproducto' => 1,
            'salario_unitario' => 0.50,
            'moneda' => 'USD'
        ];
        $result = $this->model->createPrecioProceso($data);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->showMessage("Validación correcta: " . $result['message']);
    }
    public function testCreatePrecioProcesoConProductoInexistente()
    {
        $data = [
            'tipo_proceso' => 'CLASIFICACION',
            'idproducto' => 99999,
            'salario_unitario' => 0.50,
            'moneda' => 'USD'
        ];
        $result = $this->model->createPrecioProceso($data);
        $this->assertIsArray($result);
        if (isset($result['status'])) {
            $this->assertIsBool($result['status']);
        }
    }
    public function testCreatePrecioProcesoClasificacionConDatosCompletos()
    {
        $data = [
            'tipo_proceso' => 'CLASIFICACION',
            'idproducto' => 1,
            'salario_unitario' => 0.30,
            'unidad_base' => 'KG',
            'moneda' => 'USD'
        ];
        $result = $this->model->createPrecioProceso($data);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }
    public function testCreatePrecioProcesoEmpaqueConDatosCompletos()
    {
        $data = [
            'tipo_proceso' => 'EMPAQUE',
            'idproducto' => 2,
            'salario_unitario' => 5.00,
            'unidad_base' => 'UNIDAD',
            'moneda' => 'USD'
        ];
        $result = $this->model->createPrecioProceso($data);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }
    public function testUpdatePrecioProcesoConIdInexistente()
    {
        $data = [
            'salario_unitario' => 0.40
        ];
        $result = $this->model->updatePrecioProceso(99999, $data);
        $this->assertIsArray($result);
        if (isset($result['status'])) {
            $this->assertIsBool($result['status']);
        }
    }
    public function testUpdatePrecioProcesoSinCambios()
    {
        $data = [];
        $result = $this->model->updatePrecioProceso(1, $data);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
    public function testDeletePrecioProcesoConIdInexistente()
    {
        $result = $this->model->deletePrecioProceso(99999);
        $this->assertIsArray($result);
        if (isset($result['status'])) {
            $this->assertIsBool($result['status']);
        }
    }
    public function testMarcarRegistroComoPagadoConIdInexistente()
    {
        $result = $this->model->marcarRegistroComoPagado(99999);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
    public function testCancelarRegistroConIdInexistente()
    {
        $result = $this->model->cancelarRegistroProduccion(99999);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
