## Cuadro Nº 1: Módulo de Sueldos (RF01)

### Objetivos de la prueba

Validar que el registro de sueldos (`insertSueldo()`) solo se ejecute correctamente cuando los datos de entrada son válidos. El sistema debe rechazar inserciones con montos negativos, errores de conexión, `lastInsertId` igual a cero y ausencia total de referencia a empleado o persona.

### Técnicas

Pruebas de caja blanca con enfoque en aislamiento mediante mocks de PDO/PDOStatement y Mockery. Se evalúa el método `insertSueldo()` en escenarios válidos e inválidos, verificando el array de retorno con las claves `status`, `message` y `sueldo_id`.

### Código Involucrado

```php
<?php

namespace Tests\UnitTest\Sueldos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\SueldosModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
class SueldosInsertUnitTest extends TestCase
{
    private SueldosModel $model;
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
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)
            ->andReturn(['total' => 0])->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('15')->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new SueldosModel();
    }

    protected function tearDown(): void
    {
        unset($this->model);
        Mockery::close();
    }

    // ─── Data Providers ──────────────────────────────────────────────────────

    public static function providerInsertSueldoExitoso(): array
    {
        return [
            'empleado_bolivares'   => [3, 800.00,   3, 'Pago quincenal - Caracas'],
            'empleado_dolares'     => [2, 200.00,   1, 'Bono en USD'],
            'empleado_monto_alto'  => [1, 50000.00, 3, 'Salario gerencial'],
        ];
    }

    public static function providerInsertSueldoFallido(): array
    {
        return [
            'monto_negativo'    => [-100.00,  'CHECK constraint failed: monto >= 0'],
            'error_conexion_bd' => [800.00,   'SQLSTATE: Connection lost'],
            'constraint_null'   => [800.00,   'Column idpersona/idempleado cannot both be NULL'],
        ];
    }

    // ─── Tests Exitosos ───────────────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerInsertSueldoExitoso')]
    public function testInsertSueldo_Exitosa_VariosEmpleados(
        int $idempleado,
        float $monto,
        int $idmoneda,
        string $observacion
    ): void {
        $stmtInsert = Mockery::mock(PDOStatement::class);
        $stmtInsert->shouldReceive('execute')->andReturn(true);

        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtInsert);
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn((string)(10 + $idempleado));

        $datos = [
            'idpersona'   => null,
            'idempleado'  => $idempleado,
            'monto'       => $monto,
            'idmoneda'    => $idmoneda,
            'observacion' => $observacion,
        ];

        $result = $this->model->insertSueldo($datos);

        $this->assertIsArray($result);
        $this->assertTrue($result['status'], $result['message'] ?? 'sin mensaje del modelo');
        $this->assertArrayHasKey('sueldo_id', $result);
        $this->assertGreaterThan(0, (int)$result['sueldo_id']);
    }

    #[Test]
    public function testInsertSueldo_Exitosa_ConIdPersona(): void
    {
        $stmtInsert = Mockery::mock(PDOStatement::class);
        $stmtInsert->shouldReceive('execute')->andReturn(true);

        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtInsert);
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('22');

        $datos = [
            'idpersona'   => 5,
            'idempleado'  => null,
            'monto'       => 600.00,
            'idmoneda'    => 3,
            'observacion' => 'Pago a persona externa',
        ];

        $result = $this->model->insertSueldo($datos);

        $this->assertIsArray($result);
        $this->assertTrue($result['status'], $result['message'] ?? 'sin mensaje del modelo');
        $this->assertSame('22', (string)$result['sueldo_id']);
    }

    #[Test]
    public function testInsertSueldo_Exitosa_SinIdMonedaUsaDefault(): void
    {
        $stmtInsert = Mockery::mock(PDOStatement::class);
        $stmtInsert->shouldReceive('execute')->andReturn(true);

        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtInsert);
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('30');

        $datos = [
            'idpersona'   => null,
            'idempleado'  => 7,
            'monto'       => 400.00,
            'observacion' => 'Sin moneda especificada',
        ];

        $result = $this->model->insertSueldo($datos);

        $this->assertIsArray($result);
        $this->assertTrue($result['status'], $result['message'] ?? 'sin mensaje del modelo');
    }

    // ─── Tests Fallidos ───────────────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerInsertSueldoFallido')]
    public function testInsertSueldo_Falla_PorExcepcionBD(float $monto, string $mensajeError): void
    {
        $stmtInsert = Mockery::mock(PDOStatement::class);
        $stmtInsert->shouldReceive('execute')->andThrow(new \Exception($mensajeError));

        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtInsert);

        $datos = [
            'idpersona'   => null,
            'idempleado'  => 3,
            'monto'       => $monto,
            'idmoneda'    => 3,
            'observacion' => 'Test de fallo',
        ];

        $result = $this->model->insertSueldo($datos);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], 'Se esperaba status false ante excepción de BD');
        $this->assertArrayHasKey('message', $result);
    }

    #[Test]
    public function testInsertSueldo_Falla_CuandoLastInsertIdEsCero(): void
    {
        $stmtInsert = Mockery::mock(PDOStatement::class);
        $stmtInsert->shouldReceive('execute')->andReturn(true);

        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtInsert);
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('0');

        $datos = [
            'idpersona'   => null,
            'idempleado'  => 3,
            'monto'       => 800.00,
            'idmoneda'    => 3,
            'observacion' => 'Test lastInsertId = 0',
        ];

        $result = $this->model->insertSueldo($datos);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], $result['message'] ?? 'sin mensaje del modelo');
    }

    #[Test]
    public function testInsertSueldo_AceptaInsercion_SinEmpleadoNiPersona(): void
    {
        // El modelo delega directo a ejecutarInsercionSueldo sin validación PHP-side.
        // La BD (constraint NOT NULL) sería la que rechaza mediante excepción.
        $stmtInsert = Mockery::mock(PDOStatement::class);
        $stmtInsert->shouldReceive('execute')->andThrow(
            new \Exception('Column idpersona/idempleado cannot both be NULL')
        );
        $this->mockPdo->shouldReceive('prepare')->andReturn($stmtInsert);

        $datos = [
            'idpersona'   => null,
            'monto'       => 800.00,
            'idmoneda'    => 3,
            'observacion' => 'Sin empleado ni persona',
        ];

        $result = $this->model->insertSueldo($datos);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], $result['message'] ?? 'sin mensaje del modelo');
    }
}
```

