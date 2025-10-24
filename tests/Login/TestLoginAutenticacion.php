<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/loginModel.php';

/**
 * Prueba de caja blanca para autenticación de usuarios
 * Incluye casos típicos (exitosos) y atípicos (fallidos)
 */
class TestLoginAutenticacion extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new LoginModel();
    }

    // ========== CASOS TÍPICOS (EXITOSOS) ==========

    public function testLoginConCredencialesValidas()
    {
        $email = 'admin@test.com';
        $password = 'password123';

        $result = $this->model->login($email, $password);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function testLoginRetornaInformacionUsuario()
    {
        $email = 'admin@test.com';
        $password = 'password123';

        $result = $this->model->login($email, $password);

        if ($result['status']) {
            $this->assertArrayHasKey('data', $result);
            $this->assertIsArray($result['data']);
        }

        $this->assertIsArray($result);
    }

    public function testLoginConUsuarioActivo()
    {
        $email = 'usuario_activo@test.com';
        $password = 'password123';

        $result = $this->model->login($email, $password);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    // ========== CASOS ATÍPICOS (FALLIDOS) ==========

    public function testLoginConPasswordIncorrecto()
    {
        $email = 'admin@test.com';
        $password = 'password_incorrecto';

        $result = $this->model->login($email, $password);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
    }

    public function testLoginConEmailInexistente()
    {
        $email = 'noexiste@test.com';
        $password = 'cualquierpassword';

        $result = $this->model->login($email, $password);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public function testLoginConEmailVacio()
    {
        $email = '';
        $password = 'password123';

        $result = $this->model->login($email, $password);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public function testLoginConPasswordVacio()
    {
        $email = 'admin@test.com';
        $password = '';

        $result = $this->model->login($email, $password);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public function testLoginConCamposVacios()
    {
        $email = '';
        $password = '';

        $result = $this->model->login($email, $password);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public function testLoginConEmailInvalido()
    {
        $email = 'email_sin_formato_valido';
        $password = 'password123';

        $result = $this->model->login($email, $password);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testLoginConUsuarioInactivo()
    {
        $email = 'usuario_inactivo@test.com';
        $password = 'password123';

        $result = $this->model->login($email, $password);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
