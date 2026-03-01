<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use App\Models\categoriasModel;

#[CoversClass(categoriasModel::class)]
#[Group('unit')]
class TestCategoriaModelUnit extends TestCase
{
    private categoriasModel $model;
    private PDO $mockPdo;
    private PDOStatement $mockStmt;

    protected function setUp(): void
    {
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockStmt = $this->createMock(PDOStatement::class);

        $this->model = $this->getMockBuilder(categoriasModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $ref = new ReflectionClass(categoriasModel::class);
        $dbProp = $ref->getProperty('db');
        $dbProp->setAccessible(true);
        $dbProp->setValue($this->model, $this->mockPdo);
    }

    #[Test]
    public function testSelectAllCategoriasRetornaDatosEnExito(): void
    {
        $expected = [
            ['idcategoria' => 1, 'nombre' => 'Cartón', 'descripcion' => 'Reciclable', 'estatus' => 'ACTIVO'],
            ['idcategoria' => 2, 'nombre' => 'Plástico', 'descripcion' => 'PET', 'estatus' => 'ACTIVO'],
        ];

        $this->mockPdo->expects($this->once())
            ->method('query')
            ->with($this->stringContains('SELECT * FROM categoria'))
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expected);

        $result = $this->model->SelectAllCategorias();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Cartón', $result[0]['nombre']);
        $this->assertEquals('Plástico', $result[1]['nombre']);
    }

    #[Test]
    public function testSelectAllCategoriasRetornaArrayVacioCuandoNoHayDatos(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('query')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([]);

        $result = $this->model->SelectAllCategorias();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function testSelectAllCategoriasRetornaArrayVacioEnExcepcion(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('query')
            ->willThrowException(new PDOException('Connection lost'));

        $result = $this->model->SelectAllCategorias();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    #[DataProvider('datosValidosInsertProvider')]
    public function testInsertCategoriaConDatosValidos(
        array $data,
        string $estatusEsperado
    ): void {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO categoria'))
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) use ($data, $estatusEsperado) {
                return $params[0] === $data['nombre']
                    && $params[1] === $data['descripcion']
                    && $params[2] === $estatusEsperado;
            }))
            ->willReturn(true);

