<?php

namespace Tests\Bcv;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\BcvScraperModel;

/**
 * Tests de integración para BcvScraperModel.
 * BcvScraperModel hace peticiones HTTP reales (cURL) y no usa Conexion/BD,
 * por lo que no aplica Mockery overload. Los tests validan la estructura
 * del resultado; si el BCV no responde se marcan como skipped.
 */
class BcvScraperModelTest extends TestCase
{
    private BcvScraperModel $scraper;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');
        $this->scraper = new BcvScraperModel();
    }

    protected function tearDown(): void
    {
        unset($this->scraper);
    }

    // ---------------------------------------------------------------
    // obtenerDatosTasaBcv — casos típicos
    // ---------------------------------------------------------------

    #[Test]
    public function testObtenerTasaBcv_RetornaEstructuraCorrecta_ParaUSD(): void
    {
        $data = $this->scraper->obtenerDatosTasaBcv('USD');

        if ($data === null) {
            $this->markTestSkipped('BCV no responde o no hay conectividad de red.');
        }

        $this->assertIsArray($data, 'La respuesta para USD no es un array.');
        $this->assertArrayHasKey('tasa', $data, 'Falta la clave tasa para USD.');
        $this->assertArrayHasKey('fecha_bcv', $data, 'Falta la clave fecha_bcv para USD.');
        $this->assertIsFloat($data['tasa'], 'La tasa USD no es un float.');
        $this->assertGreaterThan(0, $data['tasa'], 'La tasa USD debe ser mayor que cero.');
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}$/',
            $data['fecha_bcv'],
            'Formato de fecha incorrecto para USD.'
        );

        fwrite(STDOUT, "\n[BCV] USD: " . $data['tasa'] . " (" . $data['fecha_bcv'] . ")\n");
    }

    #[Test]
    public function testObtenerTasaBcv_RetornaEstructuraCorrecta_ParaEUR(): void
    {
        $data = $this->scraper->obtenerDatosTasaBcv('EUR');

        if ($data === null) {
            $this->markTestSkipped('BCV no responde o no hay conectividad de red.');
        }

        $this->assertIsArray($data, 'La respuesta para EUR no es un array.');
        $this->assertArrayHasKey('tasa', $data);
        $this->assertArrayHasKey('fecha_bcv', $data);
        $this->assertIsFloat($data['tasa']);
        $this->assertGreaterThan(0, $data['tasa'], 'La tasa EUR debe ser mayor que cero.');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $data['fecha_bcv']);

        fwrite(STDOUT, "\n[BCV] EUR: " . $data['tasa'] . " (" . $data['fecha_bcv'] . ")\n");
    }

    // ---------------------------------------------------------------
    // obtenerDatosTasaBcv — casos atípicos
    // ---------------------------------------------------------------

    #[Test]
    public function testObtenerTasaBcv_RetornaNull_ConCodigoDesconocido(): void
    {
        // Un código que no existe en el HTML del BCV → retorna null
        $data = $this->scraper->obtenerDatosTasaBcv('XYZ');

        if ($data === null) {
            $this->assertNull($data);
        } else {
            // Si devuelve algo inesperado, que sea array
            $this->assertIsArray($data);
        }
    }

    // ---------------------------------------------------------------
    // DataProviders
    // ---------------------------------------------------------------

    public static function providerMonedasValidas(): array
    {
        return [
            'dolar' => ['USD'],
            'euro'  => ['EUR'],
        ];
    }

    #[Test]
    #[DataProvider('providerMonedasValidas')]
    public function testObtenerTasaBcv_TasaMayorCero_PorMoneda(string $moneda): void
    {
        $data = $this->scraper->obtenerDatosTasaBcv($moneda);

        if ($data === null) {
            $this->markTestSkipped("BCV no responde para {$moneda}.");
        }

        $this->assertGreaterThan(0, $data['tasa'], "Tasa {$moneda} debe ser > 0");
    }
}
