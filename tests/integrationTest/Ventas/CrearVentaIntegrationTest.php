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

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->ventasModel = new VentasModel();
        $this->productosModel = new ProductosModel();
        $this->clientesModel = new ClientesModel();
    }

    private function getActivoYInactivoCliente()
    {
        $resultado = $this->clientesModel->selectAllClientes();
        $clientes = $resultado['data'] ?? [];
        $clienteActivo = null;
        $clienteInactivo = null;
        foreach ($clientes as $c) {
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'activo')
                $clienteActivo = $c;
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'inactivo')
                $clienteInactivo = $c;
        }
        return [$clienteActivo, $clienteInactivo];
    }

    #[Test]
    public function testCrearVentaExitosa()
    {
        $producto = $this->productosModel->selectProductoById(1);
        list($clienteActivo, $clienteInactivo) = $this->getActivoYInactivoCliente();

        if (!$clienteActivo || !$producto)
            $this->markTestSkipped('Falta dependencia en BD');

        $datosVenta = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => $clienteActivo['idcliente'],
            'idmoneda_general' => 3,
            'subtotal_general' => 60,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 60
        ];
        $detallesVenta = [['idproducto' => $producto['idproducto'], 'cantidad' => 3, 'precio_unitario_venta' => 20]];

        $resultado = $this->ventasModel->insertVenta($datosVenta, $detallesVenta);
        $this->assertTrue($resultado['success']);
        $this->assertArrayHasKey('idventa', $resultado);
    }

    #[Test]
    public function testInsertVenta_Falla_ClienteInexistente()
    {
        $datos = ['idcliente' => 99999, 'idmoneda_general' => 3, 'subtotal_general' => 10, 'fecha_venta' => date('Y-m-d')];
        $resultado = $this->ventasModel->insertVenta($datos, []);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('Cliente no existe', $resultado['message']);
    }

    #[Test]
    public function testInsertVenta_Falla_ClienteInactivo()
    {
        list($clienteActivo, $clienteInactivo) = $this->getActivoYInactivoCliente();
        if (!$clienteInactivo)
            $this->markTestSkipped('No hay cliente inactivo en DB');

        $datos = ['idcliente' => $clienteInactivo['idcliente'], 'idmoneda_general' => 3, 'subtotal_general' => 10, 'fecha_venta' => date('Y-m-d')];
        $resultado = $this->ventasModel->insertVenta($datos, []);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('Cliente inactivo', $resultado['message']);
    }

    #[Test]
    public function testInsertVenta_Falla_MonedaInexistente()
    {
        list($clienteActivo, $clienteInactivo) = $this->getActivoYInactivoCliente();
        if (!$clienteActivo)
            $this->markTestSkipped('Falta dependencia');

        $datos = ['idcliente' => $clienteActivo['idcliente'], 'idmoneda_general' => 9999, 'subtotal_general' => 10, 'fecha_venta' => date('Y-m-d')];
        $resultado = $this->ventasModel->insertVenta($datos, []);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('Moneda no existe', $resultado['message']);
    }

    #[Test]
    public function testInsertVenta_Falla_DescuentoMayorSubtotal()
    {
        list($clienteActivo, $clienteInactivo) = $this->getActivoYInactivoCliente();
        if (!$clienteActivo)
            $this->markTestSkipped('Falta dep');

        $datos = ['idcliente' => $clienteActivo['idcliente'], 'idmoneda_general' => 3, 'subtotal_general' => 10, 'monto_descuento_general' => 20, 'fecha_venta' => date('Y-m-d')];
        $resultado = $this->ventasModel->insertVenta($datos, []);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('Descuento mayor al subtotal', $resultado['message']);
    }

    #[Test]
    public function testInsertVenta_Falla_ProductoInexistente()
    {
        list($clienteActivo, $clienteInactivo) = $this->getActivoYInactivoCliente();
        if (!$clienteActivo)
            $this->markTestSkipped('Falta dep');

        $datos = ['idcliente' => $clienteActivo['idcliente'], 'idmoneda_general' => 3, 'subtotal_general' => 10, 'monto_descuento_general' => 0, 'descuento_porcentaje_general' => 0, 'fecha_venta' => date('Y-m-d'), 'total_general' => 10];
        $det = [['idproducto' => 99999, 'cantidad' => 1, 'precio_unitario_venta' => 10]];
        $resultado = $this->ventasModel->insertVenta($datos, $det);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('no existe en el detalle', $resultado['message']);
    }

    #[Test]
    public function testInsertVenta_Falla_CantidadInvalida()
    {
        $producto = $this->productosModel->selectProductoById(1);
        list($clienteActivo, $clienteInactivo) = $this->getActivoYInactivoCliente();
        if (!$clienteActivo || !$producto)
            $this->markTestSkipped('Falta dep');

        $datos = ['idcliente' => $clienteActivo['idcliente'], 'idmoneda_general' => 3, 'subtotal_general' => 10, 'monto_descuento_general' => 0, 'descuento_porcentaje_general' => 0, 'fecha_venta' => date('Y-m-d'), 'total_general' => 10];
        $det = [['idproducto' => $producto['idproducto'], 'cantidad' => -1, 'precio_unitario_venta' => 10]];
        $resultado = $this->ventasModel->insertVenta($datos, $det);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('La cantidad debe ser mayor a 0', $resultado['message']);
    }
}
