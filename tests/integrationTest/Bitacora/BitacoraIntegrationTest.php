<?php
declare(strict_types=1);

namespace Tests\IntegrationTest\Bitacora;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\BitacoraModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

/**
 * Pruebas de Integración — BitacoraModel
 *
 * Interactúa directamente con bd_pda_seguridad_test.
 * Si la base de datos no está disponible, todos los tests se marcan SKIPPED.
 */
class BitacoraIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private BitacoraModel $model;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new BitacoraModel();
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }

    // ---------------------------------------------------------------
    // DataProviders
    // ---------------------------------------------------------------

    public static function providerRegistrarAccionExitosa(): array
    {
        return [
            'insert_productos' => ['PRODUCTOS', 'INSERT',  1, 'Integración INSERT productos',  null],
            'update_ventas'    => ['VENTAS',    'UPDATE',  1, 'Integración UPDATE ventas',       1],
            'delete_compras'   => ['COMPRAS',   'DELETE',  1, 'Integración DELETE compras',      5],
        ];
    }

    public static function providerRegistrarAccionFallida(): array
    {
        return [
            'sin_usuario'  => ['PRODUCTOS', 'INSERT', null, 'Sin usuario'],
            'sin_modulo'   => ['',           'INSERT', 1,    'Sin módulo'],
            'sin_accion'   => ['PRODUCTOS', '',        1,    'Sin acción'],
            'id_negativo'  => ['PRODUCTOS', 'INSERT', -1,   'ID usuario negativo'],
        ];
    }

    public static function providerFiltrosHistorial(): array
    {
        return [
            'sin_filtros'   => [[]],
            'por_modulo'    => [['tabla' => 'PRODUCTOS']],
        ];
    }

    public static function providerIdsInexistentes(): array
    {
        return [
            'id_cero'     => [0],
            'id_negativo' => [-1],
            'id_muy_alto' => [999999],
        ];
    }

    // ---------------------------------------------------------------
    // registrarAccion
    // ---------------------------------------------------------------

    #[Test]
    #[DataProvider('providerRegistrarAccionExitosa')]
    public function testRegistrarAccion_ConDatosValidos_RetornaIdNumerico(
        string $tabla,
        string $accion,
        ?int $idusuario,
        string $detalle,
        ?int $idRegistro
    ): void {
        $result = $this->model->registrarAccion($tabla, $accion, $idusuario, $detalle, $idRegistro);

        $this->assertNotFalse($result, 'registrarAccion debe devolver un ID, no false.');
        $this->assertIsNumeric($result, 'El ID retornado debe ser numérico.');
        $this->assertGreaterThan(0, (int)$result, 'El ID insertado debe ser mayor que cero.');
    }

    #[Test]
    #[DataProvider('providerRegistrarAccionFallida')]
    public function testRegistrarAccion_ConDatosInvalidos_RetornaFalse(
        string $tabla,
        string $accion,
        ?int $idusuario,
        string $detalle
    ): void {
        $result = $this->model->registrarAccion($tabla, $accion, $idusuario, $detalle);

        $this->assertFalse($result, "registrarAccion debe retornar false con datos inválidos.");
    }

    // ---------------------------------------------------------------
    // SelectAllBitacora
    // ---------------------------------------------------------------

    #[Test]
    public function testSelectAllBitacora_RetornaArray(): void
    {
        $result = $this->model->SelectAllBitacora();

        $this->assertIsArray($result);

        if (!empty($result)) {
            $this->assertArrayHasKey('idbitacora',     $result[0]);
            $this->assertArrayHasKey('tabla',          $result[0]);
            $this->assertArrayHasKey('accion',         $result[0]);
            $this->assertArrayHasKey('idusuario',      $result[0]);
            $this->assertArrayHasKey('nombre_usuario', $result[0]);
            $this->assertArrayHasKey('fecha',          $result[0]);
        }
    }

    // ---------------------------------------------------------------
    // obtenerHistorial
    // ---------------------------------------------------------------

    #[Test]
    #[DataProvider('providerFiltrosHistorial')]
    public function testObtenerHistorial_ConFiltros_RetornaArray(array $filtros): void
    {
        $result = $this->model->obtenerHistorial($filtros);

        $this->assertIsArray($result);
    }

    #[Test]
    public function testObtenerHistorial_ConModuloInexistente_RetornaVacio(): void
    {
        $result = $this->model->obtenerHistorial(['tabla' => 'MODULO_FAKE_XYZ_9999']);

        $this->assertIsArray($result);
        $this->assertEmpty($result, 'No debe haber registros para un módulo inventado.');
    }

    // ---------------------------------------------------------------
    // obtenerRegistroPorId
    // ---------------------------------------------------------------

    #[Test]
    #[DataProvider('providerIdsInexistentes')]
    public function testObtenerRegistroPorId_ConIdInexistente_RetornaFalse(int $id): void
    {
        $result = $this->model->obtenerRegistroPorId($id);

        $this->assertFalse($result, "obtenerRegistroPorId({$id}) debe retornar false para ID inexistente.");
    }
}
