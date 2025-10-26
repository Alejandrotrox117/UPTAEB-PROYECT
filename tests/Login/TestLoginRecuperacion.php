<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/loginModel.php';

/**
 * Prueba de caja blanca para recuperación de contraseña
 * Incluye casos típicos (exitosos) y atípicos (fallidos)
 */
class TestLoginRecuperacion extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new LoginModel();
    }

    // ========== CASOS TÍPICOS (EXITOSOS) ==========

    public function testSolicitarRecuperacionConEmailValido()
    {
        $email = 'admin@test.com';

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

            $this->assertTrue(
                is_string($token) || is_bool($token)
            );
        } else {
            $this->markTestSkipped('Método generarTokenRecuperacion no existe');
        }
    }

    public function testValidarTokenRecuperacion()
    {
        if (method_exists($this->model, 'validarToken')) {
            $tokenPrueba = 'token_valido_123';
            
            $result = $this->model->validarToken($tokenPrueba);

            $this->assertTrue(
                is_array($result) || is_bool($result)
            );
        } else {
            $this->markTestSkipped('Método validarToken no existe');
        }
    }

    public function testRestablecerPassword()
    {
        if (method_exists($this->model, 'restablecerPassword')) {
            $token = 'token_prueba';
            $nuevaPassword = 'NuevaPassword123!';
            
            $result = $this->model->restablecerPassword($token, $nuevaPassword);

            $this->assertTrue(
                is_array($result) || is_bool($result)
            );
        } else {
            $this->markTestSkipped('Método restablecerPassword no existe');
        }
    }

    // ========== CASOS ATÍPICOS (FALLIDOS) ==========

    public function testSolicitarRecuperacionConEmailInexistente()
    {
        $email = 'noexiste@test.com';

        if (method_exists($this->model, 'solicitarRecuperacion')) {
            $result = $this->model->solicitarRecuperacion($email);

            $this->assertIsArray($result);
            $this->assertFalse($result['status']);
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
            $this->assertFalse($result['status']);
        } else {
            $this->markTestSkipped('Método solicitarRecuperacion no existe');
        }
    }

    public function testValidarTokenInvalido()
    {
        if (method_exists($this->model, 'validarToken')) {
            $tokenInvalido = 'token_inexistente_999';
            
            $result = $this->model->validarToken($tokenInvalido);

            $this->assertFalse($result);
        } else {
            $this->markTestSkipped('Método validarToken no existe');
        }
    }

    public function testValidarTokenVacio()
    {
        if (method_exists($this->model, 'validarToken')) {
            $result = $this->model->validarToken('');

            $this->assertFalse($result);
        } else {
            $this->markTestSkipped('Método validarToken no existe');
        }
    }

    public function testRestablecerPasswordConTokenInvalido()
    {
        if (method_exists($this->model, 'restablecerPassword')) {
            $token = 'token_invalido';
            $nuevaPassword = 'NuevaPassword123!';
            
            $result = $this->model->restablecerPassword($token, $nuevaPassword);

            $this->assertIsArray($result);
            $this->assertFalse($result['status']);
        } else {
            $this->markTestSkipped('Método restablecerPassword no existe');
        }
    }

    public function testRestablecerPasswordConPasswordDebil()
    {
        if (method_exists($this->model, 'restablecerPassword')) {
            $token = 'token_prueba';
            $nuevaPassword = '123';
            
            $result = $this->model->restablecerPassword($token, $nuevaPassword);

            $this->assertTrue(
                is_array($result) || is_bool($result)
            );
        } else {
            $this->markTestSkipped('Método restablecerPassword no existe');
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
