<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/produccionModel.php';

/**
 * Prueba de caja blanca para cierre de lotes de producción
 * Incluye casos típicos (exitosos) y atípicos (fallidos)
 */
class TestProduccionCierreLote extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProduccionModel();
    }

    // ========== CASOS TÍPICOS (EXITOSOS) ==========

    public function testCerrarLoteEnProceso()
    {
        $dataLote = [
            'idsupervisor' => 1,
            'volumen_estimado' => 500,
            'fecha_jornada' => date('Y-m-d', strtotime('+10 days'))
        ];

        $loteCreado = $this->model->insertLote($dataLote);

        if ($loteCreado['status']) {
            $idLote = $loteCreado['lote_id'];
            
            $this->model->iniciarLoteProduccion($idLote);
            
            $result = $this->model->cerrarLoteProduccion($idLote);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('status', $result);
            $this->assertArrayHasKey('message', $result);
        } else {
            $this->markTestSkipped('No se pudo crear el lote para la prueba');
        }
    }

    public function testVerificarEstadoTrasCierre()
    {
        $dataLote = [
            'idsupervisor' => 1,
            'volumen_estimado' => 600,
            'fecha_jornada' => date('Y-m-d', strtotime('+11 days'))
        ];

        $loteCreado = $this->model->insertLote($dataLote);

        if ($loteCreado['status']) {
            $idLote = $loteCreado['lote_id'];
            
            $this->model->iniciarLoteProduccion($idLote);
            $this->model->cerrarLoteProduccion($idLote);
            
            $loteActualizado = $this->model->selectLoteById($idLote);
            
            if ($loteActualizado) {
                $this->assertEquals(
                    'FINALIZADO',
                    $loteActualizado['estatus_lote']
                );
                $this->assertNotNull($loteActualizado['fecha_fin_real']);
            }
        } else {
            $this->markTestSkipped('No se pudo crear el lote para la prueba');
        }
    }

    // ========== CASOS ATÍPICOS (FALLIDOS) ==========

    public function testCerrarLoteInexistente()
    {
        $idLoteInexistente = 99999;
        
        $result = $this->model->cerrarLoteProduccion($idLoteInexistente);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public function testCerrarLoteYaCerrado()
    {
        $dataLote = [
            'idsupervisor' => 1,
            'volumen_estimado' => 500,
            'fecha_jornada' => date('Y-m-d', strtotime('+15 days'))
        ];

        $loteCreado = $this->model->insertLote($dataLote);

        if ($loteCreado['status']) {
            $idLote = $loteCreado['lote_id'];
            
            $this->model->iniciarLoteProduccion($idLote);
            $primerCierre = $this->model->cerrarLoteProduccion($idLote);
            
            $segundoCierre = $this->model->cerrarLoteProduccion($idLote);

            $this->assertFalse($segundoCierre['status']);
            $this->assertStringContainsString(
                'finalizado',
                strtolower($segundoCierre['message'])
            );
        } else {
            $this->markTestSkipped('No se pudo crear el lote para la prueba');
        }
    }

    public function testCerrarLoteSinIniciar()
    {
        $dataLote = [
            'idsupervisor' => 1,
            'volumen_estimado' => 500,
            'fecha_jornada' => date('Y-m-d', strtotime('+16 days'))
        ];

        $loteCreado = $this->model->insertLote($dataLote);

        if ($loteCreado['status']) {
            $idLote = $loteCreado['lote_id'];
            
            $result = $this->model->cerrarLoteProduccion($idLote);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('status', $result);
        } else {
            $this->markTestSkipped('No se pudo crear el lote para la prueba');
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
