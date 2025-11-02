<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/produccionModel.php';





class TestProduccionOperaciones extends TestCase
{
    private $model;

    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }

    protected function setUp(): void
    {
        $this->model = new ProduccionModel();
    }

    

    public function testRegistrarProduccionDiariaLote()
    {
        if (method_exists($this->model, 'registrarProduccionDiariaLote')) {
            $idLote = 1;
            $registros = [
                [
                    'idempleado' => 1,
                    'cantidad_producida' => 100,
                    'fecha_registro' => date('Y-m-d'),
                    'observaciones' => 'Producción normal'
                ],
                [
                    'idempleado' => 2,
                    'cantidad_producida' => 80,
                    'fecha_registro' => date('Y-m-d'),
                    'observaciones' => 'Producción normal'
                ]
            ];

            $result = $this->model->registrarProduccionDiariaLote($idLote, $registros);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('status', $result);
        } else {
            $this->markTestSkipped('Método registrarProduccionDiariaLote no existe');
        }
    }

    public function testObtenerRegistrosPorLote()
    {
        if (method_exists($this->model, 'obtenerRegistrosPorLote')) {
            $idLote = 1;
            
            $result = $this->model->obtenerRegistrosPorLote($idLote);

            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método obtenerRegistrosPorLote no existe');
        }
    }

    

    public function testCalcularNominaProduccion()
    {
        if (method_exists($this->model, 'calcularNominaProduccion')) {
            $fechaInicio = date('Y-m-01');
            $fechaFin = date('Y-m-d');

            $result = $this->model->calcularNominaProduccion($fechaInicio, $fechaFin);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('status', $result);
        } else {
            $this->markTestSkipped('Método calcularNominaProduccion no existe');
        }
    }

    public function testRegistrarSolicitudPago()
    {
        if (method_exists($this->model, 'registrarSolicitudPago')) {
            $registros = [
                [
                    'idempleado' => 1,
                    'monto' => 500.00,
                    'concepto' => 'Pago por producción',
                    'periodo' => date('Y-m')
                ]
            ];

            $result = $this->model->registrarSolicitudPago($registros);

            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método registrarSolicitudPago no existe');
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
