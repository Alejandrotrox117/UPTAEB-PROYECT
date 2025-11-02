<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/categoriasModel.php';





class TestCategoriaDelete extends TestCase
{
    private $model;
    private $categoriaIdPrueba;

    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }

    protected function setUp(): void
    {
        $this->model = new categoriasModel();
        
        
        $data = [
            'nombre' => 'Categoría Delete Test ' . time(),
            'descripcion' => 'Para eliminar',
            'estatus' => 'activo'
        ];
        
        $this->model->insertCategoria($data);
        
        
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

        
        $result1 = $this->model->deleteCategoria($this->categoriaIdPrueba);
        $this->assertTrue($result1);
        
        
        $result2 = $this->model->deleteCategoria($this->categoriaIdPrueba);
        $this->assertTrue($result2);
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
