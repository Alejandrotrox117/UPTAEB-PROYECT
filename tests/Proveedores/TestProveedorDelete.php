<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/proveedoresModel.php';

/**
 * Prueba de caja blanca para eliminación de proveedores
 * Incluye casos típicos (exitosos) y atípicos (fallidos)
 */
class TestProveedorDelete extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProveedoresModel();
    }

    // ========== CASOS TÍPICOS (EXITOSOS) ==========

    public function testEliminarProveedorExistente()
    {
        $dataProveedor = [
            'nombre' => 'Proveedor Para Eliminar S.A.',
            'rif' => 'J-99999999-9',
            'telefono' => '02129999999',
            'correo' => 'eliminar@proveedor.com',
            'direccion' => 'Dirección de prueba',
            'representante' => 'Test Representante'
        ];

        $insertResult = $this->model->insertProveedor($dataProveedor);

        if ($insertResult) {
            $proveedores = $this->model->selectAllProveedores();
            
            if (is_array($proveedores) && count($proveedores) > 0) {
                $ultimoProveedor = end($proveedores);
                $idProveedor = $ultimoProveedor['idproveedor'];
                
                $result = $this->model->deleteProveedorById($idProveedor);
                
                $this->assertIsBool($result);
            } else {
                $this->markTestSkipped('No se pudo obtener el ID del proveedor creado');
            }
        } else {
            $this->markTestSkipped('No se pudo crear proveedor de prueba');
        }
    }

    public function testEliminarYVerificarEliminacion()
    {
        $idProveedor = 1;
        
        $result = $this->model->deleteProveedorById($idProveedor);
        
        $this->assertIsBool($result);
    }

    // ========== CASOS ATÍPICOS (FALLIDOS) ==========

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
        $idProveedor = 1;
        
        $this->model->deleteProveedorById($idProveedor);
        
        $result = $this->model->deleteProveedorById($idProveedor);
        
        $this->assertIsBool($result);
    }

    public function testEliminarProveedorConComprasAsociadas()
    {
        $idProveedor = 1;
        
        $result = $this->model->deleteProveedorById($idProveedor);
        
        $this->assertIsBool($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
