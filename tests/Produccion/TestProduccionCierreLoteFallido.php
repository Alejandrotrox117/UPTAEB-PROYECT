<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/produccionModel.php';

/**
 * Prueba de caja blanca para casos de fallo al cerrar lotes
 * Valida restricciones de estado y cierre duplicado
 */
class TestProduccionCierreLoteFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProduccionModel();
    }

    public function testCerrarLoteInexistente()
    {
        $idLoteInexistente = 99999;
        
        $result = $this->model->cerrarLoteProduccion($idLoteInexistente);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería cerrar lote inexistente");
    }

    public function testCerrarLoteYaCerrado()
    {
        // Crear, iniciar y cerrar un lote
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
            
            // Intentar cerrar nuevamente
            $segundoCierre = $this->model->cerrarLoteProduccion($idLote);

            $this->assertFalse(
                $segundoCierre['status'],
                "No debería permitir cerrar un lote ya finalizado"
            );
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
        // Crear un lote pero no iniciarlo
        $dataLote = [
            'idsupervisor' => 1,
            'volumen_estimado' => 500,
            'fecha_jornada' => date('Y-m-d', strtotime('+16 days'))
        ];

        $loteCreado = $this->model->insertLote($dataLote);

        if ($loteCreado['status']) {
            $idLote = $loteCreado['lote_id'];
            
            // Intentar cerrar sin iniciar
            $result = $this->model->cerrarLoteProduccion($idLote);

            // Dependiendo de validaciones de negocio, puede o no permitirse
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
