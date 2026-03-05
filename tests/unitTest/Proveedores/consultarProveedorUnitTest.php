<?php

namespace Tests\UnitTest\Proveedores;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\ProveedoresModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class consultarProveedorUnitTest extends TestCase
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
        $this->mockPdo->shouldReceive('query')->andReturn($this->mockStmt)->byDefault();

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

    public static function providerIdsInexistentes(): array
    {
        return [
            'ID grande inexistente' => [99999],
            'ID muy grande'         => [12345678],
        ];
    }

    public static function providerTerminosBusqueda(): array
    {
        return [
            'término vacío'      => [''],
            'término sin match'  => ['xyzxyzxyz'],
        ];
    }

    // --- Tests: selectAllProveedores ---

    #[Test]
    public function testSelectAllProveedores_RetornaArrayConClavesStatus(): void
    {
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([]);
        // esSuperUsuario → fetch devuelve false (no es super usuario)
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);

        $result = $this->model->selectAllProveedores(0);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }

    #[Test]
    public function testSelectAllProveedores_CuandoFetchAllRetornaLista_StatusTrue(): void
    {
        $filas = [
            ['idproveedor' => 1, 'nombre' => 'Ana', 'apellido' => 'García', 'identificacion' => 'V-12345678', 'estatus' => 'ACTIVO'],
        ];
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn($filas);
        // esSuperUsuario → usuario con rol 1
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(['idrol' => 1]);

        $result = $this->model->selectAllProveedores(1);

        $this->assertTrue($result['status']);
        $this->assertCount(1, $result['data']);
    }

    #[Test]
    public function testSelectAllProveedores_SinUsuarioSesion_FiltroActivo(): void
    {
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([]);
        // Sin usuario de sesión → esSuperUsuario consulta usuario 0 → devuelve false
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);

        $result = $this->model->selectAllProveedores();

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
    }

    // --- Tests: selectProveedorById ---

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testSelectProveedorById_IdInexistente_RetornaFalse(int $id): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);

        $result = $this->model->selectProveedorById($id);

        $this->assertFalse($result);
    }

    #[Test]
    public function testSelectProveedorById_IdExistente_RetornaDatos(): void
    {
        $fila = [
            'idproveedor'             => 5,
            'nombre'                  => 'Pedro',
            'apellido'                => 'Jiménez',
            'identificacion'          => 'V-20000001',
            'telefono_principal'      => '04141234567',
            'correo_electronico'      => 'pedro@test.com',
            'estatus'                 => 'ACTIVO',
            'fecha_nacimiento_formato' => '01/01/1990',
        ];
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn($fila);

        $result = $this->model->selectProveedorById(5);

        $this->assertIsArray($result);
        $this->assertEquals(5, $result['idproveedor']);
        $this->assertEquals('Pedro', $result['nombre']);
    }

    // --- Tests: selectProveedoresActivos ---

    #[Test]
    public function testSelectProveedoresActivos_RetornaArrayConData(): void
    {
        $filas = [
            ['idproveedor' => 1, 'identificacion' => 'V-10000001', 'nombre_completo' => 'Ana García'],
        ];
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn($filas);

        $result = $this->model->selectProveedoresActivos();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertTrue($result['status']);
    }

    #[Test]
    public function testSelectProveedoresActivos_SinProveedores_DataVacia(): void
    {
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([]);

        $result = $this->model->selectProveedoresActivos();

        $this->assertTrue($result['status']);
        $this->assertEmpty($result['data']);
    }

    // --- Tests: buscarProveedores ---

    #[Test]
    #[DataProvider('providerTerminosBusqueda')]
    public function testBuscarProveedores_SinCoincidencias_DataVacia(string $termino): void
    {
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([]);

        $result = $this->model->buscarProveedores($termino);

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
        $this->assertEmpty($result['data']);
    }

    #[Test]
    public function testBuscarProveedores_ConCoincidencias_RetornaDatos(): void
    {
        $filas = [
            ['idproveedor' => 2, 'nombre' => 'Luis', 'apellido' => 'Pérez', 'identificacion' => 'V-30000001'],
        ];
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn($filas);

        $result = $this->model->buscarProveedores('Luis');

        $this->assertTrue($result['status']);
        $this->assertCount(1, $result['data']);
    }
}
