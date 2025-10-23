<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/categoriasModel.php';

/**
 * Prueba de caja blanca para casos de fallo en actualización de categoría
 * Verifica validaciones y manejo de errores
 */
class TestCategoriaUpdateFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new categoriasModel();
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
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
