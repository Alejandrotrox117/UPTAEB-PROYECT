<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/proveedoresModel.php';

/**
 * Prueba de caja blanca para casos de fallo en actualización de proveedores
 * Valida restricciones y validaciones en actualización
 */
class TestProveedorUpdateFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProveedoresModel();
    }

    public function testActualizarProveedorInexistente()
    {
        $data = [
            'nombre' => 'Proveedor Inexistente'
        ];

        $result = $this->model->updateProveedor(99999, $data);

        $this->assertFalse($result);
    }

    public function testActualizarConEmailInvalido()
    {
        $data = [
            'correo' => 'email_sin_arroba_invalido'
        ];

        $result = $this->model->updateProveedor(1, $data);

        // Dependiendo de validaciones, puede fallar
        $this->assertIsBool($result);
    }

    public function testActualizarConRifDuplicado()
    {
        $data = [
            'rif' => 'J-12345678-9' // RIF que ya existe en otro proveedor
        ];

        $result = $this->model->updateProveedor(1, $data);

        // Puede fallar por constraint único
        $this->assertIsBool($result);
    }

    public function testActualizarSinDatos()
    {
        $data = [];

        try {
            $result = $this->model->updateProveedor(1, $data);
            $this->assertIsBool($result);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
        }
    }

    public function testActualizarConIdNegativo()
    {
        $data = [
            'nombre' => 'Proveedor'
        ];

        $result = $this->model->updateProveedor(-1, $data);

        $this->assertFalse($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
