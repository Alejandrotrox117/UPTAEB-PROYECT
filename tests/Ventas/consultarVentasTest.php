<?php
use PHPUnit\Framework\TestCase;
use App\Models\VentasModel;
class ConsultarVentasTest extends TestCase
{
    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "\n[MODEL MESSAGE] " . $msg . "\n");
    }
    public function testConsultarTodasVentas()
    {
        $ventasModel = new VentasModel();
        $result = $ventasModel->getVentasDatatable();
        $this->assertIsArray($result);
    }
    public function testEstructuraBasicaVenta()
    {
        $ventasModel = new VentasModel();
        $result = $ventasModel->getVentasDatatable();
        if (!empty($result)) {
            $venta = $result[0];
            $this->assertArrayHasKey('idventa', $venta);
            $this->assertArrayHasKey('nro_venta', $venta);
            $this->assertArrayHasKey('fecha_venta', $venta);
            $this->assertArrayHasKey('cliente_nombre', $venta);
            $this->assertArrayHasKey('total_general', $venta);
            $this->assertArrayHasKey('estatus', $venta);
        } else {
            $this->assertTrue(true); 
        }
    }
}
