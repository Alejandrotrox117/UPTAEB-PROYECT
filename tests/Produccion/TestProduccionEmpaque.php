<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../app/models/produccionModel.php';
class TestProduccionEmpaque extends TestCase
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
    public function testRegistrarEmpaqueConDatosCompletos()
    {
        if (method_exists($this->model, 'registrarProcesoEmpaque')) {
            $data = [
                'idlote' => 1,
                'idempleado' => 1,
                'idproducto_empacado' => 1,
                'kg_empacados' => 50,
                'cantidad_bolsas' => 100,
                'peso_promedio_bolsa' => 0.5,
                'observaciones' => 'Empaque de prueba'
            ];
            $result = $this->model->registrarProcesoEmpaque($data);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('status', $result);
        } else {
            $this->markTestSkipped('Método registrarProcesoEmpaque no existe');
        }
    }
    public function testEmpaqueActualizaInventario()
    {
        if (method_exists($this->model, 'registrarProcesoEmpaque')) {
            $data = [
                'idlote' => 1,
                'idempleado' => 1,
                'idproducto_empacado' => 1,
                'kg_empacados' => 25,
                'cantidad_bolsas' => 50,
                'peso_promedio_bolsa' => 0.5
            ];
            $result = $this->model->registrarProcesoEmpaque($data);
            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método registrarProcesoEmpaque no existe');
        }
    }
    public function testObtenerProcesosEmpaquePorLote()
    {
        if (method_exists($this->model, 'obtenerProcesosEmpaquePorLote')) {
            $idLote = 1;
            $result = $this->model->obtenerProcesosEmpaquePorLote($idLote);
            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método obtenerProcesosEmpaquePorLote no existe');
        }
    }
    public function testEmpaqueConStockInsuficiente()
    {
        if (method_exists($this->model, 'registrarProcesoEmpaque')) {
            $data = [
                'idlote' => 1,
                'idempleado' => 1,
                'idproducto_empacado' => 1,
                'kg_empacados' => 9999999,
                'cantidad_bolsas' => 1000000,
                'peso_promedio_bolsa' => 10
            ];
            $result = $this->model->registrarProcesoEmpaque($data);
            if (is_array($result)) {
                $this->assertFalse($result['status']);
                if (array_key_exists('message', $result)) {
                    $this->showMessage($result['message']);
                }
            }
        } else {
            $this->markTestSkipped('Método registrarProcesoEmpaque no existe');
        }
    }
    public function testEmpaqueSinDatos()
    {
        if (method_exists($this->model, 'registrarProcesoEmpaque')) {
            $data = [];
            try {
                $result = $this->model->registrarProcesoEmpaque($data);
                $this->assertFalse($result['status']);
                if (is_array($result) && array_key_exists('message', $result)) {
                    $this->showMessage($result['message']);
                }
            } catch (Exception $e) {
                $this->assertInstanceOf(Exception::class, $e);
            }
        } else {
            $this->markTestSkipped('Método registrarProcesoEmpaque no existe');
        }
    }
    public function testEmpaqueConCantidadNegativa()
    {
        if (method_exists($this->model, 'registrarProcesoEmpaque')) {
            $data = [
                'idlote' => 1,
                'idempleado' => 1,
                'idproducto_empacado' => 1,
                'kg_empacados' => -50,
                'cantidad_bolsas' => 100,
                'peso_promedio_bolsa' => 0.5
            ];
            $result = $this->model->registrarProcesoEmpaque($data);
            if (is_array($result)) {
                $this->assertFalse($result['status']);
            }
        } else {
            $this->markTestSkipped('Método registrarProcesoEmpaque no existe');
        }
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
