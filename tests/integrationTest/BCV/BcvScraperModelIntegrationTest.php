<?php

namespace Tests\IntegrationTest\BCV;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\BcvScraperModel;
use ReflectionClass;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

/**
 * Tests de integración para BcvScraperModel.
 * BcvScraperModel hace peticiones HTTP reales (cURL) y no usa Conexion/BD.
 * Los tests validan la estructura del resultado; si el BCV no responde se marcan como skipped.
 */
class BcvScraperModelIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private BcvScraperModel $scraper;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');
        $this->requireDatabase();
        $this->scraper = new BcvScraperModel();
    }

    protected function tearDown(): void
    {
        unset($this->scraper);
    }

    // ---------------------------------------------------------------
    // obtenerDatosTasaBcv — casos de Exito
    // ---------------------------------------------------------------

    #[Test]
    public function testObtenerTasaBcv_Exito_ParaUSD(): void
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
    }

    #[Test]
    public function testObtenerTasaBcv_Exito_ParaEUR(): void
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
    }

    // ---------------------------------------------------------------
    // obtenerDatosTasaBcv — casos de Falla
    // ---------------------------------------------------------------

    #[Test]
    public function testObtenerTasaBcv_Falla_CodigoMonedaNoSoportado(): void
    {
        // Un código inválido debe retornar null inmediatamente
        $data = $this->scraper->obtenerDatosTasaBcv('XYZ');
        $this->assertNull($data);
    }

    #[Test]
    public function testObtenerTasaBcv_Falla_FalloConexionHtml(): void
    {
        // Forzamos un error de conexión inyectando una URL invalida mediante Reflection
        $reflection = new ReflectionClass($this->scraper);
        $property = $reflection->getProperty('bcvUrl');
        $property->setAccessible(true);
        $property->setValue($this->scraper, 'http://localhost:65432/no-existe'); // Puerto invalido para fallar rapido

        $data = $this->scraper->obtenerDatosTasaBcv('USD');
        $this->assertNull($data);
    }

    // ---------------------------------------------------------------
    // DataProviders
    // ---------------------------------------------------------------

    public static function providerMonedasValidas(): array
    {
        return [
            'dolar' => ['USD'],
            'euro' => ['EUR'],
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
