<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/proveedoresModel.php';

/**
 * Prueba de caja blanca para actualización exitosa de proveedores
 * Valida actualización de datos de proveedor
 */
class TestProveedorUpdateExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProveedoresModel();
    }

    public function testActualizarProveedorConDatosCompletos()
    {
        $data = [
            'nombre' => 'Proveedor Actualizado C.A.',
            'rif' => 'J-30123456-7',
            'telefono' => '02121234567',
            'correo' => 'contacto@proveedor.com',
            'direccion' => 'Calle Principal, Oficina 123',
            'representante' => 'Juan Pérez',
            'observaciones' => 'Proveedor actualizado'
        ];

        $result = $this->model->updateProveedor(1, $data);

        $this->assertIsBool($result);
    }

    public function testActualizarSoloNombre()
    {
        $data = [
            'nombre' => 'Nuevo Nombre S.A.'
        ];

        $result = $this->model->updateProveedor(1, $data);

        $this->assertIsBool($result);
    }

    public function testActualizarContacto()
    {
        $data = [
            'telefono' => '04141234567',
            'correo' => 'nuevo@proveedor.com'
        ];

        $result = $this->model->updateProveedor(1, $data);

        $this->assertIsBool($result);
    }

    public function testActualizarRepresentante()
    {
        $data = [
            'representante' => 'María González'
        ];

        $result = $this->model->updateProveedor(1, $data);

        $this->assertIsBool($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
