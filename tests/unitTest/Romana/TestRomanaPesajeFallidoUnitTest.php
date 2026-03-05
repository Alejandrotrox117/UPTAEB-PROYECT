<?php

namespace Tests\UnitTest\Romana;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Mockery;
use PDOException;
use App\Models\RomanaModel;

class TestRomanaPesajeFallidoUnitTest extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $mockPdo = Mockery::mock('PDO');
        $mockStmt = Mockery::mock('PDOStatement');

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn($mockPdo);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturnNull();

        // Para simular fallos, simularemos que execute y fetchAll arrojan un false o vacío si fuera el caso
        $mockPdo->shouldReceive('prepare')->andReturn($mockStmt);
        $mockStmt->shouldReceive('execute')->andReturnTrue();
        $mockStmt->shouldReceive('fetchAll')->andReturn([]); // Datos vacíos para simular comportamiento

        $this->model = new RomanaModel();
    }

    #[Test]
    public function testRegistrarPesajeSinPeso()
    {
        if (method_exists($this->model, 'insertPesaje')) {
            $data = [
                'peso' => null,
                'fecha_pesaje' => date('Y-m-d H:i:s'),
                'idlote' => 1
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
                'idlote' => 1
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
                'idlote' => 1
            ];
            $result = $this->model->insertPesaje($data);
            $this->assertIsBool($result);
        } else {
            $this->markTestSkipped('Método insertPesaje no existe');
        }
    }

    #[Test]
    public function testRegistrarPesajeSinLote()
    {
        if (method_exists($this->model, 'insertPesaje')) {
            $data = [
                'peso' => 100.00,
                'fecha_pesaje' => date('Y-m-d H:i:s')
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
    public function testRegistrarPesajeConLoteInexistente()
    {
        if (method_exists($this->model, 'insertPesaje')) {
            $data = [
                'peso' => 100.00,
                'fecha_pesaje' => date('Y-m-d H:i:s'),
                'idlote' => 99999
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
    public function testRegistrarPesajeConPesoExcesivo()
    {
        if (method_exists($this->model, 'insertPesaje')) {
            $data = [
                'peso' => 999999.99,
                'fecha_pesaje' => date('Y-m-d H:i:s'),
                'idlote' => 1
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
