<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/categoriasModel.php';

/**
 * Prueba de caja blanca para casos de fallo en inserción de categoría
 * Verifica el comportamiento ante datos inválidos o faltantes
 */
class TestCategoriaInsertFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new categoriasModel();
    }

    public function testInsertCategoriaSinNombre()
    {
        $data = [
            'nombre' => null,
            'descripcion' => 'Sin nombre',
            'estatus' => 'activo'
        ];

        try {
            $this->model->insertCategoria($data);
            $this->fail('Debería lanzar PDOException');
        } catch (PDOException $e) {
            $this->assertInstanceOf(PDOException::class, $e);
            $this->assertNotEmpty($e->getMessage());
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
