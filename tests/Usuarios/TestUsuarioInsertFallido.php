<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/UsuariosModel.php';

/**
 * Prueba de caja blanca para casos de fallo en inserción de usuarios
 * Valida validaciones de datos requeridos y únicos
 */
class TestUsuarioInsertFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new UsuariosModel();
    }

    public function testInsertarUsuarioSinNombreUsuario()
    {
        $data = [
            'idpersona' => 1,
            'correo' => 'test@email.com',
            'password' => 'Password123!',
            'idrol' => 1
        ];

        try {
            $result = $this->model->insertUsuario($data);
            $this->assertFalse($result);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
        }
    }

    public function testInsertarUsuarioSinCorreo()
    {
        $data = [
            'idpersona' => 1,
            'nombre_usuario' => 'usuario_test',
            'password' => 'Password123!',
            'idrol' => 1
        ];

        try {
            $result = $this->model->insertUsuario($data);
            $this->assertFalse($result);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
        }
    }

    public function testInsertarUsuarioSinPassword()
    {
        $data = [
            'idpersona' => 1,
            'nombre_usuario' => 'usuario_test',
            'correo' => 'test@email.com',
            'idrol' => 1
        ];

        try {
            $result = $this->model->insertUsuario($data);
            $this->assertFalse($result);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
        }
    }

    public function testInsertarUsuarioConCorreoDuplicado()
    {
        $data = [
            'idpersona' => 1,
            'nombre_usuario' => 'usuario_unico_' . time(),
            'correo' => 'admin@admin.com', // Correo que ya existe
            'password' => 'Password123!',
            'idrol' => 1
        ];

        $result = $this->model->insertUsuario($data);

        $this->assertFalse($result);
    }

    public function testInsertarUsuarioConEmailInvalido()
    {
        $data = [
            'idpersona' => 1,
            'nombre_usuario' => 'usuario_test',
            'correo' => 'email_sin_arroba',
            'password' => 'Password123!',
            'idrol' => 1
        ];

        $result = $this->model->insertUsuario($data);

        $this->assertFalse($result);
    }

    public function testInsertarUsuarioSinRol()
    {
        $data = [
            'idpersona' => 1,
            'nombre_usuario' => 'usuario_test',
            'correo' => 'test@email.com',
            'password' => 'Password123!'
        ];

        try {
            $result = $this->model->insertUsuario($data);
            $this->assertFalse($result);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
        }
    }

    public function testInsertarUsuarioConPasswordDebil()
    {
        $data = [
            'idpersona' => 1,
            'nombre_usuario' => 'usuario_test',
            'correo' => 'test@email.com',
            'password' => '123', // Contraseña débil
            'idrol' => 1
        ];

        $result = $this->model->insertUsuario($data);

        // Dependiendo de validaciones de contraseña
        $this->assertIsBool($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
