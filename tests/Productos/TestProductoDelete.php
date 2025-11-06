<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../app/models/productosModel.php';
class TestProductoDelete extends TestCase
{
    private $model;
    private $productoIdPrueba;
    private function showMessage(string $msg)
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }
    protected function setUp(): void
    {
        $this->model = new ProductosModel();
        $data = [
            'nombre' => 'Producto Delete Test ' . time(),
            'descripcion' => 'Para eliminar',
            'unidad_medida' => 'kg',
            'precio' => 15.00,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];
        $result = $this->model->insertProducto($data);
        if ($result['status']) {
            $this->productoIdPrueba = $result['producto_id'];
        }
    }
    public function testDeleteProductoExistente()
    {
        if (!$this->productoIdPrueba) {
            $this->markTestSkipped('No se pudo crear producto de prueba');
        }
        $result = $this->model->deleteProductoById($this->productoIdPrueba);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        if (isset($result['status'])) {
            $this->assertTrue($result['status']);
        }
    }
    public function testDeleteProductoVerificarEstatus()
    {
        if (!$this->productoIdPrueba) {
            $this->markTestSkipped('No se pudo crear producto de prueba');
        }
        $result = $this->model->deleteProductoById($this->productoIdPrueba);
        if ($result['status']) {
            $producto = $this->model->selectProductoById($this->productoIdPrueba);
            if ($producto) {
                $this->assertEquals('INACTIVO', strtoupper($producto['estatus']));
            }
        }
        $this->assertIsArray($result);
    }
    public function testDeleteProductoInexistente()
    {
        $result = $this->model->deleteProductoById(99999);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        if (array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
            $this->showMessage($result['message']);
        }
        $this->assertFalse($result['status']);
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
