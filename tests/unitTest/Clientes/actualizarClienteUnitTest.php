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
class actualizarClienteUnitTest extends TestCase
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
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1)->byDefault();

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
    // Datos de prueba reutilizables
    // -------------------------------------------------------------------------

    private function datosActualizacion(): array
    {
        return [
            'cedula'             => 'V-12345678',
            'nombre'             => 'Juan Actualizado',
            'apellido'           => 'Pérez',
            'direccion'          => 'Calle Nueva 10',
            'telefono_principal' => '04141234567',
            'estatus'            => 'activo',
            'observaciones'      => 'Actualizado en prueba',
        ];
    }

    // -------------------------------------------------------------------------
    // DataProviders
    // -------------------------------------------------------------------------

    public static function providerUpdateClienteValidos(): array
    {
        return [
            'update_con_cambios' => [
                'idcliente' => 1,
                'data' => [
                    'cedula'             => 'V-11111111',
                    'nombre'             => 'Ana Modificada',
                    'apellido'           => 'García',
                    'direccion'          => 'Av. Modificada',
                    'telefono_principal' => '04161111111',
                    'estatus'            => 'activo',
                    'observaciones'      => '',
                ],
            ],
            'update_datos_identicos' => [
                'idcliente' => 2,
                'data' => [
                    'cedula'             => 'V-22222222',
                    'nombre'             => 'Pedro',
                    'apellido'           => 'López',
                    'direccion'          => 'Calle 5',
                    'telefono_principal' => '02122222222',
                    'estatus'            => 'activo',
                    'observaciones'      => '',
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // updateCliente — exitoso
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerUpdateClienteValidos')]
    public function updateClienteExitosoRetornaStatusTrue(int $idcliente, array $data): void
    {
        // Primera prepare: verificación de cédula → no existe conflicto (total=0)
        $mockStmtVerif = Mockery::mock(PDOStatement::class);
        $mockStmtVerif->shouldReceive('execute')->andReturn(true);
        $mockStmtVerif->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        // Segunda prepare: UPDATE
        $mockStmtUpdate = Mockery::mock(PDOStatement::class);
        $mockStmtUpdate->shouldReceive('execute')->andReturn(true);
        $mockStmtUpdate->shouldReceive('rowCount')->andReturn(1);

        $this->mockPdo->shouldReceive('prepare')
            ->andReturn($mockStmtVerif, $mockStmtUpdate);

        $resultado = $this->model->updateCliente($idcliente, $data);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status']);
        $this->assertArrayHasKey('message', $resultado);
    }

    // -------------------------------------------------------------------------
    // updateCliente — cédula duplicada de otro cliente
    // -------------------------------------------------------------------------

    #[Test]
    public function updateClienteCedulaDuplicadaDeOtroRetornaStatusFalse(): void
    {
        // La verificación encuentra que la cédula ya la usa otro cliente
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 1]);

        $resultado = $this->model->updateCliente(1, $this->datosActualizacion());

        $this->assertFalse($resultado['status']);
        $this->assertStringContainsStringIgnoringCase('cédula', $resultado['message']);
    }

    // -------------------------------------------------------------------------
    // updateCliente — sin cambios (rowCount = 0)
    // -------------------------------------------------------------------------

    #[Test]
    public function updateClienteSinCambiosRetornaStatusTrue(): void
    {
        $mockStmtVerif = Mockery::mock(PDOStatement::class);
        $mockStmtVerif->shouldReceive('execute')->andReturn(true);
        $mockStmtVerif->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        $mockStmtUpdate = Mockery::mock(PDOStatement::class);
        $mockStmtUpdate->shouldReceive('execute')->andReturn(true);
        $mockStmtUpdate->shouldReceive('rowCount')->andReturn(0); // ninguna fila modificada

        $this->mockPdo->shouldReceive('prepare')
            ->andReturn($mockStmtVerif, $mockStmtUpdate);

        $resultado = $this->model->updateCliente(1, $this->datosActualizacion());

        // Incluso sin cambios el modelo devuelve true (datos idénticos)
        $this->assertTrue($resultado['status']);
        $this->assertStringContainsStringIgnoringCase('idénticos', $resultado['message']);
    }

    // -------------------------------------------------------------------------
    // updateCliente — excepción de BD
    // -------------------------------------------------------------------------

    #[Test]
    public function updateClienteArrojaExcepcionRetornaStatusFalse(): void
    {
        // La verificación pasa (no duplicado)
        $mockStmtVerif = Mockery::mock(PDOStatement::class);
        $mockStmtVerif->shouldReceive('execute')->andReturn(true);
        $mockStmtVerif->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        // El UPDATE lanza excepción
        $this->mockPdo->shouldReceive('prepare')
            ->andReturn($mockStmtVerif)
            ->andThrow(new \Exception('Error grave de BD'));

        $resultado = $this->model->updateCliente(1, $this->datosActualizacion());

        $this->assertFalse($resultado['status']);
        $this->assertArrayHasKey('message', $resultado);
    }

    // -------------------------------------------------------------------------
    // updateCliente — excepción por cédula duplicada en BD
    // -------------------------------------------------------------------------

    #[Test]
    public function updateClienteExcepcionDuplicadoBdRetornaMensajeAdecuado(): void
    {
        $mockStmtVerif = Mockery::mock(PDOStatement::class);
        $mockStmtVerif->shouldReceive('execute')->andReturn(true);
        $mockStmtVerif->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        $this->mockPdo->shouldReceive('prepare')
            ->andReturn($mockStmtVerif)
            ->andThrow(new \Exception("Duplicate entry 'V-12345678' for key 'cedula'"));

        $resultado = $this->model->updateCliente(1, $this->datosActualizacion());

        $this->assertFalse($resultado['status']);
        $this->assertStringContainsStringIgnoringCase('cédula', $resultado['message']);
    }
}
