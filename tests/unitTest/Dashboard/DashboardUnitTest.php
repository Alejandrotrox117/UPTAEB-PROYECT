<?php
declare(strict_types=1);

namespace Tests\UnitTest\Dashboard;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\DashboardModel;
use Mockery;
use PDO;
use PDOStatement;

/**
 * Pruebas Unitarias — DashboardModel
 *
 * Usa Mockery overload:App\Core\Conexion para interceptar toda creación de
 * Conexion y reemplazarla con mocks de PDO/PDOStatement.
 * No toca la base de datos real.
 *
 * Nota: DashboardModel usa patrón Lazy Load (getInstanciaModel) que crea
 * internamente una segunda instancia de DashboardModel — el overload de
 * Conexion captura ambas instancias correctamente.
 */
#[RunTestsInSeparateProcesses]
class DashboardUnitTest extends TestCase
{
    private DashboardModel $model;

    /** @var \Mockery\MockInterface */
    private $mockPdo;
    /** @var \Mockery\MockInterface */
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');

        $this->mockPdo  = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        // Defaults: todos los stmt devuelven un row genérico con las claves
        // necesarias para las consultas del DashboardModel.
        $resumenRow = [
            'ventas_hoy'           => 100.0,
            'ventas_ayer'          => 80.0,
            'compras_hoy'          => 50.0,
            'compras_ayer'         => 45.0,
            'valor_inventario'     => 10000.0,
            'producciones_activas' => 3,
            'productos_en_rotacion'=> 25,
            'eficiencia_promedio'  => 75.0,
            'fecha_consulta'       => date('Y-m-d'),
            // Claves para getAnalisisInventario sub-consultas
            'critico'  => 5,
            'normal'   => 20,
            'entradas' => 10,
            'salidas'  => 5,
        ];

        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn($resumenRow)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('0')->byDefault();

        // overload: intercepta todo new Conexion() del proceso actual
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new DashboardModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // ---------------------------------------------------------------
    // DataProviders
    // ---------------------------------------------------------------

    public static function providerRangosFechasValidos(): array
    {
        return [
            'año_actual'      => ['2026-01-01', '2026-12-31'],
            'mes_actual'      => ['2026-03-01', '2026-03-31'],
            'rango_amplio'    => ['2020-01-01', '2026-12-31'],
        ];
    }

    public static function providerRangosFechasInvalidos(): array
    {
        return [
            'fechas_invertidas' => ['2026-12-31', '2026-01-01'],
            'formato_invalido'  => ['no-es-fecha', 'tampoco'],
            'fecha_futura'      => ['2099-01-01', '2099-12-31'],
        ];
    }

    // ---------------------------------------------------------------
    // getResumen
    // ---------------------------------------------------------------

    #[Test]
    public function testGetResumen_RetornaEstructuraCorrecta(): void
    {
        $result = $this->model->getResumen();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('ventas_hoy',            $result);
        $this->assertArrayHasKey('ventas_ayer',           $result);
        $this->assertArrayHasKey('compras_hoy',           $result);
        $this->assertArrayHasKey('compras_ayer',          $result);
        $this->assertArrayHasKey('valor_inventario',      $result);
        $this->assertArrayHasKey('producciones_activas',  $result);
        $this->assertArrayHasKey('productos_en_rotacion', $result);
        $this->assertArrayHasKey('eficiencia_promedio',   $result);
        $this->assertArrayHasKey('fecha_consulta',        $result);
    }

    #[Test]
    public function testGetResumen_CuandoExcepcionEnBD_RetornaDatosPorDefecto(): void
    {
        $stmtFallo = Mockery::mock(PDOStatement::class);
        $stmtFallo->shouldReceive('execute')->andThrow(new \Exception('DB Error'));
        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtFallo);

        $result = $this->model->getResumen();

