<?php

use PHPUnit\Framework\TestCase;
use App\Models\ProduccionModel;

class TestProduccionCreacionLote extends TestCase
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
    public function testCrearLoteConDatosCompletos()
    {
        $data = [
            'idsupervisor' => 1,
            'volumen_estimado' => 1000,
            'fecha_jornada' => date('Y-m-d'),
            'observaciones' => 'Lote de prueba'
        ];
        $result = $this->model->insertLote($data);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        if ($result['status']) {
            $this->assertArrayHasKey('lote_id', $result);
            $this->assertArrayHasKey('numero_lote', $result);
            $this->assertArrayHasKey('operarios_requeridos', $result);
        }
    }
    public function testGeneracionNumeroLoteUnico()
    {
        $data1 = [
            'idsupervisor' => 1,
            'volumen_estimado' => 300,
            'fecha_jornada' => date('Y-m-d', strtotime('+3 days'))
        ];
        $data2 = [
            'idsupervisor' => 1,
            'volumen_estimado' => 400,
            'fecha_jornada' => date('Y-m-d', strtotime('+3 days'))
        ];
        $result1 = $this->model->insertLote($data1);
        $result2 = $this->model->insertLote($data2);
        if ($result1['status'] && $result2['status']) {
            $this->assertNotEquals(
                $result1['numero_lote'],
                $result2['numero_lote']
            );
        }
    }
    public function testCrearLoteSinSupervisor()
    {
        $data = [
            'volumen_estimado' => 1000,
            'fecha_jornada' => date('Y-m-d')
        ];
        $result = $this->model->insertLote($data);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsString('supervisor', strtolower($result['message']));
        $this->showMessage("Validación correcta: " . $result['message']);
    }
    public function testCrearLoteConVolumenCero()
    {
        $data = [
            'idsupervisor' => 1,
            'volumen_estimado' => 0,
            'fecha_jornada' => date('Y-m-d')
        ];
        $result = $this->model->insertLote($data);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsString('volumen', strtolower($result['message']));
        $this->showMessage("Validación correcta: " . $result['message']);
    }
    public function testCrearLoteConVolumenNegativo()
    {
        $data = [
            'idsupervisor' => 1,
            'volumen_estimado' => -500,
            'fecha_jornada' => date('Y-m-d')
        ];
        $result = $this->model->insertLote($data);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
    public function testCrearLoteSinFechaJornada()
    {
        $data = [
            'idsupervisor' => 1,
            'volumen_estimado' => 1000
        ];
        $result = $this->model->insertLote($data);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsString('fecha', strtolower($result['message']));
        $this->showMessage("Validación correcta: " . $result['message']);
    }
    public function testCrearLoteConFechaInvalida()
    {
        $data = [
            'idsupervisor' => 1,
            'volumen_estimado' => 1000,
            'fecha_jornada' => '2024-13-45'
        ];
        $result = $this->model->insertLote($data);
        $this->assertFalse($result['status']);
        $this->assertStringContainsString('fecha', strtolower($result['message']));
        $this->showMessage("Validación correcta: " . $result['message']);
    }
    public function testCrearLoteExcedeCapacidadMaxima()
    {
        $data = [
            'idsupervisor' => 1,
            'volumen_estimado' => 999999,
            'fecha_jornada' => date('Y-m-d')
        ];
        $result = $this->model->insertLote($data);
        if (!$result['status']) {
            $this->assertStringContainsString(
                'capacidad',
                strtolower($result['message'])
            );
            $this->showMessage("Validación correcta: " . $result['message']);
        }
    }
    public function testCrearLoteConSupervisorInexistente()
    {
        $data = [
            'idsupervisor' => 99999,
            'volumen_estimado' => 500,
            'fecha_jornada' => date('Y-m-d')
        ];
        $result = $this->model->insertLote($data);
        $this->assertIsArray($result);
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
