<?php

namespace Tests\UnitTest\Pagos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\PagosModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class insertarPagoUnitTest extends TestCase
{
    private PagosModel $pagosModel;
    /** @var \Mockery\MockInterface */
    private $mockPdo;
    /** @var \Mockery\MockInterface */
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');

        $this->mockPdo  = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        // Comportamientos por defecto del statement
        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false)->byDefault();
        $this->mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([])->byDefault();
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1)->byDefault();

        // Comportamientos por defecto del PDO mock
        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn("42")->byDefault();
        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('commit')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('rollBack')->andReturn(true)->byDefault();
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(false)->byDefault();

        // Sobrecargar la clase Conexion con un Mock
        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->pagosModel = new PagosModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // -----------------------------------------------------------------------
    // DataProviders
    // -----------------------------------------------------------------------

    public static function providerCasosInsertPagoExitoso(): array
    {
        return [
            'Pago por compra con persona nula' => [
                [
                    'idpersona'     => null,
                    'idtipo_pago'   => 1,
                    'idventa'       => null,
                    'idcompra'      => 1,
                    'idsueldotemp'  => null,
                    'monto'         => 500.00,
                    'referencia'    => 'REF-TEST-001',
                    'fecha_pago'    => '2026-03-05',
                    'observaciones' => 'Pago de prueba unitaria',
                ],
                42,
            ],
            'Pago por venta sin persona (activa balance update)' => [
                [
                    'idpersona'     => null,
                    'idtipo_pago'   => 2,
                    'idventa'       => 10,
                    'idcompra'      => null,
                    'idsueldotemp'  => null,
                    'monto'         => 1200.50,
                    'referencia'    => 'REF-TEST-002',
                    'fecha_pago'    => '2026-03-05',
                    'observaciones' => 'Pago de venta unitaria',
                ],
                42,
            ],
        ];
    }

    public static function providerCasosInsertPagoFallido(): array
    {
        return [
            'Sin campo monto (undefined key)' => [
                [
                    'idpersona'     => null,
                    'idtipo_pago'   => 1,
                    'idventa'       => null,
                    'idcompra'      => 1,
                    'idsueldotemp'  => null,
                    // 'monto' omitido intencionalmente
                    'referencia'    => 'REF-SIN-MONTO',
                    'fecha_pago'    => '2026-03-05',
                    'observaciones' => 'Sin monto',
                ],
                'pdoException',
            ],
            'Monto negativo causa excepción en BD' => [
                [
                    'idpersona'     => null,
                    'idtipo_pago'   => 1,
                    'idventa'       => null,
                    'idcompra'      => 1,
                    'idsueldotemp'  => null,
                    'monto'         => -100.00,
                    'referencia'    => 'REF-NEGATIVO',
                    'fecha_pago'    => '2026-03-05',
                    'observaciones' => 'Monto negativo',
                ],
                'pdoException',
            ],
        ];
    }

    // -----------------------------------------------------------------------
    // Tests de inserción exitosa
    // -----------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerCasosInsertPagoExitoso')]
    public function testInsertPago_DatosCompletos_RetornaStatusTrueConId(array $data, int $idEsperado): void
    {
        // Cuando idpersona no es null se invoca una verificación previa (1 fetch con total)
        if ($data['idpersona'] !== null) {
            $this->mockStmt->shouldReceive('fetch')
                ->with(PDO::FETCH_ASSOC)
                ->once()
                ->andReturn(['total' => 1]);
        }

        // Cuando idventa no es null, verificarEstadoVentaDespuesPago llama fetch 2 veces
        if ($data['idventa'] !== null) {
            $this->mockStmt->shouldReceive('fetch')
                ->with(PDO::FETCH_ASSOC)
                ->andReturn(
                    ['total_pagado' => 0.00],
                    ['total_general' => $data['monto']]
                );
        }

        // Simular lastInsertId devolviendo el ID simulado
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn((string)$idEsperado);

        $resultado = $this->pagosModel->insertPago($data);

        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status'], 'Se esperaba status true en inserción exitosa');
        $this->assertArrayHasKey('data', $resultado);
        $this->assertArrayHasKey('idpago', $resultado['data']);
        $this->assertEquals($idEsperado, $resultado['data']['idpago']);
    }

    // -----------------------------------------------------------------------
    // Tests de inserción fallida (excepciones de BD simuladas)
    // -----------------------------------------------------------------------

    #[Test]
    #[DataProvider('providerCasosInsertPagoFallido')]
    public function testInsertPago_DatosInvalidos_RetornaStatusFalse(array $data, string $tipoFallo): void
    {
        // Simular que el execute del statement lanza PDOException (constraint violation / NULL)
        $this->mockStmt->shouldReceive('execute')
            ->andThrow(new \PDOException('Simulated DB error for invalid data'));

        $resultado = $this->pagosModel->insertPago($data);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status'], 'Se esperaba status false ante error de BD');
        $this->assertArrayHasKey('message', $resultado);
        $this->assertNotEmpty($resultado['message']);
    }

    // -----------------------------------------------------------------------
    // Test: lastInsertId devuelve 0 → status false
    // -----------------------------------------------------------------------

    #[Test]
    public function testInsertPago_CuandoLastInsertIdEsCero_RetornaStatusFalse(): void
    {
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn("0");

        $data = [
            'idpersona'     => null,
            'idtipo_pago'   => 1,
            'idventa'       => null,
            'idcompra'      => 1,
            'idsueldotemp'  => null,
            'monto'         => 100.00,
            'referencia'    => 'REF-CERO-ID',
            'fecha_pago'    => '2026-03-05',
            'observaciones' => 'lastInsertId cero',
        ];

        $resultado = $this->pagosModel->insertPago($data);

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['status']);
    }

    // -----------------------------------------------------------------------
    // Test: idpersona no nulo pero persona NO existe → se pone null y sigue
    // -----------------------------------------------------------------------

    #[Test]
    public function testInsertPago_PersonaInexistente_SeNulifica_YInsertaIgual(): void
    {
        // Verificación de persona devuelve total = 0 (no existe)
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->once()
            ->andReturn(['total' => 0]);

        $this->mockPdo->shouldReceive('lastInsertId')->andReturn("55");

        $data = [
            'idpersona'     => 9999,
            'idtipo_pago'   => 1,
            'idventa'       => null,
            'idcompra'      => 1,
            'idsueldotemp'  => null,
            'monto'         => 300.00,
            'referencia'    => 'REF-PERS-FAKE',
            'fecha_pago'    => '2026-03-05',
            'observaciones' => 'Persona inexistente',
        ];

        $resultado = $this->pagosModel->insertPago($data);

        // El modelo nulifica idpersona y continúa → inserción exitosa
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['status']);
        $this->assertEquals(55, $resultado['data']['idpago']);
    }
}
