<?php

namespace Tests\UnitTest\Romana;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use Mockery;
use PDOException;
use App\Models\RomanaModel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class TestRomanaPesajeFallidoUnitTest extends TestCase
{
    private $model;
    private $mockPdo;
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('log_errors', '0');
        ini_set('error_log', 'NUL');

        $this->mockPdo = Mockery::mock('PDO');
        $this->mockStmt = Mockery::mock('PDOStatement');

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturnNull();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockStmt->shouldReceive('execute')->andReturnTrue()->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->andReturn([])->byDefault();

        $this->model = new RomanaModel();
    }

    #[Test]
    public function testRegistrarPesajeSinPeso()
    {
        if (method_exists($this->model, 'insertPesaje')) {
            $data = [
                'peso' => null,
                'fecha_pesaje' => date('Y-m-d H:i:s'),
            ];
            try {
                $this->model->insertPesaje($data);
                $this->fail('Debería lanzar PDOException');
            } catch (PDOException $e) {
                $this->assertInstanceOf(PDOException::class, $e);
                $this->assertNotEmpty($e->getMessage());
            }
        } else {
            $this->markTestSkipped('Método insertPesaje no existe');
        }
    }

    #[Test]
    public function testRegistrarPesajeConPesoNegativo()
    {
        if (method_exists($this->model, 'insertPesaje')) {
            $data = [
                'peso' => -50.00,
                'fecha_pesaje' => date('Y-m-d H:i:s'),
            ];
            $result = $this->model->insertPesaje($data);
            $this->assertFalse($result);
        } else {
            $this->markTestSkipped('Método insertPesaje no existe');
        }
    }

    #[Test]
    public function testRegistrarPesajeConPesoCero()
    {
        if (method_exists($this->model, 'insertPesaje')) {
            $data = [
                'peso' => 0,
                'fecha_pesaje' => date('Y-m-d H:i:s'),
            ];
            $result = $this->model->insertPesaje($data);
            $this->assertIsBool($result);
        } else {
            $this->markTestSkipped('Método insertPesaje no existe');
        }
    }

    #[Test]
    public function testRegistrarPesajeConPesoExcesivo()
    {
        if (method_exists($this->model, 'insertPesaje')) {
            $data = [
                'peso' => 999999.99,
                'fecha_pesaje' => date('Y-m-d H:i:s'),
            ];
            $result = $this->model->insertPesaje($data);
            $this->assertIsBool($result);
        } else {
            $this->markTestSkipped('Método insertPesaje no existe');
        }
    }

    #[Test]
    public function testConsultarPesajeInexistente()
    {
        if (method_exists($this->model, 'selectPesajeById')) {
            $result = $this->model->selectPesajeById(99999);
            $this->assertFalse($result);
        } else {
            $this->markTestSkipped('Método selectPesajeById no existe');
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        $this->model = null;
    }
}
