<?php

namespace Tests\IntegrationTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\VentasModel;
use App\Models\ProductosModel;
use App\Models\ClientesModel;
use App\Core\Conexion;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

/**
 * Tests de integración: creación de ventas.
 *
 * Qué se verifica en cada test:
 *  - La venta queda persistida en BD con los valores enviados.
 *  - El stock del producto se reduce exactamente en la cantidad vendida.
 *  - El movimiento de inventario queda registrado en movimientos_existencia.
 *  - balance inicial == total_general (regla de negocio).
 *  - El modelo rechaza datos inválidos con success=false y mensaje descriptivo.
 */
class VentaCreacionIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private VentasModel $ventasModel;
    private ProductosModel $productosModel;
    private \PDO $pdo;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->ventasModel    = new VentasModel();
        $this->productosModel = new ProductosModel();

        // PDO directo para consultar efectos reales en la BD de test
        $conexion = new Conexion();
        $conexion->connect();
        $this->pdo = $conexion->get_conectGeneral();
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Stock conocido antes de cada test
        $this->pdo->exec('UPDATE producto SET existencia = 999 WHERE idproducto = 1');
    }

    protected function tearDown(): void
    {
        unset($this->ventasModel, $this->productosModel, $this->pdo);
    }

    // ─────────────────────────────────────────────
    // Helpers internos
    // ─────────────────────────────────────────────

    /** Devuelve idcliente de un cliente ACTIVO de la BD de test. */
    private function idClienteActivo(): int
    {
        $stmt = $this->pdo->query(
            "SELECT idcliente FROM cliente WHERE UPPER(estatus) = 'ACTIVO' LIMIT 1"
        );
        $id = $stmt->fetchColumn();
        $this->assertNotFalse($id, 'No existe ningún cliente activo en la BD de test.');
        return (int) $id;
    }

    /** Existencias actuales de un producto. */
    private function stockActual(int $idproducto): float
    {
        $stmt = $this->pdo->prepare('SELECT existencia FROM producto WHERE idproducto = ?');
        $stmt->execute([$idproducto]);
        return (float) $stmt->fetchColumn();
    }

    /** Número de movimientos de salida activos asociados a una venta. */
    private function movimientosSalidaDeVenta(int $idventa): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM movimientos_existencia
              WHERE idventa = ? AND estatus = 'activo'"
        );
        $stmt->execute([$idventa]);
        return (int) $stmt->fetchColumn();
    }

    /** Arma los datos principales de la venta. */
    private function datosVenta(int $idCliente, float $subtotal, float $total,
                                int $descPct = 0, float $descMonto = 0.0): array
    {
        return [
            'fecha_venta'                  => date('Y-m-d'),
            'idcliente'                    => $idCliente,
            'idmoneda_general'             => 3,
            'subtotal_general'             => $subtotal,
            'descuento_porcentaje_general' => $descPct,
            'monto_descuento_general'      => $descMonto,
            'estatus'                      => 'BORRADOR',
            'total_general'                => $total,
            'observaciones'                => 'Test integración ' . date('His'),
            'tasa_usada'                   => 1,
        ];
    }

    /** Arma el array de detalles con un único producto. */
    private function detalles(int $idProducto, int $cantidad, float $precio): array
    {
        return [[
            'idproducto'            => $idProducto,
            'cantidad'              => $cantidad,
            'precio_unitario_venta' => $precio,
            'subtotal_general'      => $cantidad * $precio,
            'id_moneda_detalle'     => 3,
        ]];
    }

    // ─────────────────────────────────────────────
    // Test 1 — Creación exitosa: nro_venta, stock, movimiento, balance
    // ─────────────────────────────────────────────

    #[Test]
    public function testCrearVenta_ReduceStockYRegistraMovimiento(): void
    {
        $idCliente  = $this->idClienteActivo();
        $stockAntes = $this->stockActual(1);   // 999 tras setUp
        $cantidad   = 5;
        $precio     = 20.00;

        $resultado = $this->ventasModel->insertVenta(
            $this->datosVenta($idCliente, $cantidad * $precio, $cantidad * $precio),
            $this->detalles(1, $cantidad, $precio)
        );

        $this->assertTrue($resultado['success'], $resultado['message'] ?? '(sin mensaje)');
        $this->assertArrayHasKey('idventa', $resultado);
        $this->assertMatchesRegularExpression('/^VT\d+$/', $resultado['nro_venta']);

        $idventa = $resultado['idventa'];

        // La venta existe en BD con los campos clave correctos
        $venta = $this->ventasModel->obtenerVentaPorId($idventa);
        $this->assertNotEmpty($venta, 'La venta no se encontró en BD.');
        $this->assertEquals($idCliente, (int) $venta['idcliente']);
        $this->assertEquals($cantidad * $precio, (float) $venta['total_general']);

        // REGLA DE NEGOCIO: balance inicial debe ser igual al total_general
        $this->assertEquals(
            (float) $venta['total_general'],
            (float) $venta['balance'],
            'El balance inicial debe ser igual al total_general.'
        );

        // INVENTARIO: stock bajó exactamente en la cantidad vendida
        $this->assertEquals(
            $stockAntes - $cantidad,
            $this->stockActual(1),
            "Stock esperado: {$stockAntes} - {$cantidad}. Stock real: {$this->stockActual(1)}."
        );

        // MOVIMIENTO: al menos un movimiento de salida creado
        $this->assertGreaterThan(
            0,
            $this->movimientosSalidaDeVenta($idventa),
            'Debe existir al menos un movimiento de inventario para la venta creada.'
        );
    }

    // ─────────────────────────────────────────────
    // Test 2 — Venta con descuento: total y monto persistidos correctamente
    // ─────────────────────────────────────────────

    #[Test]
    public function testCrearVentaConDescuento_TotalEnBDEsCorrecto(): void
    {
        $idCliente = $this->idClienteActivo();
        $subtotal  = 200.00;
        $descMonto = 20.00;   // 10 %
        $total     = $subtotal - $descMonto;

        $resultado = $this->ventasModel->insertVenta(
            $this->datosVenta($idCliente, $subtotal, $total, 10, $descMonto),
            $this->detalles(1, 10, 20.00)
        );

        $this->assertTrue($resultado['success'], $resultado['message'] ?? '');

        $venta = $this->ventasModel->obtenerVentaPorId($resultado['idventa']);
        $this->assertEquals($total,     (float) $venta['total_general'],   'total_general incorrecto en BD.');
        $this->assertEquals($descMonto, (float) $venta['monto_descuento_general'], 'monto_descuento incorrecto.');
        $this->assertEquals(10,         (float) $venta['descuento_porcentaje_general'], 'descuento_% incorrecto.');
        $this->assertEquals($total,     (float) $venta['balance'], 'balance debe ser igual al total con descuento.');
    }

    // ─────────────────────────────────────────────
    // Test 3 — Descuento mayor al subtotal: debe rechazarse
    // ─────────────────────────────────────────────

    #[Test]
    public function testCrearVenta_DescuentoMayorAlSubtotal_Falla(): void
    {
        $idCliente = $this->idClienteActivo();
        $subtotal  = 100.00;
        $descuento = 150.00;   // mayor al subtotal

        $resultado = $this->ventasModel->insertVenta(
            $this->datosVenta($idCliente, $subtotal, $subtotal - $descuento, 0, $descuento),
            $this->detalles(1, 1, 100.00)
        );

        $this->assertFalse($resultado['success'],
            'No debe permitirse un descuento mayor al subtotal.');
        $this->assertArrayHasKey('message', $resultado);
    }

    // ─────────────────────────────────────────────
    // Test 4 — Stock insuficiente: rechazado sin alterar existencias
    // ─────────────────────────────────────────────

    #[Test]
    public function testCrearVenta_StockInsuficiente_FallaYNoAlteraStock(): void
    {
        $idCliente = $this->idClienteActivo();

        // Forzar stock bajo
        $this->pdo->exec('UPDATE producto SET existencia = 2 WHERE idproducto = 1');
        $stockAntes = $this->stockActual(1);   // 2

        $resultado = $this->ventasModel->insertVenta(
            $this->datosVenta($idCliente, 500.00, 500.00),
            $this->detalles(1, 50, 10.00)      // 50 > stock(2)
        );

        $this->assertFalse($resultado['success'],
            'No debe permitir vender más cantidad que el stock disponible.');

        // El stock NO debe haberse modificado
        $this->assertEquals($stockAntes, $this->stockActual(1),
            'El stock no debe cambiar cuando la operación falla.');
    }

    // ─────────────────────────────────────────────
    // Test 5 — Cliente inactivo: rechazado
    // ─────────────────────────────────────────────

    #[Test]
    public function testCrearVenta_ClienteInactivo_Falla(): void
    {
        // Crear cliente inactivo temporal
        $this->pdo->exec(
            "INSERT INTO cliente (cedula, nombre, apellido, direccion, estatus, telefono_principal)
             VALUES ('TEST9999', 'Test', 'Inactivo', 'Dir. test', 'inactivo', '04140000000')"
        );
        $idInactivo = (int) $this->pdo->lastInsertId();

        try {
            $resultado = $this->ventasModel->insertVenta(
                $this->datosVenta($idInactivo, 100.00, 100.00),
                $this->detalles(1, 2, 50.00)
            );

            $this->assertFalse($resultado['success'],
                'No debe permitirse una venta para un cliente inactivo.');
            $this->assertStringContainsStringIgnoringCase('inactivo', $resultado['message']);
        } finally {
            $this->pdo->exec("DELETE FROM cliente WHERE idcliente = {$idInactivo}");
        }
    }

    // ─────────────────────────────────────────────
    // Test 6 — Cliente inexistente: rechazado
    // ─────────────────────────────────────────────

    #[Test]
    public function testCrearVenta_ClienteInexistente_Falla(): void
    {
        $resultado = $this->ventasModel->insertVenta(
            $this->datosVenta(888888, 100.00, 100.00),
            $this->detalles(1, 1, 100.00)
        );

        $this->assertFalse($resultado['success']);
        $this->assertArrayHasKey('message', $resultado);
    }

    // ─────────────────────────────────────────────
    // Test 7 — Producto inactivo: rechazado
    // ─────────────────────────────────────────────

    #[Test]
    public function testCrearVenta_ProductoInactivo_Falla(): void
    {
        $idCliente = $this->idClienteActivo();

        // Crear producto inactivo temporal
        $this->pdo->exec(
            "INSERT INTO producto (nombre, descripcion, unidad_medida, precio, idcategoria, moneda, estatus, existencia)
             VALUES ('ProdTestInactivo', 'Test', 'UND', 10.00, 1, 'USD', 'inactivo', 100)"
        );
        $idProdInactivo = (int) $this->pdo->lastInsertId();

        try {
            $resultado = $this->ventasModel->insertVenta(
                $this->datosVenta($idCliente, 100.00, 100.00),
                $this->detalles($idProdInactivo, 1, 100.00)
            );

            $this->assertFalse($resultado['success'],
                'No debe permitirse vender un producto inactivo.');
            $this->assertArrayHasKey('message', $resultado);
        } finally {
            $this->pdo->exec("DELETE FROM producto WHERE idproducto = {$idProdInactivo}");
        }
    }

    // ─────────────────────────────────────────────
    // Test 8 — Producto inexistente en detalle
    // ─────────────────────────────────────────────

    #[Test]
    public function testCrearVenta_ProductoInexistente_Falla(): void
    {
        $idCliente = $this->idClienteActivo();

        $resultado = $this->ventasModel->insertVenta(
            $this->datosVenta($idCliente, 100.00, 100.00),
            $this->detalles(888888, 1, 100.00)
        );

        $this->assertFalse($resultado['success']);
        $this->assertArrayHasKey('message', $resultado);
    }

    // ─────────────────────────────────────────────
    // Test 9 — Detalles con datos inválidos (DataProvider)
    // ─────────────────────────────────────────────

    public static function providerDetallesInvalidos(): array
    {
        return [
            'cantidad cero'     => [0,   10.00, 'cantidad'],
            'cantidad negativa' => [-3,  10.00, 'cantidad'],
            'precio cero'       => [2,    0.00, 'precio'],
            'precio negativo'   => [2,  -10.00, 'precio'],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('providerDetallesInvalidos')]
    public function testCrearVenta_DetallesInvalidos_Falla(
        int $cantidad, float $precio, string $campoEsperado
    ): void {
        $idCliente = $this->idClienteActivo();

        $resultado = $this->ventasModel->insertVenta(
            $this->datosVenta($idCliente, abs($cantidad * $precio), abs($cantidad * $precio)),
            $this->detalles(1, $cantidad, $precio)
        );

        $this->assertFalse($resultado['success'],
            "Una venta con {$campoEsperado} inválido debe fallar.");
        $this->assertStringContainsStringIgnoringCase($campoEsperado, $resultado['message'],
            "El mensaje de error debe mencionar '{$campoEsperado}'.");
    }
}
