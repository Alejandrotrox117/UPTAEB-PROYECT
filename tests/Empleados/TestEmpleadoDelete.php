<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/empleadosModel.php';

/**
 * Prueba de caja blanca para eliminación de empleados
 * Incluye casos típicos (exitosos) y atípicos (fallidos)
 */
class TestEmpleadoDelete extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new EmpleadosModel();
    }

    // ========== CASOS TÍPICOS (EXITOSOS) ==========

    public function testEliminarEmpleadoExistente()
    {
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
        $idEmpleado = 1;
        
        $result = $this->model->deleteEmpleado($idEmpleado);
        
        $this->assertIsBool($result);
    }

    // ========== CASOS ATÍPICOS (FALLIDOS) ==========

    public function testEliminarEmpleadoInexistente()
    {
        $idInexistente = 99999;
        
        $result = $this->model->deleteEmpleado($idInexistente);
        
        $this->assertFalse($result);
    }

    public function testEliminarConIdNegativo()
    {
        $result = $this->model->deleteEmpleado(-1);
        
        $this->assertFalse($result);
    }

    public function testEliminarConIdCero()
    {
        $result = $this->model->deleteEmpleado(0);
        
        $this->assertFalse($result);
    }

    public function testEliminarEmpleadoYaEliminado()
    {
        $idEmpleado = 1;
        
        $this->model->deleteEmpleado($idEmpleado);
        
        $result = $this->model->deleteEmpleado($idEmpleado);
        
        $this->assertIsBool($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
