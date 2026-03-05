<?php

namespace Tests\IntegrationTest\Produccion;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\ProduccionModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class LotesProduccionIntegrationTest extends TestCase
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
    // selectAllLotes
    // ---------------------------------------------------------------

    #[Test]
    public function testSelectAllLotes_RetornaArrayConStatusYData(): void
    {
        $result = $this->model->selectAllLotes();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }

    // ---------------------------------------------------------------
    // selectLoteById
    // ---------------------------------------------------------------

    #[Test]
    public function testSelectLoteById_IdInexistente_RetornaFalse(): void
    {
        $result = $this->model->selectLoteById(99999);

        $this->assertFalse($result);
    }

    // ---------------------------------------------------------------
    // insertLote - validaciones de datos
    // ---------------------------------------------------------------

    public static function providerInsertLoteInvalido(): array
    {
        return [
            'Sin supervisor' => [
                ['volumen_estimado' => 100, 'fecha_jornada' => date('Y-m-d')],
                false,
                'supervisor',
            ],
            'Volumen cero' => [
                ['idsupervisor' => 1, 'volumen_estimado' => 0, 'fecha_jornada' => date('Y-m-d')],
                false,
                'volumen',
            ],
            'Volumen negativo' => [
                ['idsupervisor' => 1, 'volumen_estimado' => -500, 'fecha_jornada' => date('Y-m-d')],
                false,
                'volumen',
            ],
            'Sin fecha jornada' => [
                ['idsupervisor' => 1, 'volumen_estimado' => 100],
                false,
                'fecha',
            ],
            'Fecha inválida' => [
                ['idsupervisor' => 1, 'volumen_estimado' => 100, 'fecha_jornada' => '2024-13-45'],
                false,
                'fecha',
            ],
        ];
    }

    #[Test]
    #[DataProvider('providerInsertLoteInvalido')]
    public function testInsertLote_DatosInvalidos_RetornaStatusFalseConMensaje(
        array $data,
        bool $esperadoStatus,
        string $palabraClave
    ): void {
        $result = $this->model->insertLote($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsString($palabraClave, strtolower($result['message']));
    }

    #[Test]
    public function testInsertLote_DatosCompletos_RetornaArrayConStatus(): void
    {
        $data = [
            'idsupervisor'    => 1,
            'volumen_estimado' => 150,
            'fecha_jornada'   => date('Y-m-d', strtotime('+30 days')),
            'observaciones'   => 'Lote integration test',
        ];

        $result = $this->model->insertLote($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        if ($result['status']) {
            $this->assertArrayHasKey('idlote', $result);
            $this->assertArrayHasKey('numero_lote', $result);
            $this->assertArrayHasKey('operarios_requeridos', $result);
        }
    }

    #[Test]
    public function testInsertLote_NumerosLoteUnicos_DosPorMismaFecha(): void
    {
        $data1 = [
            'idsupervisor'    => 1,
            'volumen_estimado' => 100,
            'fecha_jornada'   => date('Y-m-d', strtotime('+60 days')),
        ];
        $data2 = [
            'idsupervisor'    => 1,
            'volumen_estimado' => 100,
            'fecha_jornada'   => date('Y-m-d', strtotime('+60 days')),
        ];

        $result1 = $this->model->insertLote($data1);
        $result2 = $this->model->insertLote($data2);

        if ($result1['status'] && $result2['status']) {
            $this->assertNotEquals($result1['numero_lote'], $result2['numero_lote']);
        } else {
            $this->markTestSkipped('No se pudieron crear dos lotes para la prueba de unicidad');
        }
    }

    #[Test]
    public function testInsertLoteExcedeCapacidadMaxima_RetornaArray(): void
    {
        $data = [
            'idsupervisor'    => 1,
            'volumen_estimado' => 999999,
            'fecha_jornada'   => date('Y-m-d', strtotime('+90 days')),
        ];

        $result = $this->model->insertLote($data);

        $this->assertIsArray($result);
        if (!$result['status']) {
            $this->assertStringContainsString('capacidad', strtolower($result['message']));
        }
    }

    // ---------------------------------------------------------------
    // iniciarLoteProduccion
    // ---------------------------------------------------------------

    #[Test]
    public function testIniciarLoteProduccion_IdInexistente_RetornaStatusFalse(): void
    {
        $result = $this->model->iniciarLoteProduccion(99999);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
    }

    #[Test]
    public function testIniciarLoteProduccion_LoteRecienCreado_RetornaArrayConStatus(): void
    {
        $dataLote = [
            'idsupervisor'    => 1,
            'volumen_estimado' => 200,
            'fecha_jornada'   => date('Y-m-d', strtotime('+120 days')),
        ];
        $loteCreado = $this->model->insertLote($dataLote);

        if (!$loteCreado['status']) {
            $this->markTestSkipped('No se pudo crear el lote de prueba para inicio');
        }

        $result = $this->model->iniciarLoteProduccion($loteCreado['idlote']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
    }

    #[Test]
    public function testIniciarLoteProduccion_DobleInicio_SegundoRetornaStatusFalse(): void
    {
        $dataLote = [
            'idsupervisor'    => 1,
            'volumen_estimado' => 200,
            'fecha_jornada'   => date('Y-m-d', strtotime('+150 days')),
        ];
        $loteCreado = $this->model->insertLote($dataLote);

        if (!$loteCreado['status']) {
            $this->markTestSkipped('No se pudo crear el lote de prueba para doble inicio');
        }

        $this->model->iniciarLoteProduccion($loteCreado['idlote']);
        $segundoInicio = $this->model->iniciarLoteProduccion($loteCreado['idlote']);

        $this->assertFalse($segundoInicio['status']);
    }

    // ---------------------------------------------------------------
    // cerrarLoteProduccion
    // ---------------------------------------------------------------

    #[Test]
    public function testCerrarLoteProduccion_IdInexistente_RetornaStatusFalse(): void
    {
        $result = $this->model->cerrarLoteProduccion(99999);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    #[Test]
    public function testCerrarLoteProduccion_LoteIniciadoYCerrado_EstadoFinalizado(): void
    {
        $dataLote = [
            'idsupervisor'    => 1,
            'volumen_estimado' => 200,
            'fecha_jornada'   => date('Y-m-d', strtotime('+180 days')),
        ];
        $loteCreado = $this->model->insertLote($dataLote);

        if (!$loteCreado['status']) {
            $this->markTestSkipped('No se pudo crear el lote para la prueba de cierre');
        }

        $this->model->iniciarLoteProduccion($loteCreado['idlote']);
        $result = $this->model->cerrarLoteProduccion($loteCreado['idlote']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);

        if ($result['status']) {
            $loteActualizado = $this->model->selectLoteById($loteCreado['idlote']);
            if ($loteActualizado) {
                $this->assertEquals('FINALIZADO', $loteActualizado['estatus_lote']);
                $this->assertNotNull($loteActualizado['fecha_fin_real']);
            }
        }
    }

    #[Test]
    public function testCerrarLoteProduccion_DoubleCierre_SegundoRetornaStatusFalse(): void
    {
        $dataLote = [
            'idsupervisor'    => 1,
            'volumen_estimado' => 200,
            'fecha_jornada'   => date('Y-m-d', strtotime('+210 days')),
        ];
        $loteCreado = $this->model->insertLote($dataLote);

        if (!$loteCreado['status']) {
            $this->markTestSkipped('No se pudo crear el lote para doble cierre');
        }

        $this->model->iniciarLoteProduccion($loteCreado['idlote']);
        $this->model->cerrarLoteProduccion($loteCreado['idlote']);
        $segundoCierre = $this->model->cerrarLoteProduccion($loteCreado['idlote']);

        $this->assertFalse($segundoCierre['status']);
        $this->assertStringContainsString('finalizado', strtolower($segundoCierre['message']));
    }
}
