---
name: Validador de Pruebas Unitarias con Mocks (Legacy PHP)
description: Valida y reestructura pruebas PHPUnit existentes para aplicaciones PHP heredadas usando Mockery overload, sin modificar modelos ni controladores.
---

# Instrucciones para el Validador de Pruebas con Mocks y Stubs (Legacy PHP)

Esta skill reestructura archivos `*Test.php` existentes aplicando **Mocks y Stubs reales** mediante **Mockery `overload:`**. Aplica cuando el modelo instancia sus dependencias internamente (ej. `new Conexion()` dentro de métodos privados) y **NO es posible la inyección de dependencias sin modificar el código fuente**.

---

## 1. Reglas Absolutas
1. **Inmutabilidad total**: Nunca modificar `app/Models/`, `app/Controllers/` ni ninguna clase de la aplicación.
2. **Refactorización, no creación**: Reescribir los archivos de test existentes. No crear archivos nuevos.
3. **Mocks y Stubs obligatorios**: Todo acceso a recursos reales (BD, red, filesystem) debe ser interceptado con Mockery.
4. **Sin base de datos real**: Eliminar el trait `RequiresDatabase` y todas las conexiones reales.

> **Comportamiento esperado**: Con este patrón, **ningún dato se escribe en la base de datos real**. Eso es correcto y es el objetivo. Los mocks interceptan al 100% las llamadas a `Conexion`. Si algo apareciera en la BD, significaría que los mocks no están funcionando.

---

## 2. Dependencia Requerida
Mockery debe estar instalado:

```bash
composer require --dev mockery/mockery
```

---

## 3. Patrón Principal: Mockery `overload:`

Cuando un modelo hace `new Conexion()` internamente, usar **overload** para interceptar todas las instanciaciones de esa clase en el proceso PHP:

```php
$mockConexion = Mockery::mock('overload:App\Core\Conexion');
$mockConexion->shouldReceive('connect')->andReturn(null);
$mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
$mockConexion->shouldReceive('disconnect')->andReturn(null);
```

> `overload:` reemplaza la clase a nivel de autoloader: **cualquier `new Conexion()`** en todo el proceso devolverá el mock.

---

## 4. Aislamiento de Procesos (Obligatorio con `overload:`)

El `overload:` contamina el proceso PHP completo. Para evitar interferencia entre tests, **cada clase de test DEBE tener**:

```php
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class MiModeloTest extends TestCase { ... }
```

---

## 5. Estructura del Archivo de Test

```php
<?php
namespace Tests\Modulo;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\MiModelo;
use Mockery;
use Mockery\MockInterface;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class MiModeloTest extends TestCase
{
    private MiModelo $model;
    private MockInterface $mockPdo;
    private MockInterface $mockStmt;

    protected function setUp(): void
    {
        // Suprimir error_log(): en CLI escribe a STDERR y PHPUnit lo convierte en excepcion
        ini_set('log_errors', '0');
        ini_set('error_log', 'NUL'); // Windows — usar '/dev/null' en Linux/Mac

        // 1. Mock del PDO y PDOStatement
        $this->mockPdo  = Mockery::mock(\PDO::class);
        $this->mockStmt = Mockery::mock(\PDOStatement::class);

        // Stubs por defecto (comportamiento "sin resultados")
        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->andReturn([])->byDefault();

        // 2. Overload: intercepta new Conexion() dentro del modelo
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        // 3. Instanciar el modelo DESPUES de configurar el overload
        $this->model = new MiModelo();
    }

    protected function tearDown(): void
    {
        unset($this->model); // No usar $this->model = null en propiedades tipadas
        Mockery::close();    // OBLIGATORIO: libera todos los mocks
    }
}
```

---

## 6. Patrones de Stub por Escenario

### Retorno vacio / no encontrado (default — ya configurado en setUp)
```php
$result = $this->model->getById(99999);
$this->assertFalse($result);
```

### Retorno de datos (override del byDefault)
```php
$datos = [['id' => 1, 'nombre' => 'Test']];
$this->mockStmt->shouldReceive('fetchAll')->with(\PDO::FETCH_ASSOC)->andReturn($datos);
$result = $this->model->selectAll();
$this->assertCount(1, $result);
```

