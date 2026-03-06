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
class deleteUsuarioUnitTest extends TestCase
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
        // esSuperUsuario → total = 0 (no es super, se puede eliminar)
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        // UPDATE SET estatus = 'INACTIVO' afecta 1 fila
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1);

        $resultado = $this->usuariosModel->deleteUsuarioById($id, 0);

        $this->assertTrue($resultado);
    }

    #[Test]
    public function testDeleteUsuarioById_SuperUsuario_RetornaFalse(): void
    {
        // esSuperUsuario → total = 1 → es super usuario, no se puede eliminar
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 1]);

        $resultado = $this->usuariosModel->deleteUsuarioById(1, 5);

        $this->assertFalse($resultado);
    }

    #[Test]
    public function testDeleteUsuarioById_UsuarioInexistente_RetornaFalse(): void
    {
        // esSuperUsuario → total = 0 (no es super), pero UPDATE no afecta filas
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
        // esSuperUsuario pasa (total = 0), pero el UPDATE lanza excepción
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
