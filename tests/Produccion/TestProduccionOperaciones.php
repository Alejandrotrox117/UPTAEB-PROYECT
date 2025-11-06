<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../app/models/produccionModel.php';
class TestProduccionOperaciones extends TestCase
{
    private $model;
    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }
    protected function setUp(): void
    {
        $this->model = new ProduccionModel();
    }
    public function testRegistrarProduccionDiariaLote()
    {
        if (method_exists($this->model, 'registrarProduccionDiariaLote')) {
            $idLote = 1;
            $registros = [
                [
                    'idempleado' => 1,
                    'cantidad_producida' => 100,
                    'fecha_registro' => date('Y-m-d'),
                    'observaciones' => 'Producción normal'
                ],
                [
                    'idempleado' => 2,
                    'cantidad_producida' => 80,
                    'fecha_registro' => date('Y-m-d'),
                    'observaciones' => 'Producción normal'
                ]
            ];
            $result = $this->model->registrarProduccionDiariaLote($idLote, $registros);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('status', $result);
        } else {
            $this->markTestSkipped('Método registrarProduccionDiariaLote no existe');
        }
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
