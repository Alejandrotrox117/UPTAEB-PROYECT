<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/proveedoresModel.php';

/**
 * Prueba de caja blanca para consultas exitosas de proveedores
 */
class TestProveedorSelectExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProveedoresModel();
    }

    public function testSelectAllProveedoresRetornaArray()
    {
        $result = $this->model->selectAllProveedores();

        $this->assertIsArray($result);
    }

    public function testSelectAllProveedoresTieneEstructuraCorrecta()
    {
        $proveedores = $this->model->selectAllProveedores();

        if (!empty($proveedores)) {
            $proveedor = $proveedores[0];
            
            $this->assertArrayHasKey('idproveedor', $proveedor);
            $this->assertArrayHasKey('nombre_empresa', $proveedor);
            $this->assertArrayHasKey('rif', $proveedor);
            $this->assertArrayHasKey('telefono', $proveedor);
        } else {
            $this->markTestSkipped('No hay proveedores para verificar estructura');
        }
    }

    public function testSelectProveedorByIdExistente()
    {
        $proveedores = $this->model->selectAllProveedores();
        
        if (empty($proveedores)) {
            $this->markTestSkipped('No hay proveedores para probar');
        }

        $idPrueba = $proveedores[0]['idproveedor'];
        $proveedor = $this->model->selectProveedorById($idPrueba);

        $this->assertIsArray($proveedor);
        $this->assertEquals($idPrueba, $proveedor['idproveedor']);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
