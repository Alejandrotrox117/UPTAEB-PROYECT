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

    private function getActivoCliente()
    {
        $resultado = $this->clientesModel->selectAllClientes();
        $clientes = $resultado['data'] ?? [];
        foreach ($clientes as $c) {
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'activo')
                return $c;
        }
        return null;
    }

    #[Test]
    public function testEditarVentaExitosa()
    {
        $producto = $this->productosModel->selectProductoById(1);
        $clienteActivo = $this->getActivoCliente();

        if (!$clienteActivo || !$producto)
            $this->markTestSkipped('Faltan dependencias en bd');

        $datosVentaOriginal = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $clienteActivo['idcliente'],
            'idmoneda_general' => 3,
            'subtotal_general' => 60,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 60
        ];
        $detallesVentaOriginal = [['idproducto' => $producto['idproducto'], 'cantidad' => 3, 'precio_unitario_venta' => 20]];

        $resultadoInsercion = $this->ventasModel->insertVenta($datosVentaOriginal, $detallesVentaOriginal);
        $this->assertTrue($resultadoInsercion['success']);

        $idVenta = $resultadoInsercion['idventa'];

        $datosVentaEditada = [
            'observaciones' => 'Venta editada',
            'total_general' => 125,
            'detalles' => [['idproducto' => $producto['idproducto'], 'cantidad' => 5, 'precio_unitario_venta' => 25]]
        ];

        $resultadoEdicion = $this->ventasModel->updateVenta($idVenta, $datosVentaEditada);
        $this->assertTrue($resultadoEdicion['success']);
    }

    #[Test]
    public function testEditarVenta_Falla_VentaNoExiste()
    {
        $resultadoEdicion = $this->ventasModel->updateVenta(999999, []);
        $this->assertFalse($resultadoEdicion['success']);
        $this->assertStringContainsString('venta especificada no existe', $resultadoEdicion['message']);
    }

    #[Test]
    public function testEditarVenta_Falla_VentaNoEnBorrador()
    {
        $producto = $this->productosModel->selectProductoById(1);
        $clienteActivo = $this->getActivoCliente();

        if (!$clienteActivo || !$producto)
            $this->markTestSkipped('Faltan dependencias en bd');

        $datosVentaOriginal = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $clienteActivo['idcliente'],
            'idmoneda_general' => 3,
            'subtotal_general' => 60,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 60
        ];
        $detallesVentaOriginal = [['idproducto' => $producto['idproducto'], 'cantidad' => 3, 'precio_unitario_venta' => 20]];

        $res = $this->ventasModel->insertVenta($datosVentaOriginal, $detallesVentaOriginal);
        $idVenta = $res['idventa'];

        $conexion = new \App\Core\Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();
        $db->exec("UPDATE venta SET estatus = 'PROCESADO' WHERE idventa = $idVenta");
        $conexion->disconnect();

        $resultadoEdicion = $this->ventasModel->updateVenta($idVenta, []);
        $this->assertFalse($resultadoEdicion['success']);
        $this->assertStringContainsString('no está en estado BORRADOR', $resultadoEdicion['message']);
    }

    #[Test]
    public function testEditarVenta_Falla_ClienteNoExiste()
    {
        $producto = $this->productosModel->selectProductoById(1);
        $clienteActivo = $this->getActivoCliente();

        if (!$clienteActivo || !$producto)
            $this->markTestSkipped('Faltan dependencias en bd');

        $datosVentaOriginal = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $clienteActivo['idcliente'],
            'idmoneda_general' => 3,
            'subtotal_general' => 60,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 60
        ];
        $detallesVentaOriginal = [['idproducto' => $producto['idproducto'], 'cantidad' => 3, 'precio_unitario_venta' => 20]];

        $res = $this->ventasModel->insertVenta($datosVentaOriginal, $detallesVentaOriginal);
        $idVenta = $res['idventa'];

        $resultadoEdicion = $this->ventasModel->updateVenta($idVenta, ['idcliente' => 999999]);
        $this->assertFalse($resultadoEdicion['success']);
        $this->assertStringContainsString('cliente especificado no existe', $resultadoEdicion['message']);
    }
}
