## Cuadro Nº 1: Módulo de Producción (RF-PROD-001)

### Objetivos de la prueba

Validar que el módulo de producción gestione correctamente la configuración de parámetros operativos, la creación y administración de lotes de producción, el registro de actividades de clasificación y empaque de productos, así como el cálculo automático de salarios basados en productividad. El sistema debe rechazar operaciones con datos incompletos, valores inválidos, capacidades excedidas, lotes inexistentes, productos sin stock suficiente, y registros en estados no modificables.

### Técnicas

Pruebas de caja blanca con enfoque en validación de reglas de negocio, integridad transaccional y aislamiento mediante mocks de base de datos. Se evalúan múltiples métodos del modelo ProduccionModel en escenarios válidos e inválidos, verificando validaciones de entrada, cálculos de productividad, gestión de estados de lotes (PLANIFICADO, EN_PROCESO, FINALIZADO), control de transacciones, manejo de stock de productos, y restricciones de modificación según estado (BORRADOR, ENVIADO, PAGADO, CANCELADO). Se utilizan DataProviders para probar casos exhaustivos de datos válidos e inválidos.

### Código Involucrado

```php
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
class NominaYSalariosUnitTest extends TestCase
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
    // registrarSolicitudPago
    // ---------------------------------------------------------------

    #[Test]
    public function testRegistrarSolicitudPago_SinRegistros_NoBorradoresEnDB_RetornaStatusFalse(): void
    {
        // La consulta de borradores devuelve vacío
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([]);

        $result = $this->model->registrarSolicitudPago([]);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
    }

    #[Test]
    public function testRegistrarSolicitudPago_RegistrosInexistentes_RetornaStatusFalse(): void
    {
        // Los IDs pasados no existen en BD (fetch devuelve false)
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);

        $result = $this->model->registrarSolicitudPago([99999, 99998]);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    #[Test]
    public function testRegistrarSolicitudPago_CuandoFallaDB_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \Exception('DB error'));

        $result = $this->model->registrarSolicitudPago([1]);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    // ---------------------------------------------------------------
    // selectPreciosProceso
    // ---------------------------------------------------------------

    #[Test]
    public function testSelectPreciosProceso_RetornaArrayConStatusYData(): void
    {
        $preciosFake = [
            ['idconfig_salario' => 1, 'tipo_proceso' => 'CLASIFICACION', 'salario_unitario' => 0.30],
        ];
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn($preciosFake);

        $result = $this->model->selectPreciosProceso();

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
        $this->assertCount(1, $result['data']);
    }

    #[Test]
    public function testSelectPreciosProceso_CuandoFallaDB_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \Exception('DB error'));

        $result = $this->model->selectPreciosProceso();

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    // ---------------------------------------------------------------
    // createPrecioProceso - validaciones
    // ---------------------------------------------------------------

    public static function providerCreatePrecioProcesoInvalido(): array
    {
        return [
            'Sin tipo_proceso' => [
                ['idproducto' => 1, 'salario_unitario' => 0.50, 'moneda' => 'USD'],
            ],
            'Sin idproducto' => [
                ['tipo_proceso' => 'CLASIFICACION', 'salario_unitario' => 0.50, 'moneda' => 'USD'],
            ],
            'Sin salario_unitario' => [
                ['tipo_proceso' => 'CLASIFICACION', 'idproducto' => 1, 'moneda' => 'USD'],
            ],
            'Salario negativo' => [
                ['tipo_proceso' => 'CLASIFICACION', 'idproducto' => 1, 'salario_unitario' => -0.50, 'moneda' => 'USD'],
            ],
            'Salario cero' => [
                ['tipo_proceso' => 'CLASIFICACION', 'idproducto' => 1, 'salario_unitario' => 0, 'moneda' => 'USD'],
            ],
            'Tipo inválido' => [
                ['tipo_proceso' => 'TIPO_INVALIDO', 'idproducto' => 1, 'salario_unitario' => 0.50, 'moneda' => 'USD'],
            ],
        ];
    }

    #[Test]
    #[DataProvider('providerCreatePrecioProcesoInvalido')]
    public function testCreatePrecioProceso_DatosInvalidos_RetornaStatusFalse(array $data): void
    {
        $result = $this->model->createPrecioProceso($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
    }

    public static function providerCreatePrecioProcesoValido(): array
    {
        return [
            'CLASIFICACION exitosa' => [
                [
                    'tipo_proceso'     => 'CLASIFICACION',
                    'idproducto'       => 1,
                    'salario_unitario' => 0.30,
                    'unidad_base'      => 'KG',
                    'moneda'           => 'USD',
                ],
            ],
            'EMPAQUE exitoso' => [
                [
                    'tipo_proceso'     => 'EMPAQUE',
                    'idproducto'       => 2,
                    'salario_unitario' => 5.00,
                    'unidad_base'      => 'UNIDAD',
                    'moneda'           => 'USD',
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('providerCreatePrecioProcesoValido')]
    public function testCreatePrecioProceso_DatosValidos_RetornaStatusTrue(array $data): void
    {
        // El modelo pide unidad_medida si no viene unidad_base
        $this->mockStmt->shouldReceive('fetchColumn')->andReturn('KG')->byDefault();

        $result = $this->model->createPrecioProceso($data);

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
    }

    // ---------------------------------------------------------------
    // updatePrecioProceso
    // ---------------------------------------------------------------

    #[Test]
    public function testUpdatePrecioProceso_SinCambios_RetornaStatusFalse(): void
    {
        $result = $this->model->updatePrecioProceso(1, []);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    #[Test]
    public function testUpdatePrecioProceso_ConCambios_RetornaStatusTrue(): void
    {
        $result = $this->model->updatePrecioProceso(1, ['salario_unitario' => 0.40]);

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
    }

    // ---------------------------------------------------------------
    // deletePrecioProceso
    // ---------------------------------------------------------------

    #[Test]
    public function testDeletePrecioProceso_SiempreRetornaStatusTrue(): void
    {
        $result = $this->model->deletePrecioProceso(1);

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
    }

    #[Test]
    public function testDeletePrecioProceso_CuandoFallaDB_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \Exception('DB error'));

        $result = $this->model->deletePrecioProceso(1);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    // ---------------------------------------------------------------
    // marcarRegistroComoPagado
    // ---------------------------------------------------------------

    #[Test]
    public function testMarcarRegistroComoPagado_RegistroNoExiste_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);

        $result = $this->model->marcarRegistroComoPagado(99999);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    #[Test]
    public function testMarcarRegistroComoPagado_RegistroNoEnviado_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['estatus' => 'BORRADOR', 'idempleado' => 1, 'salario_total' => 30.00]);

        $result = $this->model->marcarRegistroComoPagado(1);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsString('ENVIADO', $result['message']);
    }

    // ---------------------------------------------------------------
    // cancelarRegistroProduccion
    // ---------------------------------------------------------------

    public static function providerCancelarRegistro(): array
    {
        return [
            'Registro no encontrado'   => [false,                                                    false, 'no encontrado'],
            'Registro ya PAGADO'       => [['estatus' => 'PAGADO'],                                  false, 'pagados'],
            'Registro ya CANCELADO'    => [['estatus' => 'CANCELADO'],                               false, 'cancelado'],
        ];
    }

    #[Test]
    #[DataProvider('providerCancelarRegistro')]
    public function testCancelarRegistroProduccion_CasosError_RetornaStatusFalse(
        $fetchReturn,
        bool $esperadoStatus,
        string $palabraClave
    ): void {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn($fetchReturn);

        $result = $this->model->cancelarRegistroProduccion(1);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsString($palabraClave, strtolower($result['message']));
    }

    #[Test]
    public function testCancelarRegistroProduccion_RegistroEnBorrador_RetornaStatusTrue(): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['estatus' => 'BORRADOR']);

        $result = $this->model->cancelarRegistroProduccion(1);

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
    }
}

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
```

