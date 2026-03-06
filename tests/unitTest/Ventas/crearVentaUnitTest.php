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
class crearVentaUnitTest extends TestCase
{
    private VentasModel $model;
    /** @var \Mockery\MockInterface */
    private $mockPdo;
    /** @var \Mockery\MockInterface */
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');

        $this->mockPdo  = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('fetchColumn')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('commit')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('rollBack')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('42')->byDefault();
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(true)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new VentasModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // -------------------------------------------------------------------------
    // Helpers privados
    // -------------------------------------------------------------------------

    private function getDatosVentaValida(): array
    {
        return [
            'fecha_venta'                  => '2026-03-06',
            'idcliente'                    => 1,
            'idmoneda_general'             => 3,
            'subtotal_general'             => 60.00,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general'      => 0,
            'estatus'                      => 'BORRADOR',
            'total_general'                => 60.00,
            'observaciones'                => 'Venta de prueba unitaria',
            'tasa_usada'                   => 1,
        ];
    }

    private function getDetallesVentaValidos(): array
    {
        return [[
            'idproducto'            => 1,
            'cantidad'              => 3,
            'precio_unitario_venta' => 20.00,
            'subtotal_general'      => 60.00,
            'id_moneda_detalle'     => 3,
        ]];
    }

    // -------------------------------------------------------------------------
    // DataProviders
    // -------------------------------------------------------------------------

    public static function providerDatosVentaExitosa(): array
    {
        return [
            'venta_sin_descuento' => [
                [
                    'fecha_venta'                  => '2026-03-06',
                    'idcliente'                    => 1,
                    'idmoneda_general'             => 3,
                    'subtotal_general'             => 60.00,
                    'descuento_porcentaje_general' => 0,
                    'monto_descuento_general'      => 0,
                    'estatus'                      => 'BORRADOR',
                    'total_general'                => 60.00,
                    'observaciones'                => 'Sin descuento',
                    'tasa_usada'                   => 1,
                ],
                [[
                    'idproducto'            => 1,
                    'cantidad'              => 3,
                    'precio_unitario_venta' => 20.00,
                    'subtotal_general'      => 60.00,
                    'id_moneda_detalle'     => 3,
                ]],
            ],
            'venta_con_descuento' => [
                [
                    'fecha_venta'                  => '2026-03-06',
                    'idcliente'                    => 2,
                    'idmoneda_general'             => 3,
                    'subtotal_general'             => 100.00,
                    'descuento_porcentaje_general' => 10,
                    'monto_descuento_general'      => 10.00,
                    'estatus'                      => 'BORRADOR',
                    'total_general'                => 90.00,
                    'observaciones'                => 'Con 10% descuento',
                    'tasa_usada'                   => 1,
                ],
                [[
                    'idproducto'            => 2,
                    'cantidad'              => 10,
                    'precio_unitario_venta' => 10.00,
                    'subtotal_general'      => 100.00,
                    'id_moneda_detalle'     => 3,
                ]],
            ],
        ];
    }

    public static function providerClienteInvalido(): array
    {
        return [
            'cliente_no_existe'  => [false,                        'no existe'],
            'cliente_inactivo'   => [['estatus' => 'INACTIVO'],    'inactivo'],
        ];
    }

