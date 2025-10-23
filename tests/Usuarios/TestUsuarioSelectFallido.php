<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/UsuariosModel.php';

/**
 * Prueba de caja blanca para casos de fallo en consultas de usuarios
 * Valida manejo de datos inexistentes
 */
class TestUsuarioSelectFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new UsuariosModel();
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

        $this->assertTrue(
            $result === false || (is_array($result) && empty($result)),
            "Debería retornar false o array vacío para email inexistente"
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
            $result === false || (is_array($result) && empty($result)),
            "Debería retornar false o array vacío para email vacío"
        );
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
