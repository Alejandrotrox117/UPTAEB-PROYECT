<?php

namespace Tests\UnitTest\Proveedores;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\ProveedoresModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
class editarProveedorUnitTest extends TestCase
{
    private ProveedoresModel $model;
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

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new ProveedoresModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // --- DataProviders ---

    public static function providerDatosActualizacionExitosa(): array
    {
        return [
            'actualización completa' => [
                10,
                [
                    'nombre'             => 'Proveedor Actualizado',
                    'apellido'           => 'S.A.',
                    'identificacion'     => 'J-30123456-7',
                    'fecha_nacimiento'   => '',
                    'direccion'          => 'Zona Industrial, Local 5',
                    'correo_electronico' => 'contacto@proveedor.com',
                    'telefono_principal' => '02121234567',
                    'observaciones'      => 'Actualizado via test',
                    'genero'             => 'M',
                ],
            ],
            'solo nombre cambiado' => [
                20,
                [
                    'nombre'             => 'Nuevo Nombre',
                    'apellido'           => 'Existente',
                    'identificacion'     => 'V-40000001',
                    'fecha_nacimiento'   => '1985-03-20',
                    'direccion'          => 'Calle 10',
                    'correo_electronico' => 'nuevo@test.com',
                    'telefono_principal' => '04160000001',
                    'observaciones'      => '',
                    'genero'             => 'F',
                ],
            ],
        ];
    }

    public static function providerIdentificacionDuplicadaUpdate(): array
    {
        return [
            'ID 1 con identificación tomada' => [1, 'V-99999999'],
            'ID 5 con identificación tomada' => [5, 'J-12345678-0'],
        ];
    }

    // --- Tests: updateProveedor ---

    #[Test]
    #[DataProvider('providerDatosActualizacionExitosa')]
    public function testUpdateProveedor_SinDuplicado_RetornaStatusTrue(int $id, array $data): void
    {
        // Verificación de duplicado → no hay coincidencias
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        $result = $this->model->updateProveedor($id, $data);

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
        $this->assertStringContainsString('actualizado', strtolower($result['message']));
    }

    #[Test]
    #[DataProvider('providerIdentificacionDuplicadaUpdate')]
    public function testUpdateProveedor_IdentificacionDuplicada_RetornaFalse(int $id, string $identificacion): void
    {
        $data = [
            'nombre'             => 'Test',
            'apellido'           => 'Actualizar',
            'identificacion'     => $identificacion,
            'fecha_nacimiento'   => '',
            'direccion'          => 'Dir',
            'correo_electronico' => 'upd@test.com',
            'telefono_principal' => '04140000000',
            'observaciones'      => '',
            'genero'             => 'M',
        ];

        // Verificación → identificación ya existe en otro registro
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 1]);

        $result = $this->model->updateProveedor($id, $data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsString('duplicada', strtolower($result['message']));
    }

    #[Test]
    public function testUpdateProveedor_ExcepcionEnBD_RetornaFalse(): void
    {
        $data = [
            'nombre'             => 'Error',
            'apellido'           => 'Test',
            'identificacion'     => 'V-00000001',
            'fecha_nacimiento'   => '',
            'direccion'          => 'Dir',
            'correo_electronico' => 'err@test.com',
            'telefono_principal' => '04140000002',
            'observaciones'      => '',
            'genero'             => 'M',
        ];

        // Verificación pasa (no duplicado)
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);
        // El execute del UPDATE lanza excepción
        $this->mockStmt->shouldReceive('execute')->andThrow(new \Exception('Error de BD simulado'));

        $result = $this->model->updateProveedor(99, $data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
}
