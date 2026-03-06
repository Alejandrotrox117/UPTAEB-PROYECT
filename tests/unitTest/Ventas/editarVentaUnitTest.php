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
class editarVentaUnitTest extends TestCase
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
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1)->byDefault();

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
    // Helpers
    // -------------------------------------------------------------------------

    private function getVentaBorradorSimulada(): array
    {
        return [
            'idventa'                      => 10,
            'nro_venta'                    => 'VT000010',
            'fecha_venta'                  => '2026-03-01',
            'idcliente'                    => 1,
            'idmoneda'                     => 3,
            'subtotal_general'             => 60.00,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general'      => 0,
            'estatus'                      => 'BORRADOR',
            'total_general'                => 60.00,
            'balance'                      => 60.00,
            'observaciones'                => 'Venta original',
            'tasa'                         => 1,
        ];
    }

    private function getDatosEdicion(): array
    {
        return [
            'fecha_venta'                  => '2026-03-06',
            'idcliente'                    => 1,
            'idmoneda_general'             => 3,
            'subtotal_general'             => 125.00,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general'      => 0,
            'estatus'                      => 'BORRADOR',
            'total_general'                => 125.00,
            'observaciones'                => 'Venta editada correctamente.',
            'tasa_usada'                   => 1,
            'detalles'                     => [[
                'idproducto'            => 1,
                'cantidad'              => 5,
                'precio_unitario_venta' => 25.00,
                'subtotal_general'      => 125.00,
                'id_moneda_detalle'     => 3,
            ]],
        ];
    }

    // -------------------------------------------------------------------------
    // DataProviders
    // -------------------------------------------------------------------------

    public static function providerEdicionExitosa(): array
    {
        return [
            'cambio_observacion' => [
                10,
                ['observaciones' => 'Nueva observación', 'total_general' => 60.00],
            ],
            'cambio_monto' => [
                10,
                ['total_general' => 200.00, 'subtotal_general' => 200.00, 'observaciones' => 'Monto actualizado'],
            ],
        ];
    }

    public static function providerEstadosNoEditables(): array
    {
        return [
            'estado_por_pagar' => [['estatus' => 'POR_PAGAR']],
            'estado_pagada'    => [['estatus' => 'PAGADA']],
            'estado_anulada'   => [['estatus' => 'ANULADA']],
        ];
    }

    // -------------------------------------------------------------------------
    // Tests updateVenta exitosa
    // -------------------------------------------------------------------------

    #[Test]
    public function testUpdateVenta_EnBorrador_RetornaSuccessTrue(): void
    {
        // search("SELECT * FROM venta WHERE idventa = ?") → ventaBorrador
        // Luego search para cliente count → ['count' => 1]
        // Luego para insertarDetallesVenta: search para producto → ['nombre'=>'X','estatus'=>'activo']
        // Luego registrarMovimientosInventario: tipo_movimiento → stock
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(
                $this->getVentaBorradorSimulada(),
                ['count' => 1],
                ['nombre' => 'Prod Test', 'estatus' => 'activo'],
                ['idtipomovimiento' => 1],
                ['stock' => 50, 'nombre' => 'Prod Test']
            );

        $result = $this->model->updateVenta(10, $this->getDatosEdicion());

        $this->assertIsArray($result);
        $this->assertTrue($result['success'], 'Esperaba success=true. Mensaje: ' . ($result['message'] ?? ''));
        $this->assertArrayHasKey('idventa', $result);
        $this->assertEquals(10, $result['idventa']);
    }

    #[Test]
    #[DataProvider('providerEdicionExitosa')]
    public function testUpdateVenta_CamposBasicos_RetornaSuccessTrue(int $idventa, array $datosActualizacion): void
    {
        $ventaActual = $this->getVentaBorradorSimulada();
        $ventaActual['idventa'] = $idventa;

        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($ventaActual, ['count' => 1]);

        $result = $this->model->updateVenta($idventa, $datosActualizacion);

        $this->assertIsArray($result);
        $this->assertTrue($result['success'], 'Mensaje: ' . ($result['message'] ?? ''));
    }

    // -------------------------------------------------------------------------
    // Tests updateVenta — venta no existe
    // -------------------------------------------------------------------------

    #[Test]
    public function testUpdateVenta_VentaNoExiste_RetornaSuccessFalse(): void
    {
        // search devuelve false → venta no encontrada
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(false);

        $result = $this->model->updateVenta(99999, $this->getDatosEdicion());

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsStringIgnoringCase('no existe', $result['message']);
    }

    // -------------------------------------------------------------------------
    // Tests updateVenta — estado no editable
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerEstadosNoEditables')]
    public function testUpdateVenta_EstadoNoEditable_RetornaSuccessFalse(array $ventaConEstado): void
    {
        $ventaSimulada = array_merge($this->getVentaBorradorSimulada(), $ventaConEstado);

        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($ventaSimulada);

        $result = $this->model->updateVenta(10, $this->getDatosEdicion());

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsStringIgnoringCase('BORRADOR', $result['message']);
    }

    // -------------------------------------------------------------------------
    // Tests updateVenta — excepción en BD
    // -------------------------------------------------------------------------

    #[Test]
    public function testUpdateVenta_ExcepcionEnBD_RetornaSuccessFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \PDOException('Server gone away'));

        $result = $this->model->updateVenta(10, $this->getDatosEdicion());

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }
}
