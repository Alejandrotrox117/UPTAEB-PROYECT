<?php

namespace Tests\UnitTest\Categorias;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\CategoriasModel;
use Mockery;
use PDO;
use PDOStatement;
use PDOException;

#[RunTestsInSeparateProcesses]
class CategoriasUnitTest extends TestCase
{
    private CategoriasModel $model;
    /** @var \Mockery\MockInterface */
    private $mockPdo;
    /** @var \Mockery\MockInterface */
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');

        $this->mockPdo  = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        // Comportamientos por defecto
        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('query')->andReturn($this->mockStmt)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new CategoriasModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // ================================================================
    // SelectAllCategorias
    // ================================================================

    #[Test]
    public function testSelectAllCategorias_CuandoExistenRegistros_RetornaArray(): void
    {
        $esperado = [
            ['idcategoria' => 1, 'nombre' => 'Cartón',   'descripcion' => 'Reciclable', 'estatus' => 'ACTIVO'],
            ['idcategoria' => 2, 'nombre' => 'Plástico',  'descripcion' => 'PET',        'estatus' => 'ACTIVO'],
        ];

        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($esperado);

        $this->mockPdo->shouldReceive('query')
            ->with(Mockery::on(fn($sql) => str_contains($sql, 'SELECT * FROM categoria')))
            ->andReturn($this->mockStmt);

        $result = $this->model->SelectAllCategorias();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Cartón', $result[0]['nombre']);
    }

    #[Test]
    public function testSelectAllCategorias_CuandoNoHayDatos_RetornaArrayVacio(): void
    {
        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn([]);

        $this->mockPdo->shouldReceive('query')->andReturn($this->mockStmt);

        $result = $this->model->SelectAllCategorias();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function testSelectAllCategorias_CuandoFallaConsulta_RetornaArrayVacio(): void
    {
        $this->mockPdo->shouldReceive('query')
            ->andThrow(new PDOException('Connection lost'));

        $result = $this->model->SelectAllCategorias();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ================================================================
    // insertCategoria
    // ================================================================

    public static function providerInsertCategoriaValidos(): array
    {
        return [
            'estatus_minusculas'     => [
                ['nombre' => 'Cartón',   'descripcion' => 'Material reciclable',    'estatus' => 'activo'],
                'ACTIVO',
            ],
            'estatus_mayusculas'     => [
                ['nombre' => 'Plástico', 'descripcion' => 'PET reciclado',           'estatus' => 'ACTIVO'],
                'ACTIVO',
            ],
            'estatus_mixto'          => [
                ['nombre' => 'Metal',    'descripcion' => 'Aluminio',               'estatus' => 'Activo'],
                'ACTIVO',
            ],
            'estatus_inactivo'       => [
                ['nombre' => 'Madera',   'descripcion' => 'Material orgánico',      'estatus' => 'inactivo'],
                'INACTIVO',
            ],
            'caracteres_especiales'  => [
                ['nombre' => 'Categoría Ñ & Ü', 'descripcion' => 'Desc especial', 'estatus' => 'activo'],
                'ACTIVO',
            ],
        ];
    }

    #[Test]
    #[DataProvider('providerInsertCategoriaValidos')]
    public function testInsertCategoria_ConDatosValidos_RetornaTrue(array $data, string $estatusEsperado): void
    {
        $this->mockStmt->shouldReceive('execute')
            ->with(Mockery::on(function ($params) use ($data, $estatusEsperado) {
                return $params[0] === $data['nombre']
                    && $params[1] === $data['descripcion']
                    && $params[2] === $estatusEsperado;
            }))
            ->andReturn(true);

        $this->mockPdo->shouldReceive('prepare')
            ->with(Mockery::on(fn($sql) => str_contains($sql, 'INSERT INTO categoria')))
            ->andReturn($this->mockStmt);

        $result = $this->model->insertCategoria($data);

        $this->assertTrue($result);
    }

    #[Test]
    public function testInsertCategoria_CuandoExecuteFalla_RetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andReturn(false);
        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt);

        $data = ['nombre' => 'Test', 'descripcion' => 'Desc', 'estatus' => 'activo'];
        $result = $this->model->insertCategoria($data);

        $this->assertFalse($result);
    }

