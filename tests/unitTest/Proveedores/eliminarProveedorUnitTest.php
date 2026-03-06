<?php

namespace Tests\UnitTest\Proveedores;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\ProveedoresModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
class eliminarProveedorUnitTest extends TestCase
{
    private ProveedoresModel $model;
    /** @var \Mockery\MockInterface */
    private $mockPdo;
    /** @var \Mockery\MockInterface */
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');

        $this->mockPdo  = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new ProveedoresModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // --- DataProviders ---

    public static function providerIdsInexistentes(): array
    {
        return [
            'ID grande' => [99999],
            'ID enorme' => [12345678],
        ];
    }

    public static function providerIdsParaReactivar(): array
    {
        return [
            'ID inexistente 1' => [88888],
            'ID inexistente 2' => [77777],
        ];
    }

    // --- Tests: deleteProveedorById ---

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testDeleteProveedorById_IdInexistente_RetornaFalse(int $id): void
    {
        // rowCount = 0 → no se afectaron filas (proveedor no existe o ya inactivo)
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0);

        $result = $this->model->deleteProveedorById($id);

        $this->assertFalse($result);
    }

    #[Test]
    public function testDeleteProveedorById_IdExistente_RetornaTrue(): void
    {
        // rowCount = 1 → la fila fue actualizada a INACTIVO
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1);

        $result = $this->model->deleteProveedorById(5);

        $this->assertTrue($result);
    }

    #[Test]
    public function testDeleteProveedorById_ExcepcionEnBD_RetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \Exception('Error de BD simulado'));

        $result = $this->model->deleteProveedorById(5);

        $this->assertFalse($result);
    }

    // --- Tests: reactivarProveedor ---

    #[Test]
    #[DataProvider('providerIdsParaReactivar')]
    public function testReactivarProveedor_IdInexistente_StatusFalse(int $id): void
    {
        // rowCount = 0 → ninguna fila afectada
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0);

        $result = $this->model->reactivarProveedor($id);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    #[Test]
    public function testReactivarProveedor_IdExistente_StatusTrue(): void
    {
        // rowCount = 1 → fila actualizada a ACTIVO
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1);

        $result = $this->model->reactivarProveedor(3);

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
    }

    #[Test]
    public function testReactivarProveedor_ExcepcionEnBD_StatusFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \Exception('Error de BD simulado'));

        $result = $this->model->reactivarProveedor(3);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
}
