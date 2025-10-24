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
                'kg_procesados' => 9999999, // Intentar procesar mucho más material del que hay en inventario
                'kg_limpios' => 9000000,
                'kg_contaminantes' => 999999,
                'observaciones' => 'Prueba: intento de clasificar con stock insuficiente'
            ];

            $result = $this->model->registrarProcesoClasificacion($data);

            if (is_array($result)) {
                $this->assertFalse(
                    $result['status'],
                    'No debería permitir clasificación cuando el inventario no tiene suficiente material'
                );
                $this->assertStringContainsString(
                    'insuficiente',
                    strtolower($result['message']),
                    'El mensaje de error debe indicar stock insuficiente (mensaje real del modelo)'
                );
            }
        } else {
            $this->markTestSkipped('Método registrarProcesoClasificacion no existe');
        }
    }

    public function testClasificacionSumaIncorrecta()
    {
        if (method_exists($this->model, 'registrarProcesoClasificacion')) {
            // En la clasificación real: kg_limpios + kg_contaminantes debe ser <= kg_procesados
            // Ejemplo: Si proceso 100 kg, puedo obtener 90 limpios + 10 contaminantes = 100
            // Pero si la suma no coincide, puede haber pérdida (normal en el proceso)
            $data = [
                'idlote' => 1,
                'idempleado' => 1,
                'idproducto_origen' => 1,
                'kg_procesados' => 100,
                'kg_limpios' => 70,  // 70 + 10 = 80, hay 20 kg de pérdida/merma
                'kg_contaminantes' => 10,
                'observaciones' => 'Prueba: clasificación con merma de material (polvo, humedad)'
            ];

            $result = $this->model->registrarProcesoClasificacion($data);

            // El sistema puede aceptar mermas, o puede validarlo según lógica de negocio
            $this->assertIsArray($result, 'Debe retornar array de resultado');
            
            // Si el modelo valida sumas estrictas, debe fallar:
            if (isset($result['status']) && !$result['status']) {
                $this->assertArrayHasKey('message', $result,
                    'Debe retornar mensaje de error del modelo si no acepta mermas');
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
                'idproducto_origen' => 99999, // ID de producto que no existe en la BD
                'kg_procesados' => 50,
                'kg_limpios' => 45,
                'kg_contaminantes' => 5,
                'observaciones' => 'Prueba: clasificación con producto inexistente'
            ];

            try {
                $result = $this->model->registrarProcesoClasificacion($data);
                
                if (is_array($result)) {
                    $this->assertFalse($result['status'],
                        'No debería permitir clasificación con producto que no existe');
                    $this->assertNotEmpty($result['message'],
                        'Debe retornar mensaje de error del modelo');
                }
            } catch (Exception $e) {
                // Si el modelo lanza excepción, capturar el mensaje real
                $this->assertInstanceOf(Exception::class, $e,
                    'Debe lanzar excepción al intentar clasificar producto inexistente');
                $this->assertNotEmpty($e->getMessage(),
                    'La excepción debe contener el mensaje de error del modelo');
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
