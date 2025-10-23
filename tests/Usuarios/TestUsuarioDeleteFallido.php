<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/UsuariosModel.php';

/**
 * Prueba de caja blanca para casos de fallo en eliminación de usuarios
 * Valida restricciones de eliminación
 */
class TestUsuarioDeleteFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new UsuariosModel();
    }

    public function testEliminarUsuarioInexistente()
    {
        $idInexistente = 99999;
        
        $result = $this->model->deleteUsuarioById($idInexistente);
        
        $this->assertFalse($result);
    }

    public function testEliminarConIdNegativo()
    {
        $result = $this->model->deleteUsuarioById(-1);
        
        $this->assertFalse($result);
    }

    public function testEliminarConIdCero()
    {
        $result = $this->model->deleteUsuarioById(0);
        
        $this->assertFalse($result);
    }

    public function testEliminarUsuarioYaEliminado()
    {
        $idUsuario = 2;
        
        // Primera eliminación
        $this->model->deleteUsuarioById($idUsuario);
        
        // Segunda eliminación
        $result = $this->model->deleteUsuarioById($idUsuario);
        
        $this->assertIsBool($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
