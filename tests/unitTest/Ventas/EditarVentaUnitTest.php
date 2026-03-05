<?php

namespace Tests\UnitTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Mockery;
use App\Models\VentasModel;

class EditarVentaUnitTest extends TestCase
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
    public function testEditarVentaMocks()
    {
        // Mock connection
        $mockStmt = Mockery::mock('PDOStatement');
        $mockStmt->shouldReceive('execute')->andReturn(true);
        $mockStmt->shouldReceive('fetchAll')->andReturn([]);
        $mockStmt->shouldReceive('fetch')->andReturn(['idventa' => 1]);
        $mockStmt->shouldReceive('rowCount')->andReturn(1);

        $mockPdo = Mockery::mock('PDO');
        $mockPdo->shouldReceive('prepare')->andReturn($mockStmt);
        $mockPdo->shouldReceive('beginTransaction')->andReturn(true);
        $mockPdo->shouldReceive('commit')->andReturn(true);
        $mockPdo->shouldReceive('rollBack')->andReturn(true);

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('getConexion')->andReturn($mockPdo);

        $datosVentaEditada = [
            'fecha_venta' => date('Y-m-d'),
            'idcliente' => 2,
            'idmoneda_general' => 3,
            'subtotal_general' => 125,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general' => 0,
            'estatus' => 'BORRADOR',
            'total_general' => 125,
            'observaciones' => 'Edición unitaria mock',
            'tasa_usada' => 1,
            'detalles' => [
                [
                    'idproducto' => 1,
                    'cantidad' => 5,
                    'precio_unitario_venta' => 25,
                    'subtotal_general' => 125,
                    'id_moneda_detalle' => 3
                ]
            ]
        ];

        $resultado = $this->model->updateVenta(1, $datosVentaEditada);
        $this->assertIsArray($resultado);
    }
}
