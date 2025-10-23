<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/movimientosModel.php';

/**
 * RF06: Prueba de caja blanca para inserción de movimientos de existencia
 * Incluye casos típicos (exitosos) y atípicos (fallidos)
 */
class TestMovimientosInsert extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new MovimientosModel();
    }

    // ========== CASOS TÍPICOS (EXITOSOS) ==========

    public function testInsertMovimientoEntradaConDatosCompletos()
    {
        $data = [
            'idproducto' => 1,
            'tipo_movimiento' => 'ENTRADA',
            'cantidad' => 100.00,
            'motivo' => 'Compra de material',
            'fecha_movimiento' => date('Y-m-d'),
            'observaciones' => 'Movimiento de prueba'
        ];

        $result = $this->model->insertMovimiento($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        
        if ($result['status']) {
            $this->assertTrue($result['status']);
            $this->assertArrayHasKey('movimiento_id', $result);
        }
    }

    public function testInsertMovimientoSalidaConDatosCompletos()
    {
        $data = [
            'idproducto' => 1,
            'tipo_movimiento' => 'SALIDA',
            'cantidad' => 50.00,
            'motivo' => 'Venta de material',
            'fecha_movimiento' => date('Y-m-d')
        ];

        $result = $this->model->insertMovimiento($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testInsertMovimientoAjusteInventario()
    {
        $data = [
            'idproducto' => 1,
            'tipo_movimiento' => 'AJUSTE',
            'cantidad' => 10.00,
            'motivo' => 'Ajuste por inventario físico',
            'fecha_movimiento' => date('Y-m-d'),
            'observaciones' => 'Diferencia encontrada en conteo'
        ];

        $result = $this->model->insertMovimiento($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    // ========== CASOS ATÍPICOS (FALLIDOS) ==========

    public function testInsertMovimientoSinProducto()
    {
        $data = [
            'tipo_movimiento' => 'ENTRADA',
            'cantidad' => 100.00,
            'motivo' => 'Sin producto',
            'fecha_movimiento' => date('Y-m-d')
        ];

        $result = $this->model->insertMovimiento($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public function testInsertMovimientoConCantidadNegativa()
    {
        $data = [
            'idproducto' => 1,
            'tipo_movimiento' => 'ENTRADA',
            'cantidad' => -50.00,
            'motivo' => 'Cantidad negativa',
            'fecha_movimiento' => date('Y-m-d')
        ];

        $result = $this->model->insertMovimiento($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public function testInsertMovimientoConProductoInexistente()
    {
        $data = [
            'idproducto' => 99999,
            'tipo_movimiento' => 'ENTRADA',
            'cantidad' => 100.00,
            'motivo' => 'Producto inexistente',
            'fecha_movimiento' => date('Y-m-d')
        ];

        $result = $this->model->insertMovimiento($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public function testInsertMovimientoConTipoInvalido()
    {
        $data = [
            'idproducto' => 1,
            'tipo_movimiento' => 'INVALIDO',
            'cantidad' => 100.00,
            'motivo' => 'Tipo inválido',
            'fecha_movimiento' => date('Y-m-d')
        ];

        $result = $this->model->insertMovimiento($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
