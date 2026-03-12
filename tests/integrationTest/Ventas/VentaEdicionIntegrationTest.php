<?php

namespace Tests\IntegrationTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\VentasModel;
use App\Models\ProductosModel;
use App\Core\Conexion;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

/**
 * Tests de integración: edición de ventas (updateVenta).
 *
 * Qué se verifica:
 *  - La edición persiste correctamente en BD (observaciones, total, detalles).
 *  - Al editar detalles, el stock se revierte del detalle anterior y se deduce el nuevo.
 *  - No se puede editar una venta en estado != BORRADOR.
 *  - Datos inválidos en detalles son rechazados con success=false.
 */
class VentaEdicionIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private VentasModel $ventasModel;
    private \PDO $pdo;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->ventasModel = new VentasModel();

        $conexion = new Conexion();
        $conexion->connect();
        $this->pdo = $conexion->get_conectGeneral();
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec('UPDATE producto SET existencia = 999 WHERE idproducto = 1');
    }

    protected function tearDown(): void
    {
        unset($this->ventasModel, $this->pdo);
    }

    // ─────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────

    private function idClienteActivo(): int
    {
        $stmt = $this->pdo->query(
            "SELECT idcliente FROM cliente WHERE UPPER(estatus) = 'ACTIVO' LIMIT 1"
        );
        $id = $stmt->fetchColumn();
        $this->assertNotFalse($id, 'No existe ningún cliente activo en la BD de test.');
        return (int) $id;
    }

    private function stockActual(int $idproducto): float
    {
        $stmt = $this->pdo->prepare('SELECT existencia FROM producto WHERE idproducto = ?');
        $stmt->execute([$idproducto]);
        return (float) $stmt->fetchColumn();
    }

    /** Crea una venta en estado BORRADOR con 3 unidades del producto 1 (precio 20). */
    private function crearVentaBorrador(int $idCliente, int $cantidad = 3, float $precio = 20.00): array
    {
        $subtotal = $cantidad * $precio;
        $res = $this->ventasModel->insertVenta(
            [
                'fecha_venta'                  => date('Y-m-d'),
                'idcliente'                    => $idCliente,
                'idmoneda_general'             => 3,
                'subtotal_general'             => $subtotal,
                'descuento_porcentaje_general' => 0,
                'monto_descuento_general'      => 0,
                'estatus'                      => 'BORRADOR',
                'total_general'                => $subtotal,
                'observaciones'                => 'Venta base edición',
                'tasa_usada'                   => 1,
            ],
            [[
                'idproducto'            => 1,
                'cantidad'              => $cantidad,
                'precio_unitario_venta' => $precio,
                'subtotal_general'      => $subtotal,
                'id_moneda_detalle'     => 3,
            ]]
        );
        $this->assertTrue($res['success'], 'Fixture: no se pudo crear venta BORRADOR. ' . ($res['message'] ?? ''));
        return $res;
    }

    // ─────────────────────────────────────────────
    // Test 1 — Edición exitosa: total, observaciones y detalles actualizados en BD
    // ─────────────────────────────────────────────

    #[Test]
    public function testEditarVenta_PersisteCambiosEnBD(): void
    {
        $idCliente = $this->idClienteActivo();
        $insercion = $this->crearVentaBorrador($idCliente);
        $idVenta   = $insercion['idventa'];

        $nuevaCantidad = 5;
        $nuevoPrecio   = 25.00;
        $nuevoTotal    = $nuevaCantidad * $nuevoPrecio;

        $resultado = $this->ventasModel->updateVenta($idVenta, [
            'fecha_venta'                  => date('Y-m-d'),
            'idcliente'                    => $idCliente,
            'idmoneda_general'             => 3,
            'subtotal_general'             => $nuevoTotal,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general'      => 0,
            'estatus'                      => 'BORRADOR',
            'total_general'                => $nuevoTotal,
            'observaciones'                => 'Venta editada OK',
            'tasa_usada'                   => 1,
            'detalles'                     => [[
                'idproducto'            => 1,
                'cantidad'              => $nuevaCantidad,
                'precio_unitario_venta' => $nuevoPrecio,
                'subtotal_general'      => $nuevoTotal,
                'id_moneda_detalle'     => 3,
            ]],
        ]);

        $this->assertTrue($resultado['success'], $resultado['message'] ?? '');

        $ventaDB = $this->ventasModel->obtenerVentaPorId($idVenta);
        $this->assertEquals($nuevoTotal, (float) $ventaDB['total_general'], 'total_general no actualizado.');
        $this->assertEquals('Venta editada OK', $ventaDB['observaciones'], 'observaciones no actualizadas.');

        $detalles = $this->ventasModel->obtenerDetalleVenta($idVenta);
        $this->assertCount(1, $detalles, 'Debe haber exactamente 1 detalle tras la edición.');
        $this->assertEquals($nuevaCantidad, (int) $detalles[0]['cantidad'], 'La cantidad del detalle no coincide.');
    }

    // ─────────────────────────────────────────────
    // Test 2 — Edición con nuevos detalles: stock revertido y reinserido correctamente
    // ─────────────────────────────────────────────

    #[Test]
    public function testEditarVenta_ActualizaStockCorrectamente(): void
    {
        $idCliente      = $this->idClienteActivo();
        $cantidadOriginal = 3;
        $insercion      = $this->crearVentaBorrador($idCliente, $cantidadOriginal);
        $idVenta        = $insercion['idventa'];

        // Después de la inserción: stock = 999 - 3 = 996
        $stockTrasInsercion = $this->stockActual(1);

        $nuevaCantidad = 7;

        $resultado = $this->ventasModel->updateVenta($idVenta, [
            'fecha_venta'                  => date('Y-m-d'),
            'idcliente'                    => $idCliente,
            'idmoneda_general'             => 3,
            'subtotal_general'             => $nuevaCantidad * 20.00,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general'      => 0,
            'estatus'                      => 'BORRADOR',
            'total_general'                => $nuevaCantidad * 20.00,
            'observaciones'                => 'Test stock edición',
            'tasa_usada'                   => 1,
            'detalles'                     => [[
                'idproducto'            => 1,
                'cantidad'              => $nuevaCantidad,
                'precio_unitario_venta' => 20.00,
                'subtotal_general'      => $nuevaCantidad * 20.00,
                'id_moneda_detalle'     => 3,
            ]],
        ]);

        $this->assertTrue($resultado['success'], $resultado['message'] ?? '');

        // Stock esperado: (999 - cantidadOriginal) revierte cantidadOriginal + deduce nuevaCantidad
        // = 999 - 3 = 996  →  +3 (reverso) = 999  →  -7 (nueva deducción) = 992
        $stockEsperado = 999 - $nuevaCantidad;
        $this->assertEquals(
            $stockEsperado,
            $this->stockActual(1),
            "El stock tras editar debe ser {$stockEsperado}."
        );
    }

    // ─────────────────────────────────────────────
    // Test 3 — No se puede editar venta en estado POR_PAGAR
    // ─────────────────────────────────────────────

    #[Test]
    public function testEditarVenta_EstadoPorPagar_Falla(): void
    {
        $idCliente = $this->idClienteActivo();
        $insercion = $this->crearVentaBorrador($idCliente);
        $idVenta   = $insercion['idventa'];

        // Cambiar estado directamente a POR_PAGAR (sin pasar por el modelo)
        $this->pdo->exec("UPDATE venta SET estatus = 'POR_PAGAR' WHERE idventa = {$idVenta}");

        $resultado = $this->ventasModel->updateVenta($idVenta, [
            'observaciones' => 'Intento edición en POR_PAGAR',
            'total_general' => 999.00,
        ]);

        $this->assertFalse($resultado['success'],
            'No debe poder editarse una venta en estado POR_PAGAR.');
        $this->assertArrayHasKey('message', $resultado);
    }

    // ─────────────────────────────────────────────
    // Test 4 — Venta inexistente: rechazada
    // ─────────────────────────────────────────────

    #[Test]
    public function testEditarVenta_Inexistente_Falla(): void
    {
        $resultado = $this->ventasModel->updateVenta(888888, [
            'observaciones' => 'Test venta no existe',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertArrayHasKey('message', $resultado);
    }

    // ─────────────────────────────────────────────
    // Test 5 — Detalles con precio negativo: rechazado
    // ─────────────────────────────────────────────

    #[Test]
    public function testEditarVenta_PrecioNegativo_Falla(): void
    {
        $idCliente = $this->idClienteActivo();
        $insercion = $this->crearVentaBorrador($idCliente);
        $idVenta   = $insercion['idventa'];

        $resultado = $this->ventasModel->updateVenta($idVenta, [
            'detalles' => [[
                'idproducto'            => 1,
                'cantidad'              => 1,
                'precio_unitario_venta' => -100.00,
                'subtotal_general'      => -100.00,
                'id_moneda_detalle'     => 3,
            ]],
        ]);

        $this->assertFalse($resultado['success'],
            'Un precio negativo en detalle debe rechazarse.');
        $this->assertStringContainsStringIgnoringCase('precio', $resultado['message']);
    }

    // ─────────────────────────────────────────────
    // Test 6 — Detalles con cantidad negativa: rechazado
    // ─────────────────────────────────────────────

    #[Test]
    public function testEditarVenta_CantidadNegativa_Falla(): void
    {
        $idCliente = $this->idClienteActivo();
        $insercion = $this->crearVentaBorrador($idCliente);
        $idVenta   = $insercion['idventa'];

        $resultado = $this->ventasModel->updateVenta($idVenta, [
            'detalles' => [[
                'idproducto'            => 1,
                'cantidad'              => -5,
                'precio_unitario_venta' => 20.00,
                'subtotal_general'      => -100.00,
                'id_moneda_detalle'     => 3,
            ]],
        ]);

        $this->assertFalse($resultado['success'],
            'Una cantidad negativa en detalle debe rechazarse.');
        $this->assertStringContainsStringIgnoringCase('cantidad', $resultado['message']);
    }

    // ─────────────────────────────────────────────
    // Test 7 — Producto inexistente en detalle: rechazado
    // ─────────────────────────────────────────────

    #[Test]
    public function testEditarVenta_ProductoInexistente_Falla(): void
    {
        $idCliente = $this->idClienteActivo();
        $insercion = $this->crearVentaBorrador($idCliente);
        $idVenta   = $insercion['idventa'];

        $resultado = $this->ventasModel->updateVenta($idVenta, [
            'detalles' => [[
                'idproducto'            => 888888,
                'cantidad'              => 1,
                'precio_unitario_venta' => 100.00,
                'subtotal_general'      => 100.00,
                'id_moneda_detalle'     => 3,
            ]],
        ]);

        $this->assertFalse($resultado['success'],
            'Un producto inexistente en detalle debe rechazarse.');
        $this->assertArrayHasKey('message', $resultado);
    }
}
