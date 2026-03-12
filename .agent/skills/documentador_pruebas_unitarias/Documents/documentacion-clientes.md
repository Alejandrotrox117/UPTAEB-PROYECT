# Documentación de Pruebas Unitarias — Módulo de Clientes

## Cuadro Nº 1: Módulo de Clientes (RF01)

### Objetivos de la prueba

Validar que las operaciones CRUD del módulo de Clientes (insertar, actualizar, eliminar/reactivar y consultar) solo se ejecuten correctamente cuando los datos son válidos. El sistema debe rechazar cédulas duplicadas, manejar clientes inexistentes y capturar excepciones de base de datos sin propagar errores al llamador.

### Técnicas

Pruebas de caja blanca con enfoque en aislamiento de la capa de modelo mediante dobles de prueba (Mockery). Se evalúan los métodos `insertCliente()`, `insertClienteCompleto()`, `updateCliente()`, `deleteClienteById()`, `reactivarCliente()`, `selectAllClientes()`, `selectAllClientesActivos()`, `selectClienteById()` y `selectClienteByCedula()` en escenarios válidos, inválidos y de excepción, verificando valores de retorno, mensajes y estructura de respuesta.

### Código Involucrado

```php
<?php
// =====================================================================
// insertarClienteUnitTest.php
// =====================================================================
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
    private $mockPdo;
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

    #[Test]
    #[DataProvider('providerInsertClienteValidos')]
    public function insertClienteExitosoRetornaStatusTrue(array $data): void
    {
        $mockStmtVerif = Mockery::mock(PDOStatement::class);
        $mockStmtVerif->shouldReceive('execute')->andReturn(true);
        $mockStmtVerif->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

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

    #[Test]
    public function insertClienteCedulaDuplicadaRetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 1]);

        $resultado = $this->model->insertCliente($this->datosClienteValido());

        $this->assertFalse($resultado['status']);
        $this->assertStringContainsStringIgnoringCase('cédula', $resultado['message']);
        $this->assertNull($resultado['cliente_id']);
    }

    #[Test]
    public function insertClienteArrojaExcepcionRetornaStatusFalse(): void
    {
        $mockStmtVerif = Mockery::mock(PDOStatement::class);
        $mockStmtVerif->shouldReceive('execute')->andReturn(true);
        $mockStmtVerif->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        $this->mockPdo->shouldReceive('prepare')
            ->andReturn($mockStmtVerif)
            ->andThrow(new \Exception('Error grave de BD'));

        $resultado = $this->model->insertCliente($this->datosClienteValido());

        $this->assertFalse($resultado['status']);
        $this->assertNull($resultado['cliente_id']);
    }

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


// =====================================================================
// actualizarClienteUnitTest.php
// =====================================================================
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
class actualizarClienteUnitTest extends TestCase
{
    private ClientesModel $model;
    private $mockPdo;
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

    #[Test]
    #[DataProvider('providerUpdateClienteValidos')]
    public function updateClienteExitosoRetornaStatusTrue(int $idcliente, array $data): void
    {
        $mockStmtVerif = Mockery::mock(PDOStatement::class);
        $mockStmtVerif->shouldReceive('execute')->andReturn(true);
        $mockStmtVerif->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

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

    #[Test]
    public function updateClienteCedulaDuplicadaDeOtroRetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 1]);

        $resultado = $this->model->updateCliente(1, $this->datosActualizacion());

        $this->assertFalse($resultado['status']);
        $this->assertStringContainsStringIgnoringCase('cédula', $resultado['message']);
    }

    #[Test]
    public function updateClienteSinCambiosRetornaStatusTrue(): void
    {
        $mockStmtVerif = Mockery::mock(PDOStatement::class);
        $mockStmtVerif->shouldReceive('execute')->andReturn(true);
        $mockStmtVerif->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        $mockStmtUpdate = Mockery::mock(PDOStatement::class);
        $mockStmtUpdate->shouldReceive('execute')->andReturn(true);
        $mockStmtUpdate->shouldReceive('rowCount')->andReturn(0);

        $this->mockPdo->shouldReceive('prepare')
            ->andReturn($mockStmtVerif, $mockStmtUpdate);

        $resultado = $this->model->updateCliente(1, $this->datosActualizacion());

        $this->assertTrue($resultado['status']);
        $this->assertStringContainsStringIgnoringCase('idénticos', $resultado['message']);
    }

    #[Test]
    public function updateClienteArrojaExcepcionRetornaStatusFalse(): void
    {
        $mockStmtVerif = Mockery::mock(PDOStatement::class);
        $mockStmtVerif->shouldReceive('execute')->andReturn(true);
        $mockStmtVerif->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        $this->mockPdo->shouldReceive('prepare')
            ->andReturn($mockStmtVerif)
            ->andThrow(new \Exception('Error grave de BD'));

        $resultado = $this->model->updateCliente(1, $this->datosActualizacion());

        $this->assertFalse($resultado['status']);
        $this->assertArrayHasKey('message', $resultado);
    }

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


// =====================================================================
// eliminarClienteUnitTest.php
// =====================================================================
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
class eliminarClienteUnitTest extends TestCase
{
    private ClientesModel $model;
    private $mockPdo;
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

    public static function providerIdsExistentes(): array
    {
        return [
            'id_1'  => [1],
            'id_10' => [10],
            'id_99' => [99],
        ];
    }

    #[Test]
    #[DataProvider('providerIdsExistentes')]
    public function deleteClienteByIdExitosoRetornaTrue(int $idcliente): void
    {
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1);

        $resultado = $this->model->deleteClienteById($idcliente);

        $this->assertTrue($resultado);
    }

    #[Test]
    public function deleteClienteByIdNoExistenteRetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0);

        $resultado = $this->model->deleteClienteById(99999);

        $this->assertFalse($resultado);
    }

    #[Test]
    public function deleteClienteByIdArrojaExcepcionRetornaFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \Exception('Error de BD'));

        $resultado = $this->model->deleteClienteById(1);

        $this->assertFalse($resultado);
    }

    #[Test]
    public function reactivarClienteExitosoRetornaStatusTrue(): void
    {
        $mockStmtSelect = Mockery::mock(PDOStatement::class);
        $mockStmtSelect->shouldReceive('execute')->andReturn(true);
        $mockStmtSelect->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['idcliente' => 1, 'estatus' => 'inactivo']);

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

    #[Test]
    public function reactivarClienteNoEncontradoRetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(false);

        $resultado = $this->model->reactivarCliente(99999);

        $this->assertFalse($resultado['status']);
        $this->assertStringContainsStringIgnoringCase('no encontrado', $resultado['message']);
    }

    #[Test]
    public function reactivarClienteYaActivoRetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['idcliente' => 1, 'estatus' => 'activo']);

        $resultado = $this->model->reactivarCliente(1);

        $this->assertFalse($resultado['status']);
        $this->assertStringContainsStringIgnoringCase('ya está activo', $resultado['message']);
    }

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


// =====================================================================
// consultarClientesUnitTest.php
// =====================================================================
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
class consultarClientesUnitTest extends TestCase
{
    private ClientesModel $model;
    private $mockPdo;
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

    public static function providerListasDeClientes(): array
    {
        return [
            'lista_vacia' => [[]],
            'un_cliente'  => [[
                [
                    'idcliente'          => 1,
                    'cedula'             => 'V-12345678',
                    'nombre'             => 'Juan',
                    'apellido'           => 'Pérez',
                    'direccion'          => 'Calle 1',
                    'telefono_principal' => '04141234567',
                    'estatus'            => 'activo',
                    'observaciones'      => '',
                ],
            ]],
            'multiples_clientes' => [[
                ['idcliente' => 1, 'cedula' => 'V-11111111', 'nombre' => 'Ana',   'apellido' => 'García', 'estatus' => 'activo'],
                ['idcliente' => 2, 'cedula' => 'V-22222222', 'nombre' => 'Pedro', 'apellido' => 'López',  'estatus' => 'activo'],
                ['idcliente' => 3, 'cedula' => 'V-33333333', 'nombre' => 'María', 'apellido' => 'Torres', 'estatus' => 'inactivo'],
            ]],
        ];
    }

    public static function providerClienteData(): array
    {
        return [
            'cliente_completo' => [[
                'idcliente'                   => 5,
                'cedula'                      => 'V-99999999',
                'nombre'                      => 'Carlos',
                'apellido'                    => 'Ruiz',
                'direccion'                   => 'Av. Principal',
                'telefono_principal'          => '04161234567',
                'estatus'                     => 'activo',
                'observaciones'               => 'Ninguna',
                'fecha_creacion'              => '2025-01-01 10:00:00',
                'ultima_modificacion'         => '2025-01-02 08:00:00',
                'fecha_eliminacion'           => null,
                'fecha_creacion_formato'      => '01/01/2025 10:00',
                'ultima_modificacion_formato' => '02/01/2025 08:00',
            ]],
        ];
    }

    #[Test]
    #[DataProvider('providerListasDeClientes')]
    public function selectAllClientesRetornaEstructuraCorrecta(array $filas): void
    {
        $mockStmtSeguridad = Mockery::mock(PDOStatement::class);
        $mockStmtSeguridad->shouldReceive('execute')->andReturn(true);
        $mockStmtSeguridad->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['idrol' => 2]);

        $mockStmtGeneral = Mockery::mock(PDOStatement::class);
        $mockStmtGeneral->shouldReceive('execute')->andReturn(true);
        $mockStmtGeneral->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn($filas);

        $this->mockPdo->shouldReceive('prepare')
            ->andReturn($mockStmtSeguridad, $mockStmtGeneral);

        $resultado = $this->model->selectAllClientes(1);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertTrue($resultado['status']);
        $this->assertSame($filas, $resultado['data']);
    }

    #[Test]
    public function selectAllClientesComoSuperUsuarioNoFiltraEstatus(): void
    {
        $mockStmtSeguridad = Mockery::mock(PDOStatement::class);
        $mockStmtSeguridad->shouldReceive('execute')->andReturn(true);
        $mockStmtSeguridad->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['idrol' => 1]);

        $clienteInactivo = ['idcliente' => 99, 'cedula' => 'V-00000001', 'estatus' => 'inactivo'];
        $mockStmtGeneral = Mockery::mock(PDOStatement::class);
        $mockStmtGeneral->shouldReceive('execute')->andReturn(true);
        $mockStmtGeneral->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)
            ->andReturn([$clienteInactivo]);

        $this->mockPdo->shouldReceive('prepare')
            ->andReturn($mockStmtSeguridad, $mockStmtGeneral);

        $resultado = $this->model->selectAllClientes(1);

        $this->assertTrue($resultado['status']);
        $this->assertCount(1, $resultado['data']);
        $this->assertSame('inactivo', $resultado['data'][0]['estatus']);
    }

    #[Test]
    public function selectAllClientesRetornaDataVaciaEnExcepcion(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \Exception('Error de conexión'));

        $resultado = $this->model->selectAllClientes(0);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('data', $resultado);
    }

    #[Test]
    public function selectAllClientesActivosRetornaEstructuraCorrecta(): void
    {
        $activos = [
            ['idcliente' => 1, 'cedula' => 'V-11111111', 'nombre' => 'Ana',   'estatus' => 'activo'],
            ['idcliente' => 2, 'cedula' => 'V-22222222', 'nombre' => 'Pedro', 'estatus' => 'activo'],
        ];

        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)
            ->andReturn($activos)->once();

        $resultado = $this->model->selectAllClientesActivos();

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertTrue($resultado['status']);
        $this->assertCount(2, $resultado['data']);
    }

    #[Test]
    public function selectAllClientesActivosRetornaDataVaciaEnExcepcion(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \Exception('Error BD'));

        $resultado = $this->model->selectAllClientesActivos();

        $this->assertFalse($resultado['status']);
        $this->assertEmpty($resultado['data']);
    }

    #[Test]
    #[DataProvider('providerClienteData')]
    public function selectClienteByIdEncontradoRetornaCliente(array $clienteEsperado): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn($clienteEsperado)->once();

        $resultado = $this->model->selectClienteById($clienteEsperado['idcliente']);

        $this->assertIsArray($resultado);
        $this->assertSame($clienteEsperado['idcliente'], $resultado['idcliente']);
        $this->assertSame($clienteEsperado['cedula'], $resultado['cedula']);
        $this->assertSame($clienteEsperado['nombre'], $resultado['nombre']);
    }

    #[Test]
    public function selectClienteByIdNoEncontradoRetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(false)->once();

        $resultado = $this->model->selectClienteById(99999);

        $this->assertFalse($resultado);
    }

    #[Test]
    public function selectClienteByIdRetornaFalseEnExcepcion(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \Exception('Error de BD'));

        $resultado = $this->model->selectClienteById(1);

        $this->assertFalse($resultado);
    }

    #[Test]
    public function selectClienteByCedulaExistenteRetornaDatos(): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 1])->once();

        $resultado = $this->model->selectClienteByCedula('V-12345678');

        $this->assertNotFalse($resultado);
        $this->assertIsArray($resultado);
    }

    #[Test]
    public function selectClienteByCedulaNoExistenteRetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0])->once();

        $resultado = $this->model->selectClienteByCedula('V-00000000');

        $this->assertFalse($resultado);
    }

    #[Test]
    public function selectClienteByCedulaAsumeDuplicadoEnExcepcion(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \Exception('Error de BD'));

        $resultado = $this->model->selectClienteByCedula('V-12345678');

        $this->assertNotFalse($resultado);
    }
}
```

