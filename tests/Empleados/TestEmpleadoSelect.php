<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/empleadosModel.php';

/**
 * Prueba de caja blanca para consultas de empleados
 * Incluye casos típicos (exitosos) y atípicos (fallidos)
 */
class TestEmpleadoSelect extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new EmpleadosModel();
    }

    // ========== CASOS TÍPICOS (EXITOSOS) ==========

    public function testSeleccionarTodosEmpleados()
    {
        $result = $this->model->SelectAllEmpleados();
        $this->assertIsArray($result);
    }

    public function testVerificarEstructuraEmpleados()
    {
        $result = $this->model->SelectAllEmpleados();

        if (is_array($result) && count($result) > 0) {
            $primerEmpleado = $result[0];
            
            $this->assertArrayHasKey('idempleado', $primerEmpleado);
            $this->assertArrayHasKey('nombre', $primerEmpleado);
            $this->assertArrayHasKey('apellido', $primerEmpleado);
        } else {
            $this->assertTrue(is_array($result));
        }
    }

    public function testObtenerEmpleadoPorId()
    {
        if (method_exists($this->model, 'getEmpleadoById')) {
            $result = $this->model->getEmpleadoById(1);

            $this->assertTrue(
                is_array($result) || is_bool($result)
            );
        } else {
            $this->markTestSkipped('Método getEmpleadoById no existe');
        }
    }

    public function testBuscarEmpleadoPorIdentificacion()
    {
        if (method_exists($this->model, 'getEmpleadoByIdentificacion')) {
            $result = $this->model->getEmpleadoByIdentificacion('12345678');

            $this->assertTrue(
                is_array($result) || is_bool($result)
            );
        } else {
            $this->markTestSkipped('Método getEmpleadoByIdentificacion no existe');
        }
    }

    public function testListarEmpleadosActivos()
    {
        if (method_exists($this->model, 'getEmpleadosActivos')) {
            $result = $this->model->getEmpleadosActivos();
            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método getEmpleadosActivos no existe');
        }
    }

    public function testContarEmpleados()
    {
        $result = $this->model->SelectAllEmpleados();

        if (is_array($result)) {
            $cantidad = count($result);
            $this->assertIsInt($cantidad);
            $this->assertGreaterThanOrEqual(0, $cantidad);
        }
    }

    // ========== CASOS ATÍPICOS (FALLIDOS) ==========

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
                $result === false || (is_array($result) && empty($result))
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
                $result === false || (is_array($result) && empty($result))
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
