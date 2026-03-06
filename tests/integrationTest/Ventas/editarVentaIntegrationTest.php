<?php

namespace Tests\IntegrationTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\VentasModel;
use App\Models\ClientesModel;
use App\Models\ProductosModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class editarVentaIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private VentasModel   $ventasModel;
    private ClientesModel $clientesModel;
    private ProductosModel $productosModel;

    private static ?int $idClienteActivo  = null;
    private static ?int $idProductoPrueba = null;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->ventasModel    = new VentasModel();
        $this->clientesModel  = new ClientesModel();
        $this->productosModel = new ProductosModel();

        if (self::$idClienteActivo === null) {
            $resultado = $this->clientesModel->selectAllClientesActivos();
            if (!empty($resultado)) {
                self::$idClienteActivo = (int)$resultado[0]['idcliente'];
            }
        }

        if (self::$idProductoPrueba === null) {
            $resultado = $this->productosModel->selectAllProductos();
            $productos  = $resultado['data'] ?? [];
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
    // Helpers
    // -------------------------------------------------------------------------

    private function crearVentaBorrador(float $precio = 20.00, int $cantidad = 3): array
    {
        $subtotal = $precio * $cantidad;
        $datos = [
            'fecha_venta'                  => date('Y-m-d'),
            'idcliente'                    => self::$idClienteActivo,
            'idmoneda_general'             => 3,
            'subtotal_general'             => $subtotal,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general'      => 0,
            'estatus'                      => 'BORRADOR',
            'total_general'                => $subtotal,
            'observaciones'                => 'Venta original para test edicion',
            'tasa_usada'                   => 1,
        ];
        $detalles = [[
            'idproducto'            => self::$idProductoPrueba,
            'cantidad'              => $cantidad,
            'precio_unitario_venta' => $precio,
            'subtotal_general'      => $subtotal,
            'id_moneda_detalle'     => 3,
        ]];
        return $this->ventasModel->insertVenta($datos, $detalles);
    }

    // -------------------------------------------------------------------------
    // DataProviders
    // -------------------------------------------------------------------------

    public static function providerCambiosObservacion(): array
    {
        return [
            'nueva_observacion_corta' => ['Texto actualizado'],
            'nueva_observacion_larga' => ['Edición realizada el ' . date('Y-m-d') . ' con más detalles del pedido'],
        ];
    }

    // -------------------------------------------------------------------------
    // Tests updateVenta exitosa
    // -------------------------------------------------------------------------

    #[Test]
    public function testUpdateVenta_EnBorrador_RetornaSuccessTrue(): void
    {
        if (!self::$idClienteActivo || !self::$idProductoPrueba) {
            $this->markTestSkipped('Se requiere cliente activo y producto activo en BD');
        }

        $insercion = $this->crearVentaBorrador();
        $this->assertTrue($insercion['success'], 'No se pudo crear venta inicial: ' . ($insercion['message'] ?? ''));

        $idVenta       = $insercion['idventa'];
        $nuevoPrecio   = 25.00;
        $nuevaCantidad = 5;
        $nuevoSubtotal = $nuevoPrecio * $nuevaCantidad;

        $datosEdicion = [
            'fecha_venta'                  => date('Y-m-d'),
            'idcliente'                    => self::$idClienteActivo,
            'idmoneda_general'             => 3,
            'subtotal_general'             => $nuevoSubtotal,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general'      => 0,
            'estatus'                      => 'BORRADOR',
            'total_general'                => $nuevoSubtotal,
            'observaciones'                => 'Venta editada correctamente.',
            'tasa_usada'                   => 1,
            'detalles'                     => [[
                'idproducto'            => self::$idProductoPrueba,
                'cantidad'              => $nuevaCantidad,
                'precio_unitario_venta' => $nuevoPrecio,
                'subtotal_general'      => $nuevoSubtotal,
                'id_moneda_detalle'     => 3,
            ]],
        ];

        $result = $this->ventasModel->updateVenta($idVenta, $datosEdicion);

        $this->assertIsArray($result);
        $this->assertTrue($result['success'], 'Edición fallo: ' . ($result['message'] ?? ''));
        $this->assertEquals($idVenta, $result['idventa']);

        // Verificar que los cambios se aplicaron en BD
        $ventaEditada = $this->ventasModel->obtenerVentaPorId($idVenta);
        $this->assertEquals('Venta editada correctamente.', $ventaEditada['observaciones']);
        $this->assertEquals($nuevoSubtotal, (float)$ventaEditada['total_general']);

        $detallesEditados = $this->ventasModel->obtenerDetalleVenta($idVenta);
        $this->assertNotEmpty($detallesEditados);
        $this->assertCount(1, $detallesEditados);
        $this->assertEquals($nuevaCantidad, (float)$detallesEditados[0]['cantidad']);
        $this->assertEquals($nuevoPrecio, (float)$detallesEditados[0]['precio_unitario_venta']);
    }

    #[Test]
    #[DataProvider('providerCambiosObservacion')]
    public function testUpdateVenta_CambiarObservacion_SeRefleja(string $nuevaObservacion): void
    {
        if (!self::$idClienteActivo || !self::$idProductoPrueba) {
            $this->markTestSkipped('Se requiere cliente activo y producto activo en BD');
        }

        $insercion = $this->crearVentaBorrador();
        $this->assertTrue($insercion['success'], 'Error al crear venta: ' . ($insercion['message'] ?? ''));

        $idVenta = $insercion['idventa'];
        $result  = $this->ventasModel->updateVenta($idVenta, ['observaciones' => $nuevaObservacion]);

        $this->assertIsArray($result);
        $this->assertTrue($result['success'], 'Mensaje: ' . ($result['message'] ?? ''));

        $ventaActualizada = $this->ventasModel->obtenerVentaPorId($idVenta);
        $this->assertEquals($nuevaObservacion, $ventaActualizada['observaciones']);
    }

    // -------------------------------------------------------------------------
    // Tests updateVenta — venta no existe
    // -------------------------------------------------------------------------

    #[Test]
    public function testUpdateVenta_VentaNoExiste_RetornaSuccessFalse(): void
    {
        $result = $this->ventasModel->updateVenta(999999, [
            'observaciones' => 'Intento editar venta inexistente',
        ]);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsStringIgnoringCase('no existe', $result['message']);
    }

    // -------------------------------------------------------------------------
    // Tests updateVenta — venta no en BORRADOR
    // -------------------------------------------------------------------------

    #[Test]
    public function testUpdateVenta_EstadoPorPagar_RetornaSuccessFalse(): void
    {
        if (!self::$idClienteActivo || !self::$idProductoPrueba) {
            $this->markTestSkipped('Se requiere cliente activo y producto activo en BD');
        }

        $insercion = $this->crearVentaBorrador();
        $this->assertTrue($insercion['success'], 'No se pudo crear venta: ' . ($insercion['message'] ?? ''));
        $idVenta = $insercion['idventa'];

        // Cambiar a POR_PAGAR
        $cambio = $this->ventasModel->cambiarEstadoVenta($idVenta, 'POR_PAGAR');
        if (!$cambio['status']) {
            $this->markTestSkipped('No se pudo cambiar estado a POR_PAGAR: ' . $cambio['message']);
        }

        $result = $this->ventasModel->updateVenta($idVenta, [
            'observaciones' => 'Intento editar venta en POR_PAGAR',
        ]);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsStringIgnoringCase('BORRADOR', $result['message']);
    }
}
