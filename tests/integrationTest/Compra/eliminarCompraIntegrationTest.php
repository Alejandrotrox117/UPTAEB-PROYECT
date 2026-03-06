<?php

namespace Tests\IntegrationTest\Compra;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\ComprasModel;
use App\Models\ProductosModel;
use App\Models\ProveedoresModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class eliminarCompraIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private $comprasModel;
    private $productosModel;
    private $proveedoresModel;
    private $productoIdPrueba;
    private $proveedorIdPrueba;

    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "\n[MODEL MESSAGE] " . $msg . "\n");
    }

    protected function setUp(): void
    {
        ini_set('log_errors', '0');
        ini_set('error_log', 'NUL');

        $this->requireDatabase();
        $this->comprasModel = new ComprasModel();
        $this->productosModel = new ProductosModel();
        $this->proveedoresModel = new ProveedoresModel();

        // Crear producto de prueba
        $dataProducto = [
            'nombre' => 'Producto Eliminar Test ' . time(),
            'descripcion' => 'Producto para pruebas de eliminar compra',
            'unidad_medida' => 'KG',
            'precio' => 10.00,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];
        $resultProducto = $this->productosModel->insertProducto($dataProducto);
        if ($resultProducto['status']) {
            $this->productoIdPrueba = $resultProducto['producto_id'];
        }

        // Obtener proveedor existente
        $resultadoProveedores = $this->proveedoresModel->selectAllProveedores(1);
        if ($resultadoProveedores['status'] && !empty($resultadoProveedores['data'])) {
            $this->proveedorIdPrueba = $resultadoProveedores['data'][0]['idproveedor'];
        }
    }

    public static function providerCasosEliminarCompra(): array
    {
        return [
            [888888 + rand(1, 99999)]
        ];
    }

    #[Test]
    #[DataProvider('providerCasosEliminarCompra')]
    public function testEliminarCompraConIdInexistente(int $id)
    {
        $resultado = $this->comprasModel->deleteCompraById($id);
        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->showMessage("Validación correcta: " . $resultado['message']);
    }

    protected function tearDown(): void
    {
        $this->comprasModel = null;
        $this->productosModel = null;
        $this->proveedoresModel = null;
    }
}
