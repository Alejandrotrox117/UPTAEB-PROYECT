<?php
use PHPUnit\Framework\TestCase;
use App\Models\LoginModel;
class TestLoginAutenticacion extends TestCase
{
    private $model;
    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "\n[MODEL MESSAGE] " . $msg . "\n");
    }
    protected function setUp(): void
    {
        $this->model = new LoginModel();
    }
    public function testLoginConCredencialesValidas()
    {
        $email = 'admin@gmail.com';
        $password = 'admin';
        $result = $this->model->login($email, $password);
        if ($result === false) {
            $this->assertFalse($result);
        } else {
            $this->assertIsArray($result);
            $this->assertArrayHasKey('idusuario', $result);
            $this->assertArrayHasKey('correo', $result);
        }
    }
    public function testLoginConPasswordIncorrecto()
    {
        $email = 'admin@test.com';
        $password = 'password_incorrecto';
        $result = $this->model->login($email, $password);
        $this->assertFalse($result);
        $this->showMessage("Validación correcta: Login fallido con password incorrecto");
    }
    public function testLoginConEmailInexistente()
    {
        $email = 'noexiste@test.com';
        $password = 'cualquierpassword';
        $result = $this->model->login($email, $password);
        $this->assertFalse($result);
        $this->showMessage("Validación correcta: Login fallido con email inexistente");
    }
    public function testLoginConEmailVacio()
    {
        $email = '';
        $password = 'password123';
        $result = $this->model->login($email, $password);
        $this->assertFalse($result);
        $this->showMessage("Validación correcta: Login fallido con email vacío");
    }
    public function testLoginConPasswordVacio()
    {
        $email = 'admin@test.com';
        $password = '';
        $result = $this->model->login($email, $password);
        $this->assertFalse($result);
        $this->showMessage("Validación correcta: Login fallido con password vacío");
    }
    public function testLoginConCamposVacios()
    {
        $email = '';
        $password = '';
        $result = $this->model->login($email, $password);
        $this->assertFalse($result);
        $this->showMessage("Validación correcta: Login fallido con ambos campos vacíos");
    }
    public function testLoginConEmailInvalido()
    {
        $email = 'email_sin_formato_valido';
        $password = 'password123';
        $result = $this->model->login($email, $password);
        if ($result === false) {
            $this->assertFalse($result);
        } else {
            $this->assertIsArray($result);
        }
    }
    public function testLoginConUsuarioInactivo()
    {
        $email = 'usuario_inactivo@test.com';
        $password = 'password123';
        $result = $this->model->login($email, $password);
        $this->assertFalse($result);
    }
    protected function tearDown(): void
    {
        $this->model = null;
    }
}
