<?php

namespace Tests\UnitTest\Empleados;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\EmpleadosModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
class EmpleadoSelectUnitTest extends TestCase
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

    public static function providerSelectAllEmpleados(): array
    {
        return [
            'lista_con_varios_empleados' => [
                [
                    ['idempleado' => 1, 'nombre' => 'Carlos', 'apellido' => 'Pérez', 'estatus' => 'ACTIVO'],
                    ['idempleado' => 2, 'nombre' => 'María', 'apellido' => 'González', 'estatus' => 'ACTIVO'],
                ],
                true,
            ],
            'lista_vacia' => [
                [],
                true,
            ],
        ];
    }

    public static function providerGetEmpleadoById(): array
    {
        return [
            'empleado_existente' => [
                1,
                ['idempleado' => 1, 'nombre' => 'Carlos', 'apellido' => 'Pérez'],
                ['idempleado' => 1, 'nombre' => 'Carlos', 'apellido' => 'Pérez'],
            ],
            'empleado_no_existe' => [
                99999,
                false,
                false,
            ],
        ];
    }

    // ─── Tests ───────────────────────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerSelectAllEmpleados')]
    public function testSelectAllEmpleados_RetornaEstructuraCorrecta(array $empleadosMock, bool $statusEsperado): void
    {
        // Mock esSuperUsuario → sin super usuario (fetchAll para el SELECT de empleados)
        $this->mockStmt->shouldReceive('fetch')->andReturn(false);
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn($empleadosMock);

        $resultado = $this->model->selectAllEmpleados(0);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertEquals($statusEsperado, $resultado['status']);
        $this->assertIsArray($resultado['data']);
    }

    #[Test]
    #[DataProvider('providerGetEmpleadoById')]
    public function testGetEmpleadoById_RetornaEmpleadoOFalso(int $id, $fetchReturn, $valorEsperado): void
    {
        $this->mockStmt->shouldReceive('fetch')->andReturn($fetchReturn);

        $resultado = $this->model->getEmpleadoById($id);

        $this->assertEquals($valorEsperado, $resultado);
    }

    #[Test]
    public function testSelectAllEmpleados_FalloEnBD_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \Exception('DB connection failed'));

        $resultado = $this->model->selectAllEmpleados(0);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertStringContainsString('Error', $resultado['message']);
    }

    #[Test]
    public function testGetEmpleadoById_FalloEnBD_RetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \PDOException('Error de BD'));

        $resultado = $this->model->getEmpleadoById(1);

        $this->assertFalse($resultado);
    }
}
