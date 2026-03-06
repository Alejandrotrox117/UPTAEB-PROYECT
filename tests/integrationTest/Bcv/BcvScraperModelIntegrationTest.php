<?php
declare(strict_types=1);

namespace Tests\IntegrationTest\Bcv;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\BcvScraperModel;

/**
 * Pruebas de Integración — BcvScraperModel
 *
 * Realiza peticiones HTTP reales a https://www.bcv.org.ve/.
 * Si no hay conectividad de red, todos los tests se marcan como SKIPPED.
 * No requiere base de datos (BcvScraperModel no usa Conexion/PDO).
 */
class BcvScraperModelIntegrationTest extends TestCase
{
    private BcvScraperModel $scraper;
    private static bool $bcvDisponible = false;

    public static function setUpBeforeClass(): void
    {
        // Verificar conectividad al BCV una sola vez para toda la clase
        $ch = curl_init('https://www.bcv.org.ve/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        self::$bcvDisponible = ($code >= 200 && $code < 500);
    }

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');
        if (!self::$bcvDisponible) {
            $this->markTestSkipped('BCV no disponible — sin conectividad de red al portal BCV.');
        }
        $this->scraper = new BcvScraperModel();
    }

    protected function tearDown(): void
    {
        unset($this->scraper);
    }

    // ---------------------------------------------------------------
    // DataProviders
    // ---------------------------------------------------------------

    public static function providerMonedasValidas(): array
    {
        return [
            'dolar_USD' => ['USD'],
            'euro_EUR'  => ['EUR'],
        ];
    }

    // ---------------------------------------------------------------
    // Tests
    // ---------------------------------------------------------------

    #[Test]
    #[DataProvider('providerMonedasValidas')]
    public function testObtenerTasaBcv_RetornaEstructuraCorrecta_ConRedReal(string $moneda): void
    {
        $result = $this->scraper->obtenerDatosTasaBcv($moneda);

        if ($result === null) {
            $this->markTestSkipped("BCV respondió pero no devolvió datos para {$moneda}.");
        }

        $this->assertIsArray($result);
        $this->assertArrayHasKey('tasa', $result);
        $this->assertArrayHasKey('fecha_bcv', $result);
        $this->assertIsFloat($result['tasa']);
        $this->assertGreaterThan(0, $result['tasa'], "La tasa {$moneda} debe ser mayor que cero.");
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $result['fecha_bcv']);
    }

    #[Test]
    public function testObtenerTasaBcv_TasaUSD_DentroDeRangoEsperado(): void
    {
        $result = $this->scraper->obtenerDatosTasaBcv('USD');

        if ($result === null) {
            $this->markTestSkipped('BCV no devolvió tasa USD.');
        }

        // Rango razonable para el bolívar en 2026: entre 1 y 2000 Bs/USD
        $this->assertGreaterThan(1.0,   $result['tasa'], 'Tasa USD demasiado baja.');
        $this->assertLessThan(2000.0,   $result['tasa'], 'Tasa USD anormalmente alta.');
    }

    #[Test]
    public function testObtenerTasaBcv_FechaBcv_EsFormatoISO(): void
    {
        $result = $this->scraper->obtenerDatosTasaBcv('USD');

        if ($result === null) {
            $this->markTestSkipped('BCV no devolvió datos USD para verificar fecha.');
        }

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}$/',
            $result['fecha_bcv'],
            'La fecha_bcv debe estar en formato YYYY-MM-DD.'
        );
    }
}
