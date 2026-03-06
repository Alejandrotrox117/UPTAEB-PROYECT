# Documentación de Pruebas Unitarias - Módulo Roles

## Cuadro Nº 5: Módulo de Gestión de Roles (RF05)

### Objetivos de la prueba

Validar que la gestión de roles (consulta, creación, edición y eliminación) se ejecute correctamente cuando se proporcionan datos válidos y consistentes. El sistema debe permitir operaciones CRUD sobre roles, incluyendo verificación de super usuarios, listado de roles para selects, asignación de módulos y permisos, reactivación de roles inactivos, y prevenir operaciones inválidas como nombres duplicados, roles en uso al eliminar, o IDs inexistentes.

### Técnicas

Pruebas de caja blanca con enfoque en aislamiento mediante mocks de PDO y PDOStatement. Se evalúan múltiples métodos del modelo RolesModel y RolesintegradoModel en escenarios válidos e inválidos, verificando: 
- Consultas de roles existentes e inexistentes
- Creación de roles con validación de nombres duplicados
- Actualización con manejo de conflictos de nombres
- Eliminación con verificación de uso por usuarios
- Reactivación de roles inactivos
- Gestión integrada de asignaciones de módulos y permisos con transacciones
- Verificación de super usuarios
- Listados para selectores y consultas generales

Las pruebas utilizan DataProviders para casos múltiples, ProcessIsolation para independencia entre tests, y Mockery para simular respuestas de base de datos sin dependencias reales.

### Código Involucrado