    // -------------------------------------------------------------------------
    // Tests insertVenta exitosa
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerDatosVentaExitosa')]
    public function testInsertVenta_DatosValidos_RetornaSuccessTrue(array $datos, array $detalles): void
    {
        // Sequence: cliente ACTIVO → nro_venta → producto detalle (activo) → tipo_movimiento → stock producto
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(
                ['estatus' => 'ACTIVO'],
                ['siguiente_numero' => 1],
                ['nombre' => 'Producto Test', 'estatus' => 'activo'],
                ['idtipomovimiento' => 1],
                ['stock' => 100, 'nombre' => 'Producto Test']
            );

        // Moneda existe
        $this->mockStmt->shouldReceive('fetchColumn')->andReturn(1);

        $result = $this->model->insertVenta($datos, $detalles);

        $this->assertIsArray($result);
        $this->assertTrue($result['success'], 'Esperaba success=true. Mensaje: ' . ($result['message'] ?? ''));
        $this->assertArrayHasKey('idventa', $result);
        $this->assertArrayHasKey('nro_venta', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals(42, $result['idventa']);
        $this->assertMatchesRegularExpression('/^VT\d+$/', $result['nro_venta']);
    }

    // -------------------------------------------------------------------------
    // Tests insertVenta — cliente inválido
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerClienteInvalido')]
    public function testInsertVenta_ClienteInvalido_RetornaSuccessFalse($retornoFetch, string $mensajeParcial): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($retornoFetch);

        // Moneda existe
        $this->mockStmt->shouldReceive('fetchColumn')->andReturn(1);

        $result = $this->model->insertVenta($this->getDatosVentaValida(), $this->getDetallesVentaValidos());

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsStringIgnoringCase($mensajeParcial, $result['message']);
    }

    // -------------------------------------------------------------------------
    // Tests insertVenta — moneda inválida
    // -------------------------------------------------------------------------

    #[Test]
    public function testInsertVenta_MonedaNoExiste_RetornaSuccessFalse(): void
    {
        // Cliente OK
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['estatus' => 'ACTIVO']);

        // Moneda no existe
        $this->mockStmt->shouldReceive('fetchColumn')->andReturn(0);

        $result = $this->model->insertVenta($this->getDatosVentaValida(), $this->getDetallesVentaValidos());

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsStringIgnoringCase('moneda', $result['message']);
    }

    // -------------------------------------------------------------------------
    // Tests insertVenta — descuento mayor al subtotal
    // -------------------------------------------------------------------------

    #[Test]
    public function testInsertVenta_DescuentoMayorSubtotal_RetornaSuccessFalse(): void
    {
        // Cliente OK
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['estatus' => 'ACTIVO']);

        // Moneda existe
        $this->mockStmt->shouldReceive('fetchColumn')->andReturn(1);

        $datos = $this->getDatosVentaValida();
        $datos['monto_descuento_general'] = 100.00; // mayor que subtotal (60.00)

        $result = $this->model->insertVenta($datos, $this->getDetallesVentaValidos());

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsStringIgnoringCase('descuento', $result['message']);
    }

    // -------------------------------------------------------------------------
    // Tests insertVenta — producto con estatus inactivo en detalle
    // -------------------------------------------------------------------------

    #[Test]
    public function testInsertVenta_ProductoInactivoEnDetalle_RetornaSuccessFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(
                ['estatus' => 'ACTIVO'],         // cliente
                ['siguiente_numero' => 1],        // generarNumeroVenta
                ['nombre' => 'Prod', 'estatus' => 'inactivo']  // producto inactivo
            );

        $this->mockStmt->shouldReceive('fetchColumn')->andReturn(1);

        $result = $this->model->insertVenta($this->getDatosVentaValida(), $this->getDetallesVentaValidos());

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsStringIgnoringCase('activo', $result['message']);
    }

    // -------------------------------------------------------------------------
    // Tests insertVenta — excepción en BD
    // -------------------------------------------------------------------------

    #[Test]
    public function testInsertVenta_ExcepcionEnBD_RetornaSuccessFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \PDOException('Connection lost'));

        $result = $this->model->insertVenta($this->getDatosVentaValida(), $this->getDetallesVentaValidos());

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    // -------------------------------------------------------------------------
    // Tests insertVenta — sin cliente (idcliente = null sin datosClienteNuevo)
    // -------------------------------------------------------------------------

    #[Test]
    public function testInsertVenta_SinIdCliente_RetornaSuccessFalse(): void
    {
        $datos = $this->getDatosVentaValida();
        $datos['idcliente'] = null;

        $result = $this->model->insertVenta($datos, $this->getDetallesVentaValidos());

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsStringIgnoringCase('cliente', $result['message']);
    }
}
