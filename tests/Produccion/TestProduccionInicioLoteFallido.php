<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/produccionModel.php';

/**
 * Prueba de caja blanca para casos de fallo al iniciar lotes
 * Valida restricciones de estado y existencia
 */
class TestProduccionInicioLoteFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProduccionModel();
    }

    public function testIniciarLoteInexistente()
    {
        $idLoteInexistente = 99999;
        
        $result = $this->model->iniciarLoteProduccion($idLoteInexistente);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería iniciar lote inexistente");
    }

    public function testIniciarLoteYaIniciado()
    {
        // Crear y luego iniciar un lote
        $dataLote = [
            'idsupervisor' => 1,
            'volumen_estimado' => 500,
            'fecha_jornada' => date('Y-m-d', strtotime('+5 days'))
        ];

        $loteCreado = $this->model->insertLote($dataLote);

        if ($loteCreado['status']) {
            $idLote = $loteCreado['lote_id'];
            
            // Iniciar primera vez
            $primerInicio = $this->model->iniciarLoteProduccion($idLote);
            
            // Intentar iniciar nuevamente
            $segundoInicio = $this->model->iniciarLoteProduccion($idLote);

            $this->assertFalse(
                $segundoInicio['status'],
                "No debería permitir iniciar un lote que ya está en proceso"
            );
        } else {
            $this->markTestSkipped('No se pudo crear el lote para la prueba');
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
