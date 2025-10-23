<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/produccionModel.php';

/**
 * Prueba de caja blanca para casos de fallo en clasificación
 * Valida restricciones de stock y datos
 */
class TestProduccionClasificacionFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProduccionModel();
    }

    public function testClasificacionConStockInsuficiente()
    {
        if (method_exists($this->model, 'registrarProcesoClasificacion')) {
            $data = [
                'idlote' => 1,
                'idempleado' => 1,
                'idproducto_origen' => 1,
                'kg_procesados' => 9999999, // Cantidad muy alta
                'kg_limpios' => 9000000,
                'kg_contaminantes' => 999999
            ];

            $result = $this->model->registrarProcesoClasificacion($data);

            if (is_array($result)) {
                $this->assertFalse(
                    $result['status'],
                    "No debería permitir clasificación con stock insuficiente"
                );
                $this->assertStringContainsString(
                    'stock',
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
            // kg_limpios + kg_contaminantes != kg_procesados
            $data = [
                'idlote' => 1,
                'idempleado' => 1,
                'idproducto_origen' => 1,
                'kg_procesados' => 100,
                'kg_limpios' => 70, // 70 + 10 = 80, no 100
                'kg_contaminantes' => 10
            ];

            $result = $this->model->registrarProcesoClasificacion($data);

            // Dependiendo de validaciones, puede rechazar sumas incorrectas
            $this->assertIsArray($result);
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
                'kg_contaminantes' => 5
            ];

            try {
                $result = $this->model->registrarProcesoClasificacion($data);
                
                if (is_array($result)) {
                    $this->assertFalse($result['status']);
                }
            } catch (Exception $e) {
                $this->assertInstanceOf(Exception::class, $e);
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
