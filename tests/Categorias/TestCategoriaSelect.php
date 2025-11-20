<?php
use PHPUnit\Framework\TestCase;
use App\Models\CategoriasModel;
class TestCategoriaSelect extends TestCase
{
    private $model;
    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }
    protected function setUp(): void
    {
        $this->model = new categoriasModel();
    }
    public function testSelectAllCategoriasRetornaArray()
    {
        $result = $this->model->SelectAllCategorias();
        $this->assertIsArray($result);
    }
    public function testSelectAllCategoriasRetornaSoloActivas()
    {
        $categorias = $this->model->SelectAllCategorias();
        foreach ($categorias as $categoria) {
            $this->assertEquals('activo', strtolower($categoria['estatus']));
        }
    }
    public function testSelectAllCategoriasTieneEstructuraCorrecta()
    {
        $categorias = $this->model->SelectAllCategorias();
        if (!empty($categorias)) {
            $categoria = $categorias[0];
            $this->assertArrayHasKey('idcategoria', $categoria);
            $this->assertArrayHasKey('nombre', $categoria);
            $this->assertArrayHasKey('descripcion', $categoria);
            $this->assertArrayHasKey('estatus', $categoria);
        } else {
            $this->markTestSkipped('No hay categorías para verificar estructura');
        }
    }
    public function testGetCategoriaByIdExistente()
    {
        $categorias = $this->model->SelectAllCategorias();
        if (empty($categorias)) {
            $this->markTestSkipped('No hay categorías para probar');
        }
        $idPrueba = $categorias[0]['idcategoria'];
        $categoria = $this->model->getCategoriaById($idPrueba);
        $this->assertIsArray($categoria);
        $this->assertEquals($idPrueba, $categoria['idcategoria']);
    }
    public function testGetCategoriaByIdInexistente()
    {
        $categoria = $this->model->getCategoriaById(99999);
        $this->assertFalse($categoria);
    }
    public function testGetCategoriaByIdNulo()
    {
        try {
            $this->model->getCategoriaById(null);
            $this->fail('Debería lanzar TypeError');
        } catch (TypeError $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
