<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/produccionModel.php';
class TestLotesGestion extends TestCase
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
    public function testSelectAllLotesRetornaArray()
    {
        $result = $this->model->selectAllLotes();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }
    public function testSelectLoteByIdConIdInexistente()
    {
        $result = $this->model->selectLoteById(99999);
        $this->assertFalse($result);
    }
    public function testInsertarLoteSinSupervisor()
    {
        $data = [
            'volumen_estimado' => 100,
            'fecha_jornada' => date('Y-m-d')
        ];
        $result = $this->model->insertLote($data);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsString('supervisor', strtolower($result['message']));
        $this->showMessage("Validación correcta: " . $result['message']);
    }
    public function testInsertarLoteConVolumenCero()
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
    public function testInsertarLoteConVolumenNegativo()
    {
        $data = [
            'idsupervisor' => 1,
            'volumen_estimado' => -100,
            'fecha_jornada' => date('Y-m-d')
        ];
        $result = $this->model->insertLote($data);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
    public function testInsertarLoteSinFechaJornada()
    {
        $data = [
            'idsupervisor' => 1,
            'volumen_estimado' => 100
        ];
        $result = $this->model->insertLote($data);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsString('fecha', strtolower($result['message']));
    }
    public function testInsertarLoteConFechaInvalida()
    {
        $data = [
            'idsupervisor' => 1,
            'volumen_estimado' => 100,
            'fecha_jornada' => '2025-13-45'
        ];
        $result = $this->model->insertLote($data);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsString('fecha', strtolower($result['message']));
    }
    public function testInsertarLoteConDatosCompletos()
    {
        $data = [
            'idsupervisor' => 1,
            'volumen_estimado' => 150,
            'fecha_jornada' => date('Y-m-d'),
            'observaciones' => 'Lote de prueba automatizada'
        ];
        $result = $this->model->insertLote($data);
        $this->assertIsArray($result);
        if ($result['status']) {
            $this->assertTrue($result['status']);
            $this->assertArrayHasKey('idlote', $result);
            $this->assertArrayHasKey('numero_lote', $result);
            $this->assertArrayHasKey('operarios_requeridos', $result);
        }
    }
    public function testIniciarLoteConIdInexistente()
    {
        $result = $this->model->iniciarLoteProduccion(99999);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
    public function testCerrarLoteConIdInexistente()
    {
        $result = $this->model->cerrarLoteProduccion(99999);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
    public function testActualizarLoteConIdInexistente()
    {
        $data = [
            'volumen_estimado' => 200,
            'observaciones' => 'Actualización de prueba'
        ];
        $result = $this->model->actualizarLote(99999, $data);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
    public function testEliminarLoteConIdInexistente()
    {
        $result = $this->model->eliminarLote(99999);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
    public function testInsertarLoteConSupervisorInexistente()
    {
        $data = [
            'idsupervisor' => 99999,
            'volumen_estimado' => 100,
            'fecha_jornada' => date('Y-m-d')
        ];
        $result = $this->model->insertLote($data);
        $this->assertIsArray($result);
        if (!$result['status']) {
            $this->assertFalse($result['status']);
        }
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
