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
class crearCompraUnitTest extends TestCase
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

        // Comportamiento por defecto
        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('commit')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('rollBack')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn("100")->byDefault(); // ID simulado
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(true)->byDefault();

        // Overload the Conexion class
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

    public static function providerCasosInvalidosCrearCompra(): array
    {
        // datosCompra, detallesCompra, mensaje_esperado, stubs_config
        return [
            'Sin Detalles' => [
                ['idproveedor' => 1, 'nro_compra' => 'C-2026-00001', 'fecha_compra' => '2026-01-01', 'idmoneda_general' => 3, 'total_general_compra' => 100, 'observaciones_compra' => 'Test'],
                [], // sin detalles
                'No hay detalles de compra para procesar.',
                'ninguno'
            ],
            'Proveedor Inexistente' => [
                ['idproveedor' => 9999, 'nro_compra' => 'C-2026-00001', 'fecha_compra' => '2026-01-01', 'idmoneda_general' => 3, 'total_general_compra' => 100, 'observaciones_compra' => 'Test'],
                [['idproducto' => 1, 'descripcion_temporal_producto' => 'Prod', 'cantidad' => 1, 'precio_unitario_compra' => 10, 'idmoneda_detalle' => 3, 'subtotal_linea' => 10, 'peso_vehiculo' => null, 'peso_bruto' => null, 'peso_neto' => null]],
                'El proveedor con ID 9999 no existe.',
                'proveedor_falso'
            ],
            'Producto Inexistente' => [
                ['idproveedor' => 1, 'nro_compra' => 'C-2026-00001', 'fecha_compra' => '2026-01-01', 'idmoneda_general' => 3, 'total_general_compra' => 100, 'observaciones_compra' => 'Test'],
                [['idproducto' => 5555, 'descripcion_temporal_producto' => 'Prod', 'cantidad' => 1, 'precio_unitario_compra' => 10, 'idmoneda_detalle' => 3, 'subtotal_linea' => 10, 'peso_vehiculo' => null, 'peso_bruto' => null, 'peso_neto' => null]],
                'El producto con ID 5555 no existe.',
                'producto_falso' // Proveedor pasa, producto falla
            ],
            'Cantidad o Precio Negativo' => [
                ['idproveedor' => 1, 'nro_compra' => 'C-2026-00001', 'fecha_compra' => '2026-01-01', 'idmoneda_general' => 3, 'total_general_compra' => 0, 'observaciones_compra' => 'Test'],
                [['idproducto' => 1, 'descripcion_temporal_producto' => 'Prod', 'cantidad' => 0, 'precio_unitario_compra' => 10, 'idmoneda_detalle' => 3, 'subtotal_linea' => 0, 'peso_vehiculo' => null, 'peso_bruto' => null, 'peso_neto' => null]],
                'La cantidad o el precio unitario no pueden ser negativos o cero.',
                'todo_valido' // Proveedor pasa, producto pasa, falla en el condicional de cantidad
            ]
        ];
    }

    #[Test]
    #[DataProvider('providerCasosInvalidosCrearCompra')]
    public function testCrearCompra_CasosInvalidos_DevuelvenFalseYMensaje(array $datosCompra, array $detallesCompra, string $mensajeEsperado, string $stubsConfig): void
    {
        // Configuramos Mocks según el caso
        if ($stubsConfig === 'proveedor_falso') {
            // El proveedor devuelve false (simulate empty fetch for provider)
            $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);
        } elseif ($stubsConfig === 'producto_falso') {
            // Primera llamada a fetch (proveedor) devuelve un array, Segunda llamada (producto) devuelve false
            $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(['idproveedor' => 1, 'nombre' => 'TestProv'], false);
        } elseif ($stubsConfig === 'todo_valido') {
            // Ambos existen
            $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(['idproveedor' => 1, 'nombre' => 'TestProv'], ['idproducto' => 1, 'nombre' => 'TestProd']);
        }

        $resultado = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertEquals($mensajeEsperado, $resultado['message']);
    }

    #[Test]
    public function testCrearCompra_Exitosa(): void
    {
        // Mocks for successful scenario
        // 1. Proveedor
        // 2. Producto
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(
                ['idproveedor' => 1, 'nombre' => 'TestProv'],
                ['idproducto' => 1, 'nombre' => 'TestProd']
            );

        $datosCompra = [
            'idproveedor' => 1,
            'nro_compra' => 'C-2026-00001',
            'fecha_compra' => '2026-01-01',
            'idmoneda_general' => 3,
            'subtotal_general_compra' => 10,
            'descuento_porcentaje_compra' => 0,
            'monto_descuento_compra' => 0,
            'total_general_compra' => 10,
            'observaciones_compra' => 'Test Unit Exitoso'
        ];

        $detallesCompra = [
            [
                'idproducto' => 1,
                'descripcion_temporal_producto' => 'Prod',
                'cantidad' => 1,
                'descuento' => 0,
                'precio_unitario_compra' => 10,
                'idmoneda_detalle' => 3,
                'subtotal_linea' => 10,
                'peso_vehiculo' => null,
                'peso_bruto' => null,
                'peso_neto' => null
            ]
        ];

        // Se usa stub de execute en el setUp que retorna true, por tanto la inserción triunfa.

        $resultado = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status']);
        $this->assertEquals('Compra registrada exitosamente.', $resultado['message']);
        $this->assertEquals("100", $resultado['id']); // El lastInsertId simulado en setup
    }
}
