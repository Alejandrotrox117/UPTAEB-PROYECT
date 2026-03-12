## Cuadro Nº 7: Módulo de Romana (RF07)

### Objetivos de la prueba

Validar que la consulta de registros de la romana (`selectAllRomana`) retorne correctamente el listado completo de pesajes con su estructura de datos esperada. El sistema debe manejar adecuadamente tanto los escenarios exitosos (uno o varios registros, lista vacía) como los fallos de base de datos (excepciones PDO), retornando siempre una respuesta estructurada con `status`, `data` y `message`.

### Técnicas

Pruebas de caja blanca con enfoque en aislamiento de dependencias mediante Mockery. Se evalúa el método `selectAllRomana()` del modelo `RomanaModel` en escenarios válidos e inválidos, verificando la estructura del array de retorno, la integridad de los campos de cada registro y el manejo de excepciones PDO en las fases `prepare` y `execute`.

### Código Involucrado

```php
<?php

namespace Tests\UnitTest\Romana;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\RomanaModel;
use Mockery;
use PDO;
use PDOStatement;
use PDOException;

#[RunTestsInSeparateProcesses]
class selectRomanaUnitTest extends TestCase
{
    private RomanaModel $model;
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
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new RomanaModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // ─── Data Providers ──────────────────────────────────────────────────────

    public static function providerRegistrosRomana(): array
    {
        return [
            'lista_con_varios_registros' => [
                [
                    ['idromana' => 1, 'peso' => 250.50, 'fecha' => '2026-03-01 08:00:00', 'estatus' => 'ACTIVO', 'fecha_creacion' => '2026-03-01'],
                    ['idromana' => 2, 'peso' => 310.00, 'fecha' => '2026-03-02 09:15:00', 'estatus' => 'ACTIVO', 'fecha_creacion' => '2026-03-02'],
                ],
            ],
            'lista_con_un_registro' => [
                [
                    ['idromana' => 5, 'peso' => 99.99, 'fecha' => '2026-03-05 12:00:00', 'estatus' => 'INACTIVO', 'fecha_creacion' => '2026-03-05'],
                ],
            ],
            'lista_vacia' => [[]],
        ];
    }

    // ─── Tests: selectAllRomana - camino exitoso ──────────────────────────────

    #[Test]
    #[DataProvider('providerRegistrosRomana')]
    public function testSelectAllRomana_RetornaStatusTrueConData(array $registrosSimulados): void
    {
        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($registrosSimulados);

        $resultado = $this->model->selectAllRomana();

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertTrue($resultado['status']);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertCount(count($registrosSimulados), $resultado['data']);
    }

    #[Test]
    public function testSelectAllRomana_CadaRegistroTieneEstructuraCorrecta(): void
    {
        $registroFake = [
            'idromana'      => 3,
            'peso'          => 175.25,
            'fecha'         => '2026-03-03 10:30:00',
            'estatus'       => 'ACTIVO',
            'fecha_creacion' => '2026-03-03',
        ];
        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn([$registroFake]);

        $resultado = $this->model->selectAllRomana();

        $this->assertTrue($resultado['status']);
        $this->assertNotEmpty($resultado['data']);

        $primer = $resultado['data'][0];
        $this->assertArrayHasKey('idromana', $primer);
        $this->assertArrayHasKey('peso', $primer);
        $this->assertArrayHasKey('fecha', $primer);
        $this->assertArrayHasKey('estatus', $primer);
        $this->assertArrayHasKey('fecha_creacion', $primer);
    }

    // ─── Tests: selectAllRomana - camino de error (PDOException) ─────────────

    #[Test]
    public function testSelectAllRomana_CuandoFallaDB_RetornaStatusFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new PDOException('Simulated DB error'));

        $resultado = $this->model->selectAllRomana();

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertEmpty($resultado['data']);
        $this->assertArrayHasKey('message', $resultado);
    }

    #[Test]
    public function testSelectAllRomana_CuandoFallaEjecucion_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')
            ->andThrow(new PDOException('Execute failed'));

        $resultado = $this->model->selectAllRomana();

        $this->assertFalse($resultado['status']);
        $this->assertSame([], $resultado['data']);
        $this->assertStringContainsString('Error', $resultado['message']);
    }

    #[Test]
    public function testSelectAllRomana_MensajeErrorEsElEsperado(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new PDOException('Connection lost'));

        $resultado = $this->model->selectAllRomana();

        $this->assertSame('Error al obtener los registros', $resultado['message']);
    }
}
```

