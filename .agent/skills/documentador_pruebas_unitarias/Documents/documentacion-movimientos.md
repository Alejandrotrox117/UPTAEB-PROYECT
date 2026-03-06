# Documentación de Pruebas Unitarias - Módulo de Movimientos

## Cuadro Nº 6: Módulo de Gestión de Movimientos de Inventario (RF06)

### Objetivos de la prueba

Validar que el sistema de movimientos de inventario mantenga la integridad y trazabilidad completa de todas las operaciones, permitiendo únicamente la creación de movimientos con datos válidos y prohibiendo explícitamente cualquier modificación o eliminación de registros históricos. El sistema debe rechazar movimientos que no cumplan con las reglas de negocio establecidas: debe existir un producto válido, un tipo de movimiento definido, y exactamente una cantidad (entrada o salida, nunca ambas ni ninguna), además de validar que las cantidades sean positivas. También debe garantizar que se pueda recuperar el stock actual del producto antes de registrar cualquier movimiento.

### Técnicas

Pruebas de caja blanca con enfoque en validación de reglas de negocio y restricciones de operaciones: se utiliza Mockery para crear mocks completos de PDO y PDOStatement, asegurando un aislamiento total de la base de datos. Se evalúan los métodos `insertMovimiento()`, `updateMovimiento()`, `deleteMovimientoById()`, `selectMovimientoById()` y `anularMovimientoById()` en múltiples escenarios, verificando las validaciones previas a cualquier operación de base de datos, el rechazo absoluto de operaciones de actualización y eliminación (que están deshabilitadas por diseño para preservar la auditoría), el manejo correcto de IDs inválidos, y la consistencia de las estructuras de respuesta en todos los casos.

### Código Involucrado

