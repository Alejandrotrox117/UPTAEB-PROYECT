<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/tareaProduccionModel.php';

/**
 * Prueba de caja blanca para asignación exitosa de tareas de producción
 * Valida creación y asignación de tareas a empleados
 */
class TestTareaProduccionAsignacionExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new TareaProduccionModel();
    }

    public function testAsignarTareaConDatosCompletos()
    {
        $data = [
            'idproduccion' => 1,
            'idempleado' => 1,
            'cantidad_asignada' => 100,
            'fecha_inicio' => date('Y-m-d'),
            'estado' => 'pendiente',
            'observaciones' => 'Tarea de prueba'
        ];

        $result = $this->model->insertTarea($data);

        $this->assertIsBool($result);
    }

    public function testAsignarTareaSinObservaciones()
    {
        $data = [
            'idproduccion' => 1,
            'idempleado' => 1,
            'cantidad_asignada' => 50,
            'fecha_inicio' => date('Y-m-d'),
            'estado' => 'pendiente'
        ];

        $result = $this->model->insertTarea($data);

        $this->assertIsBool($result);
    }

    public function testAsignarVariasTareasAMismoEmpleado()
    {
        $data1 = [
            'idproduccion' => 1,
            'idempleado' => 1,
            'cantidad_asignada' => 30,
            'fecha_inicio' => date('Y-m-d'),
            'estado' => 'pendiente'
        ];

        $data2 = [
            'idproduccion' => 2,
            'idempleado' => 1,
            'cantidad_asignada' => 40,
            'fecha_inicio' => date('Y-m-d'),
            'estado' => 'pendiente'
        ];

        $result1 = $this->model->insertTarea($data1);
        $result2 = $this->model->insertTarea($data2);

        $this->assertIsBool($result1);
        $this->assertIsBool($result2);
    }

    public function testAsignarTareaConFechaEspecifica()
    {
        $data = [
            'idproduccion' => 1,
            'idempleado' => 1,
            'cantidad_asignada' => 75,
            'fecha_inicio' => date('Y-m-d', strtotime('+1 day')),
            'estado' => 'programada'
        ];

        $result = $this->model->insertTarea($data);

        $this->assertIsBool($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
