# Documentación de Pruebas Unitarias

## Cuadro Nº 1: Módulo de Login (RF01)

### Objetivos de la prueba

Validar que la autenticación de usuarios y el flujo de recuperación de contraseña solo se ejecuten correctamente cuando los datos de entrada son válidos. El sistema debe rechazar credenciales inexistentes, vacías o incorrectas, tokens inválidos o expirados, e IDs de usuario inexistentes, retornando `false` en todos los casos de error.

### Técnicas

Pruebas de caja blanca con enfoque en aislamiento de dependencias mediante mocks (Mockery). Se evalúan los métodos `login()`, `sessionLogin()`, `getUsuarioEmail()`, `getTokenUserByToken()`, `updatePassword()`, `setTokenUser()` y `deleteToken()` de `LoginModel`, verificando respuestas ante escenarios válidos, inválidos y excepciones PDO simuladas.

### Código Involucrado

```php
<?php
// ── LoginAutenticacionUnitTest.php ──────────────────────────────────────────

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


// ── LoginRecuperacionUnitTest.php ────────────────────────────────────────────

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
```

### Caso de prueba

**TIPO:** Funcional (Caja blanca)

**OBJETIVO:** Verificar que `LoginModel` maneje correctamente la autenticación de usuarios, la carga de datos de sesión y todas las operaciones del flujo de recuperación de contraseña.

**DESCRIPCIÓN:** Se evalúan dos clases de prueba: `LoginAutenticacionUnitTest` cubre `login()` y `sessionLogin()`; `LoginRecuperacionUnitTest` cubre `getUsuarioEmail()`, `getTokenUserByToken()`, `updatePassword()`, `setTokenUser()` y `deleteToken()`. Todos los escenarios usan PDO mockeado para aislar la lógica del modelo.

**ENTRADAS:**

- Credenciales válidas: `admin@gmail.com` / `admin` → filaDB retorna array con `idusuario=1`, `estatus=activo`
- Credenciales inválidas: email inexistente, contraseña incorrecta, ambos vacíos, usuario inactivo (6 casos via DataProvider)
- IDs de sesión: `id=1` (válido), `id=999999`, `id=2147483647`, `id=0` (inexistentes)
- Emails de recuperación: email registrado `admin@test.com`, emails inexistentes/vacíos/sin formato (3 casos)
- Tokens: `valid_token_abc123` (válido), token aleatorio, vacío, expirado (3 casos)
- IDs para actualizar contraseña: `id=1` (válido), `id=0`, `id=-1`, `id=999999` (inválidos)
- Excepción PDO simulada en todos los métodos

**SALIDAS ESPERADAS:**

| Escenario | Resultado esperado |
|---|---|
| Credenciales válidas en `login()` | Array con claves `idusuario`, `correo`, `estatus` |
| Credencial inválida / inexistente en `login()` | `false` |
| ID válido en `sessionLogin()` | Array con claves `idusuario`, `usuario`, `idrol`, `rol_nombre` |
| ID inexistente en `sessionLogin()` | `false` |
| Email existente en `getUsuarioEmail()` | Array con clave `correo` igual al buscado |
| Email inexistente / vacío / malformado | `false` |
| Token válido en `getTokenUserByToken()` | Array con claves `idusuario` y `token` |
| Token inválido / vacío / expirado | `false` |
| `updatePassword()` con ID existente (rowCount=1) | `true` |
| `updatePassword()` con ID inexistente (rowCount=0) | `false` |
| `setTokenUser()` exitoso (rowCount=1) | `true` |
| `deleteToken()` con token existente (rowCount=1) | `true` |
| Cualquier método con excepción PDO | `false` |

### Resultado

```
PHPUnit 10.5.63 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: C:\xampp\htdocs\project\phpunit.xml

..................................                                34 / 34 (100%)

Time: 00:09.815, Memory: 10.00 MB

Login Autenticacion Unit (Tests\UnitTest\Login\LoginAutenticacionUnit)
 ✔ Login CredencialesValidas RetornaArrayConDatos
 ✔ Login CredencialesInvalidas RetornaFalse with email_inexistente
 ✔ Login CredencialesInvalidas RetornaFalse with password_incorrecto
 ✔ Login CredencialesInvalidas RetornaFalse with email_vacio
 ✔ Login CredencialesInvalidas RetornaFalse with password_vacio
 ✔ Login CredencialesInvalidas RetornaFalse with ambos_vacios
 ✔ Login CredencialesInvalidas RetornaFalse with usuario_inactivo
 ✔ Login ExcepcionPDO RetornaFalse
 ✔ SessionLogin IdValido RetornaArrayCompleto
 ✔ SessionLogin IdInexistente RetornaFalse with id_inexistente
 ✔ SessionLogin IdInexistente RetornaFalse with id_muy_alto
 ✔ SessionLogin IdInexistente RetornaFalse with id_cero
 ✔ SessionLogin ExcepcionPDO RetornaFalse

Login Recuperacion Unit (Tests\UnitTest\Login\LoginRecuperacionUnit)
 ✔ GetUsuarioEmail EmailExistente RetornaArray
 ✔ GetUsuarioEmail EmailInexistente RetornaFalse with email_no_registrado
 ✔ GetUsuarioEmail EmailInexistente RetornaFalse with email_vacio
 ✔ GetUsuarioEmail EmailInexistente RetornaFalse with formato_invalido
 ✔ GetUsuarioEmail ExcepcionPDO RetornaFalse
 ✔ GetTokenUserByToken TokenValido RetornaArray
 ✔ GetTokenUserByToken TokenInvalido RetornaFalse with token_aleatorio
 ✔ GetTokenUserByToken TokenInvalido RetornaFalse with token_vacio
 ✔ GetTokenUserByToken TokenInvalido RetornaFalse with token_expirado
 ✔ GetTokenUserByToken ExcepcionPDO RetornaFalse
 ✔ UpdatePassword UsuarioValido RetornaTrue
 ✔ UpdatePassword UsuarioInexistente RetornaFalse with id_cero
 ✔ UpdatePassword UsuarioInexistente RetornaFalse with id_negativo
 ✔ UpdatePassword UsuarioInexistente RetornaFalse with id_inexistente
 ✔ UpdatePassword ExcepcionPDO RetornaFalse
 ✔ SetTokenUser ActualizacionExitosa RetornaTrue
 ✔ SetTokenUser UsuarioInexistente RetornaFalse
 ✔ SetTokenUser ExcepcionPDO RetornaFalse
 ✔ DeleteToken TokenExistente RetornaTrue
 ✔ DeleteToken TokenInexistente RetornaFalse
 ✔ DeleteToken ExcepcionPDO RetornaFalse

OK (34 tests, 50 assertions)
```

### Observaciones

Se ejecutaron 34 pruebas con 50 aserciones en 9.815 segundos, todas con resultado exitoso (100%). El módulo cubre correctamente los flujos de autenticación y recuperación de contraseña con aislamiento completo de la base de datos mediante mocks, incluyendo manejo de excepciones PDO en los 7 métodos evaluados.
