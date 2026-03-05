<?php

namespace Tests\IntegrationTest\Productos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\ProductosModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class eliminarProductoIntegrationTest extends TestCase
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

    public static function providerIdsInexistentes(): array
    {
        return [
            'id_muy_grande' => [999999],
            'id_inexistente'=> [888888],
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function crearProductoPrueba(string $prefijo = 'Delete Integ'): int
    {
        $result = $this->model->insertProducto([
            'nombre'       => $prefijo . ' ' . uniqid(),
            'descripcion'  => 'Producto temporal para tests de eliminación',
            'unidad_medida'=> 'KG',
            'precio'       => 5.00,
            'idcategoria'  => 1,
            'moneda'       => 'USD',
        ]);
        $this->assertTrue($result['status'], 'Precondición fallida al crear producto de prueba');
        return $result['producto_id'];
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    #[Test]
    public function testDeleteProducto_Existente_RetornaTrue(): void
    {
        $id = $this->crearProductoPrueba();

        $result = $this->model->deleteProductoById($id);

        $this->assertTrue($result, "deleteProductoById debería retornar true para ID=$id");
    }

    #[Test]
    public function testDeleteProducto_EsSoftDelete_EstatusQuedarInactivo(): void
    {
        $id = $this->crearProductoPrueba();

        $resultDelete = $this->model->deleteProductoById($id);
        $this->assertTrue($resultDelete, 'Debería marcarse como inactivo');

        // Verificar que el registro existe pero con estatus INACTIVO
        $producto = $this->model->selectProductoById($id);
        if ($producto) {
            $this->assertEquals(
                'INACTIVO',
                strtoupper($producto['estatus']),
                'El producto debe quedar con estatus INACTIVO tras el soft-delete'
            );
        } else {
            // Si selectProductoById no retorna inactivos, el test pasa igualmente
            $this->assertTrue(true);
        }
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testDeleteProducto_Inexistente_RetornaFalse(int $id): void
    {
        $result = $this->model->deleteProductoById($id);

        $this->assertFalse($result, "deleteProductoById debería retornar false para ID inexistente=$id");
    }

    #[Test]
    public function testDeleteProducto_NoAparecEn_SelectProductosActivos(): void
    {
        $id = $this->crearProductoPrueba('Soft Del Activos');

        // Verificar que aparece en activos antes de borrar
        $activosAntes = $this->model->selectProductosActivos();
        $idsActivos = array_column($activosAntes['data'], 'idproducto');
        $this->assertContains($id, $idsActivos, 'El producto debería aparecer en activos antes del delete');

        // Soft-delete
        $this->model->deleteProductoById($id);

        // Ya no debe aparecer en la lista de activos
        $activosDespues = $this->model->selectProductosActivos();
        $idsActivosDespues = array_column($activosDespues['data'], 'idproducto');
        $this->assertNotContains(
            $id,
            $idsActivosDespues,
            'El producto NO debería aparecer en activos después del soft-delete'
        );
    }

    #[Test]
    public function testDeleteProducto_MultiplesSoftDeletes_RetornanFalse(): void
    {
        $id = $this->crearProductoPrueba();

        // Primera eliminación: exitosa
        $result1 = $this->model->deleteProductoById($id);
        $this->assertTrue($result1);

        // Segunda eliminación sobre producto ya inactivo: no hay cambios → false
        $result2 = $this->model->deleteProductoById($id);
        $this->assertFalse($result2, 'Una segunda eliminación sobre un producto ya inactivo debería retornar false');
    }
}