### Multiples llamadas secuenciales a `prepare()`
```php
$stmt1 = Mockery::mock(\PDOStatement::class);
$stmt1->shouldReceive('execute')->andReturn(true);
$stmt1->shouldReceive('fetch')->andReturn(['id' => 1, 'nombre' => 'Proveedor']);

$stmt2 = Mockery::mock(\PDOStatement::class);
$stmt2->shouldReceive('execute')->andReturn(true);
$stmt2->shouldReceive('fetch')->andReturn(false); // producto no existe

$this->mockPdo->shouldReceive('prepare')->andReturn($stmt1, $stmt2);
```

### Transacciones (INSERT / UPDATE / DELETE)
```php
$this->mockPdo->shouldReceive('beginTransaction')->andReturn(true);
$this->mockPdo->shouldReceive('lastInsertId')->andReturn('42');
$this->mockPdo->shouldReceive('commit')->andReturn(true);
$this->mockPdo->shouldReceive('rollBack')->andReturn(true);
$this->mockPdo->shouldReceive('inTransaction')->andReturn(false);
```

---

## 7. Nombrado de Tests y Cobertura Tipica/Atipica

Formato: `testNombreMetodo_Escenario_ResultadoEsperado`

Cada modulo DEBE tener cobertura de **casos tipicos** (flujo feliz) Y **casos atipicos** (errores, bordes, datos invalidos).

### Casos Tipicos (flujo exitoso esperado)

| Escenario | Ejemplo de nombre |
|---|---|
| Operacion exitosa con datos validos | `testInsertar_Exitosa_DatosValidos` |
| Consulta retorna datos existentes | `testSelectById_RetornaDatos_CuandoIdExiste` |
| Actualizacion persiste en BD | `testActualizar_Exitosa_DatosValidos` |
| Eliminacion cambia estatus | `testEliminar_RetornaTrue_CuandoExiste` |

### Casos Atipicos (errores, bordes, invalidos)

| Escenario | Ejemplo de nombre |
|---|---|
| ID que no existe | `testGetById_RetornaFalse_CuandoIdNoExiste` |
| Sin datos de entrada | `testInsertar_Falla_SinDetalles` |
| Identificacion duplicada | `testInsertar_Falla_IdentificacionDuplicada` |
| Estado incorrecto | `testEliminar_RetornaError_CuandoEstadoNoBorrador` |
| Segunda operacion idempotente | `testEliminar_SegundaEliminacion_RetornaFalse` |

> **Regla**: por cada metodo publico del modelo debe haber al menos 1 caso tipico y 1 caso atipico.

---

## 8. DataProviders

```php
public static function providerIdsInexistentes(): array
{
    return [
        'ID Negativo'   => [-1],
        'ID Cero'       => [0],
        'ID Muy Grande' => [99999999],
    ];
}

#[Test]
#[DataProvider('providerIdsInexistentes')]
public function testGetById_RetornaFalse_CuandoIdNoExiste(int $id): void
{
    $result = $this->model->getById($id);
    $this->assertFalse($result, "Se esperaba false para ID: $id");
}
```

---

## 9. Aserciones Recomendadas

| Situacion | Asercion |
|---|---|
| Metodo retorna false | `assertFalse($result)` |
| Metodo retorna array vacio | `assertIsArray($result); assertEmpty($result);` |
| Resultado contiene clave | `assertArrayHasKey('status', $result)` |
| Operacion exitosa | `assertTrue($result['status'], $result['message'] ?? '')` |
| ID generado | `assertGreaterThan(0, $result['id'])` |
| Mensaje de error | `assertStringContainsString('identificaci', strtolower($result['message']))` |

### Regla critica: en casos atipicos imprimir el mensaje del MODELO, no del test

Cuando el escenario es un caso de error o validacion fallida, el mensaje que aparece en consola debe provenir del modelo (`$result['message']`), **no de un string inventado en el test**. Esto permite ver exactamente que valido el modelo.

