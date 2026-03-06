## Cuadro Nº 1: Módulo de Productos (RF001)

### Objetivos de la prueba

Validar que las operaciones de gestión de productos (inserción, actualización, consulta y eliminación) se ejecuten correctamente bajo escenarios válidos y manejen adecuadamente las situaciones de error. El sistema debe garantizar la integridad de los datos, prevenir duplicación de nombres, manejar correctamente las transacciones de base de datos y realizar eliminaciones lógicas (soft-delete) en lugar de físicas.

### Técnicas

Pruebas de caja blanca con enfoque en aislamiento y validación de lógica de negocio mediante mocks de PDO. Se evalúan los métodos `insertProducto()`, `updateProducto()`, `deleteProductoById()`, `selectAllProductos()`, `selectProductoById()`, `selectProductosActivos()` y `selectCategoriasActivas()` del modelo ProductosModel en múltiples escenarios, incluyendo casos exitosos, duplicación de nombres, registros inexistentes, excepciones de base de datos y validación de soft-delete. Se utilizan DataProviders para probar diferentes combinaciones de datos y estados.

### Código Involucrado

```php
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
```

### Caso de prueba

**TIPO:** Funcional (Caja blanca)

**OBJETIVO:** Validar la integridad y correcto funcionamiento de las operaciones CRUD sobre el módulo de Productos, asegurando que el sistema maneje apropiadamente tanto escenarios exitosos como situaciones de error.

**DESCRIPCIÓN:** Se prueban cuatro conjuntos principales de operaciones. En primer lugar, la inserción de productos donde se verifica que el sistema pueda crear nuevos productos con datos válidos en diferentes unidades de medida como kilogramos y litros, utilizando distintas monedas como USD y BS. También se valida que el sistema rechace productos con nombres duplicados y maneje correctamente casos donde la base de datos no genera un ID tras la inserción o cuando ocurren excepciones durante el proceso. En segundo lugar, se prueba la actualización de productos existentes, verificando que se puedan modificar campos como nombre, precio, descripción, unidad de medida y moneda de manera exitosa. Se valida que el sistema impida actualizar un producto con un nombre que ya está siendo usado por otro producto activo, que retorne error al intentar actualizar productos con IDs inexistentes y que maneje apropiadamente las excepciones de base de datos. En tercer lugar, se evalúan las operaciones de consulta, incluyendo la obtención de todos los productos, la búsqueda por ID específico, el filtrado de productos activos y la consulta de categorías activas. Se prueba tanto el comportamiento con datos disponibles como con conjuntos vacíos, y se verifica el manejo de IDs inexistentes y excepciones de conexión. Finalmente, se prueba la eliminación lógica de productos, confirmando que los productos existentes puedan ser marcados como inactivos mediante un soft-delete en lugar de ser eliminados físicamente de la base de datos, que el sistema retorne falso al intentar eliminar productos inexistentes y que las excepciones sean manejadas correctamente.

**ENTRADAS:**

- Inserción: “Cartón Corrugado” (kg, $0.15 USD, cat. 1), “Aceite Mineral” (litros, $2.50 BS, cat. 2), nombre duplicado, `lastInsertId` = 0, excepción BD.
- Actualización: ID 5 → “Cartón Premium” $0.20; conflicto de nombre (ID 7 + “Nombre Duplicado”); ID inexistente (999999); excepción BD.
- Consultas: dos productos activos (“Cartón”, “Plástico”), por ID existente/inexistente (999999, 0), conjunto vacío, excepción BD.
- Eliminación: IDs existentes (1 – “Cartón Corrugado”, 50 – “Plástico Duro”), inexistentes (999999, 888888), verificación de soft-delete.

**SALIDAS ESPERADAS:**

- Inserción válida → `status true` + mensaje + ID generado.
- Nombre duplicado / `lastInsertId` = 0 / excepción → `status false` + mensaje.
- Actualización exitosa → `status true`; conflicto de nombre / ID inexistente / excepción → `status false`.
- Consulta con datos → `status true` + `data` completo (id, nombre, precio, unidad, existencia, categoría, moneda, estatus).
- ID inexistente → `false`; sin registros → `status true` + `data []`; excepción → `status false`.
- Eliminación de ID existente → `true` (UPDATE estatus → INACTIVO, **no** DELETE físico).
- Eliminación de ID inexistente / excepción → `false`.

### Resultado

```
PS C:\xampp\htdocs\project> php vendor/bin/phpunit tests/unitTest/Productos/
PHPUnit 10.5.63 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: C:\xampp\htdocs\project\phpunit.xml

.............................                                     29 / 29 (100%)

Time: 00:20.691, Memory: 10.00 MB

There was 1 PHPUnit test runner warning:

1) No code coverage driver available

OK, but there were issues!
Tests: 29, Assertions: 105, PHPUnit Warnings: 1, PHPUnit Deprecations: 1.
```

### Observaciones

29 pruebas y 105 aserciones ejecutadas correctamente en ~20 s. Se confirmó que el módulo implementa soft-delete: ninguna consulta ejecutada contiene `DELETE FROM`. La validación de unicidad de nombres opera tanto en creación como en actualización.
