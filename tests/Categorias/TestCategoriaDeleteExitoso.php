<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/categoriasModel.php';

/**
 * Prueba de caja blanca para eliminación exitosa de categoría
 * Verifica el borrado lógico (cambio de estatus a INACTIVO)
 */
class TestCategoriaDeleteExitoso extends TestCase
{
    private $model;
    private $categoriaIdPrueba;

    protected function setUp(): void
    {
        $this->model = new categoriasModel();
        
        // Crear una categoría de prueba para eliminar
        $data = [
            'nombre' => 'Categoría Delete Test ' . time(),
            'descripcion' => 'Para eliminar',
            'estatus' => 'activo'
        ];
        
        $this->model->insertCategoria($data);
        
        // Obtener todas las categorías y tomar la última insertada
        $categorias = $this->model->SelectAllCategorias();
        if (!empty($categorias)) {
            $this->categoriaIdPrueba = end($categorias)['idcategoria'];
        }
    }

    public function testDeleteCategoriaExistente()
    {
        if (!$this->categoriaIdPrueba) {
            $this->markTestSkipped('No se pudo crear categoría de prueba');
        }

        $result = $this->model->deleteCategoria($this->categoriaIdPrueba);

        $this->assertTrue($result);
        
        // Verificar que ya no aparezca en las categorías activas
        $categorias = $this->model->SelectAllCategorias();
        $encontrada = false;
        
        foreach ($categorias as $cat) {
            if ($cat['idcategoria'] == $this->categoriaIdPrueba) {
                $encontrada = true;
                break;
            }
        }
        
        $this->assertFalse($encontrada);
    }

    public function testDeleteCategoriaYaEliminada()
    {
        if (!$this->categoriaIdPrueba) {
            $this->markTestSkipped('No se pudo crear categoría de prueba');
        }

        // Primera eliminación
        $result1 = $this->model->deleteCategoria($this->categoriaIdPrueba);
        $this->assertTrue($result1);
        
        // Segunda eliminación (debería seguir siendo exitosa pero sin cambios)
        $result2 = $this->model->deleteCategoria($this->categoriaIdPrueba);
        $this->assertTrue($result2);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
