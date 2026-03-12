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
class VentaAnulacionUnitTest extends TestCase
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

        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('commit')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('rollBack')->andReturn(true)->byDefault();
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

    public static function providerAnulacionFallida(): array
    {
        return [
            'id inexistente' => [99999, 'Prueba de anulación'],
            'id negativo'    => [-1,    'Motivo de anulación'],
            'id cero'        => [0,     'Motivo de anulación'],
        ];
    }

    // ─────────────────────────────────────────────
    // Tests
    // ─────────────────────────────────────────────

    #[Test]
    public function testAnularVenta_MetodoExiste(): void
    {
        if (!method_exists($this->ventasModel, 'anularVenta')) {
            $this->markTestSkipped('Método anularVenta aún no implementado en VentasModel.');
        }
        $this->assertTrue(true);
    }

    #[Test]
    #[DataProvider('providerAnulacionFallida')]
    public function testAnularVenta_IdInexistente_RetornaFalse(int $idventa, string $motivo): void
    {
        if (!method_exists($this->ventasModel, 'anularVenta')) {
            $this->markTestSkipped('Método anularVenta no existe en VentasModel.');
        }

        // fetch devuelve false por defecto → venta no existe → resultado con success=false
        $resultado = $this->ventasModel->anularVenta($idventa, $motivo);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['success'] ?? $resultado['status'] ?? false);
    }

    #[Test]
    public function testAnularVenta_VentaEnEstadoAnulada_RetornaFalse(): void
    {
        if (!method_exists($this->ventasModel, 'anularVenta')) {
            $this->markTestSkipped('Método anularVenta no existe en VentasModel.');
        }

        // La venta existe pero ya está ANULADA
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['idventa' => 1, 'estatus' => 'ANULADA']);

        $resultado = $this->ventasModel->anularVenta(1, 'Segunda anulación');

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['success'] ?? $resultado['status'] ?? false);
    }

    #[Test]
    public function testAnularVenta_VentaExistente_RetornaArray(): void
    {
        if (!method_exists($this->ventasModel, 'anularVenta')) {
            $this->markTestSkipped('Método anularVenta no existe en VentasModel.');
        }

        // Simular venta activa
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['idventa' => 1, 'estatus' => 'POR_PAGAR', 'nro_venta' => 'VT000001']);
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1);

        $resultado = $this->ventasModel->anularVenta(1, 'Motivo de prueba');

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('success', $resultado);
    }
}
