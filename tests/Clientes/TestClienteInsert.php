<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../app/models/clientesModel.php';
class TestClienteInsert extends TestCase
{
    private $model;
    private function showMessage(string $msg)
    {
        fwrite(STDOUT, "\n[MODEL MESSAGE] " . $msg . "\n");
    }
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
            $this->assertIsInt($result['cliente_id']);
            $this->assertGreaterThan(0, $result['cliente_id']);
        }
    }
    public function testInsertClienteSinCampoRequerido()
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
        $this->assertFalse($result['status']);
        $this->showMessage("Validación correcta: " . $result['message']);
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
        $result1 = $this->model->insertCliente($data);
        $data['nombre'] = 'Cliente Dos';
        $result2 = $this->model->insertCliente($data);
        $this->assertIsArray($result2);
        $this->assertFalse($result2['status']);
        $this->assertStringContainsString('cédula', strtolower($result2['message']));
        $this->showMessage("Validación correcta: " . $result2['message']);
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
