# Documentación de Pruebas Unitarias — Módulo de Ventas

## Cuadro Nº 1: Módulo de Ventas (RF06)

### Objetivos de la prueba

Validar que las operaciones CRUD del módulo de Ventas (consultar, crear, editar y eliminar) se ejecuten correctamente solo cuando los datos de entrada son válidos. El sistema debe rechazar ventas con clientes inexistentes o inactivos, monedas inválidas, descuentos mayores al subtotal, productos inactivos en el detalle, ventas en estados no editables (POR_PAGAR, PAGADA, ANULADA) y transiciones de estado no permitidas, devolviendo mensajes descriptivos del error en cada caso.

---

### Técnicas

Pruebas de caja blanca con aislamiento mediante dobles de prueba (Mockery). Se evalúan los métodos `getVentasDatatable()`, `obtenerVentaPorId()`, `obtenerDetalleVenta()`, `insertVenta()`, `updateVenta()`, `eliminarVenta()` y `cambiarEstadoVenta()` del modelo `VentasModel` en escenarios válidos e inválidos. Se verifican las validaciones de negocio (estado BORRADOR requerido para edición/eliminación, existencia de cliente y moneda, stock de productos, transiciones de estado) y el manejo correcto de excepciones de base de datos mediante `PDOException`.

---

### Código Involucrado

