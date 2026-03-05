<?php

namespace Tests\IntegrationTest\Proveedores;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\ProveedoresModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class consultarProveedorIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private ?ProveedoresModel $model;

    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "\n[MODEL MESSAGE] " . $msg . "\n");
    }

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new ProveedoresModel();
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }

    // --- DataProviders ---

    public static function providerIdsInexistentes(): array
    {
        return [
            'ID muy grande' => [888888 + rand(1, 99999)],
            'ID enorme'     => [999999 + rand(1, 99999)],
        ];
    }

    public static function providerTerminosSinCoincidencia(): array
    {
        return [
            'cadena aleatoria' => ['xyzabcunico_' . time()],
            'cadena especial'  => ['@@##$$_sinmatch'],
        ];
    }

    // --- Tests: selectAllProveedores ---

    #[Test]
    public function testSelectAllProveedores_RetornaArrayConStatus(): void
    {
        $result = $this->model->selectAllProveedores();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertTrue($result['status']);
        $this->showMessage('selectAllProveedores OK – registros: ' . count($result['data']));
    }

    #[Test]
    public function testSelectAllProveedores_TieneEstructuraCorrecta(): void
    {
        $result = $this->model->selectAllProveedores(1);
        $proveedores = $result['data'] ?? [];

        if (empty($proveedores)) {
            $this->markTestSkipped('No hay proveedores en la BD para verificar estructura.');
        }

        $proveedor = $proveedores[0];
        $this->assertArrayHasKey('idproveedor', $proveedor);
        $this->assertArrayHasKey('nombre', $proveedor);
        $this->assertArrayHasKey('apellido', $proveedor);
        $this->assertArrayHasKey('identificacion', $proveedor);
        $this->assertArrayHasKey('telefono_principal', $proveedor);
        $this->assertArrayHasKey('estatus', $proveedor);
        $this->showMessage('Estructura de proveedor validada correctamente.');
    }

    // --- Tests: selectProveedorById ---

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testSelectProveedorById_IdInexistente_RetornaFalse(int $id): void
    {
        $result = $this->model->selectProveedorById($id);

        $this->assertFalse($result);
        $this->showMessage("selectProveedorById($id) retornó false correctamente.");
    }

    #[Test]
    public function testSelectProveedorById_IdExistente_RetornaDatos(): void
    {
        $todos = $this->model->selectAllProveedores(1);
        if (empty($todos['data'])) {
            $this->markTestSkipped('No hay proveedores en la BD para esta prueba.');
        }

        $id = $todos['data'][0]['idproveedor'];
        $result = $this->model->selectProveedorById($id);

        $this->assertIsArray($result);
        $this->assertEquals($id, $result['idproveedor']);
        $this->showMessage("selectProveedorById($id) retornó datos correctamente.");
    }

    // --- Tests: selectProveedoresActivos ---

    #[Test]
    public function testSelectProveedoresActivos_RetornaArrayConData(): void
    {
        $result = $this->model->selectProveedoresActivos();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertTrue($result['status']);

        if (!empty($result['data'])) {
            $this->assertArrayHasKey('idproveedor', $result['data'][0]);
            $this->assertArrayHasKey('identificacion', $result['data'][0]);
            $this->assertArrayHasKey('nombre_completo', $result['data'][0]);
        }
        $this->showMessage('selectProveedoresActivos OK – activos: ' . count($result['data']));
    }

    // --- Tests: buscarProveedores ---

    #[Test]
    #[DataProvider('providerTerminosSinCoincidencia')]
    public function testBuscarProveedores_SinCoincidencias_DataVacia(string $termino): void
    {
        $result = $this->model->buscarProveedores($termino);

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
        $this->assertEmpty($result['data']);
        $this->showMessage("buscarProveedores('$termino') retornó data vacía correctamente.");
    }

    #[Test]
    public function testBuscarProveedores_ConTerminoValido_RetornaArray(): void
    {
        // Buscar con un término genérico que podría tener resultados
        $result = $this->model->buscarProveedores('a');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->showMessage("buscarProveedores('a') retornó " . count($result['data']) . ' resultado(s).');
    }
}
