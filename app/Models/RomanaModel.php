<?php
namespace App\Models;

use App\Core\Conexion;
use App\Core\Mysql;
use PDO;
use PDOException;

class RomanaModel extends Mysql
{
    private $query;

    public function setQuery($query)
    {
        $this->query = $query;
    }

    public function getQuery()
    {
        return $this->query;
    }

    // Propiedad para la instancia interna (patrón de doble instancia)
    private $objRomanaModel = null;

    public function __construct()
    {
    }

    /**
     * Obtiene la instancia interna del modelo (Lazy Load - Patrón de doble instancia)
     */
    private function getInstanciaModel(): RomanaModel
    {
        if ($this->objRomanaModel == null) {
            $this->objRomanaModel = new RomanaModel();
        }
        return $this->objRomanaModel;
    }

    // ─── CONSULTAS ────────────────────────────────────────────────────────────

    /**
     * Retorna todos los registros de pesaje en historial_romana.
     */
    public function selectAllRomana(): array
    {
        $objRomanaModel = $this->getInstanciaModel();
        return $objRomanaModel->ejecutarBusquedaTodasRomana();
    }

    private function ejecutarBusquedaTodasRomana(): array
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT idromana, peso, fecha, estatus, fecha_creacion
                 FROM historial_romana ORDER BY idromana DESC"
            );
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'status' => true,
                'data' => $result,
            ];
        } catch (PDOException $e) {
            error_log("RomanaModel::selectAllRomana - Error: " . $e->getMessage());
            return [
                'status' => false,
                'data' => [],
                'message' => 'Error al obtener los registros',
            ];
        } finally {
            $conexion->disconnect();
        }
    }

    /**
     * Retorna un pesaje por su ID, o false si no existe.
     *
     * @param int $id
     * @return array|false
     */
    public function selectPesajeById(int $id)
    {
        $objRomanaModel = $this->getInstanciaModel();
        return $objRomanaModel->ejecutarBusquedaPesajePorId($id);
    }

    private function ejecutarBusquedaPesajePorId(int $id)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT idromana, peso, fecha, estatus, fecha_creacion
                 FROM historial_romana WHERE idromana = :id LIMIT 1"
            );
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($result)) {
                return false;
            }
            return $result[0];
        } catch (PDOException $e) {
            error_log("RomanaModel::selectPesajeById - Error: " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    /**
     * Calcula el peso total de todos los registros.
     *
     * @return float
     */
    public function calcularPesoTotal(): float
    {
        $objRomanaModel = $this->getInstanciaModel();
        return $objRomanaModel->ejecutarCalculoPesoTotal();
    }

    private function ejecutarCalculoPesoTotal(): float
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT SUM(peso) AS total FROM historial_romana"
            );
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return (float) ($result[0]['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("RomanaModel::calcularPesoTotal - Error: " . $e->getMessage());
            return 0.0;
        } finally {
            $conexion->disconnect();
        }
    }

    /**
     * Calcula el promedio de peso de todos los registros.
     *
     * @return float
     */
    public function calcularPromedioPeso(): float
    {
        $objRomanaModel = $this->getInstanciaModel();
        return $objRomanaModel->ejecutarCalculoPromedioPeso();
    }

    private function ejecutarCalculoPromedioPeso(): float
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "SELECT AVG(peso) AS promedio FROM historial_romana"
            );
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return (float) ($result[0]['promedio'] ?? 0);
        } catch (PDOException $e) {
            error_log("RomanaModel::calcularPromedioPeso - Error: " . $e->getMessage());
            return 0.0;
        } finally {
            $conexion->disconnect();
        }
    }

    // ─── ESCRITURA ────────────────────────────────────────────────────────────

    /**
     * Registra un nuevo pesaje en historial_romana.
     *
     * Validaciones:
     *   - peso === null o ausente  → PDOException (campo requerido)
     *   - peso <= 0                → false  (valor inválido)
     *   - peso > 99999.99          → false  (valor excesivo)
     *
     * @param array $data  Claves: peso, fecha_pesaje (opt)
     * @return bool
     * @throws PDOException
     */
    public function insertPesaje(array $data): bool
    {
        $objRomanaModel = $this->getInstanciaModel();
        return $objRomanaModel->ejecutarInsercionPesaje($data);
    }

    private function ejecutarInsercionPesaje(array $data): bool
    {
        // ── Validar presencia y valor de 'peso' ──────────────────────────────
        if (!array_key_exists('peso', $data) || $data['peso'] === null) {
            throw new PDOException("El campo 'peso' es requerido y no puede ser nulo");
        }

        // ── Validar que peso sea positivo ────────────────────────────────────
        if ($data['peso'] <= 0) {
            return false;
        }

        // ── Validar que peso no sea excesivo (> 99999.99 kg) ─────────────────
        if ($data['peso'] > 99999.99) {
            return false;
        }

        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery(
                "INSERT INTO historial_romana (peso, fecha)
                 VALUES (:peso, :fecha)"
            );
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute([
                ':peso' => $data['peso'],
                ':fecha' => $data['fecha_pesaje'] ?? date('Y-m-d H:i:s'),
            ]);

            return true;
        } catch (PDOException $e) {
            error_log("RomanaModel::insertPesaje - Error: " . $e->getMessage());
            throw $e;
        } finally {
            $conexion->disconnect();
        }
    }
}