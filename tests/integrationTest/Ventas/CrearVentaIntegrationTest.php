<?php

namespace Tests\IntegrationTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\VentasModel;
use App\Models\ProductosModel;
use App\Models\ClientesModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class CrearVentaIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private $ventasModel;
    private $productosModel;
    private $clientesModel;

    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "\n[MODEL MESSAGE] " . $msg . "\n");
    }

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->ventasModel = new VentasModel();
        $this->productosModel = new ProductosModel();
        $this->clientesModel = new ClientesModel();
    }

    #[Test]
    public function testCrearVentaExitosa()
    {
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto);
        $resultado = $this->clientesModel->selectAllClientes();
        $this->assertNotEmpty($resultado['data']);
        $clientes = $resultado['data'];
        $clienteActivo = null;
        foreach ($clientes as $c) {
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'activo') {
                $clienteActivo = $c;
                break;
            }
        }
        if (!$clienteActivo) {
            $this->markTestSkipped('No hay cliente activo');
        }

        $precioUnitario = 20.0;
        $cantidad = 3;
        $subtotal = $precioUnitario * $cantidad;
        $datosVenta = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $clienteActivo['idcliente'],
            'idmoneda_general' => 3,
            'subtotal_general' => $subtotal,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => $subtotal,
            'observaciones' => 'Prueba de caja blanca',
            'tasa_usada' => 1
        ];
        $detallesVenta = [
            [
                'idproducto' => $producto['idproducto'],
                'cantidad' => $cantidad,
                'precio_unitario_venta' => $precioUnitario,
                'subtotal_general' => $subtotal,
                'id_moneda_detalle' => 3
            ]
        ];

        $resultado = $this->ventasModel->insertVenta($datosVenta, $detallesVenta);
        $this->assertTrue($resultado['success']);
        $this->assertArrayHasKey('idventa', $resultado);
    }
}
