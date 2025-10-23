<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/clientesModel.php';

/**
 * Prueba de caja blanca para casos de fallo en consultas de clientes
 * Verifica el comportamiento ante consultas inválidas
 */
class TestClienteSelectFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ClientesModel();
    }

    public function testSelectClienteByIdInexistente()
    {
        $cliente = $this->model->selectClienteById(99999);

        $this->assertFalse($cliente);
    }

    public function testSelectClienteByIdNegativo()
    {
        $cliente = $this->model->selectClienteById(-1);

        $this->assertFalse($cliente);
    }

    public function testSelectClienteByIdCero()
    {
        $cliente = $this->model->selectClienteById(0);

        $this->assertFalse($cliente);
    }

    public function testSelectClienteByCedulaInexistente()
    {
        $cliente = $this->model->selectClienteByCedula('V00000000');

        $this->assertFalse($cliente);
    }

    public function testSelectClienteByCedulaVacia()
    {
        $cliente = $this->model->selectClienteByCedula('');

        $this->assertFalse($cliente);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
