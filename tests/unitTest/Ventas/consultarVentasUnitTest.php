<?php

namespace Tests\UnitTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\VentasModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
class consultarVentasUnitTest extends TestCase
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
    // DataProviders
    // -------------------------------------------------------------------------

    public static function providerVentasSimuladas(): array
    {
        return [
            'una_venta' => [[
                [
                    'idventa'       => 1,
                    'nro_venta'     => 'VT000001',
                    'fecha_venta'   => '2026-01-15',
                    'cliente_nombre'=> 'Juan Pérez',
                    'total_general' => 150.00,
                    'balance'       => 150.00,
                    'codigo_moneda' => 'USD',
                    'estatus'       => 'BORRADOR',
                    'observaciones' => 'Test',
                ],
            ]],
            'multiples_ventas' => [[
                ['idventa' => 1, 'nro_venta' => 'VT000001', 'estatus' => 'BORRADOR', 'total_general' => 100.00],
                ['idventa' => 2, 'nro_venta' => 'VT000002', 'estatus' => 'POR_PAGAR', 'total_general' => 200.00],
                ['idventa' => 3, 'nro_venta' => 'VT000003', 'estatus' => 'PAGADA',    'total_general' => 300.00],
            ]],
        ];
    }

    public static function providerIdsInexistentes(): array
    {
        return [
            'id_grande'    => [999999],
            'id_cero'      => [0],
            'id_muy_grande'=> [PHP_INT_MAX],
        ];
    }

    public static function providerVentaCompletaSimulada(): array
    {
        return [
            'venta_borrador' => [[
                'idventa'         => 5,
                'nro_venta'       => 'VT000005',
                'fecha_venta'     => '2026-03-01',
                'idcliente'       => 1,
                'total_general'   => 60.00,
                'balance'         => 60.00,
                'estatus'         => 'BORRADOR',
                'observaciones'   => 'Consulta unitaria',
            ]],
            'venta_por_pagar' => [[
                'idventa'         => 6,
                'nro_venta'       => 'VT000006',
                'fecha_venta'     => '2026-03-02',
                'idcliente'       => 2,
                'total_general'   => 300.00,
                'balance'         => 300.00,
                'estatus'         => 'POR_PAGAR',
                'observaciones'   => 'Pendiente de pago',
            ]],
        ];
    }

    // -------------------------------------------------------------------------
    // Tests getVentasDatatable
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerVentasSimuladas')]
    public function testGetVentasDatatable_ConDatos_RetornaArray(array $ventasSimuladas): void
    {
        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($ventasSimuladas);

        $result = $this->model->getVentasDatatable();

        $this->assertIsArray($result);
        $this->assertCount(count($ventasSimuladas), $result);
    }

    #[Test]
    public function testGetVentasDatatable_SinDatos_RetornaArrayVacio(): void
    {
        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn([]);

        $result = $this->model->getVentasDatatable();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function testGetVentasDatatable_ExcepcionEnBD_RetornaArrayVacio(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \PDOException('Connection refused'));

        $result = $this->model->getVentasDatatable();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // -------------------------------------------------------------------------
    // Tests obtenerVentaPorId
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerVentaCompletaSimulada')]
    public function testObtenerVentaPorId_Existente_RetornaDatosVenta(array $ventaSimulada): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($ventaSimulada);

        $result = $this->model->obtenerVentaPorId($ventaSimulada['idventa']);

        $this->assertIsArray($result);
        $this->assertEquals($ventaSimulada['idventa'], $result['idventa']);
        $this->assertEquals($ventaSimulada['nro_venta'], $result['nro_venta']);
        $this->assertEquals($ventaSimulada['estatus'], $result['estatus']);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testObtenerVentaPorId_Inexistente_RetornaFalse(int $id): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(false);

        $result = $this->model->obtenerVentaPorId($id);

        $this->assertFalse($result);
    }

    #[Test]
    public function testObtenerVentaPorId_ExcepcionEnBD_RetornaFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \PDOException('DB error'));

        $result = $this->model->obtenerVentaPorId(1);

        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // Tests obtenerDetalleVenta
    // -------------------------------------------------------------------------

    #[Test]
    public function testObtenerDetalleVenta_ConDetalles_RetornaArray(): void
    {
        $detallesSimulados = [
            [
                'iddetalle_venta'    => 1,
                'idventa'            => 1,
                'idproducto'         => 10,
                'cantidad'           => 3,
                'precio_unitario_venta' => 20.00,
                'subtotal_general'   => 60.00,
                'nombre_producto'    => 'Cartón Corrugado',
            ],
        ];

        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($detallesSimulados);

        $result = $this->model->obtenerDetalleVenta(1);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('idproducto', $result[0]);
        $this->assertArrayHasKey('cantidad', $result[0]);
        $this->assertArrayHasKey('precio_unitario_venta', $result[0]);
    }

    #[Test]
    public function testObtenerDetalleVenta_SinDetalles_RetornaArrayVacio(): void
    {
        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn([]);

        $result = $this->model->obtenerDetalleVenta(999);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function testObtenerDetalleVenta_ExcepcionEnBD_RetornaArrayVacio(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \Exception('Error BD'));

        $result = $this->model->obtenerDetalleVenta(1);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
