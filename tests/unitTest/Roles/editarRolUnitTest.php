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
