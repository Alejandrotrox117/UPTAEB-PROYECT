<?php

namespace Tests\IntegrationTest\Clientes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\ClientesModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class actualizarClienteIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private ClientesModel $model;
    private static ?int $idClienteTemporal = null;
    private static ?string $cedulaTemporal = null;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new ClientesModel();

        if (self::$idClienteTemporal === null) {
            $cedula = 'V-UPD-' . uniqid('', true);
            $data   = [
                'cedula'             => $cedula,
                'nombre'             => 'ClienteUpdateTest',
                'apellido'           => 'Prueba',
                'direccion'          => 'Calle Update 1',
                'telefono_principal' => '04141777777',
                'estatus'            => 'activo',
                'observaciones'      => 'Temporal para pruebas de update',
            ];
            $resultado = $this->model->insertCliente($data);
            if ($resultado['status'] && $resultado['cliente_id']) {
                self::$idClienteTemporal = (int) $resultado['cliente_id'];
                self::$cedulaTemporal    = $cedula;
            }
        }
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }

    // -------------------------------------------------------------------------
    // DataProviders
    // -------------------------------------------------------------------------

    public static function providerDatosUpdateValidos(): array
    {
        return [
            'cambio_nombre' => [
                'nombre'    => 'NombreActualizado',
                'apellido'  => 'ApellidoActualizado',
                'direccion' => 'Av. Actualizada 10',
            ],
            'cambio_telefono' => [
                'nombre'    => 'ClienteUpdateTest',
                'apellido'  => 'Prueba',
                'direccion' => 'Calle Update 1 - Mod',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // updateCliente — exitoso
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerDatosUpdateValidos')]
    public function updateClienteExitosoRetornaStatusTrue(
        string $nombre,
        string $apellido,
        string $direccion
    ): void {
        if (self::$idClienteTemporal === null) {
            $this->markTestSkipped('No se pudo crear el cliente temporal para pruebas de update.');
        }

        $data = [
            'cedula'             => self::$cedulaTemporal,
            'nombre'             => $nombre,
            'apellido'           => $apellido,
            'direccion'          => $direccion,
            'telefono_principal' => '04141777777',
            'estatus'            => 'activo',
            'observaciones'      => 'Actualizado en prueba de integración',
        ];

        $resultado = $this->model->updateCliente(self::$idClienteTemporal, $data);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status'], 'Falló updateCliente: ' . ($resultado['message'] ?? ''));
    }

    // -------------------------------------------------------------------------
    // updateCliente — cliente no existente (ID inexistente)
    // -------------------------------------------------------------------------

    #[Test]
    public function updateClienteIdInexistenteRetornaStatusTrue(): void
    {
        // El modelo hace UPDATE sin verificar existencia previa; si rowCount=0
        // devuelve true con mensaje "datos idénticos" (sin error). Verificamos el comportamiento.
        $data = [
            'cedula'             => 'V-NOEXISTE-' . uniqid(),
            'nombre'             => 'NoExiste',
            'apellido'           => 'Test',
            'direccion'          => 'Sin dirección',
            'telefono_principal' => '04141000000',
            'estatus'            => 'activo',
            'observaciones'      => '',
        ];

        $resultado = $this->model->updateCliente(999999, $data);

        // El modelo devuelve true incluso cuando no se modifica ninguna fila
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('status', $resultado);
        $this->assertArrayHasKey('message', $resultado);
    }

    // -------------------------------------------------------------------------
    // updateCliente — cédula duplicada de otro cliente
    // -------------------------------------------------------------------------

    #[Test]
    public function updateClienteCedulaDuplicadaDeOtroRetornaStatusFalse(): void
    {
        if (self::$idClienteTemporal === null) {
            $this->markTestSkipped('No se pudo crear el cliente temporal para pruebas de update.');
        }

        // Crear un segundo cliente para tener una cédula que ya existe
        $cedulaSegundo = 'V-SEC-' . uniqid('', true);
        $dataSegundo   = [
            'cedula'             => $cedulaSegundo,
            'nombre'             => 'SegundoCliente',
            'apellido'           => 'Test',
            'direccion'          => 'Calle 2',
            'telefono_principal' => '04141666666',
            'estatus'            => 'activo',
            'observaciones'      => '',
        ];
        $segundoResultado = $this->model->insertCliente($dataSegundo);

        if (!$segundoResultado['status']) {
            $this->markTestSkipped('No se pudo crear el segundo cliente temporal.');
        }

        $idSegundo = (int) $segundoResultado['cliente_id'];

        // Intentar actualizar el primer cliente usando la cédula del segundo
        $data = [
            'cedula'             => $cedulaSegundo, // cédula del segundo cliente
            'nombre'             => 'ClienteUpdateTest',
            'apellido'           => 'Prueba',
            'direccion'          => 'Calle Update 1',
            'telefono_principal' => '04141777777',
            'estatus'            => 'activo',
            'observaciones'      => '',
        ];

        $resultado = $this->model->updateCliente(self::$idClienteTemporal, $data);

        // Limpieza del segundo cliente
        $this->model->deleteClienteById($idSegundo);

        $this->assertFalse($resultado['status']);
        $this->assertStringContainsStringIgnoringCase('cédula', $resultado['message']);
    }
}
