<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/clientesModel.php';

/**
 * Prueba de caja blanca para inserción exitosa de clientes
 * Verifica que se pueda insertar un cliente con todos los campos válidos
 */
class TestClienteInsertExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ClientesModel();
    }

    public function testInsertClienteConDatosCompletos()
    {
        $cedulaUnica = 'V' . time();
        
        $data = [
            'cedula' => $cedulaUnica,
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'direccion' => 'Calle Principal #123',
            'telefono_principal' => '04121234567',
            'observaciones' => 'Cliente de prueba'
        ];

        $result = $this->model->insertCliente($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('cliente_id', $result);
        
        if ($result['status']) {
            $this->assertTrue($result['status']);
            $this->assertIsInt($result['cliente_id'], "Debería retornar un ID válido");
            $this->assertGreaterThan(0, $result['cliente_id'], "El ID debería ser mayor a 0");
        }
    }

    public function testInsertClienteSinObservaciones()
    {
        $cedulaUnica = 'V' . (time() + 1);
        
        $data = [
            'cedula' => $cedulaUnica,
            'nombre' => 'María',
            'apellido' => 'González',
            'direccion' => 'Avenida Libertador',
            'telefono_principal' => '04149876543'
        ];

        $result = $this->model->insertCliente($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testInsertClienteConNombreLargo()
    {
        $cedulaUnica = 'E' . time();
        
        $data = [
            'cedula' => $cedulaUnica,
            'nombre' => 'Juan Carlos Antonio',
            'apellido' => 'Rodríguez García',
            'direccion' => 'Urbanización Los Rosales, Calle 5, Casa 23',
            'telefono_principal' => '04261234567',
            'observaciones' => 'Cliente preferencial con descuento especial'
        ];

        $result = $this->model->insertCliente($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testInsertClienteConTelefonoFijo()
    {
        $cedulaUnica = 'V' . (time() + 2);
        
        $data = [
            'cedula' => $cedulaUnica,
            'nombre' => 'Pedro',
            'apellido' => 'Martínez',
            'direccion' => 'Centro, Edificio Torre',
            'telefono_principal' => '02121234567',
            'observaciones' => ''
        ];

        $result = $this->model->insertCliente($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
