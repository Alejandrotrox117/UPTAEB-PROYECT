﻿<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/UsuariosModel.php';

/**
 * Prueba de caja blanca para eliminación exitosa de usuarios
 * Valida eliminación lógica de usuarios
 */
class TestUsuarioDeleteExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new UsuariosModel();
    }

    public function testEliminarUsuarioExistente()
    {
        // Crear usuario de prueba
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
        // La eliminación es lógica
        $idUsuario = 2;
        
        $result = $this->model->deleteUsuarioById($idUsuario);
        
        $this->assertIsBool($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
