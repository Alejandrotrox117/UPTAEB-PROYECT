<?php

namespace Tests\UnitTest\Roles;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\RolesModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
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