    #[Test]
    public function testInsertCategoria_CuandoFallaBD_RetornaFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new PDOException('Duplicate entry'));

        $data = ['nombre' => 'Duplicado', 'descripcion' => 'Desc', 'estatus' => 'activo'];
        $result = $this->model->insertCategoria($data);

        $this->assertFalse($result);
    }

    // ================================================================
    // deleteCategoria (soft-delete → estatus = INACTIVO)
    // ================================================================

    public static function providerDeleteCategoria(): array
    {
        return [
            'id_existente'  => [5,     true],
            'id_grande'     => [99999, true],
        ];
    }

    #[Test]
    #[DataProvider('providerDeleteCategoria')]
    public function testDeleteCategoria_CuandoEjecutaOk_RetornaTrue(int $id, bool $esperado): void
    {
        $this->mockStmt->shouldReceive('execute')
            ->with([$id])
            ->andReturn($esperado);

        $this->mockPdo->shouldReceive('prepare')
            ->with(Mockery::on(fn($sql) => str_contains($sql, "SET estatus = 'INACTIVO'")))
            ->andReturn($this->mockStmt);

        $result = $this->model->deleteCategoria($id);

        $this->assertSame($esperado, $result);
    }

    #[Test]
    public function testDeleteCategoria_CuandoFallaBD_RetornaFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new PDOException('DB error'));

        $result = $this->model->deleteCategoria(1);

        $this->assertFalse($result);
    }

    // ================================================================
    // updateCategoria
    // ================================================================

    public static function providerUpdateCategoriaValidos(): array
    {
        return [
            'datos_completos' => [
                ['idcategoria' => 1, 'nombre' => 'Cartón Actualizado', 'descripcion' => 'Nueva desc', 'estatus' => 'ACTIVO'],
            ],
            'estatus_inactivo' => [
                ['idcategoria' => 2, 'nombre' => 'Metal',              'descripcion' => 'Aluminio',   'estatus' => 'INACTIVO'],
            ],
        ];
    }

    #[Test]
    #[DataProvider('providerUpdateCategoriaValidos')]
    public function testUpdateCategoria_ConDatosValidos_RetornaTrue(array $data): void
    {
        $this->mockStmt->shouldReceive('execute')
            ->with([$data['nombre'], $data['descripcion'], $data['estatus'], $data['idcategoria']])
            ->andReturn(true);

        $this->mockPdo->shouldReceive('prepare')
            ->with(Mockery::on(fn($sql) => str_contains($sql, 'UPDATE categoria SET')))
            ->andReturn($this->mockStmt);

        $result = $this->model->updateCategoria($data);

        $this->assertTrue($result);
    }

    #[Test]
    public function testUpdateCategoria_CuandoExecuteFalla_RetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andReturn(false);
        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt);

        $data = ['idcategoria' => 1, 'nombre' => 'X', 'descripcion' => 'Y', 'estatus' => 'ACTIVO'];
        $result = $this->model->updateCategoria($data);

        $this->assertFalse($result);
    }

    #[Test]
    public function testUpdateCategoria_CuandoFallaBD_RetornaFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new PDOException('Lock timeout'));

        $data = ['idcategoria' => 1, 'nombre' => 'X', 'descripcion' => 'Y', 'estatus' => 'ACTIVO'];
        $result = $this->model->updateCategoria($data);

        $this->assertFalse($result);
    }

    // ================================================================
    // getCategoriaById
    // ================================================================

    #[Test]
    public function testGetCategoriaById_CuandoExiste_RetornaArray(): void
    {
        $fila = ['idcategoria' => 3, 'nombre' => 'Vidrio', 'descripcion' => 'Transparente', 'estatus' => 'ACTIVO'];

        $this->mockStmt->shouldReceive('execute')->with([3])->andReturn(true);
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn($fila);

        $this->mockPdo->shouldReceive('prepare')
            ->with(Mockery::on(fn($sql) => str_contains($sql, 'WHERE idcategoria = ?')))
            ->andReturn($this->mockStmt);

        $result = $this->model->getCategoriaById(3);

        $this->assertIsArray($result);
        $this->assertEquals(3, $result['idcategoria']);
        $this->assertEquals('Vidrio', $result['nombre']);
    }

    #[Test]
    public function testGetCategoriaById_CuandoNoExiste_RetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andReturn(true);
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt);

        $result = $this->model->getCategoriaById(99999);

        $this->assertFalse($result);
    }

    #[Test]
    public function testGetCategoriaById_CuandoFallaBD_RetornaNull(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new PDOException('Connection error'));

        $result = $this->model->getCategoriaById(1);

        $this->assertNull($result);
    }

    // ================================================================
    // reactivarCategoria
    // ================================================================

    public static function providerReactivarCategoria(): array
    {
        return [
            'id_existente' => [4, true],
            'id_grande'    => [99999, true],
        ];
    }

    #[Test]
    #[DataProvider('providerReactivarCategoria')]
    public function testReactivarCategoria_CuandoEjecutaOk_RetornaTrue(int $id, bool $esperado): void
    {
        $this->mockStmt->shouldReceive('execute')
            ->with([$id])
            ->andReturn($esperado);

        $this->mockPdo->shouldReceive('prepare')
            ->with(Mockery::on(fn($sql) => str_contains($sql, "SET estatus = 'ACTIVO'")))
            ->andReturn($this->mockStmt);

        $result = $this->model->reactivarCategoria($id);

        $this->assertSame($esperado, $result);
    }

    #[Test]
    public function testReactivarCategoria_CuandoFallaBD_RetornaFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new PDOException('DB error'));

        $result = $this->model->reactivarCategoria(1);

        $this->assertFalse($result);
    }
}
