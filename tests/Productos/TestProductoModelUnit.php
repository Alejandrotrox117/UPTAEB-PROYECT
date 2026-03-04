<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Depends;
use App\Models\ProductosModel;

require_once __DIR__ . '/../Traits/RequiresDatabase.php';

class TestProductoModelUnit extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private ProductosModel $model;
    private static array $productosCreados = [];

    protected function setUp(): void
    {
        $this->model = new ProductosModel();
        $this->requireDatabase();
    }

    #[Test]
    public function testInsertProductoExitosoConDatosCompletos(): void
    {
        $data = [
            'nombre' => 'PHPUnit Producto Válido ' . uniqid(),
            'descripcion' => 'Producto creado por test unitario',
            'unidad_medida' => 'KG',
            'precio' => 25.50,
            'idcategoria' => 1,
            'moneda' => 'USD',
        ];

        $result = $this->model->insertProducto($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('producto_id', $result);
        $this->assertTrue($result['status'], 'Insert debería ser exitoso: ' . ($result['message'] ?? ''));
        $this->assertIsInt($result['producto_id']);
        $this->assertGreaterThan(0, $result['producto_id']);

        self::$productosCreados[] = $result['producto_id'];
    }

    #[Test]
    #[DataProvider('datosInvalidosProductoProvider')]
    public function testInsertProductoConDatosInvalidos(
        array $data,
        string $mensajeEsperadoContiene
    ): void {
        $result = $this->model->insertProducto($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], 'Debería rechazar datos inválidos');
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsStringIgnoringCase(
            $mensajeEsperadoContiene,
            $result['message'],
            'Mensaje de error debería indicar el problema'
        );
    }

    public static function datosInvalidosProductoProvider(): array
    {
        return [
            'nombre_vacio' => [
                [
                    'nombre' => '',
                    'descripcion' => 'Sin nombre',
                    'unidad_medida' => 'KG',
                    'precio' => 10.00,
                    'idcategoria' => 1,
                    'moneda' => 'USD',
                ],
                'producto',
            ],
            'categoria_inexistente' => [
                [
                    'nombre' => 'Prod Cat Invalida ' . PHP_INT_MAX,
                    'descripcion' => 'Categoría no existe',
                    'unidad_medida' => 'KG',
                    'precio' => 10.00,
                    'idcategoria' => 999999,
                    'moneda' => 'USD',
                ],
                'categ',
            ],
        ];
    }

    #[Test]
    public function testInsertProductoDuplicadoRetornaError(): void
    {
        $nombreUnico = 'PHPUnit Duplicado ' . uniqid();
        $data = [
            'nombre' => $nombreUnico,
            'descripcion' => 'Primer insert',
            'unidad_medida' => 'KG',
            'precio' => 10.00,
            'idcategoria' => 1,
            'moneda' => 'USD',
        ];

        $result1 = $this->model->insertProducto($data);
        $this->assertTrue($result1['status'], 'Primera inserción debe ser exitosa');
        self::$productosCreados[] = $result1['producto_id'];

        $result2 = $this->model->insertProducto($data);

        $this->assertIsArray($result2);
        $this->assertFalse($result2['status']);
        $this->assertStringContainsStringIgnoringCase('existe', $result2['message']);
        $this->assertNull($result2['producto_id']);
    }

    #[Test]
    public function testUpdateProductoExitoso(): void
    {
        $id = $this->crearProductoPrueba('PHPUnit Update OK');

        $dataUpdate = [
            'nombre' => 'PHPUnit Actualizado ' . uniqid(),
            'descripcion' => 'Descripción actualizada',
            'unidad_medida' => 'LT',
            'precio' => 99.99,
            'idcategoria' => 1,
            'moneda' => 'BS',
        ];

        $result = $this->model->updateProducto($id, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertTrue($result['status'], 'Update exitoso: ' . ($result['message'] ?? ''));
    }

    #[Test]
    #[DataProvider('datosInvalidosUpdateProvider')]
    public function testUpdateProductoConDatosInvalidos(
        int $idproducto,
        array $data,
        bool $statusEsperado
    ): void {
        $result = $this->model->updateProducto($idproducto, $data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals($statusEsperado, $result['status']);
    }

    public static function datosInvalidosUpdateProvider(): array
    {
        return [
            'id_inexistente' => [
                99999,
                ['nombre' => 'No Existe', 'descripcion' => 'X', 'unidad_medida' => 'KG', 'precio' => 1, 'idcategoria' => 1, 'moneda' => 'USD'],
                false,
            ],
            'nombre_vacio' => [
                1,
                ['nombre' => '', 'descripcion' => 'Sin nombre', 'unidad_medida' => 'KG', 'precio' => 10, 'idcategoria' => 1, 'moneda' => 'USD'],
                false,
            ],
            'precio_negativo' => [
                1,
                ['nombre' => 'Test Negativo', 'descripcion' => 'X', 'unidad_medida' => 'KG', 'precio' => -50.00, 'idcategoria' => 1, 'moneda' => 'USD'],
                false,
            ],
        ];
    }

    #[Test]
    public function testUpdateProductoConNombreDuplicado(): void
    {
        $id1 = $this->crearProductoPrueba('PHPUnit Dup1');
        $nombreProd2 = 'PHPUnit Dup2 ' . uniqid();
        $id2 = $this->crearProductoPrueba($nombreProd2);

        $dataUpdate = [
            'nombre' => $nombreProd2,
            'descripcion' => 'Intento de duplicar',
            'unidad_medida' => 'KG',
            'precio' => 10.00,
            'idcategoria' => 1,
            'moneda' => 'USD',
        ];

        $result = $this->model->updateProducto($id1, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('existe', $result['message']);
    }

    #[Test]
    public function testSelectProductoByIdExistenteRetornaEstructuraCompleta(): void
    {
        $id = $this->crearProductoPrueba('PHPUnit Select');

        $result = $this->model->selectProductoById($id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('idproducto', $result);
        $this->assertArrayHasKey('nombre', $result);
        $this->assertArrayHasKey('descripcion', $result);
        $this->assertArrayHasKey('precio', $result);
        $this->assertArrayHasKey('unidad_medida', $result);
        $this->assertArrayHasKey('estatus', $result);
        $this->assertEquals($id, $result['idproducto']);
    }

    #[Test]
    #[DataProvider('idsInexistentesProvider')]
    public function testSelectProductoByIdInexistente(int $id): void
    {
        $result = $this->model->selectProductoById($id);
        $this->assertFalse($result);
    }

    public static function idsInexistentesProvider(): array
    {
        return [
            'id_grande' => [999999],
            'id_negativo_como_int' => [0],
        ];
    }

    #[Test]
    public function testDeleteProductoExitenteEsSoftDelete(): void
    {
        $id = $this->crearProductoPrueba('PHPUnit Delete');

        $result = $this->model->deleteProductoById($id);

        $this->assertIsArray($result);
        $this->assertTrue($result['status'], 'Delete debería ser exitoso');

        $producto = $this->model->selectProductoById($id);
        if ($producto) {
            $this->assertEquals('INACTIVO', strtoupper($producto['estatus']),
                'El producto debe quedar con estatus INACTIVO (soft-delete)'
            );
        }
    }

    #[Test]
    public function testDeleteProductoInexistenteRetornaFalse(): void
    {
        $result = $this->model->deleteProductoById(999999);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
    }

    #[Test]
    public function testSelectAllProductosRetornaEstructuraCorrecta(): void
    {
        $result = $this->model->selectAllProductos();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
    }

    #[Test]
    public function testSelectAllProductosTieneColumnasEsperadas(): void
    {
        $result = $this->model->selectAllProductos();
        $productos = $result['data'] ?? [];

        if (empty($productos)) {
            $this->markTestSkipped('No hay productos en BD para validar estructura');
        }

        $producto = $productos[0];
        $columnasRequeridas = ['idproducto', 'nombre', 'descripcion', 'precio', 'unidad_medida', 'estatus'];

        foreach ($columnasRequeridas as $columna) {
            $this->assertArrayHasKey($columna, $producto,
                "La columna '{$columna}' debe existir en el resultado"
            );
        }
    }

    #[Test]
    public function testSelectProductosActivosSoloRetornaActivos(): void
    {
        $result = $this->model->selectProductosActivos();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);

        foreach ($result['data'] as $producto) {
            $this->assertEquals('ACTIVO', strtoupper($producto['estatus']),
                "Producto ID={$producto['idproducto']} no debería aparecer con estatus '{$producto['estatus']}'"
            );
        }
    }

    #[Test]
    public function testSelectCategoriasActivasRetornaEstructura(): void
    {
        $result = $this->model->selectCategoriasActivas();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);

        foreach ($result['data'] as $categoria) {
            $this->assertEquals('activo', strtolower($categoria['estatus']));
        }
    }

    private function crearProductoPrueba(string $prefijo): int
    {
        $data = [
            'nombre' => $prefijo . ' ' . uniqid(),
            'descripcion' => 'Producto de test',
            'unidad_medida' => 'KG',
            'precio' => 10.00,
            'idcategoria' => 1,
            'moneda' => 'USD',
        ];

        $result = $this->model->insertProducto($data);
        $this->assertTrue($result['status'], 'Precondición fallida: no se pudo crear producto de prueba');

        self::$productosCreados[] = $result['producto_id'];
        return $result['producto_id'];
    }

    public static function tearDownAfterClass(): void
    {
        if (!empty(self::$productosCreados)) {
            try {
                $model = new ProductosModel();
                foreach (self::$productosCreados as $id) {
                    try {
                        $model->deleteProductoById($id);
                    } catch (\Throwable $e) {
                    }
                }
            } catch (\Throwable $e) {
            }
            self::$productosCreados = [];
        }
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }
}

