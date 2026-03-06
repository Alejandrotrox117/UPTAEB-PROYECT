<?php

namespace Tests\IntegrationTest\Sueldos;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\SueldosModel;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

use Tests\Traits\RequiresDatabase;

class SueldosDeleteIntegrationTest extends TestCase
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

    private function preparaSueldoParaBorrarYObtenId(): int
    {
        // Creates a fresh record that can be safely deleted logically without test pollution
        $stmtInsert = $this->db->prepare(
            "INSERT INTO sueldos (idempleado, monto, balance, idmoneda, observacion, estatus, fecha_creacion, fecha_modificacion) 
            VALUES (1, 1500.00, 1500.00, 1, 'Pago que se borrara unit', 'POR_PAGAR', NOW(), NOW())"
        );
        $stmtInsert->execute();
        return (int) $this->db->lastInsertId();
    }

    #[Test]
    public function testDeleteSueldo_Exitosa_BorradoLogico(): void
    {
        $idsueldo = $this->preparaSueldoParaBorrarYObtenId();

        $result = $this->model->deleteSueldo($idsueldo);

        $this->assertIsBool($result);
        $this->assertTrue($result);

        // Assert it changed to INACTIVO via select query 
        // We query manually to confirm real DB update
        $stmt = $this->db->prepare("SELECT estatus FROM sueldos WHERE idsueldo = ?");
        $stmt->execute([$idsueldo]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertEquals('INACTIVO', $row['estatus']);
    }

    #[Test]
    public function testDeleteSueldo_Falla_NoExistente(): void
    {
        // Enviar un ID que no exista (uno muy alto o invalido), la DB processa rapido la busqueda index y reportará 0 afectaciones
        $result = $this->model->deleteSueldo(9999999);

        // El modelo devuelve false (boolean) si no hay cambios en filas
        $this->assertIsBool($result);
        $this->assertFalse($result);
    }
}
