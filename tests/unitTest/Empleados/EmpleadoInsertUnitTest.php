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
class EmpleadoInsertUnitTest extends TestCase
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

    public static function providerCasosExitososInsert(): array
    {
        return [
            'empleado_operario_completo' => [[
                'nombre'            => 'María',
                'apellido'          => 'González',
                'identificacion'    => 'V-12345678',
                'tipo_empleado'     => 'OPERARIO',
                'puesto'            => 'Operario de Clasificación',
                'salario'           => 30.00,
                'fecha_nacimiento'  => '1995-03-15',
                'direccion'         => 'Urbanización La Victoria, Calle 5',
                'correo_electronico'=> 'maria.gonzalez@recicladora.com',
                'telefono_principal'=> '0414-5551234',
                'genero'            => 'F',
                'fecha_inicio'      => '2024-01-01',
                'observaciones'     => 'Especializada en cartón y papel',
                'estatus'           => 'ACTIVO',
            ]],
            'empleado_sin_campos_opcionales' => [[
                'nombre'         => 'Luis',
                'apellido'       => 'Ramírez',
                'identificacion' => 'V-87654321',
                'tipo_empleado'  => 'ADMINISTRATIVO',
                'estatus'        => 'ACTIVO',
            ]],
            'empleado_con_salario_cero' => [[
                'nombre'         => 'Ana',
                'apellido'       => 'Torres',
                'identificacion' => 'V-11223344',
                'salario'        => 0.00,
                'estatus'        => 'ACTIVO',
            ]],
        ];
    }

    public static function providerCasosFallidosInsert(): array
    {
        return [
            'execute_falla_en_bd' => [[
                'nombre'         => 'Test',
                'apellido'       => 'Fail',
                'identificacion' => 'V-00000001',
            ]],
        ];
    }

    // ─── Tests ───────────────────────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerCasosExitososInsert')]
    public function testInsertEmpleado_DatosValidos_RetornaTrue(array $data): void
    {
        $this->mockStmt->shouldReceive('execute')->andReturn(true);

        $resultado = $this->model->insertEmpleado($data);

        $this->assertTrue($resultado);
    }

    #[Test]
    #[DataProvider('providerCasosFallidosInsert')]
    public function testInsertEmpleado_FalloEnBD_RetornaFalse(array $data): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \PDOException('Duplicate entry'));

        $resultado = $this->model->insertEmpleado($data);

        $this->assertFalse($resultado);
    }

    #[Test]
    public function testInsertEmpleado_ExecuteRetornaFalse_RetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andReturn(false);

        $resultado = $this->model->insertEmpleado([
            'nombre'         => 'Test',
            'apellido'       => 'Prueba',
            'identificacion' => 'V-99999999',
        ]);

        $this->assertFalse($resultado);
    }
}
