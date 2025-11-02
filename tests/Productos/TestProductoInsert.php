<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/productosModel.php';





class TestProductoInsert extends TestCase
{
    private $model;

    private function showMessage(string $msg)
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }

    protected function setUp(): void
    {
        $this->model = new ProductosModel();
    }

    

    public function testInsertProductoConDatosCompletos()
    {
        $data = [
            'nombre' => 'Cartón Corrugado Mixto ' . time(),
            'descripcion' => 'Cartón corrugado recibido de recolectores, sin clasificar, contiene material mezclado',
            'unidad_medida' => 'KG',
            'precio' => 0.15,
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
            $this->assertEquals('Producto registrado exitosamente.', $result['message']);
            $this->assertIsInt($result['producto_id']);
            $this->assertGreaterThan(0, $result['producto_id']);
        }
    }

    public function testInsertProductoConPrecioCero()
    {
        $data = [
            'nombre' => 'Material Contaminante para Desecho ' . time(),
            'descripcion' => 'Material separado durante clasificación que no tiene valor comercial',
            'unidad_medida' => 'KG',
            'precio' => 0,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];

        $result = $this->model->insertProducto($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        
        if ($result['status']) {
            $this->assertEquals('Producto registrado exitosamente.', $result['message']);
        }
    }

    public function testInsertProductoConDescripcionVacia()
    {
        $data = [
            'nombre' => 'Plástico PET Transparente ' . time(),
            'descripcion' => '',
            'unidad_medida' => 'KG',
            'precio' => 0.35,
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
            'nombre' => 'Paca de Cartón Corrugado Calidad Premium para Exportación Industrial Compactada 30kg ' . time(),
            'descripcion' => 'Paca de cartón corrugado de alta calidad, limpio, libre de contaminantes',
            'unidad_medida' => 'KG',
            'precio' => 10.00,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];

        $result = $this->model->insertProducto($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
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
        if (array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
            $this->showMessage($result['message']);
        }
        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
    }

    public function testInsertProductoConPrecioNegativo()
    {
        $data = [
            'nombre' => 'Producto Precio Negativo ' . time(),
            'descripcion' => 'Precio inválido',
            'unidad_medida' => 'kg',
            'precio' => -10.00,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];

        $result = $this->model->insertProducto($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testInsertProductoConCategoriaInexistente()
    {
        $data = [
            'nombre' => 'Producto Cat Inexistente ' . time(),
            'descripcion' => 'Categoría no existe',
            'unidad_medida' => 'kg',
            'precio' => 10.00,
            'idcategoria' => 99999,
            'moneda' => 'USD'
        ];

        $result = $this->model->insertProducto($data);

        $this->assertIsArray($result);
        if (array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
            $this->showMessage($result['message']);
        }
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
        if (array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
            $this->showMessage($result['message']);
        }
        $this->assertFalse($result['status']);
    }

    public function testInsertProductoDuplicado()
    {
        $nombreUnico = 'Producto Duplicado Test ' . time();
        
        $data = [
            'nombre' => $nombreUnico,
            'descripcion' => 'Primera inserción',
            'unidad_medida' => 'kg',
            'precio' => 10.00,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];

        $result1 = $this->model->insertProducto($data);
        $result2 = $this->model->insertProducto($data);

        $this->assertIsArray($result2);
        if (array_key_exists('status', $result2) && $result2['status'] === false) {
            $this->assertArrayHasKey('message', $result2);
            $this->showMessage($result2['message']);
        }
        $this->assertFalse($result2['status']);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
