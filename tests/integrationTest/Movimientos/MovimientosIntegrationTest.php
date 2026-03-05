<?php

namespace Tests\IntegrationTest\Movimientos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\MovimientosModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class MovimientosIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private MovimientosModel $model;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new MovimientosModel();
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }

    // =========================================================================
    // SELECT ALL MOVIMIENTOS
    // =========================================================================

    #[Test]
    public function testSelectAllMovimientos_RetornaEstructura(): void
    {
        $result = $this->model->selectAllMovimientos();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
    }

    #[Test]
    public function testSelectAllMovimientos_ValidaColumnasClave(): void
    {
        $result      = $this->model->selectAllMovimientos();
        $movimientos = $result['data'] ?? [];

        if (empty($movimientos)) {
            $this->markTestSkipped('No hay movimientos en la base de datos de pruebas.');
        }

        $columnas = ['idmovimiento', 'idproducto', 'cantidad_entrada', 'cantidad_salida', 'estatus'];
        foreach ($columnas as $col) {
            $this->assertArrayHasKey(
                $col,
                $movimientos[0],
                "La columna '{$col}' debe existir en cada movimiento retornado."
            );
        }
    }

    // =========================================================================
    // SELECT BY ID
    // =========================================================================

    #[Test]
    public function testSelectMovimientoById_IdInexistente_RetornaFalso(): void
    {
        $result = $this->model->selectMovimientoById(999999);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
    }

    #[Test]
    public function testSelectMovimientoById_IdCero_RetornaInvalido(): void
    {
        $result = $this->model->selectMovimientoById(0);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('inválido', $result['message']);
    }

    // =========================================================================
    // BUSCAR MOVIMIENTOS
    // =========================================================================

    public static function providerCriteriosVacios(): array
    {
        return [
            'string_vacio'   => [''],
            'solo_espacios'  => ['   '],
            'tab_y_espacios' => ["\t  "],
        ];
    }

    #[Test]
    #[DataProvider('providerCriteriosVacios')]
    public function testBuscarMovimientos_CriterioVacio_RetornaIgualQueSelectAll(string $criterio): void
    {
        $resultBusqueda = $this->model->buscarMovimientos($criterio);
        $resultTodos    = $this->model->selectAllMovimientos();

        $this->assertIsArray($resultBusqueda);
        $this->assertArrayHasKey('status', $resultBusqueda);
        $this->assertArrayHasKey('data', $resultBusqueda);

        $this->assertCount(
            count($resultTodos['data']),
            $resultBusqueda['data'],
            'buscarMovimientos con criterio vacío debe retornar los mismos registros que selectAllMovimientos.'
        );
    }

    #[Test]
    public function testBuscarMovimientos_CriterioTexto_RetornaEstructura(): void
    {
        $result = $this->model->buscarMovimientos('MOV-');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }

    // =========================================================================
    // LISTAS DE APOYO
    // =========================================================================

    #[Test]
    public function testGetProductosActivos_RetornaEstructura(): void
    {
        $result = $this->model->getProductosActivos();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }

    #[Test]
    public function testGetTiposMovimientoActivos_RetornaEstructura(): void
    {
        $result = $this->model->getTiposMovimientoActivos();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }

    #[Test]
    public function testGetTiposMovimientoConEstadisticas_RetornaEstructura(): void
    {
        $result = $this->model->getTiposMovimientoConEstadisticas();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
    }

    // =========================================================================
    // INSERT — VALIDACIÓN DE STOCK CON BD REAL
    // =========================================================================

    #[Test]
    public function testInsertMovimiento_StockDesincronizado_RetornaError(): void
    {
        // stock_anterior enviado es absurdamente alto y no coincide con el real
        $data = [
            'idproducto'       => 1,
            'idtipomovimiento' => 1,
            'cantidad_entrada' => 50,
            'cantidad_salida'  => 0,
            'stock_anterior'   => 999999.99,
            'stock_resultante' => 1000049.99,
            'observaciones'    => 'Test integración: stock desincronizado',
        ];

        $result = $this->model->insertMovimiento($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('stock', $result['message']);
    }
}
