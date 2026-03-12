## Cuadro Nº 1: Módulo de BCV Scraper (RF-BCV-01)

### Objetivos de la prueba

Validar que el scraper del Banco Central de Venezuela (BCV) obtenga correctamente las tasas de cambio para USD y EUR desde la página web oficial, maneje apropiadamente los errores de conexión HTTP/cURL, y retorne null cuando el HTML no contenga la estructura esperada.

### Técnicas

Pruebas de caja blanca con enfoque en aislamiento mediante mocking de funciones cURL a nivel de namespace. Se evalúa el método `obtenerDatosTasaBcv()` en escenarios de respuesta HTTP exitosa (200), errores HTTP (403, 500), errores de cURL (timeout), y HTML sin estructura BCV. Se utiliza `#[RunTestsInSeparateProcesses]` para garantizar independencia entre pruebas y evitar conflictos de declaración de funciones sobrescritas.

### Código Involucrado

```php
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
```

### Caso de prueba

**TIPO:** Funcional (Caja blanca)

**OBJETIVO:** Verificar que el scraper BCV obtenga y parsee correctamente las tasas de cambio en escenarios exitosos y maneje apropiadamente errores de conexión o estructura HTML inválida.

**DESCRIPCIÓN:** Se evalúa la obtención de tasas para USD (38.1234) y EUR (42.5678) con respuestas HTTP 200 exitosas. También se prueban casos de falla: errores HTTP (500, 403), errores de cURL (timeout), y HTML sin los nodos esperados (estructura inválida).

**ENTRADAS:**
- Monedas válidas: USD y EUR con HTML fixture que contiene tasas 38.1234 y 42.5678
- Errores HTTP: códigos 500 y 403 con HTML vacío o false en curl_exec
- Error cURL: `curl_exec` retorna false con mensaje "Connection timed out"
- HTML sin estructura BCV: respuesta 200 pero sin nodos `<strong>` en `#dolar` o `#euro`

**SALIDAS ESPERADAS:**

| Escenario | Resultado esperado |
|-----------|-------------------|
| USD con HTML válido | Array con tasa=38.1234, fecha_bcv en formato AAAA-MM-DD |
| EUR con HTML válido | Array con tasa=42.5678, fecha_bcv en formato AAAA-MM-DD |
| HTTP 500 | null |
| HTTP 403 | null |
| cURL timeout | null |
| HTML sin nodos BCV | null |

### Resultado

```
PHPUnit 10.5.63 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: C:\xampp\htdocs\project\phpunit.xml

......                                                              6 / 6 (100%)

Time: 00:00.994, Memory: 10.00 MB

Bcv Scraper Model Unit (Tests\UnitTest\Bcv\BcvScraperModelUnit)
 ✔ ObtenerTasaBcv RetornaEstructuraCorrecta ConFixture with dolar_USD
 ✔ ObtenerTasaBcv RetornaEstructuraCorrecta ConFixture with euro_EUR
 ✔ ObtenerTasaBcv RetornaNull CuandoHTTPFalla with http_500
 ✔ ObtenerTasaBcv RetornaNull CuandoHTTPFalla with http_403
 ✔ ObtenerTasaBcv RetornaNull CuandoHTTPFalla with curl_error
 ✔ ObtenerTasaBcv RetornaNull CuandoHTMLSinNodosEsperados

OK (6 tests, 20 assertions)
```

### Observaciones

Se ejecutaron 6 pruebas con 20 aserciones en 0.994 segundos, todas exitosas. La técnica de mocking a nivel de namespace permite aislar completamente las dependencias cURL sin modificar el código de producción, validando efectivamente el comportamiento del scraper en múltiples escenarios de éxito y falla.
