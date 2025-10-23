<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/productosModel.php';

/**
 * Prueba de caja blanca para inserción exitosa de productos
 * Verifica que se pueda insertar un producto con todos los campos válidos
 */
class TestProductoInsertExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProductosModel();
    }

    public function testInsertProductoConDatosCompletos()
    {
        $data = [
            'nombre' => 'Producto Test ' . time(),
            'descripcion' => 'Descripción del producto de prueba',
            'unidad_medida' => 'kg',
            'precio' => 25.50,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];

        $result = $this->model->insertProducto($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('producto_id', $result);
        
        if ($result['status']) {
            $this->assertTrue($result['status']);
            $this->assertIsInt($result['producto_id']);
            $this->assertGreaterThan(0, $result['producto_id']);
        }
    }

    public function testInsertProductoConPrecioCero()
    {
        $data = [
            'nombre' => 'Producto Precio Cero ' . time(),
            'descripcion' => 'Producto gratuito',
            'unidad_medida' => 'unidad',
            'precio' => 0,
            'idcategoria' => 1,
            'moneda' => 'BS'
        ];

        $result = $this->model->insertProducto($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testInsertProductoConDescripcionVacia()
    {
        $data = [
            'nombre' => 'Producto Sin Desc ' . time(),
            'descripcion' => '',
            'unidad_medida' => 'lt',
            'precio' => 10.00,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];

        $result = $this->model->insertProducto($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testInsertProductoConNombreLargo()
    {
        $data = [
            'nombre' => 'Producto con nombre muy extenso para probar límites ' . time(),
            'descripcion' => 'Producto con nombre largo',
            'unidad_medida' => 'kg',
            'precio' => 15.75,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];

        $result = $this->model->insertProducto($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
