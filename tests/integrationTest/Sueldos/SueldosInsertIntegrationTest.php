<?php

namespace Tests\IntegrationTest\Sueldos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\SueldosModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

use Tests\Traits\RequiresDatabase;

class SueldosInsertIntegrationTest extends TestCase
{
    use RequiresDatabase;

    private SueldosModel $model;
    private \PDO $db;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new SueldosModel();

        $conexion = new \App\Core\Conexion();
        $conexion->connect();
        $this->db = $conexion->get_conectGeneral();
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }



    private function datosSueldoValidos(): array
    {
        return [
            'idpersona' => null,
            'idempleado' => 1,
            'monto' => 800.00,
            'idmoneda' => 1,
            'observacion' => 'Pago de prueba integracion',
        ];
    }

    #[Test]
    public function testInsertSueldo_Exitosa_DatosValidos(): void
    {
        $datos = $this->datosSueldoValidos();



        $result = $this->model->insertSueldo($datos);

        $this->assertIsArray($result);
        $this->assertTrue($result['status'], $result['message'] ?? 'Fallo la insercion en integracion');
        $this->assertArrayHasKey('sueldo_id', $result);
        $this->assertGreaterThan(0, (int) $result['sueldo_id']);
    }

    #[Test]
    public function testInsertSueldo_Falla_SinEmpleadoNiPersona(): void
    {
        $datos = [
            'idpersona' => null,
            'idempleado' => null,
            'monto' => 800.00,
            'idmoneda' => 1,
            'observacion' => 'Sin empleado ni persona',
        ];

        $result = $this->model->insertSueldo($datos);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertEquals('Debe especificar al menos una Persona o un Empleado.', $result['message']);
    }

    #[Test]
    public function testInsertSueldo_Falla_ConMontoNegativo(): void
    {
        $datos = $this->datosSueldoValidos();
        $datos['monto'] = -100.00;

        $result = $this->model->insertSueldo($datos);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertEquals('El monto del sueldo debe ser mayor a cero.', $result['message']);
    }

}