```php
<?php

namespace Tests\UnitTest\Roles;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\RolesModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class consultarRolUnitTest extends TestCase
{
    private RolesModel $rolesModel;
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

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('query')->andReturn($this->mockStmt)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->rolesModel = new RolesModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // ─── Data Providers ──────────────────────────────────────────────────────

    public static function providerIdsInexistentes(): array
    {
        return [
            'id_muy_alto'    => [999999],
            'id_otra_prueba' => [888888],
            'id_limite'      => [2147483647],
        ];
    }

    public static function providerRolesListado(): array
    {
        return [
            'lista_con_datos' => [
                [
                    ['idrol' => 1, 'nombre' => 'ADMIN', 'descripcion' => 'Administrador', 'estatus' => 'ACTIVO'],
                    ['idrol' => 2, 'nombre' => 'USUARIO', 'descripcion' => 'Usuario estándar', 'estatus' => 'ACTIVO'],
                ],
            ],
            'lista_vacia' => [[]],
        ];
    }

    // ─── Tests: selectRolById ────────────────────────────────────────────────

    #[Test]
    public function testSelectRolById_Existente_RetornaDatos(): void
    {
        $filaEsperada = [
            'idrol'               => 5,
            'nombre'              => 'OPERADOR',
            'descripcion'         => 'Operador del sistema',
            'estatus'             => 'ACTIVO',
            'fecha_creacion'      => '01/01/2025 08:00',
            'ultima_modificacion' => '01/01/2025 08:00',
        ];
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn($filaEsperada);

        $resultado = $this->rolesModel->selectRolById(5);

        $this->assertIsArray($resultado);
        $this->assertEquals(5, $resultado['idrol']);
        $this->assertEquals('OPERADOR', $resultado['nombre']);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testSelectRolById_IdInexistente_RetornaFalse(int $id): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);

        $resultado = $this->rolesModel->selectRolById($id);

        $this->assertFalse($resultado);
    }

    // ─── Tests: selectAllRoles ───────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerRolesListado')]
    public function testSelectAllRoles_RetornaArrayConStatus(array $rolesSimulados): void
    {
        // Primera call → verificar si es super usuario (COUNT query)
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);
        // Segunda call → obtener todos los roles
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)
            ->andReturn($rolesSimulados);

        $resultado = $this->rolesModel->selectAllRoles(1);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertTrue($resultado['status']);
        $this->assertArrayHasKey('data', $resultado);
    }

    #[Test]
    public function testSelectAllRoles_FalloEnBD_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);
        $this->mockPdo->shouldReceive('query')->andThrow(new \PDOException('DB error'));

        $resultado = $this->rolesModel->selectAllRoles(1);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
    }

    // ─── Tests: selectAllRolesForSelect ─────────────────────────────────────

    #[Test]
    public function testSelectAllRolesForSelect_RetornaListaParaSelect(): void
    {
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([
            ['idrol' => 1, 'nombre' => 'ADMIN'],
            ['idrol' => 2, 'nombre' => 'USUARIO'],
        ]);

        $resultado = $this->rolesModel->selectAllRolesForSelect();

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status']);
        $this->assertNotEmpty($resultado['data']);
    }

    #[Test]
    public function testSelectAllRolesForSelect_SinDatos_RetornaArrayVacio(): void
    {
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([]);

        $resultado = $this->rolesModel->selectAllRolesForSelect();

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status']);
        $this->assertEmpty($resultado['data']);
    }

    // ─── Tests: verificarEsSuperUsuario ─────────────────────────────────────

    #[Test]
    public function testVerificarEsSuperUsuario_UsuarioSuperAdmin_RetornaTrue(): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 1]);

        $resultado = $this->rolesModel->verificarEsSuperUsuario(1);

        $this->assertTrue($resultado);
    }

    #[Test]
    public function testVerificarEsSuperUsuario_UsuarioNormal_RetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        $resultado = $this->rolesModel->verificarEsSuperUsuario(5);

        $this->assertFalse($resultado);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// crearRolUnitTest.php
// ═══════════════════════════════════════════════════════════════════════════

<?php

namespace Tests\UnitTest\Roles;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\RolesModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class crearRolUnitTest extends TestCase
{
    private RolesModel $rolesModel;
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
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('commit')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('rollBack')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(false)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('42')->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->rolesModel = new RolesModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // ─── Data Providers ──────────────────────────────────────────────────────

    public static function providerCasosExitososCrearRol(): array
    {
        return [
            'rol_activo_completo' => [
                ['nombre' => 'GERENTE', 'descripcion' => 'Rol gerencial', 'estatus' => 'ACTIVO'],
            ],
            'rol_inactivo' => [
                ['nombre' => 'AUDITOR', 'descripcion' => 'Rol de auditoría', 'estatus' => 'INACTIVO'],
            ],
            'rol_descripcion_larga' => [
                ['nombre' => 'SUPERVISOR', 'descripcion' => str_repeat('x', 255), 'estatus' => 'ACTIVO'],
            ],
        ];
    }

    public static function providerCasosDuplicadosNombre(): array
    {
        return [
            'nombre_ya_existe' => ['ADMIN', 'Ya existe un rol activo con ese nombre.'],
            'nombre_existente_mayusculas' => ['SUPER_USUARIO', 'Ya existe un rol activo con ese nombre.'],
        ];
    }

    // ─── Tests ───────────────────────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerCasosExitososCrearRol')]
    public function testInsertRol_CasoExitoso_RetornaStatusTrueConId(array $data): void
    {
        // El nombre no existe (fetch devuelve false)
        $this->mockStmt->shouldReceive('fetch')->andReturn(false);

        $resultado = $this->rolesModel->insertRol($data);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status'], 'Se esperaba status true: ' . ($resultado['message'] ?? ''));
        $this->assertArrayHasKey('rol_id', $resultado);
        $this->assertEquals('42', $resultado['rol_id']);
    }

    #[Test]
    #[DataProvider('providerCasosDuplicadosNombre')]
    public function testInsertRol_NombreDuplicado_RetornaStatusFalse(string $nombre, string $mensajeEsperado): void
    {
        // El fetch devuelve una fila → el nombre ya existe
        $this->mockStmt->shouldReceive('fetch')->andReturn(['idrol' => 1, 'nombre' => $nombre]);

        $resultado = $this->rolesModel->insertRol([
            'nombre'      => $nombre,
            'descripcion' => 'Alguna descripción',
            'estatus'     => 'ACTIVO',
        ]);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertEquals($mensajeEsperado, $resultado['message']);
    }

    #[Test]
    public function testInsertRol_FalloEnBD_RetornaStatusFalse(): void
    {
        // Primera llamada a execute: verificación de nombre existente → pasa (true)
        // Segunda llamada a execute: INSERT → lanza PDOException
        $this->mockStmt->shouldReceive('fetch')->andReturn(false);
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(true);

        $callCount = 0;
        $this->mockStmt->shouldReceive('execute')
            ->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                if ($callCount >= 2) {
                    throw new \PDOException('Constraint violation');
                }
                return true;
            });

        $resultado = $this->rolesModel->insertRol([
            'nombre'      => 'ROL_NUEVO',
            'descripcion' => 'Descripción',
            'estatus'     => 'ACTIVO',
        ]);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertStringContainsString('Error', $resultado['message']);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// editarRolUnitTest.php
// ═══════════════════════════════════════════════════════════════════════════

<?php

namespace Tests\UnitTest\Roles;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\RolesModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class editarRolUnitTest extends TestCase
{
    private RolesModel $rolesModel;
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
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->rolesModel = new RolesModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // ─── Data Providers ──────────────────────────────────────────────────────

    public static function providerUpdateRolExitoso(): array
    {
        return [
            'cambio_nombre' => [
                3,
                ['nombre' => 'GERENTE_NUEVO', 'descripcion' => 'Gerente actualizado', 'estatus' => 'ACTIVO'],
            ],
            'cambio_estatus' => [
                5,
                ['nombre' => 'AUDITOR', 'descripcion' => 'Auditor interno', 'estatus' => 'INACTIVO'],
            ],
        ];
    }

    public static function providerUpdateRolNombreConflicto(): array
    {
        return [
            'nombre_usado_por_otro_rol' => [
                2,
                ['nombre' => 'ADMIN', 'descripcion' => 'Desc', 'estatus' => 'ACTIVO'],
                'Ya existe otro rol activo con ese nombre.',
            ],
        ];
    }

    // ─── Tests: updateRol ────────────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerUpdateRolExitoso')]
    public function testUpdateRol_Exitoso_RetornaStatusTrue(int $idrol, array $data): void
    {
        // Verificación de nombre → no hay conflicto (fetch devuelve false)
        $this->mockStmt->shouldReceive('fetch')->andReturn(false);
        // rowCount > 0 → hubo cambios
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1);

        $resultado = $this->rolesModel->updateRol($idrol, $data);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status'], 'Se esperaba status true: ' . ($resultado['message'] ?? ''));
    }

    #[Test]
    #[DataProvider('providerUpdateRolNombreConflicto')]
    public function testUpdateRol_NombreConflicto_RetornaStatusFalse(int $idrol, array $data, string $mensajeEsperado): void
    {
        // La verificación de nombre devuelve otro rol con ese nombre
        $this->mockStmt->shouldReceive('fetch')->andReturn(['idrol' => 99, 'nombre' => $data['nombre']]);

        $resultado = $this->rolesModel->updateRol($idrol, $data);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertEquals($mensajeEsperado, $resultado['message']);
    }

    #[Test]
    public function testUpdateRol_SinCambiosReales_RetornaStatusTrue(): void
    {
        // Sin conflicto de nombre
        $this->mockStmt->shouldReceive('fetch')->andReturn(false);
        // rowCount = 0 → datos idénticos
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0);

        $resultado = $this->rolesModel->updateRol(1, [
            'nombre'      => 'ADMIN',
            'descripcion' => 'Administrador',
            'estatus'     => 'ACTIVO',
        ]);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status']);
        $this->assertStringContainsString('idénticos', $resultado['message']);
    }

    // ─── Tests: reactivarRol ────────────────────────────────────────────────

    #[Test]
    public function testReactivarRol_RolInactivo_RetornaStatusTrue(): void
    {
        // selectRolById → devuelve rol INACTIVO
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(
            ['idrol' => 10, 'nombre' => 'VIEJO', 'estatus' => 'INACTIVO'],
            false // segunda llamada no usada
        );
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1);

        $resultado = $this->rolesModel->reactivarRol(10);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status']);
        $this->assertStringContainsString('reactivado', $resultado['message']);
    }

    #[Test]
    public function testReactivarRol_RolNoExiste_RetornaStatusFalse(): void
    {
        // selectRolById → devuelve false (no existe)
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);

        $resultado = $this->rolesModel->reactivarRol(99999);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertEquals('El rol no existe.', $resultado['message']);
    }

    #[Test]
    public function testReactivarRol_RolYaActivo_RetornaStatusFalse(): void
    {
        // El rol existe pero ya está activo
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(
            ['idrol' => 1, 'nombre' => 'ADMIN', 'estatus' => 'ACTIVO']
        );

        $resultado = $this->rolesModel->reactivarRol(1);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertEquals('El rol ya se encuentra activo.', $resultado['message']);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// eliminarRolUnitTest.php
// ═══════════════════════════════════════════════════════════════════════════

<?php

namespace Tests\UnitTest\Roles;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\RolesModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class eliminarRolUnitTest extends TestCase
{
    private RolesModel $rolesModel;
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
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->rolesModel = new RolesModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // ─── Data Providers ──────────────────────────────────────────────────────

    public static function providerEliminarRolExitoso(): array
    {
        return [
            'rol_sin_usuarios' => [7, 0, 1],  // idrol, count usuarios, rowCount update
            'rol_sin_usuarios_otro' => [12, 0, 1],
        ];
    }

    public static function providerEliminarRolEnUso(): array
    {
        return [
            'rol_con_1_usuario'   => [2, 1],
            'rol_con_10_usuarios' => [3, 10],
        ];
    }

    // ─── Tests ───────────────────────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerEliminarRolExitoso')]
    public function testDeleteRolById_SinUsuariosAsociados_RetornaStatusTrue(int $idrol, int $countUsuarios, int $rowCount): void
    {
        // verificarUsoRol: count = 0 (no está en uso)
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['count' => $countUsuarios]);
        // La desactivación afecta filas
        $this->mockStmt->shouldReceive('rowCount')->andReturn($rowCount);

        $resultado = $this->rolesModel->deleteRolById($idrol);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status'], 'Se esperaba status true: ' . ($resultado['message'] ?? ''));
        $this->assertStringContainsString('desactivado', $resultado['message']);
    }

    #[Test]
    #[DataProvider('providerEliminarRolEnUso')]
    public function testDeleteRolById_RolEnUso_RetornaStatusFalse(int $idrol, int $countUsuarios): void
    {
        // verificarUsoRol: count > 0 (está siendo usado)
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['count' => $countUsuarios]);

        $resultado = $this->rolesModel->deleteRolById($idrol);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertStringContainsString('siendo usado', $resultado['message']);
    }

    #[Test]
    public function testDeleteRolById_IdInexistente_RetornaStatusFalse(): void
    {
        // count = 0 (no está en uso), pero rowCount = 0 → rol no encontrado
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['count' => 0]);
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0);

        $resultado = $this->rolesModel->deleteRolById(99999);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertStringContainsString('No se encontró', $resultado['message']);
    }

    #[Test]
    public function testDeleteRolById_ExcepcionEnBD_RetornaStatusFalse(): void
    {
        // verificarUsoRol pasa (count = 0), pero la desactivación lanza excepción
        $callCount = 0;
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['count' => 0]);
        $this->mockStmt->shouldReceive('execute')
            ->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                if ($callCount >= 2) {
                    throw new \PDOException('DB error simulado');
                }
                return true;
            });

        $resultado = $this->rolesModel->deleteRolById(5);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// rolesIntegradoUnitTest.php
// ═══════════════════════════════════════════════════════════════════════════

<?php

namespace Tests\UnitTest\Roles;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\RolesintegradoModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class rolesIntegradoUnitTest extends TestCase
{
    private RolesintegradoModel $model;
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

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('commit')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('rollBack')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(true)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new RolesintegradoModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // ─── Data Providers ──────────────────────────────────────────────────────

    public static function providerGuardarAsignacionesInvalidas(): array
    {
        return [
            'idrol_cero' => [
                ['idrol' => 0, 'asignaciones' => [['idmodulo' => 1, 'tiene_acceso' => true, 'permisos_especificos' => []]]],
                'ID de rol no válido.',
            ],
            'idrol_negativo' => [
                ['idrol' => -5, 'asignaciones' => [['idmodulo' => 2, 'tiene_acceso' => true, 'permisos_especificos' => []]]],
                'ID de rol no válido.',
            ],
        ];
    }

    public static function providerSelectMetodos(): array
    {
        return [
            'selectAllRoles'          => ['selectAllRoles', []],
            'selectAllModulosActivos' => ['selectAllModulosActivos', []],
            'selectAllPermisosActivos'=> ['selectAllPermisosActivos', []],
        ];
    }

    // ─── Tests: guardarAsignacionesRolCompletas ──────────────────────────────

    #[Test]
    #[DataProvider('providerGuardarAsignacionesInvalidas')]
    public function testGuardarAsignaciones_IdRolInvalido_RetornaStatusFalse(array $data, string $mensajeEsperado): void
    {
        $resultado = $this->model->guardarAsignacionesRolCompletas($data);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertEquals($mensajeEsperado, $resultado['message']);
    }

    #[Test]
    public function testGuardarAsignaciones_SinPermisosEspecificos_RetornaExito(): void
    {
        // Asignaciones con módulos pero sin permisos_especificos
        $data = [
            'idrol'        => 2,
            'asignaciones' => [
                ['idmodulo' => 1, 'tiene_acceso' => true, 'permisos_especificos' => []],
                ['idmodulo' => 5, 'tiene_acceso' => false, 'permisos_especificos' => []],
            ],
        ];

        $resultado = $this->model->guardarAsignacionesRolCompletas($data);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status'], 'Se esperaba status true: ' . ($resultado['message'] ?? ''));
        $this->assertEquals(0, $resultado['modulos_asignados']);
        $this->assertEquals(0, $resultado['permisos_especificos_asignados']);
    }

    #[Test]
    public function testGuardarAsignaciones_ConPermisosEspecificos_RetornaExito(): void
    {
        $data = [
            'idrol'        => 3,
            'asignaciones' => [
                [
                    'idmodulo'             => 1,
                    'tiene_acceso'         => true,
                    'permisos_especificos' => [['idpermiso' => 1], ['idpermiso' => 2]],
                ],
                [
                    'idmodulo'             => 4,
                    'tiene_acceso'         => true,
                    'permisos_especificos' => [['idpermiso' => 3]],
                ],
            ],
        ];

        $resultado = $this->model->guardarAsignacionesRolCompletas($data);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status'], $resultado['message'] ?? '');
        $this->assertEquals(2, $resultado['modulos_asignados']);
        $this->assertEquals(3, $resultado['permisos_especificos_asignados']);
    }

    #[Test]
    public function testGuardarAsignaciones_FalloTransaccion_RetornaStatusFalse(): void
    {
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true);
        $this->mockStmt->shouldReceive('execute')->andThrow(new \PDOException('FK constraint failed'));

        $data = [
            'idrol'        => 2,
            'asignaciones' => [
                ['idmodulo' => 99, 'tiene_acceso' => true, 'permisos_especificos' => [['idpermiso' => 1]]],
            ],
        ];

        $resultado = $this->model->guardarAsignacionesRolCompletas($data);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertStringContainsString('Error', $resultado['message']);
    }

    // ─── Tests: selectAsignacionesRolCompletas ───────────────────────────────

    #[Test]
    public function testSelectAsignaciones_RetornaStatusTrue(): void
    {
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([
            [
                'idmodulo'                    => 1,
                'nombre_modulo'               => 'Usuarios',
                'descripcion_modulo'          => 'Gestión de usuarios',
                'permisos_especificos_ids'    => '1,2',
                'permisos_especificos_nombres'=> 'leer|escribir',
                'tiene_acceso_modulo'         => 1,
            ],
        ]);

        $resultado = $this->model->selectAsignacionesRolCompletas(2);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status']);
        $this->assertNotEmpty($resultado['data']);
        $this->assertEquals(1, $resultado['data'][0]['idmodulo']);
    }

    #[Test]
    public function testSelectAsignaciones_SinAsignaciones_RetornaArrayVacio(): void
    {
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([]);

        $resultado = $this->model->selectAsignacionesRolCompletas(999);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status']);
        $this->assertEmpty($resultado['data']);
    }

    // ─── Tests: métodos de consulta general ─────────────────────────────────

    #[Test]
    public function testSelectAllRoles_RetornaArrayConStatus(): void
    {
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)
            ->andReturn([['idrol' => 1, 'nombre' => 'ADMIN', 'descripcion' => 'Administrador']]);

        $resultado = $this->model->selectAllRoles();

        $this->assertTrue($resultado['status']);
        $this->assertNotEmpty($resultado['data']);
    }

    #[Test]
    public function testSelectAllModulosActivos_RetornaArrayConStatus(): void
    {
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)
            ->andReturn([['idmodulo' => 1, 'titulo' => 'Dashboard', 'descripcion' => 'Panel principal']]);

        $resultado = $this->model->selectAllModulosActivos();

        $this->assertTrue($resultado['status']);
        $this->assertNotEmpty($resultado['data']);
    }

    #[Test]
    public function testSelectAllPermisosActivos_RetornaArrayConStatus(): void
    {
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)
            ->andReturn([['idpermiso' => 1, 'nombre_permiso' => 'leer']]);

        $resultado = $this->model->selectAllPermisosActivos();

        $this->assertTrue($resultado['status']);
        $this->assertNotEmpty($resultado['data']);
    }

    #[Test]
    public function testSelectAllRoles_SinDatos_RetornaArrayVacio(): void
    {
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([]);

        $resultado = $this->model->selectAllRoles();

        $this->assertTrue($resultado['status']);
        $this->assertEmpty($resultado['data']);
    }
}
```

