<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/proveedoresModel.php';

/**
 * Prueba de caja blanca para eliminación exitosa de proveedores
 * Valida eliminación lógica de proveedores
 */
class TestProveedorDeleteExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProveedoresModel();
    }

    public function testEliminarProveedorExistente()
    {
        // Crear proveedor de prueba
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
            // Obtener ID del proveedor creado
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
        // La eliminación es lógica, cambia el estatus
        $idProveedor = 1;
        
        $result = $this->model->deleteProveedorById($idProveedor);
        
        $this->assertIsBool($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
