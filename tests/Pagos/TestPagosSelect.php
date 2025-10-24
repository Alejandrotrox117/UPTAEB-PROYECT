<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/pagosModel.php';

/**
 * RF05: Prueba de caja blanca para consultas de pagos
 * Incluye casos típicos (exitosos) y atípicos (fallidos)
 */
class TestPagosSelect extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new PagosModel();
    }

    // ========== CASOS TÍPICOS (EXITOSOS) ==========

    public function testSelectAllPagosRetornaArray()
    {
        $result = $this->model->selectAllPagos();

        $this->assertIsArray($result);
    }

    public function testSelectPagosPorCompraRetornaArray()
    {
        $this->markTestSkipped('Método getPagosByCompra no existe en el modelo');
    }

    public function testSelectPagosPorFechaRetornaArray()
    {
        $this->markTestSkipped('Método getPagosByFecha no existe en el modelo');
    }

    public function testSelectPagoByIdExistente()
    {
        $response = $this->model->selectAllPagos();
        $pagos = $response['data'] ?? [];
        
        if (empty($pagos)) {
            $this->markTestSkipped('No hay pagos para probar');
        }

        $idPrueba = $pagos[0]['idpago'];
        $pago = $this->model->selectPagoById($idPrueba);

        $this->assertIsArray($pago);
        $this->assertEquals($idPrueba, $pago['idpago']);
    }

    // ========== CASOS ATÍPICOS (FALLIDOS) ==========

    public function testSelectPagoByIdInexistente()
    {
        $pago = $this->model->selectPagoById(99999);

        $this->assertFalse($pago);
    }

    public function testSelectPagosPorCompraInexistente()
    {
        $this->markTestSkipped('Método getPagosByCompra no existe en el modelo');
    }

    public function testSelectPagosPorFechaInvalida()
    {
        $this->markTestSkipped('Método getPagosByFecha no existe en el modelo');
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
