<?php

namespace Tests\IntegrationTest\Roles;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\RolesModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class editarRolIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private RolesModel $rolesModel;
    private ?int $idRolPrueba = null;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->rolesModel = new RolesModel();

        // Crear un rol temporal sobre el que se realizarán las ediciones
        $resultado = $this->rolesModel->insertRol([
            'nombre'      => 'ROL_EDIT_' . time(),
            'descripcion' => 'Rol temporal para edición',
            'estatus'     => 'ACTIVO',
        ]);
        if ($resultado['status']) {
            $this->idRolPrueba = (int)$resultado['rol_id'];
        }
    }

    protected function tearDown(): void
    {
        unset($this->rolesModel);
    }

    // ─── Data Providers ──────────────────────────────────────────────────────

    public static function providerEscenarioUpdateRol(): array
    {
        return [
            'cambio_descripcion' => [
                'descripcion'     => 'Descripción actualizada por integration test',
                'estatus'         => 'ACTIVO',
                'esperado_status' => true,
            ],
            'cambio_a_inactivo' => [
                'descripcion'     => 'Ahora inactivo',
                'estatus'         => 'INACTIVO',
                'esperado_status' => true,
            ],
        ];
    }

    // ─── Tests: updateRol ────────────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerEscenarioUpdateRol')]
    public function testUpdateRol_DatosValidos_RetornaStatusTrue(
        string $descripcion,
        string $estatus,
        bool $esperado_status
    ): void {
        if (!$this->idRolPrueba) {
            $this->markTestSkipped('No se pudo crear el rol de prueba.');
        }

        $rolActual = $this->rolesModel->selectRolById($this->idRolPrueba);
        if (!$rolActual) {
            $this->markTestSkipped('El rol de prueba no existe en la BD.');
        }

        $resultado = $this->rolesModel->updateRol($this->idRolPrueba, [
            'nombre'      => $rolActual['nombre'],
            'descripcion' => $descripcion,
            'estatus'     => $estatus,
        ]);

        $this->assertIsArray($resultado);
        $this->assertEquals($esperado_status, $resultado['status'], $resultado['message'] ?? '');
    }

    #[Test]
    public function testUpdateRol_NombreConflicto_RetornaStatusFalse(): void
    {
        if (!$this->idRolPrueba) {
            $this->markTestSkipped('No se pudo crear el rol de prueba.');
        }

        // Crear un segundo rol con nombre distinto
        $resultado2 = $this->rolesModel->insertRol([
            'nombre'      => 'ROL_CON_' . (time() + 1),
            'descripcion' => 'Segundo rol',
            'estatus'     => 'ACTIVO',
        ]);

        if (!$resultado2['status']) {
            $this->markTestSkipped('No se pudo crear el segundo rol de prueba.');
        }

        $rolConflicto = $this->rolesModel->selectRolById((int)$resultado2['rol_id']);

        // Intentar renombrar el primer rol con el nombre del segundo
        $resultado = $this->rolesModel->updateRol($this->idRolPrueba, [
            'nombre'      => $rolConflicto['nombre'],
            'descripcion' => 'Conflicto',
            'estatus'     => 'ACTIVO',
        ]);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertStringContainsString('Ya existe', $resultado['message']);
    }

    // ─── Tests: reactivarRol ────────────────────────────────────────────────

    #[Test]
    public function testReactivarRol_RolInactivo_RetornaStatusTrue(): void
    {
        if (!$this->idRolPrueba) {
            $this->markTestSkipped('No se pudo crear el rol de prueba.');
        }

        // Primero desactivamos el rol (sin usuarios)
        $eliminar = $this->rolesModel->deleteRolById($this->idRolPrueba);
        if (!$eliminar['status']) {
            $this->markTestSkipped('No se pudo desactivar el rol: ' . ($eliminar['message'] ?? ''));
        }

        $resultado = $this->rolesModel->reactivarRol($this->idRolPrueba);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status'], $resultado['message'] ?? '');
        $this->assertStringContainsString('reactivado', $resultado['message']);
    }

    #[Test]
    public function testReactivarRol_RolInexistente_RetornaStatusFalse(): void
    {
        $resultado = $this->rolesModel->reactivarRol(999999);

        $this->assertFalse($resultado['status']);
        $this->assertEquals('El rol no existe.', $resultado['message']);
    }

    #[Test]
    public function testReactivarRol_RolYaActivo_RetornaStatusFalse(): void
    {
        if (!$this->idRolPrueba) {
            $this->markTestSkipped('No se pudo crear el rol de prueba.');
        }

        $resultado = $this->rolesModel->reactivarRol($this->idRolPrueba);

        $this->assertFalse($resultado['status']);
        $this->assertEquals('El rol ya se encuentra activo.', $resultado['message']);
    }
}
