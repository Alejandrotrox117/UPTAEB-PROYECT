<?php
namespace Tests\UnitTest\Roles;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Mockery;
use App\Models\RolesModel;
use PDO;
use PDOStatement;

class RolesUnitTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public static function insertRolProvider(): array
    {
        return [
            'Exito' => [
                'data' => [
                    'nombre' => 'ADMIN',
                    'descripcion' => 'Administrador',
                    'estatus' => 'ACTIVO'
                ],
                'nombreExiste' => false,
                'lastInsertId' => 1,
                'dbExecuteReturn' => true,
                'expected' => ['status' => true, 'message' => 'Rol registrado exitosamente.', 'rol_id' => 1]
            ],
            'Nombre Existente' => [
                'data' => [
                    'nombre' => 'ADMIN',
                    'descripcion' => 'Administrador',
                    'estatus' => 'ACTIVO'
                ],
                'nombreExiste' => true,
                'lastInsertId' => 0,
                'dbExecuteReturn' => true,
                'expected' => ['status' => false, 'message' => 'Ya existe un rol activo con ese nombre.']
            ],
        ];
    }

    #[Test]
    #[DataProvider('insertRolProvider')]
    public function testInsertRol(array $data, bool $nombreExiste, int $lastInsertId, bool $dbExecuteReturn, array $expected)
    {
        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')->andReturn($dbExecuteReturn);

        if ($nombreExiste) {
            $mockStmt->shouldReceive('fetch')->andReturn(['idrol' => 1]);
        } else {
            $mockStmt->shouldReceive('fetch')->andReturn(false);
        }

        $mockPdo = Mockery::mock(PDO::class);
        $mockPdo->shouldReceive('prepare')->andReturn($mockStmt);
        $mockPdo->shouldReceive('beginTransaction')->andReturn(true);
        $mockPdo->shouldReceive('commit')->andReturn(true);
        $mockPdo->shouldReceive('lastInsertId')->andReturn((string) $lastInsertId);

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(true);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(true);

        $model = new RolesModel();
        $result = $model->insertRol($data);

        $this->assertEquals($expected['status'], $result['status']);
        $this->assertStringContainsString($expected['message'], $result['message']);
        if (isset($expected['rol_id'])) {
            $this->assertEquals($expected['rol_id'], $result['rol_id']);
        }
    }

    public static function selectRolByIdProvider(): array
    {
        return [
            'Existente' => [
                'idrol' => 1,
                'fetchResult' => ['idrol' => 1, 'nombre' => 'ADMIN'],
                'expected' => ['idrol' => 1, 'nombre' => 'ADMIN']
            ],
            'Inexistente' => [
                'idrol' => 999,
                'fetchResult' => false,
                'expected' => false
            ]
        ];
    }

    #[Test]
    #[DataProvider('selectRolByIdProvider')]
    public function testSelectRolById(int $idrol, $fetchResult, $expected)
    {
        $mockStmt = Mockery::mock(PDOStatement::class);
        $mockStmt->shouldReceive('execute')->andReturn(true);
        $mockStmt->shouldReceive('fetch')->with(PDO::FETCH_ASSOC)->andReturn($fetchResult);

        $mockPdo = Mockery::mock(PDO::class);
        $mockPdo->shouldReceive('prepare')->andReturn($mockStmt);

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(true);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(true);

        $model = new RolesModel();
        $result = $model->selectRolById($idrol);

        if ($expected === false) {
            $this->assertFalse($result);
        } else {
            $this->assertEquals($expected['idrol'], $result['idrol']);
        }
    }
}
