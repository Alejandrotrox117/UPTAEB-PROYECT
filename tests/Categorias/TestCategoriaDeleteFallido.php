<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/categoriasModel.php';

/**
 * Prueba de caja blanca para casos de fallo en eliminación de categoría
 * Verifica validaciones al intentar eliminar categorías
 */
class TestCategoriaDeleteFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new categoriasModel();
    }

    public function testDeleteCategoriaInexistente()
    {
        $result = $this->model->deleteCategoria(99999);

        $this->assertIsBool($result);
    }

    public function testDeleteCategoriaConIdNulo()
    {
        try {
            $this->model->deleteCategoria(null);
            $this->fail('Debería lanzar TypeError');
        } catch (TypeError $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function testDeleteCategoriaConIdCero()
    {
        $result = $this->model->deleteCategoria(0);

        $this->assertIsBool($result);
    }

    public function testDeleteCategoriaConIdNegativo()
    {
        $result = $this->model->deleteCategoria(-1);

        $this->assertIsBool($result);
    }

    public function testDeleteCategoriaConIdString()
    {
        try {
            $this->model->deleteCategoria("texto");
            $this->fail('Debería lanzar TypeError');
        } catch (TypeError $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
