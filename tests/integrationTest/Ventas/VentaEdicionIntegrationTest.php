<?php

namespace Tests\IntegrationTest\Ventas;

use PHPUnit\Framework\TestCase;
use App\Models\VentasModel;
use App\Models\ProductosModel;
use App\Models\ClientesModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class VentaEdicionIntegrationTest extends TestCase
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
            'subtotal_general'             => 60.00,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general'      => 0,
            'estatus'                      => 'BORRADOR',
            'total_general'                => 60.00,
            'observaciones'                => 'Venta original para test de edición',
            'tasa_usada'                   => 1,
        ];
        $detalles = [[
            'idproducto'            => $idProducto,
            'cantidad'              => 3,
            'precio_unitario_venta' => 20.00,
            'subtotal_general'      => 60.00,
            'id_moneda_detalle'     => 3,
        ]];
        return $this->ventasModel->insertVenta($datos, $detalles);
    }

    // ─────────────────────────────────────────────
    // DataProviders
    // ─────────────────────────────────────────────

    public static function edicionFallidaProvider(): array
    {
        return [
            'venta inexistente'     => [888888, ['observaciones' => 'Test']],
            'precio negativo'       => [0, [
                'observaciones' => 'Test precio negativo',
                'detalles'      => [[
                    'idproducto'            => 1,
                    'cantidad'              => 1,
                    'precio_unitario_venta' => -100,
                    'subtotal_general'      => -100,
                    'id_moneda_detalle'     => 3,
                ]],
            ]],
            'cantidad negativa' => [0, [
                'observaciones' => 'Test cantidad negativa',
                'detalles'      => [[
                    'idproducto'            => 1,
                    'cantidad'              => -5,
                    'precio_unitario_venta' => 10,
                    'subtotal_general'      => -50,
                    'id_moneda_detalle'     => 3,
                ]],
            ]],
            'producto inexistente' => [0, [
                'observaciones' => 'Test producto inexistente',
                'detalles'      => [[
                    'idproducto'            => 888888,
                    'cantidad'              => 1,
                    'precio_unitario_venta' => 100,
                    'subtotal_general'      => 100,
                    'id_moneda_detalle'     => 3,
                ]],
            ]],
        ];
    }

    // ─────────────────────────────────────────────
    // Tests: edición exitosa
    // ─────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function testEditarVentaExitosa(): void
    {
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto, 'Producto no encontrado.');

        $clienteActivo = $this->obtenerClienteActivo();
        $this->assertNotNull($clienteActivo, 'No se encontró un cliente activo.');

        $insercion = $this->crearVentaBorrador($clienteActivo['idcliente'], $producto['idproducto']);
        $this->assertTrue($insercion['success'], 'No se pudo crear la venta original.');
        $idVenta = $insercion['idventa'];

        $datosEdicion = [
            'fecha_venta'                  => date('Y-m-d'),
            'idcliente'                    => $clienteActivo['idcliente'],
            'idmoneda_general'             => 3,
            'subtotal_general'             => 125.00,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general'      => 0,
            'estatus'                      => 'BORRADOR',
            'total_general'                => 125.00,
            'observaciones'                => 'Venta editada correctamente.',
            'tasa_usada'                   => 1,
            'detalles'                     => [[
                'idproducto'            => $producto['idproducto'],
                'cantidad'              => 5,
                'precio_unitario_venta' => 25.00,
                'subtotal_general'      => 125.00,
                'id_moneda_detalle'     => 3,
            ]],
        ];

        $resultadoEdicion = $this->ventasModel->updateVenta($idVenta, $datosEdicion);
        $this->assertTrue($resultadoEdicion['success'], 'La edición debería ser exitosa.');

        $ventaEditada = $this->ventasModel->obtenerVentaPorId($idVenta);
        $this->assertNotEmpty($ventaEditada);
        $this->assertEquals('Venta editada correctamente.', $ventaEditada['observaciones']);
        $this->assertEquals(125.00, (float)$ventaEditada['total_general']);

        $detalles = $this->ventasModel->obtenerDetalleVenta($idVenta);
        $this->assertNotEmpty($detalles);
        $this->assertCount(1, $detalles);
        $this->assertEquals(5, $detalles[0]['cantidad']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testEditarVentaSinDetallesActualizaSoloEncabezado(): void
    {
        $producto = $this->productosModel->selectProductoById(1);
        $this->assertNotNull($producto);

        $clienteActivo = $this->obtenerClienteActivo();
        $this->assertNotNull($clienteActivo);

        $insercion = $this->crearVentaBorrador($clienteActivo['idcliente'], $producto['idproducto']);
        $this->assertTrue($insercion['success']);
        $idVenta = $insercion['idventa'];

        $datosEdicion = [
            'observaciones' => 'Solo actualicé la observación',
            'total_general' => 200.00,
        ];

        $resultadoEdicion = $this->ventasModel->updateVenta($idVenta, $datosEdicion);
        $this->assertTrue($resultadoEdicion['success'], 'La edición parcial debería ser exitosa.');

        $ventaEditada = $this->ventasModel->obtenerVentaPorId($idVenta);
        $this->assertEquals('Solo actualicé la observación', $ventaEditada['observaciones']);

        $detalles = $this->ventasModel->obtenerDetalleVenta($idVenta);
        $this->assertNotEmpty($detalles, 'Los detalles originales deben mantenerse.');
    }

    // ─────────────────────────────────────────────
    // Tests: edición fallida (DataProvider)
    // ─────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('edicionFallidaProvider')]
    public function testEditarVentaConDatosInvalidosFalla(int $idVentaOffset, array $datos): void
    {
        // Si el offset es 0, intentamos con un id venta real pero datos inválidos
        // Si el offset es 888888, es un id ficticio
        $idVenta = ($idVentaOffset === 888888) ? 888888 : 1;

        $resultado = $this->ventasModel->updateVenta($idVenta, $datos);
        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['success']);
        $this->assertArrayHasKey('message', $resultado);
    }
}
