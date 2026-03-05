<?php

namespace Tests\IntegrationTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\VentasModel;
use App\Models\ProductosModel;
use App\Models\ClientesModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class EditarVentaIntegrationTest extends TestCase
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
    public function testEditarVentaExitosa()
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

        $datosVentaOriginal = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $clienteActivo['idcliente'],
            'idmoneda_general' => 3,
            'subtotal_general' => 60,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 60,
            'observaciones' => 'Venta original',
            'tasa_usada' => 1
        ];
        $detallesVentaOriginal = [
            [
                'idproducto' => $producto['idproducto'],
                'cantidad' => 3,
                'precio_unitario_venta' => 20,
                'subtotal_general' => 60,
                'id_moneda_detalle' => 3
            ]
        ];

        $resultadoInsercion = $this->ventasModel->insertVenta($datosVentaOriginal, $detallesVentaOriginal);
        $this->assertTrue($resultadoInsercion['success']);
        $idVenta = $resultadoInsercion['idventa'];

        $datosVentaEditada = [
            'observaciones' => 'Venta editada correctamente.',
            'total_general' => 125,
            'detalles' => [
                [
                    'idproducto' => $producto['idproducto'],
                    'cantidad' => 5,
                    'precio_unitario_venta' => 25,
                    'subtotal_general' => 125,
                    'id_moneda_detalle' => 3
                ]
            ]
        ];

        $resultadoEdicion = $this->ventasModel->updateVenta($idVenta, $datosVentaEditada);
        $this->assertTrue($resultadoEdicion['success']);
    }
}
