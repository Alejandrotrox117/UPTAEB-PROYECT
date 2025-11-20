<?php
use PHPUnit\Framework\TestCase;
use App\Models\PagosModel;
class TestPagosSelect extends TestCase
{
    private $model;
    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }
    protected function setUp(): void
    {
        $this->model = new PagosModel();
    }
    public function testSelectAllPagosRetornaArray()
    {
        $result = $this->model->selectAllPagos();
        $this->assertIsArray($result);
    }
    public function testSelectPagosPorCompraRetornaArray()
    {
        $this->markTestSkipped('Método getPagosByCompra no existe en el modelo');
    }
    public function testSelectPagosPorFechaRetornaArray()
    {
        $this->markTestSkipped('Método getPagosByFecha no existe en el modelo');
    }
    public function testSelectPagoByIdExistente()
    {
        $response = $this->model->selectAllPagos();
        $pagos = $response['data'] ?? [];
        if (empty($pagos)) {
            $this->markTestSkipped('No hay pagos para probar');
        }
        $idPrueba = $pagos[0]['idpago'];
        $pago = $this->model->selectPagoById($idPrueba);
        $this->assertIsArray($pago);
        $this->assertEquals($idPrueba, $pago['idpago']);
    }
    public function testSelectPagoByIdInexistente()
    {
        $pago = $this->model->selectPagoById(99999);
        $this->assertFalse($pago);
    }
    public function testSelectPagosPorCompraInexistente()
    {
        $this->markTestSkipped('Método getPagosByCompra no existe en el modelo');
    }
    public function testSelectPagosPorFechaInvalida()
    {
        $this->markTestSkipped('Método getPagosByFecha no existe en el modelo');
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
