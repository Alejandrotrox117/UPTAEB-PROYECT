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
