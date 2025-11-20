<?php
use PHPUnit\Framework\TestCase;
use App\Models\EmpleadosModel;
class TestEmpleadoInsert extends TestCase
{
    private $model;
    private function showMessage(string $msg)
    {
        fwrite(STDOUT, "\n[MODEL MESSAGE] " . $msg . "\n");
    }
    protected function setUp(): void
    {
        $this->model = new EmpleadosModel();
    }
    public function testInsertEmpleadoConDatosCompletos()
    {
        $data = [
            'nombre' => 'María',
            'apellido' => 'González',
            'identificacion' => 'V-' . (12000000 + time() % 10000000),
            'tipo_empleado' => 'OPERARIO',
            'puesto' => 'Operario de Clasificación',
            'salario' => 30.00,
            'fecha_nacimiento' => '1995-03-15',
            'direccion' => 'Urbanización La Victoria, Calle 5',
            'correo_electronico' => 'maria.gonzalez' . time() . '@recicladora.com',
            'telefono_principal' => '0414-5551234',
            'genero' => 'F',
            'fecha_inicio' => date('Y-m-d'),
            'observaciones' => 'Operaria especializada en clasificación de cartón y papel',
            'estatus' => 'ACTIVO'
        ];
        $result = $this->model->insertEmpleado($data);
        $this->assertTrue($result);
    }
    public function testInsertEmpleadoSinCampoRequerido()
    {
        $data = [
            'nombre' => '',
            'apellido' => 'García',
            'identificacion' => 'V11111111',
            'fecha_nacimiento' => '1990-01-01',
            'direccion' => 'Dirección',
            'correo_electronico' => 'test@test.com',
            'telefono_principal' => '04121234567',
            'estatus' => 'activo'
        ];
        try {
            $result = $this->model->insertEmpleado($data);
            if ($result === false) {
                $this->showMessage("Validación correcta: Inserción fallida por campo requerido vacío");
            }
            $this->assertFalse($result);
        } catch (PDOException $e) {
            $this->showMessage("Validación correcta: " . $e->getMessage());
            $this->assertInstanceOf(PDOException::class, $e);
        }
    }
    public function testInsertEmpleadoConCorreoDuplicado()
    {
        $correoUnico = 'duplicado' . time() . '@test.com';
        $data = [
            'nombre' => 'Juan',
            'apellido' => 'Uno',
            'identificacion' => 'V' . time(),
            'fecha_nacimiento' => '1990-01-01',
            'direccion' => 'Dirección 1',
            'correo_electronico' => $correoUnico,
            'telefono_principal' => '04121234567',
            'estatus' => 'activo'
        ];
        $result1 = $this->model->insertEmpleado($data);
        $data['identificacion'] = 'V' . (time() + 1);
        $data['nombre'] = 'Juan Dos';
        try {
            $this->model->insertEmpleado($data);
            $this->fail('Debería lanzar PDOException por correo duplicado');
        } catch (PDOException $e) {
            $this->showMessage("Validación correcta: " . $e->getMessage());
            $this->assertInstanceOf(PDOException::class, $e);
            $this->assertNotEmpty($e->getMessage());
        }
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
