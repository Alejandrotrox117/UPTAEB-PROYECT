<?php

namespace Tests\UnitTest\Productos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\ProductosModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class insertarProductoUnitTest extends TestCase
{
    private ProductosModel $model;
    /** @var \Mockery\MockInterface */
    private $mockPdo;
    /** @var \Mockery\MockInterface */
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');

        $this->mockPdo  = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        // Defaults
        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('0')->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new ProductosModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // -------------------------------------------------------------------------
    // DataProviders
    // -------------------------------------------------------------------------

    public static function providerInsertExitoso(): array
    {
        return [
            'producto_kg' => [[
                'nombre'       => 'Cartón Corrugado',
                'descripcion'  => 'Material de reciclaje',
                'unidad_medida'=> 'KG',
                'precio'       => 0.15,
                'idcategoria'  => 1,
                'moneda'       => 'USD',
            ]],
            'producto_lt' => [[
                'nombre'       => 'Aceite Mineral',
                'descripcion'  => 'Aceite reciclado',
                'unidad_medida'=> 'LT',
                'precio'       => 2.50,
                'idcategoria'  => 2,
                'moneda'       => 'BS',
            ]],
        ];
    }

    public static function providerInsertDuplicado(): array
    {
        return [
            'nombre_repetido' => [[
                'nombre'       => 'Producto Ya Existente',
                'descripcion'  => 'Duplicado',
                'unidad_medida'=> 'KG',
                'precio'       => 10.00,
                'idcategoria'  => 1,
                'moneda'       => 'USD',
            ]],
        ];
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerInsertExitoso')]
    public function testInsertProducto_Exitoso_RetornaStatusTrueConId(array $data): void
    {
        // Verificación: el producto NO existe (total = 0)
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        // Inserción: lastInsertId = 42
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('42');

        $result = $this->model->insertProducto($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('producto_id', $result);
        $this->assertTrue($result['status'], 'Debería insertar correctamente: ' . ($result['message'] ?? ''));
        $this->assertEquals(42, $result['producto_id']);
        $this->assertStringContainsStringIgnoringCase('exitosamente', $result['message']);
    }

    #[Test]
    #[DataProvider('providerInsertDuplicado')]
    public function testInsertProducto_NombreDuplicado_RetornaStatusFalse(array $data): void
    {
        // Verificación: el producto YA existe (total = 1)
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 1]);

        $result = $this->model->insertProducto($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsStringIgnoringCase('existe', $result['message']);
        $this->assertNull($result['producto_id']);
    }

    #[Test]
    public function testInsertProducto_LastInsertIdCero_RetornaError(): void
    {
        // Producto no existe en verificación
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        // Insert no genera ID (fallo silencioso)
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('0');

        $data = [
            'nombre'       => 'Producto Test',
            'descripcion'  => 'Sin ID generado',
            'unidad_medida'=> 'KG',
            'precio'       => 5.00,
            'idcategoria'  => 1,
            'moneda'       => 'USD',
        ];

        $result = $this->model->insertProducto($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
    }

    #[Test]
    public function testInsertProducto_ExcepcionEnBD_RetornaStatusFalse(): void
    {
        // Verificación lanza excepción (DB caída)
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \PDOException('Connection refused'));

        $data = [
            'nombre'       => 'Producto Excepcion',
            'descripcion'  => 'Test excepción',
            'unidad_medida'=> 'KG',
            'precio'       => 1.00,
            'idcategoria'  => 1,
            'moneda'       => 'USD',
        ];

        $result = $this->model->insertProducto($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
    }
}
