<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/RolesModel.php';





class TestRoles extends TestCase
{
    private $model;

    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }

    protected function setUp(): void
    {
        $this->model = new RolesModel();
    }

    

    public function testInsertRolConDatosCompletos()
    {
        $data = [
            'nombre' => 'ROL_TEST_' . time(),
            'descripcion' => 'Rol de prueba para testing',
            'estatus' => 'activo'
        ];

        $result = $this->model->insertRol($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        
        if ($result['status']) {
            $this->assertTrue($result['status']);
            $this->assertArrayHasKey('rol_id', $result);
        }
    }

    public function testSelectAllRolesRetornaArray()
    {
        $result = $this->model->getAllRoles();

        $this->assertIsArray($result);
    }

    public function testSelectRolByIdExistente()
    {
        $roles = $this->model->getAllRoles();
        
        if (empty($roles)) {
            $this->markTestSkipped('No hay roles para probar');
        }

        $idPrueba = $roles[0]['idrol'];
        $rol = $this->model->getRolById($idPrueba);

        $this->assertIsArray($rol);
        $this->assertEquals($idPrueba, $rol['idrol']);
    }

    public function testAsignarPermisoARol()
    {
        $data = [
            'idrol' => 1,
            'idmodulo' => 1,
            'puede_leer' => 1,
            'puede_escribir' => 1,
            'puede_actualizar' => 1,
            'puede_eliminar' => 0
        ];

        $result = $this->model->asignarPermiso($data);

        $this->assertIsBool($result);
    }

    public function testObtenerPermisosPorRol()
    {
        $result = $this->model->getPermisosByRol(1);

        $this->assertIsArray($result);
    }

    

    public function testInsertRolSinNombre()
    {
        $data = [
            'nombre' => '',
            'descripcion' => 'Sin nombre',
            'estatus' => 'activo'
        ];

        $result = $this->model->insertRol($data);

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
    }

    public function testInsertRolDuplicado()
    {
        $nombreUnico = 'ROL_DUPLICADO_' . time();
        
        $data = [
            'nombre' => $nombreUnico,
            'descripcion' => 'Primera inserción',
            'estatus' => 'activo'
        ];

        $result1 = $this->model->insertRol($data);
        $result2 = $this->model->insertRol($data);

        $this->assertIsArray($result2);
        $this->assertFalse($result2['status']);
    }

    public function testSelectRolByIdInexistente()
    {
        $rol = $this->model->getRolById(99999);

        $this->assertFalse($rol);
    }

    public function testAsignarPermisoConRolInexistente()
    {
        $data = [
            'idrol' => 99999,
            'idmodulo' => 1,
            'puede_leer' => 1,
            'puede_escribir' => 0,
            'puede_actualizar' => 0,
            'puede_eliminar' => 0
        ];

        $result = $this->model->asignarPermiso($data);

        $this->assertFalse($result);
    }

    public function testObtenerPermisosPorRolInexistente()
    {
        $result = $this->model->getPermisosByRol(99999);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