```php
// CORRECTO: el mensaje del modelo aparece en consola si la asercion falla
$this->assertFalse($result['status'], $result['message'] ?? 'sin mensaje del modelo');
$this->assertStringContainsString('identificaci', strtolower($result['message']));

// CORRECTO: imprimir el mensaje del modelo como informacion adicional
fwrite(STDOUT, "\n[MODELO] " . ($result['message'] ?? '') . "\n");
$this->assertFalse($result['status'], $result['message'] ?? '');

// INCORRECTO: mensaje inventado en el test oculta lo que dice el modelo
$this->assertFalse($result['status'], 'Se esperaba que fallara la insercion'); // ← MAL
```

> El parametro `$message` de las aserciones (`assertTrue`, `assertFalse`, etc.) es lo que PHPUnit muestra si la asercion FALLA. Pasarle `$result['message']` garantiza que el desarrollador vea exactamente la razon que retorno el modelo.

---

## 10. Convencion de Nomenclatura de Archivos

PHPUnit detecta archivos automáticamente solo cuando terminan en `Test.php`. Usar siempre el sufijo estándar:

| Incorrecto | Correcto |
|---|---|
| `TestProveedorInsert.php` | `ProveedorInsertTest.php` |
| `TestProductoSelect.php` | `ProductoSelectTest.php` |

Si los archivos existentes tienen el prefijo `Test`, renombrarlos y actualizar el nombre de clase:
```bash
Rename-Item TestProveedorInsert.php ProveedorInsertTest.php  # PowerShell
mv TestProveedorInsert.php ProveedorInsertTest.php           # bash
```

---

## 11. Helper de Datos de Prueba (Localidad Venezuela)

Definir metodos privados con datos de entrada representativos para reutilizar en multiples tests. Los datos deben reflejar la **localidad venezolana**: formatos de cedula, telefonos, ciudades y nombres propios del pais.

```php
private function datosEntidadValidos(): array
{
    return [
        'nombre'             => 'Carlos',
        'apellido'           => 'Mendoza',
        'identificacion'     => 'V-' . rand(10000000, 30000000), // cedula venezolana
        'fecha_nacimiento'   => '1990-06-15',
        'direccion'          => 'Av. Bolivar, Maracay',          // ciudad venezolana
        'correo_electronico' => 'cmendoza@gmail.com',
        'telefono_principal' => '04241550001',                   // prefijo venezolano
        'genero'             => 'MASCULINO',
        'observaciones'      => 'Fixture de prueba Venezuela',
    ];
}
```

Uso en tests:
```php
$result = $this->model->insertarEntidad($this->datosEntidadValidos());
```

> Los datos del helper son ficticios — **nunca llegan a la BD real** en patron Mockery porque los mocks interceptan toda llamada a `Conexion`. En patron hibrido (BD real) si se persisten hasta que `tearDown` los elimine.

Ver **Seccion 17** para referencia completa de datos venezolanos de prueba.

---

## 12. Advertencias Criticas

- **`unset($this->model)`** en `tearDown()`, nunca `$this->model = null` si la propiedad tiene tipo no-nullable.
- **`Mockery::close()`** es obligatorio en `tearDown()` para verificar expectativas y liberar memoria.
- **`ini_set('error_log', 'NUL')`** en `setUp()` para evitar que `error_log()` del modelo escriba a STDERR y PHPUnit lo interprete como excepcion (problema especifico de `#[RunTestsInSeparateProcesses]`).
- El **overload** solo funciona si la clase mockeada **no ha sido cargada** por el autoloader antes. `#[RunTestsInSeparateProcesses]` garantiza que cada test tiene su propio proceso, por lo que el overload siempre funciona.

---

## 13. Patron Hibrido: BD de Prueba Real + DataProvider + Stubs de Fixture

Cuando se requiere que los tests **escriban en la BD de prueba real** pero **tambien apliquen DataProviders y Stubs**, usar este patron sin Mockery.

### Cuando usar este patron vs Mockery overload

