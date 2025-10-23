<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/categoriasModel.php';

/**
 * Prueba de caja blanca para inserción exitosa de categoría
 * Verifica que se pueda insertar una categoría con todos los campos válidos
 */
class TestCategoriaInsertExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new categoriasModel();
    }

    public function testInsertCategoriaConDatosValidos()
    {
        $data = [
            'nombre' => 'Categoría Test ' . time(),
            'descripcion' => 'Descripción de prueba para categoría',
            'estatus' => 'activo'
        ];

        $result = $this->model->insertCategoria($data);

        $this->assertTrue($result);
    }

    public function testInsertCategoriaConDescripcionVacia()
    {
        $data = [
            'nombre' => 'Categoría Sin Desc ' . time(),
            'descripcion' => '',
            'estatus' => 'activo'
        ];

        $result = $this->model->insertCategoria($data);

        $this->assertTrue($result);
    }

    public function testInsertCategoriaConNombreLargo()
    {
        $nombreLargo = str_repeat('A', 100);
        
        $data = [
            'nombre' => $nombreLargo,
            'descripcion' => 'Prueba con nombre extenso',
            'estatus' => 'activo'
        ];

        $result = $this->model->insertCategoria($data);

        $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
