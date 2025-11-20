<?php
use PHPUnit\Framework\TestCase;
use App\Models\ClientesModel;
class TestClienteSelect extends TestCase
{
    private $model;
    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }
    protected function setUp(): void
    {
        $this->model = new ClientesModel();
    }
    public function testSelectAllClientesRetornaArray()
    {
        $result = $this->model->selectAllClientes();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
    }
    public function testSelectAllClientesTieneEstructuraCorrecta()
    {
        $response = $this->model->selectAllClientes();
        $clientes = $response['data'] ?? [];
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
        $response = $this->model->selectAllClientesActivos();
        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);
        $clientes = $response['data'] ?? [];
        foreach ($clientes as $cliente) {
            $this->assertEquals('activo', strtolower($cliente['estatus']));
        }
    }
    public function testSelectClienteByIdExistente()
    {
        $response = $this->model->selectAllClientes();
        $clientes = $response['data'] ?? [];
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
        $response = $this->model->selectAllClientes();
        $clientes = $response['data'] ?? [];
        if (empty($clientes)) {
            $this->markTestSkipped('No hay clientes para probar');
        }
        $cedulaPrueba = $clientes[0]['cedula'];
        $cliente = $this->model->selectClienteByCedula($cedulaPrueba);
        $this->assertIsArray($cliente);
        $this->assertEquals($cedulaPrueba, $cliente['cedula']);
    }
    public function testSelectClienteByIdInexistente()
    {
        $cliente = $this->model->selectClienteById(99999);
        $this->assertFalse($cliente);
    }
    public function testSelectClienteByCedulaInexistente()
    {
        $cliente = $this->model->selectClienteByCedula('V00000000');
        $this->assertFalse($cliente);
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
