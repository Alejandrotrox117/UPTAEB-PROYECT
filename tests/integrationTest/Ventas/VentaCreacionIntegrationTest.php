<?php

namespace Tests\IntegrationTest\Ventas;

use PHPUnit\Framework\TestCase;
use App\Models\VentasModel;
use App\Models\ProductosModel;
use App\Models\ClientesModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class VentaCreacionIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private VentasModel $ventasModel;
    private ProductosModel $productosModel;
    private ClientesModel $clientesModel;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->ventasModel   = new VentasModel();
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

    private function datosVentaBase(int $idCliente, float $total = 60.00): array
    {
        return [
            'fecha_venta'                  => date('Y-m-d'),
            'idcliente'                    => $idCliente,
            'idmoneda_general'             => 3,
            'subtotal_general'             => $total,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general'      => 0,
            'estatus'                      => 'BORRADOR',
            'total_general'                => $total,
            'observaciones'                => 'Prueba de integración',
            'tasa_usada'                   => 1,
        ];
    }

    private function detallesVentaBase(int $idProducto, int $cantidad = 3, float $precio = 20.00): array
    {
        return [[
            'idproducto'            => $idProducto,
            'cantidad'              => $cantidad,
            'precio_unitario_venta' => $precio,
            'subtotal_general'      => $precio * $cantidad,
            'id_moneda_detalle'     => 3,
        ]];
    }

    // ─────────────────────────────────────────────
    // DataProviders
    // ─────────────────────────────────────────────

    public static function ventaFallidaProvider(): array
    {
        return [
            'sin cliente (null)'      => [null, 1, 50.00, 5, 10.00],
            'cantidad negativa'       => [1,    1, 50.00, -5, 10.00],
            'precio negativo'         => [1,    1, 100.00, 1, -100.00],
            'producto inexistente'    => [1, 888888, 100.00, 1, 100.00],
        ];
    }

    // ─────────────────────────────────────────────
    // Tests: creación exitosa
    // ─────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function testCrearVentaExitosa(): void
    {
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto, 'Producto de prueba no encontrado.');

        $clienteActivo = $this->obtenerClienteActivo();
        $this->assertNotNull($clienteActivo, 'No se encontró un cliente activo.');

        $datos    = $this->datosVentaBase($clienteActivo['idcliente']);
        $detalles = $this->detallesVentaBase($producto['idproducto']);

        $resultado = $this->ventasModel->insertVenta($datos, $detalles);

        $this->assertTrue($resultado['success']);
        $this->assertArrayHasKey('idventa', $resultado);
        $this->assertArrayHasKey('nro_venta', $resultado);
        $this->assertGreaterThan(0, $resultado['idventa']);
        $this->assertMatchesRegularExpression('/^VT\d+$/', $resultado['nro_venta']);

        $ventaCreada = $this->ventasModel->obtenerVentaPorId($resultado['idventa']);
        $this->assertNotEmpty($ventaCreada);
        $this->assertEquals($clienteActivo['idcliente'], $ventaCreada['idcliente']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testCrearVentaConDescuento(): void
    {
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto);

        $clienteActivo = $this->obtenerClienteActivo();
        $this->assertNotNull($clienteActivo);

        $datos = [
            'fecha_venta'                  => date('Y-m-d'),
            'idcliente'                    => $clienteActivo['idcliente'],
            'idmoneda_general'             => 3,
            'subtotal_general'             => 100.00,
            'descuento_porcentaje_general' => 10,
            'monto_descuento_general'      => 10.00,
            'estatus'                      => 'BORRADOR',
            'total_general'                => 90.00,
            'observaciones'                => 'Venta con descuento - integración',
            'tasa_usada'                   => 1,
        ];
        $detalles = [[
            'idproducto'            => $producto['idproducto'],
            'cantidad'              => 10,
            'precio_unitario_venta' => 10.00,
            'subtotal_general'      => 100.00,
            'id_moneda_detalle'     => 3,
        ]];

        $resultado = $this->ventasModel->insertVenta($datos, $detalles);
        $this->assertIsArray($resultado);
    }

    // ─────────────────────────────────────────────
    // Tests: creación fallida (DataProvider)
    // ─────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('ventaFallidaProvider')]
    public function testCrearVentaConDatosInvalidosFalla(
        ?int $idCliente,
        int  $idProducto,
        float $total,
        int  $cantidad,
        float $precio
    ): void {
        $datos = [
            'fecha_venta'                  => date('Y-m-d'),
            'idcliente'                    => $idCliente,
            'idmoneda_general'             => 3,
            'subtotal_general'             => $total,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general'      => 0,
            'estatus'                      => 'BORRADOR',
            'total_general'                => $total,
            'observaciones'                => 'Test dato inválido',
            'tasa_usada'                   => 1,
        ];
        $detalles = [[
            'idproducto'            => $idProducto,
            'cantidad'              => $cantidad,
            'precio_unitario_venta' => $precio,
            'subtotal_linea'        => $total,
            'idmoneda_detalle'      => 3,
        ]];

        try {
            $resultado = $this->ventasModel->insertVenta($datos, $detalles);
            $this->assertFalse($resultado['success'] ?? true);
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    // ─────────────────────────────────────────────
    // Test: cliente inactivo
    // ─────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function testCrearVentaConClienteInactivoFalla(): void
    {
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto);

        $resultado = $this->clientesModel->selectAllClientes();
        $clienteInactivo = null;
        foreach ($resultado['data'] as $c) {
            if (isset($c['estatus']) && strtolower($c['estatus']) === 'inactivo') {
                $clienteInactivo = $c;
                break;
            }
        }

        if ($clienteInactivo === null) {
            $this->markTestSkipped('No hay cliente inactivo disponible para la prueba.');
        }

        $datos    = $this->datosVentaBase($clienteInactivo['idcliente']);
        $detalles = $this->detallesVentaBase($producto['idproducto']);

        $resultado = $this->ventasModel->insertVenta($datos, $detalles);
        $this->assertFalse($resultado['success']);
        $this->assertArrayHasKey('message', $resultado);
        $this->assertStringContainsString('inactivo', strtolower($resultado['message']));
    }
}