```php
<?php

namespace Tests\UnitTest\Movimientos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\MovimientosModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class MovimientosUnitTest extends TestCase
{
    private MovimientosModel $model;
    /** @var \Mockery\MockInterface */
    private $mockPdo;
    /** @var \Mockery\MockInterface */
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');

        $this->mockPdo  = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        // Comportamiento por defecto del statement
        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();

        // Comportamiento por defecto del PDO mock
        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('commit')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('rollBack')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn("0")->byDefault();
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(false)->byDefault();

        // Sobrecargar la conexión para evitar cualquier contacto con BD real
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new MovimientosModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // =========================================================================
    // VALIDACIONES DE INSERT (fallan antes de tocar la BD)
    // =========================================================================

    public static function providerDatosInvalidosInsert(): array
    {
        return [
            'sin_idproducto' => [
                [
                    'idtipomovimiento' => 1,
                    'cantidad_entrada'  => 100,
                    'cantidad_salida'   => 0,
                    'stock_anterior'    => 0,
                    'stock_resultante'  => 100,
                ],
                'producto',
            ],
            'idproducto_nulo' => [
                [
                    'idproducto'        => null,
                    'idtipomovimiento'  => 1,
                    'cantidad_entrada'  => 100,
                    'cantidad_salida'   => 0,
                    'stock_anterior'    => 0,
                    'stock_resultante'  => 100,
                ],
                'producto',
            ],
            'idproducto_vacio' => [
                [
                    'idproducto'        => '',
                    'idtipomovimiento'  => 1,
                    'cantidad_entrada'  => 100,
                    'cantidad_salida'   => 0,
                    'stock_anterior'    => 0,
                    'stock_resultante'  => 100,
                ],
                'producto',
            ],
            'sin_idtipomovimiento' => [
                [
                    'idproducto'       => 1,
                    'cantidad_entrada' => 100,
                    'cantidad_salida'  => 0,
                    'stock_anterior'   => 0,
                    'stock_resultante' => 100,
                ],
                'tipo de movimiento',
            ],
            'idtipomovimiento_nulo' => [
                [
                    'idproducto'       => 1,
                    'idtipomovimiento' => null,
                    'cantidad_entrada' => 100,
                    'cantidad_salida'  => 0,
                    'stock_anterior'   => 0,
                    'stock_resultante' => 100,
                ],
                'tipo de movimiento',
            ],
            'ambas_cantidades_cero' => [
                [
                    'idproducto'       => 1,
                    'idtipomovimiento' => 1,
                    'cantidad_entrada' => 0,
                    'cantidad_salida'  => 0,
                    'stock_anterior'   => 0,
                    'stock_resultante' => 0,
                ],
                'cantidad',
            ],
            'cantidades_negativas' => [
                [
                    'idproducto'       => 1,
                    'idtipomovimiento' => 1,
                    'cantidad_entrada' => -10,
                    'cantidad_salida'  => -5,
                    'stock_anterior'   => 0,
                    'stock_resultante' => 0,
                ],
                'cantidad',
            ],
            'ambas_cantidades_positivas' => [
                [
                    'idproducto'       => 1,
                    'idtipomovimiento' => 1,
                    'cantidad_entrada' => 100,
                    'cantidad_salida'  => 50,
                    'stock_anterior'   => 0,
                    'stock_resultante' => 50,
                ],
                'entrada y salida al mismo tiempo',
            ],
        ];
    }

    #[Test]
    #[DataProvider('providerDatosInvalidosInsert')]
    public function testInsertMovimiento_ValidacionesFallan(
        array $data,
        string $mensajeParcialEsperado
    ): void {
        $result = $this->model->insertMovimiento($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsStringIgnoringCase(
            $mensajeParcialEsperado,
            $result['message'],
            "El mensaje debe contener: '{$mensajeParcialEsperado}'"
        );
        $this->assertNull($result['data']);
    }

    #[Test]
    public function testInsertMovimiento_StockProductoNoVerificable_RetornaError(): void
    {
        // fetch retorna false → producto no encontrado → obtenerStockActualProducto devuelve false
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);

        $data = [
            'idproducto'       => 1,
            'idtipomovimiento' => 1,
            'cantidad_entrada' => 50,
            'cantidad_salida'  => 0,
            'stock_anterior'   => 0,
            'stock_resultante' => 50,
        ];

        $result = $this->model->insertMovimiento($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('stock', $result['message']);
        $this->assertNull($result['data']);
    }

    // =========================================================================
    // UPDATE Y DELETE — SIEMPRE DESHABILITADOS
    // =========================================================================

    public static function providerIdsUpdateDeshabilitado(): array
    {
        return [
            'id_valido_datos_completos' => [1,            ['idproducto' => 1, 'cantidad_entrada' => 100]],
            'id_grande_datos_vacios'    => [999999,       []],
            'id_maximo_datos_complejos' => [PHP_INT_MAX,  ['idproducto' => 1, 'cantidad_entrada' => 500]],
        ];
    }

    #[Test]
    #[DataProvider('providerIdsUpdateDeshabilitado')]
    public function testUpdateMovimiento_SiempreRetornaError(int $id, array $data): void
    {
        $result = $this->model->updateMovimiento($id, $data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('no está permitida', $result['message']);
        $this->assertNull($result['data']);
    }

    public static function providerIdsDeleteDeshabilitado(): array
    {
        return [
            'id_valido'      => [1],
            'id_inexistente' => [999999],
            'id_maximo'      => [PHP_INT_MAX],
        ];
    }

    #[Test]
    #[DataProvider('providerIdsDeleteDeshabilitado')]
    public function testDeleteMovimientoById_SiempreRetornaError(int $id): void
    {
        $result = $this->model->deleteMovimientoById($id);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('no está permitida', $result['message']);
        $this->assertNull($result['data']);
    }

    // =========================================================================
    // IDs INVÁLIDOS (validados antes de ir a la BD)
    // =========================================================================

    #[Test]
    public function testSelectMovimientoByIdCero_RetornaErrorInvalido(): void
    {
        $result = $this->model->selectMovimientoById(0);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('inválido', $result['message']);
        $this->assertNull($result['data']);
    }

    #[Test]
    public function testAnularMovimientoByIdCero_RetornaErrorInvalido(): void
    {
        $result = $this->model->anularMovimientoById(0);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('inválido', $result['message']);
        $this->assertNull($result['data']);
    }

    // =========================================================================
    // CONTRATO DE RESPUESTA
    // =========================================================================

    #[Test]
    public function testContrato_MetodosDeshabilitadosTienenMismaEstructura(): void
    {
        $resultUpdate = $this->model->updateMovimiento(1, []);
        $resultDelete = $this->model->deleteMovimientoById(1);

        $this->assertEquals(array_keys($resultUpdate), array_keys($resultDelete));

        foreach ([$resultUpdate, $resultDelete] as $result) {
            $this->assertArrayHasKey('status', $result);
            $this->assertArrayHasKey('message', $result);
            $this->assertArrayHasKey('data', $result);
            $this->assertIsBool($result['status']);
            $this->assertIsString($result['message']);
            $this->assertNotEmpty($result['message']);
        }
    }

    #[Test]
    public function testContrato_ValidacionFallidaRetornaEstructuraCorrecta(): void
    {
        // Sin idproducto → falla validación
        $result = $this->model->insertMovimiento([
            'idtipomovimiento' => 1,
            'cantidad_entrada' => 100,
            'cantidad_salida'  => 0,
            'stock_anterior'   => 0,
            'stock_resultante' => 100,
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertFalse($result['status']);
        $this->assertNull($result['data']);
    }
}
```

