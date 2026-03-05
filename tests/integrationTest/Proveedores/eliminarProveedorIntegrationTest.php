<?php

namespace Tests\IntegrationTest\Proveedores;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\ProveedoresModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class eliminarProveedorIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private ?ProveedoresModel $model;

    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "\n[MODEL MESSAGE] " . $msg . "\n");
    }

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new ProveedoresModel();
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }

    // --- DataProviders ---

    public static function providerIdsInexistentes(): array
    {
        return [
            'ID grande inexistente' => [888888 + rand(1, 99999)],
            'ID enorme inexistente' => [999999 + rand(1, 99999)],
        ];
    }

    // --- Tests: deleteProveedorById ---

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testDeleteProveedorById_IdInexistente_RetornaFalse(int $id): void
    {
        $result = $this->model->deleteProveedorById($id);

        $this->assertFalse($result);
        $this->showMessage("deleteProveedorById($id) retornó false correctamente.");
    }

    #[Test]
    public function testDeleteProveedorById_ProveedorExistente_RetornaTrue(): void
    {
        // Crear proveedor temporal para eliminar
        $ts   = time();
        $data = [
            'nombre'             => 'Eliminar C.A.',
            'apellido'           => 'Integration',
            'identificacion'     => 'D-' . $ts,
            'fecha_nacimiento'   => '',
            'direccion'          => 'Dirección provisional',
            'correo_electronico' => 'eliminar_' . $ts . '@test.com',
            'telefono_principal' => '04150000001',
            'observaciones'      => 'Creado para eliminar',
            'genero'             => 'M',
        ];
        $insert = $this->model->insertProveedor($data);

        if (!$insert['status']) {
            $this->markTestSkipped('No se pudo crear el proveedor temporal: ' . $insert['message']);
        }

        $idNuevo = (int) $insert['proveedor_id'];
        $result  = $this->model->deleteProveedorById($idNuevo);

        $this->assertTrue($result);
        $this->showMessage("deleteProveedorById($idNuevo) retornó true correctamente.");
    }

    #[Test]
    public function testDeleteProveedorById_ProveedorYaInactivo_RetornaFalse(): void
    {
        // Crear proveedor, eliminarlo dos veces; la segunda debe retornar false
        $ts   = time() + 1;
        $data = [
            'nombre'             => 'YaInactivo S.A.',
            'apellido'           => 'Test',
            'identificacion'     => 'F-' . $ts,
            'fecha_nacimiento'   => '',
            'direccion'          => 'Avenida sin retorno',
            'correo_electronico' => 'inactivo_' . $ts . '@test.com',
            'telefono_principal' => '04150000002',
            'observaciones'      => '',
            'genero'             => 'F',
        ];
        $insert = $this->model->insertProveedor($data);

        if (!$insert['status']) {
            $this->markTestSkipped('No se pudo crear el proveedor temporal.');
        }

        $id = (int) $insert['proveedor_id'];

        $primero  = $this->model->deleteProveedorById($id);
        $segundo  = $this->model->deleteProveedorById($id);

        $this->assertTrue($primero, 'La primera eliminación debería ser exitosa.');
        $this->assertFalse($segundo, 'La segunda eliminación debería retornar false.');
        $this->showMessage("Doble eliminación validada correctamente para ID $id.");
    }

    // --- Tests: reactivarProveedor ---

    #[Test]
    public function testReactivarProveedor_ProveedorInactivo_StatusTrue(): void
    {
        // Crear, eliminar y luego reactivar un proveedor
        $ts   = time() + 2;
        $data = [
            'nombre'             => 'Reactivar Test',
            'apellido'           => 'Integration',
            'identificacion'     => 'R-' . $ts,
            'fecha_nacimiento'   => '',
            'direccion'          => 'Calle Reactivación',
            'correo_electronico' => 'reactiv_' . $ts . '@test.com',
            'telefono_principal' => '04160000003',
            'observaciones'      => 'Para reactivar',
            'genero'             => 'M',
        ];
        $insert = $this->model->insertProveedor($data);

        if (!$insert['status']) {
            $this->markTestSkipped('No se pudo crear el proveedor temporal.');
        }

        $id = (int) $insert['proveedor_id'];

        // Inactivar primero
        $this->model->deleteProveedorById($id);

        // Reactivar
        $result = $this->model->reactivarProveedor($id);

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
        $this->showMessage("reactivarProveedor($id): " . $result['message']);

        // Limpiar
        $this->model->deleteProveedorById($id);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testReactivarProveedor_IdInexistente_StatusFalse(int $id): void
    {
        $result = $this->model->reactivarProveedor($id);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->showMessage("reactivarProveedor($id) retornó status false correctamente.");
    }
}
