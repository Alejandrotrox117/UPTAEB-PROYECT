<?php

namespace Tests\IntegrationTest\Productos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\ProductosModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class actualizarProductoIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private ProductosModel $model;
    private static array $idsCreados = [];

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new ProductosModel();
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }

    // -------------------------------------------------------------------------
    // DataProviders
    // -------------------------------------------------------------------------

    public static function providerUpdateExitoso(): array
    {
        return [
            'cambio_completo' => [
                ['nombre' => 'Actualizado Integ ' . uniqid(), 'descripcion' => 'Desc actualizada', 'unidad_medida' => 'KG', 'precio' => 0.99, 'idcategoria' => 1, 'moneda' => 'USD'],
            ],
            'cambio_moneda' => [
                ['nombre' => 'Prod BS Integ ' . uniqid(), 'descripcion' => 'En bolívares', 'unidad_medida' => 'LT', 'precio' => 3.50, 'idcategoria' => 1, 'moneda' => 'BS'],
            ],
        ];
    }

    public static function providerUpdateInvalido(): array
    {
        return [
            'id_inexistente' => [
                999999,
                ['nombre' => 'Ghost Product', 'descripcion' => 'No existe', 'unidad_medida' => 'KG', 'precio' => 1, 'idcategoria' => 1, 'moneda' => 'USD'],
                false,
            ],
            'categoria_invalida' => [
                null, // ID se asignará dinámicamente en el test
                ['nombre' => 'Prod Cat Invalida ' . uniqid(), 'descripcion' => 'X', 'unidad_medida' => 'KG', 'precio' => 1, 'idcategoria' => 999999, 'moneda' => 'USD'],
                false,
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function crearProductoPrueba(string $prefijo = 'Update Integ'): int
    {
        $result = $this->model->insertProducto([
            'nombre'       => $prefijo . ' ' . uniqid(),
            'descripcion'  => 'Producto temporal para tests de actualización',
            'unidad_medida'=> 'KG',
            'precio'       => 10.00,
            'idcategoria'  => 1,
            'moneda'       => 'USD',
        ]);
        $this->assertTrue($result['status'], 'Precondición fallida al crear producto de prueba');
        self::$idsCreados[] = $result['producto_id'];
        return $result['producto_id'];
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerUpdateExitoso')]
    public function testUpdateProducto_DatosValidos_RetornaStatusTrue(array $dataUpdate): void
    {
        $id = $this->crearProductoPrueba();

        $result = $this->model->updateProducto($id, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertTrue($result['status'], 'Update fallido: ' . ($result['message'] ?? ''));
        $this->assertStringContainsStringIgnoringCase('actualizado', $result['message']);
    }

    #[Test]
    public function testUpdateProducto_VerificaRegistro_PostUpdate(): void
    {
        $id = $this->crearProductoPrueba();
        $nuevoNombre = 'Verificado Post Update ' . uniqid();

        $dataUpdate = [
            'nombre'       => $nuevoNombre,
            'descripcion'  => 'Descripción tras update',
            'unidad_medida'=> 'LT',
            'precio'       => 99.99,
            'idcategoria'  => 1,
            'moneda'       => 'BS',
        ];

        $result = $this->model->updateProducto($id, $dataUpdate);
        $this->assertTrue($result['status']);

        // Verificar que el cambio persistió en BD
        $productoActualizado = $this->model->selectProductoById($id);
        $this->assertIsArray($productoActualizado);
        $this->assertEquals($nuevoNombre, $productoActualizado['nombre']);
        $this->assertEquals('LT', $productoActualizado['unidad_medida']);
    }

    #[Test]
    public function testUpdateProducto_NombreDuplicado_RetornaStatusFalse(): void
    {
        $nombreFijo = 'Prod Dup Update Integ ' . time();

        // Crear primer producto
        $result1 = $this->model->insertProducto([
            'nombre' => $nombreFijo, 'descripcion' => 'Primero',
            'unidad_medida' => 'KG', 'precio' => 5, 'idcategoria' => 1, 'moneda' => 'USD',
        ]);
        $this->assertTrue($result1['status']);
        self::$idsCreados[] = $result1['producto_id'];

        // Crear segundo producto
        $id2 = $this->crearProductoPrueba('Prod2 Dup');

        // Intentar renombrar el segundo con el nombre del primero
        $dataUpdate = [
            'nombre' => $nombreFijo, 'descripcion' => 'Conflicto',
            'unidad_medida' => 'KG', 'precio' => 5, 'idcategoria' => 1, 'moneda' => 'USD',
        ];

        $result = $this->model->updateProducto($id2, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('existe', $result['message']);
    }

    #[Test]
    public function testUpdateProducto_IdInexistente_RetornaStatusFalse(): void
    {
        $dataUpdate = [
            'nombre'       => 'No Existe',
            'descripcion'  => 'ID fantasma',
            'unidad_medida'=> 'KG',
            'precio'       => 1.00,
            'idcategoria'  => 1,
            'moneda'       => 'USD',
        ];

        $result = $this->model->updateProducto(999999, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertArrayHasKey('message', $result);
    }

    #[Test]
    public function testUpdateProducto_PrecioNegativo_RetornaStatusFalse(): void
    {
        $id = $this->crearProductoPrueba();

        $dataUpdate = [
            'nombre'       => 'Precio Negativo Integ',
            'descripcion'  => 'Test precio inválido',
            'unidad_medida'=> 'KG',
            'precio'       => -50.00,
            'idcategoria'  => 1,
            'moneda'       => 'USD',
        ];

        $result = $this->model->updateProducto($id, $dataUpdate);

        $this->assertIsArray($result);
        // La BD o el modelo deben rechazar precio negativo
        $this->assertFalse($result['status']);
    }
}
