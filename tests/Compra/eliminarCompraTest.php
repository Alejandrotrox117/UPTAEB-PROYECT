<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../app/models/ComprasModel.php';
require_once __DIR__ . '/../../app/models/productosModel.php';
require_once __DIR__ . '/../../app/models/proveedoresModel.php';
class eliminarCompraTest extends TestCase
{
    private $comprasModel;
    private $productosModel;
    private $proveedoresModel;
    private $productoIdPrueba;
    private $proveedorIdPrueba;
    
    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "\n[MODEL MESSAGE] " . $msg . "\n");
    }
    
    public function setUp(): void
    {
        $this->comprasModel = new ComprasModel();
        $this->productosModel = new ProductosModel();
        $this->proveedoresModel = new ProveedoresModel();
        
        // Crear producto de prueba
        $dataProducto = [
            'nombre' => 'Producto Eliminar Test ' . time(),
            'descripcion' => 'Producto para pruebas de eliminar compra',
            'unidad_medida' => 'KG',
            'precio' => 10.00,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];
        $resultProducto = $this->productosModel->insertProducto($dataProducto);
        if ($resultProducto['status']) {
            $this->productoIdPrueba = $resultProducto['producto_id'];
        }
        
        // Obtener proveedor existente
        $resultadoProveedores = $this->proveedoresModel->selectAllProveedores(1);
        if ($resultadoProveedores['status'] && !empty($resultadoProveedores['data'])) {
            $this->proveedorIdPrueba = $resultadoProveedores['data'][0]['idproveedor'];
        }
    }
    
    public function testEliminarCompraConIdInexistente()
    {
        $resultado = $this->comprasModel->deleteCompraById(888888 + rand(1, 99999));
        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->showMessage("Validación correcta: " . $resultado['message']);
    }
    
    protected function tearDown(): void
    {
        $this->comprasModel = null;
        $this->productosModel = null;
        $this->proveedoresModel = null;
    }
}
