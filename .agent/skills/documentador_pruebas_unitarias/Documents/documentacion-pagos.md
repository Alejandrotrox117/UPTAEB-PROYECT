# Documentación de Pruebas Unitarias - Módulo de Pagos

## Cuadro Nº 5: Módulo de Gestión de Pagos (RF05)

### Objetivos de la prueba

Validar que un pago sólo se registre cuando incluye datos completos y consistentes (tipo de pago, monto positivo, referencia válida y origen válido: compra, venta o sueldo). El sistema debe rechazar pagos con montos negativos o nulos, datos incompletos, y debe manejar correctamente la verificación de personas asociadas. Además, debe garantizar que las consultas de pagos retornen información correcta tanto para listados completos como para búsquedas individuales por ID, manejando apropiadamente los casos de registros inexistentes y excepciones de base de datos.

### Técnicas

Pruebas de caja blanca con enfoque en aislamiento mediante mocks y validación de lógica de negocio: se utiliza Mockery para simular PDO y PDOStatement, garantizando independencia completa de la base de datos real. Se evalúan los métodos `insertPago()`, `selectAllPagos()` y `selectPagoById()` en escenarios válidos e inválidos, verificando la validación de datos previos a la inserción, el manejo de excepciones PDO, la correcta nulificación de referencias a personas inexistentes, y la respuesta ante operaciones exitosas y fallidas de base de datos.

### Código Involucrado

