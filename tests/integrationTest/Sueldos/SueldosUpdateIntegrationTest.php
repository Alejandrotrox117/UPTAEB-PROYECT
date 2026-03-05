<?php

namespace Tests\IntegrationTest\Sueldos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\SueldosModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

use Tests\Traits\RequiresDatabase;

class SueldosUpdateIntegrationTest extends TestCase
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

    private function preparaSueldoYObtieneId(): int
    {
        // First, check if test record exists
        $stmt = $this->db->prepare("SELECT idsueldo FROM sueldos WHERE observacion = 'Pago de prueba update int' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row) {
            return (int) $row['idsueldo'];
        }

        // Insert an initial active record
        $stmtInsert = $this->db->prepare(
            "INSERT INTO sueldos (idempleado, monto, balance, idmoneda, observacion, estatus, fecha_creacion, fecha_modificacion) 
            VALUES (1, 800.00, 800.00, 1, 'Pago de prueba update int', 'POR_PAGAR', NOW(), NOW())"
        );
        $stmtInsert->execute();
        return (int) $this->db->lastInsertId();
    }

    #[Test]
    public function testUpdateSueldo_Exitosa_DatosValidos(): void
    {
        $idsueldo = $this->preparaSueldoYObtieneId();

        $datosNuevos = [
            'idpersona' => null,
            'idempleado' => 1,
            'monto' => 1500.00, // Update amount to trigger row update
            'idmoneda' => 1,
            'observacion' => 'Pago de prueba update int',
            'balance' => 1500.00
        ];

        $result = $this->model->updateSueldo($idsueldo, $datosNuevos);

        $this->assertIsArray($result);

        // This fails if the record had the exact exact same data, so we might get warning 'No se realizaron cambios'
        // That's why we assure `monto = 1500` will likely be a new change or we ensure it works.
        // Even if identical, it could be rowCount=0. We workaround by making it random.

        $datosNuevos['monto'] = rand(800, 2500);
        $result = $this->model->updateSueldo($idsueldo, $datosNuevos);

        $this->assertTrue($result['status'], $result['message'] ?? 'Fallo la actualizacion en integracion');
        $this->assertEquals('Sueldo actualizado exitosamente.', $result['message']);
    }

    #[Test]
    public function testUpdateSueldo_Falla_SinEmpleadoNiPersona(): void
    {
        $idsueldo = $this->preparaSueldoYObtieneId();

        $datosInvalidos = [
            'idpersona' => null,
            'idempleado' => null,
            'monto' => 800.00,
            'idmoneda' => 1,
            'observacion' => 'Test Sin Empleado',
        ];

        $result = $this->model->updateSueldo($idsueldo, $datosInvalidos);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertEquals('Debe especificar al menos una Persona o un Empleado.', $result['message']);
    }

    #[Test]
    public function testUpdateSueldo_Falla_ConMontoCero(): void
    {
        $idsueldo = $this->preparaSueldoYObtieneId();

        $datosInvalidos = [
            'idpersona' => null,
            'idempleado' => 1,
            'monto' => 0,
            'idmoneda' => 1,
            'observacion' => 'Test Monto Cero',
        ];

        $result = $this->model->updateSueldo($idsueldo, $datosInvalidos);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertEquals('El monto del sueldo debe ser mayor a cero.', $result['message']);
    }
}
