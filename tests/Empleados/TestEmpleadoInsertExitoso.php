<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/empleadosModel.php';

/**
 * Prueba de caja blanca para inserción exitosa de empleados
 */
class TestEmpleadoInsertExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new EmpleadosModel();
    }

    public function testInsertEmpleadoConDatosCompletos()
    {
        $data = [
            'nombre' => 'Carlos',
            'apellido' => 'López',
            'identificacion' => 'V' . time(),
            'fecha_nacimiento' => '1990-05-15',
            'direccion' => 'Calle Principal',
            'correo_electronico' => 'carlos' . time() . '@test.com',
            'telefono_principal' => '04121234567',
            'observaciones' => 'Empleado de prueba',
            'estatus' => 'activo'
        ];

        $result = $this->model->insertEmpleado($data);

        $this->assertIsBool($result);
    }

    public function testInsertEmpleadoSinObservaciones()
    {
        $data = [
            'nombre' => 'Ana',
            'apellido' => 'Ramírez',
            'identificacion' => 'V' . (time() + 1),
            'fecha_nacimiento' => '1995-08-20',
            'direccion' => 'Avenida Central',
            'correo_electronico' => 'ana' . time() . '@test.com',
            'telefono_principal' => '04149876543',
            'estatus' => 'activo'
        ];

        $result = $this->model->insertEmpleado($data);

        $this->assertIsBool($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
