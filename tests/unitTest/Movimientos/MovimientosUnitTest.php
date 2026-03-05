<?php

namespace Tests\UnitTest\Movimientos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\MovimientosModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class MovimientosUnitTest extends TestCase
{
    private MovimientosModel $model;
    /** @var \Mockery\MockInterface */
    private $mockPdo;
    /** @var \Mockery\MockInterface */
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');

        $this->mockPdo  = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        // Comportamiento por defecto del statement
        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();

        // Comportamiento por defecto del PDO mock
        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('commit')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('rollBack')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn("0")->byDefault();
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(false)->byDefault();

        // Sobrecargar la conexión para evitar cualquier contacto con BD real
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new MovimientosModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // =========================================================================
    // VALIDACIONES DE INSERT (fallan antes de tocar la BD)
    // =========================================================================

    public static function providerDatosInvalidosInsert(): array
    {
        return [
            'sin_idproducto' => [
                [
                    'idtipomovimiento' => 1,
                    'cantidad_entrada'  => 100,
                    'cantidad_salida'   => 0,
                    'stock_anterior'    => 0,
                    'stock_resultante'  => 100,
                ],
                'producto',
            ],
            'idproducto_nulo' => [
                [
                    'idproducto'        => null,
                    'idtipomovimiento'  => 1,
                    'cantidad_entrada'  => 100,
                    'cantidad_salida'   => 0,
                    'stock_anterior'    => 0,
                    'stock_resultante'  => 100,
                ],
                'producto',
            ],
            'idproducto_vacio' => [
                [
                    'idproducto'        => '',
                    'idtipomovimiento'  => 1,
                    'cantidad_entrada'  => 100,
                    'cantidad_salida'   => 0,
                    'stock_anterior'    => 0,
                    'stock_resultante'  => 100,
                ],
                'producto',
            ],
            'sin_idtipomovimiento' => [
                [
                    'idproducto'       => 1,
                    'cantidad_entrada' => 100,
                    'cantidad_salida'  => 0,
                    'stock_anterior'   => 0,
                    'stock_resultante' => 100,
                ],
                'tipo de movimiento',
            ],
            'idtipomovimiento_nulo' => [
                [
                    'idproducto'       => 1,
                    'idtipomovimiento' => null,
                    'cantidad_entrada' => 100,
                    'cantidad_salida'  => 0,
                    'stock_anterior'   => 0,
                    'stock_resultante' => 100,
                ],
                'tipo de movimiento',
            ],
            'ambas_cantidades_cero' => [
                [
                    'idproducto'       => 1,
                    'idtipomovimiento' => 1,
                    'cantidad_entrada' => 0,
                    'cantidad_salida'  => 0,
                    'stock_anterior'   => 0,
                    'stock_resultante' => 0,
                ],
                'cantidad',
            ],
            'cantidades_negativas' => [
                [
                    'idproducto'       => 1,
                    'idtipomovimiento' => 1,
                    'cantidad_entrada' => -10,
                    'cantidad_salida'  => -5,
                    'stock_anterior'   => 0,
                    'stock_resultante' => 0,
                ],
                'cantidad',
            ],
            'ambas_cantidades_positivas' => [
                [
                    'idproducto'       => 1,
                    'idtipomovimiento' => 1,
                    'cantidad_entrada' => 100,
                    'cantidad_salida'  => 50,
                    'stock_anterior'   => 0,
                    'stock_resultante' => 50,
                ],
                'entrada y salida al mismo tiempo',
            ],
        ];
    }

