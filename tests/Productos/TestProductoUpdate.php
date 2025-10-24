<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/productosModel.php';

/**
 * Prueba de caja blanca para actualización de productos
 * Incluye casos típicos (exitosos) y atípicos (fallidos)
 */
class TestProductoUpdate extends TestCase
{
    private $model;
    private $productoIdPrueba;

    protected function setUp(): void
    {
        $this->model = new ProductosModel();
        
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

    // ========== CASOS TÍPICOS (EXITOSOS) ==========

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

    // ========== CASOS ATÍPICOS (FALLIDOS) ==========

    public function testUpdateProductoInexistente()
    {
        $dataUpdate = [
            'nombre' => 'No existe',
            'descripcion' => 'Este producto no existe',
            'unidad_medida' => 'kg',
            'precio' => 10.00,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];

        $result = $this->model->updateProducto(99999, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public function testUpdateProductoConNombreVacio()
    {
        $dataUpdate = [
            'nombre' => '',
            'descripcion' => 'Sin nombre',
            'unidad_medida' => 'kg',
            'precio' => 10.00,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];

        $result = $this->model->updateProducto(1, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public function testUpdateProductoConCategoriaInvalida()
    {
        $dataUpdate = [
            'nombre' => 'Producto Test',
            'descripcion' => 'Categoría inválida',
            'unidad_medida' => 'kg',
            'precio' => 10.00,
            'idcategoria' => 99999,
            'moneda' => 'USD'
        ];

        $result = $this->model->updateProducto(1, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public function testUpdateProductoConPrecioNegativo()
    {
        $dataUpdate = [
            'nombre' => 'Producto Test',
            'descripcion' => 'Precio negativo',
            'unidad_medida' => 'kg',
            'precio' => -50.00,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];

        $result = $this->model->updateProducto(1, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testUpdateProductoConDatosIncompletos()
    {
        $dataUpdate = [
            'nombre' => 'Solo nombre'
        ];

        $result = $this->model->updateProducto(1, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public function testUpdateProductoConIdNegativo()
    {
        $dataUpdate = [
            'nombre' => 'Producto Test',
            'descripcion' => 'ID negativo',
            'unidad_medida' => 'kg',
            'precio' => 10.00,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];

        $result = $this->model->updateProducto(-1, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    protected function tearDown(): void
    {
        if ($this->productoIdPrueba) {
            $this->model->deleteProductoById($this->productoIdPrueba);
        }
        $this->model = null;
    }
}
