<?php

namespace Tests\IntegrationTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\VentasModel;
use App\Models\ClientesModel;
use App\Models\ProductosModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class crearVentaIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private VentasModel  $ventasModel;
    private ClientesModel $clientesModel;
    private ProductosModel $productosModel;

    private static ?int $idClienteActivo  = null;
    private static ?int $idProductoPrueba = null;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->ventasModel   = new VentasModel();
        $this->clientesModel = new ClientesModel();
        $this->productosModel = new ProductosModel();

        // Obtener cliente activo de la BD
        if (self::$idClienteActivo === null) {
            $resultado = $this->clientesModel->selectAllClientesActivos();
            if (!empty($resultado)) {
                self::$idClienteActivo = (int)$resultado[0]['idcliente'];
            }
        }

        // Obtener producto activo de la BD
        if (self::$idProductoPrueba === null) {
            $resultado = $this->productosModel->selectAllProductos();
            $productos = $resultado['data'] ?? [];
            foreach ($productos as $p) {
                if (strtolower($p['estatus'] ?? '') === 'activo') {
                    self::$idProductoPrueba = (int)$p['idproducto'];
                    break;
                }
            }
        }
    }

    protected function tearDown(): void
    {
        unset($this->ventasModel, $this->clientesModel, $this->productosModel);
    }

    // -------------------------------------------------------------------------
    // DataProviders
    // -------------------------------------------------------------------------

    public static function providerCasosInsertValidos(): array
    {
        return [
            'venta_sin_descuento' => [
                'precio'   => 20.00,
                'cantidad' => 3,
                'descPct'  => 0,
                'descMonto'=> 0.00,
            ],
            'venta_con_descuento' => [
                'precio'   => 50.00,
                'cantidad' => 2,
                'descPct'  => 10,
                'descMonto'=> 10.00,
            ],
        ];
    }

    public static function providerCasosInsertInvalidos(): array
    {
        return [
            'sin_cliente' => [
                'idcliente'       => null,
                'idproducto_valido'=> true,
                'mensajeParcial'  => 'cliente',
            ],
            'producto_inexistente' => [
                'idcliente'       => null,  // se reemplaza en el test con ID real
                'idproducto_valido'=> false,
                'mensajeParcial'  => 'no existe',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Tests insert exitosa
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerCasosInsertValidos')]
    public function testInsertVenta_DatosValidos_RetornaSuccessTrue(
        float $precio,
        int   $cantidad,
        int   $descPct,
        float $descMonto
    ): void {
        if (!self::$idClienteActivo || !self::$idProductoPrueba) {
            $this->markTestSkipped('Se requiere cliente activo y producto activo en BD');
        }

        $subtotal = $precio * $cantidad;
        $total    = $subtotal - $descMonto;

        $datos = [
            'fecha_venta'                  => date('Y-m-d'),
            'idcliente'                    => self::$idClienteActivo,
            'idmoneda_general'             => 3,
            'subtotal_general'             => $subtotal,
            'descuento_porcentaje_general' => $descPct,
            'monto_descuento_general'      => $descMonto,
            'estatus'                      => 'BORRADOR',
            'total_general'                => $total,
            'observaciones'                => 'Prueba integracion crear venta',
            'tasa_usada'                   => 1,
        ];

        $detalles = [[
            'idproducto'            => self::$idProductoPrueba,
            'cantidad'              => $cantidad,
            'precio_unitario_venta' => $precio,
            'subtotal_general'      => $subtotal,
            'id_moneda_detalle'     => 3,
        ]];

        $result = $this->ventasModel->insertVenta($datos, $detalles);

        $this->assertIsArray($result);
        $this->assertTrue($result['success'], 'Mensaje: ' . ($result['message'] ?? ''));
        $this->assertArrayHasKey('idventa', $result);
        $this->assertArrayHasKey('nro_venta', $result);
        $this->assertGreaterThan(0, $result['idventa']);
        $this->assertMatchesRegularExpression('/^VT\d+$/', $result['nro_venta']);

        // Verificar que la venta quedó en BD
        $venta = $this->ventasModel->obtenerVentaPorId($result['idventa']);
        $this->assertIsArray($venta);
        $this->assertEquals((float)$total, (float)$venta['total_general']);
        $this->assertEquals(self::$idClienteActivo, (int)$venta['idcliente']);
    }

    // -------------------------------------------------------------------------
    // Tests insert — cliente inválido
    // -------------------------------------------------------------------------

    #[Test]
    public function testInsertVenta_ClienteSinId_RetornaSuccessFalse(): void
    {
        if (!self::$idProductoPrueba) {
            $this->markTestSkipped('Se requiere producto activo en BD');
        }

        $datos = [
            'fecha_venta'                  => date('Y-m-d'),
            'idcliente'                    => null,
            'idmoneda_general'             => 3,
            'subtotal_general'             => 50.00,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general'      => 0,
            'estatus'                      => 'BORRADOR',
            'total_general'                => 50.00,
            'observaciones'                => 'Sin cliente',
            'tasa_usada'                   => 1,
        ];
        $detalles = [[
            'idproducto'            => self::$idProductoPrueba,
            'cantidad'              => 1,
            'precio_unitario_venta' => 50.00,
            'subtotal_general'      => 50.00,
            'id_moneda_detalle'     => 3,
        ]];

        $result = $this->ventasModel->insertVenta($datos, $detalles);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsStringIgnoringCase('cliente', $result['message']);
    }

    // -------------------------------------------------------------------------
    // Tests insert — producto inexistente
    // -------------------------------------------------------------------------

    #[Test]
    public function testInsertVenta_ProductoInexistente_RetornaSuccessFalse(): void
    {
        if (!self::$idClienteActivo) {
            $this->markTestSkipped('Se requiere cliente activo en BD');
        }

        $datos = [
            'fecha_venta'                  => date('Y-m-d'),
            'idcliente'                    => self::$idClienteActivo,
            'idmoneda_general'             => 3,
            'subtotal_general'             => 100.00,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general'      => 0,
            'estatus'                      => 'BORRADOR',
            'total_general'                => 100.00,
            'observaciones'                => 'Producto inexistente',
            'tasa_usada'                   => 1,
        ];
        $detalles = [[
            'idproducto'            => 888888 + rand(1, 99999),
            'cantidad'              => 1,
            'precio_unitario_venta' => 100.00,
            'subtotal_general'      => 100.00,
            'id_moneda_detalle'     => 3,
        ]];

        $result = $this->ventasModel->insertVenta($datos, $detalles);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsStringIgnoringCase('no existe', $result['message']);
    }

    // -------------------------------------------------------------------------
    // Tests insert — descuento mayor al subtotal
    // -------------------------------------------------------------------------

    #[Test]
    public function testInsertVenta_DescuentoMayorSubtotal_RetornaSuccessFalse(): void
    {
        if (!self::$idClienteActivo || !self::$idProductoPrueba) {
            $this->markTestSkipped('Se requiere cliente activo y producto activo en BD');
        }

        $datos = [
            'fecha_venta'                  => date('Y-m-d'),
            'idcliente'                    => self::$idClienteActivo,
            'idmoneda_general'             => 3,
            'subtotal_general'             => 50.00,
            'descuento_porcentaje_general' => 100,
            'monto_descuento_general'      => 200.00, // mayor que subtotal
            'estatus'                      => 'BORRADOR',
            'total_general'                => 0.00,
            'observaciones'                => 'Descuento excesivo',
            'tasa_usada'                   => 1,
        ];
        $detalles = [[
            'idproducto'            => self::$idProductoPrueba,
            'cantidad'              => 1,
            'precio_unitario_venta' => 50.00,
            'subtotal_general'      => 50.00,
            'id_moneda_detalle'     => 3,
        ]];

        $result = $this->ventasModel->insertVenta($datos, $detalles);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsStringIgnoringCase('descuento', $result['message']);
    }

    // -------------------------------------------------------------------------
    // Tests insert — detalles verifican correctamente en BD
    // -------------------------------------------------------------------------

    #[Test]
    public function testInsertVenta_DetallesGuardadosCorrectamente(): void
    {
        if (!self::$idClienteActivo || !self::$idProductoPrueba) {
            $this->markTestSkipped('Se requiere cliente activo y producto activo en BD');
        }

        $cantidad = 4;
        $precio   = 15.00;
        $subtotal = $cantidad * $precio;

        $datos = [
            'fecha_venta'                  => date('Y-m-d'),
            'idcliente'                    => self::$idClienteActivo,
            'idmoneda_general'             => 3,
            'subtotal_general'             => $subtotal,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general'      => 0,
            'estatus'                      => 'BORRADOR',
            'total_general'                => $subtotal,
            'observaciones'                => 'Verificar detalles',
            'tasa_usada'                   => 1,
        ];
        $detalles = [[
            'idproducto'            => self::$idProductoPrueba,
            'cantidad'              => $cantidad,
            'precio_unitario_venta' => $precio,
            'subtotal_general'      => $subtotal,
            'id_moneda_detalle'     => 3,
        ]];

        $result = $this->ventasModel->insertVenta($datos, $detalles);

        $this->assertTrue($result['success'], 'Inserción fallida: ' . ($result['message'] ?? ''));

        $detallesGuardados = $this->ventasModel->obtenerDetalleVenta($result['idventa']);
        $this->assertNotEmpty($detallesGuardados);
        $this->assertCount(1, $detallesGuardados);
        $this->assertEquals(self::$idProductoPrueba, (int)$detallesGuardados[0]['idproducto']);
        $this->assertEquals($cantidad, (float)$detallesGuardados[0]['cantidad']);
        $this->assertEquals($precio, (float)$detallesGuardados[0]['precio_unitario_venta']);
    }
}
