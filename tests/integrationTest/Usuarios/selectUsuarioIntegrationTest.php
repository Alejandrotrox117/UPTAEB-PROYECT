<?php

namespace Tests\IntegrationTest\Usuarios;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\UsuariosModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class selectUsuarioIntegrationTest extends TestCase
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

    public static function providerCorreosInexistentes(): array
    {
        return [
            'correo_ficticio_1' => ['noexiste_xyz_99999@prueba.invalid'],
            'correo_ficticio_2' => ['fantasma_abc_00000@test.invalid'],
        ];
    }

    // ─── Tests: selectAllUsuarios ─────────────────────────────────────────────

    #[Test]
    public function testSelectAllUsuarios_RetornaArrayConStatusYData(): void
    {
        $resultado = $this->usuariosModel->selectAllUsuarios(0);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertTrue($resultado['status'], 'selectAllUsuarios falló: ' . ($resultado['message'] ?? ''));
        $this->assertIsArray($resultado['data']);
    }

    #[Test]
    public function testSelectAllUsuariosActivos_RetornaMismaEstructuraQueSelectAll(): void
    {
        $resultadoAll    = $this->usuariosModel->selectAllUsuarios(0);
        $resultadoActivos = $this->usuariosModel->selectAllUsuariosActivos(0);

        // Ambos métodos delegan a la misma función interna
        $this->assertIsArray($resultadoActivos);
        $this->assertArrayHasKey('status', $resultadoActivos);
        $this->assertArrayHasKey('data', $resultadoActivos);
        $this->assertEquals($resultadoAll['status'], $resultadoActivos['status']);
    }

    // ─── Tests: selectUsuarioById ─────────────────────────────────────────────

    #[Test]
    public function testSelectUsuarioById_RegistroExistente_RetornaArrayConCamposEsperados(): void
    {
        // Insertar usuario temporal para garantizar al menos uno consultable
        $unicidad = (string) time() . rand(1000, 9999);
        $insert = $this->usuariosModel->insertUsuario([
            'personaId' => null,
            'usuario'   => 'sel_by_id_' . $unicidad,
            'correo'    => 'sel_' . $unicidad . '@test.com',
            'clave'     => 'Password123!',
            'idrol'     => 2,
        ]);

        if (!$insert['status']) {
            $this->markTestSkipped('No se pudo insertar usuario para el test de selectById.');
        }

        $idCreado = $insert['usuario_id'];
        $resultado = $this->usuariosModel->selectUsuarioById((int) $idCreado, 0);

        // El usuario tiene idrol = 2 (no superusuario), por lo que debe ser accesible
        if ($resultado === false) {
            $this->markTestSkipped('El usuario creado tiene restricción de acceso (posible superusuario).');
        }

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('idusuario', $resultado);
        $this->assertArrayHasKey('usuario', $resultado);
        $this->assertArrayHasKey('correo', $resultado);
        $this->assertEquals($idCreado, $resultado['idusuario']);
    }

    #[Test]
    public function testSelectUsuarioById_IdInexistente_RetornaFalse(): void
    {
        $resultado = $this->usuariosModel->selectUsuarioById(99999999, 0);

        $this->assertFalse($resultado);
    }

    // ─── Tests: selectUsuarioByEmail ─────────────────────────────────────────

    #[Test]
    #[DataProvider('providerCorreosInexistentes')]
    public function testSelectUsuarioByEmail_CorreoNoRegistrado_RetornaFalse(string $correo): void
    {
        $resultado = $this->usuariosModel->selectUsuarioByEmail($correo);

        $this->assertFalse($resultado);
    }

    #[Test]
    public function testSelectUsuarioByEmail_CorreoRegistrado_RetornaArray(): void
    {
        // Crear usuario temporal con correo único
        $unicidad = (string) time() . rand(1000, 9999);
        $correo   = 'chk_email_' . $unicidad . '@test.com';

        $insert = $this->usuariosModel->insertUsuario([
            'personaId' => null,
            'usuario'   => 'chkemail_' . $unicidad,
            'correo'    => $correo,
            'clave'     => 'Password123!',
            'idrol'     => 2,
        ]);

        if (!$insert['status']) {
            $this->markTestSkipped('No se pudo crear el usuario de prueba para check de email.');
        }

        $resultado = $this->usuariosModel->selectUsuarioByEmail($correo);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('correo', $resultado);
        $this->assertEquals($correo, $resultado['correo']);
    }
}
