<?php

namespace Tests\UnitTest\Sueldos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\SueldosModel;
use Mockery;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class SueldosInsertUnitTest extends TestCase
{
    private SueldosModel $model;
    /** @var \Mockery\MockInterface */
    private $mockPdo;
    /** @var \Mockery\MockInterface */
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');

        $this->mockPdo = Mockery::mock(\PDO::class);
        $this->mockStmt = Mockery::mock(\PDOStatement::class);

        // Stmt por defecto: verificación → no existe sueldo previo (total=0)
        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(\PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0])->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(\PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('15')->byDefault();

        // overload: intercepta todo new Conexion()
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new SueldosModel();
    }

    protected function tearDown(): void
    {
        unset($this->model);
        Mockery::close();
    }

    private function datosSueldoValidos(): array
    {
        return [
            'idpersona' => null,
            'idempleado' => 3,
            'monto' => 800.00,
            'idmoneda' => 3,       // Bolívares (VES)
            'observacion' => 'Pago quincenal - Caracas',
        ];
    }

    #[Test]
    public function testInsertSueldo_Exitosa_DatosValidos(): void
    {
        $stmtInsert = Mockery::mock(\PDOStatement::class);
        $stmtInsert->shouldReceive('execute')->andReturn(true);

        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtInsert);
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('15');

        $result = $this->model->insertSueldo($this->datosSueldoValidos());

        $this->assertIsArray($result);
        $this->assertTrue($result['status'], $result['message'] ?? 'sin mensaje del modelo');
        $this->assertArrayHasKey('sueldo_id', $result);
        $this->assertGreaterThan(0, (int) $result['sueldo_id']);
    }

    #[Test]
    public function testInsertSueldo_Exitosa_ConMontoAlto(): void
    {
        $stmtInsert = Mockery::mock(\PDOStatement::class);
        $stmtInsert->shouldReceive('execute')->andReturn(true);

        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtInsert);
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('20');

        $datos = $this->datosSueldoValidos();
        $datos['monto'] = 50000.00;

        $result = $this->model->insertSueldo($datos);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    #[Test]
    public function testInsertSueldo_AceptaInsercion_SinEmpleadoNiPersona(): void
    {
        $stmtInsert = Mockery::mock(\PDOStatement::class);
        $stmtInsert->shouldReceive('execute')->andThrow(
            new \Exception('Column idpersona/idempleado cannot both be NULL')
        );
        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtInsert);

        $datos = [
            'idpersona' => null,
            'monto' => 800.00,
            'idmoneda' => 3,
            'observacion' => 'Sin empleado ni persona',
        ];

        $result = $this->model->insertSueldo($datos);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], $result['message'] ?? 'sin mensaje del modelo');
    }

    #[Test]
    public function testInsertSueldo_Falla_ConMontoNegativo(): void
    {
        $stmtInsert = Mockery::mock(\PDOStatement::class);
        $stmtInsert->shouldReceive('execute')
            ->andThrow(new \Exception('CHECK constraint failed: monto >= 0'));
        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtInsert);

        $datos = $this->datosSueldoValidos();
        $datos['monto'] = -100.00;

        $result = $this->model->insertSueldo($datos);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], $result['message'] ?? 'sin mensaje del modelo');
    }

    #[Test]
    public function testInsertSueldo_Falla_CuandoLastInsertIdEsCero(): void
    {
        $stmtVerif = Mockery::mock(\PDOStatement::class);
        $stmtVerif->shouldReceive('execute')->andReturn(true);
        $stmtVerif->shouldReceive('fetch')->with(\PDO::FETCH_ASSOC)->andReturn(['total' => 0]);

        $stmtInsert = Mockery::mock(\PDOStatement::class);
        $stmtInsert->shouldReceive('execute')->andReturn(true);

        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtVerif, $stmtInsert);
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('0');

        $result = $this->model->insertSueldo($this->datosSueldoValidos());

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], $result['message'] ?? 'sin mensaje del modelo');
    }

    #[Test]
    public function testInsertSueldo_Falla_ErrorDeBD(): void
    {
        $stmtInsert = Mockery::mock(\PDOStatement::class);
        $stmtInsert->shouldReceive('execute')
            ->andThrow(new \Exception('SQLSTATE: Connection lost'));
        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtInsert);

        $result = $this->model->insertSueldo($this->datosSueldoValidos());

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], $result['message'] ?? 'sin mensaje del modelo');
    }

    public static function providerDatosSueldosVariados(): array
    {
        return [
            'empleado_1_bolivares' => [1, 800.00, 3],
            'empleado_2_dolares' => [2, 200.00, 1],
            'empleado_3_euros' => [3, 180.00, 2],
        ];
    }

    #[Test]
    #[DataProvider('providerDatosSueldosVariados')]
    public function testInsertSueldo_Exitosa_VariosEmpleados(int $idempleado, float $monto, int $idmoneda): void
    {
        $stmtInsert = Mockery::mock(\PDOStatement::class);
        $stmtInsert->shouldReceive('execute')->andReturn(true);

        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtInsert);
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn((string) (10 + $idempleado));

        $datos = [
            'idpersona' => null,
            'idempleado' => $idempleado,
            'monto' => $monto,
            'idmoneda' => $idmoneda,
            'observacion' => 'Pago prueba empleado ' . $idempleado,
        ];

        $result = $this->model->insertSueldo($datos);

        $this->assertIsArray($result);
        $this->assertTrue($result['status'], $result['message'] ?? 'sin mensaje del modelo');
    }
}
