<?php

namespace Tests\IntegrationTest\Ventas;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\VentasModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class consultarVentasIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private VentasModel $model;
    private static ?int $idVentaPrueba = null;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new VentasModel();
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }

    // -------------------------------------------------------------------------
    // DataProviders
    // -------------------------------------------------------------------------

    public static function providerColumnasEsperadasDatatable(): array
    {
        return [
            ['idventa'],
            ['nro_venta'],
            ['fecha_venta'],
            ['total_general'],
            ['estatus'],
        ];
    }

    public static function providerIdsInexistentes(): array
    {
        return [
            'id_muy_grande' => [999999],
            'id_cero'       => [0],
        ];
    }

    // -------------------------------------------------------------------------
    // Tests getVentasDatatable
    // -------------------------------------------------------------------------

    #[Test]
    public function testGetVentasDatatable_RetornaArray(): void
    {
        $result = $this->model->getVentasDatatable();

        $this->assertIsArray($result);
    }

    #[Test]
    #[DataProvider('providerColumnasEsperadasDatatable')]
    public function testGetVentasDatatable_CadaRegistroTieneColumnaRequerida(string $columna): void
    {
        $result = $this->model->getVentasDatatable();

        if (empty($result)) {
            $this->markTestSkipped("No hay ventas en BD para validar columna '$columna'");
        }

        $this->assertArrayHasKey(
            $columna,
            $result[0],
            "La columna '$columna' debe existir en cada venta del datatable"
        );
    }

    // -------------------------------------------------------------------------
    // Tests obtenerVentaPorId
    // -------------------------------------------------------------------------

    #[Test]
    public function testObtenerVentaPorId_VentaExistente_RetornaDatosCompletos(): void
    {
        $ventas = $this->model->getVentasDatatable();

        if (empty($ventas)) {
            $this->markTestSkipped('No hay ventas en BD para realizar consulta por ID');
        }

        $idVenta = (int)$ventas[0]['idventa'];
        $result  = $this->model->obtenerVentaPorId($idVenta);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('idventa', $result);
        $this->assertArrayHasKey('nro_venta', $result);
        $this->assertArrayHasKey('fecha_venta', $result);
        $this->assertArrayHasKey('total_general', $result);
        $this->assertArrayHasKey('estatus', $result);
        $this->assertEquals($idVenta, (int)$result['idventa']);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testObtenerVentaPorId_Inexistente_RetornaFalse(int $id): void
    {
        $result = $this->model->obtenerVentaPorId($id);

        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // Tests obtenerDetalleVenta
    // -------------------------------------------------------------------------

    #[Test]
    public function testObtenerDetalleVenta_VentaExistente_RetornaArray(): void
    {
        $ventas = $this->model->getVentasDatatable();

        if (empty($ventas)) {
            $this->markTestSkipped('No hay ventas en BD para obtener detalles');
        }

        $idVenta = (int)$ventas[0]['idventa'];
        $result  = $this->model->obtenerDetalleVenta($idVenta);

        $this->assertIsArray($result);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testObtenerDetalleVenta_Inexistente_RetornaArrayVacio(int $id): void
    {
        $result = $this->model->obtenerDetalleVenta($id);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
