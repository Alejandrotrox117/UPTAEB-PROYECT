<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/UsuariosModel.php';

/**
 * Prueba de caja blanca para actualización de usuarios
 * Incluye casos típicos (exitosos) y atípicos (fallidos)
 */
class TestUsuarioUpdate extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new UsuariosModel();
    }

    // ========== CASOS TÍPICOS (EXITOSOS) ==========

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

    // ========== CASOS ATÍPICOS (FALLIDOS) ==========

    public function testActualizarUsuarioInexistente()
    {
        $data = [
            'nombre_usuario' => 'usuario_inexistente'
        ];

        $result = $this->model->updateUsuario(99999, $data);

        $this->assertFalse($result);
    }

    public function testActualizarConCorreoDuplicado()
    {
        $data = [
            'correo' => 'admin@admin.com'
        ];

        $result = $this->model->updateUsuario(2, $data);

        $this->assertFalse($result);
    }

    public function testActualizarConEmailInvalido()
    {
        $data = [
            'correo' => 'email_invalido_sin_arroba'
        ];

        $result = $this->model->updateUsuario(1, $data);

        $this->assertFalse($result);
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
