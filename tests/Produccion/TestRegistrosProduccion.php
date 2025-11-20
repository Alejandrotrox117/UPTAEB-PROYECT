<?php

use PHPUnit\Framework\TestCase;
use App\Models\ProduccionModel;
class TestRegistrosProduccion extends TestCase
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
    public function testInsertarRegistroSinIdLote()
    {
        $data = [
            'idempleado' => 1,
            'fecha_jornada' => date('Y-m-d'),
            'idproducto_producir' => 1,
            'cantidad_producir' => 100,
            'idproducto_terminado' => 2,
            'cantidad_producida' => 90,
            'tipo_movimiento' => 'CLASIFICACION'
        ];
        try {
            $result = $this->model->insertarRegistroProduccion($data);
            $this->assertIsArray($result);
            if (isset($result['status'])) {
                $this->assertFalse($result['status']);
            }
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
        }
    }
    public function testInsertarRegistroConLoteInexistente()
    {
        $data = [
            'idlote' => 99999,
            'idempleado' => 1,
            'fecha_jornada' => date('Y-m-d'),
            'idproducto_producir' => 1,
            'cantidad_producir' => 100,
            'idproducto_terminado' => 2,
            'cantidad_producida' => 90,
            'tipo_movimiento' => 'CLASIFICACION'
        ];
        $result = $this->model->insertarRegistroProduccion($data);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsString('lote', strtolower($result['message']));
        $this->showMessage("Validación correcta: " . $result['message']);
    }
    public function testInsertarRegistroConEmpleadoInexistente()
    {
        $data = [
            'idlote' => 1,
            'idempleado' => 99999,
            'fecha_jornada' => date('Y-m-d'),
            'idproducto_producir' => 1,
            'cantidad_producir' => 100,
            'idproducto_terminado' => 2,
            'cantidad_producida' => 90,
            'tipo_movimiento' => 'CLASIFICACION'
        ];
        $result = $this->model->insertarRegistroProduccion($data);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
    public function testInsertarRegistroConCantidadNegativa()
    {
        $data = [
            'idlote' => 1,
            'idempleado' => 1,
            'fecha_jornada' => date('Y-m-d'),
            'idproducto_producir' => 1,
            'cantidad_producir' => 100,
            'idproducto_terminado' => 2,
            'cantidad_producida' => -50,
            'tipo_movimiento' => 'CLASIFICACION'
        ];
        $result = $this->model->insertarRegistroProduccion($data);
        $this->assertIsArray($result);
        if (isset($result['salarios'])) {
            $this->assertArrayHasKey('salario_total', $result['salarios']);
        }
    }
    public function testInsertarRegistroSinFechaJornada()
    {
        $data = [
            'idlote' => 1,
            'idempleado' => 1,
            'idproducto_producir' => 1,
            'cantidad_producir' => 100,
            'idproducto_terminado' => 2,
            'cantidad_producida' => 90,
            'tipo_movimiento' => 'CLASIFICACION'
        ];
        try {
            $result = $this->model->insertarRegistroProduccion($data);
            $this->assertIsArray($result);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
        }
    }
    public function testInsertarRegistroSinTipoMovimiento()
    {
        $data = [
            'idlote' => 1,
            'idempleado' => 1,
            'fecha_jornada' => date('Y-m-d'),
            'idproducto_producir' => 1,
            'cantidad_producir' => 100,
            'idproducto_terminado' => 2,
            'cantidad_producida' => 90
        ];
        try {
            $result = $this->model->insertarRegistroProduccion($data);
            $this->assertIsArray($result);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
        }
    }
    public function testObtenerRegistrosPorLoteConIdInexistente()
    {
        $result = $this->model->obtenerRegistrosPorLote(99999);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        if ($result['status']) {
            $this->assertEmpty($result['data']);
        }
    }
    public function testSelectAllRegistrosProduccion()
    {
        $result = $this->model->selectAllRegistrosProduccion();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }
    public function testSelectAllRegistrosConFiltroFechas()
    {
        $filtros = [
            'fecha_desde' => date('Y-m-d', strtotime('-30 days')),
            'fecha_hasta' => date('Y-m-d')
        ];
        $result = $this->model->selectAllRegistrosProduccion($filtros);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
    }
    public function testSelectAllRegistrosConFiltroTipoMovimiento()
    {
        $filtros = [
            'tipo_movimiento' => 'CLASIFICACION'
        ];
        $result = $this->model->selectAllRegistrosProduccion($filtros);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
    }
    public function testActualizarRegistroConIdInexistente()
    {
        $data = [
            'fecha_jornada' => date('Y-m-d'),
            'cantidad_producida' => 100,
            'tipo_movimiento' => 'CLASIFICACION',
            'idproducto_producir' => 1,
            'cantidad_producir' => 100,
            'idproducto_terminado' => 2,
            'observaciones' => 'Actualización de prueba'
        ];
        $result = $this->model->actualizarRegistroProduccion(99999, $data);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
    public function testEliminarRegistroConIdInexistente()
    {
        $result = $this->model->eliminarRegistroProduccion(99999);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
    public function testGetRegistroByIdConIdInexistente()
    {
        $result = $this->model->getRegistroById(99999);
        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
