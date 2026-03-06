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
class insertUsuarioUnitTest extends TestCase
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
        // Ambas verificaciones de duplicado → total = 0 (no existe)
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
        // Primera verificación (correo) → total = 1 → duplicado detectado, retorno temprano
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
        // Primera verificación (correo) pasa, segunda (nombre) detecta duplicado
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
        // Verificaciones pasan (total = 0), pero el INSERT lanza PDOException
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
        // Verificaciones pasan, INSERT ejecuta, pero lastInsertId retorna 0 (fallo al obtener ID)
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
