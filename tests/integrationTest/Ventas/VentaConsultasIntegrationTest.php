<?php

namespace Tests\IntegrationTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\VentasModel;
use App\Core\Conexion;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

/**
 * Tests de integración: consultas de ventas (getVentasDatatable, obtenerVentaPorId, etc.).
 *
 * Qué se verifica:
 *  - getVentasDatatable retorna array no vacío y con la estructura de columnas correcta
 *    (se crea una venta en setUp para garantizar al menos 1 registro).
 *  - obtenerVentaPorId con un ID real devuelve la venta con campos correctos.
 *  - obtenerVentaPorId con un ID inexistente devuelve false/null.
 *  - obtenerDetalleVenta con un ID real devuelve los detalles con sus columnas.
 *  - obtenerDetalleVenta con un ID inexistente devuelve array vacío.
 *  - getMonedasActivas devuelve al menos una moneda.
 */
class VentaConsultasIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private VentasModel $ventasModel;
    private \PDO $pdo;

    /** ID de la venta creada en setUp para los tests de consulta. */
    private int $idVentaFixture;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->ventasModel = new VentasModel();

        $conexion = new Conexion();
        $conexion->connect();
        $this->pdo = $conexion->get_conectGeneral();
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec('UPDATE producto SET existencia = 999 WHERE idproducto = 1');

        // Crear una venta de referencia para que los tests de consulta siempre
        // tengan al menos un registro real con el que trabajar.
        $idCliente = (int) $this->pdo
            ->query("SELECT idcliente FROM cliente WHERE UPPER(estatus)='ACTIVO' LIMIT 1")
            ->fetchColumn();

        $res = $this->ventasModel->insertVenta(
            [
                'fecha_venta'                  => date('Y-m-d'),
                'idcliente'                    => $idCliente,
                'idmoneda_general'             => 3,
                'subtotal_general'             => 40.00,
                'descuento_porcentaje_general' => 0,
                'monto_descuento_general'      => 0,
                'estatus'                      => 'BORRADOR',
                'total_general'                => 40.00,
                'observaciones'                => 'Fixture consultas',
                'tasa_usada'                   => 1,
            ],
            [[
                'idproducto'            => 1,
                'cantidad'              => 2,
                'precio_unitario_venta' => 20.00,
                'subtotal_general'      => 40.00,
                'id_moneda_detalle'     => 3,
            ]]
        );

        $this->assertTrue($res['success'], 'setUp: no se pudo crear la venta de referencia.');
        $this->idVentaFixture = $res['idventa'];
    }

    protected function tearDown(): void
    {
        unset($this->ventasModel, $this->pdo);
    }

    // ─────────────────────────────────────────────
    // Test 1 — getVentasDatatable: array no vacío con estructura correcta
    // ─────────────────────────────────────────────

    #[Test]
    public function testGetVentasDatatable_RetornaRegistrosConEstructuraCorrecta(): void
    {
        $result = $this->ventasModel->getVentasDatatable();

        $this->assertIsArray($result, 'getVentasDatatable debe retornar un array.');
        $this->assertNotEmpty($result,
            'El datatable debe tener al menos 1 registro (el creado en setUp).');

        $primera = $result[0];
        foreach (['idventa', 'nro_venta', 'fecha_venta', 'total_general', 'estatus'] as $campo) {
            $this->assertArrayHasKey($campo, $primera,
                "El datatable debe incluir la columna '{$campo}'.");
        }
    }

    // ─────────────────────────────────────────────
    // Test 2 — obtenerVentaPorId: ID real devuelve la venta correcta
    // ─────────────────────────────────────────────

    #[Test]
    public function testObtenerVentaPorId_IDReal_RetornaVentaConCamposCorrectos(): void
    {
        $venta = $this->ventasModel->obtenerVentaPorId($this->idVentaFixture);

        $this->assertNotEmpty($venta, 'Debe retornar la venta para un ID existente.');
        $this->assertEquals($this->idVentaFixture, (int) $venta['idventa']);
        $this->assertMatchesRegularExpression('/^VT\d+$/', $venta['nro_venta']);
        $this->assertEquals(40.00, (float) $venta['total_general']);
        $this->assertEquals(40.00, (float) $venta['balance'],
            'El balance de la venta fixture debe ser 40.00.');
        $this->assertEquals('BORRADOR', strtoupper($venta['estatus']));
    }

    // ─────────────────────────────────────────────
    // Test 3 — obtenerVentaPorId: ID inexistente devuelve false
    // ─────────────────────────────────────────────

    #[Test]
    public function testObtenerVentaPorId_Inexistente_RetornaFalse(): void
    {
        $result = $this->ventasModel->obtenerVentaPorId(888888);

        $this->assertFalse((bool) $result,
            'Un ID inexistente debe retornar false o null/vacío.');
    }

    // ─────────────────────────────────────────────
    // Test 4 — obtenerDetalleVenta: ID real devuelve detalles con estructura
    // ─────────────────────────────────────────────

    #[Test]
    public function testObtenerDetalleVenta_IDReal_RetornaDetallesConCamposCorrectos(): void
    {
        $detalles = $this->ventasModel->obtenerDetalleVenta($this->idVentaFixture);

        $this->assertIsArray($detalles);
        $this->assertNotEmpty($detalles, 'Debe haber al menos 1 detalle para la venta fixture.');

        $det = $detalles[0];
        foreach (['idproducto', 'cantidad', 'precio_unitario_venta'] as $campo) {
            $this->assertArrayHasKey($campo, $det,
                "El detalle debe incluir la columna '{$campo}'.");
        }
        $this->assertEquals(1, (int) $det['idproducto']);
        $this->assertEquals(2, (int) $det['cantidad']);
        $this->assertEquals(20.00, (float) $det['precio_unitario_venta']);
    }

    // ─────────────────────────────────────────────
    // Test 5 — obtenerDetalleVenta: ID inexistente devuelve array vacío
    // ─────────────────────────────────────────────

    #[Test]
    public function testObtenerDetalleVenta_Inexistente_RetornaArrayVacio(): void
    {
        $result = $this->ventasModel->obtenerDetalleVenta(888888);

        $this->assertIsArray($result);
        $this->assertEmpty($result, 'Un ID inexistente debe retornar un array vacío.');
    }

    // ─────────────────────────────────────────────
    // Test 6 — getMonedasActivas: devuelve al menos una moneda con id y nombre
    // ─────────────────────────────────────────────

    #[Test]
    public function testGetMonedasActivas_RetornaListaNoVacia(): void
    {
        $result = $this->ventasModel->getMonedasActivas();

        $this->assertIsArray($result, 'getMonedasActivas debe retornar un array.');
        $this->assertNotEmpty($result, 'Debe haber al menos una moneda activa en la BD de test.');

        $primera = $result[0];
        $this->assertArrayHasKey('idmoneda', $primera);
        // La columna real en BD se llama nombre_moneda
        $tieneNombre = array_key_exists('nombre', $primera) || array_key_exists('nombre_moneda', $primera);
        $this->assertTrue($tieneNombre, 'La moneda debe tener una columna de nombre (nombre o nombre_moneda).');
    }
}
