<?php

namespace Tests\UnitTest\Compra;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\ComprasModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class consultarComprasUnitTest extends TestCase
{
    private ComprasModel $compras;
    /** @var \Mockery\MockInterface */
    private $mockPdo;
    /** @var \Mockery\MockInterface */
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');

        $this->mockPdo = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('query')->andReturn($this->mockStmt)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->compras = new ComprasModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public static function providerIdsInexistentes(): array
    {
        return [
            [888888],
            [999999],
            [12345678]
        ];
    }

    #[Test]
    public function testSelectAllComprasRetornaArray(): void
    {
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([['id' => 1]]);
        $result = $this->compras->selectAllCompras();
        $this->assertIsArray($result);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testGetCompraByIdConIdInexistente(int $id): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);
        $result = $this->compras->getCompraById($id);
        $this->assertFalse($result);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testSelectCompraConIdInexistente(int $id): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);
        $result = $this->compras->selectCompra($id);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testGetDetalleCompraByIdInexistente(int $id): void
    {
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([]);
        $result = $this->compras->getDetalleCompraById($id);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testGetCompraCompletaParaEditarInexistente(int $id): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false); // First part failure
        $result = $this->compras->getCompraCompletaParaEditar($id);
        $this->assertFalse($result);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testObtenerEstadoCompraInexistente(int $id): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false); // Assume it's scalar or false
        $result = $this->compras->obtenerEstadoCompra($id);
        $this->assertNull($result);
    }

    #[Test]
    public function testGetMonedasActivas(): void
    {
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([['id' => 1]]);
        $result = $this->compras->getMonedasActivas();
        $this->assertIsArray($result);
    }

    #[Test]
    public function testGetProductosConCategoria(): void
    {
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([['id' => 1]]);
        $result = $this->compras->getProductosConCategoria();
        $this->assertIsArray($result);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testGetProductoByIdInexistente(int $id): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);
        $result = $this->compras->getProductoById($id);
        $this->assertFalse($result);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testGetProveedorByIdInexistente(int $id): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);
        $result = $this->compras->getProveedorById($id);
        $this->assertFalse($result);
    }
}
