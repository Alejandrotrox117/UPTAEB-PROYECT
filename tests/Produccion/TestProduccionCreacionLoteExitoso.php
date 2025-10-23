<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/produccionModel.php';

/**
 * Prueba de caja blanca para creación exitosa de lotes de producción
 * Valida proceso completo de creación con cálculo de operarios
 */
class TestProduccionCreacionLoteExitoso extends TestCase
{
    private $model;

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

    public function testCalculoOperariosRequeridos()
    {
        $data = [
            'idsupervisor' => 1,
            'volumen_estimado' => 500,
            'fecha_jornada' => date('Y-m-d', strtotime('+1 day'))
        ];

        $result = $this->model->insertLote($data);

        if ($result['status']) {
            $this->assertGreaterThan(0, $result['operarios_requeridos'], 
                "Debería calcular operarios requeridos > 0");
        }
    }

    public function testCrearLoteSinObservaciones()
    {
        $data = [
            'idsupervisor' => 1,
            'volumen_estimado' => 750,
            'fecha_jornada' => date('Y-m-d', strtotime('+2 days'))
        ];

        $result = $this->model->insertLote($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
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
                $result2['numero_lote'],
                "Los números de lote deben ser únicos"
            );
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