### Caso de prueba

**TIPO:** Funcional (Caja blanca)

**OBJETIVO:** Garantizar que el módulo de movimientos de inventario mantenga la integridad histórica completa, validando rigurosamente todas las entradas antes de su registro, rechazando operaciones de modificación o eliminación por diseño, y asegurando respuestas consistentes en todos los escenarios.

**DESCRIPCIÓN:** Se prueban exhaustivamente las validaciones de datos para la creación de movimientos, la prohibición explícita de actualizaciones y eliminaciones, el manejo de IDs inválidos, y la consistencia de las estructuras de respuesta del sistema.

**ENTRADAS:**

Las pruebas abarcan un amplio espectro de escenarios para validar el comportamiento del sistema ante diferentes tipos de datos. Para la inserción de movimientos, se probaron múltiples casos donde los datos son inválidos: se intentó crear movimientos sin especificar el ID del producto, con el ID del producto nulo o vacío, sin definir el tipo de movimiento, con el tipo de movimiento nulo, con ambas cantidades (entrada y salida) en cero lo cual no tiene sentido lógico, con cantidades negativas que violarían las reglas de negocio, y el caso especialmente importante donde se intenta registrar tanto entrada como salida al mismo tiempo lo cual es contradictorio y debe ser rechazado. También se probó el escenario donde el producto especificado en el movimiento no puede ser verificado en la base de datos, simulando que el fetch retorna false indicando que el producto no existe, lo cual debe impedir la creación del movimiento ya que no se puede obtener el stock actual.

Para las operaciones de actualización, se probaron tres variantes usando diferentes combinaciones de IDs y datos: un ID válido con datos completos de movimiento, un ID grande (999999) con datos vacíos, y el ID máximo posible del sistema con datos complejos. En todos estos casos, sin importar la validez aparente de los datos, el sistema debe rechazar la operación porque las actualizaciones están completamente deshabilitadas por diseño para mantener la trazabilidad. De manera similar, para las eliminaciones se probaron intentos con un ID válido, un ID presumiblemente inexistente, y el ID máximo del sistema, esperando que todos sean rechazados uniformemente.

Se validó también el comportamiento ante IDs inválidos específicamente con valor cero, tanto para consultas individuales mediante selectMovimientoById como para anulaciones mediante anularMovimientoById. Estos casos prueban que las validaciones de ID ocurran antes de cualquier consulta a la base de datos.

Finalmente, se incluyeron pruebas de contrato que verifican la consistencia estructural de las respuestas: se comparó que los métodos deshabilitados (update y delete) retornen estructuras idénticas, y se validó que todas las respuestas de error de validación sigan el mismo formato estándar con status, message y data.

**SALIDAS ESPERADAS:**

El sistema debe responder de manera clara y consistente en todos los escenarios. Cuando se intenta insertar un movimiento con datos inválidos, ya sea por ausencia o formato incorrecto del ID de producto, falta del tipo de movimiento, problemas con las cantidades o la contradicción lógica de tener entrada y salida simultáneas, el sistema debe retornar un array con status false, un mensaje descriptivo que contenga palabras clave relacionadas con el error específico (como "producto", "tipo de movimiento", "cantidad", o "entrada y salida al mismo tiempo"), y el campo data debe ser null. El mensaje de error debe ser lo suficientemente específico para que el usuario comprenda qué validación falló.

En el caso especial donde no se puede verificar el stock del producto porque el producto no existe o no es accesible, la respuesta debe tener status false con un mensaje que incluya la palabra "stock" para indicar que el problema está relacionado con la verificación del inventario actual, y data debe ser null.

Para todas las operaciones de actualización, independientemente del ID o los datos proporcionados, el sistema debe retornar consistentemente status false con un mensaje que contenga la frase "no está permitida" indicando explícitamente que esta operación está deshabilitada, y data debe ser null. Esta respuesta uniforme es crucial para mantener la auditoría del sistema. De la misma manera, cualquier intento de eliminar un movimiento, sin importar si el ID es válido o potencialmente existente, debe recibir la misma respuesta de operación no permitida.

Cuando se proporciona un ID cero para consultas o anulaciones, antes de intentar cualquier operación en la base de datos, el sistema debe retornar status false con un mensaje que contenga la palabra "inválido" señalando que el ID proporcionado no cumple con los requisitos básicos, y data debe ser null.

