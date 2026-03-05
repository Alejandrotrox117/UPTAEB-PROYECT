<?php

namespace Tests\UnitTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Mockery;
use App\Models\VentasModel;

class EliminarVentaUnitTest extends TestCase
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
    public function testEliminarVentaMocks()
    {
        // Mock connection
        $mockStmt = Mockery::mock('PDOStatement');
        $mockStmt->shouldReceive('execute')->andReturn(true);
        $mockStmt->shouldReceive('fetchAll')->andReturn([]);
        $mockStmt->shouldReceive('fetch')->andReturn(['idventa' => 1, 'estatus' => 'BORRADOR']);
        $mockStmt->shouldReceive('rowCount')->andReturn(1);

        $mockPdo = Mockery::mock('PDO');
        $mockPdo->shouldReceive('prepare')->andReturn($mockStmt);

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('getConexion')->andReturn($mockPdo);

        $resultado = $this->model->eliminarVenta(1);
        $this->assertIsArray($resultado);
    }
}
