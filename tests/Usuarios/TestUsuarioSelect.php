<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/UsuariosModel.php';

/**
 * Prueba de caja blanca para consultas de usuarios
 * Incluye casos típicos (exitosos) y atípicos (fallidos)
 */
class TestUsuarioSelect extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new UsuariosModel();
    }

    // ========== CASOS TÍPICOS (EXITOSOS) ==========

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
            is_array($result) || is_bool($result)
        );
    }

    public function testBuscarUsuarioPorEmail()
    {
        $result = $this->model->selectUsuarioByEmail('admin@admin.com');

        $this->assertTrue(
            is_array($result) || is_bool($result)
        );
    }

    public function testVerificarEstructuraUsuarios()
    {
        $result = $this->model->selectAllUsuarios();

        if (is_array($result) && count($result) > 0) {
            $primerUsuario = $result[0];
            
            $this->assertArrayHasKey('idusuario', $primerUsuario);
            $this->assertArrayHasKey('nombre_usuario', $primerUsuario);
            $this->assertArrayHasKey('correo', $primerUsuario);
        } else {
            $this->assertTrue(is_array($result));
        }
    }

    public function testObtenerUsuariosEliminados()
    {
        $result = $this->model->selectAllUsuariosEliminados();

        $this->assertIsArray($result);
    }

    // ========== CASOS ATÍPICOS (FALLIDOS) ==========

    public function testObtenerUsuarioInexistente()
    {
        $idInexistente = 99999;
        $result = $this->model->selectUsuarioById($idInexistente);

        $this->assertFalse($result);
    }

    public function testBuscarUsuarioPorEmailInexistente()
    {
        $emailInexistente = 'usuario_no_existe@email.com';
        $result = $this->model->selectUsuarioByEmail($emailInexistente);

        $this->assertTrue(
            $result === false || (is_array($result) && empty($result))
        );
    }

    public function testObtenerUsuarioConIdNegativo()
    {
        $result = $this->model->selectUsuarioById(-1);

        $this->assertFalse($result);
    }

    public function testObtenerUsuarioConIdCero()
    {
        $result = $this->model->selectUsuarioById(0);

        $this->assertFalse($result);
    }

    public function testBuscarConEmailVacio()
    {
        $result = $this->model->selectUsuarioByEmail('');

        $this->assertTrue(
            $result === false || (is_array($result) && empty($result))
        );
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
