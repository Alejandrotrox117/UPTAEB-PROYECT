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
class ConfiguracionProduccionUnitTest extends TestCase
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
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('1')->byDefault();
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
    // selectConfiguracionProduccion
    // ---------------------------------------------------------------

    #[Test]
    public function testSelectConfiguracionProduccion_CuandoExisteConfiguracion_RetornaStatusTrue(): void
    {
        $configFake = [
            'idconfig' => 1,
            'productividad_clasificacion' => 150,
            'capacidad_maxima_planta' => 50,
            'salario_base' => 30,
            'beta_clasificacion' => 0.25,
            'gamma_empaque' => 5,
            'estatus' => 'activo',
        ];
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn($configFake);

        $result = $this->model->selectConfiguracionProduccion();

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
        $this->assertArrayHasKey('data', $result);
    }

    #[Test]
    public function testSelectConfiguracionProduccion_CuandoNoExisteConfiguracion_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);

        $result = $this->model->selectConfiguracionProduccion();

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
    }

    #[Test]
    public function testSelectConfiguracionProduccion_CuandoFallaConsulta_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \Exception('DB error'));

        $result = $this->model->selectConfiguracionProduccion();

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    // ---------------------------------------------------------------
    // updateConfiguracionProduccion
    // ---------------------------------------------------------------

    public static function providerUpdateConfiguracion(): array
    {
        return [
            'Actualización exitosa' => [
                [
                    'productividad_clasificacion' => 150,
                    'capacidad_maxima_planta'      => 50,
                    'salario_base'                 => 35,
                    'beta_clasificacion'           => 0.30,
                    'gamma_empaque'                => 6,
                    'umbral_error_maximo'          => 5,
                    'peso_minimo_paca'             => 25,
                    'peso_maximo_paca'             => 35,
                ],
                1,     // rowCount
                true,
            ],
            'Sin filas afectadas' => [
                [
                    'productividad_clasificacion' => 150,
                    'capacidad_maxima_planta'      => 50,
                    'salario_base'                 => 35,
                    'beta_clasificacion'           => 0.30,
                    'gamma_empaque'                => 6,
                    'umbral_error_maximo'          => 5,
                    'peso_minimo_paca'             => 25,
                    'peso_maximo_paca'             => 35,
                ],
                0,     // rowCount
                false,
            ],
        ];
    }

    #[Test]
    #[DataProvider('providerUpdateConfiguracion')]
    public function testUpdateConfiguracionProduccion(array $data, int $rowCount, bool $esperadoStatus): void
    {
        $this->mockStmt->shouldReceive('rowCount')->andReturn($rowCount);

        $result = $this->model->updateConfiguracionProduccion($data);

        $this->assertIsArray($result);
        $this->assertSame($esperadoStatus, $result['status']);
    }

    // ---------------------------------------------------------------
    // selectEmpleadosActivos
    // ---------------------------------------------------------------

    #[Test]
    public function testSelectEmpleadosActivos_RetornaArrayConStatus(): void
    {
        $empleadosFake = [
            ['idempleado' => 1, 'nombre_completo' => 'Juan Pérez'],
            ['idempleado' => 2, 'nombre_completo' => 'María López'],
        ];
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn($empleadosFake);

        $result = $this->model->selectEmpleadosActivos();

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
        $this->assertCount(2, $result['data']);
    }

    #[Test]
    public function testSelectEmpleadosActivos_CuandoFallaDB_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \Exception('DB error'));

        $result = $this->model->selectEmpleadosActivos();

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    // ---------------------------------------------------------------
    // selectProductos
    // ---------------------------------------------------------------

    public static function providerTiposProductos(): array
    {
        return [
            'tipo todos'          => ['todos'],
            'tipo por_clasificar' => ['por_clasificar'],
            'tipo clasificados'   => ['clasificados'],
            'tipo inválido'       => ['tipo_invalido'],
        ];
    }

    #[Test]
    #[DataProvider('providerTiposProductos')]
    public function testSelectProductos_SiempreRetornaArrayConStatus(string $tipo): void
    {
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([]);

        $result = $this->model->selectProductos($tipo);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }

    #[Test]
    public function testSelectProductos_CuandoFallaDB_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \Exception('DB error'));

        $result = $this->model->selectProductos('todos');

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
}