| Criterio | Mockery overload | Patron hibrido |
|---|---|---|
| Datos persisten en BD real | NO | SI |
| Requiere MySQL corriendo | NO | SI |
| Prueba flujo completo | Parcial | Completo |

### Estructura base

```php
class MiModeloTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;  // salta si MySQL no esta disponible

    private MiModelo $model;
    private ?int $idCreado = null;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new MiModelo();
        // Stub de fixture: datos conocidos para el Arrange del test
        $result = $this->model->insertar($this->datosFixture());
        $this->idCreado = ($result['status'] ?? false) ? (int)$result['id'] : null;
    }

    protected function tearDown(): void
    {
        if ($this->idCreado !== null) {
            $this->model->eliminar($this->idCreado);
        }
        unset($this->model);
    }
}
```

### Reglas criticas para datos de fixture (esquema real)

Siempre verificar el esquema real con `DESCRIBE tabla` antes de definir fixtures.

- **Respetar limites varchar**: `varchar(10)` → `'X' . substr(uniqid(), -9)` (exactamente 10 chars)
- **ENUM columns**: usar el valor exacto del ENUM, no abreviaciones
  - `enum('activo','inactivo')` → `'activo'` NO `'ACTIVO'`
  - `enum('MASCULINO','FEMENINO','OTRO','')` → `'MASCULINO'` NO `'M'`
- **Asserts de ENUM**: `assertEquals('inactivo', strtolower($result['estatus']))` por portabilidad

### Identificaciones unicas en DataProvider

Si los IDs en el DataProvider pueden ya existir en BD por corridas anteriores, usar `uniqid()`:

```php
public static function providerIdentificacionesUnicas(): array
{
    $u = substr(uniqid(), -8); // unico por corrida de PHPUnit
    return [
        'Formato V' => ['V' . $u],  // 9 chars, dentro de varchar(10)
        'Formato E' => ['E' . $u],
    ];
}
```

PHPUnit evalua el DataProvider UNA SOLA VEZ al construir el suite. El `uniqid()` cambia en cada corrida, evitando colisiones con corridas anteriores.

### Eliminacion logica (estatus en lugar de DELETE fisico)

Si `deleteXxx()` hace `UPDATE estatus='inactivo'` en vez de `DELETE`:
- El registro PERMANECE en la tabla (puede interferir con verificaciones de duplicados)
- **Siempre usar `uniqid()` en identificaciones de fixture** para que sean distintas en cada corrida
- NO usar IDs fijos en fixtures si el modelo hace eliminacion logica sin filtrar inactivos
---

## 14. Configuracion Correcta de bootstrap.php para BD de Prueba

### Problema: EnvLoader cargado antes que PHPUnit aplique sus `<env>`

Si el proyecto usa un `EnvLoader` registrado en la seccion `files` de `composer.json`, se carga **automaticamente junto con `vendor/autoload.php`**. Esto ocurre ANTES de que PHPUnit aplique las variables `<env>` de `phpunit.xml`, haciendo que `config.php` defina las constantes con los valores de produccion.

**Sintoma:** los tests escriben en la BD de produccion aunque `phpunit.xml` tenga `<env name="DB_NAME_GENERAL" value="bd_pda_test"/>`.

**Diagnostico rapido:**
```php
// tests/DiagnosticoDBTest.php (borrar despues)
public function testQueDBSeUsaEnTests(): void {
    $this->assertStringContainsString('test', DB_NAME_GENERAL,
        'ERROR: usando BD de produccion: ' . DB_NAME_GENERAL);
}
```

### Solucion: fijar `$_ENV` y `putenv()` ANTES de require autoload

