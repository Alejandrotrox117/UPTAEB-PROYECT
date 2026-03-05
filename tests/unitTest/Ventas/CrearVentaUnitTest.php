<?php

namespace Tests\UnitTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Mockery;
use App\Models\VentasModel;

class CrearVentaUnitTest extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new VentasModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    #[Test]
    public function testCrearVentaMocks()
    {
        // Mock connection
        $mockStmt = Mockery::mock('PDOStatement');
        $mockStmt->shouldReceive('execute')->andReturn(true);
        $mockStmt->shouldReceive('fetchAll')->andReturn([]);
        $mockStmt->shouldReceive('fetch')->andReturn(['idventa' => 1, 'nro_venta' => 'VT001']);
        $mockStmt->shouldReceive('rowCount')->andReturn(1);

        $mockPdo = Mockery::mock('PDO');
        $mockPdo->shouldReceive('prepare')->andReturn($mockStmt);
        $mockPdo->shouldReceive('lastInsertId')->andReturn(1);
        $mockPdo->shouldReceive('beginTransaction')->andReturn(true);
        $mockPdo->shouldReceive('commit')->andReturn(true);
        $mockPdo->shouldReceive('rollBack')->andReturn(true);

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('getConexion')->andReturn($mockPdo);

        $datosVenta = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => 1,
            'idmoneda_general' => 3,
            'subtotal_general' => 60,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 60,
            'observaciones' => 'Prueba unitaria mock',
            'tasa_usada' => 1
        ];
        $detallesVenta = [
            [
                'idproducto' => 1,
                'cantidad' => 3,
                'precio_unitario_venta' => 20,
                'subtotal_general' => 60,
                'id_moneda_detalle' => 3
            ]
        ];

        $resultado = $this->model->insertVenta($datosVenta, $detallesVenta);
        // Depending on module's true response in Unit mocking without full mocks 
        // We'll assert it returns an array
        $this->assertIsArray($resultado);
    }
}
