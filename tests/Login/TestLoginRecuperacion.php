<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../app/models/loginModel.php';
class TestLoginRecuperacion extends TestCase
{
    private $model;
    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "\n[MODEL MESSAGE] " . $msg . "\n");
    }
    protected function setUp(): void
    {
        $this->model = new LoginModel();
    }
    public function testGetUsuarioEmailExiste()
    {
        $email = 'admin@test.com';
        $result = $this->model->getUsuarioEmail($email);
        if ($result === false) {
            $this->markTestSkipped('Usuario de prueba no existe en la BD');
            return;
        }
        $this->assertIsArray($result);
        $this->assertArrayHasKey('idusuario', $result);
        $this->assertArrayHasKey('correo', $result);
    }
    public function testGetTokenUserByTokenInvalido()
    {
        $token = 'token_aleatorio_inexistente_'.uniqid();
        $result = $this->model->getTokenUserByToken($token);
        $this->assertFalse($result);
        $this->showMessage("Validación correcta: Token inválido retorna false");
    }
    public function testGetUsuarioEmailNoExiste()
    {
        $email = 'noexiste_'.uniqid().'@test.com';
        $result = $this->model->getUsuarioEmail($email);
        $this->assertFalse($result);
        $this->showMessage("Validación correcta: Email inexistente retorna false");
    }
    public function testUpdatePasswordConUsuarioInvalido()
    {
        $result = $this->model->updatePassword(0, 'hash_de_prueba');
        $this->assertFalse($result);
        $this->showMessage("Validación correcta: Update password con usuario inválido retorna false");
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
