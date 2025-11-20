<?php

use PHPUnit\Framework\TestCase;
use App\Models\UsuariosModel;
class TestUsuarioSelect extends TestCase
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
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
    }

    public function testSeleccionarUsuariosActivos()
    {
        $result = $this->model->selectAllUsuariosActivos();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
    }

    public function testObtenerUsuarioPorId()
    {
        $result = $this->model->selectUsuarioById(1);

        
        $this->assertTrue(is_array($result) || $result === false);
        if (is_array($result)) {
            $this->assertArrayHasKey('idusuario', $result);
            $this->assertArrayHasKey('usuario', $result);
            $this->assertArrayHasKey('correo', $result);
        }
    }

    public function testBuscarUsuarioPorEmail()
    {
        $result = $this->model->selectUsuarioByEmail('admin@admin.com');
        $this->assertTrue($result === false || (is_array($result) && array_key_exists('correo', $result)));
    }

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
        $this->assertTrue($result === false || (is_array($result) && empty($result)));
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
