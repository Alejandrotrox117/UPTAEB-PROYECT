<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../app/models/ventasModel.php';
class TestVentaConsultas extends TestCase
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
    public function testBuscarTodasLasVentas()
    {
        if (method_exists($this->model, 'getAllVentas')) {
            $result = $this->model->getAllVentas();
            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método getAllVentas no existe');
        }
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
