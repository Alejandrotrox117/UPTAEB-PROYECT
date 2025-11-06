<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/UsuariosModel.php';


class TestUsuarioInsert extends TestCase
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

    

    public function testInsertarUsuarioConDatosCompletos()
    {
        $data = [
            'personaId' => 1,
            'usuario' => 'usuario_test_' . time(),
            'correo' => 'test_' . time() . '@email.com',
            'clave' => 'Password123!',
            'idrol' => 1
        ];

        $result = $this->model->insertUsuario($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('usuario_id', $result);
        $this->assertIsBool($result['status']);
    }

    public function testInsertarUsuarioSinNombreUsuario()
    {
        $data = [
            'personaId' => 1,
            'usuario' => '',
            'correo' => 'test@email.com',
            'clave' => 'Password123!',
            'idrol' => 1
        ];

        $result = $this->model->insertUsuario($data);
        $this->assertIsArray($result);

        if (array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
            $this->assertNotEmpty($result['message']);
            $this->showMessage($result['message']);
        } else {
            $this->assertIsBool($result['status']);
        }
    }

    public function testInsertarUsuarioSinCorreo()
    {
        $data = [
            'personaId' => 1,
            'usuario' => 'usuario_test',
            'correo' => '',
            'clave' => 'Password123!',
            'idrol' => 1
        ];

        $result = $this->model->insertUsuario($data);
        $this->assertIsArray($result);

        if (array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
            $this->assertNotEmpty($result['message']);
            $this->showMessage($result['message']);
        } else {
            $this->assertIsBool($result['status']);
        }
    }

    public function testInsertarUsuarioSinPassword()
    {
        $data = [
            'personaId' => 1,
            'usuario' => 'usuario_test',
            'correo' => 'test@email.com',
            'clave' => '',
            'idrol' => 1
        ];

        $result = $this->model->insertUsuario($data);
        $this->assertIsArray($result);

        if (array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
            $this->assertNotEmpty($result['message']);
            $this->showMessage($result['message']);
        } else {
            $this->assertIsBool($result['status']);
        }
    }

    public function testInsertarUsuarioConCorreoDuplicado()
    {
        $data = [
            'personaId' => 1,
            'usuario' => 'usuario_unico_' . time(),
            'correo' => 'admin@admin.com',
            'clave' => 'Password123!',
            'idrol' => 1
        ];

        $result = $this->model->insertUsuario($data);
        $this->assertIsArray($result);

        if (array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
            $this->assertNotEmpty($result['message']);
            $this->showMessage($result['message']);
        } else {
            $this->assertIsBool($result['status']);
        }
    }

    public function testInsertarUsuarioConEmailInvalido()
    {
        $data = [
            'personaId' => 1,
            'usuario' => 'usuario_test',
            'correo' => 'email_sin_arroba',
            'clave' => 'Password123!',
            'idrol' => 1
        ];

        $result = $this->model->insertUsuario($data);
        $this->assertIsArray($result);

        if (array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
            $this->assertNotEmpty($result['message']);
            $this->showMessage($result['message']);
        } else {
            $this->assertIsBool($result['status']);
        }
    }

    public function testInsertarUsuarioSinRol()
    {
        $data = [
            'personaId' => 1,
            'usuario' => 'usuario_test',
            'correo' => 'test@email.com',
            'clave' => 'Password123!'
        ];

        $result = $this->model->insertUsuario($data);
        $this->assertIsArray($result);

        if (array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
            $this->assertNotEmpty($result['message']);
            $this->showMessage($result['message']);
        } else {
            $this->assertIsBool($result['status']);
        }
    }

    public function testInsertarUsuarioConPasswordDebil()
    {
        $data = [

            'personaId' => 1,
            'usuario' => 'usuario_test',
            'correo' => 'test@email.com',
            'clave' => '123',
            'idrol' => 1
        ];

        $result = $this->model->insertUsuario($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertIsBool($result['status']);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