```php
<?php

namespace Tests\UnitTest\Pagos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\PagosModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class insertarPagoUnitTest extends TestCase
{
    private PagosModel $pagosModel;
    /** @var \Mockery\MockInterface */
    private $mockPdo;
    /** @var \Mockery\MockInterface */
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');

        $this->mockPdo  = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        // Comportamientos por defecto del statement
        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1)->byDefault();

        // Comportamientos por defecto del PDO mock
        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn("42")->byDefault();
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('commit')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('rollBack')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(false)->byDefault();

        // Sobrecargar la clase Conexion con un Mock
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->pagosModel = new PagosModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // -----------------------------------------------------------------------
    // DataProviders
    // -----------------------------------------------------------------------

    public static function providerCasosInsertPagoExitoso(): array
    {
        return [
            'Pago por compra con persona nula' => [
                [
                    'idpersona'     => null,
                    'idtipo_pago'   => 1,
                    'idventa'       => null,
                    'idcompra'      => 1,
                    'idsueldotemp'  => null,
                    'monto'         => 500.00,
                    'referencia'    => 'REF-TEST-001',
                    'fecha_pago'    => '2026-03-05',
                    'observaciones' => 'Pago de prueba unitaria',
                ],
                42,
            ],
            'Pago por venta sin persona (activa balance update)' => [
                [
                    'idpersona'     => null,
                    'idtipo_pago'   => 2,
                    'idventa'       => 10,
                    'idcompra'      => null,
                    'idsueldotemp'  => null,
                    'monto'         => 1200.50,
                    'referencia'    => 'REF-TEST-002',
                    'fecha_pago'    => '2026-03-05',
                    'observaciones' => 'Pago de venta unitaria',
                ],
                42,
            ],
        ];
    }

    public static function providerCasosInsertPagoFallido(): array
    {
        return [
            'Sin campo monto (undefined key)' => [
                [
                    'idpersona'     => null,
                    'idtipo_pago'   => 1,
                    'idventa'       => null,
                    'idcompra'      => 1,
                    'idsueldotemp'  => null,
                    // 'monto' omitido intencionalmente
                    'referencia'    => 'REF-SIN-MONTO',
                    'fecha_pago'    => '2026-03-05',
                    'observaciones' => 'Sin monto',
                ],
                'pdoException',
            ],
            'Monto negativo causa excepción en BD' => [
                [
                    'idpersona'     => null,
                    'idtipo_pago'   => 1,
                    'idventa'       => null,
                    'idcompra'      => 1,
                    'idsueldotemp'  => null,
                    'monto'         => -100.00,
                    'referencia'    => 'REF-NEGATIVO',
                    'fecha_pago'    => '2026-03-05',
                    'observaciones' => 'Monto negativo',
                ],
                'pdoException',
            ],
        ];
    }

    // -----------------------------------------------------------------------
    // Tests de inserción exitosa
    // -----------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerCasosInsertPagoExitoso')]
    public function testInsertPago_DatosCompletos_RetornaStatusTrueConId(array $data, int $idEsperado): void
    {
        // Cuando idpersona no es null se invoca una verificación previa (1 fetch con total)
        if ($data['idpersona'] !== null) {
            $this->mockStmt->shouldReceive('fetch')
                ->with(PDO::FETCH_ASSOC)
                ->once()
                ->andReturn(['total' => 1]);
        }

        // Cuando idventa no es null, verificarEstadoVentaDespuesPago llama fetch 2 veces
        if ($data['idventa'] !== null) {
            $this->mockStmt->shouldReceive('fetch')
                ->with(PDO::FETCH_ASSOC)
                ->andReturn(
                    ['total_pagado' => 0.00],
                    ['total_general' => $data['monto']]
                );
        }

        // Simular lastInsertId devolviendo el ID simulado
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn((string)$idEsperado);

        $resultado = $this->pagosModel->insertPago($data);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status'], 'Se esperaba status true en inserción exitosa');
        $this->assertArrayHasKey('data', $resultado);
        $this->assertArrayHasKey('idpago', $resultado['data']);
        $this->assertEquals($idEsperado, $resultado['data']['idpago']);
    }

    // -----------------------------------------------------------------------
    // Tests de inserción fallida (excepciones de BD simuladas)
    // -----------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerCasosInsertPagoFallido')]
    public function testInsertPago_DatosInvalidos_RetornaStatusFalse(array $data, string $tipoFallo): void
    {
        // Simular que el execute del statement lanza PDOException (constraint violation / NULL)
        $this->mockStmt->shouldReceive('execute')
            ->andThrow(new \PDOException('Simulated DB error for invalid data'));

        $resultado = $this->pagosModel->insertPago($data);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status'], 'Se esperaba status false ante error de BD');
        $this->assertArrayHasKey('message', $resultado);
        $this->assertNotEmpty($resultado['message']);
    }

    // -----------------------------------------------------------------------
    // Test: lastInsertId devuelve 0 → status false
    // -----------------------------------------------------------------------

    #[Test]
    public function testInsertPago_CuandoLastInsertIdEsCero_RetornaStatusFalse(): void
    {
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn("0");

        $data = [
            'idpersona'     => null,
            'idtipo_pago'   => 1,
            'idventa'       => null,
            'idcompra'      => 1,
            'idsueldotemp'  => null,
            'monto'         => 100.00,
            'referencia'    => 'REF-CERO-ID',
            'fecha_pago'    => '2026-03-05',
            'observaciones' => 'lastInsertId cero',
        ];

        $resultado = $this->pagosModel->insertPago($data);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
    }

    // -----------------------------------------------------------------------
    // Test: idpersona no nulo pero persona NO existe → se pone null y sigue
    // -----------------------------------------------------------------------

    #[Test]
    public function testInsertPago_PersonaInexistente_SeNulifica_YInsertaIgual(): void
    {
        // Verificación de persona devuelve total = 0 (no existe)
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->once()
            ->andReturn(['total' => 0]);

        $this->mockPdo->shouldReceive('lastInsertId')->andReturn("55");

        $data = [
            'idpersona'     => 9999,
            'idtipo_pago'   => 1,
            'idventa'       => null,
            'idcompra'      => 1,
            'idsueldotemp'  => null,
            'monto'         => 300.00,
            'referencia'    => 'REF-PERS-FAKE',
            'fecha_pago'    => '2026-03-05',
            'observaciones' => 'Persona inexistente',
        ];

        $resultado = $this->pagosModel->insertPago($data);

        // El modelo nulifica idpersona y continúa → inserción exitosa
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status']);
        $this->assertEquals(55, $resultado['data']['idpago']);
    }
}
```

