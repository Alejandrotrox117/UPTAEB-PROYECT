<?php

namespace Tests\IntegrationTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\VentasModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class ConsultarVentasIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private $model;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new VentasModel();
    }

    #[Test]
    public function testGetAllVentas()
    {
        if (method_exists($this->model, 'getAllVentas')) {
            $result = $this->model->getAllVentas();
            $this->assertIsArray($result);
        } else {
            $this->markTestSkipped('Método getAllVentas no existe');
        }
    }

    #[Test]
    public function testGetVentasDatatable()
    {
        if (method_exists($this->model, 'getVentasDatatable')) {
            $result = $this->model->getVentasDatatable();
            $this->assertIsArray($result);

            if (!empty($result)) {
                $venta = $result[0];
                $this->assertArrayHasKey('idventa', $venta);
                $this->assertArrayHasKey('nro_venta', $venta);
                $this->assertArrayHasKey('fecha_venta', $venta);
                $this->assertArrayHasKey('total_general', $venta);
                $this->assertArrayHasKey('estatus', $venta);
            }
        } else {
            $this->markTestSkipped('Método getVentasDatatable no existe');
        }
    }
}
