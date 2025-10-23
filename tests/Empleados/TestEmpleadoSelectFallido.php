<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/empleadosModel.php';

/**
 * Prueba de caja blanca para casos de fallo en consultas de empleados
 * Valida manejo de datos inexistentes
 */
class TestEmpleadoSelectFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new EmpleadosModel();
    }

    public function testObtenerEmpleadoInexistente()
    {
        if (method_exists($this->model, 'getEmpleadoById')) {
            $idInexistente = 99999;
            $result = $this->model->getEmpleadoById($idInexistente);

            $this->assertFalse($result);
        } else {
            $this->markTestSkipped('Método getEmpleadoById no existe');
        }
    }

    public function testBuscarEmpleadoPorIdentificacionInexistente()
    {
        if (method_exists($this->model, 'getEmpleadoByIdentificacion')) {
            $identificacionInexistente = '00000000';
            $result = $this->model->getEmpleadoByIdentificacion($identificacionInexistente);

            $this->assertTrue(
                $result === false || (is_array($result) && empty($result)),
                "Debería retornar false o array vacío para identificación inexistente"
            );
        } else {
            $this->markTestSkipped('Método getEmpleadoByIdentificacion no existe');
        }
    }

    public function testObtenerEmpleadoConIdNegativo()
    {
        if (method_exists($this->model, 'getEmpleadoById')) {
            $result = $this->model->getEmpleadoById(-1);

            $this->assertFalse($result);
        } else {
            $this->markTestSkipped('Método getEmpleadoById no existe');
        }
    }

    public function testObtenerEmpleadoConIdCero()
    {
        if (method_exists($this->model, 'getEmpleadoById')) {
            $result = $this->model->getEmpleadoById(0);

            $this->assertFalse($result);
        } else {
            $this->markTestSkipped('Método getEmpleadoById no existe');
        }
    }

    public function testBuscarConIdentificacionVacia()
    {
        if (method_exists($this->model, 'getEmpleadoByIdentificacion')) {
            $result = $this->model->getEmpleadoByIdentificacion('');

            $this->assertTrue(
                $result === false || (is_array($result) && empty($result)),
                "Debería retornar false o array vacío para identificación vacía"
            );
        } else {
            $this->markTestSkipped('Método getEmpleadoByIdentificacion no existe');
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
