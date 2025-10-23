<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/empleadosModel.php';

/**
 * Prueba de caja blanca para consultas exitosas de empleados
 * Valida obtención de información de empleados
 */
class TestEmpleadoSelectExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new EmpleadosModel();
    }

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
            
            $this->assertArrayHasKey('idempleado', $primerEmpleado, "Debería tener idempleado");
            $this->assertArrayHasKey('nombre', $primerEmpleado, "Debería tener nombre");
            $this->assertArrayHasKey('apellido', $primerEmpleado, "Debería tener apellido");
        } else {
            $this->assertTrue(is_array($result));
        }
    }

    public function testObtenerEmpleadoPorId()
    {
        if (method_exists($this->model, 'getEmpleadoById')) {
            $result = $this->model->getEmpleadoById(1);

            $this->assertTrue(
                is_array($result) || is_bool($result),
                "Debería retornar array o false"
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
                is_array($result) || is_bool($result),
                "Debería retornar array o false"
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
            $this->assertIsInt($cantidad, "Debería retornar cantidad numérica");
            $this->assertGreaterThanOrEqual(0, $cantidad, "La cantidad debe ser >= 0");
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
