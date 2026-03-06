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
class eliminarVentaUnitTest extends TestCase
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
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('commit')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('rollBack')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('0')->byDefault();
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
    // DataProviders
    // -------------------------------------------------------------------------

    public static function providerIdsInexistentes(): array
    {
        return [
            'id_muy_grande' => [999999],
            'id_cero'       => [0],
        ];
    }

    public static function providerEstadosNoEliminables(): array
    {
        return [
            'estado_por_pagar' => ['POR_PAGAR'],
            'estado_pagada'    => ['PAGADA'],
            'estado_anulada'   => ['ANULADA'],
            'estado_inactivo'  => ['INACTIVO'],
        ];
    }

    // -------------------------------------------------------------------------
    // Tests eliminarVenta exitosa
    // -------------------------------------------------------------------------

    #[Test]
    public function testEliminarVenta_EnBorrador_RetornaSuccessTrue(): void
    {
        // 1ra fetch: count de venta existe → ['count' => 1]
        // 2da fetch: verificar estatus BORRADOR → ['estatus' => 'BORRADOR']
        // Resto: registrarMovimientosDevolucion → fetch detalles (fetchAll), tipo_movimiento, stock
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(
                ['count' => 1],
                ['estatus' => 'BORRADOR'],
                ['idtipomovimiento' => 1],   // tipo_movimiento para devolución
                ['stock' => 50, 'nombre' => 'Prod Test']  // stock producto
            );

        $detallesSimulados = [
            ['idproducto' => 1, 'cantidad' => 3, 'precio_unitario_venta' => 20.00],
        ];
        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($detallesSimulados);

        $result = $this->model->eliminarVenta(1);

        $this->assertIsArray($result);
        $this->assertTrue($result['success'], 'Esperaba success=true. Mensaje: ' . ($result['message'] ?? ''));
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsStringIgnoringCase('desactivada', $result['message']);
    }

    // -------------------------------------------------------------------------
    // Tests eliminarVenta — venta no existe
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testEliminarVenta_Inexistente_RetornaSuccessFalse(int $id): void
    {
        // count = 0 → venta no existe
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['count' => 0]);

        $result = $this->model->eliminarVenta($id);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    // -------------------------------------------------------------------------
    // Tests eliminarVenta — estado no permite eliminación
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerEstadosNoEliminables')]
    public function testEliminarVenta_EstadoNoEliminable_RetornaSuccessFalse(string $estado): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(
                ['count' => 1],
                ['estatus' => $estado]
            );

        $result = $this->model->eliminarVenta(1);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    // -------------------------------------------------------------------------
    // Tests eliminarVenta — excepción en BD
    // -------------------------------------------------------------------------

    #[Test]
    public function testEliminarVenta_ExcepcionEnBD_RetornaSuccessFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \PDOException('Disk full'));

        $result = $this->model->eliminarVenta(1);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    // -------------------------------------------------------------------------
    // Tests cambiarEstadoVenta
    // -------------------------------------------------------------------------

    #[Test]
    public function testCambiarEstadoVenta_TransicionValida_RetornaStatusTrue(): void
    {
        // Venta existe en estado BORRADOR → puede pasar a POR_PAGAR
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['estatus' => 'BORRADOR']);

        $result = $this->model->cambiarEstadoVenta(1, 'POR_PAGAR');

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
        $this->assertStringContainsStringIgnoringCase('actualizado', $result['message']);
    }

    #[Test]
    public function testCambiarEstadoVenta_TransicionInvalida_RetornaStatusFalse(): void
    {
        // Venta en PAGADA no puede cambiar a BORRADOR
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['estatus' => 'PAGADA']);

        $result = $this->model->cambiarEstadoVenta(1, 'BORRADOR');

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
    }

    #[Test]
    public function testCambiarEstadoVenta_EstadoInvalido_RetornaStatusFalse(): void
    {
        $result = $this->model->cambiarEstadoVenta(1, 'ESTADO_INVENTADO');

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('válido', $result['message']);
    }

    #[Test]
    public function testCambiarEstadoVenta_VentaNoExiste_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(false);

        $result = $this->model->cambiarEstadoVenta(99999, 'POR_PAGAR');

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('encontrada', $result['message']);
    }
}