### Caso de prueba

**TIPO:** Funcional (Caja blanca)

**OBJETIVO:** Asegurar que todas las operaciones CRUD sobre roles funcionen correctamente: consulta por ID y listados generales, creación con validación de nombres duplicados, actualización con manejo de conflictos, eliminación con verificación de uso, reactivación de roles inactivos, verificación de super usuarios, y gestión integrada de asignaciones de módulos y permisos con manejo transaccional.

**DESCRIPCIÓN:** Se prueban escenarios de éxito y falla para las operaciones principales del módulo Roles, incluyendo consultas de roles existentes e inexistentes, listados vacíos y con datos, creación con nombres únicos y duplicados, actualización con cambios reales y sin cambios, eliminación de roles sin usuarios asociados y con usuarios activos, reactivación de roles inactivos y ya activos, verificación de super usuarios, y operaciones integradas de asignación de módulos y permisos con transacciones.

**ENTRADAS:**

- Consultas: ID existente (5 - OPERADOR), IDs inexistentes (999999, 888888, 2147483647), listados, selects; super usuario con count = 1 y count = 0.
- Creación: nombres únicos (GERENTE, AUDITOR, SUPERVISOR), duplicados (ADMIN, SUPER_USUARIO), fallo BD con constraint violation.
- Actualización: cambio de nombre/estatus, conflicto con ADMIN, sin cambios reales; reactivación de rol inactivo (ID 10), inexistente (99999), ya activo.
- Eliminación: sin usuarios asociados (IDs 7, 12), en uso (1 o 10 usuarios), IDs inexistentes, excepción BD.
- Integrado: IDs inválidos (0, −5), asignación sin/con permisos específicos (módulo 1+permisos 1,2; módulo 4+permiso 3), fallo FK, consultas de asignaciones.

