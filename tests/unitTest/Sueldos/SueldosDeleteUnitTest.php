<?php

namespace Tests\UnitTest\Sueldos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\SueldosModel;
use Mockery;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class SueldosDeleteUnitTest extends TestCase
{
    private SueldosModel $model;
    /** @var \Mockery\MockInterface */
    private $mockPdo;
    /** @var \Mockery\MockInterface */
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');

        $this->mockPdo = Mockery::mock(\PDO::class);
        $this->mockStmt = Mockery::mock(\PDOStatement::class);

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new SueldosModel();
    }

    protected function tearDown(): void
    {
        unset($this->model);
        Mockery::close();
    }

    #[Test]
    public function testDeleteSueldo_Exitosa_BorradoLogico(): void
    {
        // Se ejecuta sin error y afecta una fila
        $this->mockStmt->shouldReceive('execute')->andReturn(true);
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1);

        $result = $this->model->deleteSueldo(1);

        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    #[Test]
    public function testDeleteSueldo_Falla_NoExistente(): void
    {
        // Se ejecuta sin error pero afecta cero filas (id inexistente)
        $this->mockStmt->shouldReceive('execute')->andReturn(true);
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0);

        $result = $this->model->deleteSueldo(999);

        $this->assertIsBool($result);
        $this->assertFalse($result);
    }

    #[Test]
    public function testDeleteSueldo_Falla_ErrorDeBD(): void
    {
        // BD falla al conectar o preparar
        $this->mockStmt->shouldReceive('execute')->andThrow(new \Exception('SQLSTATE: Connection lost'));

        $result = $this->model->deleteSueldo(1);

        $this->assertIsBool($result);
        $this->assertFalse($result);
    }
}
