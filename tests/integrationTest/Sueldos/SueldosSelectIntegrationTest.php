<?php

namespace Tests\IntegrationTest\Sueldos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\SueldosModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

use Tests\Traits\RequiresDatabase;

class SueldosSelectIntegrationTest extends TestCase
{
    use RequiresDatabase;

    private SueldosModel $model;
    private \PDO $db;

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->model = new SueldosModel();

        $conexion = new \App\Core\Conexion();
        $conexion->connect();
        $this->db = $conexion->get_conectGeneral();
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }

    private function preparaSueldoParaSelectYObtenId(): int
    {
        $stmt = $this->db->prepare("SELECT idsueldo FROM sueldos WHERE observacion = 'Pago prueba get int' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row) {
            return (int) $row['idsueldo'];
        }

        $stmtInsert = $this->db->prepare(
            "INSERT INTO sueldos (idempleado, monto, balance, idmoneda, observacion, estatus, fecha_creacion, fecha_modificacion) 
            VALUES (1, 500.00, 500.00, 1, 'Pago prueba get int', 'POR_PAGAR', NOW(), NOW())"
        );
        $stmtInsert->execute();
        return (int) $this->db->lastInsertId();
    }

    #[Test]
    public function testSelectSueldoById_Exitosa_Encontrado(): void
    {
        $idsueldo = $this->preparaSueldoParaSelectYObtenId();

        $result = $this->model->selectSueldoById($idsueldo);

        $this->assertIsArray($result);
        $this->assertEquals($idsueldo, $result['idsueldo']);
        $this->assertEquals('Pago prueba get int', $result['observacion']);
    }

    #[Test]
    public function testSelectSueldoById_Falla_NoEncontrado(): void
    {
        // Enviar un ID que no exista (uno muy alto o invalido)
        $result = $this->model->selectSueldoById(9999999);

        // El modelo devuelve un var false (boolean) si es vacío
        $this->assertFalse($result);
    }

    #[Test]
    public function testSelectAllSueldos_Exitosa(): void
    {
        $this->preparaSueldoParaSelectYObtenId();

        $result = $this->model->selectAllSueldos(1);

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
        $this->assertIsArray($result['data']);
        $this->assertGreaterThanOrEqual(1, count($result['data']));
    }
}
