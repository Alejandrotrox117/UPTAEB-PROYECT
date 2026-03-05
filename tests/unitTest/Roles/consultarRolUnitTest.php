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
