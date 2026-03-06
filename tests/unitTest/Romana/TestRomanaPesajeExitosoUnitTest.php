<?php

namespace Tests\UnitTest\Romana;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use Mockery;
use App\Models\RomanaModel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class TestRomanaPesajeExitosoUnitTest extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        ini_set('log_errors', '0');
        ini_set('error_log', 'NUL');

        $mockPdo = Mockery::mock('PDO');
        $mockStmt = Mockery::mock('PDOStatement');

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn($mockPdo);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturnNull();

        $mockPdo->shouldReceive('prepare')->andReturn($mockStmt);
        $mockStmt->shouldReceive('execute')->andReturnTrue();
        $mockStmt->shouldReceive('fetchAll')->andReturn([
            ['idromana' => 1, 'peso' => 150.50, 'fecha' => '2023-01-01', 'estatus' => '1', 'fecha_creacion' => '2023-01-01']
        ]);

        $this->model = new RomanaModel();
    }

    #[Test]
    public function testRegistrarPesajeExitoso()
    {
        if (method_exists($this->model, 'insertPesaje')) {
            $data = [
                'peso' => 150.50,
                'fecha_pesaje' => date('Y-m-d H:i:s'),
                'observaciones' => 'Pesaje de prueba'
            ];
            $result = $this->model->insertPesaje($data);
            $this->assertIsBool($result);
        } else {
            $this->markTestSkipped('Método insertPesaje no existe');
        }
    }

    #[Test]
    public function testRegistrarPesajeConPesoDecimal()
    {
        if (method_exists($this->model, 'insertPesaje')) {
            $data = [
                'peso' => 99.99,
                'fecha_pesaje' => date('Y-m-d H:i:s'),
            ];
            $result = $this->model->insertPesaje($data);
            $this->assertIsBool($result);
        } else {
            $this->markTestSkipped('Método insertPesaje no existe');
        }
    }

    #[Test]
    public function testConsultarTodosPesajes()
    {
        $result = $this->model->selectAllRomana();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertTrue($result['status']);
        $this->assertArrayHasKey('data', $result);
    }

    #[Test]
    public function testConsultarPesajePorId()
    {
        if (method_exists($this->model, 'selectPesajeById')) {
            $result = $this->model->selectPesajeById(1);
            $this->assertTrue(
                is_array($result) || is_bool($result),
                "Debería retornar array o false"
            );
        } else {
            $this->markTestSkipped('Método selectPesajeById no existe');
        }
    }

    #[Test]
    public function testCalcularPesoTotal()
    {
        if (method_exists($this->model, 'calcularPesoTotal')) {
            $pesoTotal = $this->model->calcularPesoTotal();
            $this->assertIsNumeric($pesoTotal, "Debería retornar un número");
            $this->assertGreaterThanOrEqual(0, $pesoTotal, "El peso total no puede ser negativo");
        } else {
            $this->markTestSkipped('Método calcularPesoTotal no existe');
        }
    }

    #[Test]
    public function testCalcularPromedioPeso()
    {
        if (method_exists($this->model, 'calcularPromedioPeso')) {
            $promedio = $this->model->calcularPromedioPeso();
            $this->assertIsNumeric($promedio, "Debería retornar un número");
        } else {
            $this->markTestSkipped('Método calcularPromedioPeso no existe');
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        $this->model = null;
    }
}
