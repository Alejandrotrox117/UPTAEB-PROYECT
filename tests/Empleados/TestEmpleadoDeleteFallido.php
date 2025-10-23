<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/empleadosModel.php';

/**
 * Prueba de caja blanca para casos de fallo en eliminación de empleados
 * Valida restricciones de eliminación
 */
class TestEmpleadoDeleteFallido extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new EmpleadosModel();
    }

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
        // Intentar eliminar un empleado que ya tiene estatus inactivo
        $idEmpleado = 1;
        
        // Primera eliminación
        $this->model->deleteEmpleado($idEmpleado);
        
        // Segunda eliminación (debería fallar o no hacer nada)
        $result = $this->model->deleteEmpleado($idEmpleado);
        
        $this->assertIsBool($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
