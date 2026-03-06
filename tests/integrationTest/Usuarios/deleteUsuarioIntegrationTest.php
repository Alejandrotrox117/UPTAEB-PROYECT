<?php

namespace Tests\IntegrationTest\Usuarios;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\UsuariosModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class deleteUsuarioIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private UsuariosModel $usuariosModel;

    public function setUp(): void
    {
        $this->requireDatabase();
        $this->usuariosModel = new UsuariosModel();
    }

    protected function tearDown(): void
    {
        unset($this->usuariosModel);
    }

    // ─── Data Providers ──────────────────────────────────────────────────────

    public static function providerIdsInexistentes(): array
    {
        return [
            'id_muy_alto'    => [99999999],
            'id_otro_alto'   => [88888888],
        ];
    }

    // ─── Tests ───────────────────────────────────────────────────────────────

    #[Test]
    public function testDeleteUsuarioById_UsuarioCreado_RetornaTrue(): void
    {
        $unicidad = (string) time() . rand(1000, 9999);
        $insert   = $this->usuariosModel->insertUsuario([
            'personaId' => null,
            'usuario'   => 'del_usr_' . $unicidad,
            'correo'    => 'del_' . $unicidad . '@test.com',
            'clave'     => 'Password123!',
            'idrol'     => 2,
        ]);

        if (!$insert['status']) {
            $this->markTestSkipped('No se pudo crear el usuario temporal para eliminar.');
        }

        $idCreado = (int) $insert['usuario_id'];
        $resultado = $this->usuariosModel->deleteUsuarioById($idCreado, 0);

        $this->assertTrue($resultado);
    }

    #[Test]
    public function testDeleteUsuarioById_UsuarioYaEliminado_RetornaFalse(): void
    {
        // Crear, eliminar, luego intentar eliminar nuevamente
        $unicidad = (string) time() . rand(1000, 9999);
        $insert   = $this->usuariosModel->insertUsuario([
            'personaId' => null,
            'usuario'   => 'del2_usr_' . $unicidad,
            'correo'    => 'del2_' . $unicidad . '@test.com',
            'clave'     => 'Password123!',
            'idrol'     => 2,
        ]);

        if (!$insert['status']) {
            $this->markTestSkipped('No se pudo crear el usuario temporal para el test de doble eliminación.');
        }

        $idCreado = (int) $insert['usuario_id'];

        $primera   = $this->usuariosModel->deleteUsuarioById($idCreado, 0);
        $this->assertTrue($primera, 'Primera eliminación debió retornar true.');

        // El usuario ya está INACTIVO, la segunda eliminación no debe afectar filas
        $segunda = $this->usuariosModel->deleteUsuarioById($idCreado, 0);
        $this->assertFalse($segunda);
    }

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testDeleteUsuarioById_IdInexistente_RetornaFalse(int $id): void
    {
        $resultado = $this->usuariosModel->deleteUsuarioById($id, 0);

        $this->assertFalse($resultado);
    }

    #[Test]
    public function testDeleteUsuarioById_SuperUsuario_RetornaFalse(): void
    {
        // El ID 1 corresponde al superusuario (idrol = 1) en la BD de prueba
        // El modelo prohíbe eliminar superusuarios
        $resultado = $this->usuariosModel->deleteUsuarioById(1, 0);

        // Si el ID 1 existe y es superusuario → false; si no existe → false
        $this->assertFalse($resultado);
    }
}
