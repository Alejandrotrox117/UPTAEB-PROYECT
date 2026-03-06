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

        // Stmt por defecto: sirve para verificación (total=0) y para inserción
        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(\PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0])->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(\PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1)->byDefault();

        // El Pdo debe permitir múltiples llamadas a prepare (una de verificación, otra de inserción)
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
        // En este test usamos el comportamiento por defecto configurado en setUp
        $result = $this->model->insertSueldo($this->datosSueldoValidos());

        $this->assertIsArray($result);
        $this->assertTrue($result['status'], $result['message'] ?? 'sin mensaje del modelo');
        $this->assertArrayHasKey('sueldo_id', $result);
        $this->assertGreaterThan(0, (int) $result['sueldo_id']);
    }



    #[Test]
    public function testInsertSueldo_Falla_SinEmpleadoNiPersona(): void
    {
        // Este caso el modelo lo rechaza ANTES de llegar a la BD ahora (validación de Persona/Empleado)
        $datos = [
            'idpersona' => null,
            'idempleado' => null,
            'monto' => 800.00,
            'idmoneda' => 3,
            'observacion' => 'Sin empleado ni persona',
        ];

        $result = $this->model->insertSueldo($datos);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], 'Debería fallar por falta de responsable');
        $this->assertEquals('Debe especificar al menos una Persona o un Empleado.', $result['message']);
    }

    #[Test]
    public function testInsertSueldo_Falla_ConMontoNegativo(): void
    {
        $datos = $this->datosSueldoValidos();
        $datos['monto'] = -100.00;

        $result = $this->model->insertSueldo($datos);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], 'Debería fallar por monto negativo');
        $this->assertEquals('El monto del sueldo debe ser mayor a cero.', $result['message']);
    }





}
