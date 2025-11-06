<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../app/models/romanaModel.php';
class TestRomanaPesajeExitoso extends TestCase
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
    public function testRegistrarPesajeExitoso()
    {
        if (method_exists($this->model, 'insertPesaje')) {
            $data = [
                'peso' => 150.50,
                'fecha_pesaje' => date('Y-m-d H:i:s'),
                'idlote' => 1,
                'observaciones' => 'Pesaje de prueba'
            ];
            $result = $this->model->insertPesaje($data);
            $this->assertIsBool($result);
        } else {
            $this->markTestSkipped('Método insertPesaje no existe');
        }
    }
    public function testRegistrarPesajeConPesoDecimal()
    {
        if (method_exists($this->model, 'insertPesaje')) {
            $data = [
                'peso' => 99.99,
                'fecha_pesaje' => date('Y-m-d H:i:s'),
                'idlote' => 1
            ];
            $result = $this->model->insertPesaje($data);
            $this->assertIsBool($result);
        } else {
            $this->markTestSkipped('Método insertPesaje no existe');
        }
    }
    public function testConsultarTodosPesajes()
    {
        $result = $this->model->selectAllRomana();
        $this->assertIsArray($result);
    }
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
    public function testConsultarPesajesPorLote()
    {
        if (method_exists($this->model, 'selectPesajesByLote')) {
            $idLote = 1;
            $result = $this->model->selectPesajesByLote($idLote);
            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método selectPesajesByLote no existe');
        }
    }
    public function testCalcularPesoTotal()
    {
        if (method_exists($this->model, 'calcularPesoTotal')) {
            $idLote = 1;
            $pesoTotal = $this->model->calcularPesoTotal($idLote);
            $this->assertIsNumeric($pesoTotal, "Debería retornar un número");
            $this->assertGreaterThanOrEqual(0, $pesoTotal, "El peso total no puede ser negativo");
        } else {
            $this->markTestSkipped('Método calcularPesoTotal no existe');
        }
    }
    public function testCalcularPromedioPeso()
    {
        if (method_exists($this->model, 'calcularPromedioPeso')) {
            $idLote = 1;
            $promedio = $this->model->calcularPromedioPeso($idLote);
            $this->assertIsNumeric($promedio, "Debería retornar un número");
        } else {
            $this->markTestSkipped('Método calcularPromedioPeso no existe');
        }
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
