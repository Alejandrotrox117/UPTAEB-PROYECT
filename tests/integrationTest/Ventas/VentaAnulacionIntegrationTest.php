<?php

namespace Tests\IntegrationTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\VentasModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class VentaAnulacionIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private ?VentasModel $ventasModel;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->ventasModel = new VentasModel();
    }

    protected function tearDown(): void
    {
        $this->ventasModel = null;
    }

    // ─────────────────────────────────────────────
    // DataProviders
    // ─────────────────────────────────────────────

    public static function providerAnulacionFallida(): array
    {
        return [
            'id inexistente' => [888888 + rand(1, 99999), 'Prueba de anulación'],
            'id negativo'    => [-1,                       'Motivo de anulación'],
            'id cero'        => [0,                        'Motivo de anulación'],
        ];
    }

    // ─────────────────────────────────────────────
    // Tests: anularVenta con IDs inválidos
    // ─────────────────────────────────────────────

    #[Test]
    #[DataProvider('providerAnulacionFallida')]
    public function testAnularVenta_ConIdInvalido_RetornaFalse(int $idventa, string $motivo): void
    {
        if (!method_exists($this->ventasModel, 'anularVenta')) {
            $this->markTestSkipped('Método anularVenta no existe en VentasModel.');
        }

        $resultado = $this->ventasModel->anularVenta($idventa, $motivo);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['success'] ?? $resultado['status'] ?? false);
    }

    // ─────────────────────────────────────────────
    // Test: anularVenta retorna array con estructura esperada
    // ─────────────────────────────────────────────

    #[Test]
    public function testAnularVenta_EstructuraDeRespuestaEsCorrecta(): void
    {
        if (!method_exists($this->ventasModel, 'anularVenta')) {
            $this->markTestSkipped('Método anularVenta no existe en VentasModel.');
        }

        // Usar un id ficticio — lo importante es validar la estructura del response
        $resultado = $this->ventasModel->anularVenta(888888, 'Test estructura');

        $this->assertIsArray($resultado);
        // El response debe tener al menos 'success' o 'status' y 'message'
        $tieneSuccess = array_key_exists('success', $resultado) || array_key_exists('status', $resultado);
        $this->assertTrue($tieneSuccess, 'La respuesta debe incluir la clave success o status.');
        $this->assertArrayHasKey('message', $resultado);
    }
}
