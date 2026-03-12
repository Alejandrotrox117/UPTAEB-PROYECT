<?php

namespace Tests\IntegrationTest\Usuarios;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\UsuariosModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class TestUsuarioSelectIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private $model;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new UsuariosModel();
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }

    #[Test]
    public function seleccionarTodosUsuarios()
    {
        $result = $this->model->selectAllUsuarios();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
    }

    #[Test]
    public function seleccionarUsuariosActivos()
    {
        $result = $this->model->selectAllUsuariosActivos();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
    }

    #[Test]
    public function obtenerUsuarioPorId()
    {
        $result = $this->model->selectUsuarioById(1);
        $this->assertTrue(is_array($result) || $result === false);
        if (is_array($result)) {
            $this->assertArrayHasKey('idusuario', $result);
            $this->assertArrayHasKey('usuario', $result);
            $this->assertArrayHasKey('correo', $result);
        }
    }

    #[Test]
    public function buscarUsuarioPorEmail()
    {
        $result = $this->model->selectUsuarioByEmail('admin@admin.com'); // assuming admin@admin.com exists
        $this->assertTrue($result === false || (is_array($result) && array_key_exists('correo', $result)));
    }

    #[Test]
    public function obtenerUsuarioInexistente()
    {
        $idInexistente = 99999;
        $result = $this->model->selectUsuarioById($idInexistente);
        $this->assertFalse($result);
    }

    #[Test]
    public function buscarUsuarioPorEmailInexistente()
    {
        $emailInexistente = 'usuario_no_existe@email.com';
        $result = $this->model->selectUsuarioByEmail($emailInexistente);
        $this->assertTrue($result === false || (is_array($result) && empty($result)));
    }
}