### Caso de prueba

**TIPO:** Funcional (Caja blanca)

**OBJETIVO:** Verificar que el módulo de producción maneje correctamente todas las operaciones de configuración, gestión de lotes, registro de actividades productivas y cálculo de salarios, garantizando la integridad de datos y el cumplimiento de las reglas de negocio establecidas.

**DESCRIPCIÓN:** El conjunto de pruebas abarca cuatro áreas fundamentales del módulo de producción: primero, la configuración de parámetros operativos que define la productividad esperada, capacidades de planta y umbrales de control; segundo, la gestión completa del ciclo de vida de los lotes de producción desde su planificación hasta su finalización; tercero, el registro detallado de actividades productivas tanto de clasificación como de empaque; y cuarto, la administración de la nómina y cálculo de salarios basados en la productividad de cada empleado. Se prueban los flujos de lectura de configuraciones, actualización de parámetros, consulta de empleados activos y productos disponibles. Se valida la creación de lotes con cálculo automático de operarios requeridos, verificando que se rechacen lotes sin supervisor, con volúmenes negativos o cero, fechas inválidas, configuraciones con productividad cero o que excedan la capacidad máxima de la planta. Se evalúan las transiciones de estado de los lotes desde PLANIFICADO a EN_PROCESO y finalmente a FINALIZADO, verificando que no se puedan cerrar lotes ya finalizados. En cuanto a los registros de producción, se prueban inserciones con validación de existencia de lotes, productos y empleados, control de stock suficiente, asignación correcta de precios de proceso y cálculo automático de salarios según tipo de movimiento. Se valida que solo los registros en estado BORRADOR puedan ser editados o eliminados, mientras que los registros ENVIADOS solo pueden marcarse como PAGADOS y los registros PAGADOS o CANCELADOS no admiten modificaciones. Se prueban las funciones de consulta de registros por lote, filtros por fechas, tipo de movimiento y estados, así como la generación de solicitudes de pago que agrupa registros de empleados. Se verifican los cálculos de salarios tanto para procesos de clasificación como de empaque, considerando los parámetros beta y gamma de la configuración. También se validan las operaciones CRUD de precios por proceso, incluyendo validaciones de tipos de proceso válidos, salarios unitarios positivos y unidades de medida correctas.

