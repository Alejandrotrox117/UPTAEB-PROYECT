<?php
use PHPUnit\Framework\TestCase;
use App\Models\RomanaModel;
class TestRomanaPesajeFallido extends TestCase
{
    private $model;
    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }
    protected function setUp(): void
    {
        $this->model = new RomanaModel();
    }
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
                $this->fail('Deber�a lanzar PDOException');
            } catch (PDOException $e) {
                $this->assertInstanceOf(PDOException::class, $e);
                $this->assertNotEmpty($e->getMessage());
            }
        } else {
            $this->markTestSkipped('M�todo insertPesaje no existe');
        }
    }
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
            $this->markTestSkipped('M�todo insertPesaje no existe');
        }
    }
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
            $this->markTestSkipped('M�todo insertPesaje no existe');
        }
    }
    public function testRegistrarPesajeSinLote()
    {
        if (method_exists($this->model, 'insertPesaje')) {
            $data = [
                'peso' => 100.00,
                'fecha_pesaje' => date('Y-m-d H:i:s')
            ];
            try {
                $this->model->insertPesaje($data);
                $this->fail('Deber�a lanzar PDOException');
            } catch (PDOException $e) {
                $this->assertInstanceOf(PDOException::class, $e);
                $this->assertNotEmpty($e->getMessage());
            }
        } else {
            $this->markTestSkipped('M�todo insertPesaje no existe');
        }
    }
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
                $this->fail('Deber�a lanzar PDOException');
            } catch (PDOException $e) {
                $this->assertInstanceOf(PDOException::class, $e);
                $this->assertNotEmpty($e->getMessage());
            }
        } else {
            $this->markTestSkipped('M�todo insertPesaje no existe');
        }
    }
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
            $this->markTestSkipped('M�todo insertPesaje no existe');
        }
    }
    public function testConsultarPesajeInexistente()
    {
        if (method_exists($this->model, 'selectPesajeById')) {
            $result = $this->model->selectPesajeById(99999);
            $this->assertFalse($result);
        } else {
            $this->markTestSkipped('M�todo selectPesajeById no existe');
        }
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
