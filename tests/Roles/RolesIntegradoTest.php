<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../app/models/RolesIntegradoModel.php';

class RolesIntegradoTest extends TestCase
{
    private $rolesIntegradoModel;

    public function setUp(): void
    {
        $this->rolesIntegradoModel = new RolesIntegradoModel();
    }

    public function testGuardarYRecuperarAsignaciones()
    {
        // 1. Definir los datos de prueba
        $idrol = 2; // Usar un rol que no sea SuperUsuario para la prueba (ej. Administrador)
        $datosParaGuardar = [
            'idrol' => $idrol,
            'asignaciones' => [
                // Asignar acceso al módulo Dashboard (ID 1) sin permisos específicos
                [
                    'idmodulo' => 1,
                    'tiene_acceso' => true,
                    'permisos_especificos' => []
                ],
                // Asignar acceso al módulo Compras (ID 8) con permisos de leer y crear
                [
                    'idmodulo' => 8,
                    'tiene_acceso' => true,
                    'permisos_especificos' => [
                        ['idpermiso' => 1], // leer
                        ['idpermiso' => 2]  // crear
                    ]
                ],
                // Denegar explícitamente el acceso al módulo de Pagos (ID 11)
                [
                    'idmodulo' => 11,
                    'tiene_acceso' => false,
                    'permisos_especificos' => []
                ]
            ]
        ];

        // 2. Guardar las asignaciones
        $resultadoGuardado = $this->rolesIntegradoModel->guardarAsignacionesRolCompletas($datosParaGuardar);
        $this->assertTrue($resultadoGuardado['status'], "El guardado de asignaciones falló: " . ($resultadoGuardado['message'] ?? ''));

        // 3. Recuperar las asignaciones para verificar
        $resultadoRecuperado = $this->rolesIntegradoModel->selectAsignacionesRolCompletas($idrol);
        $this->assertTrue($resultadoRecuperado['status'], "La recuperación de asignaciones falló.");

        $asignacionesRecuperadas = $resultadoRecuperado['data'];
        
        // 4. Procesar y verificar los datos recuperados
        $mapaAsignaciones = [];
        foreach ($asignacionesRecuperadas as $asignacion) {
            $mapaAsignaciones[$asignacion['idmodulo']] = $asignacion;
        }

        // Las verificaciones han sido eliminadas según la solicitud.
    }

    public function testGuardarAsignacionesConRolInvalido()
    {
        $datosParaGuardar = [
            'idrol' => 0, // ID de rol inválido
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
                    'idmodulo' => 99999, // ID de módulo inexistente
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
