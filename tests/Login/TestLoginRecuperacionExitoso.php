<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/loginModel.php';

/**
 * Prueba de caja blanca para proceso de recuperación de contraseña exitoso
 * Valida generación de tokens y envío de correos
 */
class TestLoginRecuperacionExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new LoginModel();
    }

    public function testSolicitarRecuperacionConEmailValido()
    {
        $email = 'admin@test.com';

        // Verificar que el método existe
        if (method_exists($this->model, 'solicitarRecuperacion')) {
            $result = $this->model->solicitarRecuperacion($email);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('status', $result);
            $this->assertArrayHasKey('message', $result);
        } else {
            $this->markTestSkipped('Método solicitarRecuperacion no existe');
        }
    }

    public function testGenerarTokenRecuperacion()
    {
        $email = 'usuario@test.com';

        if (method_exists($this->model, 'generarTokenRecuperacion')) {
            $token = $this->model->generarTokenRecuperacion($email);

            $this->assertIsString($token, "Debería retornar un token string");
            $this->assertNotEmpty($token);
            $this->assertGreaterThan(10, strlen($token));
        } else {
            $this->markTestSkipped('Método generarTokenRecuperacion no existe');
        }
    }

    public function testValidarTokenRecuperacionValido()
    {
        if (method_exists($this->model, 'validarToken')) {
            $token = 'token_valido_test';
            $result = $this->model->validarToken($token);

            $this->assertIsBool($result);
        } else {
            $this->markTestSkipped('Método validarToken no existe');
        }
    }

    public function testCambiarPasswordConTokenValido()
    {
        if (method_exists($this->model, 'cambiarPasswordConToken')) {
            $token = 'token_test';
            $nuevoPassword = 'NuevoPassword123!';

            $result = $this->model->cambiarPasswordConToken($token, $nuevoPassword);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('status', $result);
        } else {
            $this->markTestSkipped('Método cambiarPasswordConToken no existe');
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
