<?php

namespace Tests\IntegrationTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\VentasModel;
use App\Models\ProductosModel;
use App\Models\ClientesModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class EliminarVentaIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private $ventasModel;
    private $productosModel;
    private $clientesModel;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->ventasModel = new VentasModel();
        $this->productosModel = new ProductosModel();
        $this->clientesModel = new ClientesModel();
    }

    #[Test]
    public function testEliminarVentaExitosa()
    {
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto);
        $resultado = $this->clientesModel->selectAllClientes();
        $clientes = $resultado['data'];
        $clienteActivo = null;
        foreach ($clientes as $c) {
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'activo') {
                $clienteActivo = $c;
                break;
            }
        }
        if (!$clienteActivo) {
            $this->markTestSkipped('No cliente activo');
        }

        $datosVenta = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $clienteActivo['idcliente'],
            'idmoneda_general' => 3,
            'subtotal_general' => 50,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 50,
            'observaciones' => 'Venta para eliminar.',
            'tasa_usada' => 1
        ];
        $detallesVenta = [
            [
                'idproducto' => $producto['idproducto'],
                'cantidad' => 5,
                'precio_unitario_venta' => 10,
                'subtotal_general' => 50,
                'id_moneda_detalle' => 3
            ]
        ];

        $resultadoInsercion = $this->ventasModel->insertVenta($datosVenta, $detallesVenta);
        $this->assertTrue($resultadoInsercion['success']);

        $idVenta = $resultadoInsercion['idventa'];
        $resultado = $this->ventasModel->eliminarVenta($idVenta);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success']);

        $ventaEliminada = $this->ventasModel->obtenerVentaPorId($idVenta);
        $this->assertEquals('inactivo', $ventaEliminada['estatus']);
    }
}
