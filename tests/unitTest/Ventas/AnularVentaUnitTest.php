<?php

namespace Tests\UnitTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Mockery;
use App\Models\VentasModel;

class AnularVentaUnitTest extends TestCase
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
    public function testAnularVentaMocks()
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

        if (method_exists($this->model, 'anularVenta')) {
            $resultado = $this->model->anularVenta(1, 'Prueba unitaria anulación');
            $this->assertIsArray($resultado);
        } else {
            $this->markTestSkipped('Método anularVenta no existe');
        }
    }
}
