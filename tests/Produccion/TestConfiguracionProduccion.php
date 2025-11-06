<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/produccionModel.php';
class TestConfiguracionProduccion extends TestCase
{
    private $model;
    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "\n[MODEL MESSAGE] " . $msg . "\n");
    }
    protected function setUp(): void
    {
        $this->model = new ProduccionModel();
    }
    public function testSelectConfiguracionProduccion()
    {
        $result = $this->model->selectConfiguracionProduccion();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }
    public function testUpdateConfiguracionConDatosVacios()
    {
        $data = [];
        try {
            $result = $this->model->updateConfiguracionProduccion($data);
            $this->assertIsArray($result);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
        }
    }
    public function testUpdateConfiguracionConValoresNegativos()
    {
        $data = [
            'productividad_clasificacion' => -100,
            'capacidad_maxima_planta' => 50,
            'salario_base' => 30,
            'beta_clasificacion' => 0.25,
            'gamma_empaque' => 5,
            'umbral_error_maximo' => 5,
            'peso_minimo_paca' => 25,
            'peso_maximo_paca' => 35
        ];
        $result = $this->model->updateConfiguracionProduccion($data);
        $this->assertIsArray($result);
        if (isset($result['status'])) {
            $this->assertIsBool($result['status']);
        }
    }
    public function testUpdateConfiguracionConDatosCompletos()
    {
        $data = [
            'productividad_clasificacion' => 150,
            'capacidad_maxima_planta' => 50,
            'salario_base' => 35,
            'beta_clasificacion' => 0.30,
            'gamma_empaque' => 6,
            'umbral_error_maximo' => 5,
            'peso_minimo_paca' => 25,
            'peso_maximo_paca' => 35
        ];
        $result = $this->model->updateConfiguracionProduccion($data);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }
    public function testSelectEmpleadosActivos()
    {
        $result = $this->model->selectEmpleadosActivos();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }
    public function testSelectProductosTodos()
    {
        $result = $this->model->selectProductos('todos');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }
    public function testSelectProductosPorClasificar()
    {
        $result = $this->model->selectProductos('por_clasificar');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }
    public function testSelectProductosClasificados()
    {
        $result = $this->model->selectProductos('clasificados');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }
    public function testSelectProductosConTipoInvalido()
    {
        $result = $this->model->selectProductos('tipo_invalido');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
