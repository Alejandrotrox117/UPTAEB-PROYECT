<?php
namespace Tests\UnitTest\Roles;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Mockery;
use App\Models\RolesintegradoModel;
use PDO;
use PDOStatement;

class RolesIntegradoUnitTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public static function guardarAsignacionesProvider(): array
    {
        return [
            'Exito' => [
                'data' => [
                    'idrol' => 2,
                    'asignaciones' => [
                        [
                            'idmodulo' => 1,
                            'tiene_acceso' => true,
                            'permisos_especificos' => [1, 2]
                        ]
                    ]
                ],
                'expectedStatus' => true,
                'expectedMessage' => 'Configuración guardada exitosamente'
            ],
            'Rol Invalido' => [
                'data' => [
                    'idrol' => 0,
                    'asignaciones' => [
                        [
                            'idmodulo' => 1,
                            'tiene_acceso' => true,
                            'permisos_especificos' => [1]
                        ]
                    ]
                ],
                'expectedStatus' => false,
                'expectedMessage' => 'ID de rol no válido.'
            ]
        ];
    }

    #[Test]
    #[DataProvider('guardarAsignacionesProvider')]
    public function testGuardarAsignacionesRolCompletas(array $data, bool $expectedStatus, string $expectedMessage)
    {
        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')->andReturn(true);

        $mockPdo = Mockery::mock(PDO::class);
        $mockPdo->shouldReceive('prepare')->andReturn($mockStmt);
        $mockPdo->shouldReceive('beginTransaction')->andReturn(true);
        $mockPdo->shouldReceive('commit')->andReturn(true);

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(true);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(true);

        $model = new RolesintegradoModel();
        $result = $model->guardarAsignacionesRolCompletas($data);

        $this->assertEquals($expectedStatus, $result['status']);
        $this->assertStringContainsString($expectedMessage, $result['message']);
    }

    public static function selectAsignacionesProvider(): array
    {
        return [
            'Exito' => [
                'idrol' => 2,
                'fetchResult' => [
                    [
                        'idmodulo' => 1,
                        'nombre_modulo' => 'Dashboard',
                        'descripcion_modulo' => 'Mod',
                        'permisos_especificos_ids' => '1,2',
                        'permisos_especificos_nombres' => 'Leer|Crear',
                        'tiene_acceso_modulo' => 1
                    ]
                ],
                'expectedStatus' => true
            ]
        ];
    }

    #[Test]
    #[DataProvider('selectAsignacionesProvider')]
    public function testSelectAsignacionesRolCompletas(int $idrol, array $fetchResult, bool $expectedStatus)
    {
        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')->andReturn(true);
        $mockStmt->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn($fetchResult);

        $mockPdo = Mockery::mock(PDO::class);
        $mockPdo->shouldReceive('prepare')->andReturn($mockStmt);

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(true);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(true);

        $model = new RolesintegradoModel();
        $result = $model->selectAsignacionesRolCompletas($idrol);

        $this->assertEquals($expectedStatus, $result['status']);
        $this->assertIsArray($result['data']);
        $this->assertNotEmpty($result['data']);
        $this->assertEquals(1, $result['data'][0]['idmodulo']);
    }
}
