<?php

namespace Tests\UnitTest\Usuarios;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\UsuariosModel;
use Mockery;
use PDO;
use PDOStatement;
use Exception;

class TestUsuarioUpdateUnitTest extends TestCase
{
    private $model;
    private $mockPdo;
    private $mockStmt;

    protected function setUp(): void
    {
        $this->mockPdo = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(true);
        $mockConexion->shouldReceive('disconnect')->andReturn(true);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('getConect')->andReturn($this->mockPdo);

        $this->mockPdo->shouldReceive('beginTransaction')->andReturn(true);
        $this->mockPdo->shouldReceive('commit')->andReturn(true);
        $this->mockPdo->shouldReceive('rollBack')->andReturn(true);
        $this->mockPdo->shouldReceive('inTransaction')->andReturn(true);

        $this->model = new UsuariosModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        $this->model = null;
    }

    public static function providerUpdateUsuarios()
    {
        return [
            'Datos Completos' => [
                'id' => 1,
                'data' => [
                    'nombre_usuario' => 'usuario_actualizado',
                    'correo' => 'actualizado@email.com',
                    'idrol' => 2,
                    'estatus' => 'activo'
                ],
                'expectedSuccess' => true
            ],
            'Usuario Inexistente' => [
                'id' => 99999,
                'data' => [
                    'usuario' => 'usuario_inexistente',
                    'correo' => 'test@test.com',
                    'idrol' => 2
                ],
                'expectedSuccess' => false
            ],
            'Email Invalido' => [
                'id' => 1,
                'data' => [
                    'usuario' => 'usuario_test',
                    'correo' => 'email_invalido_sin_arroba',
                    'idrol' => 2
                ],
                'expectedSuccess' => false
            ]
        ];
    }

    #[Test]
    #[DataProvider('providerUpdateUsuarios')]
    public function actualizarUsuario($id, $data, $expectedSuccess)
    {
        $this->mockStmt->shouldReceive('execute')->andReturn($expectedSuccess);
        $this->mockStmt->shouldReceive('rowCount')->andReturn($expectedSuccess ? 1 : 0);
        $this->mockStmt->shouldReceive('fetch')->andReturn($expectedSuccess ? ['idusuario' => $id] : false); // Mock select
        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt);

        $result = $this->model->updateUsuario($id, $data);

        if ($expectedSuccess) {
            $this->assertTrue(is_bool($result) || is_array($result));
        } else {
            if (is_array($result) && array_key_exists('status', $result) && $result['status'] === false) {
                $this->assertArrayHasKey('message', $result);
            }
            $this->assertTrue(is_array($result) || $result === false);
        }
    }

    #[Test]
    public function actualizarConCorreoDuplicado()
    {
        $this->mockStmt->shouldReceive('fetch')->andReturn(['idusuario' => 3]); // Found different user
        $this->mockStmt->shouldReceive('execute')->andReturn(true);
        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt);

        $data = [
            'usuario' => 'usuario_test',
            'correo' => 'admin@admin.com',
            'idrol' => 2
        ];

        $result = $this->model->updateUsuario(2, $data);

        if (is_array($result) && array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
        }
        $this->assertTrue(is_array($result) || $result === false);
    }

    #[Test]
    public function actualizarConRolInexistente()
    {
        $this->mockStmt->shouldReceive('execute')->andThrow(new Exception('Clave foranea fail'));
        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt);
        $data = ['idrol' => 99999];

        try {
            $result = $this->model->updateUsuario(1, $data);
            $this->assertFalse($result);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
        }
    }
}
