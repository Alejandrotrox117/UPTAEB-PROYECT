<?php

namespace Tests\UnitTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Mockery;
use App\Models\VentasModel;

class CrearVentaUnitTest extends TestCase
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

    private function getPdoMock($clienteRet, $monedaCount, $numeroVentaRet, $productoRet = null, $stockRet = null)
    {
        $mockStmtCliente = Mockery::mock('PDOStatement');
        $mockStmtCliente->shouldReceive('execute')->andReturn(true);
        $mockStmtCliente->shouldReceive('fetch')->andReturn($clienteRet);

        $mockStmtMoneda = Mockery::mock('PDOStatement');
        $mockStmtMoneda->shouldReceive('execute')->andReturn(true);
        $mockStmtMoneda->shouldReceive('fetchColumn')->andReturn($monedaCount);

        $mockStmtNumVenta = Mockery::mock('PDOStatement');
        $mockStmtNumVenta->shouldReceive('execute')->andReturn(true);
        $mockStmtNumVenta->shouldReceive('fetch')->andReturn($numeroVentaRet);

        $mockStmtProducto = Mockery::mock('PDOStatement');
        $mockStmtProducto->shouldReceive('execute')->andReturn(true);
        $mockStmtProducto->shouldReceive('fetch')->andReturn($productoRet);

        $mockStmtStock = Mockery::mock('PDOStatement');
        $mockStmtStock->shouldReceive('execute')->andReturn(true);
        $mockStmtStock->shouldReceive('fetch')->andReturn($stockRet);

        $mockStmtGenerico = Mockery::mock('PDOStatement');
        $mockStmtGenerico->shouldReceive('execute')->andReturn(true);
        $mockStmtGenerico->shouldReceive('fetch')->andReturn([]);
        $mockStmtGenerico->shouldReceive('fetchAll')->andReturn([]);

        $mockPdo = Mockery::mock('PDO');

        $mockPdo->shouldReceive('prepare')->andReturnUsing(function ($query) use ($mockStmtCliente, $mockStmtMoneda, $mockStmtNumVenta, $mockStmtProducto, $mockStmtStock, $mockStmtGenerico) {
            if (strpos($query, 'SELECT estatus FROM cliente') !== false) {
                return $mockStmtCliente;
            }
            if (strpos($query, 'COUNT(*) as count FROM monedas') !== false) {
                return $mockStmtMoneda;
            }
            if (strpos($query, 'siguiente_numero') !== false) {
                return $mockStmtNumVenta;
            }
            if (strpos($query, 'SELECT nombre, estatus FROM producto') !== false) {
                return $mockStmtProducto;
            }
            if (strpos($query, 'existencia') !== false && strpos($query, 'SELECT') !== false) {
                return $mockStmtStock;
            }
            return $mockStmtGenerico;
        });

        $mockPdo->shouldReceive('lastInsertId')->andReturn(1);
        $mockPdo->shouldReceive('beginTransaction')->andReturn(true);
        $mockPdo->shouldReceive('commit')->andReturn(true);
        $mockPdo->shouldReceive('rollBack')->andReturn(true);

        return $mockPdo;
    }

    #[Test]
    public function testCrearVentaMocks()
    {
        $mockPdo = $this->getPdoMock(['estatus' => 'ACTIVO'], 1, ['siguiente_numero' => 1], ['nombre' => 'Prod', 'estatus' => 'ACTIVO'], ['stock' => 10, 'nombre' => 'Prod']);
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('getConexion')->andReturn($mockPdo);
        $mockConexion->shouldReceive('connect')->andReturn(true);
        $mockConexion->shouldReceive('disconnect')->andReturn(true);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($mockPdo);

        $datosVenta = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => 1,
            'idmoneda_general' => 3,
            'subtotal_general' => 60,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'total_general' => 60
        ];
        $detallesVenta = [['idproducto' => 1, 'cantidad' => 3, 'precio_unitario_venta' => 20]];

        $resultado = $this->model->insertVenta($datosVenta, $detallesVenta);
        $this->assertTrue($resultado['success']);
    }

    #[Test]
    public function testInsertVenta_Falla_ClienteInexistente()
    {
        $mockPdo = $this->getPdoMock(false, 1, false);
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('getConexion')->andReturn($mockPdo);
        $mockConexion->shouldReceive('connect')->andReturn(true);
        $mockConexion->shouldReceive('disconnect')->andReturn(true);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($mockPdo);

        $resultado = $this->model->insertVenta(['idcliente' => 99, 'idmoneda_general' => 1, 'monto_descuento_general' => 0, 'subtotal_general' => 10], []);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('Cliente no existe', $resultado['message']);
    }

    #[Test]
    public function testInsertVenta_Falla_ClienteInactivo()
    {
        $mockPdo = $this->getPdoMock(['estatus' => 'INACTIVO'], 1, false);
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('getConexion')->andReturn($mockPdo);
        $mockConexion->shouldReceive('connect')->andReturn(true);
        $mockConexion->shouldReceive('disconnect')->andReturn(true);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($mockPdo);

        $resultado = $this->model->insertVenta(['idcliente' => 99, 'idmoneda_general' => 1, 'monto_descuento_general' => 0, 'subtotal_general' => 10], []);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('Cliente inactivo', $resultado['message']);
    }

    #[Test]
    public function testInsertVenta_Falla_MonedaInexistente()
    {
        $mockPdo = $this->getPdoMock(['estatus' => 'ACTIVO'], 0, false);
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('getConexion')->andReturn($mockPdo);
        $mockConexion->shouldReceive('connect')->andReturn(true);
        $mockConexion->shouldReceive('disconnect')->andReturn(true);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($mockPdo);

        $resultado = $this->model->insertVenta(['idcliente' => 1, 'idmoneda_general' => 99, 'monto_descuento_general' => 0, 'subtotal_general' => 10], []);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('Moneda no existe', $resultado['message']);
    }

    #[Test]
    public function testInsertVenta_Falla_DescuentoMayorSubtotal()
    {
        $mockPdo = $this->getPdoMock(['estatus' => 'ACTIVO'], 1, false);
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('getConexion')->andReturn($mockPdo);
        $mockConexion->shouldReceive('connect')->andReturn(true);
        $mockConexion->shouldReceive('disconnect')->andReturn(true);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($mockPdo);

        $resultado = $this->model->insertVenta(['idcliente' => 1, 'idmoneda_general' => 1, 'monto_descuento_general' => 50, 'subtotal_general' => 10], []);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('Descuento mayor al subtotal', $resultado['message']);
    }

    #[Test]
    public function testInsertVenta_Falla_ProductoInexistente()
    {
        $mockPdo = $this->getPdoMock(['estatus' => 'ACTIVO'], 1, ['siguiente_numero' => 1], false);
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('getConexion')->andReturn($mockPdo);
        $mockConexion->shouldReceive('connect')->andReturn(true);
        $mockConexion->shouldReceive('disconnect')->andReturn(true);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($mockPdo);

        $datos = ['idcliente' => 1, 'idmoneda_general' => 1, 'monto_descuento_general' => 0, 'subtotal_general' => 10, 'fecha_venta' => date('Y-m-d'), 'total_general' => 10];
        $det = [['idproducto' => 99, 'cantidad' => 1, 'precio_unitario_venta' => 10]];
        $resultado = $this->model->insertVenta($datos, $det);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('no existe en el detalle', $resultado['message']);
    }

    #[Test]
    public function testInsertVenta_Falla_ProductoInactivo()
    {
        $mockPdo = $this->getPdoMock(['estatus' => 'ACTIVO'], 1, ['siguiente_numero' => 1], ['nombre' => 'Prod', 'estatus' => 'INACTIVO']);
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('getConexion')->andReturn($mockPdo);
        $mockConexion->shouldReceive('connect')->andReturn(true);
        $mockConexion->shouldReceive('disconnect')->andReturn(true);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($mockPdo);

        $datos = ['idcliente' => 1, 'idmoneda_general' => 1, 'monto_descuento_general' => 0, 'subtotal_general' => 10, 'fecha_venta' => date('Y-m-d'), 'total_general' => 10];
        $det = [['idproducto' => 1, 'cantidad' => 1, 'precio_unitario_venta' => 10]];
        $resultado = $this->model->insertVenta($datos, $det);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('no está activo', $resultado['message']);
    }

    #[Test]
    public function testInsertVenta_Falla_CantidadInvalida()
    {
        $mockPdo = $this->getPdoMock(['estatus' => 'ACTIVO'], 1, ['siguiente_numero' => 1], ['nombre' => 'Prod', 'estatus' => 'ACTIVO']);
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('getConexion')->andReturn($mockPdo);
        $mockConexion->shouldReceive('connect')->andReturn(true);
        $mockConexion->shouldReceive('disconnect')->andReturn(true);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($mockPdo);

        $datos = ['idcliente' => 1, 'idmoneda_general' => 1, 'monto_descuento_general' => 0, 'subtotal_general' => 10, 'fecha_venta' => date('Y-m-d'), 'total_general' => 10];
        $det = [['idproducto' => 1, 'cantidad' => -5, 'precio_unitario_venta' => 10]];
        $resultado = $this->model->insertVenta($datos, $det);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('cantidad debe ser mayor a 0', $resultado['message']);
    }

    #[Test]
    public function testInsertVenta_Falla_PrecioInvalido()
    {
        $mockPdo = $this->getPdoMock(['estatus' => 'ACTIVO'], 1, ['siguiente_numero' => 1], ['nombre' => 'Prod', 'estatus' => 'ACTIVO']);
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('getConexion')->andReturn($mockPdo);
        $mockConexion->shouldReceive('connect')->andReturn(true);
        $mockConexion->shouldReceive('disconnect')->andReturn(true);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($mockPdo);

        $datos = ['idcliente' => 1, 'idmoneda_general' => 1, 'monto_descuento_general' => 0, 'subtotal_general' => 10, 'fecha_venta' => date('Y-m-d'), 'total_general' => 10];
        $det = [['idproducto' => 1, 'cantidad' => 1, 'precio_unitario_venta' => -10]];
        $resultado = $this->model->insertVenta($datos, $det);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('precio unitario debe ser válido', $resultado['message']);
    }

    #[Test]
    public function testInsertVenta_Falla_StockInsuficiente()
    {
        $mockPdo = $this->getPdoMock(['estatus' => 'ACTIVO'], 1, ['siguiente_numero' => 1], ['nombre' => 'Prod', 'estatus' => 'ACTIVO'], ['stock' => 0, 'nombre' => 'Prod']);
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('getConexion')->andReturn($mockPdo);
        $mockConexion->shouldReceive('connect')->andReturn(true);
        $mockConexion->shouldReceive('disconnect')->andReturn(true);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($mockPdo);

        $datos = ['idcliente' => 1, 'idmoneda_general' => 1, 'monto_descuento_general' => 0, 'subtotal_general' => 10, 'fecha_venta' => date('Y-m-d'), 'total_general' => 10];
        $det = [['idproducto' => 1, 'cantidad' => 5, 'precio_unitario_venta' => 10]];
        $resultado = $this->model->insertVenta($datos, $det);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('Stock insuficiente', $resultado['message']);
    }
}
