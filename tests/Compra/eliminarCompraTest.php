<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../app/models/ComprasModel.php';
require_once __DIR__ . '/../app/models/productosModel.php';
require_once __DIR__ . '/../app/models/proveedoresModel.php';

class eliminarCompraTest extends TestCase
{
    private $comprasModel;
    private $productosModel;
    private $proveedoresModel;

    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }

    public function setUp(): void
    {
        $this->comprasModel = new ComprasModel();
        $this->productosModel = new ProductosModel();
        $this->proveedoresModel = new ProveedoresModel();
    }

    public function testEliminarCompraExitosa()
    {
        
        $producto = $this->productosModel->selectProductoById(1);
        $resultadoProveedores = $this->proveedoresModel->selectAllProveedores(1);
        $proveedor = $resultadoProveedores['data'][0];

        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => $proveedor['idproveedor'],
            'idmoneda_general' => 3,
            'subtotal_general_compra' => 50,
            'descuento_porcentaje_compra' => 0,
            'monto_descuento_compra' => 0,
            'total_general_compra' => 50,
            'observaciones_compra' => 'Compra para eliminar.',
        ];
        $detallesCompra = [
            [
                'idproducto' => $producto['idproducto'],
                'descripcion_temporal_producto' => $producto['nombre'],
                'cantidad' => 5,
                'descuento' => 0,
                'precio_unitario_compra' => 10,
                'idmoneda_detalle' => 3,
                'subtotal_linea' => 50,
                'subtotal_original_linea' => 50,
                'monto_descuento_linea' => 0,
                'peso_vehiculo' => null,
                'peso_bruto' => null,
                'peso_neto' => null,
            ]
        ];
        $resultadoInsercion = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);
        $this->assertTrue($resultadoInsercion['status'], "La inserción inicial de la compra falló.");
        $idCompra = $resultadoInsercion['id'];

        
        $resultado = $this->comprasModel->deleteCompraById($idCompra);
        
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status'], "La eliminación de la compra debería ser exitosa: " . ($resultado['message'] ?? 'Error desconocido'));
        echo $resultado['message'];

        $compraEliminada = $this->comprasModel->getCompraById($idCompra);
        $this->assertEquals('inactivo', $compraEliminada['estatus_compra'], "La compra no fue marcada como inactivo correctamente.");
    }

    public function testNoSePuedeEliminarCompraSiNoEstaEnBorrador()
    {
        
        $producto = $this->productosModel->selectProductoById(1);
        $resultadoProveedores = $this->proveedoresModel->selectAllProveedores(1);
        $proveedor = $resultadoProveedores['data'][0];
        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => $proveedor['idproveedor'],
            'idmoneda_general' => 3, 'total_general_compra' => 50,
            'observaciones_compra' => 'Compra para prueba de eliminación fallida.',
        ];
        $detallesCompra = [[
            'idproducto' => $producto['idproducto'], 'cantidad' => 5, 'precio_unitario_compra' => 10,
            'descripcion_temporal_producto' => $producto['nombre'], 'idmoneda_detalle' => 3, 'subtotal_linea' => 50,
            'peso_vehiculo' => null, 'peso_bruto' => null, 'peso_neto' => null,
        ]];
        $resultadoInsercion = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);
        $this->assertTrue($resultadoInsercion['status']);
        $idCompra = $resultadoInsercion['id'];

        
        $this->comprasModel->cambiarEstadoCompra($idCompra, 'POR_AUTORIZAR');

        
        $resultado = $this->comprasModel->deleteCompraById($idCompra);

        
        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status'], "El sistema no debería permitir eliminar una compra que no está en estado BORRADOR.");
        $this->assertEquals('La compra solo se puede eliminar si su estado es BORRADOR.', $resultado['message']);
        fwrite(STDERR, "Mensaje de error verificado: " . $resultado['message'] . "\n");

        
        $compra = $this->comprasModel->getCompraById($idCompra);
        $this->assertEquals('POR_AUTORIZAR', $compra['estatus_compra'], "El estado de la compra no debería haber cambiado.");
    }
}
