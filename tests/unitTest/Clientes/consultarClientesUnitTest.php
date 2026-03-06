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
class consultarClientesUnitTest extends TestCase
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

    // -------------------------------------------------------------------------
    // selectAllClientes
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerListasDeClientes')]
    public function selectAllClientesRetornaEstructuraCorrecta(array $filas): void
    {
        // El primer prepare es para esSuperUsuario (get_conectSeguridad + fetch)
        // El segundo prepare es la consulta principal (get_conectGeneral + fetchAll)
        $mockStmtSeguridad = Mockery::mock(PDOStatement::class);
        $mockStmtSeguridad->shouldReceive('execute')->andReturn(true);
        $mockStmtSeguridad->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['idrol' => 2]); // usuario normal (rol != 1)

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
            ->andReturn(['idrol' => 1]); // superusuario (rol = SUPER_USUARIO_ROL_ID)

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

    // -------------------------------------------------------------------------
    // selectAllClientesActivos
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // selectClienteById
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // selectClienteByCedula
    // -------------------------------------------------------------------------

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

        // En caso de excepción, ejecutarVerificacionClientePorCedula retorna true
        // (asume que existe), por lo tanto selectClienteByCedula retorna ['cedula' => ...]
        $this->assertNotFalse($resultado);
    }
}
