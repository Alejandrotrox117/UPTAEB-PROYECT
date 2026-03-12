## Cuadro Nº 7: Módulo de Dashboard (RF07)

### Objetivos de la prueba

Validar que los métodos del `DashboardModel` retornen estructuras de datos correctas y bien definidas para los indicadores del panel principal. El sistema debe manejar excepciones de base de datos de forma controlada, retornando valores por defecto en lugar de propagar errores, y debe aceptar rangos de fechas válidos e inválidos sin lanzar excepciones.

### Técnicas

Pruebas de caja blanca con enfoque en aislamiento total de la base de datos mediante Mockery (overload de `App\Core\Conexion`). Se evalúan los métodos `getResumen()`, `getUltimasVentas()`, `getReporteCompras()`, `getEficienciaEmpleados()` y `getAnalisisInventario()` en escenarios válidos y de fallo, verificando la estructura de los arrays devueltos, la presencia de claves requeridas y el comportamiento ante excepciones de PDO.

### Código Involucrado

```php
<?php
declare(strict_types=1);

namespace Tests\UnitTest\Dashboard;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\DashboardModel;
use Mockery;
use PDO;
use PDOStatement;

/**
 * Pruebas Unitarias — DashboardModel
 *
 * Usa Mockery overload:App\Core\Conexion para interceptar toda creación de
 * Conexion y reemplazarla con mocks de PDO/PDOStatement.
 * No toca la base de datos real.
 *
 * Nota: DashboardModel usa patrón Lazy Load (getInstanciaModel) que crea
 * internamente una segunda instancia de DashboardModel — el overload de
 * Conexion captura ambas instancias correctamente.
 */
#[RunTestsInSeparateProcesses]
class DashboardUnitTest extends TestCase
{
    private DashboardModel $model;

    /** @var \Mockery\MockInterface */
    private $mockPdo;
    /** @var \Mockery\MockInterface */
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');

        $this->mockPdo  = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        // Defaults: todos los stmt devuelven un row genérico con las claves
        // necesarias para las consultas del DashboardModel.
        $resumenRow = [
            'ventas_hoy'           => 100.0,
            'ventas_ayer'          => 80.0,
            'compras_hoy'          => 50.0,
            'compras_ayer'         => 45.0,
            'valor_inventario'     => 10000.0,
            'producciones_activas' => 3,
            'productos_en_rotacion'=> 25,
            'eficiencia_promedio'  => 75.0,
            'fecha_consulta'       => date('Y-m-d'),
            // Claves para getAnalisisInventario sub-consultas
            'critico'  => 5,
            'normal'   => 20,
            'entradas' => 10,
            'salidas'  => 5,
        ];

        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn($resumenRow)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('0')->byDefault();

        // overload: intercepta todo new Conexion() del proceso actual
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new DashboardModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // ---------------------------------------------------------------
    // DataProviders
    // ---------------------------------------------------------------

    public static function providerRangosFechasValidos(): array
    {
        return [
            'año_actual'      => ['2026-01-01', '2026-12-31'],
            'mes_actual'      => ['2026-03-01', '2026-03-31'],
            'rango_amplio'    => ['2020-01-01', '2026-12-31'],
        ];
    }

    public static function providerRangosFechasInvalidos(): array
    {
        return [
            'fechas_invertidas' => ['2026-12-31', '2026-01-01'],
            'formato_invalido'  => ['no-es-fecha', 'tampoco'],
            'fecha_futura'      => ['2099-01-01', '2099-12-31'],
        ];
    }

    // ---------------------------------------------------------------
    // getResumen
    // ---------------------------------------------------------------

    #[Test]
    public function testGetResumen_RetornaEstructuraCorrecta(): void
    {
        $result = $this->model->getResumen();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('ventas_hoy',            $result);
        $this->assertArrayHasKey('ventas_ayer',           $result);
        $this->assertArrayHasKey('compras_hoy',           $result);
        $this->assertArrayHasKey('compras_ayer',          $result);
        $this->assertArrayHasKey('valor_inventario',      $result);
        $this->assertArrayHasKey('producciones_activas',  $result);
        $this->assertArrayHasKey('productos_en_rotacion', $result);
        $this->assertArrayHasKey('eficiencia_promedio',   $result);
        $this->assertArrayHasKey('fecha_consulta',        $result);
    }

    #[Test]
    public function testGetResumen_CuandoExcepcionEnBD_RetornaDatosPorDefecto(): void
    {
        $stmtFallo = Mockery::mock(PDOStatement::class);
        $stmtFallo->shouldReceive('execute')->andThrow(new \Exception('DB Error'));
        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtFallo);

        $result = $this->model->getResumen();

        $this->assertIsArray($result);
        // En caso de excepción el modelo retorna un array con claves y valores 0
        $this->assertArrayHasKey('ventas_hoy', $result);
        $this->assertEquals(0, $result['ventas_hoy']);
    }

    // ---------------------------------------------------------------
    // getUltimasVentas
    // ---------------------------------------------------------------

    #[Test]
    public function testGetUltimasVentas_RetornaArray(): void
    {
        $result = $this->model->getUltimasVentas();

        $this->assertIsArray($result);
    }

    #[Test]
    public function testGetUltimasVentas_ConDatos_RetornaEstructuraCorrecta(): void
    {
        $ventas = [[
            'idventa'       => 1,
            'nro_venta'     => 'V-0001',
            'fecha_venta'   => '2026-03-06',
            'cliente'       => 'Cliente Prueba',
            'total_general' => 150.0,
            'estado'        => 'PAGADA',
        ]];
        $stmtVentas = Mockery::mock(PDOStatement::class);
        $stmtVentas->shouldReceive('execute')->andReturn(true);
        $stmtVentas->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn($ventas);
        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtVentas);

        $result = $this->model->getUltimasVentas();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('idventa',       $result[0]);
        $this->assertArrayHasKey('nro_venta',     $result[0]);
        $this->assertArrayHasKey('fecha_venta',   $result[0]);
        $this->assertArrayHasKey('cliente',       $result[0]);
        $this->assertArrayHasKey('total_general', $result[0]);
        $this->assertArrayHasKey('estado',        $result[0]);
    }

    // ---------------------------------------------------------------
    // getReporteCompras
    // ---------------------------------------------------------------

    #[Test]
    #[DataProvider('providerRangosFechasValidos')]
    public function testGetReporteCompras_ConFechasValidas_RetornaArray(string $desde, string $hasta): void
    {
        $result = $this->model->getReporteCompras($desde, $hasta);

        $this->assertIsArray($result);
    }

    #[Test]
    #[DataProvider('providerRangosFechasInvalidos')]
    public function testGetReporteCompras_ConFechasInvalidas_RetornaArray(string $desde, string $hasta): void
    {
        $result = $this->model->getReporteCompras($desde, $hasta);

        $this->assertIsArray($result);
    }

    #[Test]
    public function testGetReporteCompras_CuandoExcepcion_RetornaArrayVacio(): void
    {
        $stmtFallo = Mockery::mock(PDOStatement::class);
        $stmtFallo->shouldReceive('execute')->andThrow(new \Exception('DB Error'));
        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtFallo);

        $result = $this->model->getReporteCompras('2026-01-01', '2026-12-31');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ---------------------------------------------------------------
    // getEficienciaEmpleados
    // ---------------------------------------------------------------

    #[Test]
    public function testGetEficienciaEmpleados_RetornaArray(): void
    {
        $result = $this->model->getEficienciaEmpleados('2026-01-01', '2026-12-31', null, null);

        $this->assertIsArray($result);
    }

    // ---------------------------------------------------------------
    // getAnalisisInventario
    // ---------------------------------------------------------------

    #[Test]
    public function testGetAnalisisInventario_RetornaEstructuraCorrecta(): void
    {
        $result = $this->model->getAnalisisInventario();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stock_critico',          $result);
        $this->assertArrayHasKey('valor_por_categoria',    $result);
        $this->assertArrayHasKey('movimientos_mes',        $result);
        $this->assertArrayHasKey('productos_mas_vendidos', $result);
    }

    #[Test]
    public function testGetAnalisisInventario_CuandoExcepcion_RetornaDatosPorDefecto(): void
    {
        $stmtFallo = Mockery::mock(PDOStatement::class);
        $stmtFallo->shouldReceive('execute')->andThrow(new \Exception('DB Error'));
        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtFallo);

        $result = $this->model->getAnalisisInventario();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stock_critico', $result);
        $this->assertEquals(0, $result['stock_critico']);
    }
}
```

