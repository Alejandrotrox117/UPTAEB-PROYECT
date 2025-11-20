<?php
use PHPUnit\Framework\TestCase;
use App\Models\CategoriasModel;
class TestCategoriaInsert extends TestCase
{
    private $model;
    private function showMessage(string $msg)
    {
        fwrite(STDOUT, "\n[MODEL MESSAGE] " . $msg . "\n");
    }
    protected function setUp(): void
    {
        $this->model = new categoriasModel();
    }
    public function testInsertCategoriaConDatosValidos()
    {
        $data = [
            'nombre' => 'Materiales Cartón - Por Clasificar ' . time(),
            'descripcion' => 'Cartón corrugado recibido de recolectores, mezclado con contaminantes',
            'estatus' => 'activo'
        ];
        $result = $this->model->insertCategoria($data);
        $this->assertTrue($result);
    }
    public function testInsertCategoriaSinNombre()
    {
        $data = [
            'nombre' => null,
            'descripcion' => 'Sin nombre',
            'estatus' => 'activo'
        ];
        try {
            $result = $this->model->insertCategoria($data);
            $this->fail('Debería lanzar PDOException');
        } catch (PDOException $e) {
            $this->showMessage("Validación correcta: " . $e->getMessage());
            $this->assertInstanceOf(PDOException::class, $e);
        }
    }
    public function testInsertCategoriaConDatosIncompletos()
    {
        $data = [
            'nombre' => 'Solo nombre'
        ];
        try {
            $this->model->insertCategoria($data);
            $this->fail('Debería lanzar TypeError o Exception');
        } catch (TypeError | Exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
