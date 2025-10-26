<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/empleadosModel.php';

/**
 * Prueba de caja blanca para inserción de empleados
 * Incluye casos típicos (exitosos) y atípicos (fallidos)
 */
class TestEmpleadoInsert extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new EmpleadosModel();
    }

    // ========== CASOS TÍPICOS (EXITOSOS) ==========

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

    public function testInsertEmpleadoSinObservaciones()
    {
        $data = [
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'identificacion' => 'V-' . (18000000 + time() % 10000000),
            'tipo_empleado' => 'SUPERVISOR',
            'puesto' => 'Supervisor de Producción',
            'salario' => 50.00,
            'fecha_nacimiento' => '1988-07-20',
            'direccion' => 'Sector Industrial, Galpón 3',
            'correo_electronico' => 'juan.perez' . time() . '@recicladora.com',
            'telefono_principal' => '0424-7778899',
            'genero' => 'M',
            'fecha_inicio' => date('Y-m-d'),
            'estatus' => 'ACTIVO'
        ];

        $result = $this->model->insertEmpleado($data);
        $this->assertTrue($result);
    }

    // ========== CASOS ATÍPICOS (FALLIDOS) ==========

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
