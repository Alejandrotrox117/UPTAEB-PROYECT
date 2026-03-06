<?php

namespace Tests\IntegrationTest\Compra;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\ComprasModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class consultarComprasIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private ?ComprasModel $compras;

    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "\n[MODEL MESSAGE] " . $msg . "\n");
    }

    protected function setUp(): void
    {
        ini_set('log_errors', '0');
        ini_set('error_log', 'NUL');

        $this->requireDatabase();
        $this->compras = new ComprasModel();
    }

    protected function tearDown(): void
    {
        $this->compras = null;
    }

    public static function providerIdsInexistentes(): array
    {
        return [
            [888888 + rand(1, 99999)],
            [999999 + rand(1, 99999)]
        ];
    }

    #[Test]
    public function testSelectAllComprasRetornaArray()
    {
        $result = $this->compras->selectAllCompras();
        $this->assertIsArray($result);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testGetCompraByIdConIdInexistente(int $id)
    {
        $result = $this->compras->getCompraById($id);
        $this->assertFalse($result);
        $this->showMessage("Validación correcta: Compra inexistente retorna false");
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testSelectCompraConIdInexistente(int $id)
    {
        $result = $this->compras->selectCompra($id);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
        $this->showMessage("Validación correcta: Select compra inexistente retorna array vacío");
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testGetDetalleCompraByIdInexistente(int $id)
    {
        $result = $this->compras->getDetalleCompraById($id);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testGetCompraCompletaParaEditarInexistente(int $id)
    {
        $result = $this->compras->getCompraCompletaParaEditar($id);
        $this->assertFalse($result);
        $this->showMessage("Validación correcta: Compra completa inexistente retorna false");
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testObtenerEstadoCompraInexistente(int $id)
    {
        $result = $this->compras->obtenerEstadoCompra($id);
        $this->assertNull($result);
    }

    #[Test]
    public function testGetMonedasActivas()
    {
        $result = $this->compras->getMonedasActivas();
        $this->assertIsArray($result);
    }

    #[Test]
    public function testGetProductosConCategoria()
    {
        $result = $this->compras->getProductosConCategoria();
        $this->assertIsArray($result);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testGetProductoByIdInexistente(int $id)
    {
        $result = $this->compras->getProductoById($id);
        $this->assertFalse($result);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testGetProveedorByIdInexistente(int $id)
    {
        $result = $this->compras->getProveedorById($id);
        $this->assertFalse($result);
    }
}
