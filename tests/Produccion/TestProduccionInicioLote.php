<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/produccionModel.php';

/**
 * Prueba de caja blanca para inicio de lotes de producción
 * Incluye casos típicos (exitosos) y atípicos (fallidos)
 */
class TestProduccionInicioLote extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProduccionModel();
    }

    // ========== CASOS TÍPICOS (EXITOSOS) ==========

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

    // ========== CASOS ATÍPICOS (FALLIDOS) ==========

    public function testIniciarLoteInexistente()
    {
        $idLoteInexistente = 99999;
        
        $result = $this->model->iniciarLoteProduccion($idLoteInexistente);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
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
        } else {
            $this->markTestSkipped('No se pudo crear el lote para la prueba');
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
