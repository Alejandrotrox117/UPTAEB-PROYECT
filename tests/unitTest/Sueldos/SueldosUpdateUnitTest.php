<?php

namespace Tests\UnitTest\Sueldos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\SueldosModel;
use Mockery;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class SueldosUpdateUnitTest extends TestCase
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

        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(\PDO::FETCH_ASSOC)
            ->andReturn(['monto' => 500, 'balance' => 500, 'estatus' => 'POR_PAGAR'])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();

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
            'idempleado' => 1,
            'monto' => 1000.00,
            'idmoneda' => 1,
            'observacion' => 'Actualizacion de sueldo',
            'balance' => 1000.00
        ];
    }

    #[Test]
    public function testUpdateSueldo_Exitosa_DatosValidos(): void
    {
        $result = $this->model->updateSueldo(1, $this->datosSueldoValidos());

        $this->assertIsArray($result);
        $this->assertTrue($result['status'], $result['message'] ?? 'Fallo la actualizacion en unit test');
        $this->assertEquals('Sueldo actualizado exitosamente.', $result['message']);
    }

    #[Test]
    public function testUpdateSueldo_Falla_SinEmpleadoNiPersona(): void
    {
        $datos = $this->datosSueldoValidos();
        $datos['idempleado'] = null;
        $datos['idpersona'] = null;

        $result = $this->model->updateSueldo(1, $datos);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertEquals('Debe especificar al menos una Persona o un Empleado.', $result['message']);
    }

    #[Test]
    public function testUpdateSueldo_Falla_ConMontoNegativoOCero(): void
    {
        $datos = $this->datosSueldoValidos();
        $datos['monto'] = 0;

        $result = $this->model->updateSueldo(1, $datos);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertEquals('El monto del sueldo debe ser mayor a cero.', $result['message']);
    }

    #[Test]
    public function testUpdateSueldo_Falla_SinCambios(): void
    {
        $stmtUpdate = Mockery::mock(\PDOStatement::class);
        $stmtUpdate->shouldReceive('execute')->andReturn(true);
        $stmtUpdate->shouldReceive('rowCount')->andReturn(0);

        $stmtBalance = Mockery::mock(\PDOStatement::class);
        $stmtBalance->shouldReceive('execute')->andReturn(true);
        $stmtBalance->shouldReceive('fetch')->with(\PDO::FETCH_ASSOC)->andReturn(['monto' => 500, 'balance' => 500, 'estatus' => 'POR_PAGAR']);

        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtBalance, $stmtUpdate);

        $result = $this->model->updateSueldo(1, $this->datosSueldoValidos());

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertEquals('No se pudo actualizar el sueldo o no se realizaron cambios.', $result['message']);
    }
}
