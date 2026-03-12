# Documentación de Pruebas Unitarias

## Cuadro Nº 1: Módulo de Categorías (RF-CAT)

### Objetivos de la prueba

Validar que las operaciones CRUD del módulo de Categorías (consultar, insertar, actualizar, eliminar y reactivar) se ejecuten correctamente bajo datos válidos, y que el sistema maneje adecuadamente los escenarios de fallo de base de datos o resultados vacíos, garantizando respuestas predecibles en todos los casos.

### Técnicas

Pruebas de caja blanca con Mockery para aislar la capa de base de datos (PDO/PDOStatement). Se evalúan los métodos `SelectAllCategorias()`, `insertCategoria()`, `deleteCategoria()`, `updateCategoria()`, `getCategoriaById()` y `reactivarCategoria()` del modelo `CategoriasModel`, verificando la normalización de campos (ej. `estatus` a mayúsculas), el manejo de excepciones PDO y la correcta propagación de valores booleanos de retorno.

### Código Involucrado

```php
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
```

### Caso de prueba

**TIPO:** Funcional (Caja blanca)

**OBJETIVO:** Verificar que el modelo `CategoriasModel` ejecute correctamente las operaciones CRUD sobre la tabla `categoria`, con normalización de `estatus` y manejo de fallos de base de datos.

**DESCRIPCIÓN:** Se prueban 6 operaciones principales con escenarios válidos (datos existentes, IDs válidos, variantes de estatus), inválidos (fallo de `execute`, excepciones PDO) y de borde (IDs muy grandes, caracteres especiales, tabla vacía).

**ENTRADAS:**
- `SelectAllCategorias`: tabla con 2 registros, tabla vacía, excepción PDO.
- `insertCategoria`: estatus en minúsculas/mayúsculas/mixto (`activo`, `ACTIVO`, `Activo`), estatus `inactivo`, caracteres especiales (`Categoría Ñ & Ü`), fallo de `execute`, excepción PDO.
- `deleteCategoria`: IDs `5` y `99999` (soft-delete a `INACTIVO`), excepción PDO.
- `updateCategoria`: datos completos con estatus `ACTIVO` e `INACTIVO`, fallo de `execute`, excepción PDO.
- `getCategoriaById`: ID `3` existente, ID `99999` inexistente, excepción PDO.
- `reactivarCategoria`: IDs `4` y `99999` (actualización a `ACTIVO`), excepción PDO.

**SALIDAS ESPERADAS:**

| Escenario | Resultado esperado |
|---|---|
| `SelectAllCategorias` con datos | Array con 2 elementos, `nombre` = `'Cartón'` |
| `SelectAllCategorias` sin datos / excepción | Array vacío `[]` |
| `insertCategoria` con estatus variado | `true`, `estatus` normalizado a mayúsculas |
| `insertCategoria` con fallo BD | `false` |
| `deleteCategoria` con ID válido | `true` (estatus → `INACTIVO`) |
| `updateCategoria` con datos válidos | `true` |
| `getCategoriaById` con ID existente | Array con `idcategoria = 3`, `nombre = 'Vidrio'` |
| `getCategoriaById` con ID inexistente / excepción | `false` / `null` respectivamente |

### Resultado

```
PHPUnit 10.5.63 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: C:\xampp\htdocs\project\phpunit.xml

.......................                                           23 / 23 (100%)

Time: 00:04.139, Memory: 10.00 MB

Categorias Unit (Tests\UnitTest\Categorias\CategoriasUnit)
 ✔ SelectAllCategorias CuandoExistenRegistros RetornaArray
 ✔ SelectAllCategorias CuandoNoHayDatos RetornaArrayVacio
 ✔ SelectAllCategorias CuandoFallaConsulta RetornaArrayVacio
 ✔ InsertCategoria ConDatosValidos RetornaTrue with estatus_minusculas
 ✔ InsertCategoria ConDatosValidos RetornaTrue with estatus_mayusculas
 ✔ InsertCategoria ConDatosValidos RetornaTrue with estatus_mixto
 ✔ InsertCategoria ConDatosValidos RetornaTrue with estatus_inactivo
 ✔ InsertCategoria ConDatosValidos RetornaTrue with caracteres_especiales
 ✔ InsertCategoria CuandoExecuteFalla RetornaFalse
 ✔ InsertCategoria CuandoFallaBD RetornaFalse
 ✔ DeleteCategoria CuandoEjecutaOk RetornaTrue with id_existente
 ✔ DeleteCategoria CuandoEjecutaOk RetornaTrue with id_grande
 ✔ DeleteCategoria CuandoFallaBD RetornaFalse
 ✔ UpdateCategoria ConDatosValidos RetornaTrue with datos_completos
 ✔ UpdateCategoria ConDatosValidos RetornaTrue with estatus_inactivo
 ✔ UpdateCategoria CuandoExecuteFalla RetornaFalse
 ✔ UpdateCategoria CuandoFallaBD RetornaFalse
 ✔ GetCategoriaById CuandoExiste RetornaArray
 ✔ GetCategoriaById CuandoNoExiste RetornaFalse
 ✔ GetCategoriaById CuandoFallaBD RetornaNull
 ✔ ReactivarCategoria CuandoEjecutaOk RetornaTrue with id_existente
 ✔ ReactivarCategoria CuandoEjecutaOk RetornaTrue with id_grande
 ✔ ReactivarCategoria CuandoFallaBD RetornaFalse

OK (23 tests, 29 assertions)
```

### Observaciones

Se ejecutaron 23 pruebas con 29 aserciones en 4.139 segundos con un consumo de 10 MB de memoria, todas pasando sin errores. El módulo cubre las 6 operaciones CRUD del modelo incluyendo el soft-delete y la reactivación, con especial atención a la normalización automática del campo `estatus` a mayúsculas independientemente del formato de entrada.
