<?php
namespace App\Models;

use App\Core\Conexion;
use PDO;
use PDOException;

class RomanaModel
{
    private $conexionObjeto;
    private $query;

    public function setQuery(string $query): void
    {
        $this->query = $query;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function __construct()
    {
        $this->conexionObjeto = new Conexion();
        $this->conexionObjeto->connect();
    }

    public function __destruct()
    {
        if ($this->conexionObjeto) {
            $this->conexionObjeto->disconnect();
        }
    }

    public function selectAllRomana(): array
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $this->setQuery("SELECT idromana, peso, fecha, estatus, fecha_creacion FROM historial_romana ORDER BY idromana DESC");
            $stmt = $db->prepare($this->getQuery());
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'status' => true,
                'data'   => $result,
            ];
        } catch (PDOException $e) {
            error_log("RomanaModel::selectAllRomana - Error: " . $e->getMessage());
            return [
                'status'  => false,
                'data'    => [],
                'message' => 'Error al obtener los registros',
            ];
        } finally {
            $conexion->disconnect();
        }
    }
}
