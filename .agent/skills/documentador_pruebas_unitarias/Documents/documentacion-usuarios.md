## Cuadro Nº 7: Módulo de Gestión de Usuarios (RF07)

### Objetivos de la prueba

Validar que las operaciones de consulta, inserción y eliminación lógica de usuarios solo se ejecuten cuando los datos son válidos y se cumplen las restricciones del sistema (sin duplicados de correo o nombre de usuario, sin modificar super usuarios). El sistema debe rechazar inserciones con correo o nombre duplicado, impedir la consulta/eliminación de super usuarios por usuarios no privilegiados y retornar errores descriptivos ante fallos en la base de datos.

### Técnicas

Pruebas de caja blanca con enfoque en aislamiento mediante mocks de PDO y Mockery. Se evalúan los métodos `selectAllUsuarios()`, `selectAllUsuariosActivos()`, `selectUsuarioById()`, `selectUsuarioByEmail()`, `insertUsuario()` y `deleteUsuarioById()` en escenarios válidos, inválidos y de fallo de BD. Se verifica la protección contra super usuarios, la detección de duplicados con múltiples llamadas simuladas a `fetch`, y la gestión correcta de transacciones con rollback.

### Código Involucrado

```php
<?php
// ─────────────────────────────────────────────────────────────────────────────
// selectUsuarioUnitTest.php
// ─────────────────────────────────────────────────────────────────────────────

namespace Tests\UnitTest\Usuarios;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\UsuariosModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
class selectUsuarioUnitTest extends TestCase
{
    private UsuariosModel $usuariosModel;
    private $mockPdo;
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
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('rollBack')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(false)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->usuariosModel = new UsuariosModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // ─── Data Providers ──────────────────────────────────────────────────────

    public static function providerIdUsuariosNormales(): array
    {
        return [
            'id_bajo'  => [2],
            'id_medio' => [50],
        ];
    }

    public static function providerCorreosParaBusqueda(): array
    {
        return [
            'correo_simple'    => ['usuario@empresa.com'],
            'correo_con_punto' => ['j.doe@corp.net'],
        ];
    }

    // ─── Tests: selectAllUsuarios ─────────────────────────────────────────────

    #[Test]
    public function testSelectAllUsuarios_UsuarioNormal_RetornaArrayConStatus(): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn([[
                'idusuario'                  => 2,
                'usuario'                    => 'empleado',
                'correo'                     => 'empleado@empresa.com',
                'estatus'                    => 'ACTIVO',
                'idrol'                      => 2,
                'personaId'                  => null,
                'fecha_creacion'             => '2024-01-01 08:00:00',
                'fecha_modificacion'         => '2024-03-01 10:00:00',
                'rol_nombre'                 => 'EMPLEADO',
                'fecha_creacion_formato'     => '01/01/2024',
                'fecha_modificacion_formato' => '01/03/2024',
            ]]);

        $resultado = $this->usuariosModel->selectAllUsuarios(0);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status']);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertIsArray($resultado['data']);
        $this->assertCount(1, $resultado['data']);
    }

    #[Test]
    public function testSelectAllUsuariosActivos_RetornaMismaEstructura(): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn([]);

        $resultado = $this->usuariosModel->selectAllUsuariosActivos(0);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status']);
        $this->assertArrayHasKey('data', $resultado);
    }

    #[Test]
    public function testSelectAllUsuarios_ErrorEnBD_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')
            ->andThrow(new \Exception('Fallo simulado de BD'));

        $resultado = $this->usuariosModel->selectAllUsuarios(0);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertArrayHasKey('message', $resultado);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertEmpty($resultado['data']);
    }

    // ─── Tests: selectUsuarioById ─────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerIdUsuariosNormales')]
    public function testSelectUsuarioById_UsuarioRegular_RetornaArrayConDatos(int $id): void
    {
        $callCount = 0;
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturnUsing(function () use (&$callCount, $id) {
                $callCount++;
                if ($callCount <= 2) {
                    return ['total' => 0];
                }
                return [
                    'idusuario'                  => $id,
                    'idrol'                      => 2,
                    'usuario'                    => 'usuario_test',
                    'correo'                     => 'test@empresa.com',
                    'personaId'                  => null,
                    'estatus'                    => 'ACTIVO',
                    'fecha_creacion'             => '2024-01-01',
                    'fecha_modificacion'         => '2024-03-01',
                    'rol_nombre'                 => 'EMPLEADO',
                    'fecha_creacion_formato'     => '01/01/2024',
                    'fecha_modificacion_formato' => '01/03/2024',
                ];
            });

        $resultado = $this->usuariosModel->selectUsuarioById($id, 0);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('idusuario', $resultado);
        $this->assertArrayHasKey('usuario', $resultado);
        $this->assertArrayHasKey('correo', $resultado);
        $this->assertEquals($id, $resultado['idusuario']);
    }

    #[Test]
    public function testSelectUsuarioById_UsuarioInexistente_RetornaFalse(): void
    {
        $callCount = 0;
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                if ($callCount <= 2) {
                    return ['total' => 0];
                }
                return false;
            });

        $resultado = $this->usuariosModel->selectUsuarioById(99999, 0);

        $this->assertFalse($resultado);
    }

    #[Test]
    public function testSelectUsuarioById_IntentarAccederSuperUsuario_RetornaFalse(): void
    {
        $callCount = 0;
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                return ['total' => $callCount === 1 ? 1 : 0];
            });

        $resultado = $this->usuariosModel->selectUsuarioById(1, 5);

        $this->assertFalse($resultado);
    }

    // ─── Tests: selectUsuarioByEmail ─────────────────────────────────────────

    #[Test]
    #[DataProvider('providerCorreosParaBusqueda')]
    public function testSelectUsuarioByEmail_CorreoExistente_RetornaArray(string $correo): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->andReturn(['total' => 1]);

        $resultado = $this->usuariosModel->selectUsuarioByEmail($correo);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('correo', $resultado);
        $this->assertEquals($correo, $resultado['correo']);
    }

    #[Test]
    public function testSelectUsuarioByEmail_CorreoInexistente_RetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->andReturn(['total' => 0]);

        $resultado = $this->usuariosModel->selectUsuarioByEmail('noexiste@example.com');

        $this->assertFalse($resultado);
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// insertUsuarioUnitTest.php
// ─────────────────────────────────────────────────────────────────────────────

namespace Tests\UnitTest\Usuarios;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\UsuariosModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
class insertUsuarioUnitTest extends TestCase
{
    private UsuariosModel $usuariosModel;
    private $mockPdo;
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
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('commit')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('rollBack')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(false)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('15')->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->usuariosModel = new UsuariosModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // ─── Data Providers ──────────────────────────────────────────────────────

    public static function providerInsertExitoso(): array
    {
        return [
            'usuario_con_persona' => [[
                'personaId' => 3,
                'usuario'   => 'jdoe',
                'correo'    => 'jdoe@example.com',
                'clave'     => 'SecurePass1!',
                'idrol'     => 2,
            ]],
            'usuario_sin_persona' => [[
                'personaId' => null,
                'usuario'   => 'sysop',
                'correo'    => 'sysop@example.com',
                'clave'     => 'AnotherPass1!',
                'idrol'     => 2,
            ]],
            'usuario_solo_obligatorios' => [[
                'personaId' => null,
                'usuario'   => 'minimal',
                'correo'    => 'minimal@corp.com',
                'clave'     => 'Pass123!',
                'idrol'     => 2,
            ]],
        ];
    }

    public static function providerCorreoDuplicado(): array
    {
        return [
            'correo_admin'     => ['admin@admin.com'],
            'correo_existente' => ['duplicado@empresa.com'],
        ];
    }

    public static function providerNombreDuplicado(): array
    {
        return [
            'nombre_admin'     => ['admin'],
            'nombre_existente' => ['operador1'],
        ];
    }

    // ─── Tests ───────────────────────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerInsertExitoso')]
    public function testInsertUsuario_DatosValidos_RetornaStatusTrue(array $data): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->andReturn(['total' => 0]);

        $resultado = $this->usuariosModel->insertUsuario($data);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status'], 'Se esperaba status true: ' . ($resultado['message'] ?? ''));
        $this->assertArrayHasKey('usuario_id', $resultado);
        $this->assertEquals('15', $resultado['usuario_id']);
        $this->assertStringContainsString('exitosamente', $resultado['message']);
    }

    #[Test]
    #[DataProvider('providerCorreoDuplicado')]
    public function testInsertUsuario_CorreoDuplicado_RetornaStatusFalse(string $correo): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->andReturn(['total' => 1]);

        $resultado = $this->usuariosModel->insertUsuario([
            'personaId' => 1,
            'usuario'   => 'usuario_nuevo',
            'correo'    => $correo,
            'clave'     => 'Pass123!',
            'idrol'     => 2,
        ]);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertStringContainsStringIgnoringCase('correo', $resultado['message']);
        $this->assertNull($resultado['usuario_id']);
    }

    #[Test]
    #[DataProvider('providerNombreDuplicado')]
    public function testInsertUsuario_NombreUsuarioDuplicado_RetornaStatusFalse(string $nombreUsuario): void
    {
        $callCount = 0;
        $this->mockStmt->shouldReceive('fetch')
            ->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                return ['total' => $callCount === 1 ? 0 : 1];
            });

        $resultado = $this->usuariosModel->insertUsuario([
            'personaId' => 1,
            'usuario'   => $nombreUsuario,
            'correo'    => 'correonuevo@example.com',
            'clave'     => 'Pass123!',
            'idrol'     => 2,
        ]);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertStringContainsStringIgnoringCase('usuario', $resultado['message']);
        $this->assertNull($resultado['usuario_id']);
    }

    #[Test]
    public function testInsertUsuario_FalloEnBD_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->andReturn(['total' => 0]);

        $callCount = 0;
        $this->mockStmt->shouldReceive('execute')
            ->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                if ($callCount >= 3) {
                    throw new \PDOException('Simulated DB constraint violation');
                }
                return true;
            });

        $this->mockPdo->shouldReceive('inTransaction')->andReturn(true);

        $resultado = $this->usuariosModel->insertUsuario([
            'personaId' => 1,
            'usuario'   => 'usuario_fallo',
            'correo'    => 'fallo@example.com',
            'clave'     => 'Pass123!',
            'idrol'     => 2,
        ]);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertArrayHasKey('message', $resultado);
        $this->assertNull($resultado['usuario_id']);
    }

    #[Test]
    public function testInsertUsuario_SinLastInsertId_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->andReturn(['total' => 0]);

        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('0');

        $resultado = $this->usuariosModel->insertUsuario([
            'personaId' => null,
            'usuario'   => 'usuario_sin_id',
            'correo'    => 'sinid@example.com',
            'clave'     => 'Pass123!',
            'idrol'     => 2,
        ]);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertArrayHasKey('message', $resultado);
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// deleteUsuarioUnitTest.php
// ─────────────────────────────────────────────────────────────────────────────

namespace Tests\UnitTest\Usuarios;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\UsuariosModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
class deleteUsuarioUnitTest extends TestCase
{
    private UsuariosModel $usuariosModel;
    private $mockPdo;
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');

        $this->mockPdo  = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('commit')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('rollBack')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(false)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->usuariosModel = new UsuariosModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // ─── Data Providers ──────────────────────────────────────────────────────

    public static function providerUsuariosEliminables(): array
    {
        return [
            'usuario_id_2'  => [2],
            'usuario_id_10' => [10],
            'usuario_id_50' => [50],
        ];
    }

    // ─── Tests ───────────────────────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerUsuariosEliminables')]
    public function testDeleteUsuarioById_UsuarioRegular_RetornaTrue(int $id): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        $this->mockStmt->shouldReceive('rowCount')->andReturn(1);

        $resultado = $this->usuariosModel->deleteUsuarioById($id, 0);

        $this->assertTrue($resultado);
    }

    #[Test]
    public function testDeleteUsuarioById_SuperUsuario_RetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 1]);

        $resultado = $this->usuariosModel->deleteUsuarioById(1, 5);

        $this->assertFalse($resultado);
    }

    #[Test]
    public function testDeleteUsuarioById_UsuarioInexistente_RetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        $this->mockStmt->shouldReceive('rowCount')->andReturn(0);

        $resultado = $this->usuariosModel->deleteUsuarioById(99999, 0);

        $this->assertFalse($resultado);
    }

    #[Test]
    public function testDeleteUsuarioById_FalloEnBD_RetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        $callCount = 0;
        $this->mockStmt->shouldReceive('execute')
            ->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                if ($callCount >= 2) {
                    throw new \PDOException('Fallo de BD simulado al eliminar');
                }
                return true;
            });

        $this->mockPdo->shouldReceive('inTransaction')->andReturn(true);

        $resultado = $this->usuariosModel->deleteUsuarioById(5, 0);

        $this->assertFalse($resultado);
    }
}
```

