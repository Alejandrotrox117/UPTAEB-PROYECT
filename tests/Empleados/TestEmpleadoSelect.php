<?php
use PHPUnit\Framework\TestCase;
use App\Models\EmpleadosModel;
class TestEmpleadoSelect extends TestCase
{
    private $model;
    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }
    protected function setUp(): void
    {
        $this->model = new EmpleadosModel();
    }
    public function testSeleccionarTodosEmpleados()
    {
        $result = $this->model->SelectAllEmpleados();
        $this->assertIsArray($result);
    }
    public function testObtenerEmpleadoPorId()
    {
        if (method_exists($this->model, 'getEmpleadoById')) {
            $result = $this->model->getEmpleadoById(1);
            $this->assertTrue(
                is_array($result) || is_bool($result)
            );
        } else {
            $this->markTestSkipped('Método getEmpleadoById no existe');
        }
    }
    public function testBuscarEmpleadoPorIdentificacion()
    {
        if (method_exists($this->model, 'getEmpleadoByIdentificacion')) {
            $result = $this->model->getEmpleadoByIdentificacion('12345678');
            $this->assertTrue(
                is_array($result) || is_bool($result)
            );
        } else {
            $this->markTestSkipped('Método getEmpleadoByIdentificacion no existe');
        }
    }
    public function testListarEmpleadosActivos()
    {
        if (method_exists($this->model, 'getEmpleadosActivos')) {
            $result = $this->model->getEmpleadosActivos();
            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método getEmpleadosActivos no existe');
        }
    }
    public function testObtenerEmpleadoInexistente()
    {
        if (method_exists($this->model, 'getEmpleadoById')) {
            $idInexistente = 99999;
            $result = $this->model->getEmpleadoById($idInexistente);
            $this->assertFalse($result);
        } else {
            $this->markTestSkipped('Método getEmpleadoById no existe');
        }
    }
    public function testBuscarEmpleadoPorIdentificacionInexistente()
    {
        if (method_exists($this->model, 'getEmpleadoByIdentificacion')) {
            $identificacionInexistente = '00000000';
            $result = $this->model->getEmpleadoByIdentificacion($identificacionInexistente);
            $this->assertTrue(
                $result === false || (is_array($result) && empty($result))
            );
        } else {
            $this->markTestSkipped('Método getEmpleadoByIdentificacion no existe');
        }
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