### Caso de prueba

**TIPO:** Funcional (Caja blanca)

**OBJETIVO:** Verificar que `selectAllRomana()` devuelva la colección completa de registros de pesaje con estructura correcta y maneje adecuadamente los fallos de base de datos.

**DESCRIPCIÓN:** Se evalúan seis escenarios mediante DataProvider y pruebas individuales: consulta con múltiples registros (IDs 1 y 2, pesos 250.50 y 310.00 kg), consulta con un único registro (ID 5, 99.99 kg, estatus INACTIVO), lista vacía, validación de la estructura de campos de cada registro, fallo en `prepare` (PDOException), fallo en `execute` (PDOException) y verificación del mensaje de error exacto.

**ENTRADAS:**
- Lista con 2 registros: `idromana` 1/2, pesos 250.50/310.00 kg, estatus ACTIVO, fechas 2026-03-01/02
- Lista con 1 registro: `idromana` 5, peso 99.99 kg, estatus INACTIVO, fecha 2026-03-05
- Lista vacía: `[]`
- Registro individual con campos: `idromana` 3, peso 175.25 kg, fecha 2026-03-03, estatus ACTIVO
- Fallo en `prepare`: `PDOException('Simulated DB error')`
- Fallo en `execute`: `PDOException('Execute failed')`

**SALIDAS ESPERADAS:**
| Escenario | Resultado esperado |
|---|---|
| Lista con varios registros | `status true` + `data` con 2 elementos |
| Lista con un registro | `status true` + `data` con 1 elemento |
| Lista vacía | `status true` + `data []` con 0 elementos |
| Estructura de campos | `data[0]` contiene `idromana`, `peso`, `fecha`, `estatus`, `fecha_creacion` |
| Fallo en `prepare` | `status false` + `data []` + `message` presente |
| Fallo en `execute` | `status false` + `data []` + `message` contiene 'Error' |
| Mensaje exacto de error | `message === 'Error al obtener los registros'` |

### Resultado

```
PHPUnit 10.5.63 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: C:\xampp\htdocs\project\phpunit.xml

.......                                                             7 / 7 (100%)

Time: 00:01.917, Memory: 10.00 MB

select Romana Unit (Tests\UnitTest\Romana\selectRomanaUnit)
 ✔ SelectAllRomana RetornaStatusTrueConData with lista_con_varios_registros
 ✔ SelectAllRomana RetornaStatusTrueConData with lista_con_un_registro
 ✔ SelectAllRomana RetornaStatusTrueConData with lista_vacia
 ✔ SelectAllRomana CadaRegistroTieneEstructuraCorrecta
 ✔ SelectAllRomana CuandoFallaDB RetornaStatusFalse
 ✔ SelectAllRomana CuandoFallaEjecucion RetornaStatusFalse
 ✔ SelectAllRomana MensajeErrorEsElEsperado

OK (7 tests, 31 assertions)
```

### Observaciones

Las 7 pruebas se ejecutaron exitosamente en ~1.9 s con 10 MB de memoria. El DataProvider cubre las tres variantes del resultado: múltiples registros, un registro y lista vacía, mientras las pruebas individuales validan la integridad de campos y el manejo de errores. El mensaje de error está fijado exactamente como `'Error al obtener los registros'`, garantizando consistencia en la respuesta del modelo ante fallos de base de datos.
