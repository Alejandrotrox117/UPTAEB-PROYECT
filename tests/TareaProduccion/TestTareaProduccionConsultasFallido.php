<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/tareaProduccionModel.php';

/**
 * Prueba de caja blanca para casos de fallo en consultas de tareas
 * Valida manejo de datos inexistentes
 */
class TestTareaProduccionConsultasFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new TareaProduccionModel();
    }

    public function testObtenerTareaInexistente()
    {
        if (method_exists($this->model, 'getIdTarea')) {
            $idInexistente = 99999;
            $result = $this->model->getIdTarea($idInexistente);

            $this->assertFalse($result);
        } else {
            $this->markTestSkipped('Método getIdTarea no existe');
        }
    }

    public function testObtenerTareasEmpleadoInexistente()
    {
        if (method_exists($this->model, 'getTareasByEmpleado')) {
            $idEmpleadoInexistente = 99999;
            $result = $this->model->getTareasByEmpleado($idEmpleadoInexistente);

            $this->assertTrue(
                is_array($result) && empty($result),
                "Debería retornar array vacío para empleado sin tareas"
            );
        } else {
            $this->markTestSkipped('Método getTareasByEmpleado no existe');
        }
    }

    public function testObtenerTareasProduccionInexistente()
    {
        if (method_exists($this->model, 'getTareasByProduccion')) {
            $idProduccionInexistente = 99999;
            $result = $this->model->getTareasByProduccion($idProduccionInexistente);

            $this->assertTrue(
                is_array($result) && empty($result),
                "Debería retornar array vacío para producción sin tareas"
            );
        } else {
            $this->markTestSkipped('Método getTareasByProduccion no existe');
        }
    }

    public function testObtenerTareasEstadoInvalido()
    {
        if (method_exists($this->model, 'getTareasByEstado')) {
            $estadoInvalido = 'estado_no_existe';
            $result = $this->model->getTareasByEstado($estadoInvalido);

            $this->assertTrue(
                is_array($result) && empty($result),
                "Debería retornar array vacío para estado inválido"
            );
        } else {
            $this->markTestSkipped('Método getTareasByEstado no existe');
        }
    }

    public function testObtenerProgresoTareaInexistente()
    {
        if (method_exists($this->model, 'getProgreso')) {
            $idInexistente = 99999;
            $progreso = $this->model->getProgreso($idInexistente);

            $this->assertTrue(
                $progreso === false || $progreso === 0,
                "Debería retornar false o 0 para tarea inexistente"
            );
        } else {
            $this->markTestSkipped('Método getProgreso no existe');
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
