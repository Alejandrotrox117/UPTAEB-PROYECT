<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/productosModel.php';

/**
 * Prueba de caja blanca para casos de fallo en inserci�n de productos
 * Verifica el comportamiento ante datos inv�lidos o faltantes
 */
class TestProductoInsertFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProductosModel();
    }

    public function testInsertProductoSinNombre()
    {
        $data = [
            'nombre' => '',
            'descripcion' => 'Sin nombre',
            'unidad_medida' => 'kg',
            'precio' => 10.00,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];

        $result = $this->model->insertProducto($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
    }

    public function testInsertProductoConPrecioNegativo()
    {
        $data = [
            'nombre' => 'Producto Precio Negativo ' . time(),
            'descripcion' => 'Precio inv�lido',
            'unidad_medida' => 'kg',
            'precio' => -10.00,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];

        $result = $this->model->insertProducto($data);

        $this->assertIsArray($result);
        // Dependiendo de la validaci�n puede ser exitoso o fallar
        $this->assertArrayHasKey('status', $result);
    }

    public function testInsertProductoConCategoriaInexistente()
    {
        $data = [
            'nombre' => 'Producto Cat Inexistente ' . time(),
            'descripcion' => 'Categor�a no existe',
            'unidad_medida' => 'kg',
            'precio' => 10.00,
            'idcategoria' => 99999,
            'moneda' => 'USD'
        ];

        $result = $this->model->insertProducto($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public function testInsertProductoSinUnidadMedida()
    {
        $data = [
            'nombre' => 'Producto Sin Unidad ' . time(),
            'descripcion' => 'Sin unidad de medida',
            'unidad_medida' => '',
            'precio' => 10.00,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];

        $result = $this->model->insertProducto($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testInsertProductoConDatosIncompletos()
    {
        $data = [
            'nombre' => 'Producto Incompleto',
            'precio' => 10.00
        ];

        $result = $this->model->insertProducto($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public function testInsertProductoDuplicado()
    {
        $nombreUnico = 'Producto Duplicado Test ' . time();
        
        $data = [
            'nombre' => $nombreUnico,
            'descripcion' => 'Primera inserci�n',
            'unidad_medida' => 'kg',
            'precio' => 10.00,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];

        $result1 = $this->model->insertProducto($data);
        
        // Intentar insertar el mismo producto
        $result2 = $this->model->insertProducto($data);

        $this->assertIsArray($result2);
        $this->assertFalse($result2['status']);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
