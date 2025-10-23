<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/UsuariosModel.php';

/**
 * Prueba de caja blanca para consultas exitosas de usuarios
 * Valida obtención de información de usuarios
 */
class TestUsuarioSelectExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new UsuariosModel();
    }

    public function testSeleccionarTodosUsuarios()
    {
        $result = $this->model->selectAllUsuarios();

        $this->assertIsArray($result);
    }

    public function testSeleccionarUsuariosActivos()
    {
        $result = $this->model->selectAllUsuariosActivos();

        $this->assertIsArray($result);
    }

    public function testObtenerUsuarioPorId()
    {
        $result = $this->model->selectUsuarioById(1);

        $this->assertTrue(
            is_array($result) || is_bool($result),
            "Debería retornar array o false"
        );
    }

    public function testBuscarUsuarioPorEmail()
    {
        $result = $this->model->selectUsuarioByEmail('admin@admin.com');

        $this->assertTrue(
            is_array($result) || is_bool($result),
            "Debería retornar array o false"
        );
    }

    public function testVerificarEstructuraUsuarios()
    {
        $result = $this->model->selectAllUsuarios();

        if (is_array($result) && count($result) > 0) {
            $primerUsuario = $result[0];
            
            $this->assertArrayHasKey('idusuario', $primerUsuario, "Debería tener idusuario");
            $this->assertArrayHasKey('nombre_usuario', $primerUsuario, "Debería tener nombre_usuario");
            $this->assertArrayHasKey('correo', $primerUsuario, "Debería tener correo");
        } else {
            $this->assertTrue(is_array($result));
        }
    }

    public function testObtenerUsuariosEliminados()
    {
        $result = $this->model->selectAllUsuariosEliminados();

        $this->assertIsArray($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
