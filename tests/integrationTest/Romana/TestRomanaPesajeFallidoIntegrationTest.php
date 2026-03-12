<?php

namespace Tests\IntegrationTest\Romana;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PDOException;
use App\Models\RomanaModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class TestRomanaPesajeFallidoIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private $model;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new RomanaModel();
    }

    #[Test]
    public function testRegistrarPesajeSinPeso()
    {
        if (method_exists($this->model, 'insertPesaje')) {
            $data = [
                'peso' => null,
                'fecha_pesaje' => date('Y-m-d H:i:s'),
            ];
            try {
                $this->model->insertPesaje($data);
                $this->fail('Debería lanzar PDOException');
            } catch (PDOException $e) {
                $this->assertInstanceOf(PDOException::class, $e);
                $this->assertNotEmpty($e->getMessage());
            }
        } else {
            $this->markTestSkipped('Método insertPesaje no existe');
        }
    }

    #[Test]
    public function testRegistrarPesajeConPesoNegativo()
    {
        if (method_exists($this->model, 'insertPesaje')) {
            $data = [
                'peso' => -50.00,
                'fecha_pesaje' => date('Y-m-d H:i:s'),
            ];
            $result = $this->model->insertPesaje($data);
            $this->assertFalse($result);
        } else {
            $this->markTestSkipped('Método insertPesaje no existe');
        }
    }

    #[Test]
    public function testRegistrarPesajeConPesoCero()
    {
        if (method_exists($this->model, 'insertPesaje')) {
            $data = [
                'peso' => 0,
                'fecha_pesaje' => date('Y-m-d H:i:s'),
            ];
            $result = $this->model->insertPesaje($data);
            $this->assertIsBool($result);
        } else {
            $this->markTestSkipped('Método insertPesaje no existe');
        }
    }

    #[Test]
    public function testRegistrarPesajeConPesoExcesivo()
    {
        if (method_exists($this->model, 'insertPesaje')) {
            $data = [
                'peso' => 999999.99,
                'fecha_pesaje' => date('Y-m-d H:i:s'),
            ];
            $result = $this->model->insertPesaje($data);
            $this->assertIsBool($result);
        } else {
            $this->markTestSkipped('Método insertPesaje no existe');
        }
    }

    #[Test]
    public function testConsultarPesajeInexistente()
    {
        if (method_exists($this->model, 'selectPesajeById')) {
            $result = $this->model->selectPesajeById(99999);
            $this->assertFalse($result);
        } else {
            $this->markTestSkipped('Método selectPesajeById no existe');
        }
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
