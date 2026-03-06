<?php

namespace Tests\UnitTest\Login;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use App\Models\LoginModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class LoginAutenticacionUnitTest extends TestCase
{
    private LoginModel $loginModel;
    /** @var \Mockery\MockInterface */
    private $mockPdo;
    /** @var \Mockery\MockInterface */
    private $mockStmt;

    protected function setUp(): void
    {
        ini_set('error_log', 'NUL');

        $this->mockPdo  = Mockery::mock(PDO::class);
        $this->mockStmt = Mockery::mock(PDOStatement::class);

        $this->mockStmt->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStmt->shouldReceive('fetch')->andReturn(false)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStmt)->byDefault();

        $mockConexion = Mockery::mock('overload:App\Core\Conexion');
        $mockConexion->shouldReceive('connect')->andReturn(null);
        $mockConexion->shouldReceive('get_conectGeneral')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('get_conectSeguridad')->andReturn($this->mockPdo);
        $mockConexion->shouldReceive('disconnect')->andReturn(null);

        $this->loginModel = new LoginModel();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // ─── Data Providers ──────────────────────────────────────────────────────

    public static function providerCredencialesInvalidas(): array
    {
        return [
            'email_inexistente'  => ['noexiste@test.com', 'password123'],
            'password_incorrecto' => ['admin@gmail.com', 'wrong_pass'],
            'email_vacio'        => ['', 'password123'],
            'password_vacio'     => ['admin@gmail.com', ''],
            'ambos_vacios'       => ['', ''],
            'usuario_inactivo'   => ['inactivo@test.com', 'password123'],
        ];
    }

    public static function providerIdsInexistentes(): array
    {
        return [
            'id_inexistente' => [999999],
            'id_muy_alto'    => [2147483647],
            'id_cero'        => [0],
        ];
    }

    // ─── Tests: login() ───────────────────────────────────────────────────────

    #[Test]
    public function testLogin_CredencialesValidas_RetornaArrayConDatos(): void
    {
        $filaEsperada = [
            'idusuario' => 1,
            'estatus'   => 'activo',
            'correo'    => 'admin@gmail.com',
        ];
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($filaEsperada);

        $resultado = $this->loginModel->login('admin@gmail.com', 'admin');

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('idusuario', $resultado);
        $this->assertArrayHasKey('correo', $resultado);
        $this->assertArrayHasKey('estatus', $resultado);
        $this->assertEquals(1, $resultado['idusuario']);
        $this->assertEquals('activo', $resultado['estatus']);
    }

    #[Test]
    #[DataProvider('providerCredencialesInvalidas')]
    public function testLogin_CredencialesInvalidas_RetornaFalse(string $email, string $pass): void
    {
        // fetch devuelve false → usuario no encontrado o inactivo
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(false);

        $resultado = $this->loginModel->login($email, $pass);

        $this->assertFalse($resultado);
    }

    #[Test]
    public function testLogin_ExcepcionPDO_RetornaFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \Exception('Error de conexión simulado'));

        $resultado = $this->loginModel->login('admin@gmail.com', 'admin');

        $this->assertFalse($resultado);
    }

    // ─── Tests: sessionLogin() ────────────────────────────────────────────────

    #[Test]
    public function testSessionLogin_IdValido_RetornaArrayCompleto(): void
    {
        $filaEsperada = [
            'idusuario'       => 1,
            'usuario'         => 'admin',
            'estatus'         => 'activo',
            'correo'          => 'admin@gmail.com',
            'idrol'           => 1,
            'rol_nombre'      => 'ADMIN',
            'rol_descripcion' => 'Administrador del sistema',
        ];
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($filaEsperada);

        $resultado = $this->loginModel->sessionLogin(1);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('idusuario', $resultado);
        $this->assertArrayHasKey('usuario', $resultado);
        $this->assertArrayHasKey('idrol', $resultado);
        $this->assertArrayHasKey('rol_nombre', $resultado);
        $this->assertEquals('ADMIN', $resultado['rol_nombre']);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testSessionLogin_IdInexistente_RetornaFalse(int $id): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(false);

        $resultado = $this->loginModel->sessionLogin($id);

        $this->assertFalse($resultado);
    }

    #[Test]
    public function testSessionLogin_ExcepcionPDO_RetornaFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \Exception('Error de conexión simulado'));

        $resultado = $this->loginModel->sessionLogin(1);

        $this->assertFalse($resultado);
    }
}
