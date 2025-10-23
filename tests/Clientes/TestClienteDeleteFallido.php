<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/clientesModel.php';

/**
 * Prueba de caja blanca para casos de fallo en eliminación de clientes
 * Verifica validaciones al intentar eliminar clientes
 */
class TestClienteDeleteFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ClientesModel();
    }

    public function testDeleteClienteInexistente()
    {
        $result = $this->model->deleteClienteById(99999);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertFalse($result['status'], "No debería eliminar un cliente inexistente");
    }

    public function testDeleteClienteConIdCero()
    {
        $result = $this->model->deleteClienteById(0);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertFalse($result['status'], "No debería eliminar con ID cero");
    }

    public function testDeleteClienteConIdNegativo()
    {
        $result = $this->model->deleteClienteById(-1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertFalse($result['status'], "No debería eliminar con ID negativo");
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
