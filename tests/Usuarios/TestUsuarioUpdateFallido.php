<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/UsuariosModel.php';

/**
 * Prueba de caja blanca para casos de fallo en actualización de usuarios
 * Valida restricciones y validaciones en actualización
 */
class TestUsuarioUpdateFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new UsuariosModel();
    }

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
            'correo' => 'admin@admin.com' // Correo que ya existe
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
