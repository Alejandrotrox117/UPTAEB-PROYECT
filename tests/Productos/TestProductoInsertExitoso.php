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
            'nombre' => 'Cartón Corrugado Mixto ' . time(),
            'descripcion' => 'Cartón corrugado recibido de recolectores, sin clasificar, contiene material mezclado',
            'unidad_medida' => 'KG',
            'precio' => 0.15,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];

        $result = $this->model->insertProducto($data);

        $this->assertIsArray($result, 'El resultado debe ser un array');
        $this->assertArrayHasKey('status', $result, 'Debe contener clave status');
        $this->assertArrayHasKey('message', $result, 'Debe contener clave message');
        $this->assertArrayHasKey('producto_id', $result, 'Debe contener clave producto_id');
        
        if ($result['status']) {
            $this->assertTrue($result['status'], 'El estatus debe ser true para inserción exitosa');
            $this->assertEquals('Producto registrado exitosamente.', $result['message'], 
                'El mensaje debe ser exactamente el que retorna el modelo');
            $this->assertIsInt($result['producto_id'], 'El ID del producto debe ser un entero');
            $this->assertGreaterThan(0, $result['producto_id'], 'El ID debe ser mayor a 0');
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

        $this->assertIsArray($result, 'El resultado debe ser un array');
        $this->assertArrayHasKey('status', $result, 'Debe contener clave status');
        
        if ($result['status']) {
            $this->assertEquals('Producto registrado exitosamente.', $result['message'],
                'Debe retornar el mensaje exacto del modelo incluso con precio 0');
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

        $this->assertIsArray($result, 'El resultado debe ser un array');
        $this->assertArrayHasKey('status', $result, 'Debe contener clave status');
        
        if ($result['status']) {
            $this->assertEquals('Producto registrado exitosamente.', $result['message'],
                'Debe permitir descripción vacía y retornar mensaje del modelo');
        }
    }

    public function testInsertProductoConNombreLargo()
    {
        $data = [
            'nombre' => 'Paca de Cartón Corrugado Calidad Premium para Exportación Industrial Compactada 30kg ' . time(),
            'descripcion' => 'Paca de cartón corrugado de alta calidad, limpio, libre de contaminantes, compactado según estándares internacionales',
            'unidad_medida' => 'KG',
            'precio' => 10.00,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];

        $result = $this->model->insertProducto($data);

        $this->assertIsArray($result, 'El resultado debe ser un array');
        $this->assertArrayHasKey('status', $result, 'Debe contener clave status');
        
        if ($result['status']) {
            $this->assertEquals('Producto registrado exitosamente.', $result['message'],
                'Debe insertar productos con nombres largos descriptivos del negocio');
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
