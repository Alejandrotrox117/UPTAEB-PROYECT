<?php

namespace Tests\IntegrationTest\Roles;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\RolesModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class eliminarRolIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private RolesModel $rolesModel;
    private ?int $idRolPrueba = null;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->rolesModel = new RolesModel();

        // Crear rol temporal que no tiene usuarios asignados
        $resultado = $this->rolesModel->insertRol([
            'nombre'      => 'ROL_DEL_' . time(),
            'descripcion' => 'Rol temporal para eliminación',
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

    public static function providerIdsRolInexistente(): array
    {
        return [
            'id_muy_alto'  => [999999],
            'id_cero'      => [0],
        ];
    }

    // ─── Tests ───────────────────────────────────────────────────────────────

    #[Test]
    public function testDeleteRolById_SinUsuarios_RetornaStatusTrue(): void
    {
        if (!$this->idRolPrueba) {
            $this->markTestSkipped('No se pudo crear el rol de prueba.');
        }

        $resultado = $this->rolesModel->deleteRolById($this->idRolPrueba);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status'], 'Desactivación falló: ' . ($resultado['message'] ?? ''));
        $this->assertStringContainsString('desactivado', $resultado['message']);

        // Verificar que el rol ya no está activo
        $rolVerif = $this->rolesModel->selectRolById($this->idRolPrueba);
        if ($rolVerif) {
            $this->assertEquals('INACTIVO', $rolVerif['estatus']);
        }
    }

    #[Test]
    #[DataProvider('providerIdsRolInexistente')]
    public function testDeleteRolById_IdInexistente_RetornaStatusFalse(int $id): void
    {
        $resultado = $this->rolesModel->deleteRolById($id);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
    }

    #[Test]
    public function testDeleteRolById_DobleLlamada_SegundaRetornaStatusFalse(): void
    {
        if (!$this->idRolPrueba) {
            $this->markTestSkipped('No se pudo crear el rol de prueba.');
        }

        $primera = $this->rolesModel->deleteRolById($this->idRolPrueba);
        if (!$primera['status']) {
            $this->markTestSkipped('La primera desactivación falló: ' . ($primera['message'] ?? ''));
        }

        // Segunda llamada: ya está INACTIVO → rowCount = 0
        $segunda = $this->rolesModel->deleteRolById($this->idRolPrueba);

        $this->assertIsArray($segunda);
        $this->assertFalse($segunda['status']);
        $this->assertStringContainsString('No se encontró', $segunda['message']);
    }
}
