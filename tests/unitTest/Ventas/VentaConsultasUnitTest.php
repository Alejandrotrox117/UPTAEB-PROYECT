<?php

namespace Tests\UnitTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\VentasModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class VentaConsultasUnitTest extends TestCase
{
    private VentasModel $ventasModel;
    /** @var \Mockery\MockInterface */
    private $mockPdo;
    /** @var \Mockery\MockInterface */
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', '/dev/null');

        $this->mockPdo  = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        // Comportamiento por defecto
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

        $this->ventasModel = new VentasModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // ─────────────────────────────────────────────
    // DataProviders
    // ─────────────────────────────────────────────

    public static function providerGetVentasDatatable(): array
    {
        return [
            'lista con ventas' => [
                [
                    ['idventa' => 1, 'nro_venta' => 'VT000001', 'fecha_venta' => '2025-01-01', 'cliente_nombre' => 'Juan Pérez', 'total_general' => 200.00, 'estatus' => 'BORRADOR'],
                    ['idventa' => 2, 'nro_venta' => 'VT000002', 'fecha_venta' => '2025-01-02', 'cliente_nombre' => 'Ana Gómez', 'total_general' => 150.00, 'estatus' => 'POR_PAGAR'],
                ],
            ],
            'lista vacía' => [[]],
        ];
    }

    public static function providerIdsInexistentes(): array
    {
        return [
            [888888],
            [999999],
            [12345678],
        ];
    }

    // ─────────────────────────────────────────────
    // Tests
    // ─────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerGetVentasDatatable')]
    public function testGetVentasDatatableRetornaArray(array $filas): void
    {
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn($filas);
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false); // esSuperUsuario retorna false

        $result = $this->ventasModel->getVentasDatatable();

        $this->assertIsArray($result);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testObtenerVentaPorIdInexistenteRetornaFalse(int $id): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);

        $result = $this->ventasModel->obtenerVentaPorId($id);

        $this->assertFalse($result);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testObtenerDetalleVentaConIdInexistenteRetornaArrayVacio(int $id): void
    {
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([]);

        $result = $this->ventasModel->obtenerDetalleVenta($id);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function testGetVentasDatatableRetornaEstructuraEsperada(): void
    {
        $filas = [
            ['idventa' => 1, 'nro_venta' => 'VT000001', 'fecha_venta' => '2025-01-01', 'cliente_nombre' => 'Juan Pérez', 'total_general' => 200.00, 'estatus' => 'BORRADOR'],
        ];

        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn($filas);
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);

        $result = $this->ventasModel->getVentasDatatable();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $primera = $result[0];
        $this->assertArrayHasKey('idventa', $primera);
        $this->assertArrayHasKey('nro_venta', $primera);
        $this->assertArrayHasKey('total_general', $primera);
        $this->assertArrayHasKey('estatus', $primera);
    }
}
