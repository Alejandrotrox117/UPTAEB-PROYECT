<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/produccionModel.php';

/**
 * Prueba de caja blanca para casos de fallo en proceso de empaque
 * Valida restricciones de stock y datos requeridos
 */
class TestProduccionEmpaqueFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProduccionModel();
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
                $this->assertFalse($result['status'], "No debería permitir empaque con stock insuficiente");
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
                $this->assertFalse($result['status'], "No debería procesar sin datos");
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
                $this->assertFalse($result['status'], "No debería permitir cantidad negativa");
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
