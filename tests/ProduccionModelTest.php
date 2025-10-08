<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../app/models/produccionModel.php';

class ProduccionModelTest extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new ProduccionModel();
    }

    public function testInsertLote()
    {
        $data = [
            'fecha_jornada' => date('Y-m-d'),
            'volumen_estimado' => 100,
            'idsupervisor' => 1,
            'observaciones' => 'Test lote',
        ];
        $result = $this->model->insertLote($data);
        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
        $this->assertArrayHasKey('lote_id', $result);
        // Guardar el ID para otras pruebas
        return $result['lote_id'];
    }

    /**
     * @depends testInsertLote
     */
    public function testSelectLoteById($idlote)
    {
        $result = $this->model->selectLoteById($idlote);
        $this->assertIsArray($result);
        $this->assertEquals($idlote, $result['idlote']);
    }

    /**
     * @depends testInsertLote
     */
    public function testDeleteLote($idlote)
    {
        // Suponiendo que tienes un método para eliminar lote, por ejemplo deleteLote($idlote)
        if (method_exists($this->model, 'deleteLote')) {
            $result = $this->model->deleteLote($idlote);
            $this->assertIsArray($result);
            $this->assertTrue($result['status']);
        } else {
            $this->markTestIncomplete('No existe el método deleteLote en ProduccionModel');
        }
    }
}
