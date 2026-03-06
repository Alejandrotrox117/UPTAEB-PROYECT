## Cuadro Nº 5: Módulo de Proveedores (RF005)

### Objetivos de la prueba

Validar que las operaciones CRUD (crear, consultar, editar, eliminar) del módulo de Proveedores se ejecuten correctamente cuando se proporcionan datos válidos y que el sistema rechace operaciones con identificaciones duplicadas, registros inexistentes o cuando ocurran excepciones en la base de datos. Se verifica que el sistema mantenga la integridad de los datos al prevenir duplicados y gestione adecuadamente los estados de activación/desactivación de proveedores.

### Técnicas

Pruebas de caja blanca con enfoque en el aislamiento de la lógica de negocio mediante mocks de PDO y PDOStatement. Se evalúan los métodos `insertProveedor()`, `selectAllProveedores()`, `selectProveedorById()`, `selectProveedoresActivos()`, `buscarProveedores()`, `updateProveedor()`, `deleteProveedorById()` y `reactivarProveedor()` en escenarios válidos e inválidos. Las pruebas verifican el manejo de transacciones, validaciones de duplicados, operaciones de consulta con diferentes filtros, y respuestas ante excepciones de base de datos. Se utilizan DataProviders para probar múltiples combinaciones de datos y se ejecutan en procesos separados para evitar interferencias entre tests.

### Código Involucrado

```php
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
class eliminarProveedorUnitTest extends TestCase
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
            'ID grande' => [99999],
            'ID enorme' => [12345678],
        ];
    }

    public static function providerIdsParaReactivar(): array
    {
        return [
            'ID inexistente 1' => [88888],
            'ID inexistente 2' => [77777],
        ];
    }

    // --- Tests: deleteProveedorById ---

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testDeleteProveedorById_IdInexistente_RetornaFalse(int $id): void
    {
        // rowCount = 0 → no se afectaron filas (proveedor no existe o ya inactivo)
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0);

        $result = $this->model->deleteProveedorById($id);

        $this->assertFalse($result);
    }

    #[Test]
    public function testDeleteProveedorById_IdExistente_RetornaTrue(): void
    {
        // rowCount = 1 → la fila fue actualizada a INACTIVO
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1);

        $result = $this->model->deleteProveedorById(5);

        $this->assertTrue($result);
    }

    #[Test]
    public function testDeleteProveedorById_ExcepcionEnBD_RetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \Exception('Error de BD simulado'));

        $result = $this->model->deleteProveedorById(5);

        $this->assertFalse($result);
    }

    // --- Tests: reactivarProveedor ---

    #[Test]
    #[DataProvider('providerIdsParaReactivar')]
    public function testReactivarProveedor_IdInexistente_StatusFalse(int $id): void
    {
        // rowCount = 0 → ninguna fila afectada
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0);

        $result = $this->model->reactivarProveedor($id);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    #[Test]
    public function testReactivarProveedor_IdExistente_StatusTrue(): void
    {
        // rowCount = 1 → fila actualizada a ACTIVO
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1);

        $result = $this->model->reactivarProveedor(3);

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
    }

    #[Test]
    public function testReactivarProveedor_ExcepcionEnBD_StatusFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \Exception('Error de BD simulado'));

        $result = $this->model->reactivarProveedor(3);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
}
```

### Caso de prueba

**TIPO:** Funcional (Caja blanca)

**OBJETIVO:** Verificar el correcto funcionamiento de todas las operaciones CRUD del módulo de Proveedores, asegurando la integridad de los datos, validación de duplicados y manejo adecuado de excepciones.

**DESCRIPCIÓN:** Se prueban ocho operaciones principales del modelo ProveedoresModel: creación, consulta, edición, eliminación y reactivación de proveedores. Las pruebas cubren tanto escenarios exitosos como casos de error, incluyendo la validación de identificaciones duplicadas y el manejo de excepciones de base de datos.

**ENTRADAS:**

- Creación: María López (V-12345678, con fecha de nacimiento), Carlos Ramos (V-98765432, sin fecha); duplicados V-11111111 y V-22222222; fallo de `lastInsertId`; excepción BD.
- Consultas: todos (superusuario y usuario regular); por ID existente (5 - Pedro Jiménez) e inexistente (99999, 12345678); solo activos; búsquedas (“Luis”, vacío, “xyzxyzxyz”).
- Actualización: datos completos (ID 10), solo nombre (ID 20), identificación duplicada (IDs 1 y 5), excepción BD.
- Eliminación: ID existente (5), inexistentes (99999, 12345678), excepción BD.
- Reactivación: ID inactivo (3), inexistentes (88888, 77777), excepción BD.

**SALIDAS ESPERADAS:**

- Inserción válida → `status true` + mensaje; duplicado / fallo → `status false` + mensaje.
- Consulta de todos → siempre `{status true, data [...]}`.
- Por ID existente → datos completos (idproveedor, nombre, apellido, identificacion, teléfono, correo, estatus); inexistente → `false`.
- Activos / búsquedas → `status true` + `data` (vacío si sin coincidencias).
- Actualización exitosa → `status true`; identificación duplicada / excepción → `status false`.
- Eliminación de existente → `true`; inexistente / inactivo / excepción → `false`.
- Reactivación exitosa → `status true`; inexistente / excepción → `status false`.

### Resultado

```
PS C:\xampp\htdocs\project> php vendor/bin/phpunit tests/unitTest/Proveedores/
PHPUnit 10.5.63 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: C:\xampp\htdocs\project\phpunit.xml

..............................                                    30 / 30 (100%)

Time: 00:06.077, Memory: 10.00 MB

There was 1 PHPUnit test runner warning:

1) No code coverage driver available

OK, but there were issues!
Tests: 30, Assertions: 68, PHPUnit Warnings: 1, PHPUnit Deprecations: 1.        


Command exited with code 1
```

### Observaciones

30 pruebas y 68 aserciones ejecutadas correctamente en ~6 s. La validación de duplicados de identificación opera tanto en creación como en actualización. El módulo cubre el ciclo CRUD completo más la operación de reactivación de registros inactivos.