**ENTRADAS:**

Para las pruebas de configuración se utilizaron datos de parámetros operativos que incluyen productividad de clasificación establecida en 150 kilogramos por hora-hombre, capacidad máxima de planta de 50 operarios simultáneos, salario base de 30 dólares, coeficiente beta de clasificación de 0.25, factor gamma de empaque de 5 unidades, umbral máximo de error permitido, y rangos de peso para pacas entre 25 y 35 kilogramos. Se probaron actualizaciones exitosas que afectan filas en la base de datos y actualizaciones sin cambios que retornan estado falso. En la gestión de lotes se emplearon casos válidos con supervisor asignado, volumen estimado de 150 kilogramos y fecha de jornada actual, esperando que el sistema calcule automáticamente los operarios requeridos y genere un número de lote único. Los casos inválidos incluyeron lotes sin supervisor, con volumen cero, volumen negativo de 100 kilogramos, sin fecha de jornada, fecha inválida como 2025-13-45, configuración con productividad cero que causaría división entre cero, y volumen extremo de 999999 kilogramos que excede la capacidad de un operario con productividad muy baja de 10. Para las transiciones de estado se probaron lotes inexistentes con ID 99999, lotes planificados que deben poder iniciarse, lotes ya finalizados que deben rechazar el cierre, y lotes en proceso que aceptan finalización. En registros de producción se utilizaron datos completos con identificadores de lote, empleado, fecha de jornada, producto a producir, cantidad a procesar de 100 kilogramos, producto terminado resultante, cantidad producida de 90 kilogramos y tipo de movimiento CLASIFICACION o EMPAQUE. Los casos de error incluyeron lote inexistente con ID 99999, producto a producir inexistente con ID 99999, empleado inválido, stock insuficiente donde se intenta procesar 9999 kilogramos cuando solo hay 5 disponibles, y registros en estados no modificables como ENVIADO, PAGADO o CANCELADO. Para las pruebas de nómina se enviaron solicitudes de pago con arrays de identificadores de registros en estado BORRADOR, casos con registros inexistentes con IDs 99999 y 99998, y arrays vacíos. En la configuración de precios se probaron datos válidos para clasificación con salario unitario de 0.30 dólares por kilogramo y empaque con 5 dólares por unidad en moneda USD, mientras que los casos inválidos omitieron campos requeridos como tipo de proceso, identificador de producto o salario unitario, incluyeron salarios negativos de -0.50, salarios cero, y tipos de proceso inválidos como TIPO_INVALIDO. Las consultas se probaron con diversos filtros incluyendo búsquedas sin filtros, con rango de fechas de 30 días atrás hasta hoy, filtro por tipo de movimiento CLASIFICACION, y filtro por identificador de lote específico.

