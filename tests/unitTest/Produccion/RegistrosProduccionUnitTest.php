<?php

namespace Tests\UnitTest\Produccion;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\ProduccionModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
class RegistrosProduccionUnitTest extends TestCase
{
    private ProduccionModel $model;
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
        $this->mockStmt->shouldReceive('fetch')->withNoArgs()->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();
        $this->mockStmt->shouldReceive('fetchColumn')->andReturn(false)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('commit')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('rollBack')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('50')->byDefault();
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(true)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new ProduccionModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // ---------------------------------------------------------------
    // insertarRegistroProduccion
    // ---------------------------------------------------------------

    public static function providerInsertarRegistroCasosError(): array
    {
        return [
            'Lote no existe' => [
                'stubs' => 'lote_inexistente',
                'data'  => [
                    'idlote'               => 99999,
                    'idempleado'           => 1,
                    'fecha_jornada'        => date('Y-m-d'),
                    'idproducto_producir'  => 1,
                    'cantidad_producir'    => 100,
                    'idproducto_terminado' => 2,
                    'cantidad_producida'   => 90,
                    'tipo_movimiento'      => 'CLASIFICACION',
                ],
                'palabraClave' => 'lote',
            ],
            'Producto a producir no existe' => [
                'stubs' => 'producto_producir_inexistente',
                'data'  => [
                    'idlote'               => 1,
                    'idempleado'           => 1,
                    'fecha_jornada'        => date('Y-m-d'),
                    'idproducto_producir'  => 99999,
                    'cantidad_producir'    => 100,
                    'idproducto_terminado' => 2,
                    'cantidad_producida'   => 90,
                    'tipo_movimiento'      => 'CLASIFICACION',
                ],
                'palabraClave' => 'producir',
            ],
        ];
    }

    #[Test]
    #[DataProvider('providerInsertarRegistroCasosError')]
    public function testInsertarRegistroProduccion_CasosError_RetornaStatusFalse(
        string $stubs,
        array $data,
        string $palabraClave
    ): void {
        $configFake = [
            'productividad_clasificacion' => 150,
            'capacidad_maxima_planta' => 50,
            'salario_base' => 30,
            'beta_clasificacion' => 0.25,
            'gamma_empaque' => 5,
            'umbral_error_maximo' => 5,
            'peso_minimo_paca' => 25,
            'peso_maximo_paca' => 35,
        ];

        if ($stubs === 'lote_inexistente') {
            // fetch(PDO::FETCH_ASSOC) → usado por obtenerConfiguracion
            $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn($configFake);
            // fetch() sin args → usado por validación de lote (retorna false: lote no existe)
            $this->mockStmt->shouldReceive('fetch')->withNoArgs()->andReturn(false);
        } elseif ($stubs === 'producto_producir_inexistente') {
            // fetch(PDO::FETCH_ASSOC): config → null producto (no existe)
            $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
                ->andReturn($configFake, false);
            // fetch() sin args: lote existe → empleado existe
            $this->mockStmt->shouldReceive('fetch')->withNoArgs()
                ->andReturn(['idlote' => 1], ['idempleado' => 1]);
        }

        $result = $this->model->insertarRegistroProduccion($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsString($palabraClave, strtolower($result['message']));
    }

    #[Test]
    public function testInsertarRegistroProduccion_StockInsuficiente_RetornaStatusFalse(): void
    {
        $configFake = [
            'productividad_clasificacion' => 150,
            'capacidad_maxima_planta' => 50,
            'salario_base' => 30,
            'beta_clasificacion' => 0.25,
            'gamma_empaque' => 5,
            'umbral_error_maximo' => 5,
            'peso_minimo_paca' => 25,
            'peso_maximo_paca' => 35,
        ];
        $productoFake = ['existencia' => 5, 'nombre' => 'Prod Test', 'descripcion' => 'Desc'];

        // fetch(PDO::FETCH_ASSOC): config → stock del producto (insuficiente)
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn($configFake, $productoFake);
        // fetch() sin args: lote existe → empleado existe
        $this->mockStmt->shouldReceive('fetch')->withNoArgs()
            ->andReturn(['idlote' => 1], ['idempleado' => 1]);
        // precio proceso
        $this->mockStmt->shouldReceive('fetchColumn')->andReturn(false)->byDefault();

        $data = [
            'idlote'               => 1,
            'idempleado'           => 1,
            'fecha_jornada'        => date('Y-m-d'),
            'idproducto_producir'  => 1,
            'cantidad_producir'    => 9999,   // más de 5 disponibles
            'idproducto_terminado' => 2,
            'cantidad_producida'   => 90,
            'tipo_movimiento'      => 'CLASIFICACION',
        ];

        $result = $this->model->insertarRegistroProduccion($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsString('stock', strtolower($result['message']));
    }

    // ---------------------------------------------------------------
    // obtenerRegistrosPorLote
    // ---------------------------------------------------------------

    #[Test]
    public function testObtenerRegistrosPorLote_RetornaArrayConStatusYData(): void
    {
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([]);

        $result = $this->model->obtenerRegistrosPorLote(99999);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }

    #[Test]
    public function testObtenerRegistrosPorLote_CuandoFallaDB_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \Exception('DB error'));

        $result = $this->model->obtenerRegistrosPorLote(1);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    // ---------------------------------------------------------------
    // selectAllRegistrosProduccion
    // ---------------------------------------------------------------

    public static function providerFiltrosRegistros(): array
    {
        return [
            'Sin filtros'               => [[]],
            'Con filtro fechas'          => [['fecha_desde' => date('Y-m-d', strtotime('-30 days')), 'fecha_hasta' => date('Y-m-d')]],
            'Con filtro tipo movimiento' => [['tipo_movimiento' => 'CLASIFICACION']],
            'Con filtro idlote'          => [['idlote' => 1]],
        ];
    }

    #[Test]
    #[DataProvider('providerFiltrosRegistros')]
    public function testSelectAllRegistrosProduccion_ConDiversosFiltros_RetornaArrayConData(array $filtros): void
    {
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([]);

        $result = $this->model->selectAllRegistrosProduccion($filtros);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
    }

    // ---------------------------------------------------------------
    // actualizarRegistroProduccion
    // ---------------------------------------------------------------

    #[Test]
    public function testActualizarRegistroProduccion_RegistroNoExiste_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);

