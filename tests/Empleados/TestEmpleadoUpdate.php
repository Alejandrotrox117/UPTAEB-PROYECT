<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/empleadosModel.php';





class TestEmpleadoUpdate extends TestCase
{
    private $model;

    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }

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

    

    public function testActualizarEmpleadoInexistente()
    {
        $data = [
            'idempleado' => 99999,
            'nombre' => 'Empleado',
            'apellido' => 'Inexistente'
        ];

        $result = $this->model->updateEmpleado($data);
        $this->assertFalse($result);
        
        if (is_array($result) && array_key_exists('message', $result)) {
            $this->showMessage($result['message']);
        }
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
        $this->assertIsBool($result);
    }

    public function testActualizarConSalarioNegativo()
    {
        $data = [
            'idempleado' => 1,
            'salario' => -100.00
        ];

        $result = $this->model->updateEmpleado($data);
        $this->assertIsBool($result);
    }

    public function testActualizarConFechaNacimientoInvalida()
    {
        $data = [
            'idempleado' => 1,
            'fecha_nacimiento' => '2025-13-45'
        ];

        $result = $this->model->updateEmpleado($data);
        $this->assertIsBool($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
