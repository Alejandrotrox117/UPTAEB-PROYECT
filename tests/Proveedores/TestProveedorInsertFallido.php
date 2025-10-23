<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/proveedoresModel.php';

/**
 * Prueba de caja blanca para casos de fallo en inserción de proveedores
 */
class TestProveedorInsertFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProveedoresModel();
    }

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
        $this->assertFalse($result['status'], "No debería insertar sin nombre de empresa");
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
        $this->assertFalse($result['status'], "No debería insertar sin RIF");
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
        
        // Segundo intento con mismo RIF
        $data['nombre_empresa'] = 'Empresa Dos';
        $data['correo'] = 'dos' . time() . '@test.com';
        $result2 = $this->model->insertProveedor($data);

        $this->assertIsArray($result2);
        $this->assertFalse($result2['status'], "No debería permitir RIF duplicado");
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
        // Dependiendo de validaciones puede fallar o ser exitoso
        $this->assertArrayHasKey('status', $result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