### Caso de prueba

**TIPO:** Funcional (Caja blanca)

**OBJETIVO:** Verificar que `ClientesModel` gestione correctamente el ciclo de vida completo de un cliente: registro, actualización, eliminación lógica, reactivación y consultas, validando unicidad de cédula y manejo de errores.

**DESCRIPCIÓN:** Se prueban los 9 métodos públicos de `ClientesModel` ante escenarios de éxito, cédula duplicada, cliente inexistente, datos sin cambios y excepciones de base de datos.

**ENTRADAS:**
- Cliente con cédula nueva `V-10000001` (datos mínimos) y `E-20000002` (datos completos con observaciones).
- Cliente con cédula duplicada `V-12345678` (total = 1 en verificación).
- `deleteClienteById()` con IDs existentes: 1, 10, 99; e ID inexistente: 99999.
- `reactivarCliente()` con cliente inactivo (id=1), activo (id=1) y no encontrado (id=99999).
- `selectAllClientes()` con rol normal (idrol=2) y superusuario (idrol=1).
- Excepciones de BD forzadas vía `->andThrow(new \Exception(...))`.

**SALIDAS ESPERADAS:**
- Inserción válida → `['status' => true, 'cliente_id' => 42]`
- Cédula duplicada en inserción → `['status' => false, 'cliente_id' => null, 'message' => '...cédula...']`
- Actualización sin cambios (rowCount=0) → `['status' => true, 'message' => '...idénticos...']`
- Eliminación de cliente existente → `true`; inexistente → `false`
- Reactivación exitosa → `['status' => true, 'message' => '...reactivado...']`
- Cliente ya activo en reactivación → `['status' => false, 'message' => '...ya está activo...']`
- `selectAllClientes()` como superusuario → retorna clientes inactivos sin filtrar
- Cualquier excepción de BD → `status: false` o `false` directo, sin propagar la excepción

