<?php

namespace Tests\IntegrationTest\Clientes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\ClientesModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class insertarClienteIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private ClientesModel $model;
    private static array $idsCreados = [];

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new ClientesModel();
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }

    // -------------------------------------------------------------------------
    // DataProviders
    // -------------------------------------------------------------------------

    public static function providerDatosClienteValidos(): array
    {
        return [
            'cliente_basico' => [[
                'cedula'             => 'V-' . bin2hex(random_bytes(4)),
                'nombre'             => 'ClienteTest',
                'apellido'           => 'IntegracionA',
                'direccion'          => 'Av. Prueba 1',
                'telefono_principal' => '04141000001',
                'estatus'            => 'activo',
                'observaciones'      => 'Test de inserción básica',
            ]],
            'cliente_con_observaciones' => [[
                'cedula'             => 'E-' . bin2hex(random_bytes(4)),
                'nombre'             => 'ClienteExtTest',
                'apellido'           => 'IntegracionB',
                'direccion'          => 'Calle Prueba 2',
                'telefono_principal' => '04161000002',
                'estatus'            => 'activo',
                'observaciones'      => 'Cliente con observaciones de prueba',
            ]],
        ];
    }

    // -------------------------------------------------------------------------
    // insertCliente — exitoso
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerDatosClienteValidos')]
    public function insertClienteExitosoRetornaStatusTrue(array $data): void
    {
        $resultado = $this->model->insertCliente($data);

        if ($resultado['status'] && $resultado['cliente_id']) {
            self::$idsCreados[] = (int) $resultado['cliente_id'];
        }

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status'], 'Falló insertCliente: ' . ($resultado['message'] ?? ''));
        $this->assertNotNull($resultado['cliente_id']);
        $this->assertGreaterThan(0, $resultado['cliente_id']);
    }

    // -------------------------------------------------------------------------
    // insertCliente — cédula duplicada
    // -------------------------------------------------------------------------

    #[Test]
    public function insertClienteCedulaDuplicadaRetornaStatusFalse(): void
    {
        $cedula = 'V-' . bin2hex(random_bytes(4));
        $data   = [
            'cedula'             => $cedula,
            'nombre'             => 'DuplicadoTest',
            'apellido'           => 'Prueba',
            'direccion'          => 'Calle Dup',
            'telefono_principal' => '04141999999',
            'estatus'            => 'activo',
            'observaciones'      => '',
        ];

        // Primera inserción → debe tener éxito
        $primera = $this->model->insertCliente($data);
        if ($primera['status'] && $primera['cliente_id']) {
            self::$idsCreados[] = (int) $primera['cliente_id'];
        }
        $this->assertTrue($primera['status'], 'La primera inserción debería tener éxito.');

        // Segunda inserción con misma cédula → debe fallar
        $segunda = $this->model->insertCliente($data);

        $this->assertFalse($segunda['status']);
        $this->assertStringContainsStringIgnoringCase('cédula', $segunda['message']);
        $this->assertNull($segunda['cliente_id']);
    }

    // -------------------------------------------------------------------------
    // insertClienteCompleto — exitoso
    // -------------------------------------------------------------------------

    #[Test]
    public function insertClienteCompletoExitosoRetornaStatusTrue(): void
    {
        $data = [
            'cedula'             => 'V-COMP-' . bin2hex(random_bytes(4)),
            'nombre'             => 'ClienteCompleto',
            'apellido'           => 'IntegTest',
            'direccion'          => 'Calle Completa 3',
            'telefono_principal' => '04241000003',
            'estatus'            => 'Activo',
            'observaciones'      => 'Insertado mediante insertClienteCompleto',
        ];

        $resultado = $this->model->insertClienteCompleto($data);

        if ($resultado['status'] && $resultado['cliente_id']) {
            self::$idsCreados[] = (int) $resultado['cliente_id'];
        }

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status'], 'Falló insertClienteCompleto: ' . ($resultado['message'] ?? ''));
        $this->assertNotNull($resultado['cliente_id']);
    }

    // -------------------------------------------------------------------------
    // insertClienteCompleto — cédula duplicada
    // -------------------------------------------------------------------------

    #[Test]
    public function insertClienteCompletoCedulaDuplicadaRetornaStatusFalse(): void
    {
        $cedula = 'V-' . bin2hex(random_bytes(4));
        $data   = [
            'cedula'             => $cedula,
            'nombre'             => 'ClienteCompletoDup',
            'apellido'           => 'DupTest',
            'direccion'          => 'Calle Dup',
            'telefono_principal' => '04141888888',
            'estatus'            => 'Activo',
            'observaciones'      => '',
        ];

        $primera = $this->model->insertClienteCompleto($data);
        if ($primera['status'] && $primera['cliente_id']) {
            self::$idsCreados[] = (int) $primera['cliente_id'];
        }
        $this->assertTrue($primera['status']);

        $segunda = $this->model->insertClienteCompleto($data);

        $this->assertFalse($segunda['status']);
        $this->assertNull($segunda['cliente_id']);
    }
}
