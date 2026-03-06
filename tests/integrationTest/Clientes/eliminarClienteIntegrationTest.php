<?php

namespace Tests\IntegrationTest\Clientes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\ClientesModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class eliminarClienteIntegrationTest extends TestCase
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
    // Helpers
    // -------------------------------------------------------------------------

    private function crearClienteTemporal(string $sufijo = ''): ?int
    {
        $data = [
            'cedula'             => 'V-' . bin2hex(random_bytes(4)) . $sufijo,
            'nombre'             => 'ClienteEliminar',
            'apellido'           => 'Prueba',
            'direccion'          => 'Calle Eliminar 1',
            'telefono_principal' => '04141555555',
            'estatus'            => 'activo',
            'observaciones'      => 'Temporal para prueba de eliminación',
        ];
        $resultado = $this->model->insertCliente($data);
        return ($resultado['status'] && $resultado['cliente_id']) ? (int) $resultado['cliente_id'] : null;
    }

    // -------------------------------------------------------------------------
    // deleteClienteById — exitoso (soft-delete)
    // -------------------------------------------------------------------------

    #[Test]
    public function deleteClienteByIdExistenteRetornaTrue(): void
    {
        $idCliente = $this->crearClienteTemporal();

        if ($idCliente === null) {
            $this->markTestSkipped('No se pudo crear cliente temporal para prueba de eliminación.');
        }

        $resultado = $this->model->deleteClienteById($idCliente);

        $this->assertTrue($resultado);
    }

    #[Test]
    public function deleteClienteByIdCambiaEstatusAInactivo(): void
    {
        $idCliente = $this->crearClienteTemporal('_verif');

        if ($idCliente === null) {
            $this->markTestSkipped('No se pudo crear cliente temporal.');
        }

        $this->model->deleteClienteById($idCliente);

        $cliente = $this->model->selectClienteById($idCliente);

        $this->assertIsArray($cliente);
        $this->assertSame('inactivo', strtolower($cliente['estatus']));
    }

    // -------------------------------------------------------------------------
    // deleteClienteById — id inexistente
    // -------------------------------------------------------------------------

    #[Test]
    public function deleteClienteByIdNoExistenteRetornaFalse(): void
    {
        $resultado = $this->model->deleteClienteById(999999);

        $this->assertFalse($resultado);
    }

    // -------------------------------------------------------------------------
    // reactivarCliente — exitoso
    // -------------------------------------------------------------------------

    #[Test]
    public function reactivarClienteExitosoRetornaStatusTrue(): void
    {
        $idCliente = $this->crearClienteTemporal('_reactiv');

        if ($idCliente === null) {
            $this->markTestSkipped('No se pudo crear cliente temporal para reactivación.');
        }

        // Primero eliminar (soft-delete → inactivo)
        $this->model->deleteClienteById($idCliente);

        // Luego reactivar
        $resultado = $this->model->reactivarCliente($idCliente);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status'], 'Falló reactivarCliente: ' . ($resultado['message'] ?? ''));
        $this->assertStringContainsStringIgnoringCase('reactivado', $resultado['message']);
    }

    #[Test]
    public function reactivarClienteCambiaEstatusAActivo(): void
    {
        $idCliente = $this->crearClienteTemporal('_estatus');

        if ($idCliente === null) {
            $this->markTestSkipped('No se pudo crear cliente temporal.');
        }

        $this->model->deleteClienteById($idCliente);
        $this->model->reactivarCliente($idCliente);

        $cliente = $this->model->selectClienteById($idCliente);

        $this->assertIsArray($cliente);
        $this->assertSame('activo', strtolower($cliente['estatus']));
    }

    // -------------------------------------------------------------------------
    // reactivarCliente — cliente no encontrado
    // -------------------------------------------------------------------------

    #[Test]
    public function reactivarClienteNoExistenteRetornaStatusFalse(): void
    {
        $resultado = $this->model->reactivarCliente(999999);

        $this->assertFalse($resultado['status']);
        $this->assertStringContainsStringIgnoringCase('no encontrado', $resultado['message']);
    }

    // -------------------------------------------------------------------------
    // reactivarCliente — ya está activo
    // -------------------------------------------------------------------------

    #[Test]
    public function reactivarClienteYaActivoRetornaStatusFalse(): void
    {
        $idCliente = $this->crearClienteTemporal('_yaact');

        if ($idCliente === null) {
            $this->markTestSkipped('No se pudo crear cliente temporal.');
        }

        // El cliente recién creado está activo; intentar reactivar debe fallar
        $resultado = $this->model->reactivarCliente($idCliente);

        // Limpieza
        $this->model->deleteClienteById($idCliente);

        $this->assertFalse($resultado['status']);
        $this->assertStringContainsStringIgnoringCase('ya está activo', $resultado['message']);
    }
}