### Caso de prueba

**TIPO:** Funcional (Caja blanca)

**OBJETIVO:** Verificar el comportamiento del método `insertSueldo()` en `SueldosModel` ante entradas válidas, excepciones de base de datos y condiciones límite de identificadores.

**DESCRIPCIÓN:** Se prueban 10 escenarios que cubren inserciones exitosas (por empleado con distintas monedas, por persona externa y sin moneda explícita), fallos por excepción simulada de BD (monto negativo, pérdida de conexión, restricción NOT NULL) y el caso donde `lastInsertId` retorna `0`.

**ENTRADAS:**
- `idempleado=3, monto=800.00, idmoneda=3` → pago quincenal en bolívares
- `idempleado=2, monto=200.00, idmoneda=1` → bono en USD
- `idempleado=1, monto=50000.00, idmoneda=3` → salario gerencial
- `idpersona=5, monto=600.00, idmoneda=3` → pago a persona externa
- `idempleado=7, monto=400.00` (sin `idmoneda`) → moneda por defecto
- `monto=-100.00` → excepción CHECK constraint
- `monto=800.00` con error de conexión SQLSTATE
- `monto=800.00` con constraint NULL en BD
- `monto=800.00` con `lastInsertId='0'`
- Sin `idempleado` ni `idpersona` → excepción NOT NULL

**SALIDAS ESPERADAS:**

| Escenario | Resultado esperado |
|---|---|
| Inserción con empleado (bolivares/dolares/alto) | `status=true`, `sueldo_id > 0` |
| Inserción con `idpersona=5` | `status=true`, `sueldo_id='22'` |
| Inserción sin `idmoneda` | `status=true` |
| Excepción CHECK monto negativo | `status=false`, clave `message` presente |
| Excepción pérdida de conexión | `status=false`, clave `message` presente |
| Excepción constraint NULL | `status=false`, clave `message` presente |
| `lastInsertId='0'` | `status=false` |
| Sin empleado ni persona | `status=false` |

### Resultado

```
PHPUnit 10.5.63 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: C:\xampp\htdocs\project\phpunit.xml

..........                                                        10 / 10 (100%)

Time: 00:02.407, Memory: 10.00 MB

Sueldos Insert Unit (Tests\UnitTest\Sueldos\SueldosInsertUnit)
 ✔ InsertSueldo Exitosa VariosEmpleados with empleado_bolivares
 ✔ InsertSueldo Exitosa VariosEmpleados with empleado_dolares
 ✔ InsertSueldo Exitosa VariosEmpleados with empleado_monto_alto
 ✔ InsertSueldo Exitosa ConIdPersona
 ✔ InsertSueldo Exitosa SinIdMonedaUsaDefault
 ✔ InsertSueldo Falla PorExcepcionBD with monto_negativo
 ✔ InsertSueldo Falla PorExcepcionBD with error_conexion_bd
 ✔ InsertSueldo Falla PorExcepcionBD with constraint_null
 ✔ InsertSueldo Falla CuandoLastInsertIdEsCero
 ✔ InsertSueldo AceptaInsercion SinEmpleadoNiPersona

OK (10 tests, 30 assertions)
```

### Observaciones

Las 10 pruebas pasaron en 2.407 segundos con 30 aserciones y uso de 10 MB de memoria. El modelo no aplica validación PHP-side para montos negativos ni para la ausencia de `idempleado`/`idpersona`, delegando esa responsabilidad a las restricciones de la base de datos, lo cual es coherente con el comportamiento verificado.
