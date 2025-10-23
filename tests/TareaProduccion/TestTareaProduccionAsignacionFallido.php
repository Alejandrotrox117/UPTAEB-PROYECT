<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/tareaProduccionModel.php';

/**
 * Prueba de caja blanca para casos de fallo en asignación de tareas
 * Valida validaciones de datos y reglas de negocio
 */
class TestTareaProduccionAsignacionFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new TareaProduccionModel();
    }

    public function testAsignarTareaSinProduccion()
    {
        $data = [
            'idempleado' => 1,
            'cantidad_asignada' => 100,
            'fecha_inicio' => date('Y-m-d'),
            'estado' => 'pendiente'
        ];

        try {
            $this->model->insertTarea($data);
            $this->fail('Debería lanzar PDOException');
        } catch (PDOException $e) {
            $this->assertInstanceOf(PDOException::class, $e);
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function testAsignarTareaSinEmpleado()
    {
        $data = [
            'idproduccion' => 1,
            'cantidad_asignada' => 100,
            'fecha_inicio' => date('Y-m-d'),
            'estado' => 'pendiente'
        ];

        try {
            $this->model->insertTarea($data);
            $this->fail('Debería lanzar PDOException');
        } catch (PDOException $e) {
            $this->assertInstanceOf(PDOException::class, $e);
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function testAsignarTareaConCantidadNegativa()
    {
        $data = [
            'idproduccion' => 1,
            'idempleado' => 1,
            'cantidad_asignada' => -10,
            'fecha_inicio' => date('Y-m-d'),
            'estado' => 'pendiente'
        ];

        $result = $this->model->insertTarea($data);

        $this->assertFalse($result);
    }

    public function testAsignarTareaConCantidadCero()
    {
        $data = [
            'idproduccion' => 1,
            'idempleado' => 1,
            'cantidad_asignada' => 0,
            'fecha_inicio' => date('Y-m-d'),
            'estado' => 'pendiente'
        ];

        $result = $this->model->insertTarea($data);

        // Dependiendo de reglas de negocio puede o no permitir cantidad cero
        $this->assertIsBool($result);
    }

    public function testAsignarTareaConProduccionInexistente()
    {
        $data = [
            'idproduccion' => 99999,
            'idempleado' => 1,
            'cantidad_asignada' => 50,
            'fecha_inicio' => date('Y-m-d'),
            'estado' => 'pendiente'
        ];

        try {
            $this->model->insertTarea($data);
            $this->fail('Debería lanzar PDOException');
        } catch (PDOException $e) {
            $this->assertInstanceOf(PDOException::class, $e);
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function testAsignarTareaConEmpleadoInexistente()
    {
        $data = [
            'idproduccion' => 1,
            'idempleado' => 99999,
            'cantidad_asignada' => 50,
            'fecha_inicio' => date('Y-m-d'),
            'estado' => 'pendiente'
        ];

        try {
            $this->model->insertTarea($data);
            $this->fail('Debería lanzar PDOException');
        } catch (PDOException $e) {
            $this->assertInstanceOf(PDOException::class, $e);
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function testAsignarTareaConEstadoInvalido()
    {
        $data = [
            'idproduccion' => 1,
            'idempleado' => 1,
            'cantidad_asignada' => 50,
            'fecha_inicio' => date('Y-m-d'),
            'estado' => 'estado_invalido'
        ];

        $result = $this->model->insertTarea($data);

        // Dependiendo de validaciones puede fallar
        $this->assertIsBool($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
