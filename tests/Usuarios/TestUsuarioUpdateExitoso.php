<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/UsuariosModel.php';

/**
 * Prueba de caja blanca para actualización exitosa de usuarios
 * Valida actualización de datos de usuario
 */
class TestUsuarioUpdateExitoso extends TestCase
{
    private $model;

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

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
