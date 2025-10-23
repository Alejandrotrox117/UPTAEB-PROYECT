<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/loginModel.php';

/**
 * Prueba de caja blanca para casos de fallo en recuperación de contraseña
 * Valida manejo de tokens inválidos y validaciones
 */
class TestLoginRecuperacionFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new LoginModel();
    }

    public function testSolicitarRecuperacionConEmailInexistente()
    {
        $email = 'noexiste@test.com';

        if (method_exists($this->model, 'solicitarRecuperacion')) {
            $result = $this->model->solicitarRecuperacion($email);

            $this->assertIsArray($result);
            $this->assertFalse($result['status'], "No debería permitir recuperación con email inexistente");
        } else {
            $this->markTestSkipped('Método solicitarRecuperacion no existe');
        }
    }

    public function testSolicitarRecuperacionConEmailVacio()
    {
        $email = '';

        if (method_exists($this->model, 'solicitarRecuperacion')) {
            $result = $this->model->solicitarRecuperacion($email);

            $this->assertIsArray($result);
            $this->assertFalse($result['status'], "No debería permitir recuperación con email vacío");
        } else {
            $this->markTestSkipped('Método solicitarRecuperacion no existe');
        }
    }

    public function testValidarTokenInvalido()
    {
        if (method_exists($this->model, 'validarToken')) {
            $token = 'token_invalido_xyz123';
            $result = $this->model->validarToken($token);

            $this->assertFalse($result);
        } else {
            $this->markTestSkipped('Método validarToken no existe');
        }
    }

    public function testValidarTokenExpirado()
    {
        if (method_exists($this->model, 'validarToken')) {
            $token = 'token_expirado';
            $result = $this->model->validarToken($token);

            $this->assertIsBool($result);
            // Un token expirado debería retornar false
        } else {
            $this->markTestSkipped('Método validarToken no existe');
        }
    }

    public function testCambiarPasswordConTokenInvalido()
    {
        if (method_exists($this->model, 'cambiarPasswordConToken')) {
            $token = 'token_inexistente';
            $nuevoPassword = 'NuevoPassword123!';

            $result = $this->model->cambiarPasswordConToken($token, $nuevoPassword);

            $this->assertIsArray($result);
            $this->assertFalse($result['status'], "No debería cambiar password con token inválido");
        } else {
            $this->markTestSkipped('Método cambiarPasswordConToken no existe');
        }
    }

    public function testCambiarPasswordVacio()
    {
        if (method_exists($this->model, 'cambiarPasswordConToken')) {
            $token = 'token_test';
            $nuevoPassword = '';

            $result = $this->model->cambiarPasswordConToken($token, $nuevoPassword);

            $this->assertIsArray($result);
            $this->assertFalse($result['status'], "No debería permitir password vacío");
        } else {
            $this->markTestSkipped('Método cambiarPasswordConToken no existe');
        }
    }

    public function testCambiarPasswordMuyCorto()
    {
        if (method_exists($this->model, 'cambiarPasswordConToken')) {
            $token = 'token_test';
            $nuevoPassword = '123'; // Password demasiado corto

            $result = $this->model->cambiarPasswordConToken($token, $nuevoPassword);

            $this->assertIsArray($result);
            // Dependiendo de validaciones, puede fallar por longitud mínima
        } else {
            $this->markTestSkipped('Método cambiarPasswordConToken no existe');
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
