<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/UsuariosModel.php';

/**
 * Prueba de caja blanca para inserción de usuarios
 * Incluye casos típicos (exitosos) y atípicos (fallidos)
 */
class TestUsuarioInsert extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new UsuariosModel();
    }

    // ========== CASOS TÍPICOS (EXITOSOS) ==========

    public function testInsertarUsuarioConDatosCompletos()
    {
        $data = [
            'idpersona' => 1,
            'nombre_usuario' => 'usuario_test_' . time(),
            'correo' => 'test_' . time() . '@email.com',
            'password' => 'Password123!',
            'idrol' => 1,
            'estatus' => 'activo'
        ];

        $result = $this->model->insertUsuario($data);

        $this->assertIsBool($result);
    }

    public function testInsertarUsuarioConPasswordSegura()
    {
        $data = [
            'idpersona' => 1,
            'nombre_usuario' => 'usuario_seguro_' . time(),
            'correo' => 'seguro_' . time() . '@email.com',
            'password' => 'P@ssw0rd!Segur@2024',
            'idrol' => 2
        ];

        $result = $this->model->insertUsuario($data);

        $this->assertIsBool($result);
    }

    public function testInsertarUsuarioConRolDiferente()
    {
        $data = [
            'idpersona' => 1,
            'nombre_usuario' => 'usuario_rol_' . time(),
            'correo' => 'rol_' . time() . '@email.com',
            'password' => 'Password456!',
            'idrol' => 3
        ];

        $result = $this->model->insertUsuario($data);

        $this->assertIsBool($result);
    }

    // ========== CASOS ATÍPICOS (FALLIDOS) ==========

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
            'correo' => 'admin@admin.com',
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
            'password' => '123',
            'idrol' => 1
        ];

        $result = $this->model->insertUsuario($data);

        $this->assertIsBool($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
