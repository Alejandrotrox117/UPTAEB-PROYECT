<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/clientesModel.php';

/**
 * Prueba de caja blanca para casos de fallo en actualización de clientes
 * Verifica validaciones y manejo de errores
 */
class TestClienteUpdateFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ClientesModel();
    }

    public function testUpdateClienteInexistente()
    {
        $dataUpdate = [
            'cedula' => 'V88888888',
            'nombre' => 'No Existe',
            'apellido' => 'Cliente',
            'direccion' => 'Ninguna',
            'telefono_principal' => '04121111111',
            'observaciones' => ''
        ];

        $result = $this->model->updateCliente(99999, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería actualizar un cliente inexistente");
    }

    public function testUpdateClienteConCedulaVacia()
    {
        $dataUpdate = [
            'cedula' => '',
            'nombre' => 'Test',
            'apellido' => 'Cliente',
            'direccion' => 'Dirección',
            'telefono_principal' => '04121111111',
            'observaciones' => ''
        ];

        $result = $this->model->updateCliente(1, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería actualizar con cédula vacía");
    }

    public function testUpdateClienteConNombreVacio()
    {
        $dataUpdate = [
            'cedula' => 'V77777777',
            'nombre' => '',
            'apellido' => 'Cliente',
            'direccion' => 'Dirección',
            'telefono_principal' => '04121111111',
            'observaciones' => ''
        ];

        $result = $this->model->updateCliente(1, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería actualizar con nombre vacío");
    }

    public function testUpdateClienteConDatosIncompletos()
    {
        $dataUpdate = [
            'cedula' => 'V66666666',
            'nombre' => 'Solo Datos'
        ];

        $result = $this->model->updateCliente(1, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería actualizar con datos incompletos");
    }

    public function testUpdateClienteConIdNegativo()
    {
        $dataUpdate = [
            'cedula' => 'V55555555',
            'nombre' => 'Test',
            'apellido' => 'Cliente',
            'direccion' => 'Dirección',
            'telefono_principal' => '04121111111',
            'observaciones' => ''
        ];

        $result = $this->model->updateCliente(-1, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería actualizar con ID negativo");
    }

    public function testUpdateClienteConIdCero()
    {
        $dataUpdate = [
            'cedula' => 'V44444444',
            'nombre' => 'Test',
            'apellido' => 'Cliente',
            'direccion' => 'Dirección',
            'telefono_principal' => '04121111111',
            'observaciones' => ''
        ];

        $result = $this->model->updateCliente(0, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], "No debería actualizar con ID cero");
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
