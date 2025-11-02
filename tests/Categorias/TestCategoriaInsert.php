<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/categoriasModel.php';





class TestCategoriaInsert extends TestCase
{
    private $model;

    private function showMessage(string $msg)
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }

    protected function setUp(): void
    {
        $this->model = new categoriasModel();
    }

    

    public function testInsertCategoriaConDatosValidos()
    {
        $data = [
            'nombre' => 'Materiales Cartón - Por Clasificar ' . time(),
            'descripcion' => 'Cartón corrugado recibido de recolectores, mezclado con contaminantes',
            'estatus' => 'activo'
        ];

        $result = $this->model->insertCategoria($data);

        $this->assertTrue($result);
    }

    public function testInsertCategoriaConDescripcionVacia()
    {
        $data = [
            'nombre' => 'Materiales Plástico PET - Clasificado ' . time(),
            'descripcion' => '',
            'estatus' => 'activo'
        ];

        $result = $this->model->insertCategoria($data);

        $this->assertTrue($result);
    }

    public function testInsertCategoriaConNombreLargo()
    {
        $nombreLargo = 'Pacas de Plástico PET Transparente Calidad Premium para Exportación Industrial ' . time();
        
        $data = [
            'nombre' => $nombreLargo,
            'descripcion' => 'Pacas de plástico PET de alta calidad, limpias y compactadas según estándares de exportación',
            'estatus' => 'activo'
        ];

        $result = $this->model->insertCategoria($data);

        $this->assertTrue($result);
    }

    

    public function testInsertCategoriaSinNombre()
    {
        $data = [
            'nombre' => null,
            'descripcion' => 'Sin nombre',
            'estatus' => 'activo'
        ];

        try {
            $result = $this->model->insertCategoria($data);
            $this->fail('Debería lanzar PDOException');
        } catch (PDOException $e) {
            $this->showMessage("PDOException: " . $e->getMessage());
            $this->assertInstanceOf(PDOException::class, $e);
        }
    }

    public function testInsertCategoriaSinEstatus()
    {
        $data = [
            'nombre' => 'Categoría Test',
            'descripcion' => 'Sin estatus',
            'estatus' => null
        ];

        try {
            $this->model->insertCategoria($data);
            $this->fail('Debería lanzar PDOException');
        } catch (PDOException $e) {
            $this->assertInstanceOf(PDOException::class, $e);
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function testInsertCategoriaConDatosIncompletos()
    {
        $data = [
            'nombre' => 'Solo nombre'
        ];

        try {
            $this->model->insertCategoria($data);
            $this->fail('Debería lanzar TypeError o Exception');
        } catch (TypeError | Exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function testInsertCategoriaConArrayVacio()
    {
        $data = [];

        try {
            $this->model->insertCategoria($data);
            $this->fail('Debería lanzar TypeError o Exception');
        } catch (TypeError | Exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