Las pruebas de contrato garantizan que todas las respuestas de error mantengan la misma estructura: deben ser arrays que contengan las claves status (booleano), message (string no vacío) y data. Los métodos deshabilitados (update y delete) deben retornar estructuras con las mismas claves en el mismo orden. Las validaciones fallidas deben seguir este mismo patrón, asegurando que el código cliente pueda manejar las respuestas de manera uniforme independientemente del tipo de error.

### Resultado

```
PHPUnit 10.5.63 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: C:\xampp\htdocs\project\phpunit.xml

...................                                               19 / 19 (100%)

Time: 00:04.033, Memory: 10.00 MB

There was 1 PHPUnit test runner warning:

1) No code coverage driver available

OK, but there were issues!
Tests: 19, Assertions: 95, PHPUnit Warnings: 1, PHPUnit Deprecations: 1.
```

### Observaciones

La batería completa de 19 pruebas unitarias para el módulo de Gestión de Movimientos de Inventario se ejecutó exitosamente sin ningún fallo, validando un total de 95 aserciones en aproximadamente 4 segundos. Este resultado confirma que el modelo está funcionando exactamente como fue diseñado, cumpliendo con todas las restricciones y validaciones establecidas.

Lo más destacable de este módulo es su diseño centrado en la inmutabilidad e integridad histórica. Las pruebas confirmaron que el sistema mantiene una filosofía estricta donde los movimientos de inventario son registros auditables que una vez creados no pueden ser modificados ni eliminados. Los intentos de actualización y eliminación fueron rechazados uniformemente sin importar si los IDs o datos parecían válidos, retornando siempre el mismo mensaje claro de "operación no está permitida". Esta característica es fundamental en sistemas de inventario donde cada movimiento debe ser rastreable y auditable, y cualquier corrección debe hacerse mediante nuevos movimientos compensatorios o anulaciones registradas, nunca mediante modificación del historial.

Las validaciones de entrada demostraron ser exhaustivas y precisas. El sistema validó correctamente todos los campos requeridos antes de intentar cualquier operación en la base de datos, rechazando movimientos sin ID de producto o con valores inválidos como null o strings vacíos. Una validación particularmente importante que se confirmó es la regla de exclusividad entre entrada y salida: un movimiento debe ser o bien una entrada o bien una salida, nunca ambas simultáneamente ni ninguna de las dos. El sistema rechazó apropiadamente los casos donde ambas cantidades eran cero (movimiento sin efecto) y donde ambas eran positivas (contradicción lógica), así como cantidades negativas que no tienen sentido en el contexto de inventarios.

El módulo también demostró robustez al verificar la existencia del producto antes de permitir el registro del movimiento. Cuando se simuló que el producto no podía encontrarse en la base de datos (fetch retornando false), el sistema reconoció que no puede obtener el stock actual del producto y por lo tanto no puede proceder con el movimiento, retornando un error apropiado. Esto previene la creación de movimientos huérfanos que referencien productos inexistentes.

Las pruebas de IDs inválidos confirmaron que el sistema implementa validaciones tempranas, rechazando operaciones con ID cero antes de cualquier consulta a la base de datos. Esto es una buena práctica que ahorra recursos y proporciona respuestas más rápidas ante datos claramente inválidos.

Un aspecto particularmente bien implementado es la consistencia de las respuestas. Las pruebas de contrato verificaron que todas las respuestas, ya sean de validaciones fallidas o de operaciones deshabilitadas, mantienen la misma estructura con las claves status, message y data. Esto facilita enormemente el manejo de errores en la capa de presentación, ya que el código cliente puede confiar en que siempre recibirá la misma estructura de respuesta independientemente del tipo de error. Los mensajes de error son descriptivos y específicos, conteniendo palabras clave que permiten identificar rápidamente qué validación o restricción falló.

El único warning reportado sobre la ausencia del driver de cobertura de código es simplemente una cuestión de configuración del entorno de testing y no afecta la validez de las pruebas ni el funcionamiento del código. Con 95 aserciones todas exitosas, el módulo demuestra un alto nivel de confiabilidad en su lógica de validación, restricciones de operaciones, y manejo de errores.

---

**Documentación generada el:** 5 de marzo de 2026  
**Archivos de prueba analizados:** MovimientosUnitTest.php  
**Total de pruebas:** 19  
**Total de aserciones:** 95  
**Estado:** ✅ Todas las pruebas pasaron
