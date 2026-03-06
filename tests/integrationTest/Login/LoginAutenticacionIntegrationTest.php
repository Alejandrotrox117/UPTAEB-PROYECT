<?php

namespace Tests\IntegrationTest\Login;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\LoginModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class LoginAutenticacionIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private LoginModel $loginModel;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->loginModel = new LoginModel();
    }

    protected function tearDown(): void
    {
        unset($this->loginModel);
    }

    // ─── Data Providers ──────────────────────────────────────────────────────

    public static function providerCredencialesInvalidas(): array
    {
        return [
            'email_inexistente'   => ['noexiste_xyz999@test.com', 'password123'],
            'password_incorrecto' => ['admin@gmail.com', 'wrong_password_xyz'],
            'email_vacio'         => ['', 'password123'],
            'password_vacio'      => ['admin@gmail.com', ''],
            'ambos_vacios'        => ['', ''],
            'usuario_inactivo'    => ['inactivo_xyz@test.com', 'pass123'],
        ];
    }

    public static function providerIdsInexistentes(): array
    {
        return [
            'id_muy_alto'  => [999999],
            'id_negativo'  => [-1],
        ];
    }

    // ─── Tests: login() ───────────────────────────────────────────────────────

    #[Test]
    public function testLogin_CredencialesValidas_RetornaArrayConClaves(): void
    {
        // Credenciales del usuario admin del sistema
        $resultado = $this->loginModel->login('admin@gmail.com', 'admin');

        if ($resultado === false) {
            $this->markTestSkipped('Usuario admin no configurado en la BD de prueba.');
            return;
        }

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('idusuario', $resultado);
        $this->assertArrayHasKey('correo', $resultado);
        $this->assertArrayHasKey('estatus', $resultado);
        $this->assertEquals('activo', $resultado['estatus']);
    }

    #[Test]
    #[DataProvider('providerCredencialesInvalidas')]
    public function testLogin_CredencialesInvalidas_RetornaFalse(string $email, string $pass): void
    {
        $resultado = $this->loginModel->login($email, $pass);

        $this->assertFalse($resultado);
    }

    // ─── Tests: sessionLogin() ────────────────────────────────────────────────

    #[Test]
    public function testSessionLogin_IdValido_RetornaArrayConRol(): void
    {
        // Intentar con el ID del admin (idusuario = 1)
        $resultado = $this->loginModel->sessionLogin(1);

        if ($resultado === false) {
            $this->markTestSkipped('Usuario con ID 1 no disponible o inactivo en BD de prueba.');
            return;
        }

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('idusuario', $resultado);
        $this->assertArrayHasKey('usuario', $resultado);
        $this->assertArrayHasKey('idrol', $resultado);
        $this->assertArrayHasKey('rol_nombre', $resultado);
        $this->assertArrayHasKey('correo', $resultado);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testSessionLogin_IdInexistente_RetornaFalse(int $id): void
    {
        $resultado = $this->loginModel->sessionLogin($id);

        $this->assertFalse($resultado);
    }
}
