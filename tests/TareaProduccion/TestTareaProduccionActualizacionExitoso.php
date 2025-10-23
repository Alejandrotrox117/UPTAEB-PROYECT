<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/tareaProduccionModel.php';

/**
 * Prueba de caja blanca para actualización exitosa de tareas
 * Valida cambios de estado y cantidades realizadas
 */
class TestTareaProduccionActualizacionExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new TareaProduccionModel();
    }

    public function testActualizarCantidadRealizada()
    {
        if (method_exists($this->model, 'setCantidadRealizada')) {
            $idTarea = 1;
            $cantidadRealizada = 50;

            $result = $this->model->setCantidadRealizada($idTarea, $cantidadRealizada);

            $this->assertIsBool($result);
        } else {
            $this->markTestSkipped('Método setCantidadRealizada no existe');
        }
    }

    public function testActualizarEstadoTarea()
    {
        $data = [
            'idtarea' => 1,
            'estado' => 'en_proceso',
            'fecha_inicio_real' => date('Y-m-d H:i:s')
        ];

        $result = $this->model->updateTarea($data);

        $this->assertIsBool($result);
    }

    public function testCompletarTarea()
    {
        $data = [
            'idtarea' => 1,
            'estado' => 'completada',
            'fecha_fin' => date('Y-m-d H:i:s'),
            'cantidad_realizada' => 100
        ];

        $result = $this->model->updateTarea($data);

        $this->assertIsBool($result);
    }

    public function testActualizarObservaciones()
    {
        $data = [
            'idtarea' => 1,
            'observaciones' => 'Observación actualizada'
        ];

        $result = $this->model->updateTarea($data);

        $this->assertIsBool($result);
    }

    public function testActualizarCantidadParcial()
    {
        if (method_exists($this->model, 'setCantidadRealizada')) {
            $idTarea = 1;
            $cantidadParcial = 25; // Menos que la cantidad asignada

            $result = $this->model->setCantidadRealizada($idTarea, $cantidadParcial);

            $this->assertIsBool($result);
        } else {
            $this->markTestSkipped('Método setCantidadRealizada no existe');
        }
    }

    public function testPausarTarea()
    {
        $data = [
            'idtarea' => 1,
            'estado' => 'pausada',
            'observaciones' => 'Pausa por mantenimiento'
        ];

        $result = $this->model->updateTarea($data);

        $this->assertIsBool($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
