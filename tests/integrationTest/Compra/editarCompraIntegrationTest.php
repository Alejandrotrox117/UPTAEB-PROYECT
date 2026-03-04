<?php

namespace Tests\IntegrationTest\Compra;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\ComprasModel;
use App\Models\ProductosModel;
use App\Models\ProveedoresModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class editarCompraIntegrationTest extends TestCase
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
        $this->requireDatabase();
        $this->comprasModel = new ComprasModel();
        $this->productosModel = new ProductosModel();
        $this->proveedoresModel = new ProveedoresModel();

        // Crear producto de prueba
        $dataProducto = [
            'nombre' => 'Producto Editar Test ' . time(),
            'descripcion' => 'Producto para pruebas de editar compra',
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

    protected function tearDown(): void
    {
        $this->comprasModel = null;
        $this->productosModel = null;
        $this->proveedoresModel = null;
    }

    public static function providerCasosInexistentes(): array
    {
        return [
            ['proveedor'],
            ['producto']
        ];
    }

    #[Test]
    #[DataProvider('providerCasosInexistentes')]
    public function testEditarCompra_DatosInexistentes(string $tipoInexistente)
    {
        if (!$this->productoIdPrueba || (!$this->proveedorIdPrueba && $this->proveedorIdPrueba !== 0)) {
            $this->markTestSkipped('No se pudo crear/obtener datos de prueba básicos');
        }

        $producto = $this->productosModel->selectProductoById($this->productoIdPrueba);

        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => $this->proveedorIdPrueba,
            'idmoneda_general' => 3,
            'total_general_compra' => 50,
            'observaciones_compra' => 'Compra original para prueba de edición.',
        ];
        $detallesCompra = [
            [
                'idproducto' => $producto['idproducto'],
                'descripcion_temporal_producto' => $producto['nombre'],
                'cantidad' => 5,
                'precio_unitario_compra' => 10,
                'idmoneda_detalle' => 3,
                'subtotal_linea' => 50,
            ]
        ];

        $resultadoInsercion = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);
        if (!$resultadoInsercion['status']) {
            $this->markTestSkipped('No se pudo crear compra de prueba');
        }
        $idCompra = $resultadoInsercion['id'];

        $datosEditados = $datosCompra;
        $detallesEditados = $detallesCompra;

        if ($tipoInexistente === 'proveedor') {
            $datosEditados['idproveedor'] = 888888 + rand(1, 99999);
        } else {
            $detallesEditados[0]['idproducto'] = 888888 + rand(1, 99999);
        }

        $resultado = $this->comprasModel->actualizarCompra($idCompra, $datosEditados, $detallesEditados);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
        $this->assertStringContainsString($tipoInexistente, strtolower($resultado['message']));
        $this->showMessage("Validación correcta: " . $resultado['message']);
    }
}
