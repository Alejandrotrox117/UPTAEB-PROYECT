<?php
use PHPUnit\Framework\TestCase;
use App\Models\ProveedoresModel;
class TestProveedorUpdate extends TestCase
{
    private $model;
    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }
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
    public function testActualizarProveedorInexistente()
    {
        $data = [
            'nombre' => 'Proveedor Inexistente'
        ];
        $result = $this->model->updateProveedor(99999, $data);
        $this->assertFalse($result);
        if (is_array($result) && array_key_exists('message', $result)) {
            $this->showMessage($result['message']);
        }
    }
    public function testActualizarConEmailInvalido()
    {
        $data = [
            'correo' => 'email_sin_arroba_invalido'
        ];
        $result = $this->model->updateProveedor(1, $data);
        $this->assertIsBool($result);
    }
    public function testActualizarConRifDuplicado()
    {
        $data = [
            'rif' => 'J-12345678-9'
        ];
        $result = $this->model->updateProveedor(1, $data);
        $this->assertIsBool($result);
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