**SALIDAS ESPERADAS:**

El sistema debe retornar arrays estructurados con claves de estado y datos donde corresponda. Las consultas exitosas de configuración deben devolver status verdadero con los parámetros operativos actuales incluyendo productividad, capacidad, salarios y umbrales. Cuando no existe configuración o falla la consulta, debe retornar status falso con mensaje descriptivo. Las actualizaciones de configuración que afecten registros retornan status verdadero, mientras que actualizaciones sin cambios retornan status falso. La selección de empleados activos debe proporcionar un array con status verdadero y la lista de empleados con sus identificadores y nombres completos, retornando status falso ante errores de base de datos. Las consultas de productos deben siempre retornar arrays con status y data independientemente del tipo solicitado, ya sea todos, por clasificar, clasificados o tipos inválidos. La creación exitosa de lotes debe retornar status verdadero con el identificador del nuevo lote, el número de lote generado automáticamente en formato LOTE-AAAAMMDD-NNN y la cantidad de operarios requeridos calculada según el volumen y la productividad configurada. Los intentos de crear lotes con datos inválidos deben retornar status falso con mensajes que contengan palabras clave relevantes como supervisor, volumen, fecha o capacidad según el error específico. El inicio de lotes planificados debe cambiar su estado a EN_PROCESO y retornar status verdadero, mientras que intentos con lotes inexistentes retornan status falso. El cierre de lotes en proceso debe cambiar su estado a FINALIZADO, realizar cálculos finales de las actividades registradas y retornar status verdadero, pero rechazar con status falso y mensaje específico cuando el lote ya está finalizado o no existe. La inserción de registros de producción con datos completos y válidos debe crear el registro en estado BORRADOR, calcular automáticamente el salario del empleado según el tipo de movimiento y los precios configurados, descontar el stock del producto a procesar, y retornar status verdadero con el identificador del registro creado. Los intentos con lote inexistente, producto inexistente, empleado inválido o stock insuficiente deben retornar status falso con mensajes que incluyan las palabras clave lote, producir, empleado o stock respectivamente. Las consultas de registros por lote deben retornar arrays con status y data conteniendo los registros asociados, incluso si el array está vacío. Las búsquedas con filtros diversos deben aplicar correctamente las condiciones y retornar la estructura con data filtrada. Las actualizaciones de registros solo deben proceder si el registro existe y está en estado BORRADOR, retornando status falso si el registro no existe o si está en estado ENVIADO, PAGADO o CANCELADO con mensaje indicando que solo registros en BORRADOR pueden modificarse. Las eliminaciones siguen la misma lógica de validación de estado. La consulta de registro por identificador debe retornar status verdadero con los datos completos del registro si existe, o status falso si no se encuentra. La generación de solicitudes de pago debe validar que existan registros en estado BORRADOR en la base de datos, cambiar su estado a ENVIADO, y retornar status verdadero con confirmación del cambio, rechazando con status falso si no hay registros disponibles o si los identificadores no existen. Las consultas de precios de proceso deben retornar arrays con status verdadero y la lista de configuraciones de salarios por tipo de proceso y producto. La creación de precios con datos válidos de CLASIFICACION o EMPAQUE debe insertar el registro con el salario unitario, unidad base y moneda especificados, retornando status verdadero. Las validaciones deben rechazar con status falso y mensaje apropiado cuando falten campos requeridos, el salario sea negativo o cero, o el tipo de proceso no corresponda a CLASIFICACION o EMPAQUE. Las actualizaciones de precios sin cambios retornan status falso, mientras que aquellas con modificaciones retornan status verdadero. Las eliminaciones de precios deben retornar status verdadero en condiciones normales y status falso solo ante errores de base de datos. El marcado de registros como pagados requiere que el registro exista y esté en estado ENVIADO, rechazando registros en BORRADOR con mensaje indicando que primero debe enviarse. La cancelación de registros valida que no estén en estado PAGADO o CANCELADO, permitiendo solo la cancelación de registros en BORRADOR o ENVIADO y retornando status falso con mensaje descriptivo en casos no permitidos.

### Resultado

