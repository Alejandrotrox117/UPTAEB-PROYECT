<?php

namespace Tests\IntegrationTest\Clientes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\ClientesModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class consultarClientesIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private ClientesModel $model;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new ClientesModel();
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }

    // -------------------------------------------------------------------------
    // selectAllClientes
    // -------------------------------------------------------------------------

    #[Test]
    public function selectAllClientesRetornaEstructuraCorrecta(): void
    {
        $resultado = $this->model->selectAllClientes(0);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertTrue($resultado['status']);
        $this->assertIsArray($resultado['data']);
    }

    #[Test]
    public function selectAllClientesContieneColumnasEsperadas(): void
    {
        $resultado = $this->model->selectAllClientes(0);

        $this->assertTrue($resultado['status']);

        if (!empty($resultado['data'])) {
            $primerCliente = $resultado['data'][0];
            $this->assertArrayHasKey('idcliente', $primerCliente);
            $this->assertArrayHasKey('cedula', $primerCliente);
            $this->assertArrayHasKey('nombre', $primerCliente);
            $this->assertArrayHasKey('apellido', $primerCliente);
            $this->assertArrayHasKey('telefono_principal', $primerCliente);
            $this->assertArrayHasKey('direccion', $primerCliente);
            $this->assertArrayHasKey('estatus', $primerCliente);
        } else {
            $this->markTestSkipped('No hay clientes en la BD de prueba.');
        }
    }

    // -------------------------------------------------------------------------
    // selectAllClientesActivos
    // -------------------------------------------------------------------------

    #[Test]
    public function selectAllClientesActivosRetornaEstructuraCorrecta(): void
    {
        $resultado = $this->model->selectAllClientesActivos();

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertTrue($resultado['status']);
    }

    #[Test]
    public function selectAllClientesActivosSoloRetornaActivos(): void
    {
        $resultado = $this->model->selectAllClientesActivos();

        $this->assertTrue($resultado['status']);

        foreach ($resultado['data'] as $cliente) {
            $this->assertSame('activo', strtolower($cliente['estatus']));
        }
    }

    // -------------------------------------------------------------------------
    // selectClienteById
    // -------------------------------------------------------------------------

    #[Test]
    public function selectClienteByIdExistenteRetornaArray(): void
    {
        $todos = $this->model->selectAllClientes(0);

        if (empty($todos['data'])) {
            $this->markTestSkipped('No hay clientes en la BD de prueba.');
        }

        $idExistente = (int) $todos['data'][0]['idcliente'];
        $resultado   = $this->model->selectClienteById($idExistente);

        $this->assertIsArray($resultado);
        $this->assertSame($idExistente, (int) $resultado['idcliente']);
    }

    #[Test]
    public function selectClienteByIdNoExistenteRetornaFalse(): void
    {
        $resultado = $this->model->selectClienteById(999999);

        $this->assertFalse($resultado);
    }

    // -------------------------------------------------------------------------
    // selectClienteByCedula
    // -------------------------------------------------------------------------

    #[Test]
    public function selectClienteByCedulaExistenteRetornaDatos(): void
    {
        $todos = $this->model->selectAllClientes(0);

        if (empty($todos['data'])) {
            $this->markTestSkipped('No hay clientes en la BD de prueba.');
        }

        $cedulaExistente = $todos['data'][0]['cedula'];
        $resultado       = $this->model->selectClienteByCedula($cedulaExistente);

        $this->assertNotFalse($resultado);
        $this->assertIsArray($resultado);
    }

    #[Test]
    public function selectClienteByCedulaNoExistenteRetornaFalse(): void
    {
        $resultado = $this->model->selectClienteByCedula('CEDULA-NO-EXISTE-XXXXXXXXX');

        $this->assertFalse($resultado);
    }

    // -------------------------------------------------------------------------
    // buscarClientes
    // -------------------------------------------------------------------------

    #[Test]
    public function buscarClientesRetornaEstructuraCorrecta(): void
    {
        $resultado = $this->model->buscarClientes('a');

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertTrue($resultado['status']);
    }

    #[Test]
    public function buscarClientesCriterioSinResultadosRetornaDataVacia(): void
    {
        $resultado = $this->model->buscarClientes('xyzXYZ_criterio_imposible_9999');

        $this->assertTrue($resultado['status']);
        $this->assertEmpty($resultado['data']);
    }
}
