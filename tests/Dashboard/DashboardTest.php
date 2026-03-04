<?php

namespace Tests\Dashboard;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\DashboardModel;

class DashboardTest extends TestCase
{
    private DashboardModel $model;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');
        $this->model = new DashboardModel();
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }

    // ---------------------------------------------------------------
    // getResumen
    // ---------------------------------------------------------------

    #[Test]
    public function testGetResumen_RetornaEstructuraCorrecta(): void
    {
        $result = $this->model->getResumen();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('ventas_hoy', $result);
        $this->assertArrayHasKey('ventas_ayer', $result);
        $this->assertArrayHasKey('compras_hoy', $result);
        $this->assertArrayHasKey('compras_ayer', $result);
        $this->assertArrayHasKey('valor_inventario', $result);
        $this->assertArrayHasKey('producciones_activas', $result);
        $this->assertArrayHasKey('productos_en_rotacion', $result);
        $this->assertArrayHasKey('eficiencia_promedio', $result);
        $this->assertArrayHasKey('fecha_consulta', $result);
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
            $this->assertArrayHasKey('idventa', $result[0]);
            $this->assertArrayHasKey('nro_venta', $result[0]);
            $this->assertArrayHasKey('fecha_venta', $result[0]);
            $this->assertArrayHasKey('cliente', $result[0]);
            $this->assertArrayHasKey('total_general', $result[0]);
            $this->assertArrayHasKey('estado', $result[0]);
        }
    }

    // ---------------------------------------------------------------
    // getReporteCompras
    // ---------------------------------------------------------------

    #[Test]
    public function testGetReporteCompras_RetornaArray(): void
    {
        $result = $this->model->getReporteCompras('2000-01-01', '2099-12-31');

        $this->assertIsArray($result);

        if (!empty($result)) {
            $this->assertArrayHasKey('nro_compra', $result[0]);
            $this->assertArrayHasKey('proveedor', $result[0]);
            $this->assertArrayHasKey('total_general', $result[0]);
        }
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
            $this->assertArrayHasKey('empleado_nombre', $result[0]);
            $this->assertArrayHasKey('ordenes_asignadas', $result[0]);
            $this->assertArrayHasKey('ordenes_completadas', $result[0]);
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
        $this->assertArrayHasKey('stock_critico', $result);
        $this->assertArrayHasKey('valor_por_categoria', $result);
        $this->assertArrayHasKey('movimientos_mes', $result);
        $this->assertArrayHasKey('productos_mas_vendidos', $result);
    }

    // ---------------------------------------------------------------
    // DataProviders
    // ---------------------------------------------------------------

    public static function providerRangosFechasInvalidos(): array
    {
        return [
            'fechas_invertidas' => ['2026-12-31', '2026-01-01'],
            'formato_invalido' => ['no-es-fecha', 'tampoco'],
            'fecha_futura' => ['2099-01-01', '2099-12-31'],
        ];
    }

    #[Test]
    #[DataProvider('providerRangosFechasInvalidos')]
    public function testGetReporteCompras_RetornaArray_ConFechasProblematicas(string $desde, string $hasta): void
    {
        $result = $this->model->getReporteCompras($desde, $hasta);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
