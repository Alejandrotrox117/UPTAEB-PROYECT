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
class VentaEdicionUnitTest extends TestCase
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
        $this->mockStmt->shouldReceive('fetchColumn')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('query')->andReturn($this->mockStmt)->byDefault();
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

    public static function providerCasosEditarVentaFalla(): array
    {
        return [
            'Venta Inexistente' => [
                'venta_falsa',
                'venta',
            ],
            'Producto Inexistente en Detalles' => [
                'producto_falso',
                'producto',
            ],
            'Cantidad Negativa en Detalles' => [
                'cantidad_negativa',
                'cantidad',
            ],
        ];
    }

    // ─────────────────────────────────────────────
    // Tests: casos inválidos
    // ─────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerCasosEditarVentaFalla')]
    public function testEditarVenta_CasosInvalidos_DevuelvenFalseYMensaje(string $stubsConfig, string $errorParcial): void
    {
        $datosVenta = [
            'idcliente'                    => 1,
            'fecha_venta'                  => date('Y-m-d'),
            'idmoneda_general'             => 3,
            'subtotal_general'             => 50.00,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general'      => 0,
            'estatus'                      => 'BORRADOR',
            'total_general'                => 50.00,
            'tasa_usada'                   => 1,
            'observaciones'                => 'Prueba de edición',
        ];

        if ($stubsConfig === 'venta_falsa') {
            // search() devuelve false → venta no existe
            $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);
            // sin detalles
        } elseif ($stubsConfig === 'producto_falso') {
            // La venta existe en BORRADOR, pero el producto del detalle no existe
            $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
                ->andReturnValues([
                    ['idventa' => 1, 'estatus' => 'BORRADOR', 'idcliente' => 1, 'idmoneda' => 3, 'total_general' => 50], // venta existe
                    ['count' => 1], // cliente existe
                    false,          // producto no existe
                ]);
            $datosVenta['detalles'] = [['idproducto' => 5555, 'cantidad' => 1, 'precio_unitario_venta' => 50.00, 'subtotal_general' => 50.00, 'id_moneda_detalle' => 3]];
        } elseif ($stubsConfig === 'cantidad_negativa') {
            // La venta existe en BORRADOR, el producto existe, pero la cantidad es negativa
            $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
                ->andReturnValues([
                    ['idventa' => 1, 'estatus' => 'BORRADOR', 'idcliente' => 1, 'idmoneda' => 3, 'total_general' => 50],
                    ['count' => 1],
                    ['nombre' => 'Prod Test', 'estatus' => 'activo'],
                ]);
            $datosVenta['detalles'] = [['idproducto' => 1, 'cantidad' => -5, 'precio_unitario_venta' => 10.00, 'subtotal_general' => -50.00, 'id_moneda_detalle' => 3]];
        }

        $resultado = $this->ventasModel->updateVenta(888888, $datosVenta);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsStringIgnoringCase($errorParcial, strtolower($resultado['message']));
    }

    // ─────────────────────────────────────────────
    // Test: edición exitosa (solo encabezado, sin detalles nuevos)
    // ─────────────────────────────────────────────

    #[Test]
    public function testEditarVenta_SoloEncabezado_Exitosa(): void
    {
        // La venta existe en BORRADOR, cliente existe → UPDATE encabezado → commit
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturnValues([
                ['idventa' => 1, 'estatus' => 'BORRADOR', 'idcliente' => 1, 'idmoneda' => 3, 'subtotal_general' => 50, 'descuento_porcentaje_general' => 0, 'monto_descuento_general' => 0, 'total_general' => 50, 'observaciones' => 'Orig', 'tasa' => 1, 'fecha_venta' => '2025-01-01'],
                ['count' => 1], // cliente existe
            ]);

        $datosVenta = [
            'observaciones' => 'Venta editada correctamente.',
            'total_general'  => 75.00,
        ];

        $resultado = $this->ventasModel->updateVenta(1, $datosVenta);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success']);
        $this->assertStringContainsStringIgnoringCase('actualizada', $resultado['message']);
    }
}
