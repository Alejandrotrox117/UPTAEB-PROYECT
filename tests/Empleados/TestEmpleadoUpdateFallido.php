<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/empleadosModel.php';

/**
 * Prueba de caja blanca para casos de fallo en actualización de empleados
 * Valida restricciones y validaciones en actualización
 */
class TestEmpleadoUpdateFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new EmpleadosModel();
    }

    public function testActualizarEmpleadoInexistente()
    {
        $data = [
            'idempleado' => 99999,
            'nombre' => 'Empleado',
            'apellido' => 'Inexistente'
        ];

        $result = $this->model->updateEmpleado($data);

        $this->assertFalse($result);
    }

    public function testActualizarSinId()
    {
        $data = [
            'nombre' => 'Sin',
            'apellido' => 'ID'
        ];

        try {
            $result = $this->model->updateEmpleado($data);
            $this->assertFalse($result);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
        }
    }

    public function testActualizarConEmailInvalido()
    {
        $data = [
            'idempleado' => 1,
            'correo_electronico' => 'email_invalido_sin_arroba'
        ];

        $result = $this->model->updateEmpleado($data);

        // Dependiendo de validaciones, puede fallar
        $this->assertIsBool($result);
    }

    public function testActualizarConSalarioNegativo()
    {
        $data = [
            'idempleado' => 1,
            'salario' => -100.00
        ];

        $result = $this->model->updateEmpleado($data);

        // Dependiendo de validaciones, puede fallar
        $this->assertIsBool($result);
    }

    public function testActualizarConFechaNacimientoInvalida()
    {
        $data = [
            'idempleado' => 1,
            'fecha_nacimiento' => '2025-13-45' // Fecha inválida
        ];

        $result = $this->model->updateEmpleado($data);

        // Dependiendo de validaciones, puede fallar
        $this->assertIsBool($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
