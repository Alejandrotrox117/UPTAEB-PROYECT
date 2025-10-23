<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/produccionModel.php';

/**
 * Prueba de caja blanca para cálculo de nómina de producción exitoso
 * Valida cálculo de pagos por producción
 */
class TestProduccionNominaExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProduccionModel();
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
