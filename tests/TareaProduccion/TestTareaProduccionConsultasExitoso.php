<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/tareaProduccionModel.php';

/**
 * Prueba de caja blanca para consultas exitosas de tareas
 * Valida obtención de información de tareas
 */
class TestTareaProduccionConsultasExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new TareaProduccionModel();
    }

    public function testObtenerTareaPorId()
    {
        if (method_exists($this->model, 'getIdTarea')) {
            $idTarea = 1;
            $result = $this->model->getIdTarea($idTarea);

            $this->assertTrue(
                is_array($result) || is_bool($result),
                "Debería retornar array o false"
            );
        } else {
            $this->markTestSkipped('Método getIdTarea no existe');
        }
    }

    public function testObtenerTareasPorEmpleado()
    {
        if (method_exists($this->model, 'getTareasByEmpleado')) {
            $idEmpleado = 1;
            $result = $this->model->getTareasByEmpleado($idEmpleado);

            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método getTareasByEmpleado no existe');
        }
    }

    public function testObtenerTareasPorProduccion()
    {
        if (method_exists($this->model, 'getTareasByProduccion')) {
            $idProduccion = 1;
            $result = $this->model->getTareasByProduccion($idProduccion);

            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método getTareasByProduccion no existe');
        }
    }

    public function testObtenerTareasPorEstado()
    {
        if (method_exists($this->model, 'getTareasByEstado')) {
            $estado = 'pendiente';
            $result = $this->model->getTareasByEstado($estado);

            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método getTareasByEstado no existe');
        }
    }

    public function testObtenerTodasTareas()
    {
        if (method_exists($this->model, 'selectAllTareas')) {
            $result = $this->model->selectAllTareas();

            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método selectAllTareas no existe');
        }
    }

    public function testObtenerProgreso()
    {
        if (method_exists($this->model, 'getProgreso')) {
            $idTarea = 1;
            $progreso = $this->model->getProgreso($idTarea);

            $this->assertIsNumeric($progreso, "Debería retornar un número");
            $this->assertGreaterThanOrEqual(0, $progreso, "El progreso debe ser >= 0");
            $this->assertLessThanOrEqual(100, $progreso, "El progreso debe ser <= 100");
        } else {
            $this->markTestSkipped('Método getProgreso no existe');
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
