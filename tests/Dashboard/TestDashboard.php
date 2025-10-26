<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/dashboardModel.php';

/**
 * RF09: Prueba de caja blanca para generación de estadísticas del dashboard
 * Incluye casos típicos (exitosos) y atípicos (fallidos)
 */
class TestDashboard extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new DashboardModel();
    }

    // ========== CASOS TÍPICOS (EXITOSOS) ==========

    public function testGetResumenRetornaArray()
    {
        $result = $this->model->getResumen();

        $this->assertIsArray($result);
    }

    public function testGetAnalisisInventarioRetornaArray()
    {
        $result = $this->model->getAnalisisInventario();

        $this->assertIsArray($result);
    }

    public function testGetUltimasVentasRetornaArray()
    {
        $limit = 10;
        $result = $this->model->getUltimasVentas($limit);

        $this->assertIsArray($result);
        $this->assertLessThanOrEqual($limit, count($result));
    }

    public function testGetProductosStockBajoRetornaArray()
    {
        $result = $this->model->getProductosStockBajo();

        $this->assertIsArray($result);
    }

    public function testGetKPIsTiempoRealRetornaArray()
    {
        $result = $this->model->getKPIsTiempoReal();

        $this->assertIsArray($result);
    }

    public function testGetTendenciasVentasRetornaArray()
    {
        $result = $this->model->getTendenciasVentas();

        $this->assertIsArray($result);
    }

    public function testGetRentabilidadProductosRetornaArray()
    {
        $result = $this->model->getRentabilidadProductos();

        $this->assertIsArray($result);
    }

    public function testGetMovimientosInventarioMesRetornaArray()
    {
        $result = $this->model->getMovimientosInventarioMes();

        $this->assertIsArray($result);
    }

    // ========== CASOS ATÍPICOS (FALLIDOS) ==========

    public function testGetUltimasVentasConLimiteCero()
    {
        $result = $this->model->getUltimasVentas(0);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetUltimasVentasConLimiteNegativo()
    {
        $result = $this->model->getUltimasVentas(-5);

        $this->assertIsArray($result);
    }

    public function testGetReporteComprasConFechasInvalidas()
    {
        $result = $this->model->getReporteCompras('2025-13-32', '2025-99-99');

        $this->assertIsArray($result);
    }

    public function testGetEficienciaEmpleadosConEmpleadoInexistente()
    {
        $fechaDesde = date('Y-m-01');
        $fechaHasta = date('Y-m-d');
        
        $result = $this->model->getEficienciaEmpleados($fechaDesde, $fechaHasta, 99999, null);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
