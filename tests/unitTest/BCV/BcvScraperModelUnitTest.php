<?php

namespace Tests\UnitTest\BCV;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\BcvScraperModel;
use Mockery;
use PDO;
use PDOStatement;

/**
 * Tests Unitarios para BcvScraperModel.
 */
class BcvScraperModelUnitTest extends TestCase
{
    private $scraper;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');

        // Mocks de Base de datos (skill require)
        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')->andReturnTrue();

        $mockPdo = Mockery::mock(PDO::class);
        $mockPdo->shouldReceive('prepare')->andReturn($mockStmt);

        Mockery::mock('overload:App\Core\Conexion')
            ->shouldReceive('getConexion')
            ->andReturn($mockPdo);

        // Usar un partial mock para poder sobreescribir fetchBcvHtml()
        $this->scraper = Mockery::mock(BcvScraperModel::class)->makePartial()->shouldAllowMockingProtectedMethods();
    }

    protected function tearDown(): void
    {
        unset($this->scraper);
        Mockery::close();
    }

    // ---------------------------------------------------------------
    // obtenerDatosTasaBcv — casos de Exito
    // ---------------------------------------------------------------

    #[Test]
    public function testObtenerTasaBcv_Exito_ParaUSD(): void
    {
        // Simulamos un HTML valido con nodo id='dolar'
        $htmlSimulado = "<html><body><div id='dolar'><strong> 36,25 </strong></div></body></html>";
        $this->scraper->shouldReceive('fetchBcvHtml')->once()->andReturn($htmlSimulado);

        $data = $this->scraper->obtenerDatosTasaBcv('USD');

        $this->assertIsArray($data);
        $this->assertArrayHasKey('tasa', $data);
        $this->assertEquals(36.25, $data['tasa']);
        $this->assertArrayHasKey('fecha_bcv', $data);
    }

    #[Test]
    public function testObtenerTasaBcv_Exito_ParaEUR(): void
    {
        // Simulamos un HTML valido con nodo id='euro'
        $htmlSimulado = "<html><body><div id='euro'><strong>39,10</strong></div></body></html>";
        $this->scraper->shouldReceive('fetchBcvHtml')->once()->andReturn($htmlSimulado);

        $data = $this->scraper->obtenerDatosTasaBcv('EUR');

        $this->assertIsArray($data);
        $this->assertEquals(39.10, $data['tasa']);
    }

    // ---------------------------------------------------------------
    // obtenerDatosTasaBcv — casos de Falla
    // ---------------------------------------------------------------

    #[Test]
    public function testObtenerTasaBcv_Falla_CodigoMonedaNoSoportado(): void
    {
        // Retorna null de inmediato sin llamar fetchBcvHtml
        $this->scraper->shouldReceive('fetchBcvHtml')->never();

        $data = $this->scraper->obtenerDatosTasaBcv('XYZ');
        $this->assertNull($data);
    }

    #[Test]
    public function testObtenerTasaBcv_Falla_FalloConexionHtml(): void
    {
        // Simulamos que cURL fallo y retorno false
        $this->scraper->shouldReceive('fetchBcvHtml')->once()->andReturn(false);

        $data = $this->scraper->obtenerDatosTasaBcv('USD');
        $this->assertNull($data);
    }

    #[Test]
    public function testObtenerTasaBcv_Falla_NodoDeTasaNoEncontrado(): void
    {
        // HTML que no tiene el nodo dolar o euro
        $htmlInvalido = "<html><body><div>No hay nada</div></body></html>";
        $this->scraper->shouldReceive('fetchBcvHtml')->once()->andReturn($htmlInvalido);

        $data = $this->scraper->obtenerDatosTasaBcv('USD');
        $this->assertNull($data);
    }
}
