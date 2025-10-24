<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/proveedoresModel.php';

/**
 * Prueba de caja blanca para inserción de proveedores
 * Incluye casos típicos (exitosos) y atípicos (fallidos)
 */
class TestProveedorInsert extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProveedoresModel();
    }

    // ========== CASOS TÍPICOS (EXITOSOS) ==========

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

    // ========== CASOS ATÍPICOS (FALLIDOS) ==========

    public function testInsertProveedorSinNombreEmpresa()
    {
        $data = [
            'nombre_empresa' => '',
            'rif' => 'J12345678',
            'direccion' => 'Dirección',
            'telefono' => '02121234567',
            'correo' => 'test@test.com',
            'contacto_principal' => 'Juan Pérez',
            'telefono_contacto' => '04121234567'
        ];

        $result = $this->model->insertProveedor($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public function testInsertProveedorSinRif()
    {
        $data = [
            'nombre_empresa' => 'Empresa Test',
            'rif' => '',
            'direccion' => 'Dirección',
            'telefono' => '02121234567',
            'correo' => 'test@test.com',
            'contacto_principal' => 'Juan Pérez',
            'telefono_contacto' => '04121234567'
        ];

        $result = $this->model->insertProveedor($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public function testInsertProveedorConRifDuplicado()
    {
        $rifUnico = 'J' . time();
        
        $data = [
            'nombre_empresa' => 'Empresa Uno',
            'rif' => $rifUnico,
            'direccion' => 'Dirección 1',
            'telefono' => '02121234567',
            'correo' => 'uno' . time() . '@test.com',
            'contacto_principal' => 'Contacto Uno',
            'telefono_contacto' => '04121234567'
        ];

        $result1 = $this->model->insertProveedor($data);
        
        $data['nombre_empresa'] = 'Empresa Dos';
        $data['correo'] = 'dos' . time() . '@test.com';
        $result2 = $this->model->insertProveedor($data);

        $this->assertIsArray($result2);
        $this->assertFalse($result2['status']);
    }

    public function testInsertProveedorConCorreoInvalido()
    {
        $data = [
            'nombre_empresa' => 'Empresa Test',
            'rif' => 'J99999999',
            'direccion' => 'Dirección',
            'telefono' => '02121234567',
            'correo' => 'correo-invalido',
            'contacto_principal' => 'Juan Pérez',
            'telefono_contacto' => '04121234567'
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