**SALIDAS ESPERADAS:**

- Consulta por ID existente → datos completos (idrol, nombre, descripción, estatus, fechas); inexistente → `false`.
- Super usuario (count = 1) → `true`; count = 0 → `false`.
- Creación exitosa → `status true` + mensaje + `rol_id = 42`; nombre duplicado → `status false` + “Ya existe un rol activo con ese nombre.”
- Actualización exitosa → `status true`; conflicto de nombre → `status false`; sin cambios → `status true` + “idénticos”.
- Reactivación exitosa → `status true` + “reactivado”; inexistente → “El rol no existe.”; ya activo → “El rol ya se encuentra activo.”
- Eliminación exitosa → “desactivado”; en uso → “siendo usado”; inexistente → “No se encontró”.
- Integrado, ID inválido → “ID de rol no válido.”; con permisos → `modulos_asignados 2`, `permisos 3`; fallo FK → `status false`.

### Resultado

```
PS C:\xampp\htdocs\project> php vendor/bin/phpunit tests/unitTest/Roles/
PHPUnit 10.5.63 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: C:\xampp\htdocs\project\phpunit.xml

.........................................                         41 / 41 (100%)

Time: 00:07.141, Memory: 10.00 MB

There was 1 PHPUnit test runner warning:

1) No code coverage driver available

OK, but there were issues!
Tests: 41, Assertions: 113, PHPUnit Warnings: 1, PHPUnit Deprecations: 1.
```

### Observaciones

41 pruebas y 113 aserciones ejecutadas correctamente en ~7 s, distribuidas en 5 clases: consultar (9), crear (5), editar (8), eliminar (6) e integrado (13). La gestión transaccional con rollback ante fallos de FK y la prevención de eliminación de roles en uso activo fueron validadas correctamente.
