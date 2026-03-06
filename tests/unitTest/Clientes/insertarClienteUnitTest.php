<?php

namespace Tests\UnitTest\Clientes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\ClientesModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
class insertarClienteUnitTest extends TestCase
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
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('0')->byDefault();

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

    private function datosClienteValido(): array
    {
        return [
            'cedula'             => 'V-12345678',
            'nombre'             => 'Juan',
            'apellido'           => 'Pérez',
            'direccion'          => 'Calle 1, Ciudad',
            'telefono_principal' => '04141234567',
            'estatus'            => 'activo',
            'observaciones'      => '',
        ];
    }

    // -------------------------------------------------------------------------
    // DataProviders
    // -------------------------------------------------------------------------

    public static function providerInsertClienteValidos(): array
    {
        return [
            'datos_minimos' => [[
                'cedula'             => 'V-10000001',
                'nombre'             => 'Ana',
                'apellido'           => 'García',
                'direccion'          => 'Av. 1',
                'telefono_principal' => '04161111111',
                'estatus'            => 'activo',
                'observaciones'      => '',
            ]],
            'datos_completos' => [[
                'cedula'             => 'E-20000002',
                'nombre'             => 'Pedro',
                'apellido'           => 'López',
                'direccion'          => 'Urb. Los Pinos, Casa 5',
                'telefono_principal' => '02122222222',
                'estatus'            => 'activo',
                'observaciones'      => 'Observación de prueba',
            ]],
        ];
    }

    // -------------------------------------------------------------------------
    // insertCliente — exitoso
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerInsertClienteValidos')]
    public function insertClienteExitosoRetornaStatusTrue(array $data): void
    {
        // Primera llamada a prepare/fetch: verificación de cédula → no existe (total=0)
        $mockStmtVerif = Mockery::mock(PDOStatement::class);
        $mockStmtVerif->shouldReceive('execute')->andReturn(true);
        $mockStmtVerif->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        // Segunda llamada: INSERT → éxito
        $mockStmtInsert = Mockery::mock(PDOStatement::class);
        $mockStmtInsert->shouldReceive('execute')->andReturn(true);

        $this->mockPdo->shouldReceive('prepare')
            ->andReturn($mockStmtVerif, $mockStmtInsert);
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('42');

        $resultado = $this->model->insertCliente($data);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status']);
        $this->assertArrayHasKey('cliente_id', $resultado);
        $this->assertNotNull($resultado['cliente_id']);
    }

    // -------------------------------------------------------------------------
    // insertCliente — cédula duplicada
    // -------------------------------------------------------------------------

    #[Test]
    public function insertClienteCedulaDuplicadaRetornaStatusFalse(): void
    {
        // La verificación detecta que la cédula ya existe
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 1]);

        $resultado = $this->model->insertCliente($this->datosClienteValido());

        $this->assertFalse($resultado['status']);
        $this->assertStringContainsStringIgnoringCase('cédula', $resultado['message']);
        $this->assertNull($resultado['cliente_id']);
    }

    // -------------------------------------------------------------------------
    // insertCliente — excepción de BD
    // -------------------------------------------------------------------------

    #[Test]
    public function insertClienteArrojaExcepcionRetornaStatusFalse(): void
    {
        // La verificación pasa (no duplicado)
        $mockStmtVerif = Mockery::mock(PDOStatement::class);
        $mockStmtVerif->shouldReceive('execute')->andReturn(true);
        $mockStmtVerif->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        // El INSERT lanza excepción
        $this->mockPdo->shouldReceive('prepare')
            ->andReturn($mockStmtVerif)
            ->andThrow(new \Exception('Error grave de BD'));

        $resultado = $this->model->insertCliente($this->datosClienteValido());

        $this->assertFalse($resultado['status']);
        $this->assertNull($resultado['cliente_id']);
    }

    // -------------------------------------------------------------------------
    // insertClienteCompleto — exitoso
    // -------------------------------------------------------------------------

    #[Test]
    public function insertClienteCompletoExitosoRetornaStatusTrue(): void
    {
        $data = [
            'cedula'             => 'V-55555555',
            'nombre'             => 'María',
            'apellido'           => 'Fernández',
            'direccion'          => 'Calle 2',
            'telefono_principal' => '04243333333',
            'estatus'            => 'Activo',
            'observaciones'      => '',
        ];

        $mockStmtVerif = Mockery::mock(PDOStatement::class);
        $mockStmtVerif->shouldReceive('execute')->andReturn(true);
        $mockStmtVerif->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        $mockStmtInsert = Mockery::mock(PDOStatement::class);
        $mockStmtInsert->shouldReceive('execute')->andReturn(true);

        $this->mockPdo->shouldReceive('prepare')
            ->andReturn($mockStmtVerif, $mockStmtInsert);
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('10');

        $resultado = $this->model->insertClienteCompleto($data);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status']);
        $this->assertArrayHasKey('cliente_id', $resultado);
    }

    // -------------------------------------------------------------------------
    // insertClienteCompleto — cédula duplicada
    // -------------------------------------------------------------------------

    #[Test]
    public function insertClienteCompletoCedulaDuplicadaRetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 1]);

        $data = [
            'cedula'             => 'V-55555555',
            'nombre'             => 'María',
            'apellido'           => 'Fernández',
            'direccion'          => 'Calle 2',
            'telefono_principal' => '04243333333',
            'estatus'            => 'Activo',
            'observaciones'      => '',
        ];

        $resultado = $this->model->insertClienteCompleto($data);

        $this->assertFalse($resultado['status']);
        $this->assertStringContainsStringIgnoringCase('identificación', $resultado['message']);
        $this->assertNull($resultado['cliente_id']);
    }
}
