<?php

namespace Tests\UnitTest\Pagos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\PagosModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class consultarPagosUnitTest extends TestCase
{
    private PagosModel $pagosModel;
    /** @var \Mockery\MockInterface */
    private $mockPdo;
    /** @var \Mockery\MockInterface */
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');

        $this->mockPdo  = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        // Comportamientos por defecto
        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn("0")->byDefault();

        // Sobrecargar Conexion
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->pagosModel = new PagosModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // -----------------------------------------------------------------------
    // DataProviders
    // -----------------------------------------------------------------------

    public static function providerSelectAllPagos(): array
    {
        $pagoMock = [
            'idpago'             => 1,
            'monto'              => '500.0000',
            'referencia'         => 'REF-001',
            'fecha_pago'         => '2026-03-05',
            'fecha_pago_formato' => '05/03/2026',
            'observaciones'      => 'Test',
            'estatus'            => 'activo',
            'metodo_pago'        => 'Transferencia',
            'tipo_pago_texto'    => 'Compra',
            'destinatario'       => 'Juan Perez',
        ];

        return [
            'Lista con pagos' => [
                [$pagoMock, array_merge($pagoMock, ['idpago' => 2, 'referencia' => 'REF-002'])],
                true,
                2,
            ],
            'Lista vacía' => [
                [],
                true,
                0,
            ],
        ];
    }

    public static function providerSelectPagoById(): array
    {
        return [
            'ID existente retorna datos' => [
                5,
                [
                    'idpago'             => 5,
                    'monto'              => '750.0000',
                    'referencia'         => 'REF-005',
                    'fecha_pago'         => '2026-03-01',
                    'fecha_pago_formato' => '01/03/2026',
                    'estatus'            => 'activo',
                    'metodo_pago'        => 'Efectivo',
                    'tipo_pago_texto'    => 'Venta',
                    'destinatario'       => 'Maria Garcia',
                ],
                true,
            ],
            'ID inexistente retorna status false' => [
                99999,
                false,
                false,
            ],
        ];
    }

    // -----------------------------------------------------------------------
    // Tests de selectAllPagos
    // -----------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerSelectAllPagos')]
    public function testSelectAllPagos_RetornaArrayConStatus(array $filasMock, bool $statusEsperado, int $cantidadEsperada): void
    {
        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($filasMock);

        $resultado = $this->pagosModel->selectAllPagos();

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertEquals($statusEsperado, $resultado['status']);
        $this->assertCount($cantidadEsperada, $resultado['data']);
    }

    #[Test]
    public function testSelectAllPagos_CuandoExcepcionBD_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')
            ->andThrow(new \PDOException('Connection lost'));

        $resultado = $this->pagosModel->selectAllPagos();

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertArrayHasKey('message', $resultado);
    }

    // -----------------------------------------------------------------------
    // Tests de selectPagoById
    // -----------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerSelectPagoById')]
    public function testSelectPagoById_SegunIdExistenciaRetornaEstadoCorrecto(int $idpago, mixed $filaMock, bool $statusEsperado): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($filaMock);

        $resultado = $this->pagosModel->selectPagoById($idpago);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertEquals($statusEsperado, $resultado['status']);

        if ($statusEsperado) {
            $this->assertArrayHasKey('data', $resultado);
            $this->assertEquals($idpago, $resultado['data']['idpago']);
        } else {
            $this->assertArrayHasKey('message', $resultado);
        }
    }

    #[Test]
    public function testSelectPagoById_CuandoExcepcionBD_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')
            ->andThrow(new \PDOException('Timeout'));

        $resultado = $this->pagosModel->selectPagoById(1);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
    }
}
