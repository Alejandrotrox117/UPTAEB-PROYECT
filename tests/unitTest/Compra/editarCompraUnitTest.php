<?php

namespace Tests\UnitTest\Compra;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\ComprasModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
class editarCompraUnitTest extends TestCase
{
    private ComprasModel $comprasModel;
    /** @var \Mockery\MockInterface */
    private $mockPdo;
    /** @var \Mockery\MockInterface */
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');

        $this->mockPdo = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();

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

        $this->comprasModel = new ComprasModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public static function providerCasosEditarCompraFalla(): array
    {
        return [
            'Proveedor Inexistente' => [
                'proveedor_falso',
                'proveedor'
            ],
            'Producto Inexistente' => [
                'producto_falso',
                'producto'
            ]
        ];
    }

    #[Test]
    #[DataProvider('providerCasosEditarCompraFalla')]
    public function testEditarCompra_CasosInvalidos_DevuelvenFalseYMensaje(string $stubsConfig, string $errorParcial): void
    {
        if ($stubsConfig === 'proveedor_falso') {
            $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);
        } elseif ($stubsConfig === 'producto_falso') {
            $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(['idproveedor' => 1, 'nombre' => 'TestProv'], false);
        }

        $datosCompra = [
            'nro_compra' => '123',
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => 888888,
            'idmoneda_general' => 3,
            'total_general_compra' => 50,
            'observaciones_compra' => 'Compra original para prueba de edición.'
        ];
        $detallesCompra = [
            [
                'idproducto' => 888888,
                'descripcion_temporal_producto' => 'Productor',
                'cantidad' => 5,
                'precio_unitario_compra' => 10,
                'idmoneda_detalle' => 3,
                'subtotal_linea' => 50
            ]
        ];

        $resultado = $this->comprasModel->actualizarCompra(1, $datosCompra, $detallesCompra);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertStringContainsString($errorParcial, strtolower($resultado['message']));
    }
}
