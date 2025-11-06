<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../app/models/productosModel.php';
class TestProductoInsert extends TestCase
{
    private $model;
    private function showMessage(string $msg)
    {
        fwrite(STDOUT, "\n[MODEL MESSAGE] " . $msg . "\n");
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
    public function testInsertProductoSinCampoRequerido()
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
        $this->showMessage("Validación correcta: " . $result['message']);
    }
    public function testInsertProductoConCategoriaInexistente()
    {
        $data = [
            'nombre' => 'Producto Cat Inexistente ' . time(),
            'descripcion' => 'Categoría no existe',
            'unidad_medida' => 'kg',
            'precio' => 10.00,
            'idcategoria' => 888888 + rand(1, 99999),
            'moneda' => 'USD'
        ];
        $result = $this->model->insertProducto($data);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->showMessage("Validación correcta: " . $result['message']);
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
        $this->assertFalse($result2['status']);
        $this->showMessage("Validación correcta: " . $result2['message']);
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
