<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/productosModel.php';

/**
 * Prueba de caja blanca para actualización exitosa de productos
 * Verifica que se puedan actualizar productos existentes
 */
class TestProductoUpdateExitoso extends TestCase
{
    private $model;
    private $productoIdPrueba;

    protected function setUp(): void
    {
        $this->model = new ProductosModel();
        
        // Crear un producto de prueba para actualizar
        $data = [
            'nombre' => 'Producto Update Test ' . time(),
            'descripcion' => 'Para actualizar',
            'unidad_medida' => 'kg',
            'precio' => 20.00,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];
        
        $result = $this->model->insertProducto($data);
        
        if ($result['status']) {
            $this->productoIdPrueba = $result['producto_id'];
        }
    }

    public function testUpdateProductoDatosCompletos()
    {
        if (!$this->productoIdPrueba) {
            $this->markTestSkipped('No se pudo crear producto de prueba');
        }

        $dataUpdate = [
            'nombre' => 'Producto Actualizado ' . time(),
            'descripcion' => 'Descripción actualizada',
            'unidad_medida' => 'lt',
            'precio' => 35.50,
            'idcategoria' => 1,
            'moneda' => 'BS'
        ];

        $result = $this->model->updateProducto($this->productoIdPrueba, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        
        if (isset($result['status'])) {
            $this->assertTrue($result['status']);
        }
    }

    public function testUpdateProductoSoloPrecio()
    {
        if (!$this->productoIdPrueba) {
            $this->markTestSkipped('No se pudo crear producto de prueba');
        }

        $producto = $this->model->selectProductoById($this->productoIdPrueba);
        
        if (!$producto) {
            $this->markTestSkipped('No se pudo obtener producto de prueba');
        }

        $dataUpdate = [
            'nombre' => $producto['nombre'],
            'descripcion' => $producto['descripcion'],
            'unidad_medida' => $producto['unidad_medida'],
            'precio' => 99.99,
            'idcategoria' => $producto['idcategoria'],
            'moneda' => $producto['moneda']
        ];

        $result = $this->model->updateProducto($this->productoIdPrueba, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testUpdateProductoCambioMoneda()
    {
        if (!$this->productoIdPrueba) {
            $this->markTestSkipped('No se pudo crear producto de prueba');
        }

        $producto = $this->model->selectProductoById($this->productoIdPrueba);
        
        if (!$producto) {
            $this->markTestSkipped('No se pudo obtener producto de prueba');
        }

        $nuevaMoneda = $producto['moneda'] === 'USD' ? 'BS' : 'USD';
        
        $dataUpdate = [
            'nombre' => $producto['nombre'],
            'descripcion' => $producto['descripcion'],
            'unidad_medida' => $producto['unidad_medida'],
            'precio' => $producto['precio'],
            'idcategoria' => $producto['idcategoria'],
            'moneda' => $nuevaMoneda
        ];

        $result = $this->model->updateProducto($this->productoIdPrueba, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    protected function tearDown(): void
    {
        // Limpiar: eliminar el producto de prueba
        if ($this->productoIdPrueba) {
            $this->model->deleteProductoById($this->productoIdPrueba);
        }
        $this->model = null;
    }
}
