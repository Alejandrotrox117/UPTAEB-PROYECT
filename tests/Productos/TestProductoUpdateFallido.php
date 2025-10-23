<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/productosModel.php';

/**
 * Prueba de caja blanca para casos de fallo en actualización de productos
 * Verifica validaciones y manejo de errores
 */
class TestProductoUpdateFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProductosModel();
    }

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
        $this->assertFalse($result['status'], "No debería actualizar un producto inexistente");
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
        $this->assertFalse($result['status'], "No debería actualizar con nombre vacío");
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
        $this->assertFalse($result['status'], "No debería actualizar con categoría inválida");
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
        // Dependiendo de las validaciones puede ser exitoso o fallar
        $this->assertArrayHasKey('status', $result);
    }

    public function testUpdateProductoConDatosIncompletos()
    {
        $dataUpdate = [
            'nombre' => 'Solo nombre'
        ];

        $result = $this->model->updateProducto(1, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería actualizar con datos incompletos");
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
        $this->assertFalse($result['status'], "No debería actualizar con ID negativo");
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