```php
<?php
// =============================================================================
// FILE: tests/unitTest/Ventas/consultarVentasUnitTest.php
// =============================================================================

namespace Tests\UnitTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\VentasModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
class consultarVentasUnitTest extends TestCase
{
    private VentasModel $model;
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
        $this->mockStmt->shouldReceive('fetchColumn')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new VentasModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // -------------------------------------------------------------------------
    // DataProviders
    // -------------------------------------------------------------------------

    public static function providerVentasSimuladas(): array
    {
        return [
            'una_venta' => [[
                [
                    'idventa'       => 1,
                    'nro_venta'     => 'VT000001',
                    'fecha_venta'   => '2026-01-15',
                    'cliente_nombre'=> 'Juan Pérez',
                    'total_general' => 150.00,
                    'balance'       => 150.00,
                    'codigo_moneda' => 'USD',
                    'estatus'       => 'BORRADOR',
                    'observaciones' => 'Test',
                ],
            ]],
            'multiples_ventas' => [[
                ['idventa' => 1, 'nro_venta' => 'VT000001', 'estatus' => 'BORRADOR',  'total_general' => 100.00],
                ['idventa' => 2, 'nro_venta' => 'VT000002', 'estatus' => 'POR_PAGAR', 'total_general' => 200.00],
                ['idventa' => 3, 'nro_venta' => 'VT000003', 'estatus' => 'PAGADA',    'total_general' => 300.00],
            ]],
        ];
    }

    public static function providerIdsInexistentes(): array
    {
        return [
            'id_grande'    => [999999],
            'id_cero'      => [0],
            'id_muy_grande'=> [PHP_INT_MAX],
        ];
    }

    public static function providerVentaCompletaSimulada(): array
    {
        return [
            'venta_borrador' => [[
                'idventa'       => 5,
                'nro_venta'     => 'VT000005',
                'fecha_venta'   => '2026-03-01',
                'idcliente'     => 1,
                'total_general' => 60.00,
                'balance'       => 60.00,
                'estatus'       => 'BORRADOR',
                'observaciones' => 'Consulta unitaria',
            ]],
            'venta_por_pagar' => [[
                'idventa'       => 6,
                'nro_venta'     => 'VT000006',
                'fecha_venta'   => '2026-03-02',
                'idcliente'     => 2,
                'total_general' => 300.00,
                'balance'       => 300.00,
                'estatus'       => 'POR_PAGAR',
                'observaciones' => 'Pendiente de pago',
            ]],
        ];
    }

    // -------------------------------------------------------------------------
    // Tests getVentasDatatable
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerVentasSimuladas')]
    public function testGetVentasDatatable_ConDatos_RetornaArray(array $ventasSimuladas): void
    {
        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($ventasSimuladas);

        $result = $this->model->getVentasDatatable();

        $this->assertIsArray($result);
        $this->assertCount(count($ventasSimuladas), $result);
    }

    #[Test]
    public function testGetVentasDatatable_SinDatos_RetornaArrayVacio(): void
    {
        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn([]);

        $result = $this->model->getVentasDatatable();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function testGetVentasDatatable_ExcepcionEnBD_RetornaArrayVacio(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \PDOException('Connection refused'));

        $result = $this->model->getVentasDatatable();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // -------------------------------------------------------------------------
    // Tests obtenerVentaPorId
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerVentaCompletaSimulada')]
    public function testObtenerVentaPorId_Existente_RetornaDatosVenta(array $ventaSimulada): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($ventaSimulada);

        $result = $this->model->obtenerVentaPorId($ventaSimulada['idventa']);

        $this->assertIsArray($result);
        $this->assertEquals($ventaSimulada['idventa'], $result['idventa']);
        $this->assertEquals($ventaSimulada['nro_venta'], $result['nro_venta']);
        $this->assertEquals($ventaSimulada['estatus'], $result['estatus']);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testObtenerVentaPorId_Inexistente_RetornaFalse(int $id): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(false);

        $result = $this->model->obtenerVentaPorId($id);

        $this->assertFalse($result);
    }

    #[Test]
    public function testObtenerVentaPorId_ExcepcionEnBD_RetornaFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \PDOException('DB error'));

        $result = $this->model->obtenerVentaPorId(1);

        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // Tests obtenerDetalleVenta
    // -------------------------------------------------------------------------

    #[Test]
    public function testObtenerDetalleVenta_ConDetalles_RetornaArray(): void
    {
        $detallesSimulados = [
            [
                'iddetalle_venta'       => 1,
                'idventa'               => 1,
                'idproducto'            => 10,
                'cantidad'              => 3,
                'precio_unitario_venta' => 20.00,
                'subtotal_general'      => 60.00,
                'nombre_producto'       => 'Cartón Corrugado',
            ],
        ];

        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($detallesSimulados);

        $result = $this->model->obtenerDetalleVenta(1);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('idproducto', $result[0]);
        $this->assertArrayHasKey('cantidad', $result[0]);
        $this->assertArrayHasKey('precio_unitario_venta', $result[0]);
    }

    #[Test]
    public function testObtenerDetalleVenta_SinDetalles_RetornaArrayVacio(): void
    {
        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn([]);

        $result = $this->model->obtenerDetalleVenta(999);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function testObtenerDetalleVenta_ExcepcionEnBD_RetornaArrayVacio(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \Exception('Error BD'));

        $result = $this->model->obtenerDetalleVenta(1);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}


// =============================================================================
// FILE: tests/unitTest/Ventas/crearVentaUnitTest.php
// =============================================================================

namespace Tests\UnitTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\VentasModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
class crearVentaUnitTest extends TestCase
{
    private VentasModel $model;
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
        $this->mockStmt->shouldReceive('fetchColumn')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('commit')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('rollBack')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('42')->byDefault();
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(true)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new VentasModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // -------------------------------------------------------------------------
    // Helpers privados
    // -------------------------------------------------------------------------

    private function getDatosVentaValida(): array
    {
        return [
            'fecha_venta'                  => '2026-03-06',
            'idcliente'                    => 1,
            'idmoneda_general'             => 3,
            'subtotal_general'             => 60.00,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general'      => 0,
            'estatus'                      => 'BORRADOR',
            'total_general'                => 60.00,
            'observaciones'                => 'Venta de prueba unitaria',
            'tasa_usada'                   => 1,
        ];
    }

    private function getDetallesVentaValidos(): array
    {
        return [[
            'idproducto'            => 1,
            'cantidad'              => 3,
            'precio_unitario_venta' => 20.00,
            'subtotal_general'      => 60.00,
            'id_moneda_detalle'     => 3,
        ]];
    }

    // -------------------------------------------------------------------------
    // DataProviders
    // -------------------------------------------------------------------------

    public static function providerDatosVentaExitosa(): array
    {
        return [
            'venta_sin_descuento' => [
                [
                    'fecha_venta'                  => '2026-03-06',
                    'idcliente'                    => 1,
                    'idmoneda_general'             => 3,
                    'subtotal_general'             => 60.00,
                    'descuento_porcentaje_general' => 0,
                    'monto_descuento_general'      => 0,
                    'estatus'                      => 'BORRADOR',
                    'total_general'                => 60.00,
                    'observaciones'                => 'Sin descuento',
                    'tasa_usada'                   => 1,
                ],
                [[
                    'idproducto'            => 1,
                    'cantidad'              => 3,
                    'precio_unitario_venta' => 20.00,
                    'subtotal_general'      => 60.00,
                    'id_moneda_detalle'     => 3,
                ]],
            ],
            'venta_con_descuento' => [
                [
                    'fecha_venta'                  => '2026-03-06',
                    'idcliente'                    => 2,
                    'idmoneda_general'             => 3,
                    'subtotal_general'             => 100.00,
                    'descuento_porcentaje_general' => 10,
                    'monto_descuento_general'      => 10.00,
                    'estatus'                      => 'BORRADOR',
                    'total_general'                => 90.00,
                    'observaciones'                => 'Con 10% descuento',
                    'tasa_usada'                   => 1,
                ],
                [[
                    'idproducto'            => 2,
                    'cantidad'              => 10,
                    'precio_unitario_venta' => 10.00,
                    'subtotal_general'      => 100.00,
                    'id_moneda_detalle'     => 3,
                ]],
            ],
        ];
    }

    public static function providerClienteInvalido(): array
    {
        return [
            'cliente_no_existe' => [false,                     'no existe'],
            'cliente_inactivo'  => [['estatus' => 'INACTIVO'], 'inactivo'],
        ];
    }

    // -------------------------------------------------------------------------
    // Tests insertVenta exitosa
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerDatosVentaExitosa')]
    public function testInsertVenta_DatosValidos_RetornaSuccessTrue(array $datos, array $detalles): void
    {
        // Sequence: cliente ACTIVO → nro_venta → producto detalle (activo) → tipo_movimiento → stock producto
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(
                ['estatus' => 'ACTIVO'],
                ['siguiente_numero' => 1],
                ['nombre' => 'Producto Test', 'estatus' => 'activo'],
                ['idtipomovimiento' => 1],
                ['stock' => 100, 'nombre' => 'Producto Test']
            );

        // Moneda existe
        $this->mockStmt->shouldReceive('fetchColumn')->andReturn(1);

        $result = $this->model->insertVenta($datos, $detalles);

        $this->assertIsArray($result);
        $this->assertTrue($result['success'], 'Esperaba success=true. Mensaje: ' . ($result['message'] ?? ''));
        $this->assertArrayHasKey('idventa', $result);
        $this->assertArrayHasKey('nro_venta', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals(42, $result['idventa']);
        $this->assertMatchesRegularExpression('/^VT\d+$/', $result['nro_venta']);
    }

    // -------------------------------------------------------------------------
    // Tests insertVenta — cliente inválido
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerClienteInvalido')]
    public function testInsertVenta_ClienteInvalido_RetornaSuccessFalse($retornoFetch, string $mensajeParcial): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($retornoFetch);

        $this->mockStmt->shouldReceive('fetchColumn')->andReturn(1);

        $result = $this->model->insertVenta($this->getDatosVentaValida(), $this->getDetallesVentaValidos());

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsStringIgnoringCase($mensajeParcial, $result['message']);
    }

    // -------------------------------------------------------------------------
    // Tests insertVenta — moneda inválida
    // -------------------------------------------------------------------------

    #[Test]
    public function testInsertVenta_MonedaNoExiste_RetornaSuccessFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['estatus' => 'ACTIVO']);

        // Moneda no existe
        $this->mockStmt->shouldReceive('fetchColumn')->andReturn(0);

        $result = $this->model->insertVenta($this->getDatosVentaValida(), $this->getDetallesVentaValidos());

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsStringIgnoringCase('moneda', $result['message']);
    }

    // -------------------------------------------------------------------------
    // Tests insertVenta — descuento mayor al subtotal
    // -------------------------------------------------------------------------

    #[Test]
    public function testInsertVenta_DescuentoMayorSubtotal_RetornaSuccessFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['estatus' => 'ACTIVO']);

        $this->mockStmt->shouldReceive('fetchColumn')->andReturn(1);

        $datos = $this->getDatosVentaValida();
        $datos['monto_descuento_general'] = 100.00; // mayor que subtotal (60.00)

        $result = $this->model->insertVenta($datos, $this->getDetallesVentaValidos());

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsStringIgnoringCase('descuento', $result['message']);
    }

    // -------------------------------------------------------------------------
    // Tests insertVenta — producto inactivo en detalle
    // -------------------------------------------------------------------------

    #[Test]
    public function testInsertVenta_ProductoInactivoEnDetalle_RetornaSuccessFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(
                ['estatus' => 'ACTIVO'],         // cliente
                ['siguiente_numero' => 1],        // generarNumeroVenta
                ['nombre' => 'Prod', 'estatus' => 'inactivo']  // producto inactivo
            );

        $this->mockStmt->shouldReceive('fetchColumn')->andReturn(1);

        $result = $this->model->insertVenta($this->getDatosVentaValida(), $this->getDetallesVentaValidos());

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsStringIgnoringCase('activo', $result['message']);
    }

    // -------------------------------------------------------------------------
    // Tests insertVenta — excepción en BD
    // -------------------------------------------------------------------------

    #[Test]
    public function testInsertVenta_ExcepcionEnBD_RetornaSuccessFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \PDOException('Connection lost'));

        $result = $this->model->insertVenta($this->getDatosVentaValida(), $this->getDetallesVentaValidos());

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    // -------------------------------------------------------------------------
    // Tests insertVenta — sin cliente (idcliente = null)
    // -------------------------------------------------------------------------

    #[Test]
    public function testInsertVenta_SinIdCliente_RetornaSuccessFalse(): void
    {
        $datos = $this->getDatosVentaValida();
        $datos['idcliente'] = null;

        $result = $this->model->insertVenta($datos, $this->getDetallesVentaValidos());

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsStringIgnoringCase('cliente', $result['message']);
    }
}


// =============================================================================
// FILE: tests/unitTest/Ventas/editarVentaUnitTest.php
// =============================================================================

namespace Tests\UnitTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\VentasModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
class editarVentaUnitTest extends TestCase
{
    private VentasModel $model;
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
        $this->mockStmt->shouldReceive('fetchColumn')->andReturn(0)->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('commit')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('rollBack')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('42')->byDefault();
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(true)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new VentasModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function getVentaBorradorSimulada(): array
    {
        return [
            'idventa'                      => 10,
            'nro_venta'                    => 'VT000010',
            'fecha_venta'                  => '2026-03-01',
            'idcliente'                    => 1,
            'idmoneda'                     => 3,
            'subtotal_general'             => 60.00,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general'      => 0,
            'estatus'                      => 'BORRADOR',
            'total_general'                => 60.00,
            'balance'                      => 60.00,
            'observaciones'                => 'Venta original',
            'tasa'                         => 1,
        ];
    }

    private function getDatosEdicion(): array
    {
        return [
            'fecha_venta'                  => '2026-03-06',
            'idcliente'                    => 1,
            'idmoneda_general'             => 3,
            'subtotal_general'             => 125.00,
            'descuento_porcentaje_general' => 0,
            'monto_descuento_general'      => 0,
            'estatus'                      => 'BORRADOR',
            'total_general'                => 125.00,
            'observaciones'                => 'Venta editada correctamente.',
            'tasa_usada'                   => 1,
            'detalles'                     => [[
                'idproducto'            => 1,
                'cantidad'              => 5,
                'precio_unitario_venta' => 25.00,
                'subtotal_general'      => 125.00,
                'id_moneda_detalle'     => 3,
            ]],
        ];
    }

    // -------------------------------------------------------------------------
    // DataProviders
    // -------------------------------------------------------------------------

    public static function providerEdicionExitosa(): array
    {
        return [
            'cambio_observacion' => [10, ['observaciones' => 'Nueva observación', 'total_general' => 60.00]],
            'cambio_monto'       => [10, ['total_general' => 200.00, 'subtotal_general' => 200.00, 'observaciones' => 'Monto actualizado']],
        ];
    }

    public static function providerEstadosNoEditables(): array
    {
        return [
            'estado_por_pagar' => [['estatus' => 'POR_PAGAR']],
            'estado_pagada'    => [['estatus' => 'PAGADA']],
            'estado_anulada'   => [['estatus' => 'ANULADA']],
        ];
    }

    // -------------------------------------------------------------------------
    // Tests updateVenta exitosa
    // -------------------------------------------------------------------------

    #[Test]
    public function testUpdateVenta_EnBorrador_RetornaSuccessTrue(): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(
                $this->getVentaBorradorSimulada(),
                ['count' => 1],
                ['nombre' => 'Prod Test', 'estatus' => 'activo'],
                ['idtipomovimiento' => 1],
                ['stock' => 50, 'nombre' => 'Prod Test']
            );

        $result = $this->model->updateVenta(10, $this->getDatosEdicion());

        $this->assertIsArray($result);
        $this->assertTrue($result['success'], 'Esperaba success=true. Mensaje: ' . ($result['message'] ?? ''));
        $this->assertArrayHasKey('idventa', $result);
        $this->assertEquals(10, $result['idventa']);
    }

    #[Test]
    #[DataProvider('providerEdicionExitosa')]
    public function testUpdateVenta_CamposBasicos_RetornaSuccessTrue(int $idventa, array $datosActualizacion): void
    {
        $ventaActual = $this->getVentaBorradorSimulada();
        $ventaActual['idventa'] = $idventa;

        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($ventaActual, ['count' => 1]);

        $result = $this->model->updateVenta($idventa, $datosActualizacion);

        $this->assertIsArray($result);
        $this->assertTrue($result['success'], 'Mensaje: ' . ($result['message'] ?? ''));
    }

    // -------------------------------------------------------------------------
    // Tests updateVenta — venta no existe
    // -------------------------------------------------------------------------

    #[Test]
    public function testUpdateVenta_VentaNoExiste_RetornaSuccessFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(false);

        $result = $this->model->updateVenta(99999, $this->getDatosEdicion());

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsStringIgnoringCase('no existe', $result['message']);
    }

    // -------------------------------------------------------------------------
    // Tests updateVenta — estado no editable
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerEstadosNoEditables')]
    public function testUpdateVenta_EstadoNoEditable_RetornaSuccessFalse(array $ventaConEstado): void
    {
        $ventaSimulada = array_merge($this->getVentaBorradorSimulada(), $ventaConEstado);

        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($ventaSimulada);

        $result = $this->model->updateVenta(10, $this->getDatosEdicion());

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsStringIgnoringCase('BORRADOR', $result['message']);
    }

    // -------------------------------------------------------------------------
    // Tests updateVenta — excepción en BD
    // -------------------------------------------------------------------------

    #[Test]
    public function testUpdateVenta_ExcepcionEnBD_RetornaSuccessFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \PDOException('Server gone away'));

        $result = $this->model->updateVenta(10, $this->getDatosEdicion());

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }
}


// =============================================================================
// FILE: tests/unitTest/Ventas/eliminarVentaUnitTest.php
// =============================================================================

namespace Tests\UnitTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\VentasModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
class eliminarVentaUnitTest extends TestCase
{
    private VentasModel $model;
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
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('commit')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('rollBack')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('0')->byDefault();
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(true)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->model = new VentasModel();
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
            'id_muy_grande' => [999999],
            'id_cero'       => [0],
        ];
    }

    public static function providerEstadosNoEliminables(): array
    {
        return [
            'estado_por_pagar' => ['POR_PAGAR'],
            'estado_pagada'    => ['PAGADA'],
            'estado_anulada'   => ['ANULADA'],
            'estado_inactivo'  => ['INACTIVO'],
        ];
    }

    // -------------------------------------------------------------------------
    // Tests eliminarVenta exitosa
    // -------------------------------------------------------------------------

    #[Test]
    public function testEliminarVenta_EnBorrador_RetornaSuccessTrue(): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(
                ['count' => 1],
                ['estatus' => 'BORRADOR'],
                ['idtipomovimiento' => 1],
                ['stock' => 50, 'nombre' => 'Prod Test']
            );

        $detallesSimulados = [
            ['idproducto' => 1, 'cantidad' => 3, 'precio_unitario_venta' => 20.00],
        ];
        $this->mockStmt->shouldReceive('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($detallesSimulados);

        $result = $this->model->eliminarVenta(1);

        $this->assertIsArray($result);
        $this->assertTrue($result['success'], 'Esperaba success=true. Mensaje: ' . ($result['message'] ?? ''));
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsStringIgnoringCase('desactivada', $result['message']);
    }

    // -------------------------------------------------------------------------
    // Tests eliminarVenta — venta no existe
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testEliminarVenta_Inexistente_RetornaSuccessFalse(int $id): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['count' => 0]);

        $result = $this->model->eliminarVenta($id);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    // -------------------------------------------------------------------------
    // Tests eliminarVenta — estado no permite eliminación
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerEstadosNoEliminables')]
    public function testEliminarVenta_EstadoNoEliminable_RetornaSuccessFalse(string $estado): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(
                ['count' => 1],
                ['estatus' => $estado]
            );

        $result = $this->model->eliminarVenta(1);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    // -------------------------------------------------------------------------
    // Tests eliminarVenta — excepción en BD
    // -------------------------------------------------------------------------

    #[Test]
    public function testEliminarVenta_ExcepcionEnBD_RetornaSuccessFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \PDOException('Disk full'));

        $result = $this->model->eliminarVenta(1);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    // -------------------------------------------------------------------------
    // Tests cambiarEstadoVenta
    // -------------------------------------------------------------------------

    #[Test]
    public function testCambiarEstadoVenta_TransicionValida_RetornaStatusTrue(): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['estatus' => 'BORRADOR']);

        $result = $this->model->cambiarEstadoVenta(1, 'POR_PAGAR');

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
        $this->assertStringContainsStringIgnoringCase('actualizado', $result['message']);
    }

    #[Test]
    public function testCambiarEstadoVenta_TransicionInvalida_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(['estatus' => 'PAGADA']);

        $result = $this->model->cambiarEstadoVenta(1, 'BORRADOR');

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
    }

    #[Test]
    public function testCambiarEstadoVenta_EstadoInvalido_RetornaStatusFalse(): void
    {
        $result = $this->model->cambiarEstadoVenta(1, 'ESTADO_INVENTADO');

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('válido', $result['message']);
    }

    #[Test]
    public function testCambiarEstadoVenta_VentaNoExiste_RetornaStatusFalse(): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(false);

        $result = $this->model->cambiarEstadoVenta(99999, 'POR_PAGAR');

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('encontrada', $result['message']);
    }
}
```

