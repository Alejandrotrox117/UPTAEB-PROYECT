<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/categoriasModel.php';

/**
 * Prueba de caja blanca para consultas de categorías
 * Incluye casos típicos (exitosos) y atípicos (fallidos)
 */
class TestCategoriaSelect extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new categoriasModel();
    }

    // ========== CASOS TÍPICOS (EXITOSOS) ==========

    public function testSelectAllCategoriasRetornaArray()
    {
        $result = $this->model->SelectAllCategorias();

        $this->assertIsArray($result);
    }

    public function testSelectAllCategoriasRetornaSoloActivas()
    {
        $categorias = $this->model->SelectAllCategorias();

        foreach ($categorias as $categoria) {
            $this->assertEquals('activo', strtolower($categoria['estatus']));
        }
    }

    public function testSelectAllCategoriasTieneEstructuraCorrecta()
    {
        $categorias = $this->model->SelectAllCategorias();

        if (!empty($categorias)) {
            $categoria = $categorias[0];
            
            $this->assertArrayHasKey('idcategoria', $categoria);
            $this->assertArrayHasKey('nombre', $categoria);
            $this->assertArrayHasKey('descripcion', $categoria);
            $this->assertArrayHasKey('estatus', $categoria);
        } else {
            $this->markTestSkipped('No hay categorías para verificar estructura');
        }
    }

    public function testGetCategoriaByIdExistente()
    {
        // Primero obtener todas las categorías
        $categorias = $this->model->SelectAllCategorias();
        
        if (empty($categorias)) {
            $this->markTestSkipped('No hay categorías para probar');
        }

        $idPrueba = $categorias[0]['idcategoria'];
        $categoria = $this->model->getCategoriaById($idPrueba);

        $this->assertIsArray($categoria);
        $this->assertEquals($idPrueba, $categoria['idcategoria']);
    }

    // ========== CASOS ATÍPICOS (FALLIDOS) ==========

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
