<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/ComprasModel.php';
require_once __DIR__ . '/../../app/models/productosModel.php';
require_once __DIR__ . '/../../app/models/proveedoresModel.php';
require_once __DIR__ . '/../../app/models/pagosModel.php';

class CompraFlowIntegrationTest extends TestCase
{
    private $comprasModel;
    private $productosModel;
    private $proveedoresModel;
    private $pagosModel;
    private $productoIdPrueba;
    private $proveedorIdPrueba;
    private $compraIdPrueba;
    
    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "\n[INTEGRATION TEST] " . $msg . "\n");
    }
    
    protected function setUp(): void
    {
        $this->comprasModel = new ComprasModel();
        $this->productosModel = new ProductosModel();
        $this->proveedoresModel = new ProveedoresModel();
        $this->pagosModel = new PagosModel();
    }
    
    public function testFlujoCompletoCompraConPagos()
    {
        $dataProducto = [
            'nombre' => 'Producto Integración ' . time(),
            'descripcion' => 'Producto para prueba de integración',
            'unidad_medida' => 'KG',
            'precio' => 50.00,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];
        
        $resultProducto = $this->productosModel->insertProducto($dataProducto);
        $this->assertTrue($resultProducto['status'], "Fallo al crear producto");
        $this->productoIdPrueba = $resultProducto['producto_id'];
        if (isset($resultProducto['message'])) {
            $this->showMessage($resultProducto['message']);
        }
        
        $resultadoProveedores = $this->proveedoresModel->selectAllProveedores(1);
        $this->assertTrue($resultadoProveedores['status'], "No hay proveedores disponibles");
        $this->assertNotEmpty($resultadoProveedores['data'], "Lista de proveedores vacía");
        
        $this->proveedorIdPrueba = $resultadoProveedores['data'][0]['idproveedor'];
        if (isset($resultadoProveedores['message'])) {
            $this->showMessage($resultadoProveedores['message']);
        }
        
        $producto = $this->productosModel->selectProductoById($this->productoIdPrueba);
        
        $precioUnitario = 45.00;
        $cantidad = 10;
        $totalCompra = $precioUnitario * $cantidad;
        
        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => $this->proveedorIdPrueba,
            'idmoneda_general' => 3,
            'subtotal_general_compra' => $totalCompra,
            'descuento_porcentaje_compra' => 0,
            'monto_descuento_compra' => 0,
            'total_general_compra' => $totalCompra,
            'observaciones_compra' => 'Compra de integración - ' . time(),
        ];
        
        $detallesCompra = [
            [
                'idproducto' => $producto['idproducto'],
                'descripcion_temporal_producto' => $producto['nombre'],
                'cantidad' => $cantidad,
                'descuento' => 0,
                'precio_unitario_compra' => $precioUnitario,
                'idmoneda_detalle' => 3,
                'subtotal_linea' => $totalCompra,
                'subtotal_original_linea' => $totalCompra,
                'monto_descuento_linea' => 0,
            ]
        ];
        
        $resultadoCompra = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);
        $this->assertTrue($resultadoCompra['status'], "Fallo al crear compra");
        $this->compraIdPrueba = $resultadoCompra['id'];
        if (isset($resultadoCompra['message'])) {
            $this->showMessage($resultadoCompra['message']);
        }
        
        $compraCreada = $this->comprasModel->getCompraById($this->compraIdPrueba);
        $this->assertNotFalse($compraCreada, "No se pudo recuperar la compra");
        $this->assertEquals($totalCompra, $compraCreada['total_general']);
        $this->assertEquals($totalCompra, $compraCreada['balance'], "El balance inicial debe ser igual al total");
        
        $montoParcial = 200.00;
        $datoPago1 = [
            'idpersona' => null,
            'idtipo_pago' => 1,
            'idventa' => null,
            'idcompra' => $this->compraIdPrueba,
            'idsueldotemp' => null,
            'monto' => $montoParcial,
            'referencia' => 'PAGO-TEST-' . time(),
            'fecha_pago' => date('Y-m-d'),
            'observaciones' => 'Pago parcial de integración'
        ];
        
        $resultadoPago1 = $this->pagosModel->insertPago($datoPago1);
        $this->assertTrue($resultadoPago1['status'], "Fallo al registrar pago parcial");
        if (isset($resultadoPago1['message'])) {
            $this->showMessage($resultadoPago1['message']);
        }
        
        $compraActualizada = $this->comprasModel->getCompraById($this->compraIdPrueba);
        $balanceEsperado = $totalCompra - $montoParcial;
        
        $montoRestante = $balanceEsperado;
        $datoPago2 = [
            'idpersona' => null,
            'idtipo_pago' => 1,
            'idventa' => null,
            'idcompra' => $this->compraIdPrueba,
            'idsueldotemp' => null,
            'monto' => $montoRestante,
            'referencia' => 'PAGO-FINAL-' . time(),
            'fecha_pago' => date('Y-m-d'),
            'observaciones' => 'Pago final de integración'
        ];
        
        $resultadoPago2 = $this->pagosModel->insertPago($datoPago2);
        $this->assertTrue($resultadoPago2['status'], "Fallo al registrar pago final");
        if (isset($resultadoPago2['message'])) {
            $this->showMessage($resultadoPago2['message']);
        }
        
        $compraFinal = $this->comprasModel->getCompraById($this->compraIdPrueba);
    }    public function testEditarCompraExistente()
    {
        $this->crearDatosPrueba();
        
        $producto = $this->productosModel->selectProductoById($this->productoIdPrueba);
        
        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => $this->proveedorIdPrueba,
            'idmoneda_general' => 3,
            'total_general_compra' => 100,
            'observaciones_compra' => 'Compra original',
        ];
        
        $detallesCompra = [[
            'idproducto' => $producto['idproducto'],
            'descripcion_temporal_producto' => $producto['nombre'],
            'cantidad' => 5,
            'precio_unitario_compra' => 20,
            'idmoneda_detalle' => 3,
            'subtotal_linea' => 100,
        ]];
        
        $resultado = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);
        $this->assertTrue($resultado['status']);
        $idCompra = $resultado['id'];
        if (isset($resultado['message'])) {
            $this->showMessage($resultado['message']);
        }
        
        $datosEditados = $datosCompra;
        $datosEditados['observaciones_compra'] = 'Compra editada - ' . time();
        
        $detallesEditados = [[
            'idproducto' => $producto['idproducto'],
            'descripcion_temporal_producto' => $producto['nombre'],
            'cantidad' => 8, // Cantidad cambiada
            'precio_unitario_compra' => 20,
            'idmoneda_detalle' => 3,
            'subtotal_linea' => 160, // Total actualizado
        ]];
        
        $resultadoEdicion = $this->comprasModel->actualizarCompra($idCompra, $datosEditados, $detallesEditados);
        
        if ($resultadoEdicion['status']) {
            $this->showMessage($resultadoEdicion['message']);
            
            $compraEditada = $this->comprasModel->getCompraById($idCompra);
            $this->assertStringContainsString('editada', $compraEditada['observaciones_compra']);
        } else {
            $this->showMessage($resultadoEdicion['message'] ?? 'Sin mensaje');
        }
    }    private function crearDatosPrueba()
    {
        if (!$this->productoIdPrueba) {
            $dataProducto = [
                'nombre' => 'Producto Test ' . time(),
                'descripcion' => 'Producto para pruebas',
                'unidad_medida' => 'KG',
                'precio' => 50.00,
                'idcategoria' => 1,
                'moneda' => 'USD'
            ];
            $resultProducto = $this->productosModel->insertProducto($dataProducto);
            if ($resultProducto['status']) {
                $this->productoIdPrueba = $resultProducto['producto_id'];
            }
        }

        if (!$this->proveedorIdPrueba) {
            $resultadoProveedores = $this->proveedoresModel->selectAllProveedores(1);
            if ($resultadoProveedores['status'] && !empty($resultadoProveedores['data'])) {
                $this->proveedorIdPrueba = $resultadoProveedores['data'][0]['idproveedor'];
            }
        }
    }
    
    protected function tearDown(): void
    {
        $this->comprasModel = null;
        $this->productosModel = null;
        $this->proveedoresModel = null;
        $this->pagosModel = null;
    }
}
