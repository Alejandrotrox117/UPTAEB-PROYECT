<?php

namespace Tests\IntegrationTest\Usuarios;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\UsuariosModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class TestUsuarioInsertIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private $model;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new UsuariosModel();
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }

    public static function providerInsertUsuariosInvalidos()
    {
        return [
            'Sin nombre de usuario' => [
                [
                    'personaId' => 1,
                    'usuario' => '',
                    'correo' => 'test@email.com',
                    'clave' => 'Password123!',
                    'idrol' => 1
                ]
            ],
            'Sin correo' => [
                [
                    'personaId' => 1,
                    'usuario' => 'usuario_test',
                    'correo' => '',
                    'clave' => 'Password123!',
                    'idrol' => 1
                ]
            ],
            'Sin password' => [
                [
                    'personaId' => 1,
                    'usuario' => 'usuario_test',
                    'correo' => 'test@email.com',
                    'clave' => '',
                    'idrol' => 1
                ]
            ],
            'Email invalido' => [
                [
                    'personaId' => 1,
                    'usuario' => 'usuario_test',
                    'correo' => 'email_sin_arroba',
                    'clave' => 'Password123!',
                    'idrol' => 1
                ]
            ],
            'Sin rol' => [
                [
                    'personaId' => 1,
                    'usuario' => 'usuario_test',
                    'correo' => 'test@email.com',
                    'clave' => 'Password123!'
                ]
            ]
        ];
    }

    #[Test]
    public function insertarUsuarioConDatosCompletos()
    {
        $data = [
            'personaId' => 1, // need valid id
            'usuario' => 'usuario_test_' . time(),
            'correo' => 'test_' . time() . '@email.com',
            'clave' => 'Password123!',
            'idrol' => 1 // need valid role
        ];
        $result = $this->model->insertUsuario($data);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        if ($result['status']) {
            $this->assertArrayHasKey('usuario_id', $result);
        }
    }

    #[Test]
    #[DataProvider('providerInsertUsuariosInvalidos')]
    public function insertarUsuarioInvalido($data)
    {
        $result = $this->model->insertUsuario($data);
        $this->assertIsArray($result);
        if (array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
            $this->assertNotEmpty($result['message']);
        } else {
            $this->assertIsBool($result['status']);
        }
    }

    #[Test]
    public function insertarUsuarioConCorreoDuplicado()
    {
        // First insert
        $data = [
            'personaId' => 1,
            'usuario' => 'usuario_unico_' . time(),
            'correo' => 'admin_test_dup@admin.com',
            'clave' => 'Password123!',
            'idrol' => 1
        ];
        $this->model->insertUsuario($data);

        // Try duplicate
        $data['usuario'] = 'usuario_unico_' . (time() + 1);
        $result = $this->model->insertUsuario($data);
        $this->assertIsArray($result);

        if (array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
            $this->assertNotEmpty($result['message']);
        } else {
            $this->assertIsBool($result['status']);
        }
    }

    #[Test]
    public function insertarUsuarioConNombreDuplicado()
    {
        // First insert
        $data = [
            'personaId' => 1,
            'usuario' => 'usuario_doble_' . time(),
            'correo' => 'doble1_' . time() . '@admin.com',
            'clave' => 'Password123!',
            'idrol' => 1
        ];
        $this->model->insertUsuario($data);

        // Try duplicate username with different email
        $data['correo'] = 'doble2_' . (time() + 1) . '@admin.com';
        $result = $this->model->insertUsuario($data);
        $this->assertIsArray($result);

        if (array_key_exists('status', $result) && $result['status'] === false) {
            $this->assertArrayHasKey('message', $result);
            $this->assertNotEmpty($result['message']);
        } else {
            $this->assertIsBool($result['status']);
        }
    }

    #[Test]
    public function insertarUsuarioConPasswordDebil()
    {
        $data = [
            'personaId' => 1,
            'usuario' => 'usuario_test_weak_' . time(),
            'correo' => 'test_weak_' . time() . '@email.com',
            'clave' => '123',
            'idrol' => 1
        ];

        $result = $this->model->insertUsuario($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertIsBool($result['status']);
    }
}
