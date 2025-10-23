<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/bitacoraModel.php';

/**
 * RF10: Prueba de caja blanca para el módulo de bitácora
 * Incluye casos típicos (exitosos) y atípicos (fallidos)
 */
class TestBitacora extends TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new BitacoraModel();
    }

    // ========== CASOS TÍPICOS (EXITOSOS) ==========

    public function testRegistrarAccionEnBitacora()
    {
        $data = [
            'accion' => 'INSERT',
            'modulo' => 'PRODUCTOS',
            'descripcion' => 'Registro de producto de prueba',
            'idusuario' => 1
        ];

        // Registrar acción -> el modelo devuelve el ID insertado o false
        $result = $this->model->registrarAccion('PRODUCTOS', 'INSERT', 1, 'Registro de producto de prueba');

    $this->assertNotFalse($result);
    $this->assertGreaterThan(0, (int)$result);
    }

    public function testRegistrarAccionUpdate()
    {
        $result = $this->model->registrarAccion('CLIENTES', 'UPDATE', 1, 'Actualización de datos del cliente');

    $this->assertNotFalse($result);
    $this->assertGreaterThan(0, (int)$result);
    }

    public function testRegistrarAccionDelete()
    {
        $result = $this->model->registrarAccion('CATEGORIAS', 'DELETE', 1, 'Eliminación lógica de categoría');

    $this->assertNotFalse($result);
    $this->assertGreaterThan(0, (int)$result);
    }

    public function testConsultarBitacoraRetornaArray()
    {
        $result = $this->model->SelectAllBitacora();

        $this->assertIsArray($result);
    }

    public function testConsultarBitacoraPorModulo()
    {
        $result = $this->model->obtenerHistorial(['tabla' => 'productos']);

        $this->assertIsArray($result);
    }

    public function testConsultarBitacoraPorUsuario()
    {
        $result = $this->model->obtenerHistorial(['idusuario' => 1]);

        $this->assertIsArray($result);
    }

    public function testConsultarBitacoraPorFechas()
    {
        $fechaInicio = date('Y-m-01');
        $fechaFin = date('Y-m-d');
        $result = $this->model->obtenerHistorial([
            'fecha_desde' => $fechaInicio,
            'fecha_hasta' => $fechaFin
        ]);

        $this->assertIsArray($result);
    }

    // ========== CASOS ATÍPICOS (FALLIDOS) ==========

    public function testRegistrarAccionSinUsuario()
    {
        // Llamada sin ID de usuario debe fallar
        $result = $this->model->registrarAccion('PRODUCTOS', 'INSERT', null, 'Sin usuario');

        $this->assertFalse($result);
    }

    public function testRegistrarAccionSinModulo()
    {
        // Llamada sin módulo debe fallar
        $result = $this->model->registrarAccion('', 'INSERT', 1, 'Sin módulo');

        $this->assertFalse($result);
    }

    public function testConsultarBitacoraPorUsuarioInexistente()
    {
        $result = $this->model->obtenerHistorial(['idusuario' => 99999]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testConsultarBitacoraPorModuloInexistente()
    {
        $result = $this->model->obtenerHistorial(['tabla' => 'modulo_inexistente']);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }
}