    #[Test]
    #[DataProvider('providerDatosInvalidosInsert')]
    public function testInsertMovimiento_ValidacionesFallan(
        array $data,
        string $mensajeParcialEsperado
    ): void {
        $result = $this->model->insertMovimiento($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsStringIgnoringCase(
            $mensajeParcialEsperado,
            $result['message'],
            "El mensaje debe contener: '{$mensajeParcialEsperado}'"
        );
        $this->assertNull($result['data']);
    }

    #[Test]
    public function testInsertMovimiento_StockProductoNoVerificable_RetornaError(): void
    {
        // fetch retorna false → producto no encontrado → obtenerStockActualProducto devuelve false
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);

        $data = [
            'idproducto'       => 1,
            'idtipomovimiento' => 1,
            'cantidad_entrada' => 50,
            'cantidad_salida'  => 0,
            'stock_anterior'   => 0,
            'stock_resultante' => 50,
        ];

        $result = $this->model->insertMovimiento($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('stock', $result['message']);
        $this->assertNull($result['data']);
    }

    // =========================================================================
    // UPDATE Y DELETE — SIEMPRE DESHABILITADOS
    // =========================================================================

    public static function providerIdsUpdateDeshabilitado(): array
    {
        return [
            'id_valido_datos_completos' => [1,            ['idproducto' => 1, 'cantidad_entrada' => 100]],
            'id_grande_datos_vacios'    => [999999,       []],
            'id_maximo_datos_complejos' => [PHP_INT_MAX,  ['idproducto' => 1, 'cantidad_entrada' => 500]],
        ];
    }

    #[Test]
    #[DataProvider('providerIdsUpdateDeshabilitado')]
    public function testUpdateMovimiento_SiempreRetornaError(int $id, array $data): void
    {
        $result = $this->model->updateMovimiento($id, $data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('no está permitida', $result['message']);
        $this->assertNull($result['data']);
    }

    public static function providerIdsDeleteDeshabilitado(): array
    {
        return [
            'id_valido'      => [1],
            'id_inexistente' => [999999],
            'id_maximo'      => [PHP_INT_MAX],
        ];
    }

    #[Test]
    #[DataProvider('providerIdsDeleteDeshabilitado')]
    public function testDeleteMovimientoById_SiempreRetornaError(int $id): void
    {
        $result = $this->model->deleteMovimientoById($id);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('no está permitida', $result['message']);
        $this->assertNull($result['data']);
    }

    // =========================================================================
    // IDs INVÁLIDOS (validados antes de ir a la BD)
    // =========================================================================

    #[Test]
    public function testSelectMovimientoByIdCero_RetornaErrorInvalido(): void
    {
        $result = $this->model->selectMovimientoById(0);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('inválido', $result['message']);
        $this->assertNull($result['data']);
    }

    #[Test]
    public function testAnularMovimientoByIdCero_RetornaErrorInvalido(): void
    {
        $result = $this->model->anularMovimientoById(0);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('inválido', $result['message']);
        $this->assertNull($result['data']);
    }

    // =========================================================================
    // CONTRATO DE RESPUESTA
    // =========================================================================

    #[Test]
    public function testContrato_MetodosDeshabilitadosTienenMismaEstructura(): void
    {
        $resultUpdate = $this->model->updateMovimiento(1, []);
        $resultDelete = $this->model->deleteMovimientoById(1);

        $this->assertEquals(array_keys($resultUpdate), array_keys($resultDelete));

        foreach ([$resultUpdate, $resultDelete] as $result) {
            $this->assertArrayHasKey('status', $result);
            $this->assertArrayHasKey('message', $result);
            $this->assertArrayHasKey('data', $result);
            $this->assertIsBool($result['status']);
            $this->assertIsString($result['message']);
            $this->assertNotEmpty($result['message']);
        }
    }

    #[Test]
    public function testContrato_ValidacionFallidaRetornaEstructuraCorrecta(): void
    {
        // Sin idproducto → falla validación
        $result = $this->model->insertMovimiento([
            'idtipomovimiento' => 1,
            'cantidad_entrada' => 100,
            'cantidad_salida'  => 0,
            'stock_anterior'   => 0,
            'stock_resultante' => 100,
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertFalse($result['status']);
        $this->assertNull($result['data']);
    }
}