### Caso de prueba

**TIPO:** Funcional (Caja blanca)

**OBJETIVO:** Verificar que el `DashboardModel` retorne estructuras correctas para todos los indicadores del panel principal y maneje errores de base de datos de forma robusta.

**DESCRIPCIÓN:** Se prueban los cinco métodos del modelo con mocks de PDO aislados de la base de datos real. Se valida integridad de claves en los arrays retornados, comportamiento ante excepciones, y tolerancia a rangos de fechas inválidos.

**ENTRADAS:**

- `getResumen()`: Sin parámetros; mock retorna fila con 9 indicadores clave
- `getResumen()` con excepción: PDOStatement lanza `\Exception('DB Error')`
- `getUltimasVentas()`: Sin parámetros; mock retorna lista vacía y luego lista con 1 venta (`id=1, 'V-0001', estado='PAGADA'`)
- `getReporteCompras($desde, $hasta)`: Fechas válidas (`2026-01-01/2026-12-31`, `2026-03-01/2026-03-31`, `2020-01-01/2026-12-31`) y fechas inválidas (`invertidas`, `no-es-fecha/tampoco`, `2099-01-01/2099-12-31`)
- `getAnalisisInventario()`: Sin parámetros; mock retorna fila con claves `critico`, `normal`, `entradas`, `salidas`

