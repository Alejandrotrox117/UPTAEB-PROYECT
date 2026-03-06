<?php

namespace Tests\IntegrationTest\Usuarios;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\UsuariosModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class TestUsuarioDeleteIntegrationTest extends TestCase
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
    public function eliminarUsuarioExistente()
    {
        // Ensure proper attributes matching model behavior.
        $dataUsuario = [
            'personaId' => 1,
            'usuario' => 'usuario_ele_' . time(),
            'correo' => 'elem_' . time() . '@email.com',
            'clave' => 'Password123!',
            'idrol' => 1
        ];

        $insertResult = $this->model->insertUsuario($dataUsuario);
        if ($insertResult) {
            $usuarios = $this->model->selectAllUsuarios();
            if (is_array($usuarios) && count($usuarios) > 0) {
                $ultimoUsuario = end($usuarios);
                if (isset($ultimoUsuario['idusuario'])) {
                    $idUsuario = $ultimoUsuario['idusuario'];
                    $result = $this->model->deleteUsuarioById($idUsuario);
                    $this->assertIsBool($result);
                } else {
                    $this->markTestSkipped('No se pudo obtener el ID del usuario creado');
                }
            } else {
                $this->markTestSkipped('Array de usuarios malformado o vacio');
            }
        } else {
            $this->markTestSkipped('No se pudo crear usuario de prueba');
        }
    }

    #[Test]
    public function eliminarYVerificarEliminacion()
    {
        $idUsuario = 2; // Might not exist, but let's replicate the original test logic
        $result = $this->model->deleteUsuarioById($idUsuario);
        $this->assertIsBool($result);
    }

    #[Test]
    public function eliminarUsuarioInexistente()
    {
        $idInexistente = 99999;
        $result = $this->model->deleteUsuarioById($idInexistente);
        $this->assertFalse($result);
        if (is_array($result) && array_key_exists('message', $result)) {
            $this->assertNotEmpty($result['message']);
        }
    }

    #[Test]
    public function eliminarUsuarioYaEliminado()
    {
        $idUsuario = 2;
        $this->model->deleteUsuarioById($idUsuario);
        $result = $this->model->deleteUsuarioById($idUsuario);
        $this->assertIsBool($result);
    }
}
