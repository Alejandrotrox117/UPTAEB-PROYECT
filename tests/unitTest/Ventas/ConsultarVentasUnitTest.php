<?php

namespace Tests\UnitTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Mockery;
use App\Models\VentasModel;

class ConsultarVentasUnitTest extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new VentasModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    #[Test]
    public function testConsultarVentasMocks()
    {
        // Mock connection
        $mockStmt = Mockery::mock('PDOStatement');
        $mockStmt->shouldReceive('execute')->andReturn(true);
        $mockStmt->shouldReceive('fetchAll')->andReturn([]);
        $mockStmt->shouldReceive('fetch')->andReturn(['idventa' => 1]);
        $mockStmt->shouldReceive('rowCount')->andReturn(1);

        $mockPdo = Mockery::mock('PDO');
        $mockPdo->shouldReceive('prepare')->andReturn($mockStmt);

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('getConexion')->andReturn($mockPdo);

        if (method_exists($this->model, 'getAllVentas')) {
            $resultado = $this->model->getAllVentas();
            $this->assertIsArray($resultado);
        }

        if (method_exists($this->model, 'getVentasDatatable')) {
            $resultado = $this->model->getVentasDatatable();
            $this->assertIsArray($resultado);
        }
    }
}
