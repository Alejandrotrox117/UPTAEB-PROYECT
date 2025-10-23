<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/categoriasModel.php';

/**
 * Prueba de caja blanca para consultas exitosas de categorías
 * Verifica la lectura de datos de categorías
 */
class TestCategoriaSelectExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new categoriasModel();
    }

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

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
