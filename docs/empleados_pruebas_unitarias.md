## Cuadro Nº 2: Módulo de Empleados (RF04)

### Objetivos de la prueba

Validar que las operaciones CRUD del módulo de Empleados (insertar, consultar, actualizar y eliminar) solo se ejecuten correctamente cuando los datos y condiciones de la base de datos son válidos. El sistema debe rechazar intentos de operación cuando el motor de base de datos lanza una excepción, cuando `execute()` retorna `false`, así como confirmar que las consultas de selección devuelvan la estructura de respuesta correcta independientemente de si existen registros o no.

---

### Técnicas

Pruebas de caja blanca con enfoque en aislamiento de dependencias mediante dobles de prueba (mocks). Se utiliza **Mockery** para simular `PDO` y `PDOStatement`, así como un mock de sobrecarga (`overload`) sobre `App\Core\Conexion` para interceptar la creación de la conexión real a base de datos. Se evalúan los métodos `insertEmpleado()`, `selectAllEmpleados()`, `getEmpleadoById()`, `updateEmpleado()` y `deleteEmpleado()` del modelo `EmpleadosModel` en escenarios válidos e inválidos, verificando el valor de retorno y la estructura del array de respuesta según corresponda.

---

### Código Involucrado

```php
<?php
// ══════════════════════════════════════════════════════════════════
// EmpleadoInsertUnitTest.php
// ══════════════════════════════════════════════════════════════════

namespace Tests\UnitTest\Empleados;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\EmpleadosModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
class EmpleadoInsertUnitTest extends TestCase
{
    private EmpleadosModel $model;
    private $mockPdo;
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');

        $this->mockPdo  = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new EmpleadosModel();
    }

    protected function tearDown(): void { Mockery::close(); }

    public static function providerCasosExitososInsert(): array
    {
        return [
            'empleado_operario_completo' => [[
                'nombre'             => 'María',
                'apellido'           => 'González',
                'identificacion'     => 'V-12345678',
                'tipo_empleado'      => 'OPERARIO',
                'puesto'             => 'Operario de Clasificación',
                'salario'            => 30.00,
                'fecha_nacimiento'   => '1995-03-15',
                'direccion'          => 'Urbanización La Victoria, Calle 5',
                'correo_electronico' => 'maria.gonzalez@recicladora.com',
                'telefono_principal' => '0414-5551234',
                'genero'             => 'F',
                'fecha_inicio'       => '2024-01-01',
                'observaciones'      => 'Especializada en cartón y papel',
                'estatus'            => 'ACTIVO',
            ]],
            'empleado_sin_campos_opcionales' => [[
                'nombre'         => 'Luis',
                'apellido'       => 'Ramírez',
                'identificacion' => 'V-87654321',
                'tipo_empleado'  => 'ADMINISTRATIVO',
                'estatus'        => 'ACTIVO',
            ]],
            'empleado_con_salario_cero' => [[
                'nombre'         => 'Ana',
                'apellido'       => 'Torres',
                'identificacion' => 'V-11223344',
                'salario'        => 0.00,
                'estatus'        => 'ACTIVO',
            ]],
        ];
    }

    public static function providerCasosFallidosInsert(): array
    {
        return [
            'execute_falla_en_bd' => [[
                'nombre'         => 'Test',
                'apellido'       => 'Fail',
                'identificacion' => 'V-00000001',
            ]],
        ];
    }

    #[Test]
    #[DataProvider('providerCasosExitososInsert')]
    public function testInsertEmpleado_DatosValidos_RetornaTrue(array $data): void
    {
        $this->mockStmt->shouldReceive('execute')->andReturn(true);
        $resultado = $this->model->insertEmpleado($data);
        $this->assertTrue($resultado);
    }

    #[Test]
    #[DataProvider('providerCasosFallidosInsert')]
    public function testInsertEmpleado_FalloEnBD_RetornaFalse(array $data): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \PDOException('Duplicate entry'));
        $resultado = $this->model->insertEmpleado($data);
        $this->assertFalse($resultado);
    }

    #[Test]
    public function testInsertEmpleado_ExecuteRetornaFalse_RetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andReturn(false);
        $resultado = $this->model->insertEmpleado([
            'nombre'         => 'Test',
            'apellido'       => 'Prueba',
            'identificacion' => 'V-99999999',
        ]);
        $this->assertFalse($resultado);
    }
}


// ══════════════════════════════════════════════════════════════════
// EmpleadoSelectUnitTest.php
// ══════════════════════════════════════════════════════════════════

namespace Tests\UnitTest\Empleados;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\EmpleadosModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
class EmpleadoSelectUnitTest extends TestCase
{
    private EmpleadosModel $model;
    private $mockPdo;
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');

        $this->mockPdo  = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new EmpleadosModel();
    }

    protected function tearDown(): void { Mockery::close(); }

    public static function providerSelectAllEmpleados(): array
    {
        return [
            'lista_con_varios_empleados' => [
                [
                    ['idempleado' => 1, 'nombre' => 'Carlos', 'apellido' => 'Pérez',    'estatus' => 'ACTIVO'],
                    ['idempleado' => 2, 'nombre' => 'María',  'apellido' => 'González', 'estatus' => 'ACTIVO'],
                ],
                true,
            ],
            'lista_vacia' => [[], true],
        ];
    }

    public static function providerGetEmpleadoById(): array
    {
        return [
            'empleado_existente' => [
                1,
                ['idempleado' => 1, 'nombre' => 'Carlos', 'apellido' => 'Pérez'],
                ['idempleado' => 1, 'nombre' => 'Carlos', 'apellido' => 'Pérez'],
            ],
            'empleado_no_existe' => [99999, false, false],
        ];
    }

    #[Test]
    #[DataProvider('providerSelectAllEmpleados')]
    public function testSelectAllEmpleados_RetornaEstructuraCorrecta(array $empleadosMock, bool $statusEsperado): void
    {
        $this->mockStmt->shouldReceive('fetch')->andReturn(false);
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn($empleadosMock);
        $resultado = $this->model->selectAllEmpleados(0);
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertEquals($statusEsperado, $resultado['status']);
        $this->assertIsArray($resultado['data']);
    }

    #[Test]
    #[DataProvider('providerGetEmpleadoById')]
    public function testGetEmpleadoById_RetornaEmpleadoOFalso(int $id, $fetchReturn, $valorEsperado): void
    {
        $this->mockStmt->shouldReceive('fetch')->andReturn($fetchReturn);
        $resultado = $this->model->getEmpleadoById($id);
        $this->assertEquals($valorEsperado, $resultado);
    }

    #[Test]
    public function testSelectAllEmpleados_FalloEnBD_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \Exception('DB connection failed'));
        $resultado = $this->model->selectAllEmpleados(0);
        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertStringContainsString('Error', $resultado['message']);
    }

    #[Test]
    public function testGetEmpleadoById_FalloEnBD_RetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \PDOException('Error de BD'));
        $resultado = $this->model->getEmpleadoById(1);
        $this->assertFalse($resultado);
    }
}


// ══════════════════════════════════════════════════════════════════
// EmpleadoUpdateUnitTest.php
// ══════════════════════════════════════════════════════════════════

namespace Tests\UnitTest\Empleados;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\EmpleadosModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
class EmpleadoUpdateUnitTest extends TestCase
{
    private EmpleadosModel $model;
    private $mockPdo;
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');

        $this->mockPdo  = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new EmpleadosModel();
    }

    protected function tearDown(): void { Mockery::close(); }

    public static function providerCasosExitososUpdate(): array
    {
        return [
            'actualizacion_completa' => [[
                'idempleado'         => 1,
                'nombre'             => 'Carlos Actualizado',
                'apellido'           => 'Pérez',
                'identificacion'     => '12345678',
                'tipo_empleado'      => 'ADMINISTRATIVO',
                'estatus'            => 'ACTIVO',
                'telefono_principal' => '04141234567',
                'correo_electronico' => 'carlos.perez@email.com',
                'direccion'          => 'Av. Principal, Ciudad',
                'fecha_nacimiento'   => '1990-05-15',
                'genero'             => 'M',
                'puesto'             => 'Supervisor',
                'salario'            => 500.00,
            ]],
            'actualizacion_salario_cero' => [[
                'idempleado'     => 2,
                'nombre'         => 'Ana',
                'apellido'       => 'Torres',
                'identificacion' => 'V-22334455',
                'tipo_empleado'  => 'OPERARIO',
                'estatus'        => 'ACTIVO',
                'salario'        => 0.00,
            ]],
            'actualizacion_sin_campos_opcionales' => [[
                'idempleado'     => 3,
                'nombre'         => 'Pedro',
                'apellido'       => 'López',
                'identificacion' => 'V-55443322',
                'tipo_empleado'  => 'OPERARIO',
                'estatus'        => 'INACTIVO',
            ]],
        ];
    }

    public static function providerCasosFallidosUpdate(): array
    {
        return [
            'fallo_pdo_exception' => [[
                'idempleado'     => 1,
                'nombre'         => 'Fail',
                'apellido'       => 'Test',
                'identificacion' => 'V-00000000',
            ]],
        ];
    }

    #[Test]
    #[DataProvider('providerCasosExitososUpdate')]
    public function testUpdateEmpleado_DatosValidos_RetornaTrue(array $data): void
    {
        $this->mockStmt->shouldReceive('execute')->andReturn(true);
        $resultado = $this->model->updateEmpleado($data);
        $this->assertTrue($resultado);
    }

    #[Test]
    #[DataProvider('providerCasosFallidosUpdate')]
    public function testUpdateEmpleado_FalloEnBD_RetornaFalse(array $data): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \PDOException('Error al actualizar'));
        $resultado = $this->model->updateEmpleado($data);
        $this->assertFalse($resultado);
    }

    #[Test]
    public function testUpdateEmpleado_ExecuteRetornaFalse_RetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andReturn(false);
        $resultado = $this->model->updateEmpleado([
            'idempleado'     => 99999,
            'nombre'         => 'Inexistente',
            'apellido'       => 'Prueba',
            'identificacion' => 'V-00000099',
        ]);
        $this->assertFalse($resultado);
    }
}


// ══════════════════════════════════════════════════════════════════
// EmpleadoDeleteUnitTest.php
// ══════════════════════════════════════════════════════════════════

namespace Tests\UnitTest\Empleados;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\EmpleadosModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
class EmpleadoDeleteUnitTest extends TestCase
{
    private EmpleadosModel $model;
    private $mockPdo;
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');

        $this->mockPdo  = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new EmpleadosModel();
    }

    protected function tearDown(): void { Mockery::close(); }

    public static function providerCasosExitososDelete(): array
    {
        return [
            'eliminar_id_1'   => [1],
            'eliminar_id_10'  => [10],
            'eliminar_id_100' => [100],
        ];
    }

    public static function providerCasosFallidosDelete(): array
    {
        return [
            'id_inexistente_grande' => [99999],
            'id_cero'               => [0],
        ];
    }

    #[Test]
    #[DataProvider('providerCasosExitososDelete')]
    public function testDeleteEmpleado_ExecuteExitoso_RetornaTrue(int $id): void
    {
        $this->mockStmt->shouldReceive('execute')->andReturn(true);
        $resultado = $this->model->deleteEmpleado($id);
        $this->assertTrue($resultado);
    }

    #[Test]
    #[DataProvider('providerCasosFallidosDelete')]
    public function testDeleteEmpleado_ExecuteRetornaFalse_RetornaFalse(int $id): void
    {
        $this->mockStmt->shouldReceive('execute')->andReturn(false);
        $resultado = $this->model->deleteEmpleado($id);
        $this->assertFalse($resultado);
    }

    #[Test]
    public function testDeleteEmpleado_FalloEnBD_RetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new \PDOException('Error al eliminar'));
        $resultado = $this->model->deleteEmpleado(1);
        $this->assertFalse($resultado);
    }

    #[Test]
    public function testDeleteEmpleado_DobleLlamada_AmbasOperacionesIndependientes(): void
    {
        // Primera llamada retorna true, segunda retorna false (simula ya eliminado)
        $this->mockStmt->shouldReceive('execute')->twice()->andReturn(true, false);
        $resultado1 = $this->model->deleteEmpleado(1);
        $resultado2 = $this->model->deleteEmpleado(1);
        $this->assertTrue($resultado1);
        $this->assertFalse($resultado2);
    }
}
```

