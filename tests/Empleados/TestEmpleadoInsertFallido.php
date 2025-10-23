<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/empleadosModel.php';

/**
 * Prueba de caja blanca para casos de fallo en inserción de empleados
 */
class TestEmpleadoInsertFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new EmpleadosModel();
    }

    public function testInsertEmpleadoSinNombre()
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
            $this->model->insertEmpleado($data);
            $this->fail('Debería lanzar PDOException');
        } catch (PDOException $e) {
            $this->assertInstanceOf(PDOException::class, $e);
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function testInsertEmpleadoSinIdentificacion()
    {
        $data = [
            'nombre' => 'Pedro',
            'apellido' => 'Sánchez',
            'identificacion' => '',
            'fecha_nacimiento' => '1992-03-10',
            'direccion' => 'Dirección',
            'correo_electronico' => 'pedro@test.com',
            'telefono_principal' => '04121234567',
            'estatus' => 'activo'
        ];

        try {
            $this->model->insertEmpleado($data);
            $this->fail('Debería lanzar PDOException');
        } catch (PDOException $e) {
            $this->assertInstanceOf(PDOException::class, $e);
            $this->assertNotEmpty($e->getMessage());
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
        
        // Segundo intento con mismo correo
        $data['identificacion'] = 'V' . (time() + 1);
        $data['nombre'] = 'Juan Dos';
        
        try {
            $this->model->insertEmpleado($data);
            $this->fail('Debería lanzar PDOException por correo duplicado');
        } catch (PDOException $e) {
            $this->assertInstanceOf(PDOException::class, $e);
            $this->assertNotEmpty($e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
