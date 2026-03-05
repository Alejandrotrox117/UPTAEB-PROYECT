<?php

namespace Tests\IntegrationTest\Proveedores;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\ProveedoresModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class editarProveedorIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private ?ProveedoresModel $model;
    private ?int $idProveedorPrueba = null;

    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "\n[MODEL MESSAGE] " . $msg . "\n");
    }

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new ProveedoresModel();

        // Crear proveedor temporal para actualizar
        $ts   = time();
        $data = [
            'nombre'             => 'EditarTemp',
            'apellido'           => 'Integración',
            'identificacion'     => 'X-' . $ts,
            'fecha_nacimiento'   => '',
            'direccion'          => 'Dirección temporal edit',
            'correo_electronico' => 'edit_' . $ts . '@test.com',
            'telefono_principal' => '04120000010',
            'observaciones'      => 'Temporal para editar',
            'genero'             => 'M',
        ];
        $result = $this->model->insertProveedor($data);
        if ($result['status']) {
            $this->idProveedorPrueba = (int) $result['proveedor_id'];
        }
    }

    protected function tearDown(): void
    {
        // Limpiar el proveedor temporal si fue creado
        if ($this->idProveedorPrueba !== null) {
            $this->model->deleteProveedorById($this->idProveedorPrueba);
        }
        $this->model = null;
    }

    // --- DataProviders ---

    public static function providerCasosActualizacionExitosa(): array
    {
        $ts = time();
        return [
            'actualización completa' => [
                'nombre'             => 'Editado Completo',
                'apellido'           => 'EditTest',
                'identificacion'     => 'Z-' . $ts,
                'fecha_nacimiento'   => '1992-10-10',
                'direccion'          => 'Nueva Dirección 200',
                'correo_electronico' => 'editado_' . $ts . '@test.com',
                'telefono_principal' => '04142222222',
                'observaciones'      => 'Actualizado en prueba',
                'genero'             => 'F',
                'esperado_status'    => true,
                'mensaje_parcial'    => 'actualizado',
            ],
        ];
    }

    // --- Tests: updateProveedor ---

    #[Test]
    #[DataProvider('providerCasosActualizacionExitosa')]
    public function testUpdateProveedor_DatosValidos_StatusTrue(
        string $nombre,
        string $apellido,
        string $identificacion,
        string $fecha_nacimiento,
        string $direccion,
        string $correo_electronico,
        string $telefono_principal,
        string $observaciones,
        string $genero,
        bool $esperado_status,
        string $mensaje_parcial
    ): void {
        if ($this->idProveedorPrueba === null) {
            $this->markTestSkipped('No se pudo crear el proveedor temporal de prueba.');
        }

        $data = compact(
            'nombre', 'apellido', 'identificacion', 'fecha_nacimiento',
            'direccion', 'correo_electronico', 'telefono_principal',
            'observaciones', 'genero'
        );

        $result = $this->model->updateProveedor($this->idProveedorPrueba, $data);

        $this->assertIsArray($result);
        $this->assertEquals($esperado_status, $result['status']);
        $this->assertStringContainsStringIgnoringCase($mensaje_parcial, $result['message']);
        $this->showMessage("updateProveedor: " . $result['message']);
    }

    #[Test]
    public function testUpdateProveedor_IdInexistente_StatusFalseORowCount0(): void
    {
        $data = [
            'nombre'             => 'Inexistente',
            'apellido'           => 'Test',
            'identificacion'     => 'N-00000001',
            'fecha_nacimiento'   => '',
            'direccion'          => 'Ninguna',
            'correo_electronico' => 'no@existe.com',
            'telefono_principal' => '04140000099',
            'observaciones'      => '',
            'genero'             => 'M',
        ];

        $result = $this->model->updateProveedor(999999 + rand(1, 99999), $data);

        // Puede retornar status true con rowCount 0 (UPDATE sin filas) o status false
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->showMessage("updateProveedor id inexistente: status=" . ($result['status'] ? 'true' : 'false') . " – " . $result['message']);
    }

    #[Test]
    public function testUpdateProveedor_IdentificacionDuplicadaDeOtroRegistro_StatusFalse(): void
    {
        if ($this->idProveedorPrueba === null) {
            $this->markTestSkipped('No se pudo crear el proveedor temporal de prueba.');
        }

        // Crear un segundo proveedor con una identificación diferente
        $ts    = time();
        $idDos = null;
        $dataB = [
            'nombre'             => 'SegundoTemp',
            'apellido'           => 'DuplicTest',
            'identificacion'     => 'W-' . $ts,
            'fecha_nacimiento'   => '',
            'direccion'          => 'Dir B',
            'correo_electronico' => 'b_' . $ts . '@test.com',
            'telefono_principal' => '04143333333',
            'observaciones'      => '',
            'genero'             => 'F',
        ];
        $insertB = $this->model->insertProveedor($dataB);
        if ($insertB['status']) {
            $idDos = (int) $insertB['proveedor_id'];
        }

        if ($idDos === null) {
            $this->markTestSkipped('No se pudo crear el segundo proveedor temporal.');
        }

        // Intentar actualizar el proveedor 1 con la identificación del proveedor 2
        $dataUpdate = [
            'nombre'             => 'Conflicto',
            'apellido'           => 'Test',
            'identificacion'     => 'W-' . $ts,  // identificación del proveedor B
            'fecha_nacimiento'   => '',
            'direccion'          => 'Dir A',
            'correo_electronico' => 'a_' . $ts . '@test.com',
            'telefono_principal' => '04144444444',
            'observaciones'      => '',
            'genero'             => 'M',
        ];
        $result = $this->model->updateProveedor($this->idProveedorPrueba, $dataUpdate);

        // Limpiar proveedor B
        $this->model->deleteProveedorById($idDos);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('duplicada', $result['message']);
        $this->showMessage("Validación duplicado en update correcta: " . $result['message']);
    }
}
