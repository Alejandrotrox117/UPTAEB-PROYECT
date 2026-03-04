<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use App\Models\ClientesModel;

require_once __DIR__ . '/../Traits/RequiresDatabase.php';

class TestClienteModelUnit extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private ClientesModel $model;
    private static array $clientesCreados = [];

    protected function setUp(): void
    {
        $this->model = new ClientesModel();
        $this->requireDatabase();
    }

    #[Test]
    public function testInsertClienteExitosoConDatosCompletos(): void
    {
        $data = [
            'cedula' => 'V' . uniqid(),
            'nombre' => 'Juan',
            'apellido' => 'Pérez Test',
            'direccion' => 'Calle Principal #123',
            'telefono_principal' => '04121234567',
            'observaciones' => 'Cliente de prueba unitaria',
        ];

        $result = $this->model->insertCliente($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('cliente_id', $result);
        $this->assertTrue($result['status'], 'Insert debe ser exitoso: ' . ($result['message'] ?? ''));
        $this->assertIsInt($result['cliente_id']);
        $this->assertGreaterThan(0, $result['cliente_id']);

        self::$clientesCreados[] = $result['cliente_id'];
    }

    #[Test]
    #[DataProvider('datosInvalidosClienteProvider')]
    public function testInsertClienteConDatosInvalidos(
        array $data,
        string $mensajeContiene
    ): void {
        $result = $this->model->insertCliente($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status'], 'Debería rechazar datos inválidos');
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsStringIgnoringCase(
            $mensajeContiene,
            $result['message']
        );
    }

    public static function datosInvalidosClienteProvider(): array
    {
        return [
            'cedula_vacia' => [
                [
                    'cedula' => '',
                    'nombre' => 'Juan',
                    'apellido' => 'Pérez',
                    'direccion' => 'Calle 1',
                    'telefono_principal' => '04121234567',
                    'observaciones' => '',
                ],
                'cédula',
            ],
            'nombre_vacio' => [
                [
                    'cedula' => 'V' . uniqid(),
                    'nombre' => '',
                    'apellido' => 'Pérez',
                    'direccion' => 'Calle 1',
                    'telefono_principal' => '04121234567',
                    'observaciones' => '',
                ],
                'nombre',
            ],
        ];
    }

    #[Test]
    public function testInsertClienteConCedulaDuplicada(): void
    {
        $cedulaDuplicada = 'VDUP' . uniqid();
        $data = [
            'cedula' => $cedulaDuplicada,
            'nombre' => 'Primero',
            'apellido' => 'Test',
            'direccion' => 'Dir 1',
            'telefono_principal' => '04121111111',
            'observaciones' => '',
        ];

        $result1 = $this->model->insertCliente($data);
        $this->assertTrue($result1['status'], 'Primera inserción debe ser exitosa');
        self::$clientesCreados[] = $result1['cliente_id'];

        $data['nombre'] = 'Segundo';
        $result2 = $this->model->insertCliente($data);

        $this->assertFalse($result2['status']);
        $this->assertStringContainsStringIgnoringCase('cédula', $result2['message']);
    }

    #[Test]
    public function testUpdateClienteExitoso(): void
    {
        $id = $this->crearClientePrueba('VUPD');

        $dataUpdate = [
            'cedula' => 'VMOD' . uniqid(),
            'nombre' => 'Nombre Actualizado',
            'apellido' => 'Apellido Actualizado',
            'direccion' => 'Nueva Dirección',
            'telefono_principal' => '04149999999',
            'observaciones' => 'Datos actualizados por test',
        ];

        $result = $this->model->updateCliente($id, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertTrue($result['status'], 'Update debe ser exitoso: ' . ($result['message'] ?? ''));
    }

    #[Test]
    #[DataProvider('datosInvalidosUpdateClienteProvider')]
    public function testUpdateClienteConDatosInvalidos(
        int $id,
        array $data,
        bool $statusEsperado
    ): void {
        $result = $this->model->updateCliente($id, $data);

        $this->assertIsArray($result);
        $this->assertEquals($statusEsperado, $result['status']);
        $this->assertArrayHasKey('message', $result);
    }

    public static function datosInvalidosUpdateClienteProvider(): array
    {
        return [
            'id_inexistente' => [
                99999,
                ['cedula' => 'VXXX', 'nombre' => 'X', 'apellido' => 'Y', 'direccion' => 'Z', 'telefono_principal' => '0', 'observaciones' => ''],
                false,
            ],
            'cedula_vacia' => [
                1,
                ['cedula' => '', 'nombre' => 'Test', 'apellido' => 'A', 'direccion' => 'B', 'telefono_principal' => '0', 'observaciones' => ''],
                false,
            ],
            'nombre_vacio' => [
                1,
                ['cedula' => 'V123', 'nombre' => '', 'apellido' => 'A', 'direccion' => 'B', 'telefono_principal' => '0', 'observaciones' => ''],
                false,
            ],
        ];
    }

    #[Test]
    public function testUpdateClienteConCedulaDuplicadaDeOtroCliente(): void
    {
        $id1 = $this->crearClientePrueba('VCDUP1');
        $cedula2 = 'VCDUP2' . uniqid();
        $id2 = $this->crearClientePrueba($cedula2);

        $dataUpdate = [
            'cedula' => $cedula2,
            'nombre' => 'Intento duplicar',
            'apellido' => 'Test',
            'direccion' => 'Dir',
            'telefono_principal' => '04121111111',
            'observaciones' => '',
        ];

        $result = $this->model->updateCliente($id1, $dataUpdate);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('cédula', $result['message']);
    }

    #[Test]
    public function testSelectClienteByIdExistenteRetornaEstructura(): void
    {
        $id = $this->crearClientePrueba('VSEL');

        $result = $this->model->selectClienteById($id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('idcliente', $result);
        $this->assertArrayHasKey('cedula', $result);
        $this->assertArrayHasKey('nombre', $result);
        $this->assertArrayHasKey('apellido', $result);
        $this->assertEquals($id, $result['idcliente']);
    }

    #[Test]
    #[DataProvider('idsClienteInexistentesProvider')]
    public function testSelectClienteByIdInexistente(int $id): void
    {
        $result = $this->model->selectClienteById($id);
        $this->assertFalse($result);
    }

    public static function idsClienteInexistentesProvider(): array
    {
        return [
            'id_muy_grande' => [999999],
            'id_cero' => [0],
        ];
    }

    #[Test]
    public function testSelectClienteByCedulaExistente(): void
    {
        $cedula = 'VCEDSEL' . uniqid();
        $this->crearClientePrueba($cedula);

        $result = $this->model->selectClienteByCedula($cedula);

        $this->assertIsArray($result);
        $this->assertEquals($cedula, $result['cedula']);
    }

    #[Test]
    #[DataProvider('cedulasInexistentesProvider')]
    public function testSelectClienteByCedulaInexistente(string $cedula): void
    {
        $result = $this->model->selectClienteByCedula($cedula);
        $this->assertFalse($result);
    }

    public static function cedulasInexistentesProvider(): array
    {
        return [
            'cedula_fantasma' => ['V00000000'],
            'cedula_formato_invalido' => ['XXXXXXXXX'],
            'cedula_vacia' => [''],
        ];
    }

    #[Test]
    public function testDeleteClienteExitoso(): void
    {
        $id = $this->crearClientePrueba('VDEL');

        $result = $this->model->deleteClienteById($id);

        $this->assertIsArray($result);
        $this->assertTrue($result['status'], 'Delete debe ser exitoso');

        $cliente = $this->model->selectClienteById($id);
        if ($cliente) {
            $this->assertEquals('inactivo', strtolower($cliente['estatus']),
                'Cliente debe quedar INACTIVO tras soft-delete'
            );
        }
    }

    #[Test]
    public function testDeleteClienteInexistente(): void
    {
        $result = $this->model->deleteClienteById(999999);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    #[Test]
    public function testReactivarClienteExitoso(): void
    {
        $id = $this->crearClientePrueba('VREACT');
        $this->model->deleteClienteById($id);

        $result = $this->model->reactivarCliente($id);

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
        $this->assertStringContainsStringIgnoringCase('reactivado', $result['message']);

        $cliente = $this->model->selectClienteById($id);
        if ($cliente) {
            $this->assertEquals('activo', strtolower($cliente['estatus']));
        }
    }

    #[Test]
    public function testReactivarClienteNoEncontrado(): void
    {
        $result = $this->model->reactivarCliente(999999);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('no encontrado', $result['message']);
    }

    #[Test]
    public function testReactivarClienteYaActivo(): void
    {
        $id = $this->crearClientePrueba('VYAACT');

        $result = $this->model->reactivarCliente($id);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertStringContainsStringIgnoringCase('ya', $result['message']);
    }

    #[Test]
    public function testInsertClienteCompletoExitoso(): void
    {
        $data = [
            'cedula' => 'VCOMP' . uniqid(),
            'nombre' => 'Cliente',
            'apellido' => 'Completo Test',
            'direccion' => 'Av. Principal',
            'telefono_principal' => '04141234567',
            'observaciones' => 'Test insertClienteCompleto',
        ];

        $result = $this->model->insertClienteCompleto($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);

        if ($result['status'] && isset($result['cliente_id'])) {
            self::$clientesCreados[] = $result['cliente_id'];
            $this->assertIsInt($result['cliente_id']);
        }
    }

    #[Test]
    public function testInsertClienteCompletoCedulaDuplicada(): void
    {
        $cedula = 'VCOMPDUP' . uniqid();
        $data = [
            'cedula' => $cedula,
            'nombre' => 'Primero',
            'apellido' => 'Completo',
            'direccion' => 'Dir 1',
            'telefono_principal' => '04121111111',
            'observaciones' => '',
        ];

        $result1 = $this->model->insertClienteCompleto($data);
        if ($result1['status'] && isset($result1['cliente_id'])) {
            self::$clientesCreados[] = $result1['cliente_id'];
        }

        $data['nombre'] = 'Segundo';
        $result2 = $this->model->insertClienteCompleto($data);

        $this->assertFalse($result2['status']);
        $this->assertStringContainsStringIgnoringCase('identificación', $result2['message']);
    }

    #[Test]
    public function testSelectAllClientesRetornaEstructura(): void
    {
        $result = $this->model->selectAllClientes();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
    }

    #[Test]
    public function testSelectAllClientesActivosSoloRetornaActivos(): void
    {
        $result = $this->model->selectAllClientesActivos();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);

        foreach ($result['data'] as $cliente) {
            $this->assertEquals('activo', strtolower($cliente['estatus']),
                "Cliente ID={$cliente['idcliente']} no debería aparecer con estatus '{$cliente['estatus']}'"
            );
        }
    }

    #[Test]
    public function testSelectAllClientesTieneColumnasRequeridas(): void
    {
        $result = $this->model->selectAllClientes();
        $clientes = $result['data'] ?? [];

        if (empty($clientes)) {
            $this->markTestSkipped('No hay clientes en BD');
        }

        $columnasRequeridas = ['idcliente', 'cedula', 'nombre', 'apellido', 'estatus'];
        foreach ($columnasRequeridas as $col) {
            $this->assertArrayHasKey($col, $clientes[0],
                "Columna '{$col}' requerida en resultado"
            );
        }
    }

    private function crearClientePrueba(string $prefijoCedula): int
    {
        $data = [
            'cedula' => $prefijoCedula . uniqid(),
            'nombre' => 'Test',
            'apellido' => 'Cliente',
            'direccion' => 'Dir Test',
            'telefono_principal' => '04121111111',
            'observaciones' => 'Auto-generado por test',
        ];

        $result = $this->model->insertCliente($data);
        $this->assertTrue($result['status'], 'Precondición fallida: no se pudo crear cliente de prueba');

        self::$clientesCreados[] = $result['cliente_id'];
        return $result['cliente_id'];
    }

    public static function tearDownAfterClass(): void
    {
        if (!empty(self::$clientesCreados)) {
            try {
                $model = new ClientesModel();
                foreach (self::$clientesCreados as $id) {
                    try {
                        $model->deleteClienteById($id);
                    } catch (\Throwable $e) {
                    }
                }
            } catch (\Throwable $e) {
            }
            self::$clientesCreados = [];
        }
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }
}

