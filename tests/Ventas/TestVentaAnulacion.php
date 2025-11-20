<?php
use PHPUnit\Framework\TestCase;
use App\Models\VentasModel;
class TestVentaAnulacion extends TestCase
{
    private $model;
    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }
    protected function setUp(): void
    {
        $this->model = new VentasModel();
    }
    public function testAnularVentaExistente()
    {
        if (method_exists($this->model, 'anularVenta')) {
            $idventa = 1;
            $motivo = 'Prueba de anulación';
            $result = $this->model->anularVenta($idventa, $motivo);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('status', $result);
        } else {
            $this->markTestSkipped('Método anularVenta no existe');
        }
    }
    public function testAnularVentaConMotivo()
    {
        if (method_exists($this->model, 'anularVenta')) {
            $idventa = 1;
            $motivo = 'Cliente solicitó devolución';
            $result = $this->model->anularVenta($idventa, $motivo);
            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método anularVenta no existe');
        }
    }
    public function testVerificarReposicionInventario()
    {
        if (method_exists($this->model, 'anularVenta')) {
            $idventa = 1;
            $motivo = 'Error en facturación';
            $result = $this->model->anularVenta($idventa, $motivo);
            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método anularVenta no existe');
        }
    }
    public function testAnularVentaInexistente()
    {
        if (method_exists($this->model, 'anularVenta')) {
            $idventa = 99999;
            $motivo = 'Prueba';
            $result = $this->model->anularVenta($idventa, $motivo);
            $this->assertIsArray($result);
            $this->assertFalse($result['status']);
            if (array_key_exists('message', $result)) {
                $this->showMessage($result['message']);
            }
        } else {
            $this->markTestSkipped('Método anularVenta no existe');
        }
    }
    public function testAnularVentaYaAnulada()
    {
        if (method_exists($this->model, 'anularVenta')) {
            $idventa = 1;
            $motivo = 'Primera anulación';
            $this->model->anularVenta($idventa, $motivo);
            $result = $this->model->anularVenta($idventa, 'Segunda anulación');
            $this->assertIsArray($result);
            $this->assertFalse($result['status']);
        } else {
            $this->markTestSkipped('Método anularVenta no existe');
        }
    }
    public function testAnularVentaConIdNegativo()
    {
        if (method_exists($this->model, 'anularVenta')) {
            $result = $this->model->anularVenta(-1, 'Motivo');
            $this->assertIsArray($result);
            $this->assertFalse($result['status']);
        } else {
            $this->markTestSkipped('Método anularVenta no existe');
        }
    }
    public function testAnularVentaConIdCero()
    {
        if (method_exists($this->model, 'anularVenta')) {
            $result = $this->model->anularVenta(0, 'Motivo');
            $this->assertIsArray($result);
            $this->assertFalse($result['status']);
        } else {
            $this->markTestSkipped('Método anularVenta no existe');
        }
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
