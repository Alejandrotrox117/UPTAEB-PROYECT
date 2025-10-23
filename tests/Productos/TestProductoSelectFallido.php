<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/productosModel.php';

/**
 * Prueba de caja blanca para casos de fallo en consultas de productos
 * Verifica el comportamiento ante consultas inválidas
 */
class TestProductoSelectFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProductosModel();
    }

    public function testSelectProductoByIdInexistente()
    {
        $producto = $this->model->selectProductoById(99999);

        $this->assertFalse($producto);
    }

    public function testSelectProductoByIdNegativo()
    {
        $producto = $this->model->selectProductoById(-1);

        $this->assertFalse($producto);
    }

    public function testSelectProductoByIdCero()
    {
        $producto = $this->model->selectProductoById(0);

        $this->assertFalse($producto);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
