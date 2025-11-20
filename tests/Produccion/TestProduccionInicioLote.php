<?php

use PHPUnit\Framework\TestCase;
use App\Models\ProduccionModel;
class TestProduccionInicioLote extends TestCase
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
    public function testIniciarLotePlanificado()
    {
        $dataLote = [
            'idsupervisor' => 1,
            'volumen_estimado' => 500,
            'fecha_jornada' => date('Y-m-d')
        ];
        $loteCreado = $this->model->insertLote($dataLote);
        if ($loteCreado['status']) {
            $idLote = $loteCreado['lote_id'];
            $result = $this->model->iniciarLoteProduccion($idLote);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('status', $result);
            $this->assertArrayHasKey('message', $result);
        } else {
            $this->markTestSkipped('No se pudo crear el lote para la prueba');
        }
    }
    public function testVerificarEstadoTrasInicio()
    {
        $dataLote = [
            'idsupervisor' => 1,
            'volumen_estimado' => 600,
            'fecha_jornada' => date('Y-m-d', strtotime('+1 day'))
        ];
        $loteCreado = $this->model->insertLote($dataLote);
        if ($loteCreado['status']) {
            $idLote = $loteCreado['lote_id'];
            $this->model->iniciarLoteProduccion($idLote);
            $loteActualizado = $this->model->selectLoteById($idLote);
            if ($loteActualizado) {
                $this->assertEquals(
                    'EN_PROCESO',
                    $loteActualizado['estatus_lote']
                );
                $this->assertNotNull($loteActualizado['fecha_inicio_real']);
            }
        } else {
            $this->markTestSkipped('No se pudo crear el lote para la prueba');
        }
    }
    public function testIniciarLoteInexistente()
    {
        $idLoteInexistente = 99999;
        $result = $this->model->iniciarLoteProduccion($idLoteInexistente);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        if (array_key_exists('message', $result)) {
            $this->showMessage("Error esperado: " . $result['message']);
        }
    }
    public function testIniciarLoteYaIniciado()
    {
        $dataLote = [
            'idsupervisor' => 1,
            'volumen_estimado' => 500,
            'fecha_jornada' => date('Y-m-d', strtotime('+5 days'))
        ];
        $loteCreado = $this->model->insertLote($dataLote);
        if ($loteCreado['status']) {
            $idLote = $loteCreado['lote_id'];
            $primerInicio = $this->model->iniciarLoteProduccion($idLote);
            $segundoInicio = $this->model->iniciarLoteProduccion($idLote);
            $this->assertFalse($segundoInicio['status']);
            if (array_key_exists('message', $segundoInicio)) {
                $this->showMessage("Error esperado (doble inicio): " . $segundoInicio['message']);
            }
        } else {
            $this->markTestSkipped('No se pudo crear el lote para la prueba');
        }
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