---

### Caso de prueba

**TIPO:** Funcional (Caja blanca)

**OBJETIVO:** Verificar que el módulo de Ventas valide correctamente las reglas de negocio para las operaciones de consulta, creación, edición, eliminación y cambio de estado de ventas, asegurando integridad de datos con clientes, monedas, productos y estados del ciclo de vida de la venta.

**DESCRIPCIÓN:** Se prueban 42 escenarios distribuidos en 4 clases de prueba. Los escenarios incluyen el camino feliz (operación exitosa) y múltiples caminos de error: cliente inexistente o inactivo, moneda no registrada, descuento mayor al subtotal, producto inactivo en el detalle, ventas en estado no editable/eliminable (POR_PAGAR, PAGADA, ANULADA), transiciones de estado inválidas y fallos de conexión a base de datos simulados con `PDOException`.

**ENTRADAS:**

- Venta válida: `idcliente=1`, `total_general=60.00`, `idmoneda_general=3`, estado `BORRADOR`, 1 producto activo con `cantidad=3` y `precio_unitario_venta=20.00`
- Venta con descuento 10%: `subtotal=100.00`, `monto_descuento=10.00`, `total=90.00`
- Escenario inválido de cliente: cliente inexistente (`fetch` → `false`) o con `estatus=INACTIVO`
- Escenario inválido de moneda: `fetchColumn` → `0` (moneda no existe en BD)
- Escenario de descuento excedido: `monto_descuento_general=100.00` sobre `subtotal=60.00`
- Producto inactivo en detalle: `estatus=inactivo` al consultar el producto en la inserción
- IDs inexistentes para consulta/edición/eliminación: `999999`, `0`, `PHP_INT_MAX`
- Estados no editables/eliminables: `POR_PAGAR`, `PAGADA`, `ANULADA`, `INACTIVO`
- Transición de estado inválida: `PAGADA` → `BORRADOR`; estado inventado: `ESTADO_INVENTADO`

