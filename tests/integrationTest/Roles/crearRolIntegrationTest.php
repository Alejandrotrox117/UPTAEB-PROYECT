<?php

namespace Tests\IntegrationTest\Roles;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\RolesModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class crearRolIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private RolesModel $rolesModel;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->rolesModel = new RolesModel();
    }

    protected function tearDown(): void
    {
        unset($this->rolesModel);
    }

    // ─── Data Providers ──────────────────────────────────────────────────────

    public static function providerCasosInsertRol(): array
    {
        return [
            'nombre_vacio_falla' => [
                'nombre'          => '',
                'descripcion'     => 'Sin nombre',
                'estatus'         => 'ACTIVO',
                'esperado_status' => false,
                'mensaje_parcial' => '',   // La validación proviene de: nombre vacío → la BD rechaza
            ],
            'datos_completos_exito' => [
                'nombre'          => 'ROL_IT_' . time(),
                'descripcion'     => 'Rol creado en integration test',
                'estatus'         => 'ACTIVO',
                'esperado_status' => true,
                'mensaje_parcial' => 'exitosamente',
            ],
        ];
    }

    // ─── Tests ───────────────────────────────────────────────────────────────

    #[Test]
    public function testInsertRol_DatosCompletos_RetornaStatusTrue(): void
    {
        $data = [
            'nombre'      => 'ROL_INTEG_' . time(),
            'descripcion' => 'Rol creado por integration test',
            'estatus'     => 'ACTIVO',
        ];

        $resultado = $this->rolesModel->insertRol($data);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status'], 'Inserción falló: ' . ($resultado['message'] ?? ''));
        $this->assertArrayHasKey('rol_id', $resultado);
        $this->assertGreaterThan(0, $resultado['rol_id']);
    }

    #[Test]
    public function testInsertRol_NombreDuplicado_RetornaStatusFalse(): void
    {
        $nombre = 'ROL_DUP_' . time();
        $data   = ['nombre' => $nombre, 'descripcion' => 'Primera inserción', 'estatus' => 'ACTIVO'];

        $resultado1 = $this->rolesModel->insertRol($data);

        if (!$resultado1['status']) {
            $this->markTestSkipped('No se pudo insertar el rol base: ' . ($resultado1['message'] ?? ''));
        }

        $resultado2 = $this->rolesModel->insertRol($data);

        $this->assertIsArray($resultado2);
        $this->assertFalse($resultado2['status']);
        $this->assertStringContainsString('Ya existe', $resultado2['message']);
    }
}
