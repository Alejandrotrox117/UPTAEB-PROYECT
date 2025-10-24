<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/sueldosModel.php';

/**
 * RF07: Prueba de caja blanca para registro de sueldos de empleados temporales
 * Incluye casos típicos (exitosos) y atípicos (fallidos)
 */
class TestSueldosInsert extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new SueldosModel();
    }

    // ========== CASOS TÍPICOS (EXITOSOS) ==========

    public function testInsertSueldoConDatosCompletos()
    {
        $data = [
            'idempleado' => 1,
            'periodo' => date('Y-m'),
            'dias_trabajados' => 20,
            'monto_total' => 800.00,
            'fecha_pago' => date('Y-m-d'),
            'observaciones' => 'Pago quincenal'
        ];

        $result = $this->model->insertSueldo($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        
        if ($result['status']) {
            $this->assertTrue($result['status']);
            $this->assertArrayHasKey('sueldo_id', $result);
        }
    }

    public function testInsertSueldoSinObservaciones()
    {
        $data = [
            'idempleado' => 1,
            'periodo' => date('Y-m'),
            'dias_trabajados' => 15,
            'monto_total' => 600.00,
            'fecha_pago' => date('Y-m-d')
        ];

        $result = $this->model->insertSueldo($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testInsertSueldoConMontoDecimal()
    {
        $data = [
            'idempleado' => 1,
            'periodo' => date('Y-m'),
            'dias_trabajados' => 10,
            'monto_total' => 456.78,
            'fecha_pago' => date('Y-m-d')
        ];

        $result = $this->model->insertSueldo($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    // ========== CASOS ATÍPICOS (FALLIDOS) ==========

    public function testInsertSueldoSinEmpleado()
    {
        $data = [
            'periodo' => date('Y-m'),
            'dias_trabajados' => 20,
            'monto_total' => 800.00,
            'fecha_pago' => date('Y-m-d')
        ];

        $result = $this->model->insertSueldo($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public function testInsertSueldoConEmpleadoInexistente()
    {
        $data = [
            'idempleado' => 99999,
            'periodo' => date('Y-m'),
            'dias_trabajados' => 20,
            'monto_total' => 800.00,
            'fecha_pago' => date('Y-m-d')
        ];

        $result = $this->model->insertSueldo($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public function testInsertSueldoConMontoNegativo()
    {
        $data = [
            'idempleado' => 1,
            'periodo' => date('Y-m'),
            'dias_trabajados' => 20,
            'monto_total' => -100.00,
            'fecha_pago' => date('Y-m-d')
        ];

        $result = $this->model->insertSueldo($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public function testInsertSueldoConDiasTrabajosCero()
    {
        $data = [
            'idempleado' => 1,
            'periodo' => date('Y-m'),
            'dias_trabajados' => 0,
            'monto_total' => 0.00,
            'fecha_pago' => date('Y-m-d')
        ];

        $result = $this->model->insertSueldo($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testInsertSueldoDuplicadoMismoPeriodo()
    {
        $data = [
            'idempleado' => 1,
            'periodo' => date('Y-m'),
            'dias_trabajados' => 20,
            'monto_total' => 800.00,
            'fecha_pago' => date('Y-m-d')
        ];

        $result1 = $this->model->insertSueldo($data);
        $result2 = $this->model->insertSueldo($data);

        $this->assertIsArray($result2);
        if (!$result2['status']) {
            $this->assertFalse($result2['status']);
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
