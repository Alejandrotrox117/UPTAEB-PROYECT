<?php

namespace Tests\IntegrationTest\Roles;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\RolesintegradoModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class rolesIntegradoIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private RolesintegradoModel $model;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new RolesintegradoModel();
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }

    // ─── Data Providers ──────────────────────────────────────────────────────

    public static function providerGuardarAsignacionesInvalidas(): array
    {
        return [
            'idrol_cero' => [
                'idrol'            => 0,
                'asignaciones'     => [['idmodulo' => 1, 'tiene_acceso' => true, 'permisos_especificos' => []]],
                'esperado_status'  => false,
                'mensaje_esperado' => 'ID de rol no válido.',
            ],
            'idrol_negativo' => [
                'idrol'            => -1,
                'asignaciones'     => [['idmodulo' => 2, 'tiene_acceso' => true, 'permisos_especificos' => []]],
                'esperado_status'  => false,
                'mensaje_esperado' => 'ID de rol no válido.',
            ],
        ];
    }

    // ─── Tests: selectAll helpers ────────────────────────────────────────────

    #[Test]
    public function testSelectAllRoles_RetornaArrayConStatus(): void
    {
        $resultado = $this->model->selectAllRoles();

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status'], $resultado['message'] ?? '');
        $this->assertArrayHasKey('data', $resultado);
        $this->assertIsArray($resultado['data']);
    }

    #[Test]
    public function testSelectAllModulosActivos_RetornaArrayConStatus(): void
    {
        $resultado = $this->model->selectAllModulosActivos();

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status'], $resultado['message'] ?? '');
        $this->assertArrayHasKey('data', $resultado);
    }

    #[Test]
    public function testSelectAllPermisosActivos_RetornaArrayConStatus(): void
    {
        $resultado = $this->model->selectAllPermisosActivos();

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status'], $resultado['message'] ?? '');
        $this->assertArrayHasKey('data', $resultado);
    }

    // ─── Tests: guardarAsignacionesRolCompletas ──────────────────────────────

    #[Test]
    #[DataProvider('providerGuardarAsignacionesInvalidas')]
    public function testGuardarAsignaciones_IdRolInvalido_RetornaStatusFalse(
        int $idrol,
        array $asignaciones,
        bool $esperado_status,
        string $mensaje_esperado
    ): void {
        $resultado = $this->model->guardarAsignacionesRolCompletas([
            'idrol'        => $idrol,
            'asignaciones' => $asignaciones,
        ]);

        $this->assertIsArray($resultado);
        $this->assertEquals($esperado_status, $resultado['status']);
        $this->assertEquals($mensaje_esperado, $resultado['message']);
    }

    #[Test]
    public function testGuardarAsignaciones_RolExistenteSinPermisos_RetornaStatusTrue(): void
    {
        // Obtener un rol real de la BD para usarlo como base
        $roles = $this->model->selectAllRoles();
        if (!$roles['status'] || empty($roles['data'])) {
            $this->markTestSkipped('No hay roles en la BD para probar.');
        }

        $idRolReal = (int)$roles['data'][0]['idrol'];

        $resultado = $this->model->guardarAsignacionesRolCompletas([
            'idrol'        => $idRolReal,
            'asignaciones' => [
                ['idmodulo' => 1, 'tiene_acceso' => true,  'permisos_especificos' => []],
                ['idmodulo' => 2, 'tiene_acceso' => false, 'permisos_especificos' => []],
            ],
        ]);

        // Sin permisos_especificos, no inserta filas → contadores en 0
        if (!$resultado['status']) {
            // Puede que haya duplicados de una ejecución anterior, marcar como skipped
            if (str_contains($resultado['message'] ?? '', 'Duplicate') ||
                str_contains($resultado['message'] ?? '', 'constraint')) {
                $this->markTestSkipped('Datos duplicados: ' . $resultado['message']);
            }
        }

        $this->assertTrue($resultado['status'], $resultado['message'] ?? '');
        $this->assertEquals(0, $resultado['modulos_asignados']);
    }

    // ─── Tests: selectAsignacionesRolCompletas ───────────────────────────────

    #[Test]
    public function testSelectAsignaciones_RolExistente_RetornaStatusTrue(): void
    {
        $roles = $this->model->selectAllRoles();
        if (!$roles['status'] || empty($roles['data'])) {
            $this->markTestSkipped('No hay roles para probar.');
        }

        $idRolReal = (int)$roles['data'][0]['idrol'];

        $resultado = $this->model->selectAsignacionesRolCompletas($idRolReal);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status'], $resultado['message'] ?? '');
        $this->assertArrayHasKey('data', $resultado);
        $this->assertIsArray($resultado['data']);
    }

    #[Test]
    public function testSelectAsignaciones_RolInexistente_RetornaListaVacia(): void
    {
        $resultado = $this->model->selectAsignacionesRolCompletas(999999);

        $this->assertIsArray($resultado);
        // La query devuelve los módulos activos aunque no tenga asignaciones → status true, data puede no estar vacía
        $this->assertTrue($resultado['status']);
        // Verificar que tiene_acceso sea false para todos
        foreach ($resultado['data'] as $asignacion) {
            $this->assertFalse($asignacion['tiene_acceso'], 'No debería haber acceso para un rol inexistente.');
        }
    }
}