```php
// tests/bootstrap.php
<?php
// 1. Fijar env vars ANTES de que el autoloader cargue EnvLoader.php
$_ENV['APP_ENV']           = 'testing';
$_ENV['DB_HOST']           = '127.0.0.1';
$_ENV['DB_USERNAME']       = 'root';
$_ENV['DB_PASSWORD']       = '';
$_ENV['DB_NAME_GENERAL']   = 'bd_pda_test';
$_ENV['DB_NAME_SEGURIDAD'] = 'bd_pda_seguridad_test';

putenv('APP_ENV=testing');
putenv('DB_HOST=127.0.0.1');
putenv('DB_USERNAME=root');
putenv('DB_PASSWORD=');
putenv('DB_NAME_GENERAL=bd_pda_test');
putenv('DB_NAME_SEGURIDAD=bd_pda_seguridad_test');

// 2. Ahora si: autoloader lee $_ENV en vez del .env de produccion
require_once __DIR__ . '/../vendor/autoload.php';

// 3. config.php define las constantes con los valores de prueba
require_once __DIR__ . '/../config/config.php';
```

`EnvLoader::load()` respeta las claves ya presentes en `$_ENV` y `getenv()` (linea: `if (getenv($key) !== false || isset($_ENV[$key])) continue;`), por lo que no sobrescribe los valores establecidos en el paso 1.

---

## 15. Sincronizacion de Esquema entre BD de Produccion y BD de Prueba

### Por que puede diferir el esquema

La BD de prueba se crea inicialmente vacia (`CREATE DATABASE IF NOT EXISTS`) y no se actualiza automaticamente cuando cambia la BD de produccion. Las diferencias mas comunes que rompen los tests:

- Nombre de columna con typo en produccion (`fecha_cracion` vs `fecha_creacion`)
- Limites `varchar` distintos
- Columnas `ENUM` con valores distintos

### Diagnostico de diferencias

```php
// compare-schema.php (borrar despues)
$prod = new PDO('mysql:host=127.0.0.1;dbname=bd_pda', 'root', '');
$test = new PDO('mysql:host=127.0.0.1;dbname=bd_pda_test', 'root', '');
foreach ($prod->query('DESCRIBE proveedor')->fetchAll(PDO::FETCH_ASSOC) as $r)
    echo "PROD: {$r['Field']} | {$r['Type']}\n";
foreach ($test->query('DESCRIBE proveedor')->fetchAll(PDO::FETCH_ASSOC) as $r)
    echo "TEST: {$r['Field']} | {$r['Type']}\n";
```

### Regla: el esquema de prueba debe ser identico al de produccion

Usar `ALTER TABLE ... CHANGE` para sincronizar:
```sql
ALTER TABLE bd_pda_test.proveedor
    CHANGE nombre         nombre          varchar(25) NOT NULL,
    CHANGE identificacion identificacion  varchar(10) NOT NULL,
    CHANGE fecha_creacion fecha_cracion   datetime    DEFAULT NULL; -- typo real de prod
```

---

## 16. Seeding de Datos de Referencia en bootstrap.php

### Cuando es necesario

Los tests de integracion pueden requerir datos de referencia (FK) que no existen en la BD de prueba vacia:
- `idcategoria = 1` en tabla `categoria` (requerido por `insertProducto`)
- Un proveedor activo para tests de compra

### Patron: INSERT IGNORE en bootstrap.php

Agregar al final de `tests/bootstrap.php`, DESPUES de cargar `config.php`:

```php
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME_GENERAL . ';charset=utf8',
        DB_USERNAME, DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT]
    );

    // Datos de referencia minimos (idempotente: INSERT IGNORE no falla si ya existen)
    $pdo->exec("INSERT IGNORE INTO categoria (idcategoria, nombre, estatus)
                VALUES (1, 'General', 'activo')");

    // Proveedor semilla solo si no hay ninguno activo
    $count = $pdo->query("SELECT COUNT(*) FROM proveedor WHERE estatus = 'activo'")->fetchColumn();
    if ((int)$count === 0) {
        $pdo->exec("INSERT IGNORE INTO proveedor
            (nombre, apellido, identificacion, fecha_nacimiento,
             direccion, correo_electronico, estatus, telefono_principal, genero)
            VALUES ('Proveedor','Seed','SEED000001','1990-01-01',
                    'Av. Seed','seed@test.com','activo','04241234567','MASCULINO')");
    }
    unset($pdo);
} catch (Exception $e) {
    // MySQL no disponible: RequiresDatabase salta los tests automaticamente
}
```

