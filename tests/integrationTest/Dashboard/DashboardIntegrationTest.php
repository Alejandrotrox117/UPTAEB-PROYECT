<?php
declare(strict_types=1);

namespace Tests\IntegrationTest\Dashboard;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\DashboardModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

/**
 * Pruebas de Integración — DashboardModel
 *
 * Interactúa directamente con bd_pda_test (base de datos general).
 * Si la base de datos no está disponible, todos los tests se marcan SKIPPED.
 */
class DashboardIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private DashboardModel $model;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new DashboardModel();
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }

    // ---------------------------------------------------------------
    // DataProviders
    // ---------------------------------------------------------------

    public static function providerRangosFechasValidos(): array
    {
        return [
            'año_actual'   => ['2026-01-01', '2026-12-31'],
            'rango_amplio' => ['2000-01-01', '2099-12-31'],
        ];
    }

    public static function providerRangosFechasInvalidos(): array
    {
        return [
            'fechas_invertidas' => ['2026-12-31', '2026-01-01'],
            'formato_invalido'  => ['no-es-fecha', 'tampoco'],
            'fecha_futura'      => ['2099-01-01', '2099-12-31'],
        ];
    }

    // ---------------------------------------------------------------
    // getResumen
    // ---------------------------------------------------------------

    #[Test]
    public function testGetResumen_RetornaEstructuraCorrecta(): void
    {
        $result = $this->model->getResumen();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('ventas_hoy',            $result);
        $this->assertArrayHasKey('ventas_ayer',           $result);
        $this->assertArrayHasKey('compras_hoy',           $result);
        $this->assertArrayHasKey('compras_ayer',          $result);
        $this->assertArrayHasKey('valor_inventario',      $result);
        $this->assertArrayHasKey('producciones_activas',  $result);
        $this->assertArrayHasKey('productos_en_rotacion', $result);
        $this->assertArrayHasKey('eficiencia_promedio',   $result);
        $this->assertArrayHasKey('fecha_consulta',        $result);
    }

    // ---------------------------------------------------------------
    // getUltimasVentas
    // ---------------------------------------------------------------

    #[Test]
    public function testGetUltimasVentas_RetornaArray(): void
    {
        $result = $this->model->getUltimasVentas();

        $this->assertIsArray($result);

        if (!empty($result)) {
            $this->assertArrayHasKey('idventa',       $result[0]);
            $this->assertArrayHasKey('nro_venta',     $result[0]);
            $this->assertArrayHasKey('fecha_venta',   $result[0]);
            $this->assertArrayHasKey('cliente',       $result[0]);
            $this->assertArrayHasKey('total_general', $result[0]);
            $this->assertArrayHasKey('estado',        $result[0]);
        }
    }

    // ---------------------------------------------------------------
    // getReporteCompras
    // ---------------------------------------------------------------

    #[Test]
    #[DataProvider('providerRangosFechasValidos')]
    public function testGetReporteCompras_ConFechasValidas_RetornaArray(string $desde, string $hasta): void
    {
        $result = $this->model->getReporteCompras($desde, $hasta);

        $this->assertIsArray($result);

        if (!empty($result)) {
            $this->assertArrayHasKey('nro_compra',     $result[0]);
            $this->assertArrayHasKey('proveedor',      $result[0]);
            $this->assertArrayHasKey('total_general',  $result[0]);
        }
    }

    #[Test]
    #[DataProvider('providerRangosFechasInvalidos')]
    public function testGetReporteCompras_ConFechasInvalidas_RetornaArrayVacio(string $desde, string $hasta): void
    {
        $result = $this->model->getReporteCompras($desde, $hasta);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ---------------------------------------------------------------
    // getEficienciaEmpleados
    // ---------------------------------------------------------------

    #[Test]
    public function testGetEficienciaEmpleados_RetornaArray(): void
    {
        $fechaDesde = date('Y-01-01');
        $fechaHasta = date('Y-12-31');

        $result = $this->model->getEficienciaEmpleados($fechaDesde, $fechaHasta, null, null);

        $this->assertIsArray($result);

        if (!empty($result)) {
            $this->assertArrayHasKey('empleado_nombre',      $result[0]);
            $this->assertArrayHasKey('ordenes_asignadas',    $result[0]);
            $this->assertArrayHasKey('ordenes_completadas',  $result[0]);
        }
    }

    // ---------------------------------------------------------------
    // getAnalisisInventario
    // ---------------------------------------------------------------

    #[Test]
    public function testGetAnalisisInventario_RetornaEstructuraCorrecta(): void
    {
        $result = $this->model->getAnalisisInventario();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stock_critico',          $result);
        $this->assertArrayHasKey('valor_por_categoria',    $result);
        $this->assertArrayHasKey('movimientos_mes',        $result);
        $this->assertArrayHasKey('productos_mas_vendidos', $result);
    }
}
