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
class VentaCreacionUnitTest extends TestCase
{
    private VentasModel $ventasModel;
    /** @var \Mockery\MockInterface */
    private $mockPdo;
    /** @var \Mockery\MockInterface */
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', '/dev/null');

        $this->mockPdo  = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        // Comportamiento por defecto
        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('fetchColumn')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('commit')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('rollBack')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('100')->byDefault();
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

    public static function providerCasosInvalidosCrearVenta(): array
    {
        return [
            'Sin Cliente' => [
                [
                    'idcliente'                    => null,   
                    'fecha_venta'                  => '2025-01-01',
                    'idmoneda_general'             => 3,
                    'subtotal_general'             => 50.00,
                    'descuento_porcentaje_general' => 0,
                    'monto_descuento_general'      => 0,
                    'total_general'                => 50.00,
                    'tasa_usada'                   => 1,
                ],
                [['idproducto' => 1, 'cantidad' => 5, 'precio_unitario_venta' => 10.00, 'subtotal_general' => 50.00, 'id_moneda_detalle' => 3]],
                'cliente',
                'ninguno',
            ],
            'Cliente Inexistente' => [
                [
                    'idcliente'                    => 9999,
                    'fecha_venta'                  => '2025-01-01',
                    'idmoneda_general'             => 3,
                    'subtotal_general'             => 50.00,
                    'descuento_porcentaje_general' => 0,
                    'monto_descuento_general'      => 0,
                    'total_general'                => 50.00,
                    'tasa_usada'                   => 1,
                ],
                [['idproducto' => 1, 'cantidad' => 5, 'precio_unitario_venta' => 10.00, 'subtotal_general' => 50.00, 'id_moneda_detalle' => 3]],
                'cliente',
                'cliente_falso',
            ],
            'Cantidad Negativa' => [
                [
                    'idcliente'                    => 1,
                    'fecha_venta'                  => '2025-01-01',
                    'idmoneda_general'             => 3,
                    'subtotal_general'             => 50.00,
                    'descuento_porcentaje_general' => 0,
                    'monto_descuento_general'      => 0,
                    'total_general'                => 50.00,
                    'tasa_usada'                   => 1,
                ],
                [['idproducto' => 1, 'cantidad' => -5, 'precio_unitario_venta' => 10.00, 'subtotal_general' => 50.00, 'id_moneda_detalle' => 3]],
                'error',
                'cliente_valido_moneda_valida',
            ],
            'Producto Inexistente' => [
                [
                    'idcliente'                    => 1,
                    'fecha_venta'                  => '2025-01-01',
                    'idmoneda_general'             => 3,
                    'subtotal_general'             => 100.00,
                    'descuento_porcentaje_general' => 0,
                    'monto_descuento_general'      => 0,
                    'total_general'                => 100.00,
                    'tasa_usada'                   => 1,
                ],
                [['idproducto' => 5555, 'cantidad' => 1, 'precio_unitario_venta' => 100.00, 'subtotal_general' => 100.00, 'id_moneda_detalle' => 3]],
                'producto',
                'cliente_valido_moneda_valida',
            ],
        ];
    }

    // ─────────────────────────────────────────────
    // Tests: casos inválidos
    // ─────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerCasosInvalidosCrearVenta')]
    public function testCrearVenta_CasosInvalidos_DevuelvenFalseYMensaje(
        array $datosVenta,
        array $detalles,
        string $mensajeParcialEsperado,
        string $stubsConfig
    ): void {
        if ($stubsConfig === 'cliente_falso') {
            // fetch retorna false → cliente no existe
            $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);
        } elseif ($stubsConfig === 'cliente_valido_moneda_valida') {
            // Primera llamada fetch → cliente activo, siguientes → nro_venta, false
            $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
                ->andReturnValues([['estatus' => 'ACTIVO'], ['siguiente_numero' => 1], false]);
            $this->mockStmt->shouldReceive('fetchColumn')->andReturn(1); // moneda existe
        }

        $resultado = $this->ventasModel->insertVenta($datosVenta, $detalles);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['success']);
        $this->assertStringContainsStringIgnoringCase($mensajeParcialEsperado, $resultado['message']);
    }

    // ─────────────────────────────────────────────
    // Test: creación exitosa
    // ─────────────────────────────────────────────

    #[Test]
    public function testCrearVenta_Exitosa(): void
    {
        // Secuencia: cliente ACTIVO → moneda existe → nro_venta → INSERT → producto activo → INSERT detalle → tipo movimiento → stock → INSERT movimiento → UPDATE stock
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturnValues([
                ['estatus' => 'ACTIVO'],                           // cliente activo
                ['siguiente_numero' => 1],                         // generarNumeroVenta
                ['nombre' => 'Prod Test', 'estatus' => 'activo'], // producto (via search)
                ['idtipomovimiento' => 1],                         // tipo movimiento Venta
                ['stock' => 100, 'nombre' => 'Prod Test'],         // stock actual
                ['stock_minimo' => 5],                             // verificarStockMinimo
            ]);
        $this->mockStmt->shouldReceive('fetchColumn')->andReturn(1); // moneda existe

        $datosVenta = [
            'idcliente'                    => 1,
            'fecha_venta'                  => '2025-01-01',
            'idmoneda_general'             => 3,
            'subtotal_general'             => 50.00,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general'      => 0,
            'total_general'                => 50.00,
            'observaciones'                => 'Test Unit Exitoso',
            'tasa_usada'                   => 1,
        ];
        $detalles = [[
            'idproducto'            => 1,
            'cantidad'              => 5,
            'precio_unitario_venta' => 10.00,
            'subtotal_general'      => 50.00,
            'id_moneda_detalle'     => 3,
        ]];

        $resultado = $this->ventasModel->insertVenta($datosVenta, $detalles);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success']);
        $this->assertEquals('100', $resultado['idventa']); // lastInsertId simulado en setUp
    }
}
