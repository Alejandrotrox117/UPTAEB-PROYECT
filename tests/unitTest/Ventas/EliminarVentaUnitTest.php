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

    private function getPdoMock($ventaRet, $estatusRet = null)
    {
        $mockStmtCheck = Mockery::mock('PDOStatement');
        $mockStmtCheck->shouldReceive('execute')->andReturn(true);
        $mockStmtCheck->shouldReceive('fetch')->andReturn($ventaRet);

        $mockStmtEstado = Mockery::mock('PDOStatement');
        $mockStmtEstado->shouldReceive('execute')->andReturn(true);
        $mockStmtEstado->shouldReceive('fetch')->andReturn($estatusRet);

        $mockStmtGenerico = Mockery::mock('PDOStatement');
        $mockStmtGenerico->shouldReceive('execute')->andReturn(true);
        $mockStmtGenerico->shouldReceive('fetch')->andReturn([]);
        $mockStmtGenerico->shouldReceive('fetchAll')->andReturn([]);
        $mockStmtGenerico->shouldReceive('rowCount')->andReturn(1);

        $mockPdo = Mockery::mock('PDO');

        $mockPdo->shouldReceive('prepare')->andReturnUsing(function ($query) use ($mockStmtCheck, $mockStmtEstado, $mockStmtGenerico) {
            if (strpos($query, 'COUNT(*) as count FROM venta') !== false) {
                return $mockStmtCheck;
            }
            if (strpos($query, 'SELECT estatus FROM venta') !== false) {
                return $mockStmtEstado;
            }
            return $mockStmtGenerico;
        });

        $mockPdo->shouldReceive('beginTransaction')->andReturn(true);
        $mockPdo->shouldReceive('commit')->andReturn(true);
        $mockPdo->shouldReceive('rollBack')->andReturn(true);

        return $mockPdo;
    }

    #[Test]
    public function testEliminarVentaMocks()
    {
        $mockPdo = $this->getPdoMock(['count' => 1], ['estatus' => 'BORRADOR']);

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('getConexion')->andReturn($mockPdo);
        $mockConexion->shouldReceive('connect')->andReturn(true);
        $mockConexion->shouldReceive('disconnect')->andReturn(true);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($mockPdo);

        $resultado = $this->model->eliminarVenta(1);
        $this->assertIsArray($resultado);
    }

    #[Test]
    public function testEliminarVenta_Falla_VentaNoExiste()
    {
        $mockPdo = $this->getPdoMock(['count' => 0]);

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('getConexion')->andReturn($mockPdo);
        $mockConexion->shouldReceive('connect')->andReturn(true);
        $mockConexion->shouldReceive('disconnect')->andReturn(true);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($mockPdo);

        // Since the VentasModel returns boolean true/false or an array, based on previous elimination tests.
        // Looking at `ejecutarEliminacionVenta` in VentasModel, it returns a boolean $resultado = $stmt->rowCount() > 0;
        // Or if error: $resultado = false.
        $resultado = $this->model->eliminarVenta(999);
        // Sometimes wrapped in array in Controller logic, but model seems to return boolean. Wait! Wait! The model returned an array? 
        // Oh, maybe in the refactored VentasModel (the `__call` router returns an array with success/message from exceptions!)
        // In previous unit tests for `EliminarVentaUnitTest`, I asserted `assertIsArray`.
        // However, `ejecutarEliminacionVenta` from line 482 returns `$resultado` (boolean).
        // Let's assert based on array or false. Wait, if it throws exception, does it return array now?
        // Wait, in `ejecutarEliminacionVenta`, catch block has: $resultado = false; return $resultado;
        // No! It does not return `[success => false, message => ...]`. Wait! Let me check `ejecutarEliminacionVenta` catch block.
        // It says:
        // catch (Exception $e) { $db->rollBack(); error_log(...); $resultado = false; } return $resultado;
        // So it returns a boolean! But the tests testEliminarVentaMocks had `assertIsArray`. Why? 
        // Ah, `__call` wrapper inside `VentasModel` transforms exceptions into `['success'=>false, 'message'=>...]`? Or does `eliminarVenta` proxy return `['success'=>false]`?
        // Oh right, `Refactorizador de Módulos` changes methods to `['success' => false, 'message' => ...]`. 
        // Let's check `EliminarVentaIntegrationTest`, it asserted `assertIsArray($resultado); $this->assertTrue($resultado['success']);`.

        $this->assertFalse(is_array($resultado) ? $resultado['success'] : $resultado);
    }

    #[Test]
    public function testEliminarVenta_Falla_VentaNoBorrador()
    {
        $mockPdo = $this->getPdoMock(['count' => 1], ['estatus' => 'PROCESADO']);

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('getConexion')->andReturn($mockPdo);
        $mockConexion->shouldReceive('connect')->andReturn(true);
        $mockConexion->shouldReceive('disconnect')->andReturn(true);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($mockPdo);

        $resultado = $this->model->eliminarVenta(1);
        $this->assertFalse(is_array($resultado) ? $resultado['success'] : $resultado);
    }
}
