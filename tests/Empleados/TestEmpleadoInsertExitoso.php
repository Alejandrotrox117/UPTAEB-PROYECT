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
            'nombre' => 'María',
            'apellido' => 'González',
            'identificacion' => 'V-' . (12000000 + time() % 10000000),
            'tipo_empleado' => 'OPERARIO',
            'puesto' => 'Operario de Clasificación',
            'salario' => 30.00,
            'fecha_nacimiento' => '1995-03-15',
            'direccion' => 'Urbanización La Victoria, Calle 5',
            'correo_electronico' => 'maria.gonzalez' . time() . '@recicladora.com',
            'telefono_principal' => '0414-5551234',
            'genero' => 'F',
            'fecha_inicio' => date('Y-m-d'),
            'observaciones' => 'Operaria especializada en clasificación de cartón y papel',
            'estatus' => 'ACTIVO'
        ];

        $result = $this->model->insertEmpleado($data);

    $this->assertTrue($result);
    }

    public function testInsertEmpleadoSinObservaciones()
    {
        $data = [
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'identificacion' => 'V-' . (18000000 + time() % 10000000),
            'tipo_empleado' => 'SUPERVISOR',
            'puesto' => 'Supervisor de Producción',
            'salario' => 50.00,
            'fecha_nacimiento' => '1988-07-20',
            'direccion' => 'Sector Industrial, Galpón 3',
            'correo_electronico' => 'juan.perez' . time() . '@recicladora.com',
            'telefono_principal' => '0424-7778899',
            'genero' => 'M',
            'fecha_inicio' => date('Y-m-d'),
            'estatus' => 'ACTIVO'
        ];

        $result = $this->model->insertEmpleado($data);

    $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
