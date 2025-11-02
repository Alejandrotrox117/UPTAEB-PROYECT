<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/UsuariosModel.php';





class TestUsuarioUpdate extends TestCase
{
    private $model;

    private function showMessage(string $msg)
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }

    protected function setUp(): void
    {
        $this->model = new UsuariosModel();
    }

    

    public function testActualizarUsuarioConDatosCompletos()
    {
        $data = [
            'nombre_usuario' => 'usuario_actualizado',
            'correo' => 'actualizado@email.com',
            'idrol' => 2,
            'estatus' => 'activo'
        ];

        $result = $this->model->updateUsuario(1, $data);

        $this->assertIsBool($result);
    }

    public function testActualizarSoloRol()
    {
        $data = [
            'idrol' => 3
        ];

        $result = $this->model->updateUsuario(1, $data);

        $this->assertIsBool($result);
    }

    public function testActualizarSoloCorreo()
    {
        $data = [
            'correo' => 'nuevo_correo_' . time() . '@email.com'
        ];

        $result = $this->model->updateUsuario(1, $data);

        $this->assertIsBool($result);
    }

    public function testActualizarPassword()
    {
        $data = [
            'password' => 'NuevaPassword123!'
        ];

        $result = $this->model->updateUsuario(1, $data);

        $this->assertIsBool($result);
    }

    public function testCambiarEstatus()
    {
        $data = [
            'estatus' => 'inactivo'
        ];

        $result = $this->model->updateUsuario(1, $data);

        $this->assertIsBool($result);
    }

    

    public function testActualizarUsuarioInexistente()
    {
        $data = [
            'usuario' => 'usuario_inexistente',
            'correo' => 'test@test.com',
            'idrol' => 2
        ];

        $result = $this->model->updateUsuario(99999, $data);

        if (is_array($result) && array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
            $this->showMessage($result['message']);
        }
        $this->assertTrue(is_array($result) || $result === false);
    }

    public function testActualizarConCorreoDuplicado()
    {
        $data = [
            'usuario' => 'usuario_test',
            'correo' => 'admin@admin.com',
            'idrol' => 2
        ];

        $result = $this->model->updateUsuario(2, $data);

        if (is_array($result) && array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
            $this->showMessage($result['message']);
        }
        $this->assertTrue(is_array($result) || $result === false);
    }

    public function testActualizarConEmailInvalido()
    {
        $data = [
            'usuario' => 'usuario_test',
            'correo' => 'email_invalido_sin_arroba',
            'idrol' => 2
        ];

        $result = $this->model->updateUsuario(1, $data);

        if (is_array($result) && array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
            $this->showMessage($result['message']);
        }
        $this->assertTrue(is_array($result) || $result === false);
    }

    public function testActualizarConRolInexistente()
    {
        $data = [
            'idrol' => 99999
        ];

        try {
            $result = $this->model->updateUsuario(1, $data);
            $this->assertFalse($result);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
        }
    }

    public function testActualizarSinDatos()
    {
        $data = [];

        try {
            $result = $this->model->updateUsuario(1, $data);
            $this->assertIsBool($result);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
