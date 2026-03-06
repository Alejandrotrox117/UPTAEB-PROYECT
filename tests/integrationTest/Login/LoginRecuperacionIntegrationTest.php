<?php

namespace Tests\IntegrationTest\Login;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\LoginModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class LoginRecuperacionIntegrationTest extends TestCase
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

    public static function providerEmailsInexistentes(): array
    {
        return [
            'email_no_registrado' => ['noexiste_xyz_' . time() . '@test.com'],
            'email_formato_raro'  => ['usuario@dominio_inexistente_xyz.com'],
        ];
    }

    public static function providerTokensInvalidos(): array
    {
        return [
            'token_completamente_invalido' => ['token_invalido_' . bin2hex(random_bytes(8))],
            'token_string_vacio'           => [''],
        ];
    }

    public static function providerIdsConHashInvalidos(): array
    {
        return [
            'id_cero'        => [0, 'hash_prueba_123'],
            'id_inexistente' => [999999, 'hash_prueba_456'],
        ];
    }

    // ─── Tests: getUsuarioEmail() ─────────────────────────────────────────────

    #[Test]
    public function testGetUsuarioEmail_EmailRegistrado_RetornaArray(): void
    {
        $resultado = $this->loginModel->getUsuarioEmail('admin@gmail.com');

        if ($resultado === false) {
            $this->markTestSkipped('Usuario admin@gmail.com no existe en la BD de prueba.');
            return;
        }

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('idusuario', $resultado);
        $this->assertArrayHasKey('correo', $resultado);
        $this->assertArrayHasKey('usuario', $resultado);
        $this->assertArrayHasKey('estatus', $resultado);
    }

    #[Test]
    #[DataProvider('providerEmailsInexistentes')]
    public function testGetUsuarioEmail_EmailInexistente_RetornaFalse(string $email): void
    {
        $resultado = $this->loginModel->getUsuarioEmail($email);

        $this->assertFalse($resultado);
    }

    // ─── Tests: getTokenUserByToken() ────────────────────────────────────────

    #[Test]
    #[DataProvider('providerTokensInvalidos')]
    public function testGetTokenUserByToken_TokenInvalido_RetornaFalse(string $token): void
    {
        $resultado = $this->loginModel->getTokenUserByToken($token);

        $this->assertFalse($resultado);
    }

    // ─── Tests: updatePassword() ─────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerIdsConHashInvalidos')]
    public function testUpdatePassword_IdInexistente_RetornaFalse(int $id, string $hash): void
    {
        $resultado = $this->loginModel->updatePassword($id, $hash);

        $this->assertFalse($resultado);
    }

    // ─── Tests: setTokenUser() / getTokenUser() ───────────────────────────────

    #[Test]
    public function testSetTokenUser_IdInexistente_RetornaFalse(): void
    {
        $resultado = $this->loginModel->setTokenUser(999999, bin2hex(random_bytes(16)));

        $this->assertFalse($resultado);
    }

    #[Test]
    public function testGetTokenUser_DatosCombinacionInvalida_RetornaFalse(): void
    {
        // Combinación de email y token que no debe existir en BD
        $resultado = $this->loginModel->getTokenUser(
            'noexiste_xyz@test.com',
            'token_invalido_' . bin2hex(random_bytes(8))
        );

        $this->assertFalse($resultado);
    }

    // ─── Tests: deleteToken() ────────────────────────────────────────────────

    #[Test]
    public function testDeleteToken_TokenInexistente_RetornaFalse(): void
    {
        // Un token que definitivamente no existe en la BD
        $resultado = $this->loginModel->deleteToken('token_que_no_existe_' . bin2hex(random_bytes(8)));

        $this->assertFalse($resultado);
    }
}
