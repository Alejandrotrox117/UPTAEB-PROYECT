<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/clientesModel.php';

/**
 * Prueba de caja blanca para consultas exitosas de clientes
 * Verifica la lectura de datos de clientes
 */
class TestClienteSelectExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ClientesModel();
    }

    public function testSelectAllClientesRetornaArray()
    {
        $result = $this->model->selectAllClientes();

        $this->assertIsArray($result);
    }

    public function testSelectAllClientesTieneEstructuraCorrecta()
    {
        $clientes = $this->model->selectAllClientes();

        if (!empty($clientes)) {
            $cliente = $clientes[0];
            
            $this->assertArrayHasKey('idcliente', $cliente);
            $this->assertArrayHasKey('cedula', $cliente);
            $this->assertArrayHasKey('nombre', $cliente);
            $this->assertArrayHasKey('apellido', $cliente);
            $this->assertArrayHasKey('telefono_principal', $cliente);
            $this->assertArrayHasKey('direccion', $cliente);
            $this->assertArrayHasKey('estatus', $cliente);
        } else {
            $this->markTestSkipped('No hay clientes para verificar estructura');
        }
    }

    public function testSelectAllClientesActivos()
    {
        $clientes = $this->model->selectAllClientesActivos();

        $this->assertIsArray($clientes);

        foreach ($clientes as $cliente) {
            $this->assertEquals('activo', strtolower($cliente['estatus']));
        }
    }

    public function testSelectClienteByIdExistente()
    {
        $clientes = $this->model->selectAllClientes();
        
        if (empty($clientes)) {
            $this->markTestSkipped('No hay clientes para probar');
        }

        $idPrueba = $clientes[0]['idcliente'];
        $cliente = $this->model->selectClienteById($idPrueba);

        $this->assertIsArray($cliente);
        $this->assertEquals($idPrueba, $cliente['idcliente']);
    }

    public function testSelectClienteByCedula()
    {
        $clientes = $this->model->selectAllClientes();
        
        if (empty($clientes)) {
            $this->markTestSkipped('No hay clientes para probar');
        }

        $cedulaPrueba = $clientes[0]['cedula'];
        $cliente = $this->model->selectClienteByCedula($cedulaPrueba);

        $this->assertIsArray($cliente);
        $this->assertEquals($cedulaPrueba, $cliente['cedula']);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
