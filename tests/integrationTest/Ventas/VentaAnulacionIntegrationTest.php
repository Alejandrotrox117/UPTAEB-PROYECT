<?php

namespace Tests\IntegrationTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\VentasModel;
use App\Core\Conexion;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

/**
 * Tests de integración: anulación de ventas (anularVenta).
 *
 * Qué se verifica:
 *  - La venta queda en estado ANULADA en BD.
 *  - El stock se restaura exactamente en la cantidad vendida.
 *  - Las observaciones incluyen el motivo de anulación.
 *  - No se puede anular una venta ya anulada (idempotencia).
 *  - Anular un ID inexistente devuelve success=false.
 */
class VentaAnulacionIntegrationTest extends TestCase
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

    /** Crea una venta BORRADOR y la pasa a POR_PAGAR mediante SQL directo. */
    private function crearVentaPorPagar(int $idCliente, int $cantidad = 5): int
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
                'observaciones'                => 'Venta fixture anulación',
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
        $this->assertTrue($res['success'], 'Fixture: no se pudo crear venta BORRADOR.');
        $idVenta = $res['idventa'];

        // Pasar a POR_PAGAR para que sea anulable
        $this->pdo->exec("UPDATE venta SET estatus = 'POR_PAGAR' WHERE idventa = {$idVenta}");

        return $idVenta;
    }

    // ─────────────────────────────────────────────
    // Test 1 — Anulación exitosa: estado cambia a ANULADA
    // ─────────────────────────────────────────────

    #[Test]
    public function testAnularVenta_EstadoCambiaAAnulada(): void
    {
        $idCliente = $this->idClienteActivo();
        $idVenta   = $this->crearVentaPorPagar($idCliente);

        $resultado = $this->ventasModel->anularVenta($idVenta, 'Anulación de prueba de integración');

        $this->assertTrue($resultado['success'], $resultado['message'] ?? '');

        $stmt = $this->pdo->prepare("SELECT estatus FROM venta WHERE idventa = ?");
        $stmt->execute([$idVenta]);
        $estatus = strtoupper($stmt->fetchColumn());
        $this->assertEquals('ANULADA', $estatus,
            'La venta anulada debe quedar en estado ANULADA en BD.');
    }

    // ─────────────────────────────────────────────
    // Test 2 — Stock restaurado exactamente tras anular
    // ─────────────────────────────────────────────

    #[Test]
    public function testAnularVenta_RestauráStockExacto(): void
    {
        $idCliente = $this->idClienteActivo();
        $cantidad  = 6;
        $stockBase = $this->stockActual(1); // 999

        $idVenta       = $this->crearVentaPorPagar($idCliente, $cantidad);
        $stockTrasVenta = $this->stockActual(1); // 999 - 6 = 993

        $this->assertEquals($stockBase - $cantidad, $stockTrasVenta);

        $resultado = $this->ventasModel->anularVenta($idVenta, 'Prueba restauración stock');
        $this->assertTrue($resultado['success'], $resultado['message'] ?? '');

        $this->assertEquals(
            $stockBase,
            $this->stockActual(1),
            'El stock debe restaurarse al valor previo a la venta.'
        );
    }

    // ─────────────────────────────────────────────
    // Test 3 — Observaciones contienen el motivo indicado
    // ─────────────────────────────────────────────

    #[Test]
    public function testAnularVenta_ObservacionesContienenMotivo(): void
    {
        $idCliente = $this->idClienteActivo();
        $idVenta   = $this->crearVentaPorPagar($idCliente);
        $motivo    = 'Error en el pedido del cliente';

        $resultado = $this->ventasModel->anularVenta($idVenta, $motivo);
        $this->assertTrue($resultado['success'], $resultado['message'] ?? '');

        $stmt = $this->pdo->prepare("SELECT observaciones FROM venta WHERE idventa = ?");
        $stmt->execute([$idVenta]);
        $observaciones = $stmt->fetchColumn();

        $this->assertStringContainsString($motivo, $observaciones,
            'Las observaciones deben contener el motivo de anulación.');
    }

    // ─────────────────────────────────────────────
    // Test 4 — No se puede re-anular una venta ya anulada
    // ─────────────────────────────────────────────

    #[Test]
    public function testAnularVenta_VentaYaAnulada_Falla(): void
    {
        $idCliente = $this->idClienteActivo();
        $idVenta   = $this->crearVentaPorPagar($idCliente);

        // Primera anulación (debe tener éxito)
        $primera = $this->ventasModel->anularVenta($idVenta, 'Primera anulación');
        $this->assertTrue($primera['success'], 'La primera anulación debe tener éxito.');

        // Segunda anulación (debe fallar)
        $segunda = $this->ventasModel->anularVenta($idVenta, 'Segunda anulación');
        $this->assertFalse($segunda['success'],
            'No debe poder anularse una venta que ya está ANULADA.');
        $this->assertArrayHasKey('message', $segunda);
    }

    // ─────────────────────────────────────────────
    // Test 5 — ID inexistente: rechazado
    // ─────────────────────────────────────────────

    #[Test]
    public function testAnularVenta_Inexistente_Falla(): void
    {
        $resultado = $this->ventasModel->anularVenta(888888, 'Motivo cualquiera');

        $this->assertFalse($resultado['success'],
            'Anular una venta inexistente debe devolver success=false.');
        $this->assertArrayHasKey('message', $resultado);
    }

    // ─────────────────────────────────────────────
    // Test 6 — Anular venta en BORRADOR: verifica comportamiento real del modelo
    // ─────────────────────────────────────────────

    #[Test]
    public function testAnularVenta_EnBorrador_ComportamientoConsistente(): void
    {
        $idCliente = $this->idClienteActivo();
        $stockAntes = $this->stockActual(1); // 999

        $res = $this->ventasModel->insertVenta(
            [
                'fecha_venta'                  => date('Y-m-d'),
                'idcliente'                    => $idCliente,
                'idmoneda_general'             => 3,
                'subtotal_general'             => 50.00,
                'descuento_porcentaje_general' => 0,
                'monto_descuento_general'      => 0,
                'estatus'                      => 'BORRADOR',
                'total_general'                => 50.00,
                'observaciones'                => 'Fixture borrador anulación',
                'tasa_usada'                   => 1,
            ],
            [[
                'idproducto'            => 1,
                'cantidad'              => 5,
                'precio_unitario_venta' => 10.00,
                'subtotal_general'      => 50.00,
                'id_moneda_detalle'     => 3,
            ]]
        );
        $this->assertTrue($res['success'], 'Fixture: no se pudo crear venta BORRADOR.');
        $idVenta = $res['idventa'];

        $resultado = $this->ventasModel->anularVenta($idVenta, 'Intentar anular borrador');

        // El modelo devuelve siempre un array con success y message
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('success', $resultado);
        $this->assertArrayHasKey('message', $resultado);

        if ($resultado['success']) {
            // Si el modelo lo permite: debe quedar ANULADA y el stock debe restaurarse
            $stmt = $this->pdo->prepare('SELECT estatus FROM venta WHERE idventa = ?');
            $stmt->execute([$idVenta]);
            $this->assertEquals('ANULADA', strtoupper($stmt->fetchColumn()),
                'Si el modelo permite anular desde BORRADOR, el estatus debe ser ANULADA.');
            $this->assertEquals($stockAntes, $this->stockActual(1),
                'Si se anuló, el stock debe haber sido restaurado.');
        } else {
            // Si el modelo lo rechaza: el stock NO debe haber cambiado (quedó en 999-5=994)
            $this->assertNotEquals($stockAntes, $this->stockActual(1) + 0,
                'La venta fue creada, por lo que el stock ya bajó 5 unidades.');
        }
    }
}
