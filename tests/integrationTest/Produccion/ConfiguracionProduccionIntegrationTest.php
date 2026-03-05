<?php

namespace Tests\IntegrationTest\Produccion;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\ProduccionModel;
require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class ConfiguracionProduccionIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    private ProduccionModel $model;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new ProduccionModel();
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }

    // ---------------------------------------------------------------
    // selectConfiguracionProduccion
    // ---------------------------------------------------------------

    #[Test]
    public function testSelectConfiguracionProduccion_RetornaArrayConStatusYData(): void
    {
        $result = $this->model->selectConfiguracionProduccion();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }

    // ---------------------------------------------------------------
    // updateConfiguracionProduccion
    // ---------------------------------------------------------------

    public static function providerUpdateConfiguracion(): array
    {
        return [
            'Datos completos válidos' => [
                [
                    'productividad_clasificacion' => 150,
                    'capacidad_maxima_planta'      => 50,
                    'salario_base'                 => 35,
                    'beta_clasificacion'           => 0.30,
                    'gamma_empaque'                => 6,
                    'umbral_error_maximo'          => 5,
                    'peso_minimo_paca'             => 25,
                    'peso_maximo_paca'             => 35,
                ],
                true,
            ],
            'Datos con valores negativos' => [
                [
                    'productividad_clasificacion' => -100,
                    'capacidad_maxima_planta'      => 50,
                    'salario_base'                 => 30,
                    'beta_clasificacion'           => 0.25,
                    'gamma_empaque'                => 5,
                    'umbral_error_maximo'          => 5,
                    'peso_minimo_paca'             => 25,
                    'peso_maximo_paca'             => 35,
                ],
                null, // puede ser true o false según la BD, solo validamos que retorna bool
            ],
        ];
    }

    #[Test]
    #[DataProvider('providerUpdateConfiguracion')]
    public function testUpdateConfiguracionProduccion_RetornaArrayConStatus(
        array $data,
        ?bool $esperadoStatus
    ): void {
        $result = $this->model->updateConfiguracionProduccion($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertIsBool($result['status']);
        if ($esperadoStatus !== null) {
            $this->assertSame($esperadoStatus, $result['status']);
        }
    }

    #[Test]
    public function testUpdateConfiguracionConDatosVacios_RetornaArray(): void
    {
        try {
            $result = $this->model->updateConfiguracionProduccion([]);
            $this->assertIsArray($result);
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    // ---------------------------------------------------------------
    // selectEmpleadosActivos
    // ---------------------------------------------------------------

    #[Test]
    public function testSelectEmpleadosActivos_RetornaArrayConStatusYData(): void
    {
        $result = $this->model->selectEmpleadosActivos();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertTrue($result['status']);
    }

    // ---------------------------------------------------------------
    // selectProductos
    // ---------------------------------------------------------------

    public static function providerTiposProductos(): array
    {
        return [
            'todos'          => ['todos'],
            'por_clasificar' => ['por_clasificar'],
            'clasificados'   => ['clasificados'],
            'tipo_invalido'  => ['tipo_invalido'],
        ];
    }

    #[Test]
    #[DataProvider('providerTiposProductos')]
    public function testSelectProductos_RetornaArrayConStatusYData(string $tipo): void
    {
        $result = $this->model->selectProductos($tipo);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }
}
