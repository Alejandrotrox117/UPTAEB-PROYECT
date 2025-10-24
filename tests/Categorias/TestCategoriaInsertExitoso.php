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

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
