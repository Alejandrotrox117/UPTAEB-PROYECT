<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/clientesModel.php';





class TestClienteUpdate extends TestCase
{
    private $model;
    private $clienteIdPrueba;

    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }

    protected function setUp(): void
    {
        $this->model = new ClientesModel();
        
        $data = [
            'cedula' => 'V' . time(),
            'nombre' => 'Update',
            'apellido' => 'Test',
            'direccion' => 'Dirección Original',
            'telefono_principal' => '04121111111',
            'observaciones' => 'Para actualizar'
        ];
        
        $result = $this->model->insertCliente($data);
        
        if ($result['status']) {
            $this->clienteIdPrueba = $result['cliente_id'];
        }
    }

    

    public function testUpdateClienteDatosCompletos()
    {
        if (!$this->clienteIdPrueba) {
            $this->markTestSkipped('No se pudo crear cliente de prueba');
        }

        $dataUpdate = [
            'cedula' => 'V99999999',
            'nombre' => 'Nombre Actualizado',
            'apellido' => 'Apellido Actualizado',
            'direccion' => 'Nueva Dirección',
            'telefono_principal' => '04149999999',
            'observaciones' => 'Datos actualizados'
        ];

        $result = $this->model->updateCliente($this->clienteIdPrueba, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        
        if (isset($result['status'])) {
            $this->assertTrue($result['status']);
        }
    }

    public function testUpdateClienteSoloTelefono()
    {
        if (!$this->clienteIdPrueba) {
            $this->markTestSkipped('No se pudo crear cliente de prueba');
        }

        $cliente = $this->model->selectClienteById($this->clienteIdPrueba);
        
        if (!$cliente) {
            $this->markTestSkipped('No se pudo obtener cliente de prueba');
        }

        $dataUpdate = [
            'cedula' => $cliente['cedula'],
            'nombre' => $cliente['nombre'],
            'apellido' => $cliente['apellido'],
            'direccion' => $cliente['direccion'],
            'telefono_principal' => '04267777777',
            'observaciones' => $cliente['observaciones']
        ];

        $result = $this->model->updateCliente($this->clienteIdPrueba, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testUpdateClienteSoloDireccion()
    {
        if (!$this->clienteIdPrueba) {
            $this->markTestSkipped('No se pudo crear cliente de prueba');
        }

        $cliente = $this->model->selectClienteById($this->clienteIdPrueba);
        
        if (!$cliente) {
            $this->markTestSkipped('No se pudo obtener cliente de prueba');
        }

        $dataUpdate = [
            'cedula' => $cliente['cedula'],
            'nombre' => $cliente['nombre'],
            'apellido' => $cliente['apellido'],
            'direccion' => 'Dirección Completamente Nueva',
            'telefono_principal' => $cliente['telefono_principal'],
            'observaciones' => $cliente['observaciones']
        ];

        $result = $this->model->updateCliente($this->clienteIdPrueba, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    

    public function testUpdateClienteInexistente()
    {
        $dataUpdate = [
            'cedula' => 'V88888888',
            'nombre' => 'No Existe',
            'apellido' => 'Cliente',
            'direccion' => 'Ninguna',
            'telefono_principal' => '04121111111',
            'observaciones' => ''
        ];

        $result = $this->model->updateCliente(99999, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        
        if (array_key_exists('message', $result)) {
            $this->showMessage($result['message']);
        }
    }

    public function testUpdateClienteConCedulaVacia()
    {
        $dataUpdate = [
            'cedula' => '',
            'nombre' => 'Test',
            'apellido' => 'Cliente',
            'direccion' => 'Dirección',
            'telefono_principal' => '04121111111',
            'observaciones' => ''
        ];

        $result = $this->model->updateCliente(1, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        
        if (array_key_exists('message', $result)) {
            $this->showMessage($result['message']);
        }
    }

    public function testUpdateClienteConNombreVacio()
    {
        $dataUpdate = [
            'cedula' => 'V77777777',
            'nombre' => '',
            'apellido' => 'Cliente',
            'direccion' => 'Dirección',
            'telefono_principal' => '04121111111',
            'observaciones' => ''
        ];

        $result = $this->model->updateCliente(1, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        
        if (array_key_exists('message', $result)) {
            $this->showMessage($result['message']);
        }
    }

    public function testUpdateClienteConDatosIncompletos()
    {
        $dataUpdate = [
            'cedula' => 'V66666666',
            'nombre' => 'Solo Datos'
        ];

        $result = $this->model->updateCliente(1, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        
        if (array_key_exists('message', $result)) {
            $this->showMessage($result['message']);
        }
    }

    public function testUpdateClienteConIdNegativo()
    {
        $dataUpdate = [
            'cedula' => 'V55555555',
            'nombre' => 'Test',
            'apellido' => 'Cliente',
            'direccion' => 'Dirección',
            'telefono_principal' => '04121111111',
            'observaciones' => ''
        ];

        $result = $this->model->updateCliente(-1, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        
        if (array_key_exists('message', $result)) {
            $this->showMessage($result['message']);
        }
    }

    public function testUpdateClienteConIdCero()
    {
        $dataUpdate = [
            'cedula' => 'V44444444',
            'nombre' => 'Test',
            'apellido' => 'Cliente',
            'direccion' => 'Dirección',
            'telefono_principal' => '04121111111',
            'observaciones' => ''
        ];

        $result = $this->model->updateCliente(0, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        
        if (array_key_exists('message', $result)) {
            $this->showMessage($result['message']);
        }
    }

    protected function tearDown(): void
    {
        if ($this->clienteIdPrueba) {
            $this->model->deleteClienteById($this->clienteIdPrueba);
        }
        $this->model = null;
    }
}
