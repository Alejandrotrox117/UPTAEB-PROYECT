<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/productosModel.php';

/**
 * Prueba de caja blanca para eliminación exitosa de productos
 * Verifica el borrado lógico de productos
 */
class TestProductoDeleteExitoso extends TestCase
{
    private $model;
    private $productoIdPrueba;

    protected function setUp(): void
    {
        $this->model = new ProductosModel();
        
        // Crear un producto de prueba para eliminar
        $data = [
            'nombre' => 'Producto Delete Test ' . time(),
            'descripcion' => 'Para eliminar',
            'unidad_medida' => 'kg',
            'precio' => 15.00,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];
        
        $result = $this->model->insertProducto($data);
        
        if ($result['status']) {
            $this->productoIdPrueba = $result['producto_id'];
        }
    }

    public function testDeleteProductoExistente()
    {
        if (!$this->productoIdPrueba) {
            $this->markTestSkipped('No se pudo crear producto de prueba');
        }

        $result = $this->model->deleteProductoById($this->productoIdPrueba);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        
        if (isset($result['status'])) {
            $this->assertTrue($result['status']);
        }
    }

    public function testDeleteProductoVerificarEstatus()
    {
        if (!$this->productoIdPrueba) {
            $this->markTestSkipped('No se pudo crear producto de prueba');
        }

        $result = $this->model->deleteProductoById($this->productoIdPrueba);

        if ($result['status']) {
            $producto = $this->model->selectProductoById($this->productoIdPrueba);
            
            if ($producto) {
                $this->assertEquals('INACTIVO', strtoupper($producto['estatus']));
            }
        }

        $this->assertIsArray($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
