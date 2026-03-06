<?php

namespace Tests\IntegrationTest\Empleados;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\EmpleadosModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class EmpleadoInsertIntegrationTest extends TestCase
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

    public static function providerCasosInsertValidos(): array
    {
        $uid = uniqid('', true);
        return [
            'operario_datos_completos' => [
                [
                    'nombre'            => 'María',
                    'apellido'          => 'González',
                    'identificacion'    => 'V-OP' . substr($uid, -6),
                    'tipo_empleado'     => 'OPERARIO',
                    'puesto'            => 'Operario de Clasificación',
                    'salario'           => 30.00,
                    'fecha_nacimiento'  => '1995-03-15',
                    'direccion'         => 'Urbanización La Victoria',
                    'correo_electronico'=> 'maria.it.' . $uid . '@recicladora.com',
                    'telefono_principal'=> '0414-5551234',
                    'genero'            => 'F',
                    'fecha_inicio'      => '2024-01-01',
                    'observaciones'     => 'Prueba de integración',
                    'estatus'           => 'ACTIVO',
                ],
                true,
            ],
            'administrativo_minimo' => [
                [
                    'nombre'         => 'Luis',
                    'apellido'       => 'Ramírez',
                    'identificacion' => 'V-ADM' . substr(uniqid('', true), -6),
                    'tipo_empleado'  => 'ADMINISTRATIVO',
                    'estatus'        => 'ACTIVO',
                ],
                true,
            ],
        ];
    }

    // ─── Tests ───────────────────────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerCasosInsertValidos')]
    public function testInsertEmpleado_DatosValidos_RetornaTrue(array $data, bool $esperado): void
    {
        $resultado = $this->model->insertEmpleado($data);

        $this->assertEquals($esperado, $resultado, 'La inserción retornó un resultado inesperado.');
    }

    #[Test]
    public function testInsertEmpleado_EmpleadoInsertadoAparece_EnSelectAll(): void
    {
        $identificacion = 'V-SA' . substr(uniqid('', true), -8);
        $data = [
            'nombre'         => 'Empleado',
            'apellido'       => 'IntegTest',
            'identificacion' => $identificacion,
            'tipo_empleado'  => 'OPERARIO',
            'estatus'        => 'ACTIVO',
        ];

        $insertResult = $this->model->insertEmpleado($data);
        $this->assertTrue($insertResult, 'La inserción base falló.');

        $todos = $this->model->selectAllEmpleados(1); // super usuario para ver todos
        $this->assertTrue($todos['status']);

        $encontrado = array_filter(
            $todos['data'],
            fn($e) => $e['identificacion'] === $identificacion
        );
        $this->assertNotEmpty($encontrado, 'El empleado insertado no aparece en SelectAll.');
    }
}
