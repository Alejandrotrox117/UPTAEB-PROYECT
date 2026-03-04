<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use App\Models\MovimientosModel;

require_once __DIR__ . '/../Traits/RequiresDatabase.php';

class TestMovimientosModelUnit extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private MovimientosModel $model;

    protected function setUp(): void
    {
        $this->model = new MovimientosModel();
    }

    #[Test]
    #[Group('unit')]
    #[DataProvider('idsParaUpdateDeshabilitadoProvider')]
    public function testUpdateMovimientoSiempreRetornaError(
        int $id,
        array $data
    ): void {
        $result = $this->model->updateMovimiento($id, $data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('no está permitida', $result['message']);
        $this->assertNull($result['data']);
    }

    public static function idsParaUpdateDeshabilitadoProvider(): array
    {
        return [
            'id_valido_datos_completos' => [
                1,
                ['idproducto' => 1, 'cantidad_entrada' => 100, 'stock_anterior' => 50],
            ],
            'id_grande_datos_vacios' => [
                999999,
                [],
            ],
            'id_uno_datos_nulos' => [
                1,
                ['campo' => null],
            ],
            'id_maximo_datos_complejos' => [
                PHP_INT_MAX,
                ['idproducto' => 1, 'idtipomovimiento' => 1, 'cantidad_entrada' => 500.50],
            ],
        ];
    }

    #[Test]
    #[Group('unit')]
    #[DataProvider('idsParaDeleteDeshabilitadoProvider')]
    public function testDeleteMovimientoSiempreRetornaError(int $id): void
    {
        $result = $this->model->deleteMovimientoById($id);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('no está permitida', $result['message']);
        $this->assertNull($result['data']);
    }

    public static function idsParaDeleteDeshabilitadoProvider(): array
    {
        return [
            'id_valido' => [1],
            'id_inexistente' => [999999],
            'id_maximo' => [PHP_INT_MAX],
        ];
    }

    #[Test]
    #[Group('unit')]
    public function testSelectMovimientoByIdCeroRetornaErrorSinBD(): void
    {
        $result = $this->model->selectMovimientoById(0);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('inválido', $result['message']);
        $this->assertNull($result['data']);
    }

    #[Test]
    #[Group('unit')]
    public function testAnularMovimientoByIdCeroRetornaErrorSinBD(): void
    {
        $result = $this->model->anularMovimientoById(0);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('inválido', $result['message']);
        $this->assertNull($result['data']);
    }

    #[Test]
    #[Group('unit')]
    public function testUpdateYDeleteRetornanMensajesConsistentes(): void
    {
        $resultUpdate = $this->model->updateMovimiento(1, []);
        $resultDelete = $this->model->deleteMovimientoById(1);

        $this->assertEquals(
            array_keys($resultUpdate),
            array_keys($resultDelete),
            'Ambos métodos deshabilitados deben retornar la misma estructura'
        );

        $this->assertStringContainsStringIgnoringCase('no está permitida', $resultUpdate['message']);
        $this->assertStringContainsStringIgnoringCase('no está permitida', $resultDelete['message']);
    }

    #[Test]
    #[DataProvider('datosInvalidosMovimientoProvider')]
    public function testInsertMovimientoValidacionesFallan(
        array $data,
        string $mensajeEsperadoContiene
    ): void {
        $result = $this->model->insertMovimiento($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsStringIgnoringCase(
            $mensajeEsperadoContiene,
            $result['message'],
            "Mensaje esperado que contenga: '{$mensajeEsperadoContiene}'"
        );
    }

    public static function datosInvalidosMovimientoProvider(): array
    {
        return [
            'rama1_sin_producto' => [
                [
                    'idtipomovimiento' => 1,
                    'cantidad_entrada' => 100,
                    'cantidad_salida' => 0,
                    'stock_anterior' => 0,
                    'stock_resultante' => 100,
                    'observaciones' => 'Sin producto',
                ],
                'producto',
            ],
            'rama1_producto_nulo' => [
                [
                    'idproducto' => null,
                    'idtipomovimiento' => 1,
                    'cantidad_entrada' => 100,
                    'cantidad_salida' => 0,
                    'stock_anterior' => 0,
                    'stock_resultante' => 100,
                ],
                'producto',
            ],
            'rama1_producto_vacio' => [
                [
                    'idproducto' => '',
                    'idtipomovimiento' => 1,
                    'cantidad_entrada' => 100,
                    'cantidad_salida' => 0,
                    'stock_anterior' => 0,
                    'stock_resultante' => 100,
                ],
                'producto',
            ],
            'rama2_sin_tipo_movimiento' => [
                [
                    'idproducto' => 1,
                    'cantidad_entrada' => 100,
                    'cantidad_salida' => 0,
                    'stock_anterior' => 0,
                    'stock_resultante' => 100,
                ],
                'tipo de movimiento',
            ],
            'rama2_tipo_nulo' => [
                [
                    'idproducto' => 1,
                    'idtipomovimiento' => null,
                    'cantidad_entrada' => 100,
                    'cantidad_salida' => 0,
                    'stock_anterior' => 0,
                    'stock_resultante' => 100,
                ],
                'tipo de movimiento',
            ],
            'rama3_ambas_cantidades_cero' => [
                [
                    'idproducto' => 1,
                    'idtipomovimiento' => 1,
                    'cantidad_entrada' => 0,
                    'cantidad_salida' => 0,
                    'stock_anterior' => 0,
                    'stock_resultante' => 0,
                ],
                'cantidad',
            ],
            'rama3_cantidades_negativas' => [
                [
                    'idproducto' => 1,
                    'idtipomovimiento' => 1,
                    'cantidad_entrada' => -10,
                    'cantidad_salida' => -5,
                    'stock_anterior' => 0,
                    'stock_resultante' => 0,
                ],
                'cantidad',
            ],
            'rama4_ambas_cantidades_positivas' => [
                [
                    'idproducto' => 1,
                    'idtipomovimiento' => 1,
                    'cantidad_entrada' => 100,
                    'cantidad_salida' => 50,
                    'stock_anterior' => 0,
                    'stock_resultante' => 50,
                ],
                'entrada y salida al mismo tiempo',
            ],
        ];
    }

    #[Test]
    public function testInsertMovimientoStockDesincronizado(): void
    {
        $this->requireDatabase();
        $data = [
            'idproducto' => 1,
            'idtipomovimiento' => 1,
            'cantidad_entrada' => 50,
            'cantidad_salida' => 0,
            'stock_anterior' => 999999.99,
            'stock_resultante' => 1000049.99,
            'observaciones' => 'Test stock desincronizado',
        ];

        $result = $this->model->insertMovimiento($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('stock', $result['message']);
    }

    #[Test]
    public function testSelectMovimientoByIdInexistente(): void
    {
        $this->requireDatabase();
        $result = $this->model->selectMovimientoById(999999);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
    }

    #[Test]
    #[DataProvider('idsInvalidosMovimientoProvider')]
    #[Group('unit')]
    public function testSelectMovimientoByIdConIdsFalsy(int $idInvalido): void
    {
        $result = $this->model->selectMovimientoById($idInvalido);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('inválido', $result['message']);
    }

    public static function idsInvalidosMovimientoProvider(): array
    {
        return [
            'id_cero' => [0],
        ];
    }

    #[Test]
    public function testSelectAllMovimientosRetornaEstructura(): void
    {
        $this->requireDatabase();
        $result = $this->model->selectAllMovimientos();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
    }

    #[Test]
    public function testSelectAllMovimientosValidaEstructuraDatos(): void
    {
        $this->requireDatabase();
        $result = $this->model->selectAllMovimientos();
        $movimientos = $result['data'] ?? [];

        if (empty($movimientos)) {
            $this->markTestSkipped('No hay movimientos en BD');
        }

        $columnasEsperadas = ['idmovimiento', 'idproducto', 'cantidad_entrada', 'cantidad_salida', 'estatus'];
        foreach ($columnasEsperadas as $col) {
            $this->assertArrayHasKey($col, $movimientos[0],
                "Columna '{$col}' requerida en movimiento"
            );
        }
    }

    #[Test]
    #[DataProvider('criteriosBusquedaVaciosProvider')]
    public function testBuscarMovimientosConCriterioVacioDelegaATodos(string $criterio): void
    {
        $this->requireDatabase();
        $resultBusqueda = $this->model->buscarMovimientos($criterio);
        $resultTodos = $this->model->selectAllMovimientos();

        $this->assertIsArray($resultBusqueda);
        $this->assertArrayHasKey('status', $resultBusqueda);
        $this->assertArrayHasKey('data', $resultBusqueda);

        $this->assertCount(
            count($resultTodos['data']),
            $resultBusqueda['data'],
            'buscarMovimientos("") debe retornar los mismos resultados que selectAllMovimientos()'
        );
    }

    public static function criteriosBusquedaVaciosProvider(): array
    {
        return [
            'string_vacio' => [''],
            'solo_espacios' => ['   '],
            'tab_y_espacios' => ["\t  "],
        ];
    }

    #[Test]
    public function testBuscarMovimientosConCriterioTexto(): void
    {
        $this->requireDatabase();
        $result = $this->model->buscarMovimientos('MOV-');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }

    #[Test]
    public function testGetProductosActivosRetornaEstructura(): void
    {
        $this->requireDatabase();
        $result = $this->model->getProductosActivos();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }

    #[Test]
    public function testGetTiposMovimientoActivosRetornaEstructura(): void
    {
        $this->requireDatabase();
        $result = $this->model->getTiposMovimientoActivos();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }

    #[Test]
    public function testGetTiposMovimientoConEstadisticasRetornaEstructura(): void
    {
        $this->requireDatabase();
        $result = $this->model->getTiposMovimientoConEstadisticas();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
    }

    #[Test]
    #[Group('unit')]
    public function testContratoDeRespuestaMetodosDeshabilitados(): void
    {
        $metodos = [
            fn() => $this->model->updateMovimiento(1, []),
            fn() => $this->model->deleteMovimientoById(1),
        ];

        foreach ($metodos as $metodo) {
            $result = $metodo();

            $this->assertIsArray($result, 'Resultado debe ser array');
            $this->assertArrayHasKey('status', $result);
            $this->assertArrayHasKey('message', $result);
            $this->assertArrayHasKey('data', $result);
            $this->assertIsBool($result['status']);
            $this->assertIsString($result['message']);
            $this->assertNotEmpty($result['message']);
        }
    }

    #[Test]
    #[Group('unit')]
    public function testContratoDeRespuestaValidacionFallida(): void
    {
        $result = $this->model->insertMovimiento([
            'idtipomovimiento' => 1,
            'cantidad_entrada' => 100,
            'cantidad_salida' => 0,
            'stock_anterior' => 0,
            'stock_resultante' => 100,
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertFalse($result['status']);
        $this->assertNull($result['data']);
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }
}

