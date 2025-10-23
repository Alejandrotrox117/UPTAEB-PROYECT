<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/categoriasModel.php';

/**
 * Prueba de caja blanca para actualización exitosa de categoría
 * Verifica que se puedan actualizar categorías existentes
 */
class TestCategoriaUpdateExitoso extends TestCase
{
    private $model;
    private $categoriaIdPrueba;

    protected function setUp(): void
    {
        $this->model = new categoriasModel();
        
        // Crear una categoría de prueba para actualizar
        $data = [
            'nombre' => 'Categoría Update Test ' . time(),
            'descripcion' => 'Para actualizar',
            'estatus' => 'activo'
        ];
        
        $this->model->insertCategoria($data);
        
        // Obtener todas las categorías y tomar la última insertada
        $categorias = $this->model->SelectAllCategorias();
        if (!empty($categorias)) {
            $this->categoriaIdPrueba = end($categorias)['idcategoria'];
        }
    }

    public function testUpdateCategoriaDatosCompletos()
    {
        if (!$this->categoriaIdPrueba) {
            $this->markTestSkipped('No se pudo crear categoría de prueba');
        }

        $dataUpdate = [
            'idcategoria' => $this->categoriaIdPrueba,
            'nombre' => 'Categoría Actualizada',
            'descripcion' => 'Descripción actualizada',
            'estatus' => 'activo'
        ];

        $result = $this->model->updateCategoria($dataUpdate);

        $this->assertTrue($result);
    }

    public function testUpdateCategoriaSoloNombre()
    {
        if (!$this->categoriaIdPrueba) {
            $this->markTestSkipped('No se pudo crear categoría de prueba');
        }

        $categoria = $this->model->getCategoriaById($this->categoriaIdPrueba);
        
        $dataUpdate = [
            'idcategoria' => $this->categoriaIdPrueba,
            'nombre' => 'Nombre Modificado ' . time(),
            'descripcion' => $categoria['descripcion'],
            'estatus' => $categoria['estatus']
        ];

        $result = $this->model->updateCategoria($dataUpdate);

        $this->assertTrue($result);
    }

    public function testUpdateCategoriaEstatus()
    {
        if (!$this->categoriaIdPrueba) {
            $this->markTestSkipped('No se pudo crear categoría de prueba');
        }

        $categoria = $this->model->getCategoriaById($this->categoriaIdPrueba);
        
        $dataUpdate = [
            'idcategoria' => $this->categoriaIdPrueba,
            'nombre' => $categoria['nombre'],
            'descripcion' => $categoria['descripcion'],
            'estatus' => 'INACTIVO'
        ];

        $result = $this->model->updateCategoria($dataUpdate);

        $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        // Limpiar: eliminar la categoría de prueba
        if ($this->categoriaIdPrueba) {
            $this->model->deleteCategoria($this->categoriaIdPrueba);
        }
        $this->model = null;
    }
}
