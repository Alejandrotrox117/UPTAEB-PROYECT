<?php

namespace Tests\IntegrationTest\Empleados;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\EmpleadosModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class EmpleadoUpdateIntegrationTest extends TestCase
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

    // ─── Data Providers ──────────────────────────────────────────────────────

    public static function providerCamposActualizacion(): array
    {
        return [
            'actualizar_puesto_y_salario' => [
                'puesto'  => 'Supervisor',
                'salario' => 500.00,
            ],
            'actualizar_estatus_inactivo' => [
                'puesto'  => 'Operario',
                'salario' => 200.00,
            ],
        ];
    }

    // ─── Tests ───────────────────────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerCamposActualizacion')]
    public function testUpdateEmpleado_SobreEmpleadoCreado_RetornaTrue(string $puesto, float $salario): void
    {
        $ts             = time();
        $identificacion = 'V-UP' . substr(uniqid('', true), -8);

        $insertResult = $this->model->insertEmpleado([
            'nombre'         => 'Emp',
            'apellido'       => 'Update',
            'identificacion' => $identificacion,
            'tipo_empleado'  => 'OPERARIO',
            'estatus'        => 'ACTIVO',
        ]);

        if (!$insertResult) {
            $this->markTestSkipped('No se pudo crear el empleado base para actualizar.');
        }

        // Obtener el ID recién insertado
        $todos = $this->model->selectAllEmpleados(1);
        $encontrado = array_filter(
            $todos['data'],
            fn($e) => $e['identificacion'] === $identificacion
        );
        if (empty($encontrado)) {
            $this->markTestSkipped('No se pudo localizar el empleado insertado.');
        }

        $empleado           = reset($encontrado);
        $empleado['puesto'] = $puesto;
        $empleado['salario']= $salario;

        $resultado = $this->model->updateEmpleado($empleado);

        $this->assertTrue($resultado, 'La actualización falló.');
    }

    #[Test]
    public function testUpdateEmpleado_CambiosReflejados_EnGetById(): void
    {
        $ts             = time();
        $identificacion = 'V-CK' . substr(uniqid('', true), -8);
        $nombreOriginal = 'Emp_Orig_' . $ts;
        $nombreNuevo    = 'Emp_Upd_' . $ts;

        $this->model->insertEmpleado([
            'nombre'         => $nombreOriginal,
            'apellido'       => 'Verify',
            'identificacion' => $identificacion,
            'tipo_empleado'  => 'OPERARIO',
            'estatus'        => 'ACTIVO',
        ]);

        $todos = $this->model->selectAllEmpleados(1);
        $encontrado = array_filter($todos['data'], fn($e) => $e['identificacion'] === $identificacion);

        if (empty($encontrado)) {
            $this->markTestSkipped('No se pudo localizar el empleado insertado.');
        }

        $empleado            = reset($encontrado);
        $empleado['nombre']  = $nombreNuevo;

        $updateResult = $this->model->updateEmpleado($empleado);
        $this->assertTrue($updateResult);

        $actualizado = $this->model->getEmpleadoById($empleado['idempleado']);
        $this->assertIsArray($actualizado);
        $this->assertEquals($nombreNuevo, $actualizado['nombre']);
    }
}
