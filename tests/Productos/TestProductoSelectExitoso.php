<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/productosModel.php';

/**
 * Prueba de caja blanca para consultas exitosas de productos
 * Verifica la lectura de datos de productos
 */
class TestProductoSelectExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProductosModel();
    }

    public function testSelectAllProductosRetornaArray()
    {
        $result = $this->model->selectAllProductos();

        $this->assertIsArray($result);
    }

    public function testSelectAllProductosTieneEstructuraCorrecta()
    {
        $productos = $this->model->selectAllProductos();

        if (!empty($productos)) {
            $producto = $productos[0];
            
            $this->assertArrayHasKey('idproducto', $producto);
            $this->assertArrayHasKey('nombre', $producto);
            $this->assertArrayHasKey('descripcion', $producto);
            $this->assertArrayHasKey('precio', $producto);
            $this->assertArrayHasKey('unidad_medida', $producto);
            $this->assertArrayHasKey('idcategoria', $producto);
            $this->assertArrayHasKey('estatus', $producto);
        } else {
            $this->markTestSkipped('No hay productos para verificar estructura');
        }
    }

    public function testSelectProductosActivos()
    {
        $productos = $this->model->selectProductosActivos();

        $this->assertIsArray($productos);

        foreach ($productos as $producto) {
            $this->assertEquals('ACTIVO', strtoupper($producto['estatus']));
        }
    }

    public function testSelectProductoByIdExistente()
    {
        $productos = $this->model->selectAllProductos();
        
        if (empty($productos)) {
            $this->markTestSkipped('No hay productos para probar');
        }

        $idPrueba = $productos[0]['idproducto'];
        $producto = $this->model->selectProductoById($idPrueba);

        $this->assertIsArray($producto);
        $this->assertEquals($idPrueba, $producto['idproducto']);
    }

    public function testSelectCategoriasActivas()
    {
        $categorias = $this->model->selectCategoriasActivas();

        $this->assertIsArray($categorias);

        foreach ($categorias as $categoria) {
            $this->assertEquals('activo', strtolower($categoria['estatus']));
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
