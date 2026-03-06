<?php

namespace Tests\UnitTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Mockery;
use App\Models\VentasModel;

class EditarVentaUnitTest extends TestCase
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

    private function getPdoMock($ventaRet, $clienteCount)
    {
        $mockStmtVenta = Mockery::mock('PDOStatement');
        $mockStmtVenta->shouldReceive('execute')->andReturn(true);
        $mockStmtVenta->shouldReceive('fetch')->andReturn($ventaRet);

        $mockStmtCliente = Mockery::mock('PDOStatement');
        $mockStmtCliente->shouldReceive('execute')->andReturn(true);
        $mockStmtCliente->shouldReceive('fetch')->andReturn(['count' => $clienteCount]);

        $mockStmtGenerico = Mockery::mock('PDOStatement');
        $mockStmtGenerico->shouldReceive('execute')->andReturn(true);
        $mockStmtGenerico->shouldReceive('fetch')->andReturn([]);
        $mockStmtGenerico->shouldReceive('fetchAll')->andReturn([]);

        $mockPdo = Mockery::mock('PDO');

        $mockPdo->shouldReceive('prepare')->andReturnUsing(function ($query) use ($mockStmtVenta, $mockStmtCliente, $mockStmtGenerico) {
            if (strpos($query, 'SELECT * FROM venta') !== false) {
                return $mockStmtVenta;
            }
            if (strpos($query, 'COUNT(*) as count FROM cliente') !== false) {
                return $mockStmtCliente;
            }
            return $mockStmtGenerico;
        });

        $mockPdo->shouldReceive('beginTransaction')->andReturn(true);
        $mockPdo->shouldReceive('commit')->andReturn(true);
        $mockPdo->shouldReceive('rollBack')->andReturn(true);

        return $mockPdo;
    }

    #[Test]
    public function testEditarVentaMocks()
    {
        $mockPdo = $this->getPdoMock(['idventa' => 1, 'estatus' => 'BORRADOR', 'idcliente' => 1], 1);
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('getConexion')->andReturn($mockPdo);
        $mockConexion->shouldReceive('connect')->andReturn(true);
        $mockConexion->shouldReceive('disconnect')->andReturn(true);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($mockPdo);

        $datosVentaEditada = [
            'idcliente' => 1,
            'estatus' => 'BORRADOR'
        ];

        $resultado = $this->model->updateVenta(1, $datosVentaEditada);
        $this->assertTrue($resultado['success']);
    }

    #[Test]
    public function testEditarVenta_Falla_VentaNoExiste()
    {
        // $ventaRet = false
        $mockPdo = $this->getPdoMock(false, 1);
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('getConexion')->andReturn($mockPdo);
        $mockConexion->shouldReceive('connect')->andReturn(true);
        $mockConexion->shouldReceive('disconnect')->andReturn(true);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($mockPdo);

        $resultado = $this->model->updateVenta(99, []);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('venta especificada no existe', $resultado['message']);
    }

    #[Test]
    public function testEditarVenta_Falla_VentaNoBorrador()
    {
        // $ventaRet estatus = PROCESADO
        $mockPdo = $this->getPdoMock(['idventa' => 1, 'estatus' => 'PROCESADO'], 1);
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('getConexion')->andReturn($mockPdo);
        $mockConexion->shouldReceive('connect')->andReturn(true);
        $mockConexion->shouldReceive('disconnect')->andReturn(true);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($mockPdo);

        $resultado = $this->model->updateVenta(1, []);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('no está en estado BORRADOR', $resultado['message']);
    }

    #[Test]
    public function testEditarVenta_Falla_ClienteNoExiste()
    {
        // $clienteCount = 0
        $mockPdo = $this->getPdoMock(['idventa' => 1, 'estatus' => 'BORRADOR', 'idcliente' => 1], 0);
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('getConexion')->andReturn($mockPdo);
        $mockConexion->shouldReceive('connect')->andReturn(true);
        $mockConexion->shouldReceive('disconnect')->andReturn(true);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($mockPdo);

        $resultado = $this->model->updateVenta(1, ['idcliente' => 99]);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('cliente especificado no existe', $resultado['message']);
    }
}
