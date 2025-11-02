<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/categoriasModel.php';





class TestCategoriaUpdate extends TestCase
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
            'nombre' => 'Categoría Update Test ' . time(),
            'descripcion' => 'Para actualizar',
            'estatus' => 'activo'
        ];
        
        $this->model->insertCategoria($data);
        
        
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

    

    public function testUpdateCategoriaInexistente()
    {
        $dataUpdate = [
            'idcategoria' => 99999,
            'nombre' => 'No existe',
            'descripcion' => 'Esta categoría no existe',
            'estatus' => 'activo'
        ];

        $result = $this->model->updateCategoria($dataUpdate);

        $this->assertFalse($result);
        
        if (is_array($result) && array_key_exists('message', $result)) {
            $this->showMessage($result['message']);
        }
    }

    public function testUpdateCategoriaSinId()
    {
        $dataUpdate = [
            'nombre' => 'Sin ID',
            'descripcion' => 'Falta el ID',
            'estatus' => 'activo'
        ];

        try {
            $this->model->updateCategoria($dataUpdate);
            $this->fail('Debería lanzar TypeError o Exception');
        } catch (TypeError | Exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function testUpdateCategoriaSinNombre()
    {
        $dataUpdate = [
            'idcategoria' => 1,
            'descripcion' => 'Sin nombre',
            'estatus' => 'activo'
        ];

        try {
            $this->model->updateCategoria($dataUpdate);
            $this->fail('Debería lanzar TypeError o Exception');
        } catch (TypeError | Exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function testUpdateCategoriaConIdNegativo()
    {
        $dataUpdate = [
            'idcategoria' => -1,
            'nombre' => 'ID negativo',
            'descripcion' => 'Prueba con ID inválido',
            'estatus' => 'activo'
        ];

        $result = $this->model->updateCategoria($dataUpdate);

        $this->assertFalse($result);
        
        if (is_array($result) && array_key_exists('message', $result)) {
            $this->showMessage($result['message']);
        }
    }

    protected function tearDown(): void
    {
        
        if ($this->categoriaIdPrueba) {
            $this->model->deleteCategoria($this->categoriaIdPrueba);
        }
        $this->model = null;
    }
}
