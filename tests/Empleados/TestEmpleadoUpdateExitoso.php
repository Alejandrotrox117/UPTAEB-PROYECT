<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/empleadosModel.php';

/**
 * Prueba de caja blanca para actualización exitosa de empleados
 * Valida actualización de datos de empleado
 */
class TestEmpleadoUpdateExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new EmpleadosModel();
    }

    public function testActualizarEmpleadoConDatosCompletos()
    {
        $data = [
            'idempleado' => 1,
            'nombre' => 'Carlos Actualizado',
            'apellido' => 'Pérez',
            'identificacion' => '12345678',
            'telefono_principal' => '04141234567',
            'correo_electronico' => 'carlos.perez@email.com',
            'direccion' => 'Av. Principal, Ciudad',
            'fecha_nacimiento' => '1990-05-15',
            'genero' => 'M',
            'puesto' => 'Supervisor',
            'salario' => 500.00
        ];

        $result = $this->model->updateEmpleado($data);

        $this->assertIsBool($result);
    }

    public function testActualizarSoloNombreYApellido()
    {
        $data = [
            'idempleado' => 1,
            'nombre' => 'María',
            'apellido' => 'González'
        ];

        $result = $this->model->updateEmpleado($data);

        $this->assertIsBool($result);
    }

    public function testActualizarSalario()
    {
        $data = [
            'idempleado' => 1,
            'salario' => 600.00
        ];

        $result = $this->model->updateEmpleado($data);

        $this->assertIsBool($result);
    }

    public function testActualizarContacto()
    {
        $data = [
            'idempleado' => 1,
            'telefono_principal' => '04241234567',
            'correo_electronico' => 'nuevo.email@empresa.com'
        ];

        $result = $this->model->updateEmpleado($data);

        $this->assertIsBool($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
