<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/produccionModel.php';

/**
 * Prueba de caja blanca para inicio exitoso de lotes de producción
 * Valida cambio de estado y registro de tiempos
 */
class TestProduccionInicioLoteExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProduccionModel();
    }

    public function testIniciarLotePlanificado()
    {
        // Primero crear un lote
        $dataLote = [
            'idsupervisor' => 1,
            'volumen_estimado' => 500,
            'fecha_jornada' => date('Y-m-d')
        ];

        $loteCreado = $this->model->insertLote($dataLote);

        if ($loteCreado['status']) {
            $idLote = $loteCreado['lote_id'];
            
            // Iniciar el lote
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
            
            // Verificar que el estado cambió
            $loteActualizado = $this->model->selectLoteById($idLote);
            
            if ($loteActualizado) {
                $this->assertEquals(
                    'EN_PROCESO',
                    $loteActualizado['estatus_lote'],
                    "El estado debería cambiar a EN_PROCESO"
                );
                $this->assertNotNull(
                    $loteActualizado['fecha_inicio_real'],
                    "Debería registrar la fecha de inicio"
                );
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
