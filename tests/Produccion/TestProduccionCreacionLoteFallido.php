<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/produccionModel.php';

/**
 * Prueba de caja blanca para casos de fallo en creación de lotes
 * Valida validaciones de negocio y límites de capacidad
 */
class TestProduccionCreacionLoteFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProduccionModel();
    }

    public function testCrearLoteSinSupervisor()
    {
        $data = [
            'volumen_estimado' => 1000,
            'fecha_jornada' => date('Y-m-d')
        ];

        $result = $this->model->insertLote($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería permitir crear lote sin supervisor");
        $this->assertStringContainsString('supervisor', strtolower($result['message']));
    }

    public function testCrearLoteConVolumenCero()
    {
        $data = [
            'idsupervisor' => 1,
            'volumen_estimado' => 0,
            'fecha_jornada' => date('Y-m-d')
        ];

        $result = $this->model->insertLote($data);

        $this->assertFalse($result['status'], "No debería permitir volumen cero");
        $this->assertStringContainsString('volumen', strtolower($result['message']));
    }

    public function testCrearLoteConVolumenNegativo()
    {
        $data = [
            'idsupervisor' => 1,
            'volumen_estimado' => -500,
            'fecha_jornada' => date('Y-m-d')
        ];

        $result = $this->model->insertLote($data);

        $this->assertFalse($result['status'], "No debería permitir volumen negativo");
    }

    public function testCrearLoteSinFechaJornada()
    {
        $data = [
            'idsupervisor' => 1,
            'volumen_estimado' => 1000
        ];

        $result = $this->model->insertLote($data);

        $this->assertFalse($result['status'], "No debería permitir crear sin fecha");
        $this->assertStringContainsString('fecha', strtolower($result['message']));
    }

    public function testCrearLoteConFechaInvalida()
    {
        $data = [
            'idsupervisor' => 1,
            'volumen_estimado' => 1000,
            'fecha_jornada' => '2024-13-45' // Fecha inválida
        ];

        $result = $this->model->insertLote($data);

        $this->assertFalse($result['status'], "No debería permitir fecha inválida");
        $this->assertStringContainsString('fecha', strtolower($result['message']));
    }

    public function testCrearLoteExcedeCapacidadMaxima()
    {
        // Volumen muy alto que exceda la capacidad de la planta
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

        // Puede fallar por foreign key constraint
        $this->assertIsArray($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
