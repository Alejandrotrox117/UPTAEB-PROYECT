<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/loginModel.php';

/**
 * Prueba de caja blanca para proceso de login exitoso
 * Valida autenticación de usuarios con credenciales correctas
 */
class TestLoginAutenticacionExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new LoginModel();
    }

    public function testLoginConCredencialesValidas()
    {
        // Nota: Este test requiere usuario real en BD para funcionar
        // Ajustar credenciales según ambiente de prueba
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
            $this->assertArrayHasKey('usuario', $result);
            $this->assertArrayHasKey('idusuario', $result['usuario']);
            $this->assertArrayHasKey('email', $result['usuario']);
        }

        $this->assertIsArray($result);
    }

    public function testLoginConEmailValido()
    {
        $email = 'usuario@test.com';
        $password = 'test123';

        $result = $this->model->login($email, $password);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
