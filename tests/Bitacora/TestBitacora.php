<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../app/models/bitacoraModel.php';
class TestBitacora extends TestCase
{
    private $model;
    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }
    protected function setUp(): void
    {
        $this->model = new BitacoraModel();
    }
    public function testRegistrarAccionEnBitacora()
    {
        $data = [
            'accion' => 'INSERT',
            'modulo' => 'PRODUCTOS',
            'descripcion' => 'Registro de producto de prueba',
            'idusuario' => 1
        ];
        $result = $this->model->registrarAccion('PRODUCTOS', 'INSERT', 1, 'Registro de producto de prueba');
    $this->assertNotFalse($result);
    $this->assertGreaterThan(0, (int)$result);
    }
    public function testRegistrarAccionSinUsuario()
    {
        $result = $this->model->registrarAccion('PRODUCTOS', 'INSERT', null, 'Sin usuario');
        $this->assertFalse($result);
    }
    public function testRegistrarAccionSinModulo()
    {
        $result = $this->model->registrarAccion('', 'INSERT', 1, 'Sin módulo');
        $this->assertFalse($result);
    }
    public function testConsultarBitacoraPorUsuarioInexistente()
    {
        $result = $this->model->obtenerHistorial(['idusuario' => 99999]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
    public function testConsultarBitacoraPorModuloInexistente()
    {
        $result = $this->model->obtenerHistorial(['tabla' => 'modulo_inexistente']);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
