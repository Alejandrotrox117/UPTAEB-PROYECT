<?php

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
        // esSuperUsuario(0) → total = 0 → no es super usuario (filtrar ACTIVO)
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        // Consulta principal devuelve lista de usuarios sin personaId
        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn([
                [
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
                ],
            ]);

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
        // La primera llamada a execute lanza excepción
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
        // Llamada 1: esSuperUsuario(target)     → total = 0 (no es super)
        // Llamada 2: esUsuarioActualSuperUsuario → total = 0
        // Llamada 3: ejecutarBusquedaUsuarioPorId → datos del usuario
        $callCount = 0;
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturnUsing(function () use (&$callCount, $id) {
                $callCount++;
                if ($callCount <= 2) {
                    return ['total' => 0]; // No es super usuario
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
        // Llamadas 1-2: esSuperUsuario checks → total = 0
        // Llamada 3: ejecutarBusquedaUsuarioPorId → false (no encontrado)
        $callCount = 0;
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                if ($callCount <= 2) {
                    return ['total' => 0];
                }
                return false; // No existe
            });

        $resultado = $this->usuariosModel->selectUsuarioById(99999, 0);

        $this->assertFalse($resultado);
    }

    #[Test]
    public function testSelectUsuarioById_IntentarAccederSuperUsuario_RetornaFalse(): void
    {
        // Usuario objetivo ES super usuario (total > 0), sesión es usuario normal → debe retornar false
        $callCount = 0;
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                return ['total' => $callCount === 1 ? 1 : 0]; // Target ES superusuario, sesión no
            });

        $resultado = $this->usuariosModel->selectUsuarioById(1, 5);

        $this->assertFalse($resultado);
    }

    // ─── Tests: selectUsuarioByEmail ─────────────────────────────────────────

    #[Test]
    #[DataProvider('providerCorreosParaBusqueda')]
    public function testSelectUsuarioByEmail_CorreoExistente_RetornaArray(string $correo): void
    {
        // Verificación de correo retorna total = 1 → existe
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
        // Verificación de correo retorna total = 0 → no existe
        $this->mockStmt->shouldReceive('fetch')
            ->andReturn(['total' => 0]);

        $resultado = $this->usuariosModel->selectUsuarioByEmail('noexiste@example.com');

        $this->assertFalse($resultado);
    }
}