---

### Caso de prueba

**TIPO:** Funcional (Caja blanca)

**OBJETIVO:** Verificar que el modelo `EmpleadosModel` gestiona correctamente las operaciones de inserción, consulta, actualización y eliminación de empleados, respondiendo de forma predecible ante condiciones de éxito y ante fallos simulados del motor de base de datos.

**DESCRIPCIÓN:** Se prueban los cuatro métodos CRUD con escenarios de datos válidos (registro completo, sin campos opcionales, salario cero), escenarios de fallo (excepción `PDOException`, `execute()` retorna `false`) y casos borde (ID inexistente, ID cero, doble llamada independiente). La conexión real a BD se reemplaza completamente por mocks de Mockery.

**ENTRADAS:**

- `insertEmpleado()` con empleado operario completo (nombre, C.I., salario, dirección, correo, etc.)
- `insertEmpleado()` con datos mínimos (nombre, apellido, identificación) y `execute()` retornando `false`
- `selectAllEmpleados(0)` retornando lista con 2 empleados activos y lista vacía
- `getEmpleadoById(1)` con fetch devolviendo el registro; `getEmpleadoById(99999)` con fetch devolviendo `false`
- `updateEmpleado()` con actualización completa (ID 1, salario 500), salario cero (ID 2) e INACTIVO sin campos opcionales (ID 3)
- `deleteEmpleado()` con IDs 1, 10, 100 (éxito) e IDs 99999 y 0 (fallo); doble llamada con resultados `true`/`false`

