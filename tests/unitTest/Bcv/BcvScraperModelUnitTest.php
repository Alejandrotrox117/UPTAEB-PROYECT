<?php
declare(strict_types=1);

// ============================================================================
// Namespace del test
// ============================================================================
namespace Tests\UnitTest\Bcv;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\BcvScraperModel;

/**
 * Estado global compartido entre el bloque namespace App\Models
 * (sobrescrituras de cURL) y la clase de prueba.
 * RunTestsInSeparateProcesses garantiza que las funciones no sean re-declaradas.
 */
final class BcvCurlFixture
{
    /** @var mixed HTML de respuesta o false para simular fallo de curl_exec */
    public static mixed $curlResponse = '';
    public static int   $httpCode     = 200;
    public static string $curlError   = '';

    /** HTML mínimo que simula la página del BCV con tasas ficticias. */
    public static function htmlConTasas(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<body>
  <div id="dolar">
    <div class="col-sm-6 col-xs-6 centrado">
      <strong>38,1234</strong>
    </div>
  </div>
  <div id="euro">
    <div class="col-sm-6 col-xs-6 centrado">
      <strong>42,5678</strong>
    </div>
  </div>
</body>
</html>
HTML;
    }

    /** HTML sin los nodos de tasas (estructura rota / vacía). */
    public static function htmlSinTasas(): string
    {
        return '<!DOCTYPE html><html><body><div id="contenido">Sin tasas</div></body></html>';
    }
}

#[RunTestsInSeparateProcesses]
class BcvScraperModelUnitTest extends TestCase
{
    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');
        // Configuración por defecto: respuesta HTTP exitosa con HTML válido
        BcvCurlFixture::$curlResponse = BcvCurlFixture::htmlConTasas();
        BcvCurlFixture::$httpCode     = 200;
        BcvCurlFixture::$curlError    = '';
    }

    // ---------------------------------------------------------------
    // DataProviders
    // ---------------------------------------------------------------

    public static function providerMonedasValidas(): array
    {
        return [
            'dolar_USD' => ['USD', 38.1234],
            'euro_EUR'  => ['EUR', 42.5678],
        ];
    }

    public static function providerFallasHTTP(): array
    {
        return [
            'http_500'   => [true,  500, ''],
            'http_403'   => [true,  403, ''],
            'curl_error' => [false, 0,   'Connection timed out'],
        ];
    }

    // ---------------------------------------------------------------
    // Tests: respuestas exitosas
    // ---------------------------------------------------------------

    #[Test]
    #[DataProvider('providerMonedasValidas')]
    public function testObtenerTasaBcv_RetornaEstructuraCorrecta_ConFixture(
        string $moneda,
        float $tasaEsperada
    ): void {
        $scraper = new BcvScraperModel();
        $result  = $scraper->obtenerDatosTasaBcv($moneda);

        $this->assertNotNull($result, "No debe retornar null para {$moneda} con fixture válido.");
        $this->assertIsArray($result);
        $this->assertArrayHasKey('tasa', $result);
        $this->assertArrayHasKey('fecha_bcv', $result);
        $this->assertIsFloat($result['tasa']);
        $this->assertGreaterThan(0, $result['tasa']);
        $this->assertEqualsWithDelta($tasaEsperada, $result['tasa'], 0.0001, "Tasa inesperada para {$moneda}.");
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $result['fecha_bcv']);
    }

    // ---------------------------------------------------------------
    // Tests: fallos de HTTP / cURL
    // ---------------------------------------------------------------

    #[Test]
    #[DataProvider('providerFallasHTTP')]
    public function testObtenerTasaBcv_RetornaNull_CuandoHTTPFalla(
        bool $htmlComoString,
        int  $httpCode,
        string $curlError
    ): void {
        BcvCurlFixture::$curlResponse = $htmlComoString ? '<html></html>' : false;
        BcvCurlFixture::$httpCode     = $httpCode;
        BcvCurlFixture::$curlError    = $curlError;

        $scraper = new BcvScraperModel();
        $result  = $scraper->obtenerDatosTasaBcv('USD');

        $this->assertNull($result, "Se esperaba null con httpCode={$httpCode} / curlError='{$curlError}'.");
    }

    // ---------------------------------------------------------------
    // Tests: HTML válido pero sin estructura BCV
    // ---------------------------------------------------------------

    #[Test]
    public function testObtenerTasaBcv_RetornaNull_CuandoHTMLSinNodosEsperados(): void
    {
        BcvCurlFixture::$curlResponse = BcvCurlFixture::htmlSinTasas();
        BcvCurlFixture::$httpCode     = 200;

        $scraper = new BcvScraperModel();
        $result  = $scraper->obtenerDatosTasaBcv('USD');

        $this->assertNull($result, 'Debe retornar null cuando el HTML no contiene nodos <strong> dentro de #dolar/#euro.');
    }
}

// ============================================================================
// Sobrescritura namespace-level de las funciones cURL usadas por BcvScraperModel.
// PHP resuelve primero en el namespace local (App\Models) antes de recurrir al
// global. Requiere #[RunTestsInSeparateProcesses] para evitar redeclaraciones.
// ============================================================================
namespace App\Models;

use Tests\UnitTest\Bcv\BcvCurlFixture;

function curl_init()                    { return 'mock_ch'; }
function curl_setopt($ch, $opt, $val)   { return true; }
function curl_exec($ch)                 { return BcvCurlFixture::$curlResponse; }
function curl_getinfo($ch, $opt = null) { return BcvCurlFixture::$httpCode; }
function curl_error($ch)                { return BcvCurlFixture::$curlError; }
function curl_close($ch)                { /* no-op */ }