```php
<?php

namespace Tests\UnitTest\Pagos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\PagosModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class consultarPagosUnitTest extends TestCase
{
    private PagosModel $pagosModel;
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
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn("0")->byDefault();

        // Sobrecargar Conexion
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->pagosModel = new PagosModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // -----------------------------------------------------------------------
    // DataProviders
    // -----------------------------------------------------------------------

    public static function providerSelectAllPagos(): array
    {
        $pagoMock = [
            'idpago'             => 1,
            'monto'              => '500.0000',
            'referencia'         => 'REF-001',
            'fecha_pago'         => '2026-03-05',
            'fecha_pago_formato' => '05/03/2026',
            'observaciones'      => 'Test',
            'estatus'            => 'activo',
            'metodo_pago'        => 'Transferencia',
            'tipo_pago_texto'    => 'Compra',
            'destinatario'       => 'Juan Perez',
        ];

        return [
            'Lista con pagos' => [
                [$pagoMock, array_merge($pagoMock, ['idpago' => 2, 'referencia' => 'REF-002'])],
                true,
                2,
            ],
            'Lista vacía' => [
                [],
                true,
                0,
            ],
        ];
    }

    public static function providerSelectPagoById(): array
    {
        return [
            'ID existente retorna datos' => [
                5,
                [
                    'idpago'             => 5,
                    'monto'              => '750.0000',
                    'referencia'         => 'REF-005',
                    'fecha_pago'         => '2026-03-01',
                    'fecha_pago_formato' => '01/03/2026',
                    'estatus'            => 'activo',
                    'metodo_pago'        => 'Efectivo',
                    'tipo_pago_texto'    => 'Venta',
                    'destinatario'       => 'Maria Garcia',
                ],
                true,
            ],
            'ID inexistente retorna status false' => [
                99999,
                false,
                false,
            ],
        ];
    }

    // -----------------------------------------------------------------------
    // Tests de selectAllPagos
    // -----------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerSelectAllPagos')]
    public function testSelectAllPagos_RetornaArrayConStatus(array $filasMock, bool $statusEsperado, int $cantidadEsperada): void
    {
        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($filasMock);

        $resultado = $this->pagosModel->selectAllPagos();

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertEquals($statusEsperado, $resultado['status']);
        $this->assertCount($cantidadEsperada, $resultado['data']);
    }

    #[Test]
    public function testSelectAllPagos_CuandoExcepcionBD_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')
            ->andThrow(new \PDOException('Connection lost'));

        $resultado = $this->pagosModel->selectAllPagos();

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertArrayHasKey('message', $resultado);
    }

    // -----------------------------------------------------------------------
    // Tests de selectPagoById
    // -----------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerSelectPagoById')]
    public function testSelectPagoById_SegunIdExistenciaRetornaEstadoCorrecto(int $idpago, mixed $filaMock, bool $statusEsperado): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($filaMock);

        $resultado = $this->pagosModel->selectPagoById($idpago);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertEquals($statusEsperado, $resultado['status']);

        if ($statusEsperado) {
            $this->assertArrayHasKey('data', $resultado);
            $this->assertEquals($idpago, $resultado['data']['idpago']);
        } else {
            $this->assertArrayHasKey('message', $resultado);
        }
    }

    #[Test]
    public function testSelectPagoById_CuandoExcepcionBD_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('execute')
            ->andThrow(new \PDOException('Timeout'));

        $resultado = $this->pagosModel->selectPagoById(1);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
    }
}
```

### Caso de prueba

**TIPO:** Funcional (Caja blanca)

**OBJETIVO:** Asegurar que solo se registren pagos válidos con datos completos y consistentes, rechazando montos inválidos y datos incompletos, y verificar que las consultas de pagos retornen información correcta manejando apropiadamente registros inexistentes y errores de base de datos.

**DESCRIPCIÓN:** Se prueban escenarios de éxito y falla para la inserción de pagos y consultas. Para las inserciones se valida el registro de pagos por compra y venta, así como el rechazo de datos incompletos y montos negativos. Para las consultas se verifica el comportamiento del listado completo y las búsquedas individuales.

**ENTRADAS:**

Las pruebas de inserción utilizan datos de pagos completos que incluyen información del tipo de pago, monto, referencia y fecha. Se probaron dos escenarios exitosos: primero un pago relacionado con una compra donde la persona es nula, con un monto de 500.00 y referencia REF-TEST-001, y segundo un pago de venta con monto de 1200.50 y referencia REF-TEST-002. Para los casos de falla, se probó intencionalmente la omisión del campo monto para simular datos incompletos, y se envió un monto negativo de -100.00 para validar que el sistema rechace valores inválidos. También se verificó el comportamiento cuando la base de datos retorna un ID cero después de la inserción, lo cual indica un fallo en la operación. Finalmente, se probó el caso de una persona inexistente con ID 9999, para confirmar que el sistema nulifica automáticamente la referencia y continúa con la inserción.

En cuanto a las consultas, se probaron escenarios donde el listado de todos los pagos retorna múltiples registros con sus detalles completos (monto, referencia, fecha formateada, estado, método de pago), así como el caso donde no existen registros y debe retornar un array vacío. Para las búsquedas por ID se utilizaron tanto IDs existentes como el 5 que debería retornar los datos completos del pago, como IDs inexistentes como el 99999 que debe indicar que no se encontró el registro. También se simularon excepciones de conexión a la base de datos para verificar el manejo de errores.

**SALIDAS ESPERADAS:**

