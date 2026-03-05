<?php

namespace Tests\IntegrationTest\Pagos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\PagosModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class consultarPagosIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private PagosModel $pagosModel;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->pagosModel = new PagosModel();
    }

    protected function tearDown(): void
    {
        unset($this->pagosModel);
    }

    // -----------------------------------------------------------------------
    // DataProvider
    // -----------------------------------------------------------------------

    public static function providerSelectPagoByIdInexistente(): array
    {
        return [
            'ID muy alto inexistente'  => [999999999, false],
            'ID negativo inexistente'  => [-1, false],
        ];
    }

    // -----------------------------------------------------------------------
    // Tests selectAllPagos
    // -----------------------------------------------------------------------

    #[Test]
    public function testSelectAllPagos_ConBDReal_RetornaEstructuraCorrecta(): void
    {
        $resultado = $this->pagosModel->selectAllPagos();

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertTrue($resultado['status']);
        $this->assertIsArray($resultado['data']);

        // Si hay pagos, verificar que cada registro tenga las columnas esperadas
        if (!empty($resultado['data'])) {
            $primer = $resultado['data'][0];
            $this->assertArrayHasKey('idpago', $primer);
            $this->assertArrayHasKey('monto', $primer);
            $this->assertArrayHasKey('estatus', $primer);
        }
    }

    // -----------------------------------------------------------------------
    // Tests selectPagoById con ID existente
    // -----------------------------------------------------------------------

    #[Test]
    public function testSelectPagoById_IdExistente_RetornaStatusTrueConDatos(): void
    {
        $todos = $this->pagosModel->selectAllPagos();

        if (empty($todos['data'])) {
            $this->markTestSkipped('No hay pagos en bd_pda_test para probar selectPagoById.');
        }

        $idPrueba = $todos['data'][0]['idpago'];
        $resultado = $this->pagosModel->selectPagoById($idPrueba);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertTrue($resultado['status']);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertEquals($idPrueba, $resultado['data']['idpago']);
    }

    // -----------------------------------------------------------------------
    // Tests selectPagoById con IDs inexistentes
    // -----------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerSelectPagoByIdInexistente')]
    public function testSelectPagoById_IdInexistente_RetornaStatusFalse(int $idInexistente, bool $statusEsperado): void
    {
        // selectPagoById solo acepta int positivo en la firma, usamos 999999999 para inexistente
        $idParaUsar = ($idInexistente > 0) ? $idInexistente : 999999999;

        $resultado = $this->pagosModel->selectPagoById($idParaUsar);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertFalse($resultado['status']);
        $this->assertArrayHasKey('message', $resultado);
    }

    // -----------------------------------------------------------------------
    // Tests selectTiposPago
    // -----------------------------------------------------------------------

    #[Test]
    public function testSelectTiposPago_ConBDReal_RetornaEstructuraCorrecta(): void
    {
        $resultado = $this->pagosModel->selectTiposPago();

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertTrue($resultado['status']);
        $this->assertIsArray($resultado['data']);
    }

    // -----------------------------------------------------------------------
    // Tests selectComprasPendientes
    // -----------------------------------------------------------------------

    #[Test]
    public function testSelectComprasPendientes_ConBDReal_RetornaEstructuraCorrecta(): void
    {
        $resultado = $this->pagosModel->selectComprasPendientes();

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertTrue($resultado['status']);
        $this->assertIsArray($resultado['data']);
    }

    // -----------------------------------------------------------------------
    // Tests selectVentasPendientes
    // -----------------------------------------------------------------------

    #[Test]
    public function testSelectVentasPendientes_ConBDReal_RetornaEstructuraCorrecta(): void
    {
        $resultado = $this->pagosModel->selectVentasPendientes();

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertTrue($resultado['status']);
        $this->assertIsArray($resultado['data']);
    }
}
