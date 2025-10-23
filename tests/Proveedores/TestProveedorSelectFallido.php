<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/proveedoresModel.php';

/**
 * Prueba de caja blanca para casos de fallo en consultas de proveedores
 */
class TestProveedorSelectFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProveedoresModel();
    }

    public function testSelectProveedorByIdInexistente()
    {
        $proveedor = $this->model->selectProveedorById(99999);

        $this->assertFalse($proveedor);
    }

    public function testSelectProveedorByIdNegativo()
    {
        $proveedor = $this->model->selectProveedorById(-1);

        $this->assertFalse($proveedor);
    }

    public function testSelectProveedorByIdCero()
    {
        $proveedor = $this->model->selectProveedorById(0);

        $this->assertFalse($proveedor);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
