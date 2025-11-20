<?php
use PHPUnit\Framework\TestCase;
use App\Models\ComprasModel;
use App\Models\ProductosModel;
use App\Models\ProveedoresModel;
class crearCompraTest extends TestCase
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
            'nombre' => 'Producto Compra Test ' . time(),
            'descripcion' => 'Producto para pruebas de compra',
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
    
    public function testCrearCompraExitosa()
    {
        if (!$this->productoIdPrueba || !$this->proveedorIdPrueba) {
            $this->markTestSkipped('No se pudo crear producto o proveedor de prueba');
        }
        
        $producto = $this->productosModel->selectProductoById($this->productoIdPrueba);
        $this->assertNotEmpty($producto);
        
        $precioUnitario = 10.5;
        $cantidad = 5;
        $subtotal = $precioUnitario * $cantidad;
        
        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => $this->proveedorIdPrueba,
            'idmoneda_general' => 3,
            'subtotal_general_compra' => $subtotal,
            'descuento_porcentaje_compra' => 0,
            'monto_descuento_compra' => 0,
            'total_general_compra' => $subtotal,
            'observaciones_compra' => 'Prueba unitaria de creación de compra.',
        ];
        
        $detallesCompra = [
            [
                'idproducto' => $producto['idproducto'],
                'descripcion_temporal_producto' => $producto['nombre'],
                'cantidad' => $cantidad,
                'descuento' => 0,
                'precio_unitario_compra' => $precioUnitario,
                'idmoneda_detalle' => 3,
                'subtotal_linea' => $subtotal,
                'subtotal_original_linea' => $subtotal,
                'monto_descuento_linea' => 0,
                'peso_vehiculo' => null,
                'peso_bruto' => null,
                'peso_neto' => null,
            ]
        ];
        
        $resultado = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status']);
        $this->assertArrayHasKey('id', $resultado);
        $this->assertGreaterThan(0, $resultado['id']);
        $this->showMessage("Compra creada exitosamente con ID: " . $resultado['id']);
    }
    
    public function testCrearCompraConProveedorInexistente()
    {
        if (!$this->productoIdPrueba) {
            $this->markTestSkipped('No se pudo crear producto de prueba');
        }
        
        $producto = $this->productosModel->selectProductoById($this->productoIdPrueba);
        $precioUnitario = 10.5;
        $cantidad = 5;
        $subtotal = $precioUnitario * $cantidad;
        
        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => 888888 + rand(1, 99999),
            'idmoneda_general' => 3,
            'subtotal_general_compra' => $subtotal,
            'descuento_porcentaje_compra' => 0,
            'monto_descuento_compra' => 0,
            'total_general_compra' => $subtotal,
            'observaciones_compra' => 'Prueba unitaria con proveedor inexistente.',
        ];
        
        $detallesCompra = [
            [
                'idproducto' => $producto['idproducto'],
                'descripcion_temporal_producto' => $producto['nombre'],
                'cantidad' => $cantidad,
                'descuento' => 0,
                'precio_unitario_compra' => $precioUnitario,
                'idmoneda_detalle' => 3,
                'subtotal_linea' => $subtotal,
                'subtotal_original_linea' => $subtotal,
                'monto_descuento_linea' => 0,
                'peso_vehiculo' => null,
                'peso_bruto' => null,
                'peso_neto' => null,
            ]
        ];
        
        $resultado = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);
        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->showMessage("Validación correcta: " . $resultado['message']);
    }
    
    public function testCrearCompraSinDetalles()
    {
        if (!$this->proveedorIdPrueba) {
            $this->markTestSkipped('No se pudo obtener proveedor de prueba');
        }
        
        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => $this->proveedorIdPrueba,
            'idmoneda_general' => 3,
            'total_general_compra' => 0,
            'observaciones_compra' => 'Prueba de compra sin detalles.',
        ];
        
        $detallesCompra = [];
        
        $resultado = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);
        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->showMessage("Validación correcta: " . $resultado['message']);
    }
    
    public function testCrearCompraConCantidadCero()
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
            'total_general_compra' => 0,
            'observaciones_compra' => 'Prueba con cantidad cero.',
        ];
        
        $detallesCompra = [
            [
                'idproducto' => $producto['idproducto'],
                'descripcion_temporal_producto' => $producto['nombre'],
                'cantidad' => 0,
                'precio_unitario_compra' => 10,
                'idmoneda_detalle' => 3,
                'subtotal_linea' => 0,
            ]
        ];
        
        $resultado = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);
        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->showMessage("Validación correcta: " . $resultado['message']);
    }
    
    public function testCrearCompraConProductoInexistente()
    {
        if (!$this->proveedorIdPrueba) {
            $this->markTestSkipped('No se pudo obtener proveedor de prueba');
        }
        
        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => $this->proveedorIdPrueba,
            'idmoneda_general' => 3,
            'total_general_compra' => 100,
            'observaciones_compra' => 'Prueba con producto inexistente.',
        ];
        
        $detallesCompra = [
            [
                'idproducto' => 888888 + rand(1, 99999),
                'descripcion_temporal_producto' => 'Producto Fantasma',
                'cantidad' => 10,
                'precio_unitario_compra' => 10,
                'idmoneda_detalle' => 3,
                'subtotal_linea' => 100,
            ]
        ];
        
        $resultado = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);
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
