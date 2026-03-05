<?php

namespace Tests\IntegrationTest\Produccion;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\ProduccionModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class RegistrosProduccionIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private ProduccionModel $model;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new ProduccionModel();
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }

    // ---------------------------------------------------------------
    // selectAllRegistrosProduccion
    // ---------------------------------------------------------------

    #[Test]
    public function testSelectAllRegistrosProduccion_RetornaArrayConStatusYData(): void
    {
        $result = $this->model->selectAllRegistrosProduccion();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public static function providerFiltrosRegistros(): array
    {
        return [
            'Filtro fechas'          => [['fecha_desde' => date('Y-m-d', strtotime('-30 days')), 'fecha_hasta' => date('Y-m-d')]],
            'Filtro tipo CLASIFICACION' => [['tipo_movimiento' => 'CLASIFICACION']],
            'Filtro tipo EMPAQUE'    => [['tipo_movimiento' => 'EMPAQUE']],
        ];
    }

    #[Test]
    #[DataProvider('providerFiltrosRegistros')]
    public function testSelectAllRegistrosProduccion_ConFiltros_RetornaArrayConData(array $filtros): void
    {
        $result = $this->model->selectAllRegistrosProduccion($filtros);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
    }

    // ---------------------------------------------------------------
    // insertarRegistroProduccion - casos de error
    // ---------------------------------------------------------------

    public static function providerInsertarRegistroError(): array
    {
        return [
            'Lote inexistente' => [
                [
                    'idlote'               => 99999,
                    'idempleado'           => 1,
                    'fecha_jornada'        => date('Y-m-d'),
                    'idproducto_producir'  => 1,
                    'cantidad_producir'    => 100,
                    'idproducto_terminado' => 2,
                    'cantidad_producida'   => 90,
                    'tipo_movimiento'      => 'CLASIFICACION',
                ],
                false,
                'lote',
            ],
            'Empleado inexistente' => [
                [
                    'idlote'               => 1,
                    'idempleado'           => 99999,
                    'fecha_jornada'        => date('Y-m-d'),
                    'idproducto_producir'  => 1,
                    'cantidad_producir'    => 1,
                    'idproducto_terminado' => 2,
                    'cantidad_producida'   => 1,
                    'tipo_movimiento'      => 'CLASIFICACION',
                ],
                false,
                null, // el mensaje puede variar
            ],
        ];
    }

    #[Test]
    #[DataProvider('providerInsertarRegistroError')]
    public function testInsertarRegistroProduccion_CasosError_RetornaStatusFalse(
        array $data,
        bool $esperadoStatus,
        ?string $palabraClave
    ): void {
        $result = $this->model->insertarRegistroProduccion($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        if ($palabraClave !== null) {
            $this->assertStringContainsString($palabraClave, strtolower($result['message']));
        }
    }

    // ---------------------------------------------------------------
    // obtenerRegistrosPorLote
    // ---------------------------------------------------------------

    #[Test]
    public function testObtenerRegistrosPorLote_IdInexistente_RetornaArrayConData(): void
    {
        $result = $this->model->obtenerRegistrosPorLote(99999);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        if ($result['status']) {
            $this->assertEmpty($result['data']);
        }
    }

    // ---------------------------------------------------------------
    // actualizarRegistroProduccion
    // ---------------------------------------------------------------

    #[Test]
    public function testActualizarRegistroProduccion_IdInexistente_RetornaStatusFalse(): void
    {
        $data = [
            'fecha_jornada'        => date('Y-m-d'),
            'cantidad_producida'   => 100,
            'tipo_movimiento'      => 'CLASIFICACION',
            'idproducto_producir'  => 1,
            'cantidad_producir'    => 100,
            'idproducto_terminado' => 2,
            'observaciones'        => 'Test integración',
        ];

        $result = $this->model->actualizarRegistroProduccion(99999, $data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    // ---------------------------------------------------------------
    // eliminarRegistroProduccion
    // ---------------------------------------------------------------

    #[Test]
    public function testEliminarRegistroProduccion_IdInexistente_RetornaStatusFalse(): void
    {
        $result = $this->model->eliminarRegistroProduccion(99999);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    // ---------------------------------------------------------------
    // getRegistroById
    // ---------------------------------------------------------------

    #[Test]
    public function testGetRegistroById_IdInexistente_RetornaStatusFalse(): void
    {
        $result = $this->model->getRegistroById(99999);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
}
