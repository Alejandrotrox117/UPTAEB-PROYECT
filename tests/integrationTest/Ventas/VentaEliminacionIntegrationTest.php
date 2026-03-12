<?php

namespace Tests\IntegrationTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\VentasModel;
use App\Core\Conexion;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

/**
 * Tests de integración: eliminación de ventas (eliminarVenta).
 *
 * Qué se verifica:
 *  - La venta queda en estado inactivo (no borrada físicamente).
 *  - El stock se restaura exactamente en la cantidad vendida.
 *  - Se registra un movimiento de devolución en movimientos_existencia.
 *  - No se puede eliminar una venta en estado != BORRADOR.
 *  - Eliminar una venta inexistente devuelve success=false.
 */
class VentaEliminacionIntegrationTest extends TestCase
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

    /** Crea una venta BORRADOR con la cantidad indicada del producto 1. */
    private function crearVentaBorrador(int $idCliente, int $cantidad = 5): array
    {
        $precio   = 10.00;
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
                'observaciones'                => 'Venta fixture eliminación',
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
    // Test 1 — Eliminación exitosa: estado cambia a inactivo
    // ─────────────────────────────────────────────

    #[Test]
    public function testEliminarVenta_EstadoCambiaAInactivo(): void
    {
        $idCliente = $this->idClienteActivo();
        $insercion = $this->crearVentaBorrador($idCliente);
        $idVenta   = $insercion['idventa'];

        $resultado = $this->ventasModel->eliminarVenta($idVenta);

        $this->assertTrue($resultado['success'], $resultado['message'] ?? '');

        $stmt = $this->pdo->prepare("SELECT estatus FROM venta WHERE idventa = ?");
        $stmt->execute([$idVenta]);
        $estatus = strtolower($stmt->fetchColumn());
        $this->assertEquals('inactivo', $estatus,
            'La venta eliminada debe quedar en estado inactivo.');
    }

    // ─────────────────────────────────────────────
    // Test 2 — Stock se restaura exactamente al eliminar
    // ─────────────────────────────────────────────

    #[Test]
    public function testEliminarVenta_RestauráStockExacto(): void
    {
        $idCliente       = $this->idClienteActivo();
        $cantidad        = 7;
        $stockBase       = $this->stockActual(1);  // 999

        $insercion       = $this->crearVentaBorrador($idCliente, $cantidad);
        $stockTrasVenta  = $this->stockActual(1);  // 999 - 7 = 992

        $this->assertEquals($stockBase - $cantidad, $stockTrasVenta,
            'El stock tras crear la venta debería ser 999 - 7 = 992.');

        $resultado = $this->ventasModel->eliminarVenta($insercion['idventa']);
        $this->assertTrue($resultado['success'], $resultado['message'] ?? '');

        // Tras la eliminación el stock debe volver a 999
        $this->assertEquals(
            $stockBase,
            $this->stockActual(1),
            'El stock debe restaurarse al valor previo a la venta.'
        );
    }

    // ─────────────────────────────────────────────
    // Test 3 — Movimiento de devolución registrado
    // ─────────────────────────────────────────────

    #[Test]
    public function testEliminarVenta_RegistraMovimientoDevolucion(): void
    {
        $idCliente = $this->idClienteActivo();
        $insercion = $this->crearVentaBorrador($idCliente, 4);
        $idVenta   = $insercion['idventa'];

        $resultado = $this->ventasModel->eliminarVenta($idVenta);
        $this->assertTrue($resultado['success'], $resultado['message'] ?? '');

        // El modelo registra la devolución con estatus='activo' y observaciones 'Entrada por cancelación de venta'
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM movimientos_existencia
              WHERE idventa = ? AND cantidad_entrada > 0"
        );
        $stmt->execute([$idVenta]);
        $total = (int) $stmt->fetchColumn();

        $this->assertGreaterThan(0, $total,
            'Debe registrarse al menos un movimiento de entrada (devolución) al eliminar la venta.');
    }

    // ─────────────────────────────────────────────
    // Test 4 — No se puede eliminar venta en estado POR_PAGAR
    // ─────────────────────────────────────────────

    #[Test]
    public function testEliminarVenta_EstadoPorPagar_Falla(): void
    {
        $idCliente = $this->idClienteActivo();
        $insercion = $this->crearVentaBorrador($idCliente);
        $idVenta   = $insercion['idventa'];

        // Forzar el estado a POR_PAGAR vía SQL directo
        $this->pdo->exec("UPDATE venta SET estatus = 'POR_PAGAR' WHERE idventa = {$idVenta}");

        $resultado = $this->ventasModel->eliminarVenta($idVenta);

        $this->assertFalse($resultado['success'],
            'No debe poder eliminarse una venta en estado POR_PAGAR.');
        $this->assertArrayHasKey('message', $resultado);
    }

    // ─────────────────────────────────────────────
    // Test 5 — Venta inexistente: rechazada
    // ─────────────────────────────────────────────

    #[Test]
    public function testEliminarVenta_Inexistente_Falla(): void
    {
        $resultado = $this->ventasModel->eliminarVenta(888888);

        $this->assertFalse($resultado['success'],
            'Eliminar una venta inexistente debe devolver success=false.');
        $this->assertArrayHasKey('message', $resultado);
    }
}
