<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/clientesModel.php';





class TestClienteInsert extends TestCase
{
    private $model;

    private function showMessage(string $msg)
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }

    protected function setUp(): void
    {
        $this->model = new ClientesModel();
    }

    

    public function testInsertClienteConDatosCompletos()
    {
        $cedulaUnica = 'V' . time();
        
        $data = [
            'cedula' => $cedulaUnica,
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'direccion' => 'Calle Principal #123',
            'telefono_principal' => '04121234567',
            'observaciones' => 'Cliente de prueba'
        ];

        $result = $this->model->insertCliente($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('cliente_id', $result);
        
        if ($result['status']) {
            $this->assertTrue($result['status']);
            $this->assertIsInt($result['cliente_id']);
            $this->assertGreaterThan(0, $result['cliente_id']);
        }
    }

    public function testInsertClienteSinObservaciones()
    {
        $cedulaUnica = 'V' . (time() + 1);
        
        $data = [
            'cedula' => $cedulaUnica,
            'nombre' => 'María',
            'apellido' => 'González',
            'direccion' => 'Avenida Libertador',
            'telefono_principal' => '04149876543'
        ];

        $result = $this->model->insertCliente($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testInsertClienteConNombreLargo()
    {
        $cedulaUnica = 'E' . time();
        
        $data = [
            'cedula' => $cedulaUnica,
            'nombre' => 'Juan Carlos Antonio',
            'apellido' => 'Rodríguez García',
            'direccion' => 'Urbanización Los Rosales, Calle 5, Casa 23',
            'telefono_principal' => '04261234567',
            'observaciones' => 'Cliente preferencial con descuento especial'
        ];

        $result = $this->model->insertCliente($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testInsertClienteConTelefonoFijo()
    {
        $cedulaUnica = 'V' . (time() + 2);
        
        $data = [
            'cedula' => $cedulaUnica,
            'nombre' => 'Pedro',
            'apellido' => 'Martínez',
            'direccion' => 'Centro, Edificio Torre',
            'telefono_principal' => '02121234567',
            'observaciones' => ''
        ];

        $result = $this->model->insertCliente($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    

    public function testInsertClienteSinCedula()
    {
        $data = [
            'cedula' => '',
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'direccion' => 'Calle 1',
            'telefono_principal' => '04121234567'
        ];

        $result = $this->model->insertCliente($data);

        $this->assertIsArray($result);
        if (array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
            $this->showMessage($result['message']);
        }
        $this->assertFalse($result['status']);
    }

    public function testInsertClienteSinNombre()
    {
        $data = [
            'cedula' => 'V12345678',
            'nombre' => '',
            'apellido' => 'Pérez',
            'direccion' => 'Calle 1',
            'telefono_principal' => '04121234567'
        ];

        $result = $this->model->insertCliente($data);

        $this->assertIsArray($result);
        if (array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
            $this->showMessage($result['message']);
        }
        $this->assertFalse($result['status']);
    }

    public function testInsertClienteSinApellido()
    {
        $data = [
            'cedula' => 'V98765432',
            'nombre' => 'Juan',
            'apellido' => '',
            'direccion' => 'Calle 1',
            'telefono_principal' => '04121234567'
        ];

        $result = $this->model->insertCliente($data);

        $this->assertIsArray($result);
        if (array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
            $this->showMessage($result['message']);
        }
        $this->assertFalse($result['status']);
    }

    public function testInsertClienteConCedulaDuplicada()
    {
        $cedulaDuplicada = 'V' . time();
        
        $data = [
            'cedula' => $cedulaDuplicada,
            'nombre' => 'Cliente',
            'apellido' => 'Uno',
            'direccion' => 'Dirección 1',
            'telefono_principal' => '04121234567'
        ];

        $result1 = $this->model->insertCliente($data);
        $data['nombre'] = 'Cliente Dos';
        $result2 = $this->model->insertCliente($data);

        $this->assertIsArray($result2);
        $this->assertFalse($result2['status']);
        $this->assertStringContainsString('cédula', strtolower($result2['message']));
    }

    public function testInsertClienteConDatosIncompletos()
    {
        $data = [
            'cedula' => 'V11111111',
            'nombre' => 'Solo Nombre'
        ];

        $result = $this->model->insertCliente($data);

        $this->assertIsArray($result);
        if (array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
            $this->showMessage($result['message']);
        }
        $this->assertFalse($result['status']);
    }

    public function testInsertClienteSinTelefono()
    {
        $data = [
            'cedula' => 'V22222222',
            'nombre' => 'Sin',
            'apellido' => 'Teléfono',
            'direccion' => 'Alguna dirección',
            'telefono_principal' => ''
        ];

        $result = $this->model->insertCliente($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testInsertClienteConArrayVacio()
    {
        $data = [];

        $result = $this->model->insertCliente($data);

        $this->assertIsArray($result);
        if (array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
            $this->showMessage($result['message']);
        }
        $this->assertFalse($result['status']);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
