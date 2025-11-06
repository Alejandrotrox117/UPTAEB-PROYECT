<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../app/models/RolesIntegradoModel.php';
class RolesIntegradoTest extends TestCase
{
    private $rolesIntegradoModel;
    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }
    public function setUp(): void
    {
        $this->rolesIntegradoModel = new RolesIntegradoModel();
    }
    public function testGuardarYRecuperarAsignaciones()
    {
        $idrol = 2; 
        $datosParaGuardar = [
            'idrol' => $idrol,
            'asignaciones' => [
                [
                    'idmodulo' => 1,
                    'tiene_acceso' => true,
                    'permisos_especificos' => []
                ],
                [
                    'idmodulo' => 8,
                    'tiene_acceso' => true,
                    'permisos_especificos' => [
                        ['idpermiso' => 1], 
                        ['idpermiso' => 2]  
                    ]
                ],
                [
                    'idmodulo' => 11,
                    'tiene_acceso' => false,
                    'permisos_especificos' => []
                ]
            ]
        ];
        $resultadoGuardado = $this->rolesIntegradoModel->guardarAsignacionesRolCompletas($datosParaGuardar);
        $this->assertTrue($resultadoGuardado['status'], "El guardado de asignaciones falló: " . ($resultadoGuardado['message'] ?? ''));
        $resultadoRecuperado = $this->rolesIntegradoModel->selectAsignacionesRolCompletas($idrol);
        $this->assertTrue($resultadoRecuperado['status'], "La recuperación de asignaciones falló.");
        $asignacionesRecuperadas = $resultadoRecuperado['data'];
        $mapaAsignaciones = [];
        foreach ($asignacionesRecuperadas as $asignacion) {
            $mapaAsignaciones[$asignacion['idmodulo']] = $asignacion;
        }
    }
    public function testGuardarAsignacionesConRolInvalido()
    {
        $datosParaGuardar = [
            'idrol' => 0, 
            'asignaciones' => [
                [
                    'idmodulo' => 1,
                    'tiene_acceso' => true,
                    'permisos_especificos' => []
                ]
            ]
        ];
        $resultado = $this->rolesIntegradoModel->guardarAsignacionesRolCompletas($datosParaGuardar);
        $this->assertFalse($resultado['status'], "El sistema no debería permitir guardar asignaciones con un ID de rol inválido.");
        $this->assertEquals('ID de rol no válido.', $resultado['message']);
    }
    public function testGuardarAsignacionesConModuloInvalido()
    {
        $datosParaGuardar = [
            'idrol' => 2,
            'asignaciones' => [
                [
                    'idmodulo' => 99999, 
                    'tiene_acceso' => true,
                    'permisos_especificos' => []
                ]
            ]
        ];
        $resultado = $this->rolesIntegradoModel->guardarAsignacionesRolCompletas($datosParaGuardar);
        $this->assertFalse($resultado['status'], "El sistema no debería permitir guardar asignaciones con un ID de módulo inválido.");
        $this->assertStringContainsString(
            'El módulo con ID 99999 no existe',
            $resultado['message'],
            "El mensaje de error debería indicar que el módulo no existe."
        );
    }
}
?>