**Reglas del seeding:**
- Siempre `INSERT IGNORE` (idempotente entre corridas)
- Usar `PDO::ERRMODE_SILENT` para que un error de conexion no rompa toda la suite
- No hacer `TRUNCATE` ni `DELETE` en bootstrap: los datos de fixture de cada test se limpian en `tearDown()`

---

## 17. Datos de Localidad Venezolana para Fixtures

Todos los datos de prueba deben usar formatos y valores propios de Venezuela.

### Identificaciones (cedula / RIF)

```php
// Cedula venezolana: prefijo + 7-8 digitos (varchar(10) → sin guion si hay limite)
'V' . rand(1000000, 9999999)    // 8 chars: persona natural venezolana
'E' . rand(1000000, 9999999)    // 8 chars: extranjero residente
'J' . rand(1000000, 9999999)    // 8 chars: empresa / juridico

// Si el campo es varchar(10) y se necesita unico por corrida:
'V' . substr(uniqid(), -7)      // V + 7 hex = 8 chars (dentro de varchar(10))
```

### Telefonos venezolanos

| Operadora | Prefijo | Ejemplo completo |
|---|---|---|
| Movistar | 0414 / 0424 | `04241234567` |
| Digitel | 0412 | `04121234567` |
| Movilnet | 0416 / 0426 | `04161234567` |

Formato en fixture: `'04' . ['12','14','16','24','26'][rand(0,4)] . rand(1000000,9999999)`

### Ciudades y estados venezolanos

```php
// Direcciones de prueba por ciudad
'Av. Bolivar, Maracay, Aragua'
'Urb. La Isabelica, Valencia, Carabobo'
'Calle 72, Maracaibo, Zulia'
'Av. Universidad, Caracas, Dtto. Capital'
'Calle Comercio, Barquisimeto, Lara'
'Av. Las Delicias, Maracay, Aragua'

// Para campos varchar(30) (limite estricto)
'Av. Bolivar, Maracay'      // 21 chars ✓
'Urb. La Isabelica, Vzla'   // 24 chars ✓
```

### Nombres venezolanos comunes

```php
// Masculinos: Carlos, Luis, Juan, Miguel, Jose, Rafael, Orlando, Eduardo
// Femeninos:  Maria, Ana, Carmen, Lucia, Gabriela, Valentina, Andreina
// Apellidos:  Mendoza, Rodriguez, Gonzalez, Perez, Garcia, Fernandez, Ramirez

'nombre'  => 'Carlos',
'apellido' => 'Mendoza',
```

### Correos de prueba

```php
// Formato sin acentos, dominio generico
'cmendoza@test.com'
'jrodriguez@correo.ve'
// Para unicidad por corrida (si hay constraint UNIQUE en correo):
'e' . substr(uniqid(), 0, 8) . '@gmail.com'  // 17 chars, dentro de varchar(30)
```

### Ejemplo completo de fixture venezolano

```php
private function datosFixtureVenezolano(string $identificacion): array
{
    return [
        'nombre'             => 'Carlos',
        'apellido'           => 'Mendoza',
        'identificacion'     => $identificacion,          // caller provee ID unico
        'fecha_nacimiento'   => '1988-03-15',
        'direccion'          => 'Av. Bolivar, Maracay',   // 21 chars ≤ varchar(30)
        'correo_electronico' => 'e' . substr(uniqid(), 0, 8) . '@gmail.com',
        'telefono_principal' => '04241550001',
        'genero'             => 'MASCULINO',
        'observaciones'      => 'Fixture test Venezuela',
    ];
}
```

### DataProvider con prefijos venezolanos

```php
public static function providerPrefijosVenezolanos(): array
{
    $n = substr(uniqid(), -7); // 7 chars hex, unico por corrida
    return [
        'Cedula venezolano' => ['V' . $n],   // V + 7 = 8 chars
        'Cedula extranjero' => ['E' . $n],   // E + 7 = 8 chars
        'RIF juridico'      => ['J' . $n],   // J + 7 = 8 chars
    ];
}
```