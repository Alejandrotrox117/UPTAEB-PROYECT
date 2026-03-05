<?php

namespace Tests\IntegrationTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\VentasModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class VentaConsultasIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private ?VentasModel $ventasModel;

    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "\n[MODEL MESSAGE] " . $msg . "\n");
    }

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->ventasModel = new VentasModel();
    }

    protected function tearDown(): void
    {
        $this->ventasModel = null;
    }

    // ─────────────────────────────────────────────
    // DataProviders
    // ─────────────────────────────────────────────

    public static function providerIdsInexistentes(): array
    {
        return [
            [888888 + rand(1, 99999)],
            [999999 + rand(1, 99999)],
        ];
    }

    // ─────────────────────────────────────────────
    // Tests: getVentasDatatable
    // ─────────────────────────────────────────────

    #[Test]
    public function testGetVentasDatatableRetornaArray(): void
    {
        $result = $this->ventasModel->getVentasDatatable();
        $this->assertIsArray($result);
        $this->showMessage('getVentasDatatable retornó un array correctamente.');
    }

    #[Test]
    public function testGetVentasDatatableRetornaEstructuraEsperada(): void
    {
        $result = $this->ventasModel->getVentasDatatable();
        $this->assertIsArray($result);

        if (!empty($result)) {
            $primera = $result[0];
            $this->assertArrayHasKey('idventa', $primera);
            $this->assertArrayHasKey('nro_venta', $primera);
            $this->assertArrayHasKey('fecha_venta', $primera);
            $this->assertArrayHasKey('total_general', $primera);
            $this->assertArrayHasKey('estatus', $primera);
            $this->showMessage('Estructura de datatable verificada con ' . count($result) . ' registros.');
        } else {
            $this->showMessage('getVentasDatatable retornó un array vacío (sin registros en BD).');
        }
    }

    // ─────────────────────────────────────────────
    // Tests: obtenerVentaPorId
    // ─────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testObtenerVentaPorIdInexistente(int $id): void
    {
        $result = $this->ventasModel->obtenerVentaPorId($id);
        $this->assertFalse($result);
        $this->showMessage("Validación correcta: venta inexistente (id=$id) retorna false.");
    }

    // ─────────────────────────────────────────────
    // Tests: obtenerDetalleVenta
    // ─────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testObtenerDetalleVentaInexistente(int $id): void
    {
        $result = $this->ventasModel->obtenerDetalleVenta($id);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
        $this->showMessage("Validación correcta: detalle de venta inexistente retorna array vacío.");
    }

    // ─────────────────────────────────────────────
    // Tests: getMonedasActivas
    // ─────────────────────────────────────────────

    #[Test]
    public function testGetMonedasActivasRetornaArray(): void
    {
        $result = $this->ventasModel->getMonedasActivas();
        $this->assertIsArray($result);
    }
}
