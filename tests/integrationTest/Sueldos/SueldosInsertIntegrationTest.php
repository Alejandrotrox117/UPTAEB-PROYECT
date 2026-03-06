<?php

namespace Tests\IntegrationTest\Sueldos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\SueldosModel;
use App\Core\Conexion;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class SueldosInsertIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private SueldosModel $model;
    /** ID de empleado disponible real en bd_pda_test, o null si no hay */
    private ?int $empleadoId = null;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new SueldosModel();

        // Obtener el primer empleado real disponible en la BD de prueba
        try {
            $conexion = new Conexion();
            $conexion->connect();
            $db = $conexion->get_conectGeneral();
            $stmt = $db->query('SELECT idempleado FROM empleado LIMIT 1');
            $row  = $stmt->fetch(\PDO::FETCH_ASSOC);
            $conexion->disconnect();
            $this->empleadoId = $row ? (int)$row['idempleado'] : null;
        } catch (\Throwable $e) {
            $this->empleadoId = null;
        }
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }

    // ─── Data Providers ──────────────────────────────────────────────────────

    public static function providerMontosYMonedas(): array
    {
        return [
            'bolivares_monto_bajo'  => [800.00,   3, 'IT - Pago quincenal Bs'],
            'dolares_monto_medio'   => [200.00,   1, 'IT - Bono USD'],
            'euros_monto_medio'     => [180.00,   2, 'IT - Bono EUR'],
            'bolivares_monto_alto'  => [50000.00, 3, 'IT - Salario gerencial'],
        ];
    }

    // ─── Tests de inserción exitosa ───────────────────────────────────────────

    #[Test]
    #[DataProvider('providerMontosYMonedas')]
    public function testInsertSueldo_ConEmpleadoReal_RetornaStatusTrue(
        float $monto,
        int $idmoneda,
        string $observacion
    ): void {
        if ($this->empleadoId === null) {
            $this->markTestSkipped('No hay empleados en bd_pda_test. Ejecuta el seeder primero.');
        }

        $datos = [
            'idpersona'   => null,
            'idempleado'  => $this->empleadoId,
            'monto'       => $monto,
            'idmoneda'    => $idmoneda,
            'observacion' => $observacion . ' - ' . time(),
        ];

        $result = $this->model->insertSueldo($datos);

        $this->assertIsArray($result);
        $this->assertTrue($result['status'], 'Inserción falló: ' . ($result['message'] ?? ''));
        $this->assertArrayHasKey('sueldo_id', $result);
        $this->assertGreaterThan(0, (int)$result['sueldo_id']);
    }

    #[Test]
    public function testInsertSueldo_DatosCompletos_RetornaStatusTrueConId(): void
    {
        if ($this->empleadoId === null) {
            $this->markTestSkipped('No hay empleados en bd_pda_test. Ejecuta el seeder primero.');
        }

        $datos = [
            'idpersona'   => null,
            'idempleado'  => $this->empleadoId,
            'monto'       => 1200.00,
            'idmoneda'    => 3,
            'observacion' => 'IT - Sueldo completo ' . time(),
        ];

        $result = $this->model->insertSueldo($datos);

        $this->assertIsArray($result);
        $this->assertTrue($result['status'], 'Inserción falló: ' . ($result['message'] ?? ''));
        $this->assertArrayHasKey('sueldo_id', $result);
        $this->assertGreaterThan(0, (int)$result['sueldo_id']);
    }

    #[Test]
    public function testInsertSueldo_YInsertadoPuedeConsultarseConSelectById(): void
    {
        if ($this->empleadoId === null) {
            $this->markTestSkipped('No hay empleados en bd_pda_test. Ejecuta el seeder primero.');
        }

        $datos = [
            'idpersona'   => null,
            'idempleado'  => $this->empleadoId,
            'monto'       => 350.00,
            'idmoneda'    => 1,
            'observacion' => 'IT - Verificar con selectById ' . time(),
        ];

        $resultInsert = $this->model->insertSueldo($datos);

        if (!$resultInsert['status']) {
            $this->markTestSkipped('No se pudo insertar el sueldo base: ' . ($resultInsert['message'] ?? ''));
        }

        $sueldoId = (int)$resultInsert['sueldo_id'];
        $sueldo   = $this->model->selectSueldoById($sueldoId);

        $this->assertIsArray($sueldo, 'selectSueldoById debería retornar array');
        $this->assertEquals($sueldoId, (int)$sueldo['idsueldo']);
        $this->assertEquals(350.00, (float)$sueldo['monto']);
        $this->assertEquals('POR_PAGAR', $sueldo['estatus']);
    }
}