**SALIDAS ESPERADAS:**

| Escenario | Resultado esperado |
|---|---|
| Venta con datos válidos (sin/con descuento) | `success=true`, `idventa=42`, `nro_venta` coincide `/^VT\d+$/` |
| Cliente no existe o inactivo | `success=false`, mensaje contiene "no existe" / "inactivo" |
| Moneda no registrada | `success=false`, mensaje contiene "moneda" |
| Descuento mayor al subtotal | `success=false`, mensaje contiene "descuento" |
| Producto inactivo en detalle | `success=false`, mensaje contiene "activo" |
| Venta en estado POR_PAGAR/PAGADA/ANULADA al editar o eliminar | `success=false`, mensaje contiene "BORRADOR" |
| Venta inexistente al consultar/editar/eliminar | `false` o `success=false`, mensaje descriptivo |
| Transición de estado inválida | `status=false`, mensaje de error |
| Excepción PDO en BD | `success=false` (o `status=false`), mensaje incluido |

---

### Resultado

```
PHPUnit 10.5.63 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: C:\xampp\htdocs\project\phpunit.xml

.............WW.......W.......W...........                        42 / 42 (100%)

Time: 00:07.379, Memory: 10.00 MB

consultar Ventas Unit (Tests\UnitTest\Ventas\consultarVentasUnit)
 ✔ GetVentasDatatable ConDatos RetornaArray with una_venta
 ✔ GetVentasDatatable ConDatos RetornaArray with multiples_ventas
 ✔ GetVentasDatatable SinDatos RetornaArrayVacio
 ✔ GetVentasDatatable ExcepcionEnBD RetornaArrayVacio
 ✔ ObtenerVentaPorId Existente RetornaDatosVenta with venta_borrador
 ✔ ObtenerVentaPorId Existente RetornaDatosVenta with venta_por_pagar
 ✔ ObtenerVentaPorId Inexistente RetornaFalse with id_grande
 ✔ ObtenerVentaPorId Inexistente RetornaFalse with id_cero
 ✔ ObtenerVentaPorId Inexistente RetornaFalse with id_muy_grande
 ✔ ObtenerVentaPorId ExcepcionEnBD RetornaFalse
 ✔ ObtenerDetalleVenta ConDetalles RetornaArray
 ✔ ObtenerDetalleVenta SinDetalles RetornaArrayVacio
 ✔ ObtenerDetalleVenta ExcepcionEnBD RetornaArrayVacio

crear Venta Unit (Tests\UnitTest\Ventas\crearVentaUnit)
 ⚠ InsertVenta DatosValidos RetornaSuccessTrue with venta_sin_descuento
 ⚠ InsertVenta DatosValidos RetornaSuccessTrue with venta_con_descuento
 ✔ InsertVenta ClienteInvalido RetornaSuccessFalse with cliente_no_existe
 ✔ InsertVenta ClienteInvalido RetornaSuccessFalse with cliente_inactivo
 ✔ InsertVenta MonedaNoExiste RetornaSuccessFalse
 ✔ InsertVenta DescuentoMayorSubtotal RetornaSuccessFalse
 ✔ InsertVenta ProductoInactivoEnDetalle RetornaSuccessFalse
 ✔ InsertVenta ExcepcionEnBD RetornaSuccessFalse
 ✔ InsertVenta SinIdCliente RetornaSuccessFalse

editar Venta Unit (Tests\UnitTest\Ventas\editarVentaUnit)
 ⚠ UpdateVenta EnBorrador RetornaSuccessTrue
 ✔ UpdateVenta CamposBasicos RetornaSuccessTrue with cambio_observacion
 ✔ UpdateVenta CamposBasicos RetornaSuccessTrue with cambio_monto
 ✔ UpdateVenta VentaNoExiste RetornaSuccessFalse
 ✔ UpdateVenta EstadoNoEditable RetornaSuccessFalse with estado_por_pagar
 ✔ UpdateVenta EstadoNoEditable RetornaSuccessFalse with estado_pagada
 ✔ UpdateVenta EstadoNoEditable RetornaSuccessFalse with estado_anulada
 ✔ UpdateVenta ExcepcionEnBD RetornaSuccessFalse

eliminar Venta Unit (Tests\UnitTest\Ventas\eliminarVentaUnit)
 ⚠ EliminarVenta EnBorrador RetornaSuccessTrue
 ✔ EliminarVenta Inexistente RetornaSuccessFalse with id_muy_grande
 ✔ EliminarVenta Inexistente RetornaSuccessFalse with id_cero
 ✔ EliminarVenta EstadoNoEliminable RetornaSuccessFalse with estado_por_pagar
 ✔ EliminarVenta EstadoNoEliminable RetornaSuccessFalse with estado_pagada
 ✔ EliminarVenta EstadoNoEliminable RetornaSuccessFalse with estado_anulada
 ✔ EliminarVenta EstadoNoEliminable RetornaSuccessFalse with estado_inactivo
 ✔ EliminarVenta ExcepcionEnBD RetornaSuccessFalse
 ✔ CambiarEstadoVenta TransicionValida RetornaStatusTrue
 ✔ CambiarEstadoVenta TransicionInvalida RetornaStatusFalse
 ✔ CambiarEstadoVenta EstadoInvalido RetornaStatusFalse
 ✔ CambiarEstadoVenta VentaNoExiste RetornaStatusFalse

OK, but there were issues!
Tests: 42, Assertions: 126, Warnings: 4, Deprecations: 2.
```

---

### Observaciones

- Se ejecutaron **42 pruebas** con **126 aserciones** en total. Todas pasan exitosamente.
- Se registraron **4 warnings** en los tests de camino feliz de `insertVenta` (×2) y `updateVenta`/`eliminarVenta` (×1 cada uno). Estos warnings corresponden a expectativas de Mockery no satisfechas en la secuencia completa de llamadas al mock (`fetch` encadenado), lo que indica que la secuencia de consultas internas del modelo en el escenario exitoso es más larga de lo simulado. Los tests pasan porque las aserciones principales se cumplen, pero conviene revisar y completar la cadena de mocks para los caminos felices.
- Se registraron **2 deprecations** propias de PHPUnit 10 con PHP 8.2, sin impacto funcional.
- La eliminación de una venta en estado `BORRADOR` utiliza el término `"desactivada"` en el mensaje de respuesta, lo que implica una eliminación lógica (soft delete) y no física.
- El método `cambiarEstadoVenta` maneja su propia máquina de estados con transiciones definidas; estados no reconocidos como `ESTADO_INVENTADO` son rechazados con el mensaje "válido".
