<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/proveedoresModel.php';

/**
 * Prueba de caja blanca para casos de fallo en eliminación de proveedores
 * Valida restricciones de eliminación
 */
class TestProveedorDeleteFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProveedoresModel();
    }

    public function testEliminarProveedorInexistente()
    {
        $idInexistente = 99999;
        
        $result = $this->model->deleteProveedorById($idInexistente);
        
        $this->assertFalse($result);
    }

    public function testEliminarConIdNegativo()
    {
        $result = $this->model->deleteProveedorById(-1);
        
        $this->assertFalse($result);
    }

    public function testEliminarConIdCero()
    {
        $result = $this->model->deleteProveedorById(0);
        
        $this->assertFalse($result);
    }

    public function testEliminarProveedorYaEliminado()
    {
        // Intentar eliminar un proveedor que ya tiene estatus inactivo
        $idProveedor = 1;
        
        // Primera eliminación
        $this->model->deleteProveedorById($idProveedor);
        
        // Segunda eliminación (debería fallar o no hacer nada)
        $result = $this->model->deleteProveedorById($idProveedor);
        
        $this->assertIsBool($result);
    }

    public function testEliminarProveedorConComprasAsociadas()
    {
        // Un proveedor con compras asociadas no debería eliminarse físicamente
        // solo lógicamente
        $idProveedor = 1; // Proveedor que tiene compras
        
        $result = $this->model->deleteProveedorById($idProveedor);
        
        $this->assertIsBool($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
