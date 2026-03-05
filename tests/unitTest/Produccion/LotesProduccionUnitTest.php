<?php

namespace Tests\UnitTest\Produccion;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\ProduccionModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class LotesProduccionUnitTest extends TestCase
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
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();
        $this->mockStmt->shouldReceive('fetchColumn')->andReturn(false)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('commit')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('rollBack')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('99')->byDefault();
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(false)->byDefault();

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
    // selectAllLotes
    // ---------------------------------------------------------------

    #[Test]
    public function testSelectAllLotes_RetornaArrayConStatusYData(): void
    {
        $lotesFake = [
            ['idlote' => 1, 'numero_lote' => 'LOTE-20260101-001', 'estatus_lote' => 'PLANIFICADO'],
        ];
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn($lotesFake);

        $result = $this->model->selectAllLotes();

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
        $this->assertArrayHasKey('data', $result);
    }

    #[Test]
    public function testSelectAllLotes_CuandoFallaDB_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \Exception('DB error'));

        $result = $this->model->selectAllLotes();

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    // ---------------------------------------------------------------
    // selectLoteById
    // ---------------------------------------------------------------

    #[Test]
    public function testSelectLoteById_CuandoNoExisteId_RetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);

        $result = $this->model->selectLoteById(99999);

        $this->assertFalse($result);
    }

    #[Test]
    public function testSelectLoteById_CuandoExiste_RetornaArray(): void
    {
        $loteFake = ['idlote' => 5, 'numero_lote' => 'LOTE-20260101-005', 'estatus_lote' => 'PLANIFICADO'];
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn($loteFake);

        $result = $this->model->selectLoteById(5);

        $this->assertIsArray($result);
        $this->assertEquals(5, $result['idlote']);
    }

    // ---------------------------------------------------------------
    // insertLote - validaciones (sin BD real)
    // ---------------------------------------------------------------

    public static function providerInsertLoteInvalido(): array
    {
        return [
            'Sin supervisor' => [
                ['volumen_estimado' => 100, 'fecha_jornada' => date('Y-m-d')],
                'supervisor',
            ],
            'Volumen cero' => [
                ['idsupervisor' => 1, 'volumen_estimado' => 0, 'fecha_jornada' => date('Y-m-d')],
                'volumen',
            ],
            'Volumen negativo' => [
                ['idsupervisor' => 1, 'volumen_estimado' => -100, 'fecha_jornada' => date('Y-m-d')],
                'volumen',
            ],
            'Sin fecha jornada' => [
                ['idsupervisor' => 1, 'volumen_estimado' => 100],
                'fecha',
            ],
            'Fecha inválida' => [
                ['idsupervisor' => 1, 'volumen_estimado' => 100, 'fecha_jornada' => '2025-13-45'],
                'fecha',
            ],
        ];
    }

    #[Test]
    #[DataProvider('providerInsertLoteInvalido')]
    public function testInsertLote_DatosInvalidos_RetornaStatusFalseConMensaje(
        array $data,
        string $palabraClave
    ): void {
        $result = $this->model->insertLote($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsString($palabraClave, strtolower($result['message']));
    }

    #[Test]
    public function testInsertLote_ConfiguracionConProductividadCero_RetornaStatusFalse(): void
    {
        // Cuando la BD devuelve un config con productividad_clasificacion = 0,
        // el modelo debe rechazarlo (no puede dividir entre 0)
        $configInvalida = [
            'productividad_clasificacion' => 0,
            'capacidad_maxima_planta'      => 50,
            'salario_base'                 => 30,
            'beta_clasificacion'           => 0.25,
            'gamma_empaque'                => 5,
            'umbral_error_maximo'          => 5,
            'peso_minimo_paca'             => 25,
            'peso_maximo_paca'             => 35,
        ];
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn($configInvalida);

        $data = [
            'idsupervisor'    => 1,
            'volumen_estimado' => 150,
            'fecha_jornada'   => date('Y-m-d'),
        ];

        $result = $this->model->insertLote($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    #[Test]
    public function testInsertLote_ExcedeCapacidadMaxima_RetornaStatusFalse(): void
    {
        // Configuración con capacidad muy baja para que falle
        $configFake = [
            'productividad_clasificacion' => 10,   // muy baja
            'capacidad_maxima_planta'      => 1,    // solo 1 operario
            'salario_base'                 => 30,
            'beta_clasificacion'           => 0.25,
            'gamma_empaque'                => 5,
            'umbral_error_maximo'          => 5,
            'peso_minimo_paca'             => 25,
            'peso_maximo_paca'             => 35,
        ];
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn($configFake)->byDefault();
        // Para el número de lote (fetchColumn)
        $this->mockStmt->shouldReceive('fetchColumn')->andReturn(0)->byDefault();

        $data = [
            'idsupervisor'    => 1,
            'volumen_estimado' => 999999,  // excede capacidad
            'fecha_jornada'   => date('Y-m-d'),
        ];

        $result = $this->model->insertLote($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsString('capacidad', strtolower($result['message']));
    }

    #[Test]
    public function testInsertLote_DatosCompletos_RetornaStatusTrue(): void
    {
        $configFake = [
            'productividad_clasificacion' => 150,
            'capacidad_maxima_planta'      => 50,
            'salario_base'                 => 30,
            'beta_clasificacion'           => 0.25,
            'gamma_empaque'                => 5,
            'umbral_error_maximo'          => 5,
            'peso_minimo_paca'             => 25,
            'peso_maximo_paca'             => 35,
        ];

        // Primera llamada fetch → config; fetch para número lote (check duplicado) → null (no existe)
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn($configFake, null, ['existe' => 0]);
        $this->mockStmt->shouldReceive('fetchColumn')->andReturn('0')->byDefault();

        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('10');

        $data = [
            'idsupervisor'    => 1,
            'volumen_estimado' => 150,
            'fecha_jornada'   => date('Y-m-d'),
            'observaciones'   => 'Lote unit test',
        ];

        $result = $this->model->insertLote($data);

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
        $this->assertArrayHasKey('idlote', $result);
        $this->assertArrayHasKey('numero_lote', $result);
        $this->assertArrayHasKey('operarios_requeridos', $result);
    }

    // ---------------------------------------------------------------
    // iniciarLoteProduccion
    // ---------------------------------------------------------------

    #[Test]
    public function testIniciarLoteProduccion_LoteInexistente_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0);

        $result = $this->model->iniciarLoteProduccion(99999);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    #[Test]
    public function testIniciarLoteProduccion_LotePlanificado_RetornaStatusTrue(): void
    {
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1);

        $result = $this->model->iniciarLoteProduccion(1);

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
    }

    #[Test]
    public function testIniciarLoteProduccion_CuandoFallaDB_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \Exception('DB error'));

        $result = $this->model->iniciarLoteProduccion(1);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    // ---------------------------------------------------------------
    // cerrarLoteProduccion
    // ---------------------------------------------------------------

    #[Test]
    public function testCerrarLoteProduccion_LoteInexistente_RetornaStatusFalse(): void
    {
        // Primera consulta (verificar lote) devuelve false
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);

        $result = $this->model->cerrarLoteProduccion(99999);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    #[Test]
    public function testCerrarLoteProduccion_LoteYaFinalizado_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['estatus_lote' => 'FINALIZADO']);

        $result = $this->model->cerrarLoteProduccion(1);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsString('finalizado', strtolower($result['message']));
    }

    #[Test]
    public function testCerrarLoteProduccion_LoteEnProceso_RetornaStatusTrue(): void
    {
        // fetch: estado lote → EN_PROCESO
        // fetchAll: registros de producción → vacío
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['estatus_lote' => 'EN_PROCESO']);
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([]);
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(true);

        $result = $this->model->cerrarLoteProduccion(1);

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
    }

    #[Test]
    public function testCerrarLoteProduccion_CuandoFallaDB_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \Exception('DB error'));

        $result = $this->model->cerrarLoteProduccion(1);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
}
