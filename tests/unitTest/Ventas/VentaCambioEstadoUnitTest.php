<?php

namespace Tests\UnitTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\VentasModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class VentaCambioEstadoUnitTest extends TestCase
{
    private VentasModel $ventasModel;
    /** @var \Mockery\MockInterface */
    private $mockPdo;
    /** @var \Mockery\MockInterface */
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'nul' : '/dev/null');

        $this->mockPdo  = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('commit')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('rollBack')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(true)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->ventasModel = new VentasModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // ─────────────────────────────────────────────
    // DataProviders
    // ─────────────────────────────────────────────

    public static function providerEstadosInvalidos(): array
    {
        return [
            'estado_desconocido' => ['INEXISTENTE'],
            'estado_vacio'       => [''],
            'estado_con_tildes'  => ['FINALIZADÁ'],
        ];
    }

    public static function providerTransicionesInvalidas(): array
    {
        return [
            'FINALIZADA a POR_PAGAR' => ['FINALIZADA', 'POR_PAGAR'],
            'FINALIZADA a BORRADOR'  => ['FINALIZADA', 'BORRADOR'],
            'FINALIZADA a ANULADA'   => ['FINALIZADA', 'ANULADA'],
            'ANULADA a BORRADOR'     => ['ANULADA',    'BORRADOR'],
            'ANULADA a POR_PAGAR'    => ['ANULADA',    'POR_PAGAR'],
            'BORRADOR a FINALIZADA'  => ['BORRADOR',   'FINALIZADA'],
        ];
    }

    public static function providerTransicionesValidas(): array
    {
        return [
            'BORRADOR a POR_PAGAR'  => ['BORRADOR',   'POR_PAGAR'],
            'POR_PAGAR a BORRADOR'  => ['POR_PAGAR',  'BORRADOR'],   // revertir a borrador
        ];
    }

    // ─────────────────────────────────────────────
    // Tests: estado no válido en los parámetros
    // ─────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerEstadosInvalidos')]
    public function testCambiarEstado_EstadoNoValido_RetornaFalse(string $estadoInvalido): void
    {
        // La venta existe pero pedimos un estado que no está en la lista de válidos
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['estatus' => 'POR_PAGAR']);

        $resultado = $this->ventasModel->cambiarEstadoVenta(1, $estadoInvalido);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertStringContainsStringIgnoringCase('válido', strtolower($resultado['message']));
    }

    #[Test]
    public function testCambiarEstado_EstadoEnMinusculas_EsNormalizadoYAceptado(): void
    {
        // El modelo hace strtoupper() antes de validar: 'por_pagar' === 'POR_PAGAR'
        // Venta en BORRADOR, nuevo estado 'por_pagar' (minúsculas) → transición válida
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['estatus' => 'BORRADOR']);
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1);

        $resultado = $this->ventasModel->cambiarEstadoVenta(1, 'por_pagar');

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status']);
    }

    // ─────────────────────────────────────────────
    // Tests: venta inexistente
    // ─────────────────────────────────────────────

    #[Test]
    public function testCambiarEstado_VentaNoEncontrada_RetornaFalse(): void
    {
        // fetch devuelve false → venta no existe
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(false);

        $resultado = $this->ventasModel->cambiarEstadoVenta(99999, 'POR_PAGAR');

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertStringContainsStringIgnoringCase('no encontrada', strtolower($resultado['message']));
    }

    // ─────────────────────────────────────────────
    // Tests: transiciones inválidas
    // ─────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerTransicionesInvalidas')]
    public function testCambiarEstado_TransicionInvalida_RetornaFalse(string $estadoActual, string $nuevoEstado): void
    {
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['estatus' => $estadoActual]);

        $resultado = $this->ventasModel->cambiarEstadoVenta(1, $nuevoEstado);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertStringContainsStringIgnoringCase('no válida', strtolower($resultado['message']));
    }

    // ─────────────────────────────────────────────
    // Tests: transiciones válidas (sin validación extra de pagos)
    // ─────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerTransicionesValidas')]
    public function testCambiarEstado_TransicionValida_RetornaTrue(string $estadoActual, string $nuevoEstado): void
    {
        // Primera llamada: obtener estatus de la venta
        // (POR_PAGAR→BORRADOR no requiere validación de pagos)
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['estatus' => $estadoActual]);

        $this->mockStmt->shouldReceive('rowCount')->andReturn(1);

        $resultado = $this->ventasModel->cambiarEstadoVenta(1, $nuevoEstado);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status']);
    }

    // ─────────────────────────────────────────────
    // Tests: POR_PAGAR → FINALIZADA con pagos insuficientes
    // ─────────────────────────────────────────────

    #[Test]
    public function testCambiarEstado_PorPagarAFinalizada_SinPagosSuficientes_RetornaFalse(): void
    {
        // Llamadas fetch en orden:
        // 1. SELECT estatus FROM venta → venta en POR_PAGAR
        // 2. SELECT total_general FROM venta (validarPagosCompletosVenta) → total 500
        // 3. SELECT SUM(monto)... pagos conciliados → 0 (sin pagos)
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturnValues([
                ['estatus'       => 'POR_PAGAR'],          // check estatus actual
                ['total_general' => 500.00],               // total de la venta
                ['total_pagado'  => 0.00],                 // pagos conciliados = 0
            ]);

        $resultado = $this->ventasModel->cambiarEstadoVenta(1, 'FINALIZADA');

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertStringContainsStringIgnoringCase('pagad', strtolower($resultado['message']));
    }

    // ─────────────────────────────────────────────
    // Tests: POR_PAGAR → FINALIZADA con pagos completos
    // ─────────────────────────────────────────────

    #[Test]
    public function testCambiarEstado_PorPagarAFinalizada_ConPagosCompletos_RetornaTrue(): void
    {
        // Llamadas fetch en orden:
        // 1. SELECT estatus FROM venta → POR_PAGAR
        // 2. SELECT total_general FROM venta → 300
        // 3. SELECT SUM(monto) pagos conciliados → 300 (cubre el total)
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturnValues([
                ['estatus'       => 'POR_PAGAR'],   // estatus actual
                ['total_general' => 300.00],         // total venta
                ['total_pagado'  => 300.00],         // pagos conciliados = total
            ]);

        $this->mockStmt->shouldReceive('rowCount')->andReturn(1);

        $resultado = $this->ventasModel->cambiarEstadoVenta(1, 'FINALIZADA');

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status']);
        $this->assertStringContainsStringIgnoringCase('actualizado', strtolower($resultado['message']));
    }

    // ─────────────────────────────────────────────
    // Tests: FINALIZADA es estado terminal
    // ─────────────────────────────────────────────

    #[Test]
    public function testCambiarEstado_DesdeFinalizadaACualquierEstado_SiempreFalse(): void
    {
        $estadosDestino = ['BORRADOR', 'POR_PAGAR', 'ANULADA'];

        foreach ($estadosDestino as $destino) {
            $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
                ->andReturn(['estatus' => 'FINALIZADA'])
                ->once();

            $resultado = $this->ventasModel->cambiarEstadoVenta(1, $destino);

            $this->assertFalse(
                $resultado['status'],
                "La transición FINALIZADA→$destino debería ser inválida"
            );
        }
    }
}
