<?php

namespace Tests\IntegrationTest\Roles;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\RolesModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class consultarRolIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private RolesModel $rolesModel;
    private ?int $idRolPrueba = null;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->rolesModel = new RolesModel();

        // Crear un rol temporal para las consultas
        $resultado = $this->rolesModel->insertRol([
            'nombre'      => 'ROL_CONS_' . time(),
            'descripcion' => 'Rol para consultas en integration test',
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

    public static function providerIdsInexistentes(): array
    {
        return [
            'id_muy_alto'    => [999999],
            'id_otro_prueba' => [888888],
        ];
    }

    // ─── Tests ───────────────────────────────────────────────────────────────

    #[Test]
    public function testSelectRolById_RolExistente_RetornaDatos(): void
    {
        if (!$this->idRolPrueba) {
            $this->markTestSkipped('No se pudo crear el rol de prueba.');
        }

        $resultado = $this->rolesModel->selectRolById($this->idRolPrueba);

        $this->assertIsArray($resultado);
        $this->assertEquals($this->idRolPrueba, $resultado['idrol']);
        $this->assertArrayHasKey('nombre', $resultado);
        $this->assertArrayHasKey('estatus', $resultado);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testSelectRolById_IdInexistente_RetornaFalse(int $id): void
    {
        $resultado = $this->rolesModel->selectRolById($id);

        $this->assertFalse($resultado);
    }

    #[Test]
    public function testSelectAllRoles_RetornaListaConStatus(): void
    {
        $resultado = $this->rolesModel->selectAllRoles(1);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertTrue($resultado['status']);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertIsArray($resultado['data']);
    }

    #[Test]
    public function testSelectAllRolesForSelect_RetornaListaSimplificada(): void
    {
        $resultado = $this->rolesModel->selectAllRolesForSelect();

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status']);
        $this->assertArrayHasKey('data', $resultado);

        if (!empty($resultado['data'])) {
            $primerRol = $resultado['data'][0];
            $this->assertArrayHasKey('idrol', $primerRol);
            $this->assertArrayHasKey('nombre', $primerRol);
        }
    }

    #[Test]
    public function testVerificarEsSuperUsuario_RetornaBooleano(): void
    {
        $resultado = $this->rolesModel->verificarEsSuperUsuario(1);

        $this->assertIsBool($resultado);
    }

    #[Test]
    public function testVerificarEsSuperUsuario_IdInexistente_RetornaFalse(): void
    {
        $resultado = $this->rolesModel->verificarEsSuperUsuario(999999);

        $this->assertFalse($resultado);
    }
}
