<?php

namespace Tests\UnitTest\Productos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\ProductosModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
class eliminarProductoUnitTest extends TestCase
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
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();

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

    public static function providerIdsExistentes(): array
    {
        return [
            'id_1' => [1,  ['nombre' => 'Cartón Corrugado']],
            'id_50'=> [50, ['nombre' => 'Plástico Duro']],
        ];
    }

    public static function providerIdsInexistentes(): array
    {
        return [
            'id_muy_grande' => [999999],
            'id_inexistente'=> [888888],
        ];
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerIdsExistentes')]
    public function testDeleteProducto_Existente_RetornaTrue(int $id, array $productoSimulado): void
    {
        // Primera consulta: SELECT nombre → devuelve el producto
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($productoSimulado);

        // Segunda consulta: UPDATE estatus = INACTIVO → afecta 1 fila
        $this->mockStmt->shouldReceive('rowCount')
            ->andReturn(1);

        $result = $this->model->deleteProductoById($id);

        $this->assertTrue($result, "deleteProductoById debería devolver true para ID=$id");
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testDeleteProducto_Inexistente_RetornaFalse(int $id): void
    {
        // SELECT nombre → producto no encontrado
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(false);

        // UPDATE no afecta ninguna fila
        $this->mockStmt->shouldReceive('rowCount')
            ->andReturn(0);

        $result = $this->model->deleteProductoById($id);

        $this->assertFalse($result, "deleteProductoById debería devolver false para ID inexistente=$id");
    }

    #[Test]
    public function testDeleteProducto_ExcepcionEnBD_RetornaFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \PDOException('Error grave en BD'));

        $result = $this->model->deleteProductoById(1);

        $this->assertFalse($result);
    }

    #[Test]
    public function testDeleteProducto_SoftDelete_NoEliminaFisicamente(): void
    {
        // El método realiza un UPDATE SET estatus = INACTIVO, no un DELETE.
        // Verificamos que mockPdo->prepare se llama con patrones de UPDATE (no DELETE).
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['nombre' => 'Producto Soft Delete']);

        $this->mockStmt->shouldReceive('rowCount')->andReturn(1);

        // Capturamos las queries ejecutadas
        $queriesCapturadas = [];
        $self = $this;
        $this->mockPdo->shouldReceive('prepare')
            ->with(Mockery::on(function ($sql) use (&$queriesCapturadas) {
                $queriesCapturadas[] = $sql;
                return true;
            }))
            ->andReturn($this->mockStmt);

        $result = $this->model->deleteProductoById(5);

        $this->assertTrue($result);

        // Ninguna de las queries debe ser un DELETE físico
        foreach ($queriesCapturadas as $sql) {
            $this->assertStringNotContainsStringIgnoringCase(
                'DELETE FROM producto',
                $sql,
                'El método debe ser un soft-delete (UPDATE), no un DELETE físico'
            );
        }
    }
}
