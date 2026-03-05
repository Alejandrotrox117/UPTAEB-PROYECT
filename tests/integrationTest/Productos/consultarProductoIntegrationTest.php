<?php

namespace Tests\IntegrationTest\Productos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\ProductosModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class consultarProductoIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private ProductosModel $model;
    private static ?int $idProductoPrueba = null;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new ProductosModel();

        // Crear producto de referencia si aún no existe
        if (self::$idProductoPrueba === null) {
            $result = $this->model->insertProducto([
                'nombre'       => 'Producto Consulta Integ ' . uniqid(),
                'descripcion'  => 'Usado para pruebas de consulta',
                'unidad_medida'=> 'KG',
                'precio'       => 5.00,
                'idcategoria'  => 1,
                'moneda'       => 'USD',
            ]);
            if ($result['status']) {
                self::$idProductoPrueba = $result['producto_id'];
            }
        }
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }

    // -------------------------------------------------------------------------
    // DataProviders
    // -------------------------------------------------------------------------

    public static function providerIdsInexistentes(): array
    {
        return [
            'id_muy_grande' => [999999],
            'id_cero'       => [0],
        ];
    }

    public static function providerColumnasEsperadas(): array
    {
        return [
            ['idproducto'],
            ['nombre'],
            ['descripcion'],
            ['precio'],
            ['unidad_medida'],
            ['estatus'],
        ];
    }

    // -------------------------------------------------------------------------
    // Tests selectAllProductos
    // -------------------------------------------------------------------------

    #[Test]
    public function testSelectAllProductos_RetornaEstructuraCorrecta(): void
    {
        $result = $this->model->selectAllProductos();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertTrue($result['status']);
        $this->assertIsArray($result['data']);
    }

    #[Test]
    #[DataProvider('providerColumnasEsperadas')]
    public function testSelectAllProductos_CadaRegistroTieneColumnasRequeridas(string $columna): void
    {
        $result = $this->model->selectAllProductos();
        $productos = $result['data'] ?? [];

        if (empty($productos)) {
            $this->markTestSkipped('No hay productos en BD para validar columnas');
        }

        $this->assertArrayHasKey(
            $columna,
            $productos[0],
            "Columna '$columna' debe existir en cada producto"
        );
    }

    // -------------------------------------------------------------------------
    // Tests selectProductoById
    // -------------------------------------------------------------------------

    #[Test]
    public function testSelectProductoById_Existente_RetornaProductoCompleto(): void
    {
        if (!self::$idProductoPrueba) {
            $this->markTestSkipped('No se pudo crear producto de referencia');
        }

        $result = $this->model->selectProductoById(self::$idProductoPrueba);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('idproducto', $result);
        $this->assertArrayHasKey('nombre', $result);
        $this->assertArrayHasKey('descripcion', $result);
        $this->assertArrayHasKey('precio', $result);
        $this->assertArrayHasKey('unidad_medida', $result);
        $this->assertArrayHasKey('estatus', $result);
        $this->assertEquals(self::$idProductoPrueba, $result['idproducto']);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testSelectProductoById_Inexistente_RetornaFalse(int $id): void
    {
        $result = $this->model->selectProductoById($id);

        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // Tests selectProductosActivos
    // -------------------------------------------------------------------------

    #[Test]
    public function testSelectProductosActivos_RetornaEstructuraCorrecta(): void
    {
        $result = $this->model->selectProductosActivos();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertTrue($result['status']);
    }

    #[Test]
    public function testSelectProductosActivos_SoloRetornaRegistrosActivos(): void
    {
        $result = $this->model->selectProductosActivos();

        foreach ($result['data'] as $producto) {
            $this->assertEquals(
                'ACTIVO',
                strtoupper($producto['estatus']),
                "Producto ID={$producto['idproducto']} no debería aparecer en la lista de activos"
            );
        }
    }

    // -------------------------------------------------------------------------
    // Tests selectCategoriasActivas
    // -------------------------------------------------------------------------

    #[Test]
    public function testSelectCategoriasActivas_RetornaEstructuraCorrecta(): void
    {
        $result = $this->model->selectCategoriasActivas();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertTrue($result['status']);
    }

    #[Test]
    public function testSelectCategoriasActivas_SoloRetornaActivas(): void
    {
        $result = $this->model->selectCategoriasActivas();

        foreach ($result['data'] as $categoria) {
            $this->assertEquals(
                'activo',
                strtolower($categoria['estatus']),
                "Categoría ID={$categoria['idcategoria']} no debería aparecer con estatus inactivo"
            );
        }
    }
}
