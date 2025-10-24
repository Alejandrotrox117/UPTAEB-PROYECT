<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/productosModel.php';

/**
 * Prueba de caja blanca para consultas de productos
 * Incluye casos típicos (exitosos) y atípicos (fallidos)
 */
class TestProductoSelect extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProductosModel();
    }

    // ========== CASOS TÍPICOS (EXITOSOS) ==========

    public function testSelectAllProductosRetornaArray()
    {
        $result = $this->model->selectAllProductos();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testSelectAllProductosTieneEstructuraCorrecta()
    {
        $response = $this->model->selectAllProductos();
        $productos = $response['data'] ?? [];

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
        $response = $this->model->selectProductosActivos();

        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);
        
        $productos = $response['data'] ?? [];
        foreach ($productos as $producto) {
            $this->assertEquals('ACTIVO', strtoupper($producto['estatus']));
        }
    }

    public function testSelectProductoByIdExistente()
    {
        $response = $this->model->selectAllProductos();
        $productos = $response['data'] ?? [];
        
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
        $response = $this->model->selectCategoriasActivas();

        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);
        
        $categorias = $response['data'] ?? [];
        foreach ($categorias as $categoria) {
            $this->assertEquals('activo', strtolower($categoria['estatus']));
        }
    }

    // ========== CASOS ATÍPICOS (FALLIDOS) ==========

    public function testSelectProductoByIdInexistente()
    {
        $producto = $this->model->selectProductoById(99999);

        $this->assertFalse($producto);
    }

    public function testSelectProductoByIdNegativo()
    {
        $producto = $this->model->selectProductoById(-1);

        $this->assertFalse($producto);
    }

    public function testSelectProductoByIdCero()
    {
        $producto = $this->model->selectProductoById(0);

        $this->assertFalse($producto);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
