<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../app/models/dashboardModel.php';
class TestDashboard extends TestCase
{
    private $model;
    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }
    protected function setUp(): void
    {
        $this->model = new DashboardModel();
    }
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
