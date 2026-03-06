<?php

namespace Tests\UnitTest\Empleados;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\EmpleadosModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class EmpleadoDeleteUnitTest extends TestCase
{
    private EmpleadosModel $model;
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
        $this->mockStmt->shouldReceive('fetch')->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new EmpleadosModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // ─── Data Providers ──────────────────────────────────────────────────────

    public static function providerCasosExitososDelete(): array
    {
        return [
            'eliminar_id_1'     => [1],
            'eliminar_id_10'    => [10],
            'eliminar_id_100'   => [100],
        ];
    }

    public static function providerCasosFallidosDelete(): array
    {
        return [
            'id_inexistente_grande' => [99999],
            'id_cero'               => [0],
        ];
    }

    // ─── Tests ───────────────────────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerCasosExitososDelete')]
    public function testDeleteEmpleado_ExecuteExitoso_RetornaTrue(int $id): void
    {
        $this->mockStmt->shouldReceive('execute')->andReturn(true);

        $resultado = $this->model->deleteEmpleado($id);

        $this->assertTrue($resultado);
    }

    #[Test]
    #[DataProvider('providerCasosFallidosDelete')]
    public function testDeleteEmpleado_ExecuteRetornaFalse_RetornaFalse(int $id): void
    {
        $this->mockStmt->shouldReceive('execute')->andReturn(false);

        $resultado = $this->model->deleteEmpleado($id);

        $this->assertFalse($resultado);
    }

    #[Test]
    public function testDeleteEmpleado_FalloEnBD_RetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \PDOException('Error al eliminar'));

        $resultado = $this->model->deleteEmpleado(1);

        $this->assertFalse($resultado);
    }

    #[Test]
    public function testDeleteEmpleado_DobleLlamada_AmbasOperacionesIndependientes(): void
    {
        // Primera llamada retorna true, segunda retorna false (simula ya eliminado)
        $this->mockStmt->shouldReceive('execute')->twice()->andReturn(true, false);

        $resultado1 = $this->model->deleteEmpleado(1);
        $resultado2 = $this->model->deleteEmpleado(1);

        $this->assertTrue($resultado1);
        $this->assertFalse($resultado2);
    }
}
