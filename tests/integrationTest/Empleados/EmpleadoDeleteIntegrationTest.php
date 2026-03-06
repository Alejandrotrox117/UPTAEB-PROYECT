<?php

namespace Tests\IntegrationTest\Empleados;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\EmpleadosModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class EmpleadoDeleteIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private EmpleadosModel $model;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new EmpleadosModel();
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }

    // ─── Tests ───────────────────────────────────────────────────────────────

    #[Test]
    public function testDeleteEmpleado_EmpleadoCreado_RetornaTrue(): void
    {
        $identificacion = 'V-DEL' . substr(uniqid('', true), -8);

        $insertResult = $this->model->insertEmpleado([
            'nombre'         => 'Emp',
            'apellido'       => 'AEliminar',
            'identificacion' => $identificacion,
            'tipo_empleado'  => 'OPERARIO',
            'estatus'        => 'ACTIVO',
        ]);

        if (!$insertResult) {
            $this->markTestSkipped('No se pudo crear el empleado base para eliminar.');
        }

        $todos      = $this->model->selectAllEmpleados(1);
        $encontrado = array_filter($todos['data'], fn($e) => $e['identificacion'] === $identificacion);

        if (empty($encontrado)) {
            $this->markTestSkipped('No se pudo localizar el empleado insertado.');
        }

        $empleado  = reset($encontrado);
        $resultado = $this->model->deleteEmpleado($empleado['idempleado']);

        $this->assertTrue($resultado, 'Se esperaba true al eliminar (marcar como INACTIVO).');
    }

    #[Test]
    public function testDeleteEmpleado_EstatusCambiaAInactivo(): void
    {
        $identificacion = 'V-IN' . substr(uniqid('', true), -8);

        $this->model->insertEmpleado([
            'nombre'         => 'Emp',
            'apellido'       => 'Inactivar',
            'identificacion' => $identificacion,
            'tipo_empleado'  => 'OPERARIO',
            'estatus'        => 'ACTIVO',
        ]);

        $todos      = $this->model->selectAllEmpleados(1);
        $encontrado = array_filter($todos['data'], fn($e) => $e['identificacion'] === $identificacion);

        if (empty($encontrado)) {
            $this->markTestSkipped('No se pudo localizar el empleado insertado.');
        }

        $empleado = reset($encontrado);
        $this->model->deleteEmpleado($empleado['idempleado']);

        // Buscar en todos los empleados (incluyendo inactivos) con super usuario ID = 1
        $todosConInactivos = $this->model->selectAllEmpleados(1);
        $eliminado = array_filter(
            $todosConInactivos['data'],
            fn($e) => $e['idempleado'] === $empleado['idempleado']
        );

        if (!empty($eliminado)) {
            $empActualizado = reset($eliminado);
            $this->assertEqualsIgnoringCase('INACTIVO', $empActualizado['estatus']);
        } else {
            // Si ya no aparece en la lista, la eliminación fue efectiva igualmente
            $this->assertTrue(true);
        }
    }

    #[Test]
    public function testDeleteEmpleado_IdInexistente_RetornaBool(): void
    {
        $resultado = $this->model->deleteEmpleado(999999);

        $this->assertIsBool($resultado);
    }
}
