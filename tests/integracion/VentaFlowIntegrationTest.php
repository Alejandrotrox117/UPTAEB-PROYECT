<?php
use PHPUnit\Framework\TestCase;
use App\Models\VentasModel;
use App\Models\ProductosModel;
use App\Models\ClientesModel;
use App\Models\PagosModel;

class VentaFlowIntegrationTest extends TestCase
{
    private $ventasModel;
    private $productosModel;
    private $clientesModel;
    private $pagosModel;
    private $productoIdPrueba;
    private $clienteIdPrueba;
    private $ventaIdPrueba;
    
    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "\n[INTEGRATION TEST] " . $msg . "\n");
    }
    
    protected function setUp(): void
    {
        $this->ventasModel = new VentasModel();
        $this->productosModel = new ProductosModel();
        $this->clientesModel = new ClientesModel();
        $this->pagosModel = new PagosModel();
    }
    
    public function testFlujoCompletoVentaConPagos()
    {
        $resultadoClientes = $this->clientesModel->selectAllClientes(1);
        
        if ($resultadoClientes['status'] && !empty($resultadoClientes['data'])) {
            // Buscar un cliente que realmente esté activo
            $clienteActivo = null;
            foreach ($resultadoClientes['data'] as $cliente) {
                if (isset($cliente['estatus']) && strtolower($cliente['estatus']) === 'activo') {
                    $clienteActivo = $cliente;
                    break;
                }
            }
            
            if ($clienteActivo) {
                $this->clienteIdPrueba = $clienteActivo['idcliente'];
            } else {
                $this->markTestSkipped("No hay clientes con estatus 'activo' disponibles para la prueba");
                return;
            }
        } else {
            $this->markTestSkipped("No hay clientes activos disponibles para la prueba");
            return;
        }
        
        $dataProducto = [
            'nombre' => 'Producto Venta ' . time(),
            'descripcion' => 'Producto para prueba de integración de venta',
            'unidad_medida' => 'UND',
            'precio' => 100.00,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];
        
        $resultProducto = $this->productosModel->insertProducto($dataProducto);
        $this->assertTrue($resultProducto['status'], "Fallo al crear producto");
        $this->productoIdPrueba = $resultProducto['producto_id'];
        if (isset($resultProducto['message'])) {
            $this->showMessage($resultProducto['message']);
        }
        
        $producto = $this->productosModel->selectProductoById($this->productoIdPrueba);        $precioUnitario = 95.00;
        $cantidad = 5;
        $totalVenta = $precioUnitario * $cantidad; // 475.00
        
        $datosVenta = [
            'nro_venta' => 'VENTA-INT-' . time(),
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $this->clienteIdPrueba,
            'idmoneda_general' => 3,
            'subtotal_general' => $totalVenta,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'total_general' => $totalVenta,
            'observaciones' => 'Venta de integración - ' . time(),
            'estatus' => 'BORRADOR',
            'tasa_usada' => 1
        ];
        
        $detallesVenta = [
            [
                'idproducto' => $producto['idproducto'],
                'cantidad' => $cantidad,
                'precio_unitario_venta' => $precioUnitario,
                'subtotal_general' => $totalVenta,
                'id_moneda_detalle' => 3
            ]
        ];
        
        $resultadoVenta = $this->ventasModel->insertVenta($datosVenta, $detallesVenta);
        $this->assertTrue($resultadoVenta['success'], "Fallo al crear venta: " . ($resultadoVenta['message'] ?? ''));
        $this->ventaIdPrueba = $resultadoVenta['idventa'];
        if (isset($resultadoVenta['message'])) {
            $this->showMessage($resultadoVenta['message']);
        }
        
        $this->assertNotNull($this->ventaIdPrueba, "No se obtuvo el ID de la venta creada");
        
        $montoParcial = 200.00;
        $datoPago1 = [
            'idpersona' => null,
            'idtipo_pago' => 1,
            'idventa' => $this->ventaIdPrueba,
            'idcompra' => null,
            'idsueldotemp' => null,
            'monto' => $montoParcial,
            'referencia' => 'VENTA-PAGO-' . time(),
            'fecha_pago' => date('Y-m-d'),
            'observaciones' => 'Pago parcial de venta - integración'
        ];
        
        $resultadoPago1 = $this->pagosModel->insertPago($datoPago1);
        $this->assertTrue($resultadoPago1['status'], "Fallo al registrar pago parcial");
        if (isset($resultadoPago1['message'])) {
            $this->showMessage($resultadoPago1['message']);
        }
        
        $montoRestante = $totalVenta - $montoParcial;
        $datoPago2 = [
            'idpersona' => null,
            'idtipo_pago' => 1,
            'idventa' => $this->ventaIdPrueba,
            'idcompra' => null,
            'idsueldotemp' => null,
            'monto' => $montoRestante,
            'referencia' => 'VENTA-FINAL-' . time(),
            'fecha_pago' => date('Y-m-d'),
            'observaciones' => 'Pago final de venta - integración'
        ];
        
        $resultadoPago2 = $this->pagosModel->insertPago($datoPago2);
        $this->assertTrue($resultadoPago2['status'], "Fallo al registrar pago final");
        if (isset($resultadoPago2['message'])) {
            $this->showMessage($resultadoPago2['message']);
        }
    }    public function testVentaConDescuentoMultiplesProductos()
    {
        $resultadoClientes = $this->clientesModel->selectAllClientes(1);
        if (!$resultadoClientes['status'] || empty($resultadoClientes['data'])) {
            $this->markTestSkipped("No hay clientes activos");
            return;
        }
        
        $clienteActivo = null;
        foreach ($resultadoClientes['data'] as $cliente) {
            if (isset($cliente['estatus']) && strtolower($cliente['estatus']) === 'activo') {
                $clienteActivo = $cliente;
                break;
            }
        }
        
        if (!$clienteActivo) {
            $this->markTestSkipped("No hay clientes con estatus 'activo'");
            return;
        }
        
        $clienteId = $clienteActivo['idcliente'];
        
        $producto1Data = [
            'nombre' => 'Producto A ' . time(),
            'precio' => 100.00,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];
        
        $producto2Data = [
            'nombre' => 'Producto B ' . time(),
            'precio' => 50.00,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];
        
        $p1 = $this->productosModel->insertProducto($producto1Data);
        $p2 = $this->productosModel->insertProducto($producto2Data);
        
        $this->assertTrue($p1['status'] && $p2['status'], "Fallo al crear productos");
        
        $subtotal = (100 * 2) + (50 * 3);
        $descuentoPorcentaje = 10;
        $montoDescuento = $subtotal * ($descuentoPorcentaje / 100);
        $total = $subtotal - $montoDescuento;
        
        $datosVenta = [
            'nro_venta' => 'VENTA-DESC-' . time(),
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $clienteId,
            'idmoneda_general' => 3,
            'subtotal_general' => $subtotal,
            'descuento_porcentaje_general' => $descuentoPorcentaje,
            'monto_descuento_general' => $montoDescuento,
            'total_general' => $total,
            'observaciones' => 'Venta con descuento - test',
            'estatus' => 'BORRADOR',
            'tasa_usada' => 1
        ];
        
        $detalles = [
            [
                'idproducto' => $p1['producto_id'],
                'cantidad' => 2,
                'precio_unitario_venta' => 100,
                'subtotal_general' => 200,
                'id_moneda_detalle' => 3
            ],
            [
                'idproducto' => $p2['producto_id'],
                'cantidad' => 3,
                'precio_unitario_venta' => 50,
                'subtotal_general' => 150,
                'id_moneda_detalle' => 3
            ]
        ];
        
        $resultado = $this->ventasModel->insertVenta($datosVenta, $detalles);
        $this->assertTrue($resultado['success'], "Fallo al crear venta con descuento");
        if (isset($resultado['message'])) {
            $this->showMessage($resultado['message']);
        }
    }
    
    protected function tearDown(): void
    {
        $this->ventasModel = null;
        $this->productosModel = null;
        $this->clientesModel = null;
        $this->pagosModel = null;
    }
}
