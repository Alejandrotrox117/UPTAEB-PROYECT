<?php
use PHPUnit\Framework\TestCase;
use App\Models\ComprasModel;
use App\Models\ProductosModel;
use App\Models\ProveedoresModel;
class editarCompraTest extends TestCase
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
            'nombre' => 'Producto Editar Test ' . time(),
            'descripcion' => 'Producto para pruebas de editar compra',
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
    
    public function testEditarCompraConProveedorInexistente()
    {
        if (!$this->productoIdPrueba || !$this->proveedorIdPrueba) {
            $this->markTestSkipped('No se pudo crear datos de prueba');
        }
        
        $producto = $this->productosModel->selectProductoById($this->productoIdPrueba);
        
        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => $this->proveedorIdPrueba,
            'idmoneda_general' => 3,
            'total_general_compra' => 50,
            'observaciones_compra' => 'Compra original para prueba de edición.',
        ];
        $detallesCompra = [[
            'idproducto' => $producto['idproducto'],
            'descripcion_temporal_producto' => $producto['nombre'],
            'cantidad' => 5, 'precio_unitario_compra' => 10,
            'idmoneda_detalle' => 3, 'subtotal_linea' => 50,
        ]];
        $resultadoInsercion = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);
        if (!$resultadoInsercion['status']) {
            $this->markTestSkipped('No se pudo crear compra de prueba');
        }
        $idCompra = $resultadoInsercion['id'];
        $datosEditados = $datosCompra;
        $datosEditados['idproveedor'] = 888888 + rand(1, 99999); 
        $resultado = $this->comprasModel->actualizarCompra($idCompra, $datosEditados, $detallesCompra);
        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertStringContainsString("proveedor", strtolower($resultado['message']));
        $this->showMessage("Validación correcta: " . $resultado['message']);
    }
    
    public function testEditarCompraConProductoInexistente()
    {
        if (!$this->productoIdPrueba || !$this->proveedorIdPrueba) {
            $this->markTestSkipped('No se pudo crear datos de prueba');
        }
        
        $producto = $this->productosModel->selectProductoById($this->productoIdPrueba);
        
        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => $this->proveedorIdPrueba,
            'idmoneda_general' => 3, 'total_general_compra' => 50,
            'observaciones_compra' => 'Compra original.',
        ];
        $detallesCompra = [[
            'idproducto' => $producto['idproducto'], 'cantidad' => 5, 'precio_unitario_compra' => 10,
            'descripcion_temporal_producto' => $producto['nombre'], 'idmoneda_detalle' => 3, 'subtotal_linea' => 50,
        ]];
        $resultadoInsercion = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);
        if (!$resultadoInsercion['status']) {
            $this->markTestSkipped('No se pudo crear compra de prueba');
        }
        $idCompra = $resultadoInsercion['id'];
        $detallesEditados = $detallesCompra;
        $detallesEditados[0]['idproducto'] = 888888 + rand(1, 99999); 
        $resultado = $this->comprasModel->actualizarCompra($idCompra, $datosCompra, $detallesEditados);
        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertStringContainsString("producto", strtolower($resultado['message']));
        $this->showMessage("Validación correcta: " . $resultado['message']);
    }
    
    protected function tearDown(): void
    {
        $this->comprasModel = null;
        $this->productosModel = null;
        $this->proveedoresModel = null;
    }
}
