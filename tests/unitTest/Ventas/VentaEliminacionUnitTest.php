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
class VentaEliminacionUnitTest extends TestCase
{
    private VentasModel $ventasModel;
    /** @var \Mockery\MockInterface */
    private $mockPdo;
    /** @var \Mockery\MockInterface */
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'nul' : '/dev/null');

        $this->mockPdo  = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('query')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('commit')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('rollBack')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('1')->byDefault();
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(true)->byDefault();

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

    public static function providerCasosEliminarVenta(): array
    {
        return [
            [888888],
            [999999],
        ];
    }

    // ─────────────────────────────────────────────
    // Tests
    // ─────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerCasosEliminarVenta')]
    public function testEliminarVentaConIdInexistente_RetornaFalse(int $id): void
    {
        // fetch devuelve ['count' => 0] → venta no existe → false
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['count' => 0]);

        $resultado = $this->ventasModel->eliminarVenta($id);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['success']);
    }

    #[Test]
    public function testEliminarVenta_EstadoNoEsBorrador_RetornaFalse(): void
    {
        // La venta existe (count=1) pero su estatus no es BORRADOR
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturnValues([
                ['count' => 1],            // SELECT COUNT(*) → existe
                ['estatus' => 'POR_PAGAR'], // SELECT estatus → no es BORRADOR
            ]);

        $resultado = $this->ventasModel->eliminarVenta(5);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['success']);
        $this->assertArrayHasKey('message', $resultado);
    }

    #[Test]
    public function testEliminarVenta_EnEstadoBorrador_RetornaTrue(): void
    {
        // La venta existe (count=1), estatus es BORRADOR, UPDATE rowCount=1
        // registrarMovimientosDevolucion puede hacer llamadas adicionales cubiertas por byDefault
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturnValues([
                ['count' => 1],           // SELECT COUNT(*) → existe
                ['estatus' => 'BORRADOR'], // SELECT estatus → es BORRADOR
            ])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1)->byDefault(); // UPDATE exitoso
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();

        // El modelo devuelve $resultado (bool), no array
        $resultado = $this->ventasModel->eliminarVenta(1);

        // Puede devolver true (bool) o array con success, aceptamos ambos
        if (is_array($resultado)) {
            $this->assertTrue($resultado['success'] ?? false);
        } else {
            $this->assertTrue((bool)$resultado);
        }
    }
}