        $this->assertIsArray($result);
        // En caso de excepción el modelo retorna un array con claves y valores 0
        $this->assertArrayHasKey('ventas_hoy', $result);
        $this->assertEquals(0, $result['ventas_hoy']);
    }

    // ---------------------------------------------------------------
    // getUltimasVentas
    // ---------------------------------------------------------------

    #[Test]
    public function testGetUltimasVentas_RetornaArray(): void
    {
        $result = $this->model->getUltimasVentas();

        $this->assertIsArray($result);
    }

    #[Test]
    public function testGetUltimasVentas_ConDatos_RetornaEstructuraCorrecta(): void
    {
        $ventas = [[
            'idventa'       => 1,
            'nro_venta'     => 'V-0001',
            'fecha_venta'   => '2026-03-06',
            'cliente'       => 'Cliente Prueba',
            'total_general' => 150.0,
            'estado'        => 'PAGADA',
        ]];
        $stmtVentas = Mockery::mock(PDOStatement::class);
        $stmtVentas->shouldReceive('execute')->andReturn(true);
        $stmtVentas->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn($ventas);
        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtVentas);

        $result = $this->model->getUltimasVentas();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('idventa',       $result[0]);
        $this->assertArrayHasKey('nro_venta',     $result[0]);
        $this->assertArrayHasKey('fecha_venta',   $result[0]);
        $this->assertArrayHasKey('cliente',       $result[0]);
        $this->assertArrayHasKey('total_general', $result[0]);
        $this->assertArrayHasKey('estado',        $result[0]);
    }

    // ---------------------------------------------------------------
    // getReporteCompras
    // ---------------------------------------------------------------

    #[Test]
    #[DataProvider('providerRangosFechasValidos')]
    public function testGetReporteCompras_ConFechasValidas_RetornaArray(string $desde, string $hasta): void
    {
        $result = $this->model->getReporteCompras($desde, $hasta);

        $this->assertIsArray($result);
    }

    #[Test]
    #[DataProvider('providerRangosFechasInvalidos')]
    public function testGetReporteCompras_ConFechasInvalidas_RetornaArray(string $desde, string $hasta): void
    {
        $result = $this->model->getReporteCompras($desde, $hasta);

        $this->assertIsArray($result);
    }

    #[Test]
    public function testGetReporteCompras_CuandoExcepcion_RetornaArrayVacio(): void
    {
        $stmtFallo = Mockery::mock(PDOStatement::class);
        $stmtFallo->shouldReceive('execute')->andThrow(new \Exception('DB Error'));
        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtFallo);

        $result = $this->model->getReporteCompras('2026-01-01', '2026-12-31');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ---------------------------------------------------------------
    // getEficienciaEmpleados
    // ---------------------------------------------------------------

    #[Test]
    public function testGetEficienciaEmpleados_RetornaArray(): void
    {
        $result = $this->model->getEficienciaEmpleados('2026-01-01', '2026-12-31', null, null);

        $this->assertIsArray($result);
    }

    // ---------------------------------------------------------------
    // getAnalisisInventario
    // ---------------------------------------------------------------

    #[Test]
    public function testGetAnalisisInventario_RetornaEstructuraCorrecta(): void
    {
        $result = $this->model->getAnalisisInventario();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stock_critico',          $result);
        $this->assertArrayHasKey('valor_por_categoria',    $result);
        $this->assertArrayHasKey('movimientos_mes',        $result);
        $this->assertArrayHasKey('productos_mas_vendidos', $result);
    }

    #[Test]
    public function testGetAnalisisInventario_CuandoExcepcion_RetornaDatosPorDefecto(): void
    {
        $stmtFallo = Mockery::mock(PDOStatement::class);
        $stmtFallo->shouldReceive('execute')->andThrow(new \Exception('DB Error'));
        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtFallo);

        $result = $this->model->getAnalisisInventario();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stock_critico', $result);
        $this->assertEquals(0, $result['stock_critico']);
    }
}
