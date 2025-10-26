<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/proveedoresModel.php';

/**
 * Prueba de caja blanca para consultas de proveedores
 * Incluye casos típicos (exitosos) y atípicos (fallidos)
 */
class TestProveedorSelect extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProveedoresModel();
    }

    // ========== CASOS TÍPICOS (EXITOSOS) ==========

    public function testSelectAllProveedoresRetornaArray()
    {
        $result = $this->model->selectAllProveedores();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testSelectAllProveedoresTieneEstructuraCorrecta()
    {
        $response = $this->model->selectAllProveedores();
        $proveedores = $response['data'] ?? [];

        if (!empty($proveedores)) {
            $proveedor = $proveedores[0];
            
            $this->assertArrayHasKey('idproveedor', $proveedor);
            $this->assertArrayHasKey('nombre', $proveedor);
            $this->assertArrayHasKey('apellido', $proveedor);
            $this->assertArrayHasKey('identificacion', $proveedor);
            $this->assertArrayHasKey('telefono_principal', $proveedor);
        } else {
            $this->markTestSkipped('No hay proveedores para verificar estructura');
        }
    }

    public function testSelectProveedorByIdExistente()
    {
        $response = $this->model->selectAllProveedores();
        $proveedores = $response['data'] ?? [];
        
        if (empty($proveedores)) {
            $this->markTestSkipped('No hay proveedores para probar');
        }

        $idPrueba = $proveedores[0]['idproveedor'];
        $proveedor = $this->model->selectProveedorById($idPrueba);

        $this->assertIsArray($proveedor);
        $this->assertEquals($idPrueba, $proveedor['idproveedor']);
    }

    // ========== CASOS ATÍPICOS (FALLIDOS) ==========

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
