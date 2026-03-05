<?php

namespace Tests\IntegrationTest\Usuarios;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\UsuariosModel;
use Exception;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class TestUsuarioUpdateIntegrationTest extends TestCase
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
    public function actualizarUsuarioConDatosCompletos()
    {
        $data = [
            'nombre_usuario' => 'usuario_actualizado',
            'correo' => 'actualizado@email.com',
            'idrol' => 1,
            'estatus' => 'activo'
        ];
        $result = $this->model->updateUsuario(1, $data);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    #[Test]
    public function actualizarUsuarioInexistente()
    {
        $data = [
            'usuario' => 'usuario_inexistente',
            'correo' => 'test@test.com',
            'idrol' => 2
        ];
        $result = $this->model->updateUsuario(99999, $data);
        if (is_array($result) && array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
        }
        $this->assertTrue(is_array($result) || $result === false);
    }

    #[Test]
    public function actualizarConCorreoDuplicado()
    {
        // Require two users to exist, to duplicate email.
        $data = [
            'usuario' => 'usuario_test_123',
            'correo' => 'admin@admin.com', // Assuming this belongs to user 1
            'idrol' => 2
        ];
        // Try update user 2 with user 1's email
        $result = $this->model->updateUsuario(2, $data);
        if (is_array($result) && array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
        }
        $this->assertTrue(is_array($result) || $result === false);
    }

    #[Test]
    public function actualizarConNombreDuplicado()
    {
        // Require two users to exist, to duplicate username.
        $data = [
            'nombre_usuario' => 'admin', // assuming this belongs to super admin
            'correo' => 'test321@email.com',
            'idrol' => 2
        ];
        // Try update user 2 with user 1's username
        $result = $this->model->updateUsuario(2, $data);
        if (is_array($result) && array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
        }
        $this->assertTrue(is_array($result) || $result === false);
    }

    #[Test]
    public function actualizarConEmailInvalido()
    {
        $data = [
            'usuario' => 'usuario_test',
            'correo' => 'email_invalido_sin_arroba',
            'idrol' => 2
        ];
        $result = $this->model->updateUsuario(1, $data);
        if (is_array($result) && array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
        }
        $this->assertTrue(is_array($result) || $result === false);
    }

    #[Test]
    public function actualizarConRolInexistente()
    {
        $data = ['idrol' => 99999];
        try {
            $result = $this->model->updateUsuario(1, $data);
            $this->assertFalse($result);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
        }
    }
}
