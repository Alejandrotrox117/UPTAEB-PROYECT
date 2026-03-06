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
