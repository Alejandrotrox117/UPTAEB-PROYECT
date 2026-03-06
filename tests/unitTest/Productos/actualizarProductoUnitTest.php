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
class actualizarProductoUnitTest extends TestCase
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

        // Defaults conservadores
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

    public static function providerUpdateExitoso(): array
    {
        return [
            'cambio_nombre_y_precio' => [
                5,
                ['nombre' => 'Cartón Premium', 'descripcion' => 'Actualizado', 'unidad_medida' => 'KG', 'precio' => 0.20, 'idcategoria' => 1, 'moneda' => 'USD'],
            ],
            'cambio_moneda' => [
                10,
                ['nombre' => 'Aceite Industrial', 'descripcion' => 'Nuevo desc', 'unidad_medida' => 'LT', 'precio' => 3.00, 'idcategoria' => 2, 'moneda' => 'BS'],
            ],
        ];
    }

    public static function providerUpdateConflictoNombre(): array
    {
        return [
            'nombre_ya_usado' => [
                7,
                ['nombre' => 'Nombre Duplicado', 'descripcion' => 'X', 'unidad_medida' => 'KG', 'precio' => 1, 'idcategoria' => 1, 'moneda' => 'USD'],
                'existe',
            ],
        ];
    }

    public static function providerUpdateIdInexistente(): array
    {
        return [
            'id_grande' => [
                999999,
                ['nombre' => 'Producto Ghost', 'descripcion' => 'No existe', 'unidad_medida' => 'KG', 'precio' => 1, 'idcategoria' => 1, 'moneda' => 'USD'],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerUpdateExitoso')]
    public function testUpdateProducto_Exitoso_RetornaStatusTrue(int $id, array $data): void
    {
        // Verificación: nombre NO está en uso por otro producto
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        // UPDATE afecta 1 fila
        $this->mockStmt->shouldReceive('rowCount')
            ->andReturn(1);

        $result = $this->model->updateProducto($id, $data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertTrue($result['status'], 'Update debería ser exitoso: ' . ($result['message'] ?? ''));
        $this->assertStringContainsStringIgnoringCase('actualizado', $result['message']);
    }

    #[Test]
    #[DataProvider('providerUpdateConflictoNombre')]
    public function testUpdateProducto_NombreConflicto_RetornaStatusFalse(int $id, array $data, string $mensajeContiene): void
    {
        // Verificación: el nombre YA está en uso por otro producto
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 1]);

        $result = $this->model->updateProducto($id, $data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsStringIgnoringCase($mensajeContiene, $result['message']);
    }

    #[Test]
    #[DataProvider('providerUpdateIdInexistente')]
    public function testUpdateProducto_IdInexistente_RetornaStatusFalse(int $id, array $data): void
    {
        // Verificación OK (nombre no duplicado)
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        // UPDATE no afecta ninguna fila (producto no existe en DB)
        $this->mockStmt->shouldReceive('rowCount')
            ->andReturn(0);

        $result = $this->model->updateProducto($id, $data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
    }

    #[Test]
    public function testUpdateProducto_ExcepcionEnBD_RetornaStatusFalse(): void
    {
        // Verificación lanza excepción
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \PDOException('Fallo de conexión'));

        $data = [
            'nombre'       => 'Producto Error',
            'descripcion'  => 'Test excepción',
            'unidad_medida'=> 'KG',
            'precio'       => 5.00,
            'idcategoria'  => 1,
            'moneda'       => 'USD',
        ];

        $result = $this->model->updateProducto(1, $data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
    }

    #[Test]
    public function testUpdateProducto_NombreVacio_VerificacionPasaInsertFalla(): void
    {
        // Nombre vacío: no hay producto con nombre vacío (verificación retorna false)
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0]);

        // Pero UPDATE no afecta filas (nombre vacío podría no coincidir con ningún registro activo)
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0);

        $data = [
            'nombre'       => '',
            'descripcion'  => 'Sin nombre',
            'unidad_medida'=> 'KG',
            'precio'       => 10.00,
            'idcategoria'  => 1,
            'moneda'       => 'USD',
        ];

        $result = $this->model->updateProducto(1, $data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        // rowCount = 0 → status false
        $this->assertFalse($result['status']);
    }
}
