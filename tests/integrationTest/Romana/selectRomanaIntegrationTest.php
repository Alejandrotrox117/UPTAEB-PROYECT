<?php

namespace Tests\IntegrationTest\Romana;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\RomanaModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class selectRomanaIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private RomanaModel $model;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new RomanaModel();
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }

    // ─── Data Providers ──────────────────────────────────────────────────────

    public static function providerCamposEsperados(): array
    {
        return [
            'campo_idromana'       => ['idromana'],
            'campo_peso'           => ['peso'],
            'campo_fecha'          => ['fecha'],
            'campo_estatus'        => ['estatus'],
            'campo_fecha_creacion' => ['fecha_creacion'],
        ];
    }

    // ─── Tests ───────────────────────────────────────────────────────────────

    #[Test]
    public function testSelectAllRomana_RetornaArrayConStatusTrue(): void
    {
        $resultado = $this->model->selectAllRomana();

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertTrue($resultado['status']);
    }

    #[Test]
    public function testSelectAllRomana_RetornaClaveData(): void
    {
        $resultado = $this->model->selectAllRomana();

        $this->assertArrayHasKey('data', $resultado);
        $this->assertIsArray($resultado['data']);
    }

    #[Test]
    #[DataProvider('providerCamposEsperados')]
    public function testSelectAllRomana_CadaRegistroTieneCampoEsperado(string $campo): void
    {
        $resultado = $this->model->selectAllRomana();

        if (empty($resultado['data'])) {
            $this->markTestSkipped('No hay registros en historial_romana para validar la estructura.');
        }

        $primerRegistro = $resultado['data'][0];
        $this->assertArrayHasKey(
            $campo,
            $primerRegistro,
            "El campo '$campo' debe estar presente en cada registro."
        );
    }

    #[Test]
    public function testSelectAllRomana_ResultadosOrdenadosPorIdDesc(): void
    {
        $resultado = $this->model->selectAllRomana();

        if (count($resultado['data']) < 2) {
            $this->markTestSkipped('Se requieren al menos 2 registros para validar el orden.');
        }

        $ids = array_column($resultado['data'], 'idromana');
        $idsOrdenados = $ids;
        rsort($idsOrdenados);

        $this->assertSame(
            $idsOrdenados,
            $ids,
            'Los registros deben estar ordenados por idromana DESC.'
        );
    }
}
