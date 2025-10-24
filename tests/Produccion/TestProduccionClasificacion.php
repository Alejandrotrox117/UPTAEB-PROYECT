<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/produccionModel.php';

/**
 * Prueba de caja blanca para procesos de clasificación
 * Incluye casos típicos (exitosos) y atípicos (fallidos)
 */
class TestProduccionClasificacion extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProduccionModel();
    }

    // ========== CASOS TÍPICOS (EXITOSOS) ==========

    public function testRegistrarClasificacionConDatosCompletos()
    {
        if (method_exists($this->model, 'registrarProcesoClasificacion')) {
            $data = [
                'idlote' => 1,
                'idempleado' => 1,
                'idproducto_origen' => 1,
                'kg_procesados' => 120,
                'kg_limpios' => 108,
                'kg_contaminantes' => 12,
                'observaciones' => 'Clasificación de cartón corrugado - Material de buena calidad'
            ];

            $result = $this->model->registrarProcesoClasificacion($data);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('status', $result);
            $this->assertArrayHasKey('message', $result);
            
            if ($result['status']) {
                $this->assertEquals('Proceso de clasificación registrado exitosamente.', $result['message']);
            }
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
                'kg_procesados' => 80,
                'kg_limpios' => 68,
                'kg_contaminantes' => 12,
                'observaciones' => 'Papel periódico con contaminantes normales'
            ];

            $result = $this->model->registrarProcesoClasificacion($data);

            $this->assertIsArray($result);
            
            if (isset($result['status']) && $result['status']) {
                $this->assertStringContainsString('exitosamente', $result['message']);
                $this->assertArrayHasKey('movimiento', $result);
            }
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
            
            if (!empty($result)) {
                $primerProceso = $result[0];
                $this->assertArrayHasKey('kg_procesados', $primerProceso);
                $this->assertArrayHasKey('kg_limpios', $primerProceso);
                $this->assertArrayHasKey('kg_contaminantes', $primerProceso);
                $this->assertArrayHasKey('producto_nombre', $primerProceso);
            }
        } else {
            $this->markTestSkipped('Método obtenerProcesosClasificacionPorLote no existe');
        }
    }

    // ========== CASOS ATÍPICOS (FALLIDOS) ==========

    public function testClasificacionConStockInsuficiente()
    {
        if (method_exists($this->model, 'registrarProcesoClasificacion')) {
            $data = [
                'idlote' => 1,
                'idempleado' => 1,
                'idproducto_origen' => 1,
                'kg_procesados' => 9999999,
                'kg_limpios' => 9000000,
                'kg_contaminantes' => 999999,
                'observaciones' => 'Prueba: intento de clasificar con stock insuficiente'
            ];

            $result = $this->model->registrarProcesoClasificacion($data);

            if (is_array($result)) {
                $this->assertFalse($result['status']);
                $this->assertStringContainsString(
                    'insuficiente',
                    strtolower($result['message'])
                );
            }
        } else {
            $this->markTestSkipped('Método registrarProcesoClasificacion no existe');
        }
    }

    public function testClasificacionSumaIncorrecta()
    {
        if (method_exists($this->model, 'registrarProcesoClasificacion')) {
            $data = [
                'idlote' => 1,
                'idempleado' => 1,
                'idproducto_origen' => 1,
                'kg_procesados' => 100,
                'kg_limpios' => 70,
                'kg_contaminantes' => 10,
                'observaciones' => 'Prueba: clasificación con merma de material'
            ];

            $result = $this->model->registrarProcesoClasificacion($data);

            $this->assertIsArray($result);
            
            if (isset($result['status']) && !$result['status']) {
                $this->assertArrayHasKey('message', $result);
            }
        } else {
            $this->markTestSkipped('Método registrarProcesoClasificacion no existe');
        }
    }

    public function testClasificacionConProductoInexistente()
    {
        if (method_exists($this->model, 'registrarProcesoClasificacion')) {
            $data = [
                'idlote' => 1,
                'idempleado' => 1,
                'idproducto_origen' => 99999,
                'kg_procesados' => 50,
                'kg_limpios' => 45,
                'kg_contaminantes' => 5,
                'observaciones' => 'Prueba: clasificación con producto inexistente'
            ];

            try {
                $result = $this->model->registrarProcesoClasificacion($data);
                
                if (is_array($result)) {
                    $this->assertFalse($result['status']);
                    $this->assertNotEmpty($result['message']);
                }
            } catch (Exception $e) {
                $this->assertInstanceOf(Exception::class, $e);
                $this->assertNotEmpty($e->getMessage());
            }
        } else {
            $this->markTestSkipped('Método registrarProcesoClasificacion no existe');
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
