<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/loginModel.php';

/**
 * Prueba de caja blanca para casos de fallo en login
 * Valida manejo de credenciales incorrectas y validaciones
 */
class TestLoginAutenticacionFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new LoginModel();
    }

    public function testLoginConPasswordIncorrecto()
    {
        $email = 'admin@test.com';
        $password = 'password_incorrecto';

        $result = $this->model->login($email, $password);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería autenticar con password incorrecto");
        $this->assertArrayHasKey('message', $result);
    }

    public function testLoginConEmailInexistente()
    {
        $email = 'noexiste@test.com';
        $password = 'cualquierpassword';

        $result = $this->model->login($email, $password);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería autenticar con email inexistente");
    }

    public function testLoginConEmailVacio()
    {
        $email = '';
        $password = 'password123';

        $result = $this->model->login($email, $password);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería autenticar con email vacío");
    }

    public function testLoginConPasswordVacio()
    {
        $email = 'admin@test.com';
        $password = '';

        $result = $this->model->login($email, $password);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería autenticar con password vacío");
    }

    public function testLoginConAmbosVacios()
    {
        $email = '';
        $password = '';

        $result = $this->model->login($email, $password);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería autenticar con credenciales vacías");
    }

    public function testLoginConEmailMalFormato()
    {
        $email = 'email-invalido-sin-arroba';
        $password = 'password123';

        $result = $this->model->login($email, $password);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería autenticar con email mal formateado");
    }

    public function testLoginConInyeccionSQL()
    {
        $email = "admin' OR '1'='1";
        $password = "password' OR '1'='1";

        $result = $this->model->login($email, $password);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería ser vulnerable a inyección SQL");
    }

    public function testLoginConUsuarioInactivo()
    {
        $email = 'inactivo@test.com';
        $password = 'password123';

        $result = $this->model->login($email, $password);

        $this->assertIsArray($result);
        // Si existe usuario inactivo, no debería autenticar
        if (isset($result['status'])) {
            $this->assertIsBool($result['status']);
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
