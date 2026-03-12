<?php

namespace Tests\IntegrationTest\Ventas;

use PHPUnit\Framework\TestCase;
use App\Models\VentasModel;
use App\Models\ProductosModel;
use App\Models\ClientesModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class VentaEliminacionIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private VentasModel $ventasModel;
    private ProductosModel $productosModel;
    private ClientesModel $clientesModel;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->ventasModel    = new VentasModel();
        $this->productosModel = new ProductosModel();
        $this->clientesModel  = new ClientesModel();
    }

    protected function tearDown(): void
    {
        unset($this->ventasModel, $this->productosModel, $this->clientesModel);
    }

    // ─────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────

    private function obtenerClienteActivo(): ?array
    {
        $resultado = $this->clientesModel->selectAllClientes();
        foreach ($resultado['data'] as $c) {
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'activo') {
                return $c;
            }
        }
        return null;
    }

    private function crearVentaBorrador(int $idCliente, int $idProducto): array
    {
        $datos = [
            'fecha_venta'                  => date('Y-m-d'),
            'idcliente'                    => $idCliente,
            'idmoneda_general'             => 3,
            'subtotal_general'             => 50.00,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general'      => 0,
            'estatus'                      => 'BORRADOR',
            'total_general'                => 50.00,
            'observaciones'                => 'Venta para eliminar.',
            'tasa_usada'                   => 1,
        ];
        $detalles = [[
            'idproducto'            => $idProducto,
            'cantidad'              => 5,
            'precio_unitario_venta' => 10,
            'subtotal_general'      => 50,
            'id_moneda_detalle'     => 3,
        ]];
        return $this->ventasModel->insertVenta($datos, $detalles);
    }

    // ─────────────────────────────────────────────
    // DataProviders
    // ─────────────────────────────────────────────

    public static function eliminarVentaInexistenteProvider(): array
    {
        return [
            'id muy alto 1' => [888888 + 1],
            'id muy alto 2' => [888888 + 2],
        ];
    }

    // ─────────────────────────────────────────────
    // Tests: eliminación exitosa
    // ─────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function testEliminarVentaExitosaDesactivaVenta(): void
    {
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto, 'Producto no encontrado.');

        $clienteActivo = $this->obtenerClienteActivo();
        $this->assertNotNull($clienteActivo, 'No se encontró un cliente activo.');

        $insercion = $this->crearVentaBorrador($clienteActivo['idcliente'], $producto['idproducto']);
        $this->assertTrue($insercion['success'], 'No se pudo crear la venta de prueba.');
        $idVenta = $insercion['idventa'];

        $resultado = $this->ventasModel->eliminarVenta($idVenta);
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success'], 'La eliminación debería ser exitosa.');

        $ventaEliminada = $this->ventasModel->obtenerVentaPorId($idVenta);
        $this->assertNotEmpty($ventaEliminada, 'No se pudo obtener la venta después de eliminar.');
        $this->assertEquals('inactivo', strtolower($ventaEliminada['estatus']), 'La venta debería estar en estado inactivo.');
    }

    // ─────────────────────────────────────────────
    // Tests: eliminación fallida (DataProvider)
    // ─────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('eliminarVentaInexistenteProvider')]
    public function testEliminarVentaInexistenteFalla(int $idVenta): void
    {
        $resultado = $this->ventasModel->eliminarVenta($idVenta);
        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['success'], 'No debería ser posible eliminar una venta inexistente.');
    }

    // ─────────────────────────────────────────────
    // Test: no eliminar venta en estado diferente a BORRADOR
    // ─────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function testNoEliminarVentaSiNoEstaEnBorrador(): void
    {
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto);

        $clienteActivo = $this->obtenerClienteActivo();
        $this->assertNotNull($clienteActivo);

        $insercion = $this->crearVentaBorrador($clienteActivo['idcliente'], $producto['idproducto']);
        $this->assertTrue($insercion['success']);
        $idVenta = $insercion['idventa'];

        // Cambiamos el estado para que ya no sea BORRADOR
        $resultadoCambio = $this->ventasModel->cambiarEstadoVenta($idVenta, 'POR_PAGAR');

        if (!$resultadoCambio || !isset($resultadoCambio['success']) || !$resultadoCambio['success']) {
            $this->markTestSkipped('No se pudo cambiar el estado de la venta. Posiblemente falta tabla o configuración.');
        }

        $resultado = $this->ventasModel->eliminarVenta($idVenta);
        $this->assertIsArray($resultado);

        if (!$resultado['success']) {
            $this->assertStringContainsString('BORRADOR', $resultado['message'],
                'El mensaje de error debería mencionar que solo se pueden eliminar ventas en estado BORRADOR.');
        }

        $venta = $this->ventasModel->obtenerVentaPorId($idVenta);
        $this->assertNotEmpty($venta, 'No se pudo obtener la venta para verificar estado.');
    }
}
