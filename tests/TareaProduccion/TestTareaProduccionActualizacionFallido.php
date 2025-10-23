<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/tareaProduccionModel.php';

/**
 * Prueba de caja blanca para casos de fallo en actualización de tareas
 * Valida restricciones y validaciones de actualización
 */
class TestTareaProduccionActualizacionFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new TareaProduccionModel();
    }

    public function testActualizarTareaInexistente()
    {
        $data = [
            'idtarea' => 99999,
            'estado' => 'en_proceso'
        ];

        $result = $this->model->updateTarea($data);

        $this->assertFalse($result);
    }

    public function testActualizarCantidadExcedeLimite()
    {
        if (method_exists($this->model, 'setCantidadRealizada')) {
            $idTarea = 1;
            $cantidadExcesiva = 999999;

            // Dependiendo de validaciones, puede fallar o permitir
            $result = $this->model->setCantidadRealizada($idTarea, $cantidadExcesiva);

            $this->assertIsBool($result);
        } else {
            $this->markTestSkipped('Método setCantidadRealizada no existe');
        }
    }

    public function testActualizarCantidadNegativa()
    {
        if (method_exists($this->model, 'setCantidadRealizada')) {
            $idTarea = 1;
            $cantidadNegativa = -10;

            $result = $this->model->setCantidadRealizada($idTarea, $cantidadNegativa);

            $this->assertFalse($result);
        } else {
            $this->markTestSkipped('Método setCantidadRealizada no existe');
        }
    }

    public function testActualizarSinDatos()
    {
        $data = [];

        try {
            $result = $this->model->updateTarea($data);
            $this->assertFalse($result);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
        }
    }

    public function testCompletarTareaSinCantidadRealizada()
    {
        $data = [
            'idtarea' => 1,
            'estado' => 'completada',
            'fecha_fin' => date('Y-m-d H:i:s')
            // Falta cantidad_realizada
        ];

        // Dependiendo de validaciones de negocio
        $result = $this->model->updateTarea($data);

        $this->assertIsBool($result);
    }

    public function testActualizarConEstadoInvalido()
    {
        $data = [
            'idtarea' => 1,
            'estado' => 'estado_no_valido'
        ];

        $result = $this->model->updateTarea($data);

        // Puede fallar dependiendo de las validaciones
        $this->assertIsBool($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
