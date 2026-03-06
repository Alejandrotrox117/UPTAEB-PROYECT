<?php
namespace Tests\IntegrationTest\Roles;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\RolesModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class RolesIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private $model;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new RolesModel();
    }

    public static function insertRolProvider(): array
    {
        return [
            'Exito' => [
                'data' => [
                    'nombre' => 'ROL_TEST_' . time() . rand(1000, 9999),
                    'descripcion' => 'Rol de prueba para testing',
                    'estatus' => 'ACTIVO'
                ],
                'expectedStatus' => true
            ]
        ];
    }

    #[Test]
    public function testInsertRolDuplicado()
    {
        $nombreUnico = 'ROL_DUPLICADO_' . time() . rand(1000, 9999);
        $data = [
            'nombre' => $nombreUnico,
            'descripcion' => 'Primera inserción',
            'estatus' => 'ACTIVO'
        ];

        $result1 = $this->model->insertRol($data);
        $this->assertTrue($result1['status']);

        $result2 = $this->model->insertRol($data);
        $this->assertFalse($result2['status']);
        $this->assertEquals('Ya existe un rol activo con ese nombre.', $result2['message']);
    }

    #[Test]
    #[DataProvider('insertRolProvider')]
    public function testInsertRolFlow(array $data, bool $expectedStatus)
    {
        // Enforce the unique constraint properly manually for the test
        if (empty($data['nombre'])) {
            $data['nombre'] = 'TEMPORAL_' . time() . rand(1000, 9999);
        }

        $result = $this->model->insertRol($data);
        if ($expectedStatus) {
            $this->assertTrue($result['status'], $result['message']);
            $this->assertArrayHasKey('rol_id', $result);
            $this->assertGreaterThan(0, $result['rol_id']);

            // Cleanup
            $this->model->deleteRolById($result['rol_id']);
        } else {
            $this->assertFalse($result['status']);
        }
    }

    #[Test]
    public function testSelectRolByIdExistente()
    {
        // Add a role to make sure it exists
        $nombreUnico = 'ROL_SELECT_' . time() . rand(1000, 9999);
        $data = [
            'nombre' => $nombreUnico,
            'descripcion' => 'Prueba',
            'estatus' => 'ACTIVO'
        ];
        $insertResult = $this->model->insertRol($data);
        $idPrueba = $insertResult['rol_id'];

        $rol = $this->model->selectRolById($idPrueba);
        $this->assertIsArray($rol);
        $this->assertEquals($idPrueba, $rol['idrol']);
    }

    #[Test]
    public function testSelectRolByIdInexistente()
    {
        $rol = $this->model->selectRolById(999999);
        $this->assertFalse($rol);
    }
}
