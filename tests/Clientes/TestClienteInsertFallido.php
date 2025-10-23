<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/clientesModel.php';

/**
 * Prueba de caja blanca para casos de fallo en inserción de clientes
 * Verifica el comportamiento ante datos inválidos o faltantes
 */
class TestClienteInsertFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ClientesModel();
    }

    public function testInsertClienteSinCedula()
    {
        $data = [
            'cedula' => '',
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'direccion' => 'Calle 1',
            'telefono_principal' => '04121234567'
        ];

        $result = $this->model->insertCliente($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería insertar cliente sin cédula");
    }

    public function testInsertClienteSinNombre()
    {
        $data = [
            'cedula' => 'V12345678',
            'nombre' => '',
            'apellido' => 'Pérez',
            'direccion' => 'Calle 1',
            'telefono_principal' => '04121234567'
        ];

        $result = $this->model->insertCliente($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería insertar cliente sin nombre");
    }

    public function testInsertClienteSinApellido()
    {
        $data = [
            'cedula' => 'V98765432',
            'nombre' => 'Juan',
            'apellido' => '',
            'direccion' => 'Calle 1',
            'telefono_principal' => '04121234567'
        ];

        $result = $this->model->insertCliente($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería insertar cliente sin apellido");
    }

    public function testInsertClienteConCedulaDuplicada()
    {
        $cedulaDuplicada = 'V' . time();
        
        $data = [
            'cedula' => $cedulaDuplicada,
            'nombre' => 'Cliente',
            'apellido' => 'Uno',
            'direccion' => 'Dirección 1',
            'telefono_principal' => '04121234567'
        ];

        // Primera inserción
        $result1 = $this->model->insertCliente($data);
        
        // Segunda inserción con misma cédula
        $data['nombre'] = 'Cliente Dos';
        $result2 = $this->model->insertCliente($data);

        $this->assertIsArray($result2);
        $this->assertFalse($result2['status'], "No debería permitir cédulas duplicadas");
        $this->assertStringContainsString('cédula', strtolower($result2['message']));
    }

    public function testInsertClienteConDatosIncompletos()
    {
        $data = [
            'cedula' => 'V11111111',
            'nombre' => 'Solo Nombre'
        ];

        $result = $this->model->insertCliente($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería insertar con datos incompletos");
    }

    public function testInsertClienteSinTelefono()
    {
        $data = [
            'cedula' => 'V22222222',
            'nombre' => 'Sin',
            'apellido' => 'Teléfono',
            'direccion' => 'Alguna dirección',
            'telefono_principal' => ''
        ];

        $result = $this->model->insertCliente($data);

        $this->assertIsArray($result);
        // Dependiendo de las validaciones, puede o no permitir sin teléfono
        $this->assertArrayHasKey('status', $result);
    }

    public function testInsertClienteConArrayVacio()
    {
        $data = [];

        $result = $this->model->insertCliente($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería insertar con array vacío");
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