**SALIDAS ESPERADAS:**

| Escenario | Resultado esperado |
|---|---|
| `getResumen()` normal | Array con 9 claves: `ventas_hoy`, `ventas_ayer`, `compras_hoy`, `compras_ayer`, `valor_inventario`, `producciones_activas`, `productos_en_rotacion`, `eficiencia_promedio`, `fecha_consulta` |
| `getResumen()` con excepción BD | Array con `ventas_hoy = 0` y demás claves en 0 |
| `getUltimasVentas()` vacío | Array vacío |
| `getUltimasVentas()` con datos | Array con 1 elemento que contiene `idventa`, `nro_venta`, `fecha_venta`, `cliente`, `total_general`, `estado` |
| `getReporteCompras()` fechas válidas | Array (sin excepción) |
| `getReporteCompras()` fechas inválidas | Array (sin excepción) |
| `getReporteCompras()` con excepción BD | Array vacío |
| `getAnalisisInventario()` con excepción BD | Array con `stock_critico = 0` |

### Resultado

```
PHPUnit 10.5.63 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: C:\xampp\htdocs\project\phpunit.xml

..............                                                    14 / 14 (100%)

Time: 00:02.781, Memory: 10.00 MB

Dashboard Unit (Tests\UnitTest\Dashboard\DashboardUnit)
 ✔ GetResumen RetornaEstructuraCorrecta
 ✔ GetResumen CuandoExcepcionEnBD RetornaDatosPorDefecto
 ✔ GetUltimasVentas RetornaArray
 ✔ GetUltimasVentas ConDatos RetornaEstructuraCorrecta
 ✔ GetReporteCompras ConFechasValidas RetornaArray with año_actual
 ✔ GetReporteCompras ConFechasValidas RetornaArray with mes_actual
 ✔ GetReporteCompras ConFechasValidas RetornaArray with rango_amplio
 ✔ GetReporteCompras ConFechasInvalidas RetornaArray with fechas_invertidas
 ✔ GetReporteCompras ConFechasInvalidas RetornaArray with formato_invalido
 ✔ GetReporteCompras ConFechasInvalidas RetornaArray with fecha_futura
 ✔ GetReporteCompras CuandoExcepcion RetornaArrayVacio
 ✔ GetEficienciaEmpleados RetornaArray
 ✔ GetAnalisisInventario RetornaEstructuraCorrecta
 ✔ GetAnalisisInventario CuandoExcepcion RetornaDatosPorDefecto

OK (14 tests, 39 assertions)
```

### Observaciones

Las 14 pruebas pasan exitosamente con 39 aserciones en 2.781 segundos usando 10 MB de memoria. El módulo demuestra un manejo defensivo correcto ante fallos de BD en `getResumen()` y `getAnalisisInventario()`, retornando valores por defecto en lugar de propagar excepciones. El patrón `#[RunTestsInSeparateProcesses]` es necesario por el overload de Mockery sobre `App\Core\Conexion`, lo que explica el tiempo de ejecución ligeramente superior al promedio.
