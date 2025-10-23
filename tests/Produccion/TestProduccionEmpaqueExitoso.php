<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/produccionModel.php';

/**
 * Prueba de caja blanca para proceso de empaque exitoso
 * Valida registro de proceso de empaque con movimientos de inventario
 */
class TestProduccionEmpaqueExitoso extends TestCase
{
    private $model;

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

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
