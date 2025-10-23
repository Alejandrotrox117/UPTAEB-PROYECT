<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/categoriasModel.php';

/**
 * Prueba de caja blanca para casos de fallo en consultas de categorías
 * Verifica el comportamiento ante consultas inválidas
 */
class TestCategoriaSelectFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new categoriasModel();
    }

    public function testGetCategoriaByIdInexistente()
    {
        $categoria = $this->model->getCategoriaById(99999);

        $this->assertFalse($categoria);
    }

    public function testGetCategoriaByIdNulo()
    {
        try {
            $this->model->getCategoriaById(null);
            $this->fail('Debería lanzar TypeError');
        } catch (TypeError $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function testGetCategoriaByIdNegativo()
    {
        $categoria = $this->model->getCategoriaById(-1);

        $this->assertFalse($categoria);
    }

    public function testGetCategoriaByIdCero()
    {
        $categoria = $this->model->getCategoriaById(0);

        $this->assertFalse($categoria);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