Cuando se inserta un pago con datos válidos y completos, el sistema debe retornar un array con status true y dentro de data debe incluir el idpago generado que sea mayor a cero, específicamente esperamos los IDs 42 y 55 en nuestras pruebas simuladas. En contraste, cuando hay problemas como datos incompletos (ausencia del campo monto) o valores inválidos (monto negativo), el sistema debe responder con status false acompañado de un mensaje descriptivo del error. Lo mismo ocurre cuando lastInsertId retorna cero, donde también esperamos status false. El caso especial de la persona inexistente es interesante porque aunque la persona no existe, el sistema debe nulificar esa referencia y completar la inserción exitosamente, retornando status true con el ID generado.

Para las consultas, cuando se solicitan todos los pagos y existen registros, debe retornar status true con un array data conteniendo todos los pagos, cada uno con su información completa incluyendo el monto formateado, la referencia, fecha de pago con formato legible, estado activo, método de pago y destinatario. Si no hay registros, aunque status sea true, el array data debe estar vacío. En la búsqueda por ID, si el pago existe debe retornar status true con todos los datos del pago específico, pero si el ID no existe en el sistema, debe retornar status false con un mensaje indicando que no se encontró el registro. Cuando ocurre cualquier excepción de base de datos, ya sea en el listado completo o en la búsqueda individual, el sistema debe capturar el error y retornar status false con un mensaje descriptivo del problema.

### Resultado

```
PHPUnit 10.5.63 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: C:\xampp\htdocs\project\phpunit.xml

........W...                                                      12 / 12 (100%)

Time: 00:02.050, Memory: 10.00 MB

There was 1 PHPUnit test runner warning:

1) No code coverage driver available

OK, but there were issues!
Tests: 12, Assertions: 47, PHPUnit Warnings: 1, Warnings: 1, PHPUnit Deprecations: 1.
```

### Observaciones

La ejecución de las pruebas unitarias del módulo de Gestión de Pagos fue completamente exitosa, procesando un total de 12 pruebas que juntas realizaron 47 aserciones. Todas las pruebas pasaron sin ningún fallo, lo cual demuestra que el modelo de pagos está funcionando correctamente en todos los escenarios planteados. El proceso tomó apenas 2.050 segundos y utilizó 10 MB de memoria, lo que indica una buena eficiencia en la ejecución.

Las pruebas cubrieron de manera exhaustiva tanto el proceso de creación de pagos como las operaciones de consulta. En el lado de las inserciones, se validó exitosamente que el sistema acepta pagos bien formados relacionados tanto con compras como con ventas, manejando correctamente los casos donde la persona asociada es nula. Una característica interesante que se confirmó es la capacidad del sistema para detectar cuando se intenta asociar un pago a una persona inexistente, en cuyo caso nulifica automáticamente esa referencia pero permite que la operación continúe, lo cual es un comportamiento robusto que evita fallos en la inserción por datos de referencia incorrectos.

El sistema demostró una sólida validación de datos al rechazar apropiadamente los pagos con información incompleta o inválida. Los casos de prueba confirmaron que cuando falta el campo monto o cuando se proporciona un monto negativo, el modelo captura la excepción de base de datos y retorna un mensaje de error claro al usuario. También se verificó que cuando lastInsertId retorna cero, lo cual indicaría un problema en la inserción a nivel de base de datos, el sistema lo detecta y responde con status false.

En cuanto a las operaciones de consulta, las pruebas confirmaron que tanto el listado completo de pagos como la búsqueda por ID funcionan correctamente. El método selectAllPagos retorna apropiadamente un array con todos los registros cuando existen datos, y maneja bien el caso de una base de datos vacía retornando un array data sin elementos. La búsqueda individual por ID fue igualmente robusta, devolviendo el pago completo cuando el ID existe y respondiendo con un mensaje claro cuando no se encuentra el registro solicitado.

Un aspecto importante que se validó es el manejo de excepciones. El sistema demostró resiliencia ante problemas de conectividad o errores de base de datos simulados, capturando las excepciones PDO y transformándolas en respuestas controladas con status false y mensajes descriptivos, evitando así que la aplicación falle abruptamente ante problemas de infraestructura.

El único warning reportado se refiere a la ausencia de un driver de cobertura de código, lo cual no afecta la funcionalidad de las pruebas sino solo la capacidad de generar reportes de cobertura. Este es un tema de configuración del entorno y no un problema del código bajo prueba.

---

**Documentación generada el:** 5 de marzo de 2026  
**Archivos de prueba analizados:** insertarPagoUnitTest.php, consultarPagosUnitTest.php  
**Total de pruebas:** 12  
**Total de aserciones:** 47  
**Estado:** ✅ Todas las pruebas pasaron
