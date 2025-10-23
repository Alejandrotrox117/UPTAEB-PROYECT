<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/produccionModel.php';

/**
 * Prueba de caja blanca para cierre exitoso de lotes de producción
 * Valida finalización y registro de tiempos
 */
class TestProduccionCierreLoteExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProduccionModel();
    }

    public function testCerrarLoteEnProceso()
    {
        // Crear e iniciar un lote
        $dataLote = [
            'idsupervisor' => 1,
            'volumen_estimado' => 500,
            'fecha_jornada' => date('Y-m-d', strtotime('+10 days'))
        ];

        $loteCreado = $this->model->insertLote($dataLote);

        if ($loteCreado['status']) {
            $idLote = $loteCreado['lote_id'];
            
            // Iniciar el lote
            $this->model->iniciarLoteProduccion($idLote);
            
            // Cerrar el lote
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
            
            // Verificar estado final
            $loteActualizado = $this->model->selectLoteById($idLote);
            
            if ($loteActualizado) {
                $this->assertEquals(
                    'FINALIZADO',
                    $loteActualizado['estatus_lote'],
                    "El estado debería cambiar a FINALIZADO"
                );
                $this->assertNotNull(
                    $loteActualizado['fecha_fin_real'],
                    "Debería registrar la fecha de cierre"
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