        $result = $this->model->actualizarRegistroProduccion(99999, [
            'fecha_jornada'        => date('Y-m-d'),
            'cantidad_producida'   => 100,
            'tipo_movimiento'      => 'CLASIFICACION',
            'idproducto_producir'  => 1,
            'cantidad_producir'    => 100,
            'idproducto_terminado' => 2,
        ]);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    #[Test]
    public function testActualizarRegistroProduccion_RegistroNoEnBorrador_RetornaStatusFalse(): void
    {
        $registroFake = [
            'idregistro'           => 5,
            'estatus'              => 'ENVIADO',
            'idlote'               => 1,
            'idproducto_producir'  => 1,
            'cantidad_producir'    => 100,
            'idproducto_terminado' => 2,
            'cantidad_producida'   => 90,
        ];
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn($registroFake);

        $result = $this->model->actualizarRegistroProduccion(5, [
            'fecha_jornada'        => date('Y-m-d'),
            'cantidad_producida'   => 100,
            'tipo_movimiento'      => 'CLASIFICACION',
            'idproducto_producir'  => 1,
            'cantidad_producir'    => 100,
            'idproducto_terminado' => 2,
        ]);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsString('BORRADOR', $result['message']);
    }

    // ---------------------------------------------------------------
    // eliminarRegistroProduccion
    // ---------------------------------------------------------------

    #[Test]
    public function testEliminarRegistroProduccion_RegistroNoExiste_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);

        $result = $this->model->eliminarRegistroProduccion(99999);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    #[Test]
    public function testEliminarRegistroProduccion_RegistroNoEnBorrador_RetornaStatusFalse(): void
    {
        $registroFake = [
            'idregistro'           => 6,
            'estatus'              => 'PAGADO',
            'idlote'               => 1,
            'idproducto_producir'  => 1,
            'cantidad_producir'    => 100,
            'idproducto_terminado' => 2,
            'cantidad_producida'   => 90,
        ];
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn($registroFake);

        $result = $this->model->eliminarRegistroProduccion(6);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsString('BORRADOR', $result['message']);
    }

    // ---------------------------------------------------------------
    // getRegistroById
    // ---------------------------------------------------------------

    #[Test]
    public function testGetRegistroById_RegistroNoExiste_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);

        $result = $this->model->getRegistroById(99999);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    #[Test]
    public function testGetRegistroById_RegistroExiste_RetornaStatusTrue(): void
    {
        $registroFake = ['idregistro' => 3, 'tipo_movimiento' => 'CLASIFICACION', 'estatus' => 'BORRADOR'];
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn($registroFake);

        $result = $this->model->getRegistroById(3);

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
        $this->assertArrayHasKey('data', $result);
    }
}
