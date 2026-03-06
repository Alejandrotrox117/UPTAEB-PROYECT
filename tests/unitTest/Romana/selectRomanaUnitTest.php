<?php

namespace Tests\UnitTest\Romana;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\RomanaModel;
use Mockery;
use PDO;
use PDOStatement;
use PDOException;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class selectRomanaUnitTest extends TestCase
{
    private RomanaModel $model;
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
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new RomanaModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // ─── Data Providers ──────────────────────────────────────────────────────

    public static function providerRegistrosRomana(): array
    {
        return [
            'lista_con_varios_registros' => [
                [
                    ['idromana' => 1, 'peso' => 250.50, 'fecha' => '2026-03-01 08:00:00', 'estatus' => 'ACTIVO', 'fecha_creacion' => '2026-03-01'],
                    ['idromana' => 2, 'peso' => 310.00, 'fecha' => '2026-03-02 09:15:00', 'estatus' => 'ACTIVO', 'fecha_creacion' => '2026-03-02'],
                ],
            ],
            'lista_con_un_registro' => [
                [
                    ['idromana' => 5, 'peso' => 99.99, 'fecha' => '2026-03-05 12:00:00', 'estatus' => 'INACTIVO', 'fecha_creacion' => '2026-03-05'],
                ],
            ],
            'lista_vacia' => [[]],
        ];
    }

    // ─── Tests: selectAllRomana - camino exitoso ──────────────────────────────

    #[Test]
    #[DataProvider('providerRegistrosRomana')]
    public function testSelectAllRomana_RetornaStatusTrueConData(array $registrosSimulados): void
    {
        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($registrosSimulados);

        $resultado = $this->model->selectAllRomana();

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertTrue($resultado['status']);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertCount(count($registrosSimulados), $resultado['data']);
    }

    #[Test]
    public function testSelectAllRomana_CadaRegistroTieneEstructuraCorrecta(): void
    {
        $registroFake = [
            'idromana'      => 3,
            'peso'          => 175.25,
            'fecha'         => '2026-03-03 10:30:00',
            'estatus'       => 'ACTIVO',
            'fecha_creacion' => '2026-03-03',
        ];
        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn([$registroFake]);

        $resultado = $this->model->selectAllRomana();

        $this->assertTrue($resultado['status']);
        $this->assertNotEmpty($resultado['data']);

        $primer = $resultado['data'][0];
        $this->assertArrayHasKey('idromana', $primer);
        $this->assertArrayHasKey('peso', $primer);
        $this->assertArrayHasKey('fecha', $primer);
        $this->assertArrayHasKey('estatus', $primer);
        $this->assertArrayHasKey('fecha_creacion', $primer);
    }

    // ─── Tests: selectAllRomana - camino de error (PDOException) ─────────────

    #[Test]
    public function testSelectAllRomana_CuandoFallaDB_RetornaStatusFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new PDOException('Simulated DB error'));

        $resultado = $this->model->selectAllRomana();

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertEmpty($resultado['data']);
        $this->assertArrayHasKey('message', $resultado);
    }

    #[Test]
    public function testSelectAllRomana_CuandoFallaEjecucion_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')
            ->andThrow(new PDOException('Execute failed'));

        $resultado = $this->model->selectAllRomana();

        $this->assertFalse($resultado['status']);
        $this->assertSame([], $resultado['data']);
        $this->assertStringContainsString('Error', $resultado['message']);
    }

    #[Test]
    public function testSelectAllRomana_MensajeErrorEsElEsperado(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new PDOException('Connection lost'));

        $resultado = $this->model->selectAllRomana();

        $this->assertSame('Error al obtener los registros', $resultado['message']);
    }
}
