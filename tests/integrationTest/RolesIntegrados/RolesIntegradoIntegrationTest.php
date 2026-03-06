<?php
namespace Tests\IntegrationTest\Roles;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\RolesintegradoModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class RolesIntegradoIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private $model;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new RolesintegradoModel();
    }

    public static function guardarAsignacionesProvider(): array
    {
        return [
            'Rol Invalido' => [
                'data' => [
                    'idrol' => 0,
                    'asignaciones' => [
                        [
                            'idmodulo' => 1,
                            'tiene_acceso' => true,
                            'permisos_especificos' => [1]
                        ]
                    ]
                ],
                'expectedStatus' => false
            ],
            'Modulo Invalido' => [
                'data' => [
                    'idrol' => 1,
                    'asignaciones' => [
                        [
                            'idmodulo' => 999999,
                            'tiene_acceso' => true,
                            'permisos_especificos' => [1]
                        ]
                    ]
                ],
                'expectedStatus' => false // Va a fallar fk o similar
            ]
        ];
    }

    #[Test]
    public function testGuardarYRecuperarAsignaciones()
    {
        $idrol = 2; // Old test usa 2

        $datosParaGuardar = [
            'idrol' => $idrol,
            'asignaciones' => [
                [
                    'idmodulo' => 7,
                    'tiene_acceso' => true,
                    'permisos_especificos' => [1, 2]
                ]
            ]
        ];

        $resultadoGuardado = $this->model->guardarAsignacionesRolCompletas($datosParaGuardar);

        if (!$resultadoGuardado['status']) {
            if (
                strpos($resultadoGuardado['message'] ?? '', 'Duplicate') !== false ||
                strpos($resultadoGuardado['message'] ?? '', 'constraint') !== false
            ) {
                $this->markTestSkipped('Datos duplicados en la base de datos o constraint.');
            }
        }

        $this->assertTrue($resultadoGuardado['status'], "El guardado de asignaciones falló: " . ($resultadoGuardado['message'] ?? ''));

        $resultadoRecuperado = $this->model->selectAsignacionesRolCompletas($idrol);
        $this->assertTrue($resultadoRecuperado['status'], "La recuperación de asignaciones falló.");

        $encontrado = false;
        foreach ($resultadoRecuperado['data'] as $modulo) {
            if ($modulo['idmodulo'] == 7 && $modulo['tiene_acceso'] == true) {
                $encontrado = true;
                break;
            }
        }
        $this->assertTrue($encontrado, "No se encontro el modulo asignado.");
    }

    #[Test]
    #[DataProvider('guardarAsignacionesProvider')]
    public function testGuardarAsignaciones(array $data, bool $expectedStatus)
    {
        $resultado = $this->model->guardarAsignacionesRolCompletas($data);
        $this->assertEquals($expectedStatus, $resultado['status']);
    }
}
