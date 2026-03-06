<?php

namespace Tests\IntegrationTest\Categorias;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\CategoriasModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class CategoriasIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private CategoriasModel $model;

    /** ID de categoría creada en setUp para reutilizarla en cada test */
    private ?int $categoriaIdPrueba = null;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new CategoriasModel();
    }

    protected function tearDown(): void
    {
        // Limpiar registro de prueba si fue creado
        if ($this->categoriaIdPrueba !== null) {
            $this->model->deleteCategoria($this->categoriaIdPrueba);
            $this->categoriaIdPrueba = null;
        }
        unset($this->model);
    }

    // -----------------------------------------------------------
    // Helpers privados
    // -----------------------------------------------------------

    /** Inserta una categoría de prueba y guarda su ID. */
    private function crearCategoriaTemp(string $sufijo = ''): void
    {
        $data = [
            'nombre'      => 'Categoria Test ' . time() . $sufijo,
            'descripcion' => 'Categoría temporal de prueba',
            'estatus'     => 'activo',
        ];
        $this->model->insertCategoria($data);

        $todas = $this->model->SelectAllCategorias();
        if (!empty($todas)) {
            $this->categoriaIdPrueba = (int) end($todas)['idcategoria'];
        }
    }

    // ================================================================
    // SelectAllCategorias
    // ================================================================

    #[Test]
    public function testSelectAllCategorias_RetornaArray(): void
    {
        $result = $this->model->SelectAllCategorias();
        $this->assertIsArray($result);
    }

    #[Test]
    public function testSelectAllCategorias_EstructuraCorrecta(): void
    {
        $categorias = $this->model->SelectAllCategorias();

        if (empty($categorias)) {
            $this->markTestSkipped('No hay categorías disponibles para validar estructura.');
        }

        $cat = $categorias[0];
        $this->assertArrayHasKey('idcategoria', $cat);
        $this->assertArrayHasKey('nombre',      $cat);
        $this->assertArrayHasKey('descripcion', $cat);
        $this->assertArrayHasKey('estatus',     $cat);
    }

    // ================================================================
    // insertCategoria
    // ================================================================

    public static function providerInsertCategoria(): array
    {
        return [
            'estatus_minusculas' => [
                ['nombre' => 'Cartón Test',   'descripcion' => 'Reciclable', 'estatus' => 'activo'],
                true,
            ],
            'estatus_mayusculas' => [
                ['nombre' => 'Plástico Test', 'descripcion' => 'PET',        'estatus' => 'ACTIVO'],
                true,
            ],
        ];
    }

    #[Test]
    #[DataProvider('providerInsertCategoria')]
    public function testInsertCategoria_ConDatosValidos_RetornaTrue(array $data, bool $esperado): void
    {
        // Hacer el nombre único para evitar duplicados en ejecuciones repetidas
        $data['nombre'] .= ' ' . time();

        $result = $this->model->insertCategoria($data);
        $this->assertSame($esperado, $result);

        // Limpiar el registro recién insertado
        $todas = $this->model->SelectAllCategorias();
        if (!empty($todas)) {
            $id = (int) end($todas)['idcategoria'];
            $this->model->deleteCategoria($id);
        }
    }

    // ================================================================
    // getCategoriaById
    // ================================================================

    #[Test]
    public function testGetCategoriaById_CuandoExiste_RetornaArrayConId(): void
    {
        $this->crearCategoriaTemp('_getById');

        if ($this->categoriaIdPrueba === null) {
            $this->markTestSkipped('No se pudo crear categoría de prueba.');
        }

        $result = $this->model->getCategoriaById($this->categoriaIdPrueba);

        $this->assertIsArray($result);
        $this->assertEquals($this->categoriaIdPrueba, $result['idcategoria']);
    }

    #[Test]
    public function testGetCategoriaById_CuandoNoExiste_RetornaFalse(): void
    {
        $result = $this->model->getCategoriaById(999999);
        $this->assertFalse($result);
    }

    // ================================================================
    // updateCategoria
    // ================================================================

    public static function providerUpdateCategoria(): array
    {
        return [
            'actualizar_nombre_y_descripcion' => [
                ['nombre' => 'Nombre Actualizado', 'descripcion' => 'Desc actualizada', 'estatus' => 'ACTIVO'],
                true,
            ],
            'cambio_a_inactivo' => [
                ['nombre' => 'Categoria Inactiva', 'descripcion' => 'Sin uso',           'estatus' => 'INACTIVO'],
                true,
            ],
        ];
    }

    #[Test]
    #[DataProvider('providerUpdateCategoria')]
    public function testUpdateCategoria_ConDatosValidos_RetornaTrue(array $campos, bool $esperado): void
    {
        $this->crearCategoriaTemp('_update');

        if ($this->categoriaIdPrueba === null) {
            $this->markTestSkipped('No se pudo crear categoría de prueba.');
        }

        $data = array_merge($campos, ['idcategoria' => $this->categoriaIdPrueba]);
        $result = $this->model->updateCategoria($data);

        $this->assertSame($esperado, $result);
    }

    // ================================================================
    // deleteCategoria (soft-delete)
    // ================================================================

    #[Test]
    public function testDeleteCategoria_CuandoExiste_RetornaTrue(): void
    {
        $this->crearCategoriaTemp('_delete');

        if ($this->categoriaIdPrueba === null) {
            $this->markTestSkipped('No se pudo crear categoría de prueba.');
        }

        $result = $this->model->deleteCategoria($this->categoriaIdPrueba);
        $this->assertTrue($result);

        // Verificar que el estatus cambió a INACTIVO (soft-delete)
        $categoria = $this->model->getCategoriaById($this->categoriaIdPrueba);
        $this->assertIsArray($categoria);
        $this->assertEquals('INACTIVO', strtoupper($categoria['estatus']), 'El estatus debería ser INACTIVO tras el soft-delete.');

        // Evitar doble-delete en tearDown
        $this->categoriaIdPrueba = null;
    }

    #[Test]
    public function testDeleteCategoria_CuandoNoExiste_RetornaIsBool(): void
    {
        $result = $this->model->deleteCategoria(999999);
        $this->assertIsBool($result);
    }

    // ================================================================
    // reactivarCategoria
    // ================================================================

    #[Test]
    public function testReactivarCategoria_CuandoCategoriaInactiva_RetornaTrue(): void
    {
        $this->crearCategoriaTemp('_reactivar');

        if ($this->categoriaIdPrueba === null) {
            $this->markTestSkipped('No se pudo crear categoría de prueba.');
        }

        // Primero desactivar
        $this->model->deleteCategoria($this->categoriaIdPrueba);

        // Luego reactivar
        $result = $this->model->reactivarCategoria($this->categoriaIdPrueba);
        $this->assertTrue($result);

        // Verificar que volvió a ACTIVO
        $categoria = $this->model->getCategoriaById($this->categoriaIdPrueba);
        $this->assertIsArray($categoria);
        $this->assertEquals('ACTIVO', strtoupper($categoria['estatus']));
    }
}