### Caso de prueba

**TIPO:** Funcional (Caja blanca)

**OBJETIVO:** Verificar que `UsuariosModel` gestione correctamente la consulta, creación y eliminación lógica de usuarios, respetando las restricciones de unicidad, protección de super usuarios y manejo de fallos en la base de datos.

**DESCRIPCIÓN:** Se prueban 25 casos distribuidos en tres clases: consultas de usuarios (listado, por ID, por email), inserción con detección de duplicados y fallo de BD, y eliminación lógica con verificación de roles y estado.

**ENTRADAS:**
- IDs de usuarios regulares: 2, 10, 50; ID inexistente: 99999; ID de super usuario: 1
- Correos de búsqueda: `usuario@empresa.com`, `j.doe@corp.net`, `noexiste@example.com`
- Insertions válidas: usuario con y sin `personaId`, solo campos obligatorios (`jdoe`, `sysop`, `minimal`)
- Correos duplicados: `admin@admin.com`, `duplicado@empresa.com`
- Nombres de usuario duplicados: `admin`, `operador1`
- Fallo de BD en INSERT: excepción `PDOException` al ejecutar; `lastInsertId` retorna `'0'`
- Fallo de BD en DELETE: `PDOException` al ejecutar UPDATE de estatus

**SALIDAS ESPERADAS:**
| Escenario | Resultado esperado |
|---|---|
| `selectAllUsuarios` usuario normal | `status true` + array `data` con registros |
| `selectAllUsuariosActivos` sin datos | `status true` + `data` vacío |
| `selectAllUsuarios` con error BD | `status false` + `message` + `data` vacío |
| `selectUsuarioById` usuario regular (ID 2, 50) | array con `idusuario`, `usuario`, `correo` |
| `selectUsuarioById` ID inexistente o super usuario | `false` |
| `selectUsuarioByEmail` correo existente | array con clave `correo` igual al buscado |
| `selectUsuarioByEmail` correo inexistente | `false` |
| `insertUsuario` datos válidos | `status true` + `usuario_id = '15'` + mensaje "exitosamente" |
| `insertUsuario` correo duplicado | `status false` + mensaje con "correo" + `usuario_id null` |
| `insertUsuario` nombre duplicado | `status false` + mensaje con "usuario" + `usuario_id null` |
| `insertUsuario` fallo BD / `lastInsertId = 0` | `status false` + `message` presente |
| `deleteUsuarioById` usuario regular (ID 2, 10, 50) | `true` |
| `deleteUsuarioById` super usuario | `false` |
| `deleteUsuarioById` ID inexistente o fallo BD | `false` |