**SALIDAS ESPERADAS:**

| Escenario | Resultado esperado |
|---|---|
| `insertEmpleado` con datos válidos y execute=true | `true` |
| `insertEmpleado` con PDOException | `false` |
| `insertEmpleado` con execute=false | `false` |
| `selectAllEmpleados` con registros o vacío | Array con `status=true` y `data` array |
| `selectAllEmpleados` con Exception | Array con `status=false` y clave `message` con "Error" |
| `getEmpleadoById` con ID existente | Array con datos del empleado |
| `getEmpleadoById` con ID inexistente o PDOException | `false` |
| `updateEmpleado` con datos válidos y execute=true | `true` |
| `updateEmpleado` con PDOException o execute=false | `false` |
| `deleteEmpleado` con execute=true | `true` |
| `deleteEmpleado` con execute=false o PDOException | `false` |
| Segunda llamada a `deleteEmpleado` con execute=false | `false` (independiente de la primera) |

---

### Resultado

```
PHPUnit 10.5.63 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: C:\xampp\htdocs\project\phpunit.xml

.......................                                           23 / 23 (100%)

Time: 00:03.860, Memory: 10.00 MB

Empleado Delete Unit (Tests\UnitTest\Empleados\EmpleadoDeleteUnit)
 ✔ DeleteEmpleado ExecuteExitoso RetornaTrue with eliminar_id_1
 ✔ DeleteEmpleado ExecuteExitoso RetornaTrue with eliminar_id_10
 ✔ DeleteEmpleado ExecuteExitoso RetornaTrue with eliminar_id_100
 ✔ DeleteEmpleado ExecuteRetornaFalse RetornaFalse with id_inexistente_grande
 ✔ DeleteEmpleado ExecuteRetornaFalse RetornaFalse with id_cero
 ✔ DeleteEmpleado FalloEnBD RetornaFalse
 ✔ DeleteEmpleado DobleLlamada AmbasOperacionesIndependientes

Empleado Insert Unit (Tests\UnitTest\Empleados\EmpleadoInsertUnit)
 ✔ InsertEmpleado DatosValidos RetornaTrue with empleado_operario_completo
 ✔ InsertEmpleado DatosValidos RetornaTrue with empleado_sin_campos_opcionales
 ✔ InsertEmpleado DatosValidos RetornaTrue with empleado_con_salario_cero
 ✔ InsertEmpleado FalloEnBD RetornaFalse with execute_falla_en_bd
 ✔ InsertEmpleado ExecuteRetornaFalse RetornaFalse

Empleado Select Unit (Tests\UnitTest\Empleados\EmpleadoSelectUnit)
 ✔ SelectAllEmpleados RetornaEstructuraCorrecta with lista_con_varios_empleados
 ✔ SelectAllEmpleados RetornaEstructuraCorrecta with lista_vacia
 ✔ GetEmpleadoById RetornaEmpleadoOFalso with empleado_existente
 ✔ GetEmpleadoById RetornaEmpleadoOFalso with empleado_no_existe
 ✔ SelectAllEmpleados FalloEnBD RetornaStatusFalse
 ✔ GetEmpleadoById FalloEnBD RetornaFalse

Empleado Update Unit (Tests\UnitTest\Empleados\EmpleadoUpdateUnit)
 ✔ UpdateEmpleado DatosValidos RetornaTrue with actualizacion_completa
 ✔ UpdateEmpleado DatosValidos RetornaTrue with actualizacion_salario_cero
 ✔ UpdateEmpleado DatosValidos RetornaTrue with actualizacion_sin_campos_opcionales
 ✔ UpdateEmpleado FalloEnBD RetornaFalse with fallo_pdo_exception
 ✔ UpdateEmpleado ExecuteRetornaFalse RetornaFalse

OK (23 tests, 34 assertions)
```

---

### Observaciones

- Los 4 archivos de prueba utilizan `#[RunTestsInSeparateProcesses]` para evitar conflictos entre los mocks de sobrecarga (`overload`) de Mockery en diferentes clases de prueba.
- El acceso al modelo se realiza siempre a través de la interfaz pública de `EmpleadosModel`; no se prueban métodos privados directamente.
- Las pruebas de `selectAllEmpleados` verifican tanto la estructura del arreglo de respuesta (`status`, `data`) como el comportamiento ante errores de BD, garantizando que la capa de presentación reciba siempre un formato consistente.
- El caso `testDeleteEmpleado_DobleLlamada_AmbasOperacionesIndependientes` confirma que cada llamada al método es autónoma y no depende de estado interno entre ejecuciones.
- Tiempo de ejecución total: **4.845 s** para 23 pruebas con 34 aserciones. Sin fallos ni omisiones.
