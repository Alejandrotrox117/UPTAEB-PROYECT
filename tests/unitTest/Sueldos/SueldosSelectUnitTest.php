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
class SueldosSelectUnitTest extends TestCase
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
    public function testSelectSueldoById_Exitosa_Encontrado(): void
    {
        // Simulando que el ID existe
        $this->mockStmt->shouldReceive('execute')->andReturn(true);
        $this->mockStmt->shouldReceive('fetch')->with(\PDO::FETCH_ASSOC)->andReturn([
            'idsueldo' => 1,
            'monto' => 1000.00
        ]);

        $result = $this->model->selectSueldoById(1);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['idsueldo']);
        $this->assertEquals(1000.00, $result['monto']);
    }

    #[Test]
    public function testSelectSueldoById_Falla_NoEncontrado(): void
    {
        // Simulando que el ID no existe
        $this->mockStmt->shouldReceive('execute')->andReturn(true);
        $this->mockStmt->shouldReceive('fetch')->with(\PDO::FETCH_ASSOC)->andReturn(false);

        $result = $this->model->selectSueldoById(999);

        // Retorna false si no consigue registros debido a la implementación actual
        $this->assertFalse($result);
    }

    #[Test]
    public function testSelectAllSueldos_Exitosa(): void
    {
        // El método internamente verifica si es superusuario, pero hemos inyectado seguridad también 
        $stmtSeguridad = Mockery::mock(\PDOStatement::class);
        $stmtSeguridad->shouldReceive('execute')->andReturn(true);
        $stmtSeguridad->shouldReceive('fetch')->with(\PDO::FETCH_ASSOC)->andReturn(['idrol' => 1]);

        $this->mockStmt->shouldReceive('execute')->andReturn(true);
        $this->mockStmt->shouldReceive('fetchAll')->with(\PDO::FETCH_ASSOC)->andReturn([
            ['idsueldo' => 1, 'monto' => 100],
            ['idsueldo' => 2, 'monto' => 200]
        ]);

        // Por los 2 prepares: una posible para get_conectSeguridad y otra get_conectGeneral
        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtSeguridad, $this->mockStmt);

        $result = $this->model->selectAllSueldos(1);

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
        $this->assertCount(2, $result['data']);
    }

    #[Test]
    public function testSelectAllSueldos_Falla_Excepcion(): void
    {
        // Excepción de base de datos
        $this->mockStmt->shouldReceive('execute')->andThrow(new \Exception('SQLSTATE: Connection lost'));

        $stmtSeguridad = Mockery::mock(\PDOStatement::class);
        $stmtSeguridad->shouldReceive('execute')->andReturn(true);
        $stmtSeguridad->shouldReceive('fetch')->with(\PDO::FETCH_ASSOC)->andReturn(['idrol' => 1]);

        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtSeguridad, $this->mockStmt);

        $result = $this->model->selectAllSueldos(1);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsString('Error al obtener sueldos', $result['message']);
    }
}
