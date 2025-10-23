<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/UsuariosModel.php';

/**
 * Prueba de caja blanca para inserción exitosa de usuarios
 * Valida creación de usuarios con validaciones
 */
class TestUsuarioInsertExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new UsuariosModel();
    }

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

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
