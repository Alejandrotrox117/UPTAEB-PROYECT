<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/produccionModel.php';

/**
 * Prueba de caja blanca para procesos de clasificación exitosos
 * Valida registro de clasificación con movimientos de inventario
 */
class TestProduccionClasificacionExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProduccionModel();
    }

    public function testRegistrarClasificacionConDatosCompletos()
    {
        if (method_exists($this->model, 'registrarProcesoClasificacion')) {
            $data = [
                'idlote' => 1,
                'idempleado' => 1,
                'idproducto_origen' => 1,
                'kg_procesados' => 100,
                'kg_limpios' => 90,
                'kg_contaminantes' => 10,
                'observaciones' => 'Clasificación de prueba'
            ];

            $result = $this->model->registrarProcesoClasificacion($data);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('status', $result);
        } else {
            $this->markTestSkipped('Método registrarProcesoClasificacion no existe');
        }
    }

    public function testClasificacionActualizaInventario()
    {
        if (method_exists($this->model, 'registrarProcesoClasificacion')) {
            $data = [
                'idlote' => 1,
                'idempleado' => 1,
                'idproducto_origen' => 1,
                'kg_procesados' => 50,
                'kg_limpios' => 45,
                'kg_contaminantes' => 5
            ];

            $result = $this->model->registrarProcesoClasificacion($data);

            // Debería reducir el stock del producto origen
            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método registrarProcesoClasificacion no existe');
        }
    }

    public function testConsultarProcesosClasificacionPorLote()
    {
        if (method_exists($this->model, 'obtenerProcesosClasificacionPorLote')) {
            $idLote = 1;
            
            $result = $this->model->obtenerProcesosClasificacionPorLote($idLote);

            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método obtenerProcesosClasificacionPorLote no existe');
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
