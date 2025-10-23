<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/productosModel.php';

/**
 * Prueba de caja blanca para casos de fallo en eliminación de productos
 * Verifica validaciones al intentar eliminar productos
 */
class TestProductoDeleteFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProductosModel();
    }

    public function testDeleteProductoInexistente()
    {
        $result = $this->model->deleteProductoById(99999);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertFalse($result['status'], "No debería eliminar un producto inexistente");
    }

    public function testDeleteProductoConIdCero()
    {
        $result = $this->model->deleteProductoById(0);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertFalse($result['status'], "No debería eliminar con ID cero");
    }

    public function testDeleteProductoConIdNegativo()
    {
        $result = $this->model->deleteProductoById(-1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertFalse($result['status'], "No debería eliminar con ID negativo");
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
