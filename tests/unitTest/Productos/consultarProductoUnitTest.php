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
class consultarProductoUnitTest extends TestCase
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

        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();

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

    public static function providerIdsInexistentes(): array
    {
        return [
            'id_grande'    => [999999],
            'id_cero'      => [0],
            'id_muy_grande'=> [PHP_INT_MAX],
        ];
    }

    public static function providerProductosSimulados(): array
    {
        return [
            'producto_usd' => [[
                'idproducto'   => 1,
                'nombre'       => 'Cartón Corrugado',
                'descripcion'  => 'Material de reciclaje',
                'unidad_medida'=> 'KG',
                'precio'       => 0.15,
                'existencia'   => 100,
                'stock_minimo' => 10,
                'idcategoria'  => 1,
                'moneda'       => 'USD',
                'estatus'      => 'ACTIVO',
                'categoria_nombre' => 'Cartón',
            ]],
            'producto_bs'  => [[
                'idproducto'   => 2,
                'nombre'       => 'Aceite Mineral',
                'descripcion'  => 'Aceite reciclado',
                'unidad_medida'=> 'LT',
                'precio'       => 2.50,
                'existencia'   => 50,
                'stock_minimo' => 5,
                'idcategoria'  => 2,
                'moneda'       => 'BS',
                'estatus'      => 'ACTIVO',
                'categoria_nombre' => 'Aceites',
            ]],
        ];
    }

    // -------------------------------------------------------------------------
    // Tests selectAllProductos
    // -------------------------------------------------------------------------

    #[Test]
    public function testSelectAllProductos_ConDatos_RetornaEstructuraCorrecta(): void
    {
        $datosSimulados = [
            ['idproducto' => 1, 'nombre' => 'Cartón', 'descripcion' => 'Test', 'precio' => 0.15, 'unidad_medida' => 'KG', 'estatus' => 'ACTIVO'],
            ['idproducto' => 2, 'nombre' => 'Plástico', 'descripcion' => 'Test2', 'precio' => 0.10, 'unidad_medida' => 'KG', 'estatus' => 'ACTIVO'],
        ];

        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($datosSimulados);

        $result = $this->model->selectAllProductos();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertTrue($result['status']);
        $this->assertCount(2, $result['data']);
    }

    #[Test]
    public function testSelectAllProductos_SinDatos_RetornaArrayVacio(): void
    {
        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn([]);

        $result = $this->model->selectAllProductos();

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
        $this->assertIsArray($result['data']);
        $this->assertEmpty($result['data']);
    }

    #[Test]
    public function testSelectAllProductos_ExcepcionEnBD_RetornaStatusFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \PDOException('DB error'));

        $result = $this->model->selectAllProductos();

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
        $this->assertIsArray($result['data']);
        $this->assertEmpty($result['data']);
    }

    // -------------------------------------------------------------------------
    // Tests selectProductoById
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerProductosSimulados')]
    public function testSelectProductoById_Existente_RetornaProducto(array $productoSimulado): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($productoSimulado);

        $result = $this->model->selectProductoById($productoSimulado['idproducto']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('idproducto', $result);
        $this->assertArrayHasKey('nombre', $result);
        $this->assertArrayHasKey('precio', $result);
        $this->assertArrayHasKey('estatus', $result);
        $this->assertEquals($productoSimulado['idproducto'], $result['idproducto']);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testSelectProductoById_Inexistente_RetornaFalse(int $id): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(false);

        $result = $this->model->selectProductoById($id);

        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // Tests selectProductosActivos
    // -------------------------------------------------------------------------

    #[Test]
    public function testSelectProductosActivos_RetornaSoloActivos(): void
    {
        $activos = [
            ['idproducto' => 1, 'nombre' => 'Cartón', 'estatus' => 'ACTIVO', 'precio' => 0.15],
            ['idproducto' => 3, 'nombre' => 'Vidrio', 'estatus' => 'ACTIVO', 'precio' => 0.05],
        ];

        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($activos);

        $result = $this->model->selectProductosActivos();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertTrue($result['status']);

        foreach ($result['data'] as $producto) {
            $this->assertEquals('ACTIVO', strtoupper($producto['estatus']));
        }
    }

    #[Test]
    public function testSelectProductosActivos_ExcepcionEnBD_RetornaStatusFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \PDOException('BD no disponible'));

        $result = $this->model->selectProductosActivos();

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertIsArray($result['data']);
    }

    // -------------------------------------------------------------------------
    // Tests selectCategoriasActivas
    // -------------------------------------------------------------------------

    #[Test]
    public function testSelectCategoriasActivas_RetornaEstructuraCorrecta(): void
    {
        $categorias = [
            ['idcategoria' => 1, 'nombre' => 'Cartón', 'descripcion' => 'Materiales de cartón', 'estatus' => 'ACTIVO'],
            ['idcategoria' => 2, 'nombre' => 'Plástico', 'descripcion' => 'Materiales plásticos', 'estatus' => 'ACTIVO'],
        ];

        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($categorias);

        $result = $this->model->selectCategoriasActivas();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertTrue($result['status']);
        $this->assertCount(2, $result['data']);

        foreach ($result['data'] as $cat) {
            $this->assertEquals('activo', strtolower($cat['estatus']));
        }
    }

    #[Test]
    public function testSelectCategoriasActivas_ExcepcionEnBD_RetornaStatusFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \PDOException('Error conectando'));

        $result = $this->model->selectCategoriasActivas();

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertIsArray($result['data']);
        $this->assertEmpty($result['data']);
    }
}
