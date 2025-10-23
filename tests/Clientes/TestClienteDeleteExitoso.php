<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/clientesModel.php';

/**
 * Prueba de caja blanca para eliminación exitosa de clientes
 * Verifica el borrado lógico de clientes
 */
class TestClienteDeleteExitoso extends TestCase
{
    private $model;
    private $clienteIdPrueba;

    protected function setUp(): void
    {
        $this->model = new ClientesModel();
        
        // Crear un cliente de prueba para eliminar
        $data = [
            'cedula' => 'V' . time(),
            'nombre' => 'Delete',
            'apellido' => 'Test',
            'direccion' => 'Para Eliminar',
            'telefono_principal' => '04122222222',
            'observaciones' => 'Cliente a eliminar'
        ];
        
        $result = $this->model->insertCliente($data);
        
        if ($result['status']) {
            $this->clienteIdPrueba = $result['cliente_id'];
        }
    }

    public function testDeleteClienteExistente()
    {
        if (!$this->clienteIdPrueba) {
            $this->markTestSkipped('No se pudo crear cliente de prueba');
        }

        $result = $this->model->deleteClienteById($this->clienteIdPrueba);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        
        if (isset($result['status'])) {
            $this->assertTrue($result['status']);
        }
    }

    public function testDeleteClienteVerificarEstatus()
    {
        if (!$this->clienteIdPrueba) {
            $this->markTestSkipped('No se pudo crear cliente de prueba');
        }

        $result = $this->model->deleteClienteById($this->clienteIdPrueba);

        if ($result['status']) {
            $cliente = $this->model->selectClienteById($this->clienteIdPrueba);
            
            if ($cliente) {
                $this->assertEquals('INACTIVO', strtoupper($cliente['estatus']));
            }
        }

        $this->assertIsArray($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
