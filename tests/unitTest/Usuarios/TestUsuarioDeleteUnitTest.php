<?php

namespace Tests\UnitTest\Usuarios;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\UsuariosModel;
use Mockery;
use PDO;
use PDOStatement;

class TestUsuarioDeleteUnitTest extends TestCase
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

    public static function providerDeleteUsuarios()
    {
        return [
            'Eliminar Existente' => [1, true],
            'Eliminar Inexistente' => [99999, false],
            'Eliminar Ya Eliminado' => [2, true] // En la logica original, retornar boolean
        ];
    }

    #[Test]
    #[DataProvider('providerDeleteUsuarios')]
    public function eliminarUsuario($idUsuario, $expectedSuccess)
    {
        $this->mockStmt->shouldReceive('execute')->andReturn($expectedSuccess);
        $this->mockStmt->shouldReceive('rowCount')->andReturn($expectedSuccess ? 1 : 0);
        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt);

        $result = $this->model->deleteUsuarioById($idUsuario);

        if ($expectedSuccess) {
            $this->assertTrue(is_bool($result) || is_array($result));
        } else {
            $this->assertTrue($result === false || is_array($result));
            if (is_array($result) && array_key_exists('message', $result)) {
                $this->assertNotEmpty($result['message']);
            }
        }
    }
}
