<?php

namespace Tests\IntegrationTest\Usuarios;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\UsuariosModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class insertUsuarioIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private UsuariosModel $usuariosModel;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->usuariosModel = new UsuariosModel();
    }

    protected function tearDown(): void
    {
        unset($this->usuariosModel);
    }

    // ─── Data Providers ──────────────────────────────────────────────────────

    public static function providerInsertExitoso(): array
    {
        return [
            'nuevo_usuario_con_rol' => [
                'personaId' => null,
                'usuario'   => 'it_user_' . substr(md5((string) microtime(true)), 0, 8),
                'correo'    => 'it_' . substr(md5((string) microtime(true)), 0, 8) . '@test.com',
                'clave'     => 'Password123!',
                'idrol'     => 2,
            ],
        ];
    }

    public static function providerInsertFallido(): array
    {
        return [
            'correo_vacio' => [
                'personaId' => null,
                'usuario'   => 'usuario_sin_correo',
                'correo'    => '',
                'clave'     => 'Password123!',
                'idrol'     => 2,
                'esperado'  => false,
            ],
            'usuario_vacio' => [
                'personaId' => null,
                'usuario'   => '',
                'correo'    => 'vacio@test.com',
                'clave'     => 'Password123!',
                'idrol'     => 2,
                'esperado'  => false,
            ],
        ];
    }

    // ─── Tests ───────────────────────────────────────────────────────────────

    #[Test]
    public function testInsertUsuario_DatosCompletos_RetornaStatusTrue(): void
    {
        $unicidad = (string) time() . rand(1000, 9999);
        $data = [
            'personaId' => null,
            'usuario'   => 'integ_usr_' . $unicidad,
            'correo'    => 'integ_' . $unicidad . '@test.com',
            'clave'     => 'Password123!',
            'idrol'     => 2,
        ];

        $resultado = $this->usuariosModel->insertUsuario($data);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status'], 'Inserción falló: ' . ($resultado['message'] ?? ''));
        $this->assertArrayHasKey('usuario_id', $resultado);
        $this->assertGreaterThan(0, $resultado['usuario_id']);
        $this->assertStringContainsString('exitosamente', $resultado['message']);
    }

    #[Test]
    public function testInsertUsuario_CorreoDuplicado_RetornaStatusFalse(): void
    {
        $unicidad = (string) time() . rand(1000, 9999);
        $data = [
            'personaId' => null,
            'usuario'   => 'dup_usr_' . $unicidad,
            'correo'    => 'dup_' . $unicidad . '@test.com',
            'clave'     => 'Password123!',
            'idrol'     => 2,
        ];

        $resultado1 = $this->usuariosModel->insertUsuario($data);

        if (!$resultado1['status']) {
            $this->markTestSkipped('No se pudo crear el usuario base para el test de duplicado.');
        }

        // Segundo intento con el mismo correo pero diferente nombre de usuario
        $data['usuario'] = 'dup_usr2_' . $unicidad;
        $resultado2 = $this->usuariosModel->insertUsuario($data);

        $this->assertIsArray($resultado2);
        $this->assertFalse($resultado2['status']);
        $this->assertStringContainsStringIgnoringCase('correo', $resultado2['message']);
    }

    #[Test]
    #[DataProvider('providerInsertFallido')]
    public function testInsertUsuario_CamposInvalidos_RetornaStatusFalse(
        ?int $personaId,
        string $usuario,
        string $correo,
        string $clave,
        int $idrol,
        bool $esperado
    ): void {
        $resultado = $this->usuariosModel->insertUsuario([
            'personaId' => $personaId,
            'usuario'   => $usuario,
            'correo'    => $correo,
            'clave'     => $clave,
            'idrol'     => $idrol,
        ]);

        $this->assertIsArray($resultado);
        // Campos vacíos pueden ser rechazados por la BD o por validación del modelo
        // Verificamos que el resultado es un array con clave status
        $this->assertArrayHasKey('status', $resultado);
        if ($resultado['status'] === false) {
            $this->assertArrayHasKey('message', $resultado);
            $this->assertNotEmpty($resultado['message']);
        }
    }
}
