<?php

namespace Tests\UnitTest\Login;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use App\Models\LoginModel;
use Mockery;
use PDO;
use PDOStatement;

#[RunTestsInSeparateProcesses]
class LoginRecuperacionUnitTest extends TestCase
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
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0)->byDefault();

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

    public static function providerEmailsInexistentes(): array
    {
        return [
            'email_no_registrado' => ['noexiste_' . time() . '@test.com'],
            'email_vacio'         => [''],
            'formato_invalido'    => ['email_sin_arroba'],
        ];
    }

    public static function providerTokensInvalidos(): array
    {
        return [
            'token_aleatorio'  => ['token_' . bin2hex(random_bytes(8))],
            'token_vacio'      => [''],
            'token_expirado'   => ['expired_token_abc123'],
        ];
    }

    public static function providerIdsConHash(): array
    {
        return [
            'id_cero'          => [0, password_hash('nuevo_pass', PASSWORD_DEFAULT)],
            'id_negativo'      => [-1, password_hash('otro_pass', PASSWORD_DEFAULT)],
            'id_inexistente'   => [999999, password_hash('pass_test', PASSWORD_DEFAULT)],
        ];
    }

    // ─── Tests: getUsuarioEmail() ─────────────────────────────────────────────

    #[Test]
    public function testGetUsuarioEmail_EmailExistente_RetornaArray(): void
    {
        $filaEsperada = [
            'idusuario' => 1,
            'usuario'   => 'admin',
            'correo'    => 'admin@test.com',
            'estatus'   => 'activo',
        ];
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($filaEsperada);

        $resultado = $this->loginModel->getUsuarioEmail('admin@test.com');

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('idusuario', $resultado);
        $this->assertArrayHasKey('correo', $resultado);
        $this->assertArrayHasKey('usuario', $resultado);
        $this->assertEquals('admin@test.com', $resultado['correo']);
    }

    #[Test]
    #[DataProvider('providerEmailsInexistentes')]
    public function testGetUsuarioEmail_EmailInexistente_RetornaFalse(string $email): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(false);

        $resultado = $this->loginModel->getUsuarioEmail($email);

        $this->assertFalse($resultado);
    }

    #[Test]
    public function testGetUsuarioEmail_ExcepcionPDO_RetornaFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \Exception('Error de conexión simulado'));

        $resultado = $this->loginModel->getUsuarioEmail('admin@test.com');

        $this->assertFalse($resultado);
    }

    // ─── Tests: getTokenUserByToken() ────────────────────────────────────────

    #[Test]
    public function testGetTokenUserByToken_TokenValido_RetornaArray(): void
    {
        $filaEsperada = [
            'idusuario' => 1,
            'correo'    => 'admin@test.com',
            'token'     => 'valid_token_abc123',
            'estatus'   => 'activo',
        ];
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($filaEsperada);

        $resultado = $this->loginModel->getTokenUserByToken('valid_token_abc123');

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('idusuario', $resultado);
        $this->assertArrayHasKey('token', $resultado);
    }

    #[Test]
    #[DataProvider('providerTokensInvalidos')]
    public function testGetTokenUserByToken_TokenInvalido_RetornaFalse(string $token): void
    {
        $this->mockStmt->shouldReceive('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->andReturn(false);

        $resultado = $this->loginModel->getTokenUserByToken($token);

        $this->assertFalse($resultado);
    }

    #[Test]
    public function testGetTokenUserByToken_ExcepcionPDO_RetornaFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \Exception('Error de conexión simulado'));

        $resultado = $this->loginModel->getTokenUserByToken('cualquier_token');

        $this->assertFalse($resultado);
    }

    // ─── Tests: updatePassword() ─────────────────────────────────────────────

    #[Test]
    public function testUpdatePassword_UsuarioValido_RetornaTrue(): void
    {
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1);

        $hash      = password_hash('nuevo_password', PASSWORD_DEFAULT);
        $resultado = $this->loginModel->updatePassword(1, $hash);

        $this->assertTrue($resultado);
    }

    #[Test]
    #[DataProvider('providerIdsConHash')]
    public function testUpdatePassword_UsuarioInexistente_RetornaFalse(int $id, string $hash): void
    {
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0);

        $resultado = $this->loginModel->updatePassword($id, $hash);

        $this->assertFalse($resultado);
    }

    #[Test]
    public function testUpdatePassword_ExcepcionPDO_RetornaFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \Exception('Error de conexión simulado'));

        $resultado = $this->loginModel->updatePassword(1, 'hash_cualquiera');

        $this->assertFalse($resultado);
    }

    // ─── Tests: setTokenUser() ────────────────────────────────────────────────

    #[Test]
    public function testSetTokenUser_ActualizacionExitosa_RetornaTrue(): void
    {
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1);

        $resultado = $this->loginModel->setTokenUser(1, bin2hex(random_bytes(16)));

        $this->assertTrue($resultado);
    }

    #[Test]
    public function testSetTokenUser_UsuarioInexistente_RetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0);

        $resultado = $this->loginModel->setTokenUser(999999, bin2hex(random_bytes(16)));

        $this->assertFalse($resultado);
    }

    #[Test]
    public function testSetTokenUser_ExcepcionPDO_RetornaFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \Exception('Error de conexión simulado'));

        $resultado = $this->loginModel->setTokenUser(1, 'token_test');

        $this->assertFalse($resultado);
    }

    // ─── Tests: deleteToken() ────────────────────────────────────────────────

    #[Test]
    public function testDeleteToken_TokenExistente_RetornaTrue(): void
    {
        $this->mockStmt->shouldReceive('rowCount')->andReturn(1);

        $resultado = $this->loginModel->deleteToken('token_actual_valido');

        $this->assertTrue($resultado);
    }

    #[Test]
    public function testDeleteToken_TokenInexistente_RetornaFalse(): void
    {
        $this->mockStmt->shouldReceive('rowCount')->andReturn(0);

        $resultado = $this->loginModel->deleteToken('token_inexistente');

        $this->assertFalse($resultado);
    }

    #[Test]
    public function testDeleteToken_ExcepcionPDO_RetornaFalse(): void
    {
        $this->mockPdo->shouldReceive('prepare')
            ->andThrow(new \Exception('Error de conexión simulado'));

        $resultado = $this->loginModel->deleteToken('token_test');

        $this->assertFalse($resultado);
    }
}
