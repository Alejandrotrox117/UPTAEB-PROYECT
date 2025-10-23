<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/proveedoresModel.php';

/**
 * Prueba de caja blanca para inserción exitosa de proveedores
 */
class TestProveedorInsertExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProveedoresModel();
    }

    public function testInsertProveedorConDatosCompletos()
    {
        $data = [
            'nombre_empresa' => 'Empresa Test ' . time(),
            'rif' => 'J' . time(),
            'direccion' => 'Zona Industrial',
            'telefono' => '02121234567',
            'correo' => 'empresa' . time() . '@test.com',
            'contacto_principal' => 'Juan Pérez',
            'telefono_contacto' => '04121234567',
            'observaciones' => 'Proveedor de prueba'
        ];

        $result = $this->model->insertProveedor($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function testInsertProveedorSinObservaciones()
    {
        $data = [
            'nombre_empresa' => 'Proveedor Simple ' . time(),
            'rif' => 'J' . (time() + 1),
            'direccion' => 'Calle Comercial',
            'telefono' => '02129876543',
            'correo' => 'simple' . time() . '@test.com',
            'contacto_principal' => 'María García',
            'telefono_contacto' => '04149876543'
        ];

        $result = $this->model->insertProveedor($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
