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
class EmpleadoUpdateUnitTest extends TestCase
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

    public static function providerCasosExitososUpdate(): array
    {
        return [
            'actualizacion_completa' => [[
                'idempleado'        => 1,
                'nombre'            => 'Carlos Actualizado',
                'apellido'          => 'Pérez',
                'identificacion'    => '12345678',
                'tipo_empleado'     => 'ADMINISTRATIVO',
                'estatus'           => 'ACTIVO',
                'telefono_principal'=> '04141234567',
                'correo_electronico'=> 'carlos.perez@email.com',
                'direccion'         => 'Av. Principal, Ciudad',
                'fecha_nacimiento'  => '1990-05-15',
                'genero'            => 'M',
                'puesto'            => 'Supervisor',
                'salario'           => 500.00,
            ]],
            'actualizacion_salario_cero' => [[
                'idempleado'     => 2,
                'nombre'         => 'Ana',
                'apellido'       => 'Torres',
                'identificacion' => 'V-22334455',
                'tipo_empleado'  => 'OPERARIO',
                'estatus'        => 'ACTIVO',
                'salario'        => 0.00,
            ]],
            'actualizacion_sin_campos_opcionales' => [[
                'idempleado'     => 3,
                'nombre'         => 'Pedro',
                'apellido'       => 'López',
                'identificacion' => 'V-55443322',
                'tipo_empleado'  => 'OPERARIO',
                'estatus'        => 'INACTIVO',
            ]],
        ];
    }

    public static function providerCasosFallidosUpdate(): array
    {
        return [
            'fallo_pdo_exception' => [[
                'idempleado'     => 1,
                'nombre'         => 'Fail',
                'apellido'       => 'Test',
                'identificacion' => 'V-00000000',
            ]],
        ];
    }

    // ─── Tests ───────────────────────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerCasosExitososUpdate')]
    public function testUpdateEmpleado_DatosValidos_RetornaTrue(array $data): void
    {
        $this->mockStmt->shouldReceive('execute')->andReturn(true);

        $resultado = $this->model->updateEmpleado($data);

        $this->assertTrue($resultado);
    }

    #[Test]
    #[DataProvider('providerCasosFallidosUpdate')]
    public function testUpdateEmpleado_FalloEnBD_RetornaFalse(array $data): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \PDOException('Error al actualizar'));

        $resultado = $this->model->updateEmpleado($data);

        $this->assertFalse($resultado);
    }

    #[Test]
    public function testUpdateEmpleado_ExecuteRetornaFalse_RetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andReturn(false);

        $resultado = $this->model->updateEmpleado([
            'idempleado'     => 99999,
            'nombre'         => 'Inexistente',
            'apellido'       => 'Prueba',
            'identificacion' => 'V-00000099',
        ]);

        $this->assertFalse($resultado);
    }
}
