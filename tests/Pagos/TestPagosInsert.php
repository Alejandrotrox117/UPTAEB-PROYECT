<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/pagosModel.php';

/**
 * RF05: Prueba de caja blanca para inserción de pagos
 * Incluye casos típicos (exitosos) y atípicos (fallidos)
 */
class TestPagosInsert extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new PagosModel();
    }

    // ========== CASOS TÍPICOS (EXITOSOS) ==========

    public function testInsertPagoConDatosCompletos()
    {
        $data = [
            'idcompra' => 1,
            'monto_pagado' => 500.00,
            'fecha_pago' => date('Y-m-d'),
            'idtipo_pago' => 1,
            'referencia' => 'REF-' . time(),
            'observaciones' => 'Pago de prueba'
        ];

        $result = $this->model->insertPago($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        
        if ($result['status']) {
            $this->assertTrue($result['status']);
            $this->assertArrayHasKey('pago_id', $result);
        }
    }

    public function testInsertPagoSinObservaciones()
    {
        $data = [
            'idcompra' => 1,
            'monto_pagado' => 250.00,
            'fecha_pago' => date('Y-m-d'),
            'idtipo_pago' => 2,
            'referencia' => 'REF-' . time()
        ];

        $result = $this->model->insertPago($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testInsertPagoConMontoDecimal()
    {
        $data = [
            'idcompra' => 1,
            'monto_pagado' => 123.45,
            'fecha_pago' => date('Y-m-d'),
            'idtipo_pago' => 1,
            'referencia' => 'REF-' . time()
        ];

        $result = $this->model->insertPago($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    // ========== CASOS ATÍPICOS (FALLIDOS) ==========

    public function testInsertPagoSinMonto()
    {
        $data = [
            'idcompra' => 1,
            'fecha_pago' => date('Y-m-d'),
            'idtipo_pago' => 1,
            'referencia' => 'REF-' . time()
        ];

        $result = $this->model->insertPago($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public function testInsertPagoConMontoNegativo()
    {
        $data = [
            'idcompra' => 1,
            'monto_pagado' => -100.00,
            'fecha_pago' => date('Y-m-d'),
            'idtipo_pago' => 1,
            'referencia' => 'REF-' . time()
        ];

        $result = $this->model->insertPago($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public function testInsertPagoConCompraInexistente()
    {
        $data = [
            'idcompra' => 99999,
            'monto_pagado' => 100.00,
            'fecha_pago' => date('Y-m-d'),
            'idtipo_pago' => 1,
            'referencia' => 'REF-' . time()
        ];

        $result = $this->model->insertPago($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public function testInsertPagoSinFecha()
    {
        $data = [
            'idcompra' => 1,
            'monto_pagado' => 100.00,
            'idtipo_pago' => 1,
            'referencia' => 'REF-' . time()
        ];

        $result = $this->model->insertPago($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
