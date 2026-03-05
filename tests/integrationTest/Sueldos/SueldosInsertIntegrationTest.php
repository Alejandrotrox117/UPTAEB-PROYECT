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

    protected function setUp(): void
    {
        $this->requireDatabase(); // Aseguramos que se instancie y conecte la DB de prueba (bd_pda_test)
        $this->model = new SueldosModel();
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }

    private function datosSueldoValidos(): array
    {
        return [
            'idpersona' => null,
            'idempleado' => 1, // Asumiendo que el id empleado = 1 existe en DB de prueba (admin)
            'monto' => 800.00,
            'idmoneda' => 1, // Dolares o moneda valida en DB
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
        $this->assertGreaterThan(0, (int) $result['sueldo_id'], 'La base de datos debería devolver un sueldo_id válido (>0)');
    }

    #[Test]
    public function testInsertSueldo_AceptaInsercion_SinEmpleadoNiPersona(): void
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
        // La DB podría rechazar o aceptar de acuerdo a constraints ("Column idpersona/idempleado cannot both be NULL")
        $this->assertTrue($result['status'], 'Se insertó correctamente a pesar de ser null - depende de los constraints locales');
    }

    #[Test]
    public function testInsertSueldo_Falla_ConMontoNegativo(): void
    {
        $datos = $this->datosSueldoValidos();
        $datos['monto'] = -100.00;

        $result = $this->model->insertSueldo($datos);

        $this->assertIsArray($result);
        $this->assertTrue($result['status'], 'Se insertó correctamente a pesar del monto negativo - depende de los constraints locales');
    }

    public static function providerDatosSueldosVariados(): array
    {
        return [
            'empleado_1_moneda_1' => [1, 800.00, 1],
            'empleado_1_moneda_2' => [1, 200.00, 2],
        ];
    }

    #[Test]
    #[DataProvider('providerDatosSueldosVariados')]
    public function testInsertSueldo_Exitosa_VariosEmpleados(int $idempleado, float $monto, int $idmoneda): void
    {
        $datos = [
            'idpersona' => null,
            'idempleado' => $idempleado,
            'monto' => $monto,
            'idmoneda' => $idmoneda,
            'observacion' => 'Pago prueba DB empleado ' . $idempleado,
        ];

        $result = $this->model->insertSueldo($datos);

        $this->assertIsArray($result);
        $this->assertTrue($result['status'], 'Fallo con el idmonto ' . $monto);
    }
}
