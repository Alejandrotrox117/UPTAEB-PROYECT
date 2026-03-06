<?php

namespace Tests\IntegrationTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\VentasModel;
use App\Models\ClientesModel;
use App\Models\ProductosModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class eliminarVentaIntegrationTest extends TestCase
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

    private function crearVentaBorrador(float $precio = 10.00, int $cantidad = 5): array
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
            'observaciones'                => 'Venta para eliminar en test',
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

    public static function providerIdsInexistentes(): array
    {
        return [
            'id_muy_grande' => [999999],
        ];
    }

    // -------------------------------------------------------------------------
    // Tests eliminarVenta exitosa
    // -------------------------------------------------------------------------

    #[Test]
    public function testEliminarVenta_EnBorrador_RetornaSuccessTrue(): void
    {
        if (!self::$idClienteActivo || !self::$idProductoPrueba) {
            $this->markTestSkipped('Se requiere cliente activo y producto activo en BD');
        }

        $insercion = $this->crearVentaBorrador();
        $this->assertTrue($insercion['success'], 'No se pudo crear venta: ' . ($insercion['message'] ?? ''));
        $idVenta = $insercion['idventa'];

        $result = $this->ventasModel->eliminarVenta($idVenta);

        $this->assertIsArray($result);
        $this->assertTrue($result['success'], 'Eliminación falló: ' . ($result['message'] ?? ''));
        $this->assertStringContainsStringIgnoringCase('desactivada', $result['message']);

        // Verificar que el estatus cambió a inactivo
        $ventaEliminada = $this->ventasModel->obtenerVentaPorId($idVenta);
        $this->assertIsArray($ventaEliminada);
        $this->assertEquals('inactivo', strtolower($ventaEliminada['estatus']));
    }

    // -------------------------------------------------------------------------
    // Tests eliminarVenta — venta inexistente
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testEliminarVenta_Inexistente_RetornaSuccessFalse(int $id): void
    {
        $result = $this->ventasModel->eliminarVenta($id);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    // -------------------------------------------------------------------------
    // Tests eliminarVenta — estado no permite eliminación (POR_PAGAR)
    // -------------------------------------------------------------------------

    #[Test]
    public function testEliminarVenta_EstadoPorPagar_RetornaSuccessFalse(): void
    {
        if (!self::$idClienteActivo || !self::$idProductoPrueba) {
            $this->markTestSkipped('Se requiere cliente activo y producto activo en BD');
        }

        $insercion = $this->crearVentaBorrador();
        $this->assertTrue($insercion['success'], 'No se pudo crear venta: ' . ($insercion['message'] ?? ''));
        $idVenta = $insercion['idventa'];

        // Cambiar estado a POR_PAGAR antes de intentar eliminar
        $cambio = $this->ventasModel->cambiarEstadoVenta($idVenta, 'POR_PAGAR');
        if (!$cambio['status']) {
            $this->markTestSkipped('No se pudo cambiar estado a POR_PAGAR: ' . $cambio['message']);
        }

        $result = $this->ventasModel->eliminarVenta($idVenta);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsStringIgnoringCase('BORRADOR', $result['message']);
    }

    // -------------------------------------------------------------------------
    // Tests cambiarEstadoVenta — transiciones válidas
    // -------------------------------------------------------------------------

    #[Test]
    public function testCambiarEstadoVenta_DeBorradorAPorPagar_RetornaStatusTrue(): void
    {
        if (!self::$idClienteActivo || !self::$idProductoPrueba) {
            $this->markTestSkipped('Se requiere cliente activo y producto activo en BD');
        }

        $insercion = $this->crearVentaBorrador();
        $this->assertTrue($insercion['success'], 'No se pudo crear venta: ' . ($insercion['message'] ?? ''));
        $idVenta = $insercion['idventa'];

        $result = $this->ventasModel->cambiarEstadoVenta($idVenta, 'POR_PAGAR');

        $this->assertIsArray($result);
        $this->assertTrue($result['status'], 'Mensaje: ' . ($result['message'] ?? ''));

        $venta = $this->ventasModel->obtenerVentaPorId($idVenta);
        $this->assertEquals('POR_PAGAR', strtoupper($venta['estatus']));
    }

    #[Test]
    public function testCambiarEstadoVenta_EstadoInvalido_RetornaStatusFalse(): void
    {
        $ventas = $this->ventasModel->getVentasDatatable();
        if (empty($ventas)) {
            $this->markTestSkipped('No hay ventas en BD para esta prueba');
        }

        $idVenta = (int)$ventas[0]['idventa'];
        $result  = $this->ventasModel->cambiarEstadoVenta($idVenta, 'ESTADO_NO_VALIDO');

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('válido', $result['message']);
    }

    #[Test]
    public function testCambiarEstadoVenta_VentaInexistente_RetornaStatusFalse(): void
    {
        $result = $this->ventasModel->cambiarEstadoVenta(999999, 'POR_PAGAR');

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('encontrada', $result['message']);
    }
}