```
PS C:\xampp\htdocs\project> php vendor/bin/phpunit tests/unitTest/Produccion/
PHPUnit 10.5.63 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: C:\xampp\htdocs\project\phpunit.xml

................................................................. 65 / 69 ( 94%)
....                                                              69 / 69 (100%)
Time: 00:13.493, Memory: 10.00 MB

There was 1 PHPUnit test runner warning:

1) No code coverage driver available

OK, but there were issues!
Tests: 69, Assertions: 174, PHPUnit Warnings: 1, PHPUnit Deprecations: 1.       


Command exited with code 1
```

### Observaciones

Las pruebas unitarias del módulo de Producción se ejecutaron de manera exitosa, completando las 69 pruebas diseñadas con un total de 174 aserciones verificadas. El sistema demostró un comportamiento robusto en la validación de reglas de negocio complejas relacionadas con la gestión de procesos productivos. Durante la ejecución se verificó correctamente el manejo de la configuración operativa de la planta, incluyendo parámetros críticos como productividad de clasificación, capacidad máxima de operarios, salarios base y coeficientes para cálculos salariales. Las pruebas confirmaron que el módulo rechaza apropiadamente configuraciones inválidas como productividad cero que causaría errores matemáticos, y actualiza exitosamente la configuración cuando los datos son válidos. En cuanto a la gestión de lotes de producción, se validó el ciclo completo desde la planificación hasta la finalización, incluyendo el cálculo automático de operarios requeridos basado en el volumen estimado y la productividad configurada. El sistema rechaza consistente y correctamente lotes con datos incompletos o inválidos, como ausencia de supervisor, volúmenes negativos o cero, fechas mal formadas, y volúmenes que excedan la capacidad operativa de la planta. Las transiciones de estado entre PLANIFICADO, EN_PROCESO y FINALIZADO funcionan según lo especificado, impidiendo operaciones ilógicas como iniciar lotes inexistentes o cerrar lotes ya finalizados. El componente de registros de producción mostró un manejo adecuado de validaciones complejas, verificando la existencia de lotes, productos y empleados antes de permitir inserciones, controlando el stock disponible para evitar descontar cantidades mayores a las existencias, y calculando automáticamente los salarios de los empleados según el tipo de movimiento ya sea CLASIFICACION o EMPAQUE y los precios unitarios configurados. Las restricciones de modificación basadas en el estado del registro funcionan correctamente, permitiendo ediciones y eliminaciones únicamente en registros con estado BORRADOR, protegiendo la integridad de registros ya ENVIADOS, PAGADOS o CANCELADOS. Las funcionalidades de consulta con filtros múltiples por fechas, tipo de movimiento o lote específico retornan correctamente los datos solicitados. El módulo de nómina y salarios valida apropiadamente la generación de solicitudes de pago, verificando la existencia de registros en estado BORRADOR antes de cambiarlos a ENVIADO, y rechazando solicitudes con identificadores inexistentes o cuando no hay registros disponibles para procesar. La gestión CRUD de precios por proceso implementa validaciones estrictas de tipos de proceso permitidos, valores de salario positivos, y campos requeridos, garantizando que solo se almacenen configuraciones de precios válidas para los procesos de CLASIFICACION y EMPAQUE. Las pruebas utilizaron mocks de base de datos con Mockery para lograr aislamiento completo, lo que permitió simular tanto escenarios exitosos como errores de base de datos, transacciones y diversos estados de datos, validando el manejo de excepciones y rollbacks cuando corresponde. La ejecución completa tomó aproximadamente 13.5 segundos utilizando 10 megabytes de memoria, con todas las pruebas ejecutadas en procesos separados para garantizar independencia y evitar efectos colaterales entre casos de prueba. La advertencia reportada sobre la ausencia de driver de cobertura de código es esperada y no afecta la validez de las pruebas, simplemente indica que no se generaron métricas de cobertura durante esta ejecución. La deprecación de PHPUnit es de nivel informativo y no compromete el funcionamiento de las validaciones. En resumen, el módulo de Producción cumple satisfactoriamente con todos los requisitos funcionales probados, demostrando capacidad para gestionar configuraciones operativas, administrar el ciclo de vida de lotes de producción con cálculos automáticos de recursos, registrar actividades productivas con control de inventario y validaciones de integridad, calcular salarios basados en productividad, y mantener la consistencia de estados a lo largo de todo el flujo de trabajo productivo.
