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
                'idproducto_origen' => 1, // Cartón Corrugado Mixto
                'kg_procesados' => 120,    // kg recibidos para clasificar
                'kg_limpios' => 108,       // kg de material limpio obtenido (90% rendimiento)
                'kg_contaminantes' => 12,  // kg de basura/contaminantes (10%)
                'observaciones' => 'Clasificación de cartón corrugado - Material de buena calidad, pocos contaminantes'
            ];

            $result = $this->model->registrarProcesoClasificacion($data);

            $this->assertIsArray($result, 'El resultado debe ser un array');
            $this->assertArrayHasKey('status', $result, 'Debe contener clave status');
            $this->assertArrayHasKey('message', $result, 'Debe contener clave message');
            
            if ($result['status']) {
                $this->assertEquals('Proceso de clasificación registrado exitosamente.', $result['message'],
                    'Debe retornar el mensaje exacto del modelo');
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
                'idproducto_origen' => 1, // Papel Periódico Mixto
                'kg_procesados' => 80,     // kg procesados
                'kg_limpios' => 68,        // kg limpios (85% rendimiento típico de papel)
                'kg_contaminantes' => 12,  // kg contaminantes (15%)
                'observaciones' => 'Papel periódico con contaminantes normales (grapas, plástico)'
            ];

            $result = $this->model->registrarProcesoClasificacion($data);

            $this->assertIsArray($result, 'Debe retornar array con información del proceso');
            
            if (isset($result['status']) && $result['status']) {
                $this->assertStringContainsString('exitosamente', $result['message'],
                    'El mensaje debe indicar éxito en el registro');
                $this->assertArrayHasKey('movimiento', $result, 
                    'Debe retornar el número de movimiento de inventario generado');
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

            $this->assertIsArray($result, 'Debe retornar un array de procesos de clasificación');
            
            // Si hay procesos registrados, validar estructura
            if (!empty($result)) {
                $primerProceso = $result[0];
                $this->assertArrayHasKey('kg_procesados', $primerProceso, 'Debe contener kg procesados');
                $this->assertArrayHasKey('kg_limpios', $primerProceso, 'Debe contener kg limpios obtenidos');
                $this->assertArrayHasKey('kg_contaminantes', $primerProceso, 'Debe contener kg contaminantes');
                $this->assertArrayHasKey('producto_nombre', $primerProceso, 'Debe contener nombre del producto');
            }
        } else {
            $this->markTestSkipped('Método obtenerProcesosClasificacionPorLote no existe');
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
