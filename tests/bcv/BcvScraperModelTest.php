<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../app/models/BcvScraperModel.php';
class BcvScraperModelTest extends TestCase
{
    private $scraper;
    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "\n[MODEL MESSAGE] " . $msg . "\n");
    }
    protected function setUp(): void
    {
        $this->scraper = new BcvScraperModel();
    }
    public function testObtenerTasaDolar()
    {
        echo "\nProbando obtener tasa del Dólar (USD)...";
        $data = $this->scraper->obtenerDatosTasaBcv('USD');
        $this->assertIsArray($data, "La respuesta para USD no es un array.");
        $this->assertArrayHasKey('tasa', $data, "No se encontró la clave 'tasa' para USD.");
        $this->assertArrayHasKey('fecha_bcv', $data, "No se encontró la clave 'fecha_bcv' para USD.");
        $this->assertIsFloat($data['tasa'], "La tasa para USD no es un número flotante.");
        $this->assertGreaterThan(0, $data['tasa'], "La tasa para USD debe ser mayor que cero.");
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $data['fecha_bcv'], "El formato de fecha para USD es incorrecto.");
        echo "\n[ÉXITO] Tasa Dólar BCV: " . $data['tasa'] . " (Fecha: " . $data['fecha_bcv'] . ")";
    }
    public function testObtenerTasaEuro()
    {
        echo "\nProbando obtener tasa del Euro (EUR)...";
        $data = $this->scraper->obtenerDatosTasaBcv('EUR');
        $this->assertIsArray($data, "La respuesta para EUR no es un array.");
        $this->assertArrayHasKey('tasa', $data, "No se encontró la clave 'tasa' para EUR.");
        $this->assertArrayHasKey('fecha_bcv', $data, "No se encontró la clave 'fecha_bcv' para EUR.");
        $this->assertIsFloat($data['tasa'], "La tasa para EUR no es un número flotante.");
        $this->assertGreaterThan(0, $data['tasa'], "La tasa para EUR debe ser mayor que cero.");
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $data['fecha_bcv'], "El formato de fecha para EUR es incorrecto.");
        echo "\n[ÉXITO] Tasa Euro BCV: " . $data['tasa'] . " (Fecha: " . $data['fecha_bcv'] . ")";
    }
}
