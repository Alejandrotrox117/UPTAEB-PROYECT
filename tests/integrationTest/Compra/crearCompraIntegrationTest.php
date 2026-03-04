<?php
namespace Tests\IntegrationTest\Compra;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\ComprasModel;
use App\Models\ProductosModel;
use App\Models\ProveedoresModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class crearCompraIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private ComprasModel $comprasModel;
    private ProductosModel $productosModel;
    private ProveedoresModel $proveedoresModel;
    private $productoIdPrueba;
    private $proveedorIdPrueba;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->comprasModel = new ComprasModel();
        $this->productosModel = new ProductosModel();
        $this->proveedoresModel = new ProveedoresModel();

        // Crear producto temporal de prueba
        $dataProducto = [
            'nombre' => 'Producto Integracion ' . time(),
            'descripcion' => 'Para pruebas de compra',
            'unidad_medida' => 'KG',
            'precio' => 10.00,
            'idcategoria' => 1,
            'moneda' => 'USD'
        ];
        $resultProducto = $this->productosModel->insertProducto($dataProducto);
        if ($resultProducto['status']) {
            $this->productoIdPrueba = $resultProducto['producto_id'];
        }

        // Obtener proveedor de forma real o crearlo temporal (tomamos 1 listado)
        $resultadoProveedores = $this->proveedoresModel->selectAllProveedores(1);
        if ($resultadoProveedores['status'] && !empty($resultadoProveedores['data'])) {
            $this->proveedorIdPrueba = $resultadoProveedores['data'][0]['idproveedor'];
        }
    }

    protected function tearDown(): void
    {
        unset($this->comprasModel);
        unset($this->productosModel);
        unset($this->proveedoresModel);
    }

    public static function providerCasosCompraIntegration(): array
    {
        return [
            'Sin Detalles' => [
                'tipo_detalles' => 'vacio',
                'proveedor_valido' => true,
                'producto_valido' => true,
                'cantidad' => 1,
                'esperado_status' => false,
                'mensaje_parcial' => 'No hay detalles',
            ],
            'Proveedor Inexistente' => [
                'tipo_detalles' => 'lleno',
                'proveedor_valido' => false,
                'producto_valido' => true,
                'cantidad' => 1,
                'esperado_status' => false,
                'mensaje_parcial' => 'proveedor con ID',
            ],
            'Producto Inexistente' => [
                'tipo_detalles' => 'lleno',
                'proveedor_valido' => true,
                'producto_valido' => false,
                'cantidad' => 1,
                'esperado_status' => false,
                'mensaje_parcial' => 'producto con ID',
            ],
            'Cantidad Cero' => [
                'tipo_detalles' => 'lleno',
                'proveedor_valido' => true,
                'producto_valido' => true,
                'cantidad' => 0,
                'esperado_status' => false,
                'mensaje_parcial' => 'negativos o cero',
            ],
            'Compra Exitosa' => [
                'tipo_detalles' => 'lleno',
                'proveedor_valido' => true,
                'producto_valido' => true,
                'cantidad' => 5,
                'esperado_status' => true,
                'mensaje_parcial' => 'exitosamente',
            ]
        ];
    }

    #[Test]
    #[DataProvider('providerCasosCompraIntegration')]
    public function testInsertarCompra_ConBaseDeDatos(
        string $tipo_detalles,
        bool $proveedor_valido,
        bool $producto_valido,
        int $cantidad,
        bool $esperado_status,
        string $mensaje_parcial
    ) {
        if (!$this->productoIdPrueba || !$this->proveedorIdPrueba) {
            $this->markTestSkipped('Faltan base de datos de pruebas inicializadas con IDs');
        }

        $idProveedorParaTest = $proveedor_valido ? $this->proveedorIdPrueba : 999999;
        $idProductoParaTest = $producto_valido ? $this->productoIdPrueba : 999999;
        $precioUnit = 10;
        $subtotal = $precioUnit * $cantidad;

        $datosCompra = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra() ?? 'C-TEST',
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => $idProveedorParaTest,
            'idmoneda_general' => 3,
            'subtotal_general_compra' => $subtotal,
            'descuento_porcentaje_compra' => 0,
            'monto_descuento_compra' => 0,
            'total_general_compra' => $subtotal,
            'observaciones_compra' => 'Integration Test DP'
        ];

        $detallesCompra = [];
        if ($tipo_detalles === 'lleno') {
            $detallesCompra[] = [
                'idproducto' => $idProductoParaTest,
                'descripcion_temporal_producto' => 'Prod DP',
                'cantidad' => $cantidad,
                'descuento' => 0,
                'precio_unitario_compra' => $precioUnit,
                'idmoneda_detalle' => 3,
                'subtotal_linea' => $subtotal,
                'peso_vehiculo' => null,
                'peso_bruto' => null,
                'peso_neto' => null
            ];
        }

        $resultado = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);

        $this->assertIsArray($resultado);
        $this->assertEquals($esperado_status, $resultado['status']);
        $this->assertStringContainsString($mensaje_parcial, $resultado['message']);

        if ($esperado_status) {
            $this->assertArrayHasKey('id', $resultado);
            $this->assertGreaterThan(0, $resultado['id']);
        }
    }
}
