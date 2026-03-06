<?php

namespace Tests\IntegrationTest\Empleados;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\EmpleadosModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class EmpleadoSelectIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private EmpleadosModel $model;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new EmpleadosModel();
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }

    // ─── Data Providers ──────────────────────────────────────────────────────

    public static function providerIdEmpleados(): array
    {
        return [
            'id_existente'    => [1],
            'id_inexistente'  => [99999],
        ];
    }

    // ─── Tests ───────────────────────────────────────────────────────────────

    #[Test]
    public function testSelectAllEmpleados_RetornaArrayConClaves(): void
    {
        $resultado = $this->model->selectAllEmpleados(0);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertArrayHasKey('message', $resultado);
        $this->assertTrue($resultado['status'], 'Se esperaba status true: ' . ($resultado['message'] ?? ''));
        $this->assertIsArray($resultado['data']);
    }

    #[Test]
    public function testSelectAllEmpleados_DataContieneClavesDeEmpleado(): void
    {
        $resultado = $this->model->selectAllEmpleados(0);

        $this->assertTrue($resultado['status']);

        if (!empty($resultado['data'])) {
            $primerEmpleado = $resultado['data'][0];
            $this->assertArrayHasKey('idempleado', $primerEmpleado);
            $this->assertArrayHasKey('nombre', $primerEmpleado);
            $this->assertArrayHasKey('apellido', $primerEmpleado);
            $this->assertArrayHasKey('estatus', $primerEmpleado);
        } else {
            $this->markTestSkipped('No hay empleados en la BD de prueba.');
        }
    }

    #[Test]
    #[DataProvider('providerIdEmpleados')]
    public function testGetEmpleadoById_RetornaArrayOFalso(int $id): void
    {
        $resultado = $this->model->getEmpleadoById($id);

        $this->assertTrue(
            is_array($resultado) || $resultado === false,
            'Se esperaba array o false, se obtuvo: ' . gettype($resultado)
        );
    }

    #[Test]
    public function testGetEmpleadoById_EmpleadoExistente_ContieneClavesObligatorias(): void
    {
        // Primero verifica que haya al menos un empleado
        $todos = $this->model->selectAllEmpleados(0);
        if (empty($todos['data'])) {
            $this->markTestSkipped('No hay empleados en la BD de prueba.');
        }

        $primerEmpleado = $todos['data'][0];
        $id             = $primerEmpleado['idempleado'];

        $resultado = $this->model->getEmpleadoById($id);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('idempleado', $resultado);
        $this->assertArrayHasKey('nombre', $resultado);
        $this->assertArrayHasKey('apellido', $resultado);
        $this->assertEquals($id, $resultado['idempleado']);
    }
}
