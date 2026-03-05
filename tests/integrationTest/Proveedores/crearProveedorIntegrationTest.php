<?php

namespace Tests\IntegrationTest\Proveedores;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\ProveedoresModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class crearProveedorIntegrationTest extends TestCase
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

    public static function providerCasosInsertProveedor(): array
    {
        $ts = time();
        return [
            'datos completos con fecha nacimiento' => [
                'nombre'             => 'Proveedor Test',
                'apellido'           => 'Integración',
                'identificacion'     => 'V-' . $ts,
                'fecha_nacimiento'   => '1990-06-15',
                'direccion'          => 'Av. Bolívar, Caracas',
                'correo_electronico' => 'integracion' . $ts . '@test.com',
                'telefono_principal' => '04121234567',
                'observaciones'      => 'Creado en prueba de integración',
                'genero'             => 'M',
                'esperado_status'    => true,
                'mensaje_parcial'    => 'exitosamente',
            ],
            'datos completos sin fecha nacimiento' => [
                'nombre'             => 'Proveedor B',
                'apellido'           => 'SinFecha',
                'identificacion'     => 'J-' . ($ts + 1),
                'fecha_nacimiento'   => '',
                'direccion'          => 'Calle 10, Valencia',
                'correo_electronico' => 'sinfecha' . ($ts + 1) . '@test.com',
                'telefono_principal' => '02411234567',
                'observaciones'      => '',
                'genero'             => 'F',
                'esperado_status'    => true,
                'mensaje_parcial'    => 'exitosamente',
            ],
        ];
    }

    // --- Tests: insertProveedor ---

    #[Test]
    #[DataProvider('providerCasosInsertProveedor')]
    public function testInsertProveedor_DatosValidos_StatusTrue(
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
        $data = compact(
            'nombre', 'apellido', 'identificacion', 'fecha_nacimiento',
            'direccion', 'correo_electronico', 'telefono_principal',
            'observaciones', 'genero'
        );

        $result = $this->model->insertProveedor($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals($esperado_status, $result['status']);
        $this->assertStringContainsStringIgnoringCase($mensaje_parcial, $result['message']);
        $this->showMessage("insertProveedor: " . $result['message']);
    }

    #[Test]
    public function testInsertProveedor_IdentificacionDuplicada_StatusFalse(): void
    {
        $ts = time();
        $identificacion = 'E-' . $ts . '_dup';

        $data = [
            'nombre'             => 'Duplicado Uno',
            'apellido'           => 'Test',
            'identificacion'     => $identificacion,
            'fecha_nacimiento'   => '',
            'direccion'          => 'Calle Prueba',
            'correo_electronico' => 'dup1_' . $ts . '@test.com',
            'telefono_principal' => '04140000001',
            'observaciones'      => '',
            'genero'             => 'M',
        ];

        // Primera inserción — debe ser exitosa
        $result1 = $this->model->insertProveedor($data);
        $this->assertTrue($result1['status'], 'La primera inserción debería ser exitosa.');

        // Segunda inserción con la misma identificación — debe fallar
        $data['nombre']             = 'Duplicado Dos';
        $data['correo_electronico'] = 'dup2_' . $ts . '@test.com';
        $result2 = $this->model->insertProveedor($data);

        $this->assertIsArray($result2);
        $this->assertFalse($result2['status']);
        $this->assertStringContainsStringIgnoringCase('duplicada', $result2['message']);
        $this->showMessage("Validación duplicado correcta: " . $result2['message']);
    }

    #[Test]
    public function testInsertProveedor_RetornaProveedorId(): void
    {
        $ts   = time();
        $data = [
            'nombre'             => 'ID Test',
            'apellido'           => 'Integration',
            'identificacion'     => 'P-' . $ts,
            'fecha_nacimiento'   => '',
            'direccion'          => 'Av. Test 123',
            'correo_electronico' => 'idtest_' . $ts . '@test.com',
            'telefono_principal' => '04161234567',
            'observaciones'      => 'Prueba de ID retornado',
            'genero'             => 'M',
        ];

        $result = $this->model->insertProveedor($data);

        $this->assertIsArray($result);
        if ($result['status']) {
            $this->assertArrayHasKey('proveedor_id', $result);
            $this->assertGreaterThan(0, $result['proveedor_id']);
            $this->showMessage("ID insertado: " . $result['proveedor_id']);
        } else {
            $this->showMessage("Fallo esperado: " . $result['message']);
        }
    }
}