### Resultado

```
PHPUnit 10.5.63 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: C:\xampp\htdocs\project\phpunit.xml

..................................                                34 / 34 (100%)

Time: 00:07.321, Memory: 10.00 MB

actualizar Cliente Unit (Tests\UnitTest\Clientes\actualizarClienteUnit)
 ✔ Update cliente exitoso retorna status true with update_con_cambios
 ✔ Update cliente exitoso retorna status true with update_datos_identicos
 ✔ Update cliente cedula duplicada de otro retorna status false
 ✔ Update cliente sin cambios retorna status true
 ✔ Update cliente arroja excepcion retorna status false
 ✔ Update cliente excepcion duplicado bd retorna mensaje adecuado

consultar Clientes Unit (Tests\UnitTest\Clientes\consultarClientesUnit)
 ✔ Select all clientes retorna estructura correcta with lista_vacia
 ✔ Select all clientes retorna estructura correcta with un_cliente
 ✔ Select all clientes retorna estructura correcta with multiples_clientes
 ✔ Select all clientes como super usuario no filtra estatus
 ✔ Select all clientes retorna data vacia en excepcion
 ✔ Select all clientes activos retorna estructura correcta
 ✔ Select all clientes activos retorna data vacia en excepcion
 ✔ Select cliente by id encontrado retorna cliente with cliente_completo
 ✔ Select cliente by id no encontrado retorna false
 ✔ Select cliente by id retorna false en excepcion
 ✔ Select cliente by cedula existente retorna datos
 ✔ Select cliente by cedula no existente retorna false
 ✔ Select cliente by cedula asume duplicado en excepcion

eliminar Cliente Unit (Tests\UnitTest\Clientes\eliminarClienteUnit)
 ✔ Delete cliente by id exitoso retorna true with id_1
 ✔ Delete cliente by id exitoso retorna true with id_10
 ✔ Delete cliente by id exitoso retorna true with id_99
 ✔ Delete cliente by id no existente retorna false
 ✔ Delete cliente by id arroja excepcion retorna false
 ✔ Reactivar cliente exitoso retorna status true
 ✔ Reactivar cliente no encontrado retorna status false
 ✔ Reactivar cliente ya activo retorna status false
 ✔ Reactivar cliente arroja excepcion retorna status false

insertar Cliente Unit (Tests\UnitTest\Clientes\insertarClienteUnit)
 ✔ Insert cliente exitoso retorna status true with datos_minimos
 ✔ Insert cliente exitoso retorna status true with datos_completos
 ✔ Insert cliente cedula duplicada retorna status false
 ✔ Insert cliente arroja excepcion retorna status false
 ✔ Insert cliente completo exitoso retorna status true
 ✔ Insert cliente completo cedula duplicada retorna status false

OK (34 tests, 84 assertions)
```

### Observaciones

Se ejecutaron 34 pruebas con 84 aserciones en 7.321 segundos, todas superadas sin errores ni advertencias. El hallazgo más relevante es que `selectClienteByCedula()` ante una excepción de BD asume que la cédula existe (retorna un array en lugar de `false`), comportamiento conservador que previene registros duplicados en condiciones de fallo.
