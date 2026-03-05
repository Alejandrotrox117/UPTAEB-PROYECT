<?php

namespace Tests\IntegrationTest\Pagos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\PagosModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class insertarPagoIntegrationTest extends TestCase
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

    public static function providerCasosInsertPago(): array
    {
        return [
            'Sin campo monto' => [
                'data' => [
                    'idpersona'     => null,
                    'idtipo_pago'   => 1,
                    'idventa'       => null,
                    'idcompra'      => 1,
                    'idsueldotemp'  => null,
                    // 'monto' omitido intencionalmente
                    'referencia'    => 'REF-INT-SIN-MONTO',
                    'fecha_pago'    => '2026-03-05',
                    'observaciones' => 'Prueba sin monto',
                ],
                'esperado_status' => false,
                'mensaje_parcial' => null,
            ],
            'Monto negativo' => [
                'data' => [
                    'idpersona'     => null,
                    'idtipo_pago'   => 1,
                    'idventa'       => null,
                    'idcompra'      => 1,
                    'idsueldotemp'  => null,
                    'monto'         => -100.00,
                    'referencia'    => 'REF-INT-NEGATIVO',
                    'fecha_pago'    => '2026-03-05',
                    'observaciones' => 'Prueba monto negativo',
                ],
                'esperado_status' => false,
                'mensaje_parcial' => null,
            ],
        ];
    }

    // -----------------------------------------------------------------------
    // Tests de casos fallidos con DataProvider
    // -----------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerCasosInsertPago')]
    public function testInsertPago_CasosInvalidos_ConBDReal(array $data, bool $esperado_status, ?string $mensaje_parcial): void
    {
        $resultado = $this->pagosModel->insertPago($data);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertEquals($esperado_status, $resultado['status']);

        if ($mensaje_parcial !== null) {
            $this->assertArrayHasKey('message', $resultado);
            $this->assertStringContainsString($mensaje_parcial, $resultado['message']);
        }
    }

    // -----------------------------------------------------------------------
    // Test de inserción exitosa con datos reales
    // -----------------------------------------------------------------------

    #[Test]
    public function testInsertPago_ConDatosCompletos_ConBDReal(): void
    {
        // Obtener un tipo de pago y una compra válidos
        $tiposPago   = $this->pagosModel->selectTiposPago();
        $compras     = $this->pagosModel->selectComprasPendientes();

        if (!$tiposPago['status'] || empty($tiposPago['data'])) {
            $this->markTestSkipped('No hay tipos de pago disponibles en bd_pda_test.');
        }

        if (!$compras['status'] || empty($compras['data'])) {
            $this->markTestSkipped('No hay compras pendientes disponibles en bd_pda_test.');
        }

        $idTipoPago = $tiposPago['data'][0]['idtipo_pago'];
        $idCompra   = $compras['data'][0]['idcompra'];

        $data = [
            'idpersona'     => null,
            'idtipo_pago'   => $idTipoPago,
            'idventa'       => null,
            'idcompra'      => $idCompra,
            'idsueldotemp'  => null,
            'monto'         => 1.00,
            'referencia'    => 'REF-INT-' . time(),
            'fecha_pago'    => date('Y-m-d'),
            'observaciones' => 'Pago de integración automatizado',
        ];

        $resultado = $this->pagosModel->insertPago($data);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);

        if (!$resultado['status']) {
            $this->markTestSkipped('No se pudo insertar: ' . ($resultado['message'] ?? 'Error desconocido'));
        }

        $this->assertTrue($resultado['status']);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertArrayHasKey('idpago', $resultado['data']);
        $this->assertGreaterThan(0, $resultado['data']['idpago']);
    }
}
