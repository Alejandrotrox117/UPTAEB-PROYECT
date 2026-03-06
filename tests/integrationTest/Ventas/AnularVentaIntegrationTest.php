<?php

namespace Tests\IntegrationTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\VentasModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class AnularVentaIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private $model;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new VentasModel();
    }

    #[Test]
    public function testAnularVentaExistente()
    {
        if (!method_exists($this->model, 'anularVenta')) {
            $this->markTestSkipped('Método anularVenta no existe');
        }

        $idventa = 1;
        $motivo = 'Prueba de anulación';
        $result = $this->model->anularVenta($idventa, $motivo);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }
}
