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
class crearProveedorUnitTest extends TestCase
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
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn("0")->byDefault();

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

    public static function providerDatosValidosInsert(): array
    {
        return [
            'con fecha nacimiento' => [[
                'nombre'             => 'María',
                'apellido'           => 'López',
                'identificacion'     => 'V-12345678',
                'fecha_nacimiento'   => '1990-05-15',
                'direccion'          => 'Av. Principal, Caracas',
                'correo_electronico' => 'maria@test.com',
                'telefono_principal' => '04121234567',
                'observaciones'      => 'Proveedor de muestra',
                'genero'             => 'F',
            ]],
            'sin fecha nacimiento' => [[
                'nombre'             => 'Carlos',
                'apellido'           => 'Ramos',
                'identificacion'     => 'V-98765432',
                'fecha_nacimiento'   => '',
                'direccion'          => 'Calle 5, Valencia',
                'correo_electronico' => 'carlos@test.com',
                'telefono_principal' => '04241234567',
                'observaciones'      => '',
                'genero'             => 'M',
            ]],
        ];
    }

    public static function providerIdentificacionDuplicada(): array
    {
        return [
            'Identificación ya registrada' => ['V-11111111'],
            'Cédula existente'             => ['V-22222222'],
        ];
    }

    // --- Tests: insertProveedor ---

    #[Test]
    #[DataProvider('providerDatosValidosInsert')]
    public function testInsertProveedor_IdentificacionNueva_Exitosa(array $data): void
    {
        // Verificación: no existe identificación (fetch → devuelve ['total' => 0])
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);
        // Inserción exitosa: lastInsertId devuelve un ID real
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn("42");

        $result = $this->model->insertProveedor($data);

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
        $this->assertStringContainsString('exitosamente', $result['message']);
    }

    #[Test]
    #[DataProvider('providerIdentificacionDuplicada')]
    public function testInsertProveedor_IdentificacionDuplicada_RetornaFalse(string $identificacion): void
    {
        $data = [
            'nombre'             => 'Test',
            'apellido'           => 'Duplicado',
            'identificacion'     => $identificacion,
            'fecha_nacimiento'   => '',
            'direccion'          => 'Dirección test',
            'correo_electronico' => 'dup@test.com',
            'telefono_principal' => '04140000000',
            'observaciones'      => '',
            'genero'             => 'M',
        ];

        // Verificación: existe identificación (total > 0)
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 1]);

        $result = $this->model->insertProveedor($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsString('duplicada', strtolower($result['message']));
    }

    #[Test]
    public function testInsertProveedor_FallaLastInsertId_RetornaFalse(): void
    {
        $data = [
            'nombre'             => 'Pedro',
            'apellido'           => 'Sánchez',
            'identificacion'     => 'V-55555555',
            'fecha_nacimiento'   => '2000-01-01',
            'direccion'          => 'Calle Real',
            'correo_electronico' => 'pedro@test.com',
            'telefono_principal' => '04120000000',
            'observaciones'      => '',
            'genero'             => 'M',
        ];

        // Verificación pasa (no duplicado)
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);
        // La inserción no genera ID
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn("0");

        $result = $this->model->insertProveedor($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    #[Test]
    public function testInsertProveedor_ExcepcionEnBD_RetornaFalse(): void
    {
        $data = [
            'nombre'             => 'Error',
            'apellido'           => 'Test',
            'identificacion'     => 'V-00000000',
            'fecha_nacimiento'   => '',
            'direccion'          => 'Dir',
            'correo_electronico' => 'error@test.com',
            'telefono_principal' => '04140000001',
            'observaciones'      => '',
            'genero'             => 'F',
        ];

        // Verificación pasa
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);
        // execute lanza excepción en la inserción
        $this->mockStmt->shouldReceive('execute')->andThrow(new \Exception('Error de BD simulado'));

        $result = $this->model->insertProveedor($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
}
