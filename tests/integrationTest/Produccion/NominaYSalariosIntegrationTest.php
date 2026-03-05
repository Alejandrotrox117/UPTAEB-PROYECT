<?php

namespace Tests\IntegrationTest\Produccion;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\ProduccionModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class NominaYSalariosIntegrationTest extends TestCase
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
    // registrarSolicitudPago
    // ---------------------------------------------------------------

    #[Test]
    public function testRegistrarSolicitudPago_SinRegistros_RetornaArrayConStatus(): void
    {
        $result = $this->model->registrarSolicitudPago([]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
    }

    #[Test]
    public function testRegistrarSolicitudPago_IdsInexistentes_RetornaStatusFalse(): void
    {
        $result = $this->model->registrarSolicitudPago([99999, 99998, 99997]);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    // ---------------------------------------------------------------
    // selectPreciosProceso
    // ---------------------------------------------------------------

    #[Test]
    public function testSelectPreciosProceso_RetornaArrayConStatusYData(): void
    {
        $result = $this->model->selectPreciosProceso();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }

    // ---------------------------------------------------------------
    // createPrecioProceso - validaciones y casos exitosos
    // ---------------------------------------------------------------

    public static function providerCreatePrecioProcesoInvalido(): array
    {
        return [
            'Sin tipo_proceso' => [
                ['idproducto' => 1, 'salario_unitario' => 0.50, 'moneda' => 'USD'],
                'inválidos',
            ],
            'Sin idproducto' => [
                ['tipo_proceso' => 'CLASIFICACION', 'salario_unitario' => 0.50, 'moneda' => 'USD'],
                'inválidos',
            ],
            'Salario negativo' => [
                ['tipo_proceso' => 'CLASIFICACION', 'idproducto' => 1, 'salario_unitario' => -0.50, 'moneda' => 'USD'],
                'inválidos',
            ],
            'Salario cero' => [
                ['tipo_proceso' => 'CLASIFICACION', 'idproducto' => 1, 'salario_unitario' => 0, 'moneda' => 'USD'],
                'inválidos',
            ],
            'Tipo proceso inválido' => [
                ['tipo_proceso' => 'TIPO_INVALIDO', 'idproducto' => 1, 'salario_unitario' => 0.50, 'moneda' => 'USD'],
                'inválidos',
            ],
        ];
    }

    #[Test]
    #[DataProvider('providerCreatePrecioProcesoInvalido')]
    public function testCreatePrecioProceso_DatosInvalidos_RetornaStatusFalse(
        array $data,
        string $palabraClave
    ): void {
        $result = $this->model->createPrecioProceso($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public static function providerCreatePrecioProcesoValido(): array
    {
        return [
            'CLASIFICACION con producto 1' => [
                [
                    'tipo_proceso'     => 'CLASIFICACION',
                    'idproducto'       => 1,
                    'salario_unitario' => 0.30,
                    'unidad_base'      => 'KG',
                    'moneda'           => 'USD',
                ],
            ],
            'EMPAQUE con producto 2' => [
                [
                    'tipo_proceso'     => 'EMPAQUE',
                    'idproducto'       => 2,
                    'salario_unitario' => 5.00,
                    'unidad_base'      => 'UNIDAD',
                    'moneda'           => 'USD',
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('providerCreatePrecioProcesoValido')]
    public function testCreatePrecioProceso_DatosValidos_RetornaArrayConStatus(array $data): void
    {
        $result = $this->model->createPrecioProceso($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertIsBool($result['status']);
    }

    // ---------------------------------------------------------------
    // updatePrecioProceso
    // ---------------------------------------------------------------

    #[Test]
    public function testUpdatePrecioProceso_SinCambios_RetornaStatusFalse(): void
    {
        $result = $this->model->updatePrecioProceso(1, []);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    #[Test]
    public function testUpdatePrecioProceso_IdInexistente_RetornaArrayConStatus(): void
    {
        $result = $this->model->updatePrecioProceso(99999, ['salario_unitario' => 0.40]);

        $this->assertIsArray($result);
        $this->assertIsBool($result['status']);
    }

    // ---------------------------------------------------------------
    // deletePrecioProceso
    // ---------------------------------------------------------------

    #[Test]
    public function testDeletePrecioProceso_IdInexistente_RetornaArrayConStatus(): void
    {
        $result = $this->model->deletePrecioProceso(99999);

        $this->assertIsArray($result);
        $this->assertIsBool($result['status']);
    }

    // ---------------------------------------------------------------
    // marcarRegistroComoPagado
    // ---------------------------------------------------------------

    #[Test]
    public function testMarcarRegistroComoPagado_IdInexistente_RetornaStatusFalse(): void
    {
        $result = $this->model->marcarRegistroComoPagado(99999);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    // ---------------------------------------------------------------
    // cancelarRegistroProduccion
    // ---------------------------------------------------------------

    #[Test]
    public function testCancelarRegistroProduccion_IdInexistente_RetornaStatusFalse(): void
    {
        $result = $this->model->cancelarRegistroProduccion(99999);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }
}
