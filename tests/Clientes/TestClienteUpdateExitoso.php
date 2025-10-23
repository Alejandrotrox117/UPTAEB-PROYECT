<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/clientesModel.php';

/**
 * Prueba de caja blanca para actualización exitosa de clientes
 * Verifica que se puedan actualizar clientes existentes
 */
class TestClienteUpdateExitoso extends TestCase
{
    private $model;
    private $clienteIdPrueba;

    protected function setUp(): void
    {
        $this->model = new ClientesModel();
        
        // Crear un cliente de prueba para actualizar
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

    protected function tearDown(): void
    {
        // Limpiar: eliminar el cliente de prueba
        if ($this->clienteIdPrueba) {
            $this->model->deleteClienteById($this->clienteIdPrueba);
        }
        $this->model = null;
    }
}
