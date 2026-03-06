<?php
declare(strict_types=1);

namespace Tests\UnitTest\Bitacora;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\BitacoraModel;
use Mockery;

/**
 * Pruebas Unitarias — BitacoraModel
 *
 * Usa Mockery overload:App\Core\Conexion para interceptar toda creación de
 * Conexion y reemplazarla con mocks de PDO/PDOStatement.
 * No toca la base de datos real.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class BitacoraUnitTest extends TestCase
{
    private BitacoraModel $model;

    /** @var \Mockery\MockInterface */
    private $mockPdo;
    /** @var \Mockery\MockInterface */
    private $mockPdoSeg;
    /** @var \Mockery\MockInterface */
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');

        $this->mockPdo    = Mockery::mock(\PDO::class);
        $this->mockPdoSeg = Mockery::mock(\PDO::class);
        $this->mockStmt   = Mockery::mock(\PDOStatement::class);

        // Configuración por defecto del stmt (sin datos)
        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(\PDO::FETCH_ASSOC)->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(\PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdoSeg->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdoSeg->shouldReceive('lastInsertId')->andReturn('0')->byDefault();

        // overload: intercepta todo new Conexion() del proceso actual
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdoSeg);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new BitacoraModel();
    }

    protected function tearDown(): void
    {
        unset($this->model);
        Mockery::close();
    }

    // ---------------------------------------------------------------
    // DataProviders
    // ---------------------------------------------------------------

    public static function providerRegistrarAccionExitosa(): array
    {
        return [
            'insert_productos'    => ['PRODUCTOS', 'INSERT', 1,  'Registro de prueba',  null, '42'],
            'update_ventas_conId' => ['VENTAS',    'UPDATE', 5,  'Actualización',        123, '99'],
        ];
    }

    public static function providerRegistrarAccionFallida(): array
    {
        return [
            'sin_usuario'  => ['PRODUCTOS', 'INSERT', null, 'Sin usuario'],
            'sin_modulo'   => ['',           'INSERT', 1,    'Sin módulo'],
            'sin_accion'   => ['PRODUCTOS', '',        1,    'Sin acción'],
            'id_negativo'  => ['PRODUCTOS', 'INSERT', -1,   'ID de usuario negativo'],
        ];
    }

    public static function providerIdsInexistentes(): array
    {
        return [
            'id_cero'     => [0],
            'id_negativo' => [-1],
            'id_muy_alto' => [999999],
        ];
    }

    // ---------------------------------------------------------------
    // registrarAccion — exitosos
    // ---------------------------------------------------------------

    #[Test]
    #[DataProvider('providerRegistrarAccionExitosa')]
    public function testRegistrarAccion_Exitosa_RetornaIdInsertado(
        string $tabla,
        string $accion,
        ?int $idusuario,
        string $detalle,
        ?int $idRegistro,
        string $idEsperado
    ): void {
        $this->mockPdoSeg->shouldReceive('lastInsertId')->andReturn($idEsperado);

        $result = $this->model->registrarAccion($tabla, $accion, $idusuario, $detalle, $idRegistro);

        $this->assertNotFalse($result, 'Se esperaba un ID insertado pero se obtuvo false.');
        $this->assertEquals($idEsperado, $result);
    }

    // ---------------------------------------------------------------
    // registrarAccion — fallidos (validaciones)
    // ---------------------------------------------------------------

    #[Test]
    #[DataProvider('providerRegistrarAccionFallida')]
    public function testRegistrarAccion_Falla_RetornaFalse(
        string $tabla,
        string $accion,
        ?int $idusuario,
        string $detalle
    ): void {
        $result = $this->model->registrarAccion($tabla, $accion, $idusuario, $detalle);

        $this->assertFalse(
            $result,
            "Debe retornar false con tabla='{$tabla}', accion='{$accion}', idusuario=" . var_export($idusuario, true)
        );
    }

    // ---------------------------------------------------------------
    // registrarAccion — fallo cuando execute() retorna false
    // ---------------------------------------------------------------

    #[Test]
    public function testRegistrarAccion_Falla_CuandoEjecutaFallaEnBD(): void
    {
        $stmtFallo = Mockery::mock(\PDOStatement::class);
        $stmtFallo->shouldReceive('execute')->andReturn(false);
        $this->mockPdoSeg->shouldReceive('prepare')->andReturn($stmtFallo);

        $result = $this->model->registrarAccion('PRODUCTOS', 'INSERT', 1, 'Test fallo BD');

        $this->assertFalse($result, 'Debe retornar false cuando execute() devuelve false.');
    }

    // ---------------------------------------------------------------
    // obtenerHistorial — típico
    // ---------------------------------------------------------------

    #[Test]
    public function testObtenerHistorial_RetornaDatos_CuandoExistenRegistros(): void
    {
        $datos = [
            ['idbitacora' => 1, 'tabla' => 'PRODUCTOS', 'accion' => 'INSERT', 'idusuario' => 1, 'nombre_usuario' => 'admin', 'fecha' => '2026-03-06 10:00:00'],
        ];
        $stmtHistorial = Mockery::mock(\PDOStatement::class);
        $stmtHistorial->shouldReceive('execute')->andReturn(true);
        $stmtHistorial->shouldReceive('fetchAll')->with(\PDO::FETCH_ASSOC)->andReturn($datos);
        $this->mockPdoSeg->shouldReceive('prepare')->andReturn($stmtHistorial);

        $result = $this->model->obtenerHistorial([]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('PRODUCTOS', $result[0]['tabla']);
    }

    #[Test]
    public function testObtenerHistorial_FiltradoPorUsuario_RetornaDatos(): void
    {
        $datos = [
            ['idbitacora' => 3, 'tabla' => 'CLIENTES', 'accion' => 'DELETE', 'idusuario' => 7, 'nombre_usuario' => 'operador', 'fecha' => '2026-03-06 11:00:00'],
        ];
        $stmtFiltrado = Mockery::mock(\PDOStatement::class);
        $stmtFiltrado->shouldReceive('execute')->andReturn(true);
        $stmtFiltrado->shouldReceive('fetchAll')->with(\PDO::FETCH_ASSOC)->andReturn($datos);
        $this->mockPdoSeg->shouldReceive('prepare')->andReturn($stmtFiltrado);

        $result = $this->model->obtenerHistorial(['idusuario' => 7]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    // ---------------------------------------------------------------
    // obtenerHistorial — atípico
    // ---------------------------------------------------------------

    #[Test]
    public function testObtenerHistorial_RetornaVacio_CuandoUsuarioInexistente(): void
    {
        $result = $this->model->obtenerHistorial(['idusuario' => 99999]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function testObtenerHistorial_RetornaVacio_CuandoModuloInexistente(): void
    {
        $result = $this->model->obtenerHistorial(['tabla' => 'modulo_inexistente_xyz']);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ---------------------------------------------------------------
    // SelectAllBitacora — típico / atípico
    // ---------------------------------------------------------------

    #[Test]
    public function testSelectAllBitacora_RetornaDatos_CuandoExistenRegistros(): void
    {
        $datos = [
            ['idbitacora' => 10, 'tabla' => 'VENTAS',   'accion' => 'INSERT', 'idusuario' => 2, 'nombre_usuario' => 'vendedor',  'fecha' => '2026-03-06 09:00:00'],
            ['idbitacora' => 11, 'tabla' => 'COMPRAS',  'accion' => 'UPDATE', 'idusuario' => 3, 'nombre_usuario' => 'comprador', 'fecha' => '2026-03-06 09:30:00'],
        ];
        $stmtAll = Mockery::mock(\PDOStatement::class);
        $stmtAll->shouldReceive('execute')->andReturn(true);
        $stmtAll->shouldReceive('fetchAll')->with(\PDO::FETCH_ASSOC)->andReturn($datos);
        $this->mockPdoSeg->shouldReceive('prepare')->andReturn($stmtAll);

        $result = $this->model->SelectAllBitacora();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    #[Test]
    public function testSelectAllBitacora_RetornaArrayVacio_SinRegistros(): void
    {
        $result = $this->model->SelectAllBitacora();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ---------------------------------------------------------------
    // obtenerRegistroPorId — típico / atípico
    // ---------------------------------------------------------------

    #[Test]
    public function testObtenerRegistroPorId_RetornaDatos_CuandoIdExiste(): void
    {
        $registro = [
            'idbitacora'     => 5,
            'tabla'          => 'PRODUCTOS',
            'accion'         => 'DELETE',
            'idusuario'      => 1,
            'nombre_usuario' => 'admin',
            'fecha'          => '2026-03-06 12:00:00',
        ];
        $stmtId = Mockery::mock(\PDOStatement::class);
        $stmtId->shouldReceive('execute')->andReturn(true);
        $stmtId->shouldReceive('fetch')->with(\PDO::FETCH_ASSOC)->andReturn($registro);
        $this->mockPdoSeg->shouldReceive('prepare')->andReturn($stmtId);

        $result = $this->model->obtenerRegistroPorId(5);

        $this->assertIsArray($result);
        $this->assertEquals(5, $result['idbitacora']);
    }

    #[Test]
    public function testObtenerRegistroPorId_RetornaFalse_CuandoIdNoExiste(): void
    {
        $result = $this->model->obtenerRegistroPorId(99999);

        $this->assertFalse($result);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testObtenerRegistroPorId_RetornaFalse_ConIdsInvalidos(int $id): void
    {
        $result = $this->model->obtenerRegistroPorId($id);

        $this->assertFalse($result, "obtenerRegistroPorId({$id}) debe retornar false.");
    }
}
