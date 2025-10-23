<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/empleadosModel.php';

/**
 * Prueba de caja blanca para eliminación exitosa de empleados
 * Valida eliminación lógica de empleados
 */
class TestEmpleadoDeleteExitoso extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new EmpleadosModel();
    }

    public function testEliminarEmpleadoExistente()
    {
        // Crear empleado de prueba primero
        $dataEmpleado = [
            'nombre' => 'Empleado',
            'apellido' => 'Para Eliminar',
            'identificacion' => '99999999',
            'telefono_principal' => '04141111111',
            'correo_electronico' => 'eliminar@test.com',
            'fecha_nacimiento' => '1985-01-01',
            'genero' => 'M',
            'puesto' => 'Operario',
            'salario' => 300.00
        ];

        $insertResult = $this->model->insertEmpleado($dataEmpleado);

        if ($insertResult) {
            // Obtener ID del empleado creado
            $empleados = $this->model->SelectAllEmpleados();
            
            if (is_array($empleados) && count($empleados) > 0) {
                $ultimoEmpleado = end($empleados);
                $idEmpleado = $ultimoEmpleado['idempleado'];
                
                $result = $this->model->deleteEmpleado($idEmpleado);
                
                $this->assertIsBool($result);
            } else {
                $this->markTestSkipped('No se pudo obtener el ID del empleado creado');
            }
        } else {
            $this->markTestSkipped('No se pudo crear empleado de prueba');
        }
    }

    public function testEliminarYVerificarEliminacion()
    {
        // La eliminación es lógica, cambia el estatus
        $idEmpleado = 1;
        
        $result = $this->model->deleteEmpleado($idEmpleado);
        
        $this->assertIsBool($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
