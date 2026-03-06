<?php

namespace Tests\UnitTest\Clientes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\ClientesModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class eliminarClienteUnitTest extends TestCase
{
    private ClientesModel $model;
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

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('commit')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('rollBack')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(false)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new ClientesModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // -------------------------------------------------------------------------
    // DataProviders
    // -------------------------------------------------------------------------

    public static function providerIdsExistentes(): array
    {
        return [
            'id_1'  => [1],
            'id_10' => [10],
            'id_99' => [99],
        ];
    }

    // -------------------------------------------------------------------------
    // deleteClienteById — exitoso
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerIdsExistentes')]
    public function deleteClienteByIdExitosoRetornaTrue(int $idcliente): void
    {
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1);

        $resultado = $this->model->deleteClienteById($idcliente);

        $this->assertTrue($resultado);
    }

    // -------------------------------------------------------------------------
    // deleteClienteById — no existe (rowCount = 0)
    // -------------------------------------------------------------------------

    #[Test]
    public function deleteClienteByIdNoExistenteRetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0);

        $resultado = $this->model->deleteClienteById(99999);

        $this->assertFalse($resultado);
    }

    // -------------------------------------------------------------------------
    // deleteClienteById — excepción de BD
    // -------------------------------------------------------------------------

    #[Test]
    public function deleteClienteByIdArrojaExcepcionRetornaFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \Exception('Error de BD'));

        $resultado = $this->model->deleteClienteById(1);

        $this->assertFalse($resultado);
    }

    // -------------------------------------------------------------------------
    // reactivarCliente — exitoso
    // -------------------------------------------------------------------------

    #[Test]
    public function reactivarClienteExitosoRetornaStatusTrue(): void
    {
        // Primera consulta: SELECT → cliente encontrado con estatus inactivo
        $mockStmtSelect = Mockery::mock(PDOStatement::class);
        $mockStmtSelect->shouldReceive('execute')->andReturn(true);
        $mockStmtSelect->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['idcliente' => 1, 'estatus' => 'inactivo']);

        // Segunda consulta: UPDATE → rowCount = 1
        $mockStmtUpdate = Mockery::mock(PDOStatement::class);
        $mockStmtUpdate->shouldReceive('execute')->andReturn(true);
        $mockStmtUpdate->shouldReceive('rowCount')->andReturn(1);

        $this->mockPdo->shouldReceive('prepare')
            ->andReturn($mockStmtSelect, $mockStmtUpdate);

        $resultado = $this->model->reactivarCliente(1);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status']);
        $this->assertStringContainsStringIgnoringCase('reactivado', $resultado['message']);
    }

    // -------------------------------------------------------------------------
    // reactivarCliente — cliente no encontrado
    // -------------------------------------------------------------------------

    #[Test]
    public function reactivarClienteNoEncontradoRetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(false); // no existe el cliente

        $resultado = $this->model->reactivarCliente(99999);

        $this->assertFalse($resultado['status']);
        $this->assertStringContainsStringIgnoringCase('no encontrado', $resultado['message']);
    }

    // -------------------------------------------------------------------------
    // reactivarCliente — ya está activo
    // -------------------------------------------------------------------------

    #[Test]
    public function reactivarClienteYaActivoRetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['idcliente' => 1, 'estatus' => 'activo']);

        $resultado = $this->model->reactivarCliente(1);

        $this->assertFalse($resultado['status']);
        $this->assertStringContainsStringIgnoringCase('ya está activo', $resultado['message']);
    }

    // -------------------------------------------------------------------------
    // reactivarCliente — excepción de BD
    // -------------------------------------------------------------------------

    #[Test]
    public function reactivarClienteArrojaExcepcionRetornaStatusFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \Exception('Error grave de BD'));

        $resultado = $this->model->reactivarCliente(1);

        $this->assertFalse($resultado['status']);
        $this->assertArrayHasKey('message', $resultado);
    }
}
