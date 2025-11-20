<?php
use PHPUnit\Framework\TestCase;
use App\Models\ProveedoresModel;
class TestProveedorInsert extends TestCase
{
    private $model;
    private function showMessage(string $msg)
    {
        fwrite(STDOUT, "\n[MODEL MESSAGE] " . $msg . "\n");
    }
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
    public function testInsertProveedorSinCampoRequerido()
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
        $this->showMessage("Validación correcta: " . $result['message']);
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
        $this->showMessage("Validación correcta: " . $result2['message']);
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