### Resultado

```
PHPUnit 10.5.63 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: C:\xampp\htdocs\project\phpunit.xml

.........................                                         25 / 25 (100%)

Time: 00:04.542, Memory: 10.00 MB

delete Usuario Unit (Tests\UnitTest\Usuarios\deleteUsuarioUnit)
 ✔ DeleteUsuarioById UsuarioRegular RetornaTrue with usuario_id_2
 ✔ DeleteUsuarioById UsuarioRegular RetornaTrue with usuario_id_10
 ✔ DeleteUsuarioById UsuarioRegular RetornaTrue with usuario_id_50
 ✔ DeleteUsuarioById SuperUsuario RetornaFalse
 ✔ DeleteUsuarioById UsuarioInexistente RetornaFalse
 ✔ DeleteUsuarioById FalloEnBD RetornaFalse

insert Usuario Unit (Tests\UnitTest\Usuarios\insertUsuarioUnit)
 ✔ InsertUsuario DatosValidos RetornaStatusTrue with usuario_con_persona
 ✔ InsertUsuario DatosValidos RetornaStatusTrue with usuario_sin_persona
 ✔ InsertUsuario DatosValidos RetornaStatusTrue with usuario_solo_obligatorios
 ✔ InsertUsuario CorreoDuplicado RetornaStatusFalse with correo_admin
 ✔ InsertUsuario CorreoDuplicado RetornaStatusFalse with correo_existente
 ✔ InsertUsuario NombreUsuarioDuplicado RetornaStatusFalse with nombre_admin
 ✔ InsertUsuario NombreUsuarioDuplicado RetornaStatusFalse with nombre_existente
 ✔ InsertUsuario FalloEnBD RetornaStatusFalse
 ✔ InsertUsuario SinLastInsertId RetornaStatusFalse

select Usuario Unit (Tests\UnitTest\Usuarios\selectUsuarioUnit)
 ✔ SelectAllUsuarios UsuarioNormal RetornaArrayConStatus
 ✔ SelectAllUsuariosActivos RetornaMismaEstructura
 ✔ SelectAllUsuarios ErrorEnBD RetornaStatusFalse
 ✔ SelectUsuarioById UsuarioRegular RetornaArrayConDatos with id_bajo
 ✔ SelectUsuarioById UsuarioRegular RetornaArrayConDatos with id_medio
 ✔ SelectUsuarioById UsuarioInexistente RetornaFalse
 ✔ SelectUsuarioById IntentarAccederSuperUsuario RetornaFalse
 ✔ SelectUsuarioByEmail CorreoExistente RetornaArray with correo_simple
 ✔ SelectUsuarioByEmail CorreoExistente RetornaArray with correo_con_punto
 ✔ SelectUsuarioByEmail CorreoInexistente RetornaFalse

OK (25 tests, 76 assertions)
```

### Observaciones

Las 25 pruebas se ejecutaron exitosamente en 4.5 s con 10 MB de memoria, cubriendo 76 aserciones. Se validaron correctamente las tres operaciones principales (consulta, inserción y eliminación lógica), con especial énfasis en la protección de super usuarios mediante verificación previa al acceso o modificación. La detección de duplicados de correo y nombre de usuario se verifica mediante llamadas secuenciales simuladas a `fetch`, garantizando la integridad de los datos antes de ejecutar la transacción de inserción.