        $result = $this->model->insertCategoria($data);
        $this->assertTrue($result);
    }

    public static function datosValidosInsertProvider(): array
    {
        return [
            'estatus_minusculas' => [
                ['nombre' => 'Cartón', 'descripcion' => 'Material reciclable', 'estatus' => 'activo'],
                'ACTIVO',
            ],
            'estatus_mayusculas' => [
                ['nombre' => 'Plástico', 'descripcion' => 'PET reciclado', 'estatus' => 'ACTIVO'],
                'ACTIVO',
            ],
            'estatus_mixto' => [
                ['nombre' => 'Metal', 'descripcion' => 'Aluminio', 'estatus' => 'Activo'],
                'ACTIVO',
            ],
            'estatus_inactivo' => [
                ['nombre' => 'Madera', 'descripcion' => 'Material orgánico', 'estatus' => 'inactivo'],
                'INACTIVO',
            ],
            'descripcion_larga' => [
                ['nombre' => 'Vidrio', 'descripcion' => str_repeat('Descripción extensa ', 25), 'estatus' => 'activo'],
                'ACTIVO',
            ],
            'caracteres_especiales' => [
                ['nombre' => 'Categoría Ñ & Ü', 'descripcion' => 'Desc especial', 'estatus' => 'activo'],
                'ACTIVO',
            ],
        ];
    }

    #[Test]
    public function testInsertCategoriaRetornaFalseCuandoExecuteFalla(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->willReturn(false);

        $data = ['nombre' => 'Test', 'descripcion' => 'Desc', 'estatus' => 'activo'];
        $result = $this->model->insertCategoria($data);

        $this->assertFalse($result);
    }

    #[Test]
    public function testInsertCategoriaPropagaExcepcionPDO(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Duplicate entry'));

        $data = ['nombre' => 'Test', 'descripcion' => 'Desc', 'estatus' => 'activo'];

        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Duplicate entry');
        $this->model->insertCategoria($data);
    }

    #[Test]
    #[DataProvider('datosIncompletosInsertProvider')]
    public function testInsertCategoriaConDatosIncompletos(array $data): void
    {
        try {
            $result = @$this->model->insertCategoria($data);
            if ($result !== null) {
                $this->assertNotTrue($result, 'No debería insertarse con datos incompletos');
            }
        } catch (\Error | \TypeError | \Exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public static function datosIncompletosInsertProvider(): array
    {
        return [
            'sin_clave_nombre' => [['descripcion' => 'Sin nombre', 'estatus' => 'activo']],
            'sin_clave_descripcion' => [['nombre' => 'Test', 'estatus' => 'activo']],
            'sin_clave_estatus' => [['nombre' => 'Test', 'descripcion' => 'Desc']],
            'array_completamente_vacio' => [[]],
        ];
    }

    #[Test]
    #[DataProvider('deleteResultadosProvider')]
    public function testDeleteCategoriaConDiferentesResultados(
        int $id,
        bool $executeReturn,
        bool $expected
    ): void {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains("estatus = 'INACTIVO'"))
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->with([$id])
            ->willReturn($executeReturn);

        $result = $this->model->deleteCategoria($id);
        $this->assertEquals($expected, $result);
    }

    public static function deleteResultadosProvider(): array
    {
        return [
            'delete_exitoso' => [1, true, true],
            'delete_id_inexistente_execute_true' => [99999, true, true],
            'delete_execute_falla' => [1, false, false],
        ];
    }

    #[Test]
    public function testDeleteCategoriaRetornaFalseEnExcepcion(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Database locked'));

        $result = $this->model->deleteCategoria(1);

        $this->assertFalse($result);
    }

    #[Test]
    #[DataProvider('datosUpdateProvider')]
    public function testUpdateCategoriaConDiferentesEscenarios(
        array $data,
        bool $executeReturn,
        bool $expected
    ): void {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('UPDATE categoria SET'))
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->with([$data['nombre'], $data['descripcion'], $data['estatus'], $data['idcategoria']])
            ->willReturn($executeReturn);

        $result = $this->model->updateCategoria($data);
        $this->assertEquals($expected, $result);
    }

    public static function datosUpdateProvider(): array
    {
        return [
            'update_exitoso_datos_completos' => [
                ['idcategoria' => 1, 'nombre' => 'Cartón Actualizado', 'descripcion' => 'Nueva desc', 'estatus' => 'ACTIVO'],
                true, true,
            ],
            'update_sin_cambios_reales' => [
                ['idcategoria' => 99, 'nombre' => 'Sin Cambios', 'descripcion' => 'Igual', 'estatus' => 'ACTIVO'],
                true, true,
            ],
            'update_execute_falla' => [
                ['idcategoria' => 1, 'nombre' => 'Fail', 'descripcion' => 'D', 'estatus' => 'ACTIVO'],
                false, false,
            ],
            'update_cambio_estatus_inactivo' => [
                ['idcategoria' => 5, 'nombre' => 'Cat', 'descripcion' => 'D', 'estatus' => 'INACTIVO'],
                true, true,
            ],
        ];
    }

    #[Test]
    public function testUpdateCategoriaRetornaFalseEnExcepcion(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Constraint violation'));

        $data = ['idcategoria' => 1, 'nombre' => 'T', 'descripcion' => 'D', 'estatus' => 'ACTIVO'];
        $result = $this->model->updateCategoria($data);

        $this->assertFalse($result);
    }

    #[Test]
    #[DataProvider('datosIncompletosUpdateProvider')]
    public function testUpdateCategoriaConDatosIncompletos(array $data): void
    {
        try {
            $result = @$this->model->updateCategoria($data);
            $this->assertNotTrue($result, 'No debería actualizar con datos incompletos');
        } catch (\Error | \TypeError | \Exception | \PDOException $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public static function datosIncompletosUpdateProvider(): array
    {
        return [
            'sin_idcategoria' => [['nombre' => 'X', 'descripcion' => 'Y', 'estatus' => 'ACTIVO']],
            'sin_nombre' => [['idcategoria' => 1, 'descripcion' => 'Y', 'estatus' => 'ACTIVO']],
            'array_vacio' => [[]],
        ];
    }

    #[Test]
    public function testGetCategoriaByIdRetornaDatosYSeteaPropiedades(): void
    {
        $expected = [
            'idcategoria' => 5,
            'nombre' => 'Metal',
            'descripcion' => 'Aluminio reciclado',
            'estatus' => 'ACTIVO',
        ];

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT * FROM categoria WHERE idcategoria'))
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->with([5]);

        $this->mockStmt->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expected);

        $result = $this->model->getCategoriaById(5);

        $this->assertIsArray($result);
        $this->assertEquals(5, $result['idcategoria']);
        $this->assertEquals('Metal', $result['nombre']);

        $this->assertEquals(5, $this->model->getIdcategoria());
        $this->assertEquals('Metal', $this->model->getNombre());
        $this->assertEquals('Aluminio reciclado', $this->model->getDescripcion());
        $this->assertEquals('ACTIVO', $this->model->getEstatus());
    }

    #[Test]
    public function testGetCategoriaByIdRetornaFalseCuandoNoExiste(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->method('execute');

        $this->mockStmt->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $result = $this->model->getCategoriaById(99999);

        $this->assertFalse($result);
    }

    #[Test]
    public function testGetCategoriaByIdRetornaNullEnExcepcion(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Table not found'));

        $result = $this->model->getCategoriaById(1);

        $this->assertNull($result);
    }

    #[Test]
    #[DataProvider('idsEdgeCaseProvider')]
    public function testGetCategoriaByIdConIdsLimite(
        int $id,
        mixed $fetchReturn,
        string $expectedType
    ): void {
        $this->mockPdo->method('prepare')->willReturn($this->mockStmt);
        $this->mockStmt->method('execute');
        $this->mockStmt->method('fetch')->willReturn($fetchReturn);

        $result = $this->model->getCategoriaById($id);

        match ($expectedType) {
            'array' => $this->assertIsArray($result),
            'false' => $this->assertFalse($result),
        };
    }

    public static function idsEdgeCaseProvider(): array
    {
        return [
            'id_1_encontrado' => [
                1,
                ['idcategoria' => 1, 'nombre' => 'Primera', 'descripcion' => 'D', 'estatus' => 'ACTIVO'],
                'array',
            ],
            'id_maximo_no_encontrado' => [PHP_INT_MAX, false, 'false'],
            'id_grande_no_encontrado' => [999999, false, 'false'],
        ];
    }

    #[Test]
    public function testReactivarCategoriaExitoso(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains("estatus = 'ACTIVO'"))
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->with([10])
            ->willReturn(true);

        $result = $this->model->reactivarCategoria(10);
        $this->assertTrue($result);
    }

    #[Test]
    public function testReactivarCategoriaExecuteRetornaFalse(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->willReturn(false);

        $result = $this->model->reactivarCategoria(99999);
        $this->assertFalse($result);
    }

    #[Test]
    public function testReactivarCategoriaPropagaExcepcion(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Connection refused'));

        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Connection refused');
        $this->model->reactivarCategoria(1);
    }

    #[Test]
    public function testInsertCategoriaVerificaParametrosSQLCorrectos(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->callback(function (string $sql) {
                return str_contains($sql, 'nombre')
                    && str_contains($sql, 'descripcion')
                    && str_contains($sql, 'estatus')
                    && substr_count($sql, '?') === 3;
            }))
            ->willReturn($this->mockStmt);

        $this->mockStmt->method('execute')->willReturn(true);

        $data = ['nombre' => 'Test', 'descripcion' => 'Desc', 'estatus' => 'activo'];
        $this->model->insertCategoria($data);
    }

    #[Test]
    public function testDeleteCategoriaVerificaEsSoftDelete(): void
    {
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->callback(function (string $sql) {
                $sqlUpper = strtoupper($sql);
                return str_contains($sqlUpper, 'UPDATE')
                    && !str_contains($sqlUpper, 'DELETE FROM')
                    && str_contains($sqlUpper, 'INACTIVO');
            }))
            ->willReturn($this->mockStmt);

        $this->mockStmt->method('execute')->willReturn(true);

        $this->model->deleteCategoria(1);
    }

    protected function tearDown(): void
    {
        unset($this->model, $this->mockPdo, $this->mockStmt);
    }
}

