<?php

namespace Tests\UnitTest\Roles;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\RolesintegradoModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
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
