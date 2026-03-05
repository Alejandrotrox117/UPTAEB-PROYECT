<?php

namespace Tests\IntegrationTest\Productos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\ProductosModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class insertarProductoIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private ProductosModel $model;

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

    public static function providerInsertValidos(): array
    {
        return [
            'producto_kg_usd' => [[
                'nombre'       => 'Cartón Corrugado Integ ' . uniqid(),
                'descripcion'  => 'Material de reciclaje mezclado',
                'unidad_medida'=> 'KG',
                'precio'       => 0.15,
                'idcategoria'  => 1,
                'moneda'       => 'USD',
            ]],
            'producto_lt_bs' => [[
                'nombre'       => 'Aceite Mineral Integ ' . uniqid(),
                'descripcion'  => 'Aceite recuperado de motores',
                'unidad_medida'=> 'LT',
                'precio'       => 2.50,
                'idcategoria'  => 1,
                'moneda'       => 'BS',
            ]],
        ];
    }

    public static function providerInsertInvalidos(): array
    {
        return [
            'categoria_inexistente' => [
                [
                    'nombre'       => 'Producto Cat Inexistente ' . uniqid(),
                    'descripcion'  => 'Categoría no existe',
                    'unidad_medida'=> 'KG',
                    'precio'       => 10.00,
                    'idcategoria'  => 999999,
                    'moneda'       => 'USD',
                ],
                false,
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerInsertValidos')]
    public function testInsertProducto_DatosValidos_RetornaStatusTrue(array $data): void
    {
        $result = $this->model->insertProducto($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('producto_id', $result);
        $this->assertTrue($result['status'], 'Insert fallido: ' . ($result['message'] ?? ''));
        $this->assertIsInt($result['producto_id']);
        $this->assertGreaterThan(0, $result['producto_id']);
        $this->assertEquals('Producto registrado exitosamente.', $result['message']);
    }

    #[Test]
    #[DataProvider('providerInsertInvalidos')]
    public function testInsertProducto_DatosInvalidos_RetornaStatusFalse(array $data, bool $statusEsperado): void
    {
        $result = $this->model->insertProducto($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals($statusEsperado, $result['status']);
        $this->assertArrayHasKey('message', $result);
    }

    #[Test]
    public function testInsertProducto_NombreDuplicado_RetornaStatusFalse(): void
    {
        $nombreUnico = 'Producto Dup Integ ' . time();
        $data = [
            'nombre'       => $nombreUnico,
            'descripcion'  => 'Primera inserción',
            'unidad_medida'=> 'KG',
            'precio'       => 10.00,
            'idcategoria'  => 1,
            'moneda'       => 'USD',
        ];

        $result1 = $this->model->insertProducto($data);
        $this->assertTrue($result1['status'], 'Primera inserción debería ser exitosa');

        $result2 = $this->model->insertProducto($data);

        $this->assertIsArray($result2);
        $this->assertFalse($result2['status']);
        $this->assertStringContainsStringIgnoringCase('existe', $result2['message']);
        $this->assertNull($result2['producto_id']);
    }

    #[Test]
    public function testInsertProducto_RetornaIdAutoincremental(): void
    {
        $data = [
            'nombre'       => 'Prod AutoID Integ ' . uniqid(),
            'descripcion'  => 'Verificar autoincremento',
            'unidad_medida'=> 'KG',
            'precio'       => 1.00,
            'idcategoria'  => 1,
            'moneda'       => 'USD',
        ];

        $result = $this->model->insertProducto($data);

        $this->assertTrue($result['status']);
        $this->assertIsInt($result['producto_id']);
        $this->assertGreaterThan(0, $result['producto_id']);

        // Verificamos que el registro existe en BD
        $producto = $this->model->selectProductoById($result['producto_id']);
        $this->assertIsArray($producto);
        $this->assertEquals($result['producto_id'], $producto['idproducto']);
        $this->assertEquals($data['nombre'], $producto['nombre']);
    }
}
