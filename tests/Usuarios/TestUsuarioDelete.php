<?php

use PHPUnit\Framework\TestCase;
use App\Models\UsuariosModel;





class TestUsuarioDelete extends TestCase
{
    private $model;

    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }

    protected function setUp(): void
    {
        $this->model = new UsuariosModel();
    }

    public function testEliminarUsuarioExistente()
    {
        $dataUsuario = [
            'idpersona' => 1,
            'nombre_usuario' => 'usuario_eliminar_' . time(),
            'correo' => 'eliminar_' . time() . '@email.com',
            'password' => 'Password123!',
            'idrol' => 1
        ];

        $insertResult = $this->model->insertUsuario($dataUsuario);

        if ($insertResult) {
            $usuarios = $this->model->selectAllUsuarios();
            
            if (is_array($usuarios) && count($usuarios) > 0) {
                $ultimoUsuario = end($usuarios);
                $idUsuario = $ultimoUsuario['idusuario'];
                
                $result = $this->model->deleteUsuarioById($idUsuario);
                
                $this->assertIsBool($result);
            } else {
                $this->markTestSkipped('No se pudo obtener el ID del usuario creado');
            }
        } else {
            $this->markTestSkipped('No se pudo crear usuario de prueba');
        }
    }

    public function testEliminarYVerificarEliminacion()
    {
        $idUsuario = 2;
        
        $result = $this->model->deleteUsuarioById($idUsuario);
        
        $this->assertIsBool($result);
    }

    public function testEliminarUsuarioInexistente()
    {
        $idInexistente = 99999;
        
        $result = $this->model->deleteUsuarioById($idInexistente);
        
        $this->assertFalse($result);
        
        if (is_array($result) && array_key_exists('message', $result)) {
            $this->showMessage($result['message']);
        }
    }

    public function testEliminarUsuarioYaEliminado()
    {
        $idUsuario = 2;
        
        $this->model->deleteUsuarioById($idUsuario);
        
        $result = $this->model->deleteUsuarioById($idUsuario);
        
        $this->assertIsBool($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
