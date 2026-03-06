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
    private $idCompraPrueba;

    protected function setUp(): void
    {
        // Silenciar error_log() y E_WARNING (ej. Redis/Predis) antes de que
        // PHPUnit los capture y marque el test como Warning (W)
        ini_set('log_errors', '0');
        ini_set('error_log', 'NUL');
        set_error_handler(static function (int $errno): bool {
            // Devolver true = 'manejado', PHPUnit no lo ve
            return in_array($errno, [E_WARNING, E_NOTICE, E_USER_WARNING, E_USER_NOTICE], true);
        });

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

        if (!$this->productoIdPrueba || !$this->proveedorIdPrueba) {
            return; // setUp falla grácilmente; el test llamará markTestSkipped
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

        // Suprimir E_WARNING de Predis/Redis durante insertarCompra para
        // evitar que PHPUnit los capture y marque el test como Warning (W)
        $prevLevel = error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
        $resultadoInsercion = $this->comprasModel->insertarCompra($datosCompra, $detallesCompra);
        error_reporting($prevLevel);

        if ($resultadoInsercion['status']) {
            $this->idCompraPrueba = $resultadoInsercion['id'];
        }
    }

    protected function tearDown(): void
    {
        restore_error_handler();
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
        if (!$this->productoIdPrueba || !$this->proveedorIdPrueba) {
            $this->markTestSkipped('No se pudo crear/obtener datos de prueba básicos');
        }

        if (!$this->idCompraPrueba) {
            $this->markTestSkipped('No se pudo crear compra de prueba');
        }

        $producto = $this->productosModel->selectProductoById($this->productoIdPrueba);

        $datosEditados = [
            'nro_compra' => $this->comprasModel->generarNumeroCompra(),
            'fecha_compra' => date('Y-m-d'),
            'idproveedor' => $this->proveedorIdPrueba,
            'idmoneda_general' => 3,
            'total_general_compra' => 50,
            'observaciones_compra' => 'Compra editada para prueba.',
        ];
        $detallesEditados = [
            [
                'idproducto' => $producto['idproducto'],
                'descripcion_temporal_producto' => $producto['nombre'],
                'cantidad' => 5,
                'precio_unitario_compra' => 10,
                'idmoneda_detalle' => 3,
                'subtotal_linea' => 50,
            ]
        ];

        // Inyectar el ID inexistente según el tipo de prueba
        if ($tipoInexistente === 'proveedor') {
            $datosEditados['idproveedor'] = 888888 + rand(1, 99999);
        } else {
            $detallesEditados[0]['idproducto'] = 888888 + rand(1, 99999);
        }

        $resultado = $this->comprasModel->actualizarCompra(
            $this->idCompraPrueba,
            $datosEditados,
            $detallesEditados
        );

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status'], $resultado['message'] ?? 'sin mensaje del modelo');
        $this->assertStringContainsString($tipoInexistente, strtolower($resultado['message']));
        fwrite(STDOUT, "\n[MODEL MESSAGE] Validación correcta: " . $resultado['message'] . "\n");
    }
}
